<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Rejoyan_CRM_DB {
    public static function campaigns_table() {
        global $wpdb;
        return $wpdb->prefix . 'rejoyan_crm_campaigns';
    }

    public static function offers_table() {
        global $wpdb;
        return $wpdb->prefix . 'rejoyan_crm_offers';
    }

    public static function recipients_table() {
        global $wpdb;
        return $wpdb->prefix . 'rejoyan_crm_recipients';
    }

    public static function logs_table() {
        global $wpdb;
        return $wpdb->prefix . 'rejoyan_crm_logs';
    }

    public static function activity_table() {
        global $wpdb;
        return $wpdb->prefix . 'rejoyan_crm_activity';
    }

    public static function log_activity( $type, $message, $object_type = '', $object_id = 0, array $context = array() ) {
        global $wpdb;

        // Rejoyan CRM & Campaigns stores its audit trail in a dedicated custom table.
        $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Required write to a dedicated plugin table.
            self::activity_table(),
            array(
                'type'         => sanitize_key( $type ),
                'object_type'  => sanitize_key( $object_type ),
                'object_id'    => absint( $object_id ),
                'user_id'      => get_current_user_id(),
                'message'      => sanitize_text_field( $message ),
                'context_json' => wp_json_encode( $context ),
                'created_at'   => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%d', '%d', '%s', '%s' )
        );
    }

    public static function dashboard_stats() {
        global $wpdb;

        $cache_key = 'dashboard_stats';
        $cached    = wp_cache_get( $cache_key, 'rejoyan-crm' );
        if ( is_array( $cached ) ) {
            return $cached;
        }

        $campaigns = self::campaigns_table();
        $recipients = self::recipients_table();

        $customer_query = new WP_User_Query(
            array(
                'role'        => 'customer',
                'count_total' => true,
                'fields'      => 'ID',
                'number'      => 1,
            )
        );

        $campaign_count = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Cached aggregate from a dedicated plugin table.
            $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $campaigns )
        );
        $sent_count = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Cached aggregate from a dedicated plugin table.
            $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE status = %s', $recipients, 'sent' )
        );
        $failed_count = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Cached aggregate from a dedicated plugin table.
            $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE status = %s', $recipients, 'failed' )
        );

        $stats = array(
            'customers' => (int) $customer_query->get_total(),
            'campaigns' => $campaign_count,
            'sent'      => $sent_count,
            'failed'    => $failed_count,
        );
        wp_cache_set( $cache_key, $stats, 'rejoyan-crm', 30 );
        return $stats;
    }

    public static function invalidate_runtime_caches() {
        wp_cache_delete( 'dashboard_stats', 'rejoyan-crm' );
        wp_cache_delete( 'system_health_counts', 'rejoyan-crm' );
        wp_cache_delete( 'reports_overview', 'rejoyan-crm' );
        wp_cache_delete( 'reports_campaign_rows_100', 'rejoyan-crm' );
        wp_cache_delete( 'reports_campaign_rows_500', 'rejoyan-crm' );
        wp_cache_delete( 'reports_daily_sent_14', 'rejoyan-crm' );
    }
}
