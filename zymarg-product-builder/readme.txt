=== Zymarg Product Builder ===
Contributors: zymarg
Tags: woocommerce, elementor, add to cart, variation swatches, product gallery
Requires at least: 6.0
Tested up to: 6.5
Stable tag: 0.7.0
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


= 0.4.0 =
* New: Variation Swatches Elementor widget
* Renders one block per variation attribute with the type configured in the Swatches admin (Color / Image / Label / Button / Default Dropdown)
* Cross-section sync via the page-global state bus: select a swatch, the Add to Cart widget in another Elementor section instantly updates price, stock, button state, and qty min/max
* Smart-greying: combinations that don't match any variation are visually disabled
* Per-attribute display override on the widget instance (Inherit / Color / Image / Label / Button)
* Toggles for: show attribute label, show selected value, show colon, show price per swatch, show reset link, auto-select first available variation
* Full keyboard support: arrow keys + Enter/Space within radiogroup, ARIA roles and aria-checked
* Themes can override templates by copying templates/swatches/* into yourtheme/zymarg-product-builder/swatches/


= 0.5.0 =
* New: Product Gallery Elementor widget with main image + thumbnails
* Three layouts: Vertical thumbs (left), Vertical thumbs (right), Horizontal thumbs
* Mobile responsive: vertical layouts auto-collapse below 768px
* Hover zoom (configurable level 1.2x to 4x) using CSS transform-origin tracking
* Custom built-in lightbox: keyboard navigation (Esc, Arrow keys), focus trap, click backdrop to close, image counter, no external library required
* Cross-section sync: listens for variation:selected on the state bus and swaps the main image to the variation image; reverts to featured on variation:cleared
* Sale, Out of Stock, and Featured badges with positioning + per-badge color controls
* Aspect ratio control: auto, 1:1, 4:3, 3:4, 16:9
* Featured-image-first toggle
* Configurable navigation arrows (visible on hover)


= 0.6.0 =
* New: GitHub-based auto-update system. The plugin now polls the GitHub Releases API every 12 hours and surfaces new versions through the standard WordPress update flow (Dashboard -> Updates and Plugins screen).
* Strict version-aware: an update is offered only when the latest release tag is strictly newer than the installed version (per version_compare).
* "Check for Updates" link added to the plugin row for on-demand checks.
* GitHub Action included: tagging vX.Y.Z (or X.Y.Z) automatically builds a clean plugin ZIP and attaches it to the release. The Action also fails if the tag does not match the Version: header, preventing accidental version mismatches.
* Pre-releases and drafts are excluded automatically (we use /releases/latest).
* Filesystem-aware extractor handles both attached release ZIPs and zipball fallbacks.


= 0.7.0 =
* New: per-product overrides via the "Product Builder" tab in the WooCommerce Product Data panel
* Override attribute display type per-product (Inherit / Color / Image / Label / Button / Default Dropdown)
* Hide attributes from the Variation Swatches widget per-product (auto-resolved server-side so cart submission still picks a real variation)
* Override Add to Cart button text, Buy Now visibility, out-of-stock behavior, and redirect-after-add per-product
* Disable any of the three widgets (Add to Cart, Variation Swatches, Product Gallery) per-product
* Reset all overrides for a product with a single checkbox + confirm dialog
* HPOS-compatible save handler (uses woocommerce_admin_process_product_object)
* Resolution chain: per-product override -> global setting -> hard-coded default
