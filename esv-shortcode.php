<?php
/*
Plugin Name: ESV Bible Shortcode for WordPress
Plugin URI: http://wordpress.org/extend/plugins/esv-bible-shortcode-for-wordpress/
Author URI: http://calebzahnd.com
Description: This plugin uses the ESV Bible Web Service API to provide an easy way to display scripture in the ESV translation using WordPress shortcodes.
Author: Caleb Zahnd
Contributors: calebzahnd
Tags: shortcode, Bible, church, English Standard Version, scripture
Version: 1.0.22
Requires at least: 2.7
Tested up to: 3.8.1
Stable tag: 1.0.2
*/

class esv_shortcode_class
{
  public static $version = '1.0.22';
  public static $options_version = 1;  // version of the options structure
  public static $default_expire_seconds = "1w";  // default expiration time, 0 = no caching
  public static $default_expire_seconds_limit = "30d";
  public static $default_size_limit = 0; // limit for cached entry size, 0 is no limit

  public static $aMults = array('s' => 1,
				'm' => MINUTE_IN_SECONDS,
				'h' => HOUR_IN_SECONDS,
				'd' => DAY_IN_SECONDS,
				'w' => WEEK_IN_SECONDS
				);

  // To be passed to register_activation_hook.
  public static function add_defaults() {

    $tmp = get_option('esv_shortcode_options');

    // The key_exists is because when I introduced chkreset I couldn't get it into the array.  It's a kludge and may not need to be there.    
    if ((!is_array($tmp))|| (!array_key_exists('chkreset', $tmp)) || (isset($tmp['chkreset']))) {

      $arr = array('options_version' => self::$options_version, 'expire_seconds' => self::$default_expire_seconds, 'expire_seconds_limit' => self::$default_expire_seconds_limit, 'size_limit' => self::$default_size_limit, 'was_reset' => isset($tmp['chkreset'])?$tmp['chkreset']: false);
      $arr['chkreset'] = false;

      update_option('esv_shortcode_options', $arr);

    }

  } // add_defaults


  // To be passed to register_uninitall_hoo'k.
  public static function uninstall()
  {
    delete_option('esv_shortcode_options');
  } // uninstall


  // for admin_menu action.
  public static function plugin_admin_add_page()
  {
    add_options_page('ESV Bible Shortcode', 'ESV Bible Shortcode', 'manage_options', __FILE__, array('esv_shortcode_class', 'esv_shortcode_options_page'));
  } // plugin_admin_add_page

  // Display the admin options page, passed to add_options_page.
  public static function esv_shortcode_options_page()
  {
    ?> <div class="wrap"> <h2>ESV Bible Shortcode plugin v<?php echo self::$version;?></h2> <p>Options relating to the ESV Bible Shortcode Plugin.</p><br/>
    <?php
    $opts = get_option('esv_shortcode_options');
    if (isset($opts['was_reset']) && $opts['was_reset'])
    {
      ?><p>Options were reset to default values.</p><br/><?php
      $opts['was_reset'] = false;
      update_option('esv_shortcode_options', $opts);
    } // if reset
    ?><form action="options.php" method="post">
    <?php settings_fields('esv_shortcode_options'); ?>
    <?php do_settings_sections(__FILE__);
    ?>   <br/><input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
    </form></div>
	<?php
	} // esv_shortcode_options_page

  // Used in call to register_setting.
  public static function esv_shortcode_options_validate($input)
  {
    $options = get_option('esv_shortcode_options');
    if (isset($input['expire_seconds_limit']))
    {
      $expire = $input['expire_seconds_limit'];
      $options['expire_seconds_limit'] = self::expire_to_seconds($expire);

    } // if expire
    if (isset($input['expire_seconds']))
    {
      $options['expire_seconds'] = $input['expire_seconds'];
    }
    if (isset($input['size_limit']))
    {
      $options['size_limit'] = $input['size_limit'];
    }
    return $options;
  } // esv_shortcode_options_validate

  // Passed to admin_init action.
  public static function plugin_admin_init()
  {
    register_setting( 'esv_shortcode_options', 'esv_shortcode_options', array('esv_shortcode_class', 'esv_shortcode_options_validate') );
    // Add settings for V1.22 if other settings are present.
    $options = get_option('esv_shortcode_options');
    if (is_array($options))
    {
      // Make sure expire_seconds_limit and size_limit are in the options.  They might not be there because of updating from v1.21 to 1.22.
      // If the options array doesn't exist, this will be taken care of in add_defaults.
      $need_update = false;
      if (!array_key_exists('expire_seconds_limit', $options))
      {
	$options['expire_seconds_limit'] = self::$default_expire_seconds_limit;
	$need_update = true;
      }
      if (!array_key_exists('size_limit', $options))
      {
	$options['size_limit'] = self::$default_size_limit;
	$need_update = true;
      }
      if ($need_update) update_option('esv_shortcode_options', $options);
    } // options exists

    add_settings_section('esv_shortcode_main', 'ESV Shortcode Settings', array('esv_shortcode_class', 'section_text'), __FILE__);
    add_settings_field('esv_shortcode_expire_seconds', 'Default expiration time (seconds)', array('esv_shortcode_class', 'expire_field'), __FILE__, 'esv_shortcode_main');
    add_settings_field('esv_shortcode_size_limit', 'Cached passage size limit', array('esv_shortcode_class', 'size_limit_field'), __FILE__, 'esv_shortcode_main');
    add_settings_field('esv_shortcode_expire_seconds_limit', 'Max cache entry expiration time', array('esv_shortcode_class', 'expire_seconds_limit_field'), __FILE__, 'esv_shortcode_main');
    add_settings_field('esv_shortcode_chkreset', 'Reset options on next activation', array('esv_shortcode_class', 'chkreset_field'), __FILE__, 'esv_shortcode_main');
  } // plugin_admin_init

  // Writes the section prologue.
  public static function section_text()
  {
  } // section_text

  public static function expire_field()
  {
    $options = get_option('esv_shortcode_options');
    echo "<input id='esv_shortcode_expire_seconds' name='esv_shortcode_options[expire_seconds]' size='40' type='text' value='{$options['expire_seconds']}' />";
  } // expire_field

  public static function size_limit_field()
  {
    $options = get_option('esv_shortcode_options');
    echo "<input id='esv_shortcode_size_limit' name='esv_shortcode_options[size_limit]' size='40' type='text' value='{$options['size_limit']}' />";
  } // size_limit_field

  public static function expire_seconds_limit_field()
  {
    $options = get_option('esv_shortcode_options');
    echo "<input id='esv_shortcode_expire_seconds_limit' name='esv_shortcode_options[expire_seconds_limit]' size='40' type='text' value='{$options['expire_seconds_limit']}' />";
  } // expire_seconds_limit_field

  // @param string $expire
  // @return string containing $expire multiplied by the appropriate constant if it ends in a multiplier character.
  // If $expire does not end with one of the multiplier characters, $expire is returned without modification.
  public static function expire_to_seconds($expire)
  {
    $msg = "expire_to_seconds: "; // debug
    
    $multchar = substr($expire, -1);
    $msg .= "multchar=$multchar"; // debug
    if (array_key_exists($multchar, self::$aMults))
    {
      $msg .= ", multchar found with value {self::$aMults[$multchar]}"; // debug
      $expire *= self::$aMults[$multchar];
    } // if multchar
    return $expire;
  } // expire_to_seconds

  public static function chkreset_field()
  {
    $options = get_option('esv_shortcode_options');
    echo "<input id='esv_shortcode_chkreset' name='esv_shortcode_options[chkreset]' type='checkbox' value='1'".checked(1, $options['chkreset'], false)." />";
  } // chkreset_field

  // Shortcode: [esv scripture="John 3:16-23"]
  // @param array $atts
  public static function esv($atts)
  {
    $opts = get_option('esv_shortcode_options');
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
				   'expire_seconds' => $opts['expire_seconds'],
				   'size_limit' => $opts['size_limit'],
				   'debug' => false,
				   'remove' => false
				   ), $atts ) );
    if ($remove == 'false') $remove = false;
    if ($debug == 'false') $debug = false;
    $msg = ""; // debug
    // Handle expiration time multipliers
    //$msg .= "expire_seconds = $expire_seconds, expire_to_seconds(expire_seconds)=".esv_shortcode_class::expire_to_seconds($expire_seconds); // debug
    $expire_seconds = esv_shortcode_class::expire_to_seconds($expire_seconds);



    $expire_seconds_limit = array_key_exists('expire_seconds_limit', $opts)?esv_shortcode_class::expire_to_seconds($opts['expire_seconds_limit']):null;
    if ($expire_seconds_limit)
    {
      if ($expire_seconds > $expire_seconds_limit) $expire_seconds = $expire_seconds_limit;
    } // if $expire_seconds_limit
    $key = "IP";
    $passage = urlencode($scripture);
    $options = "include_passage_references=".$include_passage_references."&include_first_verse_numbers=".$include_first_verse_numbers."&include_verse_numbers=".$include_verse_numbers."&include_footnotes=".$include_footnotes."&include_footnote_links=".$include_footnote_links."&include_headings=".$include_headings."&include_subheadings=".$include_subheadings."&include_surrounding_chapters=".$include_surrounding_chapters."&include_word_ids=".$include_word_ids."&link_url=".$link_url."&include_audio_link=".$include_audio_link."&audio_format=".$audio_format."&audio_version=".$audio_version."&include_short_copyright=".$include_short_copyright."&include_copyright=".$include_copyright."&output_format=".$output_format."&include_passage_horizontal_lines=".$include_passage_horizontal_lines."&include_heading_horizontal_lines=".$include_passage_horizontal_lines;
    $url = "http://www.esvapi.org/v2/rest/passageQuery?key=".$key."&passage=".$passage."&".$options;
    $hash = "esv" . md5($url);
    $msg .= "from cache entry $hash"; // debug
    $response = get_transient($hash);
    if (!$expire_seconds || !$response)
    {
      // fetch passage from server
      $msg .= "fetched, not cached, expire_seconds=$expire_seconds, expire_seconds_limit=$expire_seconds_limit"; // debug
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
} // esv_shortcode_class

register_activation_hook(__file__, array('esv_shortcode_class', 'add_defaults'));
register_uninstall_hook(__FILE__, array('esv_shortcode_class', 'uninstall'));
add_action('admin_menu', array('esv_shortcode_class', 'plugin_admin_add_page'));
add_action('admin_init', array('esv_shortcode_class', 'plugin_admin_init'));
add_shortcode('esv', array('esv_shortcode_class', 'esv'));

  ?>