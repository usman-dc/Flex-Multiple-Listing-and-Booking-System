=== Flex Listings and Booking Manager ===
Contributors: usmanaliwpdeveloper
Tags: booking, listings, rental, appointment, calendar
Requires at least: 6.2
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Multiple listing grids and a full booking engine for property rentals, car hire, tours, services, and appointments on WordPress.

== Description ==

**Flex Listings and Booking Manager** helps you publish many bookable listings and accept reservations on WordPress — hotels, vacation rentals, car rental, tours, events, and services.

Developed by [Usman Ali](https://profiles.wordpress.org/usmanaliwpdeveloper/) at [WpRogers](https://wprogers.com/).

Source code (development, issues, releases): [GitHub repository](https://github.com/usman-dc/Flex-Multiple-Listing-and-Booking-System)

= Key features =

* **Dynamic booking types** — create types for property, car, tour, or custom industries
* **Multiple listing post types** — rich single pages with gallery, map, FAQ, features, and pricing
* **AJAX listing grid** — keyword, price, guest, and sort filters with pagination
* **Booking form** — industry-aware fields, instant booking support, email notifications
* **Partner / vendor portal** — front-end registration, login, dashboard, add listings
* **Gutenberg blocks & Elementor widgets** — booking form, listing grid, search
* **Admin dashboard** — bookings overview, status management, activity charts
* **Settings** — currency, colors, layout, container width, notifications, demo content
* **REST API** — bookings and settings endpoints for integrations
* **WooCommerce bridge** — optional integration when WooCommerce is active

= Shortcodes =

* `[ulbm_booking_form id="1"]` — booking form for a booking type
* `[ulbm_listing_grid type="car-rental" columns="3" limit="12"]` — filterable listing grid
* `[ulbm_search]` — search UI placeholder
* `[ulbm_register]` — partner registration
* `[ulbm_login]` — partner login
* `[ulbm_dashboard]` — partner dashboard
* `[ulbm_become_partner]` — partner call-to-action block

= External services =

**Bootstrap 5.3.8**, **Bootstrap Icons 1.11.3**, and **Chart.js 4.5.1** are bundled inside the plugin (`assets/vendor/`) and enqueued from the site only on admin and booking UI screens. No CDN is used for those libraries.

**Optional — Google Maps (not affiliated with Google):** If the site owner enables embedded maps under **Settings → Partner Portal**, listing pages can show a button for visitors to opt in before loading an iframe from `https://maps.google.com/`. Until the visitor clicks that button, no request is sent to Google. When loaded, the visitor’s IP address and map coordinates may be processed by Google. Terms: https://www.google.com/intl/en/policies/terms/ — Privacy: https://policies.google.com/privacy

**Optional — License validation (not required for core features):** When a site owner activates a purchase key under **Settings → License**, the plugin sends a one-time admin request to your license server (default: `wprogers.com`) with the key and site URL. No automatic calls are made until activation or the daily status check for an already-activated key.

**Optional:** Listing video embeds use WordPress `wp_oembed_get()` for URLs the site owner adds (e.g. YouTube). WooCommerce integration loads only when WooCommerce is active. Demo content uses placeholder images bundled in `assets/demo/` (no remote downloads).

= Privacy =

Booking forms collect customer name, email, phone, and booking details stored in your WordPress database. Configure your site privacy policy for GDPR compliance.

== Installation ==

1. Upload the `flex-multiple-listing-and-booking-system` folder to `/wp-content/plugins/` or install via **Plugins → Add New**
2. Activate **Flex Listings and Booking Manager** through the **Plugins** menu
3. Go to **Flex Listings & Booking → Setup** (first run) and choose your industries
4. Open **Flex Listings & Booking → Settings** to configure currency, colors, and layout
5. Partner pages (register, login, dashboard) are created automatically — see **Partner Portal** tab
6. Add listings under the plugin menu or import demo content from **Settings → Demo Content**
7. Embed grids and forms with shortcodes, blocks, or Elementor widgets

= Build from source =

If you clone from Git, run `npm install && npm run build` before use so `/dist` CSS and JS exist.

= Submitting to WordPress.org =

WordPress.org does **not** install plugins directly from GitHub. Use this flow:

1. Download or clone from [GitHub](https://github.com/usman-dc/Flex-Multiple-Listing-and-Booking-System).
2. Create a ZIP of the **`flex-multiple-listing-and-booking-system`** folder containing **only runtime plugin files**. **Do not include** `dev-tools/`, `node_modules/`, `vendor/`, `assets/src/`, or `package.json`. The `/dist` folder **must** be included. With [WP-CLI](https://wp-cli.org/), run `wp dist-archive . ../flex-multiple-listing-and-booking-system.zip` from the plugin folder — `.distignore` excludes dev files automatically.
3. Submit the ZIP at [WordPress.org Add Plugin](https://wordpress.org/plugins/developers/add/) (requires a WordPress.org account).
4. After approval, releases are published via WordPress.org SVN — keep GitHub and SVN versions in sync.

Reviewers may read the GitHub repo for context; the ZIP upload is still required for review.

== Frequently Asked Questions ==

= Can I submit only my GitHub URL to WordPress.org? =

No. You must upload a plugin ZIP through [wordpress.org/plugins/developers/add/](https://wordpress.org/plugins/developers/add/). Linking GitHub in this readme helps reviewers find source code and report issues.

= How do I prepare the ZIP for WordPress.org? =

Zip the `flex-multiple-listing-and-booking-system` directory so the archive unpacks to one folder named `flex-multiple-listing-and-booking-system`. Include `dist/`, `readme.txt`, and `LICENSE`. Do not include `node_modules`. Run [Plugin Check](https://wordpress.org/plugins/plugin-check/) on your site before submitting.

= Why is the text domain different from the folder name? =

WordPress.org requires the text domain to match the plugin slug (`flex-multiple-listing-and-booking-system`). The install folder may remain `flex-multiple-listing-and-booking-system` for compatibility with existing sites and GitHub releases.

= Does this work with any theme? =

Yes. The plugin ships frontend styles and uses Bootstrap 5 on booking pages. Container width is configurable in **Settings → Layout**.

= Can partners add their own listings? =

Yes. Enable the partner portal, assign the auto-created pages, and vendors can register, log in, and manage listings from the front end.

= How is price displayed? =

Set currency and position under **Settings → General**. Each listing has base price, optional sale price, and suffix (e.g. `/ night` or `/ booking`).

= Is WooCommerce required? =

No. WooCommerce integration is optional and loads only when WooCommerce is active.

= What happens when I delete the plugin? =

Custom database tables and plugin options are removed on uninstall. Use the `ulbm_uninstall_remove_all_data` filter to keep data if needed.

== Screenshots ==

1. Admin dashboard with booking statistics and charts
2. Single listing page with image gallery and booking form
3. Listing grid with AJAX filters and price cards
4. Settings page — colors, layout, container width, and shortcodes
5. Partner portal — vendor dashboard and listing management

== Changelog ==

= 1.0.5 =
* Fix text domain: all plugin strings use `flex-multiple-listing-and-booking-system` only
* Submission ZIP excludes `dev-tools/` via `.distignore`

= 1.0.4 =
* WordPress.org: `.distignore` and ZIP build script exclude `dev-tools/` and source files
* Gutenberg block render callbacks escape shortcode HTML via `wp_kses_post()`
* License key tab in Settings (optional purchase activation)
* Partners admin page and listing grid improvements

= 1.0.3 =
* Demo content: bundled local placeholder images in `assets/demo/` (no remote Picsum downloads)
* Removed invalid external-service link from readme

= 1.0.2 =
* Plugin slug and text domain: flex-multiple-listing-and-booking-system
* Partner registration: pending approval by default, no auto-login until approved
* Google Maps embed opt-in; external services documented in readme
* Sanitization and JSON decode hardening; admin scripts enqueued
* Upgraded bundled Bootstrap 5.3.8 and Chart.js 4.5.1

= 1.0.1 =
* Rebranded to Flex Listings and Booking Manager
* Improved color settings save and scoped backgrounds to plugin UI only
* WordPress.org readiness: security index files, uninstall hook, i18n
* Added GitHub source link and WordPress.org ZIP submission instructions in readme

= 1.0.0 =
* Initial public release
* Booking types, listings, grid filters, single page templates
* Partner portal with auto-created pages
* Gutenberg blocks and Elementor widgets
* Demo content importer
* REST API and admin dashboard

== Upgrade Notice ==

= 1.0.1 =
Improved color settings and plugin branding. Settings and data are preserved on upgrade.
