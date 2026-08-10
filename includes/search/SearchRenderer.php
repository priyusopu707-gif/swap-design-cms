<?php
/**
 * Swap Design - Search Renderer
 *
 * Renders the full /search results page: search form, active filter
 * chips, filter sidebar (type/category/tag/date/featured), result list
 * with keyword highlighting, sort control, pagination, related results,
 * and a no-results state with suggestions.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class SearchRenderer
{
    private SearchManager $manager;

    public const SORTS = [
        'relevance'    => 'Most Relevant',
        'newest'       => 'Newest First',
        'oldest'       => 'Oldest First',
        'alphabetical' => 'Alphabetical',
    ];

    public function __construct()
    {
        $this->manager = new SearchManager();
    }

    public function getPageAssets(): array
    {
        return [
            'css' => ['/assets/css/pages/search.css'],
            'js'  => ['/assets/js/pages/search.js'],
        ];
    }

    /**
     * Render the search results page.
     */
    public function render(): string
    {
        $params = $this->collectParams();

        $filters = [
            'type'     => $params['type'],
            'category' => $params['category'],
            'tag'      => $params['tag'],
            'featured' => $params['featured'],
            'from'     => $params['from'],
            'to'       => $params['to'],
            'sort'     => $params['sort'],
        ];

        $result = $this->manager->search($params['q'], $filters, $params['page'], $params['per_page']);

        /* Log full-page searches for admin analytics. */
        if ($params['q'] !== '') {
            $searchLogId = $this->manager->logSearch($params['q'], $result['total']);
        } else {
            $searchLogId = null;
        }

        $facets   = $this->manager->getFacets();
        $terms    = $this->queryTerms($params['q']);
        $related  = $result['total'] > 0 && $result['total'] < $params['per_page']
            ? $this->manager->getRelatedForQuery($result['items'])
            : [];

        $csrfToken = csrfToken();

        return $this->renderLayout($params, $result, $facets, $related, $terms, $searchLogId, $csrfToken);
    }

    /* ================================================================
       Layout
       ================================================================ */

    private function renderLayout(array $params, array $result, array $facets, array $related, array $terms, ?int $searchLogId, string $csrfToken): string
    {
        $hasQuery   = $params['q'] !== '';
        $hasFilters = $params['type'] || $params['category'] || $params['tag'] || $params['featured'] || $params['from'] || $params['to'];

        $formHtml      = $this->renderForm($params['q']);
        $chipsHtml     = $hasFilters ? $this->renderActiveFilters($params) : '';
        $sidebarHtml   = $this->renderSidebar($params, $facets);
        $metaHtml      = $this->renderResultsMeta($params, $result);
        $listHtml      = $this->renderResultsList($result, $params, $terms, $searchLogId, $csrfToken);
        $pagination    = $this->renderPagination($params, $result);
        $noResultsHtml = $hasQuery && $result['total'] === 0 ? $this->renderNoResults($params, $result, $terms) : '';
        $relatedHtml   = !empty($related) ? $this->renderRelated($related) : '';
        $csrfInput     = '<input type="hidden" name="search_csrf" value="' . esc($csrfToken) . '">';

        return <<<HTML
        <section class="search-page section" id="search-page">
            <div class="container">
                <header class="search-page__header">
                    <h1 class="section__heading">Search</h1>
                    {$formHtml}
                    {$chipsHtml}
                </header>

                <div class="search-page__layout">
                    <aside class="search-page__sidebar" aria-label="Search filters">
                        {$sidebarHtml}
                    </aside>

                    <div class="search-page__content">
                        {$metaHtml}
                        {$listHtml}
                        {$pagination}
                        {$noResultsHtml}
                        {$relatedHtml}
                    </div>
                </div>
            </div>
            {$csrfInput}
        </section>
        HTML;
    }

    private function renderForm(string $query): string
    {
        $value = esc($query);

        return <<<HTML
        <form class="search-page__form" action="/search" method="get" role="search">
            <div class="search-page__form-inner">
                <input type="search" class="search-page__input" name="q" value="{$value}"
                       placeholder="Search services, portfolio, blog posts..." autocomplete="off">
                <button type="submit" class="btn btn--primary search-page__submit">Search</button>
            </div>
        </form>
        HTML;
    }

    private function renderActiveFilters(array $params): string
    {
        $chips = [];

        if ($params['type']) {
            $chips[] = ['label' => 'Type: ' . $this->typeLabel($params['type']), 'overrides' => ['type' => '']];
        }
        if ($params['category']) {
            $chips[] = ['label' => 'Category: ' . $params['category'], 'overrides' => ['category' => '']];
        }
        if ($params['tag']) {
            $chips[] = ['label' => 'Tag: ' . $params['tag'], 'overrides' => ['tag' => '']];
        }
        if ($params['featured']) {
            $chips[] = ['label' => 'Featured only', 'overrides' => ['featured' => '']];
        }
        if ($params['from']) {
            $chips[] = ['label' => 'From: ' . $params['from'], 'overrides' => ['from' => '']];
        }
        if ($params['to']) {
            $chips[] = ['label' => 'To: ' . $params['to'], 'overrides' => ['to' => '']];
        }

        $html = '<div class="search-page__chips">';
        foreach ($chips as $chip) {
            $url = $this->buildSearchUrl($chip['overrides']);
            $html .= '<a class="search-page__chip" href="' . esc($url) . '">' . esc($chip['label']) . ' <span aria-hidden="true">&times;</span></a>';
        }
        $html .= '</div>';

        return $html;
    }

    private function renderSidebar(array $params, array $facets): string
    {
        $html = '<div class="search-sidebar">';

        /* Content type */
        $html .= '<div class="search-sidebar__group">';
        $html .= '<h2 class="search-sidebar__heading">Content Type</h2>';
        $html .= '<ul class="search-sidebar__list">';
        foreach ($facets['types'] as $type) {
            if ($type['count'] <= 0) {
                continue;
            }
            $url   = $this->buildSearchUrl(['type' => $type['value']]);
            $active = $params['type'] === $type['value'] ? ' search-sidebar__link--active' : '';
            $html .= '<li><a class="search-sidebar__link' . $active . '" href="' . esc($url) . '">'
                   . esc($type['label']) . ' <span class="search-sidebar__count">' . (int)$type['count'] . '</span></a></li>';
        }
        $html .= '</ul></div>';

        /* Categories */
        if (!empty($facets['categories'])) {
            $html .= '<div class="search-sidebar__group">';
            $html .= '<h2 class="search-sidebar__heading">Categories</h2>';
            $html .= '<ul class="search-sidebar__list">';
            foreach ($facets['categories'] as $cat) {
                $url    = $this->buildSearchUrl(['category' => $cat['category']]);
                $active = $params['category'] === $cat['category'] ? ' search-sidebar__link--active' : '';
                $html .= '<li><a class="search-sidebar__link' . $active . '" href="' . esc($url) . '">'
                       . esc($cat['category']) . ' <span class="search-sidebar__count">' . (int)$cat['count'] . '</span></a></li>';
            }
            $html .= '</ul></div>';
        }

        /* Tags */
        if (!empty($facets['tags'])) {
            $html .= '<div class="search-sidebar__group">';
            $html .= '<h2 class="search-sidebar__heading">Tags</h2>';
            $html .= '<div class="search-sidebar__tags">';
            foreach ($facets['tags'] as $tag => $count) {
                $url   = $this->buildSearchUrl(['tag' => $tag]);
                $active = $params['tag'] === $tag ? ' search-sidebar__tag--active' : '';
                $html .= '<a class="search-sidebar__tag' . $active . '" href="' . esc($url) . '">' . esc($tag) . '</a>';
            }
            $html .= '</div></div>';
        }

        /* Date range */
        $html .= '<div class="search-sidebar__group">';
        $html .= '<h2 class="search-sidebar__heading">Date Range</h2>';
        $html .= '<form class="search-sidebar__dateform" method="get" action="/search">';
        $html .= '<input type="hidden" name="q" value="' . esc($params['q']) . '">';
        if ($params['type'])     $html .= '<input type="hidden" name="type" value="' . esc($params['type']) . '">';
        if ($params['category']) $html .= '<input type="hidden" name="category" value="' . esc($params['category']) . '">';
        if ($params['tag'])      $html .= '<input type="hidden" name="tag" value="' . esc($params['tag']) . '">';
        $html .= '<label class="search-sidebar__label" for="search-from">From</label>';
        $html .= '<input class="search-sidebar__date" type="date" id="search-from" name="from" value="' . esc($params['from']) . '">';
        $html .= '<label class="search-sidebar__label" for="search-to">To</label>';
        $html .= '<input class="search-sidebar__date" type="date" id="search-to" name="to" value="' . esc($params['to']) . '">';
        $html .= '<button type="submit" class="search-sidebar__apply">Apply</button>';
        $html .= '</form></div>';

        /* Featured + clear */
        $featuredUrl = $this->buildSearchUrl(['featured' => $params['featured'] ? '' : '1']);
        $featuredOn  = $params['featured'] ? ' checked' : '';
        $html .= '<div class="search-sidebar__group">';
        $html .= '<label class="search-sidebar__featured"><a href="' . esc($featuredUrl) . '">'
               . '<input type="checkbox" data-featured-toggle' . $featuredOn . '> Featured results only</a></label>';
        $html .= '</div>';

        $html .= '<a class="search-sidebar__clear" href="/search">Clear all filters</a>';
        $html .= '</div>';

        return $html;
    }

    private function renderResultsMeta(array $params, array $result): string
    {
        $label = $result['total'] . ' ' . ($result['total'] === 1 ? 'result' : 'results');
        $label .= $params['q'] !== '' ? ' for &ldquo;' . esc($params['q']) . '&rdquo;' : '';

        $options = '';
        foreach (self::SORTS as $value => $labelText) {
            $selected = $params['sort'] === $value ? ' selected' : '';
            $options .= '<option value="' . $value . '"' . $selected . '>' . $labelText . '</option>';
        }

        $sortUrl = $this->buildSearchUrl(['sort' => '__SORT__']);

        return <<<HTML
        <div class="search-page__meta">
            <p class="search-page__count">{$label}</p>
            <label class="search-page__sort">
                <span class="search-page__sort-label">Sort:</span>
                <select class="search-page__sort-select" data-sort-select data-sort-url="{$sortUrl}">
                    {$options}
                </select>
            </label>
        </div>
        HTML;
    }

    private function renderResultsList(array $result, array $params, array $terms, ?int $searchLogId, string $csrfToken): string
    {
        if ($result['total'] === 0) {
            return '';
        }

        $html = '<ol class="search-results">';
        $position = ($result['page'] - 1) * $result['per_page'];

        foreach ($result['items'] as $item) {
            $position++;
            $html .= $this->renderResultItem($item, $terms, $position, $searchLogId);
        }

        $html .= '</ol>';

        return $html;
    }

    private function renderResultItem(array $item, array $terms, int $position, ?int $searchLogId): string
    {
        $title   = $this->highlight($item['title'] ?? '', $terms);
        $excerpt = $item['excerpt'] !== '' ? $item['excerpt'] : $this->truncate($item['content'] ?? '', 220);
        $excerpt = $this->highlight($excerpt, $terms);

        $url   = esc($item['url'] ?? '#');
        $type  = esc($item['type_label'] ?? '');
        $cat   = $item['category'] ?? '';
        $date  = $this->formatDate($item['published_at'] ?? null);
        $image = $item['image'] ?? '';
        $logId = $searchLogId !== null ? (int)$searchLogId : 0;

        $imageHtml = $image
            ? '<img class="search-result__image" src="' . esc($image) . '" alt="' . esc($item['title'] ?? '') . '" loading="lazy">'
            : '<span class="search-result__placeholder" aria-hidden="true"></span>';

        $meta = [];
        if ($cat !== '') {
            $meta[] = esc($cat);
        }
        if ($date !== '') {
            $meta[] = $date;
        }
        $metaHtml = $meta ? '<div class="search-result__meta">' . implode('<span class="search-result__dot">·</span>', $meta) . '</div>' : '';

        return <<<HTML
        <li class="search-result">
            <a class="search-result__link"
               href="{$url}"
               data-search-result
               data-search-log-id="{$logId}"
               data-position="{$position}"
               data-content-type="{$item['content_type']}"
               data-content-id="{$item['content_id']}">
                {$imageHtml}
                <div class="search-result__body">
                    <span class="search-result__type">{$type}</span>
                    <h3 class="search-result__title">{$title}</h3>
                    <p class="search-result__excerpt">{$excerpt}</p>
                    {$metaHtml}
                </div>
            </a>
        </li>
        HTML;
    }

    private function renderPagination(array $params, array $result): string
    {
        if ($result['total_pages'] <= 1) {
            return '';
        }

        $page = $result['page'];
        $totalPages = $result['total_pages'];

        $html = '<nav class="search-page__pagination" aria-label="Search results pages">';
        $html .= '<span class="search-page__pagination-label">Page ' . $page . ' of ' . $totalPages . '</span>';
        $html .= '<div class="search-page__pagination-links">';

        if ($page > 1) {
            $html .= '<a class="search-page__page-link" rel="prev" href="' . esc($this->buildSearchUrl(['page' => $page - 1])) . '">&laquo; Prev</a>';
        }

        $start = max(1, $page - 2);
        $end   = min($totalPages, $page + 2);
        if ($start > 1) {
            $html .= '<a class="search-page__page-link" href="' . esc($this->buildSearchUrl(['page' => 1])) . '">1</a>';
            if ($start > 2) {
                $html .= '<span class="search-page__page-ellipsis">&hellip;</span>';
            }
        }

        for ($i = $start; $i <= $end; $i++) {
            $active = $i === $page ? ' search-page__page-link--active' : '';
            $html .= '<a class="search-page__page-link' . $active . '" href="' . esc($this->buildSearchUrl(['page' => $i])) . '"' . ($i === $page ? ' aria-current="page"' : '') . '>' . $i . '</a>';
        }

        if ($end < $totalPages) {
            if ($end < $totalPages - 1) {
                $html .= '<span class="search-page__page-ellipsis">&hellip;</span>';
            }
            $html .= '<a class="search-page__page-link" href="' . esc($this->buildSearchUrl(['page' => $totalPages])) . '">' . $totalPages . '</a>';
        }

        if ($page < $totalPages) {
            $html .= '<a class="search-page__page-link" rel="next" href="' . esc($this->buildSearchUrl(['page' => $page + 1])) . '">Next &raquo;</a>';
        }

        $html .= '</div></nav>';

        return $html;
    }

    private function renderNoResults(array $params, array $result, array $terms): string
    {
        $query    = esc($params['q']);
        $html     = '<div class="search-empty">';
        $html    .= '<h2 class="search-empty__title">No results found for &ldquo;' . $query . '&rdquo;</h2>';
        $html    .= '<p class="search-empty__hint">Try different keywords, check the spelling, or browse popular searches below.</p>';

        if ($result['did_you_mean']) {
            $corrected = esc($result['did_you_mean']);
            $url       = esc($this->buildSearchUrl(['q' => $result['did_you_mean']]));
            $html     .= '<p class="search-empty__didyoumean">Did you mean: <a href="' . $url . '">' . $corrected . '</a></p>';
        }

        $popular = $this->manager->getPopularSearches(8);
        if (!empty($popular)) {
            $html .= '<h3 class="search-empty__subtitle">Popular Searches</h3><ul class="search-empty__popular">';
            foreach ($popular as $p) {
                if (empty($p['query'])) {
                    continue;
                }
                $url   = esc($this->buildSearchUrl(['q' => $p['query']]));
                $html .= '<li><a href="' . $url . '">' . esc($p['query']) . '</a></li>';
            }
            $html .= '</ul>';
        }

        $latest = $this->manager->getLatestItems(6);
        if (!empty($latest)) {
            $html .= '<h3 class="search-empty__subtitle">You might also like</h3><ul class="search-empty__latest">';
            foreach ($latest as $item) {
                $html .= '<li><a href="' . esc($item['url']) . '">' . esc($item['title']) . '</a>'
                       . '<span class="search-empty__latest-type">' . esc($item['type_label']) . '</span></li>';
            }
            $html .= '</ul>';
        }

        $html .= '</div>';

        return $html;
    }

    private function renderRelated(array $related): string
    {
        $html = '<div class="search-related">';
        $html .= '<h2 class="search-related__heading">Related Results</h2>';
        $html .= '<ul class="search-related__list">';

        foreach ($related as $item) {
            $html .= '<li class="search-related__item">';
            $html .= '<a href="' . esc($item['url']) . '">';
            $html .= '<span class="search-related__type">' . esc($item['type_label']) . '</span>';
            $html .= '<span class="search-related__title">' . esc($item['title']) . '</span>';
            $html .= '</a></li>';
        }

        $html .= '</ul></div>';

        return $html;
    }

    /* ================================================================
       Helpers
       ================================================================ */

    private function collectParams(): array
    {
        $q = trim((string)($_GET['q'] ?? ''));
        if (mb_strlen($q) > 100) {
            $q = mb_substr($q, 0, 100);
        }

        return [
            'q'        => $q,
            'type'     => $this->validType($_GET['type'] ?? ''),
            'category' => trim((string)($_GET['category'] ?? '')),
            'tag'      => trim((string)($_GET['tag'] ?? '')),
            'featured' => !empty($_GET['featured']) ? '1' : '',
            'from'     => $this->validDate($_GET['from'] ?? ''),
            'to'       => $this->validDate($_GET['to'] ?? ''),
            'sort'     => in_array($_GET['sort'] ?? '', array_keys(self::SORTS), true) ? $_GET['sort'] : 'relevance',
            'page'     => max(1, (int)($_GET['page'] ?? 1)),
            'per_page' => min(50, max(1, (int)$this->manager->getSetting('results_per_page', 10))),
        ];
    }

    private function validType(string $type): string
    {
        return in_array($type, SearchManager::FILTER_TYPES, true) ? $type : '';
    }

    private function validDate(string $date): string
    {
        if ($date === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            return '';
        }
        return $date;
    }

    private function typeLabel(string $type): string
    {
        return SearchManager::TYPE_LABELS[$type] ?? $type;
    }

    /**
     * Build a search URL preserving current params except overrides.
     * Changing filters/sort resets pagination to page 1.
     */
    private function buildSearchUrl(array $overrides): string
    {
        $params = $_GET;
        unset($params['url']);

        foreach ($overrides as $key => $value) {
            if ($value === '') {
                unset($params[$key]);
            } else {
                $params[$key] = $value;
            }
        }

        if (!isset($overrides['page'])) {
            unset($params['page']);
        }

        if (empty($params)) {
            return '/search';
        }

        return '/search?' . http_build_query($params);
    }

    private function queryTerms(string $query): array
    {
        $query = mb_strtolower(trim($query));
        if ($query === '') {
            return [];
        }

        $terms = preg_split('/[\s,\.\/\\\+\-]+/u', $query);
        $terms = array_values(array_filter($terms, fn($t) => trim($t) !== ''));

        return array_slice($terms, 0, 6);
    }

    /**
     * Wrap query terms in <mark> for keyword highlighting.
     */
    private function highlight(string $text, array $terms): string
    {
        $escaped = esc($text);

        if (empty($terms)) {
            return $escaped;
        }

        $patterns = array_map(fn($t) => preg_quote($t, '/'), $terms);
        $regex    = '/(' . implode('|', $patterns) . ')/iu';

        return preg_replace($regex, '<mark class="search-highlight">$1</mark>', $escaped);
    }

    private function truncate(string $text, int $length = 220): string
    {
        $text = trim($text);
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return rtrim(mb_substr($text, 0, $length)) . '&hellip;';
    }

    private function formatDate(?string $date): string
    {
        if (!$date || $date === '0000-00-00 00:00:00' || $date === '0000-00-00') {
            return '';
        }

        $ts = strtotime($date);
        if ($ts === false) {
            return '';
        }

        return date('M j, Y', $ts);
    }
}
