<?php 
/*
Plugin Name: QQWorld Auto Save Images
Plugin URI: https://wordpress.org/plugins/qqworld-auto-save-images/
Description: Automatically keep the all remote picture to the local, and automatically set featured image. 自动保存远程图片到本地，自动设置特色图片，并且支持机器人采集软件从外部提交。
Version: 1.5.7.1
Author: Michael Wang
Author URI: http://www.qqworld.org
*/
define('QQWORLD_AUTO_SAVE_IMAGES_DIR', __DIR__ . DIRECTORY_SEPARATOR);
define('QQWORLD_AUTO_SAVE_IMAGES_URL', plugin_dir_url(__FILE__));

class QQWorld_auto_save_images {
	var $using_action;
	var $type;
	var $preg = '/<img.*?src="((?![\"\']).*?)((?![\"\'])\?.*?)?".*?>/';
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
		add_action( 'wp_ajax_save_remote_images_list_all_posts', array($this, 'save_remote_images_list_all_posts') );
		add_action( 'wp_ajax_nopriv_save_remote_images_list_all_posts', array($this, 'save_remote_images_list_all_posts') );
		
		add_action( 'plugins_loaded', array($this, 'load_language') );
		add_action( 'admin_menu', array($this, 'admin_menu') );
		add_action( 'admin_init', array($this, 'register_settings') );
		add_filter( 'plugin_row_meta', array($this, 'registerPluginLinks'),10,2 );

		add_action( 'admin_head', array($this, 'options_general_add_js') );

	}

	public function options_general_add_js() {
		?><script src="<?php echo QQWORLD_AUTO_SAVE_IMAGES_URL; ?>js/jquery.noty.packaged.min.js"></script>
		<link rel='stylesheet' href='<?php echo QQWORLD_AUTO_SAVE_IMAGES_URL; ?>css/style.css' type='text/css' media='all' />
		<link rel='stylesheet' href='<?php echo QQWORLD_AUTO_SAVE_IMAGES_URL; ?>css/jquery-ui/jquery-ui.min.css' type='text/css' media='all' /><?php
		wp_enqueue_script('jquery-ui-tooltip');
		wp_enqueue_script('jquery-effects-core');
		wp_enqueue_script('jquery-effects-shake');
	}

	public function get_scan_list() {
		if ( !current_user_can( 'manage_options' ) ) return;
		$post_types = isset($_REQUEST['qqworld_auto_save_imagess_post_types']) ? $_REQUEST['qqworld_auto_save_imagess_post_types'] : 'post';
		// Scope of ID
		$id_from = $_REQUEST['id_from'];
		$id_to = $_REQUEST['id_to'];
		$id_from = $id_from=='0' ? 1 : $id_from;
		$id_to = $id_to=='0' ? 1 : $id_to;
		$post__in = array();
		if (!empty($id_from) && is_numeric($id_from) && empty($id_to)) {
				$post__in[] = $id_from;
		} elseif (empty($id_from) && !empty($id_to) && is_numeric($id_to)) {
			$post__in[] = $id_to;
		} elseif (!empty($id_from) && is_numeric($id_from) && !empty($id_to) && is_numeric($id_to)) {
			if ($id_from == $id_to) $post__in[] = $id_from;
			elseif ($id_from < $id_to) for ($s=$id_from; $s<=$id_to; $s++) $post__in[]=$s;
			elseif($id_from > $id_to) for ($s=$id_from; $s>=$id_to; $s--) $post__in[]=$s;
		}
		// Offset
		$offset = empty($_REQUEST['offset']) ? 0 : $_POST['offset'];
		$posts_per_page = $_REQUEST['posts_per_page'];
		$args = array(
			'posts_per_page' => $posts_per_page,
			'offset' => $offset,
			'order' => 'ASC',
			'post_type' => $post_types,
			'post__in' => $post__in
		);
		$posts = get_posts($args);
		$result=array();
		foreach ($posts as $post) array_push($result, $post->ID);
		echo json_encode($result);
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
			$preg=preg_match_all($this->preg,stripslashes($content),$matches);
			$has_remote_images = false;
			$has_not_exits_remote_images = false;
			if($preg){
				foreach($matches[1] as $image_url){
					if(empty($image_url)) continue;
					$pos=strpos($image_url,get_bloginfo('url'));
					if($pos===false){
						$has_remote_images = true;
						if ($res=$this->save_images($image_url,$post_id)) {
							$replace=$res['url'];
							$content=str_replace($image_url,$replace,$content);
						} else $has_not_exits_remote_images = true;
					}
				}
			}
			wp_update_post(array('ID' => $post_id, 'post_content' => $content));
			$post_type_object = get_post_type_object($post_type);
			if ($has_remote_images && $has_not_exits_remote_images) $class =' class="has_remote_images has_not_exits_remote_images"';
			else $class = $has_remote_images ? ' class="has_remote_images"' : '';
?>
			<tr<?php echo $class; ?>>
				<td><?php echo $post_id; ?></td>
				<td><?php echo $post_type_object->labels->name; ?></td>
				<td><a href="<?php echo get_edit_post_link($post_id); ?>" target="_blank"><?php echo $title; ?> &#8667;</a></td>
				<td><?php
					if ($has_remote_images) {
						echo $has_not_exits_remote_images ? '<span class="red">'.__('Has missing images.', 'qqworld_auto_save_images').'</span>' : '<span class="green">'.__('All remote images have been saved.', 'qqworld_auto_save_images').'</span>';
					} else _e('No remote images found.', 'qqworld_auto_save_images')
				?></td>
			</tr>
<?php
		endforeach;
		exit;
	}

	public function save_remote_images_list_all_posts() {
		set_time_limit(0);
		if ( !current_user_can( 'manage_options' ) ) return;
		$post_ids = $_REQUEST['post_id'];
		if (!empty($post_ids)) foreach ($post_ids as $post_id) :
			$post = get_post($post_id);
			$post_id = $post->ID;
			$post_type =  $post->post_type;
			$content = $post->post_content;
			$title = $post->post_title;
			$preg=preg_match_all($this->preg,stripslashes($content),$matches);
			$has_remote_images = false;
			$has_not_exits_remote_images = false;
			if($preg){
				foreach($matches[1] as $image_url){
					if(empty($image_url)) continue;
					$pos=strpos($image_url,get_bloginfo('url'));
					if($pos===false) {
						$has_remote_images = true;
						$has_not_exits_remote_images = @!fopen( $image_url, 'r' );
					}
				}
			}
			if ($has_remote_images) :
				$post_type_object = get_post_type_object($post_type);
				$class = $has_not_exits_remote_images ? ' has_not_exits_remote_images' : '';
?>
			<tr class="has_remote_images<?php echo $class; ?>">
				<td><?php echo $post_id; ?></td>
				<td><?php echo $post_type_object->labels->name; ?></td>
				<td><a href="<?php echo get_edit_post_link($post_id); ?>" target="_blank"><?php echo $title; ?> &#8667;</a></td>
				<td><?php echo $has_not_exits_remote_images ? '<span class="red">'.__('Has missing images.', 'qqworld_auto_save_images').'</span>' : __('Normal', 'qqworld_auto_save_images'); ?></a></td>
				<td id="list-<?php echo $post_id; ?>"><input type="button" post-id="<?php echo $post_id; ?>" class="fetch-remote-images button button-primary" value="&#9997; <?php _e('Fetch', 'qqworld_auto_save_images'); ?>" /></td>
			</tr>
<?php
			else:
?>
			<tr>
				<td colspan="5" class="hr"></td>
			</tr>
<?php		endif;
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
	<div id="scan-result"></div>
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
					<th scope="row"><label><?php _e('Scan Posts', 'qqworld_auto_save_images'); ?></label> <span class="icon help" title="<?php _e("If you have too many posts to be scan, sometimes in process looks like stopping, but it may be fake. please be patient.", 'qqworld_auto_save_images') ?>"></span></th>
					<td>
					<div id="post_types_list">
						<p><?php _e('Select post types:', 'qqworld_auto_save_images'); ?> <?php
						$post_types = get_post_types('', 'objects'); ?>
						<ul>
						<?php foreach ($post_types as $name => $post_type) :
							if ( !in_array($name, array('attachment', 'revision', 'nav_menu_item') )) : ?>
							<li><label><input name="qqworld_auto_save_imagess_post_types[]" type="checkbox" value="<?php echo $name; ?>" /> <?php echo $post_type->labels->name; ?> (<?php $count = wp_count_posts($name); echo $count->publish; ?>)</label></li>
						<?php endif;
						endforeach;
						?></ul>
					</div>

					<p id="scope-id"><?php _e('Scope of Post ID:', 'qqworld_auto_save_images'); ?> <?php printf(__('From %1$s to %2$s', 'qqworld_auto_save_images'), '<input type="number" class="small-text" name="id_from" />', '<input type="number" class="small-text" name="id_to" />'); ?> <span class="icon help" title="<?php _e("Default empty for scan all posts ID. If you want to scan posts ID from 50 to 100. please type '50' and '100' or '100' and '50', The order in which two numbers can be reversed. If you only type one number, system would only scan that ID.", 'qqworld_auto_save_images'); ?>"></span></p>
					
					<p><?php _e('Offset:', 'qqworld_auto_save_images'); ?> <?php printf(__('Start from %s to Scan', 'qqworld_auto_save_images'), '<input type="number" class="small-text" name="offset" value="0" disabled />'); ?>
						<select name="posts_per_page">
							<option value="-1"><?php _e('All'); ?></option>
							<?php for ($i=1; $i<=10; $i++) : ?>
							<option value="<?php echo $i*100; ?>"><?php echo $i*100; ?></option>
							<?php endfor; ?>
						</select> <?php _e('Posts'); ?> <span class="icon help" title="<?php _e("Default scan all posts. If you want to scan 50-150 posts, please type '50' in the textfield and select '100'.", 'qqworld_auto_save_images'); ?>"></span>
					</p>
					
					<fieldset>
						<legend class="screen-reader-text"><span><?php _e('Scan Posts', 'qqworld_auto_save_images'); ?></span></legend>
							<?php _e('Speed:', 'qqworld_auto_save_images'); ?>
							<select name="speed">
								<?php for ($i=1; $i<10; $i++) : ?>
								<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
								<?php endfor; ?>
								<option value="10" selected>10</option>
							</select> <span class="icon help" title="<?php _e('If the server is too much stress may be appropriately reduced speed.', 'qqworld_auto_save_images'); ?>"></span><br />
							<label for="scan_old_posts">
								<input name="scan_old_posts" type="button" class="button-primary" id="scan_old_posts" value="<?php _e('Automatic', 'qqworld_auto_save_images'); ?> &#8667;" />
							</label> <span class="icon help" title="<?php _e('Scan posts and keep remote images in all posts to local media library. Maybe take a long time.', 'qqworld_auto_save_images'); ?>"></span>
							<label for="print_all_posts">
								<input name="list_all_posts" type="button" class="button-primary" id="list_all_posts" value="<?php _e('Manual', 'qqworld_auto_save_images'); ?> &#9776;" />
							</label> <span class="icon help" title="<?php _e("The list displayed will show you which posts including remote images, then you can keep them to local manually via click \"Fetch\" button.", 'qqworld_auto_save_images'); ?>"></span>
					</fieldset>
					</td>
				</tr>
			</tbody>
		</table>
		<script>
		function str_repeat(i, m) {
			for (var o = []; m > 0; o[--m] = i);
			return o.join('');
		}
		function sprintf() {
			var i = 0, a, f = arguments[i++], o = [], m, p, c, x, s = '';
			while (f) {
				if (m = /^[^\x25]+/.exec(f)) {
					o.push(m[0]);
				}
				else if (m = /^\x25{2}/.exec(f)) {
					o.push('%');
				}
				else if (m = /^\x25(?:(\d+)\$)?(\+)?(0|'[^$])?(-)?(\d+)?(?:\.(\d+))?([b-fosuxX])/.exec(f)) {
					if (((a = arguments[m[1] || i++]) == null) || (a == undefined)) {
						throw('Too few arguments.');
					}
					if (/[^s]/.test(m[7]) && (typeof(a) != 'number')) {
						throw('Expecting number but found ' + typeof(a));
					}
					switch (m[7]) {
						case 'b': a = a.toString(2); break;
						case 'c': a = String.fromCharCode(a); break;
						case 'd': a = parseInt(a); break;
						case 'e': a = m[6] ? a.toExponential(m[6]) : a.toExponential(); break;
						case 'f': a = m[6] ? parseFloat(a).toFixed(m[6]) : parseFloat(a); break;
						case 'o': a = a.toString(8); break;
						case 's': a = ((a = String(a)) && m[6] ? a.substring(0, m[6]) : a); break;
						case 'u': a = Math.abs(a); break;
						case 'x': a = a.toString(16); break;
						case 'X': a = a.toString(16).toUpperCase(); break;
					}
					a = (/[def]/.test(m[7]) && m[2] && a >= 0 ? '+'+ a : a);
					c = m[3] ? m[3] == '0' ? '0' : m[3].charAt(1) : ' ';
					x = m[5] - String(a).length - s.length;
					p = m[5] ? str_repeat(c, x) : '';
					o.push(s + (m[4] ? a + p : p + a));
				}
				else {
					throw('Huh ?!');
				}
				f = f.substring(m[0].length);
			}
			return o.join('');
		}
		if (!QQWorld_auto_save_images) var QQWorld_auto_save_images = {};
		QQWorld_auto_save_images.are_your_sure = '<?php _e('Are you sure?<br />Before you click the yes button, I recommend backup site database.', 'qqworld_auto_save_images'); ?>';
		QQWorld_auto_save_images.pls_select_post_types = '<?php _e('Please select post types.', 'qqworld_auto_save_images'); ?>';
		QQWorld_auto_save_images.maybe_problem = '<?php _e('May be a problem with some posts: ', 'qqworld_auto_save_images'); ?>';
		QQWorld_auto_save_images.catch_errors = function(XMLHttpRequest, textStatus, errorThrown) {
			var $=jQuery, error='', args=new Array;
			error += '<div style="text-align: left;">';
			var query = this.data.split('&');
			var data = new Array;
			for (var d in query) {
				var q = query[d].split('=');
				if (q[0]=='post_id[]') {
					data.push(q[1]);
				}
			}
			error += QQWorld_auto_save_images.maybe_problem + data.join(', ');
			if (XMLHttpRequest) {
				error += '<hr />';
				args = new Array;
				for (var x in XMLHttpRequest) {
					switch (x) {
						case 'readyState':
						case 'responseText':
						case 'status':
							args.push( x + ': ' + XMLHttpRequest[x] );
							break;
					}
				}
				error += args.join('<br />', args);
			}
			error += '<br />' + textStatus + ': ' + errorThrown;
			error += '</div>';
			$('#form').slideDown('slow');
			$('body').data('noty').close();
			$('#scan_old_post_list').slideUp('slow');
			noty({
				text: error,	
				type: 'error',
				layout: 'bottom',
				dismissQueue: true,
				closeWith: ['button']
			});
			$('#scan_old_posts').removeAttr('disabled');
			$('#list_all_posts').removeAttr('disabled');
		};
		QQWorld_auto_save_images.scan = function(respond, r) {
			var $ = jQuery;
			if (typeof respond[r] == 'undefined') {
				$('#scan-result').effect( 'shake', null, 500 );
				$('#form').slideDown('slow');
				$('body').data('noty').close();
				var count = $('#scan_old_post_list tbody tr').length;
				var count_remote_images = $('#scan_old_post_list tbody tr.has_remote_images').length;
				var count_not_exits_remote_images = $('#scan_old_post_list tbody tr.has_not_exits_remote_images').length;
				var count = $('#scan_old_post_list tbody tr').length;
				if (count) {
					if (count==1) count_html = sprintf("<?php _e( '%d post has been scanned.', 'qqworld_auto_save_images'); ?>", count);
					else count_html = sprintf("<?php _e( '%d posts have been scanned.', 'qqworld_auto_save_images'); ?>", count);
					if (count_remote_images) {
						count_remote_images = count_remote_images - count_not_exits_remote_images;
						if (count_remote_images<=1) count_html += sprintf("<br /><?php _e( '%d post included remote images processed.', 'qqworld_auto_save_images'); ?>", count_remote_images);
						else count_html += sprintf("<br /><?php _e( '%d posts included remote images processed.', 'qqworld_auto_save_images'); ?>", count_remote_images);
						if (count_not_exits_remote_images) {
							if (count_not_exits_remote_images==1) count_html += sprintf("<br /><?php _e( "%d post has missing images couldn't be processed.", 'qqworld_auto_save_images'); ?>", count_not_exits_remote_images);
							else count_html += sprintf("<br /><?php _e( "%d posts have missing images couldn't be processed.", 'qqworld_auto_save_images'); ?>", count_not_exits_remote_images);
						}
					}
				} else {
					$('#scan_old_post_list').slideUp('slow');
					count_html = '<?php _e('No posts found.', 'qqworld_auto_save_images'); ?>';
				}
				noty({
					text: '<?php _e('All done.', 'qqworld_auto_save_images'); ?><br />'+count_html,	
					type: 'success',
					layout: 'center',
					dismissQueue: true,
					modal: true
				});
				$('#scan_old_posts').removeAttr('disabled');
				$('#list_all_posts').removeAttr('disabled');
				return;
			}
			var speed = parseInt($('select[name="speed"]').val()),
			post_id = new Array;
			var data = 'action=save_remote_images_after_scan';
			for (var p=r; p<r+speed; p++) {
				if (typeof respond[p] != 'undefined') data += '&post_id[]='+respond[p];
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
				},
				error: QQWorld_auto_save_images.catch_errors
			});
		};
		QQWorld_auto_save_images.list = function(respond, r) {
			var $ = jQuery;
			if (typeof respond[r] == 'undefined') {
				$('#scan-result').effect( 'shake', null, 500 );
				$('#form').slideDown('slow');
				$('body').data('noty').close();
				var count = $('#scan_old_post_list tbody tr').length;
				var count_remote_images = $('#scan_old_post_list tbody tr.has_remote_images').length;
				var count_not_exits_remote_images = $('#scan_old_post_list tbody tr.has_not_exits_remote_images').length;
				if (count) {
					if (count==1) count_html = sprintf("<?php _e( '%d post has been scanned.', 'qqworld_auto_save_images'); ?>", count);
					else count_html = sprintf("<?php _e( '%d posts have been scanned.', 'qqworld_auto_save_images'); ?>", count);
					if (count_remote_images) {
						if (count_remote_images==1) count_html += sprintf("<br /><?php _e( 'found %d post including remote images.', 'qqworld_auto_save_images'); ?>", count_remote_images);
						else count_html += sprintf("<br /><?php _e( 'found %d posts including remote images.', 'qqworld_auto_save_images'); ?>", count_remote_images);
						if (count_not_exits_remote_images) {
							if (count_not_exits_remote_images==1) count_html += sprintf("<br /><?php _e( "And with %d post has missing images.", 'qqworld_auto_save_images'); ?>", count_not_exits_remote_images);
							else count_html += sprintf("<br /><?php _e( "And with %d posts have missing images.", 'qqworld_auto_save_images'); ?>", count_not_exits_remote_images);
						}
					} else count_html += '<br /><?php _e('No post has remote images found.', 'qqworld_auto_save_images'); ?>';
				} else {
					$('#scan_old_post_list').slideUp('slow');
					count_html = '<?php _e('No posts found.', 'qqworld_auto_save_images'); ?>';
				}
				noty({
					text: '<?php _e('All done.', 'qqworld_auto_save_images'); ?><br />'+count_html,	
					type: 'success',
					layout: 'center',
					dismissQueue: true,
					modal: true
				});
				$('#scan_old_posts').removeAttr('disabled');
				$('#list_all_posts').removeAttr('disabled');
				return;
			}
			var speed = parseInt($('select[name="speed"]').val()),
			post_id = new Array;
			var data = 'action=save_remote_images_list_all_posts';
			for (var p=r; p<r+speed; p++) {
				if (typeof respond[p] != 'undefined') data += '&post_id[]='+respond[p];
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
					QQWorld_auto_save_images.list(respond, r);
				},
				error: QQWorld_auto_save_images.catch_errors
			});
		};
		QQWorld_auto_save_images.if_not_select_post_type = function() {
			var $ = jQuery;
			$('#post_types_list').effect( 'shake', null, 500 );
			var n = noty({
				text: QQWorld_auto_save_images.pls_select_post_types,	
				type: 'error',
				dismissQueue: true,
				layout: 'bottomCenter',
				timeout: 3000
			});
		}
		QQWorld_auto_save_images.events = function() {
			var $ = jQuery;
			$(".icon.help").tooltip({
				show: {
					effect: "slideDown",
					delay: 250
				}
			});
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
									$('#form').slideUp('slow');
									$noty.close();
									$('#scan_old_posts').attr('disabled', true);
									$('#list_all_posts').attr('disabled', true);
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
												layout: 'center',
												dismissQueue: true
											}) );
											QQWorld_auto_save_images.scan(respond, 0);
										},
										error: QQWorld_auto_save_images.catch_errors
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
				} else QQWorld_auto_save_images.if_not_select_post_type();
			});
			$('#list_all_posts').on('click', function() {
				if (jQuery('input[name="qqworld_auto_save_imagess_post_types[]"]:checked').length) {
					$('#form').slideUp('slow');
					$('#scan_old_posts').attr('disabled', true);
					$('#list_all_posts').attr('disabled', true);
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
							\		<th><?php _e('Control', 'qqworld_auto_save_images'); ?></th>\
							\	</thead>\
							\	<tbody>\
							\	</tbody>\
							\</table>');
							$('body').data('noty', noty({
								text: '<?php _e('Listing...', 'qqworld_auto_save_images'); ?>',	
								type: 'notification',
								layout: 'center',
								dismissQueue: true
							}) );
							QQWorld_auto_save_images.list(respond, 0);
						},
						error: QQWorld_auto_save_images.catch_errors
					});
				} else QQWorld_auto_save_images.if_not_select_post_type();
			});
			$(document).on('click', '#scan_old_post_list .fetch-remote-images', function() {
				var wait = '<img src="data:image/gif;base64,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" />';
				var post_id = $(this).attr('post-id');
				$(this).hide().after(wait);
				var data = 'action=save_remote_images_after_scan&post_id[]='+post_id;
				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: data,
					success: function(data) {
						$('#list-'+post_id).html('<span class="green"><?php _e('Done'); ?></span>');
					},
					error: QQWorld_auto_save_images.catch_errors
				});
			})
		};
		jQuery(function($) {
			QQWorld_auto_save_images.events();
		});
		</script>
		<p class="submit"><input type="submit" value="<?php _e('Save Changes') ?>" class="button-primary" name="Submit" /></p>
	</form>
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

		$post_id = $_POST['post_id'];

		if ( !current_user_can('edit_post', $post_id) ) 
		return;

		$content = $this->js_unescape($_POST['content']);

		$preg=preg_match_all($this->preg,stripslashes($content),$matches);
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
		$preg=preg_match_all($this->preg,stripslashes($content),$matches);
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
		set_time_limit(0);
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