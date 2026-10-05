<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Rejoyan_CRM_Offers {
    private $table;

    public function __construct() {
        $this->table = Rejoyan_CRM_DB::offers_table();
    }

    private function cache_version() {
        $version = wp_cache_get( 'offers_version', 'rejoyan-crm' );
        if ( false === $version ) {
            $version = '1';
            wp_cache_set( 'offers_version', $version, 'rejoyan-crm' );
        }
        return (string) $version;
    }

    private function invalidate_cache( $offer_id = 0 ) {
        if ( $offer_id ) {
            wp_cache_delete( 'offer_' . absint( $offer_id ), 'rejoyan-crm' );
        }
        wp_cache_set( 'offers_version', (string) microtime( true ), 'rejoyan-crm' );
        Rejoyan_CRM_DB::invalidate_runtime_caches();
    }

    public function built_in_templates() {
        return array(
            'flash_sale' => array(
                'name'           => __( 'Flash Sale', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'description'    => __( 'Urgent, short-window discount campaign.', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'subject'        => __( '⚡ {{first_name}}, a limited-time deal on {{product_name}}', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'badge'          => __( 'Flash Sale', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'headline'       => __( 'Your limited-time offer is live', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'body'           => __( 'Hi {{first_name}}, we picked a special deal on {{product_name}} for you. Shop while the offer is available and enjoy the promotional price.', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'preheader'      => __( 'A limited-time product offer is waiting for you.', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'cta_text'       => __( 'View flash offer', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'expiry_text'    => __( 'Limited-time offer. Availability and terms may change.', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'template_style' => 'spotlight',
                'accent_color'   => '#dc2626',
            ),
            'new_arrival' => array(
                'name'           => __( 'New Arrival', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'description'    => __( 'Introduce a newly launched product.', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'subject'        => __( 'New: meet {{product_name}}', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'badge'          => __( 'Just In', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'headline'       => __( 'Something new just arrived', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'body'           => __( 'Hi {{first_name}}, discover {{product_name}} now available from {{site_name}}. Take a closer look and see if it belongs in your next order.', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'preheader'      => __( 'Take a first look at our newest product.', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'cta_text'       => __( 'Explore the product', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'expiry_text'    => '',
                'template_style' => 'minimal',
                'accent_color'   => '#2563eb',
            ),
            'vip_exclusive' => array(
                'name'           => __( 'VIP Exclusive', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'description'    => __( 'Premium offer for VIP or high-value customers.', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'subject'        => __( '{{first_name}}, an exclusive {{site_name}} offer for you', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'badge'          => __( 'VIP Exclusive', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'headline'       => __( 'A private offer, selected for you', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'body'           => __( 'Thank you for being a valued customer. Enjoy this special offer on {{product_name}} and use your coupon if one is included below.', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'preheader'      => __( 'Your VIP product offer is ready.', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'cta_text'       => __( 'Unlock VIP offer', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'expiry_text'    => __( 'Exclusive offer subject to availability.', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'template_style' => 'classic',
                'accent_color'   => '#7c3aed',
            ),
            'weekend_deal' => array(
                'name'           => __( 'Weekend Deal', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'description'    => __( 'Weekend promotion with a friendly sales tone.', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'subject'        => __( 'Your weekend deal on {{product_name}} is here', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'badge'          => __( 'Weekend Deal', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'headline'       => __( 'Make your weekend order count', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'body'           => __( 'Hi {{first_name}}, this weekend is a great time to pick up {{product_name}}. Open the offer below to see the product and apply the included coupon automatically when available.', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'preheader'      => __( 'A weekend-only product promotion from {{site_name}}.', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'cta_text'       => __( 'Shop weekend deal', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'expiry_text'    => __( 'Weekend promotion. Check the product page for current availability.', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'template_style' => 'spotlight',
                'accent_color'   => '#ea580c',
            ),
            'product_spotlight' => array(
                'name'           => __( 'Product Spotlight', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'description'    => __( 'Feature one product with image, price and a clear CTA.', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'subject'        => __( 'Spotlight: {{product_name}}', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'badge'          => __( 'Product Spotlight', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'headline'       => __( 'Take a closer look at {{product_name}}', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'body'           => __( '{{product_name}} is currently available for {{product_price}}. We have linked this email directly to the product so you can review the offer in one click.', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'preheader'      => __( 'Product details, price and offer in one click.', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'cta_text'       => __( 'View product offer', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'expiry_text'    => '',
                'template_style' => 'classic',
                'accent_color'   => '#0f766e',
            ),
            'back_in_stock' => array(
                'name'           => __( 'Back in Stock', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'description'    => __( 'Bring customers back to a product that is available again.', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'subject'        => __( '{{product_name}} is back in stock', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'badge'          => __( 'Back in Stock', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'headline'       => __( 'It is available again', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'body'           => __( 'Good news, {{first_name}} — {{product_name}} is available again. Follow the button below to view the product and complete your order while stock lasts.', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'preheader'      => __( 'The product you may have been waiting for is available again.', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'cta_text'       => __( 'View availability', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'expiry_text'    => __( 'Available while stock lasts.', 'rejoyan-crm-campaigns-for-woocommerce' ),
                'template_style' => 'minimal',
                'accent_color'   => '#16a34a',
            ),
        );
    }

    private function normalize_product_id( $product_id ) {
        $product_id = absint( $product_id );
        if ( ! $product_id ) {
            return 0;
        }
        $product = wc_get_product( $product_id );
        return ( $product && 'publish' === get_post_status( $product_id ) ) ? $product_id : 0;
    }

    private function normalize_datetime( $value ) {
        $value = sanitize_text_field( (string) $value );
        if ( '' === $value ) {
            return null;
        }
        $timezone = wp_timezone();
        $date = DateTimeImmutable::createFromFormat( 'Y-m-d\TH:i', $value, $timezone );
        return $date ? $date->format( 'Y-m-d H:i:s' ) : null;
    }

    public function is_offer_available( $offer ) {
        if ( ! $offer || 'active' !== (string) $offer->status ) {
            return false;
        }
        $now = current_time( 'mysql' );
        if ( ! empty( $offer->starts_at ) && $offer->starts_at > $now ) {
            return false;
        }
        if ( ! empty( $offer->ends_at ) && $offer->ends_at < $now ) {
            return false;
        }
        return true;
    }

    public function create_offer( array $data ) {
        global $wpdb;

        $title             = sanitize_text_field( $data['title'] ?? '' );
        $subject           = sanitize_text_field( $data['subject'] ?? '' );
        $badge             = sanitize_text_field( $data['badge'] ?? '' );
        $headline          = sanitize_text_field( $data['headline'] ?? '' );
        $description       = wp_kses_post( $data['description'] ?? '' );
        $coupon_code       = isset( $data['coupon_code'] ) ? wc_format_coupon_code( $data['coupon_code'] ) : '';
        $product_id        = $this->normalize_product_id( $data['product_id'] ?? 0 );
        $auto_apply_coupon = empty( $data['auto_apply_coupon'] ) ? 0 : 1;
        $cta_text          = sanitize_text_field( $data['cta_text'] ?? '' );
        $cta_url           = esc_url_raw( $data['cta_url'] ?? '' );
        $image_url         = esc_url_raw( $data['image_url'] ?? '' );
        $expiry_text       = sanitize_text_field( $data['expiry_text'] ?? '' );
        $preheader         = sanitize_text_field( $data['preheader'] ?? '' );
        $template_style    = in_array( $data['template_style'] ?? 'classic', array( 'classic', 'minimal', 'spotlight' ), true ) ? $data['template_style'] : 'classic';
        $accent_color      = sanitize_hex_color( $data['accent_color'] ?? '' );
        $starts_at         = $this->normalize_datetime( $data['starts_at'] ?? '' );
        $ends_at           = $this->normalize_datetime( $data['ends_at'] ?? '' );
        if ( ! $accent_color ) {
            $accent_color = sanitize_hex_color( get_option( 'rejoyan_crm_default_accent_color', '#5b4cf0' ) ) ?: '#5b4cf0';
        }

        if ( '' === $title || '' === $subject || '' === $headline || '' === trim( wp_strip_all_tags( $description ) ) ) {
            return new WP_Error( 'rejoyan_crm_offer_invalid', __( 'Offer title, subject, headline and description are required.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }

        if ( $starts_at && $ends_at && $ends_at <= $starts_at ) {
            return new WP_Error( 'rejoyan_crm_offer_dates', __( 'Offer end time must be later than the start time.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }

        if ( $cta_text && ! $cta_url && ! $product_id ) {
            return new WP_Error( 'rejoyan_crm_offer_cta_url', __( 'Link a WooCommerce product or add a valid CTA URL when using CTA button text.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }

        $now = current_time( 'mysql' );
        $ok  = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Offer data is stored in a dedicated Rejoyan CRM & Campaigns table.
            $this->table,
            array(
                'title'             => $title,
                'subject'           => $subject,
                'badge'             => $badge,
                'headline'          => $headline,
                'description'       => $description,
                'coupon_code'       => $coupon_code,
                'product_id'        => $product_id,
                'auto_apply_coupon' => $auto_apply_coupon,
                'cta_text'          => $cta_text,
                'cta_url'           => $cta_url,
                'image_url'         => $image_url,
                'expiry_text'       => $expiry_text,
                'preheader'         => $preheader,
                'template_style'    => $template_style,
                'accent_color'      => $accent_color,
                'starts_at'         => $starts_at,
                'ends_at'           => $ends_at,
                'status'            => 'active',
                'created_by'        => get_current_user_id(),
                'created_at'        => $now,
                'updated_at'        => $now,
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
        );

        if ( false === $ok ) {
            return new WP_Error( 'rejoyan_crm_offer_db', __( 'The offer could not be saved.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }

        $offer_id = (int) $wpdb->insert_id;
        $this->invalidate_cache( $offer_id );
        Rejoyan_CRM_DB::log_activity( 'offer_created', __( 'Offer created.', 'rejoyan-crm-campaigns-for-woocommerce' ), 'offer', $offer_id, array( 'product_id' => $product_id ) );
        return $offer_id;
    }

    public function update_offer( $offer_id, array $data ) {
        global $wpdb;

        $offer_id = absint( $offer_id );
        $offer    = $this->get_offer( $offer_id );
        if ( ! $offer ) {
            return new WP_Error( 'rejoyan_crm_offer_missing', __( 'Offer not found.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }

        $title             = sanitize_text_field( $data['title'] ?? '' );
        $subject           = sanitize_text_field( $data['subject'] ?? '' );
        $badge             = sanitize_text_field( $data['badge'] ?? '' );
        $headline          = sanitize_text_field( $data['headline'] ?? '' );
        $description       = wp_kses_post( $data['description'] ?? '' );
        $coupon_code       = isset( $data['coupon_code'] ) ? wc_format_coupon_code( $data['coupon_code'] ) : '';
        $product_id        = $this->normalize_product_id( $data['product_id'] ?? 0 );
        $auto_apply_coupon = empty( $data['auto_apply_coupon'] ) ? 0 : 1;
        $cta_text          = sanitize_text_field( $data['cta_text'] ?? '' );
        $cta_url           = esc_url_raw( $data['cta_url'] ?? '' );
        $image_url         = esc_url_raw( $data['image_url'] ?? '' );
        $expiry_text       = sanitize_text_field( $data['expiry_text'] ?? '' );
        $preheader         = sanitize_text_field( $data['preheader'] ?? '' );
        $template_style    = in_array( $data['template_style'] ?? 'classic', array( 'classic', 'minimal', 'spotlight' ), true ) ? $data['template_style'] : 'classic';
        $accent_color      = sanitize_hex_color( $data['accent_color'] ?? '' );
        $starts_at         = $this->normalize_datetime( $data['starts_at'] ?? '' );
        $ends_at           = $this->normalize_datetime( $data['ends_at'] ?? '' );
        if ( ! $accent_color ) {
            $accent_color = sanitize_hex_color( get_option( 'rejoyan_crm_default_accent_color', '#5b4cf0' ) ) ?: '#5b4cf0';
        }

        if ( '' === $title || '' === $subject || '' === $headline || '' === trim( wp_strip_all_tags( $description ) ) ) {
            return new WP_Error( 'rejoyan_crm_offer_invalid', __( 'Offer title, subject, headline and description are required.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }
        if ( $starts_at && $ends_at && $ends_at <= $starts_at ) {
            return new WP_Error( 'rejoyan_crm_offer_dates', __( 'Offer end time must be later than the start time.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }
        if ( $cta_text && ! $cta_url && ! $product_id ) {
            return new WP_Error( 'rejoyan_crm_offer_cta_url', __( 'Link a WooCommerce product or add a valid CTA URL when using CTA button text.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }

        $ok = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Offer updates must be written immediately to the dedicated plugin table.
            $this->table,
            array(
                'title'             => $title,
                'subject'           => $subject,
                'badge'             => $badge,
                'headline'          => $headline,
                'description'       => $description,
                'coupon_code'       => $coupon_code,
                'product_id'        => $product_id,
                'auto_apply_coupon' => $auto_apply_coupon,
                'cta_text'          => $cta_text,
                'cta_url'           => $cta_url,
                'image_url'         => $image_url,
                'expiry_text'       => $expiry_text,
                'preheader'         => $preheader,
                'template_style'    => $template_style,
                'accent_color'      => $accent_color,
                'starts_at'         => $starts_at,
                'ends_at'           => $ends_at,
                'updated_at'        => current_time( 'mysql' ),
            ),
            array( 'id' => $offer_id ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
            array( '%d' )
        );

        if ( false === $ok ) {
            return new WP_Error( 'rejoyan_crm_offer_db', __( 'The offer could not be updated.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }
        $this->invalidate_cache( $offer_id );
        Rejoyan_CRM_DB::log_activity( 'offer_updated', __( 'Offer updated.', 'rejoyan-crm-campaigns-for-woocommerce' ), 'offer', $offer_id, array( 'product_id' => $product_id ) );
        return $offer_id;
    }

    public function duplicate_offer( $offer_id ) {
        $offer = $this->get_offer( $offer_id );
        if ( ! $offer ) {
            return new WP_Error( 'rejoyan_crm_offer_missing', __( 'Offer not found.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }

        return $this->create_offer(
            array(
                /* translators: %s: original offer title. */
                'title'             => sprintf( __( '%s (Copy)', 'rejoyan-crm-campaigns-for-woocommerce' ), $offer->title ),
                'subject'           => $offer->subject,
                'badge'             => $offer->badge,
                'headline'          => $offer->headline,
                'description'       => $offer->description,
                'coupon_code'       => $offer->coupon_code,
                'product_id'        => isset( $offer->product_id ) ? $offer->product_id : 0,
                'auto_apply_coupon' => isset( $offer->auto_apply_coupon ) ? $offer->auto_apply_coupon : 1,
                'cta_text'          => $offer->cta_text,
                'cta_url'           => $offer->cta_url,
                'image_url'         => $offer->image_url,
                'expiry_text'       => $offer->expiry_text,
                'preheader'         => $offer->preheader,
                'template_style'    => $offer->template_style,
                'accent_color'      => $offer->accent_color,
                'starts_at'         => ! empty( $offer->starts_at ) ? mysql2date( 'Y-m-d\TH:i', $offer->starts_at, false ) : '',
                'ends_at'           => ! empty( $offer->ends_at ) ? mysql2date( 'Y-m-d\TH:i', $offer->ends_at, false ) : '',
            )
        );
    }

    public function set_status( $offer_id, $status ) {
        global $wpdb;

        $offer_id = absint( $offer_id );
        $status   = in_array( $status, array( 'active', 'archived' ), true ) ? $status : '';
        if ( ! $offer_id || ! $status || ! $this->get_offer( $offer_id ) ) {
            return new WP_Error( 'rejoyan_crm_offer_invalid_status', __( 'Offer action is invalid.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }

        $ok = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Offer status writes must be immediate.
            $this->table,
            array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) ),
            array( 'id' => $offer_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );
        if ( false === $ok ) {
            return new WP_Error( 'rejoyan_crm_offer_db', __( 'The offer status could not be changed.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }
        $this->invalidate_cache( $offer_id );
        Rejoyan_CRM_DB::log_activity( 'offer_status_changed', __( 'Offer status changed.', 'rejoyan-crm-campaigns-for-woocommerce' ), 'offer', $offer_id, array( 'status' => $status ) );
        return true;
    }

    public function get_offer( $offer_id ) {
        global $wpdb;

        $offer_id = absint( $offer_id );
        if ( ! $offer_id ) {
            return false;
        }
        $cache_key = 'offer_' . $offer_id;
        $cached    = wp_cache_get( $cache_key, 'rejoyan-crm' );
        if ( false !== $cached ) {
            return $cached;
        }

        $offer = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Cached lookup from a dedicated plugin table.
            $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $this->table, $offer_id )
        );
        wp_cache_set( $cache_key, $offer ?: 0, 'rejoyan-crm', 300 );
        return $offer ?: false;
    }

    public function get_offers( $limit = 100, $status = 'active' ) {
        global $wpdb;

        $limit     = max( 1, min( 500, absint( $limit ) ) );
        $status    = $status ? sanitize_key( $status ) : '';
        $cache_key = 'offers_' . md5( $this->cache_version() . '|' . $status . '|' . $limit );
        $cached    = wp_cache_get( $cache_key, 'rejoyan-crm' );
        if ( is_array( $cached ) ) {
            return $cached;
        }

        if ( $status ) {
            $offers = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Cached list from a dedicated plugin table.
                $wpdb->prepare( 'SELECT * FROM %i WHERE status = %s ORDER BY id DESC LIMIT %d', $this->table, $status, $limit )
            );
        } else {
            $offers = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Cached list from a dedicated plugin table.
                $wpdb->prepare( 'SELECT * FROM %i ORDER BY id DESC LIMIT %d', $this->table, $limit )
            );
        }
        wp_cache_set( $cache_key, $offers, 'rejoyan-crm', 300 );
        return $offers;
    }

    public function get_linked_product( $offer ) {
        if ( ! $offer || empty( $offer->product_id ) ) {
            return false;
        }
        $product = wc_get_product( absint( $offer->product_id ) );
        return $product && 'publish' === get_post_status( $product->get_id() ) ? $product : false;
    }

    public function offer_click_url( $offer ) {
        if ( $offer && ! empty( $offer->id ) && $this->get_linked_product( $offer ) ) {
            return add_query_arg( 'rejoyan_crm_offer', absint( $offer->id ), home_url( '/' ) );
        }
        return $offer && ! empty( $offer->cta_url ) ? esc_url_raw( $offer->cta_url ) : ( wc_get_page_permalink( 'shop' ) ?: home_url( '/' ) );
    }

    public function handle_offer_click() {
        if ( ! isset( $_GET['rejoyan_crm_offer'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        $offer_id = absint( wp_unslash( $_GET['rejoyan_crm_offer'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $offer    = $offer_id ? $this->get_offer( $offer_id ) : false;
        $product  = $offer ? $this->get_linked_product( $offer ) : false;

        if ( ! $offer || ! $product || ! $this->is_offer_available( $offer ) ) {
            wp_safe_redirect( wc_get_page_permalink( 'shop' ) ?: home_url( '/' ) );
            exit;
        }

        if ( ! empty( $offer->auto_apply_coupon ) && ! empty( $offer->coupon_code ) && wc_get_coupon_id_by_code( $offer->coupon_code ) ) {
            if ( function_exists( 'wc_load_cart' ) && ( ! WC()->session || ! WC()->cart ) ) {
                wc_load_cart();
            }
            if ( WC()->session ) {
                WC()->session->set( 'rejoyan_crm_pending_coupon', $offer->coupon_code );
            }
            if ( WC()->cart && ! WC()->cart->is_empty() && ! WC()->cart->has_discount( $offer->coupon_code ) ) {
                if ( WC()->cart->apply_coupon( $offer->coupon_code ) && WC()->session ) {
                    WC()->session->set( 'rejoyan_crm_pending_coupon', null );
                }
            }
        }

        wp_safe_redirect( $product->get_permalink() );
        exit;
    }

    public function apply_pending_coupon() {
        if ( ! function_exists( 'WC' ) || ! WC()->session || ! WC()->cart || WC()->cart->is_empty() ) {
            return;
        }

        $coupon_code = wc_format_coupon_code( (string) WC()->session->get( 'rejoyan_crm_pending_coupon', '' ) );
        if ( ! $coupon_code ) {
            return;
        }

        if ( WC()->cart->has_discount( $coupon_code ) ) {
            WC()->session->set( 'rejoyan_crm_pending_coupon', null );
            return;
        }

        if ( wc_get_coupon_id_by_code( $coupon_code ) && WC()->cart->apply_coupon( $coupon_code ) ) {
            WC()->session->set( 'rejoyan_crm_pending_coupon', null );
        }
    }
}
