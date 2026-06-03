# Flex Multiple Listing and Booking System

WordPress plugin for **multiple listing grids** and **bookings** — rentals, tours, appointments, and services.

**Source:** [github.com/usman-dc/Flex-Multiple-Listing-and-Booking-System](https://github.com/usman-dc/Flex-Multiple-Listing-and-Booking-System)

## Requirements

- WordPress 6.0+
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+

## Quick start

1. Install and activate the plugin.
2. Complete **Flex MLS & Booking → Setup** wizard (choose industries).
3. Configure **Flex MLS & Booking → Settings** (currency, colors, layout).
4. Import demo listings (**Settings → Demo Content**) or add listings manually.
5. Place shortcodes, blocks, or Elementor widgets on pages.

## Shortcodes

| Shortcode | Description |
|-----------|-------------|
| `[fbs_booking_form id="1"]` | Booking form for booking type ID |
| `[fbs_listing_grid type="car-rental" columns="3" limit="12"]` | Filterable listing grid |
| `[fbs_search]` | Search UI |
| `[fbs_register]` | Partner registration |
| `[fbs_login]` | Partner login |
| `[fbs_dashboard]` | Partner dashboard |
| `[fbs_become_partner]` | Partner signup CTA |

## Build assets (required before upload if `dist/` is missing)

```bash
npm install
npm run build
```

## Submit to WordPress.org (not GitHub URL alone)

WordPress.org hosts plugins on their directory after review. You **upload a ZIP**, not the GitHub link.

1. Clone or download this repo.
2. Zip the **`flex-booking-system`** folder (must contain `flex-booking-system.php` at the top level inside the zip).
3. **Include:** `dist/`, `readme.txt`, `LICENSE`, all PHP/templates.
4. **Exclude:** `node_modules/`, `vendor/`, `.git/`, `.env`, IDE folders.
5. Submit at [wordpress.org/plugins/developers/add/](https://wordpress.org/plugins/developers/add/).
6. Run [Plugin Check](https://wordpress.org/plugins/plugin-check/) on your site first.

Put the GitHub URL in `readme.txt` (already added) so reviewers can browse source — the ZIP is still required.

## WordPress.org upload checklist

- Folder name for Git/local ZIP: `flex-booking-system` (keeps existing installs stable)
- Text domain (all `__()`, `_e()`, etc.): `flex-multiple-listing-and-booking-system` (must match WordPress.org plugin slug)
- WordPress.org SVN slug: `flex-multiple-listing-and-booking-system`
- `Stable tag` in readme.txt matches plugin version (1.0.1)
- Include `/dist` built CSS/JS
- Exclude `node_modules/`, `.git/`, dev-only files
- GPL-compatible license

## QA

```bash
php tests/color-settings-save-test.php
npm run build
```

## Author

Usman Ali — [WpRogers](https://wprogers.com/) — [WordPress profile](https://profiles.wordpress.org/usmanaliwpdeveloper/)

Documentation: this file and **Flex MLS & Booking → Settings → Shortcodes** in wp-admin.
