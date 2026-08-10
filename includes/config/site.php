<?php
/**
 * Swap Design - Global Site Configuration
 *
 * Central configuration for all site-wide settings.
 * Include this file once at bootstrap (index.php).
 * All components and pages reference these values.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

return (object) [

    /* ============================================================
       Brand Identity
       ============================================================ */
    'brand' => (object) [
        'name'        => 'Swap Design',
        'tagline'     => 'Creative Design Solutions for Modern Brands',
        'description' => 'Swap Design is a remote freelance design studio with 8+ years of experience delivering logo & branding, graphic design, UI/UX design, web development, digital marketing, and website maintenance services.',
        'businessType'=> 'Remote Freelancer',
        'experience'  => '8+ Years',
        'foundedYear' => 2016,
        'email'       => 'hello@swapdesign.com',
        'phone'       => '',
        'address'     => '',
        'timezone'    => 'America/New_York',
        'language'    => 'en',
    ],

    /* ============================================================
       SEO Defaults
       ============================================================ */
    'seo' => (object) [
        'titleTemplate'       => '%s | Swap Design',
        'defaultTitle'        => 'Swap Design - Creative Design Solutions for Modern Brands',
        'defaultDescription'  => 'Swap Design is a remote freelance design studio offering logo & branding, graphic design, UI/UX design, web development, digital marketing, and website maintenance.',
        'defaultOgImage'      => '/assets/images/og-default.jpg',
        'defaultOgType'       => 'website',
        'twitterHandle'       => '@swapdesign',
        'googleSiteVerification' => '',
    ],

    /* ============================================================
       URLs
       ============================================================ */
    'urls' => (object) [
        'base'     => 'https://swapdesign.com',
        'email'    => 'hello@swapdesign.com',
    ],

    /* ============================================================
       Social Media Links
       ============================================================ */
    'social' => [
        'instagram'  => ['url' => '', 'label' => 'Instagram',  'icon' => 'instagram'],
        'behance'    => ['url' => '', 'label' => 'Behance',    'icon' => 'behance'],
        'dribbble'   => ['url' => '', 'label' => 'Dribbble',   'icon' => 'dribbble'],
        'linkedin'   => ['url' => '', 'label' => 'LinkedIn',   'icon' => 'linkedin'],
        'x'          => ['url' => '', 'label' => 'X / Twitter', 'icon' => 'x'],
        'youtube'    => ['url' => '', 'label' => 'YouTube',    'icon' => 'youtube'],
    ],

    /* ============================================================
       Primary Navigation Menu
       ============================================================ */
    'navigation' => [
        'primary' => [
            ['label' => 'Home',       'url' => '/',             'slug' => 'home'],
            ['label' => 'About',      'url' => '/about',        'slug' => 'about'],
            [
                'label'    => 'Services',
                'url'      => '/services',
                'slug'     => 'services',
                'children' => [
                    ['label' => 'Logo & Branding Design',  'url' => '/services/logo-branding'],
                    ['label' => 'Graphic Design',          'url' => '/services/graphic-design'],
                    ['label' => 'UI/UX Design',            'url' => '/services/ui-ux-design'],
                    ['label' => 'Web Development',         'url' => '/services/web-development'],
                    ['label' => 'Digital Marketing',       'url' => '/services/digital-marketing'],
                    ['label' => 'Website Maintenance',     'url' => '/services/website-maintenance'],
                ],
            ],
            ['label' => 'Portfolio',  'url' => '/portfolio',    'slug' => 'portfolio'],
            ['label' => 'Contact',    'url' => '/contact',      'slug' => 'contact'],
        ],
    ],

    /* ============================================================
       Footer Navigation
       ============================================================ */
    'footer' => (object) [
        'quickLinks' => [
            ['label' => 'Home',        'url' => '/'],
            ['label' => 'About',       'url' => '/about'],
            ['label' => 'Services',    'url' => '/services'],
            ['label' => 'Portfolio',   'url' => '/portfolio'],
            ['label' => 'Contact',     'url' => '/contact'],
        ],
        'servicesLinks' => [
            ['label' => 'Logo & Branding',   'url' => '/services/logo-branding'],
            ['label' => 'Graphic Design',    'url' => '/services/graphic-design'],
            ['label' => 'UI/UX Design',      'url' => '/services/ui-ux-design'],
            ['label' => 'Web Development',   'url' => '/services/web-development'],
            ['label' => 'Digital Marketing', 'url' => '/services/digital-marketing'],
            ['label' => 'Website Maintenance','url' => '/services/website-maintenance'],
        ],
        'legalLinks' => [
            ['label' => 'Privacy Policy',  'url' => '/privacy-policy'],
            ['label' => 'Terms of Service','url' => '/terms-of-service'],
        ],
        'copyright' => '&copy; %s Swap Design. All rights reserved.',
    ],

    /* ============================================================
       Services Offered
       ============================================================ */
    'services' => [
        [
            'id'          => 'logo-branding',
            'name'        => 'Logo & Branding Design',
            'icon'        => 'branding',
            'description' => 'Custom logos and complete brand identity systems that communicate your values and resonate with your audience.',
        ],
        [
            'id'          => 'graphic-design',
            'name'        => 'Graphic Design',
            'icon'        => 'graphic',
            'description' => 'Print and digital design for brochures, flyers, social media graphics, presentations, and marketing collateral.',
        ],
        [
            'id'          => 'ui-ux-design',
            'name'        => 'UI/UX Design',
            'icon'        => 'uiux',
            'description' => 'User-centered interface design and experience strategy for websites, web apps, and mobile applications.',
        ],
        [
            'id'          => 'web-development',
            'name'        => 'Web Development',
            'icon'        => 'webdev',
            'description' => 'Custom website development using modern technologies including HTML5, CSS3, JavaScript, PHP, and MySQL.',
        ],
        [
            'id'          => 'digital-marketing',
            'name'        => 'Digital Marketing',
            'icon'        => 'marketing',
            'description' => 'SEO optimization, content strategy, email campaigns, and social media management to grow your online presence.',
        ],
        [
            'id'          => 'website-maintenance',
            'name'        => 'Website Maintenance',
            'icon'        => 'maintenance',
            'description' => 'Ongoing support, security updates, performance optimization, content updates, and technical maintenance.',
        ],
    ],

    /* ============================================================
       Design System - CSS Variables Reference
       These are applied in /assets/css/main.css as :root {}
       This file documents the values for PHP-side access when needed
       ============================================================ */
    'design' => (object) [

        /* Colors */
        'colors' => (object) [
            'primary'       => '#0a0a0a',
            'primaryLight'  => '#2a2a2a',
            'accent'        => '#ff4d2e',
            'accentHover'   => '#e6391a',
            'bg'            => '#ffffff',
            'bgAlt'         => '#f6f6f6',
            'bgDark'        => '#0a0a0a',
            'text'          => '#1a1a1a',
            'textLight'     => '#666666',
            'textOnDark'    => '#f0f0f0',
            'border'        => '#e0e0e0',
            'borderLight'   => '#f0f0f0',
            'error'         => '#dc3545',
            'success'       => '#22c55e',
            'warning'       => '#f59e0b',
            'info'          => '#3b82f6',
        ],

        /* Typography */
        'typography' => (object) [
            'fontPrimary'   => "'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif",
            'fontHeading'   => "'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif",
            'fontMono'      => "'JetBrains Mono', 'Fira Code', 'SF Mono', monospace",
            'baseSize'      => '1rem',
            'scaleRatio'    => 1.25,
            'lineHeight'    => 1.6,
            'lineHeading'   => 1.15,
            'weightLight'   => 300,
            'weightRegular' => 400,
            'weightMedium'  => 500,
            'weightSemibold'=> 600,
            'weightBold'    => 700,
            'weightBlack'   => 900,
        ],

        /* Spacing Scale (4px base) */
        'spacing' => (object) [
            'unit'  => 4,
            'xs'    => '0.25rem',  // 4px
            'sm'    => '0.5rem',   // 8px
            'md'    => '1rem',     // 16px
            'lg'    => '1.5rem',   // 24px
            'xl'    => '2rem',     // 32px
            '2xl'   => '3rem',     // 48px
            '3xl'   => '4rem',     // 64px
            '4xl'   => '6rem',     // 96px
            '5xl'   => '8rem',     // 128px
        ],

        /* Border Radius */
        'radius' => (object) [
            'none'   => '0',
            'sm'     => '4px',
            'md'     => '8px',
            'lg'     => '12px',
            'xl'     => '16px',
            '2xl'    => '24px',
            'full'   => '9999px',
        ],

        /* Shadows */
        'shadows' => (object) [
            'none'   => 'none',
            'sm'     => '0 1px 2px rgba(0, 0, 0, 0.04)',
            'md'     => '0 4px 12px rgba(0, 0, 0, 0.08)',
            'lg'     => '0 10px 30px rgba(0, 0, 0, 0.1)',
            'xl'     => '0 20px 60px rgba(0, 0, 0, 0.12)',
            'inner'  => 'inset 0 2px 4px rgba(0, 0, 0, 0.05)',
        ],

        /* Transitions */
        'transitions' => (object) [
            'fast'   => '150ms ease',
            'base'   => '300ms ease',
            'slow'   => '500ms ease',
            'spring' => '400ms cubic-bezier(0.34, 1.56, 0.64, 1)',
        ],

        /* Layout */
        'layout' => (object) [
            'containerMax'    => '1200px',
            'containerNarrow' => '800px',
            'containerPadding'=> '1rem',
        ],

        /* Z-Index Scale */
        'zIndex' => (object) [
            'dropdown' => 100,
            'sticky'   => 200,
            'overlay'  => 300,
            'modal'    => 400,
            'toast'    => 500,
        ],
    ],

    /* ============================================================
       Responsive Breakpoints
       Used in CSS media queries in /assets/css/responsive.css
       ============================================================ */
    'breakpoints' => (object) [
        'sm'    => 640,
        'md'    => 768,
        'lg'    => 1024,
        'xl'    => 1280,
        '2xl'   => 1440,
        '3xl'   => 1920,
    ],

    /* ============================================================
       Favicon & PWA
       ============================================================ */
    'favicon' => (object) [
        'favicon16'   => '/assets/images/favicon/favicon-16.png',
        'favicon32'   => '/assets/images/favicon/favicon-32.png',
        'appleIcon'   => '/assets/images/favicon/apple-touch-icon.png',
        'android192'  => '/assets/images/favicon/icon-192.png',
        'android512'  => '/assets/images/favicon/icon-512.png',
        'manifest'    => '/site.webmanifest',
        'themeColor'  => '#0a0a0a',
        'bgColor'     => '#ffffff',
    ],

    /* ============================================================
       Form Configuration
       ============================================================ */
    'forms' => (object) [
        'contactRecipient' => 'hello@swapdesign.com',
        'contactSubject'   => 'New Inquiry from Swap Design Website',
        'enableHoneypot'   => true,
        'enableCsrf'       => true,
    ],

    /* ============================================================
       Feature Flags
       Toggle features on/off across the site
       ============================================================ */
    'features' => (object) [
        'blog'         => false,
        'portfolio'    => false,
        'testimonials' => false,
        'newsletter'   => false,
        'liveChat'     => false,
        'darkMode'     => true,
    ],

    /* ============================================================
       Analytics & Tracking Placeholders
       ============================================================ */
    'analytics' => (object) [
        'googleAnalyticsId' => '',  // G-XXXXXXXXXX
        'googleTagManagerId'=> '',  // GTM-XXXXXXX
        'facebookPixelId'   => '',
    ],

];
