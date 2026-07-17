# ApexDrive — High-Tech Used Car WordPress Site

A complete WordPress package for a modern used-car dealership: a dark, neon-accented
"digital showroom" theme plus an inventory engine plugin.

## What's included

```
wp-content/
├── themes/apexdrive/              # The theme (design + templates)
└── plugins/apexdrive-inventory/   # The data engine (vehicles, filters, leads)
```

### ApexDrive Inventory (plugin)

- **Vehicle post type** at `/inventory/` with full spec fields: price, sale price, year,
  mileage, transmission, drivetrain, engine, MPG, colors, VIN, stock #, owners,
  status (available / pending / sold), featured flag, history-report URL, features list,
  and a photo gallery (comma-separated attachment IDs).
- **Taxonomies**: Makes, Body Types, Fuel Types, Condition Tags.
- **AJAX filtering endpoint** with keyword, make, body, fuel, price range, min year,
  max mileage, and sorting — shared with the archive page so filtered URLs are
  shareable and SEO-crawlable.
- **Lead capture**: test-drive/inquiry forms store leads as a private `Leads` post type
  and email the site admin (honeypot spam protection included).
- **Demo data**: Tools → ApexDrive Demo Data seeds 12 realistic sample vehicles with
  specs, taxonomies, and features in one click.
- Admin list view shows photo, price, year, mileage, and status columns.

### ApexDrive (theme)

- **Front page**: animated hero with grid-line backdrop, quick-search bar
  (make / body / price / keyword), featured-vehicle grid, trust tiles, CTA band.
- **Inventory page**: sticky filter sidebar, live AJAX results (no reload), result count,
  sorting, "Load More" pagination, URL sync via `history.replaceState`.
- **Vehicle detail page**: photo gallery with thumbnails, full spec table, features
  checklist, **financing calculator** (down payment / APR / term → monthly payment),
  and a test-drive request form.
- **Customizer → Dealership Info**: phone, address, hours, hero tagline.
- Sticky glassmorphism header, mobile nav, scroll-reveal animations, responsive
  down to phones, no external dependencies (system fonts, vanilla JS).

## Installation

1. Copy both folders into your WordPress install:
   - `wp-content/themes/apexdrive`
   - `wp-content/plugins/apexdrive-inventory`
2. In wp-admin: **Plugins → Activate "ApexDrive Inventory"** (activate the plugin first).
3. **Appearance → Themes → Activate "ApexDrive"**.
4. **Settings → Permalinks → Save** (flushes rewrite rules for `/inventory/`).
5. Optional: **Tools → ApexDrive Demo Data → Seed Demo Vehicles**.
6. **Settings → Reading**: set "Your homepage displays" to a static page (any page)
   so `front-page.php` renders, or leave as latest posts — `front-page.php` is used
   either way for the site front.
7. **Appearance → Customize → Dealership Info**: set your phone, address, hours, tagline.
8. Add vehicles under **Vehicles → Add Vehicle** — set a featured image, fill in the
   specs box, assign a Make / Body Type / Fuel Type.

### Vehicle photo galleries

Upload photos to the Media Library, note their attachment IDs, and enter them
comma-separated in the vehicle's **Gallery image IDs** field. The featured image is
always the first photo.

## Requirements

- WordPress 6.0+
- PHP 7.4+
- No third-party plugins or build steps required.

## Customizing the look

All colors live in CSS custom properties at the top of `themes/apexdrive/style.css`
(`--accent`, `--bg`, etc.) — swap the cyan/indigo for your brand palette in one place.
