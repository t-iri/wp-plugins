<?php 
/*****************************************************************
 * Func
 *****************************************************************/
/****************** ¤Conditional prefix is_* *******************/
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
		$req = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : "";
		return $usces->is_cart_page($req) ? true : false;
	}
}

if( !function_exists('is_usces_member_page') ){
	function is_usces_member_page(){
		global $usces;
		$req = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : "";
		return $usces->is_member_page($req) ? true : false;
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
/****************** ¤Object *******************/
function my_call_the_object_method($obj, $func, $args=array()){
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
function my_get_the_object_property($obj, $key){
	$property = ( is_object($obj) && property_exists($obj, $key) ) ? $obj->$key : NULL;
	return $property;
}
function my_get_the_global_usces_property($key){
	global $usces;
	$property = my_get_the_object_property($usces, $key);
	return $property;
}

/****************** ¤General Purpose *******************/
function my_check_conditions($bools, $relate="AND"){
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
	$res = false;
	if( function_exists($func) ){
		if( $args ){
			$args = (!is_array($args)) ? array($args) : $args;
			$res = call_user_func_array($func, $args);
		} else {
			$res = call_user_func($func);
		}
	}
	return $res;
}

/****************** ¤WP *******************/
function my_get_post_id($post=NULL){
	if( $post === NULL ) global $post;
	$post_id = !is_numeric($post) ? ( ($post instanceof WP_Post) ? $post->ID : 0 ) : $post;
	return $post_id;
}

function my_get_term_by_slug($taxonomy, $slug, $key=""){
	$term = get_term_by('slug', $slug, $taxonomy);
	$rtn = NULL;
	if( $term instanceof WP_Term ){
		$key =(string)$key;
		$rtn = ( "" === $key ) ? $term : my_get_the_object_property($term, $key);
	}
	return $rtn;
}

function my_get_term_link_by_slug($taxonomy, $slug){
	$term_id = my_get_term_by_slug($taxonomy, $slug, 'term_id');
	$term_link = get_term_link($term_id, $taxonomy);
	return $term_link;
}

function my_get_category_by_slug($slug, $key=""){
	$rtn = my_get_term_by_slug('category', $slug, $key);
	return $rtn;
}

function my_get_category_link_by_slug($slug){
	$cat_link = my_get_term_link_by_slug('category', $slug);
	return $cat_link;
}

function my_safe_add_filter($name, $func='', $priority=10, $argnum=1){
	if( is_array($name) ) extract($name, EXTR_OVERWRITE);

	if( !has_filter($name, $func) ){
		add_filter($name, $func, $priority, $argnum);
	}
}
function my_safe_remove_filter($name, $func='', $priority=10){
	if( is_array($name) ) extract($name, EXTR_OVERWRITE);

	if( has_filter($name, $func) ){
		remove_filter($name, $func, $priority);
	}
}

function my_get_terms_property($taxonomy, $prpty="name", $args=array()){
	$terms_property = array();

	$defaults = array(
		"taxonomy" => $taxonomy, 
		"hide_empty" => false, 
	);
	$args = array_merge($defaults, $args);

	$terms = get_terms($args);
	if( $terms && !is_wp_error($terms) ){
		$args = array_fill(0, count($terms), $prpty);
		$terms_property = array_map("my_get_the_object_property", $terms, $args);
	}
	return $terms_property;
}

function my_get_terms_properties($taxonomy, $key="term_id", $val="name", $args=array()){
	$properties = array();
	$prop_keys = my_get_terms_property($taxonomy, $key, $args);
	$prop_vals = my_get_terms_property($taxonomy, $val, $args);
	if( $prop_keys && ( count($prop_keys) === count($prop_vals) ) ){
		$properties = array_combine($prop_keys, $prop_vals);
	}
	return $properties;
}

function my_get_the_post_terms_property($taxonomy, $prpty="name", $post=NULL){
	$terms_property = array();
	if( is_numeric($post) ) $post = get_post($post);
	if( $post === NULL ) global $post;

	if( $post instanceof WP_Post ){
		$terms = get_the_terms($post->ID, $taxonomy);
		if( $terms && !is_wp_error($terms) ){
			$args = array_fill(0, count($terms), $prpty);
			$terms_property = array_map("my_get_the_object_property", $terms, $args);
		}
	}
	return $terms_property;
}

/****************** ¤No Trait but useful function *******************/
/**
 * Get size information for all currently-registered image sizes.
 *
 * @global $_wp_additional_image_sizes
 * @uses   get_intermediate_image_sizes()
 * @return array $sizes Data for all currently-registered image sizes.
 */
function my_get_wp_image_sizes() {
	global $_wp_additional_image_sizes;

	$sizes = array();

	foreach ( get_intermediate_image_sizes() as $_size ) {
		if ( in_array( $_size, array('thumbnail', 'medium', 'medium_large', 'large') ) ) {
			$sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
			$sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
			$sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );
		} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
			$sizes[ $_size ] = array(
				'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
				'height' => $_wp_additional_image_sizes[ $_size ]['height'],
				'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
			);
		}
	}

	return $sizes;
}

/**
 * Get size information for a specific image size.
 *
 * @uses   my_get_wp_image_sizes()
 * @param  string $size The image size for which to retrieve data.
 * @return bool|array $size Size data about an image size or false if the size doesn't exist.
 */
function my_get_wp_image_size( $size ) {
	$sizes = my_get_wp_image_sizes();

	if ( isset( $sizes[ $size ] ) ) {
		return $sizes[ $size ];
	}

	return false;
}

/**
 * Get the width of a specific image size.
 *
 * @uses   my_get_wp_image_size()
 * @param  string $size The image size for which to retrieve data.
 * @return bool|string $size Width of an image size or false if the size doesn't exist.
 */
function my_get_wp_image_w( $size ) {
	if ( ! $size = my_get_wp_image_size( $size ) ) {
		return false;
	}

	if ( isset( $size['width'] ) ) {
		return $size['width'];
	}

	return false;
}

/**
 * Get the height of a specific image size.
 *
 * @uses   my_get_wp_image_size()
 * @param  string $size The image size for which to retrieve data.
 * @return bool|string $size Height of an image size or false if the size doesn't exist.
 */
function my_get_wp_image_h( $size ) {
	if ( ! $size = my_get_wp_image_size( $size ) ) {
		return false;
	}

	if ( isset( $size['height'] ) ) {
		return $size['height'];
	}

	return false;
}

/**
 * Parse the args with recursive
 *
 * @uses   self
 * @param  array $a parsing to
 * @param  array $b parsed base
 * @return array $result 
 */
function my_recursive_wp_parse_args( &$a, $b ) {
	$a = (array) $a;
	$b = (array) $b;
	$result = $b;
	foreach ( $a as $k => &$v ) {
		if ( is_array($v) && isset($result[$k] ) ) {
			$result[$k] = my_recursive_wp_parse_args( $v, $result[ $k ] );
		} else {
			$result[$k] = $v;
		}
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
function my_get_term_tree($taxonomy, $terms){
	$tree = array();
	if( !$terms || is_wp_error($terms) || !taxonomy_exists($taxonomy) ) return $tree;

	foreach($terms as $term){
		$ancestors = get_ancestors($term->term_id, $taxonomy);
		if( $ancestors ){
			$ancestors = array_reverse($ancestors);
		}
		$ancestors[] = $term->term_id;

		$tree = my_recursive_get_term_tree($ancestors, $tree);
	}
	return $tree;
}
function my_get_the_term_tree($taxonomy, $post=NULL){
	$post_id = my_get_post_id($post);
	$terms = get_the_terms($post_id, $taxonomy);
	$tree = my_get_term_tree($taxonomy, $terms);
	return $tree;
}
function my_get_the_term_tree_flatten($taxonomy, $post=NULL, $depth=false){
	$tree = my_get_the_term_tree($taxonomy, $post);
	$depth = ( $depth ) ? 0 : NULL;
	$flat = my_make_term_tree_flatten($tree, $depth);
	return $flat;
}
function my_get_the_term_tree_flatten_having_depth($taxonomy, $post=NULL){
	return my_get_the_term_tree_flatten($taxonomy, $post, true);
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

	foreach($tree as $term_id => $branch){
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
	return my_get_term_tree("category", $terms);
}
function my_get_the_cat_tree($post=NULL){
	return my_get_the_term_tree("category", $post);
}
function my_get_the_cat_tree_flatten($post=NULL, $depth=false){
	return my_get_the_term_tree_flatten("category", $post, $depth);
}
function my_get_the_cat_tree_flatten_having_depth($post=NULL){
	return my_get_the_term_tree_flatten_having_depth("category", $post);
}

/*****************************************************************
 * Trait / usable ge PHP 5.4
 *****************************************************************/
trait myFuncsAll {
	use myFuncsCondition, myFuncsObject, myFuncsGeneral, myFuncsWp;
}

trait myFuncsCondition {
	public function is_usces_single_item(){
		return is_usces_single_item();
	}
}

trait myFuncsObject {
	public function mf_call_obj_method($obj, $func, $args=array()){
		return my_call_the_object_method($obj, $func, $args);
	}
	public function mf_get_obj_prop($obj, $key){
		return my_get_the_object_property($obj, $key);
	}
	public function mf_get_usces_prop($key){
		return my_get_the_global_usces_property($key);
	}
}

trait myFuncsGeneral {
	public function mf_check_conditions($bools, $relate="AND"){
		return my_check_conditions($bools, $relate);
	}
	public function mf_safe_call_func($func, $args=array()){
		return my_safe_call_func($func, $args);
	}
}

trait myFuncsWp {
	public function mf_get_post_id($post=NULL){
		return my_get_post_id($post);
	}
	public function mf_get_category_by_slug($slug, $key=""){
		return my_get_category_by_slug($slug, $key);
	}
	public function mf_safe_add_filter($name, $func='', $priority=10, $argnum=1){
		return my_safe_add_filter($name, $func, $priority, $argnum);
	}
	public function mf_safe_remove_filter($name, $func='', $priority=10){
		return my_safe_remove_filter($name, $func, $priority);
	}
}

