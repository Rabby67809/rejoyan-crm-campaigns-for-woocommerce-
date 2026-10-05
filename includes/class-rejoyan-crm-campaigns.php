<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * Campaign queue state lives in Rejoyan CRM & Campaigns-owned custom tables. These reads/writes
 * intentionally use $wpdb because WordPress has no CRUD API for arbitrary plugin
 * tables, and queue operations require transaction-fresh data rather than caches.
 */
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

final class Rejoyan_CRM_Campaigns {
    private $mailer;
    private $campaigns_table;
    private $recipients_table;
    private $segments;

    public function __construct( Rejoyan_CRM_Mailer $mailer, Rejoyan_CRM_Segments $segments ) {
        $this->mailer           = $mailer;
        $this->segments         = $segments;
        $this->campaigns_table  = Rejoyan_CRM_DB::campaigns_table();
        $this->recipients_table = Rejoyan_CRM_DB::recipients_table();
    }

    public function create_campaign( array $data ) {
        global $wpdb;

        $name          = sanitize_text_field( $data['name'] ?? '' );
        $subject       = sanitize_text_field( $data['subject'] ?? '' );
        $content       = wp_kses_post( $data['content'] ?? '' );
        $requested_audience = sanitize_key( $data['audience'] ?? 'optin' );
        $audience           = in_array( $requested_audience, array( 'optin', 'all_registered', 'selected' ), true ) ? $requested_audience : 'optin';
        if ( 0 === strpos( $requested_audience, 'segment_' ) ) {
            $segment_key = substr( $requested_audience, 8 );
            if ( isset( $this->segments->definitions()[ $segment_key ] ) ) {
                $audience = $requested_audience;
            }
        } elseif ( preg_match( '/^product_buyer_(\d+)$/', $requested_audience, $matches ) && wc_get_product( absint( $matches[1] ) ) ) {
            $audience = 'product_buyer_' . absint( $matches[1] );
        }
        $campaign_type = in_array( $data['campaign_type'] ?? 'standard', array( 'standard', 'offer' ), true ) ? $data['campaign_type'] : 'standard';
        $offer_id      = absint( $data['offer_id'] ?? 0 );

        if ( '' === $name || '' === $subject || '' === trim( wp_strip_all_tags( $content ) ) ) {
            return new WP_Error( 'rejoyan_crm_campaign_invalid', __( 'Campaign name, subject and content are required.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }

        $now = current_time( 'mysql' );
        $ok  = $wpdb->insert(
            $this->campaigns_table,
            array(
                'name'          => $name,
                'subject'       => $subject,
                'content'       => $content,
                'audience'      => $audience,
                'campaign_type' => $campaign_type,
                'offer_id'      => $offer_id,
                'status'        => 'draft',
                'created_by'    => get_current_user_id(),
                'created_at'    => $now,
                'updated_at'    => $now,
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s' )
        );

        if ( false === $ok ) {
            return new WP_Error( 'rejoyan_crm_campaign_db', __( 'The campaign could not be saved.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }

        $campaign_id = (int) $wpdb->insert_id;
        Rejoyan_CRM_DB::invalidate_runtime_caches();
        Rejoyan_CRM_DB::log_activity( 'campaign_created', __( 'Campaign created.', 'rejoyan-crm-campaigns-for-woocommerce' ), 'campaign', $campaign_id );
        return $campaign_id;
    }

    public function parse_schedule_input( $value ) {
        $value = sanitize_text_field( (string) $value );
        if ( '' === $value ) {
            return 0;
        }
        $date = DateTimeImmutable::createFromFormat( 'Y-m-d\TH:i', $value, wp_timezone() );
        if ( ! $date ) {
            return new WP_Error( 'rejoyan_crm_schedule_invalid', __( 'The scheduled date/time is invalid.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }
        $timestamp = $date->getTimestamp();
        return $timestamp > ( time() + 30 ) ? $timestamp : 0;
    }

    public function create_offer_campaign( $offer, array $user_ids = array(), $send_all = false, $segment = '', $scheduled_timestamp = 0 ) {
        $segment     = sanitize_key( $segment );
        $definitions = $this->segments->definitions();
        $audience    = $send_all ? 'all_registered' : 'selected';
        if ( $segment && isset( $definitions[ $segment ] ) ) {
            $audience = 'segment_' . $segment;
        }

        $campaign_id = $this->create_campaign(
            array(
                /* translators: %s: saved offer title. */
                'name'          => sprintf( __( 'Offer: %s', 'rejoyan-crm-campaigns-for-woocommerce' ), $offer->title ),
                'subject'       => $offer->subject,
                'content'       => $offer->description,
                'audience'      => $audience,
                'campaign_type' => 'offer',
                'offer_id'      => absint( $offer->id ),
            )
        );

        if ( is_wp_error( $campaign_id ) ) {
            return $campaign_id;
        }

        if ( $send_all || $segment ) {
            $queued = $this->queue_campaign( $campaign_id, $scheduled_timestamp );
            return is_wp_error( $queued ) ? $queued : $campaign_id;
        }

        $queued = $this->queue_selected_campaign( $campaign_id, $user_ids, $scheduled_timestamp );
        return is_wp_error( $queued ) ? $queued : $campaign_id;
    }

    public function queue_selected_campaign( $campaign_id, array $user_ids, $scheduled_timestamp = 0 ) {
        global $wpdb;

        $campaign = $this->get_campaign( $campaign_id );
        if ( ! $campaign ) {
            return new WP_Error( 'rejoyan_crm_campaign_missing', __( 'Campaign not found.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }

        $user_ids = array_values( array_unique( array_filter( array_map( 'absint', $user_ids ) ) ) );
        if ( ! $user_ids ) {
            return new WP_Error( 'rejoyan_crm_no_recipients', __( 'Select at least one customer.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }

        $wpdb->delete( $this->recipients_table, array( 'campaign_id' => absint( $campaign_id ) ), array( '%d' ) );
        $added = 0;
        foreach ( $user_ids as $user_id ) {
            $user = get_userdata( $user_id );
            if ( ! $user || ! in_array( 'customer', (array) $user->roles, true ) ) {
                continue;
            }
            if ( '1' === (string) get_user_meta( $user_id, 'rejoyan_crm_unsubscribed', true ) ) {
                continue;
            }
            $email = sanitize_email( $user->user_email );
            if ( ! is_email( $email ) ) {
                continue;
            }

            $inserted = $wpdb->query(
                $wpdb->prepare(
                    "INSERT IGNORE INTO %i (campaign_id,user_id,email,email_hash,status,created_at) VALUES (%d,%d,%s,%s,'pending',%s)",
                    $this->recipients_table,
                    absint( $campaign_id ),
                    $user_id,
                    $email,
                    hash( 'sha256', strtolower( $email ) ),
                    current_time( 'mysql' )
                )
            );
            if ( $inserted ) {
                $added++;
            }
        }

        if ( 0 === $added ) {
            return new WP_Error( 'rejoyan_crm_no_eligible_recipients', __( 'No eligible customer email addresses were selected.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }

        $scheduled_timestamp = absint( $scheduled_timestamp );
        $is_scheduled        = $scheduled_timestamp > ( time() + 30 );
        $wpdb->update(
            $this->campaigns_table,
            array(
                'status'          => $is_scheduled ? 'scheduled' : 'queued',
                'total_count'     => $added,
                'processed_count' => 0,
                'sent_count'      => 0,
                'failed_count'    => 0,
                'skipped_count'   => 0,
                'scheduled_at'    => $is_scheduled ? wp_date( 'Y-m-d H:i:s', $scheduled_timestamp, wp_timezone() ) : null,
                'updated_at'      => current_time( 'mysql' ),
            ),
            array( 'id' => absint( $campaign_id ) ),
            array( '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s' ),
            array( '%d' )
        );
        $wpdb->query(
            $wpdb->prepare(
                'UPDATE %i SET started_at = NULL, completed_at = NULL WHERE id = %d',
                $this->campaigns_table,
                absint( $campaign_id )
            )
        );

        Rejoyan_CRM_DB::invalidate_runtime_caches();
        if ( $is_scheduled ) {
            Rejoyan_CRM_DB::invalidate_runtime_caches();
            $this->schedule_action_at( 'rejoyan_crm_dispatch_scheduled_campaign', array( absint( $campaign_id ) ), $scheduled_timestamp );
        } else {
            $this->schedule_action( 'rejoyan_crm_send_campaign_batch', array( absint( $campaign_id ) ) );
        }
        Rejoyan_CRM_DB::log_activity( $is_scheduled ? 'offer_campaign_scheduled' : 'offer_campaign_queued', __( 'Offer email campaign queued for selected customers.', 'rejoyan-crm-campaigns-for-woocommerce' ), 'campaign', $campaign_id, array( 'recipients' => $added ) );
        return true;
    }

    public function get_campaign( $campaign_id ) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $this->campaigns_table, absint( $campaign_id ) )
        );
    }

    public function get_campaigns( $limit = 50 ) {
        global $wpdb;
        $limit = max( 1, min( 100, absint( $limit ) ) );
        return $wpdb->get_results(
            $wpdb->prepare( 'SELECT * FROM %i ORDER BY id DESC LIMIT %d', $this->campaigns_table, $limit )
        );
    }

    public function queue_campaign( $campaign_id, $scheduled_timestamp = 0 ) {
        global $wpdb;

        $campaign = $this->get_campaign( $campaign_id );
        if ( ! $campaign ) {
            return new WP_Error( 'rejoyan_crm_campaign_missing', __( 'Campaign not found.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }
        if ( ! in_array( $campaign->status, array( 'draft', 'cancelled', 'completed', 'scheduled' ), true ) ) {
            return new WP_Error( 'rejoyan_crm_campaign_busy', __( 'This campaign is already queued or sending.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }

        $scheduled_timestamp = absint( $scheduled_timestamp );
        if ( $scheduled_timestamp > ( time() + 30 ) ) {
            $wpdb->delete( $this->recipients_table, array( 'campaign_id' => absint( $campaign_id ) ), array( '%d' ) );
            $wpdb->update(
                $this->campaigns_table,
                array(
                    'status'          => 'scheduled',
                    'scheduled_at'    => wp_date( 'Y-m-d H:i:s', $scheduled_timestamp, wp_timezone() ),
                    'total_count'     => 0,
                    'processed_count' => 0,
                    'sent_count'      => 0,
                    'failed_count'    => 0,
                    'skipped_count'   => 0,
                    'updated_at'      => current_time( 'mysql' ),
                ),
                array( 'id' => absint( $campaign_id ) ),
                array( '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s' ),
                array( '%d' )
            );
            $this->schedule_action_at( 'rejoyan_crm_dispatch_scheduled_campaign', array( absint( $campaign_id ) ), $scheduled_timestamp );
            Rejoyan_CRM_DB::log_activity( 'campaign_scheduled', __( 'Campaign scheduled.', 'rejoyan-crm-campaigns-for-woocommerce' ), 'campaign', $campaign_id );
            return true;
        }

        $wpdb->delete( $this->recipients_table, array( 'campaign_id' => absint( $campaign_id ) ), array( '%d' ) );
        $wpdb->update(
            $this->campaigns_table,
            array(
                'status'          => 'preparing',
                'total_count'     => 0,
                'processed_count' => 0,
                'sent_count'      => 0,
                'failed_count'    => 0,
                'skipped_count'   => 0,
                'scheduled_at'    => null,
                'updated_at'      => current_time( 'mysql' ),
            ),
            array( 'id' => absint( $campaign_id ) ),
            array( '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s' ),
            array( '%d' )
        );
        $wpdb->query(
            $wpdb->prepare(
                'UPDATE %i SET started_at = NULL, completed_at = NULL WHERE id = %d',
                $this->campaigns_table,
                absint( $campaign_id )
            )
        );

        Rejoyan_CRM_DB::invalidate_runtime_caches();
        $this->schedule_action( 'rejoyan_crm_prepare_campaign_batch', array( absint( $campaign_id ), 0 ) );
        Rejoyan_CRM_DB::log_activity( 'campaign_queued', __( 'Campaign preparation queued.', 'rejoyan-crm-campaigns-for-woocommerce' ), 'campaign', $campaign_id );
        return true;
    }

    public function cancel_campaign( $campaign_id ) {
        global $wpdb;

        $campaign = $this->get_campaign( $campaign_id );
        if ( ! $campaign ) {
            return new WP_Error( 'rejoyan_crm_campaign_missing', __( 'Campaign not found.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }
        if ( 'completed' === $campaign->status ) {
            return new WP_Error( 'rejoyan_crm_campaign_completed', __( 'Completed campaigns cannot be cancelled.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }

        $wpdb->update(
            $this->campaigns_table,
            array( 'status' => 'cancelled', 'updated_at' => current_time( 'mysql' ) ),
            array( 'id' => absint( $campaign_id ) ),
            array( '%s', '%s' ),
            array( '%d' )
        );
        Rejoyan_CRM_DB::invalidate_runtime_caches();
        Rejoyan_CRM_DB::log_activity( 'campaign_cancelled', __( 'Campaign cancelled.', 'rejoyan-crm-campaigns-for-woocommerce' ), 'campaign', $campaign_id );
        return true;
    }

    public function prepare_batch( $campaign_id, $offset = 0 ) {
        global $wpdb;

        $campaign = $this->get_campaign( $campaign_id );
        if ( ! $campaign || 'preparing' !== $campaign->status ) {
            return;
        }

        $chunk = 250;
        $args  = array(
            'role'    => 'customer',
            'orderby' => 'ID',
            'order'   => 'ASC',
            'number'  => $chunk,
            'offset'  => absint( $offset ),
            'fields'  => 'all',
        );

        $users            = get_users( $args );
        $segment_key      = 0 === strpos( (string) $campaign->audience, 'segment_' ) ? substr( (string) $campaign->audience, 8 ) : '';
        $product_buyer_id = preg_match( '/^product_buyer_(\d+)$/', (string) $campaign->audience, $buyer_match ) ? absint( $buyer_match[1] ) : 0;
        foreach ( $users as $user ) {
            if ( 'optin' === $campaign->audience && '1' !== (string) get_user_meta( $user->ID, 'rejoyan_crm_marketing_optin', true ) ) {
                continue;
            }
            if ( $segment_key && ! $this->segments->matches( $user->ID, $segment_key ) ) {
                continue;
            }
            if ( $product_buyer_id && function_exists( 'wc_customer_bought_product' ) && ! wc_customer_bought_product( $user->user_email, $user->ID, $product_buyer_id ) ) {
                continue;
            }
            if ( '1' === (string) get_user_meta( $user->ID, 'rejoyan_crm_unsubscribed', true ) ) {
                continue;
            }
            $email = sanitize_email( $user->user_email );
            if ( ! is_email( $email ) ) {
                continue;
            }

            $wpdb->query(
                $wpdb->prepare(
                    "INSERT IGNORE INTO %i (campaign_id,user_id,email,email_hash,status,created_at) VALUES (%d,%d,%s,%s,'pending',%s)",
                    $this->recipients_table,
                    absint( $campaign_id ),
                    absint( $user->ID ),
                    $email,
                    hash( 'sha256', strtolower( $email ) ),
                    current_time( 'mysql' )
                )
            );
        }

        $wpdb->update(
            $this->campaigns_table,
            array( 'updated_at' => current_time( 'mysql' ) ),
            array( 'id' => absint( $campaign_id ) ),
            array( '%s' ),
            array( '%d' )
        );

        if ( count( $users ) === $chunk ) {
            $this->schedule_action( 'rejoyan_crm_prepare_campaign_batch', array( absint( $campaign_id ), absint( $offset ) + $chunk ) );
            return;
        }

        $total = (int) $wpdb->get_var(
            $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE campaign_id = %d', $this->recipients_table, absint( $campaign_id ) )
        );

        $wpdb->update(
            $this->campaigns_table,
            array(
                'status'      => 'queued',
                'total_count' => $total,
                'updated_at'  => current_time( 'mysql' ),
            ),
            array( 'id' => absint( $campaign_id ) ),
            array( '%s', '%d', '%s' ),
            array( '%d' )
        );

        Rejoyan_CRM_DB::invalidate_runtime_caches();
        if ( $total > 0 ) {
            $this->schedule_action( 'rejoyan_crm_send_campaign_batch', array( absint( $campaign_id ) ) );
        } else {
            $this->complete_campaign( $campaign_id );
        }
    }

    public function send_batch( $campaign_id ) {
        global $wpdb;

        $campaign = $this->get_campaign( $campaign_id );
        if ( ! $campaign || ! in_array( $campaign->status, array( 'queued', 'sending' ), true ) ) {
            return;
        }

        $batch_size = max( 5, min( 100, absint( get_option( 'rejoyan_crm_batch_size', 20 ) ) ) );
        $recipients = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM %i WHERE campaign_id = %d AND status = 'pending' ORDER BY id ASC LIMIT %d",
                $this->recipients_table,
                absint( $campaign_id ),
                $batch_size
            )
        );

        if ( ! $recipients ) {
            $this->complete_campaign( $campaign_id );
            return;
        }

        if ( 'queued' === $campaign->status ) {
            $wpdb->update(
                $this->campaigns_table,
                array( 'status' => 'sending', 'started_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ),
                array( 'id' => absint( $campaign_id ) ),
                array( '%s', '%s', '%s' ),
                array( '%d' )
            );
        }

        foreach ( $recipients as $recipient ) {
            $fresh_campaign = $this->get_campaign( $campaign_id );
            if ( ! $fresh_campaign || 'cancelled' === $fresh_campaign->status ) {
                return;
            }

            $user = get_userdata( absint( $recipient->user_id ) );
            if ( ! $user || '1' === (string) get_user_meta( $recipient->user_id, 'rejoyan_crm_unsubscribed', true ) ) {
                $this->mark_recipient( $recipient->id, 'skipped', __( 'Recipient is unavailable or unsubscribed.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
                continue;
            }

            $result = $this->mailer->send_campaign_to_user( $fresh_campaign, $user );
            if ( is_wp_error( $result ) ) {
                $this->mark_recipient( $recipient->id, 'failed', $result->get_error_message() );
            } else {
                $this->mark_recipient( $recipient->id, 'sent', '' );
            }
        }

        $pending = (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE campaign_id = %d AND status = 'pending'", $this->recipients_table, absint( $campaign_id ) )
        );

        if ( $pending > 0 ) {
            $this->schedule_action( 'rejoyan_crm_send_campaign_batch', array( absint( $campaign_id ) ) );
        } else {
            $this->complete_campaign( $campaign_id );
        }
    }

    public function dispatch_scheduled_campaign( $campaign_id ) {
        global $wpdb;
        $campaign = $this->get_campaign( $campaign_id );
        if ( ! $campaign || 'scheduled' !== $campaign->status ) {
            return;
        }
        $selected_count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE campaign_id = %d', $this->recipients_table, absint( $campaign_id ) ) );
        if ( 'selected' === $campaign->audience && $selected_count > 0 ) {
            $wpdb->update( $this->campaigns_table, array( 'status' => 'queued', 'scheduled_at' => null, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => absint( $campaign_id ) ), array( '%s', '%s', '%s' ), array( '%d' ) );
            $this->schedule_action( 'rejoyan_crm_send_campaign_batch', array( absint( $campaign_id ) ) );
            return;
        }
        $this->queue_campaign( $campaign_id, 0 );
    }

    public function retry_failed( $campaign_id ) {
        global $wpdb;
        $campaign = $this->get_campaign( $campaign_id );
        if ( ! $campaign ) {
            return new WP_Error( 'rejoyan_crm_campaign_missing', __( 'Campaign not found.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }
        if ( in_array( $campaign->status, array( 'preparing', 'queued', 'sending', 'scheduled' ), true ) ) {
            return new WP_Error( 'rejoyan_crm_campaign_busy', __( 'Wait until the current campaign run is finished before retrying failed recipients.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }
        $failed = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE campaign_id = %d AND status = 'failed'", $this->recipients_table, absint( $campaign_id ) ) );
        if ( $failed < 1 ) {
            return new WP_Error( 'rejoyan_crm_no_failed', __( 'This campaign has no failed recipients to retry.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }
        $wpdb->query( $wpdb->prepare( "UPDATE %i SET status='pending', error_message='', sent_at=NULL WHERE campaign_id=%d AND status='failed'", $this->recipients_table, absint( $campaign_id ) ) );
        $wpdb->query( $wpdb->prepare( "UPDATE %i SET status='queued', processed_count=GREATEST(0,processed_count-%d), failed_count=GREATEST(0,failed_count-%d), completed_at=NULL, updated_at=%s WHERE id=%d", $this->campaigns_table, $failed, $failed, current_time( 'mysql' ), absint( $campaign_id ) ) );
        Rejoyan_CRM_DB::invalidate_runtime_caches();
        $this->schedule_action( 'rejoyan_crm_send_campaign_batch', array( absint( $campaign_id ) ) );
        Rejoyan_CRM_DB::log_activity( 'campaign_retry_failed', __( 'Failed campaign recipients queued for retry.', 'rejoyan-crm-campaigns-for-woocommerce' ), 'campaign', $campaign_id, array( 'recipients' => $failed ) );
        return $failed;
    }

    public function send_test( $campaign_id, $email ) {
        $campaign = $this->get_campaign( $campaign_id );
        if ( ! $campaign ) {
            return new WP_Error( 'rejoyan_crm_campaign_missing', __( 'Campaign not found.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }
        return $this->mailer->send_test( $campaign, $email );
    }

    public function recipient_stats( $campaign_id ) {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT status, COUNT(*) AS total FROM %i WHERE campaign_id = %d GROUP BY status',
                $this->recipients_table,
                absint( $campaign_id )
            )
        );
        $stats = array( 'pending' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0 );
        foreach ( $rows as $row ) {
            if ( isset( $stats[ $row->status ] ) ) {
                $stats[ $row->status ] = (int) $row->total;
            }
        }
        return $stats;
    }

    private function mark_recipient( $recipient_id, $status, $message ) {
        global $wpdb;

        $recipient = $wpdb->get_row(
            $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $this->recipients_table, absint( $recipient_id ) )
        );
        if ( ! $recipient || 'pending' !== $recipient->status ) {
            return;
        }

        $data = array(
            'status'        => sanitize_key( $status ),
            'error_message' => sanitize_text_field( $message ),
        );
        $format = array( '%s', '%s' );
        if ( 'sent' === $status ) {
            $data['sent_at'] = current_time( 'mysql' );
            $format[]        = '%s';
        }

        $updated = $wpdb->update( $this->recipients_table, $data, array( 'id' => absint( $recipient_id ), 'status' => 'pending' ), $format, array( '%d', '%s' ) );
        if ( ! $updated ) {
            return;
        }

        $column = 'sent_count';
        if ( 'failed' === $status ) {
            $column = 'failed_count';
        } elseif ( 'skipped' === $status ) {
            $column = 'skipped_count';
        }

        $wpdb->query(
            $wpdb->prepare(
                'UPDATE %i SET processed_count = processed_count + 1, %i = %i + 1, updated_at = %s WHERE id = %d',
                $this->campaigns_table,
                $column,
                $column,
                current_time( 'mysql' ),
                absint( $recipient->campaign_id )
            )
        );
        Rejoyan_CRM_DB::invalidate_runtime_caches();
    }

    private function complete_campaign( $campaign_id ) {
        global $wpdb;
        $campaign = $this->get_campaign( $campaign_id );
        if ( ! $campaign || 'cancelled' === $campaign->status ) {
            return;
        }

        $wpdb->update(
            $this->campaigns_table,
            array(
                'status'       => 'completed',
                'completed_at' => current_time( 'mysql' ),
                'updated_at'   => current_time( 'mysql' ),
            ),
            array( 'id' => absint( $campaign_id ) ),
            array( '%s', '%s', '%s' ),
            array( '%d' )
        );
        Rejoyan_CRM_DB::invalidate_runtime_caches();
        Rejoyan_CRM_DB::log_activity( 'campaign_completed', __( 'Campaign completed.', 'rejoyan-crm-campaigns-for-woocommerce' ), 'campaign', $campaign_id );
    }

    private function schedule_action_at( $hook, array $args, $timestamp ) {
        $timestamp = max( time() + 5, absint( $timestamp ) );
        if ( function_exists( 'as_schedule_single_action' ) ) {
            as_schedule_single_action( $timestamp, $hook, $args, 'rejoyan-crm', false );
            return;
        }
        wp_schedule_single_event( $timestamp, $hook, $args );
    }

    private function schedule_action( $hook, array $args ) {
        if ( function_exists( 'as_enqueue_async_action' ) ) {
            as_enqueue_async_action( $hook, $args, 'rejoyan-crm' );
            return;
        }
        wp_schedule_single_event( time() + 5, $hook, $args );
    }

    public function recover_stalled_campaigns() {
        global $wpdb;

        if ( get_transient( 'rejoyan_crm_queue_recovery_lock' ) ) {
            return;
        }
        set_transient( 'rejoyan_crm_queue_recovery_lock', '1', 5 * MINUTE_IN_SECONDS );

        $cutoff = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp', true ) - ( 10 * MINUTE_IN_SECONDS ) );
        $rows   = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id,status FROM %i WHERE status IN ('preparing','queued','sending') AND updated_at < %s ORDER BY id ASC LIMIT 10",
                $this->campaigns_table,
                get_date_from_gmt( $cutoff )
            )
        );

        foreach ( $rows as $row ) {
            if ( 'preparing' === $row->status ) {
                $this->schedule_action( 'rejoyan_crm_prepare_campaign_batch', array( absint( $row->id ), 0 ) );
            } else {
                $this->schedule_action( 'rejoyan_crm_send_campaign_batch', array( absint( $row->id ) ) );
            }
        }
    }

    public function handle_unsubscribe() {
        // Public email unsubscribe links use a per-user HMAC-style token instead of a logged-in WordPress nonce.
        $user_id = isset( $_GET['uid'] ) ? absint( $_GET['uid'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $token   = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $user    = $user_id ? get_userdata( $user_id ) : false;

        if ( ! $user || ! $token || ! hash_equals( $this->mailer->unsubscribe_token( $user ), $token ) ) {
            wp_die( esc_html__( 'This unsubscribe link is invalid.', 'rejoyan-crm-campaigns-for-woocommerce' ), esc_html__( 'Invalid link', 'rejoyan-crm-campaigns-for-woocommerce' ), array( 'response' => 400 ) );
        }

        update_user_meta( $user_id, 'rejoyan_crm_unsubscribed', '1' );
        update_user_meta( $user_id, 'rejoyan_crm_marketing_optin', '0' );
        Rejoyan_CRM_DB::log_activity( 'customer_unsubscribed', __( 'Customer unsubscribed from marketing.', 'rejoyan-crm-campaigns-for-woocommerce' ), 'user', $user_id );

        wp_die(
            '<h1>' . esc_html__( 'You are unsubscribed.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</h1><p>' . esc_html__( 'You will no longer receive Rejoyan CRM & Campaigns marketing emails from this site.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</p>',
            esc_html__( 'Unsubscribed', 'rejoyan-crm-campaigns-for-woocommerce' ),
            array( 'response' => 200 )
        );
    }
}
