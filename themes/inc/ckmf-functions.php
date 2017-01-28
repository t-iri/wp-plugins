<?php 
/*****************************************************************
 * Pager for any archive
 *****************************************************************/
if( !function_exists('ckmf_pager') ){
	function ckmf_pager(){
		$pager = ckmf_get_pager();
		echo $pager;
	}
}

if( !function_exists('ckmf_get_esc_pagenum_link') ){
	function ckmf_get_esc_pagenum_link($num){
		$link = get_pagenum_link($num);
		$link = apply_filters('ckmf_filter_get_esc_pagenum_link', $link, $num);
		$link = esc_url($link);
		return $link;
	}
}

if( !function_exists('ckmf_get_pager') ){
	function ckmf_get_pager($args=array()){
		global $wp_query;
		$defaults = array(
			'paged_key' => 'paged', 
			'prev_text' => "&laquo;Prev", 
			'prev_always' => false, 
			'next_text' => "Next&raquo;", 
			'next_always' => false, 
			'itvl_text' => "...", 
			'use_first' => true, 
			'use_last' => true, 
			'class_base' => 'ckmf-pager', 
			'class_extra' => '', 
			'current_wrap_tag' => '', 
			'current_wrap_cls' => '', 
			'class_li' => '', 
			'disp_num' => 5, 
			'the_query' => $wp_query
		);
		$args = apply_filters('ckmf_filter_get_pager_args', $args);
		$args = ckmf_parse_args($defaults, $args);
		if(!$args) return '';
		extract($args);

		$found_info = ckmf_get_found_posts_info($args);
		$found_posts = isset($found_info["found_posts"]) ? (int)$found_info["found_posts"] : 0;
		$paged = isset($found_info["paged"]) ? (int)$found_info["paged"] : 1;
		$per_page = isset($found_info["per_page"]) ? (int)$found_info["per_page"] : 0;
		$max_page = isset($found_info["max_page"]) ? (int)$found_info["max_page"] : 0;
		if(!$max_page) return '';

		$prev_flag = ($paged !== 1) ? true : false;
		$next_flag = ($paged !== $max_page) ? true : false;
		$prev_interval = false;
		$prev_first_link = false;
		$next_interval = false;
		$next_last_link = false;
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

		$cls_prev .= ' ' . $cls_ctrl;
		$cls_next .= ' ' . $cls_ctrl;

		if( $class_li ){
			$cls_prev .= ' ' . $class_li;
			$cls_next .= ' ' . $class_li;
			$cls_ctrl .= ' ' . $class_li;
			$cls_itvl .= ' ' . $class_li;
			$cls_crnt .= ' ' . $class_li;
			$class_li = ' class="' . $class_li . '"';
		}

		$pager_temp = (string)$prev_text;
		if( $pager_temp !== '' ){
			if( $prev_flag ){
				$cls_prev .= ' ctrl-active';
				$pager_temp = '<a href="'.ckmf_get_esc_pagenum_link($paged - 1).'">'.$pager_temp.'</a>';
			} elseif( $prev_always ){
				$cls_prev .= ' ctrl-inactive';
				$pager_temp = '<span class="ctrl-always">'.$pager_temp.'</span>';
			}
			if( $prev_flag || $prev_always ){
				$pager[] = '<li class="' . $cls_prev . '">' . $pager_temp . '</li>';
			}
		}

		if($prev_first_link && $use_first){
			$pager[] = '<li' . $class_li . '><a href="' . ckmf_get_esc_pagenum_link(1) . '">1</a></li>';
		}
		if($prev_interval && $itvl_text !== ''){
			$pager[] = '<li class="' . $cls_itvl . '">' . $itvl_text . '</li>';
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

		$current_wrap_tag = in_array($current_wrap_tag, array("span", "b"), true) ? $current_wrap_tag : "";
		if( $current_wrap_tag ){
			$current_wrap_cls = ( $current_wrap_cls ) ? " class=\"{$current_wrap_cls}\"" : "";
		}
		for($i=$start_page; $i<($start_page+$disp_num); $i++){
			if($i <= $max_page){
				if($i === $paged){
					$li_current = $i;
					if( $current_wrap_tag ){
						$li_current = "<{$current_wrap_tag}{$current_wrap_cls}>{$li_current}</{$current_wrap_tag}>";
					}
					$pager[] = '<li class="' . $cls_crnt . '">' . $li_current . '</li>';
				} else {
					$pager[] = '<li' . $class_li . '><a href="' . ckmf_get_esc_pagenum_link($i) . '">' . $i . '</a></li>';
				}
			}
		}

		if($next_interval && $itvl_text !== ''){
			$pager[] = '<li class="' . $cls_itvl . '">' . $itvl_text . '</li>';
		}
		if($next_last_link && $use_last){
			$pager[] = '<li' . $class_li . '><a href="' . ckmf_get_esc_pagenum_link($max_page) . '">' . $max_page . '</a></li>';
		}

		$pager_temp = (string)$next_text;
		if( $pager_temp !== '' ){
			if( $next_flag ){
				$cls_next .= ' ctrl-active';
				$pager_temp = '<a href="'.ckmf_get_esc_pagenum_link($paged + 1).'">'.$pager_temp.'</a>';
			} elseif( $next_always ){
				$cls_next .= ' ctrl-inactive';
				$pager_temp = '<span class="ctrl-always">'.$pager_temp.'</span>';
			}
			if( $next_flag || $next_always ){
				$pager[] = '<li class="' . $cls_next . '">' . $pager_temp . '</li>';
			}
		}

		$pager[] = '</ul>';
		$pager = implode("\n", $pager);

		return $pager;
	}
}

if( !function_exists('ckmf_get_found_posts_info') ){
	function ckmf_get_found_posts_info($args=array()){
		global $wp_query;
		$defaults = array(
			'paged_key' => 'paged', 
			'the_query' => $wp_query
		);
		$args = apply_filters('ckmf_filter_get_found_posts_info_args', $args);
		$args = ckmf_parse_args($defaults, $args);
		if(!$args) return '';
		extract($args);

		$found_posts = (int)$the_query->found_posts;
		$paged = ( (int)$the_query->get($paged_key) > 0 ) ? (int)$the_query->get($paged_key) : 1;
		$per_page = (int)$the_query->get('posts_per_page');
		$max_page = intval(ceil($found_posts / $per_page));

		$post_count = (int)$the_query->post_count;
		$start_post = ( ( $paged - 1 ) * $per_page ) + 1;
		$end_post = $start_post + $post_count - 1;

		$info = compact("found_posts", "paged", "per_page", "max_page", "post_count", "start_post", "end_post");

		return $info;
	}
}
/*****************************************************************
 * timthumb
 *****************************************************************/
if( !function_exists('ckmf_attachment_url_for_timsrc') ){
	function ckmf_attachment_url_for_timsrc($data){
		$url = ckmf_get_attachment_url_for_timsrc($data);
		echo $url;
	}
}

if( !function_exists('ckmf_get_attachment_url_for_timsrc') ){
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
}

if( !function_exists('ckmf_timsrc') ){
	function ckmf_timsrc($src, $args=array(), $external=false){
		$src = ckmf_get_timsrc($src, $args, $external);
		echo $src;
	}
}

if( !function_exists('ckmf_get_timsrc') ){
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
}
/*****************************************************************
 * thumbnail
 *****************************************************************/
if( !function_exists('ckmf_get_the_post_thumbnail') ){
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
			'noimage_src' => '', 
			'type' => 'thumbnail', 
			'class' => '', 
			'tim_base' => '', 
		);
		$args = ckmf_parse_args($defaults, $args, true);
		if(!$args) return $thumb;
		extract($args);

		$noimage_src = ($noimage_src && file_exists($noimage_src)) ? $noimage_src : '';
		$thumb_id = get_post_thumbnail_id($post_id);
		$thumb_src = "";
		if($thumb_id){
			$thumb_src = wp_get_attachment_image_src($thumb_id, $type);
			$thumb_src = ($thumb_src[0]) ? $thumb_src[0] : '';
		}
		if(!$thumb_src){
			$content = apply_filters('the_content', $post->post_content);
			$ptn = "/<img.*?src=['\"](.*?)['\"]/u";
			$thumb_src = (preg_match($ptn, $content, $mt)) ? $mt[1] : "";
		}
		$thumb_src = ($thumb_src) ? $thumb_src : $noimage_src;
		if($thumb_src && $w && function_exists('ckmf_get_timsrc') && $tim_base){
			$thumb_src = ckmf_get_timsrc($thumb_src, compact('w', 'h'), $tim_base);
		}
		if($thumb_src){
			$alt = esc_attr($post->post_title);
			$cls = ( $class ) ? " class=\"{$class}\"" : '';
			$thumb = "<img{$cls} src=\"{$thumb_src}\" alt=\"{$alt}\">";
		}
		return $thumb;
	}
}

/*****************************************************************
 * Others
 *****************************************************************/
if( !function_exists('ckmf_trim') ){
	function ckmf_trim($v){
		if(!empty($v)){
			$v = preg_replace("/(^[ @\s]+)|([ @\s]+$)/u", "", $v);
		}
		if(empty($v) && !is_numeric($v)){
			return "";
		}
		return $v;
	}
}

if( !function_exists('ckmf_trim_map_and_str_filter') ){
	function ckmf_trim_map_and_str_filter($val){
		$val = (is_array($val)) ? array_filter(array_map('ckmf_trim', $val), 'mb_strlen') : array();
		return $val;
	}
}

if( !function_exists('ckmf_convert_eol') ){
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
}

if( !function_exists('ckmf_validate') ){
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
}

if( !function_exists('ckmf_parse_args') ){
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
}
