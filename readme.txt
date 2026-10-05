=== Rejoyan CRM & Campaigns for WooCommerce ===
Contributors: rejoyan9009
Tags: woocommerce, crm, email-marketing, coupons, customer-management
Requires at least: 6.5
Tested up to: 7.1
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage WooCommerce customers, build product offers, schedule email campaigns, review sending reports and share store products.

== Description ==

Rejoyan CRM & Campaigns brings customer management and email campaign tools into your WooCommerce store. Create reusable offers linked to your products, choose a customer audience, schedule marketing emails and review campaign sending results from your WordPress dashboard.

Use smart customer segments, prepare product offers with optional coupons, send test emails, retry failed messages and trigger WooCommerce invoice emails. The Social Studio helps you prepare captions and share published store products through Facebook, X, LinkedIn and WhatsApp sharing links.

Rejoyan CRM & Campaigns keeps its campaign data in the WordPress database and uses WooCommerce APIs for products, orders and coupons. Marketing email is handed to WordPress through `wp_mail()`; the plugin does not include a mail-delivery service or tracking pixel.

Rejoyan CRM & Campaigns is independently developed and is not affiliated with or endorsed by WooCommerce or Automattic.

= Customer Email Center and Segments =

* Load registered WooCommerce customers in one admin workspace.
* Select all eligible customers, selected customers, or smart segments.
* Segments include VIP, repeat, inactive, new, no-order and recent buyers.
* Target registered customers who previously bought a selected product.
* Unsubscribed customers are skipped automatically.
* New campaign and offer sending forms include an administrator marketing-permission confirmation. Site owners must ensure every audience is eligible before sending.

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
* Mail handoff totals, 14-day send volume and CSV reporting. A successful handoff to WordPress mail transport does not confirm inbox delivery.
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
* Optional cleanup of plugin-owned data is available on uninstall; database table structures are retained.

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

The plugin includes an all-registered-customer workflow and skips customers marked as unsubscribed. Site owners must ensure they are allowed to contact their selected audience before queueing a campaign.

= Does Rejoyan CRM & Campaigns track opens or clicks? =

No tracking pixel is inserted and click events are not stored. Product-linked offer buttons only resolve the saved offer, optionally prepare its coupon, and redirect to the linked product.

= Does it support WooCommerce HPOS? =

Rejoyan CRM & Campaigns declares HPOS compatibility and uses WooCommerce order APIs. Test the plugin with your store configuration before production use.

= Does uninstall remove Rejoyan CRM & Campaigns data? =

Not by default. Enable the explicit cleanup option in Settings before uninstalling to clear plugin-owned table data, settings and marketing preference metadata. Database table structures are retained.

== External Services ==

Rejoyan CRM & Campaigns does not make automatic server-side requests to a Rejoyan CRM & Campaigns cloud service and does not bundle third-party tracking.

When an administrator clicks a Social Studio share button, the browser opens the selected third-party network's sharing URL. This is a user-initiated action.

* Facebook: https://www.facebook.com/ — Terms: https://www.facebook.com/terms.php — Privacy: https://www.facebook.com/privacy/policy/
* X: https://x.com/ — Terms: https://x.com/en/tos — Privacy: https://x.com/en/privacy
* LinkedIn: https://www.linkedin.com/ — User Agreement: https://www.linkedin.com/legal/user-agreement — Privacy: https://www.linkedin.com/legal/privacy-policy
* WhatsApp: https://www.whatsapp.com/ — Terms: https://www.whatsapp.com/legal/terms-of-service — Privacy: https://www.whatsapp.com/legal/privacy-policy

Email delivery is performed through WordPress `wp_mail()`. Any external SMTP or transactional-email service is configured separately by the site owner and is not included in Rejoyan CRM & Campaigns.

== Screenshots ==

1. Dashboard overview with customer totals, campaign sending counters, queue status and recent campaigns. Presented with a branded frame.
2. Customer Email Center showing registered customers, eligible emails and the first-offer setup state. Presented with a branded frame.
3. Customer Segments for VIP, repeat, inactive, new, no-order and recent buyers. Presented with a branded frame.
4. System Health showing scheduled campaigns, queue activity, failed recipients and WooCommerce, cron, database and sender readiness checks.
5. Delivery Analytics showing mail handoff totals, send volume, skipped and pending counts, CSV export and campaign history. The example has no campaign data.
6. Social Studio showing published WooCommerce product cards, editable captions, copy controls and social sharing buttons. These products have no images assigned.
7. Original Customer Email Center interface showing customer and email counters and the prompt to create the first reusable offer.

== Changelog ==

= 1.0.0 =
* Initial WordPress.org release with customer segments, reusable offers, email campaigns, delivery reporting, System Health and product social sharing.

Full version history is included in `changelog.txt`.

== Upgrade Notice ==

= 1.0.0 =
First stable submission release. Existing v0.8.0 data and database schema are preserved; no new schema migration is required.
