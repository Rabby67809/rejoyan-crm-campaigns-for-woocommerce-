<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Rejoyan_CRM_Privacy {
    const PAGE_SIZE = 100;

    public function register() {
        add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
        add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
        add_action( 'admin_init', array( $this, 'privacy_policy_content' ) );
    }

    public function register_exporter( $exporters ) {
        $exporters['rejoyan-crm-campaigns-for-woocommerce'] = array(
            'exporter_friendly_name' => __( 'Rejoyan CRM & Campaigns marketing data', 'rejoyan-crm-campaigns-for-woocommerce' ),
            'callback'               => array( $this, 'export_data' ),
        );
        return $exporters;
    }

    public function register_eraser( $erasers ) {
        $erasers['rejoyan-crm-campaigns-for-woocommerce'] = array(
            'eraser_friendly_name' => __( 'Rejoyan CRM & Campaigns marketing data', 'rejoyan-crm-campaigns-for-woocommerce' ),
            'callback'             => array( $this, 'erase_data' ),
        );
        return $erasers;
    }

    public function export_data( $email_address, $page = 1 ) {
        global $wpdb;

        $page   = max( 1, absint( $page ) );
        $offset = ( $page - 1 ) * self::PAGE_SIZE;
        $email  = sanitize_email( $email_address );
        $user   = $email ? get_user_by( 'email', $email ) : false;
        $data   = array();

        if ( 1 === $page && $user ) {
            $data[] = array(
                'group_id'    => 'rejoyan-crm-preferences',
                'group_label' => __( 'Rejoyan CRM & Campaigns marketing preferences', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'item_id'     => 'rejoyan-crm-user-' . $user->ID,
                'data'        => array(
                    array( 'name' => __( 'Marketing opt-in', 'rejoyan-crm-campaigns-for-woocommerce' ), 'value' => get_user_meta( $user->ID, 'rejoyan_crm_marketing_optin', true ) ),
                    array( 'name' => __( 'Unsubscribed', 'rejoyan-crm-campaigns-for-woocommerce' ), 'value' => get_user_meta( $user->ID, 'rejoyan_crm_unsubscribed', true ) ),
                ),
            );
        }

        $rows = array();
        if ( $email ) {
            $hash = hash( 'sha256', strtolower( $email ) );
            $rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Privacy export must read fresh recipient data from the dedicated plugin table.
                $wpdb->prepare(
                    'SELECT id,campaign_id,status,sent_at,created_at FROM %i WHERE email_hash = %s ORDER BY id ASC LIMIT %d OFFSET %d',
                    Rejoyan_CRM_DB::recipients_table(),
                    $hash,
                    self::PAGE_SIZE,
                    $offset
                )
            );
        }

        foreach ( $rows as $row ) {
            $data[] = array(
                'group_id'    => 'rejoyan-crm-campaign-history',
                'group_label' => __( 'Rejoyan CRM & Campaigns campaign history', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'item_id'     => 'rejoyan-crm-recipient-' . $row->id,
                'data'        => array(
                    array( 'name' => __( 'Campaign ID', 'rejoyan-crm-campaigns-for-woocommerce' ), 'value' => $row->campaign_id ),
                    array( 'name' => __( 'Status', 'rejoyan-crm-campaigns-for-woocommerce' ), 'value' => $row->status ),
                    array( 'name' => __( 'Created', 'rejoyan-crm-campaigns-for-woocommerce' ), 'value' => $row->created_at ),
                    array( 'name' => __( 'Sent', 'rejoyan-crm-campaigns-for-woocommerce' ), 'value' => $row->sent_at ),
                ),
            );
        }

        return array(
            'data' => $data,
            'done' => count( $rows ) < self::PAGE_SIZE,
        );
    }

    public function erase_data( $email_address, $page = 1 ) {
        global $wpdb;

        $page          = max( 1, absint( $page ) );
        $email         = sanitize_email( $email_address );
        $items_removed = false;
        $user          = $email ? get_user_by( 'email', $email ) : false;

        if ( 1 === $page && $user ) {
            delete_user_meta( $user->ID, 'rejoyan_crm_marketing_optin' );
            delete_user_meta( $user->ID, 'rejoyan_crm_unsubscribed' );
            $items_removed = true;
        }

        $rows = array();
        if ( $email ) {
            $hash = hash( 'sha256', strtolower( $email ) );
            $rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Privacy erasure must read fresh recipient data from the dedicated plugin table.
                $wpdb->prepare(
                    'SELECT id FROM %i WHERE email_hash = %s ORDER BY id ASC LIMIT %d',
                    Rejoyan_CRM_DB::recipients_table(),
                    $hash,
                    self::PAGE_SIZE
                )
            );
        }

        foreach ( $rows as $row ) {
            $anonymous = 'deleted-' . absint( $row->id ) . '@example.invalid';
            $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Privacy erasure writes to the dedicated plugin table and must not be cached.
                Rejoyan_CRM_DB::recipients_table(),
                array(
                    'email'         => $anonymous,
                    'email_hash'    => hash( 'sha256', $anonymous ),
                    'error_message' => '',
                    'user_id'       => 0,
                ),
                array( 'id' => absint( $row->id ) ),
                array( '%s', '%s', '%s', '%d' ),
                array( '%d' )
            );
            $items_removed = true;
        }

        return array(
            'items_removed'  => $items_removed,
            'items_retained' => false,
            'messages'       => array(),
            'done'           => count( $rows ) < self::PAGE_SIZE,
        );
    }

    public function privacy_policy_content() {
        if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
            return;
        }

        wp_add_privacy_policy_content(
            'Rejoyan CRM & Campaigns for WooCommerce',
            wp_kses_post(
                '<p>' . __( 'Rejoyan CRM & Campaigns may store marketing preferences and campaign delivery records for registered customers. The plugin does not send this information to a third-party service by itself. Site owners should document the legal basis used for marketing communications and configure their mail transport separately.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</p>'
            )
        );
    }
}
