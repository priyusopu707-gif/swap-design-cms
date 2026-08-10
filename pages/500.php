<?php
/**
 * Swap Design - 500 Internal Server Error Page
 *
 * Renders when an unhandled exception occurs.
 * Shows a friendly message with navigation options.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');
?>
<div class="error-page">
    <div class="container u-text-center">
        <div class="error-page__content">
            <span class="error-page__code" aria-hidden="true">500</span>
            <h2 class="error-page__title">Something Went Wrong</h2>
            <p class="error-page__text">
                An unexpected error occurred. Our team has been notified and is working on a fix.
                Please try again later or reach out to us directly.
            </p>
            <div class="error-page__actions">
                <a href="/" class="btn btn--primary btn--lg">Back to Home</a>
                <a href="/contact" class="btn btn--secondary btn--lg">Contact Us</a>
            </div>
        </div>
    </div>
</div>
