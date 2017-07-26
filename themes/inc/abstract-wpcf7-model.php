<?php 
class MyWpcf7Model {
	protected $onoff = true; //使用する => true, 使用しない => false
	protected $form_ids = array(); //適用コンタクトフォームID
	protected $current_form_id; //適用中コンタクトフォームID

	protected $the_user_info = array(); //ログイン中WPユーザ情報
	protected $wpcf7_to_user_keys = array();

	public function __construct(){
		if( !$this->onoff ) return;

		$this->action_filters();
	}

	protected function action_filters(){
		add_action('wpcf7_contact_form', array($this, 'hook_wpcf7_contact_form'), 10, 1);
	}

	public function hook_wpcf7_contact_form($obj){
		//for all wpcf7
		add_filter('wpcf7_form_tag', array($this, 'hook_wpcf7_form_tag_for_all'), 15, 2);

		$this->set_wpcf7_contact_form_if_appliable($obj);
	}

	protected function set_wpcf7_contact_form_if_appliable($obj){
		if( !$this->is_appliable_wpcf7_form($obj) ) return;

		$this->current_form_id = (int)$obj->id();

		add_filter('wpcf7_form_tag', array($this, 'hook_wpcf7_form_tag_for_paticular'), 11, 2);
		add_filter('wpcf7_form_elements', array($this, 'hook_wpcf7_form_elements'), 11, 1);
		add_action('wpcf7_before_send_mail', array($this, 'hook_wpcf7_before_send_mail'), 11, 1);
		add_filter('wpcf7_skip_mail', array($this, 'hook_wpcf7_skip_mail'), 11, 2);
		add_filter('wpcf7_special_mail_tags', array($this, 'hook_wpcf7_special_mail_tags'), 11, 3);
		add_filter('wpcf7_mail_components', array($this, 'hook_wpcf7_mail_components'), 20, 3);
		add_filter('wpcf7_ajax_json_echo', array($this, 'hook_wpcf7_ajax_json_echo'), 11, 2);

		$this->the_user_info = $this->get_the_user_data_logged_in();
		if( !$this->the_user_info ) return;

		add_filter('wpcf7_form_tag', array($this, 'hook_wpcf7_form_tag_to_complicate_user_value'), 12, 2);
	//WPCF7_Submission
		add_filter('wpcf7_posted_data', array($this, 'hook_wpcf7_posted_data_to_override_user_value'), 9, 1);
	}

/********************************/

	public function hook_wpcf7_form_tag_for_all($tag, $exec){
	/* $exec = Executing shortcodes (true) or just scanning (false) */
		if( !$exec ) return $tag;

		$wpcf7_key = $tag['name'];
		$tag = $this->own_filter_wpcf7_form_tag_at_exec_all_wpcf($tag, $wpfc7_key);

		return $tag;
	}

	protected function own_filter_wpcf7_form_tag_at_exec_all_wpcf($tag, $wpcf7_key){
		return $tag;
	}

	public function hook_wpcf7_form_tag_for_paticular($tag, $exec){
	/* $exec = Executing shortcodes (true) or just scanning (false) */
		if( !$exec ) return $tag;

		$wpcf7_key = $tag['name'];
		$tag = $this->own_filter_wpcf7_form_tag_at_exec_particular_wpcf($tag, $wpfc7_key);

		return $tag;
	}

	protected function own_filter_wpcf7_form_tag_at_exec_particular_wpcf($tag, $wpcf7_key){
		return $tag;
	}

/********************************/

	public function hook_wpcf7_form_elements($form){
		//as need arises
		return $form;
	}

/********************************/

	public function hook_wpcf7_before_send_mail($contact_form){
		//as need arises
		return false;
	}

/********************************/

	public function hook_wpcf7_skip_mail($skip_mail, $contact_form){
	/* $skip_mail = skip sending mail (true) or not (false), be careful to treat */
		//as need arises
		return $skip_mail;
	}

/********************************/

	public function hook_wpcf7_special_mail_tags($output, $name, $html){
		$name = preg_replace( '/^wpcf7\./', '_', $name ); /* for back-compat before wpcf-7.2.2 */
		//as need arises
		return $output;
	}

/********************************/

	public function hook_wpcf7_mail_components($components, $obj_form, $obj_mail){
		//as need arises
		return $components;
	}

/********************************/

	public function hook_wpcf7_ajax_json_echo($items, $result){
		//as need arises
		return $items;
	}

/********************************/

	public function hook_wpcf7_form_tag_to_complicate_user_value($tag, $exec){
	/* $exec = Executing shortcodes (true) or just scanning (false) */
		if( !$exec ) return $tag;

		$wpcf7_key = $tag['name'];
		$user_info = $this->the_user_info;
		if( !isset($user_info[$wpcf7_key]) ) return $tag;

		$itype = $tag['type'];
		$user_val = (string)$user_info[$wpcf7_key];
		$user_val = $this->own_filter_user_value_at_form_tag($user_val, $wpcf7_key, $tag);
		switch( $itype ){
			case 'radio':
			case 'checkbox':
			case 'select':
				$tag_key = array_keys($tag['values'], $user_val, true);
				$tag_key = ( $tag_key ) ? (string)($tag_key[0] + 1) : '';
				$str_default = ( '' !== $tag_key ) ? "default:{$tag_key}" : '';
				if( $str_default ){
					$tag['options'][] = $str_default;
				}
				break;

			default:
				if( '' === $user_val ) break;

				$pholder_key = array_keys($tag['options'], 'placeholder', true);
				if( $pholder_key ){
					$pholder_key = $pholder_key[0];
					unset($tag['options'][$pholder_key]);
				}
				$tag['values'][0] = $user_val;
				break;
		}

		return $tag;
	}

	protected function own_filter_user_value_at_form_tag($user_val, $wpcf7_key, $tag){
		return $user_val;
	}

	public function hook_wpcf7_posted_data_to_override_user_value($posted_data){
	/* avoid user rewriting post value from front wpcf page */
		$user_info = $this->the_user_info;
		if( !$user_info ) return $posted_data;

		foreach( $user_info as $wpcf7_key => $user_val ){
			if( !isset($posted_data[$wpcf7_key]) ) continue;

			$user_val = $this->own_filter_user_value_at_posted_data($user_val, $wpcf7_key, $posted_data);
			$posted_data[$wpcf7_key] = $user_val;
		}

		return $posted_data;
	}

	protected function own_filter_user_value_at_posted_data($user_val, $wpcf7_key, $posted_data){
		return $user_val;
	}

/********************************/

	protected function is_appliable_wpcf7_form($id){
		$id = (
			( class_exists('WPCF7_ContactForm') ) 
			&& ( $id instanceof WPCF7_ContactForm ) 
			&& ( method_exists($id, 'id') ) 
		) ? $id->id() : $id;
		return in_array( (int)$id, $this->form_ids, true );
	}

	protected function get_the_user_data_logged_in(){
		$user_info = array();
		$convert_keys = $this->wpcf7_to_user_keys;
		if( !is_user_logged_in() || !$convert_keys ) return $user_info;

		$current_user = wp_get_current_user();
		foreach( $convert_keys as $wpcf7_key => $user_key ){
			$data = $current_user->get($user_key);
			$data = $this->own_filter_user_data_logged_in($data, $wpcf7_key, $user_key, $current_user);
			$user_info[$wpcf7_key] = $data;
		}

		return $user_info;
	}

	protected function own_filter_user_data_logged_in($data, $wpcf7_key, $user_key, $current_user){
		return $data;
	}

	protected function trim_value($v){
		if( !$this->is_valid_str_value($v) ) return "";

		$v = preg_replace("/(^[ 　\t]+)|([ 　\t]+$)/u", "", (string)$v);
		return $v;
	}

	protected function is_valid_str_value($v){
		return ( is_string($v) || is_numeric($v) ) ? true : false;
	}

	protected function recursive_validate_string($strarr){
		if( !is_array($strarr) ) return $strarr;

		foreach($strarr as $k => $v){
			if( is_array($v) ){
				$strarr[$k] = $this->recursive_validate_string($v);
				continue;
			}

			$v = $this->trim_value($v);
			$v = str_replace( array("\r\n", "\r"), "\n", $v );
			$strarr[$k] = $v;
		}
		return $strarr;
	}
}

