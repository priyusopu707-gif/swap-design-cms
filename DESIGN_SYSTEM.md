# Swap Design System — Documentation

## Overview
The Swap Design System is a premium, dark-first glassmorphism UI kit designed for PHP 8+ environments without build tools. It uses standard CSS custom properties (tokens) as the source of truth, enabling easy theming and component reuse.

## 1. Design Tokens (`design-tokens.css`)
Loaded first to define variables available to all components.

### Core Token Families:
- **Colors**: Semantic roles (`--ds-primary`, `--ds-secondary`, `--ds-accent`) and surface levels (`--ds-dark-canvas`, `--ds-dark-base`, `--ds-dark-surface`).
- **Glass**: `background: var(--ds-glass-bg); backdrop-filter: blur(16px); border: 1px solid var(--ds-glass-border);`
- **Spacing**: 4px base scale (`--ds-space-4` to `--ds-space-120`).
- **Typography**: Inter (Display/Body), JetBrains Mono.
- **Motion**: `dur-fast` (150ms), `dur-base` (300ms), `dur-slow` (500ms). Easing: `ease-out-expo`, `ease-out-back`.

## 2. Utility Classes
- **Glassmorphism**: `.glass` applies backdrop-filter, border, and background. Variants: `.glass--elevated`, `.glass--glow`, `.glass--hoverable`.
- **Layout**: `.container`, `.container--narrow`, `.section`.
- **Flex/Grid**: `.u-flex`, `.u-grid`, `.grid--2`, `.grid--3`, etc.
- **Spacing**: `.u-mb-*`, `.u-mt-*` (margin modifiers).

## 3. Component System
- **Buttons**: `.btn` with modifiers:
    - Variants: `.btn--primary`, `.btn--secondary`, `.btn--outline`, `.btn--cta`, `.btn--accent`, `.btn--whatsapp`.
    - Sizes: `.btn--sm`, `.btn--lg`, `.btn--xl`.
- **Cards**: `.card` base with variants:
    - `.card--service`, `.card--portfolio`, `.card--testimonial`, `.card--pricing`, `.card--blog`, `.card--stat`, `.card--team`.
- **Forms**: `.form-group`, `.form-label`, `.input`, `textarea`, `select`, `.input-check`.

## 4. Animation Utilities
- **Reveals**: Elements with `[data-reveal]` automatically reveal on scroll (GSAP ScrollTrigger + IO hybrid).
- **Interactions**: 
    - `.tilt-card`: Mouse-move 3D perspective tilt.
    - `.magnetic`: Magnetic button hover with spring physics.
    - `.float`: Floating animation for decorative elements.
- **Performance**: All animations gated by `prefers-reduced-motion`.

## 5. Iconography
- SVG-based Lucide/SimpleIcons/Tabler icons via `.icon` class.
- Usage: `<span class="icon icon--md"><svg>...</svg></span>`

## 5c. Premium Homepage Foundation (Sprint 15E, homepage-scoped)

- **Container**: `.container { max-width:1200px; margin:0 auto; padding:0 24px }`
- **Section header**: `.section__header { text-align:center; max-width:760px; margin:0 auto 64px }`
- **Card system**: `.card-premium` (glass bg, border-radius 20px, padding 32, flex column, height 100%, hover lift+glow). Desc `flex-grow:1` ensures equal heights in grid.
- **Grid**: `.grid-premium { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 32px }`
- **Icon wrap**: `.cp-icon-wrap` fixed 32×32px, flex-shrink:0
- **Portfolio placeholders**: `getPlaceholderPortfolio()` (6 cards) shown when DB empty + `show_placeholders` config. Gradient `.pf-card__placeholder` thumbnail.
- **Testimonial placeholders**: `getPlaceholderTestimonials()` (3 reviews) shown when DB empty + `show_placeholders` config. Clearly labelled as dummy content.

## 5b. Premium Landing Patterns (Sprint 15D, page-scoped)

The homepage (`homepage.css`) adds premium landing-page patterns reusable as reference:

- **Layered depth**: `.hero__glass-layer`, `.hero__glass-layer--1/2/3` (floating blurred glass circles), `.hero__glow-pulse--primary/accent` (breathing gradient light).
- **Floating particles**: `.hero__particles` container + `.hero__particle` (8 spans, `particle-drift` keyframe, GPU transform/opacity only).
- **Animated badge**: `.hero__badge` + `.hero__badge-dot` (glass pill with pulsing success dot).
- **Glass stat card**: `.hero__stat-card` (glass surface, `::before` sheen, hover lift + glow).
- **Card reflection**: `.problem-card::after`, `.work-me-card::after`, `.industry-card::after`, `.tech-item::after` (diagonal light sweep on hover).
- **Gradient border reveal**: `.card--service::before` (conic gradient via CSS `mask` + `mask-composite: exclude`).
- **Section dividers**: `.section--divider-top`, `.section--divider-glow` (top gradient hairline).
- **Empty state icon wrap**: `.empty-state__icon-wrap` (gradient tile + `icon-ring-pulse` keyframes) + `.empty-state::before` (rotating conic gradient).
- **Brand icons**: `simpleIcon()` in `HomepageRenderer.php` returns official Simple Icons filled SVG paths (WordPress, WooCommerce, Shopify, Wix, PHP, MySQL, HTML5, CSS3, JavaScript, Figma). Fallback Lucide-style via `inlineIcon()`.

## 5d. Premium Creative Landing Polish (Sprint 16, homepage-scoped)

Homepage `homepage.css` adds premium creative-freelancer landing refinements (reusable as reference):

- **Exaggerated minimalism**: Oversized hero heading `clamp(3rem, 7vw, 5.5rem)` with `font-weight:900`, `letter-spacing:-0.04em`, subtle text-shadow for depth.
- **Conic gradient ring badge**: `.experience__badge` uses `background-origin:border-box` + `background-clip:padding-box, border-box` to render a rotating conic brand ring around a glass badge.
- **Brand-tinted composition accents**:
  - `.problem-card--left` / `--right`: left=red / right=orange 3px side borders.
  - `.why-choose__stacked-col .card--service`: 3px red left border, hover slide.
  - `.work-me-card__node`: brand border + `node-pulse` keyframe glow.
- **Gradient hover depth**: `.services__featured`, `.portfolio__featured .pf-card--featured`, `.testimonials__featured .testimonial-card--featured` get layered brand glass + glow shadows.
- **CTA visual orbs**: `.cta__visual-shape--1/2/3` radial brand blobs with `filter:blur(20px)`.
- **Contact premium panel**: gradient glass + large soft brand glow.
- **Scroll progress bar**: `.scroll-progress` fixed top brand-gradient bar (width set by `homepage.js` `initScrollProgress`).

### Premium JS Enhancements (homepage.js)
- `initSectionReveal()`: GSAP ScrollTrigger staggered `[data-reveal]` reveal per section (fallback to IntersectionObserver).
- `initScrollProgress()`: brand-gradient scroll progress bar.
- All gated by `prefers-reduced-motion`.

## 6. Global Theme Toggle
- Default: Dark mode.
- Light mode (opt-in): Set `data-theme="light"` on `<html>`.

---
*For more details, see individual files in `/assets/css/components/design-system/`.*
