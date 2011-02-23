<?php
/*
Plugin Name: Modify Author Url
Plugin URI: 
Description: Allows administrators to modify a users author url from the profile page.
Version: 1.0
Author: Jared Harbour
Author URI: http://www.jaredharbour.com
Network: true
*/

require_once(dirname(__FILE__) . '/config.php');
require_once(dirname(__FILE__) . '/classes/authorurl.class.php');

add_action('admin_init','init_modify_author_url');

function init_modify_author_url(){
	$wh_mod_author = new WH_modify_author_url();
}
?>
