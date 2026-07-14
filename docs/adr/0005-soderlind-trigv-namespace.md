# 5. PHP namespace `Soderlind\Trigv` to disambiguate from the trigv-php SDK

Date: 2026-07-14

## Status

Accepted.

## Context

The plugin adopted the official `trigv/trigv` (trigv-php) SDK for API transport
(see the 2.0.0 changelog). That vendor library owns the root PHP namespace
`Trigv\` (`Trigv\Client`, `Trigv\Http\*`, `Trigv\Exception\*`). Our own code
also lived in `Trigv\` (`Trigv\TrigvClient`, `Trigv\Notification`, …). Sharing a
namespace prefix with a third-party dependency is fragile: class-name
collisions become possible as the SDK grows, and readers cannot tell our code
from vendor code at a glance.

## Decision

Move all first-party classes and constants from `Trigv\` to
**`Soderlind\Trigv\`** (e.g. `Soderlind\Trigv\TrigvClient`,
`Soderlind\Trigv\VERSION`). Class names are unchanged. The SDK keeps `Trigv\`;
our code imports it explicitly (`use Trigv\Client;`, `use Trigv\Exception\*`,
`use Trigv\Http\*`).

## Consequences

- No namespace overlap with the vendored SDK; collisions are impossible.
- One-time churn across `src/`, the test suite, the Composer PSR-4 map, and the
  main-file constants; covered by the existing unit tests.
- wp.org does not require the namespace to match the slug, so this is purely an
  internal-clarity decision, independent of ADR 0004.
