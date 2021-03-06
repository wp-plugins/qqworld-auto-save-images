<?php 
/*
Plugin Name: QQWorld Auto Save Images
Plugin URI: https://wordpress.org/plugins/qqworld-auto-save-images/
Description: Automatically keep the all remote picture to the local, and automatically set featured image.
Version: 1.7.15.1
Author: Michael Wang
Author URI: http://www.qqworld.org
Text Domain: qqworld_auto_save_images
*/
define('QQWORLD_AUTO_SAVE_IMAGES_DIR', __DIR__ . DIRECTORY_SEPARATOR);
define('QQWORLD_AUTO_SAVE_IMAGES_URL', plugin_dir_url(__FILE__));

class QQWorld_auto_save_images {
	var $mode;
	var $when;
	var $type;
	var $remote_publishing;
	var $current_post_id; // for xmlrpc
	var $change_image_name;
	var $has_remote_image;
	var $has_missing_image;
	var $count;
	var $only_save_first;
	var $minimum_picture_size;
	var $maximum_picture_size;
	var $exclude_domain;
	var $format;
	var $filename_structure;
	var $change_title_alt;
	var $save_outside_links;
	var $auto_caption;
	var $format_align_to;
	var $link_to;
	var $additional_content;

	function __construct() {
		$this->mode = get_option('qqworld_auto_save_images_mode', 'auto');
		$this->when = get_option('qqworld_auto_save_images_when', 'publish');
		$this->remote_publishing = get_option('qqworld_auto_save_images_remote_publishing', 'yes');
		$this->schedule_publish = get_option('qqworld_auto_save_images_schedule_publish', 'yes');
		$this->featured_image = get_option('qqworld_auto_save_images_set_featured_image', 'yes');
		$this->only_save_first = get_option('qqworld_auto_save_images_only_save_first', 'all');
		$this->change_image_name = get_option('qqworld_auto_save_images_auto_change_name', 'none');
		// temporary start
		$this->change_image_name = $this->change_image_name == 'yes' ? 'ascii' : $this->change_image_name;
		// temporary end
		$this->minimum_picture_size = get_option('qqworld_auto_save_images_minimum_picture_size', array('width'=>32, 'height'=>32));
		$this->maximum_picture_size = get_option('qqworld_auto_save_images_maximum_picture_size', array('width'=>1280, 'height'=>1280));
		$this->exclude_domain = get_option('qqworld-auto-save-images-exclude-domain');
		$this->format = get_option('qqworld-auto-save-images-format', array('size'=>'full', 'link-to'=>'none'));
		$this->change_title_alt = isset($this->format['title-alt']) ? $this->format['title-alt'] : 'no';
		$this->filename_structure = isset($this->format['filename-structure']) ? $this->format['filename-structure'] : '%filename%';
		$this->keep_outside_links = isset($this->format['keep-outside-links']) ? $this->format['keep-outside-links'] : 'no';
		$this->save_outside_links = isset($this->format['save-outside-links']) ? $this->format['save-outside-links'] : 'no';
		$this->additional_content = isset($this->format['additional-content']) ? $this->format['additional-content'] : array('before'=>'', 'after'=>'');
		$this->auto_caption = isset($this->format['auto-caption']) ? $this->format['auto-caption'] : 'no';
		$this->format_link_to = isset($this->format['link-to']) ? $this->format['link-to'] : 'none';
		$this->format_align_to = isset($this->format['align-to']) ? $this->format['align-to'] : 'none';

		switch ($this->mode) {
			case 'auto':
				$this->add_actions();
				break;
			case 'manual':
				add_action( 'media_buttons', array($this, 'media_buttons' ), 11 );
				add_action( 'wp_ajax_save_remote_images', array($this, 'save_remote_images') );
				add_action( 'wp_ajax_nopriv_save_remote_images', array($this, 'save_remote_images') );	
				break;
		}
		if ($this->schedule_publish == 'yes') add_action( 'publish_future_post', array($this, 'fetch_images') );
		if ($this->remote_publishing) add_action('xmlrpc_publish_post', array($this, 'fetch_images') );

		add_action( 'wp_ajax_get_scan_list', array($this, 'get_scan_list') );
		add_action( 'wp_ajax_nopriv_get_scan_list', array($this, 'get_scan_list') );
		add_action( 'wp_ajax_save_remote_images_get_categories_list', array($this, 'save_remote_images_get_categories_list') );
		add_action( 'wp_ajax_nopriv_save_remote_images_get_categories_list', array($this, 'save_remote_images_get_categories_list') );
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

		add_filter( 'post_updated_messages', array($this, 'post_updated_messages') );
		add_filter( 'qqworld-auto-save-images-custom-filename-structure', array($this, 'custom_filename_structure') );
		add_action( 'admin_notices', array($this, 'admin_notices') );
	}

	public function languages() {
		__('Michael Wang', 'qqworld_auto_save_images');
		__(' (In Development)', 'qqworld_auto_save_images');
	}

	public function admin_notices() {
		$screen = get_current_screen();
		if (strstr($screen->id, 'qqworld-auto-save-images')) {
			settings_errors();
			if (!function_exists('curl_init')) add_settings_error('qqworld-auto-save-images', esc_attr('needs_php_lib'), __("Your server PHP does not support cUrl, please remove ';' from in front of extension=php_curl.dll in the php.ini.", 'qqworld_auto_save_images'), 'error' );
			if (!function_exists('imagecreate')) add_settings_error('qqworld-auto-save-images', esc_attr('needs_php_lib'), __("Your server PHP does not support GD2, please remove ';' from in front of extension=php_gd2.dll in the <strong>php.ini</strong>.", 'qqworld_auto_save_images'), 'error' );
			if (!function_exists('file_get_contents')) add_settings_error('qqworld-auto-save-images', esc_attr('needs_php_lib'), __('Your server PHP does not support fopen, please set allow_url_fopen=1 in the php.ini.', 'qqworld_auto_save_images'), 'error' );
			settings_errors('qqworld-auto-save-images');
		}
	}

	public function custom_filename_structure($filename) {
		$blogtime = current_time( 'mysql' );
		list( $today_year, $today_month, $today_day, $hour, $minute, $second ) = preg_split( '([^0-9])', $blogtime );
		$date = $today_year . $today_month . $today_day;
		$year = $today_year;
		$month = $today_month;
		$day = $today_day;
		$time = $hour . $minute . $second;
		$timestamp = current_time('timestamp');
		$filename_structure = str_replace('%filename%', $filename, $this->filename_structure );
		$filename_structure = str_replace('%date%', $date, $filename_structure );
		$filename_structure = str_replace('%year%', $year, $filename_structure );
		$filename_structure = str_replace('%month%', $month, $filename_structure );
		$filename_structure = str_replace('%day%', $day, $filename_structure );
		$filename_structure = str_replace('%time%', $time, $filename_structure );
		$filename = str_replace('%timestamp%', $timestamp, $filename_structure );
		return $filename;
	}

	public function save_remote_images_get_categories_list() {
		if (isset($_REQUEST['posttype']) && !empty($_REQUEST['posttype'])) {
			$posttype = $_REQUEST['posttype'];
			$taxonomies = get_object_taxonomies($posttype);
			if (!empty($taxonomies)) foreach ($taxonomies as $tax) {
				$taxonomy = get_taxonomy($tax);
				echo '<div id="'.$tax.'div" post-type="'.$tax.'" class="postbox"><div class="hndle">'.$taxonomy->labels->name.'</div><div class="inside"><div id="'.$tax.'-all" class="tabs-panel"><ul>';
				wp_terms_checklist('', array(
					'taxonomy' => $tax,
					'walker' => new QQWorld_Save_Remote_Images_Walker_Category_Checklist
				));
				echo '</ul></div></div></div>';
			} else _e('No taxonomies found.', 'qqworld_auto_save_images');
		}
		exit;
	}

	public function post_updated_messages($messages) {
		global $post, $post_ID;
		$post_type = get_post_type( $post_ID );
		$messages[$post_type][21] = __('All remote images have been saved.', 'qqworld_auto_save_images') . sprintf( __(' <a href="%s">View</a>', 'qqworld_auto_save_images'), esc_url( get_permalink($post_ID) ) );
		$messages[$post_type][22] = __('Has missing images or image which could not download.', 'qqworld_auto_save_images') . sprintf( __(' <a href="%s">View</a>', 'qqworld_auto_save_images'), esc_url( get_permalink($post_ID) ) );
		return $messages;
	}

	public function redirect_post_location($location, $post_id) {
		if ($this->has_remote_image) {
			if ($this->has_missing_image) $location = add_query_arg( 'message', 22, get_edit_post_link( $post_id, 'url' ) );
			else $location = add_query_arg( 'message', 21, get_edit_post_link( $post_id, 'url' ) );
		}		
		return $location;
	}

	public function add_to_post_php() {
		global $post;
		if ( $this->mode == 'manual' && ($GLOBALS['hook_suffix'] == 'post.php' || $GLOBALS['hook_suffix'] == 'post-new.php') ) {
			wp_register_script('noty', QQWORLD_AUTO_SAVE_IMAGES_URL . 'js/jquery.noty.packaged.min.js', array('jquery') );
			wp_enqueue_script('noty');
			wp_register_script('qqworld-auto-save-images-script-post', QQWORLD_AUTO_SAVE_IMAGES_URL . 'js/manual.js', array('jquery') );
			wp_enqueue_script('qqworld-auto-save-images-script-post');
			$translation_array = array(
				'post_id' => $post->ID,
				'in_process' => __('In Process...', 'qqworld_auto_save_images'),
				'error' => __('Something error, please check.', 'qqworld_auto_save_images')
			);
			wp_localize_script('qqworld-auto-save-images-script-post', 'QASI', $translation_array, '3.0.0');
		}
	}

	public function add_to_page_qqworld_auto_save_images() {
		if ( preg_match('/qqworld-auto-save-images$/i', $GLOBALS['hook_suffix'], $matche) ) {
			wp_register_script('noty-4-save', QQWORLD_AUTO_SAVE_IMAGES_URL . 'js/jquery.noty.packaged.min.js', array('jquery') );
			wp_enqueue_script('noty-4-save');
			wp_register_style('qqworld-auto-save-images-style', QQWORLD_AUTO_SAVE_IMAGES_URL . 'css/style.css' );
			wp_enqueue_style('qqworld-auto-save-images-style');
			wp_register_style('jquery-ui-style', QQWORLD_AUTO_SAVE_IMAGES_URL . 'css/jquery-ui/jquery-ui.min.css' );
			wp_enqueue_style('jquery-ui-style');
			wp_enqueue_script('jquery-ui-tooltip');
			wp_enqueue_script('jquery-ui-draggable');
			wp_enqueue_script('jquery-effects-core');
			wp_enqueue_script('jquery-effects-shake');
			wp_register_script('qqworld-auto-save-images-script', QQWORLD_AUTO_SAVE_IMAGES_URL . 'js/admin.js', array('jquery') );
			wp_enqueue_script('qqworld-auto-save-images-script');
			wp_enqueue_media();
			$translation_array = array(
				'are_your_sure' => __('Are you sure?<br />Before you click the yes button, I recommend backup site database.', 'qqworld_auto_save_images'),
				'pls_select_post_types' => __('Please select post types.', 'qqworld_auto_save_images'),
				'maybe_problem' => __('May be a problem with some posts: ', 'qqworld_auto_save_images'),
				'no_need_enter_' => __("No need enter \"%s\".", 'qqworld_auto_save_images'),
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
				'delete' => __('Delete')
			);
			wp_localize_script('qqworld-auto-save-images-script', 'QASI', $translation_array, '3.0.0');
		}
	}

	public function get_scan_list() {
		if ( !current_user_can( 'manage_options' ) ) return;
		$args = array();

		//post types
		$post_types = isset($_REQUEST['qqworld_auto_save_images_post_types']) ? $_REQUEST['qqworld_auto_save_images_post_types'] : 'post';
		$args['post_type'] = $post_types;

		//cagegory
		if (isset($_REQUEST['terms']) && !empty($_REQUEST['terms'])) {
			$terms = $_REQUEST['terms'];
			$args['tax_query'] = array(
				'relation' => 'OR'
			);
			foreach ($terms as $taxonomy => $term_ids) {
				$args['tax_query'][] = array(
					'taxonomy' => $taxonomy,
					'terms' => $term_ids,
					'field' => 'id'
				);
			}
		}
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
		$args['post__in'] = $post__in;

		// Offset
		$offset = empty($_REQUEST['offset']) ? 0 : $_POST['offset'];
		$args['offset'] = $offset;

		// posts per page
		$posts_per_page = $_REQUEST['posts_per_page'];
		$args['posts_per_page'] = $posts_per_page;

		// order
		$args['order'] = $_REQUEST['order'];

		// status
		$args['post_status'] = $_REQUEST['post_status'];

		// orderby
		$args['orderby'] = $_REQUEST['orderby'];

		//echo '<pre>'; print_r($args); echo '</pre>';
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
			$this->has_remote_image = 0;
			$this->has_missing_image = 0;
			$post = get_post($post_id);
			$post_id = $post->ID;
			$post_type =  $post->post_type;
			$content = $post->post_content;
			$title = $post->post_title;
			$content = $this->content_save_pre($content, $post_id);
			wp_update_post(array('ID' => $post_id, 'post_content' => $content));

			$post_type_object = get_post_type_object($post_type);
			if ($this->has_remote_image) :
				$class = 'has_remote_images';
				if ($this->has_missing_image) $class .= ' has_not_exits_remote_images';
				$class = ' class="' . $class . '"';
?>
			<tr<?php echo $class; ?>>
				<td><?php echo $post_id; ?></td>
				<td><?php echo $post_type_object->labels->name; ?></td>
				<td><a href="<?php echo get_edit_post_link($post_id); ?>" target="_blank"><?php echo $title; ?> &#8667;</a></td>
				<td><?php echo $this->has_missing_image ? '<span class="red">'.__('Has missing images.', 'qqworld_auto_save_images').'</span>' : '<span class="green">'.__('All remote images have been saved.', 'qqworld_auto_save_images').'</span>'; ?></td>
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
			$this->has_remote_image = 0;
			$this->has_missing_image = 0;
			$post = get_post($post_id);
			$post_id = $post->ID;
			$post_type =  $post->post_type;
			$content = $post->post_content;
			$title = $post->post_title;

			$content = $this->content_save_pre($content, $post_id, 'scan');

			if ($this->has_remote_image) :
				$post_type_object = get_post_type_object($post_type);
				$class = $this->has_missing_image ? ' has_not_exits_remote_images' : '';
?>
			<tr class="has_remote_images<?php echo $class; ?>">
				<td><?php echo $post_id; ?></td>
				<td><?php echo $post_type_object->labels->name; ?></td>
				<td><a href="<?php echo get_edit_post_link($post_id); ?>" target="_blank"><?php echo $title; ?> &#8667;</a></td>
				<td><?php echo $this->has_missing_image ? '<span class="red">'.__('Has missing images.', 'qqworld_auto_save_images').'</span>' : __('Normal', 'qqworld_auto_save_images'); ?></a></td>
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
		#noty_center_layout_container img {
			vertical-align: middle;
		}
		</style>
		<a href="javascript:" id="save-remote-images-button" class="button save_remote_images" title="<?php _e('Save Remote Images', 'qqworld_auto_save_images'); ?>"><span class="wp-media-buttons-icon"></span><?php _e('Save Remote Images', 'qqworld_auto_save_images'); ?></a>
	<?php
	}

	public function load_language() {
		load_plugin_textdomain( 'qqworld_auto_save_images', dirname( __FILE__ ) . '/lang' . 'lang', basename( dirname( __FILE__ ) ) . '/lang' );
	}

	function registerPluginLinks($links, $file) {
		$base = plugin_basename(__FILE__);
		if ($file == $base) {
			$links[] = '<a href="' . menu_page_url( 'qqworld-auto-save-images', 0 ) . '">' . __('Settings') . '</a>';
		}
		return $links;
	}

	function admin_menu() {
		$page_name = 'qqworld-auto-save-images';
		if ( is_plugin_active( 'qqworld-collector/qqworld-collector.php' ) ) {
			$settings_page = add_submenu_page('qqworld-collector', __('Auto Save Images', 'qqworld_auto_save_images'), __('Auto Save Images', 'qqworld_auto_save_images'), 'manage_options', $page_name, array($this, 'fn'));
		} else {
			$settings_page = add_submenu_page('options-general.php', __('QQWorld Auto Save Images', 'qqworld_auto_save_images'), __('QQWorld Auto Save Images', 'qqworld_auto_save_images'), 'manage_options', $page_name, array($this, 'fn'));
		}
		add_action( "load-{$settings_page}", array($this, 'help_tab') );
	}

	public function help_tab() {
		$screen = get_current_screen();
		$screen->add_help_tab( array( 
			'id' => 'qqworld-auto-save-images-installation',
			'title' => __('Installation', 'qqworld_auto_save_images'),
			'content' => __('<ol><li>Make sure the server configuration <strong>allow_url_fopen=1</strong> in php.ini.</li><li>Warning: If your website domain has been changed, you must modify all image link to new domain from database, or else all images which not modified in post content will be save again.</li></ol>', 'qqworld_auto_save_images')
		) );
		$screen->add_help_tab( array( 
			'id' => 'qqworld-auto-save-images-notice',
			'title' => __('Notice', 'qqworld_auto_save_images'),
			'content' => __("<ul><li>This plugin has a little problem that is all the image url must be full url, it means must included \"http(s)://\", for example:<ul><li>&lt;img src=&quot;http://img.whitehouse.gov/image/2014/08/09/gogogo.jpg&quot; /&gt;</li><li>&lt;img src=&quot;http://www.bubugao.me/image/travel/beijing.png?date=20140218&quot; /&gt;</li>			<li>&lt;img src=&quot;http://r4.ykimg.com/05410408543927D66A0B4D03A98AED24&quot; /&gt;</li><li>&lt;img src=&quot;https://example.com/image?id=127457&quot; /&gt;</li></ul></li><li>The examples that not works:<ul><li>&lt;img src=&quot;/images/great.png&quot; /&gt;</li><li>&lt;img src=&quot;./photo-lab/2014-08-09.jpg&quot; /&gt;</li><li>&lt;img src=&quot;img/background/black.gif&quot; /&gt;</li></ul></li></ul>I'v tried to figure this out, but i couldn't get the host name to make image src full.<br />So if you encounter these codes, plaese manually fix the images src to full url.", 'qqworld_auto_save_images')
		) );
		$screen->add_help_tab( array( 
			'id' => 'qqworld-auto-save-images-about',
			'title' => __('About'),
			'content' => __("<p>Hi everyone, My name is Michael Wang from china.</p><p>I made this plugin just for play in the first place, after 1 year, oneday someone sent an email to me for help , I was surprise and glad to realized my plugin has a fan. then more and more peoples asked me for helps, and my plugin was getting more and more powerful. Now this's my plugin. I hope you will like it, thanks.</p>", 'qqworld_auto_save_images')
		) );
	}

	function fn() {
?>
<div class="wrap">
	<h2><?php _e('QQWorld Auto Save Images', 'qqworld_auto_save_images'); ?></h2>
	<p><?php _e('Automatically keep the all remote picture to the local, and automatically set featured image.', 'qqworld_auto_save_images'); ?>
	<form action="options.php" method="post" id="form">
		<?php settings_fields('qqworld_auto_save_images_settings'); ?>
		<img src="<?php echo QQWORLD_AUTO_SAVE_IMAGES_URL; ?>images/banner-772x250.png" width="772" height="250" id="banner" />
		<ul id="qqworld-auto-save-images-tabs">
			<li class="current"><?php _e('General'); ?></li>
			<li><?php _e('Filter', 'qqworld_auto_save_images'); ?></li>
			<li><?php _e('Format', 'qqworld_auto_save_images'); ?></li>
			<li><?php _e('Scan Posts', 'qqworld_auto_save_images'); ?></li>
			<li><?php _e('Pro Edition', 'qqworld_auto_save_images'); ?></li>
		</ul>
		<div class="tab-content">
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><label><?php _e('Mode', 'qqworld_auto_save_images'); ?></label></th>
						<td><fieldset>
							<legend class="screen-reader-text"><span><?php _e('Mode', 'qqworld_auto_save_images'); ?></span></legend>
								<label for="auto">
									<input name="qqworld_auto_save_images_mode" type="radio" id="auto" value="auto" <?php checked('auto', $this->mode); ?> />
									<?php _e('Automatic', 'qqworld_auto_save_images'); ?>
								</label> <span class="icon help" title="<?php _e('Automatically save all remote images to local media libary when you save or publish post.', 'qqworld_auto_save_images'); ?>"></span><br />
								<label for="manual">
									<input name="qqworld_auto_save_images_mode" type="radio" id="manual" value="manual" <?php checked('manual', $this->mode); ?> />
									<?php _e('Manual', 'qqworld_auto_save_images'); ?>
								</label> <span class="icon help" title="<?php _e('Manually save all remote images to local media libary when you click the button on the top of editor.', 'qqworld_auto_save_images'); ?>"></span>
						</fieldset></td>
					</tr>
					
					<tr id="second_level" valign="top"<?php if ($this->mode != 'auto') echo ' style="display: none;"'; ?>>
						<th scope="row"><label><?php _e('When', 'qqworld_auto_save_images'); ?></label></th>
						<td><fieldset>
							<legend class="screen-reader-text"><span><?php _e('When', 'qqworld_auto_save_images'); ?></span></legend>
								<label for="save">
									<input name="qqworld_auto_save_images_when" type="radio" id="save" value="save" <?php checked('save', $this->when); ?> />
									<?php _e('Save post (Publish, save draft or pedding review).', 'qqworld_auto_save_images'); ?>
								</label><br />
								<label for="publish">
									<input name="qqworld_auto_save_images_when" type="radio" id="publish" value="publish" <?php checked('publish', $this->when); ?> />
									<?php _e('Publish post only.', 'qqworld_auto_save_images'); ?>
								</label>
						</fieldset></td>
					</tr>

					<tr valign="top">
						<th scope="row"><label><?php _e('Schedule Publish', 'qqworld_auto_save_images'); ?></label> <span class="icon help" title="<?php _e("Save remote images via Schedule Publish.", 'qqworld_auto_save_images'); ?>"></span></th>
						<td><fieldset>
							<legend class="screen-reader-text"><span><?php _e('Schedule Publish', 'qqworld_auto_save_images'); ?></span></legend>
								<label for="qqworld_auto_save_images_schedule_publish">
									<input name="qqworld_auto_save_images_schedule_publish" type="checkbox" id="qqworld_auto_save_images_schedule_publish" value="yes" <?php checked('yes', $this->schedule_publish); ?> />
								</label>
						</fieldset></td>
					</tr>

					<tr valign="top">
						<th scope="row"><label><?php _e('Remote Publishing', 'qqworld_auto_save_images'); ?></label> <span class="icon help" title="<?php _e("Save remote images via remote publishing from IFTTT or other way using XMLRPC. Only supports publish post.", 'qqworld_auto_save_images'); ?>"></span></th>
						<td><fieldset>
							<legend class="screen-reader-text"><span><?php _e('Remote Publishing', 'qqworld_auto_save_images'); ?></span></legend>
								<label for="qqworld_auto_save_images_remote_publishing">
									<input name="qqworld_auto_save_images_remote_publishing" type="checkbox" id="qqworld_auto_save_images_remote_publishing" value="yes" <?php checked('yes', $this->remote_publishing); ?> />
								</label>
						</fieldset></td>
					</tr>

					<tr valign="top">
						<th scope="row"><label><?php _e('Set Featured Image', 'qqworld_auto_save_images'); ?></label> <span class="icon help" title="<?php _e("Set first one of the remote images as featured image.", 'qqworld_auto_save_images'); ?>"></span></th>
						<td><fieldset>
							<legend class="screen-reader-text"><span><?php _e('Set Featured Image', 'qqworld_auto_save_images'); ?></span></legend>
								<label for="qqworld_auto_save_images_set_featured_image_yes">
									<input name="qqworld_auto_save_images_set_featured_image" type="checkbox" id="qqworld_auto_save_images_set_featured_image_yes" value="yes" <?php checked('yes', $this->featured_image); ?> />
								</label>
						</fieldset></td>
					</tr>
				</tbody>
			</table>
			<?php submit_button(); ?>
		</div>
		<div class="tab-content" style="display: none;">
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><label for="only_save_first"><?php _e('Grabbing from Each Posts', 'qqworld_auto_save_images'); ?></label></th>
						<td><fieldset>
							<legend class="screen-reader-text"><span><?php _e('Grabbing from Each Posts', 'qqworld_auto_save_images'); ?></span></legend>
								<select id="only_save_first" name="qqworld_auto_save_images_only_save_first">
									<option value="all" <?php selected('all', $this->only_save_first); ?>><?php _e('All Images', 'qqworld_auto_save_images'); ?></option>
									<?php
									for ($i=1; $i<=30; $i++) {
										$selected = selected($i, $this->only_save_first, false);
										echo '<option value="' . $i . '" '.$selected.'>' . sprintf(_n('First %d image only', 'First %d images only', $i, 'qqworld_auto_save_images'), number_format_i18n($i)) . '</option>';
									}
									?>
								</select>
						</fieldset></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label><?php _e('Minimum Picture Size', 'qqworld_auto_save_images'); ?></label> <span class="icon help" title="<?php _e("Ignore smaller than this size picture.", 'qqworld_auto_save_images'); ?>"></span></th>
						<td><fieldset>
							<legend class="screen-reader-text"><span><?php _e('Minimum Picture Size', 'qqworld_auto_save_images'); ?></span></legend>
								<label for="qqworld_auto_save_images_minimum_picture_size_width">
									<?php _e('Width:', 'qqworld_auto_save_images'); ?> <input name="qqworld_auto_save_images_minimum_picture_size[width]" class="small-text" type="text" id="qqworld_auto_save_images_minimum_picture_size_width" value="<?php echo $this->minimum_picture_size['width']; ?>" /> <?php _e('(px)', 'qqworld_auto_save_images'); ?>
								</label><br />
								<label for="qqworld_auto_save_images_minimum_picture_size_height">
									<?php _e('Height:', 'qqworld_auto_save_images'); ?> <input name="qqworld_auto_save_images_minimum_picture_size[height]" class="small-text" type="text" id="qqworld_auto_save_images_minimum_picture_size_height" value="<?php echo $this->minimum_picture_size['height']; ?>" readonly /> <?php _e('(px)', 'qqworld_auto_save_images'); ?>
								</label>
						</fieldset></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label><?php _e('Maximum Picture Size', 'qqworld_auto_save_images'); ?></label> <span class="icon help" title="<?php _e("Automatic reduction is greater than the size of the picture. if you want image width less than 800px with any size height, please set width 800 and leave height blank.", 'qqworld_auto_save_images'); ?>"></span></th>
						<td><fieldset>
							<legend class="screen-reader-text"><span><?php _e('Maximum Picture Size', 'qqworld_auto_save_images'); ?></span></legend>
								<label for="qqworld_auto_save_images_maximum_picture_size_width">
									<?php _e('Width:', 'qqworld_auto_save_images'); ?> <input name="qqworld_auto_save_images_maximum_picture_size[width]" class="small-text" type="text" id="qqworld_auto_save_images_maximum_picture_size_width" value="<?php echo $this->maximum_picture_size['width']; ?>" /> <?php _e('(px)', 'qqworld_auto_save_images'); ?>
								</label><br />
								<label for="qqworld_auto_save_images_maximum_picture_size_height">
									<?php _e('Height:', 'qqworld_auto_save_images'); ?> <input name="qqworld_auto_save_images_maximum_picture_size[height]" class="small-text" type="text" id="qqworld_auto_save_images_maximum_picture_size_height" value="<?php echo $this->maximum_picture_size['height']; ?>" /> <?php _e('(px)', 'qqworld_auto_save_images'); ?>
								</label>
						</fieldset></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label><?php _e('Exclude Domain/Keyword', 'qqworld_auto_save_images'); ?></label> <span class="icon help" title="<?php _e("Images will not be saved, if that url contains Exclude-Domain/Keyword.", 'qqworld_auto_save_images'); ?>"></span></th>
						<td><fieldset>
							<legend class="screen-reader-text"><span><?php _e('Exclude Domain/Keyword', 'qqworld_auto_save_images'); ?></span></legend>
								<ul id="exclude_domain_list">
								<?php
								if (!empty($this->exclude_domain)) foreach ($this->exclude_domain as $domain) :
									if (!empty($domain)) :
								?>
								<li>http(s):// <input type="text" name="qqworld-auto-save-images-exclude-domain[]" class="regular-text" value="<?php echo $domain; ?>" /><input type="button" class="button delete-exclude-domain" value="<?php _e('Delete'); ?>"></li>
									<?php endif;
								endforeach; ?>
								</ul>
								<input type="button" id="add_exclude_domain" class="button" value="<?php _e('Add a Domain/Keyword', 'qqworld_auto_save_images');?>" />
						</fieldset></td>
					</tr>
				</tbody>
			</table>
			<?php submit_button(); ?>
		</div>
		<div class="tab-content" style="display: none;">
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><label for="auto_change_name"><?php _e('Change Image Filename', 'qqworld_auto_save_images'); ?></label></th>
						<td>
							<fieldset>
							<legend class="screen-reader-text"><span><?php _e('Change Image Filename', 'qqworld_auto_save_images'); ?></span></legend>
								<select id="auto_change_name" name="qqworld_auto_save_images_auto_change_name">
									<option value="none" <?php selected('none', $this->change_image_name); ?>>1. <?php _e('No'); ?></option>
									<option value="ascii" <?php selected('ascii', $this->change_image_name); ?>>2. <?php _e('Only change remote images filename that have Non-ASCii characters (for Windows Server)', 'qqworld_auto_save_images'); ?></option>
									<option value="all" <?php selected('all', $this->change_image_name); ?>>3. <?php _e('Change all remote images Filename and Alt as post name (for Linux Server)', 'qqworld_auto_save_images'); ?></option>
								</select>
						</fieldset></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="filename-structure"><?php _e('Custom Filename Structure', 'qqworld_auto_save_images'); ?></label></th>
						<td><fieldset>
							<legend class="screen-reader-text"><span><?php _e('Custom Filename Structure', 'qqworld_auto_save_images'); ?></span></legend>
								<label for="filename-structure">
									<input name="qqworld-auto-save-images-format[filename-structure]" type="text" id="filename-structure" class="regular-text" value="<?php echo $this->filename_structure; ?>" />
								</label>
								<p class="description">
									<strong>%filename%</strong> : <?php _e('Original filename or automatic changed filename.', 'qqworld_auto_save_images'); ?><br />
									<strong>%date%</strong> : <?php _e('Full date, e.g. 20150209.', 'qqworld_auto_save_images'); ?><br />
									<strong>%year%</strong> - <?php _e('YYYY, e.g. 2015.', 'qqworld_auto_save_images'); ?><br />
									<strong>%month%</strong> - <?php _e('MM, e.g. 02.', 'qqworld_auto_save_images'); ?><br />
									<strong>%day%</strong> -  <?php _e('DD, e.g. 15.', 'qqworld_auto_save_images'); ?><br />
									<strong>%time%</strong> - <?php _e('HHMMSS, e.g. 182547.', 'qqworld_auto_save_images'); ?><br />
									<strong>%timestamp%</strong> - <?php printf(__('Unix timestamp, e.g. %s.', 'qqworld_auto_save_images'), time()); ?>
								</p>
						</fieldset></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label><?php _e('Change Title & Alt', 'qqworld_auto_save_images'); ?></label> <span class="icon help" title="<?php _e('Automatically add title & alt of image as post title.', 'qqworld_auto_save_images'); ?>"></span></th>
						<td><fieldset>
							<legend class="screen-reader-text"><span><?php _e('Change Title & Alt', 'qqworld_auto_save_images'); ?></span></legend>
								<label for="qqworld_auto_save_images_format_title_alt">
									<input name="qqworld-auto-save-images-format[title-alt]" type="checkbox" id="qqworld_auto_save_images_format_title_alt" value="yes" <?php checked('yes', $this->change_title_alt); ?> />
								</label>
						</fieldset></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label><?php _e('Keep Outside Links', 'qqworld_auto_save_images'); ?></label> <span class="icon help" title="<?php _e("Keep the outside links of remote images if exist.", 'qqworld_auto_save_images'); ?>"></span></th>
						<td><fieldset>
							<legend class="screen-reader-text"><span><?php _e('Keep Outside Links', 'qqworld_auto_save_images'); ?></span></legend>
								<label for="qqworld_auto_save_images_format_keep_outside_links">
									<input name="qqworld-auto-save-images-format[keep-outside-links]" type="checkbox" id="qqworld_auto_save_images_format_keep_outside_links" value="yes" <?php checked('yes', $this->keep_outside_links); ?> />
								</label>
						</fieldset></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label><?php _e('Save Outside Links', 'qqworld_auto_save_images'); ?></label> <span class="icon help" title="<?php _e("Save the outside links to description of attachments.", 'qqworld_auto_save_images'); ?>"></span></th>
						<td><fieldset>
							<legend class="screen-reader-text"><span><?php _e('Save Outside Links', 'qqworld_auto_save_images'); ?></span></legend>
								<label for="qqworld_auto_save_images_format_save_outside_links">
									<input name="qqworld-auto-save-images-format[save-outside-links]" type="checkbox" id="qqworld_auto_save_images_format_save_outside_links" value="yes" <?php checked('yes', $this->save_outside_links); ?> />
								</label>
								<p><?php _e('To custom the content, add codes into <strong>functions.php</strong> like this below:', 'qqworld_auto_save_images'); ?></p>
								<pre>add_filter('qqworld-auto-save-images-save-outsite-link', 'save_outside_link', 10, 2);
function save_outside_link($content, $link) {
	$content = '&lta href="'.$link.'" target="_blank" rel="nofollow"&gt;Original Link&lt;/a&gt;';
	return $content;
}</pre>
						</fieldset></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label><?php _e('Image Size', 'qqworld_auto_save_images'); ?></label> <span class="icon help" title="<?php _e("Replace images you want size to display.", 'qqworld_auto_save_images'); ?>"></span></th>
						<td><fieldset>
							<legend class="screen-reader-text"><span><?php _e('Image Size', 'qqworld_auto_save_images'); ?></span></legend>
								<label>
									<select name="qqworld-auto-save-images-format[size]">
									<?php
									$sizes = apply_filters( 'image_size_names_choose', array(
										'thumbnail' => __('Thumbnail'),
										'medium'    => __('Medium'),
										'large'     => __('Large'),
										'full'      => __('Full Size')
									) );
									foreach ($sizes as $value => $title) echo '<option value="'.$value.'"'.selected($value, $this->format['size'], false).'>'.$title.'</option>';
									?>
									</select>
								</label>
						</fieldset></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label><?php _e('Link To', 'qqworld_auto_save_images'); ?></label> <span class="icon help" title="<?php _e("If you checked Keep-Outside-Links, this option will not works.", 'qqworld_auto_save_images'); ?>"></span></th>
						<td><fieldset>
							<legend class="screen-reader-text"><span><?php _e('Link To', 'qqworld_auto_save_images'); ?></span></legend>
								<label>
									<select name="qqworld-auto-save-images-format[link-to]">
									<?php
									$linkTo = array(
										'file' => __('Media File'), 
										'post' => __('Attachment Page'),
										'none' => __('None')
									);
									foreach ($linkTo as $value => $title) echo '<option value="'.$value.'"'.selected($value, $this->format_link_to, false).'>'.$title.'</option>';
									?>
									</select>
								</label>
						</fieldset></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="format-align-to"><?php _e('Align To', 'qqworld_auto_save_images'); ?></label></th>
						<td><fieldset>
							<legend class="screen-reader-text"><span><?php _e('Align To', 'qqworld_auto_save_images'); ?></span></legend>
								<label>
									<select name="qqworld-auto-save-images-format[align-to]" id="format-align-to">
									<?php
									$linkTo = array(
										'left' => __('Left'),
										'center' => __('Center'),
										'right' => __('Right'),
										'none' => __('None')
									);
									foreach ($linkTo as $value => $title) echo '<option value="'.$value.'"'.selected($value, $this->format_align_to, false).'>'.$title.'</option>';
									?>
									</select>
								</label>
						</fieldset></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="format_caption"><?php _e('Auto Caption', 'qqworld_auto_save_images'); ?></label> <span class="icon help" title="<?php _e("Automatically add caption shortcode.", 'qqworld_auto_save_images'); ?>"></span></th>
						<td><fieldset>
							<legend class="screen-reader-text"><span><?php _e('Auto Caption', 'qqworld_auto_save_images'); ?></span></legend>
								<label for="format_caption">
									<input name="qqworld-auto-save-images-format[auto-caption]" type="checkbox" id="format_caption" value="yes" <?php checked('yes', $this->auto_caption); ?> />
								</label>
						</fieldset></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label><?php _e('Additional Content', 'qqworld_auto_save_images'); ?></label> <span class="icon help" title="<?php _e("This content will be displayed after the each remote images code. you can use [Attachment ID] indicate current attachment ID.", 'qqworld_auto_save_images'); ?>"></span></th>
						<td><fieldset>
							<legend class="screen-reader-text"><span><?php _e('Additional Content', 'qqworld_auto_save_images'); ?></span></legend>
								<label for="qqworld_auto_save_images_additional_content_after">
									<textarea name="qqworld-auto-save-images-format[additional-content][after]" rows="3" cols="80" id="qqworld_auto_save_images_additional_content_after"><?php echo $this->additional_content['after']; ?></textarea>
									<p class="discription"><?php _e("For example: [Gbuy id='[Attachment ID]']", 'qqworld_auto_save_images'); ?></p>
								</label>
						</fieldset></td>
					</tr>
				</tbody>
			</table>
			<?php do_action('qqworld-auto-save-images-general-options-form'); ?>
			<?php submit_button(); ?>
		</div>
	</form>
	<form action="" method="post" id="scan">
		<div class="tab-content hidden">
			<div id="scan-result"></div>
			<div id="scan-post-block">
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><label><?php _e('Select post types', 'qqworld_auto_save_images'); ?></label> <span class="icon help" title="<?php _e("If you have too many posts to be scan, sometimes in process looks like stopping, but it may be fake. please be patient.", 'qqworld_auto_save_images') ?>"></span></th>
							<td>
								<?php $post_types = get_post_types('', 'objects'); ?>
								<ul id="post_types_list">
								<?php foreach ($post_types as $name => $post_type) :
									if ( !in_array($name, array('attachment', 'revision', 'nav_menu_item') )) : ?>
									<li><label><input name="qqworld_auto_save_images_post_types[]" type="checkbox" value="<?php echo $name; ?>" /> <?php echo $post_type->labels->name; ?> (<?php $count = wp_count_posts($name); echo $count->publish; ?>)</label></li>
								<?php endif;
								endforeach;
								?></ul>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row"><label><?php _e('Categories'); ?></label> <span class="icon help" title="<?php _e("Default empty to scan all categories.", 'qqworld_auto_save_images') ?>"></span></th>
							<td id="categories_block"><?php _e('Please select post types.', 'qqworld_auto_save_images'); ?></td>
						</tr>

						<tr valign="top">
							<th scope="row"><label><?php _e('Scope of Post ID', 'qqworld_auto_save_images'); ?></label> <span class="icon help" title="<?php _e("Default empty for scan all posts ID. If you want to scan posts ID from 50 to 100. please type '50' and '100' or '100' and '50', The order in which two numbers can be reversed. If you only type one number, system would only scan that ID.", 'qqworld_auto_save_images'); ?>"></span></th>
							<td><?php printf(__('From %1$s to %2$s', 'qqworld_auto_save_images'), '<input type="number" class="small-text" name="id_from" />', '<input type="number" class="small-text" name="id_to" />'); ?></td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label><?php _e('Offset', 'qqworld_auto_save_images'); ?></label> <span class="icon help" title="<?php _e("Default scan all posts. If you want to scan 50-150 posts, please type '50' in the textfield and select '100'.", 'qqworld_auto_save_images'); ?>"></span></th>
							<td>
								<?php printf(__('Start from %s to Scan', 'qqworld_auto_save_images'), '<input type="number" class="small-text" name="offset" value="0" disabled />'); ?>
								<select name="posts_per_page">
									<option value="-1"><?php _e('All'); ?></option>
									<?php for ($i=1; $i<=10; $i++) : ?>
									<option value="<?php echo $i*100; ?>"><?php echo $i*100; ?></option>
									<?php endfor; ?>
								</select> <?php _e('Posts'); ?>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row"><label><?php _e('Status'); ?></label></th>
							<td>
								<select name="post_status">
								<?php
								global $wp_post_statuses;
								echo '<option value="any" /> '.__('Any', 'qqworld_auto_save_images').'</option>';
								foreach ($wp_post_statuses as $slug => $status) {
									if (!in_array($slug, array('auto-draft', 'inherit', 'trash'))) echo '<option value="'.$slug.'" '.selected('publish', $slug, false).'> '.$status->label.'</option>';
								}
								?>
								</select>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row"><label><?php _e('Order By', 'qqworld_auto_save_images'); ?></label></th>
							<td>
								<select name="orderby">
									<?php
									$orderby = array(
										'ID' => __('ID'),
										'author' => __('Author'),
										'title' => __('Title'),
										'date' => __('Date'),
										'modified' => __('Last Modified'),
										'comment_count' => __('Comment Count', 'qqworld_auto_save_images')
									);
									foreach ($orderby as $key => $name) : ?>
									<option value="<?php echo $key; ?>"<?php selected('date', $key); ?>><?php echo $name; ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row"><label for="order"><?php _e('Order'); ?></label></th>
							<td id="categories_block"><fieldset>
								<select name="order" id="order">
									<option value="DESC">DESC</option>
									<option value="ASC">ASC</option>
								</select>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label><?php _e('Speed', 'qqworld_auto_save_images'); ?></label> <span class="icon help" title="<?php _e('If the server is too much stress may be appropriately reduced speed.', 'qqworld_auto_save_images'); ?>"></span></th>
							<td>
								<select name="speed">
									<?php for ($i=1; $i<10; $i++) : ?>
									<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
									<?php endfor; ?>
									<option value="10" selected>10</option>
								</select>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<input name="scan_old_posts" type="button" class="button-primary" id="scan_old_posts" value="<?php _e('Automatic', 'qqworld_auto_save_images'); ?> &#8667;" /> <span class="icon help" title="<?php _e('Scan posts and keep remote images in all posts to local media library. Maybe take a long time.', 'qqworld_auto_save_images'); ?>"></span>
					<input name="list_all_posts" type="button" class="button-primary" id="list_all_posts" value="<?php _e('Manual', 'qqworld_auto_save_images'); ?> &#9776;" /> <span class="icon help" title="<?php _e("The list displayed will show you which posts including remote images, then you can keep them to local manually via click \"Fetch\" button.", 'qqworld_auto_save_images'); ?>"></span>
				</p>
			</div>
		</div>
	</form>
	<form action="" method="post" id="scan-cron">
		<div class="tab-content hidden">
			<div id="cron-list-container">
				<table class="form-table">
					<tbody>
						<tr valign="top" id="manual-demo">
							<th scope="row"><label><?php _e('Demo of Manual Mode', 'qqworld_auto_save_images'); ?></label></th>
							<td><fieldset>
								<legend class="screen-reader-text"><span><?php _e('Set Featured Image', 'qqworld_auto_save_images'); ?></span></legend>
									<?php _e("<iframe width=\"560\" height=\"315\" src=\"https://www.youtube.com/embed/-KImHNbPA-o\" frameborder=\"0\" allowfullscreen></iframe>", 'qqworld_auto_save_images'); ?>	
							</fieldset></td>
						</tr>
						<tr>
							<th><?php _e('Comparison', 'qqworld_auto_save_images'); ?></th>
							<td>

								<table id="comparison-table">
									<thead>
										<tr>
											<th><?php _e('Items', 'qqworld_auto_save_images'); ?></th>
											<th class="comparison-cell"><?php _e('Pro Edition', 'qqworld_auto_save_images'); ?></th>
											<th class="comparison-cell"><?php _e('Free Edition', 'qqworld_auto_save_images'); ?></th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td><?php _e('New manual mode with multi-thread (Faster than automatic mode and modify Title & Alt in realtime)', 'qqworld_auto_save_images'); ?></td>
											<td>&radic;</td>
											<td>&#9587;</td>
										</tr>
										<tr>
										</tr>
										<tr>
											<td><?php _e('Download Images from WeChat', 'qqworld_auto_save_images'); ?></td>
											<td>&radic;</td>
											<td>&#9587;</td>
										</tr>
										<tr>
											<td><?php _e('Cron Scan Posts', 'qqworld_auto_save_images'); ?></td>
											<td>&radic;</td>
											<td>&#9587;</td>
										</tr>
										<tr>
											<td><?php _e('Watermark', 'qqworld_auto_save_images'); ?></td>
											<td>&radic;</td>
											<td>&#9587;</td>
										</tr>
										<tr>
											<td><?php _e('Images Compression (JPG Only)', 'qqworld_auto_save_images'); ?></td>
											<td>&radic;</td>
											<td>&#9587;</td>
										</tr>
										<tr>
											<td><?php _e('Use Proxy', 'qqworld_auto_save_images'); ?></td>
											<td>&radic;</td>
											<td>&#9587;</td>
										</tr>
										<tr>
											<td><?php _e('Smart Grabbing, Automatically detect 2 images url from href/src of remote image with outside link, and grab the bigger one.', 'qqworld_auto_save_images'); ?></td>
											<td>&radic;</td>
											<td>&#9587;</td>
										</tr>
										<tr>
											<td><?php _e('Use Cookie-Free Domain &amp; CDN Servive (AliYun OSS, UpYun, QiNiu)', 'qqworld_auto_save_images'); ?></td>
											<td>&radic;</td>
											<td>&#9587;</td>
										</tr>
										<tr>
											<td><?php _e('Collect webpage manually: Easy reprint articles, and get all the images to choose from web pages.', 'qqworld_auto_save_images'); ?></td>
											<td>&radic;</td>
											<td>&#9587;</td>
										</tr>
									</tbody>
								</table>

							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label><?php _e('Buy', 'qqworld_auto_save_images'); ?></label></th>
							<td><a href="http://www.qqworld.org/products/qqworld-collector" target="_blank" class="button button-primary"><?php _e('QQWorld Collector', 'qqworld_auto_save_images'); ?></a>
							<p class="description"><?php _e('(No need understand chinese, there is a language switcher on left sidebar.)', 'qqworld_auto_save_images')?></td></p>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</form>
<?php
	}

	function register_settings() {
		$settings_fields = array(
			'settings' => array(
				'qqworld_auto_save_images_mode',
				'qqworld_auto_save_images_when',
				'qqworld_auto_save_images_remote_publishing',
				'qqworld_auto_save_images_schedule_publish',
				'qqworld_auto_save_images_set_featured_image',
				'qqworld_auto_save_images_auto_change_name',
				'qqworld_auto_save_images_only_save_first',
				'qqworld_auto_save_images_maximum_picture_size',
				'qqworld_auto_save_images_minimum_picture_size',
				'qqworld-auto-save-images-exclude-domain',
				'qqworld-auto-save-images-format'
			)
		);
		foreach ( $settings_fields as $field => $settings )
			foreach ( $settings as $setting )
			 register_setting("qqworld_auto_save_images_{$field}", $setting);
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
		if ($post_type) add_action($this->when.'_'.$post_type, array($this, 'fetch_images') );
	}

	function remove_actions() {
		$post_type = $this->get_current_post_type();
		if ($post_type) remove_action($this->when.'_'.$post_type, array($this, 'fetch_images') );
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

		$this->current_post_id = $post_id;

		$this->has_remote_image = 0;
		$this->has_missing_image = 0;

		$content = $this->content_save_pre($this->utf8_urldecode($this->utf8_urldecode($_POST['content'])), $post_id);
		wp_update_post(array('ID' => $post_id, 'post_content' => $content));

		$result = array();
		if ($this->has_remote_image) {
			if ($this->has_missing_image) {
				$result['type'] = 3;
				$result['msg'] = __('Has Missing/Undownloadable images.', 'qqworld_auto_save_images');
			} else {
				$result['type'] = 2;
				$result['msg'] = __('All remote images have been saved.', 'qqworld_auto_save_images');
			}
		} else {
			$result['type'] = 1;
			$result['msg'] = __('No remote images found.', 'qqworld_auto_save_images');
		}
		$result['content'] = stripslashes($content);
		echo json_encode($result);
		exit;
	}

	function fetch_images($post_id) { // for automatic mode
		set_time_limit(0);

		//Check to make sure function is not executed more than once on save
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
			return;

		// AJAX? Not used here
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) 
			return;

		$this->current_post_id = $post_id;
		
		$this->has_remote_image = 0;
		$this->has_missing_image = 0;
		
		add_filter( 'redirect_post_location', array($this, 'redirect_post_location'), 10, 2);

		if ($this->mode=='auto') $this->remove_actions();
		if ($this->remote_publishing) remove_action('xmlrpc_publish_post', array($this, 'fetch_images') );
		if ($this->schedule_publish == 'yes') remove_action('publish_future_post', array($this, 'fetch_images') );

		$post = get_post($post_id);
		$content = $this->content_save_pre($post->post_content, $post_id);
	    //Replace the image in the post
		remove_action( 'post_updated', 'wp_save_post_revision' );
	    wp_update_post(array('ID' => $post_id, 'post_content' => $content));

		add_action( 'post_updated', 'wp_save_post_revision', 10, 1 );

		if ($this->mode=='auto') $this->add_actions();
		if ($this->remote_publishing) add_action('xmlrpc_publish_post', array($this, 'fetch_images') );
		if ($this->schedule_publish == 'yes') add_action('publish_future_post', array($this, 'fetch_images') );
	}

	public function getimagesize($image_url) {
		$params = @getimagesize($image_url);
		$width = $params[0];
		$height = $params[1];
		$this->type = $params['mime'];
		if ($width==null) {
			$file = @file_get_contents( $image_url );
			if ($file) {
				$encoding = $this->fsockopen_image_header($image_url, 'Content-Encoding');
				if ($encoding == 'gzip' && function_exists('gzdecode')) $file = gzdecode($file);
				if (function_exists('getimagesizefromstring')) {
				$params = getimagesizefromstring($file);
					$width = $params[0];
					$height = $params[1];
					$this->type = $params['mime'];
				}
			}
		} else {
			$width = $params[0];
			$height = $params[1];
			$this->type = $params['mime'];
		}
		return array($width, $height, $this->type);
	}

	public function convert_space_from_content($content) {
		$preg = preg_match_all('/(src|href)=\"((?!\").*?)\"/i', stripslashes($content), $matches);
		if ($preg) {
			foreach ($matches[2] as $match) {
				if (preg_match("/ /", $match)) {
					$new_str = str_replace(' ', '%20', $match);
					$content = str_replace($match, $new_str, $content);
				}
			}
		}
		return $content;
	}

	public function content_save_pre($content, $post_id=null, $action='save') {
		$post = get_post($post_id);
		if ($post->post_type == 'revision') return;

		$this->count = 1;
		$content = $this->convert_space_from_content($content);
		$this->change_attachment_url_to_permalink($content);
		$remote_images = array();
		$preg = preg_match_all('/<img.*?src=\"((?!\").*?)\"/i', stripslashes($content), $matches);
		if ($preg) $remote_images = $matches[1];
		$preg = preg_match_all('/<img.*?src=\'((?!\').*?)\'/i', stripslashes($content), $matches);
		if ($preg) $remote_images = array_merge($remote_images, $matches[1]);
		if(!empty($remote_images)){
			foreach($remote_images as $image_url) {
				if ($this->only_save_first != 'all' && $this->count++ > $this->only_save_first) continue;
				if (empty($image_url)) continue;
				// exclude domain
				$allow=true;
				if (!empty($this->exclude_domain)) foreach ($this->exclude_domain as $domain) {
					if ($allow==true) {
						$pos=strpos($image_url, $domain);
						if($pos) $allow=false;
					}
				}
				// check pictrue size
				list($width, $height, $type) = $this->getimagesize($image_url);
				
				if ($width != NULL && ($width<$this->minimum_picture_size['width'] || $height<$this->minimum_picture_size['height'])) $allow = false;
				// check if remote image
				if ($allow) {
					$pos = strpos($image_url, get_bloginfo('url'));
					if($pos===false){
						$this->has_remote_image = 1;
						if ($action=="save" && $res=$this->save_images($image_url,$post_id)) {
							$content = $this->format($image_url, $res, $content);
						}
					}
				}
			}
		}
		return apply_filters( 'qqworld-auto-save-images-content-save-pre', $content, $post_id );
	}

	public function change_attachment_url_to_permalink(&$content) {
		$pattern = '/<a\s[^>]*href=\"'.$this->encode_pattern(home_url('?attachment_id=')).'(.*?)\".*?>/i';
		if ( preg_match_all($pattern, $content, $matches) ) {
			foreach ($matches[1] as $attachment_id) {
				$attachment = get_post($attachment_id);
				$post = get_post($attachment->post_parent);
				if ($post->post_status != 'draft' && $post->post_status != 'pending' && $post->post_status != 'future') {
					$url = get_permalink($attachment_id);
					$content = preg_replace('/'.$this->encode_pattern(home_url('?attachment_id='.$attachment_id)).'/i', $url, $content);
				}
			}
		}
	}

	public function encode_pattern($str) {
		$str = str_replace('(', '\(', $str);
		$str = str_replace(')', '\)', $str);
		$str = str_replace('{', '\{', $str);
		$str = str_replace('}', '\}', $str);
		$str = str_replace('+', '\+', $str);
		$str = str_replace('.', '\.', $str);
		$str = str_replace('?', '\?', $str);
		$str = str_replace('*', '\*', $str);
		$str = str_replace('/', '\/', $str);
		$str = str_replace('^', '\^', $str);
		$str = str_replace('$', '\$', $str);
		$str = str_replace('|', '\|', $str);
		return $str;
	}

	public function format($image_url, $res, $content) {
		$no_match = false;
		$attachment_id = $res['id'];
		$url_path = str_replace(basename($res['file']), '', $res['url']);
		$size = isset($res['sizes'][$this->format['size']]) ? $this->format['size'] : 'full';
		if ($size == 'full') {
			$src = $res['url'];
			$width = $res['width'];
			$height = $res['height'];
		} else {
			$src = $url_path . $res['sizes'][$size]['file'];
			$width = $res['sizes'][$size]['width'];
			$height = $res['sizes'][$size]['height'];
		}
		$pattern_image_url = $this->encode_pattern($image_url);
		$preg = false;
		if ($this->keep_outside_links=='no') {
			$pattern = '/<a[^<]+><img\s[^>]*'.$pattern_image_url.'.*?>?<[^>]+a>/i';
			$preg = preg_match($pattern, $content, $matches);
			if ($preg) {
				if ( $this->save_outside_links == 'yes' ) {
					if ( preg_match('/<a[^>]*href=\"(.*?)\".*?>/i', $matches[0], $match) ) {
						$link = $match[1];
						$description = '<a href="'.$link.'" target="_blank" rel="nofollow">'.__('Original Link', 'qqworld_auto_save_images').'</a>';
						$description = apply_filters('qqworld-auto-save-images-save-outsite-link', $description, $link);
						$args = array(
							'ID' => $attachment_id,
							'post_content' => $description
						);
						wp_update_post($args);
					}
				}
				$args = $this->set_img_metadata($matches[0], $attachment_id);
			}
		}
		if (!$preg) {
			$pattern = '/<img\s[^>]*'.$pattern_image_url.'.*?>/i';
			if ( preg_match($pattern, $content, $matches) ) {
				$args = $this->set_img_metadata($matches[0], $attachment_id);
			} else {
				$pattern = '/'.$pattern_image_url.'/i';
				$no_match = true;
			}
		}
		$alt = isset($args['alt']) ? ' alt="'.$args['alt'].'"' : '';
		$title = isset($args['title']) ? ' title="'.$args['title'].'"' : '';
		$align = $this->auto_caption == 'yes' ? '' : 'align'.$this->format_align_to.' ';
		$img = '<img class="'.$align.'size-'.$size.' wp-image-'.$attachment_id.'" src="'.$src.'" width="'.$width.'" height="'.$height.'"'.$alt.$title.' />';
		$link_to = $this->keep_outside_links=='no' ? $this->format['link-to'] : 'none';
		switch ($link_to) {
			case 'none':
				$replace = $img; break;
			case 'file':
				$replace = '<a href="'.$res['url'].'">'.$img.'</a>';
				break;
			case 'post':
				$replace = '<a href="'.get_permalink($attachment_id).'">'.$img.'</a>';
				break;
		}
		if ($no_match) $replace = $res['url'];
		else if ($this->auto_caption == 'yes') $replace = '[caption id="attachment_'.$attachment_id.'" align="align'.$this->format_align_to.'" width="'.$width.'"]' . $replace . ' ' . (isset($args['alt']) ? $args['alt'] : '') . '[/caption]';
		$replace .= str_replace( '[Attachment ID]', $res['id'], $this->additional_content['after'] );

		if ( $this->keep_outside_links=='yes' ) {
			$patt = '/<a[^<]+><img\s[^>]*'.$pattern_image_url.'.*?>?<[^>]+a>/i';
			if ( preg_match($patt, $content, $match) ) {
				$string = $match[0];
				$pos = strpos($string, '>');
				$string = substr_replace($string, ' rel="nofollow">', $pos, 1);
				$content = preg_replace($patt, $string, $content);
			}
		}

		$content = preg_replace($pattern, $replace, $content);
		return $content;
	}

	public function set_img_metadata($img, $attachment_id) {
		if ($this->change_title_alt == 'no') {
			$pattern = '/<img\s[^>]*alt=\"(.*?)\".*?>/i';
			if ( preg_match($pattern, $img, $matches) ) $alt = $matches[1];
			else {
				$pattern = '/<img\s[^>]*alt=\'(.*?)\'.*?>/i';
				$alt = preg_match($pattern, $img, $matches) ? $matches[1] : null;
			}
			$pattern = '/<img\s[^>]*title=\"(.*?)\".*?>/i';
			if ( preg_match($pattern, $img, $matches) ) $title = $matches[1];
			else {
				$pattern = '/<img\s[^>]*alt=\'(.*?)\'.*?>/i';
				$title = preg_match($pattern, $img, $matches) ? $matches[1] : null;
			}
		} else {
			$alt = $this->get_post_title() ? $this->get_post_title() : null;
			$title = $this->get_post_title() ? $this->get_post_title() : null;
		}
		if ($alt) update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt);
		if ($title) {
			$attachment = array(
				'ID' => $attachment_id,
				'post_title' => $title
			);
			wp_update_post($attachment);
		}
		return array(
			'alt' => $alt,
			'title' => $title
		);
	}

	public function get_post_title() {
		$post = get_post($this->current_post_id);
		return $post->post_title;
	}

	public function get_post_name() {
		return sanitize_title_with_dashes( $this->get_post_title() );
	}

	public function change_images_filename($name, $extension) {
		switch ($this->change_image_name) {
			case 'none':
				break;
			case 'ascii':
				if ( !preg_match( '/^[\x20-\x7f]*$/', $name, $match ) ) $name = md5($name);
				break;
			case 'all':
				global $post;
				$name = urldecode($this->get_post_name());
				break;
		}
		return apply_filters('qqworld-auto-save-images-custom-filename-structure', $name) . $extension;
	}

	public function get_filename_from_url($url) {
		$url = parse_url($url);
		$path = $url['path'];
		$filename = explode('/', $path);
		$filename = urldecode($filename[count($filename)-1]);
		return $filename;
	}

	public function automatic_reduction($file, $image_url) {
		$filetype = $this->getFileType($file);
		list($width, $height, $type) = $this->getimagesize($image_url);

		if ((!empty($this->maximum_picture_size['width']) || !empty($this->maximum_picture_size['height'])) && ($width > $this->maximum_picture_size['width'] || $height > $this->maximum_picture_size['height'])) {
			if ($width > $height) {
				$maximum_picture_size_width = empty($this->maximum_picture_size['width']) ? $width*$this->maximum_picture_size['height']/$height : $this->maximum_picture_size['width'];
				$new_width = $maximum_picture_size_width;
				$new_height = $height*$maximum_picture_size_width/$width;
			} else {
				$maximum_picture_size_height = empty($this->maximum_picture_size['height']) ? $height*$this->maximum_picture_size['width']/$width : $this->maximum_picture_size['height'];
				$new_width = $width*$maximum_picture_size_height/$height;
				$new_height = $maximum_picture_size_height;
			}
			$image_p = imagecreatetruecolor($new_width, $new_height);
			$image = imagecreatefromstring($file);
			imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
			ob_start();
			switch ($filetype) {
				case 'jpg':
				case 'jpeg':
					imageJpeg($image_p, null, 75);
					break;
				case 'png':
					imagePng($image_p, null);
					break;
				case 'gif':
					imageGif($image_p, null);
					break;
			}
			$file = ob_get_contents();
			ob_end_clean();
			imagedestroy($image_p);
			imagedestroy($image);
			$width = $new_width;
			$height = $new_height;
		}
		return array($file, $width, $height);
	}

	function fsockopen_image_header($image_url, $mode='Content-Type') { // 'Content-Length' | 'Content-Type' | 'Date' | 'Last-Modified'
		$url = parse_url($image_url);
		$fp = @fsockopen($url['host'], 80, $errno, $errstr, 30);
		if ($fp) {
			//这里请求设置为HEAD
			$out = "HEAD {$url['path']} HTTP/1.1\r\n";
			$out .= "Host: {$url['host']}\r\n";
			$out .= "Connection: Close\r\n\r\n";
			fwrite($fp, $out);
			while (!feof($fp)) {
				$header = fgets($fp);
				if (stripos($header, $mode) !== false) {
					$value = trim(substr($header, strpos($header, ':') + 1));
					return $value;
				}
			}
			fclose($fp);
		}
		return null;
	}

	public function download_image($image_url) {
		$encoding = $this->fsockopen_image_header($image_url, 'Content-Encoding');
		$file = '';

		// file_get_contents
		if (function_exists('file_get_contents')) {
			$file = @file_get_contents($image_url);
			if ($encoding == 'gzip' && function_exists('gzdecode')) $file = @gzdecode($file);
		}

		// curl
		if (!$file && function_exists('curl_init')) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $image_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			//curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			$file = curl_exec($ch);
			if ($encoding == 'gzip' && function_exists('gzdecode')) $file = @gzdecode($file);
			curl_close($ch);
		}

		// GD
		$img = @imagecreatefromstring($file);
		if (!$img && function_exists('fsockopen')) {
			$this->type = @$this->fsockopen_image_header($image_url);
			if ($this->type && in_array($type, array('image/jpeg', 'image/gif', 'image/png'))) {
				$type = substr($this->type, 6);
				$img = @call_user_func("imagecreatefrom{$type}", $image_url);
				if ($img) {
					ob_start();
					call_user_func("image{$type}", $img);
					$file = ob_get_contents();
					ob_end_clean();
					imagedestroy($img);
					if ($encoding == 'gzip' && function_exists('gzdecode')) $file = @gzdecode($file);
				}
			} else $file = '';
		}
		return $file;
	}

	//save exterior images
	function save_images($image_url, $post_id){
		set_time_limit(0);
		$file = $this->download_image($image_url);
		if ( $file ) {
			$filename = $this->get_filename_from_url($image_url);
			preg_match( '/(.*?)(\.(jpg|jpeg|png|gif|bmp))$/i', $filename, $match );
			if ( empty($match) ) {
				if ($filetype = $this->getFileType($file) ) {
					preg_match( '/(.*?)$/i', $filename, $match );
					$pos=strpos($image_url,'?'); // if has '?', md5()
					$img_name = $pos ? md5($match[0]) : $match[0];
					$img_name = $this->change_images_filename($img_name, '.'.$filetype);
				} else return false;
			} else {
				$img_name = $match[1];
				$filetype = preg_replace('/^./', '', $match[2]);
				$img_name = $this->change_images_filename($match[1], $match[2]);
			}
			// Automatic reduction pictures size
			list($file, $width, $height) = $this->automatic_reduction($file, $image_url);
			/* fit to custom-upload-dir - start */
			if (function_exists('cud_custom_upload_dir')) {
				global $cud_file_ext, $cud_file_type;
				$cud_file_type = $this->type;
				$cud_file_ext = $filetype;
				add_filter('upload_dir', 'cud_custom_upload_dir', 1);
			}
			/* fit to custom-upload-dir - end */
			$res=wp_upload_bits($img_name,'',$file);
			if (isset( $res['error'] ) && !empty($res['error'])) return false;
			$attachment_id = $this->insert_attachment($res['file'], $post_id);
			$res['id'] = $attachment_id;
			$meta_data = wp_get_attachment_metadata($attachment_id);
			$res = @array_merge($res, $meta_data);
			if( !has_post_thumbnail($post_id) && $this->featured_image=='yes' ) set_post_thumbnail( $post_id, $attachment_id );
			return $res;
		} else {
			$this->has_missing_image = 1;
		}
		return false;
	}

	public function getFileType($file){
		$bin = substr($file,0,2);
		$strInfo = @unpack("C2chars", $bin);
		$typeCode = intval($strInfo['chars1'].$strInfo['chars2']);
		switch ($typeCode) {
			case 7790: $fileType = 'exe'; return false;
			case 7784: $fileType = 'midi'; return false;
			case 8297: $fileType = 'rar'; return false;
			case 255216: $fileType = 'jpg'; $mime = 'image/jpeg'; return $fileType;
			case 7173: $fileType = 'gif'; $mime = 'image/gif'; return $fileType;
			case 6677: $fileType = 'bmp'; $mime = 'image/bmp'; return $fileType;
			case 13780: $fileType = 'png'; $mime = 'image/png'; return $fileType;
			default: return false;
		}
	}
	
	//insert attachment
	function insert_attachment($file, $id){
		$dirs = wp_upload_dir();
		$filetype = wp_check_filetype($file);
		$attachment=array(
			'guid' => $dirs['baseurl'].'/'._wp_relative_upload_path($file),
			'post_mime_type' => $filetype['type'],
			'post_title' => preg_replace('/\.[^.]+$/','',basename($file)),
			'post_content' => '',
			'post_status' => 'inherit'
		);
		$attach_id = wp_insert_attachment($attachment, $file, $id);
		if (!function_exists('wp_generate_attachment_metadata')) include_once (ABSPATH . DIRECTORY_SEPARATOR . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'image.php');
		$attach_data = wp_generate_attachment_metadata($attach_id, $file);
		wp_update_attachment_metadata($attach_id, $attach_data);
		return $attach_id;
	}
}
new QQWorld_auto_save_images;

class QQWorld_Save_Remote_Images_Walker_Category_Checklist extends Walker {
	public $tree_type = 'category';
	public $db_fields = array ('parent' => 'parent', 'id' => 'term_id'); //TODO: decouple this

	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent<ul class='children'>\n";
	}
	public function end_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}
	public function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {
		if ( empty( $args['taxonomy'] ) ) {
			$taxonomy = 'category';
		} else {
			$taxonomy = $args['taxonomy'];
		}

		if ( $taxonomy == 'category' ) {
			$name = 'post_category';
		} else {
			$name = 'tax_input[' . $taxonomy . ']';
		}
		$args['popular_cats'] = empty( $args['popular_cats'] ) ? array() : $args['popular_cats'];
		$class = in_array( $category->term_id, $args['popular_cats'] ) ? ' class="popular-category"' : '';

		$args['selected_cats'] = empty( $args['selected_cats'] ) ? array() : $args['selected_cats'];

		/** This filter is documented in wp-includes/category-template.php */
		$output .= "\n<li id='{$taxonomy}-{$category->term_id}'$class>" .
			'<label class="selectit"><input value="' . $category->term_id . '" type="checkbox" name="terms['.$taxonomy.'][]" id="in-'.$taxonomy.'-' . $category->term_id . '"' .
			checked( in_array( $category->term_id, $args['selected_cats'] ), true, false ) .
			disabled( empty( $args['disabled'] ), false, false ) . ' /> ' .
			esc_html( apply_filters( 'the_category', $category->name ) ) . '</label>';
	}
	public function end_el( &$output, $category, $depth = 0, $args = array() ) {
		$output .= "</li>\n";
	}
}