<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Deferred admin data renderer.
 *
 * Rejoyan CRM & Campaigns admin pages render a lightweight shell first. Expensive WooCommerce
 * and customer queries are requested only after the browser has painted the page.
 * This keeps navigation responsive while preserving the full feature set.
 */
final class Rejoyan_CRM_Admin_Lazy {
    private $campaigns;
    private $offers;
    private $reports;
    private $segments;

    public function __construct( Rejoyan_CRM_Campaigns $campaigns, Rejoyan_CRM_Offers $offers, Rejoyan_CRM_Reports $reports, Rejoyan_CRM_Segments $segments ) {
        $this->campaigns = $campaigns;
        $this->offers    = $offers;
        $this->reports   = $reports;
        $this->segments  = $segments;

        add_action( 'wp_ajax_rejoyan_crm_lazy_panel', array( $this, 'handle_panel' ) );
        add_action( 'wp_ajax_rejoyan_crm_social_products', array( $this, 'handle_social_products' ) );
    }

    public function placeholder( $panel, $min_height = 260 ) {
        $panel = sanitize_key( $panel );
        echo '<div class="rejoyan-crm-lazy" data-rejoyan-crm-lazy="' . esc_attr( $panel ) . '" style="--mf-lazy-min-height:' . esc_attr( absint( $min_height ) ) . 'px">';
        echo '<div class="rejoyan-crm-skeleton-grid" aria-hidden="true">';
        echo '<span class="rejoyan-crm-skeleton-card"></span><span class="rejoyan-crm-skeleton-card"></span><span class="rejoyan-crm-skeleton-card"></span>';
        echo '</div><span class="screen-reader-text">' . esc_html__( 'Loading Rejoyan CRM & Campaigns data…', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span></div>';
    }

    private function guard_ajax() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to load this data.', 'rejoyan-crm-campaigns-for-woocommerce' ) ), 403 );
        }
    }

    public function handle_panel() {
        $this->guard_ajax();
        check_ajax_referer( 'rejoyan_crm_lazy_panel', 'nonce' );
        $panel = isset( $_POST['panel'] ) ? sanitize_key( wp_unslash( $_POST['panel'] ) ) : '';

        ob_start();
        switch ( $panel ) {
            case 'dashboard':
                $this->render_dashboard();
                break;
            case 'customers':
                $offer_id = isset( $_POST['offer_id'] ) ? absint( $_POST['offer_id'] ) : 0;
                $this->render_customers( $offer_id );
                break;
            case 'segments':
                $this->render_segments();
                break;
            case 'campaign-history':
                $this->render_campaign_history();
                break;
            case 'social-products':
                $this->render_social_products();
                break;
            case 'analytics':
                $this->render_analytics();
                break;
            case 'invoices':
                $this->render_invoices();
                break;
            default:
                ob_end_clean();
                wp_send_json_error( array( 'message' => __( 'Unknown Rejoyan CRM & Campaigns panel.', 'rejoyan-crm-campaigns-for-woocommerce' ) ), 400 );
        }

        $html = ob_get_clean();
        wp_send_json_success( array( 'html' => $html ) );
    }

    private function stat_card( $label, $value, $icon, $helper = '' ) {
        echo '<section class="rejoyan-crm-card rejoyan-crm-stat"><span class="dashicons ' . esc_attr( $icon ) . '"></span><div><strong>' . esc_html( $value ) . '</strong><small>' . esc_html( $label ) . '</small>';
        if ( $helper ) {
            echo '<em>' . esc_html( $helper ) . '</em>';
        }
        echo '</div></section>';
    }

    private function render_dashboard() {
        $stats  = Rejoyan_CRM_DB::dashboard_stats();
        $recent = $this->campaigns->get_campaigns( 6 );
        $offers = $this->offers->get_offers( 1 );

        echo '<div class="rejoyan-crm-grid rejoyan-crm-stats">';
        $this->stat_card( __( 'Customers', 'rejoyan-crm-campaigns-for-woocommerce' ), number_format_i18n( $stats['customers'] ), 'dashicons-groups', __( 'registered accounts', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        $this->stat_card( __( 'Campaigns', 'rejoyan-crm-campaigns-for-woocommerce' ), number_format_i18n( $stats['campaigns'] ), 'dashicons-email-alt2', __( 'all time', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        $this->stat_card( __( 'Emails sent', 'rejoyan-crm-campaigns-for-woocommerce' ), number_format_i18n( $stats['sent'] ), 'dashicons-yes-alt', __( 'delivery handoffs', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        $this->stat_card( __( 'Failed', 'rejoyan-crm-campaigns-for-woocommerce' ), number_format_i18n( $stats['failed'] ), 'dashicons-warning', __( 'needs review', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        echo '</div>';

        echo '<div class="rejoyan-crm-grid rejoyan-crm-two">';
        echo '<section class="rejoyan-crm-card rejoyan-crm-hero-card"><div><span class="rejoyan-crm-label">' . esc_html__( 'Offer workflow', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span><h2>' . esc_html__( 'Create an offer, choose customers, send once', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</h2><p>' . esc_html__( 'Build a branded offer first, then open Email Center to select every customer or a specific group and queue the offer with one action.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</p></div><div class="rejoyan-crm-actions">';
        echo '<a class="button button-primary button-hero" href="' . esc_url( admin_url( 'admin.php?page=rejoyan-crm-offers' ) ) . '">' . esc_html__( 'Create offer', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</a>';
        echo '<a class="button" href="' . esc_url( admin_url( 'admin.php?page=rejoyan-crm-customers' ) ) . '">' . esc_html__( 'Open Email Center', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</a>';
        echo '</div></section>';

        echo '<section class="rejoyan-crm-card"><div class="rejoyan-crm-section-head"><div><span class="rejoyan-crm-label">' . esc_html__( 'Delivery engine', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span><h2>' . esc_html__( 'Queue status', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</h2></div><span class="rejoyan-crm-health rejoyan-crm-health-good">' . esc_html( function_exists( 'as_enqueue_async_action' ) ? __( 'Action Scheduler', 'rejoyan-crm-campaigns-for-woocommerce' ) : __( 'WP-Cron fallback', 'rejoyan-crm-campaigns-for-woocommerce' ) ) . '</span></div><p>' . esc_html__( 'Recipients are snapshotted before sending and each address has its own status, helping retries avoid completed recipients.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</p>';
        if ( $offers ) {
            echo '<p><strong>' . esc_html__( 'Latest offer:', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</strong> ' . esc_html( $offers[0]->title ) . '</p>';
        }
        echo '</section></div>';

        echo '<section class="rejoyan-crm-card"><div class="rejoyan-crm-section-head"><div><span class="rejoyan-crm-label">' . esc_html__( 'Recent activity', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span><h2>' . esc_html__( 'Campaigns', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</h2></div><a href="' . esc_url( admin_url( 'admin.php?page=rejoyan-crm-campaigns' ) ) . '">' . esc_html__( 'View all', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</a></div>';
        $this->campaign_table( $recent, false );
        echo '</section>';
    }

    private function render_customers( $selected_offer = 0 ) {
        $users = get_users(
            array(
                'role'        => 'customer',
                'orderby'     => 'registered',
                'order'       => 'DESC',
                'fields'      => 'all',
                'number'      => -1,
                'count_total' => false,
            )
        );
        if ( $users ) {
            update_meta_cache( 'user', wp_list_pluck( $users, 'ID' ) );
        }
        $offers              = array_values( array_filter( $this->offers->get_offers( 200 ), array( $this->offers, 'is_offer_available' ) ) );
        $segment_definitions = $this->segments->definitions();
        if ( ! $selected_offer && $offers ) {
            $selected_offer = absint( $offers[0]->id );
        }

        $eligible_count = 0;
        foreach ( $users as $user ) {
            if ( is_email( $user->user_email ) && '1' !== (string) get_user_meta( $user->ID, 'rejoyan_crm_unsubscribed', true ) ) {
                $eligible_count++;
            }
        }

        echo '<div class="rejoyan-crm-grid rejoyan-crm-email-summary">';
        $this->stat_card( __( 'Registered customers', 'rejoyan-crm-campaigns-for-woocommerce' ), number_format_i18n( count( $users ) ), 'dashicons-admin-users', __( 'loaded on demand', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        $this->stat_card( __( 'Eligible emails', 'rejoyan-crm-campaigns-for-woocommerce' ), number_format_i18n( $eligible_count ), 'dashicons-email-alt', __( 'valid and not unsubscribed', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        $this->stat_card( __( 'Saved offers', 'rejoyan-crm-campaigns-for-woocommerce' ), number_format_i18n( count( $offers ) ), 'dashicons-tickets-alt', __( 'ready to select', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        echo '</div>';

        if ( ! $offers ) {
            echo '<section class="rejoyan-crm-card rejoyan-crm-empty-state"><span class="dashicons dashicons-tickets-alt"></span><h2>' . esc_html__( 'Create an offer first', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</h2><p>' . esc_html__( 'Email Center sends saved Rejoyan CRM & Campaigns offers. Create your first branded offer, then return here and select customers.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=rejoyan-crm-offers' ) ) . '">' . esc_html__( 'Create offer', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</a></section>';
            return;
        }

        echo '<form id="rejoyan-crm-offer-send-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="rejoyan_crm_send_offer"><input type="hidden" id="rejoyan-crm-select-all-mode" name="select_all_customers" value="0"><input type="hidden" id="rejoyan-crm-customer-ids-csv" name="customer_ids_csv" value="">';
        wp_nonce_field( 'rejoyan_crm_send_offer' );

        echo '<div class="rejoyan-crm-grid rejoyan-crm-email-center-layout">';
        echo '<section class="rejoyan-crm-card rejoyan-crm-email-list-card">';
        echo '<div class="rejoyan-crm-section-head rejoyan-crm-email-head"><div><span class="rejoyan-crm-label">' . esc_html__( 'Recipients', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span><h2>' . esc_html__( 'Customer emails', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</h2><p>' . esc_html__( 'Customer records are fetched only after this screen opens, keeping Rejoyan CRM & Campaigns navigation fast.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</p></div><div class="rejoyan-crm-email-tools"><input type="search" id="rejoyan-crm-customer-search" placeholder="' . esc_attr__( 'Search customer or email…', 'rejoyan-crm-campaigns-for-woocommerce' ) . '"><span id="rejoyan-crm-selected-count" class="rejoyan-crm-selection-count">0 ' . esc_html__( 'selected', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span></div></div>';

        /* translators: %s: number of eligible customer email addresses. */
        echo '<div class="rejoyan-crm-select-all-bar"><label><input type="checkbox" id="rejoyan-crm-select-all-customers"> <strong>' . esc_html__( 'Select all customers', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</strong></label><span>' . esc_html( sprintf( __( '%s eligible email addresses', 'rejoyan-crm-campaigns-for-woocommerce' ), number_format_i18n( $eligible_count ) ) ) . '</span></div>';

        echo '<div class="rejoyan-crm-table-scroll rejoyan-crm-email-table-wrap"><table class="widefat striped rejoyan-crm-email-table"><thead><tr><th class="check-column"></th><th>' . esc_html__( 'Customer', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</th><th>' . esc_html__( 'Email', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</th><th>' . esc_html__( 'Marketing status', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</th><th>' . esc_html__( 'Joined', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</th></tr></thead><tbody>';

        if ( ! $users ) {
            echo '<tr><td colspan="5">' . esc_html__( 'No registered customers found.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</td></tr>';
        }

        foreach ( $users as $user ) {
            $optin        = '1' === (string) get_user_meta( $user->ID, 'rejoyan_crm_marketing_optin', true );
            $unsubscribed = '1' === (string) get_user_meta( $user->ID, 'rejoyan_crm_unsubscribed', true );
            $valid_email  = is_email( $user->user_email );
            $eligible     = $valid_email && ! $unsubscribed;
            $status       = $unsubscribed ? __( 'Unsubscribed', 'rejoyan-crm-campaigns-for-woocommerce' ) : ( $optin ? __( 'Opted in', 'rejoyan-crm-campaigns-for-woocommerce' ) : __( 'Not marked opted in', 'rejoyan-crm-campaigns-for-woocommerce' ) );
            $status_class = $unsubscribed ? 'bad' : ( $optin ? 'good' : 'neutral' );
            $search_text  = strtolower( ( $user->display_name ?: $user->user_login ) . ' ' . $user->user_email );

            echo '<tr class="rejoyan-crm-customer-row" data-search="' . esc_attr( $search_text ) . '"><th class="check-column"><input class="rejoyan-crm-customer-checkbox" type="checkbox" name="customer_ids[]" value="' . esc_attr( $user->ID ) . '" ' . disabled( $eligible, false, false ) . '></th><td><div class="rejoyan-crm-customer"><span class="rejoyan-crm-avatar">' . esc_html( strtoupper( substr( $user->display_name ?: $user->user_login, 0, 1 ) ) ) . '</span><div><strong>' . esc_html( $user->display_name ?: $user->user_login ) . '</strong><small>#' . esc_html( $user->ID ) . '</small></div></div></td><td><strong>' . esc_html( $user->user_email ) . '</strong>';
            if ( ! $valid_email ) {
                echo '<small class="rejoyan-crm-invalid-email">' . esc_html__( 'Invalid email', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</small>';
            }
            echo '</td><td><span class="rejoyan-crm-status ' . esc_attr( $status_class ) . '">' . esc_html( $status ) . '</span></td><td>' . esc_html( mysql2date( get_option( 'date_format' ), $user->user_registered ) ) . '</td></tr>';
        }
        echo '</tbody></table></div></section>';

        echo '<aside class="rejoyan-crm-card rejoyan-crm-offer-send-panel"><span class="rejoyan-crm-label">' . esc_html__( 'Offer sender', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span><h2>' . esc_html__( 'Choose offer & send', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</h2>';
        echo '<div class="rejoyan-crm-field"><label for="rejoyan-crm-offer-select">' . esc_html__( 'Saved offer', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><select class="widefat" id="rejoyan-crm-offer-select" name="offer_id" required>';
        foreach ( $offers as $offer ) {
            $preview_product       = $this->offers->get_linked_product( $offer );
            $preview_product_name  = $preview_product ? $preview_product->get_name() : '';
            $preview_product_price = $preview_product ? wp_strip_all_tags( $preview_product->get_price_html() ) : '';
            echo '<option value="' . esc_attr( $offer->id ) . '" ' . selected( $selected_offer, $offer->id, false ) . ' data-subject="' . esc_attr( $offer->subject ) . '" data-badge="' . esc_attr( $offer->badge ) . '" data-headline="' . esc_attr( $offer->headline ) . '" data-description="' . esc_attr( wp_trim_words( wp_strip_all_tags( $offer->description ), 28 ) ) . '" data-product="' . esc_attr( $preview_product_name ) . '" data-product-price="' . esc_attr( $preview_product_price ) . '" data-coupon="' . esc_attr( $offer->coupon_code ) . '" data-cta="' . esc_attr( $offer->cta_text ) . '" data-expiry="' . esc_attr( $offer->expiry_text ) . '" data-template="' . esc_attr( $offer->template_style ?? 'classic' ) . '" data-accent="' . esc_attr( $offer->accent_color ?? '#5b4cf0' ) . '" data-preheader="' . esc_attr( $offer->preheader ?? '' ) . '">' . esc_html( $offer->title ) . '</option>';
        }
        echo '</select></div>';
        echo '<div class="rejoyan-crm-field"><label for="rejoyan-crm-audience-segment">' . esc_html__( 'Smart audience (optional)', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><select class="widefat" id="rejoyan-crm-audience-segment" name="audience_segment"><option value="">' . esc_html__( 'Use the customers selected on the left', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</option>';
        foreach ( $segment_definitions as $segment_key => $segment_data ) {
            echo '<option value="' . esc_attr( $segment_key ) . '">' . esc_html( $segment_data['label'] ) . '</option>';
        }
        echo '</select><small>' . esc_html__( 'Choosing a smart audience ignores the manual checkboxes and prepares that segment in the background.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</small></div>';

        echo '<div class="rejoyan-crm-mini-email" id="rejoyan-crm-offer-preview"><div class="rejoyan-crm-mini-email-top">' . esc_html( get_bloginfo( 'name' ) ) . '</div><div class="rejoyan-crm-mini-email-body"><span class="rejoyan-crm-mini-badge" data-preview="badge"></span><small class="rejoyan-crm-mini-subject"><strong>' . esc_html__( 'Subject:', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</strong> <span data-preview="subject"></span></small><h3 data-preview="headline"></h3><p data-preview="description"></p><div class="rejoyan-crm-mini-product" data-preview="product"></div><div class="rejoyan-crm-mini-coupon" data-preview="coupon"></div><span class="rejoyan-crm-mini-cta" data-preview="cta"></span><small class="rejoyan-crm-mini-expiry" data-preview="expiry"></small></div></div>';

        echo '<div class="rejoyan-crm-field"><label>' . esc_html__( 'Send later (optional)', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input class="widefat" type="datetime-local" name="scheduled_for"><small>' . esc_html__( 'Leave empty to queue now. Scheduled sending uses the WordPress site timezone.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</small></div>';

        echo '<div class="rejoyan-crm-send-rules"><strong>' . esc_html__( 'Before sending', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</strong><p>' . esc_html__( 'Unsubscribed customers are always skipped. For other recipients, make sure your store has the required consent or other lawful basis for marketing.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</p></div>';
        echo '<label class="rejoyan-crm-check rejoyan-crm-consent-check"><input type="checkbox" name="marketing_permission_confirmed" value="1" required> <span>' . esc_html__( 'I confirm I am allowed to send this marketing offer to the selected recipients.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span></label>';
        echo '<button class="button button-primary button-hero rejoyan-crm-confirm-offer-send" type="submit">' . esc_html__( 'Queue offer email', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</button><a class="button rejoyan-crm-secondary-action" href="' . esc_url( admin_url( 'admin.php?page=rejoyan-crm-offers' ) ) . '">' . esc_html__( 'Create another offer', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</a>';
        echo '</aside></div></form>';
    }

    private function render_segments() {
        $definitions = $this->segments->definitions();
        $summary     = $this->segments->summary();

        echo '<div class="rejoyan-crm-grid rejoyan-crm-segment-grid">';
        foreach ( $definitions as $key => $segment ) {
            $count = isset( $summary[ $key ] ) ? absint( $summary[ $key ] ) : 0;
            echo '<section class="rejoyan-crm-card rejoyan-crm-segment-card"><div class="rejoyan-crm-section-head"><div><span class="rejoyan-crm-label">' . esc_html__( 'Smart audience', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span><h2>' . esc_html( $segment['label'] ) . '</h2></div><strong class="rejoyan-crm-segment-count">' . esc_html( number_format_i18n( $count ) ) . '</strong></div><p>' . esc_html( $segment['description'] ) . '</p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=rejoyan-crm-customers' ) ) . '">' . esc_html__( 'Send an offer', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</a></section>';
        }
        echo '</div>';

        echo '<section class="rejoyan-crm-card rejoyan-crm-narrow"><span class="rejoyan-crm-label">' . esc_html__( 'How segmentation works', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span><h2>' . esc_html__( 'Recency, frequency and customer value', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</h2><p>' . esc_html__( 'Rejoyan CRM & Campaigns builds these audiences from registered WooCommerce customer accounts, paid-order recency, order count and lifetime spend. Segment preparation runs through the campaign queue when you send an offer.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</p><p><a href="' . esc_url( admin_url( 'admin.php?page=rejoyan-crm-settings#rejoyan-crm-segment-settings' ) ) . '">' . esc_html__( 'Adjust segment thresholds in Settings', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</a></p></section>';
    }

    private function render_campaign_history() {
        $campaigns = $this->campaigns->get_campaigns( 50 );
        echo '<section class="rejoyan-crm-card rejoyan-crm-campaign-history"><div class="rejoyan-crm-section-head"><div><span class="rejoyan-crm-label">' . esc_html__( 'Campaign history', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span><h2>' . esc_html__( 'Recent campaigns', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</h2></div></div>';
        $this->campaign_table( $campaigns, true );
        echo '</section>';
    }

    private function campaign_table( array $campaigns, $show_actions ) {
        echo '<div class="rejoyan-crm-table-scroll"><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Campaign', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</th><th>' . esc_html__( 'Type / Audience', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</th><th>' . esc_html__( 'Status', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</th><th>' . esc_html__( 'Progress', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</th><th>' . esc_html__( 'Sent / Failed', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</th>';
        if ( $show_actions ) {
            echo '<th>' . esc_html__( 'Actions', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</th>';
        }
        echo '</tr></thead><tbody>';

        if ( ! $campaigns ) {
            echo '<tr><td colspan="' . esc_attr( $show_actions ? 6 : 5 ) . '">' . esc_html__( 'No campaigns yet.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</td></tr>';
        }

        foreach ( $campaigns as $campaign ) {
            $total    = max( 0, (int) $campaign->total_count );
            $done     = max( 0, (int) $campaign->processed_count );
            $percent  = $total > 0 ? min( 100, (int) round( ( $done / $total ) * 100 ) ) : ( 'completed' === $campaign->status ? 100 : 0 );
            $audience = 'optin' === $campaign->audience ? __( 'Opt-in only', 'rejoyan-crm-campaigns-for-woocommerce' ) : ( 'selected' === $campaign->audience ? __( 'Selected customers', 'rejoyan-crm-campaigns-for-woocommerce' ) : __( 'Registered customers', 'rejoyan-crm-campaigns-for-woocommerce' ) );
            if ( 0 === strpos( (string) $campaign->audience, 'segment_' ) ) {
                $segment_key = substr( (string) $campaign->audience, 8 );
                $definitions = $this->segments->definitions();
                $audience    = isset( $definitions[ $segment_key ] ) ? $definitions[ $segment_key ]['label'] : __( 'Smart segment', 'rejoyan-crm-campaigns-for-woocommerce' );
            } elseif ( preg_match( '/^product_buyer_(\d+)$/', (string) $campaign->audience, $buyer_match ) ) {
                $buyer_product = wc_get_product( absint( $buyer_match[1] ) );
                /* translators: %s: WooCommerce product name. */
                $audience = $buyer_product ? sprintf( __( 'Bought: %s', 'rejoyan-crm-campaigns-for-woocommerce' ), $buyer_product->get_name() ) : __( 'Product buyers', 'rejoyan-crm-campaigns-for-woocommerce' );
            }
            $type         = 'offer' === (string) ( $campaign->campaign_type ?? 'standard' ) ? __( 'Offer', 'rejoyan-crm-campaigns-for-woocommerce' ) : __( 'Campaign', 'rejoyan-crm-campaigns-for-woocommerce' );
            $status_class = in_array( $campaign->status, array( 'completed' ), true ) ? 'good' : ( in_array( $campaign->status, array( 'cancelled' ), true ) ? 'bad' : 'neutral' );

            echo '<tr><td><strong>' . esc_html( $campaign->name ) . '</strong><small class="rejoyan-crm-subject">' . esc_html( $campaign->subject ) . '</small>';
            if ( 'scheduled' === $campaign->status && ! empty( $campaign->scheduled_at ) ) {
                echo '<small class="rejoyan-crm-subject"><span class="dashicons dashicons-clock" aria-hidden="true"></span> ' . esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $campaign->scheduled_at ) ) . '</small>';
            }
            echo '</td><td><span class="rejoyan-crm-type-pill">' . esc_html( $type ) . '</span><small class="rejoyan-crm-subject">' . esc_html( $audience ) . '</small></td><td><span class="rejoyan-crm-status ' . esc_attr( $status_class ) . '">' . esc_html( ucfirst( $campaign->status ) ) . '</span></td><td><div class="rejoyan-crm-progress"><span style="width:' . esc_attr( $percent ) . '%"></span></div><small>' . esc_html( number_format_i18n( $done ) . ' / ' . number_format_i18n( $total ) ) . '</small></td><td><strong>' . esc_html( number_format_i18n( $campaign->sent_count ) ) . '</strong> / ' . esc_html( number_format_i18n( $campaign->failed_count ) ) . '</td>';

            if ( $show_actions ) {
                echo '<td><div class="rejoyan-crm-row-actions">';
                if ( in_array( $campaign->status, array( 'draft', 'cancelled', 'completed' ), true ) && 'selected' !== $campaign->audience ) {
                    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="rejoyan_crm_campaign_action"><input type="hidden" name="campaign_id" value="' . esc_attr( $campaign->id ) . '"><input type="hidden" name="do" value="queue">';
                    wp_nonce_field( 'rejoyan_crm_campaign_action_' . $campaign->id );
                    echo '<button class="button button-small rejoyan-crm-confirm-send" type="submit">' . esc_html__( 'Queue', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</button></form>';
                }
                if ( in_array( $campaign->status, array( 'preparing', 'queued', 'sending', 'scheduled' ), true ) ) {
                    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="rejoyan_crm_campaign_action"><input type="hidden" name="campaign_id" value="' . esc_attr( $campaign->id ) . '"><input type="hidden" name="do" value="cancel">';
                    wp_nonce_field( 'rejoyan_crm_campaign_action_' . $campaign->id );
                    echo '<button class="button button-small rejoyan-crm-confirm-cancel" type="submit">' . esc_html__( 'Cancel', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</button></form>';
                }
                $preview_url = wp_nonce_url( add_query_arg( array( 'action' => 'rejoyan_crm_preview_campaign', 'campaign_id' => $campaign->id ), admin_url( 'admin-post.php' ) ), 'rejoyan_crm_preview_campaign_' . $campaign->id );
                echo '<a class="button button-small" target="_blank" rel="noopener" href="' . esc_url( $preview_url ) . '">' . esc_html__( 'Preview', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</a>';
                if ( (int) $campaign->failed_count > 0 && ! in_array( $campaign->status, array( 'preparing', 'queued', 'sending', 'scheduled' ), true ) ) {
                    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="rejoyan_crm_campaign_action"><input type="hidden" name="campaign_id" value="' . esc_attr( $campaign->id ) . '"><input type="hidden" name="do" value="retry_failed">';
                    wp_nonce_field( 'rejoyan_crm_campaign_action_' . $campaign->id );
                    echo '<button class="button button-small" type="submit">' . esc_html__( 'Retry failed', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</button></form>';
                }
                echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="rejoyan-crm-test-form"><input type="hidden" name="action" value="rejoyan_crm_send_test"><input type="hidden" name="campaign_id" value="' . esc_attr( $campaign->id ) . '">';
                wp_nonce_field( 'rejoyan_crm_send_test_' . $campaign->id );
                echo '<input type="email" name="test_email" value="' . esc_attr( wp_get_current_user()->user_email ) . '" aria-label="' . esc_attr__( 'Test email address', 'rejoyan-crm-campaigns-for-woocommerce' ) . '" required><button class="button button-small" type="submit">' . esc_html__( 'Test', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</button></form>';
                echo '</div></td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }

    public function handle_social_products() {
        $this->guard_ajax();
        check_ajax_referer( 'rejoyan_crm_lazy_panel', 'nonce' );

        $page     = isset( $_POST['page'] ) ? max( 1, absint( $_POST['page'] ) ) : 1;
        $search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
        $category = isset( $_POST['category'] ) ? sanitize_title( wp_unslash( $_POST['category'] ) ) : '';
        $filter   = isset( $_POST['filter'] ) ? sanitize_key( wp_unslash( $_POST['filter'] ) ) : 'all';
        $allowed  = array( 'all', 'sale', 'instock', 'outofstock' );
        if ( ! in_array( $filter, $allowed, true ) ) {
            $filter = 'all';
        }

        $result = $this->query_social_products( $page, $search, $category, $filter );
        ob_start();
        $this->render_social_product_cards( $result['products'] );
        $html = ob_get_clean();

        wp_send_json_success(
            array(
                'html'     => $html,
                'page'     => $page,
                'has_more' => $page < $result['max_pages'],
                'total'    => $result['total'],
            )
        );
    }

    private function query_social_products( $page = 1, $search = '', $category = '', $filter = 'all' ) {
        $args = array(
            'post_type'              => 'product',
            'post_status'            => 'publish',
            'fields'                 => 'ids',
            'posts_per_page'         => 18,
            'paged'                  => max( 1, absint( $page ) ),
            'orderby'                => 'date',
            'order'                  => 'DESC',
            'ignore_sticky_posts'    => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => true,
        );

        if ( '' !== $search ) {
            $args['s'] = $search;
        }

        if ( '' !== $category ) {
            $args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Admin-only, paginated live-product category filter.
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => array( $category ),
                ),
            );
        }

        if ( 'sale' === $filter ) {
            $sale_ids        = array_map( 'absint', wc_get_product_ids_on_sale() );
            $args['post__in'] = $sale_ids ? $sale_ids : array( 0 );
        } elseif ( in_array( $filter, array( 'instock', 'outofstock' ), true ) ) {
            $args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Admin-only, paginated stock-status filter.
                array(
                    'key'   => '_stock_status',
                    'value' => $filter,
                ),
            );
        }

        $query    = new WP_Query( $args );
        $products = array();
        foreach ( $query->posts as $product_id ) {
            $product = wc_get_product( $product_id );
            if ( $product instanceof WC_Product ) {
                $products[] = $product;
            }
        }

        return array(
            'products'  => $products,
            'total'     => (int) $query->found_posts,
            'max_pages' => max( 1, (int) $query->max_num_pages ),
        );
    }

    private function render_social_products() {
        $result     = $this->query_social_products( 1, '', '', 'all' );
        $categories = get_terms(
            array(
                'taxonomy'   => 'product_cat',
                'hide_empty' => true,
                'orderby'    => 'name',
                'order'      => 'ASC',
                'number'     => 200,
            )
        );

        echo '<div class="rejoyan-crm-social-browser" data-page="1">';
        echo '<section class="rejoyan-crm-card rejoyan-crm-social-intro"><div><span class="rejoyan-crm-label">' . esc_html__( 'Live WooCommerce products', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span><h2>' . esc_html__( 'Share the products that actually exist in your store', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</h2><p>' . esc_html__( 'Rejoyan CRM & Campaigns reads published WooCommerce products directly from this website. No demo or fake products are generated. Search, filter and share the real product URL from each card.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</p></div><div class="rejoyan-crm-live-product-pill"><span class="rejoyan-crm-live-dot"></span><strong data-rejoyan-crm-product-total>' . esc_html( number_format_i18n( $result['total'] ) ) . '</strong><span>' . esc_html__( 'live products', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span></div></section>';

        echo '<section class="rejoyan-crm-card rejoyan-crm-social-toolbar">';
        echo '<div class="rejoyan-crm-social-filter rejoyan-crm-social-search"><label for="rejoyan-crm-social-search">' . esc_html__( 'Search products', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input type="search" id="rejoyan-crm-social-search" data-rejoyan-crm-social-search placeholder="' . esc_attr__( 'Search by product name…', 'rejoyan-crm-campaigns-for-woocommerce' ) . '"></div>';
        echo '<div class="rejoyan-crm-social-filter"><label for="rejoyan-crm-social-category">' . esc_html__( 'Category', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><select id="rejoyan-crm-social-category" data-rejoyan-crm-social-category><option value="">' . esc_html__( 'All categories', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</option>';
        if ( ! is_wp_error( $categories ) ) {
            foreach ( $categories as $category ) {
                echo '<option value="' . esc_attr( $category->slug ) . '">' . esc_html( $category->name ) . ' (' . esc_html( number_format_i18n( $category->count ) ) . ')</option>';
            }
        }
        echo '</select></div>';
        echo '<div class="rejoyan-crm-social-filter"><label for="rejoyan-crm-social-status">' . esc_html__( 'Product filter', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><select id="rejoyan-crm-social-status" data-rejoyan-crm-social-filter><option value="all">' . esc_html__( 'All published products', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</option><option value="sale">' . esc_html__( 'On sale', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</option><option value="instock">' . esc_html__( 'In stock', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</option><option value="outofstock">' . esc_html__( 'Out of stock', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</option></select></div>';
        echo '<div class="rejoyan-crm-social-toolbar-note"><span class="dashicons dashicons-update"></span><span>' . esc_html__( 'Product data is loaded from WooCommerce when you use this screen.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span></div>';
        echo '</section>';

        echo '<div class="rejoyan-crm-social-results" aria-live="polite"><div class="rejoyan-crm-product-grid rejoyan-crm-social-grid" data-rejoyan-crm-social-grid>';
        $this->render_social_product_cards( $result['products'] );
        echo '</div></div>';
        echo '<div class="rejoyan-crm-social-load-wrap"><button type="button" class="button button-large rejoyan-crm-social-load-more" data-rejoyan-crm-social-load-more' . ( 1 >= $result['max_pages'] ? ' hidden' : '' ) . '>' . esc_html__( 'Load more products', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</button><span class="spinner" data-rejoyan-crm-social-spinner></span></div>';
        echo '</div>';
    }

    private function render_social_product_cards( $products ) {
        if ( ! $products ) {
            echo '<section class="rejoyan-crm-card rejoyan-crm-social-empty"><span class="dashicons dashicons-products"></span><h3>' . esc_html__( 'No matching live products found', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</h3><p>' . esc_html__( 'Try a different search or filter. Rejoyan CRM & Campaigns never inserts demo products here.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</p></section>';
            return;
        }

        foreach ( $products as $product ) {
            $product_id = $product->get_id();
            $url        = get_permalink( $product_id );
            $title      = $product->get_name();
            $price      = wp_strip_all_tags( $product->get_price_html() );
            $short      = wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 16 );
            $caption    = trim( $title . ( $price ? ' — ' . $price : '' ) . ( $short ? "

" . $short : '' ) . "

" . $url );
            $image      = wp_get_attachment_image_url( $product->get_image_id(), 'medium_large' );
            $on_sale    = $product->is_on_sale();
            $stock      = $product->is_in_stock() ? __( 'In stock', 'rejoyan-crm-campaigns-for-woocommerce' ) : __( 'Out of stock', 'rejoyan-crm-campaigns-for-woocommerce' );
            $sku        = $product->get_sku();
            $terms      = get_the_terms( $product_id, 'product_cat' );
            $category   = ( is_array( $terms ) && $terms ) ? $terms[0]->name : '';

            echo '<section class="rejoyan-crm-card rejoyan-crm-product rejoyan-crm-social-product" data-product-id="' . esc_attr( $product_id ) . '">';
            echo '<div class="rejoyan-crm-product-media">';
            if ( $on_sale ) {
                echo '<span class="rejoyan-crm-sale-badge">' . esc_html__( 'Sale', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span>';
            }
            echo '<span class="rejoyan-crm-live-product-badge"><span class="rejoyan-crm-live-dot"></span>' . esc_html__( 'Live', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span>';
            if ( $image ) {
                echo '<img loading="lazy" decoding="async" src="' . esc_url( $image ) . '" alt="' . esc_attr( $title ) . '">';
            } else {
                echo '<div class="rejoyan-crm-no-product-image"><span class="dashicons dashicons-format-image"></span><small>' . esc_html__( 'No product image', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</small></div>';
            }
            echo '</div><div class="rejoyan-crm-product-body">';
            echo '<div class="rejoyan-crm-product-meta-line">';
            if ( $category ) {
                echo '<span>' . esc_html( $category ) . '</span>';
            }
            if ( $sku ) {
                echo '<span>SKU: ' . esc_html( $sku ) . '</span>';
            }
            echo '</div>';
            echo '<div class="rejoyan-crm-product-title-row"><div><h3>' . esc_html( $title ) . '</h3><small class="rejoyan-crm-stock-state ' . ( $product->is_in_stock() ? 'is-in-stock' : 'is-out-stock' ) . '">' . esc_html( $stock ) . '</small></div><p class="rejoyan-crm-price">' . wp_kses_post( $product->get_price_html() ) . '</p></div>';
            echo '<label class="rejoyan-crm-caption-label">' . esc_html__( 'Social caption', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><textarea class="rejoyan-crm-caption rejoyan-crm-social-caption">' . esc_textarea( $caption ) . '</textarea>';
            echo '<div class="rejoyan-crm-product-quick-actions"><a class="button" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-external"></span>' . esc_html__( 'View product', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</a><button type="button" class="button rejoyan-crm-copy-caption"><span class="dashicons dashicons-clipboard"></span>' . esc_html__( 'Copy caption', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</button><button type="button" class="button rejoyan-crm-copy" data-copy="' . esc_attr( $url ) . '"><span class="dashicons dashicons-admin-links"></span>' . esc_html__( 'Copy link', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</button></div>';
            echo '<div class="rejoyan-crm-social-share-block"><div><strong>' . esc_html__( 'Share product', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</strong><small>' . esc_html__( 'Opens the selected network with this live product URL.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</small></div><div class="rejoyan-crm-social-iconbar">';
            $this->social_share_button( 'facebook', 'f', __( 'Share on Facebook', 'rejoyan-crm-campaigns-for-woocommerce' ), $url, $image );
            $this->social_share_button( 'x', 'X', __( 'Share on X', 'rejoyan-crm-campaigns-for-woocommerce' ), $url, $image );
            $this->social_share_button( 'linkedin', 'in', __( 'Share on LinkedIn', 'rejoyan-crm-campaigns-for-woocommerce' ), $url, $image );
            $this->social_share_button( 'whatsapp', 'WA', __( 'Share on WhatsApp', 'rejoyan-crm-campaigns-for-woocommerce' ), $url, $image );
            $this->social_share_button( 'native', '↗', __( 'Share with device', 'rejoyan-crm-campaigns-for-woocommerce' ), $url, $image );
            echo '</div></div></div></section>';
        }
    }

    private function social_share_button( $platform, $glyph, $label, $url, $image = '' ) {
        echo '<button type="button" class="rejoyan-crm-social-share rejoyan-crm-social-icon-btn rejoyan-crm-share-' . esc_attr( $platform ) . '" data-platform="' . esc_attr( $platform ) . '" data-url="' . esc_attr( $url ) . '" data-image="' . esc_attr( $image ) . '" aria-label="' . esc_attr( $label ) . '" title="' . esc_attr( $label ) . '"><span class="rejoyan-crm-share-glyph" aria-hidden="true">' . esc_html( $glyph ) . '</span><span class="screen-reader-text">' . esc_html( $label ) . '</span></button>';
    }

    private function render_analytics() {
        $overview = $this->reports->overview();
        $rows     = $this->reports->campaign_rows( 100 );
        $series   = $this->reports->daily_sent( 14 );
        $max_sent = 1;
        foreach ( $series as $point ) {
            $max_sent = max( $max_sent, (int) $point['sent'] );
        }

        echo '<div class="rejoyan-crm-grid rejoyan-crm-stats">';
        $this->stat_card( __( 'Campaigns', 'rejoyan-crm-campaigns-for-woocommerce' ), number_format_i18n( $overview['campaigns'] ), 'dashicons-chart-bar', __( 'all time', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        $this->stat_card( __( 'Sent', 'rejoyan-crm-campaigns-for-woocommerce' ), number_format_i18n( $overview['sent'] ), 'dashicons-yes-alt', __( 'mail handoffs', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        $this->stat_card( __( 'Failed', 'rejoyan-crm-campaigns-for-woocommerce' ), number_format_i18n( $overview['failed'] ), 'dashicons-warning', __( 'delivery errors', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        $this->stat_card( __( 'Send success rate', 'rejoyan-crm-campaigns-for-woocommerce' ), number_format_i18n( $overview['delivery_rate'], 1 ) . '%', 'dashicons-performance', __( 'wp_mail handoff / processed', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        echo '</div>';

        echo '<div class="rejoyan-crm-grid rejoyan-crm-two">';
        echo '<section class="rejoyan-crm-card"><div class="rejoyan-crm-section-head"><div><span class="rejoyan-crm-label">' . esc_html__( 'Last 14 days', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span><h2>' . esc_html__( 'Emails sent', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</h2></div></div><div class="rejoyan-crm-bars" aria-label="' . esc_attr__( 'Emails sent by day', 'rejoyan-crm-campaigns-for-woocommerce' ) . '">';
        foreach ( $series as $point ) {
            $height = (int) round( ( (int) $point['sent'] / $max_sent ) * 100 );
            /* translators: 1: chart date label, 2: number of emails sent. */
            echo '<div class="rejoyan-crm-bar-item"><div class="rejoyan-crm-bar-track"><span style="height:' . esc_attr( max( 2, $height ) ) . '%" title="' . esc_attr( sprintf( __( '%1$s: %2$d sent', 'rejoyan-crm-campaigns-for-woocommerce' ), $point['label'], $point['sent'] ) ) . '"></span></div><small>' . esc_html( $point['label'] ) . '</small><strong>' . esc_html( number_format_i18n( $point['sent'] ) ) . '</strong></div>';
        }
        echo '</div></section>';

        echo '<section class="rejoyan-crm-card"><span class="rejoyan-crm-label">' . esc_html__( 'How to read this', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span><h2>' . esc_html__( 'Delivery, not surveillance', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</h2><p>' . esc_html__( 'Rejoyan CRM & Campaigns records whether WordPress handed each message to the configured mail transport. It does not add tracking pixels or redirect customer links for open/click tracking.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</p><div class="rejoyan-crm-report-kpis"><div><strong>' . esc_html( number_format_i18n( $overview['skipped'] ) ) . '</strong><small>' . esc_html__( 'Skipped', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</small></div><div><strong>' . esc_html( number_format_i18n( $overview['pending'] ) ) . '</strong><small>' . esc_html__( 'Pending', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</small></div></div>';
        $export_url = wp_nonce_url( admin_url( 'admin-post.php?action=rejoyan_crm_export_report' ), 'rejoyan_crm_export_report' );
        echo '<p><a class="button" href="' . esc_url( $export_url ) . '">' . esc_html__( 'Export campaign CSV', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</a></p></section></div>';

        echo '<section class="rejoyan-crm-card"><div class="rejoyan-crm-section-head"><div><span class="rejoyan-crm-label">' . esc_html__( 'Campaign report', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span><h2>' . esc_html__( 'Delivery history', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</h2></div></div><div class="rejoyan-crm-table-scroll"><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Campaign', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</th><th>' . esc_html__( 'Type', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</th><th>' . esc_html__( 'Status', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</th><th>' . esc_html__( 'Total', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</th><th>' . esc_html__( 'Sent', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</th><th>' . esc_html__( 'Failed', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</th><th>' . esc_html__( 'Skipped', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</th><th>' . esc_html__( 'Pending', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</th><th>' . esc_html__( 'Created', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</th></tr></thead><tbody>';
        if ( ! $rows ) {
            echo '<tr><td colspan="9">' . esc_html__( 'No campaign data yet.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</td></tr>';
        }
        foreach ( $rows as $row ) {
            echo '<tr><td><strong>' . esc_html( $row->name ) . '</strong><small class="rejoyan-crm-table-note">#' . esc_html( $row->id ) . '</small></td><td>' . esc_html( ucfirst( (string) $row->campaign_type ) ) . '</td><td><span class="rejoyan-crm-status neutral">' . esc_html( ucfirst( (string) $row->status ) ) . '</span></td><td>' . esc_html( number_format_i18n( $row->total_count ) ) . '</td><td>' . esc_html( number_format_i18n( $row->sent_count ) ) . '</td><td>' . esc_html( number_format_i18n( $row->failed_count ) ) . '</td><td>' . esc_html( number_format_i18n( $row->skipped_count ) ) . '</td><td>' . esc_html( number_format_i18n( $row->pending_count ) ) . '</td><td>' . esc_html( mysql2date( get_option( 'date_format' ), $row->created_at ) ) . '</td></tr>';
        }
        echo '</tbody></table></div></section>';
    }

    private function render_invoices() {
        $orders = wc_get_orders( array( 'limit' => 30, 'orderby' => 'date', 'order' => 'DESC', 'return' => 'objects' ) );

        echo '<section class="rejoyan-crm-card rejoyan-crm-narrow"><span class="rejoyan-crm-label">' . esc_html__( 'Invoice action', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span><h2>' . esc_html__( 'Send customer invoice', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</h2><p>' . esc_html__( 'Orders are loaded only when this tool opens. Select an order and Rejoyan CRM & Campaigns asks WooCommerce to generate and send its configured customer invoice email.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="rejoyan_crm_send_invoice">';
        wp_nonce_field( 'rejoyan_crm_send_invoice' );
        echo '<div class="rejoyan-crm-field"><label for="mf-order">' . esc_html__( 'Order', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><select id="mf-order" class="widefat" name="order_id" required><option value="">' . esc_html__( 'Select an order…', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</option>';
        foreach ( $orders as $order ) {
            $label = '#' . $order->get_order_number() . ' — ' . $order->get_formatted_billing_full_name() . ' — ' . $order->get_billing_email();
            echo '<option value="' . esc_attr( $order->get_id() ) . '">' . esc_html( $label ) . '</option>';
        }
        echo '</select></div><button class="button button-primary rejoyan-crm-confirm-invoice" type="submit">' . esc_html__( 'Send invoice email', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</button></form></section>';
    }
}
