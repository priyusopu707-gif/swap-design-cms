<?php
/**
 * Swap Design - Lead Manager
 *
 * Full CRUD for leads, status workflows, notes, search/filter,
 * pagination, CSV export, and file upload handling.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class LeadManager
{
    private Database $db;

    public const STATUSES = ['new', 'contacted', 'follow_up', 'proposal_sent', 'won', 'lost', 'archived'];

    public const STATUS_LABELS = [
        'new'            => 'New',
        'contacted'      => 'Contacted',
        'follow_up'      => 'Follow Up',
        'proposal_sent'  => 'Proposal Sent',
        'won'            => 'Won',
        'lost'           => 'Lost',
        'archived'       => 'Archived',
    ];

    public const STATUS_COLORS = [
        'new'            => '#3b82f6',
        'contacted'      => '#8b5cf6',
        'follow_up'      => '#f59e0b',
        'proposal_sent'  => '#10b981',
        'won'            => '#22c55e',
        'lost'           => '#ef4444',
        'archived'       => '#6b7280',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /* ================================================================
       Create
       ================================================================ */

    public function create(array $data): int
    {
        $insert = [
            'full_name'      => substr($data['full_name'] ?? '', 0, 150),
            'email'          => substr($data['email'] ?? '', 0, 254),
            'phone'          => substr($data['phone'] ?? '', 0, 30),
            'company'        => substr($data['company'] ?? '', 0, 150),
            'service_id'     => !empty($data['service_id']) ? (int)$data['service_id'] : null,
            'budget'         => substr($data['budget'] ?? '', 0, 50),
            'timeline'       => substr($data['timeline'] ?? '', 0, 50),
            'subject'        => substr($data['subject'] ?? '', 0, 255),
            'message'        => $data['message'] ?? '',
            'uploaded_files' => !empty($data['uploaded_files']) ? json_encode($data['uploaded_files'], JSON_UNESCAPED_UNICODE) : null,
            'source_page'    => substr($data['source_page'] ?? '', 0, 500),
            'referrer_url'   => substr($data['referrer_url'] ?? '', 0, 500),
            'ip_address'     => substr($data['ip_address'] ?? '', 0, 45),
            'user_agent'     => substr($data['user_agent'] ?? '', 0, 500),
            'device_type'    => substr($data['device_type'] ?? '', 0, 20),
            'consent_given'  => !empty($data['consent_given']) ? 1 : 0,
            'email_sent'     => 0,
        ];

        return (int)$this->db->insert('leads', $insert);
    }

    /* ================================================================
       Read
       ================================================================ */

    public function count(array $filters = []): int
    {
        [$where, $params] = $this->buildFilterClause($filters);
        $sql = "SELECT COUNT(*) FROM leads" . ($where ? " WHERE $where" : '');
        return (int)$this->db->fetchColumn($sql, $params);
    }

    public function getById(int $id): ?array
    {
        $row = $this->db->fetch("SELECT * FROM leads WHERE id = ?", [$id]);
        if ($row) {
            $row['uploaded_files'] = $row['uploaded_files'] ? json_decode($row['uploaded_files'], true) : [];
        }
        return $row ?: null;
    }

    public function getAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        [$where, $params] = $this->buildFilterClause($filters);
        $sql = "SELECT * FROM leads" . ($where ? " WHERE $where" : '') . " ORDER BY created_at DESC";
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT $perPage OFFSET $offset";

        $rows = $this->db->fetchAll($sql, $params);
        foreach ($rows as &$row) {
            $row['uploaded_files'] = $row['uploaded_files'] ? json_decode($row['uploaded_files'], true) : [];
        }
        return $rows;
    }

    public function search(string $query, array $filters = [], int $page = 1, int $perPage = 20): array
    {
        [$where, $params] = $this->buildFilterClause($filters);
        $searchWhere = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR company LIKE ? OR message LIKE ? OR subject LIKE ?)";
        $searchParam = "%$query%";
        $searchParams = [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam];

        if ($where) {
            $where = "$searchWhere AND $where";
            $params = array_merge($searchParams, $params);
        } else {
            $where = $searchWhere;
            $params = $searchParams;
        }

        $sql = "SELECT * FROM leads WHERE $where ORDER BY created_at DESC";
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT $perPage OFFSET $offset";

        $rows = $this->db->fetchAll($sql, $params);
        foreach ($rows as &$row) {
            $row['uploaded_files'] = $row['uploaded_files'] ? json_decode($row['uploaded_files'], true) : [];
        }
        return $rows;
    }

    public function searchCount(string $query, array $filters = []): int
    {
        [$where, $params] = $this->buildFilterClause($filters);
        $searchWhere = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR company LIKE ? OR message LIKE ? OR subject LIKE ?)";
        $searchParam = "%$query%";
        $searchParams = [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam];

        if ($where) {
            $where = "$searchWhere AND $where";
            $params = array_merge($searchParams, $params);
        } else {
            $where = $searchWhere;
            $params = $searchParams;
        }

        return (int)$this->db->fetchColumn("SELECT COUNT(*) FROM leads WHERE $where", $params);
    }

    /* ================================================================
       Update
       ================================================================ */

    public function updateStatus(int $id, string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            $status = 'new';
        }
        $this->db->update('leads', ['status' => $status], 'id = ?', [$id]);
    }

    public function markEmailed(int $id): void
    {
        $this->db->update('leads', ['email_sent' => 1], 'id = ?', [$id]);
    }

    /* ================================================================
       Notes
       ================================================================ */

    public function getNotes(int $leadId): array
    {
        return $this->db->fetchAll(
            "SELECT n.*, u.name AS username FROM lead_notes n LEFT JOIN users u ON n.user_id = u.id WHERE n.lead_id = ? ORDER BY n.created_at ASC",
            [$leadId]
        );
    }

    public function addNote(int $leadId, int $userId, string $note): int
    {
        return (int)$this->db->insert('lead_notes', [
            'lead_id' => $leadId,
            'user_id' => $userId,
            'note'    => $note,
        ]);
    }

    /* ================================================================
       Status counts for dashboard
       ================================================================ */

    public function statusCounts(): array
    {
        $rows = $this->db->fetchAll("SELECT status, COUNT(*) AS cnt FROM leads GROUP BY status");
        $counts = [];
        foreach (self::STATUSES as $s) {
            $counts[$s] = 0;
        }
        foreach ($rows as $row) {
            $counts[$row['status']] = (int)$row['cnt'];
        }
        return $counts;
    }

    /* ================================================================
       CSV Export
       ================================================================ */

    public function exportCsv(array $filters = []): array
    {
        [$where, $params] = $this->buildFilterClause($filters);
        $sql = "SELECT * FROM leads" . ($where ? " WHERE $where" : '') . " ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, $params);
    }

    /* ================================================================
       Determine device type from user agent
       ================================================================ */

    public static function detectDeviceType(string $ua): string
    {
        $ua = strtolower($ua);
        if (strpos($ua, 'mobile') !== false || strpos($ua, 'android') !== false) {
            return 'mobile';
        }
        if (strpos($ua, 'tablet') !== false || strpos($ua, 'ipad') !== false) {
            return 'tablet';
        }
        return 'desktop';
    }

    /* ================================================================
       File upload handling
       ================================================================ */

    public static function handleUpload(string $fieldName, int $maxSizeBytes, array $allowedExts): array
    {
        $uploaded = [];
        if (empty($_FILES[$fieldName]['name'][0])) {
            return $uploaded;
        }

        $uploadDir = ROOT_PATH . '/uploads/leads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $files = $_FILES[$fieldName];
        $count = is_array($files['name']) ? count($files['name']) : 1;

        for ($i = 0; $i < $count; $i++) {
            $name     = is_array($files['name']) ? $files['name'][$i] : $files['name'];
            $tmpName  = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $error    = is_array($files['error']) ? $files['error'][$i] : $files['error'];
            $size     = is_array($files['size']) ? $files['size'][$i] : $files['size'];

            if ($error !== UPLOAD_ERR_OK || empty($name)) {
                continue;
            }

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExts, true)) {
                continue;
            }

            if ($size > $maxSizeBytes) {
                continue;
            }

            $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
            $destPath = $uploadDir . $safeName;

            if (move_uploaded_file($tmpName, $destPath)) {
                $uploaded[] = [
                    'original_name' => $name,
                    'stored_name'   => $safeName,
                    'size'          => $size,
                    'type'          => mime_content_type($destPath),
                ];
            }
        }

        return $uploaded;
    }

    /* ================================================================
       Filter clause builder
       ================================================================ */

    private function buildFilterClause(array $filters): array
    {
        $conditions = [];
        $params     = [];

        if (!empty($filters['status'])) {
            $conditions[] = 'status = ?';
            $params[]     = $filters['status'];
        }
        if (!empty($filters['service_id'])) {
            $conditions[] = 'service_id = ?';
            $params[]     = (int)$filters['service_id'];
        }
        if (!empty($filters['date_from'])) {
            $conditions[] = 'created_at >= ?';
            $params[]     = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = 'created_at <= ?';
            $params[]     = $filters['date_to'] . ' 23:59:59';
        }

        return [implode(' AND ', $conditions), $params];
    }

    /**
     * Delete a lead and its associated notes and email logs.
     */
    public function delete(int $id): void
    {
        $this->db->query("DELETE FROM lead_notes WHERE lead_id = ?", [$id]);
        $this->db->query("DELETE FROM email_log WHERE lead_id = ?", [$id]);
        $this->db->query("DELETE FROM leads WHERE id = ?", [$id]);
    }
}
