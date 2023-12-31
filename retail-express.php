<?php 
/*
Plugin Name: Retail Express By Sujan
Plugin URI: https://imjol.com
Description: Retail Express Integration for Woocommerce
Version: 1.0
Author: Md Toriqul Mowla Sujan
Author URI: https://imjol.com
License: GPLv2 or later
Text Domain: retail-express
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Your main plugin file
require_once 'vendor/autoload.php';

$menu_page = new AdminPage\MenuPage();
$products = new Database\Products();
$shortcodes = new Shortcodes\Shortcodes();
$products_api = new API\Products();
$customer_api = new API\CustomerAPI();
$customer_sync = new WooCommerce\CustomerSync();



// add a database table while plugin is activated
register_activation_hook(__FILE__, array('Database\Products', 'create_table'));
register_deactivation_hook(__FILE__, array('Database\Products', 'remove_table'));