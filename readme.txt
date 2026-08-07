=== Trust Badges – Payment Icons & Secure Checkout Seals for WooCommerce ===
Contributors: converswp, parvezvai, shamsbd71
Tags: trust badges, woocommerce, payment icons, trust seal, checkout
Requires at least: 5.8
Tested up to: 7.0
Stable tag: 1.2.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add trust badges, payment icons and secure checkout seals to WooCommerce and EDD. 450+ ready-made SVG badges, shortcodes, no coding needed.

== Description ==

**Trust Badges is a free WordPress plugin that displays payment icons, security seals and guarantee badges on your product, cart and footer areas.** It works with WooCommerce and Easy Digital Downloads, and on any WordPress site through a shortcode.

Shoppers look for familiar payment logos and reassurance signals before they hand over card details. Trust Badges lets you place those signals exactly where the hesitation happens — under the add-to-cart button, on the cart page, or in the footer — without writing any code or uploading image files.

= What's New in 1.2.0 =

Version 1.2.0 is a substantial security, performance and correctness release. Highlights:

* **Faster front end.** The badge manifest is now parsed once per request instead of once per badge, and results are cached. Badge rendering went from roughly 1 ms per badge to effectively zero.
* **Lighter admin.** The settings screen bundle is minified and no longer ships a source map — about 3 MB less to download.
* **Fixed: WooCommerce Cart and Checkout blocks.** A leftover filter in earlier versions disabled WooCommerce's Cart and Checkout blocks across the whole site. It has been removed.
* **Fixed: monochrome badge styles.** The "mono" and "mono-card" styles now render correctly; their CSS mask was previously being stripped before output.
* **Fixed: shortcodes.** `[trust_badges id="footer"]` and numeric IDs both resolve now. Previously neither did.
* **Fixed: settings changes appear immediately** instead of after a one-hour cache delay.
* **Security.** Removed an unauthenticated REST endpoint that exposed badge configuration to anonymous visitors, and hardened input validation throughout.

See the changelog for the complete list.

= Key Features =

* **459 ready-made SVG badges.** Payment logos, security seals, delivery and money-back guarantees — all scalable vector graphics, so they stay sharp on any screen.
* **Works where it matters.** Automatic placement on WooCommerce product and cart pages, and EDD download pages. Anywhere else via shortcode.
* **Badge groups.** Configure a different set of badges, with different styling, for each location.
* **Four display styles.** Original colour, monochrome, card, and monochrome card — recolour any badge to match your theme.
* **No coding required.** Everything is configured from **Settings → Trust Badges**.
* **Lightweight.** SVG badges, cached rendering, and no front-end JavaScript.
* **Free and open source.** GPLv2 or later, no paid tier required for any feature listed here.

= At a Glance =

* **What it does:** Displays payment icons, trust seals and guarantee badges on a WordPress site.
* **Works with:** WooCommerce, Easy Digital Downloads, or any WordPress theme via shortcode.
* **Badge library:** 459 SVG badges included.
* **Automatic placements:** WooCommerce product page, WooCommerce cart (classic and Cart block), EDD download page, site footer.
* **Shortcode:** `[trust_badges id="footer"]`
* **Front-end JavaScript:** None.
* **Cost:** Free, GPLv2 or later.
* **Settings location:** Settings → Trust Badges.

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

= What is a trust badge? =

A trust badge is a small icon shown near a purchase decision that signals the transaction is safe — a payment logo such as Visa or PayPal, an SSL or secure-checkout seal, or a guarantee like "30 day money back". They are most commonly placed under an add-to-cart button, on the cart page, or in the site footer.

= Is Trust Badges free? =

Yes. Trust Badges is free and open source under GPLv2 or later. Every feature described on this page is included; there is no paid tier or upsell required to use them.

= Do I need WooCommerce to use this plugin? =

No. WooCommerce and Easy Digital Downloads get automatic badge placement, but on any other WordPress site you can put badges anywhere with the `[trust_badges]` shortcode.

= Where do badges appear automatically? =

On WooCommerce product pages (under the add-to-cart form), on the WooCommerce cart page (both the classic cart and the Cart block), and on Easy Digital Downloads download pages (after the purchase link). The footer group displays on every page. Anywhere else, use the shortcode.

= How do I add badges to my checkout page? =

Use the shortcode in a block or template on the checkout page, for example `[trust_badges id="checkout"]` after creating a group with that ID. Automatic checkout placement was removed in 1.1.0 because it conflicted with block-based checkouts.

= How many badges does the plugin include? =

459 badges are bundled, all as SVG files. They cover payment methods, banks and wallets, security and SSL seals, shipping and delivery, and money-back and satisfaction guarantees.

= Will this slow down my site? =

It should not. Badges are SVG files rendered server-side, there is no front-end JavaScript, and badge lookups are cached. Version 1.2.0 specifically removed the largest cost: the badge manifest is now parsed once per request rather than once per badge.

= Can I change badge colours to match my theme? =

Yes. The "mono" and "mono-card" styles recolour any badge to a single colour of your choosing using a CSS mask, so payment logos can be made to match your palette. Size, spacing, alignment, border radius and hover effects are all configurable too.

= Can I upload my own custom badges? =

Yes, you can add your own images alongside the bundled library and style them the same way.

= How many badges should I display? =

There is no technical limit. Three to five is a common choice — enough to reassure without crowding the buy button.

= Where are the settings? =

**Settings → Trust Badges** in your WordPress dashboard.

= Does the plugin work with block themes and the WooCommerce Cart block? =

Yes. The Cart block is supported through a dedicated hook. Note that versions before 1.2.0 contained a bug that disabled the WooCommerce Cart and Checkout blocks site-wide; upgrading to 1.2.0 fixes it.

= Does it collect any data? =

No. The plugin makes no external requests and stores only your badge configuration in your own database.

= Is it translation ready? =

Yes, all interface strings use the `trust-badges` text domain.

== Requirements ==

* **WordPress:** 5.8 or higher (tested up to 7.0)
* **PHP:** 7.4 or higher
* **WooCommerce or Easy Digital Downloads:** optional — only needed for automatic placement

== Support ==

For support, please visit the [plugin support page](https://converswp.com/trust-badges/) or create an issue on our [GitHub repository](https://github.com/converswp/trust-badges).

== Upgrade Notice ==

= 1.2.0 =
Recommended for all users. Fixes a bug that disabled the WooCommerce Cart and Checkout blocks site-wide, repairs the shortcode and the monochrome badge styles, removes an unauthenticated REST endpoint that exposed badge configuration, and makes the front end significantly faster.

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