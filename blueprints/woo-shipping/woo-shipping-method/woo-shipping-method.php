<?php

/**
 * Plugin Name:    Woo Shipping Method
 * Plugin URI:     https://calvinrodrigues.in
 * Description:    Creates a Flat Rate Shipping Method
 * Version:        1.0.0
 * Author:         Calvin Rodrigues
 * Author URI:     https://calvinrodrigues.in
 * Text Domain:    woo-shipping-method
 */

function add_flat_rate_shipping_method() {

	$zone = new WC_Shipping_Zone(0);

	$shipping_methods = $zone->get_shipping_methods();
	$method_exists = false;

	foreach ($shipping_methods as $method) {
		if ('flat_rate' == $method->id) {
			$method_exists = true;
			break;
		}
	}

	if ($method_exists) {
		return;
	}

	$instance_id = $zone->add_shipping_method('flat_rate');

	if (!$instance_id) {
		return;
	}

	$settings = array(
		'title'			=> __('My Custom Flat Rate', 'woo-shippinqg-method'),
		'cost' 			=> 10,
		'tax_status' 	=> 'none',
	);

	update_option('woocommerce_flat_rate_' . $instance_id . '_settings', $settings);
}

add_action('init', 'add_flat_rate_shipping_method');
