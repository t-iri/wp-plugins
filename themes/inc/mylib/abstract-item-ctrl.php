<?php 
abstract class MyUscesItemCtrlAbstract {
	protected $item_taxonomies = array();
	protected $item_archive_template_name = '';
	protected $replace_term_slugs = array(
/* this is just a sample values
		%taxonomy% => array(
			%term_slug% => %replace_slug%, 
		), 
	//example...
		'category' => array(
			'item' => 'shop/item', 
		), 
 */
	);
	protected $replace_item_post_base_slug = '';
	protected $archive_items_query_args = array(
		'category' => array(
			'orderby' => array( 'modified' => 'DESC', 'ID' => 'DESC' ), 
		), 
	);

	protected $rewrite_vars = array(
		'rule' => array(), 
		'query_var' => array(), 
		'init_flush' => false, //be careful to set true
	);

	protected $item_category_id;
	protected $search_metas = array(
		'_itemCode', 
	);

	protected $uneditable_csmb_keys = array(
/* this is just a sample values
		%csmb_key% => array(
			'hide_memberedit' => 0 / 1 / 2 / 3, //show / hide / show but value '-' and disabled / show any value but disabled
			'hide_mail' => 0 / 1, //show / hide 
		), 
	//example...
		'company_id' => array(
			'hide_memberedit' => 1, 
			'hide_mail' => 0, 
		), 
 */
	);

	protected $campaign_terms = array();

	protected $debugs = array();

	protected $item_acf_id = 0;

	public function __construct($args=array()){

		$this->set_vars();
		$this->set_campaign_terms();
		$this->activate_item_taxonomies();
		$this->activate_archive_item_posts();
		$this->activate_item_template();
		$this->activate_url_rewrite();
		$this->activate_replace_wp_link();
		$this->activate_item_search();
		$this->activate_uneditable_csmb_keys();

	}

	protected function set_vars(){
		$item_cat = $this->get_item_cat();
		if( $item_cat && $item_cat->term_id ){
			$this->item_category_id = (int)$item_cat->term_id;
		}
	}

/******************************/

	protected function get_item_cat(){
		$cat = get_category_by_slug('item');
		return $this->is_wp_term($cat) ? $cat : array();
	}

	protected function find_the_term_in($presumed_query, $id_key, $slug_key, $tax_name){
		if( is_numeric($presumed_query) ){
			return get_term_by('term_id', (int)$presumed_query, $tax_name);
		}

		global $wp_query;
		$qry = $this->is_wp_query($presumed_query) ? $presumed_query : $wp_query;
		$term_id = (int)$qry->get($id_key, 0);
		if( $term_id ){
			return get_term_by('term_id', $term_id, $tax_name);
		}

		$assumed_slug = basename( $qry->get($slug_key, '') );
		return get_term_by('slug', $assumed_slug, $tax_name);
	}

	protected function is_cat_of_item($presumed_query=NULL){
		if( !function_exists('usces_is_cat_of_item') ) return false;

		$cat_obj = $this->find_the_term_in($presumed_query, 'cat', 'category_name', 'category');
		$cat_id = $this->is_wp_term($cat_obj) ? $cat_obj->term_id : 0;

		return (bool)usces_is_cat_of_item($cat_id);
	}

	protected function is_tag_of_item($presumed_query=NULL){
		$tag_obj = $this->find_the_term_in($presumed_query, 'tag_id', 'tag', 'post_tag');
		return $this->own_filter_is_tag_of_item($tag_obj);
	}

	protected function own_filter_is_tag_of_item($tag_obj){
	//define at your instance
		return false;
	}

	protected function is_wp_term($obj){
		return (bool)( is_object($obj) && $obj instanceof WP_Term );
	}

	protected function is_wp_query($obj){
		return (bool)( is_object($obj) && $obj instanceof WP_Query );
	}

	protected function is_wp_post($obj){
		return (bool)( is_object($obj) && $obj instanceof WP_Post );
	}

	protected function is_usces_item_post($post){
		if( $this->is_wp_post($post) ){
			if( $post->post_type === 'post' && $post->post_mime_type === 'item' ){
				return true;
			}
		}
		return false;
	}

	protected function is_item_page(){
		return (bool)in_array( true, array(
			( $this->is_usces_single_item() ), 
			( is_archive() && ( $this->is_cat_of_item() || $this->is_item_tax() ) ), 
		) );
	}

	protected function is_usces_single_item(){
		return (bool)( is_single() && usces_is_item() );
	}

	protected function is_the_tax_archive($tax, $q=NULL){
		if( 'category' === $tax ) return $this->is_wp_query($q) ? $q->is_category() : is_category();
		if( 'post_tag' === $tax ) return $this->is_wp_query($q) ? $q->is_tag() : is_tag();
		return $this->is_wp_query($q) ? $q->is_tax($tax) : is_tax($tax);
	}

	protected function is_the_term_archive($tax, $term, $q=NULL){
		if( 'category' === $tax ) return $this->is_wp_query($q) ? $q->is_category($term) : is_category($term);
		if( 'post_tag' === $tax ) return $this->is_wp_query($q) ? $q->is_tag($term) : is_tag($term);
		return $this->is_wp_query($q) ? $q->is_tax($tax, $term) : is_tax($tax, $term);
	}

	protected function is_item_tax($q=NULL){
		$taxes = $this->item_taxonomies;
		if( $this->is_wp_query($q) ){
			return (bool)( $taxes && $q->is_tax($taxes) );
		}
		return (bool)( $taxes && is_tax($taxes) );
	}

	protected function is_catchable_global_post(){
		global $post;
		return $this->is_wp_post($post);
	}

	protected function is_admin_usces_item_page($suffix=''){
		if( is_admin() ){
			$page = isset($_GET['page']) ? $_GET['page'] : '';
			$suffix = is_array($suffix) ? $suffix : array($suffix);
			foreach( $suffix as $sffx ){
				if( false !== strpos($page, 'usces_item'.(string)$sffx) ){
					return true;
				}
			}
		}
		return false;
	}

	protected function is_any_tax_term_of_item($q=NULL){
		return (bool)(
			$this->is_cat_of_item($q) //category >> term of item( usces defined )
			|| $this->is_tag_of_item($q)  //post_tag >> term of item( you must define )
			|| $this->is_item_tax($q) //item_tax not considering term( you can define )
		);
	}

	protected function is_assumed_item_query($query){
		if( $this->is_any_tax_term_of_item($query) ) return true;

		$is_item_query = false;
		$item_cat_id = $this->item_category_id;
		if( !$item_cat_id ) return $is_item_query;

	/* 
	 * try to detect item query by the q's other keys
	 * but be careful to call in case expecting too many keys' value exist
	 */
		$search_keys = array(
			'category__in', 
			'category__and', 
			'tax_query', 
		);
		foreach( $search_keys as $qk ){
			$qv = $query->get($qk, array());
			if( !is_array($qv) || empty($qv) ) continue;

			if( 'tax_query' !== $qk ){
				$qv = array_filter( $qv, 'is_numeric' );
				if( !$qv ) continue;

				foreach( $qv as $term_id ){
					$is_item_query = usces_is_cat_of_item($term_id);
					if( $is_item_query ) return $is_item_query; //return to escape
				}
				continue;
			}

			foreach( $qv as $tx_k => $tx_a ){
				$taxonomy = $this->get_str_if_isset($tx_a, 'taxonomy');
				if( 'category' !== $taxonomy ) continue;

				$oprtr = $this->get_str_if_isset($tx_a, 'operator');
				if( !in_array( $oprtr, array('IN', 'AND', 'EXISTS'), true ) ) continue;

				$get_by = $this->get_str_if_isset($tx_a, 'field');
				$term_vals = $this->get_if_isset($tx_a, 'terms');
				$term_vals = is_array($term_vals) ? $term_vals : array($term_vals);
				if( !$get_by || !$term_vals ) continue;

				foreach( $term_vals as $get_val ){
					$term = get_term_by($get_by, $get_val, $taxonomy);
					if( !$this->is_wp_term($term) ) continue;

					$is_item_query = usces_is_cat_of_item($term->term_id);
					if( $is_item_query ) return $is_item_query; //return to escape
				}
			}
		}
		return $is_item_query;
	}

	/***********************************************************
	* Campaign
	***********************************************************/
	//Site is ?
	protected function set_campaign_terms(){
		$terms = $this->campaign_terms;

		global $usces;
		$usces_term = isset($usces->options['campaign_category']) ? (int)$usces->options['campaign_category'] : 0;
		if( $usces_term ){
			$terms['category'][] = $usces_term;
		}
		$this->campaign_terms = $terms;
	}

	function is_site_campaign_mode(){
		if( true === $this->debugs['is_campaign'] ) return true;

		static $bool = NULL;
		if( NULL === $bool ){
			$bool = false;
			$options = get_option('usces');
			$k = 'display_mode';
			$v = 'Promotionsale';
			if( isset($options[$k]) && $options[$k] == $v ){
				$bool = true;
			}
		}
		return $bool;
	}
	//Post is ?
	function is_item_campaign_mode($post=NULL){
		if( $this->is_site_campaign_mode() ){
			if( is_numeric($post) ){
				$post = get_post($post);
			} else {
				if( !$post && $this->is_item_page() ) global $post;
			}
			$campaign_terms = $this->campaign_terms;
			if( $this->is_wp_post($post) && $campaign_terms ){
				foreach( $campaign_terms as $tax => $term_ids ){
					if( !$term_ids || !is_array($term_ids) )  continue;

					foreach( $term_ids as $t_id ){
						if( has_term( (int)$t_id, $tax, $post ) ){
							return true;
						}
					}
				}
			}
		}
		return false;
	}

	function get_site_campaign_date($kind, $date_fmt=''){
		$date_fmt = (string)$date_fmt;
		if( '' === $date_fmt ) $date_fmt = 'Y/m/d H:i:00';

		$timestamp = $this->get_site_campaign_timestamp($kind);
		return ( $timestamp ) ? date($date_fmt, $timestamp) : '';
	}
	function get_site_campaign_timestamp($kind){
		static $timestamps = NULL;
		if( NULL === $timestamps ){
			$timestamps = $this->get_site_campaign_timestamps();
		}
		$kind = (string)$kind;
		return isset($timestamps[$kind]) ? $timestamps[$kind] : false;
	}
	function get_site_campaign_date_info($kind, $key=""){
		static $dates = NULL;
		if( NULL === $dates ){
			$dates = $this->get_site_campaign_dates();
		}

		$date = '';
		$kind = (string)$kind;
		if( isset($dates[$kind]) ){
			$date = $dates[$kind];
			$key = (string)$key;
			if( '' !== $key && isset($date[$key]) ){
				$date = $date[$key];
			}
		}
		return $date;
	}

	protected function get_site_campaign_dates(){
		global $usces;
		$schedules = isset($usces->options['campaign_schedule']) ? $usces->options['campaign_schedule'] : array();
		$dates = array(
			'start' => array(), 
			'end' => array(), 
		);
		$date_separates = array(
			'year' => 0, 
			'month' => 0, 
			'day' => 0, 
			'hour' => 0, 
			'min' => 0, 
		);
		foreach( $dates as $kind => $val ){
			$scdl = isset($schedules[$kind]) ? $schedules[$kind] : array();
			foreach( $date_separates as $dk => $dv ){
				if( isset($scdl[$dk]) ){
					$dv = (int)$scdl[$dk];
					$dv = ( $dk !== 'year' ) ? str_pad($dv, 2, '0', STR_PAD_LEFT) : $dv;
				}
				$date_separates[$dk] = $dv;
			}
			$dates[$kind] = $date_separates;
		}
		return $dates;
	}

	protected function get_site_campaign_timestamps(){
		$dates = $this->get_site_campaign_dates();
		foreach( $dates as $kind => $val ){
			$date_str = $val['year'] . '-' . $val['month'] . '-' . $val['day'];
			$date_str .= ' ' . $val['hour'] . ':' . $val['min'] . ':00';
			$timestamp = strtotime($date_str);
			$dates[$kind] = $timestamp;
		}
		return $dates;
	}

/******************************/

	function activate_item_taxonomies(){
		add_action('do_meta_boxes', array($this, 'allocate_meta_boxes_on_post_edit'), 10, 3);
		add_action('wp_terms_checklist_args', array($this, 'hook_terms_checklist_args'), 10, 2);

		add_action('usces_action_item_master_page', array($this, 'activate_item_csv_filters'), 10, 1);
	}

	function allocate_meta_boxes_on_post_edit($post_type, $context, $post){
		if( !$this->is_post_screen_in_any_context($post_type, $post, $context, 'side') ) return;

		$item_taxonomies = ( $this->item_taxonomies ) ? $this->item_taxonomies : array();
		$is_in_item_screen = array_filter( array(
			( $this->is_admin_usces_item_page( 'new' ) ), 
			( 'item' === $post->post_mime_type ), 
		) );
		$rmv_taxonomies = $item_taxonomies; //normal post edit
		if( $is_in_item_screen ) { //item post edit
			$keep_taxonomies = $rmv_taxonomies;
			$rmv_taxonomies = get_taxonomies( array(
				'_builtin' => false, 
			), 'names' );
			$rmv_taxonomies = is_array($rmv_taxonomies) ? $rmv_taxonomies : array();
			$rmv_taxonomies = array_diff($rmv_taxonomies, $keep_taxonomies);
		}
		if( $rmv_taxonomies ){
			$this->do_rmv_tax_metaboxes($rmv_taxonomies, $post_type, $context);
		}
	}

	protected function do_rmv_tax_metaboxes($rmv_taxonomies, $post_type, $context){
		if( !is_array($rmv_taxonomies) || empty($rmv_taxonomies) ) return;

		foreach( $rmv_taxonomies as $tax ){
			$assumed_id = is_taxonomy_hierarchical($tax) ? "{$tax}div" : "tagsdiv-{$tax}";
			remove_meta_box($assumed_id, $post_type, $context);
		}
	}

	protected function is_any_screen_in_any_context($hit_type, $post_type, $post, $now_cntxt, $hit_cntxt=''){
		return (bool)(
			is_admin() 
			&& ( $hit_type === $post_type ) 
			&& ( $post && $hit_type === $post->post_type ) 
			&& ( !$hit_cntxt || $hit_cntxt === $now_cntxt ) 
		);
	}

	protected function is_post_screen_in_any_context($post_type, $post, $now_cntxt, $hit_cntxt=''){
		return $this->is_any_screen_in_any_context('post', $post_type, $post, $now_cntxt, $hit_cntxt);
	}

	function hook_terms_checklist_args($args, $post_id){
		if( !$this->is_admin_usces_item_page( array('new', 'edit') ) ) return $args;

		$taxonomy = $args['taxonomy'];
		$r_taxes = ( $this->item_taxonomies ) ? $this->item_taxonomies : array();
		$args['checked_ontop'] = (bool)( $r_taxes && in_array($taxonomy, $r_taxes, true) );
		return apply_filters('my_icc_term_checklist_args_at_usces_item_edit', $args, $post_id);
	}

	function activate_item_csv_filters($action){
		if( 'dlitemlist' === $action ){
			add_filter('get_object_terms', array($this, 'hook_get_object_terms_for_downloadcsv'), 10, 4);
		}

		if( 'upload_register' === $action ){
			if( function_exists('update_field') ){
				add_filter('usces_filter_uploadcsv_skuvalue', array($this, 'hook_uploadcsv_skuvalue_for_acf'), 10, 2);
			}
		}
	}

//download csv
	function hook_get_object_terms_for_downloadcsv($terms, $object_ids, $taxonomies, $args){
		$taxonomies = is_array($taxonomies) ? $taxonomies : array($taxonomies);
		if( in_array('category', $taxonomies, true) ){
			$taxonomies = get_object_taxonomies('post', 'names');
			remove_filter('get_object_terms', array($this, __FUNCTION__), 10); //avoid recursive
			$terms = wp_get_object_terms($object_ids, $taxonomies, $args);
			add_filter('get_object_terms', array($this, __FUNCTION__), 10, 4);
		}
		return $terms;
	}

//upload csv
	function hook_uploadcsv_skuvalue_for_acf($skuvalue, $datas){
	//do nothing to $skuvalue, just execute for acf
		global $usces;
		$assumed_post_id = 0;
		if( defined('USCES_COL_POST_ID') && isset($datas[USCES_COL_POST_ID]) ){
			$assumed_post_id = intval($datas[USCES_COL_POST_ID]);
			if( !$assumed_post_id ){
				$assumed_item_code = ( defined('USCES_COL_ITEM_CODE') && isset($datas[USCES_COL_ITEM_CODE]) ) ? $datas[USCES_COL_ITEM_CODE] : false;
				if( false !== $assumed_item_code ){
					$assumed_post_id = $usces->get_postIDbyCode($assumed_item_code);
				}
			}
		}

		$acf_keys = ( $assumed_post_id ) ? $this->get_item_acf_field_keys() : array();
		if( $acf_keys ){
			$cfrows = ( defined('USCES_COL_CUSTOM_FIELD') && isset($datas[USCES_COL_CUSTOM_FIELD]) ) ? $datas[USCES_COL_CUSTOM_FIELD] : array();
			$cfrows = ( $usces->options['system']['csv_encode_type'] == 0 ) ? mb_convert_encoding($cfrows, 'UTF-8', 'SJIS') : $cfrows;
			$cfrows = explode( ';', trim($cfrows) );
			if( !( 1 === count($cfrows) && '' == reset($cfrows) ) ){
				foreach( $cfrows as $row ){
					list($meta_key, $meta_value) = explode('=', $row, 2);
					if( !WCUtils::is_blank($meta_key) && isset($acf_keys[$meta_key]) ){
						$acf_field_key = $acf_keys[$meta_key];
						update_field($acf_field_key, $meta_value, $assumed_post_id);
					}
				}
			}
		}
		return $skuvalue;
	}

	protected function get_item_acf_field_keys(){
		$acf_keys = array();
		$acf_fields = self::get_acf_fields($this->item_acf_id);
		if( $acf_fields ){
			foreach( $acf_fields as $field_arr ){
				$name = isset($field_arr['name']) ? $field_arr['name'] : '';
				$acf_keys[$name] = isset($field_arr['key']) ? $field_arr['key'] : '';
			}
		}
		return $acf_keys;
	}

	static public function get_acf_fields($post_id){
		if( function_exists('acf_get_fields') ) return acf_get_fields($post_id); //available ge 5.0.0
		return apply_filters('acf/field_group/get_fields', array(), $post_id); //available lt 5.0.0
	}

/******************************/

	function activate_archive_item_posts(){
		add_action('pre_get_posts', array($this, 'hook_pre_get_posts_for_item_posts'), 12, 1);
		add_filter('getarchives_where', array($this, 'hook_getarchives_where'), 10, 2);

		add_filter('posts_join_request', array($this, 'hook_join_request'), 10, 2);
		add_filter('posts_orderby_request', array($this, 'hook_orderby_request'), 10, 2);
		add_filter('posts_fields_request', array($this, 'hook_fields_request'), 10, 2);
		add_filter('posts_request', array($this, 'hook_request'), 10, 2);
		add_filter('posts_pre_query', array($this, 'hook_pre_query'), 10, 2); //available ge 4.6.0
	}

	function hook_pre_get_posts_for_item_posts($query){
		if( is_admin() || !$query->is_main_query() ) return;

		$item_cat = $this->get_item_cat();
		if( !$item_cat ) return;

		if( $query->is_archive() || $query->is_search() ){

			if( $this->is_any_tax_term_of_item($query) ){

				if( $this->is_item_tax($query) ){
					$category__in = $query->get('category__in', array());
					$category__in[] = $item_cat->term_id;
					$query->set('category__in', $category__in);
				}

				$item_query_args = $this->archive_items_query_args;
				if( $item_query_args ) {
					$the_args = array();
					foreach( $item_query_args as $taxonomy => $args ){
						if( $this->is_the_tax_archive($taxonomy, $query) ){
							foreach($args as $k => $v){
								$query->set($k, $v);
							}
							break;
						}
					}
				}
				$this->own_hook_pre_get_posts_when_the_item_archive($query);

				return;
			}

			$category__not_in = $query->get('category__not_in', array());
			$category__not_in[] = $item_cat->term_id;
			$query->set('category__not_in', $category__not_in);

			$this->own_hook_pre_get_posts_when_not_item_archive($query);

		}

	}

	protected function own_hook_pre_get_posts_when_the_item_archive($query){
		return false;
	}

	protected function own_hook_pre_get_posts_when_not_item_archive($query){
		return false;
	}

	function hook_getarchives_where($sql_where, $args){
		$item_cat = $this->get_item_cat();
		if( $item_cat ) $sql_where .= " AND post_mime_type <> 'item'";
		return $sql_where;
	}

	function hook_join_request($join, $query){
		return $join;
	}

	function hook_orderby_request($orderby, $query){
		return $orderby;
	}

	function hook_fields_request($fields, $query){
		return $fields;
	}

	function hook_request($request, $query){
		//do anything as need arises, but remember to return '$request'
		return $request;
	}

	function hook_pre_query($posts, $query){
		//$posts is NULL
		//be accessible to $query->request
		return $posts;
	}

/******************************/

	protected function activate_item_template(){
		if( !$this->item_archive_template_name ) return;

		add_filter('category_template', array($this, 'category_item_template'));
		add_filter('tag_template', array($this, 'tag_item_template'));
		add_filter('archive_template', array($this, 'archive_item_template'));
		add_filter('search_template', array($this, 'search_item_template'));
	}

	public function category_item_template($template){
	//Specific templates for item archives
		return $this->is_cat_of_item() ? $this->get_item_template() : $template;
	}

	public function tag_item_template($template){
	//Specific templates for item tag page
		return $this->is_tag_of_item() ? $this->get_item_template() : $template;
	}

	public function archive_item_template($template){
	//Specific templates for item archive page
		return $this->is_item_tax() ? $this->get_item_template() : $template;
	}

	public function search_item_template($template){
	//Specific templates for item search page
		return $this->is_any_tax_term_of_item() ? $this->get_item_template() : $template;
	}

	protected function get_item_template(){
		return get_stylesheet_directory() . '/' . $this->item_archive_template_name;
	}

/******************************/

	protected function activate_url_rewrite(){
		add_filter('init', array($this, 'url_rewrite_flush'));
		add_filter('rewrite_rules_array', array($this, 'url_rewrite_rule'));
		add_filter('query_vars', array($this, 'url_rewrite_add_vars'));
	}

	function url_rewrite_flush(){
		$init_flush = $this->get_rewrite_vars('init_flush');
		if( true === $init_flush ){
			global $wp_rewrite;
			$wp_rewrite->flush_rules();
		}
	}

	function url_rewrite_rule($rules){
		$newrules = $this->get_rewrite_vars('rule');
		$newrules = is_array($newrules) ? $newrules : array();
		return $newrules + $rules;
	}

	function url_rewrite_add_vars($vars){
		$query_vars = $this->get_rewrite_vars('query_var');
		$query_vars = is_array($query_vars) ? $query_vars : array();
		if( $query_vars ){
			foreach($query_vars as $qv){
				$qv = (string)$qv;
				if( '' !== $qv ){
					array_push($vars, $qv);
				}
			}
		}
		return $vars;
	}

	protected function get_rewrite_vars($key=''){
		$vars = $this->rewrite_vars;
		$key = (string)$key;
		if( '' !== $key ){
			$vars = isset($vars[$key]) ? $vars[$key] : NULL;
		}
		return $vars;
	}

/******************************/

	function activate_replace_wp_link(){
		if( $this->replace_term_slugs ){
			add_filter('term_link', array($this, 'replace_term_link'), 10, 3);
			add_action('wp', array($this, 'redirect_term'));
		}

	//use by combination with rewrite rules, when adding base slug for item post
		if( $this->replace_item_post_base_slug ){
			add_filter('post_link', array($this, 'replace_item_post_link'), 10, 3);
			add_action('wp', array($this, 'redirect_a_normal_post'));
		}
	}

	function replace_term_link($termlink, $term, $taxonomy){
		$replace_slugs = $this->replace_term_slugs;
		foreach( $replace_slugs as $tax => $slugs ){
			if( !$slugs ) continue;
			if( !taxonomy_exists($tax) || $tax !== $taxonomy ) continue;

			foreach( $slugs as $term_slug => $rep_slug ){
				if( $term_slug === $term->slug ){
					$termlink = home_url('/') . $rep_slug;
					$termlink = trailingslashit($termlink);
					break 2;
				}
			}
		}
		return $termlink;
	}

	function redirect_term(){
		$replace_slugs = $this->replace_term_slugs;
		$req_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

		foreach( $replace_slugs as $tax => $slugs ){
			if( !$slugs ) continue;
			if( !taxonomy_exists($tax) ) continue;

			foreach( $slugs as $term_slug => $rep_slug ){
				$is_term_archive = $this->is_the_term_archive($tax, $term_slug);
				if( $is_term_archive || false === strpos($req_uri, $rep_slug) ){
					$term = get_queried_object();
					$term_link = get_term_link($term, $tax);
					if( false !== strpos($term_link, $rep_slug) ){ //avoid loop
						wp_safe_redirect($term_link);
						exit;
					}
				}
			}
		}
	}

	function replace_item_post_link($url, $post, $leavename){
		if( $this->is_usces_item_post($post) ){
			$url = home_url('/') . $this->replace_item_post_base_slug . '/' . $post->ID . '/';
		}
		return $url;
	}

	function redirect_a_normal_post(){
		$req_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
		$search_uri = '/' . $this->replace_item_post_base_slug;
		if( is_single() && strpos($req_uri, $search_uri) === 0 ){
			global $post;
			if( !$this->is_usces_item_post($post) ){
				$post_link = get_permalink($post);
				wp_safe_redirect($post_link);
				exit;
			}
		}
	}

/******************************/

	public function activate_item_search(){
		if( $this->item_category_id ){
			add_filter('posts_search', array($this, 'hook_posts_search_for_item_search'), 10, 2);
		}
	}

	function hook_posts_search_for_item_search($search, $query){
		if( is_admin() ) return $search;

		$s_count = $query->get('search_terms_count', 0);
		$s_terms = $query->get('search_terms', array());
		if( 0 === $s_count || 0 === count($s_terms) ){
			return $search;
		}

		global $wpdb;
		$meta_keys = is_array($this->search_metas) ? $this->search_metas : array($this->search_metas);
		$p_ids = array();
		$collates = array(
			'posts' => $this->get_appropriate_unicode_collate($wpdb->posts), 
			'postmeta' => $this->get_appropriate_unicode_collate($wpdb->postmeta), 
		);
		foreach( $s_terms as $term ){
			$p_ids_per_term = array();

			$word = mb_convert_kana($term, 'aKV');
			$word = '%' . $wpdb->esc_like( $word ) . '%';
			$meta_queries = array();
			$meta_args = array();
			if( $meta_keys ){
				$colt = ( $collates['postmeta'] ) ? " COLLATE " . $collates['postmeta'] : '';
				foreach($meta_keys as $meta_key){
					$meta_queries[] = "OR ( meta_key = %s AND meta_value{$colt} LIKE %s ) ";
					$meta_args[] = $meta_key;
					$meta_args[] = $word;
				}
			}
			$meta_query = implode('', $meta_queries);

			$colt = ( $collates['posts'] ) ? " COLLATE " . $collates['posts'] : '';
			$p_ids_per_term = $wpdb->get_col( $wpdb->prepare("
				SELECT DISTINCT post_id 
				FROM {$wpdb->posts} AS p 
				INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id 
				WHERE ( 
					( p.post_title{$colt} LIKE %s ) 
					OR 
					( p.post_content{$colt} LIKE %s ) 
					{$meta_query}
				) ", 
			array_merge( array($word, $word), $meta_args )) );
			if( $p_ids_per_term ){
				$p_ids = ( $p_ids ) ? array_intersect($p_ids, $p_ids_per_term) : $p_ids_per_term;
			} else {
				$p_ids = array();
			}
			if( !$p_ids ) break; //behave as AND search
		}
		$p_ids = array_filter($p_ids, 'is_numeric');
		$p_ids = ( $p_ids ) ? $p_ids : array('0');
		$p_ids = implode(',', $p_ids);
		$search = " AND ( {$wpdb->posts}.ID IN ({$p_ids}) ) ";
		if( !is_user_logged_in() ){
			$search .= " AND ( $wpdb->posts.post_password = '' ) ";
		}
		return $search;
	}

	function get_appropriate_unicode_collate($tbl_name){
		$tbl_charset = $this->get_db_table_charset($tbl_name);
		return $this->get_unicode_collate($tbl_charset);
	}

	function get_unicode_collate($tbl_charset){
		$tbl_charset = (string)$tbl_charset;
		$collates = array(
			'utf8mb4' => 'utf8mb4_unicode_ci', 
			'utf8' => 'utf8_unicode_ci', 
		);
		return isset($collates[$tbl_charset]) ? $collates[$tbl_charset] : '';
	}

	function get_db_table_charset($tbl_name){
		global $wpdb;
		$tbl_name = esc_sql($tbl_name);
		$res = $wpdb->get_results("SHOW CREATE TABLE `{$tbl_name}`", ARRAY_A);
		$res = ( $res && $res[0] && isset($res[0]['Create Table']) ) ? $res[0]['Create Table'] : '';
		return preg_match("/CHARSET=([^\s]+)/u", $res, $mt) ? $mt[1] : '';
	}

/******************************/

	function activate_uneditable_csmb_keys(){
		if( !$this->uneditable_csmb_keys ) return;

		add_filter('usces_filter_member_check', array($this, 'hook_to_avoid_edit_memberdata'), 9, 1);
		add_filter('usces_filter_custom_field_input', array($this, 'hook_to_disable_memberinput'), 10, 4);
		add_filter('usces_filter_mail_custom_field_info', array($this, 'hook_mail_custom_field_info_to_delete_memberdata'), 10, 5);
	}

	function hook_to_avoid_edit_memberdata($mes){
	//need to call earlier than 'usces_filter_member_check_custom_member'
		if( is_admin() ) return $mes;

		$member_regmode = isset($_POST['member_regmode']) ? $_POST['member_regmode'] : '';
		if( 'editmemberform' === $member_regmode || 'editmemberfromcart' === $member_regmode ){
			$root_key = 'custom_member';
			if( isset($_POST[$root_key]) ){
				global $usces;
				$usces->get_current_member();
				$mem_id = $usces->current_member['id'];
				if( $mem_id ){
					$uneditable_keys = $this->uneditable_csmb_keys;
					foreach( $uneditable_keys as $uek => $uea ){
						$hide_type = isset($uea['hide_memberedit']) ? (int)$uea['hide_memberedit'] : 1;
						if( 0 === $hide_type ) continue;

						$_POST[$root_key][$uek] = $usces->get_member_meta_value('csmb_'.$uek, $mem_id);
					}
				}
			}
		}
		return $mes;
	}

	function hook_to_disable_memberinput($html, $data, $custom_field, $position){
		if( is_admin() ) return $html;

		switch( $custom_field ){
			case 'member':
				$uneditable_keys = $this->uneditable_csmb_keys;
				foreach( $uneditable_keys as $uek => $uea ){
					$hide_type = isset($uea['hide_memberedit']) ? (int)$uea['hide_memberedit'] : 1;
					if( 0 === $hide_type ) continue;

					$ptn = "/(<tr class=[\'\"]customkey_{$uek}[\'\"]>[\s\S]+)(<input[^>]+?\[{$uek}\][^>]*?>)([\s\S]*?<\/tr>)/u";
					if( preg_match($ptn, $html, $mt) ){
						$pre = $mt[1];
						$input = $mt[2];
						$aft = $mt[3];
						$ptn2 = "/value=[\'\"](.*?)[\'\"]/u";
						$value = preg_match($ptn2, $input, $mt) ? $mt[1] : '';
						$rep = '';
						if( 1 < $hide_type ) {
							if( 2 === $hide_type ) {
								$value = '-'; //display '-' whether value exists or not
							} else {
								$value = ($value === '') ? '-' : $value; //display if value exists
							}
							$rep = '${1}' . $value . '${3}';
						}
						$html = preg_replace($ptn, $rep, $html);
					}
				}
				break;
		}
		return $html;
	}

	function hook_mail_custom_field_info_to_delete_memberdata($msg_body, $custom_field, $position, $id, $mailaddress){
		$rmv_keys = array();
		$uneditable_keys = $this->uneditable_csmb_keys;
		foreach( $uneditable_keys as $uek => $uea ){
			$hide_type = isset($uea['hide_mail']) ? (int)$uea['hide_mail'] : 1;
			if( 0 === $hide_type ) continue;

			$rmv_keys[] = $uek;
		}
		if( $rmv_keys ){
			$msg_body = $this->get_usces_msg_body_with_deleting_cs_mail_line($rmv_keys, $msg_body, $custom_field, $position, $id, $mailaddress);
		}
		return $msg_body;
	}

/*************** mail ***************/
	//remove any mail line based on cs field using cs keys
	function get_usces_msg_body_with_deleting_cs_mail_line($rmv_keys, $msg_body, $custom_field, $position, $id, $mailaddress){
		if( !$rmv_keys ) return $msb_body;

		$meta = usces_has_custom_field_meta($custom_field);
		$keys = ( !empty($meta) && is_array($meta) ) ? array_keys($meta) : array();
		$apply_fields = array('order', 'customer', 'delivery', 'member');
		if( $keys && in_array($custom_field, $apply_fields, true) ){
			foreach( $keys as $key ){
				if( in_array($key, $rmv_keys, true) ){
					$name = $meta[$key]['name'];
					$ptn = "/" . preg_quote($name, '/') ." : .*?\r\n/u";
					$msg_body = preg_replace($ptn, '', $msg_body);
				}
			}
		}
		return $msg_body;
	}

/*************** utility ***************/
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
	protected function get_num_if_isset($args, $key){
		$val = $this->get_str_if_isset($args, $key);
		return is_numeric($val) ? $val : '0';
	}
	protected function get_if_isset($args, $key, $fmt=''){
		$val = ( is_array($args) && isset($args[$key]) ) ? $args[$key] : NULL;
		if( '' !== (string)$fmt ) settype($val, $fmt);
		return $val;
	}

	protected function search_arr_val_deeply($arr, $key){
		if( is_string($key) ) $key = explode('/', $key);
		if( !is_array($key) ) $key = array($key);
		if( !is_array($arr) || !$key ) return false;

		foreach( $key as $k ){
			$arr = ( is_array($arr) && isset($arr[$k]) ) ? $arr[$k] : NULL;
		}
		return $arr;
	}

}
