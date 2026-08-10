# Sprint 15E — Premium Homepage Audit Report

**Date:** 2026-08-07
**Version:** 1.5.5
**Scope:** Full premium homepage design audit rebuild. No CMS architecture/routing/content/order changes.

---

## Executive Summary

Transformed Swap Design homepage from "basic admin template" feel to premium freelancer/agency landing page through systematic audit-and-fix of every section. Key problems identified and fixed: inconsistent grid systems across sections, invalid CSS tokens causing zero padding on cards, non-equal card heights, oversized inconsistent icons, missing placeholder content for empty sections.

---

## Audit Findings

### Problem: Card Padding Zero on problem/work-me cards
**Root cause:** CSS used `padding: var(--ds-space-36)` but `--ds-space-36` was never defined in `design-tokens.css`. Valid scale: 4,8,12,16,20,24,32,40,48,64,80,96,120. Undefined variable → declaration dropped → cards rendered with zero padding.

**Fix:** Changed to `var(--ds-space-32)` across problem-card and work-me-card.

### Problem: Inconsistent grids across sections
| Section | Before | After |
|---------|--------|-------|
| Problems | minmax(280px), gap:24 | minmax(300px), gap:32 |
| Why Work | minmax(260px), gap:24 | minmax(280px), gap:32 |
| Industries | minmax(200px), gap:20 | minmax(240px), gap:24 |
| Technologies | minmax(120px), gap:16 | minmax(140px), gap:24 |
| Why Choose | varied per breakpoint | single-flow auto-fit, gap:32 |

### Problem: Cards not equal height
**Cause:** Cards lacked `display:flex; flex-direction:column; height:100%`. Shorter descriptions left varying empty space below.

**Fix:** Added flex column + height 100% + `flex-grow:1` on descriptions across all card types (problem, work-me, industry, testimonial).

### Problem: Inconsistent icon sizes
**Fix:** `.cp-icon-wrap` normalized to `32px` × `32px` with `flex-shrink:0`. Previously was `100%` of parent which varied per card type.

### Problem: Empty portfolio/testimonials felt like broken CMS
**Fix:** Added `show_placeholders` config flag and placeholder data methods.

---

## Portfolio Placeholders

6 realistic dummy projects designed to be instantly replaced by CMS data:

| ID | Title | Category | Description |
|----|-------|----------|-------------|
| 9001 | Solaris Energy Brand Identity | Branding | Complete brand identity for renewable energy startup |
| 9002 | FreshBite Restaurant Website | Website Design | Mobile-first restaurant website with online menu |
| 9003 | TechNova SaaS Dashboard | UI / UX | Enterprise dashboard with data visualisation |
| 9004 | GreenLeaf Organic E-Commerce | Website Design | WooCommerce store with subscription model |
| 9005 | CloudBridge IT Services | Graphic Design | Corporate brochure and social media graphics |
| 9006 | EduPath Online Learning Platform | UI / UX | LMS interface with course cards and progress |

- Thumbnail: gradient placeholder with centered image icon (`.pf-card__placeholder`)
- Shown only when DB returns no published portfolio items AND `show_placeholders` enabled
- Replaced automatically once real CMS data exists

---

## Testimonial Placeholders

3 clearly-labelled dummy testimonials:

| ID | Client | Role | Company | Rating | Quote |
|----|--------|------|---------|--------|-------|
| 9101 | Rajesh Kumar | Founder | Solaris Energy | 5/5 | Excellent work on our company website... |
| 9102 | Priya Sharma | Director | GreenLeaf Organics | 5/5 | Swap Design delivered our e-commerce website on time... |
| 9103 | Amit Deshpande | CTO | TechNova Solutions | 5/5 | Our SaaS dashboard redesign improved user engagement... |

- Avatar: initial letter placeholder (no image)
- Shown only when DB returns no published testimonial entries AND `show_placeholders` enabled
- Replaced automatically once real CMS testimonials exist

---

## Files Modified

| File | Change |
|------|--------|
| `assets/css/homepage.css` | Foundation block (container 1200px, section header center, premium card system), fixed `--ds-space-36` tokens, normalized grids/gaps, equal-height cards, icon normalization, portfolio placeholder thumbnail |
| `includes/content/HomepageRenderer.php` | `getPlaceholderPortfolio()` + `getPlaceholderTestimonials()` methods, portfolio card markup with gradient placeholder + description, testimonial fallback wiring |
| `includes/content/HomepageManager.php` | `show_placeholders` flag on portfolio_preview + testimonials config |

---

## Responsive Behavior

| Viewport | Cards | Grid |
|----------|-------|------|
| 1920px | 4 per row | Auto-fit, even spacing |
| 1440px | 3-4 per row | Auto-fit, even spacing |
| 1280px | 3 per row | Auto-fit, even spacing |
| 1024px | 2 per row | Auto-fit, even spacing |
| 640px | 1 per row | Single column |

All grids use `auto-fit` with `minmax()` so cards flow naturally and wrap correctly at every breakpoint.

---

## Quality Checklist

| Criterion | Status |
|-----------|--------|
| Cards equal height | ✅ All card types |
| Cards equal padding | ✅ All `--ds-space-32` |
| Cards equal icon sizing | ✅ `cp-icon-wrap` 32px |
| Grid consistent minmax | ✅ Unified per section |
| Grid gaps uniform | ✅ `--ds-space-32`/`--ds-space-24` |
| No invalid CSS tokens | ✅ Fixed `--ds-space-36` → `--ds-space-32` |
| Typography hierarchy consistent | ✅ Section titles 800 weight |
| Centered content | ✅ 1200px container |
| Portfolio placeholders present | ✅ 6 realistic cards |
| Testimonial placeholders present | ✅ 3 labelled reviews |
| CMS auto-replaces placeholders | ✅ When DB data exists |
| PHP lint clean | ✅ Both files |

---

*Report generated for Sprint 15E completion.*
