<?php
/*
Plugin Name: ESV Bible Shortcode for WordPress
Plugin URI: http://wordpress.org/extend/plugins/esv-bible-shortcode-for-wordpress/
Author URI: http://calebzahnd.com
Description: This plugin uses the ESV Bible Web Service API to provide an easy way to display scripture in the ESV translation using WordPress shortcodes.
Author: Caleb Zahnd
Contributors: calebzahnd
Tags: shortcode, Bible, church, English Standard Version, scripture
Version: 1.0.21
Requires at least: 2.5
Tested up to: 3.8.1
Stable tag: 1.0.2
*/

/*
3/11/14 Changed ...limit to ...size_limit.
2/26/14 Previous saved to HG rev 1.
2/26/14 Added remove option.
2/25/14 Added option to cache passages.  uses the transients API, uses "esv" . md5(URL that fetches the passage).
If container or class is set to empty the element or attribute is not added.  Added option expire_seconds is set to the number of seconds to cache the passages.  Its value can be suffixed with "m", "h", "d", or "w" for minute, hour, day, or week, respectively.
Added option limit, if nonzero sets limit of size to cache.
Added option debug, if true adds a message indicating whether the entry came from the cache or the server.
*/

$esv_shortcode_default_expire_seconds = WEEK_IN_SECONDS;  // default expiration time, 0 = no caching
$esv_shortcode_default_size_limit = 0; // limit for cached entry size, 0 is no limit

// Shortcode: [esv scripture="John 3:16-23"]
function esv($atts)
{

  global $esv_shortcode_default_expire_seconds, $esv_shortcode_default_size_limit;
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
				 'include_heading_horizontal_lines'	=>	'false',
				 'expire_seconds' => $esv_shortcode_default_expire_seconds,
				 'size_limit' => $esv_shortcode_default_size_limit,
				 'debug' => false,
				 'remove' => false
				 ), $atts ) );
  // Handle expiration time multipliers
  $aMults = array('s' => 1,
		  'm' => MINUTE_IN_SECONDS,
		  'h' => HOUR_IN_SECONDS,
		  'd' => DAY_IN_SECONDS,
		  'w' => WEEK_IN_SECONDS
		  );
  $multchar = substr($expire_seconds, -1);
  if (array_key_exists($multchar, $aMults))
  {
    $expire_seconds *= $aMults[$multchar];
  } // if multchar



  $key = "IP";
  $passage = urlencode($scripture);
  $options = "include_passage_references=".$include_passage_references."&include_first_verse_numbers=".$include_first_verse_numbers."&include_verse_numbers=".$include_verse_numbers."&include_footnotes=".$include_footnotes."&include_footnote_links=".$include_footnote_links."&include_headings=".$include_headings."&include_subheadings=".$include_subheadings."&include_surrounding_chapters=".$include_surrounding_chapters."&include_word_ids=".$include_word_ids."&link_url=".$link_url."&include_audio_link=".$include_audio_link."&audio_format=".$audio_format."&audio_version=".$audio_version."&include_short_copyright=".$include_short_copyright."&include_copyright=".$include_copyright."&output_format=".$output_format."&include_passage_horizontal_lines=".$include_passage_horizontal_lines."&include_heading_horizontal_lines=".$include_passage_horizontal_lines;
  $url = "http://www.esvapi.org/v2/rest/passageQuery?key=".$key."&passage=".$passage."&".$options;
  $hash = "esv" . md5($url);
  $msg = "from cache entry $hash"; // debug
  $response = get_transient($hash);
  if (!$expire_seconds || !$response)
  {
    // fetch passage from server
    $msg = "fetched, not cached"; // debug
    $ch = curl_init($url); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    $response = curl_exec($ch);
    curl_close($ch);
    if ($expire_seconds && (!$size_limit || strlen($response) < $size_limit))
    {
      $msg = "fetched, ". strlen($response)." bytes cached as $hash for $expire_seconds seconds"; // debug
      set_transient($hash, $response, $expire_seconds);
    } // cache
  } // fetch passage

  if ($remove)
  {
    $fRtn = delete_transient($hash);
    $msg .= $fRtn?", removed":", remove failed"; // debug

  } // remove

    //Display the Title as a link to the Post's permalink.
    //return ("$response;
  return ($debug?"[$msg]":'') . ($container?"<".$container. ($class?" class=\"" . $class:'') . "\">":'') . $response . ($container?"</".$container.">":'');
  print $url;
}
  add_shortcode('esv', 'esv');

  ?>