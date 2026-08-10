/**
 * Swap Design - FAQ Accordion
 *
 * Toggle .accordion__item open/close. Only one open at a time.
 * Keyboard accessible: Enter/Space to toggle, focus visible.
 *
 * @package SwapDesign
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.accordion').forEach(function (acc) {
            var buttons = acc.querySelectorAll('.accordion__button');

            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var item = btn.closest('.accordion__item');
                    var isOpen = item.classList.contains('is-open');
                    // Close all items in this accordion
                    acc.querySelectorAll('.accordion__item.is-open').forEach(function (openItem) {
                        openItem.classList.remove('is-open');
                        var content = openItem.querySelector('.accordion__content');
                        if (content) content.style.maxHeight = '0';
                        var openBtn = openItem.querySelector('.accordion__button');
                        if (openBtn) openBtn.setAttribute('aria-expanded', 'false');
                    });
                    // Toggle clicked item
                    if (!isOpen) {
                        item.classList.add('is-open');
                        var content = item.querySelector('.accordion__content');
                        if (content) {
                            content.style.maxHeight = content.scrollHeight + 'px';
                        }
                        btn.setAttribute('aria-expanded', 'true');
                    }
                });
            });
        });
    });
})();
