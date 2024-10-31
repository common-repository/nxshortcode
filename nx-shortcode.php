<?php
/*
Plugin Name: NxShortCode
Plugin URI: #
Description: Shortcodes fuer WP, [php][list]
Author: Bjoern R. Salgert
Version: 1.0.7
Author URI: http://www.bsnx.net/~bjoern/
*/

#
# Config
#
# Ref: http://codex.wordpress.org/Shortcode_API
#

/**
 * Path to GeSHi
 * Download GeSHi from http://qbnz.com/highlighter/
 *
 */
define("PATH_GESHI", dirname(__FILE__) . '/../../../system/libs/geshi/');

/**
 * URL zum Bild mit einen Stern
 */
define('NX_URL_FULLSTAR', '');

/**
 * Gibt an ob bei der Ausgabe einer Liste eine CSS Klasse benutzt werden soll,
 * um eine CSS Klasse zu benutzen als false definieren
 * 
 * Benutzt in der Funktion: nx_listex()
 */
define('NX_LISTSTYLE_INLINE', true);

#
# Script
#


/**
 * loading GeSHi
 *
 * @return bool
 */
function nx_load_geshi() {
	
	if (PATH_GESHI == '') {
		# assum geshi.php is in the searchpath 
		require_once 'geshi.php';
		return true;
	}
	$file = PATH_GESHI . 'geshi.php';
	if (file_exists($file)) {
		require_once $file;
		return true;
	} else {
		trigger_error(
			"Could not find geshi.php! ".
			"Please define the path in PATH_GESHI", 
			E_USER_ERROR
		);
		return false;
	}
	
}


/**
 * Syntax:
 * [h]Headline[/h]
 * 
 * @category Markup
 */
function shortcode_h($attr, $content) {

	return "<h1>$content</h1>";
	
}


/**
 * Function to display php in Posts
 * Syntax:
 * [php name="$name_off_the_custom_field"]
 *
 * @category Custom Fields
 * @param array $attr
 * @param string $content
 * @return string
 */
function shortcode_php($attr, $content = null) {

	global $wp_query;
	
	nx_load_geshi();
	
	if (isset($attr['name'])) {
		
		$name = $attr['name'];
		$postID = $wp_query->post->ID;
		$code = get_post_custom_values($name, $postID);
		$code = $code[0];
		$geshi = new GeSHi($code, 'php');
		$geshi->set_header_type(GESHI_HEADER_NONE);
		$result = $geshi->parse_code();
		$result = '<code>' . $result . '</code>';
		return $result;
		
	} else {
	
		$content = str_replace('<p>', '', $content);
		$content = str_replace('</p>', '', $content);
		$content = str_replace('&lt;', '<', $content);
		$content = str_replace('&gt;', '>', $content);
		$geshi = new GeSHi($content, 'php');
		$geshi->set_header_type(GESHI_HEADER_NONE);
		$result = $geshi->parse_code();
		$result = str_replace("\n", "", $result);
		$result = str_replace("<br /><br />", "\n", $result);
		$result = '<pre>' . $result . '</pre>';
		
		return $result;
		
	}
	
}

/**
 * Syntax:
 * [field name="$name"]
 * 
 * @category Custom Fields
 * @param array $attr
 * @param string $content
 * @return string
 */
function shortcode_field($attr, $content = null) {
	
	global $wp_query;
	
	$name = $attr['name'];
	
	$postID = $wp_query->post->ID;
	$text = get_post_custom_values($name, $postID);
	$text = $text[0];
	
	return $text;
	
}

/**
 * Syntax:
 * [url]http://www.domain.com[/url]
 * [url url="http://www.domain.com"]domain[/url]
 * [url url="http://www.domain.com"]
 * [url url="http://www.domain.com" title="domain"]
 * 
 * @category Markup
 * @param array $attr
 * @param string $content
 * @return string
 */
function shortcode_url($attr, $content = null) {
	
	if ($content != null) {
		$title = $content;
	} else {
		$title = $attr['title'];
	}
	if (isset($attr['url'])) {
		$href = $attr['url'];
	} else {
		$href = $content;
	}
	
	return sprintf('<a href="%s">%s</a>', $href, $title);
	
}

/**
 * Shortcode für DeviantART
 * 
 * @category Webservice
 */
function shortcode_deviantart($attr, $content = null) {
	
	$id = $attr['id'];
	
	return '
	<p style="text-align: center;">
		<object width="450" height="385">
			<param name="movie" value="http://backend.deviantart.com/embed/view.swf" />
			<param name="flashvars" value="id='.$id.'&#038;width=1337" />
			<param name="allowScriptAccess" value="always" />
			<embed 
				src="http://backend.deviantart.com/embed/view.swf" 
				type="application/x-shockwave-flash" 
				width="450" 
				flashvars="id='.$id.'&#038;width=1337" 
				height="385" 
				allowscriptaccess="always">
			</embed>
			</object>
			<br />
			<a href="http://www.deviantart.com/deviation/'.$id.'/">No Wood</a>
			 by ~<a class="u" href="http://b-oern.deviantart.com/">b-oern</a> on 
			<a href="http://www.deviantart.com">deviant</a><a href="http://www.deviantart.com">ART</a>
	</p>
	';
	
}

/**
 * YouTube Video anzeigen
 *
 * @param array $attr
 * @param string $content
 * 
 * @category Webservice
 */
function shortcode_youtube($attr, $content = null) {
	
	$id = $attr['v'];
	
	$html = '<object width="425" height="344">
		<param name="movie" value="http://www.youtube.com/v/'.$id.'&hl=de&fs=1"></param>
		<param name="allowFullScreen" value="true"></param>
		<embed src="http://www.youtube.com/v/'.$id.'&hl=de&fs=1" type="application/x-shockwave-flash" allowfullscreen="true" width="425" height="344"></embed>
	</object>';
	
	return $html;
	
}	


function shortcode_songogtheday($attr, $content = null) {
	
	return sprintf('<div style="text-align: center;">%s - %s</div><p />', $attr['artist'], $attr['title']);
	
}

/**
 * Liste darstellen
 *
 * @param array $array
 * @return string
 */
function nx_listex($array) {
	
	$inline = true;
	if (defined('NX_LISTSTYLE_INLINE')) {
		if (!NX_LISTSTYLE_INLINE) {
			$inline = false;		
		}
	}
	
	$content = "\n\n<div class=\"nx_list\">\n";
	foreach ($array as $item) {
		if ($inline) {
			$content .= '<div style="float: left; padding: 3px; border: 1px #ccc dotted; margin: 3px;">';
		} else {
			$content .= '<div class="nx_listitem">';
		}
		$content .= (string) $item;
		$content .= '</div>';
	}
	
	$content .= "<div style=\"clear: both;\"></div>\n";
	$content .= "</div>\n\n";
	return $content;
	
}


/**
 * Syntax: [list field="$custom_field"]
 *
 * @category Custom Fields
 * @param array $attr
 * @param string $content
 * @return string
 */
function shortcode_list($attr, $content = null) {
	
	global $wp_query;
	
	if (isset($attr['field'])) {
		$name = $attr['field'];
		$postID = $wp_query->post->ID;
		$list_source = get_post_custom_values($name, $postID);
		$list_source = $list_source[0];
		$items = explode("\n", $list_source);
		return nx_listex($items);
	} else {
		$items = explode("\n", $content);
		return nx_listex($items);
	}
	
}

/**
 * Syntax:
 * [rating v=5]
 * [rating field="$field"]
 *
 * @param array $attr
 * @param string $content
 */
function shortcode_rating($attr, $content = null) {
	
	global $wp_query;
	
	$value = 0;
	if (isset($attr['v'])) {
		$value = $attr['v'];
	} elseif (isset($attr['field'])) {
		$name = $attr['field'];
		$postID = $wp_query->post->ID;
		$value = get_post_custom_values($name, $postID);
		$value = $value[0];
	}
	$result = "\n\n<div class=\"nx_rating\">\n";
	for ($i = 0; $i < $value; $i++) {
		$result .= "<img src=\"".NX_URL_FULLSTAR."\" alt=\"Star\" border=\"0\"/>\n";
	}
	$result .= "</div>\n\n";
	
	return $result;
	
}

#
# Register
#

add_shortcode('h', 'shortcode_h');

add_shortcode('php', 'shortcode_php');

add_shortcode('field', 'shortcode_field');

add_shortcode('url', 'shortcode_url');

add_shortcode('deviantart', 'shortcode_deviantart');

add_shortcode('youtube', 'shortcode_youtube');

add_shortcode('songoftheday', 'shortcode_songogtheday');

add_shortcode('list', 'shortcode_list');

add_shortcode('rating', 'shortcode_rating');

?>