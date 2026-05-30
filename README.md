# Zymarg Product Builder

Connected Elementor widgets for WooCommerce — Product Gallery, Variation Swatches, and Add to Cart — designed to live in **separate Elementor sections** while staying in sync via a shared client-side state bus.

The actual WordPress plugin lives in [`zymarg-product-builder/`](./zymarg-product-builder/).

## Documentation

- [Getting Started](./docs/getting-started.md) — 5-minute setup
- [Creating Swatches](./docs/creating-swatches.md) — full swatch configuration walkthrough
- [Using the Widgets](./docs/using-the-widgets.md) — control reference for all 3 widgets
- [Per-Product Overrides](./docs/per-product-overrides.md) — Phase 8 features
- [Troubleshooting](./docs/troubleshooting.md) — common issues
- [Releasing a New Version](./docs/releasing.md) — auto-update workflow
- [QA Checklist](./docs/qa-checklist.md) — pre-release smoke test matrix

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
| GitHub auto-updater | done | Strict version-aware GitHub Releases integration |
| 8 | done | Per-product override meta box (display type, hide attributes, ATC overrides, widget disable) |
| 9 | done | Polish: a11y, URL param sync, grouped/external products, low-stock + backorder, animations, mobile |
| 10 | done | **v1.0.0**: full-resolution gallery option, translations (.pot), plugin icons + banner, expanded readme.txt, docs/ directory, QA checklist |

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
   git tag v1.0.1
   git push origin v1.0.1
   ```
5. The included GitHub Action (`.github/workflows/release.yml`) automatically:
   - Verifies the plugin's `Version:` header matches the tag (fails the build if not)
   - Builds a clean `zymarg-product-builder.zip` containing only the plugin folder
   - Creates a GitHub Release with that ZIP attached
   - Generates release notes from your commits

Within 12 hours every WP site will see the update notification (or the site owner can click **Plugins → Zymarg Product Builder → Check for Updates** for an instant check).

For the full release walkthrough including pre-releases, rollback, and forking guidance, see [docs/releasing.md](./docs/releasing.md).

## License

GPL-2.0-or-later
