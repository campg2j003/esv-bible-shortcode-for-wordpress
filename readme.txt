=== ESV Bible Shortcode for WordPress ===
Plugin URI: http://wordpress.org/extend/plugins/esv-bible-shortcode-for-wordpress/
Author URI: http://calebzahnd.com
Description: This plugin uses the ESV Bible Web Service API to provide an easy way to display scripture in the ESV translation using WordPress shortcodes.  Passages can be cached on the local server as transients.  It provides  a options page where the administrator can set the default expiration time, maximum expiration time, and maximum size of a cached passage, and also shows access statistics.
Author: Caleb Zahnd
Contributors: calebzahnd, campg2003
Tags: shortcode, Bible, church, English Standard Version, scripture
Version: 1.0.23
Requires at least: 2.7
Tested up to: 3.9.1
Stable tag: 1.0.2

This plugin uses the ESV Bible Web Service API to provide an easy way to display scripture in the ESV translation using WordPress shortcodes.

== Description ==

This plugin uses the ESV Bible Web Service API to provide an easy way to display scripture in the ESV translation on a WordPress installation. Using WordPress shortcodes, you can quickly display lengthy passages or single verses in your WordPress posts.  You can also have the passages cached using the WordPress Transients API.  The hash tag under which the entry is cached is formed by "ESV" followed by the result of applying md5() to the URL used to fetch the passage.

The plugin provides an options page where the default expiration time, maximum expiration time, and the maximum cached passage size can be set.  (Passages larger than the maximum size are displayed but not cached.)  The options page also shows the number of passage accesses, the number of passage fetches, and the maximum number of fetches per day.

On the options page you can also define a list of passage names and their associated scripture references.  You can then reference the passage by its name using the 'passage' attribute in place of the 'scripture' attribute.  The 'passage' attribute expands strfTime codes so it is possible to have passage names like junscripture or 0529selection and automatically reference them.  This expansion facility has the ability to specify that the date that should be used is the previous or next Sunday, so in 2014 you could define a passage named jun29passage.  Then if you use a shortcode like

[esv passage='%b%dpassage(%W)']

it would be displayed during the previous week.



== Installation ==

The plugin is simple to install:

1. Download 'esv-bible-shortcode-for-wordpress.zip'
2. Unzip
3. Upload 'esv-shortcode' directory to your '/wp-content/plugins' directory
4. Go to the plugin management page and activate the plugin
5. (optional) Go to the ESV Bible Shortcode page and change any default values as desired.
6. Done!

If you are upgrading from v1.0.21 (caching without admin page), note that the limit option has changed to size_limit.

The options page allows you to set the default and maximum caching period, and the maximum size (bytes) passage that can be cached.  It also has a checkbox that causes the plugin's options to be reset to their installation defaults the next time the plugin is activated.

To uninstall, go to the Plugin Management page and deactivate, then delete the plugin.  This will remove the plugin's options (including passages list) from the database and its files from the plugins directory.  It does not remove any passages that are still cached.

== Usage ==

The simplest usage of the plugin is to insert the '[esv]' shortcode into your page or post, using the attributes listed below. These attributes pretty much mirror those on the ESV API.

[esv scripture='John 3:16-21']

If you have defined one or more passage names on the options page, you can also use:

[esv passage='passagename']

If 'passagename' is defined as 'john 3:16-21', the output would be the same as the above.

* NOTE: For reasons that should be obvious, either the scripture or passage attribute is required, (and is the only required attribute.)


**Optional Attributes:**

'container' // Default: 'span'.
The html tag to wrap your scripture in.

'class' // Default: 'esv-scripture'.
The css class to assign to the html container tag. Aids in adding custom CSS to your scripture.

'include_passage_references' // Default: 'true;
Boolean value to include `<h2>` tags that indicate which passage is being displayed. For example: Isaiah 53:1-5.

'include_first_verse_numbers' // Default: 'false'
Boolean value to show the verse number (e.g., 53:1) at the beginnings of chapters. 

'include_verse_numbers' // Default: 'true'
Boolean value to show verse numbers in the text.

'include_footnotes' // Default: 'false'
Include footnotes and references to them in the text.

'include_footnote_links' // Default: 'false'
If you have set include-footnotes to true, set this option to false to turn off the links to the footnotes within the text. The footnotes will still appear at the bottom of the passage. If include-footnotes is false, this parameter does not do anything.

'include_headings' // Default: 'false'
Include section headings. For example, the section heading of Matthew 5 is The Sermon on the Mount.

'include_subheadings' // Default: 'false'
Include subheadings. Subheadings are the titles of psalms (e.g., Psalm 73's A Maskil of Asaph), the acrostic divisions in Psalm 119, the speakers in Song of Solomon, and the textual notes that appear in John 7 and Mark 16.

'include_surrounding_chapters' // Default: 'false'
Show links under the reference to the previous, current (if not showing the whole chapter) and next chapters in the Bible. The link points to the ESV website, but you can modify it by changing the link-url parameter.

'include_word_ids' // Default: 'false'
Include a <span> tags surrounding each word with a unique id. The id has several parts; the id "w40001002.01-1" consists of: the letter w (needed for valid XHTML ids), an eight-digit verse identifier (40001002 indicates Matthew 1:2), a period (.), a two-digit word identifier (01), and a hyphen followed by a number (this number is incremented with each passage; it starts with 1). Footnotes do not have word ids.

'link_url' // Default: 'http://www.gnpcb.org/esv/search/'
Where embedded links to other passages should point. It currently applies only when include-surrounding-chapters is set to true.

'include_audio_link' // Default: 'false'
Include a link to the audio version of the requested passage. The link appears in a `<small>` tag in the passage's identifying `<h2>`.

'audio_format' // Default: 'flash'
Takes a value of flash, mp3, real, or wma to indicate the format of the audio. It defaults to flash, but the default could change; if you have a strong preference for one of these formats, we recommend that you specify it explicitly. We recommend flash as the most flexible; an embedded Flash player is included in the text. When the audio-version is hw, the mp3 option includes a link to an MP3 file.

'audio_version' // Default: 'hw'
Which recording to use. The options are: hw (David Cochran Heath [Hear the Word], complete Bible) mm (Max McLean, complete Bible), ml (Marquis Laughlin, New Testament only), ml-mm (David Cochran Heath for Old Testament, Marquis Laughlin for New Testament), and ml-mm (Max McLean for Old Testament, Marquis Laughlin for New Testament). Only affects the output if audio-format is flash or mp3. (David Cochran Heath and Max McLean's versions are only available in these two formats.)

'include_short_copyright // Default: 'false'
Each passage from the ESV needs to include the letters "ESV" at the end of the passage. To turn off this behavior, set this option to fals

'include_copyright' // Default: 'false'
Show a copyright notice at the bottom of the text. Any page that shows the ESV text from this service needs to include a copyright notice, but you do not need to include it with each passage. Best practice is probably to include the copyright manually on your page, rather than download it every time. This option is mutually exclusive with include-short-copyright, which overrides include-copyright.

'output_format' // Default: 'html'
The format to output. Options are 'html', 'plain-text', and 'xml'

'include_passage_horizontal_lines' // Default: 'false'
Applicable only when outputting plain-text. Include a line of equals signs (===) above the beginning of each passage.

'include_heading_horizontal_lines // Default: 'false'
Applicable only when outputting plain-text. Include a line of underscores (___) above each section heading.

'expire_seconds' // Default: set on options page, initial default 1 week.
The number of seconds until the cached entry expires.  The number can be suffixed with s (does nothing), m, h, d, or w for seconds, minutes, hours, days, and weeks, respectively.  If set to 0, the passage is not cached.

'size_limit' // Default: set on options page, initial default 0, no limit.
The maximum number of bytes in a cached passage.  Passages larger than this are processed but not cached.  0=no limit.

'remove' // Default: false.
If "true", the specified passage is removed from the cache.
The key for the passage is generated from the specified URL, so the scripture reference, and most of the other options determine the key.  So if a passage was fetched as "John 3:16-21", then specifying  "John 3:16-20,21" will not remove it.

'debug' // Default: false.
If 'true', a message is included in the displayed text giving information about how the request was processed.

== Using the Passage Name Facility ==

On the options page there is an edit box in which you can define one or more passage names and their references.  You can then reference the passage by name using the 'passage' attribute instead of the 'scripture' attribute.  Each passage definition appears on its own line.  The name is at the beginning of the line and is followed by one or more spaces or tabs and the scripture reference.  This format allows you to paste a passage list prepared in a text editor into the edit box.  Passage names are composed of letters, numbers, hyphen, or underline, but names are converted to lower case.  Multiple spaces in the reference are replaced by a single SPACE character.  When Save is pressed, scriptures not already in the list are sent to the server and checked for accuracy.  If the server reports an error, a "#" and a message are inserted before the reference.  Such references are ignored by the 'passage' attribute.  An error message is also printed on the options page.  Note that if the reference "ps 1" is in the list and you add "psalm 1", it will be treated as a new reference and checked, as will "ps1 ps3".

The name specified in the 'passage' attribute can contain strfTime formatting codes to substitute components of the date.  It can also contain a date specifier which allows you to specify a particular day in the week of the date.  The format of the name is:

&lt;<var>format</var>&gt;[(&lt;<var>date</var>&gt;)]
where
&lt;<var>format</var>&gt; is a string containing letters, numbers, hyphen, and underline characters that form the name.  strfTime formatting codes in &lt;<var>format</var>&gt; are replaced with the appropriate date component.  &lt;<var>format</var>&gt; is optionally followed by &lt;<var>date</var>&gt; in parentheses.  &lt;<var>date</var>&gt; is of the form:

[&lt;<var>modifier</var>&gt;[&lt;<var>offset</var>&gt;]][,&lt;<var>datespec</var>&gt;]

&lt;<var>modifier</var>&gt; is %w or %W.  %w stands for the date of the previous Sunday, %W stands for the date of the next Sunday, and if specified &lt;<var>offset</var>&gt; is a one-digit weekday offset, so %w1 would be the date of the previous Monday.  &lt;<var>datespec</var>&gt; is for testing and is a date in the form YYYYMMDD.  The &lt;<var>name</var>&gt; is processed as follows:

1. If &lt;<var>datespec</var>&gt; is specified, it is converted to a timestamp, otherwise the current timestamp is used.
1. If a modifier is specified, the date of the previous or next Sunday is substituted for the timestamp.
1. If &lt;<var>offset</var>&gt; is specified, that many days are added to the timestamp.
1. The timestamp is then processed by strfTime with &lt;<var>format</var>&gt;.
1. The resulting name is converted to lower case and used to look up the reference for the passage.

Note that the (&lt;<var>date</var>&gt;) must be at the end of the name.

It has been tested with these strings: 

* test%m%d(%w) and test%m%d(%W), which produce names like "test1227" and "test0103" for last and next Sunday, respectively, which could be used to access passages that change weekly
* test%m, which produces a name like "test12" or "test01" that could be used for passages that change each month
* test%m%d(%w2) and test%m%d(%W2) which verify that the day offset works.
* test%bns(%w) which produces a name like testjunns

**Useful strfTime formatting characters**:

<table>
<tr><td>%b</td><td>Abbreviated month name</td></tr>
<tr><td>%B</td><td>Full month name</td></tr>
<tr><td>%m</td><td>2-digit month number (01-12)</td></tr>
<tr><td>%d</td><td>2-digit day of the month (01-31)</td></tr>
<tr><td>%a</td><td>Abbreviated day of the week</td></tr>
<tr><td>%A</td><td>Full day of the week</td></tr>
</table>

**Examples**

We use the Lectionary readings in our worship service.  We also have a "Gospel in a Nutshell" passage for each month.  We produce a spreadsheet with columns for the four lectionary readings and the nutshell passage.  I wrote a Python program that converts the tab-delimited form of this spreadsheet to a file with lines like:

    lecjul06ps Zec 9:9-12               Ps 145:8-14  
    lecjul06ot Gen 24:34-49, 58-67    Ps 45:10-17  
    lecjul06nt Rom 7:15-25a  
    lec06gs Mt 11:16-19, 25-30  
    julns Rom 3:21-26

(Yes, I know Zechariah isn't a psalm :-).)

Shortcodes like

    [esv passage='lec%b%dps']  
    [esv passage='lec%b%dot']  
    [esv passage='lec%b%dnt']  
    [esv passage='lec%b%dgs']  
    [esv passage='%bns']

will display the appropriate passages.

== Changelog ==

= 1.0.23 =

* Added a passages list section on the options page.
* Added the 'passage' attribute.
* Some error messages are now written when saving the options.

= 1.0.22 =
* Encapsulated functions in a class.  
* Added an options page.
* Changed min WordPress version to 2.7.
* Added a version constant.
* Added expire_seconds_limit option.  It can have the same letter suffixes as expire_seconds.
* A checkbox on the options page causes options to be set to "factory" defaults on the next plugin activation.
* Added validate function, validates expire_seconds , expire_seconds_limit, and size_limit.
* Wrote static method expire_to_seconds to convert the expire fields into seconds.
* If the 'debug' and 'remove' options are set to the string 'false', they are set to false.
* Refactored expire_seconds_limit code in esv_shortcode_class::esv.
* Moved code to fetch the passage into static method get_response.
* Added Settings link on the plugins page.
* Added statistics display on options page.
* Added Conditions of Use link on options page.


= 1.0.21 =
* Added option to cache passages.  uses the transients API, uses "esv" . md5(URL that fetches the passage).

* If container or class is set to empty the element or attribute is not added.

* Added option expire_seconds to specify the number of seconds to cache the passages.  Its value can be suffixed with "m", "h", "d", or "w" for minutes, hours, days, or weeks, respectively.

* Added option size_limit, if nonzero sets limit of size of the passage to cache.  Passages larger than this limit will not be cached.

* Added option debug, if true adds a message indicating whether the entry came from the cache or the server.

* Added remove option.

== Upgrade Notice ==

= 1.0.23 =
Adds passages facility allowing you to reference passages by name with date substitution.

= 1.0.22 =
Adds an options page to allow setting of caching defaults.

= 1.0.21 =
Adds caching of passages via the WordPress Transients API.
