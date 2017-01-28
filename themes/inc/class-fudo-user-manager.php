<?php 
class myFudoUserManager extends myBaseFunctionsWP {
	private $wp_user_extra_components = array(
		"fudo_bukken_users" => array(
			"title" => "不動産会員項目", 
			"components" => array(
				"furigana_first" => array(
					"title" => "フリガナ（姓）", 
					"description" => "", 
					"val" => "", 
					"itype" => "text", 
					"dtype" => "string", 
					"ipt_after" => "カタカナ表記", 
				), 
				"furigana_last" => array(
					"title" => "フリガナ（名）", 
					"description" => "", 
					"val" => "", 
					"itype" => "text", 
					"dtype" => "string", 
					"ipt_after" => "カタカナ表記", 
				), 
				"age" => array(
					"title" => "ご年齢", 
					"description" => "", 
					"val" => "", 
					"itype" => "text", 
					"dtype" => "number", 
					"ipt_after" => "歳　半角数値", 
				), 
				"contact_tel" => array(
					"title" => "ご連絡先電話番号（携帯番号）", 
					"description" => "", 
					"val" => "", 
					"itype" => "text", 
					"dtype" => "number", 
					"ipt_after" => "ハイフン無", 
				), 
				"house_type" => array(
					"title" => "現在のお住まい", 
					"description" => "", 
					"val" => "", 
					"itype" => "radio", 
					"dtype" => "string", 
					"candidates" => array(
						"借家", 
						"持家", 
					), 
				), 
				"job_name" => array(
					"title" => "ご職業（業種）", 
					"description" => "", 
					"val" => "", 
					"itype" => "select", 
					"dtype" => "string", 
					"candidates" => array(
						"サービス業", 
						"製造業", 
						"建設業", 
						"金融機関", 
						"流通小売業", 
						"医療機関", 
						"専業大家", 
						"官公庁", 
						"個人資産管理会社", 
						"飲食業", 
						"不動産投資ファンド", 
						"農林水産業", 
						"その他", 
					), 
				), 
				"income_person_year" => array(
					"title" => "現在のご年収", 
					"description" => "", 
					"val" => "", 
					"itype" => "select", 
					"dtype" => "string", 
					"candidates" => array(
						"600万以下", 
						"600万～800万", 
						"800万～1000万", 
						"1000万～1500万", 
						"1500万以上", 
					), 
				), 
				"job_years" => array(
					"title" => "勤続年数", 
					"description" => "", 
					"val" => "", 
					"itype" => "text", 
					"dtype" => "number", 
					"ipt_after" => "年", 
				), 
				"is_married" => array(
					"title" => "未婚・既婚", 
					"description" => "", 
					"val" => "", 
					"itype" => "radio", 
					"dtype" => "string", 
					"candidates" => array(
						"未婚", 
						"既婚", 
					), 
				), 
				"income_household_year" => array(
					"title" => "世帯年収", 
					"description" => "", 
					"val" => "", 
					"itype" => "select", 
					"dtype" => "string", 
					"candidates" => array(
						"600万以下", 
						"600万～800万", 
						"800万～1000万", 
						"1000万～1500万", 
						"1500万以上", 
					), 
				), 
				"financial_asset_total" => array(
					"title" => "保有金融資産合計", 
					"description" => "", 
					"val" => "", 
					"itype" => "text", 
					"dtype" => "number", 
					"ipt_before" => "約", 
					"ipt_after" => "万円", 
				), 
				"loans_balance" => array(
					"title" => "ローン残高", 
					"description" => "※不動産以外の株式、退職金、積立保険、などの合計を入力してください。", 
					"val" => "", 
					"itype" => "text", 
					"dtype" => "number", 
					"ipt_before" => "約", 
					"ipt_after" => "万円", 
				), 
				"others_note" => array(
					"title" => "その他", 
					"description" => "", 
					"val" => "", 
					"itype" => "textarea", 
					"dtype" => "string", 
				), 
			), 
		), 
	);
	private $fudo_user_role = 'subscriber';
	private $iframe_view_key = 'is_my_iframe';
	private $retrieve_pass_mail_title;

	public function __construct(){
		add_action("init", array($this, "hook_wp_init"), 11); //11 is to make sure using taxonomy

		$this->activate_login_iframe_ctrler();
	}

	public function hook_wp_init(){

		$this->action_filters();

		$this->set_wp_user_extra_components();
	}

	protected function action_filters(){

		if( is_admin() ){
			add_action('show_user_profile', array($this, 'hook_user_profile'), 10, 1);
			add_action('edit_user_profile', array($this, 'hook_user_profile'), 10, 1);
			add_action('personal_options_update', array($this, 'hook_user_update'), 20, 1);
			add_action('edit_user_profile_update', array($this, 'hook_user_update'), 20, 1);

			add_action('admin_menu', array($this, 'remove_admin_menus'));
		}

		add_filter('user_contactmethods', array($this, 'hook_fudo_user_contactmethods'), 20, 2);
		add_action('wp_login', array($this, 'hook_just_wp_user_logged_in'), 20, 2);

	/* plugins/fudoukaiin/fudoukaiin.php */
		remove_filter('wp_footer', 'fudou_kaiin_footer_version');
	/* plugins/fudoukaiin/fudoukaiin-register.php */
		remove_filter('user_contactmethods', 'fudou_original_profile_fields', 11);
	}

	public function remove_admin_menus() {
		if( !current_user_can($this->fudo_user_role) ) return;

		global $menu, $submenu;
		unset($menu[2]); // ダッシュボード
		unset($menu[10]); // メディア
		unset($menu[25]); // コメント
		unset($menu[28]); // ContactForm
		unset($menu[60]); // 外観
		unset($menu[65]); // プラグイン
//		unset($menu[70]); // ユーザー
		unset($menu[75]); // ツール
		unset($menu[80]); // 設定
	}

	public function hook_just_wp_user_logged_in($user_login, $user){
		if( !$this->is_fudo_user($user) ) return;

		if( function_exists('fudoukaiin_userlogin_success') ){
			fudoukaiin_userlogin_success($user->ID);
		}
	}

	protected function is_fudo_user($user){
		$user = ( $user instanceof WP_User ) ? $user : get_userdata($user);
		$roles = isset($user->roles) ? $user->roles : array();
		return (bool)( in_array($this->fudo_user_role, $roles, true) );
	}

	protected function has_fudo_kaiin_level($user_id){
		$fudou_kaiin_level = get_user_meta($user_id, 'fudou_kaiin_level', true);
		return (bool)$fudou_kaiin_level;
	}

	public function hook_fudo_user_contactmethods($contactmethods, $user){
		if( !$this->is_fudo_user($user) ) return $contactmethods;

		$unset_keys = array(
			"googleplus", "aim", "jabber", "yim", 
		);
		foreach( $unset_keys as $k ){
			if( isset($contactmethods[$k]) ) unset($contactmethods[$k]);
		}

		return $contactmethods;
	}

	protected function fudo_user_data_process($user_id, $kind){
		if( !$this->is_fudo_user($user_id) ) return;

	/* based on plugins/fudoukaiin/wp-login.php */
		//会員レベル
		$fudou_kaiin_level = apply_filters( 'fudou_user_kaiin_level', 1 );
		update_user_meta( $user_id, 'fudou_kaiin_level', $fudou_kaiin_level );

		if( 'register' === $kind ){
			//IPアドレス
			$ipaddress = $this->get_str_if_isset($_SERVER, "REMOTE_ADDR");
			if( $ipaddress !='' )
				update_user_meta( $user_id, 'ipaddress', $ipaddress );

			$useragent = $this->get_str_if_isset($_SERVER, "HTTP_USER_AGENT");
			if( $useragent !='' )
				update_user_meta( $user_id, 'useragent', esc_attr( $useragent ) );

			$today = date("Y/m/d");	// 2011/04/01
			if( $today != '' )
				update_user_meta( $user_id, 'login_date', $today );

			update_user_meta( $user_id, 'login_count', '0' );
		}

		//show_admin_bar_front false
		//update_user_meta( $user_id, 'show_admin_bar_front', 'false' ); //←incorrect...
		update_user_option( $user_id, 'show_admin_bar_front', false );
	}

	protected function set_wp_user_extra_components(){
		$ex_components = $this->get_the_wp_user_extra_components();
		if( !$ex_components ) return;

		$blank = array( '' => '--選択--' );
		foreach( $ex_components as $group_key => $component_group ){
			$components = $this->get_arr_if_isset($component_group, 'components');
			if( !$components ) continue;

			foreach( $components as $per_key => $cmpnt ){
				$candidates = $this->get_arr_if_isset($cmpnt, 'candidates');
				if( !$candidates ) continue;

				$candidate_idx = (bool)$this->get_if_isset($cmpnt, 'candidate_index');
				if( $candidate_idx ){
					$candidates = array_values( $candidates );
				} else {
					$candidates = array_combine( $candidates, $candidates );
				}

				$itype = $this->get_str_if_isset($cmpnt, 'itype');
				if( 'select' === $itype ){
					$without_blank_first = (bool)$this->get_if_isset($cmpnt, 'without_blank_first');
					if( !$without_blank_first ){
						$candidates = $blank + $candidates;
					}
				}

				$cmpnt['candidates'] = $candidates;
				$components[$per_key] = $cmpnt;
			}

			$component_group['components'] = $components;
			$ex_components[$group_key] = $component_group;
		}

		$this->wp_user_extra_components = $ex_components;
	}

	protected function get_the_wp_user_extra_components($key=''){
		return $this->get_the_arr_val($this->wp_user_extra_components, $key);
	}

	protected function get_the_arr_val($any, $key=''){
		$key = (string)$key;
		if( '' !== $key ) $any = $this->search_arr_val_deeply( $any, $key );
		return $any;
	}

	protected function make_wp_user_extra_meta_key($prime_key, $per_key){
	//caution, no empty filtering to each argument
		$key = implode( "-", array(
			$prime_key, $per_key, 
		) );
		return $key;
	}

	protected function get_current_wp_user_meta_vals_into_key_value(){
		$user_id = get_current_user_id();
		return $this->get_wp_user_meta_vals_into_key_value($user_id);
	}

	protected function get_wp_user_meta_vals_into_key_value($user_id){
		$key_value = array();
		$meta_vals = get_user_meta($user_id);
		if( !$meta_vals ) return $key_value;

		foreach( $meta_vals as $k => $v ){
			$key_value[$k] = is_array($v) ? array_shift($v) : $v;
		}
		return $key_value;
	}

	public function hook_user_profile($user){
		if( !$this->is_fudo_user($user) ) return;

		$extra_components = $this->get_the_wp_user_extra_components();
		if( !$this->is_valid_arr($extra_components) ) return;

		$meta_vals = $this->get_wp_user_meta_vals_into_key_value($user->ID);
 ?>
	<?php foreach( $extra_components as $prime_key => $component_group ): ?>
	<?php 
		$title = $this->get_str_if_isset($component_group, "title");
		$components = $this->get_arr_if_isset($component_group, "components");
		if( !$components ) continue;
	 ?>
	<h3><?php echo $title; ?></h3>

	<table class="form-table">
		<?php foreach($components as $per_key => $cmpnt): ?>
		<?php 
			$title = $this->get_str_if_isset($cmpnt, "title");
			$meta_key = $this->make_wp_user_extra_meta_key($prime_key, $per_key);

			$itype = $this->get_str_if_isset($cmpnt, "itype");
			$name = "{$prime_key}[{$per_key}]";
			$val = $this->get_the_user_or_component_typed_val($meta_key, $cmpnt, $meta_vals);
			$candidates = $this->get_arr_if_isset($cmpnt, "candidates");

			$ipt_components = array(
				'ipt_before', 'ipt', 'ipt_after', 
			);
			foreach( $ipt_components as $ic_idx => $ic_val ){
				if( 'ipt' === $ic_val ){
					$ic_val = $this->get_util_input_html($itype, $name, $val, $candidates);
					$ipt_components[$ic_idx] = $ic_val;
					continue;
				}

				$exp = $this->get_str_if_isset($cmpnt, $ic_val);
				if( $exp ){
					$cls = str_replace("_", "-", $ic_val) . " ipt-explain";
					$cls = $this->make_cls_attr($cls);
					$exp = $this->make_html_tag('span', $cls, $exp);
				}
				$ipt_components[$ic_idx] = $exp;
			}
			$ipt = implode( "\n", array_filter($ipt_components) );

			$dscrptn = $this->get_str_if_isset($cmpnt, "description");
			$dscrptn = $this->own_filter_wp_user_extra_ipt_description($dscrptn, $prime_key, $per_key);
			if( $dscrptn ){
				$cls = $this->make_cls_attr("description");
				$ipt .= $this->make_html_tag('p', $cls, $dscrptn);
			}
		 ?>
		<tr>
			<th><?php echo $title; ?></th>
			<td><?php echo $ipt; ?></td>
		</tr>
		<?php endforeach; ?>
	</table>
	<?php endforeach; ?>

<?php 
	}

	protected function get_util_input_html($itype, $name, $val, $candidates=array()){
		$ipt_fmt = '<input type="%s" name="%s" value="%s"%s>';
		$sel_fmt = '<select name="%s"%s>%s</select>';
		$sel_opt_fmt = '<option value="%s"%s>%s</option>';
		$txt_fmt = '<textarea name="%s"%s>%s</textarea>';
		$lbl_fmt = '<label for="%s"%s>%s</label>';

		$checked = $this->make_prop('checked');
		$selected = $this->make_prop('selected');
		$disabled = $this->make_prop('disabled');

		$ele_id = $this->get_util_ipt_id($name);

		$ipt = '';
		$ipt_group = '';

		$itype = $this->is_valid_str($itype) ? $itype : 'none';
		switch($itype){
			case 'checkbox':
			case 'radio':
				if( !$this->is_valid_arr($candidates) ) break;

				$opts = array();
				$val = is_array($val) ? $val : array($val);
				$val = array_map('strval', $val);
				$loop_idx = 0;
				foreach($candidates as $c_key => $c_val){
					$prop = ( in_array( (string)$c_key, $val, true ) ) ? $checked : '';
					$i_val = esc_html($c_key);
					$i_txt = esc_html($c_val);

					$id = $ele_id . '-' . (string)$loop_idx;
					$attr = " id=\"{$id}\"{$prop}";
					$ele = sprintf($ipt_fmt, $itype, $name, $i_val, $attr);
					$ele .= $i_txt;
					$ele = sprintf($lbl_fmt, $id, '', $ele);

					$opts[] = $ele;

					$loop_idx++;
				}
				$ipt = implode("\n", $opts);
				$ipt_group = 'check-radio';
				break;
			case 'select':
				if( !$this->is_valid_arr($candidates) ) break;

				$opts = array();
				$val = (string)$val;
				foreach($candidates as $c_key => $c_val){
					$c_key = (string)$c_key;
					$prop = ( $c_key === $val ) ? $selected : '';
					$i_val = esc_html($c_key);
					$i_txt = esc_html($c_val);

					$opts[] = sprintf($sel_opt_fmt, $i_val, $prop, $i_txt);
				}
				$ipt = implode("\n", $opts);
				$ipt = sprintf($sel_fmt, $name, '', $ipt);
				break;
			case 'textarea':
				$val = esc_html($val);
				$val = $this->convert_eol($val);
				$ipt = sprintf($txt_fmt, $name, '', $val);
				break;
			case 'none':
				$val = esc_html($val);
				$ipt = $val;
				break;
			default:
				$val = esc_html($val);
				$ipt = sprintf($ipt_fmt, $itype, $name, $val, '');
				$ipt_group = 'text';
				break;
		}
		if( !$ipt ) return $ipt;

		$wrap_cls = array("ipt-type-{$itype}");
		$wrap_cls[] = "ipt-wrap";
		if( $ipt_group ){
			$wrap_cls[] = "ipt-group-{$ipt_group}";
		}
		$wrap_cls = ( $wrap_cls ) ? $this->make_cls_attr( implode(" ", $wrap_cls) ) : '';
		$ipt = $this->make_html_tag('span', $wrap_cls, $ipt);

		return $ipt;
	}

	protected function get_util_ipt_id($name){
		$name = (string)$name;
		$names = preg_replace("/^[^\[]*/u", "", $name);
		$names = preg_replace("/(^\[)|(\]$)/u", "", $names);
		$names = explode("][", $names);
		$ele_id = implode("-", $names);
		return $ele_id;
	}

	protected function own_filter_wp_user_extra_ipt_description($desc, $prime_key, $per_key){
		return $desc;
	}

	public function hook_user_update($user_id){
		$extra_components = $this->get_the_wp_user_extra_components();
		if( !$this->is_valid_arr($extra_components) ) return;

		if( !current_user_can('edit_user', $user_id) ) return;

		foreach( $extra_components as $prime_key => $component_group ){
			$up_values = $this->get_arr_if_isset($_POST, $prime_key);
			$components = $this->get_arr_if_isset($component_group, "components");
			if( !$up_values || !$components ) continue;

			$up_values = $this->validate_components_values($up_values, $components);
			foreach( $up_values as $per_key => $meta_val ){
				$meta_key = $this->make_wp_user_extra_meta_key($prime_key, $per_key);
				$res = update_user_meta( $user_id, $meta_key, $meta_val );
			}
		}

		$this->fudo_user_data_process($user_id, 'update');
	}

//validators
	protected function validate_user_value($val, $dtype){
		if( in_array( $dtype, array("integer", "number", "float"), true ) ){
			$val = $this->validate_user_numeric($val, $dtype);
		} else {
			settype($val, $dtype);
		}
		return $val;
	}

	protected function validate_user_numeric($nmrc, $dtype="integer"){
		$nmrc = (string)$nmrc;
		$nmrc = $this->trim($nmrc);
		$nmrc = mb_convert_kana($nmrc, "n"); //to half [0-9]
		if( $dtype === "integer" ){
			$nmrc = (int)$nmrc;
		} elseif( $dtype === "float" ){
			$nmrc = (float)$nmrc;
		} elseif( $dtype === "number" ){
			$nmrc = preg_replace("/\D/u", "", $nmrc);
		}
		return $nmrc;
	}

	protected function validate_components_values($values, $components){
		if( !$this->is_valid_arr($components) ) return $values;

		foreach( $components as $key => $cmpnt ){
			$vl = $this->get_the_component_typed_val($cmpnt);
			if( isset($values[$key]) ){
				$dtype = $this->get_str_if_isset($cmpnt, 'dtype');
				$vl = $this->validate_user_value($values[$key], $dtype);
			}
			$values[$key] = $vl;
		}

		return $values;
	}

	protected function get_the_component_data_type($component, $default_type="string"){
		$dtype = $this->get_str_if_isset($component, 'dtype');
		$dtype = ( "" === $dtype ) ? $default_type : $dtype;
		return $dtype;
	}

	protected function get_the_component_data_val($component, $dtype=""){
		$val_raw = $this->get_if_isset($component, 'val');
		$val_typed = $this->get_if_isset($component, 'val', $dtype);
		$val = ( $val_raw === $val_typed ) ? $val_typed : $val_raw;
		return $val;
	}

	protected function get_the_component_typed_val($component){
		$dtype = $this->get_the_component_data_type($component);
		$val = $this->get_the_component_data_val($component, $dtype);
		return $val;
	}

	protected function get_the_user_or_component_typed_val($key, $cmpnt, $user_vals){
		$val = NULL;
		if( !is_array($cmpnt) || !is_array($user_vals) ) return $val;

		$dtype = $this->get_the_component_data_type($cmpnt);
		if( isset($user_vals[$key]) ){
			$val = $this->get_if_isset($user_vals, $key, $dtype);
		} else {
			$val = $this->get_the_component_data_val($cmpnt, $dtype);
		}

		return $val;
	}

	protected function activate_login_iframe_ctrler(){
		add_filter('login_redirect', array($this, 'hook_login_redirect'), 10, 3);

		add_action('login_header', array($this, 'hook_login_header'));
		add_action('login_form', array($this, 'insert_input_to_continue_iframe'));
		add_action('login_footer', array($this, 'hook_login_footer_on_success'));
		add_filter('login_errors', array($this, 'insert_param_to_continue_iframe'));
		add_action('lostpassword_form', array($this, 'insert_input_to_continue_iframe'));
		add_filter('retrieve_password_title', array($this, 'hook_retrieve_password_title'), 100, 3);
	}

	public function hook_login_redirect($redirect_to, $requested_redirect_to, $user){
		if( $this->is_fudo_user($user) ){
			if( preg_match("/wp-(admin|login.php)/u", $requested_redirect_to) ){
				$redirect_to = home_url();
			}
		}
		return $redirect_to;
	}

	public function get_iframe_link($type){
		$link = '';
		switch( $type ){
			case 'lostpassword':
				$link = wp_lostpassword_url();
				break;

			case 'login':
				$link = wp_login_url();
				break;
		}

		if( $link ){
			$link = add_query_arg( array(
				$this->iframe_view_key => 'true', 
				'KeepThis' => 'true', 
				'TB_iframe' => 'true', 
				'width' => '400', 
				'height' => '350', 
			), $link );
		}

		if( !$link ) $link = home_url();

		return $link;
	}

	protected function is_view_by_fudo_user(){
		$user_login = trim( $this->get_str_if_isset($_REQUEST, 'login') );
		if( $user_login ){
			$user_data = get_user_by('login', $user_login);
			return $this->is_fudo_user($user_login);
		}

		return false;
	}

	protected function is_view_by_iframe(){
		return (bool)( isset($_REQUEST[$this->iframe_view_key]) );
	}

	public function hook_login_header(){
		$is_view_by_iframe = $this->get_str_if_isset($_REQUEST, $this->iframe_view_key);
		if( !$this->is_view_by_fudo_user() && !$is_view_by_iframe ) return;
 ?>
<style type="text/css">
.login h1 a, 
.login #backtoblog, 
.login #nav, 
.login .forgetmenot {
	display: none !important;
}
<?php if( 'hidden_loginform' === $is_view_by_iframe ): ?>
.login #loginform {
	display: none !important;
}
<?php endif; ?>
</style>
<?php 
	}

	public function insert_input_to_continue_iframe(){
		if( !$this->is_view_by_iframe() ) return;

		$ipts = array(
			array(
				'name' => $this->iframe_view_key, 
				'value' => doing_filter('lostpassword_form') ? 'hidden_loginform' : 'true', 
			), 
			array(
				'name' => doing_filter('login_form') ? 'interim-login' : '', 
				'value' => 'true', 
			), 
		);

		foreach( $ipts as $ipt ){
			$name = $this->get_str_if_isset($ipt, 'name');
			if( !$name ) continue;

			$ipt = $this->make_ipt_tag( array(
				'type' => 'hidden', 
				'name' => $name, 
				'value' => $this->get_str_if_isset($ipt, 'value'), 
			) );

			echo $ipt;
		}
	}

	public function hook_login_footer_on_success(){
		if( !$this->is_view_by_iframe() ) return;

		global $interim_login;
		if( 'success' !== $interim_login ) return;

 ?>
<script type="text/javascript">
setTimeout( function(){ parent.tb_remove(); parent.location.reload(); }, 1000);
</script>
<?php 
	}

	public function insert_param_to_continue_iframe($errors){
		if( !$this->is_view_by_iframe() ) return $errors;

		if( preg_match("/(href=.)([^\'\"]*?(?=lostpassword)[^\'\"]*?)([\'\"])/u", $errors, $mt) ){
			$ptn = $mt[0];
			unset($mt[0]);

			$mt[2] = add_query_arg($this->iframe_view_key, 'true', $mt[2]);
			$rep = implode('', $mt);
			$errors = str_replace($ptn, $rep, $errors);
		}

		return $errors;
	}

	public function hook_retrieve_password_title($title, $user_login, $user_data){
		if( !$this->is_view_by_iframe() ) return $title;

		$this->retrieve_pass_mail_title = $title;
		add_filter('retrieve_password_message', array($this, 'hook_retrieve_password_message'), 100, 4);
	}

	public function hook_retrieve_password_message($message, $key, $user_login, $user_data){
		if( !$this->retrieve_pass_mail_title ) return $message;

		$user_email = $user_data->user_email;
		$title = $this->retrieve_pass_mail_title;
		if ( $message && !wp_mail( $user_email, wp_specialchars_decode( $title ), $message ) )
			wp_die( __('The email could not be sent.') . "<br />\n" . __('Possible reason: your host may have disabled the mail() function.') );

		$redirect_to = !empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : 'wp-login.php?checkemail=confirm';
		$redirect_to = add_query_arg( array(
			$this->iframe_view_key => 'hidden_loginform', 
		), $redirect_to );
		wp_safe_redirect( $redirect_to );
		exit();
	}

}

