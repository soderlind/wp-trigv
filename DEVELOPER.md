# Trigv — Developer Guide

How to send your own notifications, shape or veto dispatches, register custom
Triggers, and work on the plugin itself.

All hooks use the `trigv_` prefix. The PHP namespace is `Trigv\`.

## Concepts

- **Trigger** — a WordPress hook the admin has chosen to watch.
- **Notification** — the payload sent to Trigv (channel, title, …).
- **Dispatch** — turning a fired Trigger (or a `trigv_send` call) into a
  Notification and sending it asynchronously via Action Scheduler.
- **Token** — a `{placeholder}` in a title/description template, resolved from a
  Trigger's context.

See [CONTEXT.md](CONTEXT.md) for the full glossary.

## Sending a Notification from code

Fire the `trigv_send` action from anywhere. The Notification is queued and sent
in the background (with retries), so it never blocks the request.

```php
do_action( 'trigv_send', array(
	'channel'     => 'general',
	'title'       => 'Deploy complete',
	'description' => 'Build #123 shipped to production.',
	'level'       => 'success', // info | success | warning | error
) );
```

Full set of arguments:

```php
do_action( 'trigv_send', array(
	'channel'          => 'ops',
	'title'            => 'Payment webhook failed',
	'description'      => 'Stripe returned 500 for charge ch_123.',
	'level'            => 'error',
	'event_type'       => 'stripe.webhook.failed', // machine-readable type
	'delivery_urgency' => 'time_sensitive',        // break through iOS Focus
	'image_url'        => 'https://example.com/snapshot.jpg',
	'idempotency_key'  => 'stripe-ch_123-failed',  // dedupe retries
) );
```

From a cron job:

```php
add_action( 'my_nightly_report', function () {
	$sales = my_get_todays_sales();
	do_action( 'trigv_send', array(
		'channel'     => 'reports',
		'title'       => sprintf( 'Nightly report: %d orders', $sales['count'] ),
		'description' => sprintf( 'Revenue: %s', $sales['total'] ),
		'level'       => 'info',
		'event_type'  => 'report.nightly',
	) );
} );
```

### Notification arguments

| Key | Required | Notes |
| --- | --- | --- |
| `channel` | Yes | Trigv channel slug. |
| `title` | Yes | Max 255 chars. |
| `description` | No | Max 1000 chars. |
| `level` | No | `info` (default), `success`, `warning`, `error`. Styling only. |
| `event_type` | No | Machine-readable, e.g. `deploy.complete`. |
| `delivery_urgency` | No | `standard` (default) or `time_sensitive`. |
| `image_url` | No | HTTPS image passed through to devices. |
| `idempotency_key` | No | Stable key so retries don't double-count. Auto-generated if omitted. |

## Shaping every dispatch — `trigv_dispatch_args`

Applied to **every** Notification (from a Trigger or from `trigv_send`) right
before it is queued. Return the (possibly modified) args.

Route all errors to a dedicated channel and make them urgent:

```php
add_filter( 'trigv_dispatch_args', function ( array $args, array $context ) {
	if ( 'error' === ( $args['level'] ?? '' ) ) {
		$args['channel']          = 'critical';
		$args['delivery_urgency'] = 'time_sensitive';
	}
	return $args;
}, 10, 2 );
```

`$context` tells you where the dispatch came from:

```php
add_filter( 'trigv_dispatch_args', function ( array $args, array $context ) {
	// $context['source']  => Trigger id (e.g. 'post_published') or 'manual'
	// $context['trigger'] => human label
	$args['title'] = '[' . get_bloginfo( 'name' ) . '] ' . $args['title'];
	return $args;
}, 10, 2 );
```

## Vetoing a dispatch — `trigv_pre_dispatch`

Return `false` (or a `WP_Error`) to suppress a Notification.

Never notify from staging/local:

```php
add_filter( 'trigv_pre_dispatch', function ( $send, array $args ) {
	if ( 'production' !== wp_get_environment_type() ) {
		return false;
	}
	return $send;
}, 10, 2 );
```

Mute a specific event type overnight:

```php
add_filter( 'trigv_pre_dispatch', function ( $send, array $args ) {
	$hour = (int) current_time( 'G' );
	if ( 'comment.created' === ( $args['event_type'] ?? '' ) && ( $hour >= 23 || $hour < 7 ) ) {
		return false;
	}
	return $send;
}, 10, 2 );
```

## Controlling the client IP — `trigv_client_ip`

Used by the "Failed login" Trigger's `{ip}` token. Default is `REMOTE_ADDR`.

Behind Cloudflare (use the real visitor IP):

```php
add_filter( 'trigv_client_ip', function ( string $ip ) {
	if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
	}
	return $ip;
} );
```

Privacy-first (store a hashed IP instead of the raw address):

```php
add_filter( 'trigv_client_ip', fn( string $ip ) => hash( 'sha256', $ip . wp_salt() ) );
```

## Adding headers to Trigv requests — `trigv_request_headers`

Every request to the Trigv API sends a `User-Agent: wp-trigv/<version>`
identifier alongside the required headers. Add or override headers here — e.g.
a client/site identifier:

```php
add_filter( 'trigv_request_headers', function ( array $headers ) {
	$headers['X-Trigv-Client'] = 'my-site-id';
	return $headers;
} );
```

## Widening "Post published" — `trigv_post_published_types`

By default the "Post published" Trigger fires only for `post`. Pages have their
own "Page published" Trigger; add any other public post types here:

```php
add_filter( 'trigv_post_published_types', function ( array $types ) {
	$types[] = 'page';
	$types[] = 'product';
	return $types;
} );
```

## Registering custom Triggers — `trigv_triggers`

Push `Trigv\Trigger` instances onto the catalog. Each Trigger declares the
WordPress hook to watch and a `resolver` that turns the hook's arguments into a
Token map (or returns `null` to skip that firing). The admin can then enable it
and set its channel/level/template like any built-in Trigger.

### Example: notify when a user is promoted to administrator

```php
add_filter( 'trigv_triggers', function ( array $triggers ) {
	if ( ! class_exists( \Trigv\Trigger::class ) ) {
		return $triggers;
	}

	$triggers[] = new \Trigv\Trigger(
		id:                  'user_became_admin',
		label:               'User promoted to administrator',
		group:               'Security',
		event_type:          'user.role.admin',
		default_level:       'warning',
		default_title:       'New administrator: {user_login}',
		default_description: '{display_name} ({user_email})',
		tokens: array(
			'user_login'   => 'Username',
			'display_name' => 'Display name',
			'user_email'   => 'Email address',
		),
		hook:          'set_user_role',
		priority:      10,
		accepted_args: 3, // $user_id, $role, $old_roles
		resolver: static function ( $user_id, $role, $old_roles = array() ): ?array {
			// Only fire when someone *becomes* an admin.
			if ( 'administrator' !== $role || in_array( 'administrator', (array) $old_roles, true ) ) {
				return null;
			}
			$user = get_userdata( (int) $user_id );
			return $user ? array(
				'user_login'   => $user->user_login,
				'display_name' => $user->display_name,
				'user_email'   => $user->user_email,
			) : null;
		},
	);

	return $triggers;
} );
```

### Example: WooCommerce order completed

A complete Add-on lives in
[`examples/woocommerce-trigv-addon`](examples/woocommerce-trigv-addon/woocommerce-trigv-addon.php):

```php
add_filter( 'trigv_triggers', function ( array $triggers ) {
	if ( ! class_exists( \Trigv\Trigger::class ) ) {
		return $triggers;
	}

	$triggers[] = new \Trigv\Trigger(
		id:                  'woo_order_completed',
		label:               'WooCommerce order completed',
		group:               'WooCommerce',
		event_type:          'woo.order.completed',
		default_level:       'success',
		default_title:       'Order #{order_id} completed',
		default_description: '{total} from {customer}',
		tokens: array(
			'order_id' => 'Order number',
			'total'    => 'Order total',
			'customer' => 'Customer name',
		),
		hook:          'woocommerce_order_status_completed',
		priority:      10,
		accepted_args: 2, // $order_id, $order
		resolver: static function ( $order_id, $order = null ): ?array {
			$order = $order instanceof \WC_Order ? $order : wc_get_order( (int) $order_id );
			return $order ? array(
				'order_id' => (string) $order->get_id(),
				'total'    => html_entity_decode( wp_strip_all_tags( $order->get_formatted_order_total() ) ),
				'customer' => $order->get_formatted_billing_full_name(),
			) : null;
		},
	);

	return $triggers;
} );
```

### Trigger constructor reference

| Argument | Type | Notes |
| --- | --- | --- |
| `id` | string | Unique slug (also the config key). |
| `label` | string | Shown in the admin. |
| `group` | string | UI grouping (e.g. `Content`, `Security`). |
| `event_type` | string | Sent to Trigv as `event_type`. |
| `default_level` | string | `info`/`success`/`warning`/`error`. |
| `default_title` | string | Template with `{tokens}`. |
| `default_description` | string | Template with `{tokens}`. |
| `tokens` | array | `token => label` map shown as hints. |
| `hook` | string | WordPress hook to watch. |
| `priority` | int | Hook priority. |
| `accepted_args` | int | Number of args the hook passes. |
| `resolver` | Closure | `fn( ...$args ): ?array` — Token map, or `null` to skip. |

### Templating & Tokens

Titles and descriptions are templates. Each `{token}` is replaced by the value
the resolver returned for that key; unknown tokens are left untouched. Admins
can override a Trigger's title/description in the UI, and the placeholders shown
are exactly the Trigger's declared tokens.

## REST API

The admin app talks to these routes under `trigv/v1`. All require
`manage_options` and a valid `wp_rest` nonce.

| Method | Route | Purpose |
| --- | --- | --- |
| GET / POST | `/trigv/v1/settings` | Read / update connection (API key never returned). |
| GET / POST | `/trigv/v1/triggers` | Read catalog + config / save per-Trigger config. |
| POST | `/trigv/v1/test` | Send a test Notification. |
| GET / DELETE | `/trigv/v1/log` | Read / clear the recent-dispatch log. |

## Development

```bash
composer install     # PHP deps (Action Scheduler, GitHub updater)
npm install          # JS deps

npm run start        # Watch/rebuild the admin app
npm run build        # Production build

composer test        # PHPUnit (Brain Monkey, no WordPress required)
npm test             # Vitest (jsdom)
npm run lint:js      # ESLint (src only)
```

## Architecture

| Class | Responsibility |
| --- | --- |
| `Plugin` | Bootstrap and wiring |
| `Settings` | Connection config (API key, defaults) |
| `TriggerCatalog` / `Trigger` | Registry of available Triggers |
| `TriggerConfig` | Per-Trigger configuration (enabled, channel, level, templates) |
| `Notification` | Immutable notification value object — build, validate, payload |
| `Dispatcher` | Enqueue, send, and retry Notifications |
| `TrigvClient` | Adapts the [trigv-php](https://github.com/Trigv/trigv-php) SDK to the dispatch contract |
| `WpHttpClient` | Routes SDK requests through the WordPress HTTP API |
| `RestController` | `trigv/v1` REST routes for the admin app |
| `AdminPage` | Menu + React app mount |
| `Log` | Recent-dispatch ring buffer |

See [docs/adr](docs/adr) for architecture decisions.
