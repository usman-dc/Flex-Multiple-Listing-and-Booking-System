# WordPress.org directory assets

PNG files in this folder are ready to upload (icons, banners, screenshots 1–5).

**Banners:** run `dev-tools/generate-banners.ps1` (modern vector-style, full bleed).

**Icons:** run `dev-tools/generate-wporg-assets.ps1`.

Upload these files to the **SVN assets folder** for the plugin:

`https://plugins.svn.wordpress.org/flex-multiple-listing-and-booking-system/assets/`

## Required files

| File | Dimensions |
|------|------------|
| `icon-128x128.png` | 128 × 128 px |
| `icon-256x256.png` | 256 × 256 px |
| `banner-772x250.png` | 772 × 250 px |
| `banner-1544x500.png` | 1544 × 500 px |
| `screenshot-1.png` | 1200 × 900 px (recommended) |
| `screenshot-2.png` | … |
| `screenshot-3.png` | … |
| `screenshot-4.png` | … |
| `screenshot-5.png` | … |

## SVN commands (example)

```bash
svn co https://plugins.svn.wordpress.org/flex-multiple-listing-and-booking-system
cd flex-multiple-listing-and-booking-system/assets
# copy PNG files here
svn add *.png
svn commit -m "Add plugin icons, banner, and screenshots"
```

Plugin code goes in `/trunk/`. Tag releases as `/tags/1.0.0/`.

Screenshots are listed in the root `readme.txt` under `== Screenshots ==`.
