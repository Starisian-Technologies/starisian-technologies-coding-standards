# CSS / Build Limits Standard

**SPARXSTAR Platform Engineering — CSS and Build Constraints**

Starisian Technologies

---

This document is the CSS and build limits standard for the SPARXSTAR platform. It governs all stylesheets, CSS architecture, and asset build output written under SPARXSTAR governance.

All rules in the [Standards Handbook](standards-handbook.md) apply in full. This document adds CSS- and build-specific requirements on top of them.

---

## Design First Principle

**Design for 2G/3G networks and low-end Android devices first.**

CSS is not free. Every byte of CSS is downloaded, parsed, and applied on devices with limited CPU, limited RAM, and metered network connections. CSS that feels weightless on a MacBook can cause visible jank and battery drain on a low-end Android phone. Heavy visual effects (blur, shadows, animations) consume GPU resources that do not exist on constrained hardware.

---

# 1. Bundle Size — Hard Limits

| Asset | Limit | Enforcement |
| :---- | :---- | :---- |
| CSS bundle (total per page) | 50 KB gzipped | CI build failure |
| CSS per component | 5 KB unminified | Code review flag |
| Critical (above-fold) CSS | 14 KB unminified | Inline or preloaded |

| **FAIL** | CSS bundle exceeds 50 KB gzipped |
| :---- | :---- |

---

# 2. Prohibited CSS — Performance

The following CSS properties are prohibited in production stylesheets on performance grounds. They consume GPU, increase paint cost, and cause visible jank on constrained devices.

| Property / Pattern | Reason | Production Status |
| :---- | :---- | :---- |
| `filter: blur(...)` | GPU-intensive. Triggers layer promotion. | Prohibited |
| `backdrop-filter: blur(...)` | Extremely GPU-intensive. Unsupported on many Android devices. | Prohibited |
| `box-shadow` with spread > 4px | Increases paint area significantly. | Prohibited |
| `text-shadow` on body text | Paint cost with no readability gain. | Prohibited |
| `animation` on layout properties (`width`, `height`, `top`, `left`) | Triggers layout recalculation on every frame. | Prohibited |
| `transition` on layout properties | Same as above. | Prohibited |
| `@keyframes` animating non-composited properties | Forces main thread paint. | Prohibited |

*Composited properties (`transform`, `opacity`) are permitted for animation. They run on the GPU compositor thread and do not trigger layout or paint.*

| **FAIL** | `filter: blur` in production CSS |
| :---- | :---- |
| **FAIL** | `backdrop-filter` in production CSS |
| **FAIL** | `box-shadow` with spread radius > 4px in production CSS |
| **FAIL** | animation or transition on layout properties |

---

# 3. Architecture

## 3.1 Methodology

- (M) BEM (Block Element Modifier) naming convention for component-scoped styles
- (M) CSS custom properties (variables) for all design tokens — colors, spacing, typography scales
- (X) Inline styles in HTML except for truly dynamic computed values that cannot be expressed as classes
- (X) `!important` except in utility classes where specificity override is the explicit intent

## 3.2 Specificity

- (M) Maximum specificity: one class selector (0,1,0)
- (M) ID selectors forbidden in stylesheets — use class selectors
- (X) Deeply nested selectors (> 3 levels) — flat selector architecture preferred

```css
/* Required — flat, BEM-style */
.spx-card { ... }
.spx-card__title { ... }
.spx-card--featured { ... }

/* Forbidden — high specificity, tightly coupled */
#app .content .card .title span { ... }
```

## 3.3 Design Tokens

All visual values (colors, spacing, radii, type scale) must be expressed as CSS custom properties. Hard-coded hex values, pixel values, or font sizes in component stylesheets are prohibited.

```css
/* Required — design tokens */
:root {
  --spx-color-primary: #1a56db;
  --spx-space-4: 1rem;
  --spx-text-base: 1rem;
  --spx-radius-md: 4px;
}

.spx-button {
  background: var(--spx-color-primary);
  padding: var(--spx-space-4);
  border-radius: var(--spx-radius-md);
}

/* Forbidden — magic values */
.spx-button {
  background: #1a56db;
  padding: 16px;
  border-radius: 4px;
}
```

---

# 4. Responsive Design

- (M) Mobile-first — base styles target smallest screen, media queries expand upward
- (M) Breakpoints expressed as named custom media tokens (for example via `@custom-media` and a build step such as PostCSS Custom Media) — never hard-coded magic numbers in media queries
- (M) Touch targets minimum 44 × 44 CSS pixels (WCAG 2.5.5)
- (X) Fixed pixel layouts that do not adapt to viewport
- (X) `vh` units for critical layout heights without fallback (iOS Safari viewport quirk)

```css
/* Breakpoint token defined once in the build pipeline — e.g., via PostCSS @custom-media plugin */
/* @custom-media --spx-bp-md (min-width: 768px); */

/* Required — mobile-first, breakpoint via named token */
.spx-nav {
  display: flex;
  flex-direction: column;
}

@media (--spx-bp-md) {
  .spx-nav {
    flex-direction: row;
  }
}

/* Forbidden — hard-coded magic number */
/* @media (min-width: 768px) { ... } */
```

---

# 5. Typography

- (M) Base font size uses `rem` — never `px` for text (respects user browser settings)
- (M) Line height expressed as unitless ratio
- (M) Minimum body font size: 1rem (16px at browser default)
- (M) Minimum contrast ratio: 4.5:1 for normal text, 3:1 for large text (WCAG AA)
- (X) `px` units for font sizes
- (X) Fixed `line-height` in pixels

```css
/* Required */
body {
  font-size: 1rem;
  line-height: 1.5;
}

/* Forbidden */
body {
  font-size: 16px;
  line-height: 24px;
}
```

---

# 6. Loading Performance

## 6.1 Critical CSS

- (M) Styles required for above-the-fold content are either inlined in `<head>` or loaded with `rel="preload"` — they must not block render
- (M) Non-critical stylesheets loaded asynchronously

## 6.2 Font Loading

- (M) Web fonts use `font-display: swap` or `font-display: optional`
- (M) Font files served from the same origin or a trusted CDN with appropriate `Cache-Control`
- (X) Blocking font loads without `font-display` strategy
- (X) Loading more than 2 web font families per page

## 6.3 Image and Background

- (M) Background images use `srcset`-equivalent where CSS supports it (via `image-set()`) or serve appropriately sized images
- (X) Large decorative background images on mobile — use `@media` to suppress or downscale

---

# 7. Accessibility

- (M) Focus styles must be visible and not overridden with `outline: none` without a custom replacement
- (M) Color alone must not convey meaning (WCAG 1.4.1)
- (M) Motion-sensitive animations must be wrapped in `@media (prefers-reduced-motion: reduce)`
- (X) `outline: none` or `outline: 0` without a visible focus replacement

```css
/* Required — respect reduced motion preference */
@media (prefers-reduced-motion: reduce) {
  .spx-transition {
    transition: none;
    animation: none;
  }
}
```

---

# 8. Linting

- (M) Stylelint enforced in CI with SPARXSTAR config
- (M) Lint failures block merge
- (X) Auto-fix in CI — report mode only
- (M) Rules enforced:
  - No unknown properties
  - No duplicate selectors
  - No empty rules
  - Consistent property order (logical properties preferred)
  - No vendor prefixes in source (use PostCSS autoprefixer in build pipeline)

---

# 9. Build Pipeline

- (M) PostCSS with autoprefixer for vendor prefix generation — never hand-written vendor prefixes
- (M) CSS minification in production build
- (M) Source maps generated for production builds (not served publicly)
- (M) Bundle size check at build time — fail build if 50 KB gzipped limit exceeded
- (X) Unused CSS in production bundle (use PurgeCSS or equivalent)

---

Version: 2.0 | Starisian Technologies | May 2026

Applies to: All CSS and stylesheet code governed by SPARXSTAR standards.
