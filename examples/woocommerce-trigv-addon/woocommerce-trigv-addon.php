<?php
/**
 * Plugin Name:       Trigv — WooCommerce Add-on (example)
 * Description:       Example Add-on that registers WooCommerce order Triggers into the Trigv catalog via the `trigv_triggers` filter.
 * Version:           1.0.0
 * Requires at least: 6.8
 * Requires PHP:      8.3
 * Requires Plugins:  wp-trigv, woocommerce
 * License:           GPL-2.0-or-later
 * Text Domain:       wc-trigv
 *
 * @package Trigv\Examples\WooCommerce
 */

declare(strict_types=1);

namespace Trigv\Examples\WooCommerce;

use Trigv\Trigger;

defined( 'ABSPATH' ) || exit;

/**
 * Register WooCommerce Triggers into the Trigv catalog.
 *
 * @param array<int|string,Trigger> $triggers Existing catalog entries.
 * @return array<int|string,Trigger>
 */
add_filter(
	'trigv_triggers',
	static function ( array $triggers ): array {
		// The core plugin must be active for the Trigger class to exist.
		if ( ! class_exists( Trigger::class ) ) {
			return $triggers;
		}

		$triggers[] = new Trigger(
			id: 'woo_new_order',
			label: __( 'WooCommerce new order', 'wc-trigv' ),
			group: __( 'WooCommerce', 'wc-trigv' ),
			event_type: 'woo.order.created',
			default_level: 'info',
			default_title: __( 'New order #{order_id}', 'wc-trigv' ),
			default_description: __( '{total} from {customer}', 'wc-trigv' ),
			tokens: array(
				'order_id' => __( 'Order number', 'wc-trigv' ),
				'total'    => __( 'Order total', 'wc-trigv' ),
				'customer' => __( 'Customer name', 'wc-trigv' ),
			),
			hook: 'woocommerce_new_order',
			priority: 10,
			accepted_args: 2,
			resolver: static fn( $order_id, $order = null ): ?array => resolve_order( $order_id, $order ),
		);

		$triggers[] = new Trigger(
			id: 'woo_order_completed',
			label: __( 'WooCommerce order completed', 'wc-trigv' ),
			group: __( 'WooCommerce', 'wc-trigv' ),
			event_type: 'woo.order.completed',
			default_level: 'success',
			default_title: __( 'Order #{order_id} completed', 'wc-trigv' ),
			default_description: __( '{total} from {customer}', 'wc-trigv' ),
			tokens: array(
				'order_id' => __( 'Order number', 'wc-trigv' ),
				'total'    => __( 'Order total', 'wc-trigv' ),
				'customer' => __( 'Customer name', 'wc-trigv' ),
			),
			hook: 'woocommerce_order_status_completed',
			priority: 10,
			accepted_args: 2,
			resolver: static fn( $order_id, $order = null ): ?array => resolve_order( $order_id, $order ),
		);

		return $triggers;
	}
);

/**
 * Resolve a WooCommerce order into a Token map.
 *
 * @param mixed $order_id Order ID.
 * @param mixed $order    Optional order object passed by the hook.
 * @return array<string,string>|null
 */
function resolve_order( $order_id, $order = null ): ?array {
	if ( ! function_exists( 'wc_get_order' ) ) {
		return null;
	}

	$order = ( $order instanceof \WC_Order ) ? $order : wc_get_order( (int) $order_id );
	if ( ! $order instanceof \WC_Order ) {
		return null;
	}

	$customer = trim( $order->get_formatted_billing_full_name() );

	return array(
		'order_id' => (string) $order->get_id(),
		'total'    => html_entity_decode( wp_strip_all_tags( $order->get_formatted_order_total() ) ),
		'customer' => '' !== $customer ? $customer : __( 'Guest', 'wc-trigv' ),
	);
}
