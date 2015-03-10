<?php
/**
 * @package Cart2Cart
 * @version 1.0.0
 */
/*
Plugin Name: Cart2Cart: YahooStore to WooCommerce Migration Module
Plugin URI: http://www.shopping-cart-migration.com/
Description: Cart2Cart Integration Plugin
Author: MagneticOne
Version: 1.0.0
Author URI: http://www.magneticone.com/
*/
defined('ABSPATH') or die("Cannot access pages directly.");
@ini_set('display_errors', '1');
error_reporting(E_ALL & ~E_NOTICE);
include 'worker.php';
$r = new WP_Http();
$worker = new Cart2CartWorker();
if (isset($_REQUEST['c2caction'])){
  $action = $_REQUEST['c2caction'];
  switch ($action){
    case 'saveToken':
      update_option('Cart2CartStoreToken',$_REQUEST['c2c_token']);
      break;
    case 'checkApi':
      $res = wp_remote_get(trim((string)$_REQUEST['url'], '/'));
      if ($res instanceof WP_Error){
        $message = '';
        foreach($res->errors as $error){
          $message .= $error[0]."\n";
        }
        echo json_encode(array(
          'messages' => $message,
          'messageType' => 'error'
        ));
        exit();
      }
      update_option('Cart2cartSourceUrl',$_REQUEST['url']);

      echo json_encode(array(
        'messages' => 'Credentials are valid',
        'messageType' => 'success'
      ));
      break;

    case 'installBridge':
      $worker->installBridge(get_option('Cart2CartStoreToken'));
      break;
    case 'removeBridge':
      $worker->unInstallBridge();
      break;
    case 'saveLoginStatus':
      update_option('Cart2CartLoginStatus',$_REQUEST['status']);
      update_option('Cart2CartLoginEmail',$_REQUEST['email']);
      update_option('Cart2CartLoginKey', $_REQUEST['encPass']);
      echo 'set status ' . $_REQUEST['status'];
      break;
  }
  die();
}

function cart2cart_plugin_action_links( $links, $file ) {
  if ( $file == plugin_basename( dirname(__FILE__).'/cart2cartMain.php' ) ) {
    $links[] = '<a href="' . admin_url( 'admin.php?page=cart2cart-config' ) . '">'.__( 'Settings' ).'</a>';
  }

  return $links;
}

add_filter( 'plugin_action_links', 'cart2cart_plugin_action_links',10,2 );

function cart2cart_config(){
  global  $worker;
  wp_enqueue_style( 'http://fonts.googleapis.com/css?family=Open+Sans:400,700,600');
  wp_enqueue_style( 'cart2cart', plugins_url( 'css/c2c.css' , __FILE__ ) );
  wp_enqueue_style( 'font-awesome','http://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css');
  wp_enqueue_script( 'md5-min', plugins_url( 'js/md5-min.js' , __FILE__ ) );
  wp_enqueue_script( 'cart2cartjs', plugins_url( 'js/c2c.js' , __FILE__ ) );
  wp_enqueue_script( 'cart2cartjs', plugins_url( 'js/base64-min.js' , __FILE__ ) );



  $showButton = 'install';
  if ($worker->isBridgeExist()){
    $showButton = 'uninstall';

  }
  $loginStatus = get_option('Cart2CartLoginStatus');
  if ($loginStatus == ''){
    $loginStatus = 'No';
  }

  $cartName = 'WooCommerce';
  $sourceCartName = 'YahooStore';
  $sourceCartId   = 'Yahoostore';
  $targetCartId   = 'Woocommerce';
  $referertext = 'Cart2Cart: '. $sourceCartName  . ' to '. $cartName  . ' module';
  $sourceCartLogo = 'http://www.shopping-cart-migration.com/images/stories/'.strtolower($sourceCartId).'.gif';
  $banner = '<div class="banner" onclick="javascript: window.open(\'http://www.shopping-cart-migration.com/support-service-plans/?utm_source=' . $cartName . '&utm_medium=Plugins\',\'_blank\'); return false;"><img src="'. plugins_url( 'images/banner.png' , __FILE__ ).'" /></div>';
  $cart2cart_logo = '<a target="_blank" class="cart2cart_logo" href="http://www.shopping-cart-migration.com/?utm_source=' . $cartName . '&utm_medium=Plugins&utm_campaign=c2c' . $cartName . '"  target="_blank">';

  $storeToken = get_option('Cart2CartStoreToken');
  $Cart2cartSourceUrl = get_option('Cart2cartSourceUrl');
  $cart2CartLoginEmail = get_option('Cart2CartLoginEmail');
  $cart2CartLoginKey = get_option('Cart2CartLoginKey');


  include 'settings.phtml';
  return true;
}

function cart2cart_load_menu() {
  add_submenu_page('plugins.php', __('Cart2Cart'), __('Cart2Cart'), 'manage_options', 'cart2cart-config', 'cart2cart_config' );
}

add_action( 'admin_menu', 'cart2cart_load_menu' );