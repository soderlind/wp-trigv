# Changelog

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
