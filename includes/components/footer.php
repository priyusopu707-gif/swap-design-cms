<?php
/**
 * Swap Design - Footer Component
 *
 * Reusable <footer> element included on every page via layout.php.
 * Reads footer links from footer_links table and copyright from
 * footer_settings via SettingsManager. Falls back to $site config.
 *
 * NOTE: This file no longer closes </main> -- layout.php handles
 *       the main landmark. Scripts are loaded via /includes/scripts.php.
 *
 * Requires: $site (global site config)
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

global $site;
$social = $site->social;
$fromDb  = false;

/* Try DB-driven footer first, fall back to site config */
try {
    $db         = Database::getInstance();
    $settingsMgr = new SettingsManager();
    $links       = $db->fetchAll(
        "SELECT * FROM footer_links WHERE is_visible = 1 ORDER BY group_name, sort_order ASC"
    );
    if (!empty($links)) {
        $fromDb = true;
    }
} catch (\Exception $e) {
    $links = [];
}

if ($fromDb) {
    /* Group links by group_name */
    $groups = [];
    foreach ($links as $link) {
        $gname = $link['group_name'] ?? 'Links';
        $groups[$gname][] = $link;
    }

    /* Copyright from settings */
    $copyrightText = $settingsMgr->get('footer.copyright_text');
    if (!$copyrightText) {
        $copyrightText = '&copy; {year} Swap Design. All rights reserved.';
    }
    $copyrightText = str_replace('{year}', date('Y'), esc($copyrightText));

    /* Legal links are grouped under "Legal" if present, otherwise separate */
    $legalLinks = $groups['Legal'] ?? [];
    unset($groups['Legal']);

    /* Remove Quick Links and Services if they were set - they render via groups */
    $footerConfig = null;
} else {
    /* Fall back to hardcoded site config */
    $footerConfig = $site->footer;
}
?>

<footer class="site-footer" role="contentinfo">
    <div class="container">
        <div class="footer-grid">
                <!-- Brand Column -->
                <div class="footer-brand">
                    <a href="/" class="footer-logo" aria-label="<?php echo esc($site->brand->name); ?> - Home">
                        <?php echo esc($site->brand->name); ?>
                    </a>
                    <?php if ($site->brand->tagline): ?>
                        <p class="footer-tagline"><?php echo esc($site->brand->tagline); ?></p>
                    <?php endif; ?>
                    <?php if ($site->brand->description): ?>
                        <p class="footer-description"><?php echo esc($site->brand->description); ?></p>
                    <?php endif; ?>
                </div>

                <?php if ($fromDb): ?>
                    <?php foreach ($groups as $groupName => $groupLinks): ?>
                <div class="footer-links">
                    <h3 class="footer-heading"><?php echo esc($groupName); ?></h3>
                    <ul>
                        <?php foreach ($groupLinks as $link): ?>
                            <li><a href="<?php echo esc($link['url']); ?>"><?php echo esc($link['label']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <!-- Quick Links -->
                <div class="footer-links">
                    <h3 class="footer-heading">Quick Links</h3>
                    <ul>
                        <?php foreach ($footerConfig->quickLinks as $link): ?>
                            <li><a href="<?php echo esc($link['url']); ?>"><?php echo esc($link['label']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Services -->
                <div class="footer-links">
                    <h3 class="footer-heading">Services</h3>
                    <ul>
                        <?php foreach ($footerConfig->servicesLinks as $link): ?>
                            <li><a href="<?php echo esc($link['url']); ?>"><?php echo esc($link['label']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Contact & Social -->
                <div class="footer-contact-social">
                    <!-- Contact -->
                    <?php if ($site->brand->email || $site->brand->phone || $site->brand->address): ?>
                    <div class="footer-contact">
                        <h3 class="footer-heading">Contact</h3>
                        <?php if ($site->brand->email): ?>
                            <p><a href="mailto:<?php echo esc($site->brand->email); ?>"><?php echo esc($site->brand->email); ?></a></p>
                        <?php endif; ?>
                        <?php if ($site->brand->phone): ?>
                            <p><a href="tel:<?php echo esc($site->brand->phone); ?>"><?php echo esc($site->brand->phone); ?></a></p>
                        <?php endif; ?>
                        <?php if ($site->brand->address): ?>
                            <p><?php echo esc($site->brand->address); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Social Links -->
                    <?php
                    $activeSocial = array_filter($social, fn($s) => !empty($s['url']));
                    if (!empty($activeSocial)):
                    ?>
                    <div class="footer-social">
                        <h3 class="footer-heading">Follow</h3>
                        <div class="social-links">
                            <?php foreach ($activeSocial as $platform => $data): ?>
                                <a href="<?php echo esc($data['url']); ?>"
                                   class="social-link social-link--<?php echo esc($platform); ?>"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   aria-label="<?php echo esc($data['label']); ?>">
                                    <span class="social-icon social-icon--<?php echo esc($platform); ?>"></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="footer-bottom">
                <?php if ($fromDb): ?>
                    <p><?php echo $copyrightText; ?></p>
                    <?php if (!empty($legalLinks)): ?>
                    <div class="footer-legal">
                        <?php foreach ($legalLinks as $i => $link): ?>
                            <?php if ($i > 0): ?><span class="footer-legal-sep">|</span><?php endif; ?>
                            <a href="<?php echo esc($link['url']); ?>"><?php echo esc($link['label']); ?></a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p><?php printf($footerConfig->copyright, date('Y')); ?></p>
                    <?php if (!empty($footerConfig->legalLinks)): ?>
                    <div class="footer-legal">
                        <?php foreach ($footerConfig->legalLinks as $i => $link): ?>
                            <?php if ($i > 0): ?><span class="footer-legal-sep">|</span><?php endif; ?>
                            <a href="<?php echo esc($link['url']); ?>"><?php echo esc($link['label']); ?></a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </footer>
