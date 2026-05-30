# QA Checklist

Run this checklist before tagging a major release (`X.0.0`). For minor releases (`X.Y.0`), focus on the sections relevant to what changed.

## Environment matrix

| Layer | Targets to test |
|-------|-----------------|
| Browsers | Chrome current + previous, Firefox current + previous, Safari current + previous, Edge current |
| WordPress | 6.0 (minimum), latest stable |
| WooCommerce | 7.0 (minimum), latest stable, HPOS on, HPOS off |
| Elementor | 3.5 (minimum), latest stable |
| PHP | 7.4, 8.0, 8.1, 8.2, 8.3 |
| Mobile | iOS Safari (last 2 versions), Android Chrome (last 2 versions) |

## Smoke test

A 5-minute pass to catch obvious regressions.

- [ ] Activate the plugin on a fresh WP install with WC + Elementor — no fatal errors
- [ ] Welcome notice appears, dismissable
- [ ] `WooCommerce → Product Builder` settings page loads without errors
- [ ] Save → Reset → Save again on each tab works
- [ ] Drop each of the 3 widgets onto a product page — they render
- [ ] Click a color swatch on the Variation Swatches widget — Gallery image swaps + Add to Cart price/stock updates
- [ ] AJAX Add to Cart succeeds — fragment refresh works (theme mini-cart updates)
- [ ] Check for Updates link returns "up to date"

## Feature test — full pass

### Add to Cart widget
- [ ] Simple product: AJAX add succeeds, Buy Now redirects to checkout
- [ ] Variable product: button stays disabled until all attributes selected
- [ ] Grouped product: table renders, single submit adds all children
- [ ] External product: button links to external URL with target=_blank
- [ ] Out-of-stock product: respects Disable / Hide / Show Message setting
- [ ] Backorder product: shows "Available on backorder" notice
- [ ] Low-stock product (≤ threshold): shows "Only N left" warning
- [ ] Network failure: error message + Try Again button work
- [ ] Quantity stepper enforces min/max from product settings
- [ ] All 3 success behaviors render correctly (Restore / Stay / Checkmark)

### Variation Swatches widget
- [ ] Color swatches render with correct hex
- [ ] Split-color (two-tone) swatches render gradient correctly
- [ ] Image swatches show the term image
- [ ] Label / Button swatches render with correct typography
- [ ] Smart-greying disables impossible combinations
- [ ] Out-of-stock variations show distinct state
- [ ] Reset link clears selection
- [ ] Auto-select first variation toggle works
- [ ] Per-attribute display override on the widget works
- [ ] URL param sync round-trips: select → URL updates → refresh → selection restored
- [ ] Browser back/forward navigates between selections
- [ ] Hidden attributes are auto-resolved server-side (cart still works)

### Product Gallery widget
- [ ] All 3 layouts render correctly (vertical-left, vertical-right, horizontal)
- [ ] Mobile: vertical layouts collapse to stacked
- [ ] Hover zoom works (desktop) — replaced by tap-to-zoom on touch devices
- [ ] Lightbox: ESC closes, arrow keys navigate, click-backdrop closes, focus restored
- [ ] Variation image swap: click swatch → main image updates with cross-fade
- [ ] Reset variation → main image reverts to gallery
- [ ] Thumbnails render with skeleton shimmer while loading
- [ ] Sale / Out-of-Stock / Featured badges render correctly when applicable
- [ ] **Full-resolution image option** works for main image, thumbnails, and lightbox independently
- [ ] Aspect ratio control (1:1 / 4:3 / 3:4 / 16:9 / auto) renders correctly

## Admin test

- [ ] Settings page: tabs save independently
- [ ] Reset This Tab to Defaults: confirms via JS dialog, then resets only that section
- [ ] Plugins list: Settings + Docs + Check for Updates row links work
- [ ] Welcome notice dismisses cleanly
- [ ] Per-attribute term meta: color picker initializes, image upload works, save round-trips
- [ ] Term list table: Swatch column shows correct preview per type
- [ ] Per-product overrides: each section saves and restores correctly
- [ ] Reset all overrides: confirms via JS dialog, clears the meta row
- [ ] HPOS on: product save still works, overrides persist

## Accessibility

- [ ] axe DevTools clean run on each widget (no violations)
- [ ] axe DevTools clean run on settings page + product builder tab
- [ ] Tab key navigates through swatches in the right order
- [ ] Arrow keys move within a swatch radiogroup; Home / End jump to ends
- [ ] Enter / Space activates a swatch
- [ ] Screen reader announces "[Color name], [Size name]. In stock." after selection (test with VoiceOver / NVDA)
- [ ] All actionable elements have visible focus rings
- [ ] `prefers-reduced-motion: reduce` disables all animations
- [ ] Color contrast: swatch borders, labels, prices ≥ 4.5:1 for text, 3:1 for UI

## Performance

- [ ] Lighthouse score ≥ 90 on a real product page (Mobile + Desktop)
- [ ] No console errors on any tested page
- [ ] No console warnings about deprecated APIs
- [ ] Plugin assets load only on pages where they're needed (verify in Network tab — settings page assets shouldn't load on the front-end)

## Compatibility

- [ ] Storefront theme: widgets render correctly
- [ ] Astra theme: widgets render correctly
- [ ] Twenty Twenty-Four theme: widgets render correctly
- [ ] WooCommerce blocks: no conflict on cart / checkout pages
- [ ] Cart fragments update correctly in theme mini-cart
- [ ] No conflict with another swatches plugin (warning notice shown, plugin deactivates gracefully)

## i18n / RTL

- [ ] `.pot` file regenerates cleanly (`bin/make-pot.sh`)
- [ ] All admin strings translatable
- [ ] All front-end strings translatable
- [ ] RTL languages (Hebrew / Arabic) render correctly — no broken layouts, no mirrored elements

## Auto-updater

- [ ] Tag a test version (e.g. `v0.9.99`)
- [ ] GitHub Action builds and publishes a release
- [ ] Plugins screen shows "Update available" within 12 hours (or instantly via "Check for Updates")
- [ ] View Details modal shows correct version, banner, icon, changelog
- [ ] Update click downloads, installs, activates the new version
- [ ] Settings + per-attribute + per-product data preserved through update

## Sign-off

- [ ] All sections above checked
- [ ] Changelog updated with full per-version notes
- [ ] Stable tag bumped in `readme.txt`
- [ ] `Version:` header bumped in main plugin file
- [ ] `ZPB_VERSION` constant matches
- [ ] `git tag vX.Y.Z && git push origin vX.Y.Z`
