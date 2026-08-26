=== HDWebmobile Back In Stock & Waitlist ===
Contributors: htrxuan
Donate link: https://paypal.me/htrxuan/20
Tags: woocommerce, back in stock, waitlist, restock notification, notify me
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.2
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Let customers join a waitlist for out-of-stock products, and reliably notify them when restocked.

== Description ==

HDWebmobile Back In Stock & Waitlist adds a "Notify Me" signup to out-of-stock products, then reliably emails subscribers when the item comes back — processed through WooCommerce's own Action Scheduler in the background rather than sent inline, so a slow or failed send never gets silently dropped.

= Key Features =
* AJAX "Notify Me" signup on out-of-stock simple products — no page reload
* Restock detection hooks WooCommerce's own stock-status change event, reliably
* Notifications are queued through Action Scheduler (bundled with WooCommerce) and logged on every attempt, success or failure
* Fair batching: if only a few units are restocked, only that many of the oldest waiting subscribers are notified — the rest stay queued for the next restock or a manual batch
* Notification email is a real WooCommerce transactional email (WooCommerce > Settings > Emails), so merchant email styling and subject/heading customization apply automatically
* One-click, no-login-required unsubscribe link in every notification
* Admin waitlist screen (WooCommerce > Waitlist) with search, status filter, and a manual "Notify next batch" action

= Limitations (please read before installing) =
* Email notifications only — no SMS/push in this version
* Single-step signup (email + optional name); no double opt-in — a deliberate choice to keep signup friction low
* Variable-product (per-variation) waitlist signup and restock detection is included in the code but is **not currently reliable on all WooCommerce versions**: some versions have a data-layer issue where saving stock changes to an *existing* product variation does not persist the change or fire the usual stock hooks. Test variation restock notifications on your own site before relying on them; simple products are unaffected and fully supported.
* Fair batching depends on the product tracking a real stock quantity; if stock isn't managed, all waiting subscribers are notified at once
* The manual "Notify next batch" admin action runs synchronously on click; very large pending waitlists (several hundred+) could approach PHP's execution time limit

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/hdwebmobile-back-in-stock-waitlist` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress. WooCommerce must already be installed and active.
3. That's it — out-of-stock products automatically show a "Notify Me" signup. Manage subscribers and customize the notification email under WooCommerce > Waitlist and WooCommerce > Settings > Emails.

== How to Use ==

= 1. No setup required to start collecting signups =
As soon as the plugin is active, any **simple product** with stock status "Out of stock" automatically shows an **"Enter your email and we'll let you know when this is back in stock"** form in place of the normal Add to Cart button (Screenshot 1). There's nothing to configure to turn this on.

= 2. What a customer sees =
They enter their email (name is optional) and click **Notify Me**. The form submits over AJAX — no page reload — and immediately confirms **"You're on the list! We'll email you when it's back."** (Screenshot 2).

= 3. What happens when you restock =
Update the product's stock status back to "In stock" (or increase its stock quantity) the normal way, through the product edit screen, a CSV import, or any other plugin. This plugin hooks WooCommerce's own stock-status-change event — you don't need to do anything else. Notification jobs are then queued through Action Scheduler (bundled with WooCommerce) and sent in the background, so a large waitlist never blocks your restock save.

= 4. Fair batching for limited restocks =
If you only restocked a few units, only that many of the **oldest** waiting subscribers are notified — the rest stay queued for the next restock, or you can notify a batch manually (see below). This prevents an email blast promising stock to more people than you actually have.

= 5. Manage the waitlist =
Go to **WooCommerce > Waitlist** (Screenshot 3) to see every signup with its product, email, name, status (**Waiting** / **Notified**), signup date, and notified date. Search by email or filter by status, and use the **"Notify next batch"** action if you want to trigger a send manually instead of waiting for the next stock update.

= 6. Customize the notification email =
The notification is a real WooCommerce transactional email under **WooCommerce > Settings > Emails**, so it inherits your store's email header/footer styling automatically and its subject/heading are editable there like any other WooCommerce email.

= 7. Unsubscribing =
Every notification email includes a one-click unsubscribe link that needs no login — customers who no longer want restock alerts can remove themselves instantly.

== Screenshots ==

1. The "Notify Me" signup form, shown automatically on an out-of-stock product.
2. The instant AJAX confirmation after a customer signs up.
3. The WooCommerce > Waitlist admin screen — signups move from "Waiting" to "Notified" once the restock email goes out.

== Changelog ==

= 1.0.2 =
* Confirmed compatibility with WordPress 7.1.
* Renamed the internal hub-coordination class to a plugin-specific name for WordPress.org naming-convention compliance. No functional changes.

= 1.0.1 =
* The Waitlist admin screen now lives under WooCommerce > HDWebmobile as a tab, alongside every other HDWebmobile plugin you have active, instead of its own separate WooCommerce submenu item. No functional changes to waitlist behavior.

= 1.0.0 =
* Initial release: AJAX waitlist signup for simple products and variations, Action Scheduler-driven restock notifications, fair quantity-aware batching, WC_Email integration, one-click unsubscribe, admin waitlist management screen.
