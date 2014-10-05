<?php 
/*
Plugin Name: QQWorld Auto Save Images
Plugin URI: https://wordpress.org/plugins/qqworld-auto-save-images/
Description: Automatically keep the all remote picture to the local, and automatically set featured image. 自动保存远程图片到本地，自动设置特色图片，并且支持机器人采集软件从外部提交。
Version: 1.5.5
Author: Michael Wang
Author URI: http://www.qqworld.org
*/
define('QQWORLD_AUTO_SAVE_IMAGES_DIR', __DIR__ . DIRECTORY_SEPARATOR);
define('QQWORLD_AUTO_SAVE_IMAGES_URL', plugin_dir_url(__FILE__));

class QQWorld_auto_save_images {
	var $using_action;
	var $type;
	function __construct() {
		$this->using_action = get_option('using_action', 'publish');
		$this->type = get_option('qqworld_auto_save_imagess_type', 'auto');
		$this->featured_image = get_option('qqworld_auto_save_imagess_set_featured_image', 'yes');
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

		add_action( 'wp_ajax_get_scan_list', array($this, 'get_scan_list') );
		add_action( 'wp_ajax_nopriv_get_scan_list', array($this, 'get_scan_list') );
		add_action( 'wp_ajax_save_remote_images_after_scan', array($this, 'save_remote_images_after_scan') );
		add_action( 'wp_ajax_nopriv_save_remote_images_after_scan', array($this, 'save_remote_images_after_scan') );
		
		add_action( 'plugins_loaded', array($this, 'load_language') );
		add_action( 'admin_menu', array($this, 'admin_menu') );
		add_action( 'admin_init', array($this, 'register_settings') );
		add_filter( 'plugin_row_meta', array($this, 'registerPluginLinks'),10,2 );
	}

	public function get_scan_list() {
		if ( !current_user_can( 'manage_options' ) ) return;
		$post_types = isset($_REQUEST['qqworld_auto_save_imagess_post_types']) ? $_REQUEST['qqworld_auto_save_imagess_post_types'] : 'post';
		$offset = empty($_REQUEST['offset']) ? 0 : $_POST['offset'];
		$posts_per_page = $_REQUEST['posts_per_page'];
		$args = array(
			'posts_per_page' => $posts_per_page,
			'offset' => $offset,
			'order' => 'ASC',
			'post_type' => $post_types
		);
		$posts = get_posts($args);
		echo json_encode($posts);
		exit;
	}

	public function save_remote_images_after_scan() {
		set_time_limit(0);
		if ( !current_user_can( 'manage_options' ) ) return;
		$post_ids = $_REQUEST['post_id'];
		if (!empty($post_ids)) foreach ($post_ids as $post_id) :
			$post = get_post($post_id);
			$post_id = $post->ID;
			$post_type =  $post->post_type;
			$content = $post->post_content;
			$title = $post->post_title;
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
			wp_update_post(array('ID' => $post_id, 'post_content' => $content));
?>
			<tr>
				<td><?php echo $post_id; ?></td>
				<td><?php echo $post_type; ?></td>
				<td><a href="<?php echo get_edit_post_link($post_id); ?>" target="_blank"><?php echo $title; ?></a></td>
				<td><?php _e('Done'); ?></td>
			</tr>
<?php
		endforeach;
		exit;
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
		<script src="<?php echo QQWORLD_AUTO_SAVE_IMAGES_URL; ?>js/jquery.noty.packaged.min.js"></script>
		<a href="javascript:" id="save-remote-images-button" class="button save_remote_images" title="<?php _e('Save Remote Images', 'qqworld_auto_save_images'); ?>"><span class="wp-media-buttons-icon"></span><?php _e('Save Remote Images', 'qqworld_auto_save_images'); ?></a>
		<script>
		var QQWorld_auto_save_images = {};
		QQWorld_auto_save_images.post_id = <?php echo $post->ID; ?>;
		QQWorld_auto_save_images.text = {
			in_process: '<?php _e('In Process...', 'qqworld_auto_save_images'); ?>',
			succesed_save_remote_images: '<?php _e('Successed save remote images', 'qqworld_auto_save_images'); ?>'
		}
		jQuery(function($) {
			$(window).on('load', function() {
				$('.mce-i-save_remote_images').closest('.mce-widget').hide();
				$(document).on('click', '#save-remote-images-button', function() {
					var mode = 'text';
					if (tinyMCE.activeEditor) {
						var id = tinyMCE.activeEditor.id;
						mode = $('#'+id).is(':visible') ? 'text' : 'virtual';
					}
					switch (mode) {
						case 'text':
							$('#save-remote-images-button').data('noty', noty({
								text: QQWorld_auto_save_images.text.in_process,	
								type: 'notification',
								layout: 'center',
								modal: true,
								closeWith: ['button']
							}) );
							$.ajax({
								type: "POST",
								url: ajaxurl,
								data: {
									action: 'save_remote_images',
									post_id: QQWorld_auto_save_images.post_id,
									content: escape($('#content').val())
								},
								success: function(respond) {
									$('#save-remote-images-button').data('noty').close();
									var n = noty({
										text: QQWorld_auto_save_images.text.succesed_save_remote_images,	
										type: 'success',
										layout: 'center',
										timeout: 3000
									});
									if (respond) $('#content').val(respond);
								}
							});
							break;
						case 'virtual':
							$('#save-remote-images-button').data('noty', noty({
								text: QQWorld_auto_save_images.text.in_process,	
								type: 'notification',
								layout: 'center',
								modal: true,
								closeWith: ['button']
							}) );
							$.ajax({
								type: "POST",
								url: ajaxurl,
								data: {
									action: 'save_remote_images',
									post_id: QQWorld_auto_save_images.post_id,
									content: escape(tinyMCE.activeEditor.getContent())
								},
								success: function(respond) {
									$('#save-remote-images-button').data('noty').close();
									var n = noty({
										text: QQWorld_auto_save_images.text.succesed_save_remote_images,	
										type: 'success',
										layout: 'center',
										timeout: 3000
									});
									if (respond) tinyMCE.activeEditor.setContent(respond);
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
		add_submenu_page('options-general.php', __('QQWorld Auto Save Images', 'qqworld_auto_save_images'), __('QQWorld Auto Save Images', 'qqworld_auto_save_images'), 'manage_options', 'qqworld-auto-save-images', array($this, 'fn'));
	}

	function fn() {
?>
<div class="wrap">
	<h2><?php _e('QQWorld Auto Save Images', 'qqworld_auto_save_images'); ?></h2>
	<?php if ($_GET['updated']=='true') { ?><div class="updated settings-error" id="setting-error-settings_updated"><p><strong><?php _e('Settings saved.'); ?></strong></p></div><?php }; ?>
	<script src="<?php echo QQWORLD_AUTO_SAVE_IMAGES_URL; ?>js/jquery.noty.packaged.min.js"></script>
	<link rel='stylesheet' href='<?php echo QQWORLD_AUTO_SAVE_IMAGES_URL; ?>css/style.css' type='text/css' media='all' />
	<form action="options.php" method="post" id="form">
		<?php settings_fields('qqworld_auto_save_images_settings'); ?>
		<img src="https://ps.w.org/qqworld-auto-save-images/assets/banner-772x250.png" width="772" height="250" id="banner" />
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row"><label><?php _e('Type'); ?></label></th>
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
					<th scope="row"><label><?php _e('When', 'qqworld_auto_save_images'); ?></label></th>
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

				<tr valign="top">
					<th scope="row"><label><?php _e('Automatically Set Featured Image', 'qqworld_auto_save_images'); ?></label></th>
					<td><fieldset>
						<legend class="screen-reader-text"><span><?php _e('Automatically Set Featured Image', 'qqworld_auto_save_images'); ?></span></legend>
							<label for="qqworld_auto_save_imagess_set_featured_image_yes">
								<input name="qqworld_auto_save_imagess_set_featured_image" type="radio" id="qqworld_auto_save_imagess_set_featured_image_yes" value="yes" <?php checked('yes', $this->featured_image); ?> />
								<?php _e('Yes'); ?>
							</label><br />
							<label for="qqworld_auto_save_imagess_set_featured_image_no">
								<input name="qqworld_auto_save_imagess_set_featured_image" type="radio" id="qqworld_auto_save_imagess_set_featured_image_no" value="no" <?php checked('no', $this->featured_image); ?> />
								<?php _e('No'); ?>
							</label>
					</fieldset></td>
				</tr>

				<tr valign="top">
					<th scope="row"><label><?php _e('Scan Old Posts', 'qqworld_auto_save_images'); ?></label></th>
					<td>
					<div id="post_types_list">
						<p><?php _e('Select post types you want to scan:', 'qqworld_auto_save_images'); ?> <?php
						$post_types = get_post_types('', 'objects');
						foreach ($post_types as $name => $post_type) : ?>
							<label><input name="qqworld_auto_save_imagess_post_types[]" type="checkbox" value="<?php echo $name; ?>" /> <?php echo $post_type->labels->name; ?></label>
						<?php endforeach;
						?></p>
						<p><?php _e('Filter:', 'qqworld_auto_save_images'); ?> <?php printf(__('Start from %s Scan', 'qqworld_auto_save_images'), '<input type="number" class="small-text" name="offset" value="0" disabled />'); ?>
							<select name="posts_per_page">
								<option value="-1"><?php _e('All'); ?></option>
								<?php for ($i=1; $i<=10; $i++) : ?>
								<option value="<?php echo $i*100; ?>"><?php echo $i*100; ?></option>
								<?php endfor; ?>
							</select> <?php _e('Posts'); ?>
						</p>
						<p class="description"><?php _e("If you want to scan 50-150 posts, please type \"50\" in the textfield and choose \"100\" in the select, and do not choose \"all\".", 'qqworld_auto_save_images'); ?></p>
					</div>
					
					<fieldset>
						<legend class="screen-reader-text"><span><?php _e('Scan Old Posts', 'qqworld_auto_save_images'); ?></span></legend>
							<?php _e('Speed:', 'qqworld_auto_save_images'); ?>
							<select name="speed">
								<?php for ($i=1; $i<=10; $i++) : ?>
								<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
								<?php endfor; ?>
							</select>
							<label for="scan_old_posts">
								<input name="scan_old_posts" type="button" class="button-primary" id="scan_old_posts" value="<?php _e('Scan', 'qqworld_auto_save_images'); ?> &#8667;" />
							</label>
							<p class="description"><?php _e('Scan posts and keep remote images in all posts to local media library. Maybe take a long time.', 'qqworld_auto_save_images'); ?></p>
					</fieldset>
					</td>
				</tr>
			</tbody>
		</table>
		<script>
		if (!QQWorld_auto_save_images) var QQWorld_auto_save_images = {};
		QQWorld_auto_save_images.are_your_sure = '<?php _e('Are you sure? Before you click the yes button, I recommend you backup the website database.', 'qqworld_auto_save_images'); ?>';
		QQWorld_auto_save_images.scan = function(respond, r) {
			var $ = jQuery;
			if (typeof respond[r] == 'undefined') {
				$('body').data('noty').close();
				noty({
					text: '<?php _e('All done.', 'qqworld_auto_save_images'); ?>',	
					type: 'success',
					layout: 'bottomCenter',
					dismissQueue: true
				});
				$('#scan_old_posts').removeAttr('disabled');
				return;
			}
			var speed = parseInt($('select[name="speed"]').val()),
			post_id = new Array;
			var data = 'action=save_remote_images_after_scan';
			for (var p=r; p<r+speed; p++) {
				if (typeof respond[p] != 'undefined') data += '&post_id[]='+respond[p]['ID'];
			}
			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: data,
				success: function(data) {
					data = $(data);
					$('#scan_old_post_list tbody').append(data);
					data.hide().fadeIn('fast');
					r += speed;
					QQWorld_auto_save_images.scan(respond, r);
				}
			});
		};
		QQWorld_auto_save_images.events = function() {
			var $ = jQuery;
			$('select[name="posts_per_page"]').on('change', function() {
				if ($(this).val() == '-1') $('input[name="offset"]').attr('disabled', true);
				else $('input[name="offset"]').removeAttr('disabled', true);
			});
			$('#auto').on('click', function() {
				$('#second_level').fadeIn('fast');
			});
			$('#manual').on('click', function() {
				$('#second_level').fadeOut('fast');
			});
			$('#scan_old_posts').on('click', function() {
				if (jQuery('input[name="qqworld_auto_save_imagess_post_types[]"]:checked').length) {
					var n = noty({
						text: QQWorld_auto_save_images.are_your_sure,	
						type: 'warning',
						dismissQueue: true,
						layout: 'center',
						modal: true,
						buttons: [
							{
								addClass: 'button button-primary',
								text: '<?php _e('Yes'); ?>',
								onClick: function ($noty) {
									$noty.close();
									$('#scan_old_posts').attr('disabled', true);
									var data = $('#form').serialize()+'&action=get_scan_list';
									$.ajax({
										type: 'POST',
										url: ajaxurl,
										data: data,
										dataType: 'json',
										success: function(respond) {
											$('#scan-result').html('<table id="scan_old_post_list">\
											\	<thead>\
											\		<th><?php _e('ID'); ?></th>\
											\		<th><?php _e('Post Type', 'qqworld_auto_save_images'); ?></th>\
											\		<th><?php _e('Title'); ?></th>\
											\		<th><?php _e('Status'); ?></th>\
											\	</thead>\
											\	<tbody>\
											\	</tbody>\
											\</table>');
											$('body').data('noty', noty({
												text: '<?php _e('Scanning...', 'qqworld_auto_save_images'); ?>',	
												type: 'notification',
												layout: 'bottomCenter',
												dismissQueue: true
											}) );
											QQWorld_auto_save_images.scan(respond, 0);
										}
									});
								}
							},
							{
								addClass: 'button button-primary',
								text: '<?php _e('No'); ?>',
								onClick: function ($noty) {
									$noty.close();
								}
							}
						]
					});
				} else {
					var n = noty({
						text: '<?php _e('Please select the post type you want to scan.', 'qqworld_auto_save_images'); ?>',	
						type: 'error',
						dismissQueue: true,
						layout: 'bottomCenter',
						timeout: 3000
					});
				}
			});
		};
		QQWorld_auto_save_images.events();
		</script>
		<p class="submit"><input type="submit" value="<?php _e('Save Changes') ?>" class="button-primary" name="Submit" /></p>
	</form>
	<div id="scan-result"></div>
<?php
	}

	function register_settings() {
		register_setting('qqworld_auto_save_images_settings', 'qqworld_auto_save_imagess_type');
		register_setting('qqworld_auto_save_images_settings', 'using_action');
		register_setting('qqworld_auto_save_images_settings', 'qqworld_auto_save_imagess_set_featured_image');
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
		wp_update_post(array('ID' => $post_id, 'post_content' => $content));
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
			if( !has_post_thumbnail($post_id) && $this->featured_image=='yes' ) set_post_thumbnail( $post_id, $attach_id );
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