<?php
/**
 * Plugin Name: Rejoyan CRM & Campaigns for WooCommerce
 * Description: Customer CRM, offer emails, smart segments, scheduled campaigns, invoices, analytics and live product sharing for WooCommerce.
 * Version: 1.0.0
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: rejoyan9009
 * Author URI: https://profiles.wordpress.org/rejoyan9009/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rejoyan-crm-campaigns-for-woocommerce
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * WC requires at least: 8.2
 * WC tested up to: 11.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'REJOYAN_CRM_VERSION', '1.0.0' );
define( 'REJOYAN_CRM_DB_VERSION', '0.8.0' );
define( 'REJOYAN_CRM_FILE', __FILE__ );
define( 'REJOYAN_CRM_DIR', plugin_dir_path( __FILE__ ) );
define( 'REJOYAN_CRM_URL', plugin_dir_url( __FILE__ ) );

require_once REJOYAN_CRM_DIR . 'includes/class-rejoyan-crm-db.php';
require_once REJOYAN_CRM_DIR . 'includes/class-rejoyan-crm-activator.php';
require_once REJOYAN_CRM_DIR . 'includes/class-rejoyan-crm-offers.php';
require_once REJOYAN_CRM_DIR . 'includes/class-rejoyan-crm-segments.php';
require_once REJOYAN_CRM_DIR . 'includes/class-rejoyan-crm-mailer.php';
require_once REJOYAN_CRM_DIR . 'includes/class-rejoyan-crm-campaigns.php';
require_once REJOYAN_CRM_DIR . 'includes/class-rejoyan-crm-privacy.php';
if ( is_admin() ) {
    require_once REJOYAN_CRM_DIR . 'includes/class-rejoyan-crm-reports.php';
    require_once REJOYAN_CRM_DIR . 'includes/class-rejoyan-crm-admin-lazy.php';
    require_once REJOYAN_CRM_DIR . 'includes/class-rejoyan-crm-admin.php';
}
require_once REJOYAN_CRM_DIR . 'includes/class-rejoyan-crm.php';

register_activation_hook( __FILE__, array( 'Rejoyan_CRM_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Rejoyan_CRM_Activator', 'deactivate' ) );


add_filter(
    'plugin_action_links_' . plugin_basename( __FILE__ ),
    static function ( $links ) {
        if ( current_user_can( 'manage_woocommerce' ) ) {
            array_unshift(
                $links,
                '<a href="' . esc_url( admin_url( 'admin.php?page=rejoyan-crm-settings' ) ) . '">' . esc_html__( 'Settings', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</a>'
            );
        }
        return $links;
    }
);

add_action(
    'before_woocommerce_init',
    static function () {
        if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        }
    }
);

add_action(
    'plugins_loaded',
    static function () {
        if ( version_compare( (string) get_option( 'rejoyan_crm_db_version', '0' ), REJOYAN_CRM_DB_VERSION, '<' ) ) {
            Rejoyan_CRM_Activator::activate();
        }

        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action(
                'admin_notices',
                static function () {
                    if ( current_user_can( 'activate_plugins' ) ) {
                        echo '<div class="notice notice-error"><p>' . esc_html__( 'Rejoyan CRM & Campaigns requires WooCommerce to be installed and active.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</p></div>';
                    }
                }
            );
            return;
        }

        Rejoyan_CRM::instance()->run();
    },
    20
);
