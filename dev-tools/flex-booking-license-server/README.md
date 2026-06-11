# Flex Booking License Server

**Install on wprogers.com only** — do not include in the public Flex Booking plugin ZIP for WordPress.org.

Free custom license key server for [Flex Listings and Booking Manager](../..). No EDD or paid extensions required.

## Install

1. Zip this folder as `flex-booking-license-server` or upload it to:
   ```
   wp-content/plugins/flex-booking-license-server/
   ```
2. Activate **Flex Booking License Server** in wp-admin.
3. Go to **Flex Licenses** in the admin menu.

## API endpoint

The Flex Booking client plugin calls:

```
POST https://wprogers.com/wp-json/flex-booking/v1/license
Content-Type: application/json

{
  "license_key": "FLEX-XXXX-XXXX-XXXX-XXXX",
  "site_url": "https://customer-site.com",
  "action": "activate",
  "item_slug": "flex-multiple-listing-and-booking-system",
  "version": "1.0.3"
}
```

Actions: `activate`, `deactivate`, `check`.

## Manual license generation

1. **Flex Licenses** → **Generate key**
2. Set customer email, activation limit (1 = single site, 0 = unlimited), validity
3. Copy the key and send it to the customer
4. Customer enters it in **Flex Listings & Booking → Settings → License**

## WooCommerce (optional)

If WooCommerce is installed on wprogers.com:

1. Edit your plugin product
2. Check **Flex license key**
3. Set activation limit and license days (empty = lifetime)
4. On order **Completed**, a key is generated and appended to the order email

## Features

- Unique keys: `FLEX-XXXX-XXXX-XXXX-XXXX`
- Per-site activation tracking
- Activation limits (1 site, 5 sites, unlimited)
- Expiry dates or lifetime
- Suspend / revoke keys
- View and remove active sites
- Daily re-check from client plugin

## Filters (wprogers.com)

```php
// Custom key prefix
add_filter( 'fbls_license_key_prefix', fn() => 'WPR' );
```

## Test with curl

```bash
curl -X POST "https://wprogers.com/wp-json/flex-booking/v1/license" \
  -H "Content-Type: application/json" \
  -d "{\"license_key\":\"FLEX-....\",\"site_url\":\"https://example.com\",\"action\":\"activate\"}"
```

Expected when valid:

```json
{"success":true,"status":"active","license":"valid","message":"License activated successfully.","expires":""}
```
