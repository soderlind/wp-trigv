<?php
/**
 * Trigger value object — one entry in the Trigger Catalog.
 *
 * @package Trigv
 */

declare(strict_types=1);

namespace Soderlind\Trigv;

defined( 'ABSPATH' ) || exit;

/**
 * Describes a watchable WordPress hook and how to turn it into a Notification.
 */
final class Trigger {

	/**
	 * @param string               $id                 Unique slug, e.g. `post_published`.
	 * @param string               $label              Human label for the admin UI.
	 * @param string               $group              UI grouping, e.g. `Content`.
	 * @param string               $event_type         Dotted machine type, e.g. `post.published`.
	 * @param string               $default_level      One of info|success|warning|error.
	 * @param string               $default_title      Default title template (with Tokens).
	 * @param string               $default_description Default description template (with Tokens).
	 * @param array<string,string> $tokens             Token slug => label map.
	 * @param string               $hook               WordPress hook name to watch.
	 * @param int                  $priority           Hook priority.
	 * @param int                  $accepted_args      Number of args the hook passes.
	 * @param \Closure             $resolver           fn(...$args): ?array — Token map, or null to skip.
	 */
	public function __construct(
		public readonly string $id,
		public readonly string $label,
		public readonly string $group,
		public readonly string $event_type,
		public readonly string $default_level,
		public readonly string $default_title,
		public readonly string $default_description,
		public readonly array $tokens,
		public readonly string $hook,
		public readonly int $priority,
		public readonly int $accepted_args,
		public readonly \Closure $resolver,
	) {
	}

	/**
	 * Resolve the fired hook's arguments into a Token map, or null to skip.
	 *
	 * @param array<int,mixed> $args Hook arguments.
	 * @return array<string,scalar>|null
	 */
	public function resolve( array $args ): ?array {
		return ( $this->resolver )( ...$args );
	}
}
