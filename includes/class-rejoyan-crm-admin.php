<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Rejoyan_CRM_Admin {
    /**
     * Neutralize spreadsheet formula prefixes in exported CSV cells.
     *
     * @param mixed $value Cell value.
     * @return string
     */
    private function csv_cell( $value ) {
        $value = (string) $value;
        if ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
            $value = "'" . $value;
        }
        return $value;
    }
    private $campaigns;
    private $offers;
    private $reports;
    private $segments;
    private $lazy;

    public function __construct( Rejoyan_CRM_Campaigns $campaigns, Rejoyan_CRM_Offers $offers, Rejoyan_CRM_Reports $reports, Rejoyan_CRM_Segments $segments, Rejoyan_CRM_Admin_Lazy $lazy ) {
        $this->campaigns = $campaigns;
        $this->offers    = $offers;
        $this->reports   = $reports;
        $this->segments  = $segments;
        $this->lazy      = $lazy;

        add_action( 'admin_menu', array( $this, 'register_menus' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        add_action( 'admin_post_rejoyan_crm_toggle_optin', array( $this, 'handle_toggle_optin' ) );
        add_action( 'admin_post_rejoyan_crm_create_campaign', array( $this, 'handle_create_campaign' ) );
        add_action( 'admin_post_rejoyan_crm_campaign_action', array( $this, 'handle_campaign_action' ) );
        add_action( 'admin_post_rejoyan_crm_send_test', array( $this, 'handle_send_test' ) );
        add_action( 'admin_post_rejoyan_crm_preview_campaign', array( $this, 'handle_preview_campaign' ) );
        add_action( 'admin_post_rejoyan_crm_send_invoice', array( $this, 'handle_send_invoice' ) );
        add_action( 'admin_post_rejoyan_crm_save_settings', array( $this, 'handle_save_settings' ) );
        add_action( 'admin_post_rejoyan_crm_create_coupon', array( $this, 'handle_create_coupon' ) );
        add_action( 'admin_post_rejoyan_crm_create_offer', array( $this, 'handle_create_offer' ) );
        add_action( 'admin_post_rejoyan_crm_offer_action', array( $this, 'handle_offer_action' ) );
        add_action( 'admin_post_rejoyan_crm_send_offer', array( $this, 'handle_send_offer' ) );
        add_action( 'admin_post_rejoyan_crm_export_report', array( $this, 'handle_export_report' ) );
    }

    public function register_menus() {
        add_menu_page(
            __( 'Rejoyan CRM & Campaigns', 'rejoyan-crm-campaigns-for-woocommerce' ),
            __( 'Rejoyan CRM & Campaigns', 'rejoyan-crm-campaigns-for-woocommerce' ),
            'manage_woocommerce',
            'rejoyan-crm',
            array( $this, 'render_dashboard' ),
            'dashicons-megaphone',
            56
        );

        add_submenu_page( 'rejoyan-crm', __( 'Dashboard', 'rejoyan-crm-campaigns-for-woocommerce' ), __( 'Dashboard', 'rejoyan-crm-campaigns-for-woocommerce' ), 'manage_woocommerce', 'rejoyan-crm', array( $this, 'render_dashboard' ) );
        add_submenu_page( 'rejoyan-crm', __( 'Customer Email Center', 'rejoyan-crm-campaigns-for-woocommerce' ), __( 'Email Center', 'rejoyan-crm-campaigns-for-woocommerce' ), 'manage_woocommerce', 'rejoyan-crm-customers', array( $this, 'render_customers' ) );
        add_submenu_page( 'rejoyan-crm', __( 'Customer Segments', 'rejoyan-crm-campaigns-for-woocommerce' ), __( 'Segments', 'rejoyan-crm-campaigns-for-woocommerce' ), 'manage_woocommerce', 'rejoyan-crm-segments', array( $this, 'render_segments' ) );
        add_submenu_page( 'rejoyan-crm', __( 'Campaigns', 'rejoyan-crm-campaigns-for-woocommerce' ), __( 'Campaigns', 'rejoyan-crm-campaigns-for-woocommerce' ), 'manage_woocommerce', 'rejoyan-crm-campaigns', array( $this, 'render_campaigns' ) );
        add_submenu_page( 'rejoyan-crm', __( 'Offers & Coupons', 'rejoyan-crm-campaigns-for-woocommerce' ), __( 'Offers & Coupons', 'rejoyan-crm-campaigns-for-woocommerce' ), 'manage_woocommerce', 'rejoyan-crm-offers', array( $this, 'render_offers' ) );
        add_submenu_page( 'rejoyan-crm', __( 'Social Studio', 'rejoyan-crm-campaigns-for-woocommerce' ), __( 'Social Studio', 'rejoyan-crm-campaigns-for-woocommerce' ), 'manage_woocommerce', 'rejoyan-crm-social', array( $this, 'render_social' ) );
        add_submenu_page( 'rejoyan-crm', __( 'Delivery Analytics', 'rejoyan-crm-campaigns-for-woocommerce' ), __( 'Analytics', 'rejoyan-crm-campaigns-for-woocommerce' ), 'manage_woocommerce', 'rejoyan-crm-analytics', array( $this, 'render_analytics' ) );
        add_submenu_page( 'rejoyan-crm', __( 'Invoices', 'rejoyan-crm-campaigns-for-woocommerce' ), __( 'Invoices', 'rejoyan-crm-campaigns-for-woocommerce' ), 'manage_woocommerce', 'rejoyan-crm-invoices', array( $this, 'render_invoices' ) );
        add_submenu_page( 'rejoyan-crm', __( 'System Health', 'rejoyan-crm-campaigns-for-woocommerce' ), __( 'System Health', 'rejoyan-crm-campaigns-for-woocommerce' ), 'manage_woocommerce', 'rejoyan-crm-health', array( $this, 'render_health' ) );
        add_submenu_page( 'rejoyan-crm', __( 'Settings', 'rejoyan-crm-campaigns-for-woocommerce' ), __( 'Settings', 'rejoyan-crm-campaigns-for-woocommerce' ), 'manage_woocommerce', 'rejoyan-crm-settings', array( $this, 'render_settings' ) );
    }

    public function enqueue_assets( $hook ) {
        if ( false === strpos( (string) $hook, 'rejoyan-crm' ) ) {
            return;
        }

        $current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( in_array( $current_page, array( 'rejoyan-crm-offers', 'rejoyan-crm-campaigns' ), true ) ) {
            if ( wp_script_is( 'wc-enhanced-select', 'registered' ) ) {
                wp_enqueue_script( 'wc-enhanced-select' );
            }
            if ( wp_style_is( 'woocommerce_admin_styles', 'registered' ) ) {
                wp_enqueue_style( 'woocommerce_admin_styles' );
            }
        }

        wp_enqueue_style( 'rejoyan-crm-admin', REJOYAN_CRM_URL . 'assets/css/admin.css', array(), REJOYAN_CRM_VERSION );
        wp_enqueue_script( 'rejoyan-crm-admin', REJOYAN_CRM_URL . 'assets/js/admin.js', array(), REJOYAN_CRM_VERSION, true );
        wp_script_add_data( 'rejoyan-crm-admin', 'strategy', 'defer' );
        wp_localize_script(
            'rejoyan-crm-admin',
            'RejoyanCRMAdmin',
            array(
                'confirmSend'      => __( 'Queue this campaign for the selected audience?', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'confirmOfferSend' => __( 'Send the selected offer to these customers? The messages will be queued in the background.', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'confirmCancel'    => __( 'Cancel this campaign? Already-sent messages cannot be recalled.', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'confirmInvoice'   => __( 'Send the WooCommerce customer invoice email for this order?', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'copied'           => __( 'Copied', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'selectCustomers'  => __( 'Select at least one customer or choose Select all customers.', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
                'lazyNonce'       => wp_create_nonce( 'rejoyan_crm_lazy_panel' ),
                'lazyError'       => __( 'This section could not be loaded. Refresh the page and try again.', 'rejoyan-crm-campaigns-for-woocommerce' ),
            )
        );
    }

    private function guard() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }
    }

    private function redirect_with_notice( $page, $message, $type = 'success' ) {
        set_transient(
            'rejoyan_crm_notice_' . get_current_user_id(),
            array( 'message' => sanitize_text_field( $message ), 'type' => sanitize_key( $type ) ),
            60
        );
        wp_safe_redirect( admin_url( 'admin.php?page=' . sanitize_key( $page ) ) );
        exit;
    }

    private function render_notice() {
        $key    = 'rejoyan_crm_notice_' . get_current_user_id();
        $notice = get_transient( $key );
        if ( ! is_array( $notice ) || empty( $notice['message'] ) ) {
            return;
        }
        delete_transient( $key );
        $class = 'error' === ( $notice['type'] ?? '' ) ? 'notice-error' : ( 'warning' === ( $notice['type'] ?? '' ) ? 'notice-warning' : 'notice-success' );
        echo '<div class="notice ' . esc_attr( $class ) . ' inline"><p>' . esc_html( $notice['message'] ) . '</p></div>';
    }

    private function render_header_menu() {
        $current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'rejoyan-crm'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $items = array(
            'rejoyan-crm'           => array( __( 'Dashboard', 'rejoyan-crm-campaigns-for-woocommerce' ), 'dashicons-dashboard' ),
            'rejoyan-crm-customers' => array( __( 'Email Center', 'rejoyan-crm-campaigns-for-woocommerce' ), 'dashicons-email-alt2' ),
            'rejoyan-crm-segments'  => array( __( 'Segments', 'rejoyan-crm-campaigns-for-woocommerce' ), 'dashicons-groups' ),
            'rejoyan-crm-campaigns' => array( __( 'Campaigns', 'rejoyan-crm-campaigns-for-woocommerce' ), 'dashicons-megaphone' ),
            'rejoyan-crm-offers'    => array( __( 'Offers', 'rejoyan-crm-campaigns-for-woocommerce' ), 'dashicons-tag' ),
            'rejoyan-crm-social'    => array( __( 'Social Studio', 'rejoyan-crm-campaigns-for-woocommerce' ), 'dashicons-share' ),
            'rejoyan-crm-analytics' => array( __( 'Analytics', 'rejoyan-crm-campaigns-for-woocommerce' ), 'dashicons-chart-bar' ),
            'rejoyan-crm-invoices'  => array( __( 'Invoices', 'rejoyan-crm-campaigns-for-woocommerce' ), 'dashicons-media-text' ),
            'rejoyan-crm-health'     => array( __( 'Health', 'rejoyan-crm-campaigns-for-woocommerce' ), 'dashicons-heart' ),
            'rejoyan-crm-settings'  => array( __( 'Settings', 'rejoyan-crm-campaigns-for-woocommerce' ), 'dashicons-admin-generic' ),
        );

        echo '<div class="rejoyan-crm-header-menu-shell">';
        echo '<div class="rejoyan-crm-header-menu-top">';
        echo '<div class="rejoyan-crm-header-brand"><span class="rejoyan-crm-header-logo dashicons dashicons-megaphone" aria-hidden="true"></span><span><strong>' . esc_html__( 'Rejoyan CRM & Campaigns', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</strong><small>' . esc_html__( 'Marketing command center', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</small></span></div>';
        echo '<div class="rejoyan-crm-header-quick"><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=rejoyan-crm-offers' ) ) . '">' . esc_html__( 'Create offer', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</a><button type="button" class="button rejoyan-crm-menu-toggle" aria-expanded="false" aria-controls="rejoyan-crm-header-nav"><span class="dashicons dashicons-menu-alt" aria-hidden="true"></span><span>' . esc_html__( 'Menu', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span></button></div>';
        echo '</div>';
        echo '<nav id="rejoyan-crm-header-nav" class="rejoyan-crm-header-nav" aria-label="' . esc_attr__( 'Rejoyan CRM & Campaigns navigation', 'rejoyan-crm-campaigns-for-woocommerce' ) . '">';

        foreach ( $items as $slug => $item ) {
            $is_active = $slug === $current_page;
            echo '<a class="rejoyan-crm-header-link' . ( $is_active ? ' is-active' : '' ) . '" href="' . esc_url( admin_url( 'admin.php?page=' . $slug ) ) . '"' . ( $is_active ? ' aria-current="page"' : '' ) . '>';
            echo '<span class="dashicons ' . esc_attr( $item[1] ) . '" aria-hidden="true"></span><span>' . esc_html( $item[0] ) . '</span></a>';
        }

        echo '</nav></div>';
    }

    private function page_header( $title, $subtitle = '' ) {
        echo '<div class="wrap rejoyan-crm-wrap">';
        $this->render_header_menu();
        echo '<div class="rejoyan-crm-heading"><div><span class="rejoyan-crm-eyebrow">' . esc_html__( 'WooCommerce Growth Suite', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span><h1>' . esc_html( $title ) . '</h1>';
        if ( $subtitle ) {
            echo '<p>' . esc_html( $subtitle ) . '</p>';
        }
        echo '</div><span class="rejoyan-crm-version">v' . esc_html( REJOYAN_CRM_VERSION ) . '</span></div>';
        $this->render_notice();
    }

    private function stat_card( $label, $value, $icon, $helper = '' ) {
        echo '<section class="rejoyan-crm-card rejoyan-crm-stat"><span class="dashicons ' . esc_attr( $icon ) . '"></span><div><strong>' . esc_html( $value ) . '</strong><small>' . esc_html( $label ) . '</small>';
        if ( $helper ) {
            echo '<em>' . esc_html( $helper ) . '</em>';
        }
        echo '</div></section>';
    }

    public function render_dashboard() {
        $this->guard();
        $this->page_header( __( 'Dashboard', 'rejoyan-crm-campaigns-for-woocommerce' ), __( 'Manage customer offers, email delivery, social sharing and store communication from one place.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        $this->lazy->placeholder( 'dashboard', 620 );
        echo '</div>';
    }

    public function render_customers() {
        $this->guard();
        $this->page_header( __( 'Customer Email Center', 'rejoyan-crm-campaigns-for-woocommerce' ), __( 'The screen opens immediately; registered customer emails and offer controls are loaded only after the page is visible.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        $this->lazy->placeholder( 'customers', 760 );
        echo '</div>';
    }

    public function render_segments() {
        $this->guard();
        $this->page_header( __( 'Customer Segments', 'rejoyan-crm-campaigns-for-woocommerce' ), __( 'Use explainable WooCommerce customer groups to target offers without manually selecting every email.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        $this->lazy->placeholder( 'segments', 520 );
        echo '</div>';
    }

    public function render_campaigns() {
        $this->guard();
        $this->page_header( __( 'Campaigns', 'rejoyan-crm-campaigns-for-woocommerce' ), __( 'Compose regular email campaigns and monitor both campaigns and offer blasts in the retry-safe background queue.', 'rejoyan-crm-campaigns-for-woocommerce' ) );

        echo '<div class="rejoyan-crm-grid rejoyan-crm-campaign-layout">';
        echo '<section class="rejoyan-crm-card rejoyan-crm-compose"><span class="rejoyan-crm-label">' . esc_html__( 'Campaign composer', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span><h2>' . esc_html__( 'Create email campaign', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</h2>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="rejoyan_crm_create_campaign">';
        wp_nonce_field( 'rejoyan_crm_create_campaign' );
        echo '<div class="rejoyan-crm-field"><label for="mf-name">' . esc_html__( 'Campaign name', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input id="mf-name" class="widefat" type="text" name="name" required placeholder="' . esc_attr__( 'October VIP newsletter', 'rejoyan-crm-campaigns-for-woocommerce' ) . '"></div>';
        echo '<div class="rejoyan-crm-field"><label for="mf-subject">' . esc_html__( 'Subject line', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input id="mf-subject" class="widefat" type="text" name="subject" required placeholder="' . esc_attr__( 'A special update for {{first_name}}', 'rejoyan-crm-campaigns-for-woocommerce' ) . '"></div>';
        echo '<div class="rejoyan-crm-field"><label for="mf-audience">' . esc_html__( 'Audience', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><select id="mf-audience" class="widefat" name="audience"><option value="optin">' . esc_html__( 'Opted-in customers only', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</option><option value="all_registered">' . esc_html__( 'All registered customers except unsubscribed', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</option>';
        foreach ( $this->segments->definitions() as $segment_key => $segment_data ) {
            echo '<option value="segment_' . esc_attr( $segment_key ) . '">' . esc_html( $segment_data['label'] ) . '</option>';
        }
        echo '</select></div>';
        echo '<div class="rejoyan-crm-field"><label>' . esc_html__( 'Target buyers of a product (optional)', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><select class="wc-product-search" style="width:100%" name="buyer_product_id" data-placeholder="' . esc_attr__( 'Search a product whose buyers should receive this campaign…', 'rejoyan-crm-campaigns-for-woocommerce' ) . '" data-action="woocommerce_json_search_products_and_variations" data-allow_clear="true"></select><small>' . esc_html__( 'If selected, this overrides the audience above and targets registered customers who previously bought that product.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</small></div>';
        echo '<div class="rejoyan-crm-field"><label>' . esc_html__( 'Send later (optional)', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input class="widefat" type="datetime-local" name="scheduled_for"><small>' . esc_html__( 'Leave empty to send immediately. Scheduling uses the WordPress site timezone.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</small></div>';
        echo '<div class="rejoyan-crm-field"><label for="mf-content">' . esc_html__( 'Email content', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><textarea id="mf-content" class="widefat" name="content" rows="12" required placeholder="' . esc_attr__( 'Hi {{first_name}},\n\nWe have something special for you…', 'rejoyan-crm-campaigns-for-woocommerce' ) . '"></textarea><small>' . esc_html__( 'Merge tags: {{first_name}}, {{last_name}}, {{email}}, {{site_name}}. An unsubscribe link is appended automatically.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</small></div>';
        echo '<label class="rejoyan-crm-check rejoyan-crm-consent-check"><input type="checkbox" name="marketing_permission_confirmed" value="1"> <span>' . esc_html__( 'Before queueing, I confirm I am allowed to send this marketing campaign to the selected audience.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span></label>';
        echo '<div class="rejoyan-crm-actions"><button class="button" type="submit" name="intent" value="draft">' . esc_html__( 'Save draft', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</button><button class="button button-primary rejoyan-crm-confirm-send" type="submit" name="intent" value="queue">' . esc_html__( 'Save & queue', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</button></div></form></section>';

        echo '<div class="rejoyan-crm-campaign-history-shell">';
        $this->lazy->placeholder( 'campaign-history', 420 );
        echo '</div></div></div>';
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
                $segment_key  = substr( (string) $campaign->audience, 8 );
                $definitions  = $this->segments->definitions();
                $audience     = isset( $definitions[ $segment_key ] ) ? $definitions[ $segment_key ]['label'] : __( 'Smart segment', 'rejoyan-crm-campaigns-for-woocommerce' );
            } elseif ( preg_match( '/^product_buyer_(\d+)$/', (string) $campaign->audience, $buyer_match ) ) {
                $buyer_product = wc_get_product( absint( $buyer_match[1] ) );
                /* translators: %s: WooCommerce product name. */
                $audience = $buyer_product ? sprintf( __( 'Bought: %s', 'rejoyan-crm-campaigns-for-woocommerce' ), $buyer_product->get_name() ) : __( 'Product buyers', 'rejoyan-crm-campaigns-for-woocommerce' );
            }
            $type     = 'offer' === (string) ( $campaign->campaign_type ?? 'standard' ) ? __( 'Offer', 'rejoyan-crm-campaigns-for-woocommerce' ) : __( 'Campaign', 'rejoyan-crm-campaigns-for-woocommerce' );
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

    public function render_offers() {
        $this->guard();
        $offers        = $this->offers->get_offers( 50, '' );
        $templates     = $this->offers->built_in_templates();
        $edit_offer_id = isset( $_GET['edit_offer'] ) ? absint( $_GET['edit_offer'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $edit_offer    = $edit_offer_id ? $this->offers->get_offer( $edit_offer_id ) : false;
        $coupons = get_posts(
            array(
                'post_type'      => 'shop_coupon',
                'post_status'    => array( 'publish', 'draft', 'pending', 'future' ),
                'posts_per_page' => 12,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'fields'         => 'ids',
            )
        );
        $this->page_header( __( 'Offers & Coupons', 'rejoyan-crm-campaigns-for-woocommerce' ), __( 'Start from a ready-made email template, link it to a WooCommerce product, then send the saved offer from Email Center.', 'rejoyan-crm-campaigns-for-woocommerce' ) );

        echo '<section class="rejoyan-crm-card rejoyan-crm-template-library"><div class="rejoyan-crm-section-head"><div><span class="rejoyan-crm-label">' . esc_html__( 'Ready-made email templates', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span><h2>' . esc_html__( 'Pick a campaign starting point', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</h2><p>' . esc_html__( 'A template fills the offer copy and email styling. You can edit everything before saving.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</p></div><span class="rejoyan-crm-template-count">' . esc_html( count( $templates ) ) . ' ' . esc_html__( 'templates', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span></div>';
        echo '<div id="rejoyan-crm-offer-template-data" data-templates="' . esc_attr( wp_json_encode( $templates ) ) . '"></div><div class="rejoyan-crm-template-grid">';
        foreach ( $templates as $template_key => $template ) {
            echo '<article class="rejoyan-crm-template-card"><div class="rejoyan-crm-template-swatch" style="--mf-template-accent:' . esc_attr( $template['accent_color'] ) . '"><span class="dashicons dashicons-email-alt2" aria-hidden="true"></span></div><div><h3>' . esc_html( $template['name'] ) . '</h3><p>' . esc_html( $template['description'] ) . '</p><div class="rejoyan-crm-template-meta"><span>' . esc_html( ucfirst( $template['template_style'] ) ) . '</span><span>' . esc_html( $template['badge'] ) . '</span></div></div><button type="button" class="button rejoyan-crm-apply-offer-template" data-template-key="' . esc_attr( $template_key ) . '">' . esc_html__( 'Use template', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</button></article>';
        }
        echo '</div></section>';

        echo '<div class="rejoyan-crm-grid rejoyan-crm-offer-builder-layout">';
        $form_title = $edit_offer ? __( 'Edit reusable offer', 'rejoyan-crm-campaigns-for-woocommerce' ) : __( 'Create reusable offer', 'rejoyan-crm-campaigns-for-woocommerce' );
        echo '<section class="rejoyan-crm-card"><span class="rejoyan-crm-label">' . esc_html__( 'Marketing offer', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span><h2>' . esc_html( $form_title ) . '</h2><form id="rejoyan-crm-offer-builder-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="rejoyan_crm_create_offer"><input type="hidden" name="offer_id" value="' . esc_attr( $edit_offer ? $edit_offer->id : 0 ) . '">';
        wp_nonce_field( 'rejoyan_crm_create_offer' );
        echo '<div class="rejoyan-crm-grid rejoyan-crm-form-two"><div class="rejoyan-crm-field"><label>' . esc_html__( 'Offer name', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input class="widefat" type="text" name="title" required value="' . esc_attr( $edit_offer ? $edit_offer->title : '' ) . '" placeholder="' . esc_attr__( 'Weekend VIP Sale', 'rejoyan-crm-campaigns-for-woocommerce' ) . '"></div><div class="rejoyan-crm-field"><label>' . esc_html__( 'Email subject', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input class="widefat" type="text" name="subject" required value="' . esc_attr( $edit_offer ? $edit_offer->subject : '' ) . '" placeholder="' . esc_attr__( '{{first_name}}, your special offer is here', 'rejoyan-crm-campaigns-for-woocommerce' ) . '"></div></div>';
        echo '<div class="rejoyan-crm-grid rejoyan-crm-form-two"><div class="rejoyan-crm-field"><label>' . esc_html__( 'Badge text', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input class="widefat" type="text" name="badge" value="' . esc_attr( $edit_offer ? $edit_offer->badge : '' ) . '" placeholder="' . esc_attr__( 'Limited Time', 'rejoyan-crm-campaigns-for-woocommerce' ) . '"></div><div class="rejoyan-crm-field"><label>' . esc_html__( 'Email headline', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input class="widefat" type="text" name="headline" required value="' . esc_attr( $edit_offer ? $edit_offer->headline : '' ) . '" placeholder="' . esc_attr__( 'Save 20% on your next order', 'rejoyan-crm-campaigns-for-woocommerce' ) . '"></div></div>';
        echo '<div class="rejoyan-crm-field"><label>' . esc_html__( 'Preheader text', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input class="widefat" type="text" name="preheader" value="' . esc_attr( $edit_offer ? $edit_offer->preheader : '' ) . '" placeholder="' . esc_attr__( 'A short inbox preview that supports the subject line.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '"><small>' . esc_html__( 'Shown by many email clients next to or below the subject.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</small></div>';
        $current_style = $edit_offer ? $edit_offer->template_style : 'classic';
        $current_color = $edit_offer ? $edit_offer->accent_color : get_option( 'rejoyan_crm_default_accent_color', '#5b4cf0' );
        echo '<div class="rejoyan-crm-grid rejoyan-crm-form-two"><div class="rejoyan-crm-field"><label>' . esc_html__( 'Template style', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><select class="widefat rejoyan-crm-offer-template-style" name="template_style"><option value="classic" ' . selected( $current_style, 'classic', false ) . '>' . esc_html__( 'Classic', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</option><option value="minimal" ' . selected( $current_style, 'minimal', false ) . '>' . esc_html__( 'Minimal', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</option><option value="spotlight" ' . selected( $current_style, 'spotlight', false ) . '>' . esc_html__( 'Spotlight', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</option></select></div><div class="rejoyan-crm-field"><label>' . esc_html__( 'Accent color', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input class="rejoyan-crm-color-input" type="color" name="accent_color" value="' . esc_attr( $current_color ) . '"></div></div>';

        echo '<div class="rejoyan-crm-product-link-box"><div class="rejoyan-crm-section-head"><div><span class="rejoyan-crm-label">' . esc_html__( 'Product-linked offer', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span><h3>' . esc_html__( 'Link this email to a WooCommerce product', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</h3><p>' . esc_html__( 'When linked, the email can use the live product name, price and image. The CTA opens the offer and then redirects to the product.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</p></div></div>';
        echo '<div class="rejoyan-crm-field"><label>' . esc_html__( 'Linked product', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><select class="wc-product-search" style="width:100%" name="product_id" data-placeholder="' . esc_attr__( 'Search for a product…', 'rejoyan-crm-campaigns-for-woocommerce' ) . '" data-action="woocommerce_json_search_products_and_variations" data-allow_clear="true">';
        if ( $edit_offer && ! empty( $edit_offer->product_id ) ) {
            $linked_product = wc_get_product( absint( $edit_offer->product_id ) );
            if ( $linked_product ) {
                echo '<option value="' . esc_attr( $linked_product->get_id() ) . '" selected>' . esc_html( wp_strip_all_tags( $linked_product->get_formatted_name() ) ) . '</option>';
            }
        }
        echo '</select><small>' . esc_html__( 'Dynamic placeholders available: {{product_name}}, {{product_price}}, {{product_url}}.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</small></div>';
        echo '<label class="rejoyan-crm-checkbox-line"><input type="checkbox" name="auto_apply_coupon" value="1" ' . checked( $edit_offer ? ! empty( $edit_offer->auto_apply_coupon ) : true, true, false ) . '> <span><strong>' . esc_html__( 'Auto-apply the coupon after offer click', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</strong><small>' . esc_html__( 'If this offer has a valid WooCommerce coupon, Rejoyan CRM & Campaigns attempts to apply it to the customer cart before redirecting to the linked product. No click tracking is stored.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</small></span></label></div>';

        echo '<div class="rejoyan-crm-field"><label>' . esc_html__( 'Offer description', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><textarea class="widefat" name="description" rows="7" required placeholder="' . esc_attr__( 'Tell customers why this offer matters and what they should do next.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '">' . esc_textarea( $edit_offer ? $edit_offer->description : '' ) . '</textarea><small>' . esc_html__( 'Customer placeholders: {{first_name}}, {{last_name}}, {{email}}, {{site_name}}. Product placeholders work when a product is linked.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</small></div>';
        echo '<div class="rejoyan-crm-grid rejoyan-crm-form-two"><div class="rejoyan-crm-field"><label>' . esc_html__( 'Coupon code (optional)', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input class="widefat" type="text" name="coupon_code" value="' . esc_attr( $edit_offer ? $edit_offer->coupon_code : '' ) . '" placeholder="VIP20"></div><div class="rejoyan-crm-field"><label>' . esc_html__( 'Expiry note (optional)', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input class="widefat" type="text" name="expiry_text" value="' . esc_attr( $edit_offer ? $edit_offer->expiry_text : '' ) . '" placeholder="' . esc_attr__( 'Offer ends Sunday at midnight', 'rejoyan-crm-campaigns-for-woocommerce' ) . '"></div></div>';
        echo '<div class="rejoyan-crm-grid rejoyan-crm-form-two"><div class="rejoyan-crm-field"><label>' . esc_html__( 'CTA button text', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input class="widefat" type="text" name="cta_text" value="' . esc_attr( $edit_offer ? $edit_offer->cta_text : __( 'View offer', 'rejoyan-crm-campaigns-for-woocommerce' ) ) . '"></div><div class="rejoyan-crm-field"><label>' . esc_html__( 'Fallback CTA URL', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input class="widefat" type="url" name="cta_url" value="' . esc_attr( $edit_offer ? $edit_offer->cta_url : ( wc_get_page_permalink( 'shop' ) ?: home_url( '/' ) ) ) . '"><small>' . esc_html__( 'Used only when no WooCommerce product is linked.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</small></div></div>';
        $start_value = ( $edit_offer && ! empty( $edit_offer->starts_at ) ) ? mysql2date( 'Y-m-d\TH:i', $edit_offer->starts_at, false ) : '';
        $end_value   = ( $edit_offer && ! empty( $edit_offer->ends_at ) ) ? mysql2date( 'Y-m-d\TH:i', $edit_offer->ends_at, false ) : '';
        echo '<div class="rejoyan-crm-grid rejoyan-crm-form-two"><div class="rejoyan-crm-field"><label>' . esc_html__( 'Offer starts (optional)', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input class="widefat" type="datetime-local" name="starts_at" value="' . esc_attr( $start_value ) . '"></div><div class="rejoyan-crm-field"><label>' . esc_html__( 'Offer ends (optional)', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input class="widefat" type="datetime-local" name="ends_at" value="' . esc_attr( $end_value ) . '"><small>' . esc_html__( 'Outside this window the offer cannot be sent or opened through its product CTA.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</small></div></div>';
        echo '<div class="rejoyan-crm-field"><label>' . esc_html__( 'Hero image URL (optional)', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input class="widefat" type="url" name="image_url" value="' . esc_attr( $edit_offer ? $edit_offer->image_url : '' ) . '" placeholder="https://example.com/offer-banner.jpg"><small>' . esc_html__( 'Leave blank to automatically use the linked product image.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</small></div>';
        echo '<div class="rejoyan-crm-actions"><button class="button button-primary" type="submit">' . esc_html( $edit_offer ? __( 'Update offer', 'rejoyan-crm-campaigns-for-woocommerce' ) : __( 'Save offer', 'rejoyan-crm-campaigns-for-woocommerce' ) ) . '</button>';
        if ( $edit_offer ) {
            echo '<a class="button" href="' . esc_url( admin_url( 'admin.php?page=rejoyan-crm-offers' ) ) . '">' . esc_html__( 'Cancel edit', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</a>';
        }
        echo '</div></form></section>';

        echo '<section class="rejoyan-crm-card"><span class="rejoyan-crm-label">' . esc_html__( 'Saved offers', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span><h2>' . esc_html__( 'Ready to email', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</h2><div class="rejoyan-crm-offer-library">';
        if ( ! $offers ) {
            echo '<p>' . esc_html__( 'No saved marketing offers yet.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</p>';
        }
        foreach ( $offers as $offer ) {
            $is_active      = 'active' === $offer->status;
            $is_available   = $this->offers->is_offer_available( $offer );
            $linked_product = $this->offers->get_linked_product( $offer );
            echo '<article class="rejoyan-crm-offer-library-card"><div class="rejoyan-crm-offer-library-top"><span class="rejoyan-crm-mini-badge">' . esc_html( $offer->badge ?: __( 'Offer', 'rejoyan-crm-campaigns-for-woocommerce' ) ) . '</span><span class="rejoyan-crm-status ' . esc_attr( $is_active ? 'good' : 'neutral' ) . '">' . esc_html( ucfirst( $offer->status ) ) . '</span></div><h3>' . esc_html( $offer->title ) . '</h3><p>' . esc_html( wp_trim_words( wp_strip_all_tags( $offer->description ), 20 ) ) . '</p><div class="rejoyan-crm-offer-meta">';
            if ( $linked_product ) {
                echo '<span class="rejoyan-crm-product-chip"><span class="dashicons dashicons-products" aria-hidden="true"></span>' . esc_html( $linked_product->get_name() ) . '</span>';
            }
            if ( $offer->coupon_code ) {
                echo '<code>' . esc_html( $offer->coupon_code ) . '</code>';
            }
            echo '<span>' . esc_html( $offer->subject ) . '</span></div><div class="rejoyan-crm-actions rejoyan-crm-offer-actions">';
            if ( $is_available ) {
                echo '<a class="button button-primary" href="' . esc_url( add_query_arg( array( 'page' => 'rejoyan-crm-customers', 'offer_id' => $offer->id ), admin_url( 'admin.php' ) ) ) . '">' . esc_html__( 'Select customers & send', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</a>';
            }
            if ( $linked_product ) {
                echo '<a class="button" target="_blank" rel="noopener noreferrer" href="' . esc_url( $linked_product->get_permalink() ) . '">' . esc_html__( 'View product', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</a>';
            }
            echo '<a class="button" href="' . esc_url( add_query_arg( array( 'page' => 'rejoyan-crm-offers', 'edit_offer' => $offer->id ), admin_url( 'admin.php' ) ) ) . '">' . esc_html__( 'Edit', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</a>';
            foreach ( array( 'duplicate' => __( 'Duplicate', 'rejoyan-crm-campaigns-for-woocommerce' ), $is_active ? 'archive' : 'restore' => $is_active ? __( 'Archive', 'rejoyan-crm-campaigns-for-woocommerce' ) : __( 'Restore', 'rejoyan-crm-campaigns-for-woocommerce' ) ) as $offer_action => $offer_label ) {
                echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="rejoyan_crm_offer_action"><input type="hidden" name="offer_id" value="' . esc_attr( $offer->id ) . '"><input type="hidden" name="do" value="' . esc_attr( $offer_action ) . '">';
                wp_nonce_field( 'rejoyan_crm_offer_action_' . $offer->id );
                echo '<button class="button" type="submit">' . esc_html( $offer_label ) . '</button></form>';
            }
            echo '</div></article>';
        }
        echo '</div></section></div>';

        echo '<section class="rejoyan-crm-card rejoyan-crm-coupon-section"><div class="rejoyan-crm-section-head"><div><span class="rejoyan-crm-label">' . esc_html__( 'WooCommerce discount', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span><h2>' . esc_html__( 'Create coupon code', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</h2></div></div><div class="rejoyan-crm-grid rejoyan-crm-two">';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="rejoyan_crm_create_coupon">';
        wp_nonce_field( 'rejoyan_crm_create_coupon' );
        echo '<div class="rejoyan-crm-field"><label>' . esc_html__( 'Coupon code', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input class="widefat" type="text" name="code" required placeholder="VIP15"></div>';
        echo '<div class="rejoyan-crm-grid rejoyan-crm-form-two"><div class="rejoyan-crm-field"><label>' . esc_html__( 'Discount type', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><select class="widefat" name="discount_type"><option value="percent">' . esc_html__( 'Percentage', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</option><option value="fixed_cart">' . esc_html__( 'Fixed cart discount', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</option></select></div><div class="rejoyan-crm-field"><label>' . esc_html__( 'Amount', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input class="widefat" type="number" min="0" step="0.01" name="amount" required value="10"></div></div>';
        echo '<div class="rejoyan-crm-grid rejoyan-crm-form-two"><div class="rejoyan-crm-field"><label>' . esc_html__( 'Usage limit', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input class="widefat" type="number" min="0" name="usage_limit" value="0"><small>' . esc_html__( '0 = unlimited', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</small></div><div class="rejoyan-crm-field"><label>' . esc_html__( 'Expiry date', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input class="widefat" type="date" name="expiry"></div></div>';
        echo '<div class="rejoyan-crm-field"><label>' . esc_html__( 'Description', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input class="widefat" type="text" name="description" placeholder="' . esc_attr__( 'VIP customer offer', 'rejoyan-crm-campaigns-for-woocommerce' ) . '"></div><button class="button" type="submit">' . esc_html__( 'Create WooCommerce coupon', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</button></form>';

        echo '<div><h3>' . esc_html__( 'Recent coupon codes', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</h3><div class="rejoyan-crm-offer-list">';
        if ( ! $coupons ) {
            echo '<p>' . esc_html__( 'No coupons found.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</p>';
        }
        foreach ( $coupons as $coupon_id ) {
            $coupon = new WC_Coupon( $coupon_id );
            echo '<div class="rejoyan-crm-offer"><div><code>' . esc_html( $coupon->get_code() ) . '</code><small>' . esc_html( $coupon->get_description() ) . '</small></div><strong>' . esc_html( $coupon->get_amount() ) . ( 'percent' === $coupon->get_discount_type() ? '%' : '' ) . '</strong></div>';
        }
        echo '</div></div></div></section></div>';
    }

    public function render_social() {
        $this->guard();
        $this->page_header( __( 'Social Studio', 'rejoyan-crm-campaigns-for-woocommerce' ), __( 'Open the workspace instantly; product cards and images are fetched only when this feature is used.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        $this->lazy->placeholder( 'social-products', 620 );
        echo '</div>';
    }

    public function render_analytics() {
        $this->guard();
        $this->page_header( __( 'Delivery Analytics', 'rejoyan-crm-campaigns-for-woocommerce' ), __( 'Privacy-friendly campaign delivery reporting without open pixels or click tracking.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        $this->lazy->placeholder( 'analytics', 620 );
        echo '</div>';
    }

    public function render_invoices() {
        $this->guard();
        $this->page_header( __( 'Invoices', 'rejoyan-crm-campaigns-for-woocommerce' ), __( 'The page shell opens first; recent WooCommerce orders are loaded only when the invoice tool is ready.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        $this->lazy->placeholder( 'invoices', 320 );
        echo '</div>';
    }

    public function render_health() {
        $this->guard();
        global $wpdb;
        $this->page_header( __( 'System Health', 'rejoyan-crm-campaigns-for-woocommerce' ), __( 'Check the queue, database, cron and email prerequisites that Rejoyan CRM & Campaigns depends on.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        $campaigns_table  = Rejoyan_CRM_DB::campaigns_table();
        $recipients_table = Rejoyan_CRM_DB::recipients_table();
        $health_cache_key = 'system_health_counts';
        $health_counts    = wp_cache_get( $health_cache_key, 'rejoyan-crm' );
        if ( false === $health_counts ) {
            // Rejoyan CRM & Campaigns uses dedicated custom tables; identifier placeholders keep table names safely quoted.
            $scheduled = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Read-only aggregate from a dedicated plugin table.
                $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE status = %s', $campaigns_table, 'scheduled' )
            );
            $active = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Read-only aggregate from a dedicated plugin table.
                $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE status IN ('preparing','queued','sending')", $campaigns_table )
            );
            $failed = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Read-only aggregate from a dedicated plugin table.
                $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE status = %s', $recipients_table, 'failed' )
            );
            $health_counts = array( 'scheduled' => $scheduled, 'active' => $active, 'failed' => $failed );
            wp_cache_set( $health_cache_key, $health_counts, 'rejoyan-crm', 30 );
        }
        $scheduled = (int) $health_counts['scheduled'];
        $active    = (int) $health_counts['active'];
        $failed    = (int) $health_counts['failed'];
        $cron_ok = ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON );
        echo '<div class="rejoyan-crm-grid rejoyan-crm-stats">';
        $this->stat_card( __( 'Scheduled campaigns', 'rejoyan-crm-campaigns-for-woocommerce' ), number_format_i18n( $scheduled ), 'dashicons-clock', __( 'waiting for send time', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        $this->stat_card( __( 'Active queue', 'rejoyan-crm-campaigns-for-woocommerce' ), number_format_i18n( $active ), 'dashicons-update', __( 'preparing or sending', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        $this->stat_card( __( 'Failed recipients', 'rejoyan-crm-campaigns-for-woocommerce' ), number_format_i18n( $failed ), 'dashicons-warning', __( 'retry from Campaigns', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        echo '</div><section class="rejoyan-crm-card"><span class="rejoyan-crm-label">' . esc_html__( 'Environment', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span><h2>' . esc_html__( 'Readiness checks', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</h2><div class="rejoyan-crm-health-list">';
        $this->health_row( __( 'WooCommerce', 'rejoyan-crm-campaigns-for-woocommerce' ), defined( 'WC_VERSION' ) ? WC_VERSION : __( 'Missing', 'rejoyan-crm-campaigns-for-woocommerce' ), defined( 'WC_VERSION' ) );
        $this->health_row( __( 'Queue engine', 'rejoyan-crm-campaigns-for-woocommerce' ), function_exists( 'as_schedule_single_action' ) ? __( 'Action Scheduler', 'rejoyan-crm-campaigns-for-woocommerce' ) : __( 'WP-Cron fallback', 'rejoyan-crm-campaigns-for-woocommerce' ), true );
        $this->health_row( __( 'WP-Cron', 'rejoyan-crm-campaigns-for-woocommerce' ), $cron_ok ? __( 'Enabled', 'rejoyan-crm-campaigns-for-woocommerce' ) : __( 'Disabled — configure a real server cron', 'rejoyan-crm-campaigns-for-woocommerce' ), $cron_ok || function_exists( 'as_schedule_single_action' ) );
        $this->health_row( __( 'Database schema', 'rejoyan-crm-campaigns-for-woocommerce' ), (string) get_option( 'rejoyan_crm_db_version', 'unknown' ), REJOYAN_CRM_DB_VERSION === get_option( 'rejoyan_crm_db_version' ) );
        $this->health_row( __( 'Sender email', 'rejoyan-crm-campaigns-for-woocommerce' ), (string) get_option( 'rejoyan_crm_sender_email', get_option( 'admin_email' ) ), is_email( get_option( 'rejoyan_crm_sender_email', get_option( 'admin_email' ) ) ) );
        echo '</div><p class="description">' . esc_html__( 'Rejoyan CRM & Campaigns can verify configuration but cannot prove inbox delivery. Use a reliable SMTP or transactional email transport and send a test campaign before production sending.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</p></section></div>';
    }

    public function render_settings() {
        $this->guard();
        $this->page_header( __( 'Settings', 'rejoyan-crm-campaigns-for-woocommerce' ), __( 'Configure sending identity, queue size, privacy behavior and environment health.', 'rejoyan-crm-campaigns-for-woocommerce' ) );

        echo '<div class="rejoyan-crm-grid rejoyan-crm-two">';
        echo '<section class="rejoyan-crm-card"><span class="rejoyan-crm-label">' . esc_html__( 'Sending', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span><h2>' . esc_html__( 'Email settings', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</h2><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="rejoyan_crm_save_settings">';
        wp_nonce_field( 'rejoyan_crm_save_settings' );
        echo '<div class="rejoyan-crm-field"><label>' . esc_html__( 'Sender name', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input class="widefat" type="text" name="sender_name" value="' . esc_attr( get_option( 'rejoyan_crm_sender_name', get_bloginfo( 'name' ) ) ) . '" required></div>';
        echo '<div class="rejoyan-crm-field"><label>' . esc_html__( 'Sender email', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input class="widefat" type="email" name="sender_email" value="' . esc_attr( get_option( 'rejoyan_crm_sender_email', get_option( 'admin_email' ) ) ) . '" required></div>';
        echo '<div class="rejoyan-crm-field"><label>' . esc_html__( 'Reply-to email', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input class="widefat" type="email" name="reply_to_email" value="' . esc_attr( get_option( 'rejoyan_crm_reply_to_email', get_option( 'admin_email' ) ) ) . '" required></div>';
        echo '<div class="rejoyan-crm-field"><label>' . esc_html__( 'Emails per batch', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input type="number" min="5" max="100" name="batch_size" value="' . esc_attr( get_option( 'rejoyan_crm_batch_size', 20 ) ) . '"><small>' . esc_html__( 'Smaller batches are safer on limited hosting. Delivery speed also depends on your mail transport.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</small></div>';
        echo '<div class="rejoyan-crm-field"><label>' . esc_html__( 'Footer text', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input class="widefat" type="text" name="footer_text" value="' . esc_attr( get_option( 'rejoyan_crm_footer_text', get_bloginfo( 'name' ) ) ) . '"></div>';
        echo '<div class="rejoyan-crm-field"><label>' . esc_html__( 'Default offer accent color', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input type="color" name="default_accent_color" value="' . esc_attr( get_option( 'rejoyan_crm_default_accent_color', '#5b4cf0' ) ) . '"></div>';
        echo '<div id="rejoyan-crm-segment-settings" class="rejoyan-crm-segment-settings"><h3>' . esc_html__( 'Customer segment thresholds', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</h3><p class="description">' . esc_html__( 'These values define the smart audiences used by Email Center and Campaigns.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</p>';
        echo '<div class="rejoyan-crm-grid rejoyan-crm-form-two"><div class="rejoyan-crm-field"><label>' . esc_html__( 'VIP lifetime spend', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input type="number" min="0" step="0.01" name="vip_spend_threshold" value="' . esc_attr( get_option( 'rejoyan_crm_vip_spend_threshold', 500 ) ) . '"></div><div class="rejoyan-crm-field"><label>' . esc_html__( 'Inactive after days', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input type="number" min="30" max="730" name="inactive_days" value="' . esc_attr( get_option( 'rejoyan_crm_inactive_days', 90 ) ) . '"></div></div>';
        echo '<div class="rejoyan-crm-grid rejoyan-crm-form-two"><div class="rejoyan-crm-field"><label>' . esc_html__( 'Recent buyer window (days)', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input type="number" min="1" max="365" name="recent_buyer_days" value="' . esc_attr( get_option( 'rejoyan_crm_recent_buyer_days', 30 ) ) . '"></div><div class="rejoyan-crm-field"><label>' . esc_html__( 'New customer window (days)', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</label><input type="number" min="1" max="365" name="new_customer_days" value="' . esc_attr( get_option( 'rejoyan_crm_new_customer_days', 30 ) ) . '"></div></div></div>';
        echo '<label class="rejoyan-crm-check"><input type="checkbox" name="delete_data" value="1" ' . checked( get_option( 'rejoyan_crm_delete_data_on_uninstall', 'no' ), 'yes', false ) . '> <span>' . esc_html__( 'Delete Rejoyan CRM & Campaigns tables and settings when the plugin is uninstalled.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span></label>';
        echo '<button class="button button-primary" type="submit">' . esc_html__( 'Save settings', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</button></form></section>';

        echo '<section class="rejoyan-crm-card"><span class="rejoyan-crm-label">' . esc_html__( 'Diagnostics', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</span><h2>' . esc_html__( 'System status', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</h2><div class="rejoyan-crm-health-list">';
        $this->health_row( __( 'WordPress', 'rejoyan-crm-campaigns-for-woocommerce' ), get_bloginfo( 'version' ), version_compare( get_bloginfo( 'version' ), '6.4', '>=' ) );
        $this->health_row( __( 'WooCommerce', 'rejoyan-crm-campaigns-for-woocommerce' ), defined( 'WC_VERSION' ) ? WC_VERSION : __( 'Active', 'rejoyan-crm-campaigns-for-woocommerce' ), true );
        $this->health_row( __( 'Queue engine', 'rejoyan-crm-campaigns-for-woocommerce' ), function_exists( 'as_enqueue_async_action' ) ? __( 'Action Scheduler', 'rejoyan-crm-campaigns-for-woocommerce' ) : __( 'WP-Cron fallback', 'rejoyan-crm-campaigns-for-woocommerce' ), true );
        $this->health_row( __( 'HPOS declaration', 'rejoyan-crm-campaigns-for-woocommerce' ), __( 'Compatible', 'rejoyan-crm-campaigns-for-woocommerce' ), true );
        $this->health_row( __( 'PHP', 'rejoyan-crm-campaigns-for-woocommerce' ), PHP_VERSION, version_compare( PHP_VERSION, '7.4', '>=' ) );
        $this->health_row( __( 'Database schema', 'rejoyan-crm-campaigns-for-woocommerce' ), (string) get_option( 'rejoyan_crm_db_version', 'unknown' ), REJOYAN_CRM_DB_VERSION === get_option( 'rejoyan_crm_db_version' ) );
        echo '</div><p class="description">' . esc_html__( 'For dependable marketing delivery, configure a transactional SMTP/API mail provider at the WordPress site level. Rejoyan CRM & Campaigns uses wp_mail() and does not bundle a third-party mail service.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</p></section>';
        echo '</div></div>';
    }

    private function health_row( $label, $value, $ok ) {
        echo '<div class="rejoyan-crm-health-row"><span>' . esc_html( $label ) . '</span><strong class="' . esc_attr( $ok ? 'good' : 'bad' ) . '">' . esc_html( $value ) . '</strong></div>';
    }

    public function handle_toggle_optin() {
        $this->guard();
        $user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
        $enable  = isset( $_POST['enable'] ) ? absint( $_POST['enable'] ) : 0;
        check_admin_referer( 'rejoyan_crm_toggle_optin_' . $user_id );

        if ( ! $user_id || ! get_userdata( $user_id ) ) {
            $this->redirect_with_notice( 'rejoyan-crm-customers', __( 'Customer not found.', 'rejoyan-crm-campaigns-for-woocommerce' ), 'error' );
        }

        update_user_meta( $user_id, 'rejoyan_crm_marketing_optin', $enable ? '1' : '0' );
        if ( $enable ) {
            delete_user_meta( $user_id, 'rejoyan_crm_unsubscribed' );
        }
        Rejoyan_CRM_DB::log_activity( 'marketing_preference_changed', __( 'Customer marketing preference updated by an administrator.', 'rejoyan-crm-campaigns-for-woocommerce' ), 'user', $user_id, array( 'enabled' => (bool) $enable ) );
        $this->redirect_with_notice( 'rejoyan-crm-customers', __( 'Marketing preference updated.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
    }

    public function handle_create_campaign() {
        $this->guard();
        check_admin_referer( 'rejoyan_crm_create_campaign' );

        $buyer_product_id = isset( $_POST['buyer_product_id'] ) ? absint( $_POST['buyer_product_id'] ) : 0;
        $audience         = isset( $_POST['audience'] ) ? sanitize_key( wp_unslash( $_POST['audience'] ) ) : 'optin';
        if ( $buyer_product_id && wc_get_product( $buyer_product_id ) ) {
            $audience = 'product_buyer_' . $buyer_product_id;
        }
        $scheduled_input = isset( $_POST['scheduled_for'] ) ? sanitize_text_field( wp_unslash( $_POST['scheduled_for'] ) ) : '';
        $scheduled       = $this->campaigns->parse_schedule_input( $scheduled_input );
        if ( is_wp_error( $scheduled ) ) {
            $this->redirect_with_notice( 'rejoyan-crm-campaigns', $scheduled->get_error_message(), 'error' );
        }

        $result = $this->campaigns->create_campaign(
            array(
                'name'     => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
                'subject'  => isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '',
                'content'  => isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '',
                'audience' => $audience,
            )
        );

        if ( is_wp_error( $result ) ) {
            $this->redirect_with_notice( 'rejoyan-crm-campaigns', $result->get_error_message(), 'error' );
        }

        $intent = isset( $_POST['intent'] ) ? sanitize_key( wp_unslash( $_POST['intent'] ) ) : 'draft';
        if ( 'queue' === $intent ) {
            if ( empty( $_POST['marketing_permission_confirmed'] ) ) {
                $this->redirect_with_notice( 'rejoyan-crm-campaigns', __( 'Confirm marketing permission before queueing this campaign.', 'rejoyan-crm-campaigns-for-woocommerce' ), 'error' );
            }
            $queued = $this->campaigns->queue_campaign( $result, $scheduled );
            if ( is_wp_error( $queued ) ) {
                $this->redirect_with_notice( 'rejoyan-crm-campaigns', $queued->get_error_message(), 'error' );
            }
            $this->redirect_with_notice( 'rejoyan-crm-campaigns', $scheduled ? __( 'Campaign saved and scheduled.', 'rejoyan-crm-campaigns-for-woocommerce' ) : __( 'Campaign saved and queued.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }

        $this->redirect_with_notice( 'rejoyan-crm-campaigns', __( 'Campaign saved as draft.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
    }

    public function handle_campaign_action() {
        $this->guard();
        $campaign_id = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;
        check_admin_referer( 'rejoyan_crm_campaign_action_' . $campaign_id );
        $action = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';

        if ( 'queue' === $action ) {
            $result  = $this->campaigns->queue_campaign( $campaign_id );
            $message = __( 'Campaign queued.', 'rejoyan-crm-campaigns-for-woocommerce' );
        } elseif ( 'cancel' === $action ) {
            $result  = $this->campaigns->cancel_campaign( $campaign_id );
            $message = __( 'Campaign cancelled.', 'rejoyan-crm-campaigns-for-woocommerce' );
        } elseif ( 'retry_failed' === $action ) {
            $result  = $this->campaigns->retry_failed( $campaign_id );
            /* translators: %d: number of failed recipients queued again. */
            $message = is_wp_error( $result ) ? '' : sprintf( __( '%d failed recipients queued for retry.', 'rejoyan-crm-campaigns-for-woocommerce' ), absint( $result ) );
        } else {
            $result  = new WP_Error( 'rejoyan_crm_invalid_action', __( 'Invalid campaign action.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
            $message = '';
        }

        if ( is_wp_error( $result ) ) {
            $this->redirect_with_notice( 'rejoyan-crm-campaigns', $result->get_error_message(), 'error' );
        }
        $this->redirect_with_notice( 'rejoyan-crm-campaigns', $message );
    }

    public function handle_send_test() {
        $this->guard();
        $campaign_id = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;
        check_admin_referer( 'rejoyan_crm_send_test_' . $campaign_id );
        $email  = isset( $_POST['test_email'] ) ? sanitize_email( wp_unslash( $_POST['test_email'] ) ) : '';
        $result = $this->campaigns->send_test( $campaign_id, $email );

        if ( is_wp_error( $result ) ) {
            $this->redirect_with_notice( 'rejoyan-crm-campaigns', $result->get_error_message(), 'error' );
        }
        $this->redirect_with_notice( 'rejoyan-crm-campaigns', __( 'Test email sent to the mail transport.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
    }

    public function handle_preview_campaign() {
        $this->guard();
        $campaign_id = isset( $_GET['campaign_id'] ) ? absint( $_GET['campaign_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        check_admin_referer( 'rejoyan_crm_preview_campaign_' . $campaign_id );
        $campaign = $this->campaigns->get_campaign( $campaign_id );
        if ( ! $campaign ) {
            wp_die( esc_html__( 'Campaign not found.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }
        $mailer = new Rejoyan_CRM_Mailer( $this->offers );
        nocache_headers();
        header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
        echo $mailer->campaign_preview_html( $campaign ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    public function handle_send_invoice() {
        $this->guard();
        check_admin_referer( 'rejoyan_crm_send_invoice' );
        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $order    = $order_id ? wc_get_order( $order_id ) : false;
        if ( ! $order ) {
            $this->redirect_with_notice( 'rejoyan-crm-invoices', __( 'Order not found.', 'rejoyan-crm-campaigns-for-woocommerce' ), 'error' );
        }

        WC()->mailer()->customer_invoice( $order );
        Rejoyan_CRM_DB::log_activity( 'invoice_sent', __( 'Customer invoice email triggered.', 'rejoyan-crm-campaigns-for-woocommerce' ), 'order', $order_id );
        /* translators: %s: WooCommerce order number. */
        $this->redirect_with_notice( 'rejoyan-crm-invoices', sprintf( __( 'Invoice email triggered for order #%s.', 'rejoyan-crm-campaigns-for-woocommerce' ), $order->get_order_number() ) );
    }

    public function handle_save_settings() {
        $this->guard();
        check_admin_referer( 'rejoyan_crm_save_settings' );

        $sender_name  = isset( $_POST['sender_name'] ) ? sanitize_text_field( wp_unslash( $_POST['sender_name'] ) ) : '';
        $sender_email = isset( $_POST['sender_email'] ) ? sanitize_email( wp_unslash( $_POST['sender_email'] ) ) : '';
        $reply_to     = isset( $_POST['reply_to_email'] ) ? sanitize_email( wp_unslash( $_POST['reply_to_email'] ) ) : '';
        $batch_size   = isset( $_POST['batch_size'] ) ? max( 5, min( 100, absint( $_POST['batch_size'] ) ) ) : 20;
        $footer_text  = isset( $_POST['footer_text'] ) ? sanitize_text_field( wp_unslash( $_POST['footer_text'] ) ) : '';
        $accent_color = isset( $_POST['default_accent_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['default_accent_color'] ) ) : '';
        $vip_spend    = isset( $_POST['vip_spend_threshold'] ) ? max( 0, (float) wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['vip_spend_threshold'] ) ) ) ) : 500;
        $inactive_days = isset( $_POST['inactive_days'] ) ? max( 30, min( 730, absint( $_POST['inactive_days'] ) ) ) : 90;
        $recent_days   = isset( $_POST['recent_buyer_days'] ) ? max( 1, min( 365, absint( $_POST['recent_buyer_days'] ) ) ) : 30;
        $new_days      = isset( $_POST['new_customer_days'] ) ? max( 1, min( 365, absint( $_POST['new_customer_days'] ) ) ) : 30;
        $delete_data  = isset( $_POST['delete_data'] ) ? 'yes' : 'no';

        if ( ! is_email( $sender_email ) || ! is_email( $reply_to ) ) {
            $this->redirect_with_notice( 'rejoyan-crm-settings', __( 'Sender and reply-to addresses must be valid email addresses.', 'rejoyan-crm-campaigns-for-woocommerce' ), 'error' );
        }

        update_option( 'rejoyan_crm_sender_name', $sender_name );
        update_option( 'rejoyan_crm_sender_email', $sender_email );
        update_option( 'rejoyan_crm_reply_to_email', $reply_to );
        update_option( 'rejoyan_crm_batch_size', $batch_size );
        update_option( 'rejoyan_crm_footer_text', $footer_text );
        update_option( 'rejoyan_crm_default_accent_color', $accent_color ?: '#5b4cf0' );
        update_option( 'rejoyan_crm_vip_spend_threshold', $vip_spend );
        update_option( 'rejoyan_crm_inactive_days', $inactive_days );
        update_option( 'rejoyan_crm_recent_buyer_days', $recent_days );
        update_option( 'rejoyan_crm_new_customer_days', $new_days );
        delete_transient( 'rejoyan_crm_segment_summary_v1' );
        update_option( 'rejoyan_crm_delete_data_on_uninstall', $delete_data );
        Rejoyan_CRM_DB::log_activity( 'settings_updated', __( 'Rejoyan CRM & Campaigns settings updated.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        $this->redirect_with_notice( 'rejoyan-crm-settings', __( 'Settings saved.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
    }

    public function handle_create_offer() {
        $this->guard();
        check_admin_referer( 'rejoyan_crm_create_offer' );

        $offer_id = isset( $_POST['offer_id'] ) ? absint( $_POST['offer_id'] ) : 0;
        $data     = array(
            'title'          => isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '',
            'subject'        => isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '',
            'badge'          => isset( $_POST['badge'] ) ? sanitize_text_field( wp_unslash( $_POST['badge'] ) ) : '',
            'headline'       => isset( $_POST['headline'] ) ? sanitize_text_field( wp_unslash( $_POST['headline'] ) ) : '',
            'description'    => isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '',
            'coupon_code'       => isset( $_POST['coupon_code'] ) ? wc_format_coupon_code( sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) ) : '',
            'product_id'        => isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0,
            'auto_apply_coupon' => isset( $_POST['auto_apply_coupon'] ) ? 1 : 0,
            'cta_text'          => isset( $_POST['cta_text'] ) ? sanitize_text_field( wp_unslash( $_POST['cta_text'] ) ) : '',
            'cta_url'        => isset( $_POST['cta_url'] ) ? esc_url_raw( wp_unslash( $_POST['cta_url'] ) ) : '',
            'image_url'      => isset( $_POST['image_url'] ) ? esc_url_raw( wp_unslash( $_POST['image_url'] ) ) : '',
            'expiry_text'    => isset( $_POST['expiry_text'] ) ? sanitize_text_field( wp_unslash( $_POST['expiry_text'] ) ) : '',
            'preheader'      => isset( $_POST['preheader'] ) ? sanitize_text_field( wp_unslash( $_POST['preheader'] ) ) : '',
            'template_style' => isset( $_POST['template_style'] ) ? sanitize_key( wp_unslash( $_POST['template_style'] ) ) : 'classic',
            'accent_color'   => isset( $_POST['accent_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['accent_color'] ) ) : '',
            'starts_at'       => isset( $_POST['starts_at'] ) ? sanitize_text_field( wp_unslash( $_POST['starts_at'] ) ) : '',
            'ends_at'         => isset( $_POST['ends_at'] ) ? sanitize_text_field( wp_unslash( $_POST['ends_at'] ) ) : '',
        );

        $result = $offer_id ? $this->offers->update_offer( $offer_id, $data ) : $this->offers->create_offer( $data );
        if ( is_wp_error( $result ) ) {
            $this->redirect_with_notice( 'rejoyan-crm-offers', $result->get_error_message(), 'error' );
        }

        $this->redirect_with_notice( 'rejoyan-crm-offers', $offer_id ? __( 'Offer updated.', 'rejoyan-crm-campaigns-for-woocommerce' ) : __( 'Offer saved. It is now available in Customer Email Center.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
    }

    public function handle_offer_action() {
        $this->guard();
        $offer_id = isset( $_POST['offer_id'] ) ? absint( $_POST['offer_id'] ) : 0;
        check_admin_referer( 'rejoyan_crm_offer_action_' . $offer_id );
        $action = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';

        if ( 'duplicate' === $action ) {
            $result  = $this->offers->duplicate_offer( $offer_id );
            $message = __( 'Offer duplicated.', 'rejoyan-crm-campaigns-for-woocommerce' );
        } elseif ( 'archive' === $action ) {
            $result  = $this->offers->set_status( $offer_id, 'archived' );
            $message = __( 'Offer archived.', 'rejoyan-crm-campaigns-for-woocommerce' );
        } elseif ( 'restore' === $action ) {
            $result  = $this->offers->set_status( $offer_id, 'active' );
            $message = __( 'Offer restored.', 'rejoyan-crm-campaigns-for-woocommerce' );
        } else {
            $result  = new WP_Error( 'rejoyan_crm_offer_action', __( 'Invalid offer action.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
            $message = '';
        }

        if ( is_wp_error( $result ) ) {
            $this->redirect_with_notice( 'rejoyan-crm-offers', $result->get_error_message(), 'error' );
        }
        $this->redirect_with_notice( 'rejoyan-crm-offers', $message );
    }

    public function handle_send_offer() {
        $this->guard();
        check_admin_referer( 'rejoyan_crm_send_offer' );

        if ( empty( $_POST['marketing_permission_confirmed'] ) ) {
            $this->redirect_with_notice( 'rejoyan-crm-customers', __( 'Confirm marketing permission before sending.', 'rejoyan-crm-campaigns-for-woocommerce' ), 'error' );
        }

        $offer_id = isset( $_POST['offer_id'] ) ? absint( $_POST['offer_id'] ) : 0;
        $offer    = $offer_id ? $this->offers->get_offer( $offer_id ) : false;
        if ( ! $offer || ! $this->offers->is_offer_available( $offer ) ) {
            $this->redirect_with_notice( 'rejoyan-crm-customers', __( 'Select a valid saved offer.', 'rejoyan-crm-campaigns-for-woocommerce' ), 'error' );
        }

        $send_all = ! empty( $_POST['select_all_customers'] );
        $segment  = isset( $_POST['audience_segment'] ) ? sanitize_key( wp_unslash( $_POST['audience_segment'] ) ) : '';
        if ( $segment && ! isset( $this->segments->definitions()[ $segment ] ) ) {
            $this->redirect_with_notice( 'rejoyan-crm-customers', __( 'Select a valid smart audience.', 'rejoyan-crm-campaigns-for-woocommerce' ), 'error' );
        }
        $user_ids = array();
        if ( ! $send_all && ! empty( $_POST['customer_ids_csv'] ) ) {
            $csv      = sanitize_text_field( wp_unslash( $_POST['customer_ids_csv'] ) );
            $user_ids = array_filter( array_map( 'absint', explode( ',', $csv ) ) );
        } elseif ( ! $send_all && ! empty( $_POST['customer_ids'] ) && is_array( $_POST['customer_ids'] ) ) {
            $user_ids = array_map( 'absint', wp_unslash( $_POST['customer_ids'] ) );
        }

        $scheduled_input = isset( $_POST['scheduled_for'] ) ? sanitize_text_field( wp_unslash( $_POST['scheduled_for'] ) ) : '';
        $scheduled       = $this->campaigns->parse_schedule_input( $scheduled_input );
        if ( is_wp_error( $scheduled ) ) {
            $this->redirect_with_notice( 'rejoyan-crm-customers', $scheduled->get_error_message(), 'error' );
        }
        $result = $this->campaigns->create_offer_campaign( $offer, $user_ids, $send_all, $segment, $scheduled );
        if ( is_wp_error( $result ) ) {
            $this->redirect_with_notice( 'rejoyan-crm-customers', $result->get_error_message(), 'error' );
        }

        $this->redirect_with_notice( 'rejoyan-crm-customers', $scheduled ? __( 'Offer email scheduled. You can monitor it from Campaigns.', 'rejoyan-crm-campaigns-for-woocommerce' ) : __( 'Offer email campaign queued. You can monitor delivery from Campaigns.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
    }

    public function handle_export_report() {
        $this->guard();
        check_admin_referer( 'rejoyan_crm_export_report' );

        $rows = $this->reports->campaign_rows( 500 );
        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=rejoyan-crm-campaign-report-' . gmdate( 'Y-m-d' ) . '.csv' );

        $output = fopen( 'php://output', 'w' );
        if ( false === $output ) {
            wp_die( esc_html__( 'Could not open the CSV output stream.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }

        fputcsv( $output, array( 'Campaign ID', 'Name', 'Type', 'Status', 'Total', 'Sent', 'Failed', 'Skipped', 'Pending', 'Created' ), ',', '"', '' );
        foreach ( $rows as $row ) {
            fputcsv(
                $output,
                array(
                    absint( $row->id ),
                    $this->csv_cell( $row->name ),
                    (string) $row->campaign_type,
                    (string) $row->status,
                    absint( $row->total_count ),
                    absint( $row->sent_count ),
                    absint( $row->failed_count ),
                    absint( $row->skipped_count ),
                    absint( $row->pending_count ),
                    (string) $row->created_at,
                ),
                ',',
                '"',
                ''
            );
        }
        // The response ends immediately; PHP closes php://output automatically.
        exit;
    }

    public function handle_create_coupon() {
        $this->guard();
        check_admin_referer( 'rejoyan_crm_create_coupon' );

        $code          = isset( $_POST['code'] ) ? wc_format_coupon_code( sanitize_text_field( wp_unslash( $_POST['code'] ) ) ) : '';
        $discount_type = isset( $_POST['discount_type'] ) ? sanitize_key( wp_unslash( $_POST['discount_type'] ) ) : 'percent';
        $amount        = isset( $_POST['amount'] ) ? wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['amount'] ) ) ) : '0';
        $usage_limit   = isset( $_POST['usage_limit'] ) ? absint( $_POST['usage_limit'] ) : 0;
        $description   = isset( $_POST['description'] ) ? sanitize_text_field( wp_unslash( $_POST['description'] ) ) : '';
        $expiry        = isset( $_POST['expiry'] ) ? sanitize_text_field( wp_unslash( $_POST['expiry'] ) ) : '';

        if ( ! $code || ! in_array( $discount_type, array( 'percent', 'fixed_cart' ), true ) || (float) $amount < 0 ) {
            $this->redirect_with_notice( 'rejoyan-crm-offers', __( 'Please check the coupon fields.', 'rejoyan-crm-campaigns-for-woocommerce' ), 'error' );
        }
        if ( wc_get_coupon_id_by_code( $code ) ) {
            $this->redirect_with_notice( 'rejoyan-crm-offers', __( 'A coupon with that code already exists.', 'rejoyan-crm-campaigns-for-woocommerce' ), 'error' );
        }

        try {
            $coupon = new WC_Coupon();
            $coupon->set_code( $code );
            $coupon->set_discount_type( $discount_type );
            $coupon->set_amount( $amount );
            $coupon->set_description( $description );
            if ( $usage_limit > 0 ) {
                $coupon->set_usage_limit( $usage_limit );
            }
            if ( $expiry ) {
                $date = wc_string_to_datetime( $expiry . ' 23:59:59' );
                if ( $date ) {
                    $coupon->set_date_expires( $date );
                }
            }
            $coupon->save();
            Rejoyan_CRM_DB::log_activity( 'coupon_created', __( 'Coupon created.', 'rejoyan-crm-campaigns-for-woocommerce' ), 'coupon', $coupon->get_id(), array( 'code' => $code ) );
        } catch ( Exception $e ) {
            $this->redirect_with_notice( 'rejoyan-crm-offers', __( 'WooCommerce could not create the coupon.', 'rejoyan-crm-campaigns-for-woocommerce' ), 'error' );
        }

        $this->redirect_with_notice( 'rejoyan-crm-offers', __( 'Coupon created.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
    }
}
