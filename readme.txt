=== ESV Bible Shortcode for WordPress ===
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

This plugin uses the ESV Bible Web Service API to provide an easy way to display scripture in the ESV translation using WordPress shortcodes.

== Description ==

This plugin uses the ESV Bible Web Service API to provide an easy way to display scripture in the ESV translation on a WordPress installation. Using WordPress shortcodes, you can quickly display lengthy passages or single verses in your WordPress posts.


== Installation ==

The plugin is simple to install:

1. Download 'esv-bible-shortcode-for-wordpress.zip'
2. Unzip
3. Upload 'esv-shortcode' directory to your '/wp-content/plugins' directory
4. Go to the plugin management page and enable the plugin
5. Done!


== Usage ==

The simplest usage of the plugin to insert the '[esv]' shortcode into your page or post, using the attributes listed below. These attributes pretty much mirror those on the ESV API.

[esv scripture='John 3:16-21']

* NOTE: For reasons that should be obvious, the scripture attribute is required, (and is the only required attribute.)


**Optional Attributes:**

'container' // Default: 'span'.
The html tag to wrap your scripture in.

'class' // Default: 'esv-scripture'.
The css class to assign to the html container tag. Aids in adding custom CSS to your scripture.

'include_passage_references' // Default: 'true;
Boolean value to include <h2> tags that indicate which passage is being displayed. For example: Isaiah 53:1-5.

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
Include a link to the audio version of the requested passage. The link appears in a <small> tag in the passage's identifying <h2>.

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