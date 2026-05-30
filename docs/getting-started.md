# Getting Started

A 5-minute setup walkthrough.

## Requirements

| Requirement | Minimum |
|-------------|---------|
| WordPress | 6.0+ |
| WooCommerce | 7.0+ |
| Elementor | 3.5+ (free version, no Pro required) |
| PHP | 7.4+ |

## Installation

1. Download the latest release ZIP from the [Releases page](https://github.com/Aspeyash/Add-to-cart-/releases).
2. Go to **Plugins → Add New → Upload Plugin** in your WordPress admin.
3. Upload the ZIP, then click **Activate**.
4. Confirm WooCommerce and Elementor are also active.

## First-time configuration

After activation, a welcome notice points you to the settings page.

### Step 1 — Set global defaults

Go to **WooCommerce → Product Builder**.

- **General tab**: confirm the AJAX add-to-cart is enabled, pick a loading indicator style.
- **Add to Cart tab**: customize the default button text, decide whether to show price, quantity stepper, or Buy Now by default. These are defaults for *new* widget instances — existing widgets keep their own settings.
- **Swatches tab**: for each registered WooCommerce attribute, pick a display type:
  - **Default Dropdown** — no swatch (native WC behavior)
  - **Color** — single-color or split-color swatches
  - **Image** — image swatches (term image)
  - **Label** — text label swatches
  - **Button** — text-as-button swatches

### Step 2 — Configure each attribute term

For example, click **Edit Terms →** next to your "Color" attribute. You'll land at `Products → Color`.

For each term (e.g. "Red", "Blue"):
1. Click **Edit**.
2. Scroll to the **Swatch Settings** section.
3. Pick a Primary Color (and optional Secondary Color for split-color swatches).
4. Optionally upload an image (if Display Type = Image), customize the label text, or set tooltip text.
5. Save.

The "Swatch" column on the term list shows a visual preview of each value.

### Step 3 — Drop the widgets onto a product page

Edit a product page in Elementor. In the panel, look for the **Zymarg Product Builder** category — it has three widgets:

- **Product Gallery**
- **Variation Swatches**
- **Add to Cart**

Drop them anywhere — same column, different sections, opposite sides of the page. They'll automatically sync via the page-global state bus.

## Next steps

- [Creating Swatches](./creating-swatches.md) — full deep-dive on swatch types
- [Using the Widgets](./using-the-widgets.md) — widget controls reference
- [Per-Product Overrides](./per-product-overrides.md) — override anything for a single product
- [Troubleshooting](./troubleshooting.md) — common issues
