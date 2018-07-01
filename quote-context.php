<?php
/**
 * @package CiteIt
 * @version 0.51
 */
/*
Plugin Name: CiteIt Quote-Context
Plugin URI: http://www.CiteIt.net

Description: Expands "blockquotes" with surrounding text by : selecting all "blockquote" tags that have a "cite" attribute, downloading the cited url, locating the citation, saving the "before" and "after" text into a json file, and adding the retrieved text to the dom.  Submits Quotations from Published posts to the CiteIt.net web service.
Author: Tim Langeman
Version: 0.51
Author URI: http://www.openpolitics.com/tim
*/
$plugin_version_num = "0.51";

function neotext_quote_context_header() {
 # Add javascript depencencies to html header
	wp_enqueue_script('jquery');
    wp_enqueue_script('sha256', plugins_url('lib/forge-sha256/build/forge-sha256.min.js', __FILE__) );
    wp_enqueue_script('quote-context', plugins_url('js/versions/0.3/CiteIt-quote-context.js', __FILE__) );
}

function neotext_quote_context_hack(){
	echo "
	<script type='text/javascript'></script>";
}

function neotext_quote_context_footer() {
  # Adds style sheets, ui javascript, hiddend div id="neotext_container"
  # Add call to .quoteContext() custom jQuery function

	wp_enqueue_style('neotext_quote_context_css', plugins_url('css/quote-context-style.css', __FILE__) );

	echo "<div id='neotext_container'><!-- neotext quote-context.js injects data returned from lookup in this hidden div --></div>
    <script type='text/javascript'>
	    // Call plugin on all blockquotes:
        	jQuery('q, blockquote').quoteContext();
    </script>";

	wp_enqueue_script('jquery-ui', '//code.jquery.com/ui/1.11.4/jquery-ui.min.js');
	wp_enqueue_style('jquery-ui-css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.css');
}

add_action( 'wp_enqueue_scripts', 'neotext_quote_context_header' );
add_action( 'wp_head', 'neotext_quote_context_hack');
add_action( 'wp_footer', 'neotext_quote_context_footer');

/*********** Modify Publish Action: Submit to Neotext ******/

function post_to_neotext($post_url){
  // Post $url to $webservice_url using curl

  $webservice_url = "http://write.citeit.net/";
  $post_fields = 'url=' . $post_url;
  $curl_user_agent = "CiteIt.net Wordpress v" . $plugin_version_num . " (http://www.CiteIt.net)";

  $ch = curl_init( $webservice_url );
  curl_setopt($ch,CURLOPT_USERAGENT, $curl_user_agent);
  curl_setopt( $ch, CURLOPT_POST, 1);
  curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_fields);
  curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt( $ch, CURLOPT_HEADER, 0);
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

  $response = curl_exec( $ch );
  return $response;
}

function count_quotations($html){
  /* Count the number of quotations using the 'cite' tag
  * (Used to determine if URL should be submitted to CiteIt.net)
  *
  * Credit: http://htmlparsing.com/php.html
  */
  $quotations_count = 0;

  # Parse the HTML
  # The @ before the method call suppresses any warnings that
  # loadHTML might throw because of invalid HTML in the page.
  $dom = new DOMDocument();
  @$dom->loadHTML($html);
  $xpath = new DOMXpath($dom);

  # Iterate over all the <blockquote> and <q> tags
  $quotations = $xpath->query('//blockquote | //q');

  //foreach($dom->getElementsByTagName('blockquote') as $quote) {
  foreach($quotations as $quote) {
    $cite_url = $quote->getAttribute('cite');

	// If URL in valid form:
	if (!filter_var($cite_url, FILTER_VALIDATE_URL) === false) {
    	$quotations_count = $quotations_count + 1;
	}
  }
  return $quotations_count;
}

function neotext_hook($post_id) {
  /* Determine whether to submit post URL to Neotext,
   * Depending upon whether a quote uses the 'cite' tag with
   * a URL of valid format
   */
  $quotations_count = 0;
  $post_url = get_permalink($post_id);
  $subject = 'A CiteIt has been published';
  $post_content = get_post_field('post_content', $post_id);

  $message = "A CiteIt has been updated on your website:\n\n";
  $message .= $post_url . "\n";
  $message .= $content;

  $quotations_count = count_quotations($post_content);
  if ($quotations_count > 0){

    # Make Sure the Post URL is of Valid Form
    if (!filter_var($post_url, FILTER_VALIDATE_URL) === false) {
        post_to_neotext($post_url);
		wp_mail( 'timlangeman@gmail.com', $subject, $message );
    } else {
        echo("Post ULR <$url> is not a valid URL");
    }
  }
}

add_action( 'publish_post', 'neotext_hook', 10, 2 );

/***************** Add TinyMCE Admin Buttons ********************/
# Tiny MCE: add Custom CiteIt button to editor
# Credit: AJ Clarke: http://www.wpexplorer.com/wordpress-tinymce-tweaks/

# Button 1: Declare script for new button: blockquote
function neotext_add_tinymce_blockquote_plugin( $plugin_array ) {
	$plugin_array['neotext_blockquote'] = plugins_url('/CiteIt/js/tinymce-blockquote.js');
	return $plugin_array;
}

function neotext_register_tinymce_blockquote( $buttons ) {
	array_push( $buttons, 'neotext_blockquote' );
	return $buttons;
}

# Button 2: Declare script for new button: q
function neotext_add_tinymce_q_plugin( $plugin_array ) {
	$plugin_array['neotext_q'] = plugins_url('/CiteIt/js/tinymce-q.js');
	return $plugin_array;
}

function neotext_register_tinymce_q( $buttons ) {
	array_push( $buttons, 'neotext_q' );
	return $buttons;
}

# Hooks your functions into the correct filters
function neotext_add_mce_quotation_buttons() {
	# check user permissions
	if ( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) {
		return;
	}
	# check if WYSIWYG is enabled
	if ( 'true' == get_user_option( 'rich_editing' ) ) {
		add_filter( 'mce_external_plugins', 'neotext_add_tinymce_blockquote_plugin' );
		add_filter( 'mce_buttons', 'neotext_register_tinymce_blockquote' );

		add_filter( 'mce_external_plugins', 'neotext_add_tinymce_q_plugin' );
		add_filter( 'mce_buttons', 'neotext_register_tinymce_q' );
	}
}

add_action('admin_head', 'neotext_add_mce_quotation_buttons');

?>
