<?php
/**
 * Swap Design - Legacy Loader Compatibility Wrapper
 *
 * Maintains backward compatibility for any code that still calls
 * resolvePage(). Delegates to DynamicRouter internally.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

/**
 * Resolve the current request to a page context (compatibility wrapper).
 *
 * @return array Page context in legacy format for renderLayout()
 */
function resolvePage(): array
{
    global $site;

    require_once __DIR__ . '/content/SlugManager.php';
    require_once __DIR__ . '/content/DynamicRouter.php';

    $router  = new DynamicRouter();
    $context = $router->resolve($_GET['url'] ?? trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/'));

    /* Convert new context format to legacy renderLayout format */
    if ($context['status'] === 301) {
        return [
            'template'   => 'redirect',
            'statusCode' => 301,
            'showCta'    => false,
            'crumbs'     => [['name' => 'Home', 'url' => '/']],
            'meta'       => ['title' => 'Redirecting...', 'heading' => '', 'description' => null, 'canonical' => null, 'ogImage' => null, 'ogType' => 'website'],
            'pageCss'    => null, 'pageJs' => null, 'data' => ['redirect' => $context['target_url']],
        ];
    }

    if ($context['type'] === 'page') {
        $page    = $context['page'];
        $isHome  = $context['is_home'] ?? false;

        return [
            'template'   => $isHome ? 'home' : 'page',
            'statusCode' => 200,
            'showCta'    => !$isHome,
            'crumbs'     => [
                ['name' => 'Home', 'url' => '/'],
                ...($isHome ? [] : [['name' => $page['title'], 'url' => '/' . $page['slug']]]),
            ],
            'meta'       => [
                'title'       => $context['meta']['title'] ?? $page['title'],
                'heading'     => $page['title'],
                'description' => $context['meta']['description'] ?? '',
                'canonical'   => null,
                'ogImage'     => null,
                'ogType'      => $isHome ? 'website' : 'article',
            ],
            'pageCss'    => null,
            'pageJs'     => null,
            'data'       => $context,
        ];
    }

    if ($context['type'] === 'archive' || $context['type'] === 'entry') {
        $label = $context['meta']['title'] ?? 'Content';
        return [
            'template'   => 'page',
            'statusCode' => 200,
            'showCta'    => true,
            'crumbs'     => [['name' => 'Home', 'url' => '/'], ['name' => $label, 'url' => '']],
            'meta'       => [
                'title'       => $label,
                'heading'     => $label,
                'description' => $context['meta']['description'] ?? '',
                'canonical'   => null,
                'ogImage'     => null,
                'ogType'      => 'website',
            ],
            'pageCss'    => null,
            'pageJs'     => null,
            'data'       => $context,
        ];
    }

    if ($context['type'] === 'empty') {
        return [
            'template'   => 'home',
            'statusCode' => 200,
            'showCta'    => false,
            'crumbs'     => [['name' => 'Home', 'url' => '/']],
            'meta'       => ['title' => 'Welcome', 'heading' => 'Welcome', 'description' => null, 'canonical' => null, 'ogImage' => null, 'ogType' => 'website'],
            'pageCss'    => null, 'pageJs' => null, 'data' => [],
        ];
    }

    /* 404 fallback */
    return [
        'template'   => '404',
        'statusCode' => 404,
        'showCta'    => false,
        'crumbs'     => [['name' => 'Home', 'url' => '/'], ['name' => 'Page Not Found', 'url' => '']],
        'meta'       => ['title' => 'Page Not Found', 'heading' => 'Page Not Found', 'description' => null, 'canonical' => null, 'ogImage' => null, 'ogType' => 'website'],
        'pageCss'    => null, 'pageJs' => null, 'data' => [],
    ];
}
