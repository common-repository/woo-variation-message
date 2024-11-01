<?php
/*
Plugin Name: WooCommerce Variation Message
Description: Add an optional message to a WooCommerce product variation.
Author: WebKinder
Version: 0.3.0
Author URI: https://www.webkinder.ch
Text Domain: woo-variation-message
Domain Path: /languages
 */

 // If this file is called directly, abort.
 if ( ! defined( 'WPINC' ) ) {
 	die;
 }


require_once 'Classes/Plugin.php';
WC_Variation_Message::get_instance( __FILE__ );