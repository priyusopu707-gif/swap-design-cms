<?php
/**
 * Swap Design - 404 Not Found Page
 *
 * Renders when no route matches the requested URL.
 * Outputs a friendly error with navigation options.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');
?>

<div class="error-page">
    <div class="container u-text-center">
        <div class="error-page__content">
            <span class="error-page__code" aria-hidden="true">404</span>
            <h2 class="error-page__title">Page Not Found</h2>
            <p class="error-page__text">
                The page you are looking for does not exist or has been moved.
            </p>
            <div class="error-page__actions">
                <a href="/" class="btn btn--primary btn--lg">Back to Home</a>
                <a href="/contact" class="btn btn--secondary btn--lg">Contact Us</a>
            </div>
        </div>
    </div>
</div>
