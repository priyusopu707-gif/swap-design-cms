<?php
/**
 * Swap Design - Portfolio Renderer
 *
 * Renders portfolio listing page (/portfolio) and single
 * project pages (/portfolio/{slug}) with all sections.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class PortfolioRenderer
{
    private PortfolioManager $manager;
    private ComponentLoader $componentLoader;
    private WhatsAppManager $whatsapp;

    public function __construct()
    {
        $this->manager         = new PortfolioManager();
        $this->componentLoader = new ComponentLoader();
        $this->whatsapp        = new WhatsAppManager();
    }

    /**
     * Render portfolio listing page.
     */
    public function renderListing(string $category = '', string $search = '', int $page = 1, int $perPage = 9): string
    {
        $filters = ['status' => 'published'];
        if ($category) $filters['category'] = $category;
        if ($search)  $filters['search']    = $search;

        $total       = $this->manager->count($filters);
        $offset      = ($page - 1) * $perPage;
        $filters['limit']  = $perPage;
        $filters['offset'] = $offset;
        $items       = $this->manager->getAll($filters);
        $categories  = $this->manager->getCategories();
        $totalPages  = (int)ceil($total / $perPage);

        $filterHtml = $this->renderFilters($categories, $category, $search);

        $cards = '';
        foreach ($items as $idx => $item) {
            $cards .= $this->renderCard($item, $idx);
        }

        $pagination = $this->renderPagination($page, $totalPages, $category, $search);

        $empty = empty($items)
            ? '<p class="portfolio-listing__empty">No projects found.</p>'
            : '';

        return <<<HTML
        <section class="portfolio-listing section" aria-labelledby="pf-listing-heading">
            <div class="container">
                <h1 class="section__heading" id="pf-listing-heading">Our Portfolio</h1>
                {$filterHtml}
                <div class="portfolio__grid">
                    {$cards}
                </div>
                {$empty}
                {$pagination}
            </div>
        </section>
        HTML;
    }

    /**
     * Render a single portfolio project page.
     */
    public function renderSingle(string $slug): ?string
    {
        $item = $this->manager->getFullBySlug($slug);
        if (!$item) return null;

        $sections = [
            $this->renderHero($item),
            $this->renderOverview($item),
            $this->renderChallenge($item),
            $this->renderProcess($item),
            $this->renderGallery($item),
            $this->renderSolution($item),
            $this->renderResults($item),
            $this->renderTestimonialsSection($item),
            $this->renderRelatedServices($item),
            $this->renderRelatedProjects($item),
            $this->renderRelatedBlogPosts($item),
            $this->renderFaqs($item),
            $this->renderCta($item),
            $this->renderBlocks($item),
        ];

        return implode('', array_filter($sections));
    }

    public function getPageAssets(): array
    {
        return [
            'css' => ['/assets/css/portfolio.css'],
            'js'  => ['/assets/js/pages/portfolio.js'],
        ];
    }

    public function getSchema(array $item): string
    {
        $title  = esc($item['title'] ?? '');
        $desc   = esc($item['description'] ?? $item['full_description'] ?? $item['meta_description'] ?? '');
        $image  = esc($item['og_image'] ?? $item['image_url'] ?? $item['hero_image'] ?? '');
        $url    = esc(SITE_URL . '/portfolio/' . ($item['slug'] ?? ''));
        $client = esc($item['client_name'] ?? '');
        $date   = $item['completion_date'] ?? '';
        $tech   = esc($item['solution_tech'] ?? '');

        return '<script type="application/ld+json">' . json_encode([
            '@context'    => 'https://schema.org',
            '@type'       => ['CreativeWork', 'Project'],
            'name'        => $title,
            'description' => $desc,
            'url'         => $url,
            'image'       => $image ?: null,
            'dateCompleted' => $date ?: null,
            'keywords'    => $tech ?: null,
            'creator'     => $client ? ['@type' => 'Organization', 'name' => $client] : null,
            'provider'    => [
                '@type' => 'Organization',
                'name'  => 'Swap Design',
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }

    /* ================================================================
       Listing: Filters
       ================================================================ */
    private function renderFilters(array $categories, string $activeCat, string $search): string
    {
        $catBtns = '<button class="pf-filter-btn' . ($activeCat === '' ? ' pf-filter-btn--active' : '') . '" data-filter="all"><a href="/portfolio">All</a></button>';
        foreach ($categories as $cat) {
            $active = $activeCat === $cat ? ' pf-filter-btn--active' : '';
            $catBtns .= '<a href="/portfolio?category=' . urlencode($cat) . '" class="pf-filter-btn' . $active . '">' . esc($cat) . '</a>';
        }

        return <<<HTML
        <div class="portfolio-listing__filters">
            <div class="portfolio__filters" role="tablist" aria-label="Project categories">{$catBtns}</div>
            <form method="get" action="/portfolio" class="portfolio-listing__search" role="search">
                <input type="text" name="search" value="{$search}" placeholder="Search projects..." class="portfolio-listing__search-input" aria-label="Search projects">
                <button type="submit" class="portfolio-listing__search-btn" aria-label="Search">&#x1F50D;</button>
            </form>
        </div>
        HTML;
    }

    /* ================================================================
       Listing: Card
       ================================================================ */
    private function renderCard(array $item, int $idx): string
    {
        $title  = esc($item['title'] ?? '');
        $slug   = esc($item['slug'] ?? '');
        $image  = esc($item['image_url'] ?? '');
        $cat    = esc($item['category'] ?? '');
        $desc   = esc(mb_substr($item['description'] ?? $item['full_description'] ?? '', 0, 120));
        $delay  = $idx * 80;

        return <<<HTML
        <article class="pf-card fade-in" style="animation-delay:{$delay}ms">
            <a href="/portfolio/{$slug}" class="pf-card__link" aria-labelledby="pf-title-{$item['id']}">
                <div class="pf-card__image-wrap">
                    <img src="{$image}" alt="{$title}" loading="lazy" width="400" height="300" class="pf-card__image">
                    <div class="pf-card__overlay"><span class="pf-card__view">View Project</span></div>
                </div>
                <div class="pf-card__body">
                    <span class="pf-card__category">{$cat}</span>
                    <h2 class="pf-card__title" id="pf-title-{$item['id']}">{$title}</h2>
                    <p class="pf-card__desc">{$desc}</p>
                </div>
            </a>
        </article>
        HTML;
    }

    /* ================================================================
       Listing: Pagination
       ================================================================ */
    private function renderPagination(int $page, int $total, string $cat, string $search): string
    {
        if ($total <= 1) return '';

        $query = '';
        if ($cat) $query .= '&category=' . urlencode($cat);
        if ($search) $query .= '&search=' . urlencode($search);

        $html = '<nav class="pagination" aria-label="Portfolio pagination"><ul class="pagination__list">';

        if ($page > 1) {
            $html .= '<li><a href="/portfolio?page=' . ($page - 1) . $query . '" class="pagination__link" aria-label="Previous page">&laquo;</a></li>';
        }

        for ($p = 1; $p <= $total; $p++) {
            $active = $p === $page ? ' pagination__link--active' : '';
            $aria   = $p === $page ? ' aria-current="page"' : '';
            $html  .= '<li><a href="/portfolio?page=' . $p . $query . '" class="pagination__link' . $active . '"' . $aria . '>' . $p . '</a></li>';
        }

        if ($page < $total) {
            $html .= '<li><a href="/portfolio?page=' . ($page + 1) . $query . '" class="pagination__link" aria-label="Next page">&raquo;</a></li>';
        }

        $html .= '</ul></nav>';
        return $html;
    }

    /* ================================================================
       Single: Hero
       ================================================================ */
    private function renderHero(array $item): string
    {
        $hTitle = esc($item['hero_title'] ?? $item['title'] ?? '');
        $hDesc  = esc($item['hero_description'] ?? $item['description'] ?? '');
        $hImage = esc($item['hero_image'] ?? $item['image_url'] ?? '');
        $hBg    = esc($item['hero_bg_image'] ?? '');
        $ctaText= esc($item['hero_cta_text'] ?? '');
        $ctaUrl = esc($item['hero_cta_url'] ?? '#');
        $client = esc($item['client_name'] ?? '');
        $industry = esc($item['industry'] ?? '');
        $date   = $item['completion_date'] ?? '';

        $meta = [];
        if ($client) $meta[] = '<span class="pf-single__meta-item"><strong>Client:</strong> ' . $client . '</span>';
        if ($industry) $meta[] = '<span class="pf-single__meta-item"><strong>Industry:</strong> ' . $industry . '</span>';
        if ($date) $meta[] = '<span class="pf-single__meta-item"><strong>Completed:</strong> ' . esc($date) . '</span>';

        $bgStyle = $hBg ? ' style="background-image:url(' . $hBg . ')"' : '';
        $hasImage = $hImage ? ' pf-single-hero--with-image' : '';
        $ctaBtn  = $ctaText ? '<a href="' . $ctaUrl . '" class="btn btn--primary pf-single-hero__btn">' . $ctaText . '</a>' : '';
        $metaHtml = implode('', $meta);

        return <<<HTML
        <section class="pf-single-hero{$hasImage}"{$bgStyle} role="banner" aria-labelledby="pf-single-title">
            <div class="pf-single-hero__overlay"></div>
            <div class="container pf-single-hero__container">
                <div class="pf-single-hero__content">
                    <nav class="breadcrumb" aria-label="Breadcrumb"><a href="/">Home</a> &rsaquo; <a href="/portfolio">Portfolio</a> &rsaquo; <span aria-current="page">{$hTitle}</span></nav>
                    <h1 class="pf-single-hero__title" id="pf-single-title">{$hTitle}</h1>
                    <p class="pf-single-hero__desc">{$hDesc}</p>
                    {$ctaBtn}
                </div>
            </div>
        </section>
        <div class="pf-single-meta container">
            <div class="pf-single-meta__items">{$metaHtml}</div>
        </div>
        HTML;
    }

    /* ================================================================
       Single: Overview
       ================================================================ */
    private function renderOverview(array $item): string
    {
        $summary       = esc($item['overview_summary'] ?? '');
        $requirements  = esc($item['overview_requirements'] ?? '');
        $problem       = esc($item['overview_problem'] ?? '');
        $objectives    = esc($item['overview_objectives'] ?? '');
        $duration      = esc($item['project_duration'] ?? '');
        $deliverables  = esc($item['project_deliverables'] ?? '');
        $servicesUsed  = esc($item['project_services_used'] ?? '');
        $projectUrl    = esc($item['project_url'] ?? '');

        if (!$summary && !$problem && !$objectives) return '';

        $blocks = '';
        if ($summary)      $blocks .= '<div class="pf-single-block"><h2 class="pf-single-block__heading">Project Summary</h2><p>' . nl2br($summary) . '</p></div>';
        if ($problem)      $blocks .= '<div class="pf-single-block"><h2 class="pf-single-block__heading">Challenge</h2><p>' . nl2br($problem) . '</p></div>';
        if ($requirements) $blocks .= '<div class="pf-single-block"><h2 class="pf-single-block__heading">Client Requirements</h2><p>' . nl2br($requirements) . '</p></div>';
        if ($objectives)   $blocks .= '<div class="pf-single-block"><h2 class="pf-single-block__heading">Objectives</h2><p>' . nl2br($objectives) . '</p></div>';

        $detailsHtml = '';
        if ($duration || $deliverables || $servicesUsed || $projectUrl) {
            $detailsHtml .= '<div class="pf-single-sidebar"><h3>Project Details</h3><ul class="pf-single-sidebar__list">';
            if ($duration)     $detailsHtml .= '<li><strong>Duration:</strong> ' . $duration . '</li>';
            if ($deliverables) $detailsHtml .= '<li><strong>Deliverables:</strong> ' . nl2br($deliverables) . '</li>';
            if ($servicesUsed) $detailsHtml .= '<li><strong>Services:</strong> ' . $servicesUsed . '</li>';
            if ($projectUrl)   $detailsHtml .= '<li><a href="' . $projectUrl . '" target="_blank" rel="noopener">View Live Project &rarr;</a></li>';
            $detailsHtml .= '</ul></div>';
        }

        return <<<HTML
        <section class="pf-single-section section" id="overview">
            <div class="container">
                <div class="pf-single-layout">
                    <div class="pf-single-main">{$blocks}</div>
                    {$detailsHtml}
                </div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       Single: Gallery
       ================================================================ */
    private function renderGallery(array $item): string
    {
        $images = $item['gallery'] ?? [];
        if (empty($images)) return '';

        $html = '<section class="pf-single-section section section--alt" id="gallery">';
        $html .= '<div class="container"><h2 class="section__heading">Project Gallery</h2>';
        $html .= '<div class="pf-gallery__grid">';

        foreach ($images as $idx => $img) {
            $url     = esc($img['image_url'] ?? '');
            $caption = esc($img['caption'] ?? '');
            $type    = esc($img['image_type'] ?? 'general');
            $delay   = $idx * 100;

            $html .= <<<HTML
            <figure class="pf-gallery__item pf-gallery__item--{$type} fade-in" style="animation-delay:{$delay}ms">
                <img src="{$url}" alt="{$caption}" loading="lazy" class="pf-gallery__image">
                <figcaption class="pf-gallery__caption">{$caption}</figcaption>
            </figure>
            HTML;
        }

        $html .= '</div></div></section>';
        return $html;
    }

    /* ================================================================
       Single: Solution
       ================================================================ */
    private function renderSolution(array $item): string
    {
        $strategy = esc($item['solution_strategy'] ?? '');
        $branding = esc($item['solution_branding'] ?? '');
        $process  = esc($item['solution_process'] ?? '');
        $tech     = esc($item['solution_tech'] ?? '');

        if (!$strategy && !$branding && !$process && !$tech) return '';

        $blocks = '';
        if ($strategy) $blocks .= '<div class="pf-single-block"><h2 class="pf-single-block__heading">Design Strategy</h2><p>' . nl2br($strategy) . '</p></div>';
        if ($branding) $blocks .= '<div class="pf-single-block"><h2 class="pf-single-block__heading">Branding Approach</h2><p>' . nl2br($branding) . '</p></div>';
        if ($process)  $blocks .= '<div class="pf-single-block"><h2 class="pf-single-block__heading">Development Process</h2><p>' . nl2br($process) . '</p></div>';
        if ($tech)     $blocks .= '<div class="pf-single-block"><h2 class="pf-single-block__heading">Technologies Used</h2><p>' . nl2br($tech) . '</p></div>';

        return <<<HTML
        <section class="pf-single-section section" id="solution">
            <div class="container">
                <h2 class="section__heading">Our Solution</h2>
                <div class="pf-single-main">{$blocks}</div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       Single: Results
       ================================================================ */
    private function renderResults(array $item): string
    {
        $summary     = esc($item['results_summary'] ?? '');
        $achievements= esc($item['results_achievements'] ?? '');
        $feedback    = esc($item['results_feedback'] ?? '');

        if (!$summary && !$achievements && !$feedback) return '';

        $blocks = '';
        if ($summary)      $blocks .= '<div class="pf-single-block"><h2 class="pf-single-block__heading">Outcome</h2><p>' . nl2br($summary) . '</p></div>';
        if ($achievements) $blocks .= '<div class="pf-single-block"><h2 class="pf-single-block__heading">Key Achievements</h2><p>' . nl2br($achievements) . '</p></div>';
        if ($feedback)     $blocks .= '<div class="pf-single-block"><blockquote class="pf-single-block__quote">' . nl2br($feedback) . '</blockquote></div>';

        return <<<HTML
        <section class="pf-single-section section section--alt" id="results">
            <div class="container">
                <h2 class="section__heading">Results</h2>
                <div class="pf-single-main">{$blocks}</div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       Single: Testimonials
       ================================================================ */
    private function renderTestimonialsSection(array $item): string
    {
        $entries = $item['testimonials'] ?? [];
        if (empty($entries)) return '';

        $cards = '';
        foreach ($entries as $e) {
            $fields = is_array($e['fields'] ?? null) ? $e['fields'] : (json_decode($e['fields'] ?? '{}', true) ?: []);
            $name  = esc($e['title'] ?? $fields['name'] ?? '');
            $role  = esc($fields['role'] ?? '');
            $quote = esc($e['excerpt'] ?? $fields['quote'] ?? '');
            $avatar= esc($e['featured_image'] ?? $fields['avatar'] ?? '');

            $avatarHtml = $avatar
                ? '<img src="' . $avatar . '" alt="' . $name . '" loading="lazy" width="48" height="48" class="testimonial-card__avatar">'
                : '<div class="testimonial-card__avatar testimonial-card__avatar--placeholder">' . esc(mb_substr($name, 0, 1)) . '</div>';

            $cards .= <<<HTML
            <div class="testimonial-card">
                <blockquote class="testimonial-card__quote"><p>{$quote}</p></blockquote>
                <div class="testimonial-card__author">{$avatarHtml}<div class="testimonial-card__info"><cite class="testimonial-card__name">{$name}</cite><span class="testimonial-card__role">{$role}</span></div></div>
            </div>
            HTML;
        }

        return <<<HTML
        <section class="pf-single-section section" id="testimonials"><div class="container"><h2 class="section__heading">Client Feedback</h2><div class="testimonials__track">{$cards}</div></div></section>
        HTML;
    }

    /* ================================================================
       Single: Related Services
       ================================================================ */
    private function renderRelatedServices(array $item): string
    {
        $services = $item['related_services'] ?? [];
        if (empty($services)) return '';

        $cards = '';
        foreach ($services as $svc) {
            $cards .= '<div class="service-card fade-in"><a href="/services/' . esc($svc['slug']) . '" class="service-card__link"><span class="service-card__icon"></span><h3 class="service-card__title">' . esc($svc['title']) . '</h3><p class="service-card__excerpt">' . esc($svc['short_description'] ?? '') . '</p><span class="service-card__arrow">&rarr;</span></a></div>';
        }

        return <<<HTML
        <section class="pf-single-section section section--alt" id="related-services"><div class="container"><h2 class="section__heading">Related Services</h2><div class="services__grid">{$cards}</div></div></section>
        HTML;
    }

    /* ================================================================
       Single: Related Projects
       ================================================================ */
    private function renderRelatedProjects(array $item): string
    {
        $category = $item['category'] ?? '';
        $projects = $this->manager->getRelatedProjects((int)$item['id'], $category, 3);
        if (empty($projects)) return '';

        $cards = '';
        foreach ($projects as $p) {
            $cards .= $this->renderCard($p, 0);
        }

        return <<<HTML
        <section class="pf-single-section section" id="related-projects"><div class="container"><h2 class="section__heading">Related Projects</h2><div class="portfolio__grid">{$cards}</div></div></section>
        HTML;
    }

    /* ================================================================
       Single: FAQs
       ================================================================ */
    private function renderFaqs(array $item): string
    {
        $faqs = $item['faqs'] ?? [];
        if (empty($faqs)) return '';

        $html = '<section class="pf-single-section section section--alt" id="faq"><div class="container"><h2 class="section__heading">FAQ</h2><div class="faq__list">';

        foreach ($faqs as $idx => $faq) {
            $q = esc($faq['question'] ?? '');
            $a = esc($faq['answer'] ?? '');
            $html .= '<div class="faq-item js-faq-item"><h3 class="faq-item__question"><button class="faq-item__trigger js-faq-trigger" aria-expanded="false" aria-controls="pf-faq-' . $idx . '" id="pf-faq-q-' . $idx . '"><span>' . $q . '</span><span class="faq-item__icon"></span></button></h3><div class="faq-item__answer" id="pf-faq-' . $idx . '" role="region" aria-labelledby="pf-faq-q-' . $idx . '" hidden><p>' . $a . '</p></div></div>';
        }

        $html .= '</div></div></section>';
        return $html;
    }

    /* ================================================================
       Single: Challenge
       ================================================================ */
    private function renderChallenge(array $item): string
    {
        $problem = esc($item['overview_problem'] ?? '');
        $requirements = esc($item['overview_requirements'] ?? '');
        $objectives = esc($item['overview_objectives'] ?? '');

        if (!$problem && !$requirements && !$objectives) return '';

        $blocks = '';
        if ($problem)      $blocks .= '<div class="pf-single-block"><h2 class="pf-single-block__heading">The Challenge</h2><p>' . nl2br($problem) . '</p></div>';
        if ($requirements) $blocks .= '<div class="pf-single-block"><h2 class="pf-single-block__heading">Client Requirements</h2><p>' . nl2br($requirements) . '</p></div>';
        if ($objectives)   $blocks .= '<div class="pf-single-block"><h2 class="pf-single-block__heading">Objectives</h2><p>' . nl2br($objectives) . '</p></div>';

        return <<<HTML
        <section class="pf-single-section section section--alt" id="challenge">
            <div class="container">
                <h2 class="section__heading">The Challenge</h2>
                <div class="pf-single-main">{$blocks}</div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       Single: Process
       ================================================================ */
    private function renderProcess(array $item): string
    {
        $process = esc($item['solution_process'] ?? '');
        $strategy = esc($item['solution_strategy'] ?? '');
        $branding = esc($item['solution_branding'] ?? '');
        $tech = esc($item['solution_tech'] ?? '');

        if (!$process && !$strategy && !$branding && !$tech) return '';

        $steps = '';
        if ($strategy) $steps .= '<div class="pf-process-step"><div class="pf-process-step__icon pf-process-step__icon--strategy" aria-hidden="true"><span class="pf-process-step__num">1</span></div><div class="pf-process-step__content"><h3 class="pf-process-step__title">Design Strategy</h3><p>' . nl2br($strategy) . '</p></div></div>';
        if ($branding) $steps .= '<div class="pf-process-step"><div class="pf-process-step__icon pf-process-step__icon--branding" aria-hidden="true"><span class="pf-process-step__num">2</span></div><div class="pf-process-step__content"><h3 class="pf-process-step__title">Branding Approach</h3><p>' . nl2br($branding) . '</p></div></div>';
        if ($process)  $steps .= '<div class="pf-process-step"><div class="pf-process-step__icon pf-process-step__icon--dev" aria-hidden="true"><span class="pf-process-step__num">3</span></div><div class="pf-process-step__content"><h3 class="pf-process-step__title">Development Process</h3><p>' . nl2br($process) . '</p></div></div>';
        if ($tech)     $steps .= '<div class="pf-process-step"><div class="pf-process-step__icon pf-process-step__icon--tech" aria-hidden="true"><span class="pf-process-step__num">4</span></div><div class="pf-process-step__content"><h3 class="pf-process-step__title">Technologies Used</h3><p>' . nl2br($tech) . '</p></div></div>';

        return <<<HTML
        <section class="pf-single-section section" id="process">
            <div class="container">
                <h2 class="section__heading">Our Process</h2>
                <div class="pf-process__steps">{$steps}</div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       Single: Related Blog Posts
       ================================================================ */
    private function renderRelatedBlogPosts(array $item): string
    {
        $blogs = $item['related_blogs'] ?? [];
        if (empty($blogs)) return '';

        $cards = '';
        foreach ($blogs as $bp) {
            $title = esc($bp['title'] ?? '');
            $slug  = esc($bp['slug'] ?? '');
            $image = esc($bp['featured_image'] ?? '');
            $desc  = esc(mb_substr($bp['short_description'] ?? '', 0, 120));
            $date  = $bp['published_at'] ?? $bp['created_at'] ?? '';
            $dateStr = $date ? date('M j, Y', strtotime($date)) : '';

            $imgHtml = $image ? '<img src="' . $image . '" alt="' . $title . '" loading="lazy" class="pf-blog-card__image">' : '';

            $cards .= <<<HTML
            <article class="pf-blog-card fade-in">
                <a href="/blog/{$slug}" class="pf-blog-card__link">
                    <div class="pf-blog-card__image-wrap">{$imgHtml}</div>
                    <div class="pf-blog-card__body">
                        <time class="pf-blog-card__date">{$dateStr}</time>
                        <h3 class="pf-blog-card__title">{$title}</h3>
                        <p class="pf-blog-card__desc">{$desc}</p>
                        <span class="pf-blog-card__more">Read Article &rarr;</span>
                    </div>
                </a>
            </article>
            HTML;
        }

        return <<<HTML
        <section class="pf-single-section section section--alt" id="related-blog">
            <div class="container">
                <h2 class="section__heading">Related Articles</h2>
                <div class="pf-blog__grid">{$cards}</div>
            </div>
        </section>
        HTML;
    }

    /* ================================================================
       Single: CTA
       ================================================================ */
    private function renderCta(array $item): string
    {
        $heading = esc($item['cta_heading'] ?? '');
        $desc    = esc($item['cta_description'] ?? '');
        $btnText = esc($item['cta_button_text'] ?? '');
        $btnUrl  = esc($item['cta_button_url'] ?? '/contact');
        $showWa  = !empty($item['cta_show_whatsapp']);
        $waLabel = esc($item['cta_whatsapp_label'] ?? '');
        $bgImg   = esc($item['cta_bg_image'] ?? '');

        if (!$heading && !$btnText) return '';

        $bgStyle = $bgImg ? ' style="background-image:url(' . $bgImg . ')"' : '';
        $waHtml = $showWa ? '<a href="#" class="btn btn--whatsapp btn--lg"><span class="cta__wa-icon">&#x1F4AC;</span> ' . $waLabel . '</a>' : '';

        return <<<HTML
        <section class="cta section section--dark"{$bgStyle}><div class="cta__overlay"></div><div class="container cta__container"><h2 class="section__heading section__heading--light">{$heading}</h2><p class="cta__description">{$desc}</p><div class="cta__actions"><a href="{$btnUrl}" class="btn btn--primary btn--lg">{$btnText}</a>{$waHtml}</div></div></section>
        HTML;
    }

    /* ================================================================
       Single: Related Blocks
       ================================================================ */
    private function renderBlocks(array $item): string
    {
        $blocks = $item['blocks'] ?? [];
        if (empty($blocks)) return '';

        $html = '';
        foreach ($blocks as $block) {
            $html .= $this->componentLoader->renderBlock($block);
        }
        return $html;
    }

    /* Meta accessor for template */
    public function renderMeta(array $item): string
    {
        $client   = esc($item['client_name'] ?? '');
        $industry = esc($item['industry'] ?? '');
        $date     = $item['completion_date'] ?? '';
        $duration = esc($item['project_duration'] ?? '');

        $parts = [];
        if ($client)   $parts[] = '<span><strong>Client:</strong> ' . $client . '</span>';
        if ($industry) $parts[] = '<span><strong>Industry:</strong> ' . $industry . '</span>';
        if ($date)     $parts[] = '<span><strong>Completed:</strong> ' . $date . '</span>';
        if ($duration) $parts[] = '<span><strong>Duration:</strong> ' . $duration . '</span>';

        return '<div class="pf-single-meta__items">' . implode('', $parts) . '</div>';
    }
}
