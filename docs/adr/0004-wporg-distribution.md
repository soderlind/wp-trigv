# 4. WordPress.org distribution: slug, name, and packaging

Date: 2026-07-14

## Status

Accepted. Supersedes [ADR 0003](0003-self-hosted-github-updater.md).

## Context

We decided to publish the plugin in the WordPress.org Plugin Directory. wp.org
imposes constraints that the self-hosted GitHub edition did not:

- Plugin slugs may not contain the restricted term "wp"; the previous slug
  `wp-trigv` is rejectable and is permanent once approved.
- Hosted plugins must receive updates from wp.org — a bundled updater that
  overrides the update mechanism is disallowed.
- The plugin must disclose any external services it contacts.
- Only runtime files may ship; dev tooling, tests, and sources must be excluded.

## Decision

- Public name **Push Notifications for Trigv**; slug and text domain
  **`push-notifications-for-trigv`**; main file
  `push-notifications-for-trigv.php`. The "… for Trigv" form follows wp.org's
  convention for a plugin that integrates a named third-party service.
- Remove the self-hosted GitHub updater (see superseded ADR 0003).
  WordPress.org is the sole update channel.
- Disclose the Trigv external service (`api.trigv.com`), the data sent, and
  links to Trigv's Terms and Privacy Policy, in `readme.txt`.
- Package via a `.distignore` consumed by both the GitHub build-zip workflows
  and the `10up/action-wordpress-plugin-deploy` SVN deploy (on release). Ship
  `vendor/` built `--no-dev` and the committed `build/` assets.
- The local working directory and Git repository keep working, but the GitHub
  repository is renamed to `push-notifications-for-trigv` to match the slug.

## Consequences

- The slug/name are now a permanent public identity.
- Releases go through wp.org review; cadence is slower than self-hosted.
- The `.distignore` is the single source of truth for what ships; the previous
  inline rsync exclude list (which incorrectly excluded all of `src/`) is gone.
- Existing self-hosted installs do not auto-migrate to the wp.org listing.
