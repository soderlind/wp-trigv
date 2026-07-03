# Trigv

Send WordPress events as push notifications via [Trigv](https://trigv.com).

Trigv watches the WordPress events (**Triggers**) you choose and dispatches them
as push notifications (**Notifications**) through the Trigv API. Dispatch happens
asynchronously in the background via Action Scheduler, with retries, so your site
stays fast.

<img width="100%" alt="Screenshot 2026-07-03 at 16 54 25" src="https://github.com/user-attachments/assets/c3d27774-de41-4f61-8383-c3d4a7d3c8a5" />


## Features

- Curated catalog of built-in WordPress Triggers (posts, comments, users,
  security, maintenance).
- Per-Trigger channel, level, title/description template, and time-sensitive
  delivery — with sensible defaults.
- Asynchronous, retrying delivery through Action Scheduler.
- React (`@wordpress/scripts`) admin screen: Connection, Triggers, and Log tabs.
- Developer API to send your own Notifications and to shape or veto any dispatch.
- Extensible catalog so Add-ons (e.g. WooCommerce) can register more Triggers.
- Self-hosted updates from GitHub releases.

## Requirements

- WordPress 6.8+
- PHP 8.3+

## Installation

Install the release zip (which bundles `vendor/` and `build/`) from the
[Releases](https://github.com/soderlind/wp-trigv/releases) page, or build from
source:

```bash
composer install
npm install
npm run build
```

## Configuration

1. Open **Trigv** in the WordPress admin menu.
2. On the **Connection** tab, paste your Trigv API key (`trgv_…`) and set a
   default channel/level. Use **Send test** to verify.
3. On the **Triggers** tab, enable the events you want and optionally override
   their channel, level, or templates.

The API key may instead be defined in `wp-config.php` (it then overrides the
stored value and is hidden from the UI):

```php
define( 'TRIGV_API_KEY', 'trgv_xxxx_yyyy' );
```

An optional GitHub token raises update-check rate limits:

```php
define( 'TRIGV_GITHUB_TOKEN', 'ghp_xxx' );
```

## Built-in Triggers

| Trigger | WordPress hook | Default level |
| --- | --- | --- |
| Post published | `transition_post_status` | success |
| Page published | `transition_post_status` | info |
| Post updated | `post_updated` | info |
| New comment | `comment_post` | info |
| Comment needs moderation | `comment_post` | warning |
| New user registered | `user_register` | info |
| Failed login | `wp_login_failed` | warning |
| Plugin or theme updated | `upgrader_process_complete` | info |
| Automatic updates completed | `automatic_updates_complete` | info |

## Developer API

All hooks use the `trigv_` prefix.

### Send a Notification

```php
do_action( 'trigv_send', array(
	'channel'     => 'general',
	'title'       => 'Deploy complete',
	'description' => 'Build #123 shipped.',
	'level'       => 'success',           // info | success | warning | error
	// Optional: event_type, delivery_urgency, image_url, idempotency_key
) );
```

### Reshape every dispatch

```php
add_filter( 'trigv_dispatch_args', function ( array $args, array $context ) {
	// $context['source'] is a Trigger id or 'manual'.
	return $args;
}, 10, 2 );
```

### Veto a dispatch

```php
add_filter( 'trigv_pre_dispatch', function ( bool $send, array $args ) {
	// Return false (or a WP_Error) to suppress this Notification.
	return $send;
}, 10, 2 );
```

### Control the client IP for security Triggers

```php
add_filter( 'trigv_client_ip', fn( string $ip ) => hash( 'sha256', $ip ) );
```

### Widen which post types "Post published" watches

By default the "Post published" Trigger fires only for posts (pages have their
own "Page published" Trigger). Add more public post types:

```php
add_filter( 'trigv_post_published_types', function ( array $types ) {
	$types[] = 'page';
	$types[] = 'product';
	return $types;
} );
```

## Writing an Add-on

Register extra Triggers by pushing `Trigv\Trigger` instances onto the
`trigv_triggers` filter. A complete WooCommerce example lives in
[`examples/woocommerce-trigv-addon`](examples/woocommerce-trigv-addon/woocommerce-trigv-addon.php):

```php
add_filter( 'trigv_triggers', function ( array $triggers ) {
	if ( ! class_exists( \Trigv\Trigger::class ) ) {
		return $triggers;
	}

	$triggers[] = new \Trigv\Trigger(
		id: 'woo_order_completed',
		label: 'WooCommerce order completed',
		group: 'WooCommerce',
		event_type: 'woo.order.completed',
		default_level: 'success',
		default_title: 'Order #{order_id} completed',
		default_description: '{total} from {customer}',
		tokens: array( 'order_id' => 'Order number', 'total' => 'Order total', 'customer' => 'Customer name' ),
		hook: 'woocommerce_order_status_completed',
		priority: 10,
		accepted_args: 2,
		resolver: static function ( $order_id, $order = null ) {
			$order = wc_get_order( (int) $order_id );
			return $order ? array(
				'order_id' => (string) $order->get_id(),
				'total'    => wp_strip_all_tags( $order->get_formatted_order_total() ),
				'customer' => $order->get_formatted_billing_full_name(),
			) : null;
		},
	);

	return $triggers;
} );
```

## Development

```bash
composer install     # PHP deps (Action Scheduler, GitHub updater)
npm install          # JS deps

npm run start        # Watch/rebuild the admin app
npm run build        # Production build

composer test        # PHPUnit (Brain Monkey, no WordPress required)
npm test             # Vitest (jsdom)
```

### Architecture

| Class | Responsibility |
| --- | --- |
| `Plugin` | Bootstrap and wiring |
| `Settings` | Connection config (API key, defaults) |
| `TriggerCatalog` / `Trigger` | Available Triggers + per-Trigger config |
| `Dispatcher` | Build, enqueue, send, retry Notifications |
| `TrigvClient` | HTTP client for the Trigv API |
| `RestController` | `trigv/v1` REST routes for the admin app |
| `AdminPage` | Menu + React app mount |
| `Log` | Recent-dispatch ring buffer |

See [CONTEXT.md](CONTEXT.md) for the domain glossary and
[docs/adr](docs/adr) for architecture decisions.

## License

GPL-2.0-or-later
