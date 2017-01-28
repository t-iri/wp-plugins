<?php 
class myFudoArchiveManager extends myBaseFunctionsWP {
	protected $per_request_key = 'per';
	protected $ordby_request_key = 'ordby';
	protected $sort_components = array(
		'per' => array(20, 40, 60), 
		'ordby' => array(
			array(
				'orderby' => 'date', 
				'order' => 'DESC', 
				'display' => '新着順', 
			), 
			array(
				'orderby' => 'meta/kakaku', 
				'order' => 'ASC', 
				'display' => '価格の安い順', 
			), 
			array(
				'orderby' => 'meta/kakaku', 
				'order' => 'DESC', 
				'display' => '価格の高い順', 
			), 
			array(
				'orderby' => 'meta/kakakuhyorimawari', 
				'order' => 'DESC', 
				'display' => '利回りの高い順', 
				'meta_type' => 'DECIMAL', 
			), 
			array(
				'orderby' => 'meta/kakakuhyorimawari', 
				'order' => 'ASC', 
				'display' => '利回りの低い順', 
				'meta_type' => 'DECIMAL', 
			), 
			array(
				'orderby' => 'meta/tatemonochikunenn', 
				'order' => 'DESC', 
				'display' => '築年の新しい順', 
			), 
			array(
				'orderby' => 'meta/tatemonochikunenn', 
				'order' => 'ASC', 
				'display' => '築年の古い順', 
			), 
			array(
				'orderby' => 'modified', 
				'order' => 'DESC', 
				'display' => '更新日の新しい順', 
			), 
			array(
				'orderby' => 'modified', 
				'order' => 'ASC', 
				'display' => '更新日の古い順', 
			), 
		), 
	);
	protected $search_components = array(
		'pref' => array(
			'meta_keys' => array('shozaichiken'), 
			'itype' => 'select', 
			'dtype' => 'string', 
			'first_display' => '都道府県', 
			'values' => array(), 
			'fudo_key' => 'middle_area', 
			'dont_search_after' => 'city', 
		), 
		'city' => array(
			'meta_keys' => array('shozaichicode'), 
			'itype' => 'select', 
			'dtype' => 'string', 
			'first_display' => '市区町村', 
			'values' => array(), 
			'fudo_key' => 'narrow_area', 
			'order_prior_to' => 'pref', 
		), 
		'kakaku:ge' => array(
			'itype' => 'select', 
			'dtype' => 'integer', 
			'values' => array(), 
		), 
		'kakaku:le' => array(
			'itype' => 'select', 
			'dtype' => 'integer', 
			'values' => array(), 
		), 
		'chikunenn' => array(
			'meta_keys' => array('tatemonochikunenn'), 
			'itype' => 'select', 
			'dtype' => 'integer', 
			'values' => array(
				array(
					'display' => '新築', 
					array(
						'key' => 'tatemonoshinchiku', 
						'compare' => '=', 'val' => 1, 
					), 
				), 
				array(
					'display' => '5年以内', 
					array( 'compare' => '<=', 'val' => 5 ), 
				), 
				array(
					'display' => '10年以内', 
					array( 'compare' => '<=', 'val' => 10 ), 
				), 
				array(
					'display' => '15年以内', 
					array( 'compare' => '<=', 'val' => 15 ), 
				), 
				array(
					'display' => '20年以上', 
					array( 'compare' => '>=', 'val' => 20 ), 
				), 
			), 
		), 
		'toho' => array(
			'meta_keys' => array('koutsutoho1f', 'koutsutoho2f'), 
			'itype' => 'select', 
			'dtype' => 'integer', 
			'values' => array(
				array(
					'display' => '5分以内', 
					array( 'compare' => '<=', 'val' => 5 ), 
				), 
				array(
					'display' => '10分以内', 
					array( 'compare' => '<=', 'val' => 10 ), 
				), 
				array(
					'display' => '15分以内', 
					array( 'compare' => '<=', 'val' => 15 ), 
				), 
				array(
					'display' => '20分以内', 
					array( 'compare' => '<=', 'val' => 20 ), 
				), 
				array(
					'display' => '20分以上', 
					array( 'compare' => '>=', 'val' => 20 ), 
				), 
			), 
		), 
		'bknshu' => array(
			'meta_keys' => array('bukkenshubetsu'), 
			'itype' => 'select', 
			'dtype' => 'integer', 
			'values' => array(), 
			'fudo_key' => 'bukkenshubetsu', 
		), 
		'kdwrs' => array(
			'tax_query' => 'bukken/selected_type', 
			'itype' => 'select', 
			'values' => array(), 
		), 
		'kdwrk' => array(
			'tax_query' => 'bukken/selected_search', 
			'itype' => 'select', 
			'values' => array(), 
		), 
		'rimawari' => array(
			'meta_keys' => array('kakakuhyorimawari'), 
			'itype' => 'select', 
			'dtype' => 'float', 
			'values' => array(
				array(
					'display' => '4%以上', 
					array( 'compare' => '>=', 'val' => 4 ), 
				), 
				array(
					'display' => '5%以上', 
					array( 'compare' => '>=', 'val' => 5 ), 
				), 
				array(
					'display' => '6%以上', 
					array( 'compare' => '>=', 'val' => 6 ), 
				), 
				array(
					'display' => '7%以上', 
					array( 'compare' => '>=', 'val' => 7 ), 
				), 
				array(
					'display' => '8%以上', 
					array( 'compare' => '>=', 'val' => 8 ), 
				), 
				array(
					'display' => '9%以上', 
					array( 'compare' => '>=', 'val' => 9 ), 
				), 
				array(
					'display' => '10%以上', 
					array( 'compare' => '>=', 'val' => 10 ), 
				), 
			), 
		), 
		'tkozo' => array(
			'meta_keys' => array('tatemonokozo'), 
			'itype' => 'select', 
			'dtype' => 'integer', 
			'values' => array(
				array(
					'display' => '木造', 
					array( 'compare' => '=', 'val' => 1 ), 
				), 
/* 
				array(
					'display' => 'ブロック', 
					array( 'compare' => '=', 'val' => 2 ), 
				), 
 */
				array(
					'display' => '鉄骨造', 
					array( 'compare' => '=', 'val' => 3 ), 
				), 
				array(
					'display' => 'RC', 
					array( 'compare' => '=', 'val' => 4 ), 
				), 
				array(
					'display' => 'SRC', 
					array( 'compare' => '=', 'val' => 5 ), 
				), 
/* 
				array(
					'display' => 'PC', 
					array( 'compare' => '=', 'val' => 6 ), 
				), 
 */
/* 
				array(
					'display' => 'HPC', 
					array( 'compare' => '=', 'val' => 7 ), 
				), 
 */
				array(
					'display' => 'その他', 
					array( 'compare' => '=', 'val' => 9 ), 
				), 
/* 
				array(
					'display' => '軽量鉄骨', 
					array( 'compare' => '=', 'val' => 10 ), 
				), 
 */
/* 
				array(
					'display' => 'ALC', 
					array( 'compare' => '=', 'val' => 11 ), 
				), 
 */
/* 
				array(
					'display' => '鉄筋ブロック', 
					array( 'compare' => '=', 'val' => 12 ), 
				), 
 */
/* 
				array(
					'display' => 'CFT(コンクリート充填鋼管)', 
					array( 'compare' => '=', 'val' => 13 ), 
				), 
 */
			), 
		), 
		'tmenseki' => array(
			'meta_keys' => array('tochikukaku'), 
			'itype' => 'select', 
			'dtype' => 'float', 
			'values' => array(
				array(
					'display' => '50m&sup2;以内', 
					array( 'compare' => '<=', 'val' => 50 ), 
				), 
				array(
					'display' => '100m&sup2;以内', 
					array( 'compare' => '<=', 'val' => 100 ), 
				), 
				array(
					'display' => '200m&sup2;以内', 
					array( 'compare' => '<=', 'val' => 200 ), 
				), 
				array(
					'display' => '300m&sup2;以内', 
					array( 'compare' => '<=', 'val' => 300 ), 
				), 
				array(
					'display' => '300m&sup2;以上', 
					array( 'compare' => '>=', 'val' => 300 ), 
				), 
			), 
		), 
		'tkenri' => array(
			'meta_keys' => array('tochikenri'), 
			'itype' => 'checkbox', 
			'dtype' => 'integer', 
			'values' => array(
				array(
					'display' => '所有権のみ', 
					array( 'compare' => '=', 'val' => 1 ), 
				), 
			), 
		), 
	);
	protected $search_fixed_values = array(
		'kakaku' => array(
			50000000, 
			60000000, 
			70000000, 
			80000000, 
			90000000, 
			100000000, 
			200000000, 
			300000000, 
			400000000, 
			500000000, 
		), 
	);
	protected $fudo_shubetsu_group = array(
		'土地' => array(
			1101, 1102, 1103, 1104, 
		), 
		'一棟商業ビル' => array(
			1401, 1403, 1404, 1405, 1406, 1407, 1408, 
			1410, 
			1412, 1413, 1414, 1415, 1416, 1420, 1421, 1499, 
		), 
		'一棟マンション' => array(
			1409, 
		), 
		'一棟アパート' => array(
			1411, 
		), 
	);

	protected $force_search_arg_key = 'force_fudo_search';
	protected $ignoring_force_search_arg = false;
	protected $searched_words = array();
	protected $archive_search_reset_key = 's_noparam';

	public function __construct(){
		$this->reset_search_form_if_isset();
		$this->action_filters();
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
		$red_to = add_query_arg('s', '', $red_to);

		$this->exec_wp_safe_redirect($red_to);

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
		if( !$key ) return $req_cmpnt_keys;

		if( 'all' === $key ){
			$req_cmpnt_keys = array_reduce($req_cmpnt_keys, 'array_merge', array());
		} else {
			$req_cmpnt_keys = $this->get_arr_if_isset($req_cmpnt_keys, $key);
		}
		return $req_cmpnt_keys;
	}

	protected function action_filters(){
		add_action('parse_query', array($this, 'hook_parse_query_for_sort_and_search'));
		add_action('pre_get_posts', array($this, 'hook_pre_get_posts_for_sort_and_search'), 15, 1);

		$this->activate_fudo_shubetsu_group();
		add_action('init', array($this, 'activate_search_fudo'), 20, 1);
	}

	protected function activate_fudo_shubetsu_group(){
		if( !function_exists('work_bukkenshubetsu_init_fudou') ) return;

		remove_action('init', 'work_bukkenshubetsu_init_fudou');
		add_action('init', array($this, 'replace_fudo_shubetsu_group'));
	}

	public function replace_fudo_shubetsu_group(){
		work_bukkenshubetsu_init_fudou();

		global $work_bukkenshubetsu;
		$shubetsu_group = $this->fudo_shubetsu_group;
		if( !$work_bukkenshubetsu || !$shubetsu_group ) return;

		foreach( $work_bukkenshubetsu as $id => $values ){
			foreach( $shubetsu_group as $group_name => $ids ){
				if( in_array($id, $ids, true) ){
					$work_bukkenshubetsu[$id]["name"] = $group_name;
					break;
				}
			}
		}
	}

	protected function get_the_sort_components($key){
		return $this->get_the_arr_val($this->sort_components, $key);
	}

	protected function get_the_search_components($key=''){
		return $this->get_the_arr_val($this->search_components, $key);
	}

	protected function get_the_arr_val($any, $key=''){
		$key = (string)$key;
		if( '' !== $key ) $any = $this->search_arr_val_deeply( $any, $key );
		return $any;
	}

	protected function is_having_force_search_arg($query){
		if( !$this->is_wp_query($query) ) return false;

		$val = $query->get($this->force_search_arg_key, false);
		return (bool)$val;
	}

	public function hook_parse_query_for_sort_and_search($query){
		if( $this->ignoring_force_search_arg ) return;

		$keys = $this->get_request_component_keys('all');
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

			$query->set($key, $g_val);
			$query->set($this->force_search_arg_key, true);
		}
	}

	public function hook_pre_get_posts_for_sort_and_search($query){
		if( is_admin() ) return;
		if( !$query->is_main_query() && !$this->is_having_force_search_arg($query) ) return;
		if( $this->ignoring_force_search_arg ) return;

		$this->set_fudo_sort_query($query);
		$this->set_fudo_search_query($query);
	}

	protected function set_fudo_sort_query($query){
		$set_val = $this->get_appropriate_query_val($query, $this->per_request_key);
		if( NULL !== $set_val ){
			$query->set( 'posts_per_page', $set_val );
		}

		$set_val = $this->get_appropriate_query_val($query, $this->ordby_request_key);
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

	protected function set_query_meta_orderby($query, $ordby, $order, $meta_type){
		if( !$this->is_wp_query($query) ) return false;

		$meta_key = $ordby;

		$query->set('meta_key', $meta_key);
		$query->set('meta_type', $meta_type);
		$query->set('orderby', 'meta_value');
		$query->set('order', $order);
	}

	protected function get_appropriate_query_val($query, $key){
		if( !$this->is_wp_query($query) ) return false;

		$cmpnts = $this->get_the_sort_components($key);
		if( !is_array($cmpnts) || !isset($cmpnts[0]) ) return NULL;

		$q_val = (int)$query->get($key, 0);
		$v = isset($cmpnts[$q_val]) ? $cmpnts[$q_val] : $cmpnts[0];
		return $v;
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

	protected function get_archive_input_html($key, $vals, $args, $itype){
		$ipt = '';
		if( !$this->is_valid_arr($vals) ) return $ipt;

		$defaults = array(
			'class' => '', 
			'value_type' => '', 
			'search_url' => '', 
			'display_prefix' => '', 
			'display_suffix' => '', 
			'first_display' => '', 
			'input_type' => $itype, 
			'tax_query' => '', 
		);
		$args = $this->recursive_parse_args($defaults, $args, true);

		$select_cls = $this->get_if_isset($args, 'class');
		$select_cls = is_array($select_cls) ? $select_cls : array_filter( explode(' ', $select_cls) );
		$val_type = $this->get_str_if_isset($args, 'value_type');
		$search_url = $this->get_str_if_isset($args, 'search_url');
		$search_base_url = ( $search_url ) ? $search_url : false;
		$disp_prfx = $this->get_str_if_isset($args, 'display_prefix');
		$disp_sffx = $this->get_str_if_isset($args, 'display_suffix');
		$frst_dsply = $this->get_str_if_isset($args, 'first_display');
		$itype = $this->get_str_if_isset($args, 'input_type');

		$keys = explode(':', $key);
		$key = $keys[0];
		$iname = $key;
		$search_key = $key;
		if( isset($keys[1]) ){
			$iname .= '['.$keys[1].']';
			$search_key .= '/' .  $keys[1];
		}
		$req_val = $this->search_arr_val_deeply($_REQUEST, $search_key);

		$tax_query = $this->get_str_if_isset($args, 'tax_query');
		$tax_val = "";
		if( $tax_query && is_tax() ){
			static $tax_component = NULL;
			if( NULL === $tax_component ){
				 $tax_component = $this->get_the_tax_component_by_query();
			}
			$cmpnt_key = $this->get_str_if_isset($tax_component, 'key');
			if( $search_key === $cmpnt_key && isset($tax_component['idx']) ){
				$tax_val = (string)$tax_component['idx'];
			}
		}

		if( "" !== $tax_val ){
			if( is_array($req_val) ){
				$req_val[] = $tax_val;
			} elseif( "" === (string)$req_val ) {
				$req_val = $tax_val;
			}
		}

		switch($itype){
			case 'select':
				$req_val = (string)$req_val;
				$has_data = array();
				$select_groups = array();
				foreach( $vals as $idx => $v ){
					$group_key = 0;
					$group_name = $this->get_str_if_isset($v, 'group_name');
					if( $group_name ){
						$group_key = $group_name;
					}

					$attr = '';

					$ipt_val = (string)$idx;
					if( $ipt_val === $req_val ){
						$attr .= $this->make_prop('selected');
					}
					if( 'url' === $val_type ){
						$ipt_val = add_query_arg($key, $ipt_val, $search_base_url);
						$ipt_val = $this->convert_search_or_sort_query_url($ipt_val);
					}
					$attr .= $this->make_attr('value', $ipt_val);

					$data_name = explode('/', $this->get_str_if_isset($v, 'data_name'), 2);
					if( isset($data_name[1]) ){
						$attr .= $this->make_attr('data-' . $data_name[0], $data_name[1]);
						$has_data[$group_key] = $data_name[0];
					}

					$disp_val = $this->make_values_display($v, $disp_prfx, $disp_sffx);

					$opt = $this->make_html_tag('option', $attr, $disp_val);

					if( $frst_dsply && !isset($select_groups[$group_key]) ){
						$attr = ( '' === $req_val ) ? $this->make_prop('selected') : '';
						$attr .= $this->make_attr('value', '');
						$select_groups[$group_key][] = $this->make_html_tag('option', $attr, $frst_dsply);
					}
					$select_groups[$group_key][] = $opt;
				}
				if( !$select_groups ) break;

				foreach( $select_groups as $group_key => $opts ){
					$attr = $this->make_attr('name', $iname);
					$group_cls = $select_cls;
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
						$this->activate_select_group_script();
					}

					if( $group_cls ){
						$attr .= $this->make_cls_attr( implode(' ', $group_cls) );
					}
					$ipt .= $this->make_html_tag( 'select', $attr, implode("\n", $opts) );
				}
				break;

			case 'checkbox':
			case 'radio':
				$req_val = ( 'checkbox' === $itype ) ? (array)$req_val : (string)$req_val;
				foreach( $vals as $idx => $v ){
					$attr = '';
					$chk_id = $iname . '__' . (string)$idx;

					$ipt_val = (string)$idx;
					if( 
						( is_array($req_val) && in_array($ipt_val, $req_val, true) ) 
						|| ( $ipt_val === $req_val ) 
					){
						$attr .= $this->make_prop('checked');
					}
					$ipername = $iname;
					if( 'checkbox' === $itype ) $ipername .= '[]';

					$attr .= $this->make_attr('name', $ipername);
					$attr .= $this->make_attr('id', $chk_id);

					$ieach = $this->make_ipt_tag($itype, $ipt_val, $attr);

					$disp_val = $this->make_values_display($v, $disp_prfx, $disp_sffx);

					$attr = $this->make_attr('for', $chk_id);
					$ieach .= $this->make_html_tag('label', $attr, $disp_val);

					$ipt .= $ieach;
				}
				break;
		}

		return $ipt;
	}

	protected function make_values_display($v, $disp_prfx, $disp_sffx){
		$disp_val = is_array($v) ? $this->get_str_if_isset($v, 'display') : $v;
		$disp_val = $disp_prfx . $disp_val . $disp_sffx;
		if( isset($v['count']) ){
			$disp_val .= "(".(string)$v['count'].")";
		}
		return $disp_val;
	}

/**************************************************************/
/*************************** search ***************************/
/**************************************************************/
	public function activate_search_fudo(){
		if( is_admin() ) return;

		$this->set_search_components();
		add_action('wp', array($this, 'hook_for_search'));
		add_action('wp', array($this, 'redirect_if_is_search_tax'), 1);

	}

	protected $current_searching_type = '';
	protected function set_current_searching_type($type){
		$this->current_searching_type = $type;
	}

	protected function clear_current_searching_type(){
		$this->current_searching_type = '';
	}

	protected function is_current_searching_type($type){
		return ( $type && $type === $this->current_searching_type );
	}

	protected function set_fudo_search_query($query){
		if( $query->is_main_query() ){
			if( $query->is_search() ){
				$query->set('post_type', 'fudo'); //avoid post_type 'any'
			}

			if( $this->is_search_page($query) ){
				$this->set_count_per_search_display($query);
			}
		}

		$srch_components = $this->get_the_search_components();
		if( !$this->is_valid_arr($srch_components) ) return;

		/* components' relation AND */
		$post_in = false;
		$is_zero_posts = false; //to store searched_words
		$this->set_current_searching_type('query');
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
			//Cancel search process if got the target key's word, means the exec has already completed
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

					$per_ids = $this->get_fudo_post_ids_for_search_query($ex_keys, $values, $cmpnts, $q_type);
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

		$this->set_current_searching_type('count');
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

				$per_ids = $this->get_fudo_post_ids_for_search_query($meta_keys, $values, $cmpnts);
				$cmpnt_values[$idx]['count'] = ( $per_ids ) ? count($per_ids) : 0;
			}

			$srch_components[$cmpnt_key]['values'] = $cmpnt_values;
		}
		$this->clear_current_searching_type();

		$this->search_components = $srch_components;
	}

	protected function get_fudo_post_ids_for_search_query($ex_keys, $values, $cmpnts, $q_type=''){
		if( !$this->is_valid_arr($ex_keys) || !$this->is_valid_arr($values) ) return false;

		$allowed_types = array('meta', 'tax');
		$q_type = in_array($q_type, $allowed_types, true) ? $q_type : $allowed_types[0];
		$q_key = $q_type . '_query';

		$d_type = $this->get_str_if_isset($cmpnts, 'dtype');
		$d_type = ( 'meta' === $q_type  ) ? $this->get_sql_data_type($d_type) : $d_type;
		$base_args = array(
			'post_type' => 'fudo', 
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
			$prime_key = explode(':', $cmpnt_key, 2);
			$sub_key = $this->get_str_if_isset($prime_key, 1);
			$prime_key = $prime_key[0];

			$prior_to = $this->get_str_if_isset($cmpnts, 'order_prior_to');
			if( $prior_to ){
				$exist_pos = array_keys($prioritize_keys, $prior_to);
				if( !$exist_pos ){
					$prioritize_keys[] = $cmpnt_key;
					$prioritize_keys[] = $prior_to;
				} else {
					$exist_pos = $exist_pos[0];
					array_splice($prioritize_keys, $exist_pos, 0, $cmpnt_key);
				}
			}

			$values = $this->get_arr_if_isset($cmpnts, 'values');
			$fxd_vals = $this->get_arr_if_isset($fixed_values, $prime_key);

			$tax_query = $this->get_str_if_isset($cmpnts, 'tax_query');
			$fudo_key = $this->get_str_if_isset($cmpnts, 'fudo_key');
			$meta_keys = $this->get_arr_if_isset($cmpnts, 'meta_keys');

			switch( true ){
				case ( $fxd_vals ):
					$values = $this->get_search_fixed_values($values, $prime_key, $sub_key, $fxd_vals);
					break;

				case ( $tax_query ):
					$values = $this->get_search_tax_terms_values($values, $prime_key, $sub_key, $tax_query);
					break;

				case ( 'bukkenshubetsu' === $fudo_key ):
					$values = $this->get_bukken_shubetsu_dynamic_values($values);
					break;

				case ( $fudo_key ):
					$values = $this->get_area_dynamic_values($values, $fudo_key);
					break;

				default:
					$values = $this->get_search_dynamic_values($values, $prime_key, $sub_key);
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

			$txnmy = explode('/', $tax_query, 2);
			$term_slug = $this->get_str_if_isset($txnmy, 1);
			$txnmy = $txnmy[0];

			foreach( $cmpnt_values as $cmpnt_idx => $values ){
				foreach( $values as $vls_idx => $vls ){
					if( !is_numeric($vls_idx) ) continue;

					$term_id = $this->get_int_if_isset($vls, 'val');
					$term = get_term($term_id, $txnmy);
					if( 
						!$this->is_wp_term($term) 
						|| !$this->is_the_term_archive($txnmy, $term_id, $query) 
					) continue;

					$tax_component['key'] = $cmpnt_key;
					$tax_component['idx'] = $cmpnt_idx;
					break 3;
				}
			}
		}

		return $tax_component;
	}

	protected function get_search_fixed_values($values, $prime_key, $sub_key, $fxd_vals){
		foreach( $fxd_vals as $fv ){
			$cmpr = $this->get_the_compare_value('operator', $sub_key);
			$dsply = $this->make_search_value_display($fv, $prime_key, $sub_key);
			$values[] = array(
				'display' => $dsply, 
				array( 'compare' => $cmpr, 'val' => $fv ), 
			);
		}
		return $values;
	}

	protected function make_search_value_display($val, $prime_key, $sub_key){
		$dsply = '';
		switch($prime_key){
			case 'kakaku':
				$val = (int)$val;
				$digits = array(
					'', '万', '億', '兆', 
				);
				foreach( $digits as $unit ){
					if( preg_match("/^(\d+?)([\d]{4})$/u", $val, $mt) ){
						$dsply = preg_match("/^0+$/u", $mt[2]) ? $dsply : strval( (int)$mt[2] ) . $unit . $dsply;
						$val = $mt[1];
						continue;
					}
					$dsply = $val . $unit . $dsply;
					break;
				}
				if( !$dsply ) break;

				$dsply .= '円';
				$compare = $this->get_the_compare_value('display', $sub_key);
				$dsply .= ( $compare ) ? $compare : '';

				break;

			default:
				$dsply = $val;
				break;
		}
		return $dsply;
	}

	protected function get_search_tax_terms_values($values, $prime_key, $sub_key, $tax_query){
		$txnmy = explode('/', $tax_query, 2);
		$term_slug = $this->get_str_if_isset($txnmy, 1);
		$txnmy = $txnmy[0];

		$args = array(
			"taxonomy" => $txnmy, 
			"hide_empty" => false, 
		);

		$term_id = $this->get_wp_term_by_slug($txnmy, $term_slug, 'term_id');
		if( $term_id ){
			$args["parent"] = $term_id;
		}

		$terms = get_terms($args);
		if( !$terms || is_wp_error($terms) ) return $values;

		foreach($terms as $term){
			$values[] = array(
				'display' => $term->name, 
				array( 'compare' => 'IN', 'val' => $term->term_id ), 
				'count' => $term->count, 
			);
		}

		return $values;
	}

	protected function get_search_dynamic_values($values, $prime_key, $sub_key){
		//as need arises
		return $values;
	}

	protected function get_area_dynamic_values($values, $area_key){
		$area_info = array(
			'middle_area' => array(
				'meta_key' => 'shozaichiken', 
				'pad_length' => 2, 
			), 
			'narrow_area' => array(
				'meta_key' => 'shozaichicode', 
				'pad_length' => 5, 
				'column_id' => 'city_area', 
				'meta_substr' => 5, 
				'val_suffix' => '000000', 
			), 
		);
		$area_info = $this->get_arr_if_isset($area_info, $area_key);
		if( !$area_info ) return $values;

		$meta_key = $this->get_str_if_isset($area_info, 'meta_key');
		$pad_length = $this->get_int_if_isset($area_info, 'pad_length');

		$id_prfx = isset($area_info['column_id']) ? $area_info['column_id'] : $area_key;
		$name_prfx = isset($area_info['name']) ? $area_info['name'] : $area_key;

		$meta_substr = $this->get_int_if_isset($area_info, 'meta_substr');
		$data_name = $this->get_str_if_isset($area_info, 'data_name');
		$group_name = $this->get_str_if_isset($area_info, 'group_name');
		$val_sffx = $this->get_str_if_isset($area_info, 'val_suffix');

		$eq_mval = 'pm.meta_value';
		if( $meta_substr ){
			$eq_mval = "SUBSTRING({$eq_mval}, 1, {$meta_substr})";
		}
		$eq_mval = "CAST( {$eq_mval} AS UNSIGNED )";

		global $wpdb;
		$tbl = $wpdb->prefix . "area_{$area_key}";
		$search_post_where = $this->get_search_posts_where_sql();
		$query = $wpdb->prepare("SELECT 
			tgt.{$id_prfx}_id as area_id, 
			tgt.{$name_prfx}_name as area_name, 
			COUNT( tgt.{$id_prfx}_id ) AS area_ct 
			FROM {$tbl} AS tgt 
			INNER JOIN {$wpdb->postmeta} AS pm 
				ON tgt.{$id_prfx}_id = {$eq_mval} 
				AND pm.meta_key = %s 
			INNER JOIN {$wpdb->posts} AS p 
				ON pm.post_id = p.ID 
				AND {$search_post_where} 
			GROUP BY area_id ", 
			$meta_key  
		);
		$rows = $wpdb->get_results($query, ARRAY_A);
		if( !$rows ) return $values;

		$data_name_for_group = "middle-area";
		foreach( $rows as $row ){
			$area_id = $row['area_id'];
			$area_val = str_pad($area_id, $pad_length, "0", STR_PAD_LEFT);
			if( $val_sffx ){
				$area_val .= $val_sffx;
			}
			$insert = array(
				'display' => $row['area_name'], 
				array( 'compare' => '=', 'val' => $area_val ), 
				'count' => $row['area_ct'], 
			);

			if( 'middle_area' === $area_key ){
				$insert['data_name'] = $data_name_for_group . '/' . (string)$area_id;
			}

			if( 'narrow_area' === $area_key ){
				$id_digit = (int)mb_substr( $area_id, 0, -3 ); //1 or 2 digit
				$insert['group_name'] = $data_name_for_group . '/' . (string)$id_digit;
				$insert['searched_word'] = $this->get_fudo_middle_area_name($id_digit) . $insert['display'];
			}

			$values[] = $insert;
		}

		return $values;
	}

	protected function get_bukken_shubetsu_dynamic_values($values){
		global $wpdb;
		$search_post_where = $this->get_search_posts_where_sql();
		$query = $wpdb->prepare("SELECT 
			pm.meta_value as m_val, 
			COUNT( pm.meta_value ) AS m_ct 
			FROM {$wpdb->postmeta} AS pm 
			INNER JOIN {$wpdb->posts} AS p 
				ON pm.post_id = p.ID 
				AND {$search_post_where} 
			WHERE pm.meta_key = %s 
			GROUP BY m_val ", 
			"bukkenshubetsu" 
		);
		$rows = $wpdb->get_results($query, ARRAY_A);
		if( !$rows ) return $values;

		global $work_bukkenshubetsu;
		$shubetsu_names = array();
		foreach( $work_bukkenshubetsu as $id => $vals ){
			$fudo_name = $this->get_str_if_isset($vals, 'name');
			if( $fudo_name ){
				$shubetsu_names[$fudo_name][] = (int)$id;
			}
		}

		$insert_shubetsu = array();
		foreach( $rows as $row ){
			$shubetsu_key = $row['m_val'] . "/name";
			$display = $this->search_arr_val_deeply($work_bukkenshubetsu, $shubetsu_key);
			if( !$display ) continue;

			if( isset($shubetsu_names[$display]) ){
				$insert_shubetsu[$display]['ids'] = $shubetsu_names[$display];
				$insert_shubetsu[$display]['ct'] = $row['m_ct'];
			} else {
				$insert_shubetsu[$display]['ct'] += (int)$row['m_ct'];
			}

		}

		foreach( $insert_shubetsu as $display => $arr ){
			$values[] = array(
				'display' => $display, 
				array( 'compare' => 'IN', 'val' => $arr['ids'] ), 
			);
		}

		return $values;
	}

	protected function get_fudo_middle_area_name($id){
		static $middle_area_names = NULL;
		if( NULL !== $middle_area_names ){
			$name = $this->get_str_if_isset($middle_area_names, $id);
			return $name;
		}

		$middle_area_names = array();

		global $wpdb;
		$tbl = $wpdb->prefix . "area_middle_area";
		$query = $wpdb->prepare("SELECT 
			tgt.middle_area_id as area_id, 
			tgt.middle_area_name as area_name 
			FROM {$tbl} AS tgt 
			WHERE 1 = %d ", 
			1 
		);
		$rows = $wpdb->get_results($query, ARRAY_A);
		if( $rows ){
			foreach( $rows as $row ){
				$area_id = $row['area_id'];
				$middle_area_names[$area_id] = $row['area_name'];
			}
		}

		$name = $this->get_str_if_isset($middle_area_names, $id);
		return $name;
	}

	protected function get_search_posts_where_sql($table_alias='p'){
		$table_alias = is_string($table_alias) ? esc_sql($table_alias) . '.' : '';
		$where = "( {$table_alias}post_type = 'fudo'";
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

		$itype = $this->get_str_if_isset($components, 'itype');
		$values = $this->get_arr_if_isset($components, 'values');
		$frst_dsply = $this->get_str_if_isset($components, 'first_display');
		$args['first_display'] = ( '' !== $frst_dsply ) ? $frst_dsply : '---';

		$tax_query = $this->get_str_if_isset($components, 'tax_query');
		if( $tax_query ){
			$args['tax_query'] = $tax_query;
		}

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
		if( is_tax( array('bukken', 'bukken_tag') ) ){

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

	protected function activate_select_group_script(){
		add_action('wp_footer', array($this, 'output_select_group_script'), 20);
	}

	public function output_select_group_script(){
 ?>
<script>
(function($){
	var selector = 'select.sgroup-switch';
	var $selectGroupSwitchParents = $(selector).parent();
	if( !$selectGroupSwitchParents.length ) return;

	var showEle = function(ele){
		if( $(ele).length ) $(ele).css('display', '').prop('disabled', false);
	}

	var hideEle = function(ele){
		if( $(ele).length ) $(ele).css('display', 'none').prop('disabled', true);
	}

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
		/* 
			you must pass two-dimensional array like below
			$arr = array( 
				key => array(), 
				...
			);
		 */
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

	public function convert_search_or_sort_query_url($url){
		$url = remove_query_arg('paged', $url);
		return $url;
	}

	public function hook_for_search(){

		$this->hook_for_search_title();

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

		if( doing_filter('bcn_breadcrumb_title') ){
			$func_args = func_get_args();
			$type = $func_args[1];
			if( isset($type[0]) && 'search' !== $type[0] ) return $title;
		}

		$searched_words = $this->searched_words;
		$searched_words['s'][] = get_search_query();
		foreach( $searched_words as $cmpnt_key => $svars ){
			$searched_words[$cmpnt_key] = implode( ', ', array_filter($svars) );
		}
		$searched_words = array_filter($searched_words);
		$search_phrase = implode( ' / ', $searched_words );
		if( $search_phrase ) $search_phrase .= 'の';
		if( is_tax() ){
			$obj = get_queried_object();
			$search_phrase = "「". esc_html($obj->name) ."」" . $search_phrase;
		}

		if( $search_phrase || is_search() ){
			$title = sprintf('%s検索結果', $search_phrase);
		}

		return $title;
	}

	private $use_redirect_in_search_tax = false;
	public function redirect_if_is_search_tax(){
		if( !$this->use_redirect_in_search_tax ) return;

		$tax_component = $this->get_the_tax_component_by_query();
		$cmpnt_key = $this->get_str_if_isset($tax_component, 'key');
		if( $cmpnt_key && isset($tax_component['idx']) ){
			$cmpnt_idx = $tax_component['idx'];
			$red_to = $this->get_search_url();
			$red_to = add_query_arg($cmpnt_key, $cmpnt_idx, $red_to);
			$this->exec_wp_safe_redirect($red_to);
		}
	}

}
