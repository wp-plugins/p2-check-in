<?php
/* 
Plugin Name: P2 Check In
Plugin URI: http://wordpress.org/extend/plugins/p2-check-in
Description: This plugin adds the ability for users to "check in" to the P2 theme when they're active. Once activated you'll find a new "Who is Checked In" widget that you can add to your sidebar, and a "Log In/I'm here!/I'm leaving!" button will automatically be added to your P2's header.
Version: 0.4.1
Author: Ryan Imel
Author URI: http://wpcandy.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/


// Adds the CSS to the front end.
function p2checkinwidget_enqueue() {
	wp_enqueue_style(  'p2checkinwidget_css', plugins_url('p2-check-in.css', __FILE__), null, '0.5' );
}
add_action('wp_enqueue_scripts', 'p2checkinwidget_enqueue');


add_action( 'plugins_loaded', 'p2checkin_languages' ); 
function p2checkin_languages() { 
	load_plugin_textdomain( 'p2-check-in', false, basename( dirname( __FILE__ ) ) . '/languages' );
}


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
				'key' => 'p2checkin_currently_checked_in',
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

// List of checked out users
function p2checkinwidget_checkedout ( $args = array() ) {
	
	$args = wp_parse_args( $args, array(
	
		'meta_key' => 'p2checkin_currently_checked_in',
		'meta_value' => false
		
	));
	
	$nothereusers = get_users( $args );
	foreach( $nothereusers as $user ) {
		
		get_user_meta( $user->ID, 'p2checkinwidget_timestamp', true );
		
	}
	
	usort( $nothereusers, 'p2checkinwidget_usersort' );
	
	return $nothereusers;
	
}

// List of checked in users
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
	
	$avatar = '<a class="user-img" href="' . get_author_posts_url( $user->ID, $user->user_nicename ) . '" title="' . esc_attr( sprintf(__( "Posts by %s", "p2-check-in" ), $user->display_name) ) . '">' . get_avatar( $user->user_email, 52 ) . '</a>';
	$name = $user->display_name;
	$link = '<p class="user-link"><a href="' . get_author_posts_url( $user->ID, $user->user_nicename ) . '" title="' . esc_attr( sprintf(__( "Posts by %s", "p2-check-in" ), $user->display_name) ) . '">' . $name . '</a></p>';

	$link = apply_filters( 'p2checkinwidget_author_link', $link, $user );

	$rwi_checkedin = get_user_meta( $user->ID, 'p2checkin_currently_checked_in', true );
	$rwi_checkedouttime = get_user_meta( $user->ID, 'p2checkin_time_checked_out', true );
	$timenow = current_time( 'timestamp', 1 );
	$minutes = 5; // how long to show someone has been gone
	$timetoshow = (60 * $minutes);
	
	$p2checkin_totaltimedisplay = get_user_meta( $user->ID, 'p2checkin_totaltime', true );
	$p2time_readable = number_format( ( $p2checkin_totaltimedisplay / 60 / 60 ), 2, '.', '' );
	
	if ( $rwi_checkedin ) {
		
		$timein = get_user_meta( $user->ID , 'p2checkin_time_checked_in', true );
		$timesofar = ( $timenow - $timein );
		$p2time_readable_temp = number_format( ( ( $timesofar + $p2checkin_totaltimedisplay ) / 60 / 60 ), 2, '.', '' );
		$timephrase = '<p><strong>' . __( 'Checked in for', 'p2-check-in' ) . '&nbsp;' . human_time_diff( $timein, $timenow ) . '</strong></p><p class="minor">' . __( 'Total:', 'p2-check-in' ) . '&nbsp;' . $p2time_readable_temp . '&nbsp;' . __( 'hours', 'p2-check-in' ) . '</p>';
		
		// Add the god action if the current user is an admin
		$admincheckout = '';
		
		// Get logged in user info
		global $current_user;
		get_currentuserinfo();
		
		$loggedinuser = $current_user->ID;
		$displayuser = $user->ID;
		
		if ( current_user_can('manage_options') && ( $displayuser != $loggedinuser ) ) {
			
			// Give the admins a god card
			$admincheckout .= '<p class="p2checkin-adminoverride"><a href="?checkout=true&p2checkinuser=' . $user->ID . '">x</a></p>';
			
		}
		
		// If there's data, woot
		$checkedin_output = $avatar . $link . $timephrase . $admincheckout;
		
	} else if ( !($rwi_checkedin ) AND ( ( $timenow - $rwi_checkedouttime ) < $timetoshow ) )  {
		
		$timephrase = '<p><strong>' . __( 'Left', 'p2-check-in' )  . human_time_diff( $rwi_checkedouttime, $timenow ) . __( ' ago.', 'p2-check-in' ) . '</strong></p><p class="minor">' . __( 'Total: ', 'p2-check-in' ) . $p2time_readable . __( 'hours', 'p2-check-in' ) . '</p>';
		
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
	
	// To enable us to prevent cheatin'
	$rwi_checkedin = get_user_meta( $current_user->ID, 'p2checkin_time_checked_in', true );
	$rwi_checkedouttime = get_user_meta( $current_user->ID, 'p2checkin_time_checked_out', true );
	
	// Now, is the current user checked in or not?
	if ( $current_user->ID AND isset( $_GET[checkin] ) ) {
		
		if ( $rwi_checkedin > $rwi_checkedouttime ) {
			
			// Do nothing, this person has already checked in. (They just refreshed and queued this up again.)
			
		} else {
		
			// So we know they're in
			update_user_meta( $current_user->ID , 'p2checkin_currently_checked_in', true );
		
			// So we know what time they showed up
			update_user_meta( $current_user->ID , 'p2checkin_time_checked_in', current_time( 'timestamp' , 1 ) );
		
		}
		
	} else if ( $current_user->ID AND isset( $_GET[checkout] ) ) {
		
		if ( $rwi_checkedouttime > $rwi_checkedin ) {
			
			// Removed, since it would be confusing for 99% of users to see this message.
			//echo '<p>Cheating? Tsk tsk.</p>';
		
		} else {
			
			if ( isset( $_GET[p2checkinuser] )  && current_user_can( 'manage_options' ) ) {
				
				// Set the user variable for who's being forcibly checked out
				$usercheckedoutbyforce = $_GET[p2checkinuser];
				
				// To enable us to prevent cheatin'
				$rwi_forced_checkedin = get_user_meta( $usercheckedoutbyforce, 'p2checkin_time_checked_in', true );
				$rwi_forced_checkedouttime = get_user_meta( $usercheckedoutbyforce, 'p2checkin_time_checked_out', true );
				
				if ( $rwi_forced_checkedouttime < $rwi_forced_checkedin ) {
				
					// So we know they're out
					update_user_meta( $usercheckedoutbyforce , 'p2checkin_currently_checked_in', false );
				
					// So we know what time they left
					update_user_meta( $usercheckedoutbyforce , 'p2checkin_time_checked_out', current_time( 'timestamp', 1 ) );
				
					// Keep a running total of checked in time. Why not?
					$rwi_forced_checkedouttime_realz = get_user_meta( $usercheckedoutbyforce, 'p2checkin_time_checked_out', true );
					$p2checkin_forced_timesofar = get_user_meta( $usercheckedoutbyforce, 'p2checkin_totaltime', true );
					$p2checkin_forced_sessiontime = ( $rwi_forced_checkedouttime_realz - $rwi_forced_checkedin );
					$p2checkin_forced_timesofar += $p2checkin_forced_sessiontime;
				
					update_user_meta( $usercheckedoutbyforce, 'p2checkin_totaltime', $p2checkin_forced_timesofar );
				
				}
				
			} else {
			
				// So we know they're out
				update_user_meta( $current_user->ID , 'p2checkin_currently_checked_in', false );

				// So we know what time they left
				update_user_meta( $current_user->ID , 'p2checkin_time_checked_out', current_time( 'timestamp', 1 ) );

				// Keep a running total of checked in time. Why not?
				$rwi_checkedouttime_realz = get_user_meta( $current_user->ID, 'p2checkin_time_checked_out', true );
				$p2checkin_timesofar = get_user_meta( $current_user->ID, 'p2checkin_totaltime', true );
				$p2checkin_sessiontime = ( $rwi_checkedouttime_realz - $rwi_checkedin );
				$p2checkin_timesofar += $p2checkin_sessiontime;
				
				update_user_meta( $current_user->ID, 'p2checkin_totaltime', $p2checkin_timesofar );
			
			}
			
		}
		
	}
	
	$nowin = get_user_meta( $current_user->ID, 'p2checkin_currently_checked_in', true );
	
	// Is anyone logged in? If not, make the button a log in button.	
	if ( !( is_user_logged_in() ) ) {
		
		$buttonoutput = '<p id="p2-check-in-button"><a class="minor" href="' . wp_login_url( home_url() ) . '">' . __( 'Log In', 'p2-check-in' ) . '</a></p>';
		
	} else if ( $nowin ) {
		
		$buttonoutput = '<p id="p2-check-in-button"><a class="minor" href="?checkout=true">' . __( 'I&rsquo;m leaving!', 'p2-check-in' ) . '</a></p>';
		
	} else {
		
		$buttonoutput = '<p id="p2-check-in-button"><a href="?checkin=true">' . __( 'I&rsquo;m here!', 'p2-check-in' ) . '</a></p>';
		
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
		echo $before_widget . $before_title . __( 'Currently Checked In', 'p2-check-in' ) . $after_title;
		
		echo checkin_checkout_button();
		
		echo '<ul class="p2checkinwidget-list">';
		
		echo p2checkinwidget_list_authors();
		
		echo '</ul>';

		echo $after_widget; 

	}

	// This registers our widget so it appears with the other available
	// widgets and can be dragged and dropped into any active sidebars.
	wp_register_sidebar_widget( 'widget_p2checkinwidget', __( "Who's Checked In", "p2-check-in" ), 'widget_p2checkinwidget', array( 'description' => __( 'Display who has checked into the office (or wherever) via P2.', 'p2-check-in' ) ) );
}
