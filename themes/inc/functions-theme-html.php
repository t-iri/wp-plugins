<?php 
/*****************************************************************
 * HTMLs
 *****************************************************************/
function my_html_make_attr($attr, $str=''){
	if( is_string($attr) ){
		if( is_array($str) ) $str = implode( ' ', array_filter($str) );
		return sprintf( ' %s="%s"', $attr, esc_attr($str) );
	}
	$single_str = '';
	if( is_array($attr) && $attr ){
		foreach( $attr as $k => $v ){
			$single_str .= my_html_make_attr($k, $v);
		}
	}
	return $single_str;
}

function my_html_make_prop($str){
	if( is_string($str) ){
		return sprintf( ' %1$s="%1$s"', esc_attr($str) );
	}
	$single_str = '';
	if( is_array($str) && $str ){
		foreach( $str as $v ){
			$single_str .= my_html_make_prop($v);
		}
	}
	return $single_str;
}

function my_html_make_tag($tag, $attr, $str){
	if( is_array($attr) ) $attr = my_html_make_attr($attr);
	return sprintf('<%1$s%2$s>%3$s</%1$s>', $tag, $attr, $str);
}

