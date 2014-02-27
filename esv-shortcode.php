<?php
/*
Plugin Name: ESV Bible Shortcode for WordPress
Plugin URI: http://wordpress.org/extend/plugins/esv-bible-shortcode-for-wordpress/
Author URI: http://calebzahnd.com
Description: This plugin uses the ESV Bible Web Service API to provide an easy way to display scripture in the ESV translation using WordPress shortcodes.
Author: Caleb Zahnd
Contributors: calebzahnd
Tags: shortcode, Bible, church, English Standard Version, scripture
Version: 1.0.2
Requires at least: 2.5
Tested up to: 3.3.1
Stable tag: 1.0.2
*/


// Shortcode: [esv scripture="John 3:16-23"]
function esv($atts)
{

extract( shortcode_atts( array(
	'scripture'	    			 		=>	'John 3:16',
	'container' 	    				=>	'span',
	'class'								=>	'esv-scripture',
	'include_passage_references'		=>	'true',
	'include_first_verse_numbers'		=>	'false',
	'include_verse_numbers'				=>	'true',
	'include_footnotes'					=>	'false',
	'include_footnote_links'			=>	'false',
	'include_headings'					=>	'false',
	'include_subheadings'				=>	'false',
	'include_surrounding_chapters'		=>	'false',
	'include_word_ids'					=>	'false',
	'link_url'							=>	'http://www.gnpcb.org/esv/search/',
	'include_audio_link'				=>	'false',
	'audio_format'						=>	'flash',
	'audio_version'						=>	'hw',
	'include_short_copyright'			=>	'false',
	'include_copyright'					=>	'false',
	'output_format'						=>	'html',
	'include_passage_horizontal_lines'	=>	'false',
	'include_heading_horizontal_lines'	=>	'false'
	), $atts ) );

  $key = "IP";
  $passage = urlencode($scripture);
  $options = "include_passage_references=".$include_passage_references."&include_first_verse_numbers=".$include_first_verse_numbers."&include_verse_numbers=".$include_verse_numbers."&include_footnotes=".$include_footnotes."&include_footnote_links=".$include_footnote_links."&include_headings=".$include_headings."&include_subheadings=".$include_subheadings."&include_surrounding_chapters=".$include_surrounding_chapters."&include_word_ids=".$include_word_ids."&link_url=".$link_url."&include_audio_link=".$include_audio_link."&audio_format=".$audio_format."&audio_version=".$audio_version."&include_short_copyright=".$include_short_copyright."&include_copyright=".$include_copyright."&output_format=".$output_format."&include_passage_horizontal_lines=".$include_passage_horizontal_lines."&include_heading_horizontal_lines=".$include_passage_horizontal_lines;
  $url = "http://www.esvapi.org/v2/rest/passageQuery?key=".$key."&passage=".$passage."&".$options;
  $ch = curl_init($url); 
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
  $response = curl_exec($ch);
  curl_close($ch);


//Display the Title as a link to the Post's permalink.
//return ("$response;
return ("<".$container." class=\"" . $class . "\">" . $response . "</".$container.">");
print $url;
}
add_shortcode('esv', 'esv');

?>