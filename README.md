# Zymarg Product Builder

Connected Elementor widgets for WooCommerce — Product Gallery, Variation Swatches, and Add to Cart — designed to live in **separate Elementor sections** while staying in sync via a shared client-side state bus.

The actual WordPress plugin lives in [`zymarg-product-builder/`](./zymarg-product-builder/).

## Roadmap

| Phase | Status | Deliverable |
|-------|--------|-------------|
| 1 | done | Plugin scaffold, dependency checks, Elementor category, asset pipeline |
| 2 | done | Product Context + state bus + product JSON injection |
| 3 | done | **Add to Cart widget** (simple + variable, AJAX, full styling) |
| 4 | done | Admin: Global settings page (General + Add to Cart defaults) |
| 5 | done | Admin: Attribute term meta (color picker, image upload, swatch type config) |
| 6 | done | **Variation Swatches widget** + cross-section sync + smart-greying |
| 7 | done | **Product Gallery widget** + variation image swap + custom lightbox |
| 8 | next | Admin: Per-product override meta box |
| 9 | next | Polish: accessibility, mobile, animations, edge cases |
| 10 | next | Translations, screenshots, release |

## Architecture

```
Page-global state bus  →  window.ZPB.product(id)
   ├─ on('variation:selected', cb)   ← Add to Cart, Gallery subscribe
   ├─ emit('variation:selected', ...) ← Swatches publishes
   └─ getData()                       ← server-injected product JSON
```

Each widget declares its product source (current product or manual pick), gets the same `ZPB.product(id)` channel, and stays in sync regardless of where on the page it lives.

## Requirements

* WordPress 6.0+
* WooCommerce 7.0+
* Elementor 3.5+ (free version)
* PHP 7.4+

## License

GPL-2.0-or-later

---

## Releasing a new version (auto-update workflow)

The plugin includes a GitHub-based auto-updater. Once the current release is installed, every WordPress site running the plugin will see new versions under **Dashboard → Updates** automatically.

### To ship an update

1. Make your changes on a branch and merge to `main`
2. Bump the version in **two places** (must match):
   - `zymarg-product-builder/zymarg-product-builder.php` — the `Version:` header AND `ZPB_VERSION` constant
   - `zymarg-product-builder/readme.txt` — `Stable tag:` line and add a changelog entry
3. Commit the version bump
4. Tag the commit and push the tag:
   ```bash
   git tag v0.6.1
   git push origin v0.6.1
   ```
5. The included GitHub Action (`.github/workflows/release.yml`) automatically:
   - Verifies the plugin's `Version:` header matches the tag (fails the build if not)
   - Builds a clean `zymarg-product-builder.zip` containing only the plugin folder
   - Creates a GitHub Release with that ZIP attached
   - Generates release notes from your commits

Within 12 hours every WP site will see the update notification (or the site owner can click **Plugins → Zymarg Product Builder → Check for Updates** for an instant check).

### Version rules
- Tags must be `vX.Y.Z` or `X.Y.Z` (e.g. `v0.6.1`, `1.0.0`)
- The plugin only offers an update when the tag's version is **strictly greater** than the installed version (per PHP `version_compare`)
- Pre-release / draft GitHub releases are ignored — only the `/releases/latest` tag is considered
