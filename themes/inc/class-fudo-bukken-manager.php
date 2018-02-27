<?php 
class myFudoBukkenManager extends myBaseFunctionsWP {
	protected $kaiin_items = array(
		"title" => "会員物件", //タイトル
		"excerpt" => "", //抜粋
		"kakaku" => "****万円", //価格
		"menseki" => "*****", //面積
		"gazo" => "", //画像
		"madori" => "*****", //間取
		"shozaichi" => "*****", //所在地
		"kotsu" => "*****", //交通
		"tikunen" => "*****", //築年月
		"kaisu" => "*****", //階数
		"shikibesu" => "*****", //物件番号
		"keisaikigenbi" => "*****", //掲載期限日
	);
	protected $kaiin_item_key_converts = array(
		'chikunengetsu' => 'tikunen', 
		'tatemonokozo' => 'tikunen', 
		'tochikukaku' => 'menseki', 
		'tatemonomenseki' => 'menseki', 
	);
	protected $kaiin_item_prefix = "kaiin_";

	public function get_fudo_new_mark_str($post=NULL){
		if( NULL === $post ) global $post;

		//newup_mark
		$new_mark_str = '';
		$max_new_day = (int)get_option('newup_mark');
		if( 0 === $max_new_day ) return $new_mark_str;

		$post_modified_date = vsprintf("%d-%02d-%02d", sscanf($post->post_modified, "%d-%d-%d"));
		$post_date = vsprintf("%d-%02d-%02d", sscanf($post->post_date, "%d-%d-%d"));

		$diff_day = abs( strtotime($post_modified_date) - strtotime( date("Y/m/d") ) );
		$diff_day /= (60 * 60 * 24);

		if( $diff_day < $max_new_day ){
			$new_mark_str = ( $post_modified_date === $post_date ) ? 'new' : 'up';
			$new_mark_str = 'new';
		}

		return $new_mark_str;
	}

	public function get_post_meta_fudo_kaiin($post=NULL){
		if( NULL === $post ) global $post;
		$post_id = is_numeric($post) ? $post : $post->ID;
		$val = get_post_meta($post_id, 'kaiin', true);
		return $val;
	}

	public function is_fudo_kaiin_post($post=NULL){
		$val = $this->get_post_meta_fudo_kaiin($post);
		$bool = ( is_numeric($val) && 0 < (int)$val ) ? true : false;
		return $bool;
	}

	public function is_fudo_vip_kaiin_post($post=NULL){
		$val = $this->get_post_meta_fudo_kaiin($post);
		$bool = ( is_numeric($val) && 1 < (int)$val ) ? true : false;
		return $bool;
	}

	public function is_unviewable_fudo_post($post=NULL){
		$bool = ( !is_user_logged_in() && $this->is_fudo_kaiin_post($post) ) ? true : false;
		return $bool;
	}

	public function is_not_fudo_users_kaiin_bukkenlist($post=NULL){
		if( NULL === $post ) global $post;
		$post_id = is_numeric($post) ? $post : $post->ID;

		$kaiin_opt_val = get_option('kaiin_users_rains_register');
		$kaiin_val = $this->get_post_meta_fudo_kaiin($post);
	//true 現ユーザー閲覧可能 / false 現ユーザー閲覧不可 = ユーザー別会員物件リストに該当
		$bool = (bool)users_kaiin_bukkenlist( $post_id, $kaiin_opt_val, $kaiin_val );
		return $bool;
	}

	public function is_the_fudo_item_viewable($key, $post=NULL){
		if( NULL === $post ) global $post;

		$kaiin = $this->is_unviewable_fudo_post($post) ? 1 : 0;
		$kaiin2 = $this->is_not_fudo_users_kaiin_bukkenlist($post) ? true : false;
		$fudo_opt_key = $this->kaiin_item_prefix . $key;
		$is_item_viewable = my_custom_kaiin_view($fudo_opt_key, $kaiin, $kaiin2);

		return $is_item_viewable;
	}

	public function get_kaiin_item_key($key){
		$kaiin_items = $this->kaiin_items;
		$key_converts = $this->kaiin_item_key_converts;
		$kaiin_key = !isset($kaiin_items[$key]) ? ( 
			isset($key_converts[$key]) ? $key_converts[$key] : false 
		) : $key;
		return $kaiin_key;
	}

	public function get_kaiin_item_val($key){
		$kaiin_items = $this->kaiin_items;
		$kaiin_val = isset($kaiin_items[$key]) ? $kaiin_items[$key] : '';
		return $kaiin_val;
	}

	public function get_appropriate_fudo_val($key, $post=NULL){
		if( NULL === $post ) global $post;
		$post_id = $post->ID;

		$val = '';
		$kaiin_key = $this->get_kaiin_item_key($key);
		if( $kaiin_key && !$this->is_the_fudo_item_viewable($kaiin_key, $post) ){
			$val = $this->get_kaiin_item_val($kaiin_key);
			return $val;
		}

		switch( $key ){
/*** nearly based on fudo theme ***/
			case 'title':
				$val = get_the_title();
				break;

			case 'excerpt':
				$val = get_the_excerpt();
				break;

			case 'kakaku':
				$meta_val = get_post_meta($post_id, 'seiyakubi', true);
				$val = 'ご成約済';
				if( !$meta_val ){
					$val = $this->call_fudo_func_with_wrapping_ob('my_custom_kakaku_print', $post_id);
					$kakaku_int = preg_match("/(\d+)[^\d]/u", $val, $mt) ? (int)$mt[1] : '';
					if( $kakaku_int ){
						$val = str_replace($kakaku_int, number_format($kakaku_int), $val);
					}
				}
				break;

			case 'shozaichi':
				$val = $this->call_fudo_func_with_wrapping_ob('my_custom_shozaichi_print', $post_id);
				$val .= get_post_meta($post_id, 'shozaichimeisho', true);
				break;

			case 'kotsu':
				$val = $this->call_fudo_func_with_wrapping_ob('my_custom_koutsu1_print', $post_id);
				$val .= $this->call_fudo_func_with_wrapping_ob('my_custom_koutsu2_print', $post_id);
				break;

			case 'kaisu':
				$meta_info = array(
					'tatemonokaisu1' => '地上%s階', 
					'tatemonokaisu2' => '地下%s階', 
				);
				$val = $this->get_fudo_post_meta_single_value($post_id, $meta_info);
				break;

/*** only get_post_meta() ***/
			case 'shikibesu':
			case 'bukkensoukosu':
			case 'tochisetsudomaguchi1':
			case 'tochisetsudomaguchi2':
			case 'tochisetsudofukuin1':
			case 'tochisetsudofukuin2':
			case 'nyukyonengetsu':
			case 'keisaikigenbi':
				$val = get_post_meta($post_id, $key, true);
				break;

/*** only fudo's my_custom_{$key}_print() ***/
			case 'tochikenri':
			case 'tochisetsudo':
			case 'torihikitaiyo':
			case 'chushajo':
			case 'tochichimoku':
			case 'tochiyouto':
			case 'tochikeikaku':
			case 'tochichisei':
			case 'tochisetsudohouko1':
			case 'tochisetsudohouko2':
			case 'tochisetsudoshurui1':
			case 'tochisetsudoshurui2':
			case 'tochisetsudoichishitei1':
			case 'tochisetsudoichishitei2':
			case 'tochikokudohou':
			case 'nyukyojiki':
			case 'nyukyosyun':
			case 'bukkenshubetsu':
			case 'jyoutai':
			case 'nyukyogenkyo':
				$fudo_func_name = "my_custom_{$key}_print";
				$val = $this->call_fudo_func_with_wrapping_ob($fudo_func_name, $post_id);
				break;

/*** adjust site theme ***/
			case 'rimawari':
				if( $this->is_unviewable_fudo_post($post) ){
					$val = "***";
				} else {
					$val = array_filter( array(
						get_post_meta($post_id, 'kakakuhyorimawari', true), 
						#get_post_meta($post_id, 'kakakurimawari', true), 
					) );
					$val = ( $val ) ? array_shift($val) : '';
				}
				$val .= ( $val ) ? '%' : '';
				break;

			case 'tochikenpei':
			case 'tochiyoseki':
				$meta_info = array(
					$key => '%s%%', 
				);
				$val = $this->get_fudo_post_meta_single_value($post_id, $meta_info);
				break;

			case 'chikunengetsu':
				$meta_val = get_post_meta($post_id, 'tatemonochikunenn', true);
				if( preg_match("/^([\d]{4})[^\d]([\d]+)$/u", $meta_val, $mt) ){
					$val = $mt[1] . "年" . $mt[2] . "月";
				}
				break;

			case 'tatemonokozo':
				$val = $this->call_fudo_func_with_wrapping_ob('my_custom_tatemonokozo_print', $post_id);
				#$val .= $this->call_fudo_func_with_wrapping_ob('my_custom_tatemonoshinchiku_print', $post_id);
				break;

			case 'tochikukaku':
			case 'tatemonomenseki':
			case 'tochishido':
			case 'tochisetback2':
				$meta_info = array(
					$key => '%sm&sup2;', 
				);
				$val = $this->get_fudo_post_meta_single_value($post_id, $meta_info);
				break;

			case 'bukkenmei':
				$meta_val = (int)get_post_meta($post_id, 'bukkenmeikoukai', true);
				if( 0 < $meta_val ){
					$val = get_post_meta($post_id, $key, true);
				}
				break;

			case 'tochisetsudo_conditions':
				$val = array(
					$this->get_appropriate_fudo_val('tochisetsudo'), 
				);

			/* 北東側/幅員6m/の公道/で約9.2m/に接道 (/位置指定道路) */
				$part_info = array(
					'tochisetsudohouko' => '%s側', 
					'tochisetsudofukuin' => '幅員%sm', 
					'tochisetsudoshurui' => 'の%s', 
					'tochisetsudomaguchi' => 'で約%sm', 
					'part_block' => 'に接道 ', 
					'tochisetsudoichishitei' => '%s', 
				);

				for( $i=1; $i<=2; $i++ ){
					$blk = array();
					foreach( $part_info as $k => $fmt ){
						if( 'part_block' === $k ){
							if( $blk ) $blk[] = $fmt;
							continue;
						}

						$fudo_key = $k . (string)$i;
						$v = (string)$this->get_appropriate_fudo_val($fudo_key);
						if( strlen($v) ){
							$blk[$k] = sprintf($fmt, $v);
						}
					}
					$val[] = implode("", $blk);
				}
				$val = implode( "<br>", array_filter($val) );
				break;

			case 'nyukyo':
				$val = implode( '', array(
					$this->get_appropriate_fudo_val('nyukyojiki'), 
					$this->get_appropriate_fudo_val('nyukyonengetsu'), 
					$this->get_appropriate_fudo_val('nyukyosyun'), 
				) );
				break;
		}

		return $val;
	}

	public function get_fudo_gazos_by_id_range_between($min, $max, $only_exist=false, $post=NULL){
		if( NULL === $post ) global $post;
		$post_id = $post->ID;

		$gazos = array();
		$min = (int)$min;
		$max = (int)$max;
		if( $min > $max ) return $gazos;

		$plugin_url = WP_PLUGIN_URL;
		$src_kaiin = "/images/kaiin.png";
		if( file_exists( get_stylesheet_directory() . $src_kaiin ) ){
			$src_kaiin = get_stylesheet_directory_uri() . $src_kaiin;
		} else {
			$src_kaiin = "{$plugin_url}/fudou/img/kaiin.jpg";
		}

		$src_nowprint = "/images/nowprinting.png";
		if( file_exists( get_stylesheet_directory() . $src_nowprint ) ){
			$src_nowprint = get_stylesheet_directory_uri() . $src_nowprint;
		} else {
			$src_nowprint = "{$plugin_url}/fudou/img/nowprinting.jpg";
		}

		$is_gazo_viewable = $this->is_the_fudo_item_viewable("gazo", $post);
		$kaiin_img = '<img src="'.$src_kaiin.'" alt="" />';

		global $wpdb;
		$a_fmt = '<a href="%1$s" rel="lightbox[%2$s] lytebox[%2$s]" title="%3$s">%4$s</a>';
		$img_fmt = '<img class="box2image" src="%s" alt="%s"%s />';
		$attr_fmt = ' %s="%s"';
		for( $img_id=$min; $img_id<=$max; $img_id++ ){
			$img_name = get_post_meta($post_id, "fudoimg{$img_id}", true);
			$img_comment = get_post_meta($post_id, "fudoimgcomment{$img_id}", true);
			$img_alt = get_post_meta($post_id, "fudoimgtype{$img_id}", true);
			$img_alt = $img_comment . my_custom_fudoimgtype_print($img_alt);

			if( !$img_name ){
				$image = '';
				if( !$only_exist ){
					$image = $kaiin_img;
					if( $is_gazo_viewable ){
						$image = sprintf($img_fmt, $src_nowprint, "", "");
					}
				}
				$gazos[$img_id] = $image;
				continue;
			}

			//Check URL
			if( checkurl_fudou($img_name) ){
				$image = $kaiin_img;
				if( $is_gazo_viewable ){
					$attr = sprintf($attr_fmt, "title", $img_alt);
					$image = sprintf($img_fmt, $img_name, $img_alt, $attr);
					$image = sprintf($a_fmt, $img_name, $post_id, $img_alt, $image);
				}
				$gazos[$img_id] = $image;
				continue;
			}

			//Check attachment
			$sql = $wpdb->prepare("SELECT P.ID, P.guid 
				FROM {$wpdb->posts} AS P 
				WHERE P.post_type = %s 
				AND P.guid LIKE %s", 
				"attachment", "%/{$img_name}"
			);
			$metas = $wpdb->get_row($sql);

			$attachmentid = ( $metas ) ? $metas->ID : '';
			if( !$attachmentid ){
				$image = '';
				if( !$only_exist ){
					$image = $kaiin_img;
					if( $is_gazo_viewable ){
						$image = sprintf($img_fmt, $src_nowprint, $img_name, "");
					}
				}
				$gazos[$img_id] = $image;
				continue;
			}

			//thumbnail、medium、large、full 
			$attachment_src = wp_get_attachment_image_src($attachmentid, 'large');
			$attachment_src = $attachment_src[0];
			$guid_url = $metas->guid;
			$img_src = ( $attachment_src ) ? $attachment_src : $guid_url;

			$image = $kaiin_img;
			if( $is_gazo_viewable ){
				$attr = sprintf($attr_fmt, "title", $img_alt);
				$image = sprintf($img_fmt, $img_src, $img_alt, $attr);
				$image = sprintf($a_fmt, $guid_url, $post_id, $img_alt, $image);
			}
			$gazos[$img_id] = $image;
		}

		return $gazos;
	}

	public function get_fudo_post_meta_single_value($post_id, $meta_info, $sep=" "){
		$val = '';
		if( !is_array($meta_info) || !$meta_info ) return $val;

		foreach( $meta_info as $meta_key => $meta_fmt ){
			$v = get_post_meta($post_id, $meta_key, true);
			$v = is_string($v) ? $v : '';
			$meta_info[$meta_key] = ( '' !== $v ) ? sprintf($meta_fmt, $v) : '';
		}
		$val = implode( $sep, array_filter($meta_info) );
		return $val;
	}

	protected function call_fudo_func_with_wrapping_ob($func, $arg){
		$res = NULL;
		if( !function_exists($func) ) return $res;

		ob_start();
		call_user_func($func, $arg);
		$res = ob_get_clean();

		return $res;
	}

}
