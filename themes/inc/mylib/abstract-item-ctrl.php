<?php 
abstract class myUscesItemCtrlAbstract {
	protected $item_taxonomies = array();
	protected $item_archive_template_name = "";
	protected $replace_term_slugs = array(
/* this is just a sample values
		%taxonomy% => array(
			%term_slug% => %replace_slug%", 
		), 
	//example...
		"category" => array(
			"item" => "shop/item", 
		), 
 */
	);
	protected $replace_item_post_base_slug = "";
	protected $archive_items_query_args = array(
		"category" => array(
			"orderby" => array( "modified" => "DESC", "ID" => "DESC" ), 
		), 
	);
	protected $rewrite_vars = array(
		'rule' => array(), 
		'query_var' => array(), 
		'init_flush' => false, //be careful to set true
	);

	protected $item_category_id;
	protected $search_metas = array(
		"_itemCode"
	);

	protected $uneditable_csmb_keys = array(
/* this is just a sample values
		%csmb_key% => array(
			"hide_memberedit" => 0 / 1 / 2 / 3, //show / hide / show but value "-" and disabled / show any value but disabled
			"hide_mail" => 0 / 1, //show / hide 
		), 
	//example...
		"company_id" => array(
			"hide_memberedit" => 1, 
			"hide_mail" => 0, 
		), 
 */
	);

	protected $campaign_terms = array();

	protected $debugs = array();

	protected $item_acf_id = 0;

	function __construct($args=array()){

		$this->set_campaign_terms();
		$this->activate_item_taxonomies();
		$this->activate_archive_item_posts();
		$this->activate_item_template();
		$this->activate_url_rewrite();
		$this->activate_replace_wp_link();
		$this->activate_item_search();
		$this->activate_uneditable_csmb_keys();

	}

/******************************/

	function get_item_cat(){
		$cat = get_category_by_slug('item');
		$cat = $this->is_wp_term($cat) ? $cat : array();
		return $cat;
	}

	function is_cat_of_item($presumed_cat_id=NULL){
		if( !function_exists('usces_is_cat_of_item') ) return false;

		$cat_id = get_query_var('cat');

		if( is_numeric($presumed_cat_id) ){

			$cat_id = (int)$presumed_cat_id;

		} elseif( $this->is_wp_query($presumed_cat_id) ){

			$query = $presumed_cat_id;
			$cat_id = $query->get('cat');
			if( !$cat_id ){
				$assumed_cat_slug = basename( $query->get('category_name') );
				$cat = get_category_by_slug($assumed_cat_slug);
				$cat_id = $this->is_wp_term($cat) ? $cat->term_id : 0;
			}

		}

		return usces_is_cat_of_item($cat_id);
	}

	function is_wp_term($obj){
		return ( is_object($obj) && $obj instanceof WP_Term ) ? true : false;
	}

	function is_wp_query($obj){
		return ( is_object($obj) && $obj instanceof WP_Query ) ? true : false;
	}

	function is_wp_post($obj){
		return ( is_object($obj) && $obj instanceof WP_Post ) ? true : false;
	}

	function is_usces_item_post($post){
		if( $this->is_wp_post($post) ){
			if( $post->post_type === 'post' && $post->post_mime_type === 'item' ){
				return true;
			}
		}
		return false;
	}

	function is_item_page(){
		$checks = array(
			( $this->is_usces_single_item() ), 
			( is_archive() && ( $this->is_cat_of_item() || $this->is_item_tax() ) ), 
		);
		if( in_array(true, $checks) ){
			return true;
		}
		return false;
	}

	function is_usces_single_item(){
		return ( is_single() && usces_is_item() ) ? true : false;
	}

	function is_the_tax_archive($tax, $q=NULL){
		if( $this->is_wp_query($q) ){
			$is_tax = ( $tax !== 'category' ) ? ( ( $tax !== 'post_tag' ) ? $q->is_tax($tax) : $q->is_tag() ) : $q->is_category();
		} else {
			$is_tax = ( $tax !== 'category' ) ? ( ( $tax !== 'post_tag' ) ? is_tax($tax) : is_tag() ) : is_category();
		}
		return (bool)$is_tax;
	}

	function is_the_term_archive($tax, $term, $q=NULL){
		if( $this->is_wp_query($q) ){
			$is_term = ( $tax !== 'category' ) ? ( ( $tax !== 'post_tag' ) ? $q->is_tax($tax, $term) : $q->is_tag($term) ) : $q->is_category($term);
		} else {
			$is_term = ( $tax !== 'category' ) ? ( ( $tax !== 'post_tag' ) ? is_tax($tax, $term) : is_tag($term) ) : is_category($term);
		}
		return (bool)$is_term;
	}

	function is_item_tax($q=NULL){
		$taxes = $this->item_taxonomies;
		if( $this->is_wp_query($q) ){
			$is_item_tax = ( $taxes && $q->is_tax( $taxes ) ) ? true : false;
		} else {
			$is_item_tax = ( $taxes && is_tax( $taxes ) ) ? true : false;
		}
		return $is_item_tax;
	}

	function is_catchable_global_post(){
		global $post;
		return $this->is_wp_post($post);
	}

	function is_admin_usces_item_page($suffix=""){
		if( is_admin() ){
			$page = isset($_GET["page"]) ? $_GET["page"] : '';

			$suffix = is_array($suffix) ? $suffix : array($suffix);
			foreach( $suffix as $sffx ){
				$needle = "usces_item" . (string)$sffx;
				if( strpos($page, $needle) !== false ){
					return true;
				}
			}
		}
		return false;
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
			$terms["category"][] = $usces_term;
		}

		$this->campaign_terms = $terms;
	}

	function is_site_campaign_mode(){
		if($this->debugs['is_campaign'] === true){
			return true;
		}
		static $bool = NULL;
		if($bool === NULL){
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
		if($this->is_site_campaign_mode()){
			if( is_numeric($post) ){
				$post = get_post($post);
			} else {
				if(!$post && $this->is_item_page()){
					global $post;	
				}
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

	function get_site_campaign_date($kind, $date_fmt=""){
		$date_str = "";
		$date_fmt = (string)$date_fmt;
		$date_fmt = ( $date_fmt === "" ) ? "Y/m/d H:i:00" : $date_fmt;

		$timestamp = $this->get_site_campaign_timestamp($kind);
		if( $timestamp ){
			$date_str = date($date_fmt, $timestamp);
		}

		return $date_str;
	}
	function get_site_campaign_timestamp($kind){
		static $timestamps = NULL;
		if( $timestamps === NULL ){
			$timestamps = $this->get_site_campaign_timestamps();
		}

		$timestamp = false;
		$kind = (string)$kind;
		if( isset($timestamps[$kind]) ){
			$timestamp = $timestamps[$kind];
		}

		return $timestamp;
	}
	function get_site_campaign_date_info($kind, $key=""){
		static $dates = NULL;
		if( $dates === NULL ){
			$dates = $this->get_site_campaign_dates();
		}

		$date = "";
		$kind = (string)$kind;
		if( isset($dates[$kind]) ){
			$date = $dates[$kind];
			$key = (string)$key;
			if( $key !== "" && isset($date[$key]) ){
				$date = $date[$key];
			}
		}

		return $date;
	}

	protected function get_site_campaign_dates(){
		global $usces;
		$schedules = isset($usces->options["campaign_schedule"]) ? $usces->options["campaign_schedule"] : array();
		$dates = array("start" => array(), "end" => array());
		$date_separates = array(
			"year" => 0, 
			"month" => 0, 
			"day" => 0, 
			"hour" => 0, 
			"min" => 0, 
		);
		foreach($dates as $kind => $val){
			$scdl = isset($schedules[$kind]) ? $schedules[$kind] : array();
			foreach( $date_separates as $dk => $dv ){
				if( isset($scdl[$dk]) ){
					$dv = (int)$scdl[$dk];
					$dv = ( $dk !== "year" ) ? str_pad($dv, 2, "0", STR_PAD_LEFT) : $dv;
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
			$date_str = $val["year"] . "-" . $val["month"] . "-" . $val["day"];
			$date_str .= " " . $val["hour"] . ":" . $val["min"] . ":00";
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

		foreach($rmv_taxonomies as $tax){
			$assumed_id = is_taxonomy_hierarchical($tax) ? "{$tax}div" : "tagsdiv-{$tax}";
			remove_meta_box($assumed_id, $post_type, $context);
		}
	}

	protected function is_any_screen_in_any_context($hit_type, $post_type, $post, $now_cntxt, $hit_cntxt=''){
		$conditions = (
			is_admin() 
			&& ( $hit_type === $post_type ) 
			&& ( $post && $hit_type === $post->post_type ) 
			&& ( !$hit_cntxt || $hit_cntxt === $now_cntxt ) 
		) ? true : false;
		return $conditions;
	}

	protected function is_post_screen_in_any_context($post_type, $post, $now_cntxt, $hit_cntxt=''){
		return $this->is_any_screen_in_any_context('post', $post_type, $post, $now_cntxt, $hit_cntxt);
	}

	function hook_terms_checklist_args($args, $post_id){
		if( !$this->is_admin_usces_item_page( array('new', 'edit') ) ) return $args;

		$taxonomy = $args['taxonomy'];
		$r_taxes = ( $this->item_taxonomies ) ? $this->item_taxonomies : array();
		$c_ontop = (bool)( $r_taxes && in_array($taxonomy, $r_taxes, true) );
		$args['checked_ontop'] = $c_ontop;

		$args = apply_filters('my_icc_term_checklist_args_at_usces_item_edit', $args, $post_id);

		return $args;
	}

	function activate_item_csv_filters($action){
		if($action === 'dlitemlist'){
			add_filter('get_object_terms', array($this, 'hook_get_object_terms_for_downloadcsv'), 10, 4);
		}

		if($action === 'upload_register'){
			if(function_exists('update_field')){
				add_filter('usces_filter_uploadcsv_skuvalue', array($this, 'hook_uploadcsv_skuvalue_for_acf'), 10, 2);
			}
		}
	}

//download csv
	function hook_get_object_terms_for_downloadcsv($terms, $object_ids, $taxonomies, $args){
		$taxonomies = is_array($taxonomies) ? $taxonomies : array($taxonomies);
		if(in_array('category', $taxonomies, true)){
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
		if(defined('USCES_COL_POST_ID') && isset($datas[USCES_COL_POST_ID])){
			$assumed_post_id = intval($datas[USCES_COL_POST_ID]);
			if(!$assumed_post_id){
				$assumed_item_code = (defined('USCES_COL_ITEM_CODE') && isset($datas[USCES_COL_ITEM_CODE])) ? $datas[USCES_COL_ITEM_CODE] : false;
				if($assumed_item_code !== false){
					$assumed_post_id = $usces->get_postIDbyCode($assumed_item_code);
				}
			}
		}

		$acf_keys = ( $assumed_post_id ) ? $this->get_acf_field_keys() : array();

		if($acf_keys){
			$cfrows = (defined('USCES_COL_CUSTOM_FIELD') && isset($datas[USCES_COL_CUSTOM_FIELD])) ? $datas[USCES_COL_CUSTOM_FIELD] : array();
			$cfrows = ( $usces->options['system']['csv_encode_type'] == 0 ) ? mb_convert_encoding($cfrows, 'UTF-8', 'SJIS') : $cfrows;
			$cfrows = explode(';', trim($cfrows));
			if( !(1 === count($cfrows) && '' == reset($cfrows)) ){
				foreach( $cfrows as $row ){
					list($meta_key, $meta_value) = explode( '=', $row, 2 );
					if( !WCUtils::is_blank($meta_key) && isset($acf_keys[$meta_key]) ){
						$acf_field_key = $acf_keys[$meta_key];
						update_field($acf_field_key, $meta_value, $assumed_post_id);
					}
				}
			}
		}

		return $skuvalue;
	}

	protected function get_acf_field_keys(){
		$acf_keys = array();
		$acf_fields = $this->get_acf_fields();
		if($acf_fields){
			foreach($acf_fields as $index => $acf){
				$key = isset($acf['key']) ? $acf['key'] : '';
				$name = isset($acf['name']) ? $acf['name'] : '';
				$acf_keys[$name] = $key;
			}
		}
		return $acf_keys;
	}

	protected function get_acf_fields($acf__id=NULL){
		$acf_fields = array();
		$acf__id = ( NULL === $acf__id  ) ? $this->item_acf_id : $acf__id;
		if( (int)$acf__id ){
			$is_acf_pro_running = class_exists('acf_pro') ? true : false;
			$acf_fields = ( $is_acf_pro_running ) ? acf_get_fields_by_id($acf__id) : apply_filters('acf/field_group/get_fields', array(), $acf__id);
		}
		return $acf_fields;
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

		if( $query->is_archive() || $query->is_home() ){
			if( $this->is_cat_of_item($query) || $this->is_item_tax($query) ){

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
		if( $item_cat ){
			$sql_where .= " AND post_mime_type <> 'item'";
		}
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

	function activate_item_template(){
		if( !$this->item_archive_template_name ) return;

		add_filter('category_template', array($this, 'category_item_template'));
		add_filter('archive_template', array($this, 'archive_item_template'));
		add_filter('search_template', array($this, 'search_item_template'));
	}

	public function category_item_template($template) {
	//Specific templates for item archives
		$category_id = get_query_var('cat', 0);
		if( $this->is_cat_of_item($category_id) ){
			$template = $this->get_item_template();
		}
		return $template;
	}

	public function archive_item_template($template){
		if( $this->is_item_tax() ){
			$template = $this->get_item_template();
		}
		return $template;
	}

	public function search_item_template($template) {
	//Specific templates for item search page
		if( $this->is_cat_of_item() ){
			$template = $this->get_item_template();
		}
		return $template;
	}

	protected function get_item_template(){
		return get_stylesheet_directory() . '/' . $this->item_archive_template_name;
	}

/******************************/

	function activate_url_rewrite(){
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
				if( "" !== $qv ){
					array_push($vars, $qv);
				}
			}
		}
		return $vars;
	}

	protected function get_rewrite_vars($key=''){
		$vars = $this->rewrite_vars;
		$key = (string)$key;
		if( "" !== $key ){
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
					$termlink = home_url("/") . $rep_slug;
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
				if( $is_term_archive || strpos($req_uri, $rep_slug) === false ){
					$term = get_queried_object();
					$term_link = get_term_link($term, $tax);
					if( strpos($term_link, $rep_slug) !== false ){ //avoid loop
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

	function activate_item_search(){
		$item_cat = get_category_by_slug('item');
		if($item_cat && $item_cat->term_id){
			$this->item_category_id = (int)$item_cat->term_id;
			add_filter('posts_search', array($this, 'hook_posts_search_for_item_search'), 10, 2);
		}
	}

	function hook_posts_search_for_item_search($search, $query){
		if( is_admin() ) return $search;

		$s_count = $query->get('search_terms_count', 0);
		$s_terms = $query->get('search_terms', array());
		if($s_count === 0 || count($s_terms) === 0){
			return $search;
		}

		global $wpdb;
		$post_status = 'publish';
		$post_status = is_array($post_status) ? $post_status : array($post_status);
		$post_status = "'" . implode("','", $post_status) . "'";
		$meta_keys = is_array($this->search_metas) ? $this->search_metas : array($this->search_metas);
		$p_ids = array();
		$collates = array(
			"posts" => $this->get_appropriate_unicode_collate($wpdb->posts), 
			"postmeta" => $this->get_appropriate_unicode_collate($wpdb->postmeta), 
		);
		foreach($s_terms as $term){
			$p_ids_per_term = array();

			$word = mb_convert_kana($term, "aKV");
			$word = '%' . $wpdb->esc_like( $word ) . '%';
			$meta_queries = array();
			$meta_args = array();
			if($meta_keys){
				$colt = ( $collates['postmeta'] ) ? " COLLATE " . $collates['postmeta'] : "";
				foreach($meta_keys as $meta_key){
					$meta_queries[] = "OR ( meta_key = %s AND meta_value{$colt} LIKE %s ) ";
					$meta_args[] = $meta_key;
					$meta_args[] = $word;
				}
			}
			$meta_query = implode("", $meta_queries);

			$colt = ( $collates['posts'] ) ? " COLLATE " . $collates['posts'] : "";
			$prepare_args = array($word, $word);
			$prepare_args = array_merge($prepare_args, $meta_args);
			$base_query = $wpdb->prepare("
				SELECT DISTINCT post_id 
				FROM {$wpdb->posts} AS p 
				INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id 
				WHERE p.post_status IN ({$post_status}) 
				AND ( 
					( p.post_title{$colt} LIKE %s ) 
					OR 
					( p.post_content{$colt} LIKE %s ) 
					{$meta_query}
				) ", 
				$prepare_args
			);
			$p_ids_per_term = $wpdb->get_col($base_query);

			if($p_ids_per_term){
				$p_ids = (!$p_ids) ? $p_ids_per_term : array_intersect($p_ids, $p_ids_per_term);
			} else {
				$p_ids = array();
			}

			if(!$p_ids) break; //behave as AND search
		}

		$p_ids = array_filter($p_ids, 'is_numeric');
		$p_ids = ($p_ids) ? $p_ids : array('0');
		$p_ids = implode(',', $p_ids);
		$search = " AND ( {$wpdb->posts}.ID IN ({$p_ids}) ) ";
		if(!is_user_logged_in()){
			$search .= " AND ( $wpdb->posts.post_password = '' ) ";
		}

		return $search;
	}

	function get_appropriate_unicode_collate($tbl_name){
		$tbl_charset = $this->get_db_table_charset($tbl_name);
		$collate = $this->get_unicode_collate($tbl_charset);
		return $collate;
	}

	function get_unicode_collate($tbl_charset){
		$tbl_charset = (string)$tbl_charset;
		$collates = array(
			"utf8mb4" => "utf8mb4_unicode_ci", 
			"utf8" => "utf8_unicode_ci", 
		);
		$collate = isset($collates[$tbl_charset]) ? $collates[$tbl_charset] : "";
		return $collate;
	}

	function get_db_table_charset($tbl_name){

		global $wpdb;
		$tbl_name = esc_sql($tbl_name);
		$q = "SHOW CREATE TABLE `{$tbl_name}`";
		$res = $wpdb->get_results($q, ARRAY_A);
		$res = ( $res && $res[0] && isset($res[0]["Create Table"]) ) ? $res[0]["Create Table"] : "";
		$tbl_charset = preg_match("/CHARSET=([^\s]+)/u", $res, $mt) ? $mt[1] : "";

		return $tbl_charset;
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

		$member_regmode = (isset($_POST['member_regmode'])) ? $_POST['member_regmode'] : '';
		if($member_regmode === 'editmemberform' || $member_regmode === 'editmemberfromcart'){
			$root_key = 'custom_member';
			if(isset($_POST[$root_key])){
				global $usces;
				$usces->get_current_member();
				$mem_id = $usces->current_member['id'];
				if($mem_id){
					$uneditable_keys = $this->uneditable_csmb_keys;
					foreach($uneditable_keys as $uek => $uea){
						$hide_type = isset($uea['hide_memberedit']) ? (int)$uea['hide_memberedit'] : 1;
						if( $hide_type === 0 ) continue;

						$_POST[$root_key][$uek] = $usces->get_member_meta_value('csmb_'.$uek, $mem_id);
					}
				}
			}
		}

		return $mes;
	}
	function hook_to_disable_memberinput($html, $data, $custom_field, $position){
		if( is_admin() ) return $html;

		switch($custom_field){
			case 'member':
				$uneditable_keys = $this->uneditable_csmb_keys;
				foreach($uneditable_keys as $uek => $uea){
					$hide_type = isset($uea['hide_memberedit']) ? (int)$uea['hide_memberedit'] : 1;
					if( $hide_type === 0 ) continue;

					$ptn = "/(<tr class=[\'\"]customkey_{$uek}[\'\"]>[\s\S]+)(<input[^>]+?\[{$uek}\][^>]*?>)([\s\S]*?<\/tr>)/u";
					if(preg_match($ptn, $html, $mt)){
						$pre = $mt[1];
						$input = $mt[2];
						$aft = $mt[3];
						$ptn2 = "/value=[\'\"](.*?)[\'\"]/u";
						$value = (preg_match($ptn2, $input, $mt)) ? $mt[1] : '';
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
		foreach($uneditable_keys as $uek => $uea){
			$hide_type = isset($uea['hide_memberedit']) ? (int)$uea['hide_memberedit'] : 1;
			if( $hide_type === 0 ) continue;

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
		$keys = (!empty($meta) && is_array($meta)) ? array_keys($meta) : array();
		$apply_fields = array('order', 'customer', 'delivery', 'member');
		if($keys && in_array($custom_field, $apply_fields, true)){
			foreach($keys as $key){
				if(in_array($key, $rmv_keys, true)){
					$name = $meta[$key]['name'];
					$ptn = "/" . preg_quote($name, "/") ." : .*?\r\n/u";
					$msg_body = preg_replace($ptn, "", $msg_body);
				}
			}
		}

		return $msg_body;
	}

/*************** utility ***************/
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
	protected function get_num_if_isset($args, $key){
		$val = $this->get_str_if_isset($args, $key);
		$val = is_numeric($val) ? $val : "0";
		return $val;
	}
	protected function get_if_isset($args, $key, $fmt=""){
		$val = ( is_array($args) && isset($args[$key]) ) ? $args[$key] : NULL;
		if( "" !== (string)$fmt ){
			settype($val, $fmt);
		}
		return $val;
	}

	protected function search_arr_val_deeply($arr, $key){
		if( is_string($key) ){
			$key = explode('/', $key);
		}
		if( !is_array($key) ){
			$key = array($key);
		}
		if( !is_array($arr) || !$key ) return false;

		foreach( $key as $k ){
			$arr = ( is_array($arr) && isset($arr[$k]) ) ? $arr[$k] : NULL;
		}
		return $arr;
	}

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
