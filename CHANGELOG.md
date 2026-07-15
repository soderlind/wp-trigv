# Changelog

## 2.0.2 - 2026-07-15

- Add a GitHub Actions workflow to sync WordPress.org plugin assets on release.
- Add a plugin icon.
- Add **Installation** and **Frequently Asked Questions** sections to the readme.

## 2.0.1 - 2026-07-14

- Update the bundled Action Scheduler library to 4.0.0.

## 2.0.0 - 2026-07-14

- Prepared for the WordPress.org Plugin Directory: renamed to **Push
  Notifications for Trigv** with text domain `push-notifications-for-trigv`.
- Added an **External services** disclosure to `readme.txt` documenting the data
  sent to the Trigv API, plus links to Trigv's Terms and Privacy Policy.
- Removed the self-hosted GitHub updater; WordPress.org is now the sole update
  channel. (Supersedes ADR 0003.)
- Escaped exception messages in the HTTP client and prefixed the uninstall
  globals for WordPress coding-standards compliance.
- Internal: moved the plugin's PHP namespace to `Soderlind\Trigv` to avoid
  clashing with the bundled `trigv-php` SDK (which owns `Trigv\`).
- Packaging: added `.distignore` and wired the build/deploy workflows to it.

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
