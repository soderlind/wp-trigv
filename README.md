# Push Notifications for Trigv

> Renamed to **Push Notifications for Trigv** in version 2.0.0, previously known as **Trigv for WordPress**. Did this to comply with WordPress.org Plugin Directory naming rules, and to clarify that the plugin is a Trigv client, not a Trigv service.

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

## Developer documentation

Building on the plugin — sending your own notifications, the filters
(`trigv_dispatch_args`, `trigv_pre_dispatch`, `trigv_client_ip`,
`trigv_post_published_types`), registering custom Triggers via `trigv_triggers`,
the REST API, local development, and architecture — is documented in
**[DEVELOPER.md](DEVELOPER.md)**.

See [CONTEXT.md](CONTEXT.md) for the domain glossary and
[docs/adr](docs/adr) for architecture decisions.

## License

GPL-2.0-or-later
