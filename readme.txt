=== Flex Multiple Listing and Booking System ===
Contributors: usmanaliwpdeveloper
Tags: booking, listings, rental, appointment, calendar
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Multiple listing grids and a full booking engine for property rentals, car hire, tours, services, and appointments on WordPress.

== Description ==

**Flex Multiple Listing and Booking System** helps you publish many bookable listings and accept reservations on WordPress — hotels, vacation rentals, car rental, tours, events, and services.

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

* `[fbs_booking_form id="1"]` — booking form for a booking type
* `[fbs_listing_grid type="car-rental" columns="3" limit="12"]` — filterable listing grid
* `[fbs_search]` — search UI placeholder
* `[fbs_register]` — partner registration
* `[fbs_login]` — partner login
* `[fbs_dashboard]` — partner dashboard
* `[fbs_become_partner]` — partner call-to-action block

= External services =

This plugin may load **Bootstrap 5** and **Bootstrap Icons** from jsDelivr CDN on pages that display booking UI. Demo content import may download sample images from picsum.photos. No personal data is sent to these services except standard HTTP requests for assets.

= Privacy =

Booking forms collect customer name, email, phone, and booking details stored in your WordPress database. Configure your site privacy policy for GDPR compliance.

== Installation ==

1. Upload the `flex-booking-system` folder to `/wp-content/plugins/` or install via **Plugins → Add New**
2. Activate **Flex Multiple Listing and Booking System** through the **Plugins** menu
3. Go to **Flex MLS & Booking → Setup** (first run) and choose your industries
4. Open **Flex MLS & Booking → Settings** to configure currency, colors, and layout
5. Partner pages (register, login, dashboard) are created automatically — see **Partner Portal** tab
6. Add listings under the plugin menu or import demo content from **Settings → Demo Content**
7. Embed grids and forms with shortcodes, blocks, or Elementor widgets

= Build from source =

If you clone from Git, run `npm install && npm run build` before use so `/dist` CSS and JS exist.

= Submitting to WordPress.org =

WordPress.org does **not** install plugins directly from GitHub. Use this flow:

1. Download or clone from [GitHub](https://github.com/usman-dc/Flex-Multiple-Listing-and-Booking-System).
2. Create a ZIP of the **`flex-booking-system`** folder (plugin root must contain `flex-booking-system.php` and `readme.txt`).
3. **Exclude** from the ZIP: `node_modules/`, `vendor/`, `.git/`, `.cursor/`, `.vscode/`, `.env` (the included `/dist` folder **must** stay in the ZIP).
4. Submit the ZIP at [WordPress.org Add Plugin](https://wordpress.org/plugins/developers/add/) (requires a WordPress.org account).
5. After approval, releases are published via WordPress.org SVN — keep GitHub and SVN versions in sync.

Reviewers may read the GitHub repo for context; the ZIP upload is still required for review.

== Frequently Asked Questions ==

= Can I submit only my GitHub URL to WordPress.org? =

No. You must upload a plugin ZIP through [wordpress.org/plugins/developers/add/](https://wordpress.org/plugins/developers/add/). Linking GitHub in this readme helps reviewers find source code and report issues.

= How do I prepare the ZIP for WordPress.org? =

Zip the `flex-booking-system` directory so the archive unpacks to one folder named `flex-booking-system`. Include `dist/`, `readme.txt`, and `LICENSE`. Do not include `node_modules`. Run [Plugin Check](https://wordpress.org/plugins/plugin-check/) on your site before submitting.

= Does this work with any theme? =

Yes. The plugin ships frontend styles and uses Bootstrap 5 on booking pages. Container width is configurable in **Settings → Layout**.

= Can partners add their own listings? =

Yes. Enable the partner portal, assign the auto-created pages, and vendors can register, log in, and manage listings from the front end.

= How is price displayed? =

Set currency and position under **Settings → General**. Each listing has base price, optional sale price, and suffix (e.g. `/ night` or `/ booking`).

= Is WooCommerce required? =

No. WooCommerce integration is optional and loads only when WooCommerce is active.

= What happens when I delete the plugin? =

Custom database tables and plugin options are removed on uninstall. Use the `fbs_uninstall_remove_all_data` filter to keep data if needed.

== Screenshots ==

1. Admin dashboard with booking statistics and charts
2. Single listing page with image gallery and booking form
3. Listing grid with AJAX filters and price cards
4. Settings page — colors, layout, container width, and shortcodes
5. Partner portal — vendor dashboard and listing management

== Changelog ==

= 1.0.1 =
* Rebranded to Flex Multiple Listing and Booking System
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
