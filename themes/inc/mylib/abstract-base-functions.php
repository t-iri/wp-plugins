<?php 
namespace Mylib\Base;

abstract class Functions {
/*************** Conditional ***************/
	protected function is_closure($obj){
		return (bool)( is_object($obj) && $obj instanceof \Closure );
	}

	protected function is_in_or_eq_value($needle, $haystack){
		return ( 
			( is_array($haystack) && in_array($needle, $haystack, true) ) 
			|| ( $needle === $haystack ) 
		) ? true : false;
	}

/*************** Functions ***************/
	protected function safe_call_func($func, $args=array()){
		if( function_exists($func) ){
			if( $args ){
				if( !is_array($args) ) $args = array($args);
				return call_user_func_array($func, $args);
			}
			return call_user_func($func);
		}
		return false;
	}

/*************** Object ***************/
	protected function call_the_object_method($obj, $func, $args=array()){
		if( is_object($obj) && method_exists($obj, $func) ){
			if( $args ){
				if( !is_array($args) ) $args = array($args);
				return call_user_func_array( array($obj, $func), $args );
			}
			return call_user_func( array($obj, $func) );
		}
		return false;
	}

	protected function get_the_object_property($obj, $key){
		return ( is_object($obj) && property_exists($obj, $key) ) ? $obj->$key : NULL;
	}

	protected function get_the_object_list_properties($tgt, $key){
		$args = array_fill(0, count($tgt), $key);
		return array_map( array($this, 'get_the_object_property'), $tgt, $args );
	}

/*************** HTML ***************/
	protected function make_cls_attr($str){
		return $this->make_attr('class', $str);
	}

	protected function make_name_attr($str){
		return $this->make_attr('name', $str);
	}

	protected function make_attr($attr, $str=''){
		if( is_string($attr) ){
			if( is_array($str) ) $str = implode( ' ', array_filter($str) );
			return sprintf( ' %s="%s"', $attr, htmlspecialchars($str, ENT_QUOTES) );
		}
		$single_str = '';
		if( is_array($attr) ){
			foreach( $attr as $k => $v ){
				$single_str .= $this->make_attr($k, $v);
			}
		}
		return $single_str;
	}

	protected function make_prop($str){
		$arr = is_array($str) ? $str : array($str);
		return $this->make_attr( array_combine($arr, $arr) );
	}

	protected function make_a_tag($href, $attr, $str){
		if( is_array($attr) ) $attr = $this->make_attr($attr);
		$attr = $this->make_attr( array(
			'href' => $href, 
		) ) . $attr;
		return $this->make_html_tag('a', $attr, $str);
	}

	protected function make_img_tag($src, $alt='', $attr=''){
		if( is_array($src) ){
			$attr = $this->make_attr($src);
		} else {
			$attr = $this->make_attr( array(
				'src' => $src, 
				'alt' => $alt, 
			) ) . $attr;
		}
		return sprintf('<img%s>', $attr);
	}

	protected function make_ipt_tag($type, $value='', $attr=''){
		if( is_array($type) ){
			$attr = $this->make_attr($type);
		} else {
			$attr = $this->make_attr( array(
				'type' => $type, 
				'value' => $value, 
			) ) . $attr;
		}
		return sprintf('<input%s>', $attr);
	}

	protected function make_html_tag($tag, $attr, $str){
		if( is_array($attr) ) $attr = $this->make_attr($attr);
		return sprintf('<%1$s%2$s>%3$s</%1$s>', $tag, $attr, $str);
	}

	protected function make_ipt_name_value($prime, $sub=array()){
		if( is_array($prime) ){
			$sub = $prime;
			$prime = array_shift($sub);
		}
		return $prime . implode( '', array_map( function($v){
			return '[' . $v .  ']';
		}, array_filter($sub, 'mb_strlen') ) );
	}

	protected function convert_ipt_name_value_into_id_value($name_val){
		return strtr( '__' . (string)$name_val, array(
			'][' => '-', 
			'[' => '-', 
			']' => '', 
		) );
	}

/*************** validate convert ***************/
	protected function convert_eol($val, $to="\n"){
		$eol = array("\r\n", "\r", "\n");
		if( '' === $to ) return is_string($val) ? str_replace($eol, '', $val) : '';

		$eol = in_array($to, $eol, true) ? array_fill_keys($eol, $to) : array();
		return ( is_string($val) && $eol ) ? strtr($val, $eol) : '';
	}

	protected function trim($val){
		$v = '';
		if( is_string($val) || is_numeric($val) ){
			$v = preg_replace("/(^[ 　\s]+)|([ 　\s]+$)/u", '', $val);
		}
		return $v;
	}

	protected function validate_str($val){
		return $this->validate($val, 'str');
	}

	protected function is_valid_str($val){
		return $this->validate($val, 'str', true);
	}

	protected function validate_arr($val){
		return $this->validate($val, 'arr');
	}

	protected function is_valid_arr($val){
		return $this->validate($val, 'arr', true);
	}

	protected function validate_int($val){
		return $this->validate($val, 'int');
	}

	protected function is_valid_int($val){
		return $this->validate($val, 'int', true);
	}

	private function validate($val, $type='', $change_bool=false){
		switch( $type ){
			case 'str':
			case 'string':
				$val = ( 
					( is_string($val) && !empty($val) ) 
					|| is_numeric($val) 
				) ? (string)$val : '';
				if( $change_bool ) $val = ( $val !== '' ) ? true : false;
				break;

			case 'arr':
			case 'array':
				$val = ( is_array($val) && !empty($val) ) ? $val : array();
				if( $change_bool ) $val = ( $val ) ? true : false;
				break;

			case 'int':
			case 'integer':
				$val = ( is_numeric($val) && !empty($val) ) ? (int)$val : 0;
				if( $change_bool ) $val = ( $val ) ? true : false;
				break;
		}
		return $val;
	}

/*************** Array ***************/
	protected function get_str_if_isset($args, $key){
		return $this->get_if_isset($args, $key, 'string');
	}

	protected function get_arr_if_isset($args, $key){
		return $this->get_if_isset($args, $key, 'array');
	}

	protected function get_int_if_isset($args, $key){
		return $this->get_if_isset($args, $key, 'integer');
	}

	protected function get_float_if_isset($args, $key){
		return $this->get_if_isset($args, $key, 'float');
	}

	protected function get_number_if_isset($args, $key){
		$val = $this->get_str_if_isset($args, $key);
		return preg_match( "/^(\d+)$/u", mb_convert_kana($val, 'n') ) ? $val : '';
	}

	protected function get_if_isset($args, $key, $fmt=''){
		if( in_array( $fmt, array('number'), true ) ){
			return $this->get_number_if_isset($args, $key);
		}

		$val = ( is_array($args) && isset($args[$key]) ) ? $args[$key] : NULL;
		if( '' === (string)$fmt ) return $val;
		if( 'string' === $fmt && is_array($val) ) return '';

		settype($val, $fmt);
		return $val;
	}

	protected function shift_str_if_isset(&$arr, $key){
		return $this->shift_if_isset($arr, $key, 'string');
	}

	protected function shift_arr_if_isset(&$arr, $key){
		return $this->shift_if_isset($arr, $key, 'array');
	}

	protected function shift_int_if_isset(&$arr, $key){
		return $this->shift_if_isset($arr, $key, 'integer');
	}

	protected function shift_float_if_isset(&$arr, $key){
		return $this->shift_if_isset($arr, $key, 'float');
	}

	protected function shift_number_if_isset(&$arr, $key){
		$val = $this->shift_str_if_isset($arr, $key);
		return preg_match( "/^(\d+)$/u", mb_convert_kana($val, 'n') ) ? $val : '';
	}

	protected function shift_if_isset(&$arr, $key, $fmt=''){
		if( in_array( $fmt, array('number'), true ) ){
			return $this->shift_number_if_isset($arr, $key);
		}
		$val = NULL;
		if( is_array($arr) && isset($arr[$key]) ){
			$val = $arr[$key];
			unset($arr[$key]);
		}
		if( '' === (string)$fmt ) return $val;
		if( 'string' === $fmt && is_array($val) ) return '';

		settype($val, $fmt);
		return $val;
	}

	protected function is_list_array($arr){
		$arr = $this->validate_arr($arr);
		return (bool)( array_filter( array_keys($arr), 'is_int' ) );
	}

	protected function recursive_parse_args($def, $add, $only_isset=false){
		$result = $def;
		if( !is_array($def) || !is_array($add) ) return $result;

		foreach( $add as $k => $v ){
			if( $only_isset && !isset($result[$k]) ) continue;

			if( is_array($v) && ( isset($result[$k]) && is_array($result[$k]) ) ){
				$v = $this->recursive_parse_args( $result[$k], $v, $only_isset );
			}
			$result[$k] = $v;
		}
		return $result;
	}

	protected function search_arr_val_deeply($arr, $key){
		$keys = array();
		if( is_string($key) ){
			$keys = explode('/', $key);
		} else {
			$keys = is_array($key) ? $key : array($key);
		}
		if( !$keys ) return false;

		foreach( $keys as $k ){
			$arr = ( is_array($arr) && isset($arr[$k]) ) ? $arr[$k] : NULL;
			if( NULL === $arr ) break;
		}
		return $arr;
	}

	protected function prioritize_arr_order($arr, $keys){
		if( !is_array($arr) || !$arr ) return $arr;

		$_arr = array();
		foreach( $keys as $k ){
			if( !isset($arr[$k]) ) continue;

			$_arr[$k] = $arr[$k];
			unset($arr[$k]);
		}
		return array_merge($_arr, $arr);
	}

/*************** PHP compatible ***************/
	protected function array_column($input = null, $columnKey = null, $indexKey = null){
		// Using func_get_args() in order to check for proper number of
		// parameters and trigger errors exactly as the built-in array_column()
		// does in PHP 5.5.
		$argc = func_num_args();
		$params = func_get_args();
		if ($argc < 2) {
			trigger_error("array_column() expects at least 2 parameters, {$argc} given", E_USER_WARNING);
			return null;
		}
		if (!is_array($params[0])) {
			trigger_error(
				'array_column() expects parameter 1 to be array, ' . gettype($params[0]) . ' given',
				E_USER_WARNING
			);
			return null;
		}
		if (!is_int($params[1])
			&& !is_float($params[1])
			&& !is_string($params[1])
			&& $params[1] !== null
			&& !(is_object($params[1]) && method_exists($params[1], '__toString'))
		) {
			trigger_error('array_column(): The column key should be either a string or an integer', E_USER_WARNING);
			return false;
		}
		if (isset($params[2])
			&& !is_int($params[2])
			&& !is_float($params[2])
			&& !is_string($params[2])
			&& !(is_object($params[2]) && method_exists($params[2], '__toString'))
		) {
			trigger_error('array_column(): The index key should be either a string or an integer', E_USER_WARNING);
			return false;
		}
		$paramsInput = $params[0];
		$paramsColumnKey = ($params[1] !== null) ? (string) $params[1] : null;
		$paramsIndexKey = null;
		if (isset($params[2])) {
			if (is_float($params[2]) || is_int($params[2])) {
					$paramsIndexKey = (int) $params[2];
			} else {
					$paramsIndexKey = (string) $params[2];
			}
		}
		$resultArray = array();
		foreach ($paramsInput as $row) {
			$key = $value = null;
			$keySet = $valueSet = false;
			if ($paramsIndexKey !== null && array_key_exists($paramsIndexKey, $row)) {
				$keySet = true;
				$key = (string) $row[$paramsIndexKey];
			}
			if ($paramsColumnKey === null) {
				$valueSet = true;
				$value = $row;
			} elseif (is_array($row) && array_key_exists($paramsColumnKey, $row)) {
				$valueSet = true;
				$value = $row[$paramsColumnKey];
			}
			if ($valueSet) {
				if ($keySet) {
					$resultArray[$key] = $value;
				} else {
					$resultArray[] = $value;
				}
			}
		}
		return $resultArray;
	}
}
