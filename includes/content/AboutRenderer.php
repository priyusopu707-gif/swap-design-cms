<?php
/**
 * Swap Design - About Renderer
 *
 * Renders the full about page from about_sections data.
 * Each section has its own renderer method producing semantic HTML.
 * Dynamic sections (services, testimonials, FAQ) query existing tables.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class AboutRenderer
{
    private AboutManager $manager;
    private Database $db;
    private WhatsAppManager $whatsapp;

    public function __construct()
    {
        $this->manager  = new AboutManager();
        $this->db       = Database::getInstance();
        $this->whatsapp = new WhatsAppManager();
    }

    /**
     * Render the full about page: all active sections in order.
     */
    public function render(): string
    {
        $sections = $this->manager->getActive();

        if (empty($sections)) {
            return $this->renderEmptyState();
        }

        $html = '';
        foreach ($sections as $section) {
            $key    = $section['section_key'];
            $config = $section['config'] ?? [];

            $method = 'render' . $this->camelCase($key);
            if (method_exists($this, $method)) {
                $html .= $this->$method($config);
            }
        }

        return $html;
    }

    /**
     * Get page-level CSS and JS assets.
     */
    public function getPageAssets(): array
    {
        return [
            'css' => ['/assets/css/about.css'],
            'js'  => ['/assets/js/pages/about.js'],
        ];
    }

    /**
     * Get about page structured data.
     */
    public function getSchema(): string
    {
        global $site;
        $name    = $site->brand->name ?? 'Swap Design';
        $url     = $site->urls->base ?? '';
        $desc    = $site->brand->description ?? '';
        $lang    = $site->brand->language ?? 'en';
        $logo    = $site->brand->logo ?? '';

        $orgSchema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Organization',
            'name'        => $name,
            'url'         => $url,
            'description' => $desc,
            'knowsAbout'  => ['Graphic Design', 'UI/UX Design', 'Web Development', 'Brand Identity'],
            'sameAs'      => array_values(array_filter(array_column($site->social ?? [], 'url'))),
        ];

        if ($logo) {
            $orgSchema['logo'] = $logo;
        }

        $personSchema = [
            '@context'      => 'https://schema.org',
            '@type'         => 'Person',
            'name'          => $name,
            'url'           => $url,
            'description'   => $desc,
            'jobTitle'      => 'Creative Designer & Developer',
            'knowsAbout'    => ['Graphic Design', 'UI/UX Design', 'Web Development', 'Brand Identity'],
            'sameAs'        => array_values(array_filter(array_column($site->social ?? [], 'url'))),
        ];

        $schemas = [
            '<script type="application/ld+json">',
            json_encode($orgSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            '</script>',
            '<script type="application/ld+json">',
            json_encode($personSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            '</script>',
        ];

        return implode("\n", $schemas);
    }

    /* ================================================================
       1. Hero Section
       ================================================================ */
    private function renderHero(array $cfg): string
    {
        $title    = esc($cfg['title'] ?? '');
        $subtitle = esc($cfg['subtitle'] ?? '');
        $intro    = esc($cfg['intro'] ?? '');
        $image    = esc($cfg['hero_image'] ?? '');
        $ctaText  = esc($cfg['cta_text'] ?? '');
        $ctaUrl   = esc($cfg['cta_url'] ?? '/contact');

        $bgStyle  = $image ? ' style="background-image:url(' . $image . ')"' : '';

        return <<<HTML
        <section class="about-hero" id="about-hero"{$bgStyle}>
            <div class="about-hero__overlay"></div>
            <div class="about-hero__container container">
                <div class="about-hero__content">
                    <p class="about-hero__subtitle">{$subtitle}</p>
                    <h1 class="about-hero__title">{$title}</h1>
                    <p class="about-hero__intro">{$intro}</p>
                    <a href="{$ctaUrl}" class="btn btn--primary btn--lg">{$ctaText}</a>
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       2. Personal Introduction
       ================================================================ */
    private function renderPersonalIntro(array $cfg): string
    {
        $name       = esc($cfg['name'] ?? '');
        $title      = esc($cfg['professional_title'] ?? '');
        $experience = esc($cfg['experience'] ?? '');
        $shortBio   = esc($cfg['short_bio'] ?? '');
        $longBio    = nl2br(esc($cfg['long_bio'] ?? ''));
        $signature  = esc($cfg['signature_image'] ?? '');

        $sigHtml = $signature ? '<div class="about-intro__signature"><img src="' . $signature . '" alt="Signature" loading="lazy" width="200" height="60"></div>' : '';

        return <<<HTML
        <section class="about-intro section" id="about-intro">
            <div class="container">
                <div class="about-intro__grid">
                    <div class="about-intro__info">
                        <p class="about-intro__name">{$name}</p>
                        <h2 class="about-intro__title">{$title}</h2>
                        <p class="about-intro__experience"><strong>Experience:</strong> {$experience}</p>
                        <p class="about-intro__short">{$shortBio}</p>
                        <div class="about-intro__long">
                            {$longBio}
                        </div>
                        {$sigHtml}
                    </div>
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       3. My Story (Timeline)
       ================================================================ */
    private function renderMyStory(array $cfg): string
    {
        $title       = esc($cfg['title'] ?? '');
        $description = esc($cfg['description'] ?? '');
        $timeline    = $cfg['timeline'] ?? [];

        $items = '';
        foreach ($timeline as $i => $item) {
            $year = esc($item['year'] ?? '');
            $ttl  = esc($item['title'] ?? '');
            $desc = esc($item['description'] ?? '');
            $side = ($i % 2 === 0) ? 'left' : 'right';
            $items .= <<<HTML
            <div class="about-timeline__item about-timeline__item--{$side}">
                <div class="about-timeline__marker">
                    <span class="about-timeline__year">{$year}</span>
                </div>
                <div class="about-timeline__card">
                    <h3 class="about-timeline__card-title">{$ttl}</h3>
                    <p class="about-timeline__card-desc">{$desc}</p>
                </div>
            </div>
            HTML;
        }

        return <<<HTML
        <section class="about-timeline section section--alt" id="about-story">
            <div class="container">
                <div class="section__header">
                    <h2 class="section__heading">{$title}</h2>
                    <p class="section__subheading">{$description}</p>
                </div>
                <div class="about-timeline__track">
                    <div class="about-timeline__line" aria-hidden="true"></div>
                    {$items}
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       4. Experience Counters
       ================================================================ */
    private function renderExperience(array $cfg): string
    {
        $title       = esc($cfg['title'] ?? '');
        $years       = esc($cfg['years'] ?? '8');
        $projects    = esc($cfg['projects'] ?? '500');
        $industries  = esc($cfg['industries'] ?? '15');
        $description = esc($cfg['description'] ?? '');

        return <<<HTML
        <section class="about-experience section" id="about-experience">
            <div class="container">
                <div class="section__header">
                    <h2 class="section__heading">{$title}</h2>
                    <p class="section__subheading">{$description}</p>
                </div>
                <div class="about-experience__counters">
                    <div class="about-experience__counter">
                        <span class="about-experience__number" data-count="{$years}">{$years}</span>
                        <span class="about-experience__suffix">+</span>
                        <span class="about-experience__label">Years Experience</span>
                    </div>
                    <div class="about-experience__counter">
                        <span class="about-experience__number" data-count="{$projects}">{$projects}</span>
                        <span class="about-experience__suffix">+</span>
                        <span class="about-experience__label">Projects Completed</span>
                    </div>
                    <div class="about-experience__counter">
                        <span class="about-experience__number" data-count="{$industries}">{$industries}</span>
                        <span class="about-experience__suffix">+</span>
                        <span class="about-experience__label">Industries Served</span>
                    </div>
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       5. Core Services (loaded dynamically from services table)
       ================================================================ */
    private function renderCoreServices(array $cfg): string
    {
        $title       = esc($cfg['title'] ?? '');
        $description = esc($cfg['description'] ?? '');
        $limit       = (int)($cfg['display_count'] ?? 6);
        $layout      = esc($cfg['layout'] ?? 'grid');

        $services = $this->db->fetchAll(
            "SELECT * FROM services WHERE status = 'published' ORDER BY sort_order ASC LIMIT ?",
            [$limit]
        );

        if (empty($services)) {
            return '';
        }

        $cards = '';
        foreach ($services as $svc) {
            $icon  = esc($svc['icon'] ?? '');
            $name  = esc($svc['title'] ?? '');
            $desc  = esc($svc['short_description'] ?? '');
            $slug  = esc($svc['slug'] ?? '#');
            $cards .= <<<HTML
            <a href="/services/{$slug}" class="about-services__card">
                <span class="about-services__icon" aria-hidden="true">{$icon}</span>
                <h3 class="about-services__name">{$name}</h3>
                <p class="about-services__desc">{$desc}</p>
            </a>
            HTML;
        }

        return <<<HTML
        <section class="about-services section" id="about-services">
            <div class="container">
                <div class="section__header">
                    <h2 class="section__heading">{$title}</h2>
                    <p class="section__subheading">{$description}</p>
                </div>
                <div class="about-services__grid about-services__grid--{$layout}">
                    {$cards}
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       6. Working Process
       ================================================================ */
    private function renderWorkingProcess(array $cfg): string
    {
        $title       = esc($cfg['title'] ?? '');
        $description = esc($cfg['description'] ?? '');
        $steps       = $cfg['steps'] ?? [];

        $stepHtml = '';
        $count    = count($steps);
        foreach ($steps as $i => $step) {
            $icon = esc($step['icon'] ?? 'check');
            $ttl  = esc($step['title'] ?? '');
            $desc = esc($step['description'] ?? '');
            $num  = $i + 1;
            $connector = ($i < $count - 1) ? '<div class="about-process__connector" aria-hidden="true"></div>' : '';
            $stepHtml .= <<<HTML
            <div class="about-process__step">
                <div class="about-process__step-number">
                    <span class="about-process__step-icon" aria-hidden="true">{$icon}</span>
                    <span class="about-process__step-num">{$num}</span>
                </div>
                <div class="about-process__step-body">
                    <h3 class="about-process__step-title">{$ttl}</h3>
                    <p class="about-process__step-desc">{$desc}</p>
                </div>
                {$connector}
            </div>
            HTML;
        }

        return <<<HTML
        <section class="about-process section section--alt" id="about-process">
            <div class="container">
                <div class="section__header">
                    <h2 class="section__heading">{$title}</h2>
                    <p class="section__subheading">{$description}</p>
                </div>
                <div class="about-process__steps">
                    {$stepHtml}
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       7. Why Work With Me
       ================================================================ */
    private function renderWhyWorkWithMe(array $cfg): string
    {
        $title       = esc($cfg['title'] ?? '');
        $description = esc($cfg['description'] ?? '');
        $cards       = $cfg['cards'] ?? [];

        $cardHtml = '';
        foreach ($cards as $card) {
            $icon = esc($card['icon'] ?? 'star');
            $ttl  = esc($card['title'] ?? '');
            $desc = esc($card['description'] ?? '');
            $cardHtml .= <<<HTML
            <div class="about-why__card">
                <span class="about-why__card-icon about-why__card-icon--{$icon}" aria-hidden="true"></span>
                <h3 class="about-why__card-title">{$ttl}</h3>
                <p class="about-why__card-desc">{$desc}</p>
            </div>
            HTML;
        }

        return <<<HTML
        <section class="about-why section" id="about-why">
            <div class="container">
                <div class="section__header">
                    <h2 class="section__heading">{$title}</h2>
                    <p class="section__subheading">{$description}</p>
                </div>
                <div class="about-why__grid">
                    {$cardHtml}
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       8. Skills
       ================================================================ */
    private function renderSkills(array $cfg): string
    {
        $title       = esc($cfg['title'] ?? '');
        $description = esc($cfg['description'] ?? '');
        $style       = esc($cfg['display_style'] ?? 'bars');
        $skills      = $cfg['skills'] ?? [];

        $groups = [];
        foreach ($skills as $skill) {
            $cat = $skill['category'] ?? 'General';
            $groups[$cat][] = $skill;
        }

        $groupHtml = '';
        foreach ($groups as $cat => $catSkills) {
            $skillItems = '';
            foreach ($catSkills as $skill) {
                $name       = esc($skill['name'] ?? '');
                $percentage = (int)($skill['percentage'] ?? 0);
                if ($style === 'bars') {
                    $skillItems .= <<<HTML
                    <div class="about-skills__bar-item">
                        <div class="about-skills__bar-header">
                            <span class="about-skills__bar-name">{$name}</span>
                            <span class="about-skills__bar-pct">{$percentage}%</span>
                        </div>
                        <div class="about-skills__bar-track" role="progressbar" aria-valuenow="{$percentage}" aria-valuemin="0" aria-valuemax="100" aria-label="{$name} proficiency">
                            <div class="about-skills__bar-fill" style="width:{$percentage}%"></div>
                        </div>
                    </div>
                    HTML;
                } else {
                    $skillItems .= '<span class="about-skills__tag">' . $name . '</span>';
                }
            }
            $groupHtml .= <<<HTML
            <div class="about-skills__group">
                <h3 class="about-skills__group-title">{$cat}</h3>
                {$skillItems}
            </div>
            HTML;
        }

        return <<<HTML
        <section class="about-skills section section--alt" id="about-skills">
            <div class="container">
                <div class="section__header">
                    <h2 class="section__heading">{$title}</h2>
                    <p class="section__subheading">{$description}</p>
                </div>
                <div class="about-skills__grid">
                    {$groupHtml}
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       9. Tools & Technologies
       ================================================================ */
    private function renderTools(array $cfg): string
    {
        $title       = esc($cfg['title'] ?? '');
        $description = esc($cfg['description'] ?? '');
        $tools       = $cfg['tools'] ?? [];

        $groups = [];
        foreach ($tools as $tool) {
            $cat = $tool['category'] ?? 'Tools';
            $groups[$cat][] = $tool;
        }

        $groupHtml = '';
        foreach ($groups as $cat => $catTools) {
            $toolItems = '';
            foreach ($catTools as $tool) {
                $name = esc($tool['name'] ?? '');
                $logo = esc($tool['logo_url'] ?? '');
                $img  = $logo ? '<img src="' . $logo . '" alt="' . $name . '" loading="lazy" class="about-tools__item-logo">' : '<span class="about-tools__item-fallback" aria-hidden="true">' . strtoupper(substr($name, 0, 2)) . '</span>';
                $toolItems .= '<div class="about-tools__item">' . $img . '<span class="about-tools__item-name">' . $name . '</span></div>';
            }
            $groupHtml .= <<<HTML
            <div class="about-tools__group">
                <h3 class="about-tools__group-title">{$cat}</h3>
                <div class="about-tools__list">
                    {$toolItems}
                </div>
            </div>
            HTML;
        }

        return <<<HTML
        <section class="about-tools section" id="about-tools">
            <div class="container">
                <div class="section__header">
                    <h2 class="section__heading">{$title}</h2>
                    <p class="section__subheading">{$description}</p>
                </div>
                <div class="about-tools__grid">
                    {$groupHtml}
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       10. Testimonials (loaded dynamically)
       ================================================================ */
    private function renderTestimonials(array $cfg): string
    {
        $title       = esc($cfg['title'] ?? '');
        $description = esc($cfg['description'] ?? '');
        $limit       = (int)($cfg['display_count'] ?? 6);

        $testimonials = $this->db->fetchAll(
            "SELECT * FROM global_blocks WHERE block_type = 'testimonials' AND status = 'published' ORDER BY created_at DESC LIMIT ?",
            [$limit]
        );

        if (empty($testimonials)) {
            return '';
        }

        $items = '';
        foreach ($testimonials as $t) {
            $content = json_decode($t['content'] ?? '{}', true) ?: [];
            $quote   = esc($content['quote'] ?? '');
            $author  = esc($content['author'] ?? '');
            $role    = esc($content['role'] ?? '');
            $items   .= <<<HTML
            <div class="about-testimonials__item">
                <blockquote class="about-testimonials__quote">{$quote}</blockquote>
                <cite class="about-testimonials__cite">
                    <strong class="about-testimonials__author">{$author}</strong>
                    <span class="about-testimonials__role">{$role}</span>
                </cite>
            </div>
            HTML;
        }

        return <<<HTML
        <section class="about-testimonials section section--alt" id="about-testimonials">
            <div class="container">
                <div class="section__header">
                    <h2 class="section__heading">{$title}</h2>
                    <p class="section__subheading">{$description}</p>
                </div>
                <div class="about-testimonials__slider">
                    {$items}
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       11. FAQ
       ================================================================ */
    private function renderFaq(array $cfg): string
    {
        $title       = esc($cfg['title'] ?? '');
        $description = esc($cfg['description'] ?? '');
        $style       = esc($cfg['style'] ?? 'accordion');
        $items       = $cfg['items'] ?? [];

        if (empty($items)) {
            return '';
        }

        $faqHtml = '';
        foreach ($items as $i => $item) {
            $q = esc($item['question'] ?? '');
            $a = esc($item['answer'] ?? '');
            $id = 'faq-' . $i;
            $faqHtml .= <<<HTML
            <div class="about-faq__item">
                <button class="about-faq__question" aria-expanded="false" aria-controls="about-faq-answer-{$i}">
                    <span>{$q}</span>
                    <svg class="about-faq__chevron" width="16" height="10" viewBox="0 0 16 10" fill="none" aria-hidden="true">
                        <path d="M1 1L8 8L15 1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="about-faq__answer" id="about-faq-answer-{$i}" role="region" aria-labelledby="about-faq-q-{$i}" hidden>
                    <div class="about-faq__answer-inner">
                        <p>{$a}</p>
                    </div>
                </div>
            </div>
            HTML;
        }

        return <<<HTML
        <section class="about-faq section" id="about-faq">
            <div class="container container--narrow">
                <div class="section__header">
                    <h2 class="section__heading">{$title}</h2>
                    <p class="section__subheading">{$description}</p>
                </div>
                <div class="about-faq__list about-faq__list--{$style}">
                    {$faqHtml}
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       12. Final CTA
       ================================================================ */
    private function renderFinalCta(array $cfg): string
    {
        $heading     = esc($cfg['heading'] ?? '');
        $description = esc($cfg['description'] ?? '');
        $primaryText = esc($cfg['primary_text'] ?? '');
        $primaryUrl  = esc($cfg['primary_url'] ?? '/contact');
        $waText      = esc($cfg['whatsapp_text'] ?? '');
        $showWa      = !empty($cfg['show_whatsapp']);
        $bgImage     = esc($cfg['background_image'] ?? '');

        $bgStyle = $bgImage ? ' style="background-image:url(' . $bgImage . ')"' : '';

        $waHtml = '';
        if ($showWa && $waText) {
            $waHtml = '<button type="button" class="btn btn--outline btn--lg about-cta__btn js-whatsapp-open">' . $waText . '</button>';
        }

        return <<<HTML
        <section class="about-cta section section--dark" id="about-cta"{$bgStyle}>
            <div class="container">
                <div class="about-cta__content">
                    <h2 class="about-cta__heading">{$heading}</h2>
                    <p class="about-cta__desc">{$description}</p>
                    <div class="about-cta__actions">
                        <a href="{$primaryUrl}" class="btn btn--accent btn--lg">{$primaryText}</a>
                        {$waHtml}
                    </div>
                </div>
            </div>
        </section>
        HTML;
    }

    /**
     * Render empty state when no sections exist.
     */
    private function renderEmptyState(): string
    {
        return <<<HTML
        <section class="about-empty section">
            <div class="container">
                <div class="empty-state">
                    <h1>About Page</h1>
                    <p>The about page is being set up. Please check back soon.</p>
                </div>
            </div>
        </section>
        HTML;
    }

    /**
     * Convert snake_case to CamelCase for method lookup.
     */
    private function camelCase(string $str): string
    {
        return str_replace('_', '', ucwords($str, '_'));
    }
}
