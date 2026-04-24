=== WooCommerce Product Table ===
Contributors:      chandananvithahrwp
Tags:              woocommerce, product table, sortable table, filterable table, add to cart
Requires at least: 5.8
Tested up to:      6.5
Stable tag:        1.0.0
Requires PHP:      7.4
WC requires at least: 6.0
WC tested up to:      8.9
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Display WooCommerce products in a beautiful, sortable, filterable table with AJAX add-to-cart — no page reload.

== Description ==

**WooCommerce Product Table** replaces the standard WooCommerce shop grid with a compact, information-dense table layout — perfect for B2B stores, wholesale catalogs, and any shop where customers need to compare and buy multiple products quickly.

= Free Features =

* Simple `[woo_product_table]` shortcode — drop it anywhere
* **Columns:** product image thumbnail, name, price, stock status, quantity input, Add to Cart button
* **Column sorting** by name, price, or rating — click any sortable header
* **Filter bar:** instant client-side search by name, category dropdown, In Stock Only checkbox
* **AJAX add-to-cart** — products added without a full page reload; mini-cart updates automatically
* **Admin settings** under WooCommerce > Product Table: choose visible columns, default sort column/order, items per page (10 / 25 / 50)
* Fully **accessible**: ARIA roles, `aria-sort`, keyboard navigation on sortable headers
* **Responsive**: graceful column hiding on small screens
* CSS custom properties for easy theming

= Pro Features =

Upgrade to [WooCommerce Product Table Pro](https://chandananvithahr.github.io/woo-product-table-site/#pricing) to unlock:

* **CSV export** — download the current product list as a spreadsheet
* **Custom columns** — add any product meta field, ACF field, or custom taxonomy as a column
* **Advanced pagination** — numbered page buttons rendered server-side with AJAX page switching
* **Drag-and-drop column ordering** in the admin
* Priority email support

= Shortcode Attributes =

| Attribute  | Default        | Description                                      |
|------------|---------------|--------------------------------------------------|
| `category` | *(empty)*     | Comma-separated category slug(s) to pre-filter   |
| `per_page` | *(setting)*   | Number of products to show (10, 25, or 50)       |
| `sort`     | *(setting)*   | Column to sort by: `name`, `price`, or `rating`  |
| `order`    | *(setting)*   | `ASC` or `DESC`                                  |

= Examples =

Show all products:
`[woo_product_table]`

Show only the "shirts" category, sorted by price low → high:
`[woo_product_table category="shirts" sort="price" order="ASC"]`

Show top-rated products, 25 per page:
`[woo_product_table sort="rating" order="DESC" per_page="25"]`

== Installation ==

= Automatic (recommended) =

1. Go to **Plugins > Add New** in your WordPress dashboard.
2. Search for **WooCommerce Product Table**.
3. Click **Install Now**, then **Activate**.

= Manual =

1. Download the plugin ZIP file.
2. Go to **Plugins > Add New > Upload Plugin**.
3. Upload the ZIP, then click **Install Now** and **Activate**.

= After activation =

1. Navigate to **WooCommerce > Product Table** to configure columns, sort defaults, and items per page.
2. Add `[woo_product_table]` to any page, post, or widget.

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =

Yes. WooCommerce must be installed and active. The plugin will display an admin notice and remain inactive if WooCommerce is missing.

= Will it conflict with my theme? =

The plugin ships with minimal, scoped CSS using the `.wpt-*` prefix. You can override any style using the provided CSS custom properties or your theme's stylesheet.

= Can I show only certain categories? =

Yes — use the `category` attribute: `[woo_product_table category="hoodies,t-shirts"]`.

= Does AJAX add-to-cart work with variable products? =

The free version handles **simple** and **grouped** products. Variable products display an "—" button and require the customer to visit the product page to select options. Variable product AJAX support is on the Pro roadmap.

= Is the table accessible? =

Yes. The table uses `role="grid"`, proper `<thead>/<tbody>` structure, `scope="col"`, `aria-sort` on sortable headers, `aria-live` on the cart message region, and full keyboard navigation.

= Where are the settings? =

Go to **WooCommerce > Product Table** in your WordPress admin.

= Will my settings be deleted when I deactivate the plugin? =

No. Settings are preserved. They are only removed if you delete the plugin entirely (uninstall).

== Screenshots ==

1. Product table on the front end with search and filter bar
2. Column sorting — price ascending
3. AJAX add-to-cart with success message
4. Admin settings page under WooCommerce > Product Table
5. Mobile view with responsive column hiding

== Changelog ==

= 1.0.0 — 2026-04-24 =
* Initial public release.
* Shortcode renderer with full column set.
* Client-side search, category filter, and in-stock checkbox.
* Server-side sorting by name, price, and rating.
* AJAX add-to-cart with quantity support.
* Admin settings page under WooCommerce menu.
* Free vs Pro feature gating with upgrade notice.
* Fully accessible and responsive.

== Upgrade Notice ==

= 1.0.0 =
Initial release. No upgrade steps required.

== License ==

WooCommerce Product Table is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any later version.

WooCommerce Product Table is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
