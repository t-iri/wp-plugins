<?php 
require_once( dirname(__FILE__) . '/abstract-base-functions-wp.php' );
if( !class_exists('MyBaseFunctionsWP') ) return;

abstract class MyArchiveModel extends MyBaseFunctionsWP {
	protected $search_post_types = array('post');
	protected $fixed_post_type = '';
	protected $per_request_key = '';
	protected $ordby_request_key = '';
	protected $sort_components = array(
	/*** 
		%key% => array(), 
		or 
		%key% => array(
			array(
				'orderby' => '', 
				'order' => '', 
				'display' => '', 
			), 
		), 
	 ***/
	);
	protected $search_components = array(
	/*** 
		%key% => array(
			'meta_keys' => array(%meta_key%), 
			'tax_query' => %taxonomy%/%term% or %taxonomy%, //'category/fish' or 'fish'
			'itype' => %input_type%, 
			'dtype' => %data_type%, 
			'values' => array(
				array(
					'display' => '', 
					array(
						'key' => %key%, 
						'compare' => '', 
						'val' => mixed, 
					), 
				), 
			), 
			'dont_search_after' => %any_key%, 
			'order_prior_to' => %any_key%, 
			'ipt_args' => array(
				//see get_archive_input_html() $defaults
			), 
		), 
	 ***/
	);
	protected $search_fixed_values = array(
	/*** 
		%key% => array(), 
	 ***/
	);
	protected $force_search_arg_key = 'my_force_search_archive';
	protected $archive_search_reset_key = 's_noparam';
	protected $paged_key = 'paged';
	protected $use_search_redirect_if_term_archive = false;
	protected $use_search_redirect_if_single_term_search = false;
	protected $use_display_count = false;

	private $ignoring_force_search_arg = false;
	private $searched_words = array();
	private $requested_values = array();
	private $current_searching_type = '';

	public function __construct(){
		$this->reset_search_form_if_isset();
		$this->action_filters();
	}

	protected function get_the_arr_val($any, $str_key=''){
		$str_key = (string)$str_key;
		if( '' !== $str_key ) $any = $this->search_arr_val_deeply( $any, $str_key );
		return $any;
	}

	protected function reset_search_form_if_isset(){
		$reset_key = $this->archive_search_reset_key;
		if( !$reset_key || !isset($_GET[$reset_key]) ) return;

		$red_to = $this->get_str_if_isset($_SERVER, 'REQUEST_URI');
		$red_to = explode('?', $red_to, 2);
		$red_to = $red_to[0];

		$sort_keys = $this->get_request_component_keys('sort');
		if( $sort_keys ){
			foreach( $sort_keys as $k ){
				if( !isset($_GET[$k]) ) continue;

				$red_to = add_query_arg($k, $_GET[$k], $red_to);
			}
		}
		$red_to = $this->own_filter_reset_search_url($red_to);

		$this->exec_wp_safe_redirect($red_to);
	}

	protected function own_filter_reset_search_url($url){
		$url = add_query_arg('s', '', $url);
		return $url;
	}

	protected function action_filters(){
		add_action('parse_query', array($this, 'hook_parse_query_for_sort_and_search'));
		add_action('pre_get_posts', array($this, 'hook_pre_get_posts_for_sort_and_search'), 15, 1);

		add_action('init', array($this, 'activate_archive_search'), 20, 1);
	}

	public function hook_parse_query_for_sort_and_search($query){
		if( $this->ignoring_force_search_arg ) return;

		$keys = $this->get_request_component_keys('all');
		$req_values = array();
		foreach($keys as $key){
			$search_key = str_replace(':', '/', $key);
			$g_val = $this->search_arr_val_deeply($_GET, $search_key);
			if( NULL === $g_val ) continue;

			$can_set = array_filter( array(
				( $query->is_archive() || $query->is_search() ), 
				( $query->get($key) ), 
				( $this->is_having_force_search_arg($query) ), 
			) ) ? true : false;

			if( !$can_set ) continue;

			$req_values[$key] = $g_val;
		}
		if( !$req_values ) return;

		$this->requested_values = $req_values;

		$query->set($this->force_search_arg_key, true);
		foreach( $this->requested_values as $key => $val ){
			$query->set($key, $val);
		}
	}

	public function hook_pre_get_posts_for_sort_and_search($query){
		if( is_admin() ) return;
		if( !$query->is_main_query() && !$this->is_having_force_search_arg($query) ) return;
		if( $this->ignoring_force_search_arg ) return;

		$this->set_archive_sort_query($query);
		$this->set_archive_search_query($query);
		$this->own_action_after_set_archive_query($query);
	}

	protected function own_action_after_set_archive_query($query){
		//as need arises
		return;
	}

	protected function set_archive_sort_query($query){
		$set_val = $this->get_appropriate_sort_query_val($query, $this->per_request_key);
		if( NULL !== $set_val ){
			$query->set( 'posts_per_page', $set_val );
		}

		$set_val = $this->get_appropriate_sort_query_val($query, $this->ordby_request_key);
		if( $this->is_valid_arr($set_val) ){
			$ordby = $this->get_str_if_isset($set_val, 'orderby');
			$order = $this->get_str_if_isset($set_val, 'order');

			$ordby = explode('/', $ordby);
			if( 'meta' === $ordby[0] && isset($ordby[1]) ){

				$meta_type = $this->get_str_if_isset($set_val, 'meta_type');
				$meta_type = ( $meta_type ) ? $meta_type : 'NUMERIC';
				$this->set_query_meta_orderby($query, $ordby[1], $order, $meta_type);

			} else {

				$query->set('orderby', $ordby[0]);
				$query->set('order', $order);

			}
		}
	}

	protected function get_appropriate_sort_query_val($query, $key){
		if( !$this->is_wp_query($query) ) return false;

		$cmpnts = $this->get_the_sort_components($key);
		if( !is_array($cmpnts) || !isset($cmpnts[0]) ) return NULL;

		$q_val = (int)$query->get($key, 0);
		$v = isset($cmpnts[$q_val]) ? $cmpnts[$q_val] : $cmpnts[0];
		return $v;
	}

	protected function set_query_meta_orderby($query, $ordby, $order, $meta_type){
		if( !$this->is_wp_query($query) ) return false;

		$meta_key = $ordby;

		$query->set('meta_key', $meta_key);
		$query->set('meta_type', $meta_type);
		$query->set('orderby', 'meta_value');
		$query->set('order', $order);
	}

	protected function get_request_component_keys($key=''){
		$srch_components = $this->get_the_search_components();
		$req_cmpnt_keys = array(
			'sort' => array(
				$this->per_request_key, 
				$this->ordby_request_key, 
			), 
			'search' => array_keys($srch_components), 
		);
		$req_cmpnt_keys = array_map('array_filter', $req_cmpnt_keys);
		if( !$key ) return $req_cmpnt_keys;

		if( 'all' === $key ){
			$req_cmpnt_keys = array_reduce($req_cmpnt_keys, 'array_merge', array());
		} else {
			$req_cmpnt_keys = $this->get_arr_if_isset($req_cmpnt_keys, $key);
		}
		return $req_cmpnt_keys;
	}

	protected function get_requested_values($cmpnt_type=''){
		$req_vals = array();
		$keys = $this->get_request_component_keys($cmpnt_type);
		if( !$keys || !$this->requested_values ) return $req_vals;

		foreach( $keys as $k ){
			$req_vals[$k] = $this->get_if_isset($this->requested_values, $k);
		}
		return $req_vals;
	}

	protected function get_the_appropriate_requested_val($cmpnt_type, $cmpnt_key){
		if( 'search' === $cmpnt_type ){
			$req_val = $this->get_the_search_requested_complementing_val($cmpnt_key);
			return $req_val;
		}

		$req_val = $this->get_the_requested_val($cmpnt_type, $cmpnt_key);
		return $req_val;
	}

	protected function get_the_requested_val($cmpnt_type, $cmpnt_key){
		$req_vals = $this->get_requested_values($cmpnt_type);
		return $this->get_if_isset($req_vals, $cmpnt_key);
	}

	protected function get_the_sort_components($str_key=''){
		return $this->get_the_arr_val($this->sort_components, $str_key);
	}

	protected function get_the_sort_requested_val($cmpnt_key){
		return $this->get_the_requested_val('sort', $cmpnt_key);
	}

	protected function is_having_force_search_arg($query){
		if( !$this->is_wp_query($query) ) return false;

		$val = $query->get($this->force_search_arg_key, false);
		return (bool)$val;
	}

	protected function is_main_query_having_force_search_arg(){
		$val = get_query_var($this->force_search_arg_key);
		return (bool)$val;
	}

	protected function get_key_parsed_component_key($cmpnt_key, $type='prime'){
		$keys = explode(':', $cmpnt_key, 2);
		$prime_key = $keys[0];
		if( in_array( $type, array('prime'), true ) ) return $prime_key;

		$sub_key = '';
		if( 'sub' === $type && isset($keys[1]) ){
			$sub_key = $keys[1];
		}

		return $sub_key;
	}

	protected function get_slug_parsed_tax_query($tax_query, $type='tax'){
		$tax_queries = explode('/', $tax_query, 2);
		$tax_slug = $tax_queries[0];
		if( in_array( $type, array('tax', 'taxonomy'), true ) ) return $tax_slug;

		$term_slug = '';
		if( 'term' === $type && isset($tax_queries[1]) ){
			$term_slug = $tax_queries[1];
		}

		return $term_slug;
	}

	protected function make_appropriate_request_key($cmpnt_key){
		$prime_key = $this->get_key_parsed_component_key($cmpnt_key);
		$sub_key = (string)$this->get_key_parsed_component_key($cmpnt_key, 'sub');

		$req_key = $prime_key;
		if( '' !== $sub_key ){
			$req_key .= '['.$sub_key.']';
		}

		return $req_key;
	}

	public function get_archive_input($key, $args=array()){
		$ipt = '';
		switch($key){
			case $this->per_request_key:
			case $this->ordby_request_key:
				$vals = $this->get_the_sort_components($key);
				$ipt = $this->get_archive_input_html($key, $vals, $args, "select");
				break;

			case 'reset_search':
				$ipt = $this->get_reset_input();
				break;

			case 'hiddens':
				$ipt = $this->get_hiddens_input();
				break;

			default:
				$ipt = $this->get_search_input($key, $args);
				break;
		}
		return $ipt;
	}

	protected function get_archive_input_html($cmpnt_key, $cmpnt_vals, $args, $itype){
		$ipt = '';
		if( !$this->is_valid_arr($cmpnt_vals) ) return $ipt;

		$defaults = array(
			'class' => '', 
			'value_type' => '', 
			'display_prefix' => '', 
			'display_suffix' => '', 
			'blank_element' => array(
				'text' => '---', 
				'value' => '', 
			), 
			'input_type' => $itype, 
			'class_arr' => array(), 
			'label_place' => '', 
			'component_type' => 'sort', 
		);
		$args = $this->recursive_parse_args($defaults, $args, true);

		$ipt_cls = $this->get_if_isset($args, 'class');
		$ipt_cls = is_array($ipt_cls) ? $ipt_cls : array_filter( explode(' ', $ipt_cls) );
		$blank_ele = $this->get_arr_if_isset($args, 'blank_element');
		$itype = $this->get_str_if_isset($args, 'input_type');
		$cmpnt_type = $this->get_str_if_isset($args, 'component_type');

		$req_val = $this->get_the_appropriate_requested_val($cmpnt_type, $cmpnt_key);
		$req_val = $this->own_filter_request_val_at_archive_input($req_val, $cmpnt_key);
		$req_val = is_array($req_val) ? $req_val : array($req_val);
		$req_val = array_map('strval', $req_val);

		$iname = $this->make_appropriate_request_key($cmpnt_key);
		switch($itype){
			case 'select':
				$val_type = $this->get_str_if_isset($args, 'value_type');

				$blank_txt = $this->get_str_if_isset($blank_ele, 'text');
				$blank_val = $this->get_str_if_isset($blank_ele, 'value');

				$has_data = array();
				$select_groups = array();
				$script_keys = array();
				foreach( $cmpnt_vals as $idx => $v_arr ){
					$group_key = 0;
					$group_name = $this->get_str_if_isset($v_arr, 'group_name');
					if( $group_name ){
						$group_key = $group_name;
					}

					$attr = '';
					$ipt_val = (string)$idx;
					if( $this->is_in_or_eq_value($ipt_val, $req_val) ){
						$attr .= $this->make_prop('selected');
					}
					if( 'url' === $val_type ){
						$ipt_val = $this->make_appropriate_input_url($cmpnt_key, $ipt_val);
					}
					$attr .= $this->make_attr('value', $ipt_val);

					$data_name = explode('/', $this->get_str_if_isset($v_arr, 'data_name'), 2);
					if( isset($data_name[1]) ){
						$attr .= $this->make_attr('data-' . $data_name[0], $data_name[1]);
						$has_data[$group_key] = $data_name[0];
					}

					$disp_val = $this->make_values_display($v_arr, $args);
					$opt = $this->make_html_tag('option', $attr, $disp_val);

					if( '' !== $blank_txt && !isset($select_groups[$group_key]) ){
						$attr = ( $this->is_in_or_eq_value('', $req_val) ) ? $this->make_prop('selected') : '';
						$attr .= $this->make_attr('value', $blank_val);
						$select_groups[$group_key][] = $this->make_html_tag('option', $attr, $blank_txt);
					}
					$select_groups[$group_key][] = $opt;
				}
				if( !$select_groups ) break;

				foreach( $select_groups as $group_key => $opts ){
					$attr = $this->make_attr('name', $iname);
					$group_cls = $ipt_cls;
					if( 0 !== $group_key ){
						$group_info = explode('/', $group_key, 2);
						$group_cls[] = 'sgroup-target';
						$group_cls[] = 'sgroup-' . $group_info[0];

						if( isset($group_info[1]) ){
							$attr .= $this->make_attr('data-' . $group_info[0], $group_info[1]);
							$attr .= $this->make_attr('data-links', $group_info[0]);
						}
					}

					if( isset($has_data[$group_key]) ){
						$group_cls[] = 'sgroup-switch';
						$attr .= $this->make_attr('data-links', $has_data[$group_key]);
						$script_keys[] = 'group';
					}

					if( $group_cls ){
						if( in_array('schange-switch', $group_cls, true) ){
							$script_keys[] = 'change';
						}
						$attr .= $this->make_cls_attr( implode(' ', $group_cls) );
					}
					$ipt .= $this->make_html_tag( 'select', $attr, implode("\n", $opts) );
				}

				$this->activate_select_script($script_keys);

				break;

			case 'checkbox':
			case 'radio':
				$label_place = $this->get_str_if_isset($args, 'label_place');
				foreach( $cmpnt_vals as $idx => $v_arr ){
					$attr = '';
					$ipt_val = (string)$idx;
					if( $this->is_in_or_eq_value($ipt_val, $req_val) ){
						$attr .= $this->make_prop('checked');
					}

					$ipername = $iname;
					$ipername .= ( $this->is_multi_arg_input_type($itype) ) ? '[]' : '';
					$attr .= $this->make_attr('name', $ipername);

					$chk_id = $this->make_archive_input_id($iname, $idx);
					$attr .= $this->make_attr('id', $chk_id);

					$ieach = $this->make_ipt_tag($itype, $ipt_val, $attr);

					$attr = $this->make_attr('for', $chk_id);
					$disp_val = $this->make_values_display($v_arr, $args);
					$ieach = $this->arrange_input_label_place($ieach, $attr, $disp_val, $label_place);

					$ipt .= $ieach;
				}
				break;

			case 'a_list':
				$ipt .= $this->make_a_list_html($cmpnt_key, $cmpnt_vals, $args, $iname, $req_val);
				break;

			case 'term_level_select':
				$selects = $this->loop_make_level_selects($cmpnt_key, $cmpnt_vals, $args);
				$ipt .= implode("\n", $selects);
				break;

		}

		return $ipt;
	}

	protected function own_filter_request_val_at_archive_input($req_val, $cmpnt_key){
		return $req_val;
	}

	protected function make_archive_input_id($iname, $idx){
		$id = $iname . '__' . (string)$idx;
		return $id;
	}

	protected function own_filter_first_opt_value_at_archive_select($frst_value, $cmpnt_key){
		return $frst_value;
	}

	protected function make_values_display($v_arr, $args){
		$disp_prfx = $this->get_str_if_isset($args, 'display_prefix');
		$disp_sffx = $this->get_str_if_isset($args, 'display_suffix');

		$disp_val = is_array($v_arr) ? $this->get_str_if_isset($v_arr, 'display') : $v_arr;
		$disp_val = $disp_prfx . $disp_val . $disp_sffx;
		if( $this->use_display_count && isset($v_arr['count']) ){
			$disp_val .= "(".(string)$v_arr['count'].")";
		}
		return $disp_val;
	}

	protected function arrange_input_label_place($ipt, $lbl_attr, $lbl_val, $lbl_place){
		$lbl_tag = 'label';
		$span_attr = $this->make_cls_attr('lbl-txt');
		$span_ele = $this->make_html_tag('span', $span_attr, $lbl_val);
		switch( $lbl_place ){
			case 'before':
				$ipt = $this->make_html_tag($lbl_tag, $lbl_attr, $lbl_val) . $ipt;
				break;

			case 'after':
				$ipt = $ipt . $this->make_html_tag($lbl_tag, $lbl_attr, $lbl_val);
				break;

			case 'wrap_span_before':
				$lbl_val = $span_ele; /* fall through, do not break */
			case 'wrap_before':
				$ipt = $this->make_html_tag($lbl_tag, $lbl_attr, $lbl_val.$ipt);
				break;

			case 'wrap_span_after':
				$lbl_val = $span_ele; /* fall through, do not break */
			case 'wrap_after':
			default:
				$ipt = $this->make_html_tag($lbl_tag, $lbl_attr, $ipt.$lbl_val);
				break;
		}
		return $ipt;
	}

	protected function make_a_list_html($cmpnt_key, $cmpnt_vals, $args, $iname, $req_val){
		$class_arr = $this->get_arr_if_isset($args, 'class_arr');
		$cls_ul = $this->get_arr_if_isset($class_arr, 'ul');
		$cls_li = $this->get_arr_if_isset($class_arr, 'li');
		$cls_a = $this->get_arr_if_isset($class_arr, 'a');

		$tax_slug = $this->get_the_tax_slug_by_component_key($cmpnt_key);

		$a_list = array();
		foreach( $cmpnt_vals as $idx => $v_arr ){
			$ipt_val = (string)$idx;
			$url = $this->make_appropriate_input_url($cmpnt_key, $ipt_val);

			$a_cls = implode( ' ', array_filter($cls_a) );
			$attr = ( $a_cls ) ? $this->make_cls_attr($a_cls) : '';

			$disp_val = $this->make_values_display($v_arr, $args);
			$a_tag = $this->make_a_tag($url, $attr, $disp_val);

			$li_id = $this->make_archive_input_id($iname, $idx);
			$attr = $this->make_attr('id', $li_id);

			$li_cls = $cls_li;
			$li_cls[] = ( $tax_slug ) ? $tax_slug : '';
			$li_cls[] = ( $this->is_in_or_eq_value($ipt_val, $req_val) ) ? 'current' : '';
			$li_cls = implode( ' ', array_filter($li_cls) );
			$attr .= ( $li_cls ) ? $this->make_cls_attr($li_cls) : '';

			$a_list[] = $this->make_html_tag('li', $attr, $a_tag);
		}

		$ul_cls = implode( ' ', array_filter($cls_ul) );
		$attr = ( $ul_cls ) ? $this->make_cls_attr($ul_cls) : '';
		$ipt_html = $this->make_html_tag( 'ul', $attr, implode("\n", $a_list) );

		return $ipt_html;
	}

	protected function loop_make_level_selects($cmpnt_key, $cmpnt_vals, $args){
		$selects = array();
		$tax_query = $this->get_the_tax_query_by_component_key($cmpnt_key);
		$tax_slug = $this->get_slug_parsed_tax_query($tax_query);
		if( !$tax_slug ) return $selects;

		$terms = $this->get_tax_terms_by_tax_query($tax_query, $cmpnt_key);
		$term_tree = $this->get_wp_term_tree($tax_slug, $terms);
		$term_tree = $this->own_filter_term_tree_at_level_selects($term_tree, $cmpnt_key);
		$term_tree = $this->make_wp_term_flatten_tree($term_tree, 0);
		if( $term_tree ){
			$term_tree = $this->make_wp_term_leveled_tree($term_tree);
		}
		if( !$term_tree ) return $selects;

		if( isset( $args['input_type'] ) ) unset($args['input_type']); //important

		foreach( $term_tree as $lvl => $term_ids ){
			$use_values = array_intersect_key( $cmpnt_vals, array_flip($term_ids) );
			$selects[$lvl] = $this->get_archive_input_html($cmpnt_key, $use_values, $args, 'select');
		}
		return $selects;
	}

	protected function own_filter_term_tree_at_level_selects($term_tree, $cmpnt_key){
		return $term_tree;
	}

	protected function make_appropriate_search_url($cmpnt_key, $str_val, $consider_others=false){
		$search_url = $this->get_search_url();

		$arr_val = array( $this->validate_str($str_val) );
		$component_keys = $this->get_request_component_keys();
		$adds = array();
		foreach( $component_keys as $cmpnt_type => $cp_keys ){
			if( !$cp_keys ) continue;

			foreach( $cp_keys as $cpk ){
				if( !$consider_others && $cpk !== $cmpnt_key ) continue;

				$keeps = NULL;
				if( $consider_others ){
					$keeps = $this->get_the_appropriate_requested_val($cmpnt_type, $cpk);
				}
				$keeps = ( NULL === $keeps ) ? array() : $keeps;
				$keeps = is_array($keeps) ? $keeps : array($keeps);

				$is_multi_arg = false;
				if( 'search' === $cmpnt_type ){
					$itype = $this->get_the_search_components("{$cpk}/itype");
					$is_multi_arg = $this->is_multi_arg_input_type($itype);
				}

				if( $cpk === $cmpnt_key ){
					if( array_intersect($keeps, $arr_val) ){
					/* hit and remove */
						$keeps = array_diff($keeps, $arr_val);
					} else {
						$keeps = ( $is_multi_arg ) ? array_merge($keeps, $arr_val) : $arr_val;
					}
					$keeps = array_values($keeps); /* reassign index */
				}

				$keeps = array_filter($keeps, 'mb_strlen');
				if( !$keeps ) continue;

				$adds[$cpk] = ( $is_multi_arg ) ? $keeps : implode('', $keeps);
			}
		}
		if( !$adds ) return $search_url;

		foreach( $adds as $cpk => $req_val ){
			$req_key = $this->make_appropriate_request_key($cpk);
			$search_url = add_query_arg($req_key, $req_val, $search_url);
		}

		$search_url = $this->convert_search_or_sort_query_url($search_url);

		$search_url = urldecode($search_url);
		$search_url = preg_replace("/\[\d+\]/u", "[]", $search_url);

		return $search_url;
	}

	protected function make_appropriate_input_url($cmpnt_key, $str_val){
		return esc_url( $this->make_appropriate_search_url($cmpnt_key, $str_val, true) );
	}

	protected function is_multi_arg_input_type($type){
		$multi_ipt_types = array('checkbox', 'a_list');
		return (bool)in_array($type, $multi_ipt_types, true);
	}

	public function convert_search_or_sort_query_url($url){
		$url = remove_query_arg($this->paged_key, $url);
		return $url;
	}

/**************************************************************/
/*************************** search ***************************/
/**************************************************************/
	public function activate_archive_search(){
		if( is_admin() ) return;

		$this->set_search_components();
		add_action('wp', array($this, 'hook_for_search'));
		add_action('wp', array($this, 'root_redirect_search_tax'), 1);

		if( $this->fixed_post_type ){
			$this->search_post_types = array($this->fixed_post_type);
		}

		add_filter('term_link', array($this, 'hook_search_components_term_link'), 20, 3);
	}

	protected function set_current_searching_type($type){
		$this->current_searching_type = $type;
	}

	protected function clear_current_searching_type(){
		$this->set_current_searching_type('');
	}

	protected function is_current_searching_type($type){
		return ( $type && $type === $this->current_searching_type );
	}

	protected function get_the_search_components($str_key=''){
		return $this->get_the_arr_val($this->search_components, $str_key);
	}

	protected function get_the_search_requested_val($cmpnt_key){
		return $this->get_the_requested_val('search', $cmpnt_key);
	}

	protected function get_the_search_requested_complementing_val($cmpnt_key){
		$req_val = $this->get_the_search_requested_val($cmpnt_key);
		$req_val = $this->complement_search_requested_val($req_val, $cmpnt_key);
		return $req_val;
	}

	protected function get_the_tax_query_by_component_key($cmpnt_key){
		$tax_key = "{$cmpnt_key}/tax_query";
		$tax_query = (string)$this->get_the_search_components($tax_key);
		return $tax_query;
	}

	protected function get_the_tax_slug_by_component_key($cmpnt_key){
		$tax_query = $this->get_the_tax_query_by_component_key($cmpnt_key);
		$tax_slug = $this->get_slug_parsed_tax_query($tax_query);
		return $tax_slug;
	}

	protected function complement_search_requested_val($req_val, $cmpnt_key){
		$tax_query = $this->get_the_tax_query_by_component_key($cmpnt_key);
		if( $tax_query ){
			$req_val = $this->complement_tax_val_if_is_term_archive($req_val, $cmpnt_key);
		}
		return $req_val;
	}

	protected function complement_tax_val_if_is_term_archive($req_val, $cmpnt_key){
		if( !is_tax() ) return $req_val;

		$tax_val = "";
		static $tax_component = NULL;
		if( NULL === $tax_component ){
			 $tax_component = $this->get_the_tax_component_by_query();
		}

		$cpk = $this->get_str_if_isset($tax_component, 'key');
		if( $cmpnt_key === $cpk && isset($tax_component['idx']) ){
			$tax_val = (string)$tax_component['idx'];
		}

		if( "" !== $tax_val ){
			if( is_array($req_val) ){
				$req_val[] = $tax_val;
			} elseif( "" === (string)$req_val ) {
				$req_val = $tax_val;
			}
		}

		return $req_val;
	}

	protected function set_archive_search_query($query){
		if( $query->is_main_query() ){
			if( $query->is_search() ){
				$query->set('post_type', $this->search_post_types); /* avoid post_type 'any' */
			}

			if( $this->use_display_count && $this->is_search_page($query) ){
				$this->set_count_per_search_display($query);
			}
		}

		$srch_components = $this->get_the_search_components();
		if( !$this->is_valid_arr($srch_components) ) return;

		$this->set_current_searching_type('query'); /* do not forget to call clear_ */

		/* components' relation AND */
		$post_in = false;
		$is_zero_posts = false; //to store searched_words
		$tax_component = $this->get_the_tax_component_by_query($query);
		$tax_key = $this->get_str_if_isset($tax_component, 'key');
		if( $tax_key && isset($tax_component['idx']) ){
			$tax_idx = (string)$tax_component['idx'];
		}
		foreach( $srch_components as $cmpnt_key => $cmpnts ){
			$queried_val = $query->get($cmpnt_key, NULL);
			if( NULL === $queried_val || "" === $queried_val ) continue;

			$cancel_check_key = $this->get_str_if_isset($cmpnts, 'dont_search_after');
			if( $cancel_check_key ){ 
			/* Cancel search process if got the key's word, means the exec has already completed */
				if( isset($this->searched_words[$cancel_check_key]) ){
					continue;
				}
			}

			$ex_keys = $this->get_arr_if_isset($cmpnts, 'meta_keys');
			$q_type = 'meta';
			if( !$ex_keys ){
				$tax_query = $this->get_str_if_isset($cmpnts, 'tax_query');
				if( $tax_query ){
					$q_type = 'tax';
					$key_components = explode('/', $tax_query, 2);
				} else {
					$key_components = explode(':', $cmpnt_key, 2);
				}
				$ex_keys = array($key_components[0]);
			}

			$cmpnt_values = $this->get_arr_if_isset($cmpnts, 'values');

			$loop_vals = is_array($queried_val) ? $queried_val : array($queried_val);
			$per_val_ids = array();
			foreach( $loop_vals as $q_val ){
				$values = $this->get_arr_if_isset($cmpnt_values, $q_val);

				if( !$is_zero_posts ){

					$per_ids = $this->get_post_ids_for_search_query($ex_keys, $values, $cmpnts, $query, $q_type);
					if( false === $per_ids ) continue;

					$per_val_ids[$q_val] = $per_ids;
				}

				if( $tax_key === $cmpnt_key && $tax_idx === (string)$q_val ) continue;

				if( isset($values['display']) ){
					$s_word = $values['display'];
					if( isset($values['searched_word']) ){
						$s_word = $values['searched_word'];
					}
					$this->searched_words[$cmpnt_key][] = $s_word;
				}
			}
			$per_val_ids = $this->sort_arr_bahaving_as_OR_relation($per_val_ids);

		/* behave as AND */
			$post_in = ( !$post_in ) ? $per_val_ids : array_intersect( $post_in, $per_val_ids );
			if( empty( $post_in ) ) $is_zero_posts = true;

		}

		$this->clear_current_searching_type();

		if( !is_array($post_in) ) return;

		$post_in = ( $post_in ) ? $post_in : array(0);
		$query->set('post__in', $post_in);
	}

	protected function set_count_per_search_display($query){
		$srch_components = $this->get_the_search_components();
		if( !$this->is_valid_arr($srch_components) ) return;

		$this->set_current_searching_type('count'); /* do not forget to call clear_ */

		foreach( $srch_components as $cmpnt_key => $cmpnts ){
			$tax_query = $this->get_str_if_isset($cmpnts, 'tax_query');
			if( $tax_query ) continue;

			$meta_keys = $this->get_arr_if_isset($cmpnts, 'meta_keys');
			if( !$meta_keys ){
				$key_components = explode(':', $cmpnt_key, 2);
				$meta_keys = array($key_components[0]);
			}

			$cmpnt_values = $this->get_arr_if_isset($cmpnts, 'values');
			if( !$cmpnt_values ) continue;

			foreach( $cmpnt_values as $idx => $values ){
				if( isset($values['count']) ) continue;

				$per_ids = $this->get_post_ids_for_search_query($meta_keys, $values, $cmpnts, $query);
				$cmpnt_values[$idx]['count'] = ( $per_ids ) ? count($per_ids) : 0;
			}

			$srch_components[$cmpnt_key]['values'] = $cmpnt_values;
		}

		$this->clear_current_searching_type();

		$this->search_components = $srch_components;
	}

	protected function get_post_ids_for_search_query($ex_keys, $values, $cmpnts, $query, $q_type=''){
		if( !$this->is_valid_arr($ex_keys) || !$this->is_valid_arr($values) ) return false;

		$allowed_types = array('meta', 'tax');
		$q_type = in_array($q_type, $allowed_types, true) ? $q_type : $allowed_types[0];
		$q_key = $q_type . '_query';

		$d_type = $this->get_str_if_isset($cmpnts, 'dtype');
		$d_type = ( 'meta' === $q_type  ) ? $this->get_sql_data_type($d_type) : $d_type;
		$base_args = array(
			'post_type' => $query->get('post_type'), 
			'posts_per_page' => -1, 
		);
		/* ex_keys' relation OR */
		/* values' relation AND */
		$post_ids = false;
		foreach( $ex_keys as $ek ){
			$ex_query = $this->make_ex_query($values, $ek, $q_type, $d_type);
			if( !$ex_query ) continue;

			$idx_key = $ek;
			if( false === $post_ids ){
				$post_ids[$idx_key] = array();
			}

			if( 1 < count($ex_query) ){
				$ex_query['relation'] = 'AND';
			}
			$args = $base_args;
			$args[$q_key] = $ex_query;

			$this->ignoring_force_search_arg = true;
			$posts = get_posts($args);
			$this->ignoring_force_search_arg = false;

			if( !$posts ) continue;

			foreach( $posts as $post ){
				$post_ids[$idx_key][] = (int)$post->ID;
			}
		}

		if( false !== $post_ids ){
			$post_ids = $this->sort_arr_bahaving_as_OR_relation($post_ids);
		}

		return $post_ids;
	}

	private function make_ex_query($values, $ex_key, $q_type, $d_type){
		$ex_query = array();
		foreach( $values as $idx => $vls ){
			if( !is_numeric($idx) ) continue;

			$key_in_values = $this->get_str_if_isset($vls, 'key');
			$q = array();
			switch( $q_type ){
				case 'meta':
					$q = array(
						'key' => ( $key_in_values ) ? $key_in_values : $ex_key, 
						'value' => $vls['val'], 
						'compare' => $vls['compare'], 
						'type' => ( $d_type ) ? $d_type : 'CHAR', 
					);
					break;

				case 'tax':
					$q = array(
						'taxonomy' => ( $key_in_values ) ? $key_in_values : $ex_key, 
						'field' => ( $d_type ) ? $d_type : 'term_id', 
						'terms' => $vls['val'], 
						'include_children' => false, 
						'operator' => $vls['compare'], 
					);
					break;
			}
			if( !$q ) continue;

			$ex_query[] = $q;
		}
		return $ex_query;
	}

	protected function set_search_components(){
		$srch_components = $this->get_the_search_components();
		if( !$this->is_valid_arr($srch_components) ) return;

		$prioritize_keys = array();
		$fixed_values = $this->search_fixed_values;
		foreach( $srch_components as $cmpnt_key => $cmpnts ){
			$prime_key = $this->get_key_parsed_component_key($cmpnt_key);
			$sub_key = $this->get_key_parsed_component_key($cmpnt_key, 'sub');

			$prior_to = $this->get_str_if_isset($cmpnts, 'order_prior_to');
			if( $prior_to ){
				$exist_pos = array_keys($prioritize_keys, $prior_to);
				if( $exist_pos ){
					$exist_pos = $exist_pos[0];
					array_splice($prioritize_keys, $exist_pos, 0, $cmpnt_key);
				} else {
					$prioritize_keys[] = $cmpnt_key;
					$prioritize_keys[] = $prior_to;
				}
			}

			$values = $this->get_arr_if_isset($cmpnts, 'values');
			$fxd_vals = $this->get_arr_if_isset($fixed_values, $prime_key);

			$tax_query = $this->get_str_if_isset($cmpnts, 'tax_query');
			$meta_keys = $this->get_arr_if_isset($cmpnts, 'meta_keys');

			switch( true ){
				case ( $fxd_vals ):
					$values = $this->get_search_fixed_values($values, $cmpnt_key, $fxd_vals);
					break;

				case ( $tax_query ):
					$values = $this->get_search_tax_terms_values($values, $cmpnt_key, $tax_query);
					break;

				default:
					$values = $this->get_search_dynamic_values($values, $cmpnt_key);
					break;
			}

			$srch_components[$cmpnt_key]['values'] = $values;

		}

		if( $prioritize_keys ){
			$srch_components = $this->prioritize_arr_order($srch_components, $prioritize_keys);
		}

		$this->search_components = $srch_components;
	}

	protected function get_the_tax_component_by_query($query=NULL){
		$tax_component = array();
		$srch_components = $this->get_the_search_components();
		if( !$srch_components ) return $tax_component;

		foreach( $srch_components as $cmpnt_key => $cmpnts ){
			$tax_query = $this->get_str_if_isset($cmpnts, 'tax_query');
			$cmpnt_values = $this->get_arr_if_isset($cmpnts, 'values');
			if( !$tax_query || !$cmpnt_values ) continue;

			$txnmy = $this->get_slug_parsed_tax_query($tax_query);

			foreach( $cmpnt_values as $cmpnt_idx => $values ){
				foreach( $values as $vls_idx => $vls ){
					if( !is_numeric($vls_idx) ) continue;

					$term_id = $this->get_int_if_isset($vls, 'val');
					$term = get_term($term_id, $txnmy);
					if( 
						( !$this->is_wp_term($term) ) 
						|| ( !$this->is_the_term_archive($txnmy, $term_id, $query) ) 
					) continue;

					$tax_component['key'] = $cmpnt_key;
					$tax_component['idx'] = $cmpnt_idx;
					break 3;
				}
			}
		}

		return $tax_component;
	}

	protected function get_search_fixed_values($values, $cmpnt_key, $fxd_vals){
		$prime_key = $this->get_key_parsed_component_key($cmpnt_key);
		$sub_key = $this->get_key_parsed_component_key($cmpnt_key, 'sub');
		foreach( $fxd_vals as $fv ){
			$cmpr = $this->get_the_compare_value('operator', $sub_key);
			$dsply = $this->make_search_value_display($fv, $cmpnt_key);
			$values[] = array(
				'display' => $dsply, 
				array( 'compare' => $cmpr, 'val' => $fv ), 
			);
		}
		return $values;
	}

	protected function make_search_value_display($val, $cmpnt_key){
		$dsply = '';
		$prime_key = $this->get_key_parsed_component_key($cmpnt_key);
		$sub_key = $this->get_key_parsed_component_key($cmpnt_key, 'sub');
		switch( $prime_key ){
			default:
				$dsply = $val;
				break;
		}
		return $dsply;
	}

	protected function get_tax_terms_by_tax_query($tax_query, $cmpnt_key){
		$txnmy = $this->get_slug_parsed_tax_query($tax_query);
		$term_slug = $this->get_slug_parsed_tax_query($tax_query, 'term');

		$srch_components = $this->get_the_search_components($cmpnt_key);
		$tax_args = $this->get_arr_if_isset($srch_components, 'tax_args');

		$args = array(
			"hide_empty" => false, 
		);
		$args = $this->recursive_parse_args($args, $tax_args);
		$args["taxonomy"] = $txnmy;

		$term_id = $this->get_wp_term_by_slug($txnmy, $term_slug, 'term_id');
		if( $term_id ){
			$args["parent"] = $term_id;
		}

		$terms = get_terms($args);

		return $terms;
	}

	protected function get_search_tax_terms_values($values, $cmpnt_key, $tax_query){
		$terms = $this->get_tax_terms_by_tax_query($tax_query, $cmpnt_key);
		if( !$terms || is_wp_error($terms) ) return $values;

		foreach($terms as $term){
			$values[$term->term_id] = array(
				'display' => $term->name, 
				array( 'compare' => 'IN', 'val' => $term->term_id ), 
				'count' => $term->count, 
			);
		}

		return $values;
	}

	protected function get_search_dynamic_values($values, $cmpnt_key){
		//as need arises
		return $values;
	}

	protected function get_search_posts_where_sql($post_types, $table_alias='p'){
		$where = "";
		$type_str = "";
		if( $this->is_valid_arr($post_types) ) {
			$type_str = $this->parse_arr_into_sql_in_str($post_types, 'string');
			if( $type_str ){
				$type_str .= "IN({$type_str})";
			}
		} elseif( $this->is_valid_str($post_types) ) {
			$type_str = "= '" . esc_sql($post_types) . "'";
		}
		if( !$type_str ) return $where;

		$table_alias = is_string($table_alias) ? esc_sql($table_alias) . '.' : '';
		$where = "( {$table_alias}post_type {$type_str}";

		$statuses = $this->get_search_post_status_where_in();
		if( $statuses ){
			$where .= " ) AND ( {$table_alias}post_status IN {$statuses}";
		}

		$where .= " )";
		return $where;
	}

	protected function get_search_post_status(){
		$post_status = array('publish');
		return $post_status;
	}

	protected function get_search_post_status_where_in(){
		$post_status = $this->get_search_post_status();
		$prepare_in = $this->parse_arr_into_sql_in_str($post_status, 'string');
		return $prepare_in;
	}

	protected function parse_arr_into_sql_in_str($arr, $type=''){
		$str = '';
		if( !$this->is_valid_arr($arr) ) return $str;

		if( 'string' === $type ){
			$arr = array_map( function($v){
				return "'". esc_sql($v) . "'";
			}, $arr );
		}
		$str =  '(' . implode(',', $arr) . ')';
		return $str;
	}

	protected function get_search_input($key, $args=array()){
		$ipt = '';
		$components = $this->get_the_search_components($key);
		if( !$components ) return $ipt;

		$values = $this->get_arr_if_isset($components, 'values');

		$ipt_args = $this->get_arr_if_isset($components, 'ipt_args');
		$args = $this->recursive_parse_args($ipt_args, $args);
		$args['component_type'] = 'search';

		$itype = $this->get_str_if_isset($components, 'itype');

		$ipt = $this->get_archive_input_html($key, $values, $args, $itype);

		return $ipt;
	}

	protected function get_reset_input(){
		$iname = $this->archive_search_reset_key;
		$ipt_args = array(
			'type' => 'submit', 
			'name' => $iname, 
			'value' => 'リセット', 
		);
		$ipt = $this->make_ipt_tag($ipt_args);
		return $ipt;
	}

	protected function get_hiddens_input(){
		$ipt_args = array(
			'type' => 'hidden', 
		);
		if( is_tax() ){

			$obj = get_queried_object();
			$ipt_args['name'] = $obj->taxonomy;
			$ipt_args['value'] = $obj->slug;
			$ipt .= $this->make_ipt_tag($ipt_args);

		} else {

			$ipt_args['name'] = 's';
			$ipt_args['value'] = '';
			$ipt = $this->make_ipt_tag($ipt_args);

		}

		$sort_keys = $this->get_request_component_keys('sort');
		if( !$sort_keys ) return $ipt;

		foreach( $sort_keys as $k ){
			if( !isset($_GET[$k]) ) continue;

			$ipt_args['name'] = $k;
			$ipt_args['value'] = $_GET[$k];
			$ipt .= $this->make_ipt_tag($ipt_args);
		}

		return $ipt;
	}

	protected function activate_select_script($keys){
		$keys = is_array($keys) ? $keys : array( $keys );
		$keys = ( $keys ) ? array_unique($keys) : array();
		if( !$keys ) return;

		foreach( $keys as $k ){
			$func = "output_select_{$k}_script";
			if( !$k || !method_exists($this, $func) ) continue;

			add_action('wp_footer', array($this, $func), 20);
		}
	}

	public function output_select_group_script(){
		static $called = false;
		if( $called ) return;
 ?>
<script>
(function($){
	var selector = 'select.sgroup-switch';
	var $selectGroupSwitchParents = $(selector).parent();
	if( !$selectGroupSwitchParents.length ) return;

	var showEle = function(ele){
		if( $(ele).length ) $(ele).css('display', '').prop('disabled', false);
	};

	var hideEle = function(ele){
		if( $(ele).length ) $(ele).css('display', 'none').prop('disabled', true);
	};

	var switchSelectFunc = function(prnt){
		var $slct = $(prnt).find(selector);
		var dataLinks = $slct.data('links');
		var $selectTgts = $(prnt).find('select[data-links="'+ dataLinks +'"]').not($slct);
		if( !$selectTgts.length ) return;

		hideEle($selectTgts);

		var theData = $slct.find('option:selected').data(dataLinks);
		if( !theData ) return;

		var $theTgt = $selectTgts.filter('[data-' + dataLinks + '="' + theData.toString() + '"]');
		showEle($theTgt);
	};

	$.each($selectGroupSwitchParents, function(){
		var $p = $(this);
		$p.find(selector).on('change', function(){
			switchSelectFunc($p);
		}).trigger('change');
	});

})(jQuery);
</script>
<?php 
		$called = true;
	}

	public function output_select_change_script(){
		static $called = false;
		if( $called ) return;
 ?>
<script>
jQuery(function($){
	$('select.schange-switch').each(function(idx, ele){
		var loadVal = $(ele).val();
		$(ele).on('change', function(){
			var nowVal = $(this).val();
			if( nowVal && loadVal !== nowVal ){
				location.href = $(this).val();
				return false;
			}
		});
	});
});
</script>
<?php 
		$called = true;
	}

	protected function is_search_page($q=NULL){
		if( !$this->is_wp_query($q) ){
			return ( is_front_page() || is_archive() || is_search() );
		}

		$page_on_front = (int)get_option('page_on_front');
		$page_on_front = (bool)( $page_on_front && $page_on_front === (int)$q->get('page_id') );
		$show_on_front = (bool)( 'page' === get_option('show_on_front') );
		$is_front_page = (bool)( $q->is_page() && $page_on_front && $show_on_front );
		return ( $is_front_page || $q->is_archive() || $q->is_search() );
	}

	protected function get_the_compare_value($kind, $key){
		$values = array(
			'operator' => array(
				'eq' => '=', 
				'ge' => '>=', 'gt' => '>', 
				'le' => '<=', 'lt' => '<', 
			), 
			'display' => array(
				'eq' => 'と同じ', 
				'ge' => '以上', 'gt' => 'より大きい', 
				'le' => '以下', 'lt' => '未満', 
			), 
		);
		$values = $this->get_arr_if_isset($values, $kind);
		$v = $this->get_str_if_isset($values, $key);
		return $v;
	}

	protected function get_sql_data_type($type){
		$data_types = array(
			'integer' => 'UNSIGNED', 
			'string' => 'CHAR', 
			'float' => 'DECIMAL', 
			'date' => 'DATE', 
		);
		$type = $this->get_str_if_isset($data_types, $type);
		return $type;
	}

	protected function sort_arr_bahaving_as_OR_relation($arr){
		if( !is_array($arr) ) return false;
		/*** 
			you must pass two-dimensional array like below
			$arr = array( 
				key => array(), 
				...
			);
		 ***/
		$arr = array_reduce($arr, 'array_merge', array());
		$arr = array_unique($arr);
		return $arr;
	}

	public function get_search_url(){
		$url = home_url();
		$url = add_query_arg('s', '', $url);
		return $url;
	}

	public function get_searching_url($q=NULL){
		$url = $this->get_search_url();
		if( $this->is_search_page($q) ){
			$uri = $this->get_srt_if_isset($_SERVER, 'REQUEST_URI');
			$uri = explode('?', $uri, 2);
			$uri = $this->get_srt_if_isset($uri, 1);
			if( $uri ){
				$url .= ( strpos($url, '?') === false ) ? '?' : '&';
				$url .= $uri;
			}
		}
		return $url;
	}

	public function get_the_search_words(){
		return $this->searched_words;
	}

	public function hook_for_search(){
		$this->hook_for_search_title();
		$this->hook_for_search_meta();
	}

	public function hook_for_search_title(){
		//WordPress ～4.3
		add_filter( 'wp_title', array($this, 'archive_search_title') );
		//WordPress 4.4～
		add_filter( 'pre_get_document_title', array($this, 'archive_search_title') );
		//All-in-One-SEO-Pack
		add_filter( 'aioseop_title', array($this, 'archive_search_title'), 50 );

		//breadcrumb
		add_filter( 'bcn_breadcrumb_title', array($this, 'archive_search_title'), 10, 3 );
	}

	public function archive_search_title($title){
		if( !is_search() && !is_archive() ) return $title;

		$is_doing_bcn = (bool)doing_filter('bcn_breadcrumb_title');
		if( $is_doing_bcn ){
			$func_args = func_get_args();
			$type = $func_args[1];
			if( isset($type[0]) && !in_array($type[0], array('search', 'archive'), true) ) return $title;
		}

		$searched_words = $this->get_the_search_words();
		$searched_words['s'][] = get_search_query();
		$searched_words = $this->own_filter_searched_words_at_search_title($searched_words);
		if( $this->is_valid_arr($searched_words) ){
			foreach( $searched_words as $cmpnt_key => $svars ){
				$svars = is_array($svars) ? $svars : array( (string)$svars );
				$searched_words[$cmpnt_key] = implode( ', ', array_filter($svars) );
			}
		}
		$searched_words = is_array($searched_words) ? $searched_words : array($searched_words);
		$search_phrase = implode( ' / ', array_filter($searched_words) );
		$search_phrase .= ( $search_phrase ) ? 'の' : '';
		if( is_tax() ){
			$obj = get_queried_object();
			$search_phrase = "「". esc_html($obj->name) ."」" . esc_html($search_phrase);
		}

		if( $search_phrase || is_search() ){
			$search_ttl = sprintf('%s検索結果', $search_phrase);
			$post_type_obj = $this->get_wp_current_post_type_object();

			if( $is_doing_bcn && $post_type_obj ){
				$href = get_post_type_archive_link($post_type_obj->name);
				$a_ttl = $this->make_a_tag($href, '', $post_type_obj->label);
				$search_ttl = $a_ttl . ' &gt; ' . $search_ttl;
			}

			$blog_name = get_bloginfo('name');
			$search_ttl .= ( false !== strpos($title, $blog_name) ) ? " | {$blog_name}" : '';

			$title = $search_ttl;
		}

		return $title;
	}

	protected function own_filter_searched_words_at_search_title($searched_words){
		return $searched_words;
	}

	protected function hook_for_search_meta(){
		add_filter('aioseop_robots_meta', array($this, 'hook_aioseop_robots_meta_when_searching'), 20, 1);
	}

	public function hook_aioseop_robots_meta_when_searching($robots_meta_str){
		if( !$this->is_main_query_having_force_search_arg() ) return $robots_meta_str;

		$robots_meta_str = preg_replace("/(?<!no)index/u", "noindex", $robots_meta_str);
		return $robots_meta_str;
	}

	public function root_redirect_search_tax(){
		if(
		//avoid redirect loop
			( $this->use_search_redirect_if_term_archive ) 
			&& ( $this->use_search_redirect_if_single_term_search ) 
		) return;

		$this->redirect_if_is_term_archive();
		$this->redirect_if_is_single_term_search();
	}

	public function redirect_if_is_term_archive(){
		if( !$this->use_search_redirect_if_term_archive ) return;

		$tax_component = $this->get_the_tax_component_by_query();
		$cmpnt_key = $this->get_str_if_isset($tax_component, 'key');
		if( $cmpnt_key && isset($tax_component['idx']) ){
			$cmpnt_idx = $tax_component['idx'];
			$red_to = $this->make_appropriate_search_url($cmpnt_key, $cmpnt_idx);

			$this->exec_wp_safe_redirect($red_to);
		}
	}

	protected function get_search_condition_counts(){
		$counts = array();
		$req_values = $this->get_requested_values('search');
		foreach( $req_values as $cmpnt_key => $req_val ){
			if( !is_array($req_val) ){
				$req_val = array( (string)$req_val );
			}
			$req_val = array_filter($req_val, 'mb_strlen');
			$req_ct = count($req_val);
			$counts[$cmpnt_key] = $req_ct;
		}
		return $counts;
	}

	protected function get_search_condition_effective_counts(){
		$cndtn_counts = $this->get_search_condition_counts();
		return array_filter($cndtn_counts);
	}

	protected function is_complex_search_condition(){
		$cndtn_counts = $this->get_search_condition_effective_counts();
		return (bool)( 1 < count($cndtn_counts) );
	}

	protected function is_single_search_condition(){
		$cndtn_counts = $this->get_search_condition_effective_counts();
		return (bool)( 1 === count($cndtn_counts) );
	}

	protected function redirect_if_is_single_term_search(){
		if( !$this->use_search_redirect_if_single_term_search ) return;

		$cndtn_counts = $this->get_search_condition_effective_counts();
		if( 1 !== count($cndtn_counts) ) return;

		$candidates = array();
		foreach( $cndtn_counts as $cmpnt_key => $cndtn_ct ){
			if( 1 < $cndtn_ct ) continue;

			$tax_slug = $this->get_the_tax_slug_by_component_key($cmpnt_key);
			if( !taxonomy_exists($tax_slug) ) continue;

			$req_s_val = $this->get_the_search_requested_val($cmpnt_key);
			$req_s_val = is_array($req_s_val) ? $req_s_val : array( (string)$req_s_val );
			$req_s_val = array_filter($req_s_val, 'mb_strlen');
			if( !$req_s_val ) continue;

			foreach( $req_s_val as $req_val ){
				$id_key = "{$cmpnt_key}/values/{$req_val}/0/val";
				$term_id = (int)$this->get_the_search_components($id_key);
				$term_obj = get_term($term_id, $tax_slug);
				if( !$this->is_wp_term($term_obj) ) continue;

				$candidates[$tax_slug] = get_term_link($term_obj);
				break 2;
			}
		}

		if( $candidates ){
			$red_to = array_shift($candidates);

			$this->exec_wp_safe_redirect($red_to);
		}
	}

	public function hook_search_components_term_link($termlink, $term, $taxonomy){
		if( $this->use_search_redirect_if_term_archive ){
			$termlink =  $this->convert_term_link_into_search_url($termlink, $term, $taxonomy);
		}
		return $termlink;
	}

	protected function convert_term_link_into_search_url($termlink, $term, $taxonomy){
		$apply_taxonomies = $this->get_apply_taxonomies_for_hook_term_link();
		$tax_info = $this->get_arr_if_isset($apply_taxonomies, $taxonomy);
		$param_key = $this->get_str_if_isset($tax_info, 'key');
		$candidates = $this->get_arr_if_isset($tax_info, 'values');
		if( !$param_key || !$candidates ) return $termlink;

		$param_val = NULL;
		foreach( $candidates as $c_idx => $values ){
			if( !$values ) continue;

			foreach( $values as $v_idx => $val ){
				if( $this->is_in_or_eq_value( (int)$term->term_id, $val ) ){
					$param_val = $c_idx;
					break 2;
				}
			}
		}

		if( $param_val ){
			$termlink = $this->make_appropriate_search_url($param_key, $param_val);
		}

		return $termlink;
	}

	protected function get_apply_taxonomies_for_hook_term_link(){
		static $apply_taxonomies;
		if( is_array($apply_taxonomies) ) return $apply_taxonomies;

		$apply_taxonomies = array();
		$search_components = $this->get_the_search_components();
		foreach( $search_components as $cmpnt_key => $cmpnts ){
			$tax_query = $this->get_str_if_isset($cmpnts, 'tax_query');
			$tax_slug = $this->get_slug_parsed_tax_query($tax_query);
			$cmpnt_values = $this->get_arr_if_isset($cmpnts, 'values');
			if( !$tax_slug || !$cmpnt_values ) continue;

			$vals = array();
			foreach( $cmpnt_values as $c_idx => $v_arr ){
				foreach( $v_arr as $v_idx => $v_info ){
					if( !is_numeric($v_idx) ) continue;

					$vals[$c_idx][$v_idx] = $this->get_if_isset($v_info, 'val');
				}
			}
			$apply_taxonomies[$tax_slug] = array(
				'key' => $cmpnt_key, 
				'values' => $vals, 
			);
		}

		return $apply_taxonomies;
	}

}
