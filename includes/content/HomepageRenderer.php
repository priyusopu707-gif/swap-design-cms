<?php
/**
 * Swap Design - Homepage Renderer
 *
 * Renders all 11 homepage sections from HomepageManager config.
 * Each section has its own renderer method producing semantic HTML.
 *
 * Sections 1-3,5-6,10-11: Content stored inline in section config.
 * Sections 4,7,8: Dynamically queried from content entries / portfolio.
 * Section 9: Inline FAQ items from config.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class HomepageRenderer
{
    private HomepageManager $manager;
    private Database $db;

    public function __construct()
    {
        $this->manager = new HomepageManager();
        $this->db      = Database::getInstance();
    }

    /**
     * Render the full homepage: all active sections in order.
     *
     * @return string Complete homepage HTML
     */
    public function render(): string
    {
        $sections = $this->manager->getActive();

        /* Auto-seed default sections on first load if none published */
        if (empty($sections)) {
            $this->manager->seedDefaults();
            $sections = $this->manager->getActive();
        }

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
     * Set page-level CSS and JS globals for the homepage.
     */
    public function getPageAssets(): array
    {
        return [
            'css' => ['/assets/css/homepage.css'],
            'js'  => ['/assets/js/homepage.js'],
        ];
    }

    /**
     * Get homepage schema markup.
     */
    public function getSchema(): string
    {
        global $site;
        $name    = $site->brand->name;
        $url     = $site->urls->base;
        $desc    = $site->brand->description;

        return '<script type="application/ld+json">' . json_encode([
            '@context'        => 'https://schema.org',
            '@type'           => 'WebSite',
            'name'            => $name,
            'url'             => $url,
            'description'     => $desc,
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => $url . '/search?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }

    /* ================================================================
       1. Hero Section
       ================================================================ */
    private function renderHero(array $cfg): string
    {
        $heading         = esc($cfg['heading'] ?? '');
        $highlight       = esc($cfg['highlight_text'] ?? '');
        $sub             = esc($cfg['sub_heading'] ?? '');
        $desc            = esc($cfg['description'] ?? '');
        $brandAlt        = esc($cfg['hero_alt'] ?? 'Swap Design — Premium Web Design & Branding');
        $primaryText     = esc($cfg['primary_button_text'] ?? '');
        $primaryUrl      = esc($cfg['primary_button_url'] ?? '#');
        $secondaryText   = esc($cfg['secondary_button_text'] ?? '');
        $secondaryUrl    = esc($cfg['secondary_button_url'] ?? '#');
        $bgImage         = esc($cfg['background_image'] ?? '');
        $heroImage       = esc($cfg['hero_image'] ?? '');

        $headingWithHighlight = '';
        if ($highlight && strpos($heading, $highlight) !== false) {
            $headingWithHighlight = str_replace(
                $highlight,
                '<span class="hero__highlight">' . $highlight . '</span>',
                $heading
            );
        } else {
            $headingWithHighlight = $heading;
        }

        $bgStyle = $bgImage ? ' style="background-image:url(' . $bgImage . ')"' : '';

        /* Optional hero visual — show a branded glass panel when no real image is set. */
        if ($heroImage) {
            $heroVisual = <<<HTML
            <div class="hero__image-frame glass glass--glow float" data-reveal>
                <img src="{$heroImage}"
                     alt="{$brandAlt}"
                     class="hero__image"
                     width="560" height="480"
                     loading="eager"
                     decoding="async">
            </div>
            HTML;
        } else {
            $heroVisual = $this->renderHeroPanel();
        }

        $heroId = 'hero-' . substr(md5(uniqid('h', true)), 0, 8);

        return <<<HTML
        <section class="hero" id="hero"{$bgStyle} role="banner" aria-labelledby="hero-heading">
            <div class="hero__gradient"></div>
            <div class="hero__overlay"></div>
            <span class="ambient ambient--primary blob-1" aria-hidden="true"></span>
            <span class="ambient ambient--accent blob-2" aria-hidden="true"></span>
            <div class="hero__particles" aria-hidden="true">
                <span class="hero__particle"></span>
                <span class="hero__particle"></span>
                <span class="hero__particle"></span>
                <span class="hero__particle"></span>
                <span class="hero__particle"></span>
                <span class="hero__particle"></span>
                <span class="hero__particle"></span>
                <span class="hero__particle"></span>
            </div>
            <div class="hero__tech-chips" aria-hidden="true">
                <span class="hero__chip hero__chip--float hero__chip--1">WordPress</span>
                <span class="hero__chip hero__chip--float hero__chip--2">UI/UX</span>
                <span class="hero__chip hero__chip--float hero__chip--3">Branding</span>
                <span class="hero__chip hero__chip--float hero__chip--4">Design</span>
            </div>
            <div class="hero__container container">
                <div class="hero__content" data-reveal>
                    <span class="hero__badge" data-reveal-delay="50">
                        <span class="hero__badge-dot" aria-hidden="true"></span>
                        Available for Projects
                    </span>
                    <p class="hero__kicker" data-reveal-delay="100">{$sub}</p>
                    <h1 class="hero__heading" id="hero-heading" data-reveal-delay="200">{$headingWithHighlight}</h1>
                    <p class="hero__description" data-reveal-delay="350">{$desc}</p>
                    <div class="hero__actions" data-reveal-delay="500">
                        <a href="{$primaryUrl}" class="btn btn--cta btn--lg magnetic hero__btn" data-magnetic>{$primaryText}</a>
                        <a href="{$secondaryUrl}" class="btn btn--outline btn--lg hero__btn">{$secondaryText}</a>
                    </div>
                    <div class="hero__trust" data-reveal-delay="650">
                        <span class="hero__trust-stat"><strong>100+</strong> Projects Delivered</span>
                        <span class="hero__trust-sep"></span>
                        <span class="hero__trust-stat"><strong>8+</strong> Years Experience</span>
                    </div>
                </div>
                <div class="hero__image-wrap" data-reveal data-reveal-delay="300">
                    <div class="hero__visual-wrap" data-parallax="0.04">
                        {$heroVisual}
                    </div>
                </div>
            </div>
            <div class="hero__scroll" aria-hidden="true">
                <span class="hero__scroll-mouse"><span class="hero__scroll-wheel"></span></span>
                <span class="hero__scroll-text">Scroll</span>
            </div>
        </section>
        HTML;
    }

    /**
     * Branded glass panel shown in the hero when no hero_image is configured.
     * A professional visual placeholder, not an empty/broken image.
     */
    private function renderHeroPanel(): string
    {
        global $site;
        $name = $site->brand->name ?? 'Swap Design';
        $experience = $site->brand->experience ?? '8+ Years';
        $services = $site->brand->description ?? '';

        return <<<HTML
        <div class="hero__panel perspective" data-tilt="4" aria-hidden="true">
            <div class="hero__panel-glow" aria-hidden="true"></div>
            <div class="hero__panel-inner glass glass--elevated">
                <div class="hero__panel-mast">
                    <span class="hero__panel-logo">SD</span>
                    <div class="hero__panel-brand">
                        <span class="hero__panel-name">{$name}</span>
                        <span class="hero__panel-exp">{$experience} Experience</span>
                    </div>
                </div>
                <div class="hero__panel-chips">
                    <span class="hero__panel-chip">WordPress</span>
                    <span class="hero__panel-chip">Design</span>
                    <span class="hero__panel-chip">Branding</span>
                    <span class="hero__panel-chip">UI/UX</span>
                </div>
            </div>
        </div>
        HTML;
    }

    /* ================================================================
       2. About Section
       ================================================================ */
    private function renderAbout(array $cfg): string
    {
        $title       = esc($cfg['title'] ?? '');
        $subtitle    = esc($cfg['subtitle'] ?? '');
        $description = esc($cfg['description'] ?? '');
        $image       = esc($cfg['image'] ?? '');
        $btnText     = esc($cfg['button_text'] ?? '');
        $btnUrl      = esc($cfg['button_url'] ?? '/about');
        $features    = $cfg['features'] ?? [];

        $featuresHtml = '';
        foreach ($features as $f) {
            $icon = esc($f['icon'] ?? 'check');
            $text = esc($f['text'] ?? '');
            $iconSvg = $this->inlineIcon($icon === 'check' ? 'check-circle' : $icon);
            $featuresHtml .= <<<HTML
            <li class="about__feature-item">
                <span class="about__feature-icon about__feature-icon--{$icon}" aria-hidden="true">{$iconSvg}</span>
                <span>{$text}</span>
            </li>
            HTML;
        }

        $imageHtml = '';
        if ($image) {
            $imageHtml = <<<HTML
            <div class="about__image-wrap fade-in">
                <img src="{$image}" alt="About Swap Design" loading="lazy" width="540" height="450" class="about__image">
            </div>
            HTML;
        }

        return <<<HTML
        <section class="about section" id="about" aria-labelledby="about-heading">
            <div class="container">
                <div class="about__editorial">
                    {$imageHtml}
                    <div class="about__content about__content--inset fade-in">
                        <div class="about__accent-bar" aria-hidden="true"></div>
                        <p class="section__subtitle">{$subtitle}</p>
                        <h2 class="section__heading" id="about-heading">{$title}</h2>
                        <p class="about__description">{$description}</p>
                        <ul class="about__features" role="list">
                            {$featuresHtml}
                        </ul>
                        <a href="{$btnUrl}" class="btn btn--primary about__btn">{$btnText}</a>
                    </div>
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       3. Experience Counter Section
       ================================================================ */
    private function renderExperience(array $cfg): string
    {
        $title = esc($cfg['title'] ?? '');
        $items = $cfg['items'] ?? [];

        $countersHtml = '';
        foreach ($items as $item) {
            $number = esc($item['number'] ?? '');
            $suffix = esc($item['suffix'] ?? '');
            $label  = esc($item['label'] ?? '');
            $countersHtml .= <<<HTML
            <div class="card card--stat card--lift glass" data-reveal>
                <div class="card__number"><span data-counter="{$number}">0</span>{$suffix}</div>
                <div class="card__label">{$label}</div>
            </div>
            HTML;
        }

        return <<<HTML
        <section class="experience section section--alt" id="experience" aria-labelledby="experience-heading">
            <div class="container">
                <h2 class="section__title" id="experience-heading">{$title}</h2>
                <div class="experience__radial" data-reveal>
                    <div class="experience__badge glass glass--elevated">
                        <span class="experience__badge-icon" aria-hidden="true">★</span>
                        <span class="experience__badge-text">Swap Design</span>
                    </div>
                    <div class="experience__stats">
                        {$countersHtml}
                    </div>
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       4. Services Section (dynamic - content entries)
       ================================================================ */
    private function renderServices(array $cfg): string
    {
        $title       = esc($cfg['title'] ?? 'What We Offer');
        $description = esc($cfg['description'] ?? '');
        $count       = (int)($cfg['display_count'] ?? 6);
        $sort        = $cfg['sort_order'] ?? 'newest';
        $layoutCls   = $cfg['layout'] ?? 'grid';
        $viewAllText = esc($cfg['view_all_text'] ?? '');
        $viewAllUrl  = esc($cfg['view_all_url'] ?? '/services');

        $entries = $this->getServiceEntries($count, $sort);

        if (empty($entries)) {
            $entries = $this->getDefaultServices($count);
        }

        $featuredHtml = '';
        $supportHtml  = '';
        $index        = 0;
        foreach ($entries as $entry) {
            $eSlug     = esc($entry['slug'] ?? '');
            $icon      = $entry['icon'] ?? '';
            $eTitle    = esc($entry['title'] ?? '');
            $eExcerpt  = esc($entry['excerpt'] ?? $entry['description'] ?? '');
            $index++;

            $cardIcon = $this->serviceIcon($icon);

            $cardInner = <<<HTML
                <a href="/services/{$eSlug}" class="card__body" aria-labelledby="svc-title-{$index}" style="text-decoration:none;color:inherit;display:flex;flex-direction:column;gap:var(--ds-space-16)">
                    <span class="card__icon cp-icon-wrap">
                        {$cardIcon}
                    </span>
                    <h3 class="card__title" id="svc-title-{$index}">{$eTitle}</h3>
                    <p class="card__desc">{$eExcerpt}</p>
                    <span class="card__arrow">&rarr;</span>
                </a>
            HTML;

            if ($index === 1) {
                $featuredHtml = <<<HTML
                <article class="card card--service card--lift card--glow glass services__featured" data-reveal>
                    {$cardInner}
                </article>
                HTML;
            } else {
                $supportHtml .= <<<HTML
                <article class="card card--service card--lift card--glow glass" data-reveal>
                    {$cardInner}
                </article>
                HTML;
            }
        }

        $viewAllHtml = '';
        if ($viewAllText) {
            $viewAllHtml = <<<HTML
            <div class="services__cta-wrap" data-reveal>
                <a href="{$viewAllUrl}" class="btn btn--outline btn--lg">{$viewAllText}</a>
            </div>
            HTML;
        }

        return <<<HTML
        <section class="services section section--top-glow" id="services" aria-labelledby="services-heading">
            <div class="container">
                <div class="section__header" data-reveal>
                    <span class="section__eyebrow">Our Services</span>
                    <h2 class="section__title" id="services-heading">{$title}</h2>
                    <p class="section__subtitle">{$description}</p>
                </div>
                <div class="services__featured-grid" data-reveal>
                    {$featuredHtml}
                </div>
                <div class="grid grid--3" style="margin-top:var(--ds-space-32)">
                    {$supportHtml}
                </div>
                {$viewAllHtml}
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       5. Why Choose Us Section
       ================================================================ */
    private function renderWhyChoose(array $cfg): string
    {
        $title       = esc($cfg['title'] ?? '');
        $description = esc($cfg['description'] ?? '');
        $image       = esc($cfg['image'] ?? '');
        $items       = $cfg['items'] ?? [];

        $featuredItem = $items[0] ?? null;
        $stackedItems = array_slice($items, 1);

        $featuredHtml = '';
        if ($featuredItem) {
            $icon = esc($featuredItem['icon'] ?? 'star');
            $itTitle = esc($featuredItem['title'] ?? '');
            $itDesc  = esc($featuredItem['description'] ?? '');
            $iconSvg = $this->inlineIcon($icon);
            $featuredHtml = <<<HTML
            <div class="why-choose__featured glass glass--elevated card card--lift" data-reveal>
                <div class="why-choose__featured-icon cp-icon-wrap" aria-hidden="true">{$iconSvg}</div>
                <h3 class="why-choose__featured-title">{$itTitle}</h3>
                <p class="why-choose__featured-desc">{$itDesc}</p>
            </div>
            HTML;
        }

        $stackedHtml = '';
        foreach ($stackedItems as $idx => $item) {
            $icon = esc($item['icon'] ?? 'star');
            $itTitle = esc($item['title'] ?? '');
            $itDesc  = esc($item['description'] ?? '');
            $iconSvg = $this->inlineIcon($icon);
            $stackedHtml .= <<<HTML
            <div class="why-choose__stacked-card card card--service card--lift glass" data-reveal>
                <span class="card__icon card__icon--{$icon} cp-icon-wrap" aria-hidden="true">{$iconSvg}</span>
                <h3 class="card__title">{$itTitle}</h3>
                <p class="card__desc">{$itDesc}</p>
            </div>
            HTML;
        }

        $imageHtml = '';
        if ($image) {
            $imageHtml = <<<HTML
            <div class="why-choose__image-wrap" data-reveal>
                <img src="{$image}" alt="Why Choose Swap Design" loading="lazy" width="500" height="500" class="why-choose__image">
            </div>
            HTML;
        }

        return <<<HTML
        <section class="why-choose section section--alt" id="why-choose" aria-labelledby="why-heading">
            <div class="container">
                <div class="section__header" data-reveal>
                    <span class="section__eyebrow">Why Choose Us</span>
                    <h2 class="section__title" id="why-heading">{$title}</h2>
                    <p class="section__subtitle">{$description}</p>
                </div>
                <div class="why-choose__grid--featured">
                    <div class="why-choose__featured-col">
                        {$imageHtml}
                        {$featuredHtml}
                    </div>
                    <div class="why-choose__stacked-col">
                        {$stackedHtml}
                    </div>
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       5b. Problems We Solve Section
       ================================================================ */
    private function renderProblemsSolve(array $cfg): string
    {
        $title       = esc($cfg['title'] ?? '');
        $description = esc($cfg['description'] ?? '');
        $items       = $cfg['items'] ?? [];

        $itemsHtml = '';
        foreach ($items as $idx => $item) {
            $icon   = esc($item['icon'] ?? '');
            $iTitle = esc($item['title'] ?? '');
            $iDesc  = esc($item['description'] ?? '');
            $delay  = $idx * 80;
            $iconSvg = $this->inlineIcon($icon);
            $side   = ($idx % 2 === 0) ? 'problem-card--left' : 'problem-card--right';
            $span   = ($idx % 3 === 0) ? ' problem-card--tall' : '';

            $itemsHtml .= <<<HTML
            <div class="problem-card {$side}{$span}" data-reveal data-reveal-delay="{$delay}" data-tilt="3">
                <span class="problem-card__icon problem-card__icon--{$icon} cp-icon-wrap" aria-hidden="true">{$iconSvg}</span>
                <h3 class="problem-card__title">{$iTitle}</h3>
                <p class="problem-card__desc">{$iDesc}</p>
            </div>
            HTML;
        }

        return <<<HTML
        <section class="problems section section--alt" id="problems" aria-labelledby="problems-heading">
            <div class="container">
                <h2 class="section__heading" id="problems-heading">{$title}</h2>
                <p class="section__description">{$description}</p>
                <div class="problems__grid problems__grid--masonry">
                    {$itemsHtml}
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       5b. Why Work With Me Section
       ================================================================ */
    private function renderWhyWorkMe(array $cfg): string
    {
        $title       = esc($cfg['title'] ?? '');
        $description = esc($cfg['description'] ?? '');
        $items       = $cfg['items'] ?? [];

        $itemsHtml = '';
        foreach ($items as $idx => $item) {
            $icon   = esc($item['icon'] ?? '');
            $iTitle = esc($item['title'] ?? '');
            $iDesc  = esc($item['description'] ?? '');
            $delay  = $idx * 80;
            $iconSvg = $this->inlineIcon($icon);
            $side   = ($idx % 2 === 0) ? 'work-me-card--left' : 'work-me-card--right';
            $num    = $idx + 1;

            $itemsHtml .= <<<HTML
            <div class="work-me-card {$side}" data-reveal data-reveal-delay="{$delay}" data-tilt="3">
                <span class="work-me-card__node" aria-hidden="true">{$num}</span>
                <span class="work-me-card__icon work-me-card__icon--{$icon} cp-icon-wrap cp-icon-wrap--white" aria-hidden="true">{$iconSvg}</span>
                <h3 class="work-me-card__title">{$iTitle}</h3>
                <p class="work-me-card__desc">{$iDesc}</p>
            </div>
            HTML;
        }

        return <<<HTML
        <section class="section section--top-glow work-me" id="work-me" aria-labelledby="work-me-heading">
            <div class="container">
                <h2 class="section__heading" id="work-me-heading">{$title}</h2>
                <p class="section__description">{$description}</p>
                <div class="work-me__timeline">
                    {$itemsHtml}
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       5c. Industries Section
       ================================================================ */
    private function renderIndustries(array $cfg): string
    {
        $title       = esc($cfg['title'] ?? '');
        $description = esc($cfg['description'] ?? '');
        $items       = $cfg['items'] ?? [];

        $featuredItems = array_slice($items, 0, 3);
        $cloudItems   = array_slice($items, 3);

        $featuredHtml = '';
        foreach ($featuredItems as $idx => $item) {
            $icon   = esc($item['icon'] ?? '');
            $iTitle = esc($item['title'] ?? '');
            $iDesc  = esc($item['description'] ?? '');
            $delay  = $idx * 70;
            $iconSvg = $this->inlineIcon($icon);

            $featuredHtml .= <<<HTML
            <div class="industry-card industry-card--featured" data-reveal data-reveal-delay="{$delay}" data-tilt="2">
                <span class="industry-card__icon industry-card__icon--{$icon} cp-icon-wrap" aria-hidden="true">{$iconSvg}</span>
                <h3 class="industry-card__title">{$iTitle}</h3>
                <p class="industry-card__desc">{$iDesc}</p>
            </div>
            HTML;
        }

        $cloudHtml = '';
        foreach ($cloudItems as $idx => $item) {
            $icon   = esc($item['icon'] ?? '');
            $iTitle = esc($item['title'] ?? '');
            $delay  = $idx * 40;
            $iconSvg = $this->inlineIcon($icon);

            $cloudHtml .= <<<HTML
            <div class="industry-card industry-card--cloud" data-reveal data-reveal-delay="{$delay}" data-tilt="2">
                <span class="industry-card__icon industry-card__icon--{$icon} cp-icon-wrap" aria-hidden="true">{$iconSvg}</span>
                <h3 class="industry-card__title">{$iTitle}</h3>
            </div>
            HTML;
        }

        return <<<HTML
        <section class="section industries section--alt" id="industries" aria-labelledby="industries-heading">
            <div class="container">
                <h2 class="section__heading" id="industries-heading">{$title}</h2>
                <p class="section__description">{$description}</p>
                <div class="industries__featured">
                    {$featuredHtml}
                </div>
                <div class="industries__cloud">
                    {$cloudHtml}
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       5d. Technologies Section
       ================================================================ */
    private function renderTechnologies(array $cfg): string
    {
        $title       = esc($cfg['title'] ?? '');
        $description = esc($cfg['description'] ?? '');
        $items       = $cfg['items'] ?? [];

        $itemsHtml = '';
        foreach ($items as $idx => $item) {
            $icon   = esc($item['icon'] ?? '');
            $iTitle = esc($item['title'] ?? '');
            $iDesc  = esc($item['description'] ?? '');
            $delay  = $idx * 50;
            $iconSvg = $this->simpleIcon($icon);
            $sizeMod = match(true) {
                ($idx % 5 === 0) => ' tech-item--large',
                ($idx % 3 === 0) => ' tech-item--medium',
                default          => '',
            };

            $itemsHtml .= <<<HTML
            <div class="tech-item{$sizeMod}" data-tech="{$icon}" data-reveal data-reveal-delay="{$delay}" data-parallax="0.02">
                <span class="tech-item__icon tech-item__icon--{$icon} tech-item__icon--brand cp-icon-wrap" aria-hidden="true">{$iconSvg}</span>
                <span class="tech-item__name">{$iTitle}</span>
                <span class="tech-item__desc">{$iDesc}</span>
            </div>
            HTML;
        }

        return <<<HTML
        <section class="section section--top-glow technologies" id="technologies" aria-labelledby="technologies-heading">
            <div class="container">
                <h2 class="section__heading" id="technologies-heading">{$title}</h2>
                <p class="section__description">{$description}</p>
                <div class="technologies__wall">
                    {$itemsHtml}
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       6. Process Section
       ================================================================ */
    private function renderProcess(array $cfg): string
    {
        $title       = esc($cfg['title'] ?? '');
        $description = esc($cfg['description'] ?? '');
        $steps       = $cfg['steps'] ?? [];

        $stepsHtml = '';
        foreach ($steps as $idx => $step) {
            $icon  = esc($step['icon'] ?? '');
            $sTitle = esc($step['title'] ?? '');
            $sDesc  = esc($step['description'] ?? '');
            $num   = $idx + 1;
            $delay = $idx * 120;
            $iconSvg = $this->inlineIcon($icon);

            $stepsHtml .= <<<HTML
            <div class="process-step fade-in" style="animation-delay:{$delay}ms">
                <div class="process-step__node" aria-hidden="true">
                    <span class="process-card__num">{$num}</span>
                    <span class="process-card__icon process-card__icon--{$icon} cp-icon-wrap cp-icon-wrap--process" aria-hidden="true">{$iconSvg}</span>
                </div>
                <div class="process-step__content">
                    <h3 class="process-card__title">{$sTitle}</h3>
                    <p class="process-card__desc">{$sDesc}</p>
                </div>
            </div>
            HTML;
        }

        return <<<HTML
        <section class="process section" id="process" aria-labelledby="process-heading">
            <div class="container">
                <h2 class="section__heading" id="process-heading">{$title}</h2>
                <p class="section__description">{$description}</p>
                <div class="process__vertical">
                    {$stepsHtml}
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       7. Portfolio Preview Section (dynamic - portfolio_items table)
       ================================================================ */
    private function renderPortfolio(array $cfg): string
    {
        $title       = esc($cfg['title'] ?? '');
        $description = esc($cfg['description'] ?? '');
        $count       = (int)($cfg['display_count'] ?? 6);
        $showFilter  = !empty($cfg['show_filter']);
        $viewAllText = esc($cfg['view_all_text'] ?? '');
        $viewAllUrl  = esc($cfg['view_all_url'] ?? '/portfolio');
        $layoutCls   = $cfg['layout'] ?? 'grid';

        $items = $this->db->fetchAll(
            "SELECT id, title, slug, category, image_url, description
             FROM portfolio_items
             WHERE status = 'published'
             ORDER BY is_featured DESC, sort_order ASC
             LIMIT ?",
            [$count]
        );

        $usingPlaceholders = false;
        if (empty($items)) {
            $placeholders = !empty($cfg['show_placeholders']) ? $this->getPlaceholderPortfolio($count) : [];
            if (empty($placeholders)) {
                return $this->renderPortfolioEmptyState($cfg);
            }
            $items = $placeholders;
            $usingPlaceholders = true;
        }

        $categories = array_unique(array_column($items, 'category'));

        $filterHtml = '';
        if ($showFilter && count($categories) > 1) {
            $filterBtns = '<button type="button" class="pf-filter-btn pf-filter-btn--active" data-filter="all" aria-pressed="true">All</button>';
            foreach ($categories as $cat) {
                if (!$cat) continue;
                $filterBtns .= '<button type="button" class="pf-filter-btn" data-filter="' . esc($cat) . '" aria-pressed="false">' . esc($cat) . '</button>';
            }
            $filterHtml = '<div class="portfolio__filters" role="group" aria-label="Portfolio categories">' . $filterBtns . '</div>';
        }

        $featuredHtml = '';
        $secondaryHtml = '';
        $index = 0;
        foreach ($items as $idx => $item) {
            $cat    = esc($item['category'] ?? '');
            $iTitle = esc($item['title'] ?? '');
            $iSlug  = esc($item['slug'] ?? '');
            $image  = esc($item['image_url'] ?? '');
            $desc   = esc($item['description'] ?? '');
            $delay  = $idx * 100;
            $isPlaceholder = empty($item['image_url']);

            $imageHtml = $isPlaceholder
                ? '<div class="pf-card__placeholder" aria-hidden="true"><span class="pf-card__placeholder-icon">' . $this->inlineIcon('image') . '</span></div>'
                : '<img src="' . $image . '" alt="' . $iTitle . '" loading="lazy" width="400" height="300" class="pf-card__image">';

            $isFeaturedCard = ($index === 0);
            $articleClass = $isFeaturedCard ? 'pf-card pf-card--featured fade-in' : 'pf-card fade-in';

            $cardInner = <<<HTML
                <article class="{$articleClass}" data-category="{$cat}" style="animation-delay:{$delay}ms">
                    <a href="/portfolio/{$iSlug}" class="pf-card__link" aria-labelledby="pf-title-{$item['id']}">
                        <div class="pf-card__image-wrap">
                            {$imageHtml}
                            <div class="pf-card__overlay">
                                <span class="pf-card__view">View Project</span>
                            </div>
                        </div>
                        <div class="pf-card__body">
                            <span class="pf-card__category">{$cat}</span>
                            <h3 class="pf-card__title" id="pf-title-{$item['id']}">{$iTitle}</h3>
                            <p class="pf-card__desc">{$desc}</p>
                        </div>
                    </a>
                </article>
            HTML;

            if ($index === 0) {
                $featuredHtml = $cardInner;
            } elseif ($index <= 4) {
                $secondaryHtml .= $cardInner;
            }
            $index++;
        }

        $viewAllHtml = '';
        if ($viewAllText) {
            $viewAllHtml = <<<HTML
            <div class="portfolio__cta-wrap">
                <a href="{$viewAllUrl}" class="btn btn--outline btn--lg">{$viewAllText}</a>
            </div>
            HTML;
        }

        $demoBanner = $usingPlaceholders
            ? '<div class="demo-content-banner" role="status" aria-label="Demo content notice">DEMO CONTENT — Not real projects</div>'
            : '';

        return <<<HTML
        <section class="portfolio section section--alt section--top-glow" id="portfolio" aria-labelledby="portfolio-heading">
            <div class="container">
                <h2 class="section__heading" id="portfolio-heading">{$title}</h2>
                <p class="section__description">{$description}</p>
                {$demoBanner}
                {$filterHtml}
                <div class="portfolio__featured" data-reveal>
                    {$featuredHtml}
                </div>
                <div class="portfolio__secondary">
                    {$secondaryHtml}
                </div>
                {$viewAllHtml}
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       8. Testimonials Section (dynamic - content entries)
       ================================================================ */
    private function renderTestimonials(array $cfg): string
    {
        $title       = esc($cfg['title'] ?? '');
        $description = esc($cfg['description'] ?? '');
        $count       = (int)($cfg['display_count'] ?? 6);
        $style       = $cfg['display_style'] ?? 'carousel';
        $showAvatar  = !empty($cfg['show_avatars']);
        $showRating  = !empty($cfg['show_ratings']);

        $entries = $this->getContentEntries('testimonials', $count);

        /*
         * Approved: keep section visually complete, no fake testimonials.
         * If none are published, show an honest empty state rather than
         * fabricating client quotes.
         */
        $usingPlaceholders = false;
        if (empty($entries)) {
            $placeholders = !empty($cfg['show_placeholders']) ? $this->getPlaceholderTestimonials($count) : [];
            if (empty($placeholders)) {
                return $this->renderTestimonialsEmptyState($cfg);
            }
            $entries = $placeholders;
            $usingPlaceholders = true;
        }

        $itemsHtml = '';
        foreach ($entries as $idx => $entry) {
            $fields  = is_array($entry['fields']) ? $entry['fields'] : (json_decode($entry['fields'] ?? '{}', true) ?: []);
            $name    = esc($entry['title'] ?? $fields['name'] ?? '');
            $role    = esc($fields['role'] ?? $fields['position'] ?? '');
            $company = esc($fields['company'] ?? '');
            $quote   = esc($entry['excerpt'] ?? $fields['quote'] ?? $fields['text'] ?? '');
            $avatar  = esc($entry['featured_image'] ?? $fields['avatar'] ?? $fields['photo'] ?? '');
            $rating  = (int)($fields['rating'] ?? 5);
            $delay   = $idx * 100;
            $isFeatured = ($idx === 0);

            $ratingHtml = '';
            if ($showRating) {
                $stars = '';
                for ($s = 1; $s <= 5; $s++) {
                    $filled = $s <= $rating ? ' testimonial-card__star--filled' : '';
                    $stars .= '<span class="testimonial-card__star' . $filled . '" aria-hidden="true">&#9733;</span>';
                }
                $ratingHtml = '<div class="testimonial-card__rating" aria-label="' . $rating . ' out of 5 stars">' . $stars . '</div>';
            }

            $avatarHtml = '';
            if ($showAvatar && $avatar) {
                $avatarHtml = '<img src="' . $avatar . '" alt="' . $name . '" loading="lazy" width="56" height="56" class="testimonial-card__avatar">';
            } elseif ($showAvatar) {
                $initial = mb_substr($name, 0, 1);
                $avatarHtml = '<div class="testimonial-card__avatar testimonial-card__avatar--placeholder" aria-hidden="true">' . esc($initial) . '</div>';
            }

            $cardClass = $isFeatured ? 'testimonial-card testimonial-card--featured fade-in' : 'testimonial-card fade-in';
            $itemsHtml .= <<<HTML
            <div class="{$cardClass}" style="animation-delay:{$delay}ms">
                <blockquote class="testimonial-card__quote">
                    <p>{$quote}</p>
                </blockquote>
                <div class="testimonial-card__author">
                    {$avatarHtml}
                    <div class="testimonial-card__info">
                        <cite class="testimonial-card__name">{$name}</cite>
                        <span class="testimonial-card__role">{$role}</span>
                    </div>
                </div>
                {$ratingHtml}
            </div>
            HTML;
        }

        $demoBanner = $usingPlaceholders
            ? '<div class="demo-content-banner" role="status" aria-label="Demo content notice">DEMO CONTENT — Not real client reviews</div>'
            : '';

        return <<<HTML
        <section class="testimonials section section--top-glow" id="testimonials" aria-labelledby="testimonials-heading">
            <div class="container">
                <h2 class="section__heading" id="testimonials-heading">{$title}</h2>
                <p class="section__description">{$description}</p>
                {$demoBanner}
                <div class="testimonials__featured">
                    {$itemsHtml}
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       9. FAQ Section (inline items)
       ================================================================ */
    private function renderFaq(array $cfg): string
    {
        $title       = esc($cfg['title'] ?? '');
        $description = esc($cfg['description'] ?? '');
        $showSearch  = !empty($cfg['show_search']);
        $style       = $cfg['style'] ?? 'accordion';
        $items       = $cfg['items'] ?? [];

        $searchHtml = '';
        if ($showSearch) {
            $searchHtml = <<<HTML
            <div class="faq__search">
                <input type="text" class="faq__search-input js-faq-search" placeholder="Search questions..." aria-label="Search FAQ">
                <span class="faq__search-icon" aria-hidden="true">&#x1F50D;</span>
            </div>
            HTML;
        }

        $itemsHtml = '';
        foreach ($items as $idx => $item) {
            $q = esc($item['question'] ?? '');
            $a = esc($item['answer'] ?? '');

            $itemsHtml .= <<<HTML
            <div class="faq-item js-faq-item">
                <h3 class="faq-item__question">
                    <button class="faq-item__trigger js-faq-trigger"
                            aria-expanded="false"
                            aria-controls="faq-answer-{$idx}"
                            id="faq-question-{$idx}">
                        <span>{$q}</span>
                        <span class="faq-item__icon" aria-hidden="true"></span>
                    </button>
                </h3>
                <div class="faq-item__answer"
                     id="faq-answer-{$idx}"
                     role="region"
                     aria-labelledby="faq-question-{$idx}"
                     hidden>
                    <p>{$a}</p>
                </div>
            </div>
            HTML;
        }

        $faqSchema = '';
        if ($items) {
            $schemaItems = [];
            foreach ($items as $item) {
                $schemaItems[] = [
                    '@type'          => 'Question',
                    'name'           => $item['question'] ?? '',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => $item['answer'] ?? '',
                    ],
                ];
            }
            $faqSchema = '<script type="application/ld+json">' . json_encode([
                '@context'   => 'https://schema.org',
                '@type'      => 'FAQPage',
                'mainEntity' => $schemaItems,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
        }

        $categoryHtml = '<div class="faq__categories"><button type="button" class="faq__category-pill faq__category-pill--active js-faq-category" data-category="all" aria-pressed="true">All</button>';
        $faqCategories = [];
        foreach ($items as $item) {
            $cat = esc($item['category'] ?? 'General');
            if (!in_array($cat, $faqCategories)) {
                $faqCategories[] = $cat;
            }
        }
        foreach ($faqCategories as $cat) {
            $categoryHtml .= '<button type="button" class="faq__category-pill js-faq-category" data-category="' . $cat . '" aria-pressed="false">' . $cat . '</button>';
        }
        $categoryHtml .= '</div>';

        return <<<HTML
        <section class="faq section section--alt" id="faq" aria-labelledby="faq-heading">
            <div class="container">
                <h2 class="section__heading" id="faq-heading">{$title}</h2>
                <p class="section__description">{$description}</p>
                {$searchHtml}
                <div class="faq__side-by-side">
                    {$categoryHtml}
                    <div class="faq__list faq__list--{$style}">
                        {$itemsHtml}
                    </div>
                </div>
            </div>
            {$faqSchema}
        </section>
        HTML;
    }

    /* ================================================================
       10. CTA Section
       ================================================================ */
    private function renderCta(array $cfg): string
    {
        $heading      = esc($cfg['heading'] ?? '');
        $description  = esc($cfg['description'] ?? '');
        $btnText      = esc($cfg['button_text'] ?? '');
        $btnUrl       = esc($cfg['button_url'] ?? '/contact');
        $showWaBtn    = !empty($cfg['show_whatsapp_btn']);
        $waLabel      = esc($cfg['whatsapp_label'] ?? 'Chat on WhatsApp');
        $bgImage      = esc($cfg['background_image'] ?? '');

        $bgStyle = $bgImage ? ' style="background-image:url(' . $bgImage . ')"' : '';

        $waHtml = '';
        if ($showWaBtn) {
            $waUrl = $this->getWhatsAppUrl($waLabel);
            $waHtml = <<<HTML
            <a href="{$waUrl}" class="btn btn--whatsapp btn--lg cta__wa-btn" target="_blank" rel="noopener noreferrer">
                <span class="cta__wa-icon" aria-hidden="true">&#x1F4AC;</span>
                {$waLabel}
            </a>
            HTML;
        }

        return <<<HTML
        <section class="cta section section--dark" id="cta"{$bgStyle} role="region" aria-labelledby="cta-heading">
            <div class="cta__overlay"></div>
            <div class="container cta__split">
                <div class="cta__copy">
                    <h2 class="cta__heading" id="cta-heading">{$heading}</h2>
                    <p class="cta__description">{$description}</p>
                    <div class="cta__actions">
                        <a href="{$btnUrl}" class="btn btn--primary btn--lg cta__btn">{$btnText}</a>
                        {$waHtml}
                    </div>
                </div>
                <div class="cta__visual" aria-hidden="true">
                    <div class="cta__visual-shape cta__visual-shape--1"></div>
                    <div class="cta__visual-shape cta__visual-shape--2"></div>
                    <div class="cta__visual-shape cta__visual-shape--3"></div>
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       11. Contact Preview Section
       ================================================================ */
    private function renderContact(array $cfg): string
    {
        global $site;
        $title       = esc($cfg['title'] ?? '');
        $description = esc($cfg['description'] ?? '');
        $showWa      = !empty($cfg['show_whatsapp']);
        $showPhone   = !empty($cfg['show_phone']);
        $showEmail   = !empty($cfg['show_email']);
        $btnText     = esc($cfg['button_text'] ?? '');
        $btnUrl      = esc($cfg['button_url'] ?? '/contact');

        /* Preferred contact values from config, with site.php as fallback. */
        $phone = !empty($cfg['phone']) ? $cfg['phone'] : ($site->brand->phone ?? '');
        $email = !empty($cfg['email']) ? $cfg['email'] : ($site->brand->email ?? '');
        $emailEncoded = esc($email);

        $contactItems = '';

        if ($showEmail && $email) {
            $contactItems .= <<<HTML
            <div class="contact-card__item fade-in">
                <span class="contact-card__icon contact-card__icon--email" aria-hidden="true">{$this->inlineIcon('mail')}</span>
                <h3 class="contact-card__label">Email</h3>
                <a href="mailto:{$emailEncoded}" class="contact-card__value">{$emailEncoded}</a>
            </div>
            HTML;
        }

        if ($showPhone && $phone) {
            $phoneEncoded = esc($phone);
            $contactItems .= <<<HTML
            <div class="contact-card__item contact-card__item--cta fade-in">
                <span class="contact-card__icon contact-card__icon--phone" aria-hidden="true">{$this->inlineIcon('phone')}</span>
                <h3 class="contact-card__label">Phone</h3>
                <a href="tel:{$phoneEncoded}" class="contact-card__value">{$phoneEncoded}</a>
            </div>
            HTML;
        }

        if ($showWa) {
            $waUrl = $this->getWhatsAppUrl('Contact from homepage');
            $contactItems .= <<<HTML
            <div class="contact-card__item contact-card__item--cta fade-in">
                <span class="contact-card__icon contact-card__icon--whatsapp" aria-hidden="true">{$this->inlineIcon('message-circle')}</span>
                <h3 class="contact-card__label">WhatsApp</h3>
                <a href="{$waUrl}" class="contact-card__value" target="_blank" rel="noopener noreferrer">Chat on WhatsApp</a>
            </div>
            HTML;
        }

        $location = !empty($cfg['location']) ? $cfg['location'] : '';
        if ($location) {
            $locationEncoded = esc($location);
            $contactItems .= <<<HTML
            <div class="contact-card__item fade-in">
                <span class="contact-card__icon contact-card__icon--location" aria-hidden="true">
                    <svg class="cp-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>
                </span>
                <h3 class="contact-card__label">Location</h3>
                <span class="contact-card__value">{$locationEncoded}</span>
            </div>
            HTML;
        }

        $availability = !empty($cfg['availability']) ? $cfg['availability'] : '';
        if ($availability) {
            $availabilityEncoded = esc($availability);
            $contactItems .= <<<HTML
            <div class="contact-card__item fade-in">
                <span class="contact-card__icon contact-card__icon--availability" aria-hidden="true">
                    <svg class="cp-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </span>
                <h3 class="contact-card__label">Availability</h3>
                <span class="contact-card__value">{$availabilityEncoded}</span>
            </div>
            HTML;
        }

        if (!$contactItems) return '';

        return <<<HTML
        <section class="contact-preview section section--alt section--top-glow" id="contact-preview" aria-labelledby="contact-preview-heading">
            <div class="container">
                <h2 class="section__heading" id="contact-preview-heading">{$title}</h2>
                <p class="section__description">{$description}</p>
                <div class="contact__premium-panel glass glass--elevated">
                    <div class="contact__methods">
                        {$contactItems}
                    </div>
                    <div class="contact__cta">
                        <a href="{$btnUrl}" class="btn btn--primary btn--lg">{$btnText}</a>
                    </div>
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       Helpers
       ================================================================ */

    private function camelCase(string $key): string
    {
        return str_replace('_', '', ucwords($key, '_'));
    }

    /* ================================================================
       Aliases for renamed sections (backward-compatible method mapping)
       ================================================================ */

    private function renderPortfolioPreview(array $cfg): string
    {
        return $this->renderPortfolio($cfg);
    }

    private function renderFinalCta(array $cfg): string
    {
        return $this->renderCta($cfg);
    }

    private function renderContactInfo(array $cfg): string
    {
        return $this->renderContact($cfg);
    }

    private function renderIntroduction(array $cfg): string
    {
        return $this->renderAbout($cfg);
    }

    /* ================================================================
       Helpers
       ================================================================ */

    /**
     * Service-specific Lucide-style icon (stroke, not generic placeholder).
     */
    private function serviceIcon(string $key): string
    {
        $map = [
            'branding' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l7.586 7.586"/><circle cx="11" cy="11" r="2"/></svg>',
            'graphic'  => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r=".5"/><circle cx="17.5" cy="10.5" r=".5"/><circle cx="8.5" cy="7.5" r=".5"/><circle cx="6.5" cy="12.5" r=".5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>',
            'uiux'     => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>',
            'web'      => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
            'marketing'=> '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
            'maintenance'=> '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        ];
        $path = $map[$key] ?? '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>';
        return $path;
    }

    /**
     * Inline SVG icon helper (Lucide-style stroke icons).
     *
     * Returns a self-contained <svg> for a named icon. Unmatched names
     * fall back to a neutral dot so no card is ever left icon-less.
     */
    private function inlineIcon(string $name): string
    {
        $paths = [
            'zap'          => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
            'smartphone'   => '<rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>',
            'settings'     => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
            'search'       => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
            'layers'       => '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>',
            'shield-check' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/>',
            'message-circle' => '<path d="M21 11.5a8.38 8.38 0 0 1-8.5 8.5 8.5 8.5 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1 .9-3.8 8.5 8.5 0 0 1 7.6-4.5c4.7 0 8.5 3.8 8.5 8.5z"/>',
            'award'        => '<circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>',
            'check-circle' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
            'user-check'   => '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/>',
            'eye-off'      => '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>',
            'mail'         => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>',
            'refresh-cw'   => '<polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>',
            'target'       => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
            'briefcase'    => '<rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
            'palette'      => '<circle cx="13.5" cy="6.5" r=".5"/><circle cx="17.5" cy="10.5" r=".5"/><circle cx="8.5" cy="7.5" r=".5"/><circle cx="6.5" cy="12.5" r=".5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/>',
            'users'        => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
            'cpu'          => '<rect x="4" y="4" width="16" height="16" rx="2" ry="2"/><rect x="9" y="9" width="6" height="6"/><line x1="9" y1="1" x2="9" y2="4"/><line x1="15" y1="1" x2="15" y2="4"/><line x1="9" y1="20" x2="9" y2="23"/><line x1="15" y1="20" x2="15" y2="23"/><line x1="20" y1="9" x2="23" y2="9"/><line x1="20" y1="14" x2="23" y2="14"/><line x1="1" y1="9" x2="4" y2="9"/><line x1="1" y1="14" x2="4" y2="14"/>',
            'building-2'   => '<path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/>',
            'graduation-cap' => '<path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5"/>',
            'utensils'     => '<path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/>',
            'brain'        => '<path d="M9.5 2A2.5 2.5 0 0 1 12 4.5v15a2.5 2.5 0 0 1-4.96.44 2.5 2.5 0 0 1-2.96-3.08 3 3 0 0 1-.34-5.58 2.5 2.5 0 0 1 1.32-4.24 2.5 2.5 0 0 1 4.44-1.04zM14.5 2A2.5 2.5 0 0 0 12 4.5v15a2.5 2.5 0 0 0 4.96.44 2.5 2.5 0 0 0 2.96-3.08 3 3 0 0 0 .34-5.58 2.5 2.5 0 0 0-1.32-4.24 2.5 2.5 0 0 0-4.44-.01z"/>',
            'sun'          => '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>',
            'lightbulb'    => '<path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7c.6.5 1 1.4 1 2.3h6c0-.9.4-1.8 1-2.3A7 7 0 0 0 12 2z"/>',
            'store'        => '<path d="M3 9l1-5h16l1 5"/><path d="M21 9v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9"/><path d="M9 21v-6h6v6"/>',
            'rocket'       => '<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/>',
            'layout'       => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/>',
            'file-code'    => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><polyline points="10 13 7 16 10 19"/><polyline points="14 13 17 16 14 19"/>',
            'database'     => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>',
            'file-text'    => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
            'code'         => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
            'clock'        => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
            'heart'        => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>',
            'home'         => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
            'pen-tool'     => '<path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l7.586 7.586"/><circle cx="11" cy="11" r="2"/>',
            'image'        => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>',
            'shopping-cart' => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>',
            'shop'         => '<path d="M4 5l-1 4h18l-1-4h-16z"/><path d="M4 9v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9"/><path d="M12 11v8"/>',
            'map'          => '<polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/>',
            'star'         => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
            'check'        => '<polyline points="20 6 9 17 4 12"/>',
            'link'         => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
            'shield'       => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
            'send'         => '<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>',
            'phone'        => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8 9 15 16l1.36-.36a2 2 0 0 1 2.11.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
            /* Technology brand-style glyphs (generic-representation fallbacks) */
            'wordpress'    => '<circle cx="12" cy="12" r="10"/><path d="M3.7 7l4.5 13M16.4 6L14 13.6 11.6 20M20.4 7.3c-1.4 2-3.9 6.7-6 12.2M7 6.2c-1.7 0-1.7-2.6 0-2.6 1.8 0 1.7 2.6 0 2.6z"/><path d="M12 2a10 10 0 0 1 10 10M12 2a10 10 0 0 0-9 6.2"/>',
            'woocommerce'  => '<circle cx="12" cy="12" r="10"/><path d="M8 16c1-1 2-1.5 3-1.5s2 .5 3 1.5 2 1.5 3 1M17.5 12.5c-.6 0-1-.4-1-1a1 1 0 0 1 2 0c0 .6-.4 1-1 1zM6.5 12.5c-.6 0-1-.4-1-1a1 1 0 0 1 2 0c0 .6-.4 1-1 1z"/>',
            'shopify'      => '<circle cx="12" cy="12" r="10"/><path d="M8.5 15.5a3.2 3.2 0 0 0 5.4 2c.8-.9.9-2.3.5-3.5.7-.2 1.3-.2 1.8.1-1 .4-1.9 1.8-1.4 3.2-.4-.2-.7-.6-.8-1-1.3.2-2.4 1.1-2.7 2.4-.3-.4-.6-.8-.7-1.2-.7.8-2 .8-2.7.2a3.3 3.3 0 0 0 .6-2.1z"/>',
            'wix'          => '<path d="M4 5c1.5 0 2.5 1 2.5 2.5L7 14l1-9c.3-2 3.7-2 4 0l1 9 .5-6.5C13.5 6 14.5 5 16 5c1 0 2 .8 2 2l-1 2 1 2 1-2c.5 0 1 .5 1 1 0 5-2 9.5-4 9.5-1 0-1.5-1-2-2.5l-.5 0"/>',
            'html5'        => '<polyline points="4 3 5.5 19 12 21 18.5 19 20 3"/><path d="M9 9h6l-.5 5H9v0a.6.6 0 0 1 1.8-.5l.2 0M9 9h-1M9 13h.5M15.6 13.5 16 9h.5M10.5 9l1 5"/>',
            'css3'         => '<polyline points="4 3 5.5 19 12 21 18.5 19 20 3"/><path d="M8.5 8h7l-.3 3H9l-.3 3h5l-.5 4-4 .8 0 0"/>',
            'dot'          => '<circle cx="12" cy="12" r="4"/>',
        ];

        $body = $paths[$name] ?? $paths['dot'];
        return '<svg class="cp-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
    }

    /**
     * Official Simple Icons brand SVGs (fill-based, not stroke).
     *
     * Returns canonical Simple Icons paths for technology brands.
     * Falls back to inlineIcon() when no official path exists.
     * Source: https://simple-icons.org — CC0 public domain.
     */
    private function simpleIcon(string $name): string
    {
        $brandPaths = [
            'wordpress' => 'M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm3.265 14.944l.571-2.325c.115-.456.053-.788-.059-.83-.114-.043-.296.015-.411.468l-.571 2.325c1.447.615 2.604 1.357 2.604 1.357zm-8.252 1.272L8.56 14.36c-.253-.281-.514-.554-.767-.59.484-1.085 1.066-2.207 1.63-3.281l-1.256-.773C7.122 11.463 6.128 13.227 5.431 15.245c.236.244.504.47.805.685l-.222 1.286zm3.393-5.594c0-.296-.152-.482-.423-.482s-.504.198-.504.496c0 .291.157.482.433.482.272 0 .494-.2.494-.496zm4.025-1.193c-.259 0-.445.193-.445.479 0 .297.157.482.433.482.277 0 .499-.198.499-.496 0-.286-.151-.482-.408-.482l-.479.017zm4.589 4.952l-.359-1.153c-.109-.349-.195-.646-.312-.646-.11 0-.21.307-.303.66l-.339 1.198c1.244.615 1.878 1.258 1.878 1.258l.708-1.257zM12 22.784c-1.059 0-2.082-.153-3.048-.437l3.237-9.406 3.315 9.087c-.024.053-.049.1-.078.149-1.118.393-2.325.609-3.426.609zM3.063 16.39c.337.003.664.026.974.056.348.113.583.414.583.765 0 .42-.395.648-.882.648-.482 0-.881-.227-.881-.648 0-.346.236-.652.584-.764l.622-.057zm-1.93-1.428l.038.498c.782.153 1.274.466 1.274.879 0 .536-.573.752-1.278.752-.703 0-1.274-.216-1.274-.752 0-.362.375-.642.935-.812l.305-.565zm.987-2.073l.078.494c1.137.235 1.786.64 1.786 1.17 0 .632-.726.926-1.786.926-1.059 0-1.785-.294-1.785-.926 0-.461.41-.823 1.134-1.017l.573-.347zm.18-2.064l.102.489c1.674.339 2.583.909 2.583 1.599 0 .805-1.071 1.357-2.583 1.357-1.512 0-2.583-.33-2.583-1.357 0-.588.564-1.085 1.653-1.342l.828-.746zm.582-2.008l.116.486c2.045.421 3.111 1.11 3.111 1.925 0 .962-1.322 1.357-3.111 1.357-1.788 0-3.111-.395-3.111-1.357 0-.666.607-1.235 1.832-1.545l1.63-1.467zm1.168-1.904l.127.483c2.472.507 3.763 1.347 3.763 2.328 0 1.148-1.633 1.598-3.763 1.598-2.13 0-3.763-.45-3.763-1.598 0-.845.738-1.517 2.223-1.896l1.413-.817zm2.364-2.854l-.003.057L14.319.244c.419-.015.815-.052 1.188-.105l-3.279-9.527c-.615-1.54-.82-2.771-.82-3.864 0-.405.026-.78.07-1.11h-.984c-.175 1.095-.888 2.015-3.003 2.873-.736.345-1.554.585-1.797 1.14-.091.33-.105.51-.046.705.15.646.915.841.515.66-.39-.12-.75-.42-.976-.91-.034.676-.034.676-1.755 1.125.27.42.404.601.586.78.63.705 1.469 1.065 2.834 1.034l.705-.089c.676-.165 1.32-.525 1.71 1.005 1.14-.291.81-3.541-.569-4.47-1.365-1.023-.361-1.244-3.616-2.205.241-.17.871-.545 1.966-1.41.811.18 1.26.586 1.755 1.336l1.831-.051c-.21-.48-.45-.689-.811-1.09-1.741-.756-.091-.666-.871-1.004-.029-.09-.24-.705-.074-1.65l-.046.067zm-8.983-7.245h-2.248c0 1.938-.009 3.864-.009 5.805 0 1.232.063 2.363-.138 2.711-.33.689-1.18.601-1.566.48-.396-.196-.597-.466-.83-.855-.063-.105-.11-.196-.127-.196l-1.825 1.125c.305.63.75 1.172 1.324 1.517.855.512.004.675 3.207.405.783-.226 1.458-.691 1.811-1.411.51-.93.402-2.07.397-3.346.012-2.054 0-4.109 0-6.179l-.004-.056z',
            'woocommerce' => 'M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm3.15 13.5c-.1.45-.45.8-1.1 1.1-.25.1-.5.15-.8.15s-.55-.05-.8-.15c-.65-.3-1-.65-1.1-1.1-.1-.45-.05-.85.2-1.15.25-.3.6-.5 1.05-.5.45 0 .8.15 1.1.45.05-.15.1-.25.15-.4.1-.35.2-.6.3-.75l-1.8-.5c-.15.3-.25.55-.3.75-.3.75-.65 1.2-1.15 1.4-.5.2-1.05.25-1.65.1-.5-.15-.95-.4-1.25-.7-.2-.2-.35-.4-.45-.55h.05c.3-.15.6-.25.85-.3.45-.1.8-.05 1.05.1.3.15.5.4.55.75.05.4-.1.7-.4 1-.25.2-.6.3-1.05.3-.4 0-.75-.1-1.05-.3-.2-.15-.35-.3-.4-.5-.05-.25-.05-.5 0-.75.1-.4.25-.7.5-.95.15-.15.35-.25.55-.35.3-.1.55-.15.8-.2l.1-.05c.15-.05.25-.1.3-.15.1-.15.1-.3 0-.5-.1-.3-.4-.55-.8-.7-.3-.1-.6-.15-.95-.1-.3.05-.6.15-.8.25l-1-1.5h5.4l-.45 1h-3.55l-.1.15c.25.05.45.15.65.25.5.3.85.7 1 1.25.15.5.1.95-.1 1.35z',
            'shopify' => 'M15.34 3.56c0 .08-.01.16-.01.24 0 .08.01.15.02.22l-.01-.02c-.38.17-.66.42-.76.54-.2.24-.34.52-.42.83-.1.38-.11.75-.02 1.13.04.19.11.37.22.54.22.32.58.56 1.03.73l.5.18c.18.06.35.15.5.26.2.14.34.32.4.54.04.14.05.28.02.42-.08.4-.35.68-.7.83-.14.06-.29.1-.45.12-.2.02-.38.01-.58-.04-.4-.1-.75-.33-1.03-.68-.12-.15-.21-.32-.28-.51-.02-.08-.05-.17-.09-.28-.2.28-.42.53-.68.73-.4.3-.86.46-1.4.48-.25.01-.5-.01-.74-.07-.44-.12-.78-.4-1-76-.14-.32-.2-.66-.16-1.02.07-.6.37-1.07.83-1.4.12-.08.25-.15.39-.2.3-.12.55-.05.76.18.12.13.2.29.26.47.02.08.04.16.05.24l.01-.02c.15-.14.32-.24.51-.3.34-.1.62.04.74.37.05.13.07.27.08.41 0 .05.01.11.02.16l.02-.02c.28-.08.56-.14.84-.16.53-.04.98.1 1.35.43.15.14.26.3.35.5l.02-.02zM9.87 8.47c.34 0 .63-.11.85-.33.21-.22.32-.5.32-.84 0-.14-.02-.27-.07-.4-.13-.35-.45-.62-.87-.73-.18-.05-.35-.07-.53-.07-.17 0-.34.02-.52.07-.41.11-.73.38-.86.73-.05.13-.07.26-.07.4 0 .33.11.62.32.84.22.22.51.33.85.33zM8.56 17.23c.45.07.91.05 1.37-.07.65-.16 1.15-.52 1.53-1.06.18-.26.3-.55.36-.86.06-.3.05-.62-.03-.92-.08-.29-.23-.55-.44-.77-.08-.08-.16-.15-.25-.22l-.06-.04c.28-.07.55-.16.8-.28.47-.23.84-.56 1.1-.99.06-.1.12-.2.17-.31h.05c.05.12.11.23.18.34.3.44.73.79 1.26 1.03l.17.08c.18.09.33.22.44.39.2.3.32.64.35 1 .03.25.01.5-.06.74-.2.66-.74 1.12-1.43 1.35-.23.08-.47.12-.71.13-.4.02-.79-.04-1.16-.19-.43-.17-.8-.44-1.13-.77-.08-.08-.15-.16-.22-.25-.18.28-.39.54-.64.77-.36.32-.76.5-1.2.55h-.08c.12-.33.16-.68.12-1.04z',
            'wix' => 'M17.678 15.26c-.518.38-1.087.63-1.7.738-.176.031-.358.047-.542.047-.405 0-.782-.084-1.115-.254-.347-.177-.632-.432-.839-.753-.06-.094-.113-.194-.16-.297l-.022-.045c.054.023.117.04.188.04.233 0 .454-.028.66-.082.436-.116.806-.352 1.09-.695.13-.157.233-.337.3-.534.02-.053.033-.11.04-.167l-2.595-7.686-.63-1.87c-.095-.28-.212-.422-.36-.422-.148 0-.264.142-.36.422l-3.02 9.14c-.128.4-.31.72-.54.973-.244.268-.538.443-.882.529-.33.083-.64.063-.922-.058-.215-.092-.39-.23-.505-.403-.14-.212-.2-.47-.173-.733.026-.25.13-.478.294-.656.224-.242.514-.383.835-.41.226-.02.44.008.642.082.154.057.29.138.405.24l.028.026 2.17 6.464c.032.086.07.17.112.251l.14.268-1.612 4.926c-.14.428-.333.77-.58 1.026-.258.272-.574.434-.932.486-.34.05-.653-.022-.932-.204-.263-.172-.46-.413-.576-.704-.16-.397-.22-.852-.173-1.314l2.168-6.64c.034-.1.053-.2.06-.297.01-.165-.004-.332-.044-.494-.025-.1-.064-.196-.115-.287l-.057-.084c.047-.01.095-.016.143-.016.23 0 .443.026.638.076.438.112.815.348 1.108.693.133.158.24.34.308.537.018.053.03.108.038.165l.18-.024-.042-2.276c-.004-.188-.004-.188-.004-.188l.19.033c.006.052.012.102.02.15.024.16.076.314.153.455.167.304.416.534.718.678.087.042.18.073.277.094.086.018.173.028.26.03l-.014.64z',
            'php' => 'M7.011 10.207h-.944l-.515 2.648h.838c.557 0 .97-.105 1.242-.314.272-.21.455-.559.55-1.049.092-.47.05-.802-.124-.995-.175-.193-.523-.29-1.047-.29zM12 5.688C5.373 5.688 0 8.514 0 12s5.373 6.313 12 6.313S24 15.486 24 12c0-3.486-5.373-6.312-12-6.312zm-3.267.451c-.261.25-.575.438-.917.551-.336.108-.765.164-1.285.164H5.357l-.327 1.681H3.652l1.23-6.326h2.65c.797 0 1.378.209 1.744.628.366.418.476 1.002.331 1.752a2.836 2.836 0 0 1-.305.847c-.143.255-.33.49-.561.703zm4.024.715l.543-2.799c.063-.318.039-.536-.068-.651-.107-.116-.336-.174-.687-.174H11.46l-.704 3.625H9.388l1.23-6.327h1.367l-.327 1.682h1.218c.767 0 1.295.134 1.586.401s.378.7.263 1.299l-.572 2.944h-1.389zm7.597-2.265a2.782 2.782 0 0 1-.305.847c-.143.255-.33.49-.561.703a2.442 2.442 0 0 1-.917.551c-.336.108-.765.164-1.286.164h-1.18l-.327 1.682h-1.378l1.23-6.326h2.649c.797 0 1.378.209 1.744.628.366.417.477 1.001.331 1.751zM17.766 10.207h-.943l-.516 2.648h.838c.557 0 .971-.105 1.242-.314.272-.21.455-.559.551-1.049.092-.47.049-.802-.125-.995s-.524-.29-1.047-.29z',
            'mysql' => 'M16.405 5.501c-.115 0-.193.014-.274.033v.013h.014c.054.104.146.18.214.273.054.107.1.214.154.32l.014-.015c.094-.066.14-.172.14-.333-.04-.047-.046-.094-.08-.14-.04-.067-.126-.1-.18-.153zM5.771 8.695h-.927a50.854 50.854 0 0 0-.27-4.41h-.008l-1.414 4.41H2.45l-1.4-4.41h-.01a72.892 72.892 0 0 0-.195 4.41H0c.055-1.966.192-3.81.41-5.53h1.15l1.335 4.064h.008l1.347-4.064h1.095c.242 2.015.384 3.86.428 5.53zm4.017-4.08c-.378 2.045-.876 3.533-1.492 4.46-.482.716-1.011.073-1.583.073-.153 0-.34-.046-.566-.138v-.494c.11.017.24.026.386.026.268 0 .483-.075.647-.222.197-.18.295-.382.295-.605 0-.155-.077-.47-.23-.944L6.231 4.615h.91l.727 2.36c.164.536.233.91.205 1.123.4-1.064.678-2.227.835-3.483zm12.325 4.08h-2.63v-5.53h.885v4.85h1.745zm-3.32.135l-1.016-.5c.09-.076.177-.158.255-.25.433-.506.648-1.258.648-2.253 0-1.83-.718-2.746-2.155-2.746-.704 0-1.254.232-1.65.697-.43.508-.646 1.256-.646 2.245 0 .972.191 1.686.574 2.14.35.41.877.615 1.583.615.264 0 .506-.033.725-.098l1.325.772.36-.622zM15.517 7.588c-.225-.36-.337-.94-.337-1.736 0-1.393.424-2.091 1.27-2.091.443 0 .77.167.977.5.224.362.336.936.336 1.723 0 1.404-.424 2.108-1.272 2.108-.445 0-.77-.167-.978-.5zm-1.658-.425c.47.172.856.516 1.156.344.3-.172.803-.45 1.384-.45.543 0 1.064.172 1.573.515l.237-.476c-.438-.22-.833-.328-1.19-.328-.332 0-.593.073-.783.22a.754.754 0 0 0-.3.615c0 .33.23.61.648.845.388.213 1.163.657 1.163.657.422.307.632.636.632 1.177 0 .45-.157.81-.47 1.085-.315.278-.72.415-1.22.415-.512 0-.98-.136-1.4-.41l-.213.476a2.726 2.726 0 0 0 1.064.23c.283 0 .502-.068.654-.206a.685.685 0 0 0-.248-.524c-.328-.234-.61-.666-.85-.393-.215.187-.671.187-.67.433.305.648.63.648 1.168zm9.382-5.852c-.535-.014-.95.04-1.297.188-.1.04-.26.04-.274.167.055.053.063.14.11.214.08.134.218.313.346.407.14.11.28.216.427.31.26.16.555.255.81.416.145.094.293.213.44.313.073.05.12.14.214.172v-.02c-.046-.06-.06-.147-.105-.214-.067-.067-.134-.127-.2-.193a3.223 3.223 0 0 0-.695-.675c-.214-.146-.682-.35-.77-.595l-.013-.014c.146-.013.32-.066.46-.106.227-.06.435-.047.67-.106.106-.027.213-.06.32-.094v-.06c-.12-.12-.21-.283-.334-.395a8.867 8.867 0 0 0-1.104-.823c-.21-.134-.476-.22-.697-.334-.08-.04-.214-.06-.26-.127-.12-.146-.19-.34-.275-.514a17.69 17.69 0 0 1-.547-1.163c-.12-.262-.193-.523-.34-.763-.69-1.137-1.437-1.826-2.586-2.5-.247-.14-.543-.2-.856-.274-.167-.008-.334-.02-.5-.027-.11-.047-.216-.174-.31-.235-.38-.24-1.364-.76-1.644-.072-.18.434.267.862.422 1.082.115.153.26.328.34.5.047.116.06.235.107.356.106.294.207.622.347.897.073.14.153.287.247.413.054.073.146.107.167.227-.094.136-.1.334-.154.5-.24.757-.146 1.693.194 2.25.107.166.362.534.703.393.3-.12.234-.5.32-.835.02-.08.007-.133.048-.187v.015c.094.188.188.367.274.555.206.328.566.668.867.895.16.12.287.328.487.402v-.02h-.015c-.043-.058-.1-.086-.154-.133a3.445 3.445 0 0 1-.35-.48.768.768 0 0 1-.747-1.218c-.11-.21-.202-.436-.29-.643-.04-.08-.04-.2-.107-.24-.1.146-.247.273-.32.453-.127.288-.14.642-.188 1.01-.027.007-.014 0-.027.014-.214-.052-.287-.274-.367-.46-.2-.475-.233-1.238-.06-1.785.047-.14.247-.582.167-.716-.042-.127-.174-.2-.247-.303a2.478 2.478 0 0 1-.24-.427c-.16-.374-.24-.788-.414-1.162-.08-.173-.22-.354-.334-.513-.127-.18-.267-.307-.368-.52-.033-.073-.08-.194-.027-.274.014-.054.042-.075.094-.09.088-.072.335.022.422.062.247.1.455.194.662.334.094.066.195.193.315.226h.14c.214.047.455.014.655.073.355.114.675.28.962.46a5.953 5.953 0 0 1 2.085 2.286c.08.154.115.295.188.455.14.33.313.663.455.982.14.315.275.636.476.897.1.14.502.213.682.286.133.06.34.115.46.188.23.14.454.3.67.454.11.076.443.243.463.378z',
            'html5' => 'M1.5 0h21l-1.91 21.563L12 24l-8.565-2.438L1.5 0zm7.031 9.75l-.232-2.718 10.059.003.23-2.622L5.412 4.41l.698 8.01h9.126l-.326 3.426-2.91.804-2.955-.81-.188-2.11H6.248l.334 4.171L12 19.351l5.379-1.443.744-8.157H8.531z',
            'css3' => 'M1.5 0h21l-1.91 21.563L11.977 24l-8.565-2.438L1.5 0zm17.094 4.413L5.414 4.41l.213 2.622 10.125.002.255 2.716H7.687l.242.573h6.182l-.366 3.523-2.91.804-2.956-.81-.188-2.11h-2.61l.293 3.855L12 19.288l5.373-1.53.634-7.344-.383-3.417z',
            'javascript' => 'M0 0h24v24H0V0zm22.034 18.276c-.175-1.095-.888-2.015-3.003-2.873-.736-.345-1.554-.585-1.797-1.14-.091-.33-.105-.51-.046-.705.15-.646.915-.841.515-.66-.39.12-.75.42-.976.91-.034-.676-.034-.676-1.755 1.125.27.42.404.601.586.78.63.705 1.469 1.065 2.834 1.034l.705-.089c.676-.165 1.32-.525 1.71 1.005 1.14-.291.81-3.541-.569-4.47-1.365-1.023-.361-1.244-3.616-2.205.241-.17.871-.545 1.966-1.41.811.18 1.26.586 1.755 1.336l1.831-.051c-.21-.48-.45-.689-.811-1.09-1.741-.756-.091-.666-.871-1.004-.029-.09-.24-.705-.074-1.65l-.046.067zm-8.983-7.245h-2.248c0 1.938-.009 3.864-.009 5.805 0 1.232.063 2.363-.138 2.711-.33.689-1.18.601-1.566.48-.396-.196-.597-.466-.83-.855-.063-.105-.11-.196-.127-.196l-1.825 1.125c.305.63.75 1.172 1.324 1.517.855.512.004.675 3.207.405.783-.226 1.458-.691 1.811-1.411.51-.93.402-2.07.397-3.346.012-2.054 0-4.109 0-6.179l-.004-.056z',
            'figma' => 'M15.852 8.981h-4.588V0h4.588c2.476 0 4.49 2.014 4.49 4.49s-2.014 4.491-4.49 4.491zM12.735 7.51h3.117c1.665 0 3.019-1.355 3.019-3.019S17.517 1.471 15.852 1.471h-3.117V7.51zm0 1.471H8.148c-2.476 0-4.49-2.014-4.49-4.49S5.672 0 8.148 0h4.588v8.981zm-4.587-7.51c-1.665 0-3.019 1.355-3.019 3.019s1.354 3.023 3.019 3.02h3.117V1.471H8.148zm4.587 15.019H8.148c-2.476 0-4.49-2.014-4.49-4.49s2.014-4.49 4.49-4.49h4.588v8.98zM8.148 8.981c-1.665 0-3.019 1.355-3.019 3.019s1.355 3.019 3.019 3.019h3.117V8.981H8.148zM8.172 24c-2.489 0-4.515-2.014-4.515-4.49s2.014-4.49 4.49-4.49h4.588v4.441c0 2.503-2.047 4.539-4.563 4.539zm-.024-7.51a3.023 3.023 0 0 0-3.019 3.019c0 1.665 1.365 3.019 3.044 3.019 1.705 0 3.093-1.376 3.093-3.068v-2.97H8.148zm7.704 0h-.098c-2.476 0-4.49-2.014-4.49-4.49s2.014-4.49 4.49-4.49h.098c2.476 0 4.49 2.014 4.49 4.49s-2.014 4.49-4.49 4.49zm-.097-7.509c-1.665 0-3.019 1.355-3.019 3.019s1.355 3.019 3.019 3.019h.098c1.665 0 3.019-1.355 3.019-3.019s-1.355-3.019-3.019-3.019h-.098z',
            'pen-tool' => 'M12 19l7-7 3 3-7 7-3-3z',
            'image' => 'M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z',
            'shopping-cart' => 'M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1.003 1.003 0 0 0 20 4H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z',
        ];

        if (isset($brandPaths[$name])) {
            return '<svg class="cp-icon cp-icon--simple" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="' . $brandPaths[$name] . '"/></svg>';
        }

        return $this->inlineIcon($name);
    }

    /**
     * Get content entries by content type slug.
     */
    private function getContentEntries(string $typeSlug, int $limit = 6): array
    {
        return $this->db->fetchAll(
            "SELECT ce.*, ct.slug AS type_slug
             FROM content_entries ce
             JOIN content_types ct ON ce.content_type_id = ct.id
             WHERE ct.slug = ? AND ce.status = 'published'
             ORDER BY ce.sort_order ASC, ce.created_at DESC
             LIMIT ?",
            [$typeSlug, $limit]
        );
    }

    /**
     * Get service-specific entries (content type slug 'services').
     */
    private function getServiceEntries(int $limit, string $sort): array
    {
        $order = match($sort) {
            'oldest' => 'created_at ASC',
            'alphabetical' => 'title ASC',
            default => 'sort_order ASC, created_at DESC',
        };

        $services = $this->db->fetchAll(
            "SELECT id, title, slug, short_description AS excerpt, icon, featured_image, category
             FROM services
             WHERE status = 'published'
             ORDER BY {$order}
             LIMIT ?",
            [$limit]
        );

        if (!empty($services)) return $services;

        return $this->getDefaultServices($limit);
    }

    /**
     * Default service cards for seeding demo.
     */
    private function getDefaultServices(int $limit): array
    {
        $defaults = [
            ['title' => 'Logo & Branding Design', 'excerpt' => 'Custom logos and complete brand identity systems that make your business stand out.', 'icon' => 'branding', 'slug' => 'logo-branding-design'],
            ['title' => 'Graphic Design',         'excerpt' => 'Eye-catching graphics for print and digital media that communicate your message.',       'icon' => 'graphic', 'slug' => 'graphic-design'],
            ['title' => 'UI/UX Design',           'excerpt' => 'User-centered interface design that delivers intuitive and engaging digital experiences.', 'icon' => 'uiux', 'slug' => 'ui-ux-design'],
            ['title' => 'Web Development',        'excerpt' => 'Fast, responsive, and SEO-friendly websites built with modern technologies.',              'icon' => 'web', 'slug' => 'web-development'],
            ['title' => 'Digital Marketing',      'excerpt' => 'Strategic digital marketing solutions to grow your online presence and reach.',            'icon' => 'marketing', 'slug' => 'digital-marketing'],
            ['title' => 'Website Maintenance',    'excerpt' => 'Reliable maintenance and support to keep your website running smoothly.',                  'icon' => 'maintenance', 'slug' => 'website-maintenance'],
        ];

        return array_slice($defaults, 0, $limit);
    }

    /**
     * Build WhatsApp URL from settings.
     */
    private function getWhatsAppUrl(string $message = ''): string
    {
        $whatsapp = new WhatsAppManager();
        $settings = $whatsapp->getSettings();
        $phone = $settings['phone_number'] ?? '';
        $defaultMsg = $settings['default_message'] ?? '';
        $msg = $message ?: $defaultMsg;
        return $whatsapp->buildUrl($phone, $msg);
    }

    /**
     * Placeholder portfolio data for design validation (6 cards).
     * Easy to replace when real CMS data exists.
     */
    private function getPlaceholderPortfolio(int $limit): array
    {
        $placeholders = [
            [
                'id'          => 9001,
                'title'       => 'Solaris Energy Brand Identity',
                'slug'        => 'solaris-energy',
                'category'    => 'Branding',
                'image_url'   => '',
                'description' => 'Complete brand identity for a renewable energy startup including logo, colour palette, and marketing collateral.',
            ],
            [
                'id'          => 9002,
                'title'       => 'FreshBite Restaurant Website',
                'slug'        => 'freshbite-restaurant',
                'category'    => 'Website Design',
                'image_url'   => '',
                'description' => 'Modern, mobile-first restaurant website with online menu, reservation system, and location map.',
            ],
            [
                'id'          => 9003,
                'title'       => 'TechNova SaaS Dashboard',
                'slug'        => 'technova-dashboard',
                'category'    => 'UI / UX',
                'image_url'   => '',
                'description' => 'Enterprise SaaS dashboard design with data visualisation, user management, and analytics overview.',
            ],
            [
                'id'          => 9004,
                'title'       => 'GreenLeaf Organic E-Commerce',
                'slug'        => 'greenleaf-organic',
                'category'    => 'Website Design',
                'image_url'   => '',
                'description' => 'WooCommerce store with custom product pages, subscription model, and brand storytelling layout.',
            ],
            [
                'id'          => 9005,
                'title'       => 'CloudBridge IT Services',
                'slug'        => 'cloudbridge-it',
                'category'    => 'Graphic Design',
                'image_url'   => '',
                'description' => 'Corporate brochure design, presentation deck, and social media graphics for an IT consulting firm.',
            ],
            [
                'id'          => 9006,
                'title'       => 'EduPath Online Learning Platform',
                'slug'        => 'edupath-learning',
                'category'    => 'UI / UX',
                'image_url'   => '',
                'description' => 'Learning management system interface with course cards, progress tracking, and student dashboard.',
            ],
        ];

        return array_slice($placeholders, 0, $limit);
    }

    /**
     * Placeholder testimonials for design validation (3 cards).
     * Clearly labelled as placeholder content — not real reviews.
     */
    private function getPlaceholderTestimonials(int $limit): array
    {
        $placeholders = [
            [
                'id'    => 9101,
                'title' => 'Rajesh Kumar',
                'excerpt' => 'Excellent work on our company website. The design was modern, the pages loaded fast, and the team was very responsive throughout the project. Highly recommended for WordPress development.',
                'fields' => [
                    'name'    => 'Rajesh Kumar',
                    'role'    => 'Founder',
                    'company' => 'Solaris Energy',
                    'rating'  => 5,
                ],
                'featured_image' => '',
            ],
            [
                'id'    => 9102,
                'title' => 'Priya Sharma',
                'excerpt' => 'Swap Design delivered our e-commerce website on time and within budget. The WooCommerce store is easy to manage and our customers love the clean layout. Great experience working together.',
                'fields' => [
                    'name'    => 'Priya Sharma',
                    'role'    => 'Director',
                    'company' => 'GreenLeaf Organics',
                    'rating'  => 5,
                ],
                'featured_image' => '',
            ],
            [
                'id'    => 9103,
                'title' => 'Amit Deshpande',
                'excerpt' => 'Our SaaS dashboard redesign improved user engagement significantly. The UI/UX was intuitive, and the design system made handoff to developers seamless.',
                'fields' => [
                    'name'    => 'Amit Deshpande',
                    'role'    => 'CTO',
                    'company' => 'TechNova Solutions',
                    'rating'  => 5,
                ],
                'featured_image' => '',
            ],
        ];

        return array_slice($placeholders, 0, $limit);
    }

    /**
     * Testimonials empty state — premium glass card with honest messaging.
     */
    private function renderTestimonialsEmptyState(array $cfg): string
    {
        $title       = esc($cfg['title'] ?? '');
        $description = esc($cfg['description'] ?? '');

        $iconSvg = $this->inlineIcon('message-circle');

        return <<<HTML
        <section class="testimonials section section--alt" id="testimonials" aria-labelledby="testimonials-heading">
            <div class="container">
                <div class="section__header" data-reveal>
                    <span class="section__eyebrow">Testimonials</span>
                    <h2 class="section__title" id="testimonials-heading">{$title}</h2>
                    <p class="section__subtitle">{$description}</p>
                </div>
                <div class="empty-state empty-state--testimonials fade-in" data-reveal>
                    <div class="empty-state__icon-wrap" aria-hidden="true">
                        <span class="cp-icon-wrap cp-icon-wrap--white" style="width:64px;height:64px;">{$iconSvg}</span>
                    </div>
                    <h3>Client testimonials coming soon</h3>
                    <p>Client feedback will appear here once reviews are approved and published from the CMS.</p>
                    <a href="/contact" class="btn btn--primary btn--lg" style="margin-top:var(--ds-space-24);">
                        Share Your Experience
                    </a>
                </div>
            </div>
        </section>
        HTML;
    }

    /**
     * Portfolio preview empty state — premium card encouraging exploration.
     */
    private function renderPortfolioEmptyState(array $cfg): string
    {
        $title       = esc($cfg['title'] ?? '');
        $description = esc($cfg['description'] ?? '');
        $viewAllText = esc($cfg['view_all_text'] ?? '');
        $viewAllUrl  = esc($cfg['view_all_url'] ?? '/portfolio');

        $viewAllHtml = '';
        if ($viewAllText) {
            $viewAllHtml = <<<HTML
            <div class="portfolio__cta-wrap">
                <a href="{$viewAllUrl}" class="btn btn--primary btn--lg">{$viewAllText}</a>
            </div>
            HTML;
        }

        return <<<HTML
        <section class="portfolio section section--alt" id="portfolio" aria-labelledby="portfolio-heading">
            <div class="container">
                <div class="section__header" data-reveal>
                    <span class="section__eyebrow">Portfolio</span>
                    <h2 class="section__title" id="portfolio-heading">{$title}</h2>
                    <p class="section__subtitle">{$description}</p>
                </div>
                <div class="empty-state empty-state--portfolio fade-in" data-reveal>
                    <div class="empty-state__icon-wrap" aria-hidden="true">
                        <span class="cp-icon-wrap cp-icon-wrap--white" style="width:64px;height:64px;">{$this->inlineIcon('layers')}</span>
                    </div>
                    <h3>Featured projects coming soon</h3>
                    <p>A curated showcase of recent work will appear here — branding, websites, UI/UX, and graphic design.</p>
                    {$viewAllHtml}
                </div>
            </div>
        </section>
        HTML;
    }

    /**
     * Empty state when no sections exist.
     */
    private function renderEmptyState(): string
    {
        return <<<HTML
        <main class="main-content" id="main-content">
            <div class="container">
                <div class="empty-state fade-in">
                    <h1>Welcome to Swap Design</h1>
                    <p>Your homepage is being configured. Visit the <a href="/admin/homepage.php">admin panel</a> to set up your homepage sections.</p>
                </div>
            </div>
        </main>
        HTML;
    }
}
