<?php
class WH_modify_author_url{
	function __construct(){
		// get the "author" role object
		$role = get_role( 'administrator' );

		// add "organize_gallery" to this role object
		$role->add_cap( 'modify_author_url' );
		
		$this->add_filters_and_actions();
	}
	
	function add_filters_and_actions(){
		add_action('show_user_profile',array( &$this, 'profile_author_url_field' ));
		add_action('edit_user_profile',array( &$this, 'profile_author_url_field' ));
		
		add_action('edit_user_profile_update',array( &$this, 'profile_author_url_save' ));
		add_action('personal_options_update',array( &$this, 'profile_author_url_save' ));
		
		add_action('admin_head' ,array( &$this, 'check_permalink' ));
				
	}
	
	function check_permalink(){
		if(is_super_admin() || current_user_can('manage_options')){
			if(!$this->has_permalink()){
				add_action('admin_notices' , array( &$this, 'bad_permalink_message' ) );
			}
		}
	}
	
	function bad_permalink_message(){
		echo "<div id='wh-pl-warning' class='updated fade'><p>This blog is using the default permalink structure, to use the Author URL plugin please choose a different permalink structure. <a href='/wp-admin/options-permalink.php'>Click Here</a> to edit permalink structure.</p></div>";
	}
	
	function has_permalink(){	
		global $wp_rewrite;
		$link = $wp_rewrite->get_author_permastruct();
		if(strlen($link) == 0){
			return false;
		}
		return true;
	}
	
	function get_old_urls($userid,$nicename = ''){
		$author_urls = get_user_meta( $userid, 'wh_author_urls'); 
		
		if(empty($author_urls)){
			update_user_meta($userid , 'wh_author_urls' , serialize(array($nicename)) );
			$author_urls = get_user_meta( $userid , 'wh_author_urls');
		}
		$author_urls = unserialize($author_urls[0]);
		
		return $author_urls;
	}
	
	function profile_author_url_save( $user_id ){
		
		$user_id = (int) $user_id;
		$user_data = get_userdata($user_id);
		
		$new_user_nicename = "";
		
		$user_login = $user_data->user_login;
		
		$current_nicename = $user_data->user_nicename;
		$new_url = $_POST['wh_author_url'];
		$set_old_url = $_POST['wh_author_url_select'];
		
		if($current_nicename == $set_old_url){
			if(strlen($new_url) > 0){
				//set new url and add to option array
				$new_url = sanitize_user( $new_url,true );
				
				$new_url = $this->check_nicename($new_url,$user_login);
				
				$this->add_nicename_to_dropdown($user_id,$new_url);
			
				$new_user_nicename = $new_url;
				
			}
		}else{
			//set different url from dropdown
			$set_old_url = $this->check_nicename($set_old_url,$user_login);
			
			$this->add_nicename_to_dropdown($user_id,$set_old_url);
			
			$new_user_nicename = $set_old_url;
		}
		
		$userid = wp_update_user(array ('ID' => $user_id, 'user_nicename' => $new_user_nicename));
	}
	
	function add_nicename_to_dropdown($userid,$name){
		$wh_author_urls = $this->get_old_urls($userid);
				
		if(!in_array($name,$wh_author_urls)){
			array_push($wh_author_urls,$name);
			update_user_meta($userid , 'wh_author_urls' , serialize($wh_author_urls) );
		}
	}
	
	function check_nicename($new_url, $user_name){
		global $wpdb;
		
		$user_nicename_check = $wpdb->get_var( $wpdb->prepare("SELECT ID FROM $wpdb->users WHERE user_nicename = %s AND user_login != %s LIMIT 1" , $new_url, $user_name));
	
		if ( $user_nicename_check ) {
		    $suffix = 2;
		    while ($user_nicename_check) {
		    	$alt_user_nicename = $new_url . "-$suffix";
		    	$user_nicename_check = $wpdb->get_var( $wpdb->prepare("SELECT ID FROM $wpdb->users WHERE user_nicename = %s AND user_login != %s LIMIT 1" , $alt_user_nicename, $user_name));
		    	$suffix++;
		    }
		    $new_url = $alt_user_nicename;
		}
		
		return $new_url;
	
	}
	
	function profile_author_url_field($profileuser){	
	
		if($this->has_permalink() && current_user_can('modify_author_url')){?>
			<br>
			<h3><?php _e('Author URL'); ?></h3>
			
			<?php $wh_author_urls = $this->get_old_urls($profileuser->ID,$profileuser->user_nicename);?>
		
			<div class="description"><?php _e('This is the url used by WordPress to display posts by this user.  You can select one from the drop down that has been used previously or create a new one.'); ?></div>
			<div class="description"><?php _e('If you select one from the drop down, that one will automatically be set, even if you try and create a new one.'); ?></div>
			<div class="description"><?php _e('If the author url you set is currently being used by another user, a suffix will automatically be added in order to make it unique.'); ?></div>
			
			<?php
				global $wp_rewrite;
				$link = $wp_rewrite->get_author_permastruct();
				$link = str_replace("%author%","",$link);
				
				$current_author_url = get_option("siteurl") . $link . $profileuser->user_nicename;
			?>
		
			<div class="description" style="font-weight:bold;"><?php _e('Your current author url is: <a href="'.$current_author_url. '" target="_blank">'.$current_author_url.'</a>'); ?></div>
			
			<table class="form-table">
				<tr>
					<th><label for="wh_author_url"><?php _e('Create New URL'); ?></label></th>
					<td>
						<code><?php echo get_option("siteurl") . $link ?></code>
						<input type="text" name="wh_author_url" id="wh_author_url" value="" class="regular-text" /> 
						<p>Only the characters a-z and 0-9 recommended.</p>
					</td>
				</tr>
			</table>
			
			<table class="form-table">
				<tr>
					<th><label for="wh_author_url_select"><?php _e('Select Previous URL'); ?></label></th>
					<td>
						<code><?php echo get_option("siteurl") . $link ?></code>
						<select id="wh_author_url_select" name="wh_author_url_select">
							<?php foreach($wh_author_urls as $url){?>
								<option value="<?php echo $url ?>" <?php if($profileuser->user_nicename == $url){echo "selected";} ?>><?php echo $url ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
			</table>
			
		<?php
		}
	}
}
?>