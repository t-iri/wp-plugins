<?php 
abstract class myBaseFunctions {
/*************** Conditional ***************/
	protected function is_closure($obj){
		return (bool)( is_object($obj) && $obj instanceof Closure );
	}

	protected function is_in_or_eq_value($needle, $haystack){
		$bool = ( 
			( is_array($haystack) && in_array($needle, $haystack, true) ) 
			|| ( $needle === $haystack ) 
		) ? true : false;
		return $bool;
	}

/*************** Object ***************/
	protected function call_the_object_method($obj, $func, $args=array()){
		$res = false;
		if( is_object($obj) && method_exists($obj, $func) ){
			if($args){
				$args = ( !is_array($args) ) ? array($args) : $args;
				$res = call_user_func_array(array($obj, $func), $args);
			} else {
				$res = call_user_func(array($obj, $func));
			}
		}
		return $res;
	}

	protected function get_the_object_property($obj, $key){
		$property = ( is_object($obj) && property_exists($obj, $key) ) ? $obj->$key : NULL;
		return $property;
	}

	protected function get_the_object_list_properties($tgt, $key){
		$args = array_fill(0, count($tgt), $key);
		$list_properties = array_map( array($this, "get_the_object_property"), $tgt, $args );
		return $list_properties;
	}

/*************** HTML ***************/
	protected function make_cls_attr($str){
		return $this->make_attr('class', $str);
	}
	protected function make_name_attr($str){
		return $this->make_attr('name', $str);
	}
	protected function make_attr($attr, $str){
		$attr_fmt = ' %s="%s"';
		return sprintf($attr_fmt, $attr, $str);
	}
	protected function make_prop($str){
		$prop_fmt = ' %1$s="%1$s"';
		return sprintf($prop_fmt, $str);
	}
	protected function make_a_tag($href, $attr, $str){
		$a_fmt = '<a href="%s"%s>%s</a>';
		return sprintf($a_fmt, $href, $attr, $str);
	}
	protected function make_img_tag($src, $alt, $attr){
		$img_fmt = '<img src="%s" alt="%s"%s>';
		return sprintf($img_fmt, $src, $alt, $attr);
	}
	protected function make_ipt_tag($type, $value='', $attr=''){
		if( is_array($type) ){
			foreach($type as $k => $v){
				$type[$k] = $this->make_attr($k, $v);
			}
			$type = implode($type, " ");
			$ipt_fmt = '<input%s>';
			$ipt = sprintf($ipt_fmt, $type);
		} else {
			$ipt_fmt = '<input type="%s" value="%s"%s>';
			$ipt = sprintf($ipt_fmt, $type, $value, $attr);
		}
		return $ipt;
	}
	protected function make_html_tag($tag, $attr, $str){
		$ele_fmt = '<%1$s%2$s>%3$s</%1$s>';
		return sprintf($ele_fmt, $tag, $attr, $str);
	}

/*************** validate convert ***************/
	protected function convert_eol($val, $to="\n"){
		$eol = array("\r\n", "\r", "\n");
		if( "" === $to ){
			$val = is_string($val) ? str_replace($eol, "", $val) : "";
			return $val;
		}
		$eol = in_array($to, $eol, true) ? array_fill_keys($eol, $to) : array();
		$val = ( is_string($val) && $eol ) ? strtr($val, $eol) : "";
		return $val;
	}

	protected function trim($val){
		$v = '';
		if( is_string($val) || is_numeric($val) ){
			$v = preg_replace("/(^[ 　\s]+)|([ 　\s]+$)/u", "", $val);
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

	private function validate($val, $type="", $change_bool=false){
		switch($type){
			case 'str':
			case 'string':
				$val = ( 
					( is_string($val) && !empty($val) ) 
					|| is_numeric($val) 
				) ? (string)$val : "";

				if( $change_bool ){
					$val = ( $val !== "" ) ? true : false;
				}
				break;
			case 'arr':
			case 'array':
				$val = ( is_array($val) && !empty($val) ) ? $val : array();
				if( $change_bool ){
					$val = ( $val ) ? true : false;
				}
				break;
			case 'int':
			case 'integer':
				$val = ( is_numeric($val) && !empty($val) ) ? (int)$val : 0;
				if( $change_bool ){
					$val = ( $val ) ? true : false;
				}
				break;
		}
		return $val;
	}

/*************** Array ***************/
	protected function get_str_if_isset($args, $key){
		$val = $this->get_if_isset($args, $key, "string");
		return $val;
	}
	protected function get_arr_if_isset($args, $key){
		$val = $this->get_if_isset($args, $key, "array");
		return $val;
	}
	protected function get_int_if_isset($args, $key){
		$val = $this->get_if_isset($args, $key, "integer");
		return $val;
	}
	protected function get_float_if_isset($args, $key){
		$val = $this->get_if_isset($args, $key, "float");
		return $val;
	}
	protected function get_number_if_isset($args, $key){
		$val = $this->get_str_if_isset($args, $key);
		$val = preg_match( "/^(\d+)$/u", mb_convert_kana($val, 'n') ) ? $val : "";
		return $val;
	}
	protected function get_if_isset($args, $key, $fmt=""){
		if( in_array( $fmt, array("number"), true ) ){
			return $this->get_number_if_isset($args, $key);
		}

		$val = ( is_array($args) && isset($args[$key]) ) ? $args[$key] : NULL;
		if( "" !== (string)$fmt ){
			settype($val, $fmt);
		}
		return $val;
	}

	protected function is_list_array($arr){
		$arr = $this->validate_arr($arr);
		return (bool)( array_filter( array_keys($arr), 'is_int' ) );
	}

	protected function recursive_parse_args( $a, $b, $only_isset=false ) {
		$a = (array) $a;
		$b = (array) $b;
		$result = $a;
		foreach ( $b as $k => $v ) {
			if ( $only_isset && !isset($result[$k]) ) continue;

			if ( is_array($v) && isset($result[$k] ) ) {
				$result[$k] = $this->recursive_parse_args( $v, $result[$k], $only_isset );
			} else {
				$result[$k] = $v;
			}
		}
		return $result;
	}

	protected function search_arr_val_deeply($arr, $key){
		$keys = ( !is_string($key) ) 
		? ( is_array($key) ? $key : array($key) ) 
		:  explode('/', $key);
		if( !is_array($keys) || !$keys ) return false;

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
		$arr = array_merge($_arr, $arr);

		return $arr;
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
