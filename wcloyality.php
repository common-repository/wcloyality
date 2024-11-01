<?php 
/**
 * Plugin Name: wcLoyality 
 * Plugin URI: http://loyality.app
 * Description: Sell Loyality E-gift cards and vouchers through woocommerce.
 * Version: 1.0.0
 * Author: Philip Neves (Neves Software Inc.)
 * Author URI: http://www.nevessoftware.com
 * Developer: Neves Software Inc. 
 * Developer URI: http://nevessoftware.com
 * Text Domain: woocommerce-extension
 * Domain Path: /languages
 *
 * WC requires at least: 3.7.0
 * WC tested up to: 3.7.0
 * requires PHP Curl Extension installed.
 * requires A Loyality account on the https://loyality.app website.
 *
 * Copyright: © 2019 Neves Software Inc.
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 */


global $wcloyality_db_version;
$wcloyality_db_version = '1.0';
ini_set('log_errors', 1);

if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }

if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
       exit;
    }

require_once plugin_dir_path(__FILE__) . "includes/_wcloyality.php"; 

/**
 * wcloyality activation function.
 *
 * @return void
 */
function wcloyality_activation()
        {
        global $wpdb;
        global $wcloyality_db_version;

        if ( ! current_user_can( 'activate_plugins' ) )
                return;

        $table_name = $wpdb->prefix . 'wcloyality_pending_gift_card_activations';
	
        $charset_collate = $wpdb->get_charset_collate();
        
        echo "<p>Registering wcLoyality</p>";

        $sql = "CREATE TABLE " . $table_name . "(
                        activationId int(11) AUTO_INCREMENT,
                        orderid int(11),
                        giftcardId character varying(64),
                        templateid character varying(64),
                        phone character varying(30),
                        recipient_name character varying(255),
                        email character varying(512),
                        pending tinyint(1) DEFAULT 1,
                        amount decimal(10, 2),
                        greetingMessage text,
                        PRIMARY KEY (activationid)
                        );"; 

       
        $wpdb->query( $sql );

        if ($wpdb->last_error !== '') 
                {
                set_transient('wcloyalty-activation', $wpdb->last_error, 5);
                return;    
                }

        $voucher_table_name = $wpdb->prefix . "wcloyality_pending_voucher_activations";

        $sql = "CREATE TABLE " . $voucher_table_name . "(
                activationid int(11) AUTO_INCREMENT,
                orderid int(11), 
                voucherblockid character varying(64), 
                recipient_name character varying(255),
                phone character varying(30),
                email character varying(512),
                greetingMessage text,
                pending tinyint(1) DEFAULT 1,
                qty int(1),
                PRIMARY KEY (activationid)
                );";  
                
        $wpdb->query( $sql );

        if ($wpdb->last_error !== '') 
                {
                set_transient('wcloyalty-activation', $wpdb->last_error, 5);
                return;
                }

        set_transient('wcloyality-activation', true, 5);            
        add_option( 'wcloyality_db_version', $wcloyality_db_version );
        }

/**
 * wcloyality deactivation.
 *
 * @return void
 */
function wcloyality_deactivation()
        {
        global $wpdb;

        if ( ! current_user_can( 'activate_plugins' ) )
                return;

        echo "<p>Unregistering wcLoyality</p>";

        $table_name = $wpdb->prefix . 'wcloyality_pending_gift_card_activations';

        $sql = "DROP TABLE " . $table_name . ";";  

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $wpdb->query( $sql );

        if ($wpdb->last_error !== '') 
                {
                set_transient('wcloyalty-activation', $wpdb->last_error, 5);
                return;
                }

        $table_name = $wpdb->prefix . "wcloyality_pending_voucher_activations";

        $sql = "DROP TABLE " . $table_name . ";";

        $wpdb->query( $sql );        

        if ($wpdb->last_error !== '') 
                {
                set_transient('wcloyalty-activation', $wpdb->last_error, 5);
                return;
                }
        
        set_transient('wcloyality-activation', true, 5);        
        }

/**
 * wc loyalty admin notice during install.
 *
 * @return void
 */
function wc_loyality_admin_notice()
        {
        global $wpdb;
        
        $result = get_transient( 'wcloyality-activation' ); 

        if ($result != true) 
                {
                ?>
                <div class="updated notice is-dismissible">
                         <?php echo $result; ?>
                 </div>
                 <?php
                }
        else 
                {
                ?>
                <div class="updated notice is-dismissible">
                        Successfully installed plugin with no errors;
                </div>
                <?php
                }

        /* Delete transient, only display this notice once. */
        delete_transient( 'wcloyality-activation' );    
        }


 /**
 * plugin action links. 
 *
 * @param Array $actions
 * @param File $file
 * @return void
 */
function wcloyality_plugin_action_links($actions, $file)
        {
        static $this_plugin;

        if (!$this_plugin)
                {
                $this_plugin = plugin_basename(__FILE__); 
                }

        if ($file == $this_plugin) 
                {
                $plugin_links[] = '<a href="' . esc_url(get_admin_url(null, '/admin.php?page=wcloyality')) . '">' . __('Settings') . '</a>';

                $actions = array_merge($actions, $plugin_links);
                }

        return $actions;
        }


add_action( 'admin_notices', 'wc_loyality_admin_notice' );

/**
 * register activation hooks.
 */

register_activation_hook(__FILE__, 'wcloyality_activation'); 
register_deactivation_hook(__FILE__, 'wcloyality_deactivation'); 



if (is_admin())
        {
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wcloyality_plugin_action_links', 10, 2);        
        }

/**
 * Initialize plugin.
 */
new WcLoyality(); 