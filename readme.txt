=== Trust Badges ===
Contributors: converswp, parvezvai, shamsbd71
Author URL: https://converswp.com/trust-badges/
Tags: badges, marketing, icons, trust, payment
Requires at least: 5.8
Tested up to: 7.0
Stable tag: 1.2.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Trust Badges adds customizable trust icons and badges to WooCommerce and EDD, boosting customer confidence and sales.

== Description ==

Trust Badges is a WordPress plugin designed to increase customer trust and boost conversions by displaying customizable trust badges on your e-commerce store. Whether you're using WooCommerce or Easy Digital Downloads (EDD), this plugin allows you to add, customize, and manage badges with ease.

Badges such as secure payment icons, money-back guarantees, free delivery, and other trust signals help reassure customers during the purchase process. You can also use shortcodes to place badges anywhere on your site, including product pages, checkout, and cart pages.

= Key Features =

* **Add and Manage Trust Badges:** Easily add, edit, and remove trust badges to build customer confidence.
* **Customizable Badges:** Adjust badge appearance, size, and position to match your store's branding.
* **Shortcode Support:** Display badges anywhere on your site using simple shortcodes.
* **Mobile-Responsive Design:** Badges look great on all devices.
* **Works with WooCommerce and EDD:** Fully compatible with WooCommerce and Easy Digital Downloads.
* **Settings Panel:** Manage all settings under **Settings -> Trust Badges** in the WordPress admin panel.
* **Pre-built Badge Library:** Choose from hundreds of pre-designed trust badges including payment icons, security badges, and delivery guarantees.
* **Custom Badge Upload:** Upload your own custom badges and integrate them seamlessly.

= Why Use Trust Badges? =

Increase customer trust by displaying secure payment icons, money-back guarantees, delivery badges, and other trust signals that reassure customers during the buying process. Studies show that trust badges can increase conversion rates by up to 15%.

= Badge Categories =

* **Payment Methods:** Visa, Mastercard, PayPal, Apple Pay, Stripe, Amazon Pay, and many more
* **Security Badges:** SSL certificates, secure checkout, and data protection
* **Delivery & Shipping:** Free delivery, fast shipping, and tracking options
* **Guarantees:** Money-back guarantee, satisfaction guarantee, and warranty
* **Certifications:** Industry certifications and compliance badges
* **Custom Badges:** Upload your own promotional or brand-specific badges

== Installation ==

1. Upload the `trust-badges` directory to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Settings -> Trust Badges** to manage your badges.

Alternatively, you can:
* Install directly from the WordPress plugin repository.
* Activate and start customizing your badges from the WordPress dashboard.

== Screenshots ==

1. Trust Badge Product Page Display
2. Badge Selection Panel  
3. Settings Panel
4. Documentation & Help
5. Admin Dashboard Overview

== Usage ==

After activation, go to the **Settings -> Trust Badges** section in the WordPress admin menu to manage your badges:

* **Add New Badges:** Upload images or select from pre-designed badges.
* **Customize Badges:** Change sizes, colors, and positions.
* **Shortcodes:** Use shortcodes to display badges anywhere on your site.

= Shortcode Usage =

The plugin uses badge groups to organize different sets of badges. Each group can have different settings, selected badges, and display options.

Display badges by group ID:
`[trust_badges id="product_page"]`
`[trust_badges id="cart"]`
`[trust_badges id="footer"]`

Display badges without specifying an ID (defaults to the footer group):
`[trust_badges]`

**Built-in Badge Groups:**
* **product_page**: Badges for product pages with payment methods
* **cart**: Badges for cart pages with checkout assurance
* **footer**: Badges for footer display with trust signals

Groups you create yourself use the ID shown next to them in the settings screen. Numeric IDs (for example `[trust_badges id="1"]`) are also accepted and resolve to the matching group.

You can create and manage badge groups in the plugin settings under **Settings -> Trust Badges**.

= WooCommerce Integration =

The plugin automatically integrates with WooCommerce and displays badges on:
* Product pages (after the add-to-cart form)
* Cart page (both the classic cart and the Cart block)

= Easy Digital Downloads Integration =

For EDD users, badges are displayed on:
* Download pages (after the purchase link)

Badges can be placed anywhere else, including checkout, using the shortcode.

== Frequently Asked Questions ==

= Can I use this plugin without WooCommerce or EDD? =

Yes, while this plugin is optimized for WooCommerce and Easy Digital Downloads, you can use the shortcodes to display badges on any WordPress site.

= Are the badges mobile responsive? =

Yes, all badges are fully responsive and look great on any device.

= What types of badges can I add? =

You can add a variety of badges, including secure payment icons, money-back guarantees, delivery badges, and custom promotional icons to boost customer trust.

= Can I upload my own custom badges? =

Absolutely! You can upload your own images and customize their appearance within the admin panel.

= Where can I find the settings? =

The settings for Trust Badges are located under **Settings -> Trust Badges** in your WordPress dashboard.

= How many badges can I display? =

There's no limit to the number of badges you can display. However, we recommend keeping it to 3-5 badges for optimal user experience.

= Can I customize the appearance of the badges? =

Yes, you can customize badge size, spacing, alignment, and even add custom CSS for advanced styling.

= Do the badges affect page load speed? =

The plugin is optimized for performance. Badges are loaded efficiently and won't significantly impact your site's loading speed.

== Requirements ==

* **WordPress Version:** 5.8 or higher
* **WooCommerce or Easy Digital Downloads (Optional but recommended)**
* **PHP Version:** 7.4 or higher

== Support ==

For support, please visit the [plugin support page](https://converswp.com/trust-badges/) or create an issue on our [GitHub repository](https://github.com/converswp/trust-badges).

== Changelog ==

= 1.2.0 =

**Security**

* Removed the unauthenticated `/badges` REST endpoint, which exposed every badge group's stored configuration to anonymous visitors.
* Removed an IP-keyed rate limiter that wrote a transient per request from a spoofable `X-Forwarded-For` header, allowing the options table to be flooded.
* Client IP detection now trusts only `REMOTE_ADDR`; sites behind a reverse proxy can opt in via the new `trust_badges_client_ip` filter.
* Database error messages are logged instead of being returned in REST responses.
* Group IDs are validated against a strict character set before use in markup, CSS selectors and queries.
* Settings payloads are recursively sanitised and depth-limited before being stored.
* Deleting a group now re-reads the `is_default` flag from the database rather than trusting a cached value.
* Added direct-file-access guards to all PHP files.

**Performance**

* Badge lookups no longer re-read and re-parse the 459-entry badge manifest once per badge. The manifest is parsed at most once per request and cached; badge rendering dropped from roughly 1 ms per badge to effectively zero.
* `assets/badges.json` is 50% smaller and no longer embeds absolute URLs.
* The admin bundle is now minified and ships without its source map, removing about 3 MB from the settings screen.
* The checkout-group cleanup no longer runs a database query on every page load; it runs once per version.
* `wp-admin/includes/plugin.php` is no longer loaded on every front-end request.
* Groups that do not exist are now negatively cached instead of being re-queried on every page load.
* Badge images decode asynchronously.

**Fixes**

* Removed a global `woocommerce_should_load_cart_block` / `woocommerce_should_load_checkout_block` override that disabled the WooCommerce Cart and Checkout blocks site-wide.
* The "mono" and "mono-card" badge styles now render: their CSS mask was being stripped before output.
* Saving settings now clears the front-end cache, so changes appear immediately instead of after an hour.
* Shortcode IDs now resolve correctly. `[trust_badges id="footer"]` and numeric IDs both work; previously neither did.
* Unknown badge IDs are skipped instead of causing a fatal error.
* Removed two REST routes whose callbacks did not exist and which referenced a database column that was never created.
* Fixed a `wpdb::prepare()` call with no placeholders that triggered a `_doing_it_wrong()` notice on WordPress 6.2+.
* Added missing array-key guards that raised warnings, and could fatal, on PHP 8.
* `dbDelta()` can now upgrade the schema on existing installs.
* Corrected the shortcode and integration documentation, which described placements and group IDs that did not exist.

**Compatibility**

* Tested up to WordPress 7.0.
* Minimum WordPress version raised to 5.8.

= 1.1.0 =
* Page support: Product, Cart
* Page footer support 
* Custom short code support  

= 1.0.0 =
* Initial release of the Trust Badges plugin.
* Added shortcode support for flexible badge placement.
* Customization options for badge appearance and position.
* Full compatibility with WooCommerce and Easy Digital Downloads.
* Pre-built library of 400+ trust badges.
* Custom badge upload functionality.
* Mobile-responsive design.
* Admin settings panel for easy management.