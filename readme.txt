=== Push Notifications for Trigv ===
Contributors: PerS
Tags: notifications, push, events, webhook, trigv
Requires at least: 6.8
Tested up to: 7.0
Requires PHP: 8.3
Stable tag: 2.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send WordPress events as push notifications via Trigv.

== Description ==

Trigv watches the WordPress events (Triggers) you choose and dispatches them as
push notifications through the Trigv API. Dispatch happens asynchronously in the
background, with retries, so your site stays fast.

* Pick from a curated catalog of WordPress Triggers.
* Map each Trigger to a Trigv channel and level, with an optional custom title/description template.
* Fire your own notifications from code with `do_action( 'trigv_send', $args )`.
* Shape or veto any notification with the `trigv_dispatch_args` and `trigv_pre_dispatch` filters.
* Add-ons can register more Triggers via the `trigv_triggers` filter.

== Developer API ==

Full developer guide (custom Triggers, filters, REST API, examples): https://github.com/soderlind/push-notifications-for-trigv/blob/main/DEVELOPER.md

Send a notification:

`do_action( 'trigv_send', array(
	'channel'     => 'general',
	'title'       => 'Deploy complete',
	'description' => 'Build #123 shipped.',
	'level'       => 'success',
) );`

Reshape every dispatch:

`add_filter( 'trigv_dispatch_args', function ( array $args, array $context ) {
	return $args;
}, 10, 2 );`

Veto a dispatch:

`add_filter( 'trigv_pre_dispatch', function ( bool $send, array $args ) {
	return $send;
}, 10, 2 );`

== Configuration ==

Set the API key on the Trigv admin screen, or define it in `wp-config.php`:

`define( 'TRIGV_API_KEY', 'trgv_xxxx_yyyy' );`

== External services ==

This plugin connects to Trigv, a third-party push-notification service, to deliver the WordPress events you choose as notifications on your devices.

When a Trigger you have enabled fires — or you send a test notification, or you call `do_action( 'trigv_send', ... )` — the plugin sends an HTTPS request to the Trigv API at https://api.trigv.com. Each request includes:

* the notification channel, title, and (optionally) description, level, event type, delivery urgency, image URL, and an idempotency key;
* your Trigv API key, sent as an `Authorization` request header;
* a `User-Agent` request header identifying the plugin and its version.

No data is sent until you enter a Trigv API key and enable at least one Trigger (or trigger a manual/test send). Trigv stores only event metadata such as timestamps, delivery status, and usage counts; notification content is delivered to your devices and is not retained on Trigv servers.

Service provider: Webtions OÜ (dba Trigv).
Terms of Service: https://trigv.com/terms
Privacy Policy: https://trigv.com/legal/privacy-policy

== Changelog ==

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
