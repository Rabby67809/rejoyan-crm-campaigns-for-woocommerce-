<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Rejoyan_CRM {
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function run() {
        $offers    = new Rejoyan_CRM_Offers();
        $segments  = new Rejoyan_CRM_Segments();
        $mailer    = new Rejoyan_CRM_Mailer( $offers );
        $campaigns = new Rejoyan_CRM_Campaigns( $mailer, $segments );
        $privacy = new Rejoyan_CRM_Privacy();

        if ( is_admin() ) {
            $reports = new Rejoyan_CRM_Reports();
            $lazy    = new Rejoyan_CRM_Admin_Lazy( $campaigns, $offers, $reports, $segments );
            new Rejoyan_CRM_Admin( $campaigns, $offers, $reports, $segments, $lazy );
        }
        $privacy->register();

        add_action( 'rejoyan_crm_prepare_campaign_batch', array( $campaigns, 'prepare_batch' ), 10, 2 );
        add_action( 'rejoyan_crm_send_campaign_batch', array( $campaigns, 'send_batch' ), 10, 1 );
        add_action( 'rejoyan_crm_dispatch_scheduled_campaign', array( $campaigns, 'dispatch_scheduled_campaign' ), 10, 1 );
        add_action( 'admin_init', array( $campaigns, 'recover_stalled_campaigns' ) );
        add_action( 'admin_post_nopriv_rejoyan_crm_unsubscribe', array( $campaigns, 'handle_unsubscribe' ) );
        add_action( 'admin_post_rejoyan_crm_unsubscribe', array( $campaigns, 'handle_unsubscribe' ) );
        add_action( 'template_redirect', array( $offers, 'handle_offer_click' ) );
        add_action( 'woocommerce_add_to_cart', array( $offers, 'apply_pending_coupon' ), 20, 0 );
        add_action( 'woocommerce_before_cart', array( $offers, 'apply_pending_coupon' ), 20, 0 );
        add_action( 'woocommerce_before_checkout_form', array( $offers, 'apply_pending_coupon' ), 20, 0 );
        add_action( 'woocommerce_order_status_changed', array( $segments, 'invalidate_from_order' ), 10, 1 );
        add_action( 'woocommerce_new_order', array( $segments, 'invalidate_from_order' ), 10, 1 );
    }
}
