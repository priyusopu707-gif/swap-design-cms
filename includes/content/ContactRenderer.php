<?php
/**
 * Swap Design - Contact Page Renderer
 *
 * Renders the full contact page with 6 dynamic sections, ContactPage
 * JSON-LD schema, and WhatsApp integration.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class ContactRenderer
{
    private ContactManager $manager;
    private WhatsAppManager $whatsapp;
    private Database $db;

    public function __construct()
    {
        $this->manager  = new ContactManager();
        $this->whatsapp = new WhatsAppManager();
        $this->db       = Database::getInstance();
    }

    /* ================================================================
       Main render
       ================================================================ */

    public function render(): string
    {
        $sections = $this->manager->getActive();
        if (empty($sections)) {
            return $this->renderEmptyState();
        }

        $html = '';
        foreach ($sections as $section) {
            $key    = $section['section_key'];
            $config = $section['config'] ?? [];
            $method = 'render' . $this->camelCase($key);
            if (method_exists($this, $method)) {
                $html .= $this->$method($config);
            }
        }
        return $html;
    }

    /* ================================================================
       Assets
       ================================================================ */

    public function getPageAssets(): array
    {
        return [
            'css' => ['/assets/css/contact.css'],
            'js'  => ['/assets/js/pages/contact.js'],
        ];
    }

    /* ================================================================
       SEO Schema
       ================================================================ */

    public function getSchema(): string
    {
        global $site;

        $contactSection = $this->manager->getByKey('contact_info');
        $cfg            = $contactSection['config'] ?? [];

        $schema = [
            '@context'   => 'https://schema.org',
            '@type'      => 'ContactPage',
            'name'       => $site->brand->name ?? 'Swap Design',
            'description'=> 'Contact page for ' . esc($site->brand->name ?? 'Swap Design'),
            'url'        => ($site->urls->base ?? '') . '/contact',
        ];

        if (!empty($cfg['phone'])) {
            $schema['contactPoint'] = [
                '@type'             => 'ContactPoint',
                'telephone'         => $cfg['phone'],
                'contactType'       => 'customer service',
                'availableLanguage' => ['English'],
            ];
        }

        $output = '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';

        $contactFaq  = $this->manager->getByKey('faq');
        $faqItems    = $contactFaq['config']['items'] ?? [];
        if (!empty($faqItems)) {
            $output .= getFaqPageSchema($faqItems);
        }

        return $output;
    }

    /* ================================================================
       Section renderers
       ================================================================ */

    private function renderHero(array $cfg): string
    {
        $title = esc($cfg['title'] ?? '');
        $desc  = esc($cfg['description'] ?? '');
        $bg    = $cfg['bg_image'] ?? '';

        $style = '';
        if ($bg) {
            $style = ' style="background-image:url(' . esc($bg) . ')"';
        }

        return <<<HTML
        <section class="contact-hero" id="contact-hero" aria-labelledby="contact-hero-heading"{$style}>
            <div class="contact-hero__overlay"></div>
            <div class="contact-hero__content">
                <h1 class="contact-hero__title" id="contact-hero-heading">{$title}</h1>
                <p class="contact-hero__desc">{$desc}</p>
            </div>
        </section>
        HTML;
    }

    private function renderContactInfo(array $cfg): string
    {
        $heading   = esc($cfg['heading'] ?? '');
        $phone     = esc($cfg['phone'] ?? '');
        $phoneLab  = esc($cfg['phone_label'] ?? '');
        $whatsapp  = esc($cfg['whatsapp'] ?? '');
        $waLabel   = esc($cfg['whatsapp_label'] ?? '');
        $email     = esc($cfg['email'] ?? '');
        $emailLab  = esc($cfg['email_label'] ?? '');
        $hours     = esc($cfg['office_hours'] ?? '');
        $area      = esc($cfg['service_area'] ?? '');
        $map       = $cfg['google_maps_embed'] ?? '';
        $showMap   = !empty($cfg['show_map']);
        $addr1     = esc($cfg['address_line1'] ?? '');
        $addr2     = esc($cfg['address_line2'] ?? '');

        $waClean   = preg_replace('/[^0-9]/', '', $whatsapp);

        $info = '';
        if ($heading) {
            $info .= "<h2 class=\"contact-info__heading\">{$heading}</h2>";
        }

        $items = '';
        if ($phone) {
            $items .= <<<HTML
            <div class="contact-info__item">
                <span class="contact-info__icon" aria-hidden="true">&#9742;</span>
                <div>
                    <span class="contact-info__label">{$phoneLab}</span>
                    <a href="tel:{$phone}" class="contact-info__value">{$phone}</a>
                </div>
            </div>
            HTML;
        }

        if ($whatsapp && $waClean) {
            $waUrl = 'https://wa.me/' . $waClean;
            $items .= <<<HTML
            <div class="contact-info__item">
                <span class="contact-info__icon" aria-hidden="true">&#128172;</span>
                <div>
                    <span class="contact-info__label">{$waLabel}</span>
                    <a href="{$waUrl}" target="_blank" rel="noopener" class="contact-info__value js-whatsapp-open">{$whatsapp}</a>
                </div>
            </div>
            HTML;
        }

        if ($email) {
            $items .= <<<HTML
            <div class="contact-info__item">
                <span class="contact-info__icon" aria-hidden="true">&#9993;</span>
                <div>
                    <span class="contact-info__label">{$emailLab}</span>
                    <a href="mailto:{$email}" class="contact-info__value">{$email}</a>
                </div>
            </div>
            HTML;
        }

        if ($hours) {
            $items .= <<<HTML
            <div class="contact-info__item">
                <span class="contact-info__icon" aria-hidden="true">&#128340;</span>
                <div>
                    <span class="contact-info__label">Office Hours</span>
                    <span class="contact-info__value">{$hours}</span>
                </div>
            </div>
            HTML;
        }

        if ($area) {
            $items .= <<<HTML
            <div class="contact-info__item">
                <span class="contact-info__icon" aria-hidden="true">&#127758;</span>
                <div>
                    <span class="contact-info__label">Service Area</span>
                    <span class="contact-info__value">{$area}</span>
                </div>
            </div>
            HTML;
        }

        $addrHtml = '';
        if ($addr1) {
            $addrHtml = '<address class="contact-info__address">' . esc($addr1);
            if ($addr2) {
                $addrHtml .= '<br>' . esc($addr2);
            }
            $addrHtml .= '</address>';
        }

        $mapHtml = '';
        if ($showMap && $map) {
            $mapHtml = <<<HTML
            <div class="contact-info__map">
                <iframe src="{$map}" width="100%" height="300" style="border:0;border-radius:12px" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Office location on Google Maps"></iframe>
            </div>
            HTML;
        }

        return <<<HTML
        <section class="contact-info" id="contact-info" aria-label="Contact information">
            {$info}
            <div class="contact-info__grid">
                {$items}
                {$addrHtml}
            </div>
            {$mapHtml}
        </section>
        HTML;
    }

    private function renderContactForm(array $cfg): string
    {
        $heading       = esc($cfg['heading'] ?? '');
        $subheading    = esc($cfg['subheading'] ?? '');
        $nameLabel     = esc($cfg['name_label'] ?? '');
        $namePlace     = esc($cfg['name_placeholder'] ?? '');
        $nameReq       = !empty($cfg['name_required']) ? 'required' : '';
        $emailLabel    = esc($cfg['email_label'] ?? '');
        $emailPlace    = esc($cfg['email_placeholder'] ?? '');
        $emailReq      = !empty($cfg['email_required']) ? 'required' : '';
        $phoneLabel    = esc($cfg['phone_label'] ?? '');
        $phonePlace    = esc($cfg['phone_placeholder'] ?? '');
        $compLabel     = esc($cfg['company_label'] ?? '');
        $compPlace     = esc($cfg['company_placeholder'] ?? '');
        $subjectLabel  = esc($cfg['subject_label'] ?? '');
        $subjectPlace  = esc($cfg['subject_placeholder'] ?? '');
        $subjectReq    = !empty($cfg['subject_required']) ? 'required' : '';
        $serviceLabel  = esc($cfg['service_label'] ?? '');
        $budgetLabel   = esc($cfg['budget_label'] ?? '');
        $timelineLabel = esc($cfg['timeline_label'] ?? '');
        $msgLabel      = esc($cfg['message_label'] ?? '');
        $msgPlace      = esc($cfg['message_placeholder'] ?? '');
        $msgReq        = !empty($cfg['message_required']) ? 'required' : '';
        $fileLabel     = esc($cfg['file_upload_label'] ?? '');
        $fileEnabled   = !empty($cfg['file_upload_enabled']);
        $fileTypes     = esc($cfg['file_allowed_types'] ?? '');
        $fileMaxSize   = (int)($cfg['file_max_size'] ?? 10);
        $consentLabel  = esc($cfg['consent_label'] ?? '');
        $consentReq    = !empty($cfg['consent_required']) ? 'required' : '';
        $submitLabel   = esc($cfg['submit_label'] ?? '');
        $successMsg    = esc($cfg['success_message'] ?? '');
        $recaptchaSite = esc($cfg['recaptcha_site_key'] ?? '');
        $recaptchaOn   = !empty($cfg['recaptcha_enabled']) && !empty($recaptchaSite);

        $csrfToken = csrfToken();

        /* Budget options */
        $budgetOptions = $cfg['budget_options'] ?? [];
        $budgetHtml    = '';
        if ($budgetOptions) {
            $budgetHtml = '<select name="budget" id="contact-budget" class="contact-form__select">';
            $budgetHtml .= '<option value="">' . esc($budgetLabel) . '</option>';
            foreach ($budgetOptions as $opt) {
                $budgetHtml .= '<option value="' . esc($opt) . '">' . esc($opt) . '</option>';
            }
            $budgetHtml .= '</select>';
        }

        /* Timeline options */
        $timelineOptions = $cfg['timeline_options'] ?? [];
        $timelineHtml    = '';
        if ($timelineOptions) {
            $timelineHtml = '<select name="timeline" id="contact-timeline" class="contact-form__select">';
            $timelineHtml .= '<option value="">' . esc($timelineLabel) . '</option>';
            foreach ($timelineOptions as $opt) {
                $timelineHtml .= '<option value="' . esc($opt) . '">' . esc($opt) . '</option>';
            }
            $timelineHtml .= '</select>';
        }

        /* Services dropdown */
        $servicesHtml = '';
        $services     = $this->db->fetchAll("SELECT id, title FROM services WHERE status = 'published' ORDER BY sort_order ASC, title ASC");
        if ($services) {
            $servicesHtml = '<select name="service_id" id="contact-service" class="contact-form__select">';
            $servicesHtml .= '<option value="">' . esc($serviceLabel) . '</option>';
            foreach ($services as $svc) {
                $servicesHtml .= '<option value="' . (int)$svc['id'] . '">' . esc($svc['title']) . '</option>';
            }
            $servicesHtml .= '</select>';
        }

        $fileField = '';
        if ($fileEnabled) {
            $fileField = <<<HTML
            <div class="contact-form__group contact-form__group--file">
                <label for="contact-file" class="contact-form__label">{$fileLabel}</label>
                <input type="file" name="files[]" id="contact-file" class="contact-form__file" multiple accept=".{$fileTypes}">
                <span class="contact-form__hint">Max {$fileMaxSize}MB. Allowed: {$fileTypes}</span>
            </div>
            HTML;
        }

        $recaptchaHtml = '';
        if ($recaptchaOn) {
            $recaptchaHtml = <<<HTML
            <div class="contact-form__recaptcha">
                <div class="g-recaptcha" data-sitekey="{$recaptchaSite}"></div>
            </div>
            HTML;
        }

        return <<<HTML
        <section class="contact-form-section" id="contact-form" aria-labelledby="contact-form-heading">
            <div class="contact-form__header">
                <h2 class="contact-form__heading" id="contact-form-heading">{$heading}</h2>
                <p class="contact-form__subheading">{$subheading}</p>
            </div>
            <form class="contact-form" id="contact-form-elm" method="POST" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="csrf_token" value="{$csrfToken}">
                <input type="hidden" name="source_page" id="contact-source-page" value="">
                <input type="hidden" name="referrer_url" id="contact-referrer-url" value="">

                <!-- Honeypot -->
                <div class="contact-form__hp" aria-hidden="true">
                    <label for="website">Website</label>
                    <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="contact-form__grid">
                    <div class="contact-form__group">
                        <label for="contact-name" class="contact-form__label">{$nameLabel} <span class="contact-form__required" aria-hidden="true">*</span></label>
                        <input type="text" name="full_name" id="contact-name" class="contact-form__input" placeholder="{$namePlace}" {$nameReq}>
                        <span class="contact-form__error" id="contact-name-error" role="alert"></span>
                    </div>
                    <div class="contact-form__group">
                        <label for="contact-email" class="contact-form__label">{$emailLabel} <span class="contact-form__required" aria-hidden="true">*</span></label>
                        <input type="email" name="email" id="contact-email" class="contact-form__input" placeholder="{$emailPlace}" {$emailReq}>
                        <span class="contact-form__error" id="contact-email-error" role="alert"></span>
                    </div>
                    <div class="contact-form__group">
                        <label for="contact-phone" class="contact-form__label">{$phoneLabel}</label>
                        <input type="tel" name="phone" id="contact-phone" class="contact-form__input" placeholder="{$phonePlace}">
                    </div>
                    <div class="contact-form__group">
                        <label for="contact-company" class="contact-form__label">{$compLabel}</label>
                        <input type="text" name="company" id="contact-company" class="contact-form__input" placeholder="{$compPlace}">
                    </div>
                    <div class="contact-form__group">
                        <label for="contact-subject" class="contact-form__label">{$subjectLabel} <span class="contact-form__required" aria-hidden="true">*</span></label>
                        <input type="text" name="subject" id="contact-subject" class="contact-form__input" placeholder="{$subjectPlace}" {$subjectReq}>
                        <span class="contact-form__error" id="contact-subject-error" role="alert"></span>
                    </div>
                    {$servicesHtml}
                    {$budgetHtml}
                    {$timelineHtml}
                </div>

                <div class="contact-form__group contact-form__group--full">
                    <label for="contact-message" class="contact-form__label">{$msgLabel} <span class="contact-form__required" aria-hidden="true">*</span></label>
                    <textarea name="message" id="contact-message" class="contact-form__textarea" rows="6" placeholder="{$msgPlace}" {$msgReq}></textarea>
                    <span class="contact-form__error" id="contact-message-error" role="alert"></span>
                </div>

                {$fileField}

                <div class="contact-form__group contact-form__group--checkbox">
                    <input type="checkbox" name="consent" id="contact-consent" class="contact-form__checkbox" {$consentReq}>
                    <label for="contact-consent" class="contact-form__checkbox-label">{$consentLabel} <span class="contact-form__required" aria-hidden="true">*</span></label>
                    <span class="contact-form__error" id="contact-consent-error" role="alert"></span>
                </div>

                {$recaptchaHtml}

                <div class="contact-form__actions">
                    <button type="submit" class="contact-form__submit" id="contact-submit-btn">{$submitLabel}</button>
                </div>

                <div class="contact-form__status" id="contact-form-status" role="status" hidden>
                    <span class="contact-form__status-text" id="contact-form-status-text"></span>
                </div>
            </form>
        </section>
        HTML;
    }

    private function renderWhatsappCta(array $cfg): string
    {
        $heading     = esc($cfg['heading'] ?? '');
        $description = esc($cfg['description'] ?? '');
        $buttonText  = esc($cfg['button_text'] ?? '');
        $msgPrefix   = $cfg['message_prefix'] ?? '';

        $waSettings = $this->whatsapp->getSettings();
        $phone       = $waSettings['phone_number'] ?? '';
        $waClean     = preg_replace('/[^0-9]/', '', $phone);

        if (empty($waClean)) {
            return '';
        }

        $encodedMsg = urlencode($msgPrefix);
        $waUrl      = "https://wa.me/{$waClean}?text={$encodedMsg}";

        return <<<HTML
        <section class="contact-whatsapp" id="contact-whatsapp" aria-labelledby="wa-heading">
            <div class="contact-whatsapp__content">
                <h2 class="contact-whatsapp__heading" id="wa-heading">{$heading}</h2>
                <p class="contact-whatsapp__desc">{$description}</p>
                <a href="{$waUrl}" target="_blank" rel="noopener" class="contact-whatsapp__btn js-whatsapp-open" data-wa-message="{$encodedMsg}">
                    <span class="contact-whatsapp__btn-icon" aria-hidden="true">&#128172;</span>
                    {$buttonText}
                </a>
            </div>
        </section>
        HTML;
    }

    private function renderFaq(array $cfg): string
    {
        $heading = esc($cfg['heading'] ?? '');
        $items   = $cfg['items'] ?? [];

        if (empty($items)) {
            return '';
        }

        $faqHtml = '';
        foreach ($items as $i => $item) {
            $q = esc($item['question'] ?? '');
            $a = esc($item['answer'] ?? '');
            $ctrlId = "faq-{$i}";
            $answerId = "faq-answer-{$i}";

            $faqHtml .= <<<HTML
            <div class="contact-faq__item">
                <button class="contact-faq__question" aria-expanded="false" aria-controls="{$answerId}" id="{$ctrlId}">
                    <span>{$q}</span>
                    <span class="contact-faq__icon" aria-hidden="true">&#43;</span>
                </button>
                <div class="contact-faq__answer" id="{$answerId}" role="region" aria-labelledby="{$ctrlId}" hidden>
                    <p>{$a}</p>
                </div>
            </div>
            HTML;
        }

        return <<<HTML
        <section class="contact-faq" id="contact-faq" aria-labelledby="contact-faq-heading">
            <h2 class="contact-faq__heading" id="contact-faq-heading">{$heading}</h2>
            <div class="contact-faq__list">
                {$faqHtml}
            </div>
        </section>
        HTML;
    }

    private function renderFinalCta(array $cfg): string
    {
        $heading     = esc($cfg['heading'] ?? '');
        $description = esc($cfg['description'] ?? '');
        $buttonText  = esc($cfg['button_text'] ?? '');
        $buttonUrl   = esc($cfg['button_url'] ?? '#contact-form');
        $bgColor     = esc($cfg['bg_color'] ?? '');

        $bgStyle = '';
        if ($bgColor) {
            $bgStyle = " style=\"background-color:{$bgColor}\"";
        }

        return <<<HTML
        <section class="contact-cta" id="contact-cta" aria-labelledby="contact-cta-heading"{$bgStyle}>
            <h2 class="contact-cta__heading" id="contact-cta-heading">{$heading}</h2>
            <p class="contact-cta__desc">{$description}</p>
            <a href="{$buttonUrl}" class="contact-cta__btn">{$buttonText}</a>
        </section>
        HTML;
    }

    /* ================================================================
       Helpers
       ================================================================ */

    private function camelCase(string $snake): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $snake)));
    }

    private function renderEmptyState(): string
    {
        return '<section class="contact-empty"><p>Contact page content has not been configured yet.</p></section>';
    }
}
