=== Zymarg Product Builder ===
Contributors: zymarg
Tags: woocommerce, elementor, add to cart, variation swatches, product gallery, color swatches, product page builder
Requires at least: 6.0
Tested up to: 6.5
Stable tag: 1.0.0
Requires PHP: 7.4
WC requires at least: 7.0
WC tested up to: 8.9
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Three connected Elementor widgets for WooCommerce: Product Gallery, Variation Swatches, and Add to Cart. Build rich product layouts that stay in sync across separate page sections.

== Description ==

**Zymarg Product Builder** is the most flexible Elementor + WooCommerce product page toolkit. Drop the three widgets in any layout you want — different rows, different columns, different sections — and they automatically stay in sync via a shared client-side state bus.

Click a color swatch on the left side of the page, and the gallery (in a different section) swaps to that color's image, while the Add to Cart widget (in yet another section) updates its price, stock status, button state, and quantity limits — instantly, with no page reload.

= Three connected widgets =

* **Product Gallery** — main image + thumbnails (vertical or horizontal layouts), hover zoom, custom built-in lightbox, configurable image resolution including full-resolution originals, mobile tap-to-zoom, sale / out-of-stock / featured badges, smooth cross-fade transitions.
* **Variation Swatches** — Color, Image, Label, or Button swatches per attribute. Smart-greying automatically disables combinations that don't lead to a real variation. Optional URL parameter sync so customers can share product links with selections preserved.
* **Add to Cart** — quantity stepper, AJAX add-to-cart, optional Buy Now button, full per-state styling (normal, hover, loading, success), low-stock warnings, backorder notices, retry-on-failure for network errors.

= Three layers of configuration =

* **Global** — set defaults once for your whole store
* **Per-attribute** — set the color of "Red" once, reused across thousands of products
* **Per-product** — override anything for a single special product without affecting others

= Designed for real stores =

* Supports **simple, variable, grouped, and external/affiliate** product types
* **HPOS-compatible** (WooCommerce custom orders table)
* **WCAG 2.1 AA** with screen-reader announcements, full keyboard navigation, `prefers-reduced-motion` support
* **Mobile-first**: touch targets, iOS safe areas, tap-to-zoom on touch devices, responsive layout collapsing
* **Translation-ready**: full text-domain coverage, RTL CSS, .pot file included
* **Theme-overridable templates**: copy any template into `yourtheme/zymarg-product-builder/...` to customize
* **GitHub auto-updates**: future releases appear under Dashboard → Updates automatically

= No bloat =

* No external JavaScript libraries (no jQuery dependency on the front-end, no Swiper, no slick)
* Conditional asset loading — assets only load on pages that use the widgets
* Vanilla CSS using modern features (CSS custom properties, logical properties, `aspect-ratio`)
* Single autoloaded option for global settings — minimal database overhead

== Installation ==

1. Upload the `zymarg-product-builder` folder to your `/wp-content/plugins/` directory, or install the ZIP via **Plugins → Add New → Upload Plugin**.
2. Activate **Zymarg Product Builder** through the **Plugins** menu in WordPress.
3. Make sure WooCommerce and Elementor are both installed and active.
4. Visit **WooCommerce → Product Builder** to configure global defaults.
5. Configure attribute swatch types under **WooCommerce → Product Builder → Swatches**, then visit **Products → [your attribute]** to set color values, upload images, etc.
6. Edit any page in Elementor — find the new **Zymarg Product Builder** widget category in the panel.

== Frequently Asked Questions ==

= Does this work with Elementor Free, or do I need Elementor Pro? =

Elementor Free is enough. The plugin doesn't require Elementor Pro for any feature.

= Which WooCommerce product types are supported? =

Simple, variable, grouped, and external/affiliate products are all fully supported. The Add to Cart widget renders the right UI for each type automatically.

= Is HPOS (High-Performance Order Storage) supported? =

Yes. The plugin declares compatibility with `custom_order_tables` and uses HPOS-compatible save hooks throughout the admin (e.g. `woocommerce_admin_process_product_object`).

= Can I override settings for a single product? =

Yes — the **Product Builder** tab in the Product Data panel lets you override attribute display types, hide attributes from the swatches, customize the Add to Cart button text, change out-of-stock behavior, redirect after add, and even disable specific widgets entirely for that product.

= Does the plugin auto-update from GitHub? =

Yes. Once installed, the plugin polls the GitHub Releases API every 12 hours and surfaces new versions through the standard WordPress update flow. Updates only appear when a release tag is strictly newer than the installed version. You can force an immediate check from the **Plugins** screen via the **"Check for Updates"** link.

= How do I show full-resolution images in the gallery? =

In the Product Gallery widget controls, find **Layout → Main Image Resolution** and choose **Full Resolution (original)**. You can also set the lightbox image resolution and thumbnail resolution independently.

= My customer can share a URL with their selected variation? =

Yes — enable **Sync Selection to URL** on the Variation Swatches widget. Selections are reflected as query parameters (e.g. `?attribute_pa_color=red`) and restored on refresh and browser back/forward.

= Does it work with my custom theme? =

Yes. The widgets render via overridable templates. Copy any template from `zymarg-product-builder/templates/` to `yourtheme/zymarg-product-builder/` to customize. The plugin works alongside any theme that supports WooCommerce.

= Can I use it without Elementor? =

The widgets are Elementor widgets, so Elementor is required to use them. The admin features (settings page, swatch term meta, per-product overrides) work even without Elementor active.

= Is there a way to disable a widget for a specific product only? =

Yes. Edit the product → **Product Data → Product Builder** tab → "Disable Widgets for This Product" → check the widget(s) you want to disable.

= Does the plugin slow down my site? =

No. Assets only load on pages where the widgets are present. There's no front-end jQuery dependency. Variation data is injected once per page (no extra AJAX round-trips for variation matching). Settings use a single autoloaded option.

= Is the plugin RTL-friendly? =

Yes. The CSS uses logical properties (`margin-inline-start`, `padding-inline-end`, etc.) so RTL layouts (Hebrew, Arabic) render correctly without a separate stylesheet.

= How do I report a bug or request a feature? =

Open an issue at the GitHub repository. Include your WordPress, WooCommerce, Elementor, and PHP versions, along with steps to reproduce.

== Screenshots ==

1. Variation Swatches widget showing color, size, and label types
2. Add to Cart widget with quantity stepper and Buy Now button
3. Product Gallery with vertical thumbnails, hover zoom, and a sale badge
4. Settings page → General tab
5. Settings page → Add to Cart Defaults tab
6. Settings page → Swatches tab — per-attribute display type configuration
7. Per-attribute term editor with WordPress color picker
8. Per-product overrides tab in the WooCommerce Product Data panel

== Changelog ==

= 1.0.0 =
* First stable release.
* Product Gallery widget: full-resolution image support — main image, thumbnail, and lightbox sizes are independently configurable per widget instance (WooCommerce Single, Thumbnail, Medium, Medium Large, Large, Full Resolution).
* Translations: bundled `.pot` file at `languages/zymarg-product-builder.pot` covering every translatable string. WP-CLI regeneration script at `bin/make-pot.sh`.
* Plugin icons + banner shipped (256×256, 128×128, 1544×500).
* Comprehensive `docs/` directory with getting-started, swatches, widgets, overrides, troubleshooting, and release guides.
* Final QA matrix documented at `docs/qa-checklist.md`.
* RTL support: CSS uses logical properties throughout for clean right-to-left layouts.
* All version-bump infrastructure verified end-to-end with the GitHub auto-updater.

= 0.8.0 =
* New: full support for grouped products (table of children with individual quantity inputs) and external / affiliate products.
* New: backorder notice + low-stock warning with admin-configurable thresholds and message templates.
* New: URL parameter sync on the Variation Swatches widget — selections survive refresh + browser back/forward.
* Accessibility: screen-reader live region announcing variation selections + stock status; `prefers-reduced-motion` respected throughout; 44×44 minimum touch targets on coarse-pointer devices.
* Mobile: hover-zoom on the gallery automatically becomes tap-to-zoom on touch devices; iOS safe-area insets honored in the lightbox.
* UX: success animation styles (Restore / Stay / Checkmark) all implemented; Add to Cart errors distinguish network / server / validation failures with a Try Again retry link; cross-fade on gallery image swap; skeleton placeholders for thumbnails while loading.

= 0.7.0 =
* New: per-product overrides via the "Product Builder" tab in the WooCommerce Product Data panel.
* Override attribute display type, hide attributes from swatches (auto-resolved server-side so cart still works), customize Add to Cart button text + Buy Now visibility + out-of-stock behavior + redirect-after-add per product.
* Disable any of the three widgets per-product.
* Reset all overrides for a product with a single checkbox + confirm dialog.
* HPOS-compatible save handler.

= 0.6.0 =
* New: GitHub-based auto-update system. The plugin polls the GitHub Releases API every 12 hours and surfaces new versions through the standard WordPress update flow.
* Strict version-aware: an update is offered only when the latest release tag is strictly newer than the installed version (per `version_compare`).
* "Check for Updates" link added to the plugin row.
* GitHub Action included: tagging vX.Y.Z (or X.Y.Z) automatically builds a clean plugin ZIP and attaches it to the release. The Action also fails if the tag does not match the Version: header, preventing accidental version mismatches.

= 0.5.0 =
* New: Product Gallery Elementor widget with main image + thumbnails.
* Three layouts: vertical thumbs (left), vertical thumbs (right), horizontal thumbs.
* Custom built-in lightbox with keyboard navigation and focus trap.
* Cross-section sync: Gallery listens for variation:selected and swaps the main image to the variation image.
* Sale, Out of Stock, and Featured badges with positioning + per-badge color controls.

= 0.4.0 =
* New: Variation Swatches Elementor widget.
* Renders one block per variation attribute with the type configured globally or per-widget (Color / Image / Label / Button / Default Dropdown).
* Cross-section sync via the page-global state bus.
* Smart-greying: combinations that don't match any variation are visually disabled.

= 0.3.0 =
* Admin: new "Swatches" tab on the settings page for per-attribute display type configuration.
* Admin: per-term meta fields on each WooCommerce attribute term page (color picker for Color attributes, media library upload for Image attributes, custom label, tooltip).
* "Swatch" preview column added to attribute term list tables.
* Split-color (two-tone) swatch support.

= 0.2.0 =
* Admin: WooCommerce → Product Builder settings page (General + Add to Cart Defaults tabs).
* Tabbed UI with Save Changes and Reset to Defaults per tab.
* Add to Cart widget Elementor controls now inherit defaults from settings.
* Out-of-stock behavior: Disable button / Hide button / Show message (admin-configurable).
* One-time welcome notice on activation.

= 0.1.0 =
* Initial scaffold: dependency checks, Elementor category, asset pipeline.
* Product Context resolver and per-product JSON data injection.
* Client-side state bus (`window.ZPB`) keyed by product ID.
* Add to Cart widget: quantity stepper (with on/off label toggle), AJAX, Buy Now option, full Content + Style controls, redirect after add, out-of-stock state.
* AJAX endpoint with WooCommerce fragments for theme mini-cart updates.

== Upgrade Notice ==

= 1.0.0 =
First stable release. Existing 0.x installations upgrade cleanly — no manual steps required. After updating, visit Plugins → Check for Updates to verify the auto-updater is working as expected.
