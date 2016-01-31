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

add_action( 'wp_enqueue_scripts', 'wpds_register_scripts' );

function wpds_register_scripts() {
    wp_enqueue_style( 'wpd-styles', plugins_url( 'css/wpd.css', __FILE__ ), array(), '' );
    //wp_enqueue_script( 'script-name', get_template_directory_uri() . '/js/example.js', array(), '1.0.0', true );
}

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

function wpd_define_taxonomies() {

	register_taxonomy('deposit', 'donations', array( 'hierarchial' => true, 'label' => 'Deposit', 'show_admin_column' => true, 'query_var' => true, 'rewrite' => true ) );
}

add_action('init', 'wpd_define_taxonomies');

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

/*
* Master Reporting Shortcode
*/

add_shortcode( 'full_report', 'render_full_report');

function render_full_report($attr){

	$year = $attr['year'];

	$output = '<div id="report_start"> </div>';

	$blogusers = get_users( 'orderby=display_name&order=ASC' );
	// Array of WP_User objects.
	foreach ( $blogusers as $user ) {
		$output .= '<h2>Gospel Light Baptist Church - Ludington, MI</h2>';
		$output .= '<h3>' . esc_html( $user->user_firstname ) . ' ' . esc_html( $user->user_lastname ) . '<br>' . $year . ' Giving Report</h3>';
		$output .= get_donations($user->ID,$year);
		$output .= '<p>Contributions are tax-deductible to the extent allowed by law. No goods or services were provided in exchange for donations.</p>';

		$output .= '<hr>';
	}

	return($output);
}

function get_donations($user_ID,$year){

	$year_start = $year . '-01-01';
	$year_end = $year . '-12-31';

	$args = array(
		'post_type' => 'donations',
		'posts_per_page' => -1,
		'meta_query' => array(
			array(
				'key'     => '_wpd_donor_id',
				'value'   => $user_ID,
				'compare' => '=',
			),
			array(
				'key' => '_wpd_donation_date',
				'value'   => array( $year_start, $year_end ),
				'compare' => 'BETWEEN',
			),
		),
		'meta_key' => '_wpd_donor_id',
		'meta_value' => $user_ID,
		'orderby' => '_wpd_donation_date',
		'order' => 'DESC'
	);

	$report_query = new WP_Query($args);

	//debug($report_query);

	$total = 0;
	$missions_total = 0;
	$other_total = 0;
	$count = 0;

	$report = '<table class="giving_report">';
	$report .= '<tr class="total_row"><th>Date</th><th>Donation Method</th><th>General</th><th>Missions</th><th>Special</th><th>Total</th></tr>';

	if ( $report_query->have_posts() ) :
	while($report_query->have_posts()): $report_query->the_post();
		
		$donation_date = get_post_meta( get_the_ID(), '_wpd_donation_date' );
		$donation_amount = get_post_meta( get_the_ID(), '_wpd_donation_amount' );
		$donation_method = get_post_meta( get_the_ID(), '_wpd_donation_method');
		$missions_amount = get_post_meta( get_the_ID(), '_wpd_missions_amount');
		$missions_note = get_post_meta( get_the_ID(), '_wpd_missions_notes');
		$other_amount = get_post_meta( get_the_ID(), '_wpd_other_amount');
		$other_note = get_post_meta( get_the_ID(), '_wpd_other_notes');

		//Format some strings

		if($donation_method[0] == 'check') {
			$check_number = get_post_meta( get_the_ID(), '_wpd_check_number');
			$d_method = 'check #' . $check_number[0];
		} else{
			$d_method = 'cash';
		}


		// Keep track of totals
		
		$total += $donation_amount[0];

		// Set row style variable
		$count++;
		
		if(($count % 2) == 1 ) {
			$row_style = 'odd_row';
		}else{
			$row_style = 'even_row';
		}

		$general = $donation_amount[0] - $missions_amount[0] - $other_amount[0];

	// $wpd_donor_id			= get_post_meta($post->ID,'_wpd_donor_id', 1);
	// $wpd_donation_date		= get_post_meta($post->ID, '_wpd_donation_date', 1);
	// $wpd_donation_amount 	= get_post_meta($post->ID,'_wpd_donation_amount',1);
	// $wpd_donation_method	= get_post_meta($post->ID, '_wpd_donation_method',1);
	// $wpd_check_number 		= get_post_meta($post->ID,'_wpd_check_number',1);
	// $wpd_missions_amount 	= get_post_meta($post->ID,'_wpd_missions_amount',1);
	// $wpd_missions_notes		= get_post_meta($post->ID,'_wpd_missions_notes',1);
	// $wpd_other_amount 		= get_post_meta($post->ID,'_wpd_other_amount',1);
	// $wpd_other_notes		= get_post_meta($post->ID,'_wpd_other_notes',1);
				
		$report .= '<tr class="' . $row_style . '"><td>' . $donation_date[0] . '</td><td>' . $d_method . '</td><td>' . $general . '</td><td>' . $missions_amount[0] . '</td><td>' . $other_amount[0] . '</td><td>$' . $donation_amount[0] . '</td></tr>';

	endwhile;
	

	endif;

	//debug($report_query);
	$report .= '<tr class="total_row"><td></td><td></td><td></td><td></td><td>TOTAL:</td><td> $' . $total . '</td></tr>';
	$report .= '</table>';

	return($report);
}

/*******************************************************************************
 ** DEBUG MESSAGES ON SCREEN **
 *******************************************************************************/

if ( !function_exists('debug') ) {
	function debug($var = false) {
		echo "\n<pre class=\"debug\" style=\"background: #FFFF99; font-size: 10px;\">\n";
		$var = print_r($var, true);
		echo $var . "\n</pre>\n";
	}
}
