<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Rejoyan_CRM_Mailer {
    private $offers;

    public function __construct( Rejoyan_CRM_Offers $offers ) {
        $this->offers = $offers;
    }

    public function send_campaign_to_user( $campaign, WP_User $user ) {
        $email = sanitize_email( $user->user_email );
        if ( ! is_email( $email ) ) {
            return new WP_Error( 'rejoyan_crm_invalid_email', __( 'Invalid email address.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }

        if ( 'offer' === (string) ( $campaign->campaign_type ?? 'standard' ) && ! empty( $campaign->offer_id ) ) {
            $offer = $this->offers->get_offer( absint( $campaign->offer_id ) );
            if ( $offer && $this->offers->is_offer_available( $offer ) ) {
                $subject = $this->personalize_offer( $offer->subject, $user, $offer );
                $body    = $this->wrap_offer_html( $offer, $user, $this->unsubscribe_url( $user ) );
                return $this->send( $email, $subject, $body );
            }
        }

        $subject = $this->personalize( $campaign->subject, $user );
        $content = $this->personalize( $campaign->content, $user );
        $body    = $this->wrap_html( $content, $this->unsubscribe_url( $user ) );

        return $this->send( $email, $subject, $body );
    }

    public function send_test( $campaign, $email ) {
        $email = sanitize_email( $email );
        if ( ! is_email( $email ) ) {
            return new WP_Error( 'rejoyan_crm_invalid_email', __( 'Please enter a valid test email address.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }

        $current_user = wp_get_current_user();
        if ( 'offer' === (string) ( $campaign->campaign_type ?? 'standard' ) && ! empty( $campaign->offer_id ) ) {
            $offer = $this->offers->get_offer( absint( $campaign->offer_id ) );
            if ( $offer ) {
                $subject = '[TEST] ' . $this->personalize_offer( $offer->subject, $current_user, $offer );
                $body    = $this->wrap_offer_html( $offer, $current_user, '' );
                return $this->send( $email, $subject, $body );
            }
        }

        $subject = '[TEST] ' . $this->personalize( $campaign->subject, $current_user );
        $content = $this->personalize( $campaign->content, $current_user );
        $body    = $this->wrap_html( $content, '' );

        return $this->send( $email, $subject, $body );
    }

    public function offer_preview_html( $offer ) {
        return $this->wrap_offer_html( $offer, wp_get_current_user(), '' );
    }

    public function campaign_preview_html( $campaign ) {
        $user = wp_get_current_user();
        if ( 'offer' === (string) ( $campaign->campaign_type ?? 'standard' ) && ! empty( $campaign->offer_id ) ) {
            $offer = $this->offers->get_offer( absint( $campaign->offer_id ) );
            if ( $offer ) {
                return $this->wrap_offer_html( $offer, $user, '' );
            }
        }
        return $this->wrap_html( $this->personalize( $campaign->content, $user ), '' );
    }

    private function send( $email, $subject, $body ) {
        $sender_name  = sanitize_text_field( get_option( 'rejoyan_crm_sender_name', get_bloginfo( 'name' ) ) );
        $sender_email = sanitize_email( get_option( 'rejoyan_crm_sender_email', get_option( 'admin_email' ) ) );
        $reply_to     = sanitize_email( get_option( 'rejoyan_crm_reply_to_email', $sender_email ) );
        $headers      = array( 'Content-Type: text/html; charset=UTF-8' );

        if ( $sender_email && is_email( $sender_email ) ) {
            $headers[] = 'From: ' . $sender_name . ' <' . $sender_email . '>';
        }
        if ( $reply_to && is_email( $reply_to ) ) {
            $headers[] = 'Reply-To: ' . $reply_to;
        }

        $sent = wp_mail( $email, wp_strip_all_tags( $subject ), $body, $headers );
        if ( ! $sent ) {
            return new WP_Error( 'rejoyan_crm_mail_failed', __( 'WordPress could not hand the message to the configured mail transport.', 'rejoyan-crm-campaigns-for-woocommerce' ) );
        }

        return true;
    }

    private function personalize( $text, WP_User $user ) {
        $first_name = get_user_meta( $user->ID, 'first_name', true );
        $last_name  = get_user_meta( $user->ID, 'last_name', true );

        return strtr(
            (string) $text,
            array(
                '{{first_name}}' => sanitize_text_field( $first_name ),
                '{{last_name}}'  => sanitize_text_field( $last_name ),
                '{{email}}'      => sanitize_email( $user->user_email ),
                '{{site_name}}'  => sanitize_text_field( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ),
            )
        );
    }

    private function personalize_offer( $text, WP_User $user, $offer ) {
        $text    = $this->personalize( $text, $user );
        $product = $this->offers->get_linked_product( $offer );

        if ( ! $product ) {
            return strtr(
                $text,
                array(
                    '{{product_name}}'  => '',
                    '{{product_price}}' => '',
                    '{{product_url}}'   => '',
                )
            );
        }

        $price_text = wp_strip_all_tags( html_entity_decode( $product->get_price_html(), ENT_QUOTES, get_bloginfo( 'charset' ) ?: 'UTF-8' ) );
        return strtr(
            $text,
            array(
                '{{product_name}}'  => sanitize_text_field( $product->get_name() ),
                '{{product_price}}' => sanitize_text_field( $price_text ),
                '{{product_url}}'   => esc_url_raw( $this->offers->offer_click_url( $offer ) ),
            )
        );
    }

    private function wrap_offer_html( $offer, WP_User $user, $unsubscribe_url ) {
        $site_name     = esc_html( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
        $product       = $this->offers->get_linked_product( $offer );
        $badge         = $this->personalize_offer( (string) $offer->badge, $user, $offer );
        $headline      = $this->personalize_offer( (string) $offer->headline, $user, $offer );
        $description   = $this->personalize_offer( (string) $offer->description, $user, $offer );
        $preheader     = $this->personalize_offer( (string) ( $offer->preheader ?? '' ), $user, $offer );
        $coupon        = sanitize_text_field( $offer->coupon_code );
        $cta_text      = sanitize_text_field( $offer->cta_text );
        $cta_url       = esc_url( $this->offers->offer_click_url( $offer ) );
        $image_url     = esc_url( $offer->image_url );
        $expiry        = $this->personalize_offer( (string) $offer->expiry_text, $user, $offer );
        $footer_text   = esc_html( get_option( 'rejoyan_crm_footer_text', get_bloginfo( 'name' ) ) );
        $template      = in_array( (string) ( $offer->template_style ?? 'classic' ), array( 'classic', 'minimal', 'spotlight' ), true ) ? (string) $offer->template_style : 'classic';
        $accent        = sanitize_hex_color( (string) ( $offer->accent_color ?? '' ) );
        $accent        = $accent ? $accent : '#5b4cf0';
        $header_bg     = 'minimal' === $template ? '#ffffff' : ( 'spotlight' === $template ? $accent : '#111827' );
        $header_color  = 'minimal' === $template ? '#111827' : '#ffffff';
        $outer_bg      = 'minimal' === $template ? '#ffffff' : '#f3f5f9';
        $radius        = 'minimal' === $template ? '0' : '18px';
        $border        = 'minimal' === $template ? '0' : '1px solid #e5e7eb';
        $preheader_html = $preheader ? '<div style="display:none!important;visibility:hidden;opacity:0;color:transparent;height:0;width:0;overflow:hidden;mso-hide:all">' . esc_html( $preheader ) . '</div>' : '';

        if ( ! $image_url && $product && $product->get_image_id() ) {
            $image_url = esc_url( wp_get_attachment_image_url( $product->get_image_id(), 'large' ) );
        }

        $hero = '';
        if ( $image_url ) {
            $hero = '<img src="' . $image_url . '" alt="" style="display:block;width:100%;max-height:380px;object-fit:cover;border:0">';
        }

        $badge_html = $badge ? '<div style="display:inline-block;padding:7px 12px;border-radius:999px;background:#f3f0ff;color:' . esc_attr( $accent ) . ';font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;margin-bottom:14px">' . esc_html( $badge ) . '</div>' : '';
        $coupon_html = $coupon ? '<div style="margin:26px 0;padding:18px;border:1px dashed ' . esc_attr( $accent ) . ';background:#faf9ff;border-radius:12px;text-align:center"><div style="font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px">' . esc_html__( 'Coupon code', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</div><div style="font-size:25px;font-weight:800;letter-spacing:.08em;color:#111827">' . esc_html( $coupon ) . '</div>' . ( ! empty( $offer->auto_apply_coupon ) && $product ? '<div style="font-size:11px;color:#6b7280;margin-top:6px">' . esc_html__( 'The coupon will be applied automatically when possible after clicking the offer.', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</div>' : '' ) . '</div>' : '';

        $product_html = '';
        if ( $product ) {
            $product_name  = esc_html( $product->get_name() );
            $product_price = wp_kses_post( $product->get_price_html() );
            $stock_text    = $product->is_in_stock() ? esc_html__( 'In stock', 'rejoyan-crm-campaigns-for-woocommerce' ) : esc_html__( 'Currently unavailable', 'rejoyan-crm-campaigns-for-woocommerce' );
            $product_html  = '<div style="margin:24px 0;padding:18px;border:1px solid #e5e7eb;border-radius:14px;background:#f8fafc"><div style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px">' . esc_html__( 'Linked product', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</div><div style="font-size:19px;font-weight:800;color:#111827">' . $product_name . '</div><div style="margin-top:7px;font-size:17px;color:#111827">' . $product_price . '</div><div style="margin-top:6px;font-size:12px;color:#64748b">' . $stock_text . '</div></div>';
        }

        $cta_html = ( $cta_text && $cta_url ) ? '<div style="margin:28px 0 8px;text-align:center"><a href="' . $cta_url . '" style="display:inline-block;background:' . esc_attr( $accent ) . ';color:#ffffff;text-decoration:none;padding:14px 24px;border-radius:10px;font-weight:700;font-size:15px">' . esc_html( $cta_text ) . '</a></div>' : '';
        $expiry_html = $expiry ? '<p style="margin:14px 0 0;text-align:center;color:#6b7280;font-size:12px">' . esc_html( $expiry ) . '</p>' : '';
        $footer = '<p style="margin:0;color:#6b7280;font-size:12px;line-height:1.6">' . $footer_text . '</p>';
        if ( $unsubscribe_url ) {
            $footer .= '<p style="margin:8px 0 0;color:#6b7280;font-size:12px;line-height:1.6">' . esc_html__( 'You received this marketing email from', 'rejoyan-crm-campaigns-for-woocommerce' ) . ' ' . $site_name . '. <a href="' . esc_url( $unsubscribe_url ) . '" style="color:' . esc_attr( $accent ) . '">' . esc_html__( 'Unsubscribe', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</a></p>';
        }

        $body_padding = 'spotlight' === $template ? '38px 34px' : '34px 32px';

        return '<!doctype html><html><body style="margin:0;padding:24px;background:' . esc_attr( $outer_bg ) . ';font-family:Arial,Helvetica,sans-serif;color:#172033">' .
            $preheader_html .
            '<div style="max-width:680px;margin:0 auto;background:#ffffff;border:' . esc_attr( $border ) . ';border-radius:' . esc_attr( $radius ) . ';overflow:hidden;box-shadow:0 10px 30px rgba(15,23,42,.06)">' .
            '<div style="padding:20px 28px;background:' . esc_attr( $header_bg ) . ';color:' . esc_attr( $header_color ) . ';font-size:20px;font-weight:800;border-bottom:' . ( 'minimal' === $template ? '1px solid #e5e7eb' : '0' ) . '">' . $site_name . '</div>' .
            $hero .
            '<div style="padding:' . esc_attr( $body_padding ) . '">' . $badge_html .
            '<h1 style="margin:0 0 14px;font-size:30px;line-height:1.2;color:#111827">' . esc_html( $headline ) . '</h1>' .
            '<div style="font-size:16px;line-height:1.75;color:#374151">' . wp_kses_post( wpautop( $description ) ) . '</div>' .
            $product_html . $coupon_html . $cta_html . $expiry_html . '</div>' .
            '<div style="padding:20px 28px;background:#f8fafc;border-top:1px solid #e5e7eb">' . $footer . '</div>' .
            '</div></body></html>';
    }

    private function wrap_html( $content, $unsubscribe_url ) {
        $site_name   = esc_html( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
        $footer_text = esc_html( get_option( 'rejoyan_crm_footer_text', get_bloginfo( 'name' ) ) );
        $safe_body   = wp_kses_post( wpautop( $content ) );
        $footer      = '<p style="margin:0;color:#6b7280;font-size:12px;line-height:1.6">' . $footer_text . '</p>';

        if ( $unsubscribe_url ) {
            $footer .= '<p style="margin:8px 0 0;color:#6b7280;font-size:12px;line-height:1.6">' .
                esc_html__( 'You received this marketing email from', 'rejoyan-crm-campaigns-for-woocommerce' ) . ' ' . $site_name . '. ' .
                '<a href="' . esc_url( $unsubscribe_url ) . '">' . esc_html__( 'Unsubscribe', 'rejoyan-crm-campaigns-for-woocommerce' ) . '</a></p>';
        }

        return '<!doctype html><html><body style="margin:0;padding:24px;background:#f5f7fb;font-family:Arial,sans-serif;color:#172033">' .
            '<div style="max-width:680px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden">' .
            '<div style="padding:22px 28px;background:#111827;color:#ffffff;font-size:20px;font-weight:700">' . $site_name . '</div>' .
            '<div style="padding:30px;font-size:15px;line-height:1.7">' . $safe_body . '</div>' .
            '<div style="padding:20px 28px;background:#f8fafc;border-top:1px solid #e5e7eb">' . $footer . '</div>' .
            '</div></body></html>';
    }

    private function unsubscribe_url( WP_User $user ) {
        return add_query_arg(
            array(
                'action' => 'rejoyan_crm_unsubscribe',
                'uid'    => $user->ID,
                'token'  => $this->unsubscribe_token( $user ),
            ),
            admin_url( 'admin-post.php' )
        );
    }

    public function unsubscribe_token( WP_User $user ) {
        return hash_hmac( 'sha256', $user->ID . '|' . strtolower( $user->user_email ), wp_salt( 'auth' ) );
    }
}
