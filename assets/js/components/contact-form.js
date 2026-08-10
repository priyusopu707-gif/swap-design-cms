/**
 * Swap Design - Contact Form Component JS
 * Client-side validation and AJAX submission for the contact form.
 */
(() => {
    const form = document.getElementById('contact-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearErrors();
        hideStatus();

        if (!validate()) return;

        const submitBtn = document.getElementById('contact-submit');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending...';

        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            });

            const result = await response.json();

            if (result.success) {
                showStatus('success', result.message || 'Message sent successfully!');
                form.reset();
            } else {
                showStatus('error', result.message || 'Something went wrong. Please try again.');

                if (result.errors) {
                    Object.entries(result.errors).forEach(([field, msg]) => showError(field, msg));
                }
            }
        } catch (err) {
            showStatus('error', 'Network error. Please check your connection and try again.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Send Message';
        }
    });

    function validate() {
        let valid = true;
        const name    = document.getElementById('contact-name');
        const email   = document.getElementById('contact-email');
        const message = document.getElementById('contact-message');

        if (!name.value.trim() || name.value.trim().length < 2) {
            showError('contact-name', 'Please enter your name (at least 2 characters).');
            valid = false;
        }

        if (!email.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
            showError('contact-email', 'Please enter a valid email address.');
            valid = false;
        }

        if (!message.value.trim() || message.value.trim().length < 10) {
            showError('contact-message', 'Please enter a message (at least 10 characters).');
            valid = false;
        }

        return valid;
    }

    function showError(fieldId, message) {
        const input = document.getElementById(fieldId);
        const error = input?.closest('.form-field')?.querySelector('.form-error');
        if (input) input.classList.add('error');
        if (error) error.textContent = message;
    }

    function clearErrors() {
        $$('.form-error').forEach((el) => (el.textContent = ''));
        $$('.error').forEach((el) => el.classList.remove('error'));
    }

    function showStatus(type, message) {
        const status = document.getElementById('contact-status');
        if (!status) return;
        status.textContent = message;
        status.className = `form-status form-status--${type}`;
        status.hidden = false;
    }

    function hideStatus() {
        const status = document.getElementById('contact-status');
        if (status) status.hidden = true;
    }
})();
