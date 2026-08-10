<?php
/**
 * Swap Design - Blog Renderer
 *
 * Renders blog listing, single post, category/tag archives with
 * Article JSON-LD schema, TOC generation, reading progress support,
 * share buttons, author box, and related posts.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class BlogRenderer
{
    private BlogManager $manager;
    private Database $db;

    public function __construct()
    {
        $this->manager = new BlogManager();
        $this->db      = Database::getInstance();
    }

    /* ================================================================
       Assets
       ================================================================ */

    public function getPageAssets(): array
    {
        return [
            'css' => ['/assets/css/blog.css'],
            'js'  => ['/assets/js/pages/blog.js'],
        ];
    }

    /* ================================================================
       Blog Listing
       ================================================================ */

    public function renderListing(int $page = 1): string
    {
        $perPage  = 12;
        $posts    = $this->manager->getPublishedPosts($page, $perPage);
        $total    = $this->manager->countPublished();
        $totalPages = max(1, ceil($total / $perPage));
        $categories = $this->manager->getAllCategories();

        if (empty($posts)) {
            return '<section class="blog-listing blog-listing--empty"><p>No posts published yet. Check back soon.</p></section>';
        }

        $cards = '';
        $sticky = array_filter($posts, fn($p) => $p['is_sticky']);

        foreach ($posts as $post) {
            $cards .= $this->renderPostCard($post);
        }

        $pagination = $this->renderPagination($page, $totalPages, '/blog');

        return <<<HTML
        <section class="blog-listing" aria-label="Blog posts">
            {$cards}
        </section>
        {$pagination}
        HTML;
    }

    /* ================================================================
       Single Post
       ================================================================ */

    public function renderSingle(array $post): string
    {
        $this->manager->incrementViews($post['id']);

        $categories = $this->manager->getPostCategories($post['id']);
        $tags       = $this->manager->getPostTags($post['id']);
        $related    = $this->manager->getRelatedPosts($post['id']);
        $prevPost   = $this->manager->getPreviousPost($post['id']);
        $nextPost   = $this->manager->getNextPost($post['id']);
        $author     = null;

        if ($post['author_id']) {
            $author = $this->db->fetch("SELECT id, username, display_name, avatar FROM users WHERE id = ?", [(int)$post['author_id']]);
        }

        $dateStr   = date('F j, Y', strtotime($post['published_at'] ?? $post['created_at']));
        $readTime  = (int)$post['reading_time'];
        $image     = $post['featured_image'] ?? '';

        /* TOC */
        $toc = $this->generateToc($post['content']);

        /* Content with heading IDs */
        $content = $this->addHeadingAnchors($post['content']);

        /* Categories */
        $catHtml = '';
        foreach ($categories as $cat) {
            $catHtml .= '<a href="/blog/category/' . esc($cat['slug']) . '" class="blog-post__cat">' . esc($cat['name']) . '</a>';
        }

        /* Tags */
        $tagHtml = '';
        foreach ($tags as $tag) {
            $tagHtml .= '<a href="/blog/tag/' . esc($tag['slug']) . '" class="blog-post__tag">' . esc($tag['name']) . '</a>';
        }

        /* Author */
        $authorHtml = '';
        if ($author) {
            $authorName = esc($author['display_name'] ?: $author['username']);
            $authorImg = $author['avatar'] ? esc($author['avatar']) : '';
            $avatarHtml = $authorImg ? '<img src="' . $authorImg . '" alt="' . $authorName . '" class="blog-author__avatar" width="64" height="64" loading="lazy">' : '<div class="blog-author__avatar blog-author__avatar--placeholder" aria-hidden="true">' . strtoupper(substr($authorName, 0, 1)) . '</div>';
            $authorHtml = <<<HTML
            <div class="blog-author">
                {$avatarHtml}
                <div class="blog-author__info">
                    <span class="blog-author__name">{$authorName}</span>
                    <span class="blog-author__bio">Author</span>
                </div>
            </div>
            HTML;
        }

        /* Featured Image */
        $imageHtml = '';
        if ($image) {
            $imageHtml = '<div class="blog-post__image"><img src="' . esc($image) . '" alt="' . esc($post['title']) . '" loading="lazy" class="blog-post__img"></div>';
        }

        /* Share */
        $postUrl  = BlogManager::getPostUrl($post);
        $shareUrl  = urlencode(baseUrl() . $postUrl);
        $shareTitle = urlencode($post['title']);
        $shareHtml = <<<HTML
        <div class="blog-share">
            <span class="blog-share__label">Share:</span>
            <a href="https://twitter.com/intent/tweet?url={$shareUrl}&text={$shareTitle}" target="_blank" rel="noopener nofollow" class="blog-share__btn blog-share__btn--twitter" aria-label="Share on Twitter">Twitter</a>
            <a href="https://www.linkedin.com/shareArticle?mini=true&url={$shareUrl}&title={$shareTitle}" target="_blank" rel="noopener nofollow" class="blog-share__btn blog-share__btn--linkedin" aria-label="Share on LinkedIn">LinkedIn</a>
            <a href="https://www.facebook.com/sharer/sharer.php?u={$shareUrl}" target="_blank" rel="noopener nofollow" class="blog-share__btn blog-share__btn--facebook" aria-label="Share on Facebook">Facebook</a>
        </div>
        HTML;

        /* Prev/Next */
        $prevNext = '';
        if ($prevPost || $nextPost) {
            $pn = '<div class="blog-post-nav">';
            if ($prevPost) {
                $pn .= '<a href="' . esc(BlogManager::getPostUrl($prevPost)) . '" class="blog-post-nav__link blog-post-nav__link--prev"><span class="blog-post-nav__dir">&larr; Previous</span><span class="blog-post-nav__title">' . esc($prevPost['title']) . '</span></a>';
            }
            if ($nextPost) {
                $pn .= '<a href="' . esc(BlogManager::getPostUrl($nextPost)) . '" class="blog-post-nav__link blog-post-nav__link--next"><span class="blog-post-nav__dir">Next &rarr;</span><span class="blog-post-nav__title">' . esc($nextPost['title']) . '</span></a>';
            }
            $pn .= '</div>';
            $prevNext = $pn;
        }

        /* Related */
        $relatedHtml = '';
        if ($related) {
            $cards = '';
            foreach ($related as $rp) {
                $cards .= $this->renderPostCard($rp);
            }
            $relatedHtml = <<<HTML
            <section class="blog-related" aria-label="Related posts">
                <h3 class="blog-related__heading">Related Posts</h3>
                <div class="blog-related__grid">{$cards}</div>
            </section>
            HTML;
        }

        return <<<HTML
        <article class="blog-post" itemscope itemtype="https://schema.org/Article">
            <meta itemprop="datePublished" content="{$post['published_at']}">
            <meta itemprop="dateModified" content="{$post['updated_at']}">
            <span itemprop="author" itemscope itemtype="https://schema.org/Person" hidden>
                <meta itemprop="name" content="{$authorName}">
            </span>
            <span itemprop="publisher" itemscope itemtype="https://schema.org/Organization" hidden>
                <meta itemprop="name" content="Swap Design">
            </span>

            <header class="blog-post__header">
                <div class="blog-post__meta">
                    <span class="blog-post__date">{$dateStr}</span>
                    <span class="blog-post__reading-time">{$readTime} min read</span>
                </div>
                <h1 class="blog-post__title" itemprop="headline">{$post['title']}</h1>
                <div class="blog-post__cats">{$catHtml}</div>
            </header>

            {$imageHtml}

            <div class="blog-post__layout">
                {$toc}
                <div class="blog-post__content" itemprop="articleBody">
                    {$content}
                </div>
            </div>

            <footer class="blog-post__footer">
                <div class="blog-post__tags-wrap">{$tagHtml}</div>
                {$shareHtml}
            </footer>

            {$authorHtml}
        </article>

        {$prevNext}
        {$relatedHtml}
        HTML;
    }

    /* ================================================================
       Category Archive
       ================================================================ */

    public function renderCategoryArchive(string $slug, int $page = 1): string
    {
        $category = $this->manager->getCategoryBySlug($slug);
        if (!$category) return '';

        $perPage = 12;
        $posts   = $this->manager->getPostsByCategory($slug, $page, $perPage);
        $total   = $this->manager->countPostsByCategory($slug);
        $totalPages = max(1, ceil($total / $perPage));

        $cards = '';
        foreach ($posts as $post) {
            $cards .= $this->renderPostCard($post);
        }
        if (!$cards) {
            $cards = '<p class="blog-listing--empty">No posts in this category yet.</p>';
        }

        $pagination = $this->renderPagination($page, $totalPages, "/blog/category/$slug");

        return <<<HTML
        <section class="blog-archive" aria-label="Posts in {$category['name']}">
            <h1 class="blog-archive__heading">Category: {$category['name']}</h1>
            {$cards}
        </section>
        {$pagination}
        HTML;
    }

    /* ================================================================
       Tag Archive
       ================================================================ */

    public function renderTagArchive(string $slug, int $page = 1): string
    {
        $tag = $this->manager->getTagBySlug($slug);
        if (!$tag) return '';

        $perPage = 12;
        $posts   = $this->manager->getPostsByTag($slug, $page, $perPage);
        $total   = $this->manager->countPostsByTag($slug);
        $totalPages = max(1, ceil($total / $perPage));

        $cards = '';
        foreach ($posts as $post) {
            $cards .= $this->renderPostCard($post);
        }
        if (!$cards) {
            $cards = '<p class="blog-listing--empty">No posts with this tag yet.</p>';
        }

        $pagination = $this->renderPagination($page, $totalPages, "/blog/tag/$slug");

        return <<<HTML
        <section class="blog-archive" aria-label="Posts tagged {$tag['name']}">
            <h1 class="blog-archive__heading">Tag: {$tag['name']}</h1>
            {$cards}
        </section>
        {$pagination}
        HTML;
    }

    /* ================================================================
       Schema
       ================================================================ */

    public function getSchema(?array $post = null): string
    {
        if (!$post) {
            return '';
        }

        $authorName = 'Swap Design';
        if ($post['author_id']) {
            $author = $this->db->fetch("SELECT display_name, username FROM users WHERE id = ?", [(int)$post['author_id']]);
            if ($author) {
                $authorName = $author['display_name'] ?: $author['username'];
            }
        }

        $image = $post['featured_image'] ?: ($post['og_image'] ?? '');
        $imageUrl = $image ? (strpos($image, 'http') === 0 ? $image : baseUrl() . '/' . ltrim($image, '/')) : '';

        $schema = [
            '@context'        => 'https://schema.org',
            '@type'           => 'Article',
            'headline'        => $post['seo_title'] ?: $post['title'],
            'description'     => $post['meta_description'] ?: $post['short_description'] ?: '',
            'datePublished'   => $post['published_at'] ?? $post['created_at'],
            'dateModified'    => $post['updated_at'],
            'author'          => ['@type' => 'Person', 'name' => $authorName],
            'publisher'       => ['@type' => 'Organization', 'name' => 'Swap Design'],
            'wordCount'       => str_word_count(strip_tags($post['content'] ?? '')),
        ];

        if ($imageUrl) {
            $schema['image'] = $imageUrl;
        }

        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }

    /* ================================================================
       Post Card
       ================================================================ */

    private function renderPostCard(array $post): string
    {
        $title   = esc($post['title']);
        $slug    = esc($post['slug']);
        $desc    = esc($post['short_description'] ?? '');
        $image   = $post['featured_image'] ?? '';
        $date    = date('M j, Y', strtotime($post['published_at'] ?? $post['created_at']));
        $read    = (int)$post['reading_time'];
        $cats    = $this->manager->getPostCategories($post['id']);

        $imageHtml = '';
        if ($image) {
            $imageHtml = '<div class="blog-card__image"><img src="' . esc($image) . '" alt="' . $title . '" loading="lazy" width="400" height="225"></div>';
        }

        $catHtml = '';
        foreach ($cats as $cat) {
            $catHtml .= '<a href="/blog/category/' . esc($cat['slug']) . '" class="blog-card__cat">' . esc($cat['name']) . '</a>';
        }

        $sticky = $post['is_sticky'] ? ' blog-card--sticky' : '';
        $featured = $post['is_featured'] ? ' blog-card--featured' : '';
        $descHtml = $desc ? '<p class="blog-card__desc">' . $desc . '</p>' : '';

        $cardUrl = esc(BlogManager::getPostUrl($post));

        return <<<HTML
        <article class="blog-card{$sticky}{$featured}">
            {$imageHtml}
            <div class="blog-card__body">
                <div class="blog-card__cats">{$catHtml}</div>
                <h2 class="blog-card__title"><a href="{$cardUrl}">{$title}</a></h2>
                {$descHtml}
                <div class="blog-card__meta">
                    <time datetime="{$post['published_at']}" class="blog-card__date">{$date}</time>
                    <span class="blog-card__read">{$read} min read</span>
                </div>
            </div>
        </article>
        HTML;
    }

    /* ================================================================
       Pagination
       ================================================================ */

    private function renderPagination(int $current, int $total, string $baseUrl): string
    {
        if ($total <= 1) return '';

        $html = '<nav class="blog-pagination" aria-label="Blog pagination">';
        if ($current > 1) {
            $html .= '<a href="' . esc($baseUrl) . '?page=' . ($current - 1) . '" class="blog-pagination__link">&larr; Prev</a>';
        }
        $html .= '<span class="blog-pagination__info">Page ' . $current . ' of ' . $total . '</span>';
        if ($current < $total) {
            $html .= '<a href="' . esc($baseUrl) . '?page=' . ($current + 1) . '" class="blog-pagination__link">Next &rarr;</a>';
        }
        $html .= '</nav>';
        return $html;
    }

    /* ================================================================
       TOC Generator
       ================================================================ */

    private function generateToc(string $content): string
    {
        $count = preg_match_all('/<h([2-3])\b[^>]*>(.*?)<\/h[2-3]>/i', $content, $matches);
        if ($count < 2) return '';

        $items = '';
        foreach ($matches[1] as $i => $level) {
            $text  = strip_tags($matches[2][$i]);
            $id    = 'heading-' . ($i + 1);
            $indent = $level === '3' ? ' blog-toc__item--child' : '';
            $items .= '<li class="blog-toc__item' . $indent . '"><a href="#' . $id . '" class="blog-toc__link">' . esc($text) . '</a></li>';
        }

        return <<<HTML
        <nav class="blog-toc" aria-label="Table of Contents">
            <strong class="blog-toc__heading">Table of Contents</strong>
            <ol class="blog-toc__list">{$items}</ol>
        </nav>
        HTML;
    }

    /* ================================================================
       Heading Anchor Injector
       ================================================================ */

    private function addHeadingAnchors(string $content): string
    {
        $counter = 0;
        return preg_replace_callback('/<h([2-3])\b([^>]*)>(.*?)<\/h[2-3]>/i', function ($m) use (&$counter) {
            $counter++;
            $id    = 'heading-' . $counter;
            $level = $m[1];
            $attrs = $m[2];
            $text  = $m[3];
            return "<h{$level}{$attrs} id=\"{$id}\">{$text} <a href=\"#{$id}\" class=\"blog-heading-link\" aria-hidden=\"true\">#</a></h{$level}>";
        }, $content);
    }
}
