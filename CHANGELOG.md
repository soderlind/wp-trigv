# Changelog

## 1.3.0 - 2026-07-14

- Send events using the official [trigv-php](https://github.com/Trigv/trigv-php)
  SDK for request building, validation, and typed error handling.
- Route SDK requests through the WordPress HTTP API via a new `WpHttpClient`
  adapter, preserving proxy/SSL config and the `trigv_request_headers` filter.
  Asynchronous Action Scheduler retries are unchanged.

## 1.2.1 - 2026-07-04

- Add a `User-Agent: wp-trigv/<version>` identifier header on requests to Trigv,
  plus a `trigv_request_headers` filter to add or override request headers.

## 1.2.0 - 2026-07-03

- Internal: introduce an immutable `Notification` value object; the Trigv HTTP
  client is now transport-only.
- Internal: split per-Trigger configuration into a dedicated module, separate
  from the Trigger catalog.
- No functional changes for existing sites.

## 1.1.0 - 2026-07-03

- Add a "Page published" Trigger.
- Add the `trigv_post_published_types` filter to control which post types the
  "Post published" Trigger watches (defaults to posts only).

## 1.0.1 - 2026-07-03

- Fix REST route error ("No route was found") when saving settings — now uses
  WordPress core's apiFetch configuration with full REST paths.
- Show saved-key state in the API key field (label and masked placeholder).
- Pre-fill Trigger title and description fields with their default templates.

## 1.0.0 - 2026-07-03

- Initial release.
