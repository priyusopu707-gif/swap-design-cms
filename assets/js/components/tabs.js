/**
 * Swap Design - Tabs
 *
 * Simple tab switching. Click .tabs__btn to show .tabs__panel.
 * Keyboard: arrow keys to move between tabs, Enter/Space to activate.
 *
 * @package SwapDesign
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.tabs').forEach(function (tab) {
            var buttons = tab.querySelectorAll('.tabs__btn');
            var panels  = tab.querySelectorAll('.tabs__panel');

            function activate(idx) {
                buttons.forEach(function (b, i) {
                    b.classList.toggle('is-active', i === idx);
                    b.setAttribute('aria-selected', i === idx ? 'true' : 'false');
                    b.setAttribute('tabindex', i === idx ? '0' : '-1');
                });
                panels.forEach(function (p, i) {
                    p.classList.toggle('is-active', i === idx);
                });
                buttons[idx].focus();
            }

            buttons.forEach(function (btn, idx) {
                btn.addEventListener('click', function () { activate(idx); });
                btn.addEventListener('keydown', function (e) {
                    var next = idx;
                    if (e.key === 'ArrowRight') next = (idx + 1) % buttons.length;
                    else if (e.key === 'ArrowLeft') next = (idx - 1 + buttons.length) % buttons.length;
                    else if (e.key === 'Home') next = 0;
                    else if (e.key === 'End') next = buttons.length - 1;
                    else return;
                    e.preventDefault();
                    activate(next);
                });
            });

            // Set initial state
            var initial = tab.querySelector('.tabs__btn.is-active') || buttons[0];
            if (initial) initial.click();
        });
    });
})();
