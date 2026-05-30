# Creating Swatches

Full walkthrough for configuring the four swatch types.

## Conceptual model — three layers

```
Layer 1: Global Display Type      "Color attribute = Color swatches"
   (one setting, applies everywhere)
                ↓
Layer 2: Per-Term Visual Data     "Red term → #FF0000"
   (set the actual values once, reused across thousands of products)
                ↓
Layer 3: Per-Product Override     "for THIS product, render Color as Image"
   (optional — handles edge cases without breaking the global config)
```

## Step 1 — Set the display type for an attribute

`WooCommerce → Product Builder → Swatches`

For each attribute, pick one of:

| Type | Best for |
|------|----------|
| **Default Dropdown** | Don't render as swatches — fall back to native WC dropdown |
| **Color** | Color attributes (Red, Blue, etc.) |
| **Image** | Pattern, material, or photo-based variants |
| **Label** | Short text values (S, M, L) — pill-shaped labels |
| **Button** | Long text values (X-Large, 3X-Large) — rectangular buttons |

Click "Save Changes". The setting now applies globally to every product using that attribute.

## Step 2 — Set per-term values

`Products → [your attribute name]` (e.g. `Products → Color`).

### Color swatches

Click "Edit" next to a term. Scroll to the **Swatch Settings** fieldset:

- **Primary Color** — the main hex color shown on the swatch
- **Secondary Color** *(optional)* — second color for split / two-tone swatches (e.g. red/blue varsity jacket)

Both use the WordPress color picker (Iris). Hex format (`#RRGGBB` or `#RGB` shorthand).

### Image swatches

In the **Swatch Settings** fieldset:

- **Swatch Image** — click "Choose Image" to open the WordPress Media Library
- The thumbnail preview appears next to the button
- Click "Remove" to clear it

The plugin uses the image's `thumbnail` size for the swatch. Make sure your images look good at small sizes.

### Label / Button swatches

These don't need any extra data — the term name is used by default. The "Custom Label" field below lets you override the displayed text per term (e.g. show "Sm" instead of "Small" on the swatch while keeping "Small" as the term name).

### Tooltip text

Available for any swatch type. Shown on hover. Defaults to the label/term name if empty.

## Step 3 (optional) — Per-product override

For a single special product, edit it and find the **Product Builder** tab in the Product Data panel. Change Color → Image (for example). This product now renders Color as image swatches; every other product on the site keeps using the global "Color" setting.

## Hiding attributes

Sometimes a variation attribute is "internal" — used for SKU tracking but not customer-facing.

Edit the product → **Product Data → Product Builder** tab → check the attribute under **Hide Attributes from Swatches**.

The plugin auto-resolves the hidden attribute to the first in-stock variation value at render time, so the variation lookup still works when adding to cart. Customers just don't see it.

## Two-color (split) swatches

Set both **Primary Color** and **Secondary Color** on a term. The swatch renders as a 45° gradient between the two colors. Useful for:

- Sport teams (red/black, blue/yellow)
- Bicolor fashion (cream/beige)
- Anything where you want to show two distinct hues at a glance

## See also

- [Using the Widgets](./using-the-widgets.md)
- [Per-Product Overrides](./per-product-overrides.md)
