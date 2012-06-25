<?php
/* 
Plugin Name: P2 Check In
Plugin URI: http://wordpress.org/extend/plugins/p2-check-in
Description: This plugin adds the ability for users to "check in" to the P2 theme when they're active. Once activated you'll find a new "Who is Checked In" widget that you can add to your sidebar, and a "Log In/I'm here!/I'm leaving!" button will automatically be added to your P2's header.
Version: 0.2.1
Author: Ryan Imel
Author URI: http://wpcandy.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/


// Adds the CSS to the front end.
function p2checkinwidget_enqueue() {
	wp_enqueue_style(  'p2checkinwidget_css', plugins_url('p2-check-in.css', __FILE__), null, '0.2' );
}
add_action('wp_enqueue_scripts', 'p2checkinwidget_enqueue');


// This sorts the users
function p2checkinwidget_usersort( $a, $b ) {
	$ts_a = get_user_meta( $a->ID, 'p2checkinwidget_timestamp', true );
	$ts_b = get_user_meta( $b->ID, 'p2checkinwidget_timestamp', true );

	if( $ts_a == $ts_b ) {
		return 0;
	}

	return ($ts_a < $ts_b) ? 1 : -1;
}

function p2checkinwidget_checkedin( $args = array() ) {
	$args = wp_parse_args( $args, array(
		
		'meta_query' => array(
		
			array(
				'key' => 'currently_checked_in',
				'value' => true // this makes sure the user is checked in
			)
			
		)

	));

	$users = get_users( $args );
	foreach( $users as $user ) {
		// grab all these values, or you'll anger usort by modifying
		// an array mid-execution.
		get_user_meta( $user->ID, 'p2checkinwidget_timestamp', true );
	}
	usort( $users, 'p2checkinwidget_usersort' );

	return $users;
}

function p2checkinwidget_checkedout ( $args = array() ) {
	
	$args = wp_parse_args( $args, array(
	
		'meta_key' => 'currently_checked_in',
		'meta_value' => false
		
	));
	
	$nothereusers = get_users( $args );
	foreach( $nothereusers as $user ) {
		
		get_user_meta( $user->ID, 'p2checkinwidget_timestamp', true );
		
	}
	
	usort( $nothereusers, 'p2checkinwidget_usersort' );
	
	return $nothereusers;
	
}

function p2checkinwidget_list_authors() {
	$users = p2checkinwidget_checkedin();
	$nothereusers = p2checkinwidget_checkedout();

	$html = '';

	foreach( $users as $user ) {

		$item = p2checkinwidget_user( $last_online_ts, $user );
		
		$item = '<li id="p2checkinwidget-' . $user->ID . '" class="checkedin">' . $item . '</li>';
		$html .= $item;
	}
	
	foreach( $nothereusers as $user ) {
		
		$item = p2checkinwidget_user( $lst_online_ts, $user );
		
		$item = '<li id="p2checkinwidget-' . $user->ID . '" class="checkedout">' . $item . '</li>';
		$html .= $item;
		
	}

	echo $html;
	
}

/**
 * Return HTML for a single blog user for the widget.
 *
 * @return string HTML for the user row
 */
function p2checkinwidget_user( $last_online_ts, $user ) {
	$avatar = '<a class="user-img" href="' . get_author_posts_url( $user->ID, $user->user_nicename ) . '" title="' . esc_attr( sprintf(__("Posts by %s"), $user->display_name) ) . '">' . get_avatar( $user->user_email, 32 ) . '</a>';
	$name = $user->display_name;
	$link = '<p class="user-link"><a href="' . get_author_posts_url( $user->ID, $user->user_nicename ) . '" title="' . esc_attr( sprintf(__("Posts by %s"), $user->display_name) ) . '">' . $name . '</a></p>';

	$link = apply_filters( 'p2checkinwidget_author_link', $link, $user );

	$rwi_checkedin = get_user_meta( $user->ID, 'currently_checked_in', true );
	$rwi_checkedouttime = get_user_meta( $user->ID, 'time_checked_out', true );
	$timenow = current_time( 'timestamp', 1 );
	$minutes = 0.5; // how long to show someone has been gone
	$timetoshow = (60 * $minutes);

	if ( $rwi_checkedin ) {
		
		$timein = get_user_meta( $user->ID , 'time_checked_in', true );
		$timephrase = '<p>Checked in for ' . human_time_diff( $timein, $timenow ) . '<p>';
		
		// If there's data, woot
		$checkedin_output = $avatar . $link . $timephrase;
		
	} else if ( !($rwi_checkedin ) AND ( ( $timenow - $rwi_checkedouttime ) < $timetoshow ) )  {
		
		$timephrase = '<p>Left ' . human_time_diff( $rwi_checkedouttime, $timenow ) . ' ago.</p>';
		
		// They left somewhat recently
		$checkedin_output = $avatar . $link . $timephrase;
		
	} 
	
	return $checkedin_output;
	
}

// Outputs the correct button/link at the right time.
function checkin_checkout_button() {
	
	// Get logged in user info
	global $current_user;
	get_currentuserinfo();
	
	// Now, is the current user checked in or not?
	if ( $current_user->ID AND isset( $_GET[checkin] ) ) {
		
		// So we know they're in
		update_user_meta( $current_user->ID , 'currently_checked_in', true );
		
		// So we know what time they showed up
		update_user_meta( $current_user->ID , 'time_checked_in', current_time( 'timestamp' , 1 ) );
		
	} else if ( $current_user->ID AND isset( $_GET[checkout] ) ) {
		
		// So we know they're out
		update_user_meta( $current_user->ID , 'currently_checked_in', false );
		
		// So we know what time they left
		update_user_meta( $current_user->ID, 'time_checked_out', current_time( 'timestamp', 1 ) );
		
	}
	
	$nowin = get_user_meta( $current_user->ID, 'currently_checked_in', true );
	
	// Is anyone logged in? If not, make the button a log in button.	
	if ( !( is_user_logged_in() ) ) {
		
		$buttonoutput = '<p id="p2-check-in-button"><a class="minor" href="' . wp_login_url( home_url() ) . '">Log In</a></p>';
		
	} else if ( $nowin ) {
		
		$buttonoutput = '<p id="p2-check-in-button"><a class="minor" href="?checkout=true">I&rsquo;m leaving!</a></p>';
		
	} else {
		
		$buttonoutput = '<p id="p2-check-in-button"><a href="?checkin=true">I&rsquo;m here!</a></p>';
		
	}
	
	return $buttonoutput;
	
}

// Run our code later in case this loads prior to any required plugins.
add_action('plugins_loaded', 'widget_p2checkinwidget_init');

// The widget itself
function widget_p2checkinwidget_init() {
	
	// Check for the required plugin functions. This will prevent fatal
	// errors occurring when you deactivate the dynamic-sidebar plugin.
	if ( !function_exists('wp_register_sidebar_widget') )
		return;
		
	// This is the function that outputs the Authors code.
	function widget_p2checkinwidget($args) {
		
		extract($args);
		echo $before_widget . $before_title . "Currently Checked In" . $after_title;
		
		echo checkin_checkout_button();
		
		echo '<ul class="p2checkinwidget-list">';
		
		echo p2checkinwidget_list_authors();
		
		echo '</ul>';

		echo $after_widget; 

	}

	// This registers our widget so it appears with the other available
	// widgets and can be dragged and dropped into any active sidebars.
	wp_register_sidebar_widget( 'widget_p2checkinwidget', "Who's Checked In", 'widget_p2checkinwidget', array( 'description' => 'Display who has checked into the office (or wherever) via P2.') );
}

