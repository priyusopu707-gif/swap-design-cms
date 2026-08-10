<?php
/**
 * Swap Design - Email Manager
 *
 * SMTP configuration, template engine, admin notification,
 * user confirmation emails, and email logging.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class EmailManager
{
    private Database $db;
    private SettingsManager $settings;

    private const SMTP_DEFAULTS = [
        'email.smtp_host'       => '',
        'email.smtp_port'       => '587',
        'email.smtp_username'   => '',
        'email.smtp_password'   => '',
        'email.smtp_encryption' => 'tls',
        'email.from_address'    => 'noreply@example.com',
        'email.from_name'       => '',
        'email.admin_email'     => '',
        'email.send_admin'      => '1',
        'email.send_user'       => '1',
    ];

    public function __construct()
    {
        $this->db       = Database::getInstance();
        $this->settings = new SettingsManager();
    }

    /* ================================================================
       SMTP Settings
       ================================================================ */

    public function getSmtpConfig(): array
    {
        $config = [];
        foreach (self::SMTP_DEFAULTS as $key => $default) {
            $short = str_replace('email.', '', $key);
            $config[$short] = $this->settings->get($key, $default);
        }
        return $config;
    }

    public function saveSmtpConfig(array $data): void
    {
        foreach (self::SMTP_DEFAULTS as $key => $default) {
            $short = str_replace('email.', '', $key);
            if (isset($data[$short])) {
                $this->settings->set($key, sanitizeString($data[$short]));
            }
        }
    }

    /* ================================================================
       Templates
       ================================================================ */

    public function getTemplate(string $key): ?array
    {
        return $this->db->fetch("SELECT * FROM email_templates WHERE template_key = ?", [$key]);
    }

    public function getAllTemplates(): array
    {
        return $this->db->fetchAll("SELECT * FROM email_templates ORDER BY name ASC");
    }

    public function updateTemplate(string $key, array $data): void
    {
        $this->db->update('email_templates', [
            'subject'   => $data['subject'] ?? '',
            'body_html' => $data['body_html'] ?? '',
            'body_text' => $data['body_text'] ?? '',
        ], 'template_key = ?', [$key]);
    }

    /* ================================================================
       Variable replacement
       ================================================================ */

    public function renderTemplate(string $body, array $variables): string
    {
        $replace = [];
        foreach ($variables as $k => $v) {
            $replace['{{' . $k . '}}'] = $v;
        }
        return strtr($body, $replace);
    }

    /* ================================================================
       Send email
       ================================================================ */

    public function send(string $to, string $subject, string $htmlBody, string $textBody = '', ?int $leadId = null, string $templateKey = ''): bool
    {
        $smtp = $this->getSmtpConfig();

        if (empty($smtp['smtp_host'])) {
            /* Fallback: PHP mail() */
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: " . ($smtp['from_name'] ?: 'Swap Design') . " <{$smtp['from_address']}>\r\n";

            $sent = mail($to, $subject, $htmlBody, $headers);
        } else {
            $sent = $this->sendSmtp($smtp, $to, $subject, $htmlBody, $textBody);
        }

        $this->log($to, $subject, $sent, $leadId, $templateKey);

        return $sent;
    }

    private function sendSmtp(array $smtp, string $to, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        try {
            $encryption = $smtp['smtp_encryption'] === 'ssl' ? 'ssl' : 'tls';
            $port       = (int)$smtp['smtp_port'];
            $host       = $smtp['smtp_host'];

            $socket = fsockopen(($encryption === 'ssl' ? 'ssl://' : '') . $host, $port, $errno, $errstr, 10);
            if (!$socket) {
                return false;
            }

            $this->smtpCommand($socket, null);

            $helo = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $this->smtpCommand($socket, "EHLO $helo");

            if ($encryption === 'tls') {
                $this->smtpCommand($socket, "STARTTLS");
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $this->smtpCommand($socket, "EHLO $helo");
            }

            if (!empty($smtp['smtp_username'])) {
                $this->smtpCommand($socket, "AUTH LOGIN");
                $this->smtpCommand($socket, base64_encode($smtp['smtp_username']));
                $this->smtpCommand($socket, base64_encode($smtp['smtp_password']));
            }

            $fromAddr = $smtp['from_address'];
            $fromName = $smtp['from_name'] ?: 'Swap Design';
            $this->smtpCommand($socket, "MAIL FROM:<$fromAddr>");
            $this->smtpCommand($socket, "RCPT TO:<$to>");
            $this->smtpCommand($socket, "DATA");

            $boundary = md5(time());
            $message  = "From: $fromName <$fromAddr>\r\n";
            $message .= "To: $to\r\n";
            $message .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $message .= "MIME-Version: 1.0\r\n";
            $message .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n\r\n";
            $message .= "--$boundary\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
            $message .= ($textBody ?: strip_tags($htmlBody)) . "\r\n";
            $message .= "--$boundary\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
            $message .= $htmlBody . "\r\n";
            $message .= "--$boundary--\r\n.\r\n";

            fwrite($socket, $message);
            $response = fread($socket, 512);
            $this->smtpCommand($socket, "QUIT");
            fclose($socket);

            return strpos($response, '250') !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function smtpCommand($socket, ?string $command): void
    {
        if ($command !== null) {
            fwrite($socket, $command . "\r\n");
        }
        fread($socket, 512);
    }

    /* ================================================================
       Admin notification on new lead
       ================================================================ */

    public function sendAdminNotification(array $lead): bool
    {
        $smtp = $this->getSmtpConfig();
        if (empty($smtp['admin_email']) || $smtp['send_admin'] !== '1') {
            return false;
        }

        $template = $this->getTemplate('admin_notification');
        if (!$template) {
            return false;
        }

        $serviceName = 'N/A';
        if (!empty($lead['service_id'])) {
            $svc = $this->db->fetch("SELECT title FROM services WHERE id = ?", [$lead['service_id']]);
            if ($svc) {
                $serviceName = $svc['title'];
            }
        }

        $adminUrl = ($_SERVER['HTTPS'] ?? 'off') !== 'off' ? 'https://' : 'http://';
        $adminUrl .= $_SERVER['HTTP_HOST'] ?? '';
        $adminUrl .= '/admin/leads.php?id=' . $lead['id'];

        $variables = [
            'full_name'   => $lead['full_name'],
            'email'       => $lead['email'],
            'phone'       => $lead['phone'] ?? 'N/A',
            'company'     => $lead['company'] ?? 'N/A',
            'service'     => $serviceName,
            'budget'      => $lead['budget'] ?? 'N/A',
            'timeline'    => $lead['timeline'] ?? 'N/A',
            'subject'     => $lead['subject'] ?? 'N/A',
            'message'     => nl2br(esc($lead['message'])),
            'source_page' => $lead['source_page'] ?? 'N/A',
            'ip_address'  => $lead['ip_address'] ?? 'N/A',
            'admin_url'   => $adminUrl,
        ];

        $subject = $this->renderTemplate($template['subject'], $variables);
        $html    = $this->renderTemplate($template['body_html'], $variables);
        $text    = $this->renderTemplate($template['body_text'], $variables);

        return $this->send($smtp['admin_email'], $subject, $html, $text, $lead['id'], 'admin_notification');
    }

    /* ================================================================
       User confirmation on new lead
       ================================================================ */

    public function sendUserConfirmation(array $lead): bool
    {
        $smtp = $this->getSmtpConfig();
        if ($smtp['send_user'] !== '1') {
            return false;
        }

        $template = $this->getTemplate('user_confirmation');
        if (!$template) {
            return false;
        }

        global $site;
        $siteName = $site->brand->name ?? 'Swap Design';

        $contactPhone = '';
        $contactEmail = '';

        $contactSection = $this->db->fetch("SELECT config FROM contact_sections WHERE section_key = 'contact_info' AND is_enabled = 1 AND status = 'published'");
        if ($contactSection) {
            $cfg = json_decode($contactSection['config'], true) ?? [];
            $contactPhone = $cfg['phone'] ?? '';
            $contactEmail = $cfg['email'] ?? '';
        }

        $waPhone = $this->settings->get('whatsapp.phone_number', '');
        $whatsappLink = $waPhone ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $waPhone) : '';

        $variables = [
            'full_name'      => $lead['full_name'],
            'message'        => nl2br(esc($lead['message'])),
            'contact_phone'  => $contactPhone,
            'contact_email'  => $contactEmail,
            'whatsapp_link'  => $whatsappLink ? '<a href="' . esc($whatsappLink) . '">Chat on WhatsApp</a>' : 'N/A',
            'site_name'      => $siteName,
        ];

        $subject = $this->renderTemplate($template['subject'], $variables);
        $html    = $this->renderTemplate($template['body_html'], $variables);
        $text    = $this->renderTemplate($template['body_text'], $variables);

        return $this->send($lead['email'], $subject, $html, $text, $lead['id'], 'user_confirmation');
    }

    /* ================================================================
       Email Log
       ================================================================ */

    public function getLog(int $page = 1, int $perPage = 30): array
    {
        $offset = ($page - 1) * $perPage;
        return $this->db->fetchAll("SELECT * FROM email_log ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
    }

    public function getLogForLead(int $leadId): array
    {
        return $this->db->fetchAll("SELECT * FROM email_log WHERE lead_id = ? ORDER BY created_at DESC", [$leadId]);
    }

    public function logCount(): int
    {
        return (int)$this->db->fetchColumn("SELECT COUNT(*) FROM email_log");
    }

    private function log(string $to, string $subject, bool $success, ?int $leadId, string $templateKey = ''): void
    {
        $this->db->insert('email_log', [
            'lead_id'      => $leadId,
            'recipient'    => $to,
            'subject'      => $subject,
            'template_key' => $templateKey,
            'status'       => $success ? 'sent' : 'failed',
            'error_message'=> $success ? null : 'SMTP delivery failed',
        ]);
    }
}
