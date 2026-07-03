# Context — wp-trigv

Ubiquitous language for the wp-trigv plugin. Glossary only — no implementation
details.

## Glossary

### Trigger

A WordPress hook the admin has chosen to watch (the source side). When a
Trigger fires, it may lead to a Dispatch. Example: `publish_post`,
`user_register`, `comment_post`.

Not to be confused with a Trigv "event" (see Notification).

### Notification

The Trigv event we send (the destination side) — the JSON payload POSTed to
the Trigv API that becomes a push notification on the admin's devices. Has a
channel, title, and optional description, level, etc.

### Dispatch

The act of turning a fired Trigger into a Notification and sending it to Trigv.

### event (lowercase, literal only)

Reserved exclusively for quoting the Trigv API literally — the `/events`
endpoint and the `event_type` body field. Never used for the WordPress side.

### Notification Template

The rule that turns a fired Trigger into a Notification's title and
description. Every supported Trigger ships with a **default** template; the
admin may optionally override the title/description per Trigger. Templates
contain Tokens.

### Token

A placeholder inside a Notification Template, resolved from the fired Trigger's
context. Example: `{post_title}`, `{user_login}`. Each Trigger exposes its own
set of Tokens.

### Trigger Catalog

The registry of all available Triggers the admin can choose from. The core
plugin registers a curated built-in set; Add-ons extend the catalog through a
public filter so extra Triggers (e.g. WooCommerce order events) can be shipped
separately.

### Trigger Config

The admin's per-Trigger configuration: whether a Trigger is enabled, and its
channel, level, Notification Template overrides, and delivery urgency. Held
separately from the Trigger Catalog.

### Add-on

A separate plugin that registers additional Triggers (and their Tokens /
default Templates) into the Trigger Catalog via the public extension filter.
