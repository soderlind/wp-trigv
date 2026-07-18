=== Push Notifications for Trigv ===
Contributors: PerS
Tags: notifications, push, events, webhook, trigv
Requires at least: 6.8
Tested up to: 7.0
Requires PHP: 8.3
Stable tag: 2.0.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send WordPress events as push notifications via Trigv.

== Description ==

[Trigv](https://trigv.com) watches the WordPress events (Triggers) you choose and dispatches them as
push notifications through the Trigv API. Dispatch happens asynchronously in the
background, with retries, so your site stays fast.

* Pick from a curated catalog of WordPress Triggers.
* Map each Trigger to a Trigv channel and level, with an optional custom title/description template.
* Fire your own notifications from code with `do_action( 'trigv_send', $args )`.
* Shape or veto any notification with the `trigv_dispatch_args` and `trigv_pre_dispatch` filters.
* Add-ons can register more Triggers via the `trigv_triggers` filter.

= Developer API =

Full developer guide (custom Triggers, filters, REST API, examples): [DEVELOPER.md](https://github.com/soderlind/push-notifications-for-trigv/blob/main/DEVELOPER.md)


= Configuration =

Set the API key on the Trigv admin screen, or define it in `wp-config.php`:

`define( 'TRIGV_API_KEY', 'trgv_xxxx_yyyy' );`

= External services =

This plugin connects to Trigv, a third-party push-notification service, to deliver the WordPress events you choose as notifications on your devices.

When a Trigger you have enabled fires — or you send a test notification, or you call `do_action( 'trigv_send', ... )` — the plugin sends an HTTPS request to the Trigv API at https://api.trigv.com. Each request includes:

* the notification channel, title, and (optionally) description, level, event type, delivery urgency, image URL, and an idempotency key;
* your Trigv API key, sent as an `Authorization` request header;
* a `User-Agent` request header identifying the plugin and its version.

No data is sent until you enter a Trigv API key and enable at least one Trigger (or trigger a manual/test send). Trigv stores only event metadata such as timestamps, delivery status, and usage counts; notification content is delivered to your devices and is not retained on Trigv servers.

Service provider: Webtions OÜ (dba Trigv).
Terms of Service: https://trigv.com/terms
Privacy Policy: https://trigv.com/legal/privacy-policy

== Installation ==

1. Upload the plugin to the `/wp-content/plugins/` directory, or install it directly from the WordPress.org Plugin Directory via **Plugins > Add New**.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to the **Trigv** admin screen and enter your Trigv API key. Alternatively, define it in `wp-config.php`:

   `define( 'TRIGV_API_KEY', 'trgv_xxxx_yyyy' );`

4. Enable the Triggers you want to watch and map each one to a Trigv channel and level.
5. (Optional) Send a test notification from the admin screen to confirm delivery to your devices.

== Frequently Asked Questions ==

= Do I need a Trigv account? =

Yes. You need a Trigv account and an API key to deliver notifications. Sign up at https://trigv.com.

= Where do I get my API key? =

Create an API key in your Trigv account, then enter it on the Trigv admin screen or define `TRIGV_API_KEY` in `wp-config.php`.

= Does dispatching notifications slow down my site? =

No. Notifications are dispatched asynchronously in the background using Action Scheduler, with automatic retries, so page loads stay fast.

= Can I send my own custom notifications from code? =

Yes. Fire a notification from anywhere with `do_action( 'trigv_send', $args )`. See the Developer API section above and the [full developer guide](https://github.com/soderlind/push-notifications-for-trigv/blob/main/DEVELOPER.md).

= Can I modify or block a notification before it is sent? =

Yes. Use the `trigv_dispatch_args` filter to reshape the data, or the `trigv_pre_dispatch` filter to veto a dispatch. Add-ons can register more Triggers via the `trigv_triggers` filter.

= What data is sent to Trigv? =

Only the notification content you configure (channel, title, and optional description, level, event type, and image), along with your API key and a User-Agent header. Nothing is sent until you enter an API key and enable a Trigger or send a test. See the "External services" section above for full details.

= Why aren't my notifications being delivered? =

Check that you have entered a valid API key, enabled at least one Trigger, and that WP-Cron (or a real cron) is running so Action Scheduler can process the background queue. Sending a test notification from the admin screen helps confirm your setup.

== Changelog ==

= 2.0.2 =
* Add a GitHub Actions workflow to sync WordPress.org plugin assets on release.
* Add a plugin icon.
* Add Installation and Frequently Asked Questions sections to the readme.

= 2.0.1 =
* Update the bundled Action Scheduler library to 4.0.0.

= 2.0.0 =
* Prepared for the WordPress.org Plugin Directory: renamed to "Push Notifications for Trigv" with text domain `push-notifications-for-trigv`.
* Added an "External services" disclosure documenting the data sent to the Trigv API.
* Removed the self-hosted GitHub updater; updates now come from WordPress.org.
* Escaped exception messages and prefixed uninstall globals for coding-standards compliance.
* Internal: moved the plugin's PHP namespace to `Soderlind\Trigv` to avoid clashing with the bundled trigv-php SDK.

= 1.3.0 =
* Send events using the official trigv-php SDK for request building, validation, and typed error handling.
* Route SDK requests through the WordPress HTTP API via a new `WpHttpClient` adapter, preserving proxy/SSL config and the `trigv_request_headers` filter. Asynchronous retries are unchanged.

= 1.2.1 =
* Add a `User-Agent` identifier header on requests to Trigv, plus a `trigv_request_headers` filter to add or override request headers.

= 1.2.0 =
* Internal: introduce an immutable `Notification` value object; the Trigv HTTP client is now transport-only.
* Internal: split per-Trigger configuration into a dedicated module, separate from the Trigger catalog.
* No functional changes for existing sites.

= 1.1.0 =
* Add a "Page published" Trigger.
* Add the `trigv_post_published_types` filter to control which post types the "Post published" Trigger watches (defaults to posts only).

= 1.0.1 =
* Fix REST route error ("No route was found") when saving settings — now uses WordPress core's apiFetch configuration with full REST paths.
* Show saved-key state in the API key field (label and masked placeholder).
* Pre-fill Trigger title and description fields with their default templates.

= 1.0.0 =
* Initial release.
