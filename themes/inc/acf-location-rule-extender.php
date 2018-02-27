<?php 
/*****************************************************************
 * ACF location
 *****************************************************************/
class MyAcfLocationRuleExtender {
	protected $acf_rule_values_hook_prefix = 'acf/location/rule_values/';
	protected $acf_rule_match_hook_prefix = 'acf/location/rule_match/';
	protected $term_rule_types = array(
		'ターム編集 - ターム指定' => array(
			'my_term_only_self' => '特定ターム（自身のみ）', 
			'my_term_include_self' => '特定親ターム（自身／子孫）', 
			'my_term_only_children' => '特定親ターム（子のみ）', 
			'my_term_only_descendants' => '特定親ターム（子以下全て）', 
		), 
		'ターム編集 - タクソノミ指定' => array(
			'my_taxonomy_level_eq_0' => '親タームのみ', 
			'my_taxonomy_level_eq_1' => '子タームのみ', 
			'my_taxonomy_level_ge_1' => '子ターム以下全て', 
			'my_taxonomy_level_eq_2' => '孫タームのみ', 
			'my_taxonomy_level_ge_2' => '孫ターム以下全て', 
		), 
	);

	public function __construct(){
		$this->action_filters();
	}

	protected function action_filters(){
		$lctn_rule_types = $this->get_location_all_rule_types();
		if( !$lctn_rule_types ) return;

		add_filter('acf/location/rule_types', array($this, 'hook_acf_location_rule_types'), 10, 1);

		$hooks = array(
			$this->acf_rule_values_hook_prefix => array(
				'f' => array($this, 'hook_acf_location_rule_values'), 
				'a' => 1, 
			), 
			$this->acf_rule_match_hook_prefix => array(
				'f' => array($this, 'hook_acf_location_rule_match'), 
				'a' => 3, 
			), 
		);
		foreach( $lctn_rule_types as $key => $val ){
			foreach( $hooks as $prfx => $hook_args ){
				$fltr_name = $prfx . $key;
				add_filter($fltr_name, $hook_args['f'], 10, $hook_args['a']);
			}
		}
	}

/*****************************************************************/

	public function get_location_all_rule_types(){
		return array_merge( array(), $this->get_location_term_rule_types() );
	}

	public function get_location_term_rule_types(){
		return array_reduce( $this->term_rule_types, 'array_merge', array() );
	}

	public function get_location_rule_type_choices(){
		return array_merge( array(), $this->term_rule_types );
	}

/*****************************************************************/

	public function hook_acf_location_rule_types($choices){
		$rule_type_choices = $this->get_location_rule_type_choices();
		return $this->recursive_wp_parse_args($rule_type_choices, $choices);
	}

/*****************************************************************/

	public function hook_acf_location_rule_values($choices){
		$sffx = $this->get_the_current_hook_suffix( $this->acf_rule_values_hook_prefix );
		$lctn_rule_types = $this->get_location_all_rule_types();
		if( !isset($lctn_rule_types[$sffx]) ) return $choices;

		if( !preg_match("/^my_(term|taxonomy)/u", $sffx, $mt) ) return $choices;

		if( 'term' === $mt[1] ){

			if( $this->is_acf_pro_running() ){

				$choices = acf_get_taxonomy_terms();
				if( isset($choices['post_format']) ) unset($choices['post_format']);

			} else {

				$simple_value = true;
				$choices = apply_filters('acf/get_taxonomies_for_select', $choices, $simple_value);

			}

		} elseif( 'taxonomy' === $mt[1] ){

			$choices = array( 'all' => __('All', 'acf') );
			if( $this->is_acf_pro_running() ){

				$choices = array_merge( $choices, acf_get_taxonomies() );

			} else {

				$taxonomies = get_taxonomies( array('public' => true), 'objects' );
				foreach( $taxonomies as $taxonomy ){
					$choices[$taxonomy->name] = $taxonomy->labels->name;
				}

			}
			if( isset($choices['post_format']) ) unset($choices['post_format']);

		}
		return $choices;
	}

/*****************************************************************/

	public function hook_acf_location_rule_match($match, $rule, $args){
		$sffx = $this->get_the_current_hook_suffix( $this->acf_rule_match_hook_prefix );

		$lctn_rule_types = $this->get_location_all_rule_types();
		if( !isset($lctn_rule_types[$sffx]) ) return $match;

		$term_rule_types = $this->get_location_term_rule_types();
		if( isset($term_rule_types[$sffx]) ){
			$match = $this->get_matched_the_term_rule($match, $rule, $args, $sffx);
		}
		return $match;
	}

	public function get_matched_the_term_rule($match, $rule, $args, $sffx){
		$txnmy = '';
		if( isset($args['taxonomy']) ) $txnmy = $args['taxonomy'];
		if( !$txnmy && isset($args['ef_taxonomy']) ) $txnmy = $args['ef_taxonomy'];
		$txnmy = is_array($txnmy) ? array_unshift($txnmy) : $txnmy;

		$trm_id = isset($_REQUEST['tag_ID']) ? (int)$_REQUEST['tag_ID'] : 0;
		if( !$txnmy || !$trm_id ) return $match;

		$trm_objct = get_term($trm_id, $txnmy);
		if( !$this->is_wp_term($trm_objct) ) return $match;

		$is_in_hierarchy = $this->is_in_taxonomy_term_hierarchy($rule, $sffx, $trm_objct);

		$results = array(
			'==' => ( $is_in_hierarchy ) ? true : false, 
			'!=' => ( $is_in_hierarchy ) ? false : true, 
		);
		$selected_oprtr = $rule['operator'];
		if( isset($results[$selected_oprtr]) ){
			$match = $results[$selected_oprtr];
		}
		return $match;
	}

	protected function is_in_taxonomy_term_hierarchy($rule, $sffx, $trm_objct){
		$is_in_hierarchy = NULL;
		$selected_val = (string)$rule['value'];

		if( preg_match("/taxonomy_level_(eq|ge|le)_(\d+)/u", $sffx, $mt) ){
			$is_in_hierarchy = false;
			$crrnt_val = $this->make_acf_tax_option_rule_value($trm_objct);
			if( $crrnt_val === $selected_val || 'all' === $selected_val ){
				$crrnt_term_lvl = $this->get_the_term_level($trm_objct);
				$rule_term_lvl = (int)$mt[2];
				switch( $mt[1] ){
					case 'eq':
						if( $rule_term_lvl === $crrnt_term_lvl ) $is_in_hierarchy = true;
						break;

					case 'ge':
						if( $rule_term_lvl <= $crrnt_term_lvl ) $is_in_hierarchy = true;
						break;
				}
			}
			return $is_in_hierarchy;
		}

		$crrnt_val = $this->make_acf_term_option_rule_value($trm_objct);
		if( $crrnt_val === $selected_val ){
			$is_in_hierarchy = preg_match("/_(?:only|include)_self$/u", $sffx) ? true : false;
		} else {
			if( preg_match("/_only_self$/u", $sffx) ) $is_in_hierarchy = false;
		}

		if( $trm_objct->parent && NULL === $is_in_hierarchy ){
			$is_in_hierarchy = false;
			$apply_only_children = preg_match("/_only_children$/u", $sffx) ? true : false;
			$loop = 0;
			do {
				$trm_objct = get_term($trm_objct->parent, $trm_objct->taxonomy);
				$crrnt_val = $this->make_acf_term_option_rule_value($trm_objct);
				if( $crrnt_val === $selected_val ){
					$is_in_hierarchy = true;
					break;
				}
				if( $apply_only_children && $loop === 0 ) break;

				$loop++;
			} while( $trm_objct->parent );
		}
		return $is_in_hierarchy;
	}

/*****************************************************************/

	protected function get_the_current_hook_suffix($prfx){
		$hook_name = current_filter();
		$sffx = '';
		if( strpos($hook_name, $prfx) === 0 ){
			$sffx = str_replace($prfx, '', $hook_name);
		}
		return $sffx;
	}

/*****************************************************************/

	protected function make_acf_term_option_rule_value($trm_objct){
		$opt_val = (string)$trm_objct->term_id;
		if( $this->is_acf_pro_running() ){
			$opt_val = $trm_objct->taxonomy . ':' . $trm_objct->slug;
		}
		return $opt_val;
	}

	protected function make_acf_tax_option_rule_value($trm_objct){
		return (string)$trm_objct->taxonomy;
	}

/*****************************************************************/

	protected function is_acf_pro_running(){
		return class_exists('acf_form_post') ? true : false; //acf pro
	}

	protected function recursive_wp_parse_args(&$a, $b){
		$a = (array)$a;
		$b = (array)$b;
		$result = $b;
		foreach ( $a as $k => &$v ){
			if ( is_array($v) && isset($result[$k] ) ) {
				$result[$k] = $this->recursive_wp_parse_args( $v, $result[ $k ] );
			} else {
				$result[$k] = $v;
			}
		}
		return $result;
	}

	protected function is_wp_term($obj){
		return (bool)( is_object($obj) && $obj instanceof WP_Term );
	}

	protected function get_the_term_level($trm_objct){
		$ancstrs = get_ancestors($trm_objct->term_id, $trm_objct->taxonomy, 'taxonomy');
		return count($ancstrs);
	}

}
