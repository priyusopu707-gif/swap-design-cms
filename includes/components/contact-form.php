<?php
/**
 * Swap Design - Contact Form Component
 *
 * Reusable contact form with client-side HTML5 validation,
 * honeypot anti-spam, and CSRF protection.
 * Submits to /api/contact.php via AJAX.
 *
 * Requires: $site (global site config)
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

global $site;
$formConfig = $site->forms;
$csrfToken  = '';
$csrfField  = '';

if ($formConfig->enableCsrf) {
    $csrfToken = csrfToken();
    $csrfField = '<input type="hidden" name="csrf_token" value="' . esc($csrfToken) . '">';
}
?>

<form id="contact-form"
      class="contact-form"
      action="/api/contact.php"
      method="POST"
      novalidate>

    <?php echo $csrfField; ?>

    <?php if ($formConfig->enableHoneypot): ?>
    <!-- Honeypot (anti-spam - hidden from real users) -->
    <div class="form-field form-field--hidden" aria-hidden="true">
        <label for="honeypot">Leave this empty</label>
        <input type="text" id="honeypot" name="website" tabindex="-1" autocomplete="off">
    </div>
    <?php endif; ?>

    <!-- Name -->
    <div class="form-field">
        <label for="contact-name">Name <span class="required" aria-hidden="true">*</span></label>
        <input type="text"
               id="contact-name"
               name="name"
               required
               minlength="2"
               maxlength="100"
               placeholder="Your full name"
               autocomplete="name">
        <span class="form-error" role="alert"></span>
    </div>

    <!-- Email -->
    <div class="form-field">
        <label for="contact-email">Email <span class="required" aria-hidden="true">*</span></label>
        <input type="email"
               id="contact-email"
               name="email"
               required
               maxlength="254"
               placeholder="your@email.com"
               autocomplete="email">
        <span class="form-error" role="alert"></span>
    </div>

    <!-- Subject -->
    <div class="form-field">
        <label for="contact-subject">Subject</label>
        <input type="text"
               id="contact-subject"
               name="subject"
               maxlength="200"
               placeholder="What is this about?">
        <span class="form-error" role="alert"></span>
    </div>

    <!-- Message -->
    <div class="form-field">
        <label for="contact-message">Message <span class="required" aria-hidden="true">*</span></label>
        <textarea id="contact-message"
                  name="message"
                  required
                  minlength="10"
                  maxlength="5000"
                  rows="5"
                  placeholder="Tell us about your project..."></textarea>
        <span class="form-error" role="alert"></span>
    </div>

    <!-- Submit -->
    <div class="form-field">
        <button type="submit" class="btn btn--primary btn--lg" id="contact-submit">
            Send Message
        </button>
    </div>

    <!-- Status message (populated by JS on submit) -->
    <div id="contact-status"
         class="form-status"
         role="status"
         aria-live="polite"
         hidden></div>
</form>
