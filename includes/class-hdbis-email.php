<?php

namespace htrxuan\hdbis;

if (!defined('ABSPATH')) {
    exit;
}

class HDBIS_Email extends \WC_Email
{

    public function __construct()
    {
        $this->id             = 'hdbis_back_in_stock';
        $this->customer_email = true;
        $this->title          = __('Back in Stock', 'hdwebmobile-back-in-stock-waitlist');
        $this->description    = __('Sent to a customer on the waitlist when the product or variation they requested is back in stock.', 'hdwebmobile-back-in-stock-waitlist');
        $this->template_html  = 'hdbis-back-in-stock.php';
        $this->template_plain = 'plain/hdbis-back-in-stock.php';
        $this->template_base  = HDBIS_PLUGIN_DIR . 'emails/';
        $this->placeholders   = array(
            '{product_name}'   => '',
            '{product_url}'    => '',
            '{unsubscribe_url}' => '',
        );

        parent::__construct();
    }

    public function get_default_subject()
    {
        return __('{product_name} is back in stock!', 'hdwebmobile-back-in-stock-waitlist');
    }

    public function get_default_heading()
    {
        return __('Good news — it\'s back!', 'hdwebmobile-back-in-stock-waitlist');
    }

    /**
     * Send the restock notification to a single subscriber.
     *
     * @param object      $subscriber Row from HDBIS_Repository (email, notify_token, ...).
     * @param \WC_Product $product    The restocked product or variation.
     * @return bool True if the email was sent.
     */
    public function trigger($subscriber, \WC_Product $product)
    {
        $this->setup_locale();

        $this->object                              = $product;
        $this->recipient                           = $subscriber->email;
        $this->placeholders['{product_name}']      = $product->get_name();
        $this->placeholders['{product_url}']       = $product->get_permalink();
        $this->placeholders['{unsubscribe_url}']   = HDBIS_Unsubscribe::build_url($subscriber->notify_token);

        if (!$this->is_enabled() || !$this->get_recipient()) {
            $this->restore_locale();
            return false;
        }

        $sent = $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());

        $this->restore_locale();

        return $sent;
    }

    public function get_content_html()
    {
        return wc_get_template_html(
            $this->template_html,
            array(
                'product'            => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'unsubscribe_url'    => $this->placeholders['{unsubscribe_url}'],
                'sent_to_admin'      => false,
                'plain_text'         => false,
                'email'              => $this,
            ),
            '',
            $this->template_base
        );
    }

    public function get_content_plain()
    {
        return wc_get_template_html(
            $this->template_plain,
            array(
                'product'            => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'unsubscribe_url'    => $this->placeholders['{unsubscribe_url}'],
                'sent_to_admin'      => false,
                'plain_text'         => true,
                'email'              => $this,
            ),
            '',
            $this->template_base
        );
    }

    public function get_default_additional_content()
    {
        return __('You received this email because you asked to be notified when this item was back in stock.', 'hdwebmobile-back-in-stock-waitlist');
    }
}
