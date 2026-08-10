<?php
/**
 * Swap Design - Service Renderer
 *
 * Renders a full service detail page from the services table
 * with all sub-sections: hero, overview, features, benefits,
 * process, portfolio, testimonials, FAQ, CTA, contact.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class ServiceRenderer
{
    private ServiceManager $manager;
    private ComponentLoader $componentLoader;
    private WhatsAppManager $whatsapp;

    public function __construct()
    {
        $this->manager         = new ServiceManager();
        $this->componentLoader = new ComponentLoader();
        $this->whatsapp        = new WhatsAppManager();
    }

    /**
     * Render the services listing/archive page.
     */
    public function renderListing(): string
    {
        $services = $this->manager->getAll(['status' => 'published']);

        $cards = '';
        foreach ($services as $service) {
            $cards .= $this->renderListingCard($service);
        }

        $empty = empty($services)
            ? '<p class="services-listing__empty">No services available yet.</p>'
            : '';

        return <<<HTML
        <section class="services-listing section" aria-labelledby="svc-listing-heading">
            <div class="container">
                <h1 class="section__heading" id="svc-listing-heading">Our Services</h1>
                <div class="services__grid">
                    {$cards}
                </div>
                {$empty}
            </div>
        </section>
        HTML;
    }

    /**
     * Render a single service card for the listing page.
     */
    private function renderListingCard(array $service): string
    {
        $title       = esc($service['title'] ?? '');
        $description = esc($service['short_description'] ?? $service['meta_description'] ?? '');
        $icon        = esc($service['icon'] ?? '');
        $image       = esc($service['image_url'] ?? $service['hero_image'] ?? '');
        $url         = SITE_URL . '/services/' . esc($service['slug'] ?? '');

        $imageHtml = $image
            ? '<img src="' . $image . '" alt="' . $title . '" class="service-card__image" loading="lazy">'
            : '';

        $iconHtml = $icon
            ? '<div class="service-card__icon">' . $icon . '</div>'
            : '';

        return <<<HTML
        <article class="service-card">
            <a href="{$url}" class="service-card__link">
                {$imageHtml}
                <div class="service-card__body">
                    {$iconHtml}
                    <h2 class="service-card__title">{$title}</h2>
                    <p class="service-card__description">{$description}</p>
                    <span class="service-card__cta">Learn More</span>
                </div>
            </a>
        </article>
        HTML;
    }

    /**
     * Render a full service detail page by slug.
     *
     * @param string $slug The service slug from the URL.
     * @return string|null Complete HTML or null if not found.
     */
    public function render(string $slug): ?string
    {
        $service = $this->manager->getFullBySlug($slug);
        if (!$service) return null;

        return $this->renderService($service);
    }

    /**
     * Render a full service detail page from service data array.
     */
    public function renderFromData(array $service): string
    {
        return $this->renderService($service);
    }

    /**
     * Get page assets (CSS, JS) and schema for the service page.
     */
    public function getPageAssets(): array
    {
        return [
            'css' => ['/assets/css/services.css'],
            'js'  => ['/assets/js/pages/services.js'],
        ];
    }

    /**
     * Get JSON-LD schema for a service.
     */
    public function getSchema(array $service): string
    {
        $title = esc($service['title'] ?? '');
        $desc  = esc($service['short_description'] ?? $service['meta_description'] ?? '');
        $image = esc($service['og_image'] ?? $service['featured_image'] ?? '');
        $url   = esc(SITE_URL . '/services/' . ($service['slug'] ?? ''));
        $cat   = esc($service['category'] ?? '');

        $schema = '<script type="application/ld+json">' . json_encode([
            '@context'    => 'https://schema.org',
            '@type'       => 'Service',
            'name'        => $title,
            'description' => $desc,
            'url'         => $url,
            'image'       => $image ?: null,
            'category'    => $cat ?: null,
            'provider'    => [
                '@type' => 'Organization',
                'name'  => 'Swap Design',
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';

        $faqs = $service['faqs'] ?? [];
        if (!empty($faqs)) {
            $schema .= getFaqPageSchema($faqs);
        }

        return $schema;
    }

    /* ================================================================
       Main Render Pipeline
       ================================================================ */

    private function renderService(array $service): string
    {
        $sections = [
            $this->renderHero($service),
            $this->renderOverview($service),
            $this->renderFeatures($service),
            $this->renderBenefits($service),
            $this->renderProcess($service),
            $this->renderPortfolio($service),
            $this->renderTestimonials($service),
            $this->renderFaqs($service),
            $this->renderCta($service),
            $this->renderContact($service),
            $this->renderRelatedBlocks($service),
        ];

        return implode('', array_filter($sections));
    }

    /* ================================================================
       Hero Section
       ================================================================ */
    private function renderHero(array $service): string
    {
        $hTitle     = esc($service['hero_title'] ?? $service['title'] ?? '');
        $hDesc      = esc($service['hero_description'] ?? $service['short_description'] ?? '');
        $hImage     = esc($service['hero_image'] ?? $service['featured_image'] ?? '');
        $hBg        = esc($service['hero_bg_image'] ?? '');
        $ctaPriText = esc($service['hero_cta_primary_text'] ?? '');
        $ctaPriUrl  = esc($service['hero_cta_primary_url'] ?? '#');
        $ctaSecText = esc($service['hero_cta_secondary_text'] ?? '');
        $ctaSecUrl  = esc($service['hero_cta_secondary_url'] ?? '#');

        $bgStyle = $hBg ? ' style="background-image:url(' . $hBg . ')"' : '';

        $ctaPrimary = $ctaPriText
            ? '<a href="' . $ctaPriUrl . '" class="btn btn--primary btn--lg">' . $ctaPriText . '</a>'
            : '';
        $ctaSecondary = $ctaSecText
            ? '<a href="' . $ctaSecUrl . '" class="btn btn--outline btn--lg">' . $ctaSecText . '</a>'
            : '';

        $imageHtml = $hImage
            ? '<div class="svc-hero__image-wrap"><img src="' . $hImage . '" alt="' . $hTitle . '" loading="eager" width="560" height="420" class="svc-hero__image"></div>'
            : '';

        $gridClass = $imageHtml ? ' svc-hero__container--with-image' : '';

        return <<<HTML
        <section class="svc-hero"{$bgStyle} role="banner" aria-labelledby="svc-hero-heading">
            <div class="svc-hero__overlay"></div>
            <div class="svc-hero__container container{$gridClass}">
                <div class="svc-hero__content">
                    <h1 class="svc-hero__heading" id="svc-hero-heading">{$hTitle}</h1>
                    <p class="svc-hero__description">{$hDesc}</p>
                    <div class="svc-hero__actions">
                        {$ctaPrimary}
                        {$ctaSecondary}
                    </div>
                </div>
                {$imageHtml}
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       Overview Section
       ================================================================ */
    private function renderOverview(array $service): string
    {
        $intro    = esc($service['overview_intro'] ?? '');
        $benefits = esc($service['overview_benefits'] ?? '');
        $why      = esc($service['overview_why'] ?? '');

        if (!$intro && !$benefits && !$why) return '';

        $blocks = '';
        if ($intro) {
            $blocks .= '<div class="svc-overview__block"><h2 class="svc-overview__subheading">Introduction</h2><p>' . nl2br($intro) . '</p></div>';
        }
        if ($benefits) {
            $blocks .= '<div class="svc-overview__block"><h2 class="svc-overview__subheading">Business Benefits</h2><p>' . nl2br($benefits) . '</p></div>';
        }
        if ($why) {
            $blocks .= '<div class="svc-overview__block"><h2 class="svc-overview__subheading">Why This Matters</h2><p>' . nl2br($why) . '</p></div>';
        }

        return <<<HTML
        <section class="svc-overview section" id="overview" aria-labelledby="overview-heading">
            <div class="container">
                <div class="svc-overview__grid">
                    {$blocks}
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       Features Section
       ================================================================ */
    private function renderFeatures(array $service): string
    {
        $features = $service['features'] ?? [];
        if (empty($features)) return '';

        $cards = '';
        foreach ($features as $idx => $f) {
            $icon  = esc($f['icon'] ?? '');
            $title = esc($f['title'] ?? '');
            $desc  = esc($f['description'] ?? '');
            $delay = $idx * 100;

            $cards .= <<<HTML
            <div class="svc-card fade-in" style="animation-delay:{$delay}ms">
                <span class="svc-card__icon" aria-hidden="true">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none"><rect width="32" height="32" rx="8" fill="var(--color-accent-muted)"/></svg>
                </span>
                <h3 class="svc-card__title">{$title}</h3>
                <p class="svc-card__desc">{$desc}</p>
            </div>
            HTML;
        }

        return <<<HTML
        <section class="svc-features section section--alt" id="features" aria-labelledby="features-heading">
            <div class="container">
                <h2 class="section__heading" id="features-heading">Key Features</h2>
                <div class="svc-features__grid">
                    {$cards}
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       Benefits Section
       ================================================================ */
    private function renderBenefits(array $service): string
    {
        $benefits = $service['benefits'] ?? [];
        if (empty($benefits)) return '';

        $cards = '';
        foreach ($benefits as $idx => $b) {
            $icon  = esc($b['icon'] ?? '');
            $title = esc($b['title'] ?? '');
            $desc  = esc($b['description'] ?? '');
            $delay = $idx * 100;

            $cards .= <<<HTML
            <div class="svc-benefit-card fade-in" style="animation-delay:{$delay}ms">
                <span class="svc-benefit-card__icon" aria-hidden="true"></span>
                <h3 class="svc-benefit-card__title">{$title}</h3>
                <p class="svc-benefit-card__desc">{$desc}</p>
            </div>
            HTML;
        }

        return <<<HTML
        <section class="svc-benefits section" id="benefits" aria-labelledby="benefits-heading">
            <div class="container">
                <h2 class="section__heading" id="benefits-heading">Benefits</h2>
                <div class="svc-benefits__grid">
                    {$cards}
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       Process Section
       ================================================================ */
    private function renderProcess(array $service): string
    {
        $steps = $service['process_steps'] ?? [];
        if (empty($steps)) return '';

        $cards = '';
        foreach ($steps as $idx => $step) {
            $sTitle = esc($step['title'] ?? '');
            $sDesc  = esc($step['description'] ?? '');
            $num    = $idx + 1;
            $delay  = $idx * 120;

            $cards .= <<<HTML
            <div class="svc-process-card fade-in" style="animation-delay:{$delay}ms">
                <div class="svc-process-card__number" aria-hidden="true">{$num}</div>
                <h3 class="svc-process-card__title">{$sTitle}</h3>
                <p class="svc-process-card__desc">{$sDesc}</p>
            </div>
            HTML;
        }

        return <<<HTML
        <section class="svc-process section section--alt" id="process" aria-labelledby="process-heading">
            <div class="container">
                <h2 class="section__heading" id="process-heading">Our Process</h2>
                <div class="svc-process__grid">
                    {$cards}
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       Portfolio Section (dynamically linked)
       ================================================================ */
    private function renderPortfolio(array $service): string
    {
        $items = $service['portfolio'] ?? [];
        if (empty($items)) return '';

        $cards = '';
        foreach ($items as $idx => $item) {
            $title = esc($item['title'] ?? '');
            $slug  = esc($item['slug'] ?? '');
            $image = esc($item['image_url'] ?? '');
            $cat   = esc($item['category'] ?? '');
            $delay = $idx * 100;

            $cards .= <<<HTML
            <article class="pf-card fade-in" style="animation-delay:{$delay}ms">
                <a href="/portfolio/{$slug}" class="pf-card__link" aria-labelledby="svc-pf-title-{$item['id']}">
                    <div class="pf-card__image-wrap">
                        <img src="{$image}" alt="{$title}" loading="lazy" width="400" height="300" class="pf-card__image">
                        <div class="pf-card__overlay"><span class="pf-card__view">View Project</span></div>
                    </div>
                    <div class="pf-card__body">
                        <span class="pf-card__category">{$cat}</span>
                        <h3 class="pf-card__title" id="svc-pf-title-{$item['id']}">{$title}</h3>
                    </div>
                </a>
            </article>
            HTML;
        }

        return <<<HTML
        <section class="svc-portfolio section" id="portfolio" aria-labelledby="portfolio-heading">
            <div class="container">
                <h2 class="section__heading" id="portfolio-heading">Related Portfolio</h2>
                <div class="portfolio__grid">
                    {$cards}
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       Testimonials Section (dynamically linked)
       ================================================================ */
    private function renderTestimonials(array $service): string
    {
        $entries = $service['testimonials'] ?? [];
        if (empty($entries)) return '';

        $cards = '';
        foreach ($entries as $idx => $e) {
            $fields = is_array($e['fields'] ?? null) ? $e['fields'] : (json_decode($e['fields'] ?? '{}', true) ?: []);
            $name    = esc($e['title'] ?? $fields['name'] ?? '');
            $role    = esc($fields['role'] ?? $fields['position'] ?? '');
            $company = esc($fields['company'] ?? '');
            $quote   = esc($e['excerpt'] ?? $fields['quote'] ?? $fields['text'] ?? '');
            $avatar  = esc($e['featured_image'] ?? $fields['avatar'] ?? '');
            $rating  = (int)($fields['rating'] ?? 5);
            $delay   = $idx * 100;

            $stars = '';
            for ($s = 1; $s <= 5; $s++) {
                $filled = $s <= $rating ? ' testimonial-card__star--filled' : '';
                $stars .= '<span class="testimonial-card__star' . $filled . '" aria-hidden="true">&#9733;</span>';
            }

            $avatarHtml = $avatar
                ? '<img src="' . $avatar . '" alt="' . $name . '" loading="lazy" width="48" height="48" class="testimonial-card__avatar">'
                : '<div class="testimonial-card__avatar testimonial-card__avatar--placeholder" aria-hidden="true">' . esc(mb_substr($name, 0, 1)) . '</div>';

            $cards .= <<<HTML
            <div class="testimonial-card fade-in" style="animation-delay:{$delay}ms">
                <blockquote class="testimonial-card__quote"><p>{$quote}</p></blockquote>
                <div class="testimonial-card__author">
                    {$avatarHtml}
                    <div class="testimonial-card__info">
                        <cite class="testimonial-card__name">{$name}</cite>
                        <span class="testimonial-card__role">{$role}</span>
                    </div>
                </div>
                <div class="testimonial-card__rating" aria-label="{$rating} out of 5 stars">{$stars}</div>
            </div>
            HTML;
        }

        return <<<HTML
        <section class="svc-testimonials section section--alt" id="testimonials" aria-labelledby="testimonials-heading">
            <div class="container">
                <h2 class="section__heading" id="testimonials-heading">Client Testimonials</h2>
                <div class="testimonials__track">
                    {$cards}
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       FAQ Section
       ================================================================ */
    private function renderFaqs(array $service): string
    {
        $faqs = $service['faqs'] ?? [];
        if (empty($faqs)) return '';

        $items = '';
        foreach ($faqs as $idx => $faq) {
            $q = esc($faq['question'] ?? '');
            $a = esc($faq['answer'] ?? '');

            $items .= <<<HTML
            <div class="faq-item js-faq-item">
                <h3 class="faq-item__question">
                    <button class="faq-item__trigger js-faq-trigger"
                            aria-expanded="false"
                            aria-controls="svc-faq-answer-{$idx}"
                            id="svc-faq-question-{$idx}">
                        <span>{$q}</span>
                        <span class="faq-item__icon" aria-hidden="true"></span>
                    </button>
                </h3>
                <div class="faq-item__answer" id="svc-faq-answer-{$idx}" role="region" aria-labelledby="svc-faq-question-{$idx}" hidden>
                    <p>{$a}</p>
                </div>
            </div>
            HTML;
        }

        return <<<HTML
        <section class="svc-faq section" id="faq" aria-labelledby="faq-heading">
            <div class="container">
                <h2 class="section__heading" id="faq-heading">Frequently Asked Questions</h2>
                <div class="faq__list">
                    {$items}
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       CTA Section
       ================================================================ */
    private function renderCta(array $service): string
    {
        $heading    = esc($service['cta_heading'] ?? '');
        $desc       = esc($service['cta_description'] ?? '');
        $btnText    = esc($service['cta_button_text'] ?? '');
        $btnUrl     = esc($service['cta_button_url'] ?? '/contact');
        $showWa     = !empty($service['cta_show_whatsapp']);
        $waLabel    = esc($service['cta_whatsapp_label'] ?? 'Chat on WhatsApp');
        $bgImage    = esc($service['cta_bg_image'] ?? '');

        if (!$heading && !$btnText) return '';

        $bgStyle = $bgImage ? ' style="background-image:url(' . $bgImage . ')"' : '';

        $waHtml = '';
        if ($showWa) {
            $settings = $this->whatsapp->getSettings();
            $waUrl = $this->whatsapp->buildUrl(
                $settings['phone_number'] ?? '',
                'Interested in ' . ($service['title'] ?? '') . ' - from service page'
            );
            $waHtml = '<a href="' . $waUrl . '" class="btn btn--whatsapp btn--lg cta__wa-btn" target="_blank" rel="noopener noreferrer"><span class="cta__wa-icon" aria-hidden="true">&#x1F4AC;</span> ' . $waLabel . '</a>';
        }

        return <<<HTML
        <section class="cta section section--dark" id="cta"{$bgStyle} role="region" aria-labelledby="cta-heading">
            <div class="cta__overlay"></div>
            <div class="container cta__container">
                <h2 class="section__heading section__heading--light cta__heading" id="cta-heading">{$heading}</h2>
                <p class="cta__description">{$desc}</p>
                <div class="cta__actions">
                    <a href="{$btnUrl}" class="btn btn--primary btn--lg cta__btn">{$btnText}</a>
                    {$waHtml}
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       Contact Section
       ================================================================ */
    private function renderContact(array $service): string
    {
        global $site;
        $showWa    = !empty($service['contact_show_whatsapp']);
        $showPhone = !empty($service['contact_show_phone']);
        $showEmail = !empty($service['contact_show_email']);
        $showForm  = !empty($service['contact_show_form']);
        $btnText   = esc($service['contact_button_text'] ?? 'Get Started');
        $btnUrl    = esc($service['contact_button_url'] ?? '/contact');

        $email  = $site->brand->email ?? '';
        $phone  = $site->brand->phone ?? '';

        $items = '';

        if ($showEmail && $email) {
            $items .= '<div class="contact-card__item fade-in"><span class="contact-card__icon contact-card__icon--email" aria-hidden="true"></span><h3 class="contact-card__label">Email</h3><a href="mailto:' . esc($email) . '" class="contact-card__value">' . esc($email) . '</a></div>';
        }

        if ($showPhone && $phone) {
            $items .= '<div class="contact-card__item fade-in"><span class="contact-card__icon contact-card__icon--phone" aria-hidden="true"></span><h3 class="contact-card__label">Phone</h3><a href="tel:' . esc($phone) . '" class="contact-card__value">' . esc($phone) . '</a></div>';
        }

        if ($showWa) {
            $waUrl = $this->whatsapp->buildUrl($phone, 'Inquiry about ' . ($service['title'] ?? 'your service'));
            $items .= '<div class="contact-card__item fade-in"><span class="contact-card__icon contact-card__icon--whatsapp" aria-hidden="true"></span><h3 class="contact-card__label">WhatsApp</h3><a href="' . $waUrl . '" class="contact-card__value" target="_blank" rel="noopener noreferrer">Chat Now</a></div>';
        }

        if (!$items) return '';

        return <<<HTML
        <section class="contact-preview section" id="contact" aria-labelledby="contact-heading">
            <div class="container">
                <h2 class="section__heading" id="contact-heading">Get In Touch</h2>
                <div class="contact-preview__grid">
                    {$items}
                </div>
                <div class="contact-preview__cta-wrap">
                    <a href="{$btnUrl}" class="btn btn--primary btn--lg">{$btnText}</a>
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       Related Global Blocks
       ================================================================ */
    private function renderRelatedBlocks(array $service): string
    {
        $blocks = $service['blocks'] ?? [];
        if (empty($blocks)) return '';

        $html = '';
        foreach ($blocks as $block) {
            $html .= $this->componentLoader->renderBlock($block);
        }
        return $html;
    }
}
