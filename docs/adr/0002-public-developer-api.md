# 2. Public developer API: `trigv_send` action + catalog filter

Date: 2026-07-03

## Status

Accepted

## Context

Two audiences extend the plugin: developers who want to send their own
Notifications from code, and Add-ons that register extra Triggers into the
Trigger Catalog. Both are public surfaces — once shipped, third parties depend
on their names and signatures, making them expensive to change.

## Decision

Commit to a stable, `trigv_`-prefixed public API:

- **`do_action( 'trigv_send', array $args )`** — primary developer entry point
  to Dispatch a Notification (accepts `channel`, `title`, `description`,
  `level`, `event_type`, `delivery_urgency`, `idempotency_key`, `image_url`).
- **`apply_filters( 'trigv_dispatch_args', array $args, array $context )`** —
  applied to every Notification (Trigger- or action-originated) before send, so
  code can rewrite fields.
- **`apply_filters( 'trigv_pre_dispatch', bool $send, array $args )`** — return
  `false`/`WP_Error` to veto a Dispatch.
- **A catalog-registration filter** (e.g. `trigv_triggers`) letting Add-ons add
  Triggers with their Tokens and default Templates.

## Consequences

- These names/signatures are a compatibility contract; changes require
  deprecation cycles.
- Enables the Add-on strategy (WooCommerce etc.) without core changes.
- Conditional-send logic (deferred from v1) is achievable today via
  `trigv_pre_dispatch`.
