<?php 
/*
Plugin Name: QQWorld Auto Save Images
Plugin URI: https://wordpress.org/plugins/qqworld-auto-save-images/
Description: Automatically keep the all remote picture to the local, and automatically set featured image. 自动保存远程图片到本地，自动设置特色图片，并且支持机器人采集软件从外部提交。
Version: 1.3
Author: Michael Wang
Author URI: http://www.qqworld.org
*/

class QQWorld_auto_save_images {
	var $using_action;
	function __construct() {
		$this->using_action = get_option('using_action', 'publish');
		$this->add_actions();
		add_action( 'plugins_loaded', array($this, 'load_language') );
		add_action( 'admin_menu', array($this, 'admin_menu') );
		add_action( 'admin_init', array($this, 'register_settings') );
		add_filter( 'plugin_row_meta', array($this, 'registerPluginLinks'),10,2 );
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
					<th scope="row"><label for="blogname"><?php _e('The action of using', 'qqworld_auto_save_images'); ?></label></th>
					<td><fieldset>
						<legend class="screen-reader-text"><span><?php _e('The action of using', 'qqworld_auto_save_images'); ?></span></legend>
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
		<p class="submit"><input type="submit" value="<?php _e('Save Changes') ?>" class="button-primary" name="Submit" /></p>
	</form>
<?php
	}

	function register_settings() {
		register_setting('qqworld_auto_save_images_settings', 'using_action');
	}

	function add_actions() {
		if ($this->using_action == 'publish') add_action('publish_post', array($this, 'fetch_images') );
		elseif ($this->using_action == 'save') add_action('save_post', array($this, 'fetch_images') );
	}

	function remove_actions() {
		if ($this->using_action == 'publish') remove_action('publish_post', array($this, 'fetch_images') );
		elseif ($this->using_action == 'save') remove_action('save_post', array($this, 'fetch_images') );
	}

	function fetch_images($post_ID) {
		//Check to make sure function is not executed more than once on save
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
		return;

		if ( !current_user_can('edit_post', $post_ID) ) 
		return;

		$this->remove_actions();

		$post=get_post($post_ID);
		$content=$post->post_content;
		$preg=preg_match_all('/<img.*?src="(.*?)(\?.*?)?"/',stripslashes($content),$matches);
		if($preg){
			$i = 1;
			foreach($matches[1] as $image_url){
				if(empty($image_url)) continue;
				$pos=strpos($image_url,get_bloginfo('url'));
				if($pos===false){
					if ($res=$this->save_images($image_url,$post_id,$i)) {
						$replace=$res['url'];
						$content=str_replace($image_url,$replace,$content);
					}
				}
				$i++;
			}
		}
	    //Replace the image in the post
	    wp_update_post(array('ID' => $post_ID, 'post_content' => $content));
		$this->add_actions();
	}

	//save exterior images
	function save_images($image_url,$post_id,$i){
		if ( $file=@file_get_contents($image_url) ) {
			$filename=basename($image_url);
			preg_match( '/(.*?)(\.\w+)$/', $filename, $match );
			$im_name = $match[1].$match[2];
			$res=wp_upload_bits($im_name,'',$file);
			$attach_id = $this->insert_attachment($res['file'],$post_id);
			if( $i==1 ) set_post_thumbnail( $post_id, $attach_id );
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