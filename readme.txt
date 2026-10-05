=== Rejoyan CRM & Campaigns for WooCommerce ===
Contributors: rejoyan9009
Tags: woocommerce, email-marketing, crm, coupons, social-sharing
Requires at least: 6.5
Tested up to: 7.1
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Customer CRM, offer email templates, smart segments, scheduled campaigns, delivery reports, invoices and live product sharing for WooCommerce.

== Description ==

Rejoyan CRM & Campaigns is a WooCommerce marketing workspace inside WordPress. It helps store managers prepare reusable product-linked offers, select registered customers or smart segments, queue marketing email, review delivery status, resend failures, trigger WooCommerce invoice email, and share live store products to social networks.

Rejoyan CRM & Campaigns keeps its campaign data in the WordPress database and uses WooCommerce APIs for products, orders and coupons. Marketing email is handed to WordPress through `wp_mail()`; the plugin does not include a mail-delivery service or tracking pixel.

Rejoyan CRM & Campaigns is independently developed and is not affiliated with or endorsed by WooCommerce or Automattic.

= Customer Email Center and Segments =

* Load registered WooCommerce customers in one admin workspace.
* Select all eligible customers, selected customers, or smart segments.
* Segments include VIP, repeat, inactive, new, no-order and recent buyers.
* Target registered customers who previously bought a selected product.
* Unsubscribed customers are skipped automatically.
* Sending requires an administrator confirmation that a lawful basis exists for the selected marketing audience.

= Offer Email Templates =

* Create, edit, duplicate, archive and restore reusable offers.
* Ready-made Flash Sale, New Arrival, VIP Exclusive, Weekend Deal, Product Spotlight and Back in Stock presets.
* Link an offer to a real published WooCommerce product.
* Use live product name, price and image in the email.
* Optional WooCommerce coupon and coupon auto-apply flow.
* Classic, Minimal and Spotlight responsive HTML layouts.
* Merge tags: `{{first_name}}`, `{{last_name}}`, `{{email}}`, `{{site_name}}`, `{{product_name}}`, `{{product_price}}`, `{{product_url}}`.
* Optional offer start/end dates and scheduled sending.

= Queue, Analytics and Reliability =

* Recipient snapshot and per-recipient pending, sent, failed and skipped states.
* WooCommerce Action Scheduler when available, with WP-Cron fallback.
* Campaign preview, test email, cancellation and failed-recipient retry.
* Delivery totals, 14-day send volume and CSV reporting.
* System Health page for queue, cron, database, WooCommerce and sender checks.
* No open tracking or click tracking is stored.

= Live Product Social Studio =

* Reads only published products from the current WooCommerce store; no demo products are inserted.
* Search, category, sale and stock filters with AJAX Load More.
* Live image, price, stock, category and SKU information.
* Editable caption, copy link/caption, device-native share when available.
* Self-contained 3D-style Facebook, X, LinkedIn and WhatsApp share controls.

= Performance and Privacy =

* Admin pages render a lightweight shell before expensive store data is loaded.
* Customer, product, analytics and order panels load only when needed.
* Rejoyan CRM & Campaigns admin/report classes are not loaded on normal storefront requests.
* WordPress personal-data exporter and eraser integration is included.
* Optional full Rejoyan CRM & Campaigns data cleanup is available on uninstall.

Site owners are responsible for obtaining any required consent or other lawful basis for marketing communications and for configuring a suitable WordPress mail transport.

== Installation ==

1. Install and activate WooCommerce.
2. Upload and activate Rejoyan CRM & Campaigns.
3. Open **Rejoyan CRM & Campaigns > Settings** and configure sender details.
4. Configure a reliable SMTP or transactional mail transport for WordPress before production sending.
5. Create an offer, then use **Rejoyan CRM & Campaigns > Email Center** to select the audience and queue or schedule it.

== Frequently Asked Questions ==

= Does Rejoyan CRM & Campaigns send customer data to its own cloud service? =

No. Rejoyan CRM & Campaigns has no bundled cloud service. Email is passed to WordPress `wp_mail()`. A separately configured SMTP/transactional mail plugin may use its own provider under the site owner's configuration.

= Can I send to every registered customer? =

The plugin includes an all-registered-customer workflow, but unsubscribed customers are skipped and the administrator must confirm they are allowed to send the marketing message to the selected audience.

= Does Rejoyan CRM & Campaigns track opens or clicks? =

No tracking pixel is inserted and click events are not stored. Product-linked offer buttons only resolve the saved offer, optionally prepare its coupon, and redirect to the linked product.

= Does it support WooCommerce HPOS? =

Yes. Rejoyan CRM & Campaigns declares HPOS compatibility and uses WooCommerce order APIs rather than directly reading legacy order posts.

= Does uninstall remove Rejoyan CRM & Campaigns data? =

Not by default. Enable the explicit cleanup option in Rejoyan CRM & Campaigns Settings before uninstalling if you want Rejoyan CRM & Campaigns tables, settings and Rejoyan CRM & Campaigns marketing user metadata removed.

== External Services ==

Rejoyan CRM & Campaigns does not make automatic server-side requests to a Rejoyan CRM & Campaigns cloud service and does not bundle third-party tracking.

When an administrator clicks a Social Studio share button, the browser opens the selected third-party network's sharing URL. This is a user-initiated action.

* Facebook: https://www.facebook.com/ — Terms: https://www.facebook.com/terms.php — Privacy: https://www.facebook.com/privacy/policy/
* X: https://x.com/ — Terms: https://x.com/en/tos — Privacy: https://x.com/en/privacy
* LinkedIn: https://www.linkedin.com/ — User Agreement: https://www.linkedin.com/legal/user-agreement — Privacy: https://www.linkedin.com/legal/privacy-policy
* WhatsApp: https://www.whatsapp.com/ — Terms: https://www.whatsapp.com/legal/terms-of-service — Privacy: https://www.whatsapp.com/legal/privacy-policy

Email delivery is performed through WordPress `wp_mail()`. Any external SMTP or transactional-email service is configured separately by the site owner and is not included in Rejoyan CRM & Campaigns.

== Screenshots ==

1. Rejoyan CRM & Campaigns dashboard and queue overview.
2. Customer Email Center with saved-offer and audience workflow.
3. Offer Template Studio with product linking.
4. Delivery Analytics and campaign history.
5. Live Product Social Studio using real WooCommerce products.
6. System Health checks.

== Changelog ==

= 1.0.0 =
* Finalized the first WordPress.org submission release.
* Resolved pre-submission Plugin Check findings for internationalization, nonce checks, input sanitization and custom-table SQL handling.
* Added server-side marketing-permission confirmation to standard queued campaigns.
* Hardened CSV report cells against spreadsheet formula execution.
* Kept the v0.8 automation, scheduling, retry, System Health, smart segments, product-linked templates and live product Social Studio feature set.
* Reworked release documentation and human-readable assets for WordPress.org review.

Full version history is included in `changelog.txt`.

== Upgrade Notice ==

= 1.0.0 =
First stable submission release. Existing v0.8.0 data and database schema are preserved; no new schema migration is required.
