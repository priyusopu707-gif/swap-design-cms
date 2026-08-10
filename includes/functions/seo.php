<?php
/**
 * Swap Design - SEO Helper Functions
 *
 * Generates meta tags, structured data, and other SEO elements.
 * Uses $site config for default values.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

global $site;

/**
 * Set SEO meta variables for the current page
 *
 * Call at the top of each page before including header.php.
 *
 * @param string      $title       Page title (appended with site name via template)
 * @param string|null $description Meta description (null = use site default)
 * @param string|null $canonical   Canonical URL (null = use current URL)
 * @param string|null $ogImage     Open Graph image URL (null = use site default)
 * @param string      $ogType      Open Graph type (article, website, etc.)
 */
function setPageSEO(
    string $title,
    ?string $description = null,
    ?string $canonical = null,
    ?string $ogImage = null,
    string $ogType = 'website'
): void {
    global $pageTitle, $pageDescription, $pageCanonical, $pageOgImage, $pageOgType;
    global $site;

    $template = $site->seo->titleTemplate ?? '%s | Swap Design';

    $pageTitle       = sprintf($template, $title);
    $pageDescription = $description ?? $site->seo->defaultDescription;
    $pageCanonical   = $canonical  ?? currentUrl();
    $pageOgImage     = $ogImage    ?? $site->seo->defaultOgImage;
    $pageOgType      = $ogType;
}

/**
 * Generate JSON-LD structured data for LocalBusiness
 *
 * @param array $overrides Values to override defaults
 * @return string JSON-LD script tag
 */
function getLocalBusinessSchema(array $overrides = []): string
{
    global $site;

    $data = array_merge([
        '@context'      => 'https://schema.org',
        '@type'         => 'ProfessionalService',
        'name'          => $site->brand->name,
        'description'   => $site->brand->description,
        'url'           => $site->urls->base,
        'email'         => $site->brand->email ?: null,
        'telephone'     => $site->brand->phone ?: null,
        'foundingDate'  => (string) $site->brand->foundedYear,
        'sameAs'        => array_values(array_filter(array_column((array) $site->social, 'url'))),
    ], $overrides);

    // Remove null values
    $data = array_filter($data, fn($v) => $v !== null);

    return '<script type="application/ld+json">'
        . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        . '</script>';
}

/**
 * Generate JSON-LD structured data for BreadcrumbList
 *
 * @param array $crumbs Array of ['name' => '', 'url' => ''] items
 * @return string JSON-LD script tag
 */
function getBreadcrumbSchema(array $crumbs): string
{
    $items = [];
    $position = 1;

    foreach ($crumbs as $crumb) {
        $name = $crumb['name'] ?? $crumb['label'] ?? '';
        if ($name === '') {
            continue;
        }
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => $name,
            'item'     => $crumb['url'] ?? null,
        ];
    }

    if (empty($items)) {
        return '';
    }

    $data = [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $items,
    ];

    return '<script type="application/ld+json">'
        . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        . '</script>';
}

/**
 * Get the default page SEO title from config
 */
function getDefaultTitle(): string
{
    global $site;
    return $site->seo->defaultTitle;
}

/**
 * Get the default meta description from config
 */
function getDefaultDescription(): string
{
    global $site;
    return $site->seo->defaultDescription;
}

/**
 * Generate JSON-LD structured data for the Organization.
 *
 * @param array $overrides Values to override defaults
 * @return string JSON-LD script tag
 */
function getOrganizationSchema(array $overrides = []): string
{
    global $site;

    $base = rtrim($site->urls->base ?? '', '/');
    $logo = $site->favicon->favicon32 ?? null;
    if ($logo && strpos($logo, 'http') !== 0) {
        $logo = $base . '/' . ltrim($logo, '/');
    }

    $data = array_merge([
        '@context' => 'https://schema.org',
        '@type'    => 'Organization',
        '@id'      => $base . '/#organization',
        'name'     => $site->brand->name,
        'url'      => $site->urls->base,
        'logo'     => $logo,
        'description' => $site->brand->description,
        'sameAs'   => array_values(array_filter(array_column((array) $site->social, 'url'))),
    ], $overrides);

    $data = array_filter($data, fn($v) => $v !== null && $v !== '');

    return '<script type="application/ld+json">'
        . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        . '</script>';
}

/**
 * Generate JSON-LD structured data for the WebSite + SearchAction.
 *
 * @param array $overrides Values to override defaults
 * @return string JSON-LD script tag
 */
function getWebsiteSchema(array $overrides = []): string
{
    global $site;

    $data = array_merge([
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        '@id'      => rtrim($site->urls->base ?? '', '/') . '/#website',
        'name'     => $site->brand->name,
        'url'      => $site->urls->base,
        'description' => $site->brand->description,
        'inLanguage'  => $site->brand->language ?? 'en',
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => rtrim($site->urls->base ?? '', '/') . '/search?q={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ], $overrides);

    return '<script type="application/ld+json">'
        . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        . '</script>';
}

/**
 * Generate JSON-LD structured data for a generic WebPage.
 *
 * @param string $title     Page title
 * @param string $url       Absolute canonical URL
 * @param array  $overrides Additional fields (description, image, etc.)
 * @return string JSON-LD script tag
 */
function getWebPageSchema(string $title, string $url, array $overrides = []): string
{
    global $site;

    $data = array_merge([
        '@context'      => 'https://schema.org',
        '@type'         => 'WebPage',
        'name'          => $title,
        'url'           => $url,
        'inLanguage'    => $site->brand->language ?? 'en',
        'isPartOf'      => [
            '@id' => rtrim($site->urls->base ?? '', '/') . '/#website',
        ],
        'about'         => [
            '@id' => rtrim($site->urls->base ?? '', '/') . '/#organization',
        ],
    ], $overrides);

    return '<script type="application/ld+json">'
        . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        . '</script>';
}

/**
 * Generate JSON-LD structured data for a CollectionPage (archive).
 *
 * @param string $title     Page title
 * @param string $url       Absolute canonical URL
 * @param array  $overrides Additional fields (description, etc.)
 * @return string JSON-LD script tag
 */
function getCollectionPageSchema(string $title, string $url, array $overrides = []): string
{
    return getWebPageSchema($title, $url, array_merge(['@type' => 'CollectionPage'], $overrides));
}

/**
 * Generate JSON-LD structured data for an FAQPage.
 *
 * @param array $faqs Array of ['question' => string, 'answer' => string]
 * @return string JSON-LD script tag
 */
function getFaqPageSchema(array $faqs): string
{
    $items = [];

    foreach ($faqs as $faq) {
        $q = trim((string)($faq['question'] ?? ''));
        $a = trim((string)($faq['answer'] ?? ''));
        if ($q === '' || $a === '') {
            continue;
        }
        $items[] = [
            '@type'          => 'Question',
            'name'           => $q,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => $a,
            ],
        ];
    }

    if (empty($items)) {
        return '';
    }

    $data = [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $items,
    ];

    return '<script type="application/ld+json">'
        . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        . '</script>';
}

/**
 * Determine a semantic @type from an og:type value.
 */
function schemaTypeForOgType(string $ogType): string
{
    return match ($ogType) {
        'article'  => 'Article',
        'profile'  => 'ProfilePage',
        'book'     => 'Book',
        'video'    => 'VideoObject',
        'music'    => 'MusicPlaylist',
        default    => 'WebPage',
    };
}

/**
 * Generate the base JSON-LD graph (WebSite + Organization + WebPage)
 * shared by every page. Types already present in the existing schema
 * output are skipped to avoid duplicate structured data.
 *
 * @param string $existing Existing pageSchema output (may already include types)
 * @param string $title    Page title
 * @param string $url      Absolute canonical URL
 * @return string JSON-LD script tag(s), or '' when nothing is added
 */
function getBaseSchemas(string $existing, string $title, string $url): string
{
    global $site;

    $out = '';

    if (strpos($existing, '"WebSite"') === false) {
        $out .= getWebsiteSchema();
    }

    /* Organization is a parent of ProfessionalService/LocalBusiness, so
       skip it when a more specific business type is already present. */
    $hasOrgLike = strpos($existing, '"Organization"') !== false
        || strpos($existing, '"ProfessionalService"') !== false
        || strpos($existing, '"LocalBusiness"') !== false;
    if (!$hasOrgLike) {
        $out .= getOrganizationSchema();
    }

    /* Only add a generic WebPage when the page does not already carry a
       more specific page-level type (Article, Service, CreativeWork,
       ContactPage, ProfilePage, CollectionPage, WebSite, FAQPage, etc.). */
    $specificTypes = [
        'Article', 'Service', 'CreativeWork', 'Project', 'ContactPage',
        'ProfilePage', 'CollectionPage', 'WebSite', 'FAQPage', 'AboutPage',
    ];
    $hasSpecific = false;
    foreach ($specificTypes as $type) {
        if (strpos($existing, '"' . $type . '"') !== false) {
            $hasSpecific = true;
            break;
        }
    }

    if (!$hasSpecific) {
        $ogType = $site->seo->defaultOgType ?? 'website';
        $webType = schemaTypeForOgType((string)$ogType);
        $out .= getWebPageSchema($title, $url, ['@type' => $webType]);
    }

    return $out;
}
