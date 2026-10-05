<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Rejoyan_CRM_Activator {
    public static function activate() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $campaigns       = Rejoyan_CRM_DB::campaigns_table();
        $offers          = Rejoyan_CRM_DB::offers_table();
        $recipients      = Rejoyan_CRM_DB::recipients_table();
        $logs            = Rejoyan_CRM_DB::logs_table();
        $activity        = Rejoyan_CRM_DB::activity_table();

        $sql_campaigns = "CREATE TABLE {$campaigns} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(191) NOT NULL,
            subject varchar(255) NOT NULL,
            content longtext NOT NULL,
            audience varchar(50) NOT NULL DEFAULT 'optin',
            campaign_type varchar(30) NOT NULL DEFAULT 'standard',
            offer_id bigint(20) unsigned NOT NULL DEFAULT 0,
            status varchar(30) NOT NULL DEFAULT 'draft',
            total_count bigint(20) unsigned NOT NULL DEFAULT 0,
            processed_count bigint(20) unsigned NOT NULL DEFAULT 0,
            sent_count bigint(20) unsigned NOT NULL DEFAULT 0,
            failed_count bigint(20) unsigned NOT NULL DEFAULT 0,
            skipped_count bigint(20) unsigned NOT NULL DEFAULT 0,
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            scheduled_at datetime NULL,
            started_at datetime NULL,
            completed_at datetime NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY campaign_type (campaign_type),
            KEY offer_id (offer_id),
            KEY scheduled_at (scheduled_at),
            KEY created_at (created_at)
        ) {$charset_collate};";

        $sql_offers = "CREATE TABLE {$offers} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(191) NOT NULL,
            subject varchar(255) NOT NULL,
            badge varchar(100) NOT NULL DEFAULT '',
            headline varchar(255) NOT NULL,
            description longtext NOT NULL,
            coupon_code varchar(191) NOT NULL DEFAULT '',
            product_id bigint(20) unsigned NOT NULL DEFAULT 0,
            auto_apply_coupon tinyint(1) unsigned NOT NULL DEFAULT 1,
            cta_text varchar(120) NOT NULL DEFAULT '',
            cta_url text NULL,
            image_url text NULL,
            expiry_text varchar(191) NOT NULL DEFAULT '',
            preheader varchar(255) NOT NULL DEFAULT '',
            template_style varchar(30) NOT NULL DEFAULT 'classic',
            accent_color varchar(20) NOT NULL DEFAULT '#5b4cf0',
            starts_at datetime NULL,
            ends_at datetime NULL,
            status varchar(30) NOT NULL DEFAULT 'active',
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY product_id (product_id),
            KEY starts_at (starts_at),
            KEY ends_at (ends_at),
            KEY created_at (created_at)
        ) {$charset_collate};";

        $sql_recipients = "CREATE TABLE {$recipients} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campaign_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            email varchar(190) NOT NULL,
            email_hash char(64) NOT NULL,
            status varchar(30) NOT NULL DEFAULT 'pending',
            error_message text NULL,
            sent_at datetime NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY campaign_email (campaign_id,email_hash),
            KEY campaign_status (campaign_id,status),
            KEY user_id (user_id),
            KEY email_hash (email_hash)
        ) {$charset_collate};";

        $sql_logs = "CREATE TABLE {$logs} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campaign_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            email varchar(190) NOT NULL,
            status varchar(30) NOT NULL,
            message text NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY campaign_id (campaign_id),
            KEY user_id (user_id),
            KEY status (status)
        ) {$charset_collate};";

        $sql_activity = "CREATE TABLE {$activity} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            type varchar(50) NOT NULL,
            object_type varchar(50) NOT NULL DEFAULT '',
            object_id bigint(20) unsigned NOT NULL DEFAULT 0,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            message varchar(255) NOT NULL,
            context_json longtext NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY type (type),
            KEY object_lookup (object_type,object_id),
            KEY created_at (created_at)
        ) {$charset_collate};";

        dbDelta( $sql_campaigns );
        dbDelta( $sql_offers );
        dbDelta( $sql_recipients );
        dbDelta( $sql_logs );
        dbDelta( $sql_activity );

        update_option( 'rejoyan_crm_db_version', REJOYAN_CRM_DB_VERSION );
        add_option( 'rejoyan_crm_sender_name', get_bloginfo( 'name' ) );
        add_option( 'rejoyan_crm_sender_email', get_option( 'admin_email' ) );
        add_option( 'rejoyan_crm_reply_to_email', get_option( 'admin_email' ) );
        add_option( 'rejoyan_crm_batch_size', 20 );
        add_option( 'rejoyan_crm_delete_data_on_uninstall', 'no' );
        add_option( 'rejoyan_crm_footer_text', get_bloginfo( 'name' ) );
        add_option( 'rejoyan_crm_default_accent_color', '#5b4cf0' );
        add_option( 'rejoyan_crm_vip_spend_threshold', 500 );
        add_option( 'rejoyan_crm_inactive_days', 90 );
        add_option( 'rejoyan_crm_recent_buyer_days', 30 );
        add_option( 'rejoyan_crm_new_customer_days', 30 );
    }

    public static function deactivate() {
        wp_clear_scheduled_hook( 'rejoyan_crm_prepare_campaign_batch' );
        wp_clear_scheduled_hook( 'rejoyan_crm_send_campaign_batch' );
        wp_clear_scheduled_hook( 'rejoyan_crm_dispatch_scheduled_campaign' );

        if ( function_exists( 'as_unschedule_all_actions' ) ) {
            as_unschedule_all_actions( 'rejoyan_crm_prepare_campaign_batch', array(), 'rejoyan-crm' );
            as_unschedule_all_actions( 'rejoyan_crm_send_campaign_batch', array(), 'rejoyan-crm' );
            as_unschedule_all_actions( 'rejoyan_crm_dispatch_scheduled_campaign', array(), 'rejoyan-crm' );
        }
    }
}
