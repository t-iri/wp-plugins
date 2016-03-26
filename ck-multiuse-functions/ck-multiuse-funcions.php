<?php
/*
Plugin Name: CK Multiuse Functions
Plugin URI:  http://URI_Of_Page_Describing_Plugin_and_Updates
Description: Load useful functions
Version:     1.1
Author:      iri
Author URI:  http://cunelwork.co.jp/
License:     GPL3
 
{Plugin Name} is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
{Plugin Name} is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with {Plugin Name}. If not, see {License URI}.
*/
if(defined('CKMF_VERSION')) return;
define('CKMF_VERSION', '1.1');
/*****************************************************************
 * Config
 *****************************************************************/
define('CKMF_USE_SHORTCODE', false);
/*****************************************************************
 * Pager for any archive
 *****************************************************************/
function ckmf_pager(){
	$pager = ckmf_get_pager();
	echo $pager;
}
function ckmf_get_esc_pagenum_link($num){
	$link = get_pagenum_link($num);
	$link = apply_filters('ckmf_filter_get_esc_pagenum_link', $link, $num);
	$link = esc_url($link);
	return $link;
}
function ckmf_get_pager($args=array()){
	global $wp_query;
	$defaults = array(
		'paged_key' => 'paged', 
		'prev_text' => "&laquo;Prev", 
		'next_text' => "Next&raquo;", 
		'itvl_text' => "...", 
		'use_first' => true, 
		'use_last' => true, 
		'class_base' => 'ckmf-pager', 
		'class_extra' => '', 
		'disp_num' => 5, 
		'the_query' => $wp_query
	);
	$args = apply_filters('ckmf_filter_get_pager_args', $args);
	$args = ckmf_parse_args($defaults, $args);
	if(!$args) return '';
	extract($args);

	$total_posts = (int)$the_query->found_posts;
	$paged = ( (int)get_query_var($paged_key) > 0 ) ? (int)get_query_var($paged_key) : 1;
	$per_page = (int)get_query_var('posts_per_page');
	$max_page = intval(ceil($total_posts / $per_page));
	if(!$max_page) return '';

	$prev_flag = ($paged !== 1) ? true : false;
	$next_flag = ($paged !== $max_page) ? true : false;
	$prev_interval = false;
	$prev_first_link = false;
	$next_interval = false;
	$next_last = false;
	if(($max_page - $disp_num) > 0){
		if( ($paged - 1) > ($disp_num - intval(floor($disp_num / 2))) ){
			$prev_interval = true;
		}
		if( ($paged - 1) >= ($disp_num - intval(floor($disp_num / 2))) ){
			$prev_first_link = true;
		}

		if( ($max_page - $paged) > ($disp_num - intval(floor($disp_num / 2))) ){
			$next_interval = true;
		}
		if( ($max_page - $paged) >= ($disp_num - intval(floor($disp_num / 2))) ){
			$next_last_link = true;
		}
	}

	$url = get_pagenum_link($paged + 1);
	$pager = array();
	$cls_pager = $class_base;
	if($class_extra){
		$cls_pager .= ' ' . $class_extra;
	}
	$pager[] = '<ul class="'.$cls_pager.'">';
	$cls_prev = $class_base.'-prev';
	$cls_next = $class_base.'-next';
	$cls_ctrl = $class_base.'-control';
	$cls_itvl = $class_base.'-interval';
	$cls_crnt = $class_base.'-current';

	$pager_temp = $prev_text;
	if($prev_flag){
		$pager_temp = '<a href="'.ckmf_get_esc_pagenum_link($paged - 1).'">'.$pager_temp.'</a>';
		$cls_prev .= ' ctrl-active';
	}
	$pager[] = '<li class="'.$cls_prev.' '.$cls_ctrl.'">'.$pager_temp.'</li>';

	if($prev_first_link && $use_first){
		$pager[] = '<li><a href="'.ckmf_get_esc_pagenum_link(1).'">1</a></li>';
	}
	if($prev_interval && $itvl_text !== ''){
		$pager[] = '<li class="'.$cls_itvl.'">'.$itvl_text.'</li>';
	}

	if($paged === 1){
		$start_page = $paged;
	} else {
		if($paged === $max_page){
			$start_page = $paged - $disp_num + 1;
		} else {
			$start_page = $paged - intval(floor($disp_num / 2));
		}
		if($start_page <= 0){
			$start_page = 1;
		}
	}
	for($i=$start_page; $i<($start_page+$disp_num); $i++){
		if($i <= $max_page){
			if($i === $paged){
				$pager[] = '<li class="'.$cls_crnt.'">'.$i.'</li>';
			} else {
				$pager[] = '<li><a href="'.ckmf_get_esc_pagenum_link($i).'">'.$i.'</a></li>';
			}
		}
	}

	if($next_interval && $itvl_text !== ''){
		$pager[] = '<li class="'.$cls_itvl.'">'.$itvl_text.'</li>';
	}
	if($next_last_link && $use_last){
		$pager[] = '<li><a href="'.ckmf_get_esc_pagenum_link($max_page).'">'.$max_page.'</a></li>';
	}

	$pager_temp = $next_text;
	if($next_flag){
		$pager_temp = '<a href="'.ckmf_get_esc_pagenum_link($paged + 1).'">'.$pager_temp.'</a>';
		$cls_next .= ' ctrl-active';
	}
	$pager[] = '<li class="'.$cls_next.' '.$cls_ctrl.'">'.$pager_temp.'</li>';

	$pager[] = '</ul>';
	$pager = implode("\n", $pager);

	return $pager;
}
/*****************************************************************
 * Read external RSS and convert to posts
 *****************************************************************/
function mkcf_get_posts_by_external_rss($url, $posts_per_page, $timezone='Asia/Tokyo'){
	$posts = array();
	$feedpath = ABSPATH . WPINC . '/feed.php';
	if(!file_exists($feedpath)) return $posts;
	require_once($feedpath);
	$rss = fetch_feed($url);
	if(!is_wp_error($rss)){
		$maxitems = $rss->get_item_quantity($posts_per_page);
		$rss_items = $rss->get_items(0, $maxitems);
	}
	if($maxitems){
		date_default_timezone_set($timezone);
		foreach($rss_items as $k => $item){
			$post = array(
				"href" => esc_url($item->get_permalink()), 
				"date" => $item->get_date('Y/m/d'), 
				"title" => esc_html($item->get_title()), 
				"content" => $item->get_content(), 
				"thumb" => "",
			);
			$posts[$k] = apply_filters('mkcf_filter_get_posts_bt_external_rss_per_post', $post, $url);
		}
	}
	return $posts;
}
/*****************************************************************
 * timthumb
 *****************************************************************/
function ckmf_attachment_url_for_timsrc($data){
	$url = ckmf_get_attachment_url_for_timsrc($data);
	echo $url;
}
function ckmf_get_attachment_url_for_timsrc($data){
	$url = "";
	if($data){
		if(is_numeric($data)){
			$url = wp_get_attachment_url($data);
		} else {
			$ptn = "/^" . preg_quote(site_url(), "/") . "/u";
			if(preg_match($ptn, $data)){
				$url = $data;
			}
		}
		if($url){
			$ptn = "/(^http[s]{0,1}:\/\/[^\/]+?)\//u";
			if(preg_match($ptn, $url, $mt)){
				$url = str_replace($mt[1], "", $url);
			}
		}
	}
	return $url;
}
function ckmf_timsrc($src, $args=array(), $external=false){
	$src = ckmf_get_timsrc($src, $args, $external);
	echo $src;
}
function ckmf_get_timsrc($src, $args=array(), $src_base='', $external=false){
	$defaults = array(
		'w' => 0, 
		'h' => 0, 
		'q' => 100, 
		'zc' => 1, 
	);
	$args = ckmf_parse_args($defaults, $args, true);
	if(!$args) return $src;

	if(!$external){
		$src = ckmf_get_attachment_url_for_timsrc($src);
	}
	if($src){
		$args['src'] = $src;
		foreach($args as $k => $a){
			if(empty($a)){
				unset($args[$k]);
				continue;
			}
			$args[$k] = (string)$a;
		}
		if($src_base){
			$src = esc_url(add_query_arg($args, $src_base));
		}
	}
	return $src;
}
/*****************************************************************
 * thumbnail
 *****************************************************************/
function ckmf_get_the_post_thumbnail($post=false, $args=array()){
	$thumb = "";
	if(!$post){
		global $post;
		if(!$post){
			return $thumb;
		}
	}
	$defaults = array(
		'w' => 0, 
		'h' => 0, 
		'noimage_src' => ''
	);
	$args = ckmf_parse_args($defaults, $args, true);
	if(!$args) return $thumb;
	extract($args);

	$noimage_src = ($noimage_src && file_exists($noimage_src)) ? $noimage_src : '';
	$thumb_id = get_post_thumbnail_id($post_id);
	$thumb_src = "";
	if($thumb_id){
		$thumb_src = wp_get_attachment_image_src($thumb_id, 'thumbnail');
		$thumb_src = ($thumb_src[0]) ? $thumb_src[0] : '';
	}
	if(!$thumb_src){
		$content = apply_filters('the_content', $post->post_content);
		$ptn = "/<img.*?src=['\"](.*?)['\"]/u";
		$thumb_src = (preg_match($ptn, $content, $mt)) ? $mt[1] : "";
	}
	$thumb_src = ($thumb_src) ? $thumb_src : $noimage_src;
	if($thumb_src && $w && function_exists('ckmf_get_timsrc')){
		$thumb_src = ckmf_get_timsrc($thumb_src, $w, $h);
	}
	if($thumb_src){
		$alt = esc_attr($post->post_title);
		$thumb = "<img src=\"{$thumb_src}\" alt=\"{$alt}\">";
	}
	return $thumb;
}
/*****************************************************************
 * Shortcodes, be careful not to collide another one
 *****************************************************************/
if(defined('CKMF_USE_SHORTCODE') && CKMF_USE_SHORTCODE === true){
	//[url]
	add_shortcode('ckmf_url', 'ckmf_shortcode_url');
	function ckmf_shortcode_url() {
	    return get_bloginfo('url');
	}
	// [template_url]
	add_shortcode('ckmf_template_url', 'ckmf_shortcode_templateurl');
	function ckmf_shortcode_templateurl() {
	    return get_bloginfo('template_url');
	}
	// [entity text=""] 
	add_shortcode('ckmf_entity', 'ckmf_shortcode_entity');
	function ckmf_shortcode_entity($atts) {
		extract(shortcode_atts(array(
			'text' => '',
		), $atts));
	//$convmap = array(0, 0x10FFFF, 0, 0x10FFFF);
	//$text =  mb_encode_numericentity($text, $convmap, 'UTF-8');
		$text = antispambot($text);
		return $text;
	}
	function ckmf_get_forcing_entity($text){
		$text = "[ckmf_entity text=\"{$text}\"]";
		$text = do_shortcode($text);
		return $text;
	}
	function ckmf_forcing_entity($text){
		$text = ckmf_get_forcing_entity($text);
		echo $text;
	}
}
/*****************************************************************
 * Others
 *****************************************************************/
function ckmf_trim($v){
	if(!empty($v)){
		$v = preg_replace("/(^[ @\s]+)|([ @\s]+$)/u", "", $v);
	}
	if(empty($v) && !is_numeric($v)){
		return "";
	}
	return $v;
}
function ckmf_trim_map_and_str_filter($val){
	$val = (is_array($val)) ? array_filter(array_map('ckmf_trim', $val), 'mb_strlen') : array();
	return $val;
}
function ckmf_convert_eol($val, $to="\n"){
	$eol = array("\r\n", "\r", "\n");
	if($to === ""){
		$val = (is_string($val)) ? str_replace($eol, "", $val) : "";
		return $val;
	}
	$eol = (in_array($to, $eol, true)) ? array_fill_keys($eol, $to) : array();
	$val = (is_string($val) && $eol) ? strtr($val, $eol) : "";
	return $val;
}
function ckmf_validate($val, $type="", $is_bool=false){
	switch($type){
		case 'str':
		case 'string':
			$val = ( (is_string($val) && !empty($val)) || is_numeric($val) ) ? (string)$val : "";
			if($is_bool){
				$val = ($val !== "") ? true : false;
			}
			break;
		case 'arr':
		case 'array':
			$val = (is_array($val) && !empty($val)) ? $val : array();
			if($is_bool){
				$val = ($val) ? true : false;
			}
			break;
		case 'int':
		case 'integer':
			$val = (is_numeric($val) && !empty($val)) ? (int)$val : 0;
			if($is_bool){
				$val = ($val) ? true : false;
			}
			break;
	}
	return $val;
}
function ckmf_parse_args($base_args, $add_args, $strict=false){
	$args = array();
	if(!ckmf_validate($base_args, 'arr', true) || !is_array($add_args)){
		return $args;
	}
	foreach($base_args as $bk => $bv){
		$av = (isset($add_args[$bk])) ? $add_args[$bk] : $bv;
		if($strict === true && gettype($bv) !== gettype($av)){
			continue;
		}
		$args[$bk] = $av;
	}
	$args = (count($base_args) === count($args)) ? $args : array();
	return $args;
}
 ?>