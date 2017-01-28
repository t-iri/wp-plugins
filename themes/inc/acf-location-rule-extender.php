<?php 
if( !function_exists('is_admin') || !is_admin() ) return;

/*****************************************************************
 * ACF location
 *****************************************************************/
class myAcfLocationRuleExtender {
	protected $acf_rule_values_hook_prefix = 'acf/location/rule_values/';
	protected $acf_rule_match_hook_prefix = 'acf/location/rule_match/';
	protected $term_parent_rule_types = array(
		'カテゴリページ' => array(
			'my_term_only_self' => '特定ターム（自身のみ）', 
			'my_term_parent_include_self' => '特定親ターム（自身／子孫）', 
			'my_term_parent_children_exclude_self' => '特定親ターム（子のみ）', 
			'my_term_parent_descendants_exclude_self' => '特定親ターム（子孫のみ）', 
		), 
	);

	function __construct(){
		$this->action_filters();
	}
	function action_filters(){
		$rule_types = $this->get_location_rule_types();
		if( $rule_types ) {
			add_filter('acf/location/rule_types', array($this, 'acf_location_rule_types'), 10, 1);
			foreach($rule_types as $prime_key => $rtypes){
				foreach($rtypes as $key => $val){
					$filter_name = $this->acf_rule_values_hook_prefix . $key;
					add_filter($filter_name, array($this, 'acf_location_rule_values'), 10, 1);

					$filter_name = $this->acf_rule_match_hook_prefix . $key;
					add_filter($filter_name, array($this, 'acf_location_rule_match'), 10, 3);
				}
			}
		}
	}

	function get_location_rule_types(){
		$rule_types = $this->term_parent_rule_types;
		return $rule_types;
	}

	function recursive_wp_parse_args( &$a, $b ) {
		$a = (array) $a;
		$b = (array) $b;
		$result = $b;
		foreach ( $a as $k => &$v ) {
			if ( is_array($v) && isset($result[$k] ) ) {
				$result[$k] = $this->recursive_wp_parse_args( $v, $result[ $k ] );
			} else {
				$result[$k] = $v;
			}
		}
		return $result;
	}

	function is_wp_term($obj){
		return (bool)( is_object($obj) && $obj instanceof WP_Term );
	}

	function get_the_current_hook_suffix($hook_prefix){
		$hook_name = current_filter();
		$hook_suffix = '';
		if( strpos($hook_name, $hook_prefix) === 0 ){
			$hook_suffix = str_replace($hook_prefix, '', $hook_name);
		}
		return $hook_suffix;
	}

	function acf_location_rule_types($choices){
		$rule_types = $this->get_location_rule_types();
		$choices = $this->recursive_wp_parse_args($rule_types, $choices);
		return $choices;
	}

	function acf_location_rule_values( $choices ) {
		$hook_suffix = $this->get_the_current_hook_suffix($this->acf_rule_values_hook_prefix);

		$term_parent_rule_types = $this->term_parent_rule_types;
		$term_parent_rule_types = array_shift( $term_parent_rule_types );
		if( isset($term_parent_rule_types[$hook_suffix]) ){
			$choices = acf_get_taxonomy_terms();
			return $choices;
		}

		return $choices;
	}

	function acf_location_rule_match( $match, $rule, $args ){
		$hook_suffix = $this->get_the_current_hook_suffix($this->acf_rule_match_hook_prefix);

		$term_parent_rule_types = $this->term_parent_rule_types;
		$term_parent_rule_types = array_shift( $term_parent_rule_types );
		if( isset($term_parent_rule_types[$hook_suffix]) ){
			$match = $this->is_match_rule_term_parent($match, $rule, $args, $hook_suffix);
		}

		return $match;
	}

	function is_match_rule_term_parent($match, $rule, $args, $hook_suffix){
		$taxonomy = isset($args['taxonomy']) ? (string)$args['taxonomy'] : '';
		$term_id = isset($_REQUEST['tag_ID']) ? (int)$_REQUEST['tag_ID'] : 0;
		if( !$taxonomy || !$term_id ) return $match;

		$selected_value = $rule['value'];
		$term = get_term_by('term_id', $term_id, $taxonomy);
		if( !$this->is_wp_term($term) ) return $match;

		$apply_self = ( preg_match("/_(?:only|include)_self$/u", $hook_suffix) ) ? true : false;
		$apply_only_self = ( preg_match("/_only_self$/u", $hook_suffix) ) ? true : false;
		$apply_only_children = ( preg_match("/_children_/u", $hook_suffix) ) ? true : false;

		$is_hierarchy = NULL;
		$current_value = $taxonomy . ":" . $term->slug;
		if( $current_value === $selected_value ){
			$is_hierarchy = ( $apply_self ) ? true : false;
		}

		if( $term->parent && NULL === $is_hierarchy ){
			$is_hierarchy = false;
			$loop = 0;
			do {
				if( $apply_only_self ) break;

				$term = get_term_by('term_id', $term->parent, $taxonomy);
				$current_value = $taxonomy . ":" . $term->slug;
				if( $current_value === $selected_value ){
					$is_hierarchy = true;
					break;
				}
				if( $apply_only_children && $loop === 0 ) break;

				$loop++;
			} while( $term->parent );
		}

		$selected_operator = $rule['operator'];
		$matches = array(
			'==' => ( $is_hierarchy ) ? true : false, 
			'!=' => ( $is_hierarchy ) ? false : true, 
		);
		if( isset($matches[$selected_operator]) ){
			$match = $matches[$selected_operator];
		}

		return $match;
	}

}

new myAcfLocationRuleExtender();

