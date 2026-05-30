# Using the Widgets

Reference for the three Elementor widgets.

## Widget category

In the Elementor panel, find the **Zymarg Product Builder** category. It contains:

- Product Gallery
- Variation Swatches
- Add to Cart

## Product Source (all widgets)

Every widget has a Product Source control:

- **Current Product** *(default)* — auto-detects the product from the page context (single product page, product loop, etc.)
- **Pick a Product** — manually select a product. Useful for landing pages, featured product blocks.

## Product Gallery

### Layouts

| Layout | Description |
|--------|-------------|
| Vertical Thumbs (left) | Thumbnails column on the left of main image (Amazon-style) |
| Vertical Thumbs (right) | Thumbnails column on the right |
| Horizontal Thumbs | Thumbnails in a row below main image |

On mobile (< 768px), vertical layouts auto-collapse to "main image on top, thumbs in a row below".

### Image resolutions

- **Main Image Resolution**: WC Single (default) / Thumbnail / Medium / Medium Large / Large / **Full Resolution**
- **Thumbnail Resolution**: WC Gallery Thumbnail / WC Thumbnail / WP Thumbnail / Medium
- **Lightbox / Zoom Image Resolution**: Large / **Full Resolution** (best for sharp zoom)

Pick "Full Resolution" when you want maximum sharpness on retina screens; the trade-off is bandwidth.

### Behavior controls

- **Hover Zoom** — magnifies the main image on hover (or tap-to-zoom on touch devices)
- **Zoom Level** — slider 1.2x – 4x
- **Click to Open Lightbox** — built-in custom lightbox; ESC and arrow keys work
- **Swap Main Image on Variation** — when a swatch is picked, the main image becomes the variation image
- **Featured Image First** — controls whether the WooCommerce featured image is the first thumb

### Badges

Three configurable badges with text and position (4 corners):
- **Sale** — when `$product->is_on_sale()`
- **Out of Stock** — when product is out of stock
- **Featured** — when product is marked Featured in WC

## Variation Swatches

Renders one block per variation attribute. Type-of-swatch comes from your global config (Phase 5) or per-product override (Phase 8).

### Display controls

- **Show Attribute Label** — show / hide the "Color:" or "Size:" text
- **Show Selected Value** — append the term name next to the label (e.g. "Color: Navy Blue")
- **Append Colon** — toggle the trailing colon on labels
- **Show Price per Swatch** — Amazon-style price under each swatch
- **Show Reset Selection Link** — clear all selections in one click
- **Auto-select First Variation** — pre-select first available combination on page load
- **Sync Selection to URL** — reflects selection in `?attribute_pa_color=red`; survives refresh + back/forward

### Per-attribute override

Inside the widget, you can override the display type for any attribute on a per-instance basis (e.g. show Color as image swatches just on this Elementor page). Useful for special landing pages without changing your global config.

### Cross-section sync

When all attributes are selected, the widget emits `variation:selected` on the page-global state bus. The Add to Cart and Gallery widgets listen for this and update themselves automatically — even if they're in completely separate Elementor sections.

## Add to Cart

Renders different markup based on product type:

| Type | Output |
|------|--------|
| **Simple** | Quantity stepper + Add to Cart button |
| **Variable** | Same — but button stays disabled until all variation attributes are selected (via the Swatches widget) |
| **Grouped** | Table of children with per-row quantity inputs + single Add to Cart button |
| **External / Affiliate** | Single button linking to the external URL with `target="_blank" rel="noopener noreferrer nofollow"` |

### Layout controls

- **Show Stock Status** — "In Stock" / "Out of Stock" text
- **Show Price** — render price (uses `$product->get_price_html()`)
- **Show Quantity Stepper** — toggle the +/– / number input
- **Show Quantity Label** — toggle the "Quantity:" label above the stepper
- **Quantity Label Text** — customize the label

### Behavior controls

- **AJAX Add to Cart** — submit without page reload (recommended)
- **After Adding** — Stay on Page / Go to Cart / Go to Checkout / Custom URL
- **Show Buy Now Button** — secondary button that goes straight to checkout

### Out-of-stock + low-stock + backorder

- **Out-of-stock behavior**: Disable button / Hide button / Show message (admin-configurable)
- **Low-stock warning**: shows "Only N left in stock" when stock ≤ threshold (default 3, configurable)
- **Backorder notice**: shows "Available on backorder" when WC backorders are enabled

### Style controls

Full state-by-state styling: normal / hover / loading / success / error states. The success animation respects the **Settings → General → Success Behavior** choice (Restore Button Text / Keep "Added" Label / Show Checkmark).

## See also

- [Creating Swatches](./creating-swatches.md)
- [Per-Product Overrides](./per-product-overrides.md)
- [Troubleshooting](./troubleshooting.md)
