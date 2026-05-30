# Zymarg Product Builder

Connected Elementor widgets for WooCommerce — Product Gallery, Variation Swatches, and Add to Cart — designed to live in **separate Elementor sections** while staying in sync via a shared client-side state bus.

The actual WordPress plugin lives in [`zymarg-product-builder/`](./zymarg-product-builder/).

## Roadmap

| Phase | Status | Deliverable |
|-------|--------|-------------|
| 1 | done | Plugin scaffold, dependency checks, Elementor category, asset pipeline |
| 2 | done | Product Context + state bus + product JSON injection |
| 3 | done | **Add to Cart widget** (simple + variable, AJAX, full styling) |
| 4 | next | Admin: Global settings page |
| 5 | next | Admin: Attribute term meta (color picker, image upload) |
| 6 | next | **Variation Swatches widget** + cross-section sync |
| 7 | next | **Product Gallery widget** + variation image swap |
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
