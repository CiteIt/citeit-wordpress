<?php
/**
 * @package Neotext
 * @version 0.1
 */
/*
Plugin Name: Neotext
Plugin URI: http://www.neotext.net

Description: Expands "blockquote" with surrounding text by : selecting all "blockquote" tags that have a "cite" attribute, downloading the cited url, locating the citation, saving the "before" and "after" text into a json file, and adding the retrieved text to the dom
Author: Tim Langeman
Version: 0.11
Author URI: http://www.openpolitics.com/tim
*/

function neotext_quote_context_header() {
	wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-md5', plugins_url('lib/jquery.md5.js', __FILE__) );
    wp_enqueue_script('quote-context', plugins_url('js/neotext-quote-context.js', __FILE__) );
}

function neotext_quote_context_hack(){
	echo "
	<script type='text/javascript'></script>";
}

function neotext_quote_context_footer() {
	wp_enqueue_style('neotext_quote_context_css', plugins_url('css/quote-context-style.css', __FILE__) );

	echo "<div id='neotext_container'><!-- neotext quote-context.js injects data returned from lookup in this hidden div --></div>
    <script type='text/javascript'> 
	    // Call plugin on all blockquotes:
        	jQuery('q, blockquote' ).quoteContext(); 
    </script>";

	wp_enqueue_script('jquery-ui', '//code.jquery.com/ui/1.11.4/jquery-ui.js');
	wp_enqueue_style('jquery-ui-css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.css');
}

add_action( 'wp_enqueue_scripts', 'neotext_quote_context_header' );
add_action( 'wp_head', 'neotext_quote_context_hack');
add_action( 'wp_footer', 'neotext_quote_context_footer');

/***************** Add TinyMCE Admin Buttons ********************/
// Tiny MCE: add Custom Neotext button to editor
// Credit: AJ Clarke: http://www.wpexplorer.com/wordpress-tinymce-tweaks/

// Hooks your functions into the correct filters
function neotext_add_mce_blockquote() {
	// check user permissions
	if ( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) {
		return;
	}
	// check if WYSIWYG is enabled
	if ( 'true' == get_user_option( 'rich_editing' ) ) {
		add_filter( 'mce_external_plugins', 'neotext_add_tinymce_blockquote_plugin' );
		add_filter( 'mce_buttons', 'neotext_register_tinymce_blockquote' );

		add_filter( 'mce_external_plugins', 'neotext_add_tinymce_q_plugin' );
		add_filter( 'mce_buttons', 'neotext_register_tinymce_q' );
	}



}
add_action('admin_head', 'neotext_add_mce_blockquote');

// Button 1: Declare script for new button: blockquote
function neotext_add_tinymce_blockquote_plugin( $plugin_array ) {
	$plugin_array['neotext_blockquote'] = plugins_url('/neotext/js/tinymce-blockquote.js');
	return $plugin_array;
}

function neotext_register_tinymce_blockquote( $buttons ) {
	array_push( $buttons, 'neotext_blockquote' );
	return $buttons;
}

// Button 2: Declare script for new button: q
function neotext_add_tinymce_q_plugin( $plugin_array ) {
	$plugin_array['neotext_q'] = plugins_url('/neotext/js/tinymce-q.js');
	return $plugin_array;
}

function neotext_register_tinymce_q( $buttons ) {
	array_push( $buttons, 'neotext_q' );
	return $buttons;
}

?>