# Flex Multiple Listing and Booking System

WordPress plugin for **multiple listing grids** and **bookings** — rentals, tours, appointments, and services.

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

## WordPress.org upload checklist

- Folder name: `flex-booking-system` (do not rename — keeps updates stable)
- Text domain: `flex-booking-system` (unchanged for translations)
- Include `/dist` built CSS/JS
- Exclude `node_modules/`, `.git/`, dev-only files
- `readme.txt` present with stable tag matching plugin version
- GPL-compatible license

## QA

```bash
php tests/color-settings-save-test.php
npm run build
```

## Author

Usman Ali — [WpRogers](https://wprogers.com/) — [WordPress profile](https://profiles.wordpress.org/usmanaliwpdeveloper/)

Documentation: this file and **Flex MLS & Booking → Settings → Shortcodes** in wp-admin.
