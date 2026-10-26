<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\SnowMonkey;

use RuntimeException;
use S2J\InquiryDestination\Dispatch\RunContext;
use S2J\InquiryDestination\Dispatch\Runner;
use S2J\InquiryDestination\Logging\InquiryLogger;
use S2J\InquiryDestination\Support\SnowMonkeyForms;
use Snow_Monkey\Plugin\Forms\App\Model\MailParser;
use Snow_Monkey\Plugin\Forms\App\Model\Responser;
use Snow_Monkey\Plugin\Forms\App\Model\Setting;

final class MailerHook
{
    public static function register(): void
    {
        if (! SnowMonkeyForms::isActive()) {
            return;
        }

        add_filter('snow_monkey_forms/administrator_mailer/skip', array( self::class, 'filterAdministratorSkip' ), 10, 3);
        add_filter('snow_monkey_forms/administrator_mailer/is_sended', array( self::class, 'filterAdministratorIsSended' ), 10, 3);
        add_action('snow_monkey_forms/administrator_mailer/after_send', array( self::class, 'afterAdministratorSend' ), 10, 4);
        add_filter('snow_monkey_forms/auto_reply_mailer/is_sended', array( self::class, 'filterAutoReplyIsSended' ), 10, 3);
        add_action('wp_mail_failed', array( self::class, 'onWpMailFailed' ), 10, 1);
        add_filter('snow_monkey_forms/system_error/message', array( self::class, 'filterSystemErrorMessage' ), 10, 3);
        add_filter('snow_monkey_forms/complete/message', array( self::class, 'filterCompleteMessage' ), 10, 3);
    }

    /**
     * @param mixed $skip
     */
    public static function filterAdministratorSkip($skip, Responser $responser, Setting $setting): bool
    {
        unset($responser);

        $form_id = (int) $setting->get('form_id');

        return FormRegistry::isMappedForm($form_id) ? true : (bool) $skip;
    }

    /**
     * @param mixed $is_sended
     */
    public static function filterAdministratorIsSended($is_sended, Responser $responser, Setting $setting): bool
    {
        $form_id = (int) $setting->get('form_id');
        if (! FormRegistry::isMappedForm($form_id)) {
            return (bool) $is_sended;
        }

        RequestContext::reset();
        RequestContext::setFormId($form_id);

        $form_row = FormRegistry::getFormRow($form_id);
        if (null === $form_row) {
            return (bool) $is_sended;
        }

        $mail_parser = new MailParser($responser, $setting);
        $attachments = array_values(
            $mail_parser->get_attachments($setting->get('administrator_email_body'))
        );

        $ctx = new RunContext($setting, $responser, $form_id, $form_row, $attachments);

        try {
            Runner::run($ctx);
        } catch (RuntimeException $e) {
            unset($e);
            throw new RuntimeException('[S2J Inquiry Destination] administrator mail failed.');
        }

        return true;
    }

    public static function afterAdministratorSend(bool $is_sended, Responser $responser, Setting $setting, MailParser $mail_parser): void
    {
        unset($responser, $mail_parser);

        if (! $is_sended) {
            return;
        }

        $form_id = (int) $setting->get('form_id');
        if (! FormRegistry::isMappedForm($form_id)) {
            return;
        }

        if (RequestContext::administratorMailSucceeded()) {
            RequestContext::beginAutoReplyPhase();
        }
    }

    /**
     * @param mixed $is_sended
     */
    public static function filterAutoReplyIsSended($is_sended, Responser $responser, Setting $setting): bool
    {
        unset($responser);

        $form_id = (int) $setting->get('form_id');
        if (! FormRegistry::isMappedForm($form_id)) {
            return (bool) $is_sended;
        }

        if (RequestContext::administratorMailSucceeded() && RequestContext::autoreplyFailed()) {
            return true;
        }

        return (bool) $is_sended;
    }

    /**
     * WordPress fires {@see wp_mail_failed} with a single {@see WP_Error} (not a filter).
     */
    public static function onWpMailFailed(\WP_Error $error): void
    {
        unset($error);

        if (! RequestContext::inAutoReplyPhase() || ! RequestContext::administratorMailSucceeded()) {
            return;
        }

        $correlation_id = RequestContext::getCorrelationId();
        $form_id        = RequestContext::getFormId();
        $inquiry_type   = '';

        $form_row = FormRegistry::getFormRow($form_id);
        if (null !== $form_row) {
            $inquiry_type = $form_row['inquiry_type'];
        }

        InquiryLogger::logAttempt($correlation_id, $inquiry_type, $form_id, false, 'autoreply_failed');
        RequestContext::markAutoreplyFailed();
    }

    public static function filterSystemErrorMessage(string $message, Responser $responser, Setting $setting): string
    {
        unset($responser);

        $form_id = (int) $setting->get('form_id');
        if (! FormRegistry::isMappedForm($form_id)) {
            return $message;
        }

        $correlation_id = RequestContext::getCorrelationId();
        if ('' === $correlation_id) {
            return $message;
        }

        return self::visitorErrorMessage($correlation_id);
    }

    public static function filterCompleteMessage(string $message, Responser $responser, Setting $setting): string
    {
        unset($responser);

        $form_id = (int) $setting->get('form_id');
        if (! FormRegistry::isMappedForm($form_id) || ! RequestContext::autoreplyFailed()) {
            return $message;
        }

        $notice = self::autoreplyNotice(RequestContext::getCorrelationId());

        return $message . $notice;
    }

    private static function visitorErrorMessage(string $correlation_id): string
    {
        return sprintf(
            /* translators: %s: correlation ID (non-PII) */
            __('We could not send your inquiry. Please try again later. Reference: %s', 's2j-inquiry-destination'),
            esc_html($correlation_id)
        );
    }

    private static function autoreplyNotice(string $correlation_id): string
    {
        $text = sprintf(
            /* translators: %s: correlation ID (non-PII) */
            __('Your inquiry was received, but we could not send a confirmation email. No resend is needed. Reference: %s', 's2j-inquiry-destination'),
            esc_html($correlation_id)
        );

        return '<div class="notice notice-warning s2j-inquiry-destination-autoreply-notice"><p>' . wp_kses_post($text) . '</p></div>';
    }
}
