<?php

/*
Plugin Name: WP-Donors
Plugin URI: https://github.com/bradchando/wp-donors
Description: This plugin is designed to help organizations keep track of donations.  It allows donors to log in and view their own giving records.
Version: 1.0
Author: Brad Chandonnet
Author URI: http://bradchandonnet.com
License: GPLv2
*/

add_action( 'init', 'wpd_register_post_types' );

function wpd_register_post_types() {

	$labels = array(
			'name' => 'Donations',
			'singular_name' => 'Donation',
			'add_new' => 'Record a New Donation',
			'add_new_item' => 'Record a New Donation',
			'all_items' => 'All Donations',
			'menu_name' => 'Donations'
	);

	$args = array(
			'labels' => $labels,
			'public' => true,
	);

	register_post_type( 'donations', $args);
}