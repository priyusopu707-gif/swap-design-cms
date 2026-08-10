/**
 * Swap Design - Contact Page
 * FAQ accordion, form validation, AJAX submission, reCAPTCHA,
 * WhatsApp click tracking, honeypot, file size check.
 */
(function () {
    'use strict';

    /* ====================================================================
       FAQ Accordion
       ==================================================================== */
    var faqQuestions = document.querySelectorAll('.contact-faq__question');
    faqQuestions.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var expanded = btn.getAttribute('aria-expanded') === 'true';
            var answerId = btn.getAttribute('aria-controls');
            var answer = document.getElementById(answerId);

            faqQuestions.forEach(function (q) {
                q.setAttribute('aria-expanded', 'false');
                var aId = q.getAttribute('aria-controls');
                var aEl = document.getElementById(aId);
                if (aEl) aEl.hidden = true;
            });

            if (!expanded) {
                btn.setAttribute('aria-expanded', 'true');
                if (answer) answer.hidden = false;
            }
        });
    });

    /* ====================================================================
       Set source page and referrer
       ==================================================================== */
    var sourcePage = document.getElementById('contact-source-page');
    var referrerUrl = document.getElementById('contact-referrer-url');
    if (sourcePage) sourcePage.value = window.location.href;
    if (referrerUrl) referrerUrl.value = document.referrer || '';

    /* ====================================================================
       Form validation
       ==================================================================== */
    var form = document.getElementById('contact-form-elm');
    if (!form) return;

    var statusEl = document.getElementById('contact-form-status');
    var statusText = document.getElementById('contact-form-status-text');
    var submitBtn = document.getElementById('contact-submit-btn');

    var fieldMap = {
        full_name: { el: document.getElementById('contact-name'), err: document.getElementById('contact-name-error'), label: 'Full Name', required: true },
        email:     { el: document.getElementById('contact-email'), err: document.getElementById('contact-email-error'), label: 'Email', required: true },
        subject:   { el: document.getElementById('contact-subject'), err: document.getElementById('contact-subject-error'), label: 'Subject', required: true },
        message:   { el: document.getElementById('contact-message'), err: document.getElementById('contact-message-error'), label: 'Project Details', required: true },
        consent:   { el: document.getElementById('contact-consent'), err: document.getElementById('contact-consent-error'), label: 'Consent', required: true },
    };

    function clearErrors() {
        Object.keys(fieldMap).forEach(function (k) {
            var f = fieldMap[k];
            if (f.err) f.err.textContent = '';
            if (f.el) f.el.classList.remove('error');
        });
    }

    function showFieldError(key, message) {
        var f = fieldMap[key];
        if (f && f.err) f.err.textContent = message;
        if (f && f.el) f.el.classList.add('error');
    }

    function validateForm() {
        var valid = true;
        clearErrors();

        Object.keys(fieldMap).forEach(function (k) {
            var f = fieldMap[k];
            if (!f.required) return;
            if (!f.el) return;

            if (f.el.type === 'checkbox') {
                if (!f.el.checked) {
                    showFieldError(k, f.label + ' is required.');
                    valid = false;
                }
            } else {
                var val = f.el.value.trim();
                if (!val) {
                    showFieldError(k, f.label + ' is required.');
                    valid = false;
                } else if (k === 'email') {
                    var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRe.test(val)) {
                        showFieldError(k, 'Please enter a valid email address.');
                        valid = false;
                    }
                }
            }
        });

        /* File size check */
        var fileInput = document.getElementById('contact-file');
        if (fileInput && fileInput.files.length) {
            var maxSizeMb = 10;
            var hint = fileInput.parentNode.querySelector('.contact-form__hint');
            if (hint) {
                var matchMb = hint.textContent.match(/Max\s+(\d+)MB/);
                if (matchMb) maxSizeMb = parseInt(matchMb[1], 10);
            }
            var maxBytes = maxSizeMb * 1024 * 1024;
            for (var i = 0; i < fileInput.files.length; i++) {
                if (fileInput.files[i].size > maxBytes) {
                    alert('File "' + fileInput.files[i].name + '" exceeds the ' + maxSizeMb + 'MB limit.');
                    valid = false;
                    break;
                }
            }
        }

        return valid;
    }

    function showStatus(message, success) {
        if (!statusEl || !statusText) return;
        statusText.textContent = message;
        statusEl.className = 'contact-form__status contact-form__status--' + (success ? 'success' : 'error');
        statusEl.hidden = false;
        if (success) {
            form.reset();
            form.style.display = 'none';
        }
    }

    /* ====================================================================
       AJAX Submission
       ==================================================================== */
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        if (!validateForm()) {
            return;
        }

        if (submitBtn) submitBtn.disabled = true;

        var fd = new FormData(form);
        fd.append('action', 'submit');

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/ajax/contact.php', true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.onload = function () {
            if (submitBtn) submitBtn.disabled = false;
            try {
                var res = JSON.parse(xhr.responseText);
                showStatus(res.message || 'Something went wrong.', res.ok);

                /* Track WhatsApp click if available */
                if (res.ok && typeof gtag === 'function') {
                    gtag('event', 'contact_form_submit', { event_category: 'lead', event_label: 'Contact Form' });
                }
            } catch (err) {
                showStatus('An unexpected error occurred. Please try again.', false);
            }
        };

        xhr.onerror = function () {
            if (submitBtn) submitBtn.disabled = false;
            showStatus('Network error. Please check your connection and try again.', false);
        };

        xhr.send(fd);
    });

    /* ====================================================================
       WhatsApp click tracking
       ==================================================================== */
    document.addEventListener('click', function (e) {
        var waBtn = e.target.closest('.js-whatsapp-open');
        if (!waBtn) return;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/ajax/contact.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send('action=track_whatsapp&page_url=' + encodeURIComponent(window.location.href) + '&button_location=' + encodeURIComponent(waBtn.className));

        if (typeof gtag === 'function') {
            gtag('event', 'whatsapp_click', { event_category: 'engagement', event_label: 'Contact Page' });
        }
    });
})();
