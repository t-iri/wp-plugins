<?php 
abstract class MyDoubleLoginModel {
	protected $apply_user_logins = array();
	protected $apply_user_roles = array();
	protected $wp_path_relations = array(
	//relative path with slash at both head and tail
	/*** 
		'/' => '/abc/', 
		'/abc/' => '/', 
	 ***/
	);
	protected $double_key = 'madl_login';
	protected $crypt_key = 'login_madl';

	private $through_action_after_login = false;

/****************************************************************/

	public function __construct(){
		if( !$this->exist_required_functions() ) return;

		$this->normalize_vars();

		$this->hook_login_actions();
		$this->hook_logout_actions();
	}

/****************************************************************/

	private function exist_required_functions(){
		return array_filter( array(
			'openssl_encrypt', 
			'openssl_decrypt', 
		), 'function_exists' ) ? true : false;
	}

/****************************************************************/

	private function normalize_vars(){
		$this->apply_user_logins = is_array($this->apply_user_logins) ? $this->apply_user_logins : array();
		$this->apply_user_roles = is_array($this->apply_user_roles) ? $this->apply_user_roles : array();
		$this->wp_path_relations = is_array($this->wp_path_relations) ? $this->wp_path_relations : array();
		$this->double_key = is_string($this->double_key) ? $this->double_key : '';
		$this->crypt_key = is_string($this->crypt_key) ? $this->crypt_key : '';
	}

/****************************************************************/

	protected function hook_login_actions(){
		add_action('wp_login', array($this, 'login_double'), 10, 2);
		add_action('login_init', array($this, 'check_double_login'), 10);
	}

/****************************************************************/

	public function login_double($user_login, $user){
		if ( !$this->is_wp_user_obj($user) || $this->through_action_after_login ) return;

		$roles = $user->roles;
		$roles = is_array($roles) ? $roles : array();

		$can_apply = array_filter( array(
			in_array($user_login, $this->apply_user_logins, true), 
			array_intersect($this->apply_user_roles, $roles), 
		) );
		$the_another_wp_url = $this->get_the_another_wp_url('wp-login.php');
		if( $can_apply && $the_another_wp_url && $this->double_key ){
			$pwd = isset($_POST['pwd']) ? $_POST['pwd'] : '';
			$red_to = admin_url();
			if( isset($_REQUEST['interim-login']) ){
				$red_to = add_query_arg( array(
					$this->double_key => $this->get_safe_crypt( $user_login, $pwd ), 
					'interim-login' => $this->double_key, 
					'k' => $this->crypt_key, 
				), wp_login_url() );
			}
			wp_safe_redirect( add_query_arg( array(
				$this->double_key => $this->get_safe_crypt( $user_login, $pwd ), 
				'redirect_to' => urlencode( $red_to ), 
				'k' => $this->crypt_key, 
				'action' => 'login', 
			), $the_another_wp_url ) );
			exit;
		}
	}

	public function check_double_login(){
		$receive_key = isset( $_GET['k'] ) ? $_GET['k'] : '';
		$receive_val = isset( $_GET[$this->double_key] ) ? urldecode( $_GET[$this->double_key] ) : '';
		if( !$receive_key || !$receive_val ) return;

		$phrases = $this->restore_safe_crypt($receive_val, $receive_key);
		if( isset($phrases[0]) && isset($phrases[1]) ){
			$_POST['log'] = $phrases[0];
			$_POST['pwd'] = $phrases[1];
			$this->through_action_after_login = true;
		}
	}

/****************************************************************/

	protected function hook_logout_actions(){
		add_action('wp_logout', array($this, 'double_logout'), 10);
		add_action('login_init', array($this, 'check_double_logout'), 10);
	}

/****************************************************************/

	public function double_logout(){
		if( !$this->is_request_action_eq('logout') ) return;
		if( isset($_GET[$this->double_key]) ) return;

		wp_safe_redirect( add_query_arg( array(
			$this->double_key => '', 
			'redirect_to' => urlencode( add_query_arg( 'loggedout', 'true', wp_login_url() ) ), 
			'action' => 'logout', 
		), $this->get_the_another_wp_url('wp-login.php') ) );
		exit;
	}

	public function check_double_logout(){
		if( !$this->is_request_action_eq('logout') ) return;

		$avoild_loop = 'o';
		if( !isset($_GET[$this->double_key]) || $_GET[$this->double_key] === $avoild_loop ) return;

		$the_another_wp_url = $this->get_the_another_wp_url('wp-login.php');
		wp_safe_redirect( add_query_arg( array(
			$this->double_key => $avoild_loop, 
			'redirect_to' => isset($_GET['redirect_to']) ? $_GET['redirect_to'] : $the_another_wp_url, 
			'action' => 'logout', 
		), str_replace( '&amp;', '&', wp_logout_url() ) ) );
		exit;
	}

/****************************************************************/

	protected function get_the_another_wp_url($tail_path=''){
		return $this->make_the_another_wp_url($tail_path);
	}

	private function make_the_another_wp_url($tail_path){
		$parses = parse_url( home_url('/') );
		$path = isset($parses['path']) ? $parses['path'] : '';
		if( !$path || !isset($this->wp_path_relations[$path]) ) return false;

		$url = trailingslashit( implode( '', array(
			'http://', 
			isset($parses['host']) ? $parses['host'] : '', 
			$this->wp_path_relations[$path], 
		) ) ) . $tail_path;
		return filter_var($url, FILTER_VALIDATE_URL);
	}

/****************************************************************/

	protected function is_request_action_eq($v){
		return ( isset($_REQUEST['action']) && $v === $_REQUEST['action'] );
	}

	protected function is_wp_user_obj($obj){
		return (bool)( is_object($obj) && $obj instanceof WP_User );
	}

/****************************************************************/

	protected function restore_safe_crypt($crypt, $key){
		$crypt = urldecode($crypt);
		$crypt = str_replace( array('_','-', '.'), array('+', '/', '='), $crypt );
		return $this->restore_crypt($crypt, $key);
	}

	protected function restore_crypt($crypt, $key){
		$sep = '/';
		$crypt = openssl_decrypt($crypt, 'AES-128-ECB', $key);
		return explode($sep, $crypt, 2);
	}

	protected function get_safe_crypt($phrase1, $phrase2){
		$crypt = $this->make_crypt($phrase1, $phrase2);
		$crypt = str_replace( array('+', '/', '='), array('_', '-', '.'), $crypt );
		return urlencode($crypt);
	}

	protected function make_crypt($phrase1, $phrase2){
		$sep = '/';
		$plain_text = $phrase1 . $sep . $phrase2;
		return openssl_encrypt($plain_text, 'AES-128-ECB', $this->crypt_key);
	}

}
