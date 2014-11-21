<?php

/*
Plugin Name: WP-Donors
Plugin URI: https://github.com/bradchando/wp-donors
Description: This plugin is designed to help organizations keep track of donations.  It allows donors to log in and view their own giving records.
Version: 1.0
Author: Brad Chandonnet
Author URI: http://bradchandonnet.com
License: GPLv2+
*/

/**
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License, version 2 or, at
* your discretion, any later version, as published by the Free
* Software Foundation.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*
*/

add_action( 'init', 'wpd_register_post_types' );

function wpd_register_post_types() {

	$labels = array(
			'name' => 'Donations',
			'singular_name' => 'Donation',
			'add_new' => 'New Donation',
			'add_new_item' => 'New Donation',
			'all_items' => 'All Donations',
			'menu_name' => 'Donations'
	);

	$args = array(
			'labels' => $labels,
			'public' => true,
	);

	register_post_type( 'donations', $args);
}