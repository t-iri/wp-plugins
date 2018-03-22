<?php 
/*****************************************************************
 * Func
 *****************************************************************/
/****************** ▽Conditional prefix is_* *******************/
if( !function_exists('is_wp_user_eq') ){
	function is_wp_user_eq($user, $key, $val){
		$user = is_numeric($user) ? get_user_by('ID', $user) : $user;
		if( !is_object($user) || !( $user instanceof WP_User ) || !$user->ID ) return false;

		if( 'roles' === $key ){
			$roles = is_array($user->roles) ? $user->roles : array();
			return ( $roles && in_array($val, $roles, true) ) ? true : false;
		}

		if( isset($user->$key) ){
			return ( $val === $user->$key ) ? true : false;
		}

		return false;
	}

	if( !function_exists('is_wp_current_user_eq') ){
		function is_wp_current_user_eq($key, $val){
			return is_wp_user_eq( wp_get_current_user(), $key, $val );
		}
	}
}

if( !function_exists('is_acf_pro_running') ){
	function is_acf_pro_running(){
		return class_exists('acf_pro') ? true : false;
	}
}

if( !function_exists('is_usces_single_item') && function_exists('usces_is_item') ){
	function is_usces_single_item(){
		return ( is_single() && usces_is_item() ) ? true : false;
	}
}

if( !function_exists('is_usces_cart_page') ){
	function is_usces_cart_page(){
		global $usces;
		$req = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
		return $usces->is_cart_page($req) ? true : false;
	}
}

if( !function_exists('is_usces_member_page') ){
	function is_usces_member_page(){
		global $usces;
		$req = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
		return $usces->is_member_page($req) ? true : false;
	}
}

if( !function_exists('is_usces_item_post') ){
	function is_usces_item_post($post){
		if( is_object($post) && $post instanceof WP_Post ){
			if( $post->post_type === 'post' && $post->post_mime_type === 'item' ){
				return true;
			}
		}
		return false;
	}
}

if( !function_exists('is_page_for_posts') ){
//post_type => post's archive / whether set on wp admin panel setting
	function is_page_for_posts(){
		$is_page_for_posts = false;
		if( is_home() ){
			$page_for_posts = (int)get_option('page_for_posts');
			$obj = get_queried_object();
			if( $page_for_posts && $page_for_posts === (int)$obj->ID ){
				$is_page_for_posts = true;
			}
		}
		return $is_page_for_posts;
	}
}

if( !function_exists('is_any_term_archive') ){
	function is_any_term_archive(){
		$is_any_term_archive = (
			is_category() 
			|| is_tag() 
			|| is_tax() //is_tax() returns false on category archives and tag archives
		) ? true : false;
		return $is_any_term_archive;
	}
}

/****************** ▽Object *******************/
function my_call_the_object_method($obj, $func, $args=array()){
	if( is_object($obj) && method_exists($obj, $func) ){
		if( $args ){
			if( !is_array($args) ) $args = array($args);
			return call_user_func_array(array($obj, $func), $args);
		}
		return call_user_func(array($obj, $func));
	}
	return false;
}

function my_get_the_object_property($obj, $key){
	return ( is_object($obj) && property_exists($obj, $key) ) ? $obj->$key : NULL;
}

function my_get_the_global_usces_property($key){
	global $usces;
	return my_get_the_object_property($usces, $key);
}

/****************** ▽General Purpose *******************/
function my_check_conditions($bools, $relate='AND'){
	$bool = false;
	$bools = is_array($bools) ? array_map('boolval', $bools) : array();
	if( $bools ){
		switch($relate){
			case 'AND':
				$bool = !in_array(false, $bools, true) ? true : false;
				break;
			case 'OR':
				$bool = in_array(true, $bools, true) ? true : false;
				break;
		}
	}
	return $bool;
}

function my_safe_call_func($func, $args=array()){
	if( function_exists($func) ){
		if( $args ){
			if( !is_array($args) ) $args = array($args);
			return call_user_func_array($func, $args);
		}
		return call_user_func($func);
	}
	return false;
}

function my_search_arr_val_deeply($arr, $key){
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

/****************** ▽WP *******************/
function my_get_post_id($post=NULL){
	if( $post === NULL ) global $post;
	return !is_numeric($post) ? ( ($post instanceof WP_Post) ? $post->ID : 0 ) : $post;
}

function my_get_term_by_slug($txnmy, $slug, $key=''){
	$term = get_term_by('slug', $slug, $txnmy);
	$rtn = NULL;
	if( $term instanceof WP_Term ){
		$key =(string)$key;
		$rtn = ( '' === $key ) ? $term : my_get_the_object_property($term, $key);
	}
	return $rtn;
}

function my_get_term_link_by_slug($txnmy, $slug){
	$term_id = my_get_term_by_slug($txnmy, $slug, 'term_id');
	return get_term_link($term_id, $txnmy);
}

function my_get_category_by_slug($slug, $key=''){
	return my_get_term_by_slug('category', $slug, $key);
}

function my_get_category_link_by_slug($slug){
	return my_get_term_link_by_slug('category', $slug);
}

function my_safe_add_filter($name, $func='', $priority=10, $argnum=1){
	if( is_array($name) ) extract($name, EXTR_OVERWRITE);
	if( !has_filter($name, $func) ) add_filter($name, $func, $priority, $argnum);
}

function my_safe_remove_filter($name, $func='', $priority=10){
	if( is_array($name) ) extract($name, EXTR_OVERWRITE);
	if( has_filter($name, $func) ) remove_filter($name, $func, $priority);
}

function my_get_terms_property($txnmy, $prpty='name', $args=array()){
	$terms_property = array();

	$defaults = array(
		'taxonomy' => $txnmy, 
		'hide_empty' => false, 
	);
	$args = array_merge($defaults, $args);

	global $wp_version;
	$terms = version_compare($wp_version, '4.5', '>=') ? get_terms($args) : get_terms($txnmy, $args);
	if( $terms && !is_wp_error($terms) ){
		$args = array_fill(0, count($terms), $prpty);
		$terms_property = array_map('my_get_the_object_property', $terms, $args);
	}
	return $terms_property;
}

function my_get_terms_properties($txnmy, $key='term_id', $val='name', $args=array()){
	$properties = array();
	$prop_keys = my_get_terms_property($txnmy, $key, $args);
	$prop_vals = my_get_terms_property($txnmy, $val, $args);
	if( $prop_keys && ( count($prop_keys) === count($prop_vals) ) ){
		$properties = array_combine($prop_keys, $prop_vals);
	}
	return $properties;
}

function my_get_the_post_terms_property($txnmy, $prpty='name', $post=NULL){
	$terms_property = array();
	if( is_numeric($post) ) $post = get_post($post);
	if( $post === NULL ) global $post;

	if( $post instanceof WP_Post ){
		$terms = get_the_terms($post->ID, $txnmy);
		if( $terms && !is_wp_error($terms) ){
			$args = array_fill(0, count($terms), $prpty);
			$terms_property = array_map('my_get_the_object_property', $terms, $args);
		}
	}
	return $terms_property;
}

function my_remove_wp_filter_from_current($tgt_func){
	$crrnt_filter = current_filter();
	$priority = ( $crrnt_filter ) ? has_filter($crrnt_filter, $tgt_func) : false;
	return ( $priority ) ? remove_filter($crrnt_filter, $tgt_func, $priority) : false;
}

function my_get_wp_object_ancestors($objct){
	$ancstrs = array();
	if( !is_object($objct) ) return;

	$object_id = 0;
	$object_type = '';
	$resource_type = '';
	if( $objct instanceof WP_Post ){
		$object_id = $objct->ID;
		$object_type = $objct->post_type;
		$resource_type = 'post_type';
	}
	if( $objct instanceof WP_Term ){
		$object_id = $objct->term_id;
		$object_type = $objct->taxonomy;
		$resource_type = 'taxonomy';
	}

	if( $object_id && $object_type && $resource_type ){
		$ancstrs = get_ancestors($object_id, $object_type, $resource_type);
	}
	return $ancstrs;
}

function my_get_current_wp_user_role(){
	$user = wp_get_current_user();
	return $user->roles ? $user->roles[0] : '';
}

function my_get_wp_user_id($usr_id_or_obj){
	$usr_id = 0;
	$usr_obj = is_numeric($usr_id_or_obj) ? get_userdata($usr_id_or_obj) : $usr_id_or_obj;
	if( !is_object($usr_obj) ) return $usr_id;

	if( ( $usr_obj instanceof WP_User || $usr_obj instanceof stdClass ) && $usr_obj->ID ){
		$usr_id = $usr_obj->ID;
	}
	return $usr_id;
}

/****************** ▽For WP useful function *******************/
function my_recursive_parse_args( $def, $add, $only_isset=false ) {
	$result = $def;
	if( !is_array($def) || !is_array($add) ) return $result;

	foreach( $add as $k => $v ){
		if( $only_isset && !isset($result[$k]) ) continue;

		if( is_array($v) && ( isset($result[$k]) && is_array($result[$k]) ) ){
			$v = call_user_func( __FUNCTION__, $result[$k], $v, $only_isset );
		}
		$result[$k] = $v;
	}
	return $result;
}

function my_get_the_all_terms($post=NULL){
	$all_terms = array();
	$post_id = my_get_post_id($post);
	if( !$post_id ) return $all_terms;

	$post = get_post($post_id);
	$post_type = $post->post_type;
	$taxonomies = get_object_taxonomies($post_type, 'objects');
	if( !$taxonomies ) return $all_terms;

	foreach($taxonomies as $tax => $obj){
		$terms = get_the_terms($post_id, $tax);
		if( !$terms || is_wp_error($terms) ) continue;

		$all_terms[$tax] = $terms;
	}
	return $all_terms;
}

/* --- Tree Root Taxonomy --- */
function my_get_term_tree($txnmy, $terms){
	$tree = array();
	if( !$terms || is_wp_error($terms) || !taxonomy_exists($txnmy) ) return $tree;

	foreach($terms as $term){
		$ancestors = get_ancestors($term->term_id, $txnmy, 'taxonomy');
		if( $ancestors ){
			$ancestors = array_reverse($ancestors);
		}
		$ancestors[] = $term->term_id;

		$tree = my_recursive_get_term_tree($ancestors, $tree);
	}
	return $tree;
}

function my_get_the_term_tree($txnmy, $post=NULL){
	$post_id = my_get_post_id($post);
	$terms = get_the_terms($post_id, $txnmy);
	return my_get_term_tree($txnmy, $terms);
}

function my_get_the_term_tree_flatten($txnmy, $post=NULL, $depth=false){
	$tree = my_get_the_term_tree($txnmy, $post);
	$depth = ( $depth ) ? 0 : NULL;
	return my_make_term_tree_flatten($tree, $depth);
}

function my_get_the_term_tree_flatten_having_depth($txnmy, $post=NULL){
	return my_get_the_term_tree_flatten($txnmy, $post, true);
}

/* --- Tree Recursive --- */
function my_recursive_get_term_tree($ancestors, $tree=array()){
	$term_id = array_shift($ancestors);
	$exist_tree = isset($tree[$term_id]) ? $tree[$term_id] : array();

	if($ancestors){
		$_tree = my_recursive_get_term_tree($ancestors, $exist_tree);
		$exist_tree = $_tree;
	}
	$tree[$term_id] = $exist_tree;
	return $tree;
}

function my_make_term_tree_flatten($tree, $depth=NULL){
	$flat = array();
	if( !$tree ) return $flat;

	$having_depth = is_numeric($depth) ? true : false;

	foreach( $tree as $term_id => $branch ){
		if( $having_depth ){
			$flat[$term_id] = $depth;
		} else {
			$flat[] = $term_id;
		}

		if( is_array($branch) && $branch ){
			$arg_depth = ( $having_depth ) ? $depth + 1 : $depth;
			$_flat = my_make_term_tree_flatten($branch, $arg_depth);
			$flat = ( $having_depth ) ? $flat + $_flat : array_merge( $flat, $_flat );
		}
	}
	return $flat;
}

/*** Tree Alias Category ***/
function my_get_cat_tree($terms){
	return my_get_term_tree('category', $terms);
}

function my_get_the_cat_tree($post=NULL){
	return my_get_the_term_tree('category', $post);
}

function my_get_the_cat_tree_flatten($post=NULL, $depth=false){
	return my_get_the_term_tree_flatten('category', $post, $depth);
}

function my_get_the_cat_tree_flatten_having_depth($post=NULL){
	return my_get_the_term_tree_flatten_having_depth('category', $post);
}
