<?php 
namespace Mylib\Base;

require_once( dirname(__FILE__) . '/abstract-base-functions.php' );
if( !class_exists('\Mylib\Base\Functions') ) return;

abstract class FunctionsWP extends Functions {
/*************** WP Conditional ***************/
	protected function is_wp_term($obj){
		return ( is_object($obj) && $obj instanceof \WP_Term ) ? true : false;
	}

	protected function is_wp_query($obj){
		return ( is_object($obj) && $obj instanceof \WP_Query ) ? true : false;
	}

	protected function is_wp_post($obj){
		return ( is_object($obj) && $obj instanceof \WP_Post ) ? true : false;
	}

	protected function is_catchable_wp_global_post(){
		global $post;
		return $this->is_wp_post($post);
	}

	protected function is_any_term_archive($q=NULL){
		$is_any_term_archive = (
			$this->is_the_tax_archive('category', $q) 
			|| $this->is_the_tax_archive('post_tag', $q) 
			|| $this->is_the_tax_archive('', $q) 
		) ? true : false;
		return $is_any_term_archive;
	}

	protected function is_the_tax_archive($tax, $q=NULL){
		if( $this->is_wp_query($q) ){
			$is_tax = ( $tax !== 'category' ) ? ( ( $tax !== 'post_tag' ) ? $q->is_tax($tax) : $q->is_tag() ) : $q->is_category();
		} else {
			$is_tax = ( $tax !== 'category' ) ? ( ( $tax !== 'post_tag' ) ? is_tax($tax) : is_tag() ) : is_category();
		}
		return (bool)$is_tax;
	}

	protected function is_the_term_archive($tax, $term, $q=NULL){
		if( $this->is_wp_query($q) ){
			$is_term = ( $tax !== 'category' ) ? ( ( $tax !== 'post_tag' ) ? $q->is_tax($tax, $term) : $q->is_tag($term) ) : $q->is_category($term);
		} else {
			$is_term = ( $tax !== 'category' ) ? ( ( $tax !== 'post_tag' ) ? is_tax($tax, $term) : is_tag($term) ) : is_category($term);
		}
		return (bool)$is_term;
	}

	protected function term_is_self_or_ancestor_of($base_term, $check_term, $taxonomy){
		$base_term = get_term($base_term, $taxonomy);
		$check_term = get_term($check_term, $taxonomy);
		if( !$this->is_wp_term($base_term) || !$this->is_wp_term($check_term) ) return false;

		$bool = ( 
			( $base_term->term_id === $check_term->term_id ) 
			|| ( term_is_ancestor_of($base_term, $check_term, $taxonomy) ) 
		) ? true : false;

		return $bool;
	}

/*************** WP Objects ***************/
	protected function get_wp_post_object($post=NULL){
		if( is_numeric($post) ) $post = get_post($post);
		if( $post === NULL ) global $post;
		return $post;
	}

	protected function get_wp_post_id($post=NULL){
		$post = $this->get_wp_post_object($post);
		$post_id = !is_numeric($post) ? ( $this->is_wp_post($post) ? $post->ID : 0 ) : $post;
		return $post_id;
	}

	protected function get_wp_term_by_slug($txnmy, $slug, $key=""){
		$term = get_term_by('slug', $slug, $txnmy);
		$rtn = NULL;
		if( $this->is_wp_term($term) ){
			$key = (string)$key;
			$rtn = ( "" === $key ) ? $term : $this->get_the_object_property($term, $key);
		}
		return $rtn;
	}

	protected function get_wp_term_link_by_slug($txnmy, $slug){
		return get_term_link( $this->get_wp_term_by_slug($txnmy, $slug) );
	}

	protected function get_wp_terms_listed_properties($txnmy, $prpty="name", $args=array()){
		$properties = array();

		$defaults = array(
			"taxonomy" => $txnmy, 
			"hide_empty" => false, 
		);
		$args = array_merge($defaults, $args);

		$terms = get_terms($args);
		if( $terms && !is_wp_error($terms) ){
			$properties = $this->get_the_object_list_properties($terms, $prpty);
		}
		return $properties;
	}

	protected function get_wp_terms_hashed_properties($txnmy, $key="term_id", $val="name", $args=array()){
		$properties = array();
		$prop_keys = $this->get_wp_terms_listed_properties($txnmy, $key, $args);
		$prop_vals = $this->get_wp_terms_listed_properties($txnmy, $val, $args);
		if( $prop_keys && ( count($prop_keys) === count($prop_vals) ) ){
			$properties = array_combine($prop_keys, $prop_vals);
		}
		return $properties;
	}

	protected function get_wp_post_terms_properties($txnmy, $prpty="name", $post=NULL){
		$properties = array();
		$post = $this->get_wp_post_object($post);
		if( $this->is_wp_post($post) ){
			$terms = get_the_terms($post->ID, $txnmy);
			if( $terms && !is_wp_error($terms) ){
				$properties = $this->get_the_object_list_properties($terms, $prpty);
			}
		}
		return $properties;
	}

	protected function get_wp_current_post_type_object($q=NULL){
		$obj = NULL;
		if( $this->is_wp_query($q) && $q->is_post_type_archive() && !$q->is_tax() ){
			$obj = $q->get_queried_object();
		} elseif( is_post_type_archive() && !is_tax() ) {
			$obj = get_queried_object();
		}
		return $obj;
	}

/*************** Tree Root Taxonomy ***************/
	protected function get_wp_term_tree($txnmy, $terms){
		$tree = array();
		if( !$terms || is_wp_error($terms) || !taxonomy_exists($txnmy) ) return $tree;

		foreach($terms as $term){
			$term = $this->is_wp_term($term) ? $term : get_term_by('term_id', $term, $txnmy);
			if( !$this->is_wp_term($term) ) continue;

			$ancestors = get_ancestors($term->term_id, $txnmy);
			if( $ancestors ){
				$ancestors = array_reverse($ancestors);
			}
			$ancestors[] = $term->term_id;

			$tree = $this->recursive_wp_term_tree($ancestors, $tree);
		}
		return $tree;
	}

	protected function get_wp_post_term_tree($txnmy, $post=NULL){
		$post_id = $this->get_wp_post_id($post);
		$terms = get_the_terms($post_id, $txnmy);
		$tree = $this->get_wp_term_tree($txnmy, $terms);
		return $tree;
	}

	protected function root_wp_term_flatten_tree($txnmy, $terms, $depth=NULL){
		$term_tree = $this->get_wp_term_tree($txnmy, $terms);
		return $this->make_wp_term_flatten_tree($term_tree, $depth);
	}

	protected function get_wp_term_flatten_tree($txnmy, $terms){
		return $this->root_wp_term_flatten_tree($txnmy, $terms);
	}

	protected function get_wp_term_flatten_tree_having_depth($txnmy, $terms){
		return $this->root_wp_term_flatten_tree($txnmy, $terms, 0);
	}

	protected function root_wp_post_term_flatten_tree($txnmy, $post=NULL, $depth=NULL){
		$term_tree = $this->get_wp_post_term_tree($txnmy, $post);
		return $this->make_wp_term_flatten_tree($term_tree, $depth);
	}

	protected function get_wp_post_term_flatten_tree($txnmy, $post=NULL){
		return $this->root_wp_post_term_flatten_tree($txnmy, $post);
	}

	protected function get_wp_post_term_flatten_tree_having_depth($txnmy, $post=NULL){
		return $this->root_wp_post_term_flatten_tree($txnmy, $post, 0);
	}

	protected function get_wp_term_leveled_tree($txnmy, $terms){
		$depth_tree = $this->get_wp_term_flatten_tree_having_depth($txnmy, $terms);
		return $this->make_wp_term_leveled_tree($depth_tree);
	}

	protected function get_wp_post_term_leveled_tree($txnmy, $post=NULL){
		$depth_tree = $this->get_wp_post_term_flatten_tree_having_depth($txnmy, $post);
		return $this->make_wp_term_leveled_tree($depth_tree);
	}

	protected function make_wp_term_leveled_tree($depth_tree){
		$leveled_tree = array();
		if( !$depth_tree ) return $leveled_tree;

		foreach( $depth_tree as $term_id => $dpth ){
			$leveled_tree[$dpth][] = $term_id;
		}
		ksort($leveled_tree);
		return $leveled_tree;
	}

	/* --- Tree Recursive --- */
	protected function recursive_wp_term_tree($ancestors, $tree=array()){
		$term_id = array_shift($ancestors);
		$exist_tree = isset($tree[$term_id]) ? $tree[$term_id] : array();

		if($ancestors){
			$_tree = $this->recursive_wp_term_tree($ancestors, $exist_tree);
			$exist_tree = $_tree;
		}

		$tree[$term_id] = $exist_tree;

		return $tree;
	}

	protected function make_wp_term_flatten_tree($tree, $depth=NULL){
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
				$_flat = $this->make_wp_term_flatten_tree($branch, $arg_depth);
				$flat = ( $having_depth ) ? $flat + $_flat : array_merge( $flat, $_flat );
			}
		}

		return $flat;
	}

/*************** Others ***************/
	protected function exec_wp_safe_redirect($red_to){
		wp_safe_redirect($red_to);
		exit;
	}

}
