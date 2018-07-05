<?php
/*
Plugin Name: ESV Bible Shortcode for WordPress
Plugin URI: http://wordpress.org/extend/plugins/esv-bible-shortcode-for-wordpress/
Author URI: http://calebzahnd.com
Description: This plugin uses the ESV Bible Web Service API to provide an easy way to display scripture in the ESV translation using WordPress shortcodes.  Passages can be cached on the local server as transients.  It provides  a settings page where the administrator can set the default expiration time, maximum expiration time, and maximum size of a cached passage, and also shows access statistics.
Author: Caleb Zahnd
Contributors: calebzahnd
Tags: shortcode, Bible, church, English Standard Version, scripture
Version: 1.1.5
Requires at least: 2.7
Tested up to: 4.9.5
Stable tag: 1.0.2
*/
  // see also version at start of class esv_shortcode


/*
11/12/16 When a passage reference starts with @, it is treated as a message and is displayed verbatim.  V1.0.25.
11/11/16 In process_passages_list now does not check passage references that start with #.

6/27/15 Added ESV search form on the plugin settings page.  This is to aid in checking scripture reference syntax, although it does not use the plugin.  
6/23/15 Conditions of use link now opens in a new window.
2/27/15 Replaced SECONDS_IN_DAY with DAY_IN_SECONDS.
2/24/15 Added esv_ref shortcode.
Made method add_action_links a static method.
Removed comment from version line in header.
Changed version to 1.0.24.
7/5/14 Previous saved to HG rev 9.
7/5/14 Moved code that process the (%w...) out of process_passage_name into make_passage_timestamp.
Added shortcode esv_date.
7/5/14 Previous saved to HG rev 8.
7/1/14 Indented code.
Updated README.
6/27/14 Updated debugging messages in esv.
Removed "getopts" from message in admin_init.
Activated ref_error so new references are checked with the server.  Added debug message displaying when a ref is checked.  Works.  Commented out these debug messages and the ones in validate and process_passages_list that print the passages array.
Changed version to 1.0.23.
6/26/14 In process_passage_name changed logic handling "date" to handle missing offset and date.  How did it ever work?
6/26/14 Added nl2br around debug messages from esv.
Made comparison of passage names case-insensitive.  (We force stored passage names to lower case and use the lower case of the final passage name to retrieve the passage.  A case-insensitive access would be preferable.
6/21/14 Fixed passage handling code in esv.
6/21/14 Added call to process_passage_name to expand date formatting codes.
6/20/14 Changed code that generates the passages list in the textarea to include passage names.
Added debugging messages for how scripture is obtained.
  6/20/14 In process_passages_list changed reference checking algorithm to check passage references not in the old list.  Now removes multiple spaces in passage references.
6/18/14 Fixed syntax errors.  Changed input for passage list to textarea.  Added code to call process_passage_list in esv_shortcode_validate.
5/7/14 Changed list_lines to implode.
Added ESV response error checking.
Corrected uptate_options syntax error.
4/23/14 Added handling of flag in passage ref retrieval.  Might add debugging to show that a passage ref was used.
4/22/14 Added process_passage_name.
In function esv changed $passage to $ref, added option 'passage', and added code to process it; '$ref = ... $scripture' becomes the else clause of this processing.  Does not yet check for a flag character starting a passage reference flagging a syntax error.
4/21/14 Added access key field in settings section and validate.  Does not need to be in add_defaults.  Changed definition in function esv.
4/20/14 Added fields section for passages and supporting functions.  Method ref_error must be completed.
ref_error assumes option 'key' which has not been implemented.
Added comment in esv_shortcode_options_validate.
4/4/14 Previous saved to HG rev 7.
4/2/14 Added method get_response to get the text from the Bible text server.
Corrected message in esv_options_onsubmit for resetting statistics to not indicate on next activation.  It is done in validate.
3/29/14 Added settings link.
3/29/14 Expanded Conditions of Use link text.
Indented code with a mixture of php-mode and html-mode, tedious process.
3/29/14 Added label_for arg in args array for adding fields.
3/28/14 Changed stats_defaults to receive and return an options array.
Added reset of statistics to esv_shortcode_options_validate.
Added methods get_opts and update_opts.  Replaced calls to get_option and update_option.
3/28/14 Added code in esv_shortcode_options_validate to clear was_reset.  For some reason I can't clear it where I write the message.
onsubmit checking of reset buttons now works.  Added alert for chkresetstats as well.
Resetting of statistics is not working.
3/27/14 In stats_record moved new day initialization before incrementing fetch count.
Added test of chkreset in fnOmSubmit.
3/27/14 Now displays opts array before form.
3/24/14 Added stats section.
3/23/14 Added stats_defaults, stats_record, and stats_write.
Added code in esv to record stats.
3/22/14 Added script to ask for confirmation when saving form with reset to defaults is checked.
Added debug message in options form.
3/21/14 Changed options page fields so that units info is on a line below the field instead of part of the field name.
3/20/14 Added conditions of use link in admin page.
3/19/14 Previous saved to HG rev 4.
*/

/*
Testing:

Options not directly passed to passage server:
'container' (not included if empty, default if not specified)
'class' (not included if empty, default if not specified)
'expire_seconds' (no multiplier, multipliers, verify cache time, capped at size limit)
'size_limit' (0=no limit, verify passage over size limit not cached.)
'debug'
'remove' (if in cache, if not in cache, if fetched should be immediately removed)


Admin panel:
expire_seconds, expire_secondt_limit (default value shown initially, multipliers, what if empty?)
reset on activation: does not reset when activated if not set, does if set.  Confirm message appears, not saved if cancelled.
stats: total hits, fetches; max fetches per day)
reset stats on save if checked, checkbox clear when reloaded.  Confirm message appears, not saved if cancelled.
*/

class esv_shortcode_class
{
  public static $version = '1.1.5';
  public static $ref_msg_symbol = '@'; // Symbol that indicates that a passage "reference" is a message to be output verbatim.
  public static $psg_spec_sep = ";"; // delimits multiple passage specs in the passage attribute
  public static $options_version = 1;  // version of the options structure
  public static $default_expire_seconds = "1w";  // default expiration time, 0 = no caching
  public static $default_expire_seconds_limit = "30d";
  public static $default_size_limit = 0; // limit for cached entry size, 0 is no limit
  public static $apiv2_psg_url = "http://www.esvapi.org/v2/rest/passageQuery";
  public static $apiv2_query_url = "http://www.esvapi.org/v2/rest/queryInfo";
  public static $apiv3_html_url = "https://api.esv.org/v3/passage/html/";
  public static $apiv3_text_url = "https://api.esv.org/v3/passage/text/";


  public static $aMults = array('s' => 1,
				'm' => MINUTE_IN_SECONDS,
				'h' => HOUR_IN_SECONDS,
				'd' => DAY_IN_SECONDS,
				'w' => WEEK_IN_SECONDS
				);


  public static function get_opts()
  {
    return get_option('esv_shortcode_options');
  } // get_opts

  public static function update_opts($opts)
  {
    update_option('esv_shortcode_options', $opts);
  } // update_opts

  // To be passed to register_activation_hook.
  public static function add_defaults()
  {

    $tmp = self::get_opts();

    // The key_exists is because when I introduced chkreset I couldn't get it into the array.  It's a kludge and may not need to be there.    
    if (!is_array($tmp) || !array_key_exists('chkreset', $tmp) || isset($tmp['chkreset']))
    {

      $arr = array('options_version' => self::$options_version, 'expire_seconds' => self::$default_expire_seconds, 'expire_seconds_limit' => self::$default_expire_seconds_limit, 'size_limit' => self::$default_size_limit, 'was_reset' => isset($tmp['chkreset'])?$tmp['chkreset']: false,
		   );
      $arr['chkreset'] = false;

      $arr = self::stats_defaults($arr);
      self::update_opts($arr);

    } // if

  } // add_defaults




  // Set statistics values to their defaults.  // @param array $opts Options array.
  // @returns $opts with stats values set to default values.
  public static function stats_defaults($opts)
  {
    $time = current_time('timestamp');
    $today = $time - $time % DAY_IN_SECONDS;
    $defaults = array(
	  'hit_count' => 0, // number of shortcode accesses
	  'start_time' => $time, // time of start of stats
	  'fetch_count' => 0, // count of passage fetches from ESV
	  'today' => $today,
	  'fetches_today' => 0,
	  'max_fetches_per_day' => 0, // max fetches per day
	  'chkresetstats' => false // control to reset stats
		      );
    $opts = array_merge($opts, $defaults);
    return $opts;
  }


  // Records a hit
  // @param bool $fetch True if we are recording a hit that causes a fetch, false if we are returning a cached passage.  Default: false.
  public static function stats_record($fetch=false)
  {
    $opts = self::get_opts();
    $opts['hit_count'] = $opts['hit_count'] + 1;
    if ($fetch)
    {
      $time = current_time('timestamp');
      if (($time - $opts['today']) > DAY_IN_SECONDS)
      {
	// new day
	$opts['today'] = $time - $time % DAY_IN_SECONDS;
	$opts['fetches_today'] = 0;
      } // if new day
      $opts['fetch_count'] = $opts['fetch_count'] + 1;
      $opts['fetches_today'] = $opts['fetches_today'] + 1;
      if ($opts['fetches_today'] > $opts['max_fetches_per_day']) $opts['max_fetches_per_day'] = $opts['fetches_today'];
    } // if $fetch
    self::update_opts( $opts);
  } // stats_record

  // echoes the HTML to display the statistics.
  public static function stats_write()
  {
    $opts = self::get_opts();
?><table>
  <tr><th scope="row">Since</th><td><?php echo date_i18n("m/d/Y H:i T", $opts['start_time']);?></td></tr>
  <tr><th scope="row">Accesses:</th><td><?php echo $opts['hit_count'];?></td></tr>
  <tr><th scope="row">Fetches:</th><td><?php echo $opts['fetch_count'];?></td></tr>
  <tr><th scope="row">Max fetches per day:</th><td><?php echo $opts['max_fetches_per_day'];?></td></tr>
  <?php //<tr><th scope="row">Max cached passage size:</th><td></td></tr>
	?></table>
<?php //echo "currenttime = ".date_i18n("m/d/Y H:i T", current_time("timestamp"));  // debug
  } // stats_write

  // To be passed to register_uninstall_hook.
  public static function uninstall()
  {
    delete_option('esv_shortcode_options');
  } // uninstall

  // for admin_menu action.
  public static function plugin_admin_add_page()
  {
    $page = add_options_page('ESV Bible Shortcode', 'ESV Bible Shortcode', 'manage_options', __FILE__, array('esv_shortcode_class', 'esv_shortcode_options_page'));
    // This inserts inline script code. If we put it in a file change this action to admin_enqueue_scripts.
    add_action('admin_print_scripts', array('esv_shortcode_class', 'add_scripts'));
  } // plugin_admin_add_page

  // Add the admin page script inline
  public static function add_scripts()
  {?><script type="text/javascript">
      // Do processing for terminating an event handler.
      // e - the event object.
      // fCancelBubble - cancel event propagation (bubbling).
      // fCancelDefault - cancel default action
      // returns what the event handler should return.
      function fnFinishEvent(e, fCancelBubble, fCancelDefault)
      {
	if (!window.event)
	{
	  // Firefox

	  if (fCancelDefault && e) e.preventDefault();
	  if (fCancelBubble && e) e.stopPropagation();
	}
	else
	{
	  // IE
	  if (e) e.cancelBubble = fCancelBubble;
	  return !fCancelDefault;
	}   // else
      }   // fnFinishEvent

    function esv_options_onsubmit(e)
    {
      var i;
      var oForm = document.forms[0];
      var oChk = oForm.elements['esv_shortcode_options[chkreset]'];
      i = true; // so we continue by default
      //alert("checked attribute of " + oChk.name + " = " + oChk.checked); // debug
      if (oChk.checked)
      {
	i = confirm("Plugin options will be reset to default values on next plugin activation.");
	if (!i) return fnFinishEvent(e, !i, !i);
      } // if oChk
      var oChk = oForm.elements['esv_shortcode_options[chkresetstats]'];
      if (oChk.checked)
      {
	i = confirm("Statistics will be reset to default values.");
	return fnFinishEvent(e, !i, !i);
      } // if oChk
    } // esv_options_onsubmit
    </script>
<?php
	} // add_scripts

  // Display the admin options page, passed to add_options_page.
  public static function esv_shortcode_options_page()
  {
?> <div class="wrap"><h2>ESV Bible Shortcode plugin v<?php echo self::$version;?></h2>
<?php
    if (isset($_GET['viewreadme']) && $_GET['viewreadme'])
      {
	// Display plugin README file.
	// plugin_path was introduced in WordPress 2.8.
	$readmefn = trailingslashit(dirname(__FILE__))."readme.txt";
	if (function_exists('readme_parser'))
	  {
	    $s = readme_parser(array(), "ESV Bible Shortcode for WordPress");
	    //echo "<p>length of string returned by readme_parser is " . strlen($s) . "</p>";
	    echo $s;
	  }
	  else
	    {
	    // Display unprocessed README.
	    echo "<p>This is the unprocessed README file.</p><pre>";
	    //ob_clean();
	    //flush();
	    //echo "<p>Looking for $readmefn</p>"; // debug
	    $fp = fopen($readmefn, 'r');
	    while (!feof($fp))
	      {
		echo htmlspecialchars(fgets($fp, 1024))."<br/>";
	      } // while not eof
	    fclose($fp);
	    //readfile($readmefn);
	    echo "</pre>";
	    } // else display unprocessed README
      } // view README
    else
      {
    ?><p>Options relating to the ESV Bible Shortcode Plugin.</p><br/>
<?php
    $opts = self::get_opts();
    // echo "<p>opts = " . print_r($opts, true) . "</p>"; // debug
    if (isset($opts['was_reset']) && $opts['was_reset'])
    {
?><p>Options were reset to default values.</p><br/><?php
      $opts['was_reset'] = false;
      // echo "<p>Before saving after clearing was_reset opts = " . print_r($opts, true) ."</p>"; // debug
      // this doesn't work.  We do it in esv_shortcode_options_validate.
      $rtn = self::update_opts( $opts);
      // echo "<p>After saving after clearing was_reset update_option returned $rtn, opts = " . print_r(self::get_opts(), true) ."</p>"; // debug
    } // if reset
							      ?><form action="options.php" method="post" onsubmit="return esv_options_onsubmit(event);">
      <?php settings_fields('esv_shortcode_options'); ?>
      <?php do_settings_sections(__FILE__);
	    ?>   <p><input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" /></p>
    </form>
      <p><a href="http://www.esvapi.org/#conditions" target="_blank">Conditions of use of ESV scripture</a></p>
	<p>Below is a search form to search the ESV Bible.  It does not use the plugin, but it can help you check the syntax of scripture references.</p>
	<form action="<?php self::$apiv2_psg_url?>"
  id="esvsearchform" method="get" target="_blank">
 
  <input type="hidden" name="key" value="<?php
echo $opts['access_key']?>"/>

<label for="esvinput">Search the ESV Bible</label>
 
  <input type="text" name="passage" id="esvinput" size="20" 
  maxlength="255" />
  <input type="submit" name="go" id="esvsearchbutton" 
  value="Search" />
  <br />
  <small>(e.g., <em>
John 1
</em>)</small>
 </form>

			<a href="?page=esv-bible-shortcode-for-wordpress%2Fesv-shortcode.php&viewreadme=true" target="_blank">View the plugin README</a>
			<?php
			} // else show options page
?></div>
	<?php
  } // esv_shortcode_options_page

  // Used in call to register_setting.
  public static function esv_shortcode_options_validate($input)
  {
    // add_settings_error('esv_shortcode_options', 'enter_validate', "<p>enter validate</p>"); // debug
    $options = self::get_opts();
    if (!array_key_exists('access_key', $input) || empty($input['access_key']))
    {
      $input['access_key'] = "";
    } // if access_key
    // Copy these options.
    foreach (array('expire_seconds_limit', 'expire_seconds', 'size_limit', 'access_key', 'chkreset') as $i => $opt)
    {
      $options[$opt] = $input[$opt];
    } // foreach
    $options['was_reset'] = false;
    // If statistics are to be reset, reset them.
    if ($input['chkresetstats'])
    {
      $options = self::stats_defaults($options);
    }
    //add_settings_error('esv_shortcode_options', 'input_settings', "<p>explode passages list text = ".var_export(explode("\n", $input['passages_list_text']), true)."</p>"); // debug
    if (isset($input['passages_list_text']))
    {
      //add_settings_error('esv_shortcode_options', 'got_passage', "<p>validate: got passages_list_text = {$input['passages_list_text']}</p>"); // debug
      $options['passages_list'] = self::process_passages_list($input['passages_list_text']);
    } // passages_list_text
    return $options;
  } // esv_shortcode_options_validate


  // Passed to admin_init action.
  public static function plugin_admin_init()
  {
    register_setting( 'esv_shortcode_options', 'esv_shortcode_options', array('esv_shortcode_class', 'esv_shortcode_options_validate') );
    // Add settings for V1.0.22 if other settings are present.
    $options = self::get_opts();
    if (is_array($options))
    {
      // Make sure expire_seconds_limit and size_limit are in the options.  They might not be there because of updating from v1.0.21 to 1.0.22.  but 1.0.21 didn't have options!
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
      if ($need_update) self::update_opts( $options);
    } // options exists

    // page fields
    add_settings_section('esv_shortcode_main', 'ESV Shortcode Settings', array('esv_shortcode_class', 'settings_section_text'), __FILE__);
    add_settings_field('esv_shortcode_expire_seconds', 'Default expiration time', array('esv_shortcode_class', 'expire_field'), __FILE__, 'esv_shortcode_main', array('label_for' => 'esv_shortcode_expire_seconds'));
    add_settings_field('esv_shortcode_size_limit', 'Cached passage size limit', array('esv_shortcode_class', 'size_limit_field'), __FILE__, 'esv_shortcode_main', array('label_for' => 'esv_shortcode_size_limit'));
    add_settings_field('esv_shortcode_expire_seconds_limit', 'Max cache entry expiration time', array('esv_shortcode_class', 'expire_seconds_limit_field'), __FILE__, 'esv_shortcode_main', array('label_for' => 'esv_shortcode_expire_seconds_limit'));
    add_settings_field('esv_shortcode_access_key', 'Access Key', array('esv_shortcode_class', 'access_key_field'), __FILE__, 'esv_shortcode_main', array('label_for' => 'esv_shortcode_access_key'));
    add_settings_field('esv_shortcode_chkreset', 'Reset options on next activation', array('esv_shortcode_class', 'chkreset_field'), __FILE__, 'esv_shortcode_main', array('label_for' => 'esv_shortcode_chkreset'));
    add_settings_section('esv_shortcode_stats', 'ESV Shortcode Statistics', array('esv_shortcode_class', 'stats_write'), __FILE__);
    add_settings_field('esv_shortcode_chkresetstats', 'Reset statistics', array('esv_shortcode_class', 'chkresetstats_field'), __FILE__, 'esv_shortcode_stats', array('label_for' => 'esv_shortcode_chkresetstats'));
    // passages
    add_settings_section('esv_shortcode_passages', 'ESV Shortcode Passages', array('esv_shortcode_class', 'passages_section_text'), __FILE__);
    add_settings_field('esv_shortcode_passages_list_text', 'Passages List', array('esv_shortcode_class', 'passages_list_field'), __FILE__, 'esv_shortcode_passages', array('label_for' => 'esv_shortcode_passages_list_text'));

  } // plugin_admin_init

  // Writes the Main section prologue.
  public static function settings_section_text()
  {
  } // section_text

  // Functions to echo the HTML for the form fields.
  public static function expire_field($args)
  {
    $options = self::get_opts();
    echo "<input id='esv_shortcode_expire_seconds' name='esv_shortcode_options[expire_seconds]' size='40' type='text' value='{$options['expire_seconds']}' /><br/>(seconds, or end with letter h(ours), m(inutes), d(ays), or w(eeks))";
  } // expire_field

  public static function size_limit_field($args)
  {
    $options = self::get_opts();
    echo "<input id='esv_shortcode_size_limit' name='esv_shortcode_options[size_limit]' size='40' type='text' value='{$options['size_limit']}' /><br>(bytes)";
  } // size_limit_field

  public static function expire_seconds_limit_field($args)
  {
    $options = self::get_opts();
    echo "<input id='esv_shortcode_expire_seconds_limit' name='esv_shortcode_options[expire_seconds_limit]' size='40' type='text' value='{$options['expire_seconds_limit']}' /><br/>(seconds, or end with letter h(ours), m(inutes), d(ays), or w(eeks))";
  } // expire_seconds_limit_field

  public static function access_key_field($args)
  {
    $options = self::get_opts();
    echo "<input id='esv_shortcode_access_key' name='esv_shortcode_options[access_key]' size='40' type='text' value='{$options['access_key']}' /><br>(if empty it must be supplied in each invocation)";
  } // access_key_field

  public static function chkreset_field($args)
  {
    $options = self::get_opts();
    echo "<input id='esv_shortcode_chkreset' name='esv_shortcode_options[chkreset]' type='checkbox' value='1'".checked(1, $options['chkreset'], false)." />";
  } // chkreset_field

  public static function chkresetstats_field($args)
  {
    $options = self::get_opts();
    echo "<input id='esv_shortcode_chkresetstats' name='esv_shortcode_options[chkresetstats]' type='checkbox' value='1'".checked(1, $options['chkresetstats'], false)." />";
  } // chkresetstats_field


  // Display a Settings link on the main Plugins page.  Passed to filter plugin_action_links.


  public static function add_action_links( $links, $file ) {



    if ($file == plugin_basename(__FILE__)) {

      $esv_links = '<a href="'.get_admin_url().'options-general.php?page=esv-bible-shortcode-for-wordpress/esv-shortcode.php">'.__('Settings').'</a>';

      // make the 'Settings' link appear first

      array_unshift( $links, $esv_links );

    }



    return $links;

  } // add_action_links



  // passages
  // Writes the Passages section prologue.
  public static function passages_section_text()
  {
    echo "<p>A passage is described by a line in the following list.  Each line consists of a passage name and its reference separated by whitespace.</p>\n";
    echo "When a syntax error is detected the line is preceeded by # and possibly a message.  Note that these lines will have a numeric \"passage name\".  Leave this in tact if you wish to retain the entry in the list.  Your passage names should not contain only numbers to avoid colliding with these.</p>\n";
    echo "<p>A \"reference\" can be displayed verbatim in place of the scripture text by starting it with " . self::$ref_msg_symbol . ".  The text may contain HTML, and will appear inside whatever container would have held the scripture text.</p>\n";
  } // passages_section_text


  public static function passages_list_field($args)
  {
    $options = self::get_opts();
    // passages_list_text is used only to communicate with the passage list processing function.
    echo "<textarea id='esv_shortcode_passages_list_text' name='esv_shortcode_options[passages_list_text]'>";
    if (!empty($options['passages_list']))
    {
      foreach ($options['passages_list'] as $k => $v) echo "$k $v\n";
    } // if passages
    echo "</textarea>";
  } // passages_list_field

  // @param string $passages_text List of passages, 1 per line.
  // @return array passage list.
  public static function process_passages_list($passages_text)
  {
    $opts = self::get_opts();
    //add_settings_error('esv_shortcode_options', 'enter_process_passages_list', "<p>enter process_passages_list</p>");  // debug
    $old_passages_list = $opts['passages_list'];
    $new_passages_lines = explode("\r\n", $passages_text);
    $a = array();
    foreach ($new_passages_lines as $i => $line)
    {
      $line = trim($line); // remove leading and trailing white space
      if (!$line) continue; // remove empty lines
      $j = preg_match('/^([a-zA-Z0-9_-]+)[ \t]+(.+)$/', $line, $a);
      if (!$j || !isset($a) || count($a) != 3)
      {
	// passage syntax error.  Note that this line will be stored with a numeric key.
	$new_passages_list[] = "#" . $line;
      } // if passage syntax error
      else
      {
	$k = $a[1];
	$v = $a[2];
	$new_passages_list[strtolower($k)] = preg_replace("/  +/", " ", $v);
	// We could move the replace out of the loop and do every replacement by substituting $new_passages_list for $v.
      } // else passage
    } // foreach line
    unset($a);
    $old_refs = array_flip($old_passages_list);  // passage refs are now keys
    // Check new references.
    foreach ($new_passages_list as $key => $val)
    {
      // We don't check if ref starts with # (comment/error) or @ (verbatim message).
      if (!preg_match("/^[" . self::$ref_msg_symbol . "#]/", $val, $a) && !array_key_exists($val, $old_refs))
      {
	// ref_error returns '' for valid references.
	if (($msg=self::ref_error($val)))
	{
	  add_settings_error('esv_shortcode_options', 'ref_error', "Error in passage $key=$val: '$msg'");
	  $new_passages_list[$key] = "# $msg: $val";
	} // error
      } // new ref
      else
      {
	// copy existing passage
	$new_passages_list[$key] = $val;
      } // else copy existing passage
    } // foreach
    //add_settings_error('esv_shortcode_options', 'psglist_rtn', "process_passages_list returning ".print_r($new_passages_list, true)); // debug
    return $new_passages_list;
  } // process_passages_list

  // Check a scripture reference.
  // @parm string $ref reference
  // @parm string access key (default key from options page)
  // @return If okay, returns '', otherwise returns error message
  public static function ref_error($ref, $key='')
  {
    $opts = self::get_opts();
    //add_settings_error('esv_shortcode_options', 'checking_ref', "<p>Checking $ref</p>"); // debug
    if (empty($key)) $key = $opts['access_key'];
    if (empty($key)) return "no access key";
    $url = self::$apiv3_html_url."?q=".urlencode($ref);
    $arrrtn = self::get_response($url, array("Accept: application/json", "Authorization: Token ".$key));
    $resp = $arrrtn['response'];
    $msg .= $arrrtn['msg'];
    /* API V2 code.
    // $resp is MXL containing information about the passage ref.
    // $resp must contain: <query-type>passage</query-type>, if invalid verse ref returns <code>ref-not-exist</code> and <readable>message<br/>...</readable>
    // Can also contain <error>message</error>
    // ?? If error, return error message, else return ""
    // | is delimiter in the following regexps.
    if (preg_match("|<error>(.*?)</error>|", $resp, $a))
    {
      return "Fatal error: {$a[1]}";
    } // if <error>
    elseif (!preg_match("|<query-type>passage|", $resp, $a))
    {
      return "not a passage ref";
    } // if not passage ref
    elseif (preg_match("|<code>ref-not-exist</code>|", $resp, $a))
    {
      return "Nonexistent reference";
    } // if <code>ref-not-exist
// end V2 code
*/
    $json = json_decode($resp, true);
    $response = $json['canonical'];
    if (empty($response))
      {
	$resp_error = true;
	$response = "Request for this reference did not contain content";
	$response .= "[$msg; JSON response: $resp]"; // debug
      }
    Else
      {
	// $response contains something.  We assume it is a valid reference and set it to '' to signal okay.
	$response = '';
      }

    return $response;
  } // ref_error

  //  (Adapted from sput-getverse.php v1.0.0 dated 2/25/14.)
  // process_passage_name-- Expand formatting characters in a passage name.

  // @param string $sName -- string to be formatted.

  // @return string The expanded name.
  // Note that we use extract() on the array returned by mktime, so a local variable is created for each of its keys, so be careful of variables that start with $tm_.

  public static function process_passage_name($sName)
  {

    //$this->msg("process_passage_name: \$sName = $sName");   // debug

    $s = $sName;

    // Parse the name, separating the output format and the code (or expression) containing the date.

    $i = preg_match('/^([^(]*)\(([^)]*)\)$/', $s, $a);

    //$this->msg("process_passage_name: after match to separate format and time \$i = $i, \$a = " . print_r($a, true) . "");   // debug

    $sTm = '';
    if (!$i || !isset($a))

    {

      //$this->msg("process_passage_name: Could not separate format and time specifier, assume no time.");   // debug

      $sFmt = $s;

    }   // no time

    else

    {

      // We have format and "time".

      $sFmt = $a[1];

      $sTm = $a[2];

      //$this->msg("  \$sFmt = $sFmt, \$sTm = $sTm");   // debug

    }   // else format and time

    // Get the necessary time stamp.

    $iTime = self::make_passage_timestamp($sTm);

    // Format the time stamp.

    $s = strfTime($sFmt, $iTime);

    //$this->msg("process_passage_name: returning $s.");   // debug

    return $s;

  }   // Process_passage_name

  // strip_passage_datecodes-- remove formatting characters from a passage name.

  // @param string $sName -- string to be stripped.

  // @return string The stripped name.
  public static function strip_passage_datecodes($sName)
  {
    //$msg = ''; // debug
    $s = $sName;
    $i = strpos($s, "(");
    if ($i !== FALSE)
      {
	//$msg .= "paren position is " . print_r($i, true) . "\n"; // debug
	if ($i == 0)
	  {
	    // The left paren is at the start of the string, return empty string.
	    //$msg .= "paren at 0, returning empty string\n"; // debug
	    return "";
	    //return array('msg' => $msg, 'rtn' => ""); // debug
	  }
	$s = substr($s, 0, $i); // remove paren and everything after it.
      } // if found a paren
    //$msg .= "Rpplacing on '$s'\n"; // debug
    $s = preg_replace("/%./", "", $s);
    //$msg .= "Returning '$s'"; // debug
    return $s;
    //return array('msg' => $msg, 'rtn' => $s); // debug
  } // strip_passage_datecodes

  // Make a timestamp for use in expanding passage names.
  // @param string $sTm the "date" (i.e. %w,yyyymmdd) spec used in the passage name.
  // @return int timestamp value
  public static function make_passage_timestamp($sTm='')
  {
    $iTime = current_time('timestamp');   // current time as a UNIX time stamp.

    if (isset($sTm) && $sTm)

    {

      // 1=code 2=offset, datestamp: (3=year 4=month 5=day)

      $i = preg_match('/^(?:(%[Ww])(\d)?)?(?:,\s*(\d{4})(\d{2})(\d{2}))?$/', $sTm, $a);

      if ($i && isset($a))

      {

	// We skip 0 (full match).

	$sCode = $a[1];
	$sNum = isset($a[2])?$a[2]:'0';
	if (count($a) > 3)
	{
	  list(, , , $sTSYr, $sTSMo, $sTSDay) = $a;
	} // if date

	//$this->msg("  \$sCode = $sCode, \$sNum = $sNum, \$sTSYr = $sTSYr, \$sTSMo = $sTSMo, \$sTSDay = $sTSDay");   // debug

	if (!empty($sTSYr) && !empty($sTSMo) && !empty($sTSDay))

	{

	  // There is a date specified to be used in place of the current date.  This feature is intended for testing and documentation example generation.

	  $iTime = mktime(0, 0, 0, $sTSMo, $sTSDay, $sTSYr);

	}   // if datestamp

	else unset($sTSYr);   // so we can just test $sTSYr to know if there is a alternate datemsg("Using " . $sTSYr?"Alternate ":"" . "date " . strftime("%Y%m%d", $iTime));   // debug

	if ($sCode)

	{

	  // Last (%w) or next (%W) Sunday.

	  $sWeekDay = strftime("%w", $iTime);   // %w = day of week (0-6, 0=sun)

	  //$this->msg("\$sWeekDay = $sWeekDay");   // debug




	  $sNum = $sNum?$sNum:0;
	  $iDayOffset = - $sWeekDay + $sNum;
	  if ($sCode == "%W") $iDayOffset += 7;


	  // $iDayOffset = day offset to last (%w) or next (%W) Sunday.

	  $iTime += $iDayOffset * DAY_IN_SECONDS;
	}  // if %w or %W

      }   // "time" spec


    }   // if $sTm
    return $iTime;
  } // make_passage_timestamp

  // Returns the first defined passage spec from a passage attribute.
  // @param string $passage The contents of the passage attribute, which contains one or more passage specs separated by self::$psg_spec_sep.
  // @returns array('psg_name', 'msg') where 'psg_name' is a string The first defined passage spec, or '', and 'msg' is debugging messages.
  // Each passage spec is checked with date codes expanded.  If a passage is still not found, each passage spec is then checked with date codes removed.
  public static function get_passage_name($passage)
  {
    $passages_list = self::get_opts()['passages_list'];
    $msg = ''; // debug
    $psg_name = '';
    $a = explode(self::$psg_spec_sep, $passage);
    foreach ($a as $k)
      {
	$s = strtolower(self::process_passage_name($k));
	// don't think $s can be empty here.
	if (isset($passages_list[$s]))
	  {
	    $psg_name = $s;
	    break;
	  } // if $s
      } // foreach $a
    $msg .= "\$passage='$passage', \$psg_name='$psg_name'\n"; // debug
    if (empty($psg_name))
      {
	$msg .= "$psg_name not set"; // debug
	foreach ($a as $k)
	  {
	    $s = self::strip_passage_datecodes($k);
	    // debugging: returns an array containing debugging messages and result for debugging
	    //$arrrtn = self::strip_passage_datecodes($k);
	    //$s = $arrrtn['rtn'];
	    //$msg .= $arrrtn['msg'];
	    $msg .= ", trying with datecodes removed: '$s'\n"; // debug
	    if (isset($passages_list[$s]))
	      {
		$psg_name = $s;
		break;
	      }
	  } // foreach
      } // if empty($psg_name)
    return array('psg_name' => $psg_name, 'msg' => $msg);;
  } // get_passage_name




  // Fetch a response from a remote server.
  // @param $url string Complete URL to fetch.
  // @returns string Response text.
  public static function get_response($url, $headers='')
  {
    $msg = '[get_response for ' . $url . ' : '; // debug
    $ch = curl_init($url); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if (!empty($headers))
      {
	$msg .= " adding headers " . print_r($headers, true). "\n"; // debug
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      }
    $response = curl_exec($ch);
    if (curl_errno($ch)) // debug
      {  // debug
	$msg .= "CURL error: " . curl_error($ch) . "\n"; // debug
      } // debug error
    $msg .= "response code: " . curl_getinfo($ch, CURLINFO_RESPONSE_CODE) . "\n"; // debug
    curl_close($ch);
    //return $response;
    return array('msg' => $msg, 'response' => $response);
  } // get_response


  // Convert the contents of an expire seconds field to seconds.
  // @param string $expire
  // @return string containing $expire multiplied by the appropriate constant if it ends in a multiplier character.
  // If $expire does not end with one of the multiplier characters, $expire is returned without modification.
  public static function expire_to_seconds($expire)
  {
    $multchar = substr($expire, -1);
    if (array_key_exists($multchar, self::$aMults))
    {
      $expire *= self::$aMults[$multchar];
    } // if multchar
    return $expire;
  } // expire_to_seconds

  // Shortcode: [esv scripture="John 3:16-23"]
  // @param array $atts
  // @return string containing the scripture as HTML.
  public static function esv($atts)
  {
    $opts = self::get_opts();
    extract( shortcode_atts( array(
				   'key' => '',
				   'scripture'	    			 		=>	'John 3:16',
				   'passage'	    			 		=>	'',
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
    //$msg .= "expire_seconds = $expire_seconds, expire_to_seconds(expire_seconds)=".self::expire_to_seconds($expire_seconds); // debug
    $expire_seconds = self::expire_to_seconds($expire_seconds);



    $expire_seconds_limit = array_key_exists('expire_seconds_limit', $opts)?self::expire_to_seconds($opts['expire_seconds_limit']):null;
    if ($expire_seconds_limit)
      {
	if ($expire_seconds > $expire_seconds_limit) $expire_seconds = $expire_seconds_limit;
      } // if $expire_seconds_limit
    $psg_name = '';
    //foreach (array("lec%b", "%b", "%bns") as $k => $v) $msg .= "strfTime($v)='".strfTime($v)."'\n"; // debug
    if ($passage)
      {
	//ASSERT: $psg_name == ''
	$arrRtn = self::get_passage_name($passage);
	$psg_name = $arrRtn['psg_name'];
	$msg .= $arrRtn['msg'];
	unset($arrRtn);
      } // if $passage
    if (!empty($psg_name) && isset($opts['passages_list'][$psg_name]))
      {
	$msg .= "Trying to use passage=$psg_name\n"; // debug
	// Check to see that the associated reference isn't a comment, if it is use $scripture instead.
	$tmp = $opts['passages_list'][$psg_name];
	$ref = !preg_match('/^\s*#/', $tmp)?
	  $tmp : $scripture;
	$msg .= "Using $ref\n"; // debug
      } // if $passage
    else
      {
	$msg .= "Using scripture attribute: $scripture\n"; // debug
	$ref = $scripture;
      } // else no passage name
    // If the "reference" isn't a verbatim text message, get the text of the reference.
    if (preg_match("/^" . self::$ref_msg_symbol . "/", $ref, $a))
      {
	// verbatim text
	$response = substr($ref, 1);
      }
    Else
      {
	$ref = urlencode($ref);
	if ($output_format == "html")
	  {
	    $url_prefix = self::$apiv3_html_url;
	  }
	else
	  {
	    $url_prefix = self::$apiv3_text_url;
	  }
	$options = "include-passage-references=".$include_passage_references."&include-first-verse-numbers=".$include_first_verse_numbers."&include-verse-numbers=".$include_verse_numbers."&include-footnotes=".$include_footnotes."&include-footnote-links=".$include_footnote_links."&include-headings=".$include_headings."&include-subheadings=".$include_subheadings."&include-surrounding-chapters=".$include_surrounding_chapters."&include-word-ids=".$include_word_ids."&link-url=".$link_url."&include-audio-link=".$include_audio_link."&audio-format=".$audio_format."&audio-version=".$audio_version."&include-short-copyright=".$include_short_copyright."&include-copyright=".$include_copyright."&include-passage-horizontal-lines=".$include_passage_horizontal_lines."&include-heading-horizontal-lines=".$include_passage_horizontal_lines;
	if (empty($key) && isset($opts['access_key'])) $key = $opts['access_key'];
	if (empty($key)) return "no access key";
	$url = $url_prefix."?q=".$ref."&".$options;
	$hash = "esv" . md5($output_format."&".$ref."&".$options);
	$msg .= "Trying for cache entry $hash"; // debug
	$response = get_transient($hash);
	if (!$expire_seconds || !$response)
	  {
	    // fetch passage from server
	    $msg .= ", not cached, fetching, expire_seconds=$expire_seconds, expire_seconds_limit=$expire_seconds_limit, url=$url"; // debug
	    $arrrtn = self::get_response($url, array("Accept: application/json", "Authorization: Token ".$key));
	    $resp = $arrrtn['response'];
	    $msg .= $arrrtn['msg'];
	    $msg .= "\nresp=$resp\n"; // debug
	    // ?? response is JSON, need to pull the text out and check for errors.
	    $json = json_decode($resp, true); // true makes elements to be returned as items of an associative array
	    $msg .= "json = " . print_r($json, true) . "\n"; // debug
	    $response = implode("\n", $json['passages']); // only first passage of a multi-passage reference
	    if ($expire_seconds && (!$size_limit || strlen($response) < $size_limit))
	      {
		$msg .= ", fetched, ". strlen($response)." bytes cached as $hash for $expire_seconds seconds"; // debug
		set_transient($hash, $response, $expire_seconds);
	      } // cache
	    else // debug
	      { // debug
		$msg .= ", not cached"; // debug
	      } // debug
	    self::stats_record(true); // fetched
	  } // fetch passage

	else self::stats_record(false); // from cache
	if ($remove)
	  {
	    $fRtn = delete_transient($hash);
	    $msg .= $fRtn?", removed":", remove failed"; // debug

	  } // remove
      } // real reference
    //Display the Title as a link to the Post's permalink.
    //return (($container?"<".$container. ($class?" class=\"" . $class:'') . "\">":'') . $response . ($container?"</".$container.">":'');
    return ($debug?nl2br("[$msg]"):'') . ($container?"<".$container. ($class?" class=\"" . $class:'') . "\">":'') . $response . ($container?"</".$container.">":'');
  } // esv

  // Shortcode: [esv_date]
  // Performs the same date format code substitution on the enclosed content as is done on the passage name.
  // usage: insert a shortcode like [esv_date "%w,20140705"]The passage is for %b %d[/esv_date] in your page.
  public static function esv_date($atts, $content=null)
  {
    extract( shortcode_atts( array(
				   'date'	    			 		=>	''				   ), $atts ) );
    if (!is_null($content)) return strfTime($content, self::make_passage_timestamp($date));
  } // esv_date

  // Shortcode: [esv_ref passage="passagename"]
  // @param array $atts
  // Returns only the reference as a string.  It is not wrapped with any HTML.  (Newlines in debug messages will be converted to <br/>.)
  // If the query returns an error, a message will be returned.
  // @param $attr array can contain the following options (same as for function esv): scripture, passage, expire_seconds, size_limit, debug, remove.
  public static function esv_ref($atts)
  {
    //return ("{not implemented for API V3}");
    $opts = self::get_opts();
    extract( shortcode_atts( array(
				   'key' => '',				   
'scripture'	    			 		=>	'John 3:16',
				   'passage'	    			 		=>	'',
				   'expire_seconds' => $opts['expire_seconds'],
				   'size_limit' => $opts['size_limit'],
				   'debug' => false,
				   'remove' => false
				   ), $atts ) );
    if ($remove == 'false') $remove = false;
    if ($debug == 'false') $debug = false;
    $msg = ""; // debug
    // Handle expiration time multipliers
    //$msg .= "expire_seconds = $expire_seconds, expire_to_seconds(expire_seconds)=".self::expire_to_seconds($expire_seconds); // debug
    $expire_seconds = self::expire_to_seconds($expire_seconds);
    $expire_seconds_limit = array_key_exists('expire_seconds_limit', $opts)?self::expire_to_seconds($opts['expire_seconds_limit']):null;
    if ($expire_seconds_limit)
      {
	if ($expire_seconds > $expire_seconds_limit) $expire_seconds = $expire_seconds_limit;
      } // if $expire_seconds_limit
    if (empty($key) && isset($opts['access_key'])) $key = $opts['access_key'];
    if (empty($key)) return "no access key";
    $psg_name = '';
    //foreach (array("lec%b", "%b", "%bns") as $k => $v) $msg .= "strfTime($v)='".strfTime($v)."'\n"; // debug
    if ($passage)
      {
	$arrRtn = self::get_passage_name($passage);
	$psg_name = $arrRtn['psg_name'];
	$msg .= $arrRtn['msg'];
	unset($arrRtn);
      } // if $passage
    $msg .= "\$passage='$passage', \$psg_name='$psg_name'\n"; // debug
    if (!empty($psg_name) && isset($opts['passages_list'][$psg_name]))
      {
	$msg .= "Trying to use passage=$psg_name\n"; // debug
	$tmp = $opts['passages_list'][$psg_name];
	$ref = !preg_match('/^\s*#/', $tmp)?
	  $tmp : $scripture;
	$msg .= "Using $ref\n"; // debug
      } // if $passage
    else
      {
	$msg .= "Using scripture attribute: $scripture\n"; // debug
	$ref = $scripture;
      } // else no passage name
    // If the "reference" isn't a verbatim text message, check the reference.
    if (preg_match("/^" . self::$ref_msg_symbol . "/", $ref, $a))
      {
	// verbatim text
	$response = substr($ref, 1);
      }
    Else
      {
	$ref = urlencode($ref);
	$url = self::$apiv3_html_url."?q=".$ref;
	$hash = "esvr" . md5($ref);
	$msg .= "Trying for cache entry $hash"; // debug
	$response = get_transient($hash);
	$resp_error = false;
	if (!$expire_seconds || !$response)
	  {
	    // fetch passage from server
	    $msg .= ", not cached, fetching, expire_seconds=$expire_seconds, expire_seconds_limit=$expire_seconds_limit"; // debug
	    $arrrtn = self::get_response($url, array("Accept: application/json", "Authorization: Token ".$key));
	    $resp = $arrrtn['response'];
	    $msg .= $arrrtn['msg'];
	    /* The following code was for API V2.
	    // $resp is MXL containing information about the passage ref.
	    // $resp must contain: <query-type>passage</query-type>, if invalid verse ref returns <code>ref-not-exist</code> and <readable>message<br/>...</readable>
	    // Can also contain <error>message</error>
	    if (preg_match("|<error>(.*?)</error>|", $resp, $a))
	      {
		$resp_error = true;
		$response = "Fatal error: {$a[1]}";
	      } // if <error>
	    elseif (!preg_match("|<query-type>passage|", $resp, $a))
	      {
		$resp_error = true;
		$response = "not a passage ref";
	      } // if not passage ref
	    elseif (preg_match("|<code>ref-not-exist</code>|", $resp, $a))
	      {
		$resp_error = true;
		$response = "Nonexistent reference";
	      } // if <code>ref-not-exist
	    else
	      {
		// No elements that indicate an error
		if (preg_match("|<readable>(.*?)</readable>|", $resp, $a))
		  {
		    $response = $a[1]; // valid
		  }
		else
		  {
		    $resp_error = true;
		    $response = "Response did not contain a reference";
		  } 
	      } // else no elements that indicate an error
// End API V2 code
*/
	    $json = json_decode($resp, true);
	    $response = $json['canonical'];
	    if (empty($response))
	      {
		$resp_error = true;
		$response = "Response did not contain a reference";
	      }
	    if (!$resp_error && $expire_seconds && (!$size_limit || strlen($response) < $size_limit))
	      {
		$msg .= ", fetched, ". strlen($response)." bytes cached as $hash for $expire_seconds seconds"; // debug
		set_transient($hash, $response, $expire_seconds);
	      } // cache
	    else // debug
	      { // debug
		$msg .= ", not cached"; // debug
		if ($resp_err) $msg .= ", query returned error"; // debug
	      } // debug
	    self::stats_record(true); // fetched
	  } // fetch passage

	else self::stats_record(false); // from cache
	if ($remove)
	  {
	    $fRtn = delete_transient($hash);
	    $msg .= $fRtn?", removed":", remove failed"; // debug

	  } // remove

	if ($resp_error)
	  {
	    $msg .= ", error fetching scripture reference: " . $response; // debug
	    $response = "Error fetching scripture reference: " . $response;
	  }
      } // real reference
    return ($debug?nl2br("[$msg]"):'') . $response;
  } // esv_ref

  // Shortcode: [esv_ifpassage not='false'] content [/esv_ifpassage]
  // Return  enclosed content if a reference is found for the passage name.
  // 
  // If the passage name after date expansion has an entry in the
  // passage list with a non-blank value, the enclosed content is
  // returned.  Otherwise an empty string is returned.  A leading @ is
  // trimmed, so an empty verbatim text is considered empty.
  // Shortcodes are expanded in $content.  If not=true, the sense of
  // the test is reversed, so content is returned if the passage name
  // is not defined.  the text of the passage is not fetched, so an
  // invalid reference is not detected.
  // @param array $atts
  // @param string $content
  // @returns string containing $content or ''.
  // Example: [esv_ifpassage passage=%bns]<p>The gospel in a nutshell
  // passage for This month:</p>[esv
  // passage="%bns"][/esv_ifpassage][esv_ifpassage passage='%bns'
  // not=true]<p>There is no nutshell passage for this
  // month.</p>[/esv_ifpassage]
  public static function esv_ifpassage($atts, $content = null)
  {
    $opts = self::get_opts();
    extract( shortcode_atts( array(
				   'passage'	    			 		=>	'',
				   'not'			=>	'false',
				   'debug' => false,
				   ), $atts ) );
    if ($not == 'false') $not = false;
    if ($debug == 'false') $debug = false;
    $msg = ""; // debug
    $psg_name = '';
    //foreach (array("lec%b", "%b", "%bns") as $k => $v) $msg .= "strfTime($v)='".strfTime($v)."'\n"; // debug
    if ($passage)
      {
	//ASSERT: $psg_name == ''
	$arrRtn = self::get_passage_name($passage);
	$psg_name = $arrRtn['psg_name'];
	$msg .= $arrRtn['msg'];
	unset($arrRtn);
      } // if $passage
    if (!empty($psg_name) && isset($opts['passages_list'][$psg_name]))
      {
	$msg .= "Trying to use passage=$psg_name\n"; // debug
	// Check to see that the associated reference isn't a comment.
	$tmp = $opts['passages_list'][$psg_name];
	$ref = !preg_match('/^\s#/', $tmp)?
	  $tmp : '';
	$msg .= "Using $ref\n"; // debug
      } // if $passage_name
    else
      {
	$msg .= "no passage ref\n"; // debug
	$ref = '';
      } // else no passage name
    // If the "reference" isn't a verbatim text message, we have one.
    if (preg_match("/^" . self::$ref_msg_symbol . "/", $ref, $a))
      {
	// verbatim text
	$response = substr($ref, 1);
	$msg .= "Got verbatim text $response\n"; // debug
      }
    else
      {
	$response = $ref;
      }
    $keep_content = !empty(trim($response));
    $keep_content = $not? !$keep_content: $keep_content;
    $msg .= "Final keep_content = $keep_content\n"; // debug
    if ($keep_content)
      {
	$msg .= "Displaying content $content\n"; // debug
	return (($debug?nl2br("[$msg]"):'') . do_shortcode($content));
      } // if response
    else
      {
	return ($debug?nl2br("[$msg-- content skipped]"):'');
      } // else empty response
  } // esv_ifpassage

  // after class.
} // esv_shortcode_class

register_activation_hook(__file__, array('esv_shortcode_class', 'add_defaults'));
register_uninstall_hook(__FILE__, array('esv_shortcode_class', 'uninstall'));
add_action('admin_menu', array('esv_shortcode_class', 'plugin_admin_add_page'));
add_action('admin_init', array('esv_shortcode_class', 'plugin_admin_init'));
// Add settings link to plugins page.
add_filter( 'plugin_action_links', array('esv_shortcode_class', 'add_action_links'), 10, 2 );

add_shortcode('esv', array('esv_shortcode_class', 'esv'));
add_shortcode('esv_date', array('esv_shortcode_class', 'esv_date'));
add_shortcode('esv_ref', array('esv_shortcode_class', 'esv_ref'));
add_shortcode('esv_ifpassage', array('esv_shortcode_class', 'esv_ifpassage'));

  ?>
