# Per-Product Overrides

Every plugin-wide setting can be overridden for a single product without affecting any other product on your site.

## Where to find it

Edit any product → in the **Product Data** panel, click the **Product Builder** tab (next to General, Inventory, Shipping, etc.).

## What you can override

### Attribute display

For each variation attribute on this product, override the swatch type:
- **Inherit (Color)** *(default)* — falls back to the global config
- **Color / Image / Label / Button / Default Dropdown** — override for this product

The "Inherit" option label tells you what the global value is, so you know what you're falling back to.

### Hide attributes from swatches

Multi-checkbox listing every variation attribute on this product.

When you check one, the Variation Swatches widget skips it entirely on the front-end. The plugin auto-resolves the hidden attribute to the first in-stock variation value, so adding to cart still works.

**Use case**: a "Material" attribute used internally for SKU tracking. You don't want customers picking it, but it has to exist as a variation attribute.

### Add to Cart overrides

- **Custom Button Text** — override "Add to Cart" with anything you want for this product
- **Show "Buy Now" Button** — Inherit / Force ON / Force OFF
- **Out-of-Stock Behavior** — Inherit / Disable Button / Hide Button / Show Message Instead
- **Redirect After Adding** — Inherit / Stay on Page / Go to Cart / Go to Checkout

All four can be left as "Inherit" to use the global config.

### Disable widgets for this product

Multi-checkbox: **Add to Cart**, **Variation Swatches**, **Product Gallery**.

Checked widgets render nothing on the front-end for this product. In the Elementor editor, they show a placeholder explaining they're disabled.

**Use case**: a "Custom Bundle" product with a hand-built Elementor layout that uses non-standard widgets. Disable the standard widgets so they don't double-render.

### Reset all overrides

A danger-styled checkbox at the bottom: "Reset all overrides for this product on save". Check it, click Update — every override on this product is wiped, falling back to global config.

## Resolution chain

Every read in the plugin walks this order:

```
Per-Product Override   →   Global Setting   →   Hard-coded Default
   (this tab)              (WC → Product           (in plugin code)
                            Builder settings)
```

Empty / "inherit" values fall through transparently — no conflicts, no surprises.

## API

For theme / plugin developers, the read API:

```php
use Zymarg\ProductBuilder\Product_Overrides;

// Generic dot-notation getter (override → fallback)
Product_Overrides::get( $product_id, 'add_to_cart.button_text', 'Add to Cart' );

// Resolution-chain helpers (override → global → default)
Product_Overrides::get_attribute_display( $product_id, 'pa_color' );
Product_Overrides::get_add_to_cart( $product_id, 'redirect_after', 'none' );

// Predicates
Product_Overrides::is_attribute_hidden( $product_id, 'pa_material' );
Product_Overrides::is_widget_disabled(  $product_id, 'gallery' );
Product_Overrides::get_hidden_attributes( $product_id );
```

## See also

- [Using the Widgets](./using-the-widgets.md)
- [Creating Swatches](./creating-swatches.md)
