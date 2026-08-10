<?php
/**
 * Swap Design - About Manager
 *
 * CRUD for about page sections. Each section has a well-defined
 * field schema and defaults. Sections are ordered, can be
 * enabled/disabled, and store their content as JSON config.
 * Supports revision history tracking.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class AboutManager
{
    private Database $db;

    /**
     * All 12 about page section definitions with default configs.
     */
    public const SECTIONS = [
        'hero' => [
            'label'  => 'Hero',
            'config' => [
                'title'       => 'About Me',
                'subtitle'    => 'Designer & Developer',
                'intro'       => 'I am a remote freelance designer with 8+ years of experience crafting digital experiences for brands worldwide.',
                'hero_image'  => '',
                'cta_text'    => 'Get in Touch',
                'cta_url'     => '/contact',
            ],
        ],
        'personal_intro' => [
            'label'  => 'Personal Introduction',
            'config' => [
                'name'           => 'Swapnil',
                'professional_title' => 'Creative Designer & Developer',
                'experience'     => '8+ Years',
                'short_bio'      => 'A passionate designer who transforms ideas into impactful digital experiences.',
                'long_bio'       => 'With over 8 years of experience in the design industry, I have helped businesses of all sizes establish their brand identity, build stunning websites, and create memorable user experiences. My approach combines creativity with strategy to deliver solutions that drive results.',
                'signature_image'=> '',
            ],
        ],
        'my_story' => [
            'label'  => 'My Story',
            'config' => [
                'title'       => 'My Story',
                'description' => 'The journey that shaped my career.',
                'timeline'    => [
                    ['year' => '2016', 'title' => 'Started Freelancing', 'description' => 'Launched my freelance design career on platforms like Behance and Dribbble.'],
                    ['year' => '2018', 'title' => 'First Major Client', 'description' => 'Landed my first enterprise client and built a complete brand identity system.'],
                    ['year' => '2020', 'title' => 'Expanded Services', 'description' => 'Added web development and UI/UX design to my service offerings.'],
                    ['year' => '2023', 'title' => '100+ Projects', 'description' => 'Crossed 100 completed projects with clients across 10+ countries.'],
                    ['year' => '2025', 'title' => 'Full-Stack Creative', 'description' => 'Now offering complete end-to-end creative solutions from brand strategy to deployment.'],
                ],
            ],
        ],
        'experience' => [
            'label'  => 'Experience',
            'config' => [
                'title'           => 'My Experience',
                'years'           => '8',
                'projects'        => '500',
                'industries'      => '15',
                'description'     => 'Over the years, I have worked with startups, agencies, and enterprises across multiple industries.',
                'show_counters'   => '1',
            ],
        ],
        'core_services' => [
            'label'  => 'Core Services',
            'config' => [
                'title'          => 'Core Services',
                'description'    => 'Services I specialize in.',
                'display_count'  => '6',
                'layout'         => 'grid',
                'show_icons'     => '1',
            ],
        ],
        'working_process' => [
            'label'  => 'Working Process',
            'config' => [
                'title'       => 'How I Work',
                'description' => 'My proven approach to delivering exceptional results.',
                'steps'       => [
                    ['icon' => 'search', 'title' => 'Discovery', 'description' => 'Understanding your brand, goals, audience, and project requirements.'],
                    ['icon' => 'pencil', 'title' => 'Strategy', 'description' => 'Developing a tailored creative strategy and project roadmap.'],
                    ['icon' => 'layout', 'title' => 'Design', 'description' => 'Creating stunning designs aligned with your brand vision.'],
                    ['icon' => 'refresh', 'title' => 'Refine', 'description' => 'Iterating based on your feedback until it is perfect.'],
                    ['icon' => 'rocket', 'title' => 'Launch', 'description' => 'Delivering the final product with ongoing support.'],
                ],
            ],
        ],
        'why_work_with_me' => [
            'label'  => 'Why Work With Me',
            'config' => [
                'title'       => 'Why Work With Me',
                'description' => 'What sets me apart as your creative partner.',
                'cards'       => [
                    ['icon' => 'star', 'title' => 'Creative Excellence', 'description' => 'Award-quality designs that captivate and convert.'],
                    ['icon' => 'clock', 'title' => 'On-Time Delivery', 'description' => 'Deadlines are sacred. I deliver on time, every time.'],
                    ['icon' => 'users', 'title' => 'Dedicated Support', 'description' => 'One-on-one attention throughout your entire project.'],
                    ['icon' => 'shield', 'title' => '100% Satisfaction', 'description' => 'Your happiness is my top priority, guaranteed.'],
                ],
            ],
        ],
        'skills' => [
            'label'  => 'Skills',
            'config' => [
                'title'          => 'My Skills',
                'description'    => 'Technologies and tools I excel at.',
                'display_style'  => 'bars',
                'skills'         => [
                    ['name' => 'UI/UX Design', 'category' => 'Design', 'percentage' => '95'],
                    ['name' => 'Brand Identity', 'category' => 'Design', 'percentage' => '90'],
                    ['name' => 'HTML5 & CSS3', 'category' => 'Development', 'percentage' => '95'],
                    ['name' => 'JavaScript', 'category' => 'Development', 'percentage' => '85'],
                    ['name' => 'PHP & MySQL', 'category' => 'Development', 'percentage' => '80'],
                    ['name' => 'Figma', 'category' => 'Tools', 'percentage' => '95'],
                    ['name' => 'Adobe Suite', 'category' => 'Tools', 'percentage' => '90'],
                    ['name' => 'Responsive Design', 'category' => 'Design', 'percentage' => '92'],
                ],
            ],
        ],
        'tools' => [
            'label'  => 'Tools & Technologies',
            'config' => [
                'title'       => 'Tools & Technologies',
                'description' => 'The tools I use to bring ideas to life.',
                'tools'       => [
                    ['name' => 'Figma', 'category' => 'Design', 'logo_url' => ''],
                    ['name' => 'Adobe Photoshop', 'category' => 'Design', 'logo_url' => ''],
                    ['name' => 'Adobe Illustrator', 'category' => 'Design', 'logo_url' => ''],
                    ['name' => 'VS Code', 'category' => 'Development', 'logo_url' => ''],
                    ['name' => 'Git', 'category' => 'Development', 'logo_url' => ''],
                    ['name' => 'PHPStorm', 'category' => 'Development', 'logo_url' => ''],
                ],
            ],
        ],
        'testimonials' => [
            'label'  => 'Testimonials',
            'config' => [
                'title'          => 'What Clients Say',
                'description'    => 'Hear from people I have worked with.',
                'display_count'  => '6',
                'display_style'  => 'carousel',
                'show_avatars'   => '1',
            ],
        ],
        'faq' => [
            'label'  => 'FAQ',
            'config' => [
                'title'       => 'Frequently Asked Questions',
                'description' => 'Quick answers to common questions.',
                'style'       => 'accordion',
                'items'       => [
                    ['question' => 'How long does a typical project take?', 'answer' => 'Timelines vary based on scope. Most design projects take 1-4 weeks.'],
                    ['question' => 'What is your design process?', 'answer' => 'I follow a 5-step process: Discovery, Strategy, Design, Refine, and Launch.'],
                    ['question' => 'Do you offer revisions?', 'answer' => 'Yes, all packages include revision rounds until you are satisfied.'],
                    ['question' => 'How do I get started?', 'answer' => 'Reach out via the contact form or WhatsApp for a free consultation.'],
                ],
            ],
        ],
        'final_cta' => [
            'label'  => 'Final CTA',
            'config' => [
                'heading'            => "Let's Work Together",
                'description'        => 'Have a project in mind? I would love to hear about it.',
                'primary_text'       => 'Start a Project',
                'primary_url'        => '/contact',
                'whatsapp_text'      => 'Chat on WhatsApp',
                'show_whatsapp'      => '1',
                'background_image'   => '',
            ],
        ],
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Seed default about page sections if none exist.
     */
    public function seedDefaults(): void
    {
        if ($this->count() > 0) return;

        $order = 0;
        foreach (self::SECTIONS as $key => $section) {
            $this->create($key, $section['label'], $section['config'], $order);
            $order++;
        }
    }

    /**
     * Count about sections.
     */
    public function count(): int
    {
        return (int)$this->db->fetchColumn("SELECT COUNT(*) FROM about_sections", [], 0);
    }

    /**
     * Get all about sections ordered by sort_order.
     */
    public function getAll(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM about_sections ORDER BY sort_order ASC"
        );

        foreach ($rows as &$row) {
            $row['config'] = json_decode($row['config'], true) ?: [];
        }

        return $rows;
    }

    /**
     * Get only enabled, published sections for frontend rendering.
     */
    public function getActive(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM about_sections WHERE is_enabled = 1 AND status = 'published' ORDER BY sort_order ASC"
        );

        foreach ($rows as &$row) {
            $row['config'] = json_decode($row['config'], true) ?: [];
        }

        return $rows;
    }

    /**
     * Get a single section by key.
     */
    public function getByKey(string $key): ?array
    {
        $row = $this->db->fetch("SELECT * FROM about_sections WHERE section_key = ?", [$key]);
        if ($row) {
            $row['config'] = json_decode($row['config'], true) ?: [];
        }
        return $row ?: null;
    }

    /**
     * Get a single section by ID.
     */
    public function getById(int $id): ?array
    {
        $row = $this->db->fetch("SELECT * FROM about_sections WHERE id = ?", [$id]);
        if ($row) {
            $row['config'] = json_decode($row['config'], true) ?: [];
        }
        return $row ?: null;
    }

    /**
     * Create a new about section.
     */
    public function create(string $key, string $label, array $config = [], int $sortOrder = -1): int
    {
        if ($sortOrder < 0) {
            $max = (int)$this->db->fetchColumn(
                "SELECT COALESCE(MAX(sort_order), -1) FROM about_sections",
                [],
                -1
            );
            $sortOrder = $max + 1;
        }

        $json = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return (int)$this->db->insert('about_sections', [
            'section_key'   => $key,
            'section_label' => $label,
            'sort_order'    => $sortOrder,
            'is_enabled'    => 1,
            'config'        => $json,
            'status'        => 'published',
        ]);
    }

    /**
     * Update a section's config.
     */
    public function update(int $id, array $config): bool
    {
        $json = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->db->update('about_sections', ['config' => $json], 'id = ?', [$id]);
        return true;
    }

    /**
     * Toggle a section enabled/disabled.
     */
    public function toggle(int $id, bool $enabled): void
    {
        $this->db->update('about_sections', ['is_enabled' => $enabled ? 1 : 0], 'id = ?', [$id]);
    }

    /**
     * Set section status.
     */
    public function setStatus(int $id, string $status): void
    {
        $this->db->update('about_sections', ['status' => $status], 'id = ?', [$id]);
    }

    /**
     * Reorder sections from an array of IDs.
     */
    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $sectionId) {
            $this->db->update('about_sections', ['sort_order' => $index], 'id = ?', [(int)$sectionId]);
        }
    }

    /**
     * Publish all sections at once.
     */
    public function publishAll(): void
    {
        $this->db->query("UPDATE about_sections SET status = 'published'");
    }

    /**
     * Get the default config for a section type.
     */
    public function getDefaults(string $sectionKey): array
    {
        return self::SECTIONS[$sectionKey]['config'] ?? [];
    }

    /**
     * Save a revision snapshot for a section.
     */
    public function saveRevision(int $sectionId, string $note = ''): int
    {
        $section = $this->getById($sectionId);
        if (!$section) return 0;

        $snapshot = json_encode($section['config'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return (int)$this->db->insert('about_revisions', [
            'section_id'      => $sectionId,
            'config_snapshot' => $snapshot,
            'revision_note'   => $note,
        ]);
    }

    /**
     * Get revision history for a section.
     */
    public function getRevisions(int $sectionId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM about_revisions WHERE section_id = ? ORDER BY created_at DESC LIMIT 50",
            [$sectionId]
        );
    }

    /**
     * Restore a section to a specific revision.
     */
    public function restoreRevision(int $revisionId): bool
    {
        $revision = $this->db->fetch("SELECT * FROM about_revisions WHERE id = ?", [$revisionId]);
        if (!$revision) return false;

        $config = json_decode($revision['config_snapshot'], true);
        if (!is_array($config)) return false;

        return $this->update((int)$revision['section_id'], $config);
    }

    /* ================================================================
       PORTFOLIO RELATIONSHIPS
       ================================================================ */

    /**
     * Get portfolio items linked to the About page.
     */
    public function getRelatedPortfolio(): array
    {
        return $this->db->fetchAll(
            "SELECT pi.* FROM portfolio_items pi
             JOIN about_related_portfolio arp ON pi.id = arp.portfolio_item_id
             WHERE pi.status = 'published'
             ORDER BY arp.sort_order ASC"
        );
    }

    /**
     * Get all published portfolio items for the picker.
     */
    public function getAllPortfolioItems(): array
    {
        return $this->db->fetchAll(
            "SELECT id, title, category, image_url FROM portfolio_items WHERE status = 'published' ORDER BY title ASC"
        );
    }

    /**
     * Link a portfolio item to the About page.
     */
    public function linkPortfolio(int $portfolioItemId): void
    {
        $exists = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM about_related_portfolio WHERE portfolio_item_id = ?",
            [$portfolioItemId], 0
        );
        if (!$exists) {
            $this->db->insert('about_related_portfolio', [
                'portfolio_item_id' => $portfolioItemId,
                'sort_order'        => $this->nextSort('about_related_portfolio'),
            ]);
        }
    }

    /**
     * Unlink a portfolio item from the About page.
     */
    public function unlinkPortfolio(int $portfolioItemId): void
    {
        $this->db->query("DELETE FROM about_related_portfolio WHERE portfolio_item_id = ?", [$portfolioItemId]);
    }

    /* ================================================================
       GLOBAL BLOCK RELATIONSHIPS
       ================================================================ */

    /**
     * Get global blocks linked to the About page.
     */
    public function getRelatedBlocks(): array
    {
        return $this->db->fetchAll(
            "SELECT gb.* FROM global_blocks gb
             JOIN about_related_blocks arb ON gb.id = arb.global_block_id
             WHERE gb.status = 'published'
             ORDER BY arb.sort_order ASC"
        );
    }

    /**
     * Get all published global blocks for the picker.
     */
    public function getAllGlobalBlocks(): array
    {
        return $this->db->fetchAll(
            "SELECT id, name, block_type, status FROM global_blocks WHERE status = 'published' ORDER BY name ASC"
        );
    }

    /**
     * Link a global block to the About page.
     */
    public function linkBlock(int $blockId): void
    {
        $exists = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM about_related_blocks WHERE global_block_id = ?",
            [$blockId], 0
        );
        if (!$exists) {
            $this->db->insert('about_related_blocks', [
                'global_block_id' => $blockId,
                'sort_order'      => $this->nextSort('about_related_blocks'),
            ]);
        }
    }

    /**
     * Unlink a global block from the About page.
     */
    public function unlinkBlock(int $blockId): void
    {
        $this->db->query("DELETE FROM about_related_blocks WHERE global_block_id = ?", [$blockId]);
    }

    /* ================================================================
       HELPERS
       ================================================================ */

    /**
     * Get next sort_order value for a given table.
     */
    private function nextSort(string $table): int
    {
        $max = (int)$this->db->fetchColumn(
            "SELECT COALESCE(MAX(sort_order), -1) FROM {$table}",
            [], -1
        );
        return $max + 1;
    }
}
