<?php 
/*
Plugin Name: QQWorld Auto Save Images
Plugin URI: https://wordpress.org/plugins/qqworld-auto-save-images/
Description: Automatically keep the all remote picture to the local, and automatically set featured image. 自动保存远程图片到本地，自动设置特色图片，并且支持机器人采集软件从外部提交。
Version: 1.4.2
Author: Michael Wang
Author URI: http://www.qqworld.org
*/

class QQWorld_auto_save_images {
	var $using_action;
	var $type;
	function __construct() {
		$this->using_action = get_option('using_action', 'publish');
		$this->type = get_option('qqworld_auto_save_imagess_type', 'auto');
		switch ($this->type) {
			case 'auto':
				$this->add_actions();
				break;
			case 'manual':
				add_action( 'media_buttons', array($this, 'media_buttons' ), 11 );
				add_action( 'wp_ajax_save_remote_images', array($this, 'save_remote_images') );
				add_action( 'wp_ajax_nopriv_save_remote_images', array($this, 'save_remote_images') );	
				break;
		}
		
		add_action( 'plugins_loaded', array($this, 'load_language') );
		add_action( 'admin_menu', array($this, 'admin_menu') );
		add_action( 'admin_init', array($this, 'register_settings') );
		add_filter( 'plugin_row_meta', array($this, 'registerPluginLinks'),10,2 );
	}

	public function media_buttons() {
		global $post;
	?>
		<style>
		.button.save_remote_images span.wp-media-buttons-icon:before {
			font: 400 18px/1 dashicons;
			speak: none;
			-webkit-font-smoothing: antialiased;
			-moz-osx-font-smoothing: grayscale;
			content: '\f161';
		}
		#save-remote-images-button {
			-webkit-transition: all .25s;
			-moz-transition: all .25s;
			-o-transition: all .25s;
			-ms-transition: all .25s;
			transition: all .25s;
		}
		#save-remote-images-button.success {
			-webkit-transform: scale(1.1);
			-moz-transform: scale(1.1);
			-o-transform: scale(1.1);
			-ms-transform: scale(1.1);
			transform: scale(1.1);
		}
		</style>
		<a href="javascript:" id="save-remote-images-button" class="button save_remote_images" title="<?php _e('Save Remote Images', 'qqworld_auto_save_images'); ?>"><span class="wp-media-buttons-icon"></span><?php _e('Save Remote Images', 'qqworld_auto_save_images'); ?></a>
		<script>
		var QQWorld_auto_save_images = {};
		QQWorld_auto_save_images.post_id = <?php echo $post->ID; ?>;
		QQWorld_auto_save_images.text = {
			save_remote_images: '<?php _e('Save Remote Images', 'qqworld_auto_save_images'); ?>',
			in_process: '<?php _e('In Process...', 'qqworld_auto_save_images'); ?>',
			succesed_save_remote_images: '<?php _e('Successed save remote images', 'qqworld_auto_save_images'); ?>'
		}
		jQuery(function($) {
			$(window).on('load', function() {
				$('.mce-i-save_remote_images').closest('.mce-widget').hide();
				$(document).on('click', '#save-remote-images-button', function() {
					var icon = '<span class="wp-media-buttons-icon"></span>',
					mode = 'text';
					if (tinyMCE.activeEditor) {
						var id = tinyMCE.activeEditor.id;
						mode = $('#'+id).is(':visible') ? 'text' : 'virtual';
					}
					switch (mode) {
						case 'text':
							$('.button.save_remote_images').html(icon+QQWorld_auto_save_images.text.in_process);
							$.ajax({
								type: "POST",
								url: ajaxurl,
								data: {
									action: 'save_remote_images',
									post_id: QQWorld_auto_save_images.post_id,
									content: escape($('#content').val())
								},
								success: function(respond) {
									$('.button.save_remote_images').addClass('success').html(icon+QQWorld_auto_save_images.text.succesed_save_remote_images);
									var init = function() {
										$('.button.save_remote_images').removeClass('success').html(icon+QQWorld_auto_save_images.text.save_remote_images);
									}
									if (respond) $('#content').val(respond)
									setTimeout(init, 3000);
								}
							});
							break;
						case 'virtual':
							$('.button.save_remote_images').html(icon+QQWorld_auto_save_images.text.in_process);
							$.ajax({
								type: "POST",
								url: ajaxurl,
								data: {
									action: 'save_remote_images',
									post_id: QQWorld_auto_save_images.post_id,
									content: escape(tinyMCE.activeEditor.getContent())
								},
								success: function(respond) {
									$('.button.save_remote_images').addClass('success').html(icon+QQWorld_auto_save_images.text.succesed_save_remote_images);
									var init = function() {
										$('.button.save_remote_images').removeClass('success').html(icon+QQWorld_auto_save_images.text.save_remote_images);
									}
									if (respond) tinyMCE.activeEditor.setContent(respond);
									setTimeout(init, 3000);
								}
							});
							break;						
					}
				});
			})
		});

		</script>
	<?php
	}

	public function load_language() {
		load_plugin_textdomain( 'qqworld_auto_save_images', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}

	function registerPluginLinks($links, $file) {
		$base = plugin_basename(__FILE__);
		if ($file == $base) {
			$links[] = '<a href="' . menu_page_url( 'qqworld-auto-save-images', 0 ) . '">' . __('Settings') . '</a>';
		}
		return $links;
	}

	function admin_menu() {
		add_submenu_page('options-general.php', 'QQWorld Auto Save Images', 'QQWorld Auto Save Images', 'manage_options', 'qqworld-auto-save-images', array($this, 'fn'));
	}

	function fn() {
?>
<div class="wrap">
	<h2><?php _e('QQWorld Auto Save Images'); ?></h2>
	<?php if ($_GET['updated']=='true') { ?><div class="updated settings-error" id="setting-error-settings_updated"><p><strong><?php _e('Settings saved.'); ?></strong></p></div><?php }; ?>
	<form action="options.php" method="post">
		<?php settings_fields('qqworld_auto_save_images_settings'); ?>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row"><label for="blogname"><?php _e('Type'); ?></label></th>
					<td><fieldset>
						<legend class="screen-reader-text"><span><?php _e('Type'); ?></span></legend>
							<label for="auto">
								<input name="qqworld_auto_save_imagess_type" type="radio" id="auto" value="auto" <?php checked('auto', $this->type); ?> />
								<?php _e('Automatically save all remote images to local media libary when you save or publish post.', 'qqworld_auto_save_images'); ?>
							</label><br />
							<label for="manual">
								<input name="qqworld_auto_save_imagess_type" type="radio" id="manual" value="manual" <?php checked('manual', $this->type); ?> />
								<?php _e('Manually save all remote images to local media libary when you click the button on the top of editor.', 'qqworld_auto_save_images'); ?>
							</label>
					</fieldset></td>
				</tr>
				
				<tr id="second_level" valign="top"<?php if ($this->type != 'auto') echo ' style="display: none;"'; ?>>
					<th scope="row"><label for="blogname"><?php _e('When', 'qqworld_auto_save_images'); ?></label></th>
					<td><fieldset>
						<legend class="screen-reader-text"><span><?php _e('When', 'qqworld_auto_save_images'); ?></span></legend>
							<label for="save">
								<input name="using_action" type="radio" id="save" value="save" <?php checked('save', $this->using_action); ?> />
								<?php _e('Save post (Publish, save draft or pedding review).', 'qqworld_auto_save_images'); ?>
							</label><br />
							<label for="publish">
								<input name="using_action" type="radio" id="publish" value="publish" <?php checked('publish', $this->using_action); ?> />
								<?php _e('Publish post only.', 'qqworld_auto_save_images'); ?>
							</label>
					</fieldset></td>
				</tr>
			</tbody>
		</table>
		<script>
		jQuery('#auto').on('click', function() {
			jQuery('#second_level').fadeIn('fast');
		});
		jQuery('#manual').on('click', function() {
			jQuery('#second_level').fadeOut('fast');
		});
		</script>
		<p class="submit"><input type="submit" value="<?php _e('Save Changes') ?>" class="button-primary" name="Submit" /></p>
	</form>
<?php
	}

	function register_settings() {
		register_setting('qqworld_auto_save_images_settings', 'qqworld_auto_save_imagess_type');
		register_setting('qqworld_auto_save_images_settings', 'using_action');
	}

	function add_actions() {
		switch ($this->using_action) {
			case 'publish':
				add_action('publish_post', array($this, 'fetch_images') );
				break;
			case 'save':
				add_action('save_post', array($this, 'fetch_images') );
				break;
		}
	}

	function remove_actions() {
		switch ($this->using_action) {
			case 'publish':
				remove_action('publish_post', array($this, 'fetch_images') );
				break;
			case 'save':
				remove_action('save_post', array($this, 'fetch_images') );
				break;
		}
	}

	function js_unescape($str) { // ucseacape escape content via js
		$ret = '';
		$len = strlen($str);
		for ($i = 0; $i < $len; $i++) {
			if ($str[$i] == '%' && $str[$i+1] == 'u') {
				$val = hexdec(substr($str, $i+2, 4));
				if ($val < 0x7f) $ret .= chr($val);
				else if($val < 0x800) $ret .= chr(0xc0|($val>>6)).chr(0x80|($val&0x3f));
				else $ret .= chr(0xe0|($val>>12)).chr(0x80|(($val>>6)&0x3f)).chr(0x80|($val&0x3f));
				$i += 5;
			} else if ($str[$i] == '%') {
				$ret .= urldecode(substr($str, $i, 3));
				$i += 2;
			} else $ret .= $str[$i];
		}
		return $ret;
	}

	function save_remote_images() {
		set_time_limit(0);
		//Check to make sure function is not executed more than once on save
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
		return;

		if ( !current_user_can('edit_post', $post_id) ) 
		return;

		$post_id = $_POST['post_id'];
		$content = $this->js_unescape($_POST['content']);

		$preg=preg_match_all('/<img.*?src="((?![\"\']).*?)((?![\"\'])\?.*?)?"/',stripslashes($content),$matches);
		if($preg){
			foreach($matches[1] as $image_url){
				if(empty($image_url)) continue;
				$pos=strpos($image_url,get_bloginfo('url'));
				if($pos===false){
					if ($res=$this->save_images($image_url,$post_id)) {
						$replace=$res['url'];
						$content=str_replace($image_url,$replace,$content);
					}
				}
			}
		}
		echo $content;
		exit;
	}

	function fetch_images($post_id) {
		set_time_limit(0);
		//Check to make sure function is not executed more than once on save
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
		return;

		if ( !current_user_can('edit_post', $post_id) ) 
		return;

		$this->remove_actions();

		$post=get_post($post_id);
		$content=$post->post_content;
		$preg=preg_match_all('/<img.*?src="((?![\"\']).*?)((?![\"\'])\?.*?)?"/',stripslashes($content),$matches);
		if($preg){
			foreach($matches[1] as $image_url){
				if(empty($image_url)) continue;
				$pos=strpos($image_url,get_bloginfo('url'));
				if($pos===false){
					if ($res=$this->save_images($image_url,$post_id)) {
						$replace=$res['url'];
						$content=str_replace($image_url,$replace,$content);
					}
				}
			}
		}
	    //Replace the image in the post
	    wp_update_post(array('ID' => $post_id, 'post_content' => $content));
		$this->add_actions();
	}

	//save exterior images
	function save_images($image_url, $post_id){
		if ( $file=@file_get_contents($image_url) ) {
			$filename=basename($image_url);
			preg_match( '/(.*?)(\.\w+)$/', $filename, $match );
			$im_name = $match[1].$match[2];
			$res=wp_upload_bits($im_name,'',$file);
			$attach_id = $this->insert_attachment($res['file'],$post_id);
			if( !has_post_thumbnail($post_id) ) set_post_thumbnail( $post_id, $attach_id );
			return $res;
		}
		return false;
	}
	
	//insert attachment
	function insert_attachment($file,$id){
		$dirs=wp_upload_dir();
		$filetype=wp_check_filetype($file);
		$attachment=array(
			'guid'=>$dirs['baseurl'].'/'._wp_relative_upload_path($file),
			'post_mime_type'=>$filetype['type'],
			'post_title'=>preg_replace('/\.[^.]+$/','',basename($file)),
			'post_content'=>'',
			'post_status'=>'inherit'
		);
		$attach_id=wp_insert_attachment($attachment,$file,$id);
		$attach_data=wp_generate_attachment_metadata($attach_id,$file);
		wp_update_attachment_metadata($attach_id,$attach_data);
		return $attach_id;
	}
}
new QQWorld_auto_save_images;