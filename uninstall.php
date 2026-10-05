<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

if ( 'yes' !== get_option( 'rejoyan_crm_delete_data_on_uninstall', 'no' ) ) {
    return;
}

global $wpdb;
$rejoyan_crm_tables = array(
    $wpdb->prefix . 'rejoyan_crm_campaigns',
    $wpdb->prefix . 'rejoyan_crm_offers',
    $wpdb->prefix . 'rejoyan_crm_recipients',
    $wpdb->prefix . 'rejoyan_crm_logs',
    $wpdb->prefix . 'rejoyan_crm_activity',
);
foreach ( $rejoyan_crm_tables as $rejoyan_crm_table ) {
    // Full cleanup is explicitly opt-in. Clear Rejoyan CRM & Campaigns-owned data while leaving the schema intact.
    $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Explicit uninstall cleanup of plugin-owned data.
        $wpdb->prepare( 'DELETE FROM %i', $rejoyan_crm_table )
    );
}

$rejoyan_crm_options = array(
    'rejoyan_crm_db_version',
    'rejoyan_crm_sender_name',
    'rejoyan_crm_sender_email',
    'rejoyan_crm_reply_to_email',
    'rejoyan_crm_batch_size',
    'rejoyan_crm_footer_text',
    'rejoyan_crm_default_accent_color',
    'rejoyan_crm_vip_spend_threshold',
    'rejoyan_crm_inactive_days',
    'rejoyan_crm_recent_buyer_days',
    'rejoyan_crm_new_customer_days',
    'rejoyan_crm_delete_data_on_uninstall',
);
foreach ( $rejoyan_crm_options as $rejoyan_crm_option ) {
    delete_option( $rejoyan_crm_option );
}

// Remove Rejoyan CRM & Campaigns marketing preference metadata when full cleanup is explicitly enabled.
delete_metadata( 'user', 0, 'rejoyan_crm_marketing_optin', '', true );
delete_metadata( 'user', 0, 'rejoyan_crm_unsubscribed', '', true );
delete_transient( 'rejoyan_crm_queue_recovery_lock' );
delete_transient( 'rejoyan_crm_segment_summary_v1' );

$rejoyan_crm_segment_transient_like = $wpdb->esc_like( '_transient_rejoyan_crm_segment_profile_' ) . '%';
$rejoyan_crm_segment_timeout_like   = $wpdb->esc_like( '_transient_timeout_rejoyan_crm_segment_profile_' ) . '%';
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Wildcard cleanup is only possible against the options table during explicit uninstall.
    $wpdb->prepare(
        'DELETE FROM %i WHERE option_name LIKE %s OR option_name LIKE %s',
        $wpdb->options,
        $rejoyan_crm_segment_transient_like,
        $rejoyan_crm_segment_timeout_like
    )
);
