<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Practical WooCommerce customer segmentation for Rejoyan CRM & Campaigns.
 *
 * Segments are intentionally deterministic and explainable. They use
 * WooCommerce customer/order APIs and cache each customer profile briefly
 * to keep repeated admin views and audience preparation lightweight.
 */
final class Rejoyan_CRM_Segments {
    const CACHE_SECONDS = 21600;

    public function definitions() {
        $currency = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '';
        $vip      = (float) get_option( 'rejoyan_crm_vip_spend_threshold', 500 );
        $inactive = max( 30, absint( get_option( 'rejoyan_crm_inactive_days', 90 ) ) );
        $recent   = max( 1, absint( get_option( 'rejoyan_crm_recent_buyer_days', 30 ) ) );
        $new_days = max( 1, absint( get_option( 'rejoyan_crm_new_customer_days', 30 ) ) );

        return array(
            'vip'          => array(
                'label'       => __( 'VIP customers', 'rejoyan-crm-campaigns-for-woocommerce' ),
                /* translators: 1: currency symbol, 2: formatted lifetime-spend threshold. */
                'description' => sprintf( __( 'Customers with lifetime spend of at least %1$s%2$s.', 'rejoyan-crm-campaigns-for-woocommerce' ), $currency, wc_format_localized_price( $vip ) ),
            ),
            'repeat'       => array(
                'label'       => __( 'Repeat buyers', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'description' => __( 'Customers with two or more orders.', 'rejoyan-crm-campaigns-for-woocommerce' ),
            ),
            'inactive'     => array(
                'label'       => __( 'Inactive customers', 'rejoyan-crm-campaigns-for-woocommerce' ),
                /* translators: %d: inactivity threshold in days. */
                'description' => sprintf( __( 'Customers whose latest paid order was at least %d days ago.', 'rejoyan-crm-campaigns-for-woocommerce' ), $inactive ),
            ),
            'new'          => array(
                'label'       => __( 'New customers', 'rejoyan-crm-campaigns-for-woocommerce' ),
                /* translators: %d: new-customer window in days. */
                'description' => sprintf( __( 'Customer accounts registered within the last %d days.', 'rejoyan-crm-campaigns-for-woocommerce' ), $new_days ),
            ),
            'no_orders'    => array(
                'label'       => __( 'No orders yet', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'description' => __( 'Registered customer accounts with no orders.', 'rejoyan-crm-campaigns-for-woocommerce' ),
            ),
            'recent_buyer' => array(
                'label'       => __( 'Recent buyers', 'rejoyan-crm-campaigns-for-woocommerce' ),
                /* translators: %d: recent-buyer window in days. */
                'description' => sprintf( __( 'Customers with a paid order within the last %d days.', 'rejoyan-crm-campaigns-for-woocommerce' ), $recent ),
            ),
        );
    }

    public function get_profile( $user_id, $force = false ) {
        $user_id = absint( $user_id );
        if ( ! $user_id ) {
            return array();
        }

        $cache_key = 'rejoyan_crm_segment_profile_' . $user_id;
        if ( ! $force ) {
            $cached = get_transient( $cache_key );
            if ( is_array( $cached ) ) {
                return $cached;
            }
        }

        $user = get_userdata( $user_id );
        if ( ! $user || ! in_array( 'customer', (array) $user->roles, true ) ) {
            return array();
        }

        $order_count = function_exists( 'wc_get_customer_order_count' ) ? absint( wc_get_customer_order_count( $user_id ) ) : 0;
        $total_spent = function_exists( 'wc_get_customer_total_spent' ) ? (float) wc_get_customer_total_spent( $user_id ) : 0.0;
        $last_order  = null;

        if ( $order_count > 0 && function_exists( 'wc_get_orders' ) ) {
            $orders = wc_get_orders(
                array(
                    'customer_id' => $user_id,
                    'status'      => function_exists( 'wc_get_is_paid_statuses' ) ? wc_get_is_paid_statuses() : array( 'processing', 'completed' ),
                    'limit'       => 1,
                    'orderby'     => 'date',
                    'order'       => 'DESC',
                    'return'      => 'objects',
                )
            );
            if ( ! empty( $orders[0] ) && is_a( $orders[0], 'WC_Order' ) ) {
                $date = $orders[0]->get_date_paid();
                if ( ! $date ) {
                    $date = $orders[0]->get_date_created();
                }
                if ( $date ) {
                    $last_order = $date->getTimestamp();
                }
            }
        }

        $registered = strtotime( (string) $user->user_registered );
        $profile    = array(
            'user_id'       => $user_id,
            'order_count'   => $order_count,
            'total_spent'   => $total_spent,
            'last_order_ts' => $last_order ? absint( $last_order ) : 0,
            'registered_ts' => $registered ? absint( $registered ) : 0,
        );

        set_transient( $cache_key, $profile, self::CACHE_SECONDS );
        return $profile;
    }

    public function matches( $user_id, $segment ) {
        $segment = sanitize_key( $segment );
        if ( ! isset( $this->definitions()[ $segment ] ) ) {
            return false;
        }

        $profile = $this->get_profile( $user_id );
        if ( empty( $profile ) ) {
            return false;
        }

        $now      = current_time( 'timestamp', true );
        $vip      = (float) get_option( 'rejoyan_crm_vip_spend_threshold', 500 );
        $inactive = max( 30, absint( get_option( 'rejoyan_crm_inactive_days', 90 ) ) );
        $recent   = max( 1, absint( get_option( 'rejoyan_crm_recent_buyer_days', 30 ) ) );
        $new_days = max( 1, absint( get_option( 'rejoyan_crm_new_customer_days', 30 ) ) );

        switch ( $segment ) {
            case 'vip':
                return $profile['total_spent'] >= $vip;
            case 'repeat':
                return $profile['order_count'] >= 2;
            case 'inactive':
                return $profile['order_count'] > 0 && $profile['last_order_ts'] > 0 && ( $now - $profile['last_order_ts'] ) >= ( DAY_IN_SECONDS * $inactive );
            case 'new':
                return $profile['registered_ts'] > 0 && ( $now - $profile['registered_ts'] ) <= ( DAY_IN_SECONDS * $new_days );
            case 'no_orders':
                return 0 === $profile['order_count'];
            case 'recent_buyer':
                return $profile['last_order_ts'] > 0 && ( $now - $profile['last_order_ts'] ) <= ( DAY_IN_SECONDS * $recent );
        }

        return false;
    }

    public function get_user_ids( $segment ) {
        $segment = sanitize_key( $segment );
        if ( ! isset( $this->definitions()[ $segment ] ) ) {
            return array();
        }

        $ids = get_users(
            array(
                'role'        => 'customer',
                'fields'      => 'ID',
                'number'      => -1,
                'count_total' => false,
            )
        );

        $matches = array();
        foreach ( $ids as $user_id ) {
            if ( $this->matches( $user_id, $segment ) ) {
                $matches[] = absint( $user_id );
            }
        }
        return $matches;
    }

    public function summary() {
        $cache_key = 'rejoyan_crm_segment_summary_v1';
        $cached    = get_transient( $cache_key );
        if ( is_array( $cached ) ) {
            return $cached;
        }

        $summary = array();
        foreach ( array_keys( $this->definitions() ) as $segment ) {
            $summary[ $segment ] = count( $this->get_user_ids( $segment ) );
        }
        set_transient( $cache_key, $summary, 10 * MINUTE_IN_SECONDS );
        return $summary;
    }

    public function invalidate_customer( $user_id ) {
        delete_transient( 'rejoyan_crm_segment_profile_' . absint( $user_id ) );
        delete_transient( 'rejoyan_crm_segment_summary_v1' );
    }

    public function invalidate_from_order( $order_id ) {
        if ( ! function_exists( 'wc_get_order' ) ) {
            return;
        }
        $order = wc_get_order( $order_id );
        if ( $order ) {
            $this->invalidate_customer( $order->get_customer_id() );
        }
    }
}
