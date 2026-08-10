<?php
/**
 * Swap Design - Contact Page Manager
 *
 * CRUD for contact page sections. Six configurable sections:
 * hero, contact_info, contact_form, whatsapp_cta, faq, final_cta.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class ContactManager
{
    private Database $db;

    public const SECTIONS = [
        'hero' => [
            'label'  => 'Hero',
            'config' => [
                'title'       => 'Get In Touch',
                'description' => 'Have a project in mind? I would love to hear from you. Send me a message and I will get back to you within 24 hours.',
                'bg_image'    => '',
            ],
        ],
        'contact_info' => [
            'label'  => 'Contact Information',
            'config' => [
                'heading'          => 'Contact Details',
                'phone'            => '+91 98765 43210',
                'phone_label'      => 'Call Me',
                'whatsapp'         => '+91 98765 43210',
                'whatsapp_label'   => 'WhatsApp',
                'email'            => 'hello@example.com',
                'email_label'      => 'Email',
                'office_hours'     => 'Monday - Friday: 9:00 AM - 6:00 PM IST',
                'service_area'     => 'Worldwide (Remote)',
                'google_maps_embed'=> '',
                'address_line1'    => '',
                'address_line2'    => '',
                'show_map'         => false,
            ],
        ],
        'contact_form' => [
            'label'  => 'Contact Form',
            'config' => [
                'heading'              => 'Send a Message',
                'subheading'           => 'Fill out the form below and I will get back to you shortly.',
                'name_label'           => 'Full Name',
                'name_placeholder'     => 'John Doe',
                'name_required'        => true,
                'email_label'          => 'Email Address',
                'email_placeholder'    => 'john@example.com',
                'email_required'       => true,
                'phone_label'          => 'Phone Number',
                'phone_placeholder'    => '+1 234 567 8900',
                'phone_required'       => false,
                'company_label'        => 'Company',
                'company_placeholder'  => 'Your Company (Optional)',
                'company_required'     => false,
                'subject_label'        => 'Subject',
                'subject_placeholder'  => 'What is this about?',
                'subject_required'     => true,
                'service_label'        => 'Service Required',
                'service_required'     => false,
                'budget_label'         => 'Budget Range',
                'budget_required'      => false,
                'budget_options'       => ['Under $500', '$500 - $1,000', '$1,000 - $5,000', '$5,000 - $10,000', '$10,000+', 'Not Sure'],
                'timeline_label'       => 'Expected Timeline',
                'timeline_required'    => false,
                'timeline_options'     => ['Immediately', 'Within 1 Week', 'Within 2 Weeks', 'Within 1 Month', '1-3 Months', '3+ Months', 'Not Sure'],
                'message_label'        => 'Project Details',
                'message_placeholder'  => 'Tell me about your project, goals, and requirements...',
                'message_required'     => true,
                'file_upload_label'    => 'Attach Files (Optional)',
                'file_upload_enabled'  => true,
                'file_max_size'        => 10,
                'file_allowed_types'   => 'pdf,doc,docx,jpg,png,zip',
                'consent_label'        => 'I agree to the privacy policy and consent to being contacted regarding my enquiry.',
                'consent_required'     => true,
                'submit_label'         => 'Send Message',
                'success_message'      => 'Thank you! Your message has been sent successfully. I will get back to you within 24 hours.',
                'recaptcha_site_key'   => '',
                'recaptcha_enabled'    => false,
            ],
        ],
        'whatsapp_cta' => [
            'label'  => 'WhatsApp CTA',
            'config' => [
                'heading'       => 'Prefer a Quick Chat?',
                'description'   => 'Reach me directly on WhatsApp for faster responses. I am available during business hours.',
                'button_text'   => 'Chat on WhatsApp',
                'message_prefix'=> 'Hi! I found your website and would like to discuss a project.',
            ],
        ],
        'faq' => [
            'label'  => 'FAQ',
            'config' => [
                'heading' => 'Frequently Asked Questions',
                'items'   => [
                    ['question' => 'What is your typical response time?', 'answer' => 'I typically respond within 24 hours on business days. For urgent enquiries, WhatsApp is the fastest way to reach me.'],
                    ['question' => 'Do you work with clients worldwide?', 'answer' => 'Yes! I work remotely with clients around the globe. All communication is done via email, video calls, and project management tools.'],
                    ['question' => 'What information should I include in my enquiry?', 'answer' => 'The more details the better! Include your project goals, timeline, budget range, and any references or inspiration you have in mind.'],
                    ['question' => 'Do you offer free consultations?', 'answer' => 'Yes, I offer a free 30-minute discovery call to understand your project and discuss how I can help.'],
                    ['question' => 'What is your payment process?', 'answer' => 'I typically work with a 50% upfront payment and 50% upon completion, with milestone-based payments for larger projects.'],
                ],
            ],
        ],
        'final_cta' => [
            'label'  => 'Final CTA',
            'config' => [
                'heading'     => 'Ready to Start Your Project?',
                'description' => 'Let us create something amazing together. Reach out today and let us discuss your vision.',
                'button_text' => 'Get a Free Quote',
                'button_url'  => '#contact-form',
                'bg_color'    => '',
            ],
        ],
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /* ================================================================
       Seed / defaults
       ================================================================ */

    public function seedDefaults(): void
    {
        if ($this->count() > 0) {
            return;
        }
        $order = 0;
        foreach (self::SECTIONS as $key => $def) {
            $this->create($key, $def['label'], $def['config'], $order++);
        }
    }

    /* ================================================================
       Read
       ================================================================ */

    public function count(): int
    {
        return (int)$this->db->fetchColumn("SELECT COUNT(*) FROM contact_sections");
    }

    public function getAll(): array
    {
        $rows = $this->db->fetchAll("SELECT * FROM contact_sections ORDER BY sort_order ASC");
        foreach ($rows as &$row) {
            $row['config'] = json_decode($row['config'], true) ?? [];
        }
        return $rows;
    }

    public function getActive(): array
    {
        $rows = $this->db->fetchAll("SELECT * FROM contact_sections WHERE is_enabled = 1 AND status = 'published' ORDER BY sort_order ASC");
        foreach ($rows as &$row) {
            $row['config'] = json_decode($row['config'], true) ?? [];
        }
        return $rows;
    }

    public function getByKey(string $key): ?array
    {
        $row = $this->db->fetch("SELECT * FROM contact_sections WHERE section_key = ?", [$key]);
        if ($row) {
            $row['config'] = json_decode($row['config'], true) ?? [];
        }
        return $row ?: null;
    }

    public function getById(int $id): ?array
    {
        $row = $this->db->fetch("SELECT * FROM contact_sections WHERE id = ?", [$id]);
        if ($row) {
            $row['config'] = json_decode($row['config'], true) ?? [];
        }
        return $row ?: null;
    }

    /* ================================================================
       Create / Update
       ================================================================ */

    public function create(string $key, string $label, array $config, int $sortOrder = -1): int
    {
        if ($sortOrder < 0) {
            $sortOrder = $this->count();
        }
        return (int)$this->db->insert('contact_sections', [
            'section_key'   => $key,
            'section_label' => $label,
            'config'        => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'sort_order'    => $sortOrder,
            'status'        => 'draft',
        ]);
    }

    public function update(int $id, array $config): bool
    {
        return $this->db->update('contact_sections', [
            'config' => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ], 'id = ?', [$id]) > 0;
    }

    public function toggle(int $id, bool $enabled): void
    {
        $this->db->update('contact_sections', ['is_enabled' => $enabled ? 1 : 0], 'id = ?', [$id]);
    }

    public function setStatus(int $id, string $status): void
    {
        $this->db->update('contact_sections', ['status' => $status], 'id = ?', [$id]);
    }

    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            $this->db->update('contact_sections', ['sort_order' => $index], 'id = ?', [(int)$id]);
        }
    }

    public function publishAll(): void
    {
        $this->db->query("UPDATE contact_sections SET status = 'published'");
    }

    public function getDefaults(string $sectionKey): array
    {
        return self::SECTIONS[$sectionKey]['config'] ?? [];
    }
}
