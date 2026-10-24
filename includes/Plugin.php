<?php

declare(strict_types=1);

namespace S2J\InquiryDestination;

use S2J\InquiryDestination\Admin\SettingsPage;
use S2J\InquiryDestination\SnowMonkey\MailerHook;
use S2J\InquiryDestination\Support\ServiceGate;

final class Plugin
{
    public static function init(): void
    {
        add_action('plugins_loaded', array( self::class, 'bootstrap' ));
    }

    public static function bootstrap(): void
    {
        load_plugin_textdomain(
            's2j-inquiry-destination',
            false,
            dirname(S2J_INQUIRY_DESTINATION_PLUGIN_BASENAME) . '/languages'
        );

        SettingsPage::register();

        if (ServiceGate::isAvailable()) {
            MailerHook::register();
            return;
        }

        if (is_admin()) {
            add_action(
                'admin_notices',
                static function (): void {
                    if (! current_user_can('manage_options')) {
                        return;
                    }
                    echo '<div class="notice notice-error"><p>';
                    echo esc_html__(
                        'S2J Inquiry Destination: s2j/inquiry-destination-service is missing. Run composer install in the plugin directory, or use the distribution zip that includes vendor/.',
                        's2j-inquiry-destination'
                    );
                    echo '</p></div>';
                }
            );
        }
    }
}
