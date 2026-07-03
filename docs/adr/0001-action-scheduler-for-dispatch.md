# 1. Use Action Scheduler for asynchronous Dispatch

Date: 2026-07-03

## Status

Accepted

## Context

When a watched Trigger fires (e.g. `publish_post`), the plugin must Dispatch a
Notification to the Trigv HTTP API. Doing this synchronously inside the request
adds external-API latency to user-facing actions (publishing a post would wait
on Trigv) and surfaces Trigv outages directly to admins. We need decoupled,
retryable background delivery.

Options considered:

- **Synchronous** `wp_remote_post()` in the hook — simplest, but couples user
  requests to Trigv latency/availability.
- **WP-Cron** via `wp_schedule_single_event` — dependency-free async, but
  WP-Cron only fires on site traffic and has no built-in retry/backoff.
- **Action Scheduler** — a robust, battle-tested background job queue (bundled
  library, used by WooCommerce) with retries, claims, and admin visibility.

## Decision

Use **Action Scheduler** as the background queue for all Dispatches. Every
Dispatch is enqueued as an async action and sent from the queue worker, with
retry/backoff on transient failure.

## Consequences

- Adds a bundled dependency (Action Scheduler) to the plugin.
- Reliable delivery with retries and idempotency-key support, independent of
  the originating request.
- Provides admin-visible queue/logging (Action Scheduler's own tooling).
- Slightly heavier footprint than WP-Cron; justified by delivery reliability.
