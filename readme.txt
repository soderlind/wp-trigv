=== Trigv ===
Contributors: PerS
Tags: notifications, push, events, webhook, trigv
Requires at least: 6.8
Tested up to: 7.0
Requires PHP: 8.3
Stable tag: 1.1.0
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

Full developer guide (custom Triggers, filters, REST API, examples): https://github.com/soderlind/wp-trigv/blob/main/DEVELOPER.md

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

== Changelog ==

= 1.1.0 =
* Add a "Page published" Trigger.
* Add the `trigv_post_published_types` filter to control which post types the "Post published" Trigger watches (defaults to posts only).

= 1.0.1 =
* Fix REST route error ("No route was found") when saving settings — now uses WordPress core's apiFetch configuration with full REST paths.
* Show saved-key state in the API key field (label and masked placeholder).
* Pre-fill Trigger title and description fields with their default templates.

= 1.0.0 =
* Initial release.
