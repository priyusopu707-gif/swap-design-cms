<?php
/**
 * Swap Design - Global Component Loader
 *
 * Dynamically loads and renders reusable frontend components
 * based on page context. Supports components registered in the
 * global_components table or defined procedurally.
 *
 * Usage:
 *   $loader = new ComponentLoader();
 *   $loader->render('cta', ['title' => 'Get Started']);
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class ComponentLoader
{
    private Database $db;
    private array $components = [];
    private string $componentDir;

    /**
     * Built-in procedural components (always available).
     */
    private const BUILTIN = [
        'header'       => 'components/header.php',
        'navigation'   => 'components/navigation.php',
        'footer'       => 'components/footer.php',
        'cta'          => 'components/cta.php',
        'breadcrumb'   => null,
        'contact-form' => 'components/contact-form.php',
    ];

    public function __construct(?string $componentDir = null)
    {
        $this->db           = Database::getInstance();
        $this->componentDir = $componentDir ?? dirname(__DIR__);
    }

    /**
     * Register a component for later rendering.
     */
    public function register(string $name, string $templatePath, array $defaultConfig = []): void
    {
        $this->components[$name] = [
            'template'       => $templatePath,
            'default_config' => $defaultConfig,
        ];
    }

    /**
     * Render a component by name.
     *
     * @param string $name   Component name or slug
     * @param array  $config Config/parameters to pass
     */
    public function render(string $name, array $config = []): void
    {
        $component = $this->resolve($name);

        if (!$component) {
            echo "<!-- Component '{$name}' not found -->\n";
            return;
        }

        /* Extract parameters as variables for the template */
        $params = array_merge($component['default_config'] ?? [], $config);
        extract($params, EXTR_SKIP);

        /* Include the template */
        $templatePath = $component['template'];
        if (file_exists($templatePath)) {
            include $templatePath;
        } else {
            echo "<!-- Component template '{$templatePath}' not found -->\n";
        }
    }

    /**
     * Render a block from the global_blocks table.
     */
    public function renderBlock(array $block, array $overrides = []): void
    {
        $content = $block['content'] ?? [];
        if (is_string($content)) {
            $content = json_decode($content, true) ?: [];
        }

        $config = array_merge($content, $overrides, [
            '_block_id'   => $block['id'],
            '_block_name' => $block['name'],
            '_block_type' => $block['block_type'],
        ]);

        /* Device visibility check */
        $visibility = $block['device_visibility'] ?? 'all';
        if ($visibility !== 'all') {
            echo "<!-- Block '{$block['name']}' hidden: visibility={$visibility} -->\n";
            /* In a real request, this would use server-side detection or JS */
        }

        /* Full-width wrapper */
        if (!empty($block['full_width'])) {
            echo '<div class="section section--full-width"';
            if (!empty($block['background_color'])) {
                echo ' style="background-color:' . esc($block['background_color']) . '"';
            }
            echo '>';
        }

        $type = $block['block_type'] ?? 'custom_html';

        switch ($type) {
            case 'cta':
                $this->renderCtaBlock($config);
                break;
            case 'faq':
                $this->renderFaqBlock($config);
                break;
            case 'stats':
                $this->renderStatsBlock($config);
                break;
            case 'testimonials':
                $this->renderTestimonialsBlock($config);
                break;
            case 'banner':
                $this->renderBannerBlock($config);
                break;
            case 'custom_html':
                echo $config['html'] ?? $config['text'] ?? '';
                break;
            default:
                $this->render($type, $config);
        }

        if (!empty($block['full_width'])) {
            echo '</div>';
        }
    }

    /**
     * Load active blocks for a page and render them.
     */
    public function renderPageBlocks(string $pageSlug): void
    {
        $blockEngine = new BlockEngine();
        $blocks      = $blockEngine->getBlocksForPage($pageSlug);

        foreach ($blocks as $block) {
            $this->renderBlock($block);
        }
    }

    /**
     * Resolve a component name to its definition.
     */
    private function resolve(string $name): ?array
    {
        /* Check runtime registrations */
        if (isset($this->components[$name])) {
            return $this->components[$name];
        }

        /* Check built-ins */
        if (isset(self::BUILTIN[$name]) && self::BUILTIN[$name] !== null) {
            return [
                'template'       => $this->componentDir . '/' . self::BUILTIN[$name],
                'default_config' => [],
            ];
        }

        /* Check database registry */
        $db = $this->db->fetch(
            "SELECT * FROM global_components WHERE (slug = ? OR name = ?) AND is_active = 1",
            [$name, $name]
        );

        if ($db) {
            return [
                'template'       => $this->componentDir . '/' . ltrim($db['template_path'], '/'),
                'default_config' => json_decode($db['default_config'] ?? '{}', true) ?: [],
            ];
        }

        return null;
    }

    /* ================================================================
       Built-in Block Renderers
       ================================================================ */

    private function renderCtaBlock(array $config): void
    {
        $title   = esc($config['title'] ?? 'Ready to get started?');
        $text    = esc($config['text'] ?? '');
        $btnText = esc($config['btn_text'] ?? 'Contact Us');
        $btnUrl  = esc($config['btn_url'] ?? '/contact');
        ?>
        <section class="cta-section">
            <div class="container">
                <h2 class="cta-title"><?php echo $title; ?></h2>
                <?php if ($text): ?>
                    <p class="cta-text"><?php echo $text; ?></p>
                <?php endif; ?>
                <a href="<?php echo $btnUrl; ?>" class="btn btn--cta cta-button"><?php echo $btnText; ?></a>
            </div>
        </section>
        <?php
    }

    private function renderFaqBlock(array $config): void
    {
        $faqs = $config['faqs'] ?? $config['items'] ?? [];
        if (empty($faqs)) return;
        ?>
        <section class="section">
            <div class="container container--narrow">
                <?php if (!empty($config['heading'])): ?>
                    <h2 class="section__heading"><?php echo esc($config['heading']); ?></h2>
                <?php endif; ?>
                <div class="faq-list">
                    <?php foreach ($faqs as $faq): ?>
                        <details class="faq-item">
                            <summary class="faq-item__question"><?php echo esc($faq['question'] ?? ''); ?></summary>
                            <div class="faq-item__answer"><?php echo esc($faq['answer'] ?? ''); ?></div>
                        </details>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
    }

    private function renderStatsBlock(array $config): void
    {
        $stats = $config['stats'] ?? $config['items'] ?? [];
        if (empty($stats)) return;
        ?>
        <section class="section section--alt">
            <div class="container">
                <?php if (!empty($config['heading'])): ?>
                    <h2 class="section__heading"><?php echo esc($config['heading']); ?></h2>
                <?php endif; ?>
                <div class="stats-grid">
                    <?php foreach ($stats as $stat): ?>
                        <div class="stat-card">
                            <span class="stat-card__value"><?php echo esc($stat['value'] ?? ''); ?></span>
                            <span class="stat-card__label"><?php echo esc($stat['label'] ?? ''); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
    }

    private function renderTestimonialsBlock(array $config): void
    {
        $items = $config['testimonials'] ?? $config['items'] ?? [];
        if (empty($items)) return;
        ?>
        <section class="section">
            <div class="container">
                <?php if (!empty($config['heading'])): ?>
                    <h2 class="section__heading"><?php echo esc($config['heading']); ?></h2>
                <?php endif; ?>
                <div class="testimonials-grid">
                    <?php foreach ($items as $item): ?>
                        <blockquote class="testimonial-card">
                            <p class="testimonial-card__text"><?php echo esc($item['quote'] ?? ''); ?></p>
                            <cite class="testimonial-card__author">
                                <strong><?php echo esc($item['name'] ?? ''); ?></strong>
                                <?php if (!empty($item['role'])): ?>
                                    <span><?php echo esc($item['role']); ?></span>
                                <?php endif; ?>
                            </cite>
                        </blockquote>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
    }

    private function renderBannerBlock(array $config): void
    {
        ?>
        <section class="section section--dark"<?php if (!empty($config['bg_color'])) echo ' style="background-color:' . esc($config['bg_color']) . '"'; ?>>
            <div class="container u-text-center">
                <h2 class="section__heading"><?php echo esc($config['heading'] ?? ''); ?></h2>
                <?php if (!empty($config['text'])): ?>
                    <p class="section__subheading"><?php echo esc($config['text']); ?></p>
                <?php endif; ?>
                <?php if (!empty($config['btn_text'])): ?>
                    <a href="<?php echo esc($config['btn_url'] ?? '#'); ?>" class="btn btn--cta"><?php echo esc($config['btn_text']); ?></a>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }
}
