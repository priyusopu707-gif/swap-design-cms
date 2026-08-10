<?php
/**
 * Swap Design - Homepage Manager
 *
 * CRUD for homepage sections. Each section has a well-defined
 * field schema and defaults. Sections are ordered, can be
 * enabled/disabled, and store their content as JSON config.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class HomepageManager
{
    private Database $db;

    /**
     * All 14 homepage section definitions with default configs.
     */
    public const SECTIONS = [
        'hero' => [
            'label'  => 'Hero',
            'icon'   => 'hero',
            'config' => [
                'heading'              => 'Websites That Help Your Business Grow',
                'highlight_text'       => 'Help Your Business Grow',
                'sub_heading'          => 'Remote WordPress Developer, Website Designer & Brand Identity Specialist',
                'description'          => "I'm Swapnil Patil, the freelancer behind Swap Design. For more than 8 years, I've helped businesses build websites that look professional, load quickly, and are easy to manage. Whether you're starting from scratch, redesigning an existing website, or looking for someone to maintain your WordPress site, I can help you build a website that works for your business.",
                'primary_button_text'  => 'Get a Free Consultation',
                'primary_button_url'   => '/contact',
                'secondary_button_text'=> 'View My Work',
                'secondary_button_url' => '/portfolio',
                'hero_image'           => '',
                'background_image'     => '',
            ],
        ],
        'introduction' => [
            'label'  => 'Introduction',
            'icon'   => 'introduction',
            'config' => [
                'title'       => 'Trusted Website Solutions for Growing Businesses',
                'subtitle'    => 'Introduction',
                'description' => 'Your website is often the first place people learn about your business. A slow website, an outdated design, or a confusing layout can make visitors leave before they even contact you. I build websites that are clean, responsive, secure, and simple to use. Every project is built around your business goals instead of using the same formula for every client. I work remotely with businesses across India and clients worldwide.',
                'image'       => '',
                'button_text' => '',
                'button_url'  => '/about',
            ],
        ],
        'experience' => [
            'label'  => 'Experience',
            'icon'   => 'experience',
            'config' => [
                'title' => 'Years of Experience & Expertise',
                'items' => [
                    ['number' => '8', 'suffix' => '+', 'label' => 'Years Experience'],
                    ['number' => '100', 'suffix' => '+', 'label' => 'Projects Delivered'],
                    ['number' => '50', 'suffix' => '+', 'label' => 'Happy Clients'],
                    ['number' => '5', 'suffix' => '', 'label' => 'Years in Business'],
                ],
            ],
        ],
        'services' => [
            'label'  => 'Services',
            'icon'   => 'services',
            'config' => [
                'title'          => 'Everything You Need to Build and Maintain Your Online Presence',
                'description'    => '',
                'display_count'  => '6',
                'sort_order'     => 'newest',
                'view_all_text'  => 'View All Services',
                'view_all_url'   => '/services',
                'layout'         => 'grid',
            ],
        ],
        'why_choose' => [
            'label'  => 'Why Choose Swap Design',
            'icon'   => 'why_choose',
            'config' => [
                'title'       => 'Why Businesses Choose Swap Design',
                'description' => 'Hiring a freelancer shouldn\'t mean sacrificing quality. When you work with Swap Design, you work directly with the person designing and developing your website. There is no middle layer, no sales team, and no confusion about who is handling your project. I focus on building websites that are:',
                'items'       => [
                    ['icon' => 'zap', 'title' => 'Fast Loading', 'description' => 'Quick-loading sites that respect your visitors\' time.'],
                    ['icon' => 'smartphone', 'title' => 'Mobile Friendly', 'description' => 'Designed to work smoothly on every device.'],
                    ['icon' => 'shield-check', 'title' => 'Secure', 'description' => 'Built with security best practices from day one.'],
                    ['icon' => 'settings', 'title' => 'Easy to Manage', 'description' => 'Simple to update so you stay in control.'],
                    ['icon' => 'search', 'title' => 'SEO Friendly', 'description' => 'Structured for search engines to find you.'],
                    ['icon' => 'layers', 'title' => 'Built for Long-Term Use', 'description' => 'Reliable and sustainable for years to come.'],
                ],
            ],
        ],
        'problems_solve' => [
            'label'  => 'Problems Solve',
            'icon'   => 'problems_solve',
            'config' => [
                'title'       => 'Problems I Solve',
                'description' => 'If any of these sound familiar, you\'re in the right place.',
                'items'       => [
                    ['icon' => 'eye-off', 'title' => 'Outdated Website', 'description' => 'Your website looks outdated.'],
                    ['icon' => 'clock', 'title' => 'Slow Loading', 'description' => 'It takes too long to load.'],
                    ['icon' => 'mail', 'title' => 'No Enquiries', 'description' => 'You\'re not getting enquiries.'],
                    ['icon' => 'refresh-cw', 'title' => 'Maintenance Needs', 'description' => 'Your WordPress website needs regular maintenance.'],
                    ['icon' => 'target', 'title' => 'Better Landing Page', 'description' => 'You need a better landing page.'],
                    ['icon' => 'briefcase', 'title' => 'Professional Presence', 'description' => 'Your business doesn\'t have a professional online presence.'],
                    ['icon' => 'palette', 'title' => 'Branding Match', 'description' => 'You need branding that matches your business.'],
                    ['icon' => 'users', 'title' => 'Single Freelancer', 'description' => 'You want one reliable freelancer instead of managing multiple people.'],
                ],
            ],
        ],
        'why_work_me' => [
            'label'  => 'Why Work With Me',
            'icon'   => 'why_work_me',
            'config' => [
                'title'       => 'Why Work With Me',
                'description' => "Over the last 8+ years, I've worked with businesses from different industries, helping them create websites that are practical, reliable, and easy to maintain. You'll always work directly with me. That means quicker communication, clear updates, and someone who understands your project from beginning to end. My goal isn't just to launch a website. It's to build something you'll be happy to use for years.",
                'items'       => [
                    ['icon' => 'message-circle', 'title' => 'Direct Communication', 'description' => 'You\'ll always work directly with me. Quicker communication, clear updates, and someone who understands your project from beginning to end.'],
                    ['icon' => 'award', 'title' => '8+ Years Experience', 'description' => 'Worked with businesses from different industries, creating practical, reliable, and easy to maintain websites.'],
                    ['icon' => 'check-circle', 'title' => 'Long-Term Focus', 'description' => 'My goal isn\'t just to launch a website. It\'s to build something you\'ll be happy to use for years.'],
                    ['icon' => 'user-check', 'title' => 'Single Point of Contact', 'description' => 'No middle layer, no sales team — just you and me working together.'],
                ],
            ],
        ],
        'industries' => [
            'label'  => 'Industries',
            'icon'   => 'industries',
            'config' => [
                'title'       => 'Industries I Work With',
                'description' => 'I work with businesses across a range of industries, including: Every business is different, so I take time to understand your goals before starting any project.',
                'items'       => [
                    ['icon' => 'cpu', 'title' => 'IT Services', 'description' => 'Technology solutions and digital transformation.'],
                    ['icon' => 'building-2', 'title' => 'B2B Companies', 'description' => 'Business-to-business services and solutions.'],
                    ['icon' => 'graduation-cap', 'title' => 'Education', 'description' => 'Schools, colleges, and training institutions.'],
                    ['icon' => 'utensils', 'title' => 'Food & Beverage', 'description' => 'Restaurants, cafes, and food businesses.'],
                    ['icon' => 'brain', 'title' => 'AI & Technology', 'description' => 'Innovative AI solutions and tech startups.'],
                    ['icon' => 'sun', 'title' => 'Solar Energy', 'description' => 'Renewable energy and sustainability businesses.'],
                    ['icon' => 'lightbulb', 'title' => 'Consulting', 'description' => 'Professional consulting and advisory services.'],
                    ['icon' => 'store', 'title' => 'Small Businesses', 'description' => 'Local and small business needs.'],
                    ['icon' => 'rocket', 'title' => 'Startups', 'description' => 'New ventures and emerging businesses.'],
                    ['icon' => 'home', 'title' => 'Real Estate', 'description' => 'Property and real estate businesses.'],
                ],
            ],
        ],
        'technologies' => [
            'label'  => 'Technologies',
            'icon'   => 'technologies',
            'config' => [
                'title'       => 'Technology Stack',
                'description' => 'The tools and platforms I use to build your project.',
                'items'       => [
                    ['icon' => 'wordpress', 'title' => 'WordPress', 'description' => 'Content Management System'],
                    ['icon' => 'shopping-cart', 'title' => 'WooCommerce', 'description' => 'E-commerce Platform'],
                    ['icon' => 'shop', 'title' => 'Shopify', 'description' => 'E-commerce Platform'],
                    ['icon' => 'layout', 'title' => 'Wix', 'description' => 'Website Builder'],
                    ['icon' => 'file-code', 'title' => 'PHP', 'description' => 'Server-side Scripting'],
                    ['icon' => 'database', 'title' => 'MySQL', 'description' => 'Database Management'],
                    ['icon' => 'file-text', 'title' => 'HTML5', 'description' => 'Web Markup'],
                    ['icon' => 'palette', 'title' => 'CSS3', 'description' => 'Stylesheets'],
                    ['icon' => 'code', 'title' => 'JavaScript', 'description' => 'Interactive Features'],
                    ['icon' => 'pen-tool', 'title' => 'Figma', 'description' => 'UI/UX Design'],
                    ['icon' => 'image', 'title' => 'Adobe Illustrator', 'description' => 'Vector Graphics'],
                    ['icon' => 'image', 'title' => 'Adobe Photoshop', 'description' => 'Image Editing'],
                ],
            ],
        ],
        'process' => [
            'label'  => 'Process',
            'icon'   => 'process',
            'config' => [
                'title'       => 'Simple, Clear and Transparent',
                'description' => 'How I work with you from start to finish.',
                'steps'       => [
                    ['icon' => 'message-circle', 'title' => 'Discussion', 'description' => 'We talk about your business, goals, and project requirements.'],
                    ['icon' => 'map', 'title' => 'Planning', 'description' => 'I create the website structure and recommend the best approach.'],
                    ['icon' => 'pen-tool', 'title' => 'Design & Development', 'description' => 'Your website is designed and built with attention to speed, usability, and performance.'],
                    ['icon' => 'check-circle', 'title' => 'Testing & Launch', 'description' => 'Everything is checked before your website goes live.'],
                    ['icon' => 'heart', 'title' => 'Ongoing Support', 'description' => 'Need updates later? I\'m here to help with maintenance and improvements whenever you need them.'],
                ],
            ],
        ],
        'portfolio_preview' => [
            'label'  => 'Portfolio Preview',
            'icon'   => 'portfolio_preview',
            'config' => [
                'title'           => 'Featured Portfolio',
                'description'     => 'A selection of my latest work across branding, website design, UI/UX, and graphic design.',
                'display_count'   => '6',
                'show_filter'     => '1',
                'view_all_text'   => 'View Portfolio',
                'view_all_url'    => '/portfolio',
                'layout'          => 'grid',
                'show_placeholders' => '0',
            ],
        ],
        'testimonials' => [
            'label'  => 'Testimonials',
            'icon'   => 'testimonials',
            'config' => [
                'title'           => 'What Clients Say',
                'description'     => 'Approved testimonials from satisfied clients.',
                'display_count'   => '6',
                'display_style'   => 'carousel',
                'show_avatars'    => '1',
                'show_ratings'    => '1',
                'show_placeholders' => '0',
            ],
        ],
        'faq' => [
            'label'  => 'FAQ',
            'icon'   => 'faq',
            'config' => [
                'title'        => 'Frequently Asked Questions',
                'description'  => 'Quick answers to common questions.',
                'show_search'  => '0',
                'style'        => 'accordion',
                'items'        => [
                    ['question' => 'Do you work remotely?', 'answer' => 'Yes. I work remotely with businesses across India and international clients.'],
                    ['question' => 'Do you only build WordPress websites?', 'answer' => 'WordPress is my primary platform, but I also work on WooCommerce, landing pages, UI/UX design, branding, and graphic design.'],
                    ['question' => 'Will my website work on mobile devices?', 'answer' => 'Yes. Every website is designed to work smoothly on desktops, tablets, and smartphones.'],
                    ['question' => 'Do you provide website maintenance?', 'answer' => 'Yes. I offer ongoing WordPress maintenance, updates, backups, security monitoring, and technical support.'],
                ],
            ],
        ],
        'final_cta' => [
            'label'  => 'Final CTA',
            'icon'   => 'final_cta',
            'config' => [
                'heading'           => 'Let\'s Talk About Your Project',
                'description'       => 'Looking for a Remote WordPress Developer, Website Designer, or Graphic Designer? Whether you need a new website, a redesign, branding, or regular website maintenance, I\'d be happy to discuss your project. Let\'s build a website that reflects your business and gives your visitors a better experience.',
                'button_text'       => 'Contact Me',
                'button_url'        => '/contact',
                'show_whatsapp_btn' => '1',
                'whatsapp_label'    => 'Chat on WhatsApp',
                'background_image'  => '',
            ],
        ],
        'contact_info' => [
            'label'  => 'Contact Information',
            'icon'   => 'contact_info',
            'config' => [
                'title'             => 'Contact Information',
                'description'       => 'Get in touch with me through any of these channels.',
                'show_whatsapp'     => '1',
                'show_phone'        => '1',
                'show_email'        => '1',
                'phone'             => '+91 8788225152',
                'email'             => 'info@swapdesign.co.in',
                'location'          => 'Pune, Maharashtra, India',
                'availability'      => 'Worldwide Remote',
                'button_text'       => 'Contact Me',
                'button_url'        => '/contact',
            ],
        ],
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Seed default homepage sections if none exist.
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
     * Count homepage sections.
     */
    public function count(): int
    {
        return (int)$this->db->fetchColumn("SELECT COUNT(*) FROM homepage_sections", [], 0);
    }

    /**
     * Get all homepage sections ordered by sort_order.
     */
    public function getAll(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM homepage_sections ORDER BY sort_order ASC"
        );

        foreach ($rows as &$row) {
            $row['config'] = $this->resolveConfig($row);
        }

        return $rows;
    }

    /**
     * Get only enabled, running, published sections for frontend rendering.
     */
    public function getActive(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM homepage_sections WHERE is_enabled = 1 AND status = 'published' ORDER BY sort_order ASC"
        );

        foreach ($rows as &$row) {
            $row['config'] = $this->resolveConfig($row);
        }

        return $rows;
    }

    /**
     * Get a single section by key.
     */
    public function getByKey(string $key): ?array
    {
        $row = $this->db->fetch("SELECT * FROM homepage_sections WHERE section_key = ?", [$key]);
        if ($row) {
            $row['config'] = $this->resolveConfig($row);
        }
        return $row ?: null;
    }

    /**
     * Get a single section by ID.
     */
    public function getById(int $id): ?array
    {
        $row = $this->db->fetch("SELECT * FROM homepage_sections WHERE id = ?", [$id]);
        if ($row) {
            $row['config'] = $this->resolveConfig($row);
        }
        return $row ?: null;
    }

    /**
     * Decode stored JSON config and merge section defaults underneath it.
     *
     * Guarantees every section carries its full default config (including
     * flags such as show_placeholders) even when the stored row predates a
     * given key being added. Stored values always win over defaults.
     */
    private function resolveConfig(array $row): array
    {
        $stored   = json_decode($row['config'] ?? '', true) ?: [];
        $defaults = self::SECTIONS[$row['section_key'] ?? ''] ?? [];
        $defaults = is_array($defaults['config'] ?? null) ? $defaults['config'] : [];
        return array_merge($defaults, $stored);
    }

    /**
     * Create a new homepage section.
     */
    public function create(string $key, string $label, array $config = [], int $sortOrder = -1): int
    {
        if ($sortOrder < 0) {
            $max = (int)$this->db->fetchColumn(
                "SELECT COALESCE(MAX(sort_order), -1) FROM homepage_sections",
                [],
                -1
            );
            $sortOrder = $max + 1;
        }

        $json = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return (int)$this->db->insert('homepage_sections', [
            'section_key' => $key,
            'label'       => $label,
            'sort_order'  => $sortOrder,
            'is_enabled'  => 1,
            'config'      => $json,
            'status'      => 'published',
        ]);
    }

    /**
     * Update a section's config.
     */
    public function update(int $id, array $config): bool
    {
        $json = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->db->update('homepage_sections', ['config' => $json], 'id = ?', [$id]);
        return true;
    }

    /**
     * Toggle a section enabled/disabled.
     */
    public function toggle(int $id, bool $enabled): void
    {
        $this->db->update('homepage_sections', ['is_enabled' => $enabled ? 1 : 0], 'id = ?', [$id]);
    }

    /**
     * Set section status.
     */
    public function setStatus(int $id, string $status): void
    {
        $this->db->update('homepage_sections', ['status' => $status], 'id = ?', [$id]);
    }

    /**
     * Reorder sections from an array of IDs.
     */
    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $sectionId) {
            $this->db->update('homepage_sections', ['sort_order' => $index], 'id = ?', [(int)$sectionId]);
        }
    }

    /**
     * Publish all sections at once.
     */
    public function publishAll(): void
    {
        $this->db->query("UPDATE homepage_sections SET status = 'published'");
    }

    /**
     * Get the default config for a section type.
     */
    public function getDefaults(string $sectionKey): array
    {
        return self::SECTIONS[$sectionKey]['config'] ?? [];
    }
}
