=== Zymarg Product Builder ===
Contributors: zymarg
Tags: woocommerce, elementor, add to cart, variation swatches, product gallery
Requires at least: 6.0
Tested up to: 6.5
Stable tag: 0.3.0
Requires PHP: 7.4
WC requires at least: 7.0
WC tested up to: 8.9
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connected Elementor widgets for WooCommerce: Product Gallery, Variation Swatches, and Add to Cart. Build rich product layouts with synchronized widgets that work across separate sections.

== Description ==

Zymarg Product Builder gives Elementor users a set of WooCommerce widgets that stay in sync with each other even when placed in different page sections — exactly like marketplaces (Amazon-style layouts).

= Widgets included =

* **Add to Cart** — quantity stepper, AJAX add-to-cart, optional Buy Now button, full styling controls.
* **Variation Swatches** *(coming next)* — color, image, label, button swatches per attribute.
* **Product Gallery** *(coming next)* — main image, thumbnails, zoom, lightbox, variation image swap.

= Phase 1–3 (this release) =

* Plugin foundation, dependency checks (WooCommerce + Elementor)
* Custom Elementor category
* Per-product client-side state bus (so widgets in different sections can talk)
* Product data JSON injection (no extra AJAX for variation lookup)
* Add to Cart widget with full Content + Style controls and AJAX endpoint

== Installation ==

1. Upload the `zymarg-product-builder` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Make sure WooCommerce and Elementor are both active.
4. Edit a page in Elementor and look for the **Zymarg Product Builder** category in the widgets panel.

== Changelog ==

= 0.1.0 =
* Initial scaffold: dependency checks, Elementor category, asset pipeline.
* Product Context resolver and per-product JSON data injection.
* Client-side state bus (`window.ZPB`) keyed by product ID.
* Add to Cart widget: quantity stepper (with on/off label toggle), AJAX, Buy Now option, full Content + Style controls, redirect after add, out-of-stock state.
* AJAX endpoint with WooCommerce fragments for theme mini-cart updates.


= 0.2.0 =
* Admin: WooCommerce -> Product Builder settings page (General + Add to Cart Defaults tabs)
* Tabbed UI with Save Changes and Reset to Defaults per tab
* Settings_Store helper: dot-notation get(), section reset, default seeding on activation
* Add to Cart widget Elementor controls now inherit defaults from settings
* Out-of-stock behavior: Disable button / Hide button / Show message (admin-configurable)
* Plugins-list "Settings" and "Docs" links
* One-time welcome notice on activation


= 0.3.0 =
* Admin: new "Swatches" tab in the settings page for per-attribute display type configuration (Default Dropdown / Color / Image / Label / Button)
* Admin: per-term meta fields on each WooCommerce attribute term page (color picker for Color attributes, media library upload for Image attributes, custom label, tooltip)
* Admin: "Swatch" preview column added to attribute term list tables (color square, image thumbnail, or text label)
* New helper API: Attribute_Settings::get_type(), Term_Meta::get_swatch(), Term_Meta::get_color(), Term_Meta::get_image_url()
* Conflict detection: warning notice if another swatches plugin is detected
* Split-color (two-tone) swatch support for color attributes
