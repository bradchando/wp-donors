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
			'add_new' => 'Record a New Donation',
			'add_new_item' => 'Record a New Donation',
			'edit_item' => 'Edit Donation',
			'add_new' => 'New Donation',
			'add_new_item' => 'New Donation',
			'all_items' => 'All Donations',
			'menu_name' => 'Donations'
	);

	$args = array(
			'labels' => $labels,
			'public' => true,
			'supports' => array(false)
	);

	register_post_type( 'donations', $args);
}

function wpd_add_meta_boxes() {

	add_meta_box(
		'donation_details',
		'Donation Details',
		'wpd_donation_details_meta_box',
		'donations',
		'normal'
	);
}

add_action('add_meta_boxes', 'wpd_add_meta_boxes');

/*
 * Display the meta box and pre-populate any existing data
 */

function wpd_donation_details_meta_box ($post){

	// Enqueue Datepicker + jQuery UI CSS
	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_style( 'jquery-ui-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.1/themes/smoothness/jquery-ui.css', true);

	//set meta data if already exists
	$wpd_donor_id			= get_post_meta($post->ID,'_wpd_donor_id', 1);
	$wpd_donation_date		= get_post_meta($post->ID, '_wpd_donation_date', 1);
	$wpd_donation_amount 	= get_post_meta($post->ID,'_wpd_donation_amount',1);
	$wpd_donation_method	= get_post_meta($post->ID, '_wpd_donation_method',1);
	$wpd_check_number 		= get_post_meta($post->ID,'_wpd_check_number',1);
	$wpd_missions_amount 	= get_post_meta($post->ID,'_wpd_missions_amount',1);
	$wpd_missions_notes		= get_post_meta($post->ID,'_wpd_missions_notes',1);
	$wpd_other_amount 		= get_post_meta($post->ID,'_wpd_other_amount',1);
	$wpd_other_notes		= get_post_meta($post->ID,'_wpd_other_notes',1);

	//build a list of donors sorted by last name
	$donor_list = get_users('orderby=meta_value&meta_key=last_name&order=ASC');

	//check for saved donation date
	if($wpd_donation_date == ""){
		$wpd_donation_date = date("Y-m-d");
	}

	?>
	<script>
		jQuery(document).ready(function(){
			jQuery('#donation_date').datepicker({
				dateFormat : 'yy-mm-dd'
			});
		});
	</script>
	<p>
		Donor: 
		<select name="donor_id" id="donor_id">
			<?php
				foreach ($donor_list as $donor){
					
					//simple logic to determine is we are dealing with the donor of this donation
					if ($wpd_donor_id == $donor->ID){
						$selected = "selected";
					} else {
						$selected = "";
					}

					//build the list of possible donors
					echo '<option value="' . esc_html($donor->ID) .'"'. $selected .'>' . esc_html($donor->last_name) . ', '. esc_html($donor->first_name) .'</option>';
				}
			?>
		</select> (<a href="user-new.php">Add New Donor</a>)
	</p>
	<p>
		Donation Date: <input type="text" name="donation_date" id="donation_date" value="<?php echo $wpd_donation_date; ?>">
	</p>
	<p>
		Total Donation Amount: $<input type="text" name="donation_amount" id="donation_amount" value="<?php echo $wpd_donation_amount; ?>">
	</p>
	<p>
		Cash:<input type="radio" name="donation_method" value="cash" <?php if($wpd_donation_method == 'cash'): echo 'checked'; endif ?>>
		&nbsp;&nbsp;
		Check:<input type="radio" name="donation_method" value="check" <?php if($wpd_donation_method == 'check'): echo 'checked'; endif ?>>
		Check Number: <input type="text" name="check_number" id="check_number" value="<?php echo $wpd_check_number; ?>">
	</p>

	<p>
		Designated Missions: $<input type="text" name="missions_amount" id="missions_amount" value="<?php echo $wpd_missions_amount; ?>">
		Notes: <input type="text" name="missions_notes" id="missions_notes" value="<?php echo $wpd_missions_notes; ?>">
	</p>
	<p>
		Designated Other: $<input type="text" name="other_amount" id="other_amount" value="<?php echo $wpd_other_amount; ?>">
		Notes: <input type="text" name="other_notes" id="other_notes" value="<?php echo $wpd_other_notes; ?>">
	</p>

	<?php

}

// function for saving meta box information to the db
function wpd_donations_save_post($post_id){
	
	// don't save anything if WP is autosaving
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		return $post_id;

	// permission checking function need to go here!!!!
	if ('donations' == get_post_type($post_id)){
		if ( ! current_user_can( 'edit_page' , $post_id ))
			return $post_id;

	} else {

		if ( ! current_user_can( 'edit_post' , $post_id ))
			return $post_id;
	}

	//update the meta data
	if (isset($_POST['donor_id'])){
		update_post_meta( $post_id, '_wpd_donor_id', $_POST['donor_id']);
	}
	if (isset($_POST['donation_date'])){
		update_post_meta( $post_id, '_wpd_donation_date', $_POST['donation_date']);
	}
	if (isset($_POST['donation_amount'])){
		update_post_meta( $post_id, '_wpd_donation_amount', $_POST['donation_amount']);
	}
	if (isset($_POST['donation_method'])){
		update_post_meta( $post_id, '_wpd_donation_method', $_POST['donation_method']);
	}
	if (isset($_POST['check_number'])){
		update_post_meta( $post_id, '_wpd_check_number', $_POST['check_number']);
	}
	if (isset($_POST['missions_amount'])){
		update_post_meta( $post_id, '_wpd_missions_amount', $_POST['missions_amount']);
	}
	if (isset($_POST['missions_notes'])){
		update_post_meta( $post_id, '_wpd_missions_notes', $_POST['missions_notes']);
	}
	if (isset($_POST['other_amount'])){
		update_post_meta( $post_id, '_wpd_other_amount', $_POST['other_amount']);
	}
	if (isset($_POST['other_notes'])){
		update_post_meta( $post_id, '_wpd_other_notes', $_POST['other_notes']);
	}
}

add_action('save_post', 'wpd_donations_save_post');

/*
* Create and save a title for the donation by combining user and donation data.
*/

function custom_donation_title( $post_id, $post ){
	if( $post->post_type == 'donations' ){
	
		//find out the user id of the donor
		$donor_id = get_post_meta($post->ID,'_wpd_donor_id', 1);

		//only continue if we have valid meta data to work with
		if( $donor_id > 0 ){

			//pull the donor's information from the WP users table
			$donor_info = get_userdata($donor_id);

			//get the donation date
			$wpd_donation_date		= get_post_meta($post->ID, '_wpd_donation_date', 1);

			//build the donation post's title from a combination of donor and donation information
			$new_title = $wpd_donation_date . " - " . $donor_info->last_name . ", ". $donor_info->first_name; // get post_meta and create post title
			
			global $wpdb;
			
			$wpdb->update(
				$wpdb->posts,
				array( 'post_title' => $new_title ),
				array( 'ID' => $post_id )
			);

		}
	}
} 

add_action( 'save_post', 'custom_donation_title', 20, 2 );