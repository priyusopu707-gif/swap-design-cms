<?php
/**
 * Swap Design - Search Manager
 *
 * Search engine over the search_index table: relevance-ranked fulltext
 * search with boolean prefix wildcards, a LIKE fallback for partial /
 * short-term matching, filters (type/category/tag/date/featured),
 * sorting, pagination, live suggestions, fuzzy "did you mean"
 * corrections, related results, and search analytics/logging.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class SearchManager
{
    private Database $db;
    private SettingsManager $settings;
    private ?array $vocabularyCache = null;

    /** Display labels for index content types. */
    public const TYPE_LABELS = [
        'page'          => 'Pages',
        'service'       => 'Services',
        'portfolio'     => 'Portfolio',
        'blog_post'     => 'Blog Posts',
        'content_entry' => 'Content Entries',
        'global_block'  => 'Blocks',
        'service_faq'   => 'FAQs',
        'portfolio_faq' => 'FAQs',
    ];

    /** Filterable type slugs exposed to the UI (single 'faq' covers both). */
    public const FILTER_TYPES = [
        'page',
        'service',
        'portfolio',
        'blog_post',
        'content_entry',
        'global_block',
        'faq',
    ];

    /** Concrete index content_type values. */
    public const TYPES = [
        'page',
        'service',
        'portfolio',
        'blog_post',
        'content_entry',
        'global_block',
        'service_faq',
        'portfolio_faq',
    ];

    public function __construct()
    {
        $this->db       = Database::getInstance();
        $this->settings = new SettingsManager();
    }

    /* ================================================================
       Settings
       ================================================================ */

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings->get('search_' . $key, $default);
    }

    public function setSetting(string $key, mixed $value): void
    {
        $this->settings->set('search_' . $key, $value);
    }

    public function isLoggingEnabled(): bool
    {
        return (bool)$this->getSetting('logging_enabled', 1);
    }

    public function minQueryLength(): int
    {
        return max(1, (int)$this->getSetting('min_query_length', 2));
    }

    public function isSuggestionsEnabled(): bool
    {
        return (bool)$this->getSetting('suggestions_enabled', 1);
    }

    public function isRateLimited(): bool
    {
        $attempts = (int)$this->getSetting('rate_limit_attempts', 30);
        $window   = (int)$this->getSetting('rate_limit_window', 1);
        return rateLimitExceeded('search:' . getClientIp(), $attempts, $window);
    }

    /* ================================================================
       Search
       ================================================================ */

    /**
     * Run a full search with filters, sorting, and pagination.
     *
     * @param string $query   Raw user query
     * @param array  $filters type, category, tag, featured, from, to, sort
     * @param int    $page    1-based page
     * @param int    $perPage Results per page
     * @return array{
     *   query: string, total: int, items: array, page: int, per_page: int,
     *   total_pages: int, did_you_mean: ?string, used_fallback: bool
     * }
     */
    public function search(string $query, array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $query   = trim($query);
        $page    = max(1, $page);
        $perPage = min(50, max(1, $perPage));
        $offset  = ($page - 1) * $perPage;

        $booleanQuery = $this->buildBooleanQuery($query);
        $usedFallback = false;
        $items        = [];
        $total        = 0;

        if ($query !== '' && $booleanQuery !== '') {
            $rows   = $this->fulltextSearch($query, $booleanQuery, $filters, $perPage, $offset);
            $total  = $rows['total'];
            $items  = $rows['rows'];

            if ($total === 0) {
                $fallback     = $this->likeSearch($query, $filters, $perPage, $offset);
                $items        = $fallback['rows'];
                $total        = $fallback['total'];
                $usedFallback = true;
            }
        } elseif ($query !== '') {
            $fallback     = $this->likeSearch($query, $filters, $perPage, $offset);
            $items        = $fallback['rows'];
            $total        = $fallback['total'];
            $usedFallback = true;
        }

        $totalPages = (int)ceil($total / $perPage);

        $didYouMean = null;
        if ($total === 0 && mb_strlen($query) >= 3) {
            $didYouMean = $this->suggestCorrections($query);
        }

        return [
            'query'        => $query,
            'total'        => (int)$total,
            'items'        => $items,
            'page'         => $page,
            'per_page'     => $perPage,
            'total_pages'  => $totalPages,
            'did_you_mean' => $didYouMean,
            'used_fallback'=> $usedFallback,
        ];
    }

    /**
     * Relevance-ranked fulltext search (boolean mode with prefix wildcards).
     */
    private function fulltextSearch(string $query, string $booleanQuery, array $filters, int $limit, int $offset): array
    {
        [$where, $whereParams] = $this->buildWhereClause($filters);

        $searchWhere = "MATCH(title, excerpt, content, keywords, tags, category) AGAINST (? IN BOOLEAN MODE)";
        $selectFrag  = "MATCH(title, excerpt, content, keywords, tags, category) AGAINST (? IN BOOLEAN MODE) AS relevance";

        $whereSql = $where !== '' ? "{$searchWhere} AND {$where}" : $searchWhere;

        $countSql = "SELECT COUNT(*) FROM search_index WHERE {$whereSql}";
        $countParams = array_merge([$booleanQuery], $whereParams);
        $total    = (int)$this->db->fetchColumn($countSql, $countParams);

        $orderBy = $this->buildOrderBy($filters, true);

        $sql = "SELECT id, content_type, content_id, title, excerpt, content, keywords, url, image,
                       category, tags, is_featured, published_at, {$selectFrag}
                FROM search_index
                WHERE {$whereSql}
                {$orderBy}
                LIMIT ? OFFSET ?";

        $params = array_merge([$booleanQuery, $booleanQuery], $whereParams, [$limit, $offset]);
        $rows   = $this->db->fetchAll($sql, $params);

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * LIKE-based fallback for partial / short-term matching.
     * Orders by recency; items are re-scored in PHP for display.
     */
    private function likeSearch(string $query, array $filters, int $limit, int $offset): array
    {
        [$where, $whereParams] = $this->buildWhereClause($filters);

        $like = '%' . $query . '%';
        $likeParams = [$like, $like, $like, $like, $like, $like];

        $whereSql = $where !== '' ? "({$where}) AND (title LIKE ? OR excerpt LIKE ? OR content LIKE ? OR keywords LIKE ? OR category LIKE ? OR tags LIKE ?)"
                                  : "(title LIKE ? OR excerpt LIKE ? OR content LIKE ? OR keywords LIKE ? OR category LIKE ? OR tags LIKE ?)";

        $countSql = "SELECT COUNT(*) FROM search_index WHERE {$whereSql}";
        $total    = (int)$this->db->fetchColumn($countSql, array_merge($whereParams, $likeParams));

        $orderBy = $this->buildOrderBy($filters, false);

        $sql = "SELECT id, content_type, content_id, title, excerpt, content, keywords, url, image,
                       category, tags, is_featured, published_at, 0 AS relevance
                FROM search_index
                WHERE {$whereSql}
                {$orderBy}
                LIMIT ? OFFSET ?";

        $params = array_merge($whereParams, $likeParams, [$limit, $offset]);
        $rows   = $this->db->fetchAll($sql, $params);

        /* PHP relevance re-score so the most relevant match ranks first. */
        $terms = $this->tokenize($query);
        foreach ($rows as &$row) {
            $row['relevance'] = $this->scoreRow($row, $terms);
        }
        unset($row);
        usort($rows, fn($a, $b) => $b['relevance'] <=> $a['relevance']);

        return ['rows' => $rows, 'total' => $total];
    }

    private function scoreRow(array $row, array $terms): int
    {
        $title  = mb_strtolower($row['title'] ?? '');
        $excerpt = mb_strtolower($row['excerpt'] ?? '');
        $content = mb_strtolower($row['content'] ?? '');
        $score   = 0;

        foreach ($terms as $term) {
            if ($title !== '' && mb_strpos($title, $term) !== false)      $score += 5;
            if ($excerpt !== '' && mb_strpos($excerpt, $term) !== false)  $score += 3;
            if ($content !== '' && mb_strpos($content, $term) !== false)  $score += 1;
        }

        return $score;
    }

    /* ================================================================
       Suggestions
       ================================================================ */

    /**
     * Lightweight title matches for the live suggestion dropdown.
     */
    public function getSuggestions(string $query, int $limit = 8): array
    {
        $query = trim($query);
        if ($query === '') {
            return $this->getPopularSearches(min($limit, 6));
        }

        if (!$this->isSuggestionsEnabled()) {
            return [];
        }

        $like = '%' . $query . '%';
        $rows = $this->db->fetchAll(
            "SELECT content_type, content_id, title, url, image, category, published_at
             FROM search_index
             WHERE title LIKE ? OR keywords LIKE ? OR category LIKE ? OR tags LIKE ?
             ORDER BY is_featured DESC, published_at DESC
             LIMIT ?",
            [$like, $like, $like, $like, $limit]
        );

        return $this->decorate($rows);
    }

    /**
     * Fuzzy "did you mean" correction via Levenshtein distance against
     * the vocabulary extracted from the index.
     */
    public function suggestCorrections(string $query, ?int $threshold = null): ?string
    {
        if ($threshold === null) {
            $threshold = max(1, (int)$this->getSetting('fuzzy_threshold', 2));
        }

        $terms = $this->tokenize($query);
        if (empty($terms)) {
            return null;
        }

        $vocabulary = $this->getVocabulary();
        if (empty($vocabulary)) {
            return null;
        }

        $corrected = [];
        $changed   = false;

        foreach ($terms as $term) {
            if (mb_strlen($term) < 3) {
                $corrected[] = $term;
                continue;
            }

            $best     = $term;
            $bestDist = $threshold + 1;

            foreach ($vocabulary as $word) {
                $dist = levenshtein($term, $word);
                if ($dist < $bestDist) {
                    $bestDist = $dist;
                    $best     = $word;
                    if ($dist === 0) {
                        break;
                    }
                }
            }

            if ($bestDist <= $threshold && $best !== $term) {
                $corrected[] = $best;
                $changed     = true;
            } else {
                $corrected[] = $term;
            }
        }

        $suggestion = implode(' ', $corrected);

        return ($changed && strcasecmp($suggestion, $query) !== 0) ? $suggestion : null;
    }

    /**
     * Related content: items sharing a category with the current results.
     */
    public function getRelatedForQuery(array $items, int $limit = 4): array
    {
        $categories = [];
        $seen       = [];

        foreach ($items as $item) {
            $seen[$item['content_type'] . ':' . $item['content_id']] = true;
            if (!empty($item['category'])) {
                foreach (array_map('trim', explode(',', $item['category'])) as $c) {
                    if ($c !== '') {
                        $categories[$c] = true;
                    }
                }
            }
        }

        if (empty($categories)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($categories), '?'));
        $params       = array_keys($categories);

        $rows = $this->db->fetchAll(
            "SELECT id, content_type, content_id, title, excerpt, url, image, category, published_at
             FROM search_index
             WHERE category IN ({$placeholders})
             ORDER BY is_featured DESC, published_at DESC
             LIMIT ?",
            array_merge($params, [$limit * 5])
        );

        $result = [];
        foreach ($rows as $row) {
            $key = $row['content_type'] . ':' . $row['content_id'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[]   = $this->decorateOne($row);
            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }

    /**
     * Latest indexed items (used for no-result suggestions / browsing).
     */
    public function getLatestItems(int $limit = 6): array
    {
        $rows = $this->db->fetchAll(
            "SELECT id, content_type, content_id, title, excerpt, url, image, category, published_at
             FROM search_index
             ORDER BY published_at DESC
             LIMIT ?",
            [$limit]
        );

        return $this->decorate($rows);
    }

    /* ================================================================
       Facets
       ================================================================ */

    /**
     * Build filter facet data (content types + categories + tags) from
     * the full index for the results page sidebar.
     */
    public function getFacets(): array
    {
        $types = $this->db->fetchAll(
            "SELECT content_type, COUNT(*) AS count FROM search_index GROUP BY content_type ORDER BY count DESC"
        );

        $typeCounts = [];
        foreach ($types as $t) {
            $typeCounts[$t['content_type']] = (int)$t['count'];
        }

        $typeFacets = [];
        foreach (self::FILTER_TYPES as $type) {
            $key      = $type === 'faq' ? ['service_faq', 'portfolio_faq'] : [$type];
            $count    = 0;
            $faqTotal = 0;
            foreach ($key as $k) {
                $count += $typeCounts[$k] ?? 0;
            }
            $typeFacets[] = ['value' => $type, 'label' => self::TYPE_LABELS[$type] ?? $type, 'count' => $count];
        }

        $categories = $this->db->fetchAll(
            "SELECT category, COUNT(*) AS count FROM search_index
             WHERE category <> '' GROUP BY category ORDER BY count DESC, category ASC LIMIT 20"
        );

        $tags = [];
        $tagRows = $this->db->fetchAll(
            "SELECT tags FROM search_index WHERE tags <> '' LIMIT 500"
        );
        foreach ($tagRows as $row) {
            foreach (array_map('trim', explode(',', $row['tags'])) as $tag) {
                if ($tag === '') {
                    continue;
                }
                $tags[$tag] = ($tags[$tag] ?? 0) + 1;
            }
        }
        arsort($tags);
        $tagFacets = array_slice($tags, 0, 20, true);

        return [
            'types'      => $typeFacets,
            'categories' => $categories,
            'tags'       => $tagFacets,
        ];
    }

    /* ================================================================
       Analytics / logging
       ================================================================ */

    /**
     * Record a search execution. Returns the log id or null when logging
     * is disabled or the query is too short.
     */
    public function logSearch(string $query, int $resultCount): ?int
    {
        if (!$this->isLoggingEnabled()) {
            return null;
        }

        $query = trim($query);
        if (mb_strlen($query) < $this->minQueryLength()) {
            return null;
        }

        return (int)$this->db->insert('search_logs', [
            'query'            => substr($query, 0, 255),
            'normalized_query' => substr($this->normalizeQuery($query), 0, 255),
            'result_count'     => $resultCount,
            'is_zero_result'   => $resultCount > 0 ? 0 : 1,
            'ip_address'       => getClientIp(),
            'user_agent'       => substr(getUserAgent(), 0, 500),
        ]);
    }

    /**
     * Record a result click (most-viewed results analytics).
     */
    public function logClick(?int $searchLogId, string $query, array $result, int $position): void
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        $this->db->insert('search_result_clicks', [
            'search_log_id' => $searchLogId,
            'query'         => substr(trim($query), 0, 255),
            'content_type'  => $result['content_type'] ?? '',
            'content_id'    => (int)($result['content_id'] ?? 0),
            'content_title' => substr($result['title'] ?? '', 0, 255),
            'url'           => substr($result['url'] ?? '', 0, 500),
            'position'      => $position,
        ]);
    }

    public function getStats(): array
    {
        return [
            'total_searches'       => (int)$this->db->fetchColumn('SELECT COUNT(*) FROM search_logs', [], 0),
            'unique_queries'       => (int)$this->db->fetchColumn('SELECT COUNT(DISTINCT normalized_query) FROM search_logs', [], 0),
            'zero_result_searches' => (int)$this->db->fetchColumn('SELECT COUNT(*) FROM search_logs WHERE is_zero_result = 1', [], 0),
            'total_clicks'         => (int)$this->db->fetchColumn('SELECT COUNT(*) FROM search_result_clicks', [], 0),
            'indexed_items'        => (int)$this->db->fetchColumn('SELECT COUNT(*) FROM search_index', [], 0),
            'logging_enabled'      => $this->isLoggingEnabled(),
        ];
    }

    public function getPopularSearches(int $limit = 10): array
    {
        $limit = min(50, max(1, $limit));

        return $this->db->fetchAll(
            "SELECT normalized_query AS query, COUNT(*) AS count, SUM(result_count) AS total_results
             FROM search_logs
             WHERE normalized_query <> ''
             GROUP BY normalized_query
             ORDER BY count DESC, MAX(created_at) DESC
             LIMIT ?",
            [$limit]
        );
    }

    public function getZeroResultKeywords(int $limit = 20): array
    {
        $limit = min(50, max(1, $limit));

        return $this->db->fetchAll(
            "SELECT normalized_query AS query, COUNT(*) AS count
             FROM search_logs
             WHERE is_zero_result = 1 AND normalized_query <> ''
             GROUP BY normalized_query
             ORDER BY count DESC, MAX(created_at) DESC
             LIMIT ?",
            [$limit]
        );
    }

    public function getMostViewedResults(int $limit = 10): array
    {
        $limit = min(50, max(1, $limit));

        return $this->db->fetchAll(
            "SELECT content_type, content_id, content_title AS title, url, COUNT(*) AS clicks
             FROM search_result_clicks
             GROUP BY content_type, content_id, content_title, url
             ORDER BY clicks DESC, MAX(created_at) DESC
             LIMIT ?",
            [$limit]
        );
    }

    public function getRecentLogs(int $limit = 20): array
    {
        $limit = min(100, max(1, $limit));

        return $this->db->fetchAll(
            "SELECT query, result_count, is_zero_result, ip_address, created_at
             FROM search_logs
             ORDER BY created_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    public function clearLogs(): int
    {
        $this->db->query('DELETE FROM search_result_clicks');
        return $this->db->query('DELETE FROM search_logs')->rowCount();
    }

    /* ================================================================
       Query helpers
       ================================================================ */

    /**
     * Build a FULLTEXT boolean-mode query: each term gets a prefix
     * wildcard for partial-keyword matching; FT operator characters are
     * stripped.
     */
    private function buildBooleanQuery(string $query): string
    {
        $parts = [];

        foreach ($this->tokenize($query) as $term) {
            $term = preg_replace('/[^\p{L}\p{N}_]+/u', '', $term);
            $term = trim($term);
            if ($term === '' || mb_strlen($term) < $this->minQueryLength()) {
                continue;
            }
            $parts[] = $term . '*';
        }

        return implode(' ', $parts);
    }

    /**
     * Tokenize a query into lowercase words.
     */
    private function tokenize(string $query): array
    {
        $query = mb_strtolower(trim($query));
        $query = str_replace(['"', "'"], ' ', $query);

        $terms = preg_split('/[\s,\.\/\\\+\-]+/u', $query);
        $terms = array_values(array_filter($terms, fn($t) => trim($t) !== ''));

        return array_map('trim', $terms);
    }

    private function normalizeQuery(string $query): string
    {
        $query = mb_strtolower(trim($query));
        $query = preg_replace('/\s+/u', ' ', $query);
        return $query;
    }

    /**
     * Build the SQL WHERE fragment + params for filters.
     *
     * @return array{0:string,1:array}
     */
    private function buildWhereClause(array $filters): array
    {
        $clauses = [];
        $params  = [];

        if (!empty($filters['type'])) {
            $types = $this->resolveTypes($filters['type']);
            $placeholders = implode(',', array_fill(0, count($types), '?'));
            $clauses[] = "content_type IN ({$placeholders})";
            foreach ($types as $t) {
                $params[] = $t;
            }
        }

        if (!empty($filters['category'])) {
            $clauses[] = 'category = ?';
            $params[]  = $filters['category'];
        }

        if (!empty($filters['tag'])) {
            $clauses[] = 'FIND_IN_SET(?, tags)';
            $params[]  = $filters['tag'];
        }

        if (!empty($filters['featured'])) {
            $clauses[] = 'is_featured = 1';
        }

        if (!empty($filters['from'])) {
            $clauses[] = 'published_at >= ?';
            $params[]  = $filters['from'] . ' 00:00:00';
        }

        if (!empty($filters['to'])) {
            $clauses[] = 'published_at <= ?';
            $params[]  = $filters['to'] . ' 23:59:59';
        }

        return [implode(' AND ', $clauses), $params];
    }

    private function buildOrderBy(array $filters, bool $fulltext): string
    {
        $sort = $filters['sort'] ?? 'relevance';

        switch ($sort) {
            case 'newest':
                return 'ORDER BY published_at DESC';
            case 'oldest':
                return 'ORDER BY published_at ASC';
            case 'alphabetical':
                return 'ORDER BY title ASC';
            case 'relevance':
            default:
                return $fulltext ? 'ORDER BY relevance DESC' : 'ORDER BY published_at DESC';
        }
    }

    /**
     * Resolve a filter type value into concrete index content_type values.
     */
    private function resolveTypes(string $type): array
    {
        $type = trim($type);

        if ($type === 'faq') {
            return ['service_faq', 'portfolio_faq'];
        }

        return in_array($type, self::TYPES, true) ? [$type] : [];
    }

    /**
     * Vocabulary of index terms for fuzzy matching.
     */
    private function getVocabulary(): array
    {
        if ($this->vocabularyCache !== null) {
            return $this->vocabularyCache;
        }

        $rows = $this->db->fetchAll(
            "SELECT title, keywords, category, tags FROM search_index LIMIT 2000"
        );

        $words = [];
        foreach ($rows as $row) {
            foreach (['title', 'keywords', 'category', 'tags'] as $col) {
                if (empty($row[$col])) {
                    continue;
                }
                foreach ($this->tokenize($row[$col]) as $word) {
                    if (mb_strlen($word) >= 3) {
                        $words[$word] = true;
                    }
                }
            }
        }

        $this->vocabularyCache = array_keys($words);
        return $this->vocabularyCache;
    }

    /* ================================================================
       Decorators (add display labels)
       ================================================================ */

    private function decorate(array $rows): array
    {
        return array_map([$this, 'decorateOne'], $rows);
    }

    private function decorateOne(array $row): array
    {
        $row['type_label'] = self::TYPE_LABELS[$row['content_type'] ?? ''] ?? ($row['content_type'] ?? '');
        return $row;
    }
}
