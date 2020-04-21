<?php
/**
* Plugin Name: Password Protection Add Email
* Plugin URI: https://pixeldevs.io
* Description: Adds Email field to Password Protected Posts and stores emails in a CSV for Admins to download from the Dashboard
* Version: 1.0
* Author: PixelDevs
* Author URI: https://pixeldevs.io
**/

register_activation_hook(__FILE__, 'gpp_plugin_activate');
function gpp_plugin_activate(){
	$upload_dir   = wp_upload_dir();
	$password_protected_dir = $upload_dir['basedir'] . '/passprotectfile';
	
	if ( ! file_exists( $password_protected_dir ) ) {
		wp_mkdir_p( $password_protected_dir );
	} else {
		return;
	}
	
	$file = fopen( $password_protected_dir . '/GPO_protected.csv', 'w');
	 
	// save the column headers
	fputcsv($file, array('Email', 'Page'));
	 
	// Close the file
	fclose($file);
 
}

add_filter( 'the_password_form', 'custom_password_form' );
function custom_password_form() {
	global $post;
	$o = '<form action="' . esc_url( site_url( 'wp-login.php?action=postpass', 'login_post' ) ) . '" method="post">
	<p>' . __( "This post is password protected. To view it please enter your email and assigned password below." ) . '</p>
	<label for="Email">' . __( "Email:" ) . ' </label><input name="post_password_email" type="text" size="20" required />
	<label for="password">' . __( "Password:" ) . ' </label><input name="post_password" id="password" type="password" size="20" required/>
	<input type="submit" name="Submit" value="' . esc_attr__( "Submit" ) . '" />
	</form>
	';
	return $o;
}

// Log when a user enters something into the post password form.
// Both correct and incorrect usage is logged.
add_action( 'login_form_postpass', function() {
	
	
    $postPass = isset($_POST['post_password']) ? $_POST['post_password'] : null;
 
    if (!$postPass) {
        return;
    }
 
    $urlPath = wp_get_referer();
    $pageByPath = url_to_postid($urlPath);
 
    if (!$pageByPath) {
        return;
    }
	
	$post = get_post($pageByPath);
   
	if ( isset( $post->post_password ) ) {
	    $correctPasswordEntered = ($post->post_password === $postPass);
	} else {
		return;
	}
    
 
    if ($correctPasswordEntered) {
		
		// open the file "demosaved.csv" for writing
			$upload_dir   = wp_upload_dir();
			$password_protected_dir = $upload_dir['basedir'] . '/passprotectfile';
			
			if ( ! file_exists( $password_protected_dir ) ) {
				wp_mkdir_p( $password_protected_dir );
			}
			
			$file = fopen( $password_protected_dir . '/GPO_protected.csv', 'a');
			 
			 
			// Sample data. This can be fetched from mysql too
			$data = array(
			array('' . $_POST['post_password_email'] . '', '' . str_replace('"', '', get_the_title($pageByPath)) . '')
			);
			 
			// save each row of the data
			foreach ($data as $row)
			{
				fputcsv($file, $row);
			}
			 
			// Close the file
			fclose($file);
       
    }
});

add_action('wp_dashboard_setup', 'my_custom_dashboard_widgets');
  
function my_custom_dashboard_widgets() {
	global $wp_meta_boxes;
	if( current_user_can('administrator') ) { 
		wp_add_dashboard_widget('custom_help_widget', 'Download Email CSV', 'custom_dashboard_help');
	}
}
 
function custom_dashboard_help() {
	$upload_dir   = wp_upload_dir();
	$password_protected_dir = $upload_dir['baseurl'] . '/passprotectfile/GPO_protected.csv';
	$fp = file($password_protected_dir, FILE_SKIP_EMPTY_LINES);
	$emails = count($fp) - 1;
	echo '<p>You have collected ' . $emails . ' emails.<p>';
	echo '<a class="button button-primary" href=' . $password_protected_dir . '>Download</a>';
}

?>