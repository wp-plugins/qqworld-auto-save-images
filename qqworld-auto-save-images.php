<?php 
/*
Plugin Name: QQWorld Auto Save Images
Plugin URI: http://wordpress.org/plugins/qqworld-auto-save-images/
Description: Automatically keep the all remote picture to the local, and automatically set featured image. 自动保存远程图片到本地，自动设置特色图片，并且支持机器人采集软件从外部提交。
Version: 1.0
Author: Michael Wang
Author URI: http://project.qqworld.org
*/

class QQWorld_auto_save_images {
	function __construct() {
		add_action('publish_post', array($this, 'fetch_images') );
	}
	function fetch_images($post_ID) {
		//Check to make sure function is not executed more than once on save
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
		return;

		if ( !current_user_can('edit_post', $post_ID) ) 
		return;

		remove_action('publish_post', array($this, 'fetch_images') );

		$post=get_post($post_ID);
		$content=$post->post_content;
		$preg=preg_match_all('/<img.*?src="(.*?)"/',stripslashes($content),$matches);
		if($preg){
			$i = 1;
			foreach($matches[1] as $image_url){
				if(empty($image_url)) continue;
				$pos=strpos($image_url,get_bloginfo('url'));
				if($pos===false){
					$res=$this->save_images($image_url,$post_id,$i);
					$replace=$res['url'];
					$content=str_replace($image_url,$replace,$content);
				}
				$i++;
			}
		}
	    //Replace the image in the post
	    wp_update_post(array('ID' => $post_ID, 'post_content' => $content));
		add_action('publish_post', array($this, 'fetch_images') );
	}
	//save exterior images
	function save_images($image_url,$post_id,$i){
		$file=file_get_contents($image_url);
		$filename=basename($image_url);
		preg_match( '/(.*?)(\.\w+)$/', $filename, $match );
		$im_name = md5($match[1]).$match[2];		
		$res=wp_upload_bits($im_name,'',$file);
		$attach_id = $this->insert_attachment($res['file'],$post_id);
		if( $i==1 ) set_post_thumbnail( $post_id, $attach_id );
		return $res;
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