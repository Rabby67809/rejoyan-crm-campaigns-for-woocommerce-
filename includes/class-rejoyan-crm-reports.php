<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Rejoyan_CRM_Reports {
    public function overview() {
        global $wpdb;

        $cache_key = 'reports_overview';
        $cached    = wp_cache_get( $cache_key, 'rejoyan-crm' );
        if ( is_array( $cached ) ) {
            return $cached;
        }

        $campaigns  = Rejoyan_CRM_DB::campaigns_table();
        $recipients = Rejoyan_CRM_DB::recipients_table();

        $totals = array(
            'campaigns' => (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Cached aggregate from a dedicated plugin table.
                $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $campaigns )
            ),
            'sent'      => 0,
            'failed'    => 0,
            'skipped'   => 0,
            'pending'   => 0,
        );

        $rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Cached aggregate from a dedicated plugin table.
            $wpdb->prepare( 'SELECT status, COUNT(*) AS total FROM %i GROUP BY status', $recipients )
        );
        foreach ( $rows as $row ) {
            if ( isset( $totals[ $row->status ] ) ) {
                $totals[ $row->status ] = (int) $row->total;
            }
        }

        $attempted               = $totals['sent'] + $totals['failed'] + $totals['skipped'];
        $totals['delivery_rate'] = $attempted > 0 ? round( ( $totals['sent'] / $attempted ) * 100, 1 ) : 0;

        wp_cache_set( $cache_key, $totals, 'rejoyan-crm', 30 );
        return $totals;
    }

    public function campaign_rows( $limit = 100 ) {
        global $wpdb;

        $limit     = max( 1, min( 500, absint( $limit ) ) );
        $cache_key = 'reports_campaign_rows_' . $limit;
        $cached    = wp_cache_get( $cache_key, 'rejoyan-crm' );
        if ( is_array( $cached ) ) {
            return $cached;
        }

        $campaigns  = Rejoyan_CRM_DB::campaigns_table();
        $recipients = Rejoyan_CRM_DB::recipients_table();

        $rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Cached report query from dedicated plugin tables.
            $wpdb->prepare(
                "SELECT c.id,c.name,c.campaign_type,c.status,c.total_count,c.sent_count,c.failed_count,c.skipped_count,c.started_at,c.completed_at,c.created_at,
                (SELECT COUNT(*) FROM %i r WHERE r.campaign_id = c.id AND r.status = 'pending') AS pending_count
                FROM %i c
                ORDER BY c.id DESC LIMIT %d",
                $recipients,
                $campaigns,
                $limit
            )
        );
        wp_cache_set( $cache_key, $rows, 'rejoyan-crm', 30 );
        return $rows;
    }

    public function daily_sent( $days = 14 ) {
        global $wpdb;

        $days      = max( 7, min( 90, absint( $days ) ) );
        $cache_key = 'reports_daily_sent_' . $days;
        $cached    = wp_cache_get( $cache_key, 'rejoyan-crm' );
        if ( is_array( $cached ) ) {
            return $cached;
        }

        $recipients = Rejoyan_CRM_DB::recipients_table();
        $now        = current_time( 'timestamp' );
        $since      = wp_date( 'Y-m-d H:i:s', $now - ( $days * DAY_IN_SECONDS ) );

        $rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Cached report query from a dedicated plugin table.
            $wpdb->prepare(
                "SELECT DATE(sent_at) AS send_date, COUNT(*) AS total FROM %i WHERE status = 'sent' AND sent_at >= %s GROUP BY DATE(sent_at) ORDER BY send_date ASC",
                $recipients,
                get_date_from_gmt( $since )
            )
        );

        $indexed = array();
        foreach ( $rows as $row ) {
            $indexed[ (string) $row->send_date ] = (int) $row->total;
        }

        $series = array();
        for ( $i = $days - 1; $i >= 0; $i-- ) {
            $date     = wp_date( 'Y-m-d', $now - ( $i * DAY_IN_SECONDS ) );
            $series[] = array(
                'date'  => $date,
                'label' => wp_date( 'M j', strtotime( $date . ' 00:00:00' ) ),
                'sent'  => isset( $indexed[ $date ] ) ? $indexed[ $date ] : 0,
            );
        }

        wp_cache_set( $cache_key, $series, 'rejoyan-crm', 30 );
        return $series;
    }
}
