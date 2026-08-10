# Layout Composition Report — Sprint 15H

**Date:** 2026-08-07  
**Scope:** Homepage layout composition redesign. Unique visual rhythm per section.  
**Constraints:** Design System tokens, colors, and typography tokens NOT modified. CMS architecture, content, routing, and DB schema preserved.

---

## Problem Statement

Homepage sections follow a repetitive template: **Heading → Subtitle → Grid**. Services, Problems, Why Work, Industries, Technologies, Portfolio, Testimonials, and Contact all use the same centered-header + grid-of-cards pattern. Visual rhythm is flat; each section looks like a sibling of the previous.

**Goal:** Every section gets a unique composition. Content stays the same. Design System stays intact.

---

## Section-by-Section Composition Map

### 1. Hero — Asymmetrical Split Layout ✅ (ALREADY DONE)
**Current:** 1.05fr / 0.95fr split with content left, visual right.  
**Verdict:** Already unique. No changes needed.  
**CSS classes used:** `.hero__container` (grid), `.hero__content`, `.hero__image-wrap`

---

### 2. About — Editorial Split with Accent Bar
**Current:** Image left, content right — standard split.  
**New composition:** Full-bleed image (left 60%) with inset content panel (right 40%) overlapping the image edge with a glass surface. Left-side accent bar (primary color, 4px wide) runs vertically alongside the content for visual distinction.  
**Changes:**
- `.about__grid` → `grid-template-columns: 1.6fr 1fr;` with negative margin overlap
- `.about__content` → `.about__content--inset` with `margin-left: -60px`, glass background, accent bar `::before`
- `.about__image-wrap` → full height with `object-fit: cover`
- **Keep:** Image shown if configured, fallback to no-image state

---

### 3. Experience — Centered Radial Stats
**Current:** 4-column grid of stat cards.  
**New composition:** 4 stat cards arranged in a 2×2 grid with a large brand badge in the center. Cards connect via subtle glass lines to the center.  
**Changes:**
- Wrap in `.experience__radial` container
- Center badge: `.experience__badge` (large icon + brand name)
- 2×2 grid around center: `.experience__stats` (absolute positioned or CSS grid with center hole)
- **Keep:** 4 items only (fixed count from CMS)
- **Keep:** Counter animation (`data-counter`)

---

### 4. Services — Featured + Support Grid
**Current:** Uniform 3-column grid of 6 service cards.  
**New composition:** 1 featured service card (span 2 columns, larger) + 5 supporting cards in a 2-row × 3-column grid below. First service from CMS becomes featured; order preserved from config.  
**Changes:**
- `.services__grid` → `.services__featured-grid`
- First card: `.services__featured` with `grid-column: span 2`, larger padding, featured badge
- Remaining 5 cards: standard `.card--service` in auto-fit grid
- **Keep:** All service content, icons, links preserved
- **Keep:** View-all CTA

---

### 5. Why Choose — Featured Card + Stacked Supporting
**Current:** Image + card grid (or centered cards-only).  
**New composition:** Left side — 1 large featured card with full-width icon and description. Right side — 3 smaller stacked supporting cards vertically.  
**Changes:**
- `.why-choose__grid--featured` with `grid-template-columns: 1.2fr 0.8fr`
- Left: `.why-choose__featured-card` (span 2 rows, large)
- Right: `.why-choose__stacked` (3 cards stacked, equal height)
- **Keep:** Image if configured (moves to top of featured card as background)
- **Keep:** All icon/content data

---

### 6. Problems — Alternating Masonry
**Current:** Uniform auto-fit grid.  
**New composition:** Cards alternate left/right offset on each row, creating a zigzag/masonry rhythm. Odd rows flush-left, even rows flush-right with asymmetric spacing. Cards have varying heights based on content.  
**Changes:**
- `.problems__grid--masonry` with CSS `grid-auto-flow: dense`
- `.problem-card--left` / `.problem-card--right` modifiers for alternating placement
- Varying card heights via `row-span` (1 or 2 rows)
- **Keep:** All 8 problem cards
- **Keep:** Icon, title, description content

---

### 7. Why Work — Vertical Timeline
**Current:** 4 cards in auto-fit grid.  
**New composition:** Vertical timeline with a central connecting line. Cards alternate left/right of the line. Each card shows icon, title, description in a connected flow.  
**Changes:**
- `.work-me__timeline` with `::before` vertical line
- `.work-me-card--left` / `.work-me-card--right` with positioning relative to line
- Line connects all 4 items with subtle glow
- **Keep:** All 4 benefit cards content
- **Keep:** Icon/title/description

---

### 8. Industries — Logo Cloud with Featured Highlights
**Current:** Uniform auto-fit grid of 10 industry cards.  
**New composition:** 3 featured industries (larger, elevated glass cards) at top in a row. Below them — a dense "logo cloud" of remaining industries in smaller compact tiles.  
**Changes:**
- `.industries__featured` — 3 large cards at top (span full width)
- `.industries__cloud` — remaining 7 in compact grid below
- Featured cards: larger padding, bigger icons, elevated shadow
- Cloud tiles: smaller, dense, reduced padding
- **Keep:** All 10 industry cards content

---

### 9. Technologies — Floating Wall with Parallax + Stagger
**Current:** Uniform auto-fill grid (140px min).  
**New composition:** Organic staggered grid with varying sizes (small/medium/large tiles) creating a "wall" feel. Parallax on scroll (existing `data-parallax`) + stagger reveal on scroll (existing GSAP).  
**Changes:**
- `.technologies__wall` with mixed tile sizes
- `.tech-item--small` (1 tile), `.tech-item--medium` (2 cols), `.tech-item--large` (2×2)
- Apply existing `data-parallax` to container
- Apply existing stagger reveal to wall
- **Keep:** All 12 technology items
- **Keep:** Simple Icons brand SVGs
- **Keep:** Brand color hints

---

### 10. Process — Stepped Vertical Timeline
**Current:** 5-column horizontal timeline.  
**New composition:** Vertical timeline with numbered nodes on the left, content on the right. Each step is a row with connector line.  
**Changes:**
- `.process__vertical` with `display: flex; flex-direction: column`
- Each step: `.process-step` with number circle (left) + content (right)
- Connecting line between steps via `::before` on step wrapper
- Responsive: on mobile, vertical collapses to single column with numbers above
- **Keep:** All 5 process steps content

---

### 11. Portfolio — Featured Project + Secondary Grid
**Current:** 3-column grid, optional featured_first layout.  
**New composition:** 1 large featured project card at top (full width). Below — 4 secondary projects in a 2×2 grid. Featured = `is_featured = 1` from DB, or first item if none featured.  
**Changes:**
- `.portfolio__featured` — single large card (aspect-ratio 16/9 or 3/2)
- `.portfolio__secondary` — 2×2 grid of 4 smaller cards
- Featured card: larger image, prominent title, view CTA
- Secondary cards: standard pf-card
- **Keep:** Filter buttons (All / categories)
- **Keep:** View-all CTA

---

### 12. Testimonials — Featured + Two Secondary
**Current:** 3-column grid or carousel.  
**New composition:** 1 large featured testimonial card centered at top. Below — 2 smaller testimonial cards side by side. Featured gets larger quote, prominent avatar.  
**Changes:**
- `.testimonials__featured` — single large card (span full width)
- `.testimonials__secondary` — 2 cards side by side below
- Featured: larger padding, bigger quote mark, prominent avatar
- Secondary: standard size, slightly reduced padding
- **Keep:** Carousel option if JS-enabled (featured scrolls first)
- **Keep:** Rating stars, avatar, name/role

---

### 13. FAQ — Side-by-Side Category + Questions
**Current:** Single-column accordion.  
**New composition:** Two-column layout. Left column: category filter pills (horizontal scrollable). Right column: accordion list filtered by selected category.  
**Changes:**
- `.faq__side-by-side` with `grid-template-columns: 240px 1fr`
- Left: `.faq__categories` (sticky pill list)
- Right: `.faq__list` (existing accordion, filters on pill click)
- On mobile: pills become horizontal scroll above accordion
- **Keep:** Search functionality
- **Keep:** All FAQ items and answers
- **Keep:** Schema markup

---

### 14. CTA — Split Panel with Visual
**Current:** Centered text + buttons on dark background.  
**New composition:** Left panel = copy + CTAs. Right panel = glass-morphed visual element (abstract gradient shape or subtle pattern). Centered divider line between panels.  
**Changes:**
- `.cta__split` with `grid-template-columns: 1fr 1fr`
- Left: existing heading, description, buttons
- Right: `.cta__visual` with abstract gradient shapes (CSS-only, no image needed)
- Center divider: subtle glass line
- On mobile: single column, visual below copy
- **Keep:** WhatsApp button if enabled

---

### 15. Contact — Premium Glass Panel
**Current:** Grid of contact method cards.  
**New composition:** Single large glass panel with contact methods listed vertically on the left. Right side = compact info panel with WhatsApp/Phone CTA buttons prominent.  
**Changes:**
- `.contact__premium-panel` — single elevated glass card
- Left: contact methods (email, phone, WhatsApp, location, availability) as inline rows
- Right: CTA buttons (WhatsApp primary, Phone secondary)
- Premium glass effect: stronger border, subtle gradient background
- **Keep:** All contact config values
- **Keep:** WhatsApp integration

---

## CSS Class Additions Summary

| Section | New CSS Classes |
|---------|----------------|
| About | `.about__content--inset`, `.about__accent-bar` |
| Experience | `.experience__radial`, `.experience__badge`, `.experience__stats` |
| Services | `.services__featured-grid`, `.services__featured` |
| Why Choose | `.why-choose__grid--featured`, `.why-choose__featured-card`, `.why-choose__stacked` |
| Problems | `.problems__grid--masonry`, `.problem-card--left`, `.problem-card--right` |
| Why Work | `.work-me__timeline`, `.work-me-card--left`, `.work-me-card--right` |
| Industries | `.industries__featured`, `.industries__cloud` |
| Technologies | `.technologies__wall`, `.tech-item--small`, `.tech-item--medium`, `.tech-item--large` |
| Process | `.process__vertical`, `.process-step` |
| Portfolio | `.portfolio__featured`, `.portfolio__secondary` |
| Testimonials | `.testimonials__featured`, `.testimonials__secondary` |
| FAQ | `.faq__side-by-side`, `.faq__categories` |
| CTA | `.cta__split`, `.cta__visual` |
| Contact | `.contact__premium-panel` |

---

## PHP Renderer Changes Summary

| Method | Key Structural Change |
|--------|----------------------|
| `renderAbout()` | Add `--inset` class to content div; add accent bar div |
| `renderExperience()` | Wrap in radial container; add center badge div |
| `renderServices()` | First card gets `services__featured`; rest in normal grid |
| `renderWhyChoose()` | Split into featured card (left) + stacked cards (right) |
| `renderProblemsSolve()` | Add `--masonry` class; alternate left/right classes per card |
| `renderWhyWorkMe()` | Wrap in timeline container; alternate left/right classes |
| `renderIndustries()` | First 3 cards get `--featured`; rest in cloud grid |
| `renderTechnologies()` | Add mixed-size classes (small/medium/large) based on index |
| `renderProcess()` | Change to vertical step layout with connector |
| `renderPortfolio()` | First item featured (full width); rest in 2×2 grid |
| `renderTestimonials()` | First item featured (large); rest in secondary row |
| `renderFaq()` | Add category pills wrapper; keep accordion |
| `renderCta()` | Split into two-panel layout with visual |
| `renderContact()` | Single panel with inline contact methods + CTA buttons |

---

## Unchanged (Preserved)

- Design System tokens: `--ds-*` variables untouched
- Color palette: all brand colors preserved
- Typography: font sizes, weights, line-heights unchanged
- CMS configuration: `homepage_sections` table schema unchanged
- Content: all approved copy, titles, descriptions preserved
- JavaScript: existing reveal/parallax/tilt/counter scripts unchanged
- PHP architecture: HomepageManager, SectionManager, RenderingEngine untouched
- Database: no schema changes

---

## Visual Rhythm Map

```
Hero        → Asymmetrical split (text | visual)
About       → Editorial overlap (image | inset content)
Experience  → Radial stats (center badge | 4 cards)
Services    → Featured + support (large | 5 small)
Why Choose  → Featured card + stacked (large | 3 small)
Problems    → Alternating masonry (zigzag rhythm)
Why Work    → Vertical timeline (line | alternating cards)
Industries  → Featured + cloud (3 large | 7 small)
Technologies→ Floating wall (mixed sizes, staggered)
Process     → Vertical steps (numbers | content)
Portfolio   → Featured project (large | 2×2 grid)
Testimonials→ Featured quote (large | 2 small)
FAQ         → Side-by-side (pills | accordion)
CTA         → Split panel (text | visual)
Contact     → Premium panel (methods | CTAs)
```

**Result:** 15 sections, 15 unique layouts. Zero grid repetition. Strong visual rhythm.

---

## Approval Required

Report generated for review. Awaiting approval to implement.
