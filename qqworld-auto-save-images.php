<?php 
/*
Plugin Name: QQWorld Auto Save Images
Plugin URI: https://wordpress.org/plugins/qqworld-auto-save-images/
Description: Automatically keep the all remote picture to the local, and automatically set featured image.
Version: 1.5.9
Author: Michael Wang
Author URI: http://www.qqworld.org
*/
define('QQWORLD_AUTO_SAVE_IMAGES_DIR', __DIR__ . DIRECTORY_SEPARATOR);
define('QQWORLD_AUTO_SAVE_IMAGES_URL', plugin_dir_url(__FILE__));

class QQWorld_auto_save_images {
	var $using_action;
	var $type;
	var $preg = '/<img.*?src=[\"\']((?![\"\']).*?)((?![\"\'])\?.*?)?[\"\']/';
	var $exclude_domain;
	function __construct() {
		$this->using_action = get_option('using_action', 'publish');
		$this->type = get_option('qqworld_auto_save_imagess_type', 'auto');
		$this->featured_image = get_option('qqworld_auto_save_imagess_set_featured_image', 'yes');
		$this->exclude_domain = get_option('qqworld-auto-save-images-exclude-domain');
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

		add_action( 'admin_enqueue_scripts', array($this, 'add_to_post_php') );
		add_action( 'admin_enqueue_scripts', array($this, 'add_to_page_qqworld_auto_save_images') );
	}

	public function add_to_post_php() {
		global $post;
		if ($GLOBALS['hook_suffix'] == 'post.php') {
			wp_register_script('noty', QQWORLD_AUTO_SAVE_IMAGES_URL . 'js/jquery.noty.packaged.min.js', array('jquery') );
			wp_enqueue_script('noty');
			wp_register_script('qqworld-auto-save-images-script-post', QQWORLD_AUTO_SAVE_IMAGES_URL . 'js/script-post.js', array('jquery') );
			wp_enqueue_script('qqworld-auto-save-images-script-post');
			$translation_array = array(
				'post_id' => $post->ID,
				'in_process' => __('In Process...', 'qqworld_auto_save_images'),
				'succesed_save_remote_images' => __('Successed save remote images', 'qqworld_auto_save_images')
			);
			wp_localize_script('qqworld-auto-save-images-script-post', 'QASI', $translation_array, '3.0.0');
		}
	}

	public function add_to_page_qqworld_auto_save_images() {
		if ($GLOBALS['hook_suffix'] == 'settings_page_qqworld-auto-save-images') {
			wp_register_script('noty-4-save', QQWORLD_AUTO_SAVE_IMAGES_URL . 'js/jquery.noty.packaged.min.js', array('jquery') );
			wp_enqueue_script('noty-4-save');
			wp_register_style('qqworld-auto-save-images-style', QQWORLD_AUTO_SAVE_IMAGES_URL . 'css/style.css' );
			wp_enqueue_style('qqworld-auto-save-images-style');
			wp_register_style('jquery-ui-style', QQWORLD_AUTO_SAVE_IMAGES_URL . 'css/jquery-ui/jquery-ui.min.css' );
			wp_enqueue_style('jquery-ui-style');
			wp_enqueue_script('jquery-ui-tooltip');
			wp_enqueue_script('jquery-effects-core');
			wp_enqueue_script('jquery-effects-shake');
			wp_register_script('qqworld-auto-save-images-script', QQWORLD_AUTO_SAVE_IMAGES_URL . 'js/script.js', array('jquery') );
			wp_enqueue_script('qqworld-auto-save-images-script');
			$translation_array = array(
				'are_your_sure' => __('Are you sure?<br />Before you click the yes button, I recommend backup site database.', 'qqworld_auto_save_images'),
				'pls_select_post_types' => __('Please select post types.', 'qqworld_auto_save_images'),
				'maybe_problem' => __('May be a problem with some posts: ', 'qqworld_auto_save_images'),
				'n_post_has_been_scanned' => __( '%d post has been scanned.', 'qqworld_auto_save_images'),
				'n_posts_have_been_scanned' => __( '%d posts have been scanned.', 'qqworld_auto_save_images'),
				'n_post_included_remote_images_processed' => __( '%d post included remote images processed.', 'qqworld_auto_save_images'),
				'n_posts_included_remote_images_processed' => __( '%d posts included remote images processed.', 'qqworld_auto_save_images'),
				'n_post_has_missing_images_couldnt_be_processed' => __( "%d post has missing images couldn't be processed.", 'qqworld_auto_save_images'),
				'n_posts_have_missing_images_couldnt_be_processed' => __( "%d posts have missing images couldn't be processed.", 'qqworld_auto_save_images'),
				'found_n_post_including_remote_images' => __( 'found %d post including remote images.', 'qqworld_auto_save_images'),
				'found_n_posts_including_remote_images' => __( 'found %d posts including remote images.', 'qqworld_auto_save_images'),
				'and_with_n_post_has_missing_images' => __( "And with %d post has missing images.", 'qqworld_auto_save_images'),
				'and_with_n_posts_have_missing_images' => __( "And with %d posts have missing images.", 'qqworld_auto_save_images'),
				'no_posts_processed' => __( "No posts processed.", 'qqworld_auto_save_images'),
				'no_post_has_remote_images_found' => __('No post has remote images found.', 'qqworld_auto_save_images'),
				'no_posts_found' => __('No posts found.', 'qqworld_auto_save_images'),
				'all_done' => __('All done.', 'qqworld_auto_save_images'),
				'yes' => __('Yes'),
				'no' => __('No'),
				'scanning' => __('Scanning...', 'qqworld_auto_save_images'),
				'listing' => __('Listing...', 'qqworld_auto_save_images'),
				'id' => __('ID'),
				'post_type' => __('Post Type', 'qqworld_auto_save_images'),
				'title' => __('Title'),
				'status' => __('Status'),
				'control' => __('Control', 'qqworld_auto_save_images'),
				'done' => __('Done'),
				'delete' => __('Delete'),
				'scheme' => is_ssl() ? 'https://' : 'http://'
			);
			wp_localize_script('qqworld-auto-save-images-script', 'QASI', $translation_array, '3.0.0');
		}
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
					// exclude domain
					$allow=true;
					if (!empty($this->exclude_domain)) foreach ($this->exclude_domain as $domain) {
						$pos=strpos($image_url, $domain);
						if($pos) $allow=false;
					}
					if ($allow) {
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
			}
			wp_update_post(array('ID' => $post_id, 'post_content' => $content));
			$post_type_object = get_post_type_object($post_type);
			if ($has_remote_images) :
				$class = 'has_remote_images';
				if ($has_not_exits_remote_images) $class += ' has_not_exits_remote_images';
				$class = ' class="' . $class . '"';
?>
			<tr<?php echo $class; ?>>
				<td><?php echo $post_id; ?></td>
				<td><?php echo $post_type_object->labels->name; ?></td>
				<td><a href="<?php echo get_edit_post_link($post_id); ?>" target="_blank"><?php echo $title; ?> &#8667;</a></td>
				<td><?php echo $has_not_exits_remote_images ? '<span class="red">'.__('Has missing images.', 'qqworld_auto_save_images').'</span>' : '<span class="green">'.__('All remote images have been saved.', 'qqworld_auto_save_images').'</span>'; ?></td>
			</tr>
<?php else: ?>
			<tr>
				<td colspan="4" class="hr"></td>
			</tr>
<?php		endif;
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
					// exclude domain
					$allow=true;
					if (!empty($this->exclude_domain)) foreach ($this->exclude_domain as $domain) {
						$pos=strpos($image_url, $domain);
						if($pos) $allow=false;
					}
					if ($allow) {
						$pos=strpos($image_url,get_bloginfo('url'));
						if($pos===false) {
							$has_remote_images = true;
							$has_not_exits_remote_images = @!fopen( $image_url, 'r' );
						}
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
<?php else: ?>
			<tr>
				<td colspan="5" class="hr"></td>
			</tr>
<?php		endif;
		endforeach;
		exit;
	}

	public function media_buttons() {
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
		ul[id^="noty_"] li {
			margin-bottom: 0;
		}
		</style>
		<a href="javascript:" id="save-remote-images-button" class="button save_remote_images" title="<?php _e('Save Remote Images', 'qqworld_auto_save_images'); ?>"><span class="wp-media-buttons-icon"></span><?php _e('Save Remote Images', 'qqworld_auto_save_images'); ?></a>
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
					<th scope="row"><label><?php _e('Mode', 'qqworld_auto_save_images'); ?></label></th>
					<td><fieldset>
						<legend class="screen-reader-text"><span><?php _e('Mode', 'qqworld_auto_save_images'); ?></span></legend>
							<label for="auto">
								<input name="qqworld_auto_save_imagess_type" type="radio" id="auto" value="auto" <?php checked('auto', $this->type); ?> />
								<?php _e('Automatic', 'qqworld_auto_save_images'); ?>
							</label> <span class="icon help" title="<?php _e('Automatically save all remote images to local media libary when you save or publish post.', 'qqworld_auto_save_images'); ?>"></span><br />
							<label for="manual">
								<input name="qqworld_auto_save_imagess_type" type="radio" id="manual" value="manual" <?php checked('manual', $this->type); ?> />
								<?php _e('Manual', 'qqworld_auto_save_images'); ?>
							</label> <span class="icon help" title="<?php _e('Manually save all remote images to local media libary when you click the button on the top of editor.', 'qqworld_auto_save_images'); ?>"></span>
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
					<th scope="row"><label><?php _e('Exclude Domain/Keyword', 'qqworld_auto_save_images'); ?></label> <span class="icon help" title="<?php _e("Images will not be saved, if that url contains Exclude-Domain/Keyword.", 'qqworld_auto_save_images'); ?>"></span></th>
					<td><fieldset>
						<legend class="screen-reader-text"><span><?php _e('Exclude Domain', 'qqworld_auto_save_images'); ?></span></legend>
							<ul id="exclude_domain_list">
							<?php
							if (!empty($this->exclude_domain)) foreach ($this->exclude_domain as $domain) :
								if (!empty($domain)) :
							?>
							<li><?php echo is_ssl() ? 'https://' : 'http://' ?> <input type="text" name="qqworld-auto-save-images-exclude-domain[]" class="regular-text" value="<?php echo $domain; ?>" /><input type="button" class="button delete-exclude-domain" value="<?php _e('Delete'); ?>"></li>
								<?php endif;
							endforeach; ?>
							</ul>
							<input type="button" id="add_exclude_domain" class="button button-primary" value="<?php _e('Add a Domain/Keyword', 'qqworld_auto_save_images');?>" />
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
		<?php submit_button(); ?>
	</form>
<?php
	}

	function register_settings() {
		register_setting('qqworld_auto_save_images_settings', 'qqworld_auto_save_imagess_type');
		register_setting('qqworld_auto_save_images_settings', 'using_action');
		register_setting('qqworld_auto_save_images_settings', 'qqworld_auto_save_imagess_set_featured_image');
		register_setting('qqworld_auto_save_images_settings', 'qqworld-auto-save-images-exclude-domain');
	}

	/**
	* gets the current post type in the WordPress Admin
	*/
	public function get_current_post_type() {
		global $post, $typenow, $current_screen;

		if (isset($_GET['post']) && $_GET['post']) {
			$post_type = get_post_type($_GET['post']);
			return $post_type;
		}

		//we have a post so we can just get the post type from that
		if ( $post && $post->post_type )
			return $post->post_type;

		//check the global $typenow - set in admin.php
		elseif( $typenow )
			return $typenow;

		//check the global $current_screen object - set in sceen.php
		elseif( $current_screen && $current_screen->post_type )
			return $current_screen->post_type;

		//lastly check the post_type querystring
		elseif( isset( $_REQUEST['post_type'] ) )
			return sanitize_key( $_REQUEST['post_type'] );

		//we do not know the post type!
		return null;
	}

	function add_actions() {
		$post_type = $this->get_current_post_type();
		if ($post_type) {
			add_action($this->using_action.'_'.$post_type, array($this, 'fetch_images') );
		}
		add_action('xmlrpc_publish_post', array($this, 'fetch_images') );
	}

	function remove_actions() {
		$post_type = $this->get_current_post_type();
		if ($post_type) {
			remove_action($this->using_action.'_'.$post_type, array($this, 'fetch_images') );
		}
		remove_action('xmlrpc_publish_post', array($this, 'fetch_images') );
	}

	function utf8_urldecode($str) {
		$str = preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\\\1;",urldecode($str));
		return html_entity_decode($str, null, 'UTF-8');
	}

	function save_remote_images() { // for manual mode
		set_time_limit(0);
		//Check to make sure function is not executed more than once on save
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
		return;

		$post_id = $_POST['post_id'];

		if ( !current_user_can('edit_post', $post_id) ) 
		return;

		$content = $this->utf8_urldecode($this->utf8_urldecode($_POST['content']));

		$preg=preg_match_all($this->preg,stripslashes($content),$matches);
		if($preg){
			foreach($matches[1] as $image_url){
				if(empty($image_url)) continue;
				// exclude domain
				$allow=true;
				if (!empty($this->exclude_domain)) foreach ($this->exclude_domain as $domain) {
					$pos=strpos($image_url, $domain);
					if($pos) $allow=false;
				}
				if ($allow) {
				$pos=strpos($image_url,get_bloginfo('url'));
					if($pos===false){
						if ($res=$this->save_images($image_url,$post_id)) {
							$replace=$res['url'];
							$content=str_replace($image_url,$replace,$content);
						}
					}
				}
			}
		}
		wp_update_post(array('ID' => $post_id, 'post_content' => $content));
		echo $content;
		exit;
	}

	function fetch_images($post_id) { // for automatic mode
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
				// exclude domain
				$allow=true;
				if (!empty($this->exclude_domain)) foreach ($this->exclude_domain as $domain) {
					$pos=strpos($image_url, $domain);
					if($pos) $allow=false;
				}
				if ($allow) {
					$pos=strpos($image_url,get_bloginfo('url'));
					if($pos===false){
						if ($res=$this->save_images($image_url,$post_id)) {
							$replace=$res['url'];
							$content=str_replace($image_url,$replace,$content);
						}
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