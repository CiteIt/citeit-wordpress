/*
 * Quote-Context JS Library
 * https://github.com/neotext/neotext-quote-context/
 *
 * Copyright 2015-2016, Tim Langeman
 * https://www.openpolitics.com/tim
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 *
 * This is a jQuery function that locates all "blockquote" and "q" tags
 * within an html document and calls the neotext.net web service to
 * locate contextual info about the requested quote.
 *
 * The neotext web service returns a json dictionary and this script
 * injects the returned contextual data into hidden html elements to be
 * displayed when the user hovers over or clicks on the cited quote.
 *
 * Demo: http://www.neotext.net/demo/
 *
 * Dependencies:
 *  - jQuery: https://jquery.com/
 *  - Sha1: https://github.com/chrisveness/crypto/blob/master/sha1.js
*/

popup_library = "jQuery";
hidden_container = "neotext_container";	// div in footer than holds injected json data, requires css class to hide
jQuery.curCSS = 'jQuery.css';
version_num = "0.02";

// Remove anchor from URL
current_page_url = window.location.href.split('#')[0]

jQuery.fn.quoteContext = function() {
	// Add "before" and "after" sections to quote excerpts
	// Designed to work for "blockquote" and "q" tags

	//Setup hidden div to store all quote metadata

	jQuery(this).each(function(){
		// Loop through all the submitted tags (blockquote or q tags) and see if any have a cite attribute
		if( jQuery(this).attr("cite") ){
			var blockcite = jQuery(this);
			var cited_url = blockcite.attr("cite");
			var citing_url = current_page_url;
			var citing_quote = blockcite.text();

			// If they have a cite tag, check to see if its hash is already saved
			if (cited_url.length > 3){
				var tag_type = jQuery(this)[0].tagName.toLowerCase();
				var url_quote_text = trim_encode(citing_quote) + '|' + trim_encode(citing_url) + '|' + trim_encode(cited_url);
				var quote_hash = Sha1.hash(url_quote_text);
				var shard = quote_hash.substring(0,2);
				var read_base = 'https://read.neotext.net/quote/';
				var read_url = read_base.concat("sha1/", version_num, "/", shard, "/", quote_hash, ".json");
				var json = null;

				//See if a json summary of this quote was already created and uploaded to the content delivery network
			    jQuery.ajax({
			        type: "GET",
					url: read_url,
			        dataType: "json",
			        success: function(json) {
			            add_quote_to_dom(tag_type, json );
						console.log("Neotext Found: " + read_url);
						console.log("       Quote: " + citing_quote);
			        },
			        error: function() {
						console.log("Neotext Missed: " + read_url);
						console.log("       Quote: " + citing_quote);
			        }
			    });

				function add_quote_to_dom(tag_type, json ) {
					if ( tag_type == "q"){
						var q_id = "hidden_" + json['sha1'];

						//Add content to a hidden div, so that the popup can later grab it
						jQuery("#" + hidden_container).append(
							"<div id='" + q_id + "' class='highslide-maincontent'>.. " +
								json['cited_context_before'] + " " + " <strong>" + json['citing_quote'] + "</strong> " +
								json['cited_context_after'] + ".. </p>" +
								"<p><a href='" + json['cited_url'] + "' target='_blank'>Read more</a> | " +
								"<a href='javascript:close_popup(" + q_id + ");'>Close</a> </p></div>");

						//Style quote as a link that calls the popup expander:  return this.onclick()
						blockcite.wrapInner("<a href='" + blockcite.attr('cite') + "' " +
							"onclick='return expand_popup(this ,\"" + q_id +"\")' " +
						 " />");
					}
					else if ( tag_type == "blockquote"){
						//Fill 'before' and 'after' divs and then quickly hide them
						blockcite.before(" \
							<div id='quote_before_" + json['sha1'] + "' class='quote_context'> \
							  <blockquote class='quote_context'>.. " + json["cited_context_before"] + "</blockquote> \
							</div> \
						");

						blockcite.after("<div id='quote_after_" + json['sha1'] + "' class='quote_context'> \
							<blockquote class='quote_context'>" + json["cited_context_after"] + " ..</blockquote></div> \
							<div class='neotext_source'><span class='neotext_source_label'>source: </span> \
							<a class='neotext_source_domain' href='" + json['cited_url'] + "'>" + extractDomain( json['cited_url'] ) + "</a></div>");

						var context_before = jQuery("#quote_before_" + json['sha1']);
						var context_after = jQuery("#quote_after_" + json['sha1']);

						context_before.hide();
						context_after.hide();

						//Display arrows if content is found
						if( json['cited_context_before'].length > 0){
						context_before.before("<div class='quote_arrows' id='context_up_" + json['sha1'] + "'> \
							<a href=\"javascript:toggle_quote('before', 'quote_before_" + json['sha1'] + "');\">&#9650;</a></div>");
						}
						if( json['cited_context_after'].length > 0){
						context_after.after("<div class='quote_arrows' id='context_down_" + json['sha1'] +"'> \
							<a href=\"javascript:toggle_quote('after', 'quote_after_" + json['sha1'] +"');\">&#9660;</a></div>");
						}
					}
				}

			} // if url.length is not blank
		}	// if "this" has a "cite" attribute
	});	   //   jQuery(this).each(function() { : blockquote, or q tag

};

function toggle_quote(section, id){
	jQuery("#" + id).fadeToggle();
}

function expand_popup(tag, hidden_popup_id){
  if (popup_library == "highslide"){
	return hs.htmlExpand(tag, {maincontentId: hidden_popup_id });
  }
  else {
	jQuery.curCSS = jQuery.css;

	// Setup Initial Dialog box
	jQuery("#" + hidden_popup_id).dialog({
		autoOpen: false,
		closeOnEscape: true,
		closeText: "hide",
		draggableType: true,
		resizable: true,
		width: 400,
		modal: false,
		title: 'powered by neotext.net',
		hide: { effect: "size", duration: 400 },
		show: { effect: "scale", duration: 400 },
	  });

	// Add centering and other settings
	jQuery("#" + hidden_popup_id).dialog("option", "position", { at: "center center", of: tag}
		).dialog("option", "hide", { effect: "size", duration: 400 }
		).dialog("option", "show", { effect: "scale", duration: 400 }
		).dialog( {"title" : "powered by neotext.net"}
		).dialog("open"
		).blur(
	);

	// Close popup when you click outside of it
	jQuery(document).mouseup(function(e) {
	  var popupbox = jQuery(".ui-widget-overlay");
	  if (popupbox.has(e.target).length === 0){
	  	 //$("#" + hidden_popup_id).dialog("close");
	  }
	});

	return false; // Don't follow link
  }
}

function close_popup(hidden_popup_id){
	// assumes jQuery library
	jQuery(hidden_popup_id).dialog("close");
}

//Source: http://stackoverflow.com/questions/10032024/how-to-remove-leading-and-trailing-white-spaces-from-a-given-html-string
// Credit:  KhanSharp:  (used for backward compatibility.
//          trim() introduced in javascript 1.8.1)
function trim_regex(str){
  return str.replace(/^[ ]+|[ ]+$/g,'')
}

function trim_encode(str){
	 //trimmed_str = trim_regex(str);
   return str.trim();
}


// Credit: http://stackoverflow.com/questions/8498592/extract-root-domain-name-from-string
function extractDomain(url) {
    var domain;
    //find & remove protocol (http, ftp, etc.) and get domain
    if (url.indexOf("://") > -1) {
        domain = url.split('/')[2];
    }
    else {
        domain = url.split('/')[0];
    }

    //find & remove port number
    domain = domain.split(':')[0];

    return domain;
}
