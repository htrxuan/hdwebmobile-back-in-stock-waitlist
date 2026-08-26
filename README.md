# HDWebmobile Back In Stock & Waitlist

Let customers join a waitlist for out-of-stock products, and reliably notify them when restocked.

- **WordPress.org:** https://wordpress.org/plugins/hdwebmobile-back-in-stock-waitlist/
- **Requires:** WordPress 6.9+, WooCommerce, PHP 7.4+
- **License:** GPLv2 or later

## Description

HDWebmobile Back In Stock & Waitlist adds a "Notify Me" signup to out-of-stock products, then reliably emails subscribers when the item comes back — processed through WooCommerce's own Action Scheduler in the background rather than sent inline, so a slow or failed send never gets silently dropped.

## Features

* AJAX "Notify Me" signup on out-of-stock simple products — no page reload
* Restock detection hooks WooCommerce's own stock-status change event, reliably
* Notifications are queued through Action Scheduler (bundled with WooCommerce) and logged on every attempt, success or failure
* Fair batching: if only a few units are restocked, only that many of the oldest waiting subscribers are notified — the rest stay queued for the next restock or a manual batch
* Notification email is a real WooCommerce transactional email (WooCommerce > Settings > Emails), so merchant email styling and subject/heading customization apply automatically
* One-click, no-login-required unsubscribe link in every notification
* Admin waitlist screen (WooCommerce > Waitlist) with search, status filter, and a manual "Notify next batch" action

## Development

Standard WordPress plugin structure:

```
hdwebmobile-back-in-stock-waitlist.php    Bootstrap
includes/class-hdbis-activator.php
includes/class-hdbis-admin-list-table.php
includes/class-hdbis-admin.php
includes/class-hdbis-ajax.php
includes/class-hdbis-core.php
includes/class-hdbis-email.php
includes/class-hdbis-frontend.php
includes/class-hdbis-hub.php
includes/class-hdbis-notifier.php
includes/class-hdbis-repository.php
includes/class-hdbis-stock-watcher.php
includes/class-hdbis-unsubscribe.php
```

Part of the [HDWebmobile](https://hdwebmobile.com/plugins/) suite of focused, single-purpose WooCommerce plugins.

## License

GPLv2 or later. See [LICENSE](LICENSE).

