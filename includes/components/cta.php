<?php
/**
 * Swap Design - Call To Action Component
 *
 * Reusable CTA section that can be placed on any page.
 * Accepts title, description, button text, and button link.
 *
 * @package SwapDesign
 * @param string $ctaTitle    Heading text for the CTA
 * @param string $ctaText     Description text
 * @param string $ctaBtnText  Button label
 * @param string $ctaBtnLink  Button URL
 */

defined('SWAP_ROOT') or die('Access denied');

$ctaTitle   = $ctaTitle   ?? 'Ready to get started?';
$ctaText    = $ctaText    ?? '';
$ctaBtnText = $ctaBtnText ?? 'Contact Us';
$ctaBtnLink = $ctaBtnLink ?? '/contact';
?>

<section class="cta-section" aria-labelledby="cta-heading">
    <div class="container">
        <h2 id="cta-heading" class="cta-title"><?php echo htmlspecialchars($ctaTitle); ?></h2>

        <?php if ($ctaText): ?>
            <p class="cta-text"><?php echo htmlspecialchars($ctaText); ?></p>
        <?php endif; ?>

        <a href="<?php echo htmlspecialchars($ctaBtnLink); ?>"
           class="btn btn--cta"
           role="button">
            <?php echo htmlspecialchars($ctaBtnText); ?>
        </a>
    </div>
</section>
