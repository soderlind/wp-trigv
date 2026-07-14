<?php
/**
 * Trigger Catalog — the registry of available Triggers.
 *
 * @package Trigv
 */

declare(strict_types=1);

namespace Soderlind\Trigv;

defined( 'ABSPATH' ) || exit;

/**
 * Holds the built-in Triggers and lets Add-ons register more via the
 * `trigv_triggers` filter. Per-Trigger configuration lives in {@see TriggerConfig}.
 */
final class TriggerCatalog {

	/**
	 * Lazily-built catalog, keyed by Trigger id.
	 *
	 * @var array<string,Trigger>|null
	 */
	private ?array $catalog = null;

	/**
	 * No-op placeholder; the catalog is built lazily on first access.
	 */
	public function init(): void {
	}

	/**
	 * All Triggers (built-in + Add-on), keyed by id.
	 *
	 * @return array<string,Trigger>
	 */
	public function all(): array {
		if ( null === $this->catalog ) {
			/**
			 * Filter the Trigger Catalog. Add-ons push additional Trigger
			 * instances onto the array.
			 *
			 * @param array<int|string,Trigger> $triggers Registered Triggers.
			 */
			$registered = apply_filters( 'trigv_triggers', $this->builtins() );

			$this->catalog = array();
			foreach ( $registered as $trigger ) {
				if ( $trigger instanceof Trigger ) {
					$this->catalog[ $trigger->id ] = $trigger;
				}
			}
		}

		return $this->catalog;
	}

	public function get( string $id ): ?Trigger {
		return $this->all()[ $id ] ?? null;
	}

	/**
	 * The curated built-in Triggers.
	 *
	 * @return array<int,Trigger>
	 */
	private function builtins(): array {
		return array(
			new Trigger(
				id: 'post_published',
				label: __( 'Post published', 'push-notifications-for-trigv' ),
				group: __( 'Content', 'push-notifications-for-trigv' ),
				event_type: 'post.published',
				default_level: 'success',
				default_title: __( 'New post published: {post_title}', 'push-notifications-for-trigv' ),
				default_description: __( 'By {author}', 'push-notifications-for-trigv' ),
				tokens: array(
					'post_title' => __( 'Post title', 'push-notifications-for-trigv' ),
					'post_url'   => __( 'Post URL', 'push-notifications-for-trigv' ),
					'author'     => __( 'Author name', 'push-notifications-for-trigv' ),
				),
				hook: 'transition_post_status',
				priority: 10,
				accepted_args: 3,
				resolver: static function ( $new_status, $old_status, $post ): ?array {
					if ( 'publish' !== $new_status || 'publish' === $old_status ) {
						return null;
					}
					if ( ! $post instanceof \WP_Post ) {
						return null;
					}
					/**
					 * Filter which post types the "Post published" Trigger watches.
					 * Defaults to posts only; add 'page' or custom types to widen it.
					 *
					 * @param string[] $types Post type slugs.
					 */
					$types = (array) apply_filters( 'trigv_post_published_types', array( 'post' ) );
					if ( ! in_array( $post->post_type, $types, true ) ) {
						return null;
					}
					return array(
						'post_title' => $post->post_title,
						'post_url'   => (string) get_permalink( $post ),
						'author'     => (string) get_the_author_meta( 'display_name', (int) $post->post_author ),
					);
				},
			),
			new Trigger(
				id: 'page_published',
				label: __( 'Page published', 'push-notifications-for-trigv' ),
				group: __( 'Content', 'push-notifications-for-trigv' ),
				event_type: 'page.published',
				default_level: 'info',
				default_title: __( 'New page published: {post_title}', 'push-notifications-for-trigv' ),
				default_description: __( 'By {author}', 'push-notifications-for-trigv' ),
				tokens: array(
					'post_title' => __( 'Page title', 'push-notifications-for-trigv' ),
					'post_url'   => __( 'Page URL', 'push-notifications-for-trigv' ),
					'author'     => __( 'Author name', 'push-notifications-for-trigv' ),
				),
				hook: 'transition_post_status',
				priority: 10,
				accepted_args: 3,
				resolver: static function ( $new_status, $old_status, $post ): ?array {
					if ( 'publish' !== $new_status || 'publish' === $old_status ) {
						return null;
					}
					if ( ! $post instanceof \WP_Post || 'page' !== $post->post_type ) {
						return null;
					}
					return array(
						'post_title' => $post->post_title,
						'post_url'   => (string) get_permalink( $post ),
						'author'     => (string) get_the_author_meta( 'display_name', (int) $post->post_author ),
					);
				},
			),
			new Trigger(
				id: 'post_updated',
				label: __( 'Post updated', 'push-notifications-for-trigv' ),
				group: __( 'Content', 'push-notifications-for-trigv' ),
				event_type: 'post.updated',
				default_level: 'info',
				default_title: __( 'Post updated: {post_title}', 'push-notifications-for-trigv' ),
				default_description: __( '{post_url}', 'push-notifications-for-trigv' ),
				tokens: array(
					'post_title' => __( 'Post title', 'push-notifications-for-trigv' ),
					'post_url'   => __( 'Post URL', 'push-notifications-for-trigv' ),
				),
				hook: 'post_updated',
				priority: 10,
				accepted_args: 3,
				resolver: static function ( $post_id, $post_after, $post_before ): ?array {
					if ( ! $post_after instanceof \WP_Post || 'post' !== $post_after->post_type ) {
						return null;
					}
					if ( 'publish' !== $post_after->post_status ) {
						return null;
					}
					// Skip the initial publish — that's post_published's job.
					if ( $post_before instanceof \WP_Post && 'publish' !== $post_before->post_status ) {
						return null;
					}
					return array(
						'post_title' => $post_after->post_title,
						'post_url'   => (string) get_permalink( $post_after ),
					);
				},
			),
			new Trigger(
				id: 'comment_created',
				label: __( 'New comment', 'push-notifications-for-trigv' ),
				group: __( 'Comments', 'push-notifications-for-trigv' ),
				event_type: 'comment.created',
				default_level: 'info',
				default_title: __( 'New comment on {post_title}', 'push-notifications-for-trigv' ),
				default_description: __( 'By {comment_author}', 'push-notifications-for-trigv' ),
				tokens: array(
					'comment_author' => __( 'Comment author', 'push-notifications-for-trigv' ),
					'post_title'     => __( 'Post title', 'push-notifications-for-trigv' ),
				),
				hook: 'comment_post',
				priority: 10,
				accepted_args: 2,
				resolver: static function ( $comment_id, $approved ): ?array {
					if ( 1 !== $approved ) {
						return null;
					}
					$comment = get_comment( (int) $comment_id );
					if ( ! $comment ) {
						return null;
					}
					return array(
						'comment_author' => $comment->comment_author,
						'post_title'     => get_the_title( (int) $comment->comment_post_ID ),
					);
				},
			),
			new Trigger(
				id: 'comment_moderation',
				label: __( 'Comment needs moderation', 'push-notifications-for-trigv' ),
				group: __( 'Comments', 'push-notifications-for-trigv' ),
				event_type: 'comment.moderation',
				default_level: 'warning',
				default_title: __( 'Comment awaiting moderation', 'push-notifications-for-trigv' ),
				default_description: __( '{comment_author} on {post_title}', 'push-notifications-for-trigv' ),
				tokens: array(
					'comment_author' => __( 'Comment author', 'push-notifications-for-trigv' ),
					'post_title'     => __( 'Post title', 'push-notifications-for-trigv' ),
				),
				hook: 'comment_post',
				priority: 10,
				accepted_args: 2,
				resolver: static function ( $comment_id, $approved ): ?array {
					// 0 / '0' means held for moderation.
					if ( '0' !== (string) $approved ) {
						return null;
					}
					$comment = get_comment( (int) $comment_id );
					if ( ! $comment ) {
						return null;
					}
					return array(
						'comment_author' => $comment->comment_author,
						'post_title'     => get_the_title( (int) $comment->comment_post_ID ),
					);
				},
			),
			new Trigger(
				id: 'user_registered',
				label: __( 'New user registered', 'push-notifications-for-trigv' ),
				group: __( 'Users', 'push-notifications-for-trigv' ),
				event_type: 'user.registered',
				default_level: 'info',
				default_title: __( 'New user: {user_login}', 'push-notifications-for-trigv' ),
				default_description: __( '{user_email}', 'push-notifications-for-trigv' ),
				tokens: array(
					'user_login' => __( 'Username', 'push-notifications-for-trigv' ),
					'user_email' => __( 'Email address', 'push-notifications-for-trigv' ),
				),
				hook: 'user_register',
				priority: 10,
				accepted_args: 1,
				resolver: static function ( $user_id ): ?array {
					$user = get_userdata( (int) $user_id );
					if ( ! $user ) {
						return null;
					}
					return array(
						'user_login' => $user->user_login,
						'user_email' => $user->user_email,
					);
				},
			),
			new Trigger(
				id: 'login_failed',
				label: __( 'Failed login', 'push-notifications-for-trigv' ),
				group: __( 'Security', 'push-notifications-for-trigv' ),
				event_type: 'login.failed',
				default_level: 'warning',
				default_title: __( 'Failed login: {username}', 'push-notifications-for-trigv' ),
				default_description: __( 'From {ip}', 'push-notifications-for-trigv' ),
				tokens: array(
					'username' => __( 'Attempted username', 'push-notifications-for-trigv' ),
					'ip'       => __( 'Client IP address', 'push-notifications-for-trigv' ),
				),
				hook: 'wp_login_failed',
				priority: 10,
				accepted_args: 1,
				resolver: static function ( $username ): ?array {
					$remote = isset( $_SERVER['REMOTE_ADDR'] )
						? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
						: '';
					/**
					 * Filter the client IP recorded for security Triggers. Return
					 * an anonymized/hashed value to satisfy privacy requirements.
					 *
					 * @param string $ip Raw REMOTE_ADDR.
					 */
					$ip = (string) apply_filters( 'trigv_client_ip', $remote );
					return array(
						'username' => (string) $username,
						'ip'       => $ip,
					);
				},
			),
			new Trigger(
				id: 'upgrade_completed',
				label: __( 'Plugin or theme updated', 'push-notifications-for-trigv' ),
				group: __( 'Maintenance', 'push-notifications-for-trigv' ),
				event_type: 'upgrade.completed',
				default_level: 'info',
				default_title: __( '{type} updated', 'push-notifications-for-trigv' ),
				default_description: __( '{name}', 'push-notifications-for-trigv' ),
				tokens: array(
					'type' => __( 'Update type (plugin/theme)', 'push-notifications-for-trigv' ),
					'name' => __( 'Updated item name(s)', 'push-notifications-for-trigv' ),
				),
				hook: 'upgrader_process_complete',
				priority: 10,
				accepted_args: 2,
				resolver: static function ( $upgrader, $hook_extra = array() ): ?array {
					if ( ! is_array( $hook_extra ) || 'update' !== ( $hook_extra['action'] ?? '' ) ) {
						return null;
					}
					$type  = (string) ( $hook_extra['type'] ?? 'unknown' );
					$names = array();
					if ( 'plugin' === $type ) {
						$names = $hook_extra['plugins'] ?? ( isset( $hook_extra['plugin'] ) ? array( $hook_extra['plugin'] ) : array() );
					} elseif ( 'theme' === $type ) {
						$names = $hook_extra['themes'] ?? ( isset( $hook_extra['theme'] ) ? array( $hook_extra['theme'] ) : array() );
					}
					return array(
						'type' => $type,
						'name' => implode( ', ', array_map( 'strval', (array) $names ) ),
					);
				},
			),
			new Trigger(
				id: 'auto_update_completed',
				label: __( 'Automatic updates completed', 'push-notifications-for-trigv' ),
				group: __( 'Maintenance', 'push-notifications-for-trigv' ),
				event_type: 'autoupdate.completed',
				default_level: 'info',
				default_title: __( 'Automatic updates completed', 'push-notifications-for-trigv' ),
				default_description: __( '{status}', 'push-notifications-for-trigv' ),
				tokens: array(
					'status' => __( 'Per-type update counts', 'push-notifications-for-trigv' ),
				),
				hook: 'automatic_updates_complete',
				priority: 10,
				accepted_args: 1,
				resolver: static function ( $results = array() ): ?array {
					$counts = array();
					foreach ( (array) $results as $type => $items ) {
						$counts[] = sprintf( '%s: %d', (string) $type, is_array( $items ) ? count( $items ) : 0 );
					}
					return array(
						'status' => $counts ? implode( ', ', $counts ) : __( 'no updates', 'push-notifications-for-trigv' ),
					);
				},
			),
		);
	}
}
