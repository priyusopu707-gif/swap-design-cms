/**
 * WhatsApp Floating Button -- JavaScript
 *
 * Handles: visibility entrance animation, click tracking,
 * device type detection for analytics.
 *
 * @package SwapDesign
 */

(function () {
    'use strict';

    var btn = document.getElementById('wa-floating-btn');
    if (!btn || btn.getAttribute('data-enabled') !== '1') {
        return;
    }

    /* Entrance animation after short delay */
    requestAnimationFrame(function () {
        requestAnimationFrame(function () {
            btn.classList.add('wa-btn--visible');
        });
    });

    /**
     * Detect device type.
     */
    function detectDeviceType() {
        var ua = navigator.userAgent || '';
        if (/tablet|ipad|playbook|silk/i.test(ua)) return 'tablet';
        if (/mobi|android|iphone|ipod|blackberry|webos/i.test(ua)) return 'mobile';
        return 'desktop';
    }

    /**
     * Track a WhatsApp click via the API endpoint.
     */
    function trackClick(source, sourceLabel, pageId, pageTitle) {
        var deviceType = detectDeviceType();

        try {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '/api/whatsapp-track.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send(
                'source=' + encodeURIComponent(source) +
                '&source_label=' + encodeURIComponent(sourceLabel || '') +
                '&page_id=' + encodeURIComponent(pageId || 0) +
                '&page_title=' + encodeURIComponent(pageTitle || document.title || '') +
                '&device_type=' + encodeURIComponent(deviceType)
            );
        } catch (e) {
            /* Silently fail -- tracking is non-critical */
        }
    }

    /**
     * Attach click tracking to all WhatsApp links on the page.
     */
    function attachTracking() {
        var links = document.querySelectorAll('[data-wa-click]');
        links.forEach(function (link) {
            link.addEventListener('click', function (e) {
                var source      = link.getAttribute('data-wa-click') || 'unknown';
                var sourceLabel = link.getAttribute('data-wa-label') || '';
                var pageId      = link.getAttribute('data-page-id') || btn.getAttribute('data-page-id') || 0;
                var pageTitle   = btn.getAttribute('data-page-title') || document.title || '';

                trackClick(source, sourceLabel, pageId, pageTitle);
            });
        });
    }

    /* Track the floating button itself */
    if (btn) {
        var floatingLink = btn.querySelector('[data-wa-click]');
        if (floatingLink) {
            floatingLink.addEventListener('click', function () {
                var pageId    = btn.getAttribute('data-page-id') || 0;
                var pageTitle = btn.getAttribute('data-page-title') || document.title || '';
                trackClick('floating_button', '', pageId, pageTitle);
            });
        }
    }

    /* Attach tracking to all CTA buttons after DOM is ready */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', attachTracking);
    } else {
        attachTracking();
    }

    /* Keyboard support: Enter/Space on the button div */
    btn.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            var link = btn.querySelector('a');
            if (link) link.click();
        }
    });
})();
