# Troubleshooting

Common issues and their fixes.

## "Select a product or place this widget on a single product page"

The widget can't resolve a product from the current page context.

**Fix**:
- Place the widget on a single product page, OR
- Change the widget's **Product Source** to "Pick a Product" and select one manually

## Variation Swatches widget shows "Variation Swatches are only available for variable products"

The widget only renders for **variable** products. Simple, grouped, and external products don't have variations.

**Fix**: confirm the product is set to type "Variable" in the Product Data panel and has at least one published variation.

## My color attribute is showing as a dropdown, not as color swatches

The attribute's display type isn't set, or you haven't configured per-term colors.

**Fix**:
1. `WooCommerce → Product Builder → Swatches` — change the attribute's display type to "Color"
2. `Products → Color → [each term] → Edit` — set the Primary Color hex value for each term
3. Save

## Swatch shows a checker pattern instead of a color

The term doesn't have a color value set yet. Edit the term and set the **Primary Color** field.

## Image swatch shows a placeholder

Either the term has no image attached, or the attached image was deleted from the Media Library.

**Fix**: edit the term, upload an image via the **Swatch Image → Choose Image** button.

## Add to Cart button is disabled on a variable product

This is expected — the button stays disabled until all variation attributes are selected. The Variation Swatches widget needs to be on the same page to provide the selection UI.

If the Swatches widget is on the page but the button still won't enable:
- Confirm at least one variation is in stock
- Check the browser console for JavaScript errors
- Try the Plugins screen → "Check for Updates" link to make sure your plugin is up to date

## "Update available" never appears

The GitHub auto-updater polls every 12 hours. To check immediately:

1. **Plugins** screen → find "Zymarg Product Builder" → click **"Check for Updates"** in the row links
2. A notice appears with the result

If updates still don't appear:
- Confirm the GitHub repo is public (private repos require a personal access token, not implemented in v1)
- Check the latest GitHub release has a tag matching the format `vX.Y.Z` or `X.Y.Z`
- Verify the release has an attached ZIP asset with the plugin folder structure

## Variation image doesn't swap when I click a swatch

The Gallery widget needs to have **Swap Main Image on Variation** enabled (default: yes). Confirm:

1. Edit the Gallery widget in Elementor
2. **Behavior** section → **Swap Main Image on Variation** is toggled on
3. The Swatches widget is on the same page (in any section)
4. The variation has a variation image set in WooCommerce (Edit Product → Variations → click the camera icon)

## Lightbox won't open

Confirm the Gallery widget's **Click to Open Lightbox** behavior toggle is on.

If it's on but the lightbox doesn't open, check the browser console for JavaScript errors. Other plugins that hijack image clicks (some lightbox plugins) can interfere.

## Swatches don't grey out for unavailable combinations (smart-greying not working)

Smart-greying is computed client-side. If it's not working:
- Confirm the variation data is being injected — view page source and search for `<script id="zpb-products-data">`. If absent, the widget didn't queue the product.
- Confirm the page only has ONE product context (smart-greying is per-product)

## "Could not add to cart" error

The plugin distinguishes three categories of errors:
- **Network** — your server couldn't be reached. The retry button re-tries.
- **Server (5xx)** — WooCommerce or another plugin threw an error. Check WP debug log.
- **Validation (4xx)** — the product can't be added (out of stock, missing variation, etc.). Adjust the form and try again.

WP debug log is your friend: enable `WP_DEBUG` and `WP_DEBUG_LOG` in `wp-config.php`, reproduce the issue, then check `wp-content/debug.log`.

## Custom CSS / theme styles overriding the widget

The plugin uses low-specificity selectors so themes can override easily. If you want the plugin's styles to win, use Elementor's per-widget Style controls — they generate `{{WRAPPER}} ...` selectors which always beat theme CSS.

## Per-product override "Reset" doesn't seem to do anything

The Reset checkbox runs on **save**. Check the box, then click "Update" on the product. The page reloads with all overrides cleared.

## Still stuck?

Open an issue at the GitHub repository with:
- WordPress version
- WooCommerce version
- Elementor version
- PHP version
- Active theme
- A list of other active plugins
- Steps to reproduce
- Browser console output (F12 → Console)
