<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Admin;

use S2J\InquiryDestination\Options\DispatchOptions;
use S2J\InquiryDestination\Options\FormOptions;
use S2J\InquiryDestination\Options\SaasOptions;
use S2J\InquiryDestination\Options\TypeOptions;
use S2J\InquiryDestination\Support\SaasConnectionProbe;
use S2J\InquiryDestination\Support\SaasProductCatalog;
use S2J\InquiryDestination\Support\SnowMonkeyForms;

final class SettingsPage
{
    public const MENU_SLUG = 's2j-inquiry-destination';

    private const TAB_SERVICE = 'service';

    private const TAB_TYPES = 'types';

    private const TAB_FORMS = 'forms';

    /** @var non-empty-string */
    private const EMAIL_AUTH_PLUGIN = 'email-auth/email-auth.php';

    public static function menuSlug(): string
    {
        return self::MENU_SLUG;
    }

    public static function register(): void
    {
        add_action('admin_menu', array( self::class, 'addMenu' ));
        add_action('admin_init', array( self::class, 'handlePost' ));
        add_action('admin_enqueue_scripts', array( self::class, 'enqueueAssets' ));
    }

    public static function addMenu(): void
    {
        add_options_page(
            __('S2J Inquiry Destination', 's2j-inquiry-destination'),
            __('S2J Inquiry Destination', 's2j-inquiry-destination'),
            'manage_options',
            self::MENU_SLUG,
            array( self::class, 'render' )
        );
    }

    /**
     * @param string $hook_suffix Admin hook suffix.
     */
    public static function enqueueAssets(string $hook_suffix): void
    {
        if ('settings_page_' . self::MENU_SLUG !== $hook_suffix) {
            return;
        }

        $tab = self::currentTab();

        if (self::TAB_TYPES === $tab) {
            wp_enqueue_style(
                's2j-inquiry-destination-admin-types',
                S2J_INQUIRY_DESTINATION_PLUGIN_URL . 'assets/admin/types-tab.css',
                array(),
                S2J_INQUIRY_DESTINATION_VERSION
            );

            wp_register_script('s2j-inquiry-destination-admin', false, array(), S2J_INQUIRY_DESTINATION_VERSION, true);
            $dispatch = DispatchOptions::get();
            wp_localize_script(
                's2j-inquiry-destination-admin',
                's2jInquiryDestinationTypesTab',
                array(
                    'channel'       => $dispatch['channel'],
                    'saasProduct'   => $dispatch['saas_product'],
                    'colSlug'       => __('Type (slug)', 's2j-inquiry-destination'),
                    'colLabel'      => __('Display name', 's2j-inquiry-destination'),
                    'colMailTo'     => __('Mail to (one address per line)', 's2j-inquiry-destination'),
                    'colProjectId'  => __('Project ID', 's2j-inquiry-destination'),
                    'colIssueType'  => __('Issue type ID', 's2j-inquiry-destination'),
                    'colPriorityId' => __('Priority ID', 's2j-inquiry-destination'),
                )
            );
            wp_enqueue_script('s2j-inquiry-destination-admin');
            wp_add_inline_script('s2j-inquiry-destination-admin', self::typesAddRowScript());
        }

        if (self::TAB_FORMS === $tab) {
            wp_enqueue_style(
                's2j-inquiry-destination-admin-forms',
                S2J_INQUIRY_DESTINATION_PLUGIN_URL . 'assets/admin/forms-tab.css',
                array(),
                S2J_INQUIRY_DESTINATION_VERSION
            );
        }

        if (self::TAB_SERVICE === $tab) {
            wp_register_script('s2j-inquiry-destination-admin-service', false, array(), S2J_INQUIRY_DESTINATION_VERSION, true);
            wp_enqueue_script('s2j-inquiry-destination-admin-service');
            wp_add_inline_script('s2j-inquiry-destination-admin-service', self::serviceTabScript());
        }
    }

    public static function handlePost(): void
    {
        if (! is_admin() || ! current_user_can('manage_options')) {
            return;
        }

        $post = self::postData();

        if (! isset($post['page']) || self::MENU_SLUG !== sanitize_key((string) $post['page'])) {
            return;
        }

        if (isset($post['s2j_inquiry_destination_test_saas_connection'])) {
            self::handleServiceConnectionTest();

            return;
        }

        if (isset($post['s2j_inquiry_destination_save_service'])) {
            self::handleServicePost();
        }

        if (isset($post['s2j_inquiry_destination_save_types'])) {
            self::handleTypesPost();
        }

        if (isset($post['s2j_inquiry_destination_save_forms'])) {
            self::handleFormsPost();
        }
    }

    public static function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $tab = self::currentTab();

        echo '<div class="wrap s2j-inquiry-destination-settings">';
        echo '<h1>' . esc_html__('S2J Inquiry Destination', 's2j-inquiry-destination') . '</h1>';

        settings_errors(self::MENU_SLUG);

        if (! SnowMonkeyForms::isActive()) {
            echo '<div class="notice notice-warning"><p>';
            echo esc_html__('Please activate Snow Monkey Forms. Mapped forms will not use this plugin until then.', 's2j-inquiry-destination');
            echo '</p></div>';
        }

        self::renderTabs($tab);

        if (self::TAB_SERVICE === $tab) {
            self::renderServiceTab();
        } elseif (self::TAB_TYPES === $tab) {
            self::renderTypesTab();
        } else {
            self::renderFormsTab();
        }

        echo '</div>';
    }

    private static function renderTabs(string $active): void
    {
        $tabs = array(
            self::TAB_SERVICE => __('Inquiry management service', 's2j-inquiry-destination'),
            self::TAB_TYPES   => __('Inquiry types and destinations', 's2j-inquiry-destination'),
            self::TAB_FORMS   => __('Snow Monkey Forms mapping', 's2j-inquiry-destination'),
        );

        echo '<nav class="nav-tab-wrapper wp-clearfix" aria-label="' . esc_attr__('Secondary menu', 's2j-inquiry-destination') . '">';
        foreach ($tabs as $slug => $label) {
            $url = add_query_arg(
                array(
                    'page' => self::MENU_SLUG,
                    'tab'  => $slug,
                ),
                admin_url('options-general.php')
            );
            $class = $slug === $active ? 'nav-tab nav-tab-active' : 'nav-tab';
            echo '<a href="' . esc_url($url) . '" class="' . esc_attr($class) . '">' . esc_html($label) . '</a>';
        }
        echo '</nav>';
    }

    private static function renderServiceTab(): void
    {
        $dispatch = DispatchOptions::get();
        $stored   = SaasOptions::getStored();
        $is_saas  = DispatchOptions::CHANNEL_SAAS === $dispatch['channel'];
        $product  = $dispatch['saas_product'];
        if ($is_saas && ! DispatchOptions::isValidProduct($product)) {
            $product = DispatchOptions::PRODUCT_BACKLOG;
        }
        $server         = $is_saas && SaasOptions::isConfiguredViaServer($product);
        $connection_lock = $server;

        echo '<form method="post" class="s2j-inquiry-destination-tab-service">';
        wp_nonce_field('s2j_inquiry_destination_service');
        echo '<input type="hidden" name="page" value="' . esc_attr(self::MENU_SLUG) . '" />';
        echo '<input type="hidden" name="tab" value="' . esc_attr(self::TAB_SERVICE) . '" />';

        echo '<h2>' . esc_html__('Dispatch channel', 's2j-inquiry-destination') . '</h2>';
        echo '<p class="description">' . esc_html__('Route inquiries to WordPress mail or to a configured inquiry management SaaS via the inquiry service.', 's2j-inquiry-destination') . '</p>';

        echo '<fieldset><legend class="screen-reader-text">' . esc_html__('Dispatch channel', 's2j-inquiry-destination') . '</legend>';
        echo '<label><input type="radio" name="channel" value="mail" ' . checked($dispatch['channel'], DispatchOptions::CHANNEL_MAIL, false) . ' /> '
            . esc_html__('Mail (WordPress / wp_mail)', 's2j-inquiry-destination') . '</label><br />';
        echo '<label><input type="radio" name="channel" value="saas" ' . checked($dispatch['channel'], DispatchOptions::CHANNEL_SAAS, false) . ' /> '
            . esc_html__('Inquiry management SaaS', 's2j-inquiry-destination') . '</label>';
        echo '</fieldset>';

        self::renderServiceMailAuthNotice(! $is_saas);

        echo '<h2>' . esc_html__('SaaS product', 's2j-inquiry-destination') . '</h2>';
        echo '<fieldset class="s2j-inquiry-destination-service-saas-product"><legend class="screen-reader-text">' . esc_html__('SaaS product', 's2j-inquiry-destination') . '</legend>';
        foreach (SaasProductCatalog::labels() as $slug => $label) {
            echo '<label><input type="radio" name="saas_product" value="' . esc_attr($slug) . '" '
                . checked($product, $slug, false)
                . ($is_saas ? '' : ' disabled="disabled"')
                . ' /> ' . esc_html($label) . '</label><br />';
        }
        echo '</fieldset>';

        echo '<h2>' . esc_html__('SaaS connection', 's2j-inquiry-destination') . '</h2>';
        echo '<div class="s2j-inquiry-destination-service-connection">';
        if ($server) {
            echo '<p class="s2j-inquiry-destination-service-server-notice"><strong>'
                . esc_html__('Configured on the server (environment or wp-config.php). Settings here will not override it.', 's2j-inquiry-destination')
                . '</strong></p>';
        }
        echo '<p class="description s2j-inquiry-destination-service-connection-idle' . ( $is_saas ? ' hidden' : '' ) . '">'
            . esc_html__('Select Inquiry management SaaS above to configure connection settings.', 's2j-inquiry-destination')
            . '</p>';
        echo '<div class="s2j-inquiry-destination-service-connection-panels' . ( ( $is_saas && ! $server ) ? '' : ' hidden' ) . '">';
        echo '<p class="description">' . esc_html__('Secrets are not shown after save.', 's2j-inquiry-destination') . '</p>';
        self::renderServiceConnectionPanel(
            DispatchOptions::PRODUCT_BACKLOG,
            $product,
            $stored,
            $connection_lock
        );
        self::renderServiceConnectionPanel(
            DispatchOptions::PRODUCT_ASANA,
            $product,
            $stored,
            $connection_lock
        );
        echo '</div>';
        echo '</div>';

        echo '<p class="submit s2j-inquiry-destination-service-verify-wrap' . ( $is_saas ? '' : ' hidden' ) . '">';
        echo '<button type="submit" name="s2j_inquiry_destination_test_saas_connection" id="s2j_inquiry_destination_test_saas_connection" class="button button-secondary">'
            . esc_html__('Verify connection', 's2j-inquiry-destination')
            . '</button></p>';

        submit_button(__('Save service settings', 's2j-inquiry-destination'), 'primary', 's2j_inquiry_destination_save_service');
        echo '</form>';
    }

    private static function renderTypesTab(): void
    {
        ListTableLoader::ensureLoaded();
        $dispatch = DispatchOptions::get();
        $table    = new TypesListTable($dispatch['channel'], $dispatch['saas_product']);
        $table->prepare_items();

        echo '<p class="description">';
        if (DispatchOptions::CHANNEL_SAAS === $dispatch['channel']) {
            echo esc_html__('Set a project ID per inquiry type. Backlog also requires issue type and priority IDs.', 's2j-inquiry-destination');
        } else {
            echo esc_html__('Use one mailing-list or shared inbox address when possible. Multiple addresses receive the same single To line. CC and BCC are not supported.', 's2j-inquiry-destination');
        }
        echo '</p>';

        $table->search_box(__('Search inquiry types', 's2j-inquiry-destination'), 'inquiry-type');

        echo '<form method="post">';
        wp_nonce_field('s2j_inquiry_destination_types');
        echo '<input type="hidden" name="page" value="' . esc_attr(self::MENU_SLUG) . '" />';
        echo '<input type="hidden" name="tab" value="' . esc_attr(self::TAB_TYPES) . '" />';

        echo '<div class="s2j-inquiry-types-list-table-wrap">';
        $table->display();
        echo '</div>';

        echo '<p class="submit">';
        echo '<button type="button" class="button" id="s2j-inquiry-destination-add-type-row">' . esc_html__('Add new inquiry type', 's2j-inquiry-destination') . '</button> ';
        submit_button(__('Save inquiry types', 's2j-inquiry-destination'), 'primary', 's2j_inquiry_destination_save_types', false);
        echo '</p>';
        echo '</form>';
    }

    private static function renderFormsTab(): void
    {
        $types = TypeOptions::get();
        $forms = FormOptions::get();
        if ($types === array()) {
            echo '<p>' . esc_html__('Add at least one inquiry type before mapping forms.', 's2j-inquiry-destination') . '</p>';
            return;
        }

        $default_type = 'sales';
        foreach ($types as $type_slug => $_row) {
            $default_type = $type_slug;
            break;
        }

        ListTableLoader::ensureLoaded();
        $table = new FormsListTable($types, $forms, $default_type);
        $table->prepare_items();

        $table->search_box(__('Search forms', 's2j-inquiry-destination'), 'smf-form');

        echo '<form method="post">';
        wp_nonce_field('s2j_inquiry_destination_forms');
        echo '<input type="hidden" name="page" value="' . esc_attr(self::MENU_SLUG) . '" />';
        echo '<input type="hidden" name="tab" value="' . esc_attr(self::TAB_FORMS) . '" />';

        echo '<div class="s2j-inquiry-forms-list-table-wrap">';
        $table->display();
        echo '</div>';

        submit_button(__('Save form mappings', 's2j-inquiry-destination'), 'primary', 's2j_inquiry_destination_save_forms');
        echo '</form>';
    }

    private static function handleServiceConnectionTest(): void
    {
        check_admin_referer('s2j_inquiry_destination_service');

        $post = self::postData();

        $dispatch_result = DispatchOptions::sanitizeFromPost($post);
        if (null !== $dispatch_result['error']) {
            add_settings_error(self::MENU_SLUG, 'service_test_error', $dispatch_result['error'], 'error');

            return;
        }

        $settings = $dispatch_result['settings'];
        if (DispatchOptions::CHANNEL_SAAS !== $settings['channel']) {
            add_settings_error(
                self::MENU_SLUG,
                'service_test_error',
                __('Select Inquiry management SaaS as the dispatch channel before verifying the connection.', 's2j-inquiry-destination'),
                'error'
            );

            return;
        }

        $product = $settings['saas_product'];
        $resolved = SaasOptions::resolveForConnectionTest($post, $product);
        if (null !== $resolved['error']) {
            add_settings_error(self::MENU_SLUG, 'service_test_error', $resolved['error'], 'error');

            return;
        }

        $result = SaasConnectionProbe::test($product, $resolved['endpoint'], $resolved['secret']);
        add_settings_error(
            self::MENU_SLUG,
            $result['ok'] ? 'service_test_ok' : 'service_test_error',
            $result['message'],
            $result['ok'] ? 'success' : 'error'
        );
    }

    private static function handleServicePost(): void
    {
        check_admin_referer('s2j_inquiry_destination_service');

        $post = self::postData();

        $dispatch_result = DispatchOptions::sanitizeFromPost($post);
        if (null !== $dispatch_result['error']) {
            add_settings_error(self::MENU_SLUG, 'service_error', $dispatch_result['error'], 'error');
            return;
        }

        update_option(DispatchOptions::OPTION_KEY, $dispatch_result['settings']);

        $settings = $dispatch_result['settings'];
        $saas_result = SaasOptions::sanitizeFromPost($post, $settings['channel'], $settings['saas_product']);
        if (null !== $saas_result['error']) {
            add_settings_error(self::MENU_SLUG, 'service_error', $saas_result['error'], 'error');
            return;
        }

        if (! SaasOptions::isConfiguredViaServer($settings['saas_product'])) {
            update_option(SaasOptions::OPTION_KEY, $saas_result['saas']);
        }

        add_settings_error(self::MENU_SLUG, 'service_saved', __('Service settings saved.', 's2j-inquiry-destination'), 'success');
    }

    private static function handleTypesPost(): void
    {
        check_admin_referer('s2j_inquiry_destination_types');

        $post  = self::postData();
        $input = self::postArray($post, 'types');
        $action = self::currentListAction();
        if ('delete' === $action) {
            $slugs     = self::postArray($post, 'type');
            $slug_list = self::nonEmptyStringList($slugs);
            $input     = TypeOptions::markRemovedInInput($input, $slug_list);
        }

        $result = TypeOptions::sanitizeFromPost($input);
        if (null !== $result['error']) {
            add_settings_error(self::MENU_SLUG, 'types_error', $result['error'], 'error');
            return;
        }

        update_option(TypeOptions::OPTION_KEY, $result['types']);
        add_settings_error(self::MENU_SLUG, 'types_saved', __('Inquiry types saved.', 's2j-inquiry-destination'), 'success');
    }

    private static function handleFormsPost(): void
    {
        check_admin_referer('s2j_inquiry_destination_forms');

        $post     = self::postData();
        $posted   = self::postArray($post, 'forms');
        $action   = self::currentListAction();
        $selected = self::postArray($post, 'form');

        $remove_ids = array();
        $bulk_type  = null;
        $bulk_ids   = array();

        if ('clear_mapping' === $action) {
            $remove_ids = self::intList($selected);
        }

        if ('change_type' === $action) {
            $bulk_type = isset($post['bulk_inquiry_type']) ? sanitize_key((string) $post['bulk_inquiry_type']) : '';
            if ('' === $bulk_type) {
                add_settings_error(self::MENU_SLUG, 'forms_error', __('Choose an inquiry type for the bulk action.', 's2j-inquiry-destination'), 'error');
                return;
            }
            $bulk_ids = $selected;
        }

        $result = FormOptions::saveFromAdminRequest($posted, $remove_ids, $bulk_type, $bulk_ids);
        if (null !== $result['error']) {
            add_settings_error(self::MENU_SLUG, 'forms_error', $result['error'], 'error');
            return;
        }

        update_option(FormOptions::OPTION_KEY, $result['saved']);
        add_settings_error(self::MENU_SLUG, 'forms_saved', __('Form mappings saved.', 's2j-inquiry-destination'), 'success');
    }

    private static function currentTab(): string
    {
        $tab = self::queryString('tab', self::TAB_SERVICE);
        if (self::isKnownTab($tab)) {
            return $tab;
        }

        return self::TAB_SERVICE;
    }

    private static function isKnownTab(string $tab): bool
    {
        return self::TAB_SERVICE === $tab
            || self::TAB_TYPES === $tab
            || self::TAB_FORMS === $tab;
    }

    /**
     * @return array<mixed, mixed>
     */
    private static function postData(): array
    {
        if (! isset($GLOBALS['_POST'])) {
            return array();
        }

        /** @var array<mixed, mixed> $raw */
        $raw = $GLOBALS['_POST'];

        return wp_unslash($raw);
    }

    /**
     * @param array<mixed, mixed> $post
     *
     * @return array<mixed, mixed>
     */
    private static function postArray(array $post, string $key): array
    {
        if (! isset($post[ $key ]) || ! \s2j_inquiry_destination_is_array($post[ $key ])) {
            return array();
        }

        /** @var array<mixed, mixed> $nested */
        $nested = $post[ $key ];

        return $nested;
    }

    /**
     * @param array<mixed, mixed> $values
     *
     * @return list<string>
     */
    private static function nonEmptyStringList(array $values): array
    {
        $out = array();
        foreach ($values as $value) {
            $string = (string) $value;
            if ('' !== $string) {
                $out[] = $string;
            }
        }

        return $out;
    }

    /**
     * @param array<mixed, mixed> $values
     *
     * @return list<int>
     */
    private static function intList(array $values): array
    {
        $out = array();
        foreach ($values as $value) {
            $out[] = (int) $value;
        }

        return $out;
    }

    private static function queryString(string $key, string $default): string
    {
        if (! isset($GLOBALS['_GET']) || ! \s2j_inquiry_destination_is_array($GLOBALS['_GET'])) {
            return $default;
        }

        /** @var array<mixed, mixed> $raw */
        $raw = $GLOBALS['_GET'];
        if (! isset($raw[ $key ])) {
            return $default;
        }

        return sanitize_key((string) wp_unslash((string) $raw[ $key ]));
    }

    /**
     * @return string|false
     */
    private static function currentListAction()
    {
        if (! isset($GLOBALS['_REQUEST']) || ! \s2j_inquiry_destination_is_array($GLOBALS['_REQUEST'])) {
            return false;
        }

        /** @var array<mixed, mixed> $request */
        $request = $GLOBALS['_REQUEST'];

        if (isset($request['action']) && '-1' !== (string) wp_unslash($request['action'])) {
            return sanitize_key((string) wp_unslash($request['action']));
        }
        if (isset($request['action2']) && '-1' !== (string) wp_unslash($request['action2'])) {
            return sanitize_key((string) wp_unslash($request['action2']));
        }

        return false;
    }

    private static function serviceConnectionInputAttrs(bool $server_locked): string
    {
        if (! $server_locked) {
            return '';
        }

        return ' disabled="disabled" data-s2j-server-locked="1"';
    }

    /**
     * @param array<string, string> $stored
     */
    private static function renderServiceConnectionPanel(
        string $panel_product,
        string $selected_product,
        array $stored,
        bool $server_locked
    ): void {
        $input_attrs = self::serviceConnectionInputAttrs($server_locked);
        $panel_hidden = $panel_product !== $selected_product ? ' hidden' : '';
        echo '<div class="s2j-inquiry-destination-service-connection-panel' . $panel_hidden . '" data-s2j-product="' . esc_attr($panel_product) . '">';
        echo '<table class="form-table" role="presentation"><tbody>';
        if (DispatchOptions::PRODUCT_ASANA === $panel_product) {
            echo '<tr><th scope="row">' . esc_html__('Personal access token', 's2j-inquiry-destination') . '</th><td>';
            if (SaasOptions::isAccessTokenConfigured()) {
                echo '<p>' . esc_html__('Status: configured', 's2j-inquiry-destination') . '</p>';
            } else {
                echo '<p>' . esc_html__('Status: not configured', 's2j-inquiry-destination') . '</p>';
            }
            echo '<input type="password" name="access_token" class="regular-text s2j-inquiry-destination-service-connection-input" autocomplete="new-password"'
                . $input_attrs
                . ' />';
            echo '<p class="description">' . esc_html__('Leave blank to keep the current token.', 's2j-inquiry-destination') . '</p>';
            echo '</td></tr>';
            $endpoint = $stored['endpoint'] ?? '';
            echo '<tr><th scope="row">' . esc_html__('API base URL (optional)', 's2j-inquiry-destination') . '</th><td>';
            echo '<input type="url" name="endpoint" class="regular-text s2j-inquiry-destination-service-connection-input" value="' . esc_attr($endpoint) . '"'
                . $input_attrs
                . ' />';
            echo '</td></tr>';
        } else {
            echo '<tr><th scope="row">' . esc_html__('Backlog space URL', 's2j-inquiry-destination') . '</th><td>';
            $endpoint = $stored['endpoint'] ?? '';
            echo '<input type="url" name="endpoint" class="regular-text s2j-inquiry-destination-service-connection-input" value="' . esc_attr($endpoint) . '"'
                . $input_attrs
                . ' placeholder="https://example.backlog.com" />';
            echo '</td></tr>';
            echo '<tr><th scope="row">' . esc_html__('API key', 's2j-inquiry-destination') . '</th><td>';
            if (SaasOptions::isApiKeyConfigured()) {
                echo '<p>' . esc_html__('Status: configured', 's2j-inquiry-destination') . '</p>';
            } else {
                echo '<p>' . esc_html__('Status: not configured', 's2j-inquiry-destination') . '</p>';
            }
            echo '<input type="password" name="api_key" class="regular-text s2j-inquiry-destination-service-connection-input" autocomplete="new-password"'
                . $input_attrs
                . ' />';
            echo '<p class="description">' . esc_html__('Leave blank to keep the current key.', 's2j-inquiry-destination') . '</p>';
            echo '</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }

    private static function renderServiceMailAuthNotice(bool $visible): void
    {
        $hidden = $visible ? '' : ' hidden';
        echo '<div class="notice notice-info inline s2j-inquiry-destination-service-mail-auth-notice' . esc_attr($hidden) . '"><p>';

        $plugin_name = self::emailAuthDisplayName();

        if (self::isEmailAuthActive()) {
            $settings_url = admin_url('options-general.php?page=email-auth');
            $settings_link_label = sprintf(
                /* translators: %s: mail authentication plugin name from its plugin header */
                __('Settings → %s', 's2j-inquiry-destination'),
                $plugin_name
            );
            echo wp_kses(
                sprintf(
                    /* translators: 1: mail authentication plugin name, 2: settings screen link */
                    __('%1$s is active. Configure DKIM, SPF, and DMARC in %2$s.', 's2j-inquiry-destination'),
                    esc_html($plugin_name),
                    '<a href="' . esc_url($settings_url) . '">' . esc_html($settings_link_label) . '</a>'
                ),
                array(
                    'a' => array(
                        'href' => array(),
                    ),
                )
            );
        } else {
            echo wp_kses(
                sprintf(
                    /* translators: %s: link to the mail authentication companion on WordPress.org */
                    __(
                        'If you need DKIM, SPF, or DMARC for outgoing mail, consider installing the %s plugin. It applies to all wp_mail delivery, including inquiries from this plugin.',
                        's2j-inquiry-destination'
                    ),
                    '<a href="https://wordpress.org/plugins/email-auth/" target="_blank" rel="noopener noreferrer">'
                    . esc_html($plugin_name)
                    . '</a>'
                ),
                array(
                    'a' => array(
                        'href'   => array(),
                        'target' => array(),
                        'rel'    => array(),
                    ),
                )
            );
        }

        echo '</p></div>';
    }

    private static function emailAuthDisplayName(): string
    {
        if (! function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if (function_exists('get_plugins')) {
            $plugins = get_plugins();
            if (isset($plugins[ self::EMAIL_AUTH_PLUGIN ]['Name'])) {
                $name = trim((string) $plugins[ self::EMAIL_AUTH_PLUGIN ]['Name']);
                if ('' !== $name) {
                    return $name;
                }
            }
        }

        /* translators: fallback display name when the mail authentication companion is not installed locally */
        return __('Email Auth', 's2j-inquiry-destination');
    }

    private static function isEmailAuthActive(): bool
    {
        if (defined('EAUTH_PLUGIN_VERSION')) {
            return true;
        }

        if (! function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return is_plugin_active(self::EMAIL_AUTH_PLUGIN);
    }

    private static function serviceTabScript(): string
    {
        return <<<'JS'
(function () {
	const form = document.querySelector('.s2j-inquiry-destination-tab-service');
	if (!form) {
		return;
	}

	const channelInputs = form.querySelectorAll('input[name="channel"]');
	const productInputs = form.querySelectorAll('input[name="saas_product"]');
	const idleHint = form.querySelector('.s2j-inquiry-destination-service-connection-idle');
	const panelsWrap = form.querySelector('.s2j-inquiry-destination-service-connection-panels');
	const serverNotice = form.querySelector('.s2j-inquiry-destination-service-server-notice');
	const verifyWrap = form.querySelector('.s2j-inquiry-destination-service-verify-wrap');
	const mailAuthNotice = form.querySelector('.s2j-inquiry-destination-service-mail-auth-notice');
	const connectionPanels = form.querySelectorAll('.s2j-inquiry-destination-service-connection-panel');
	const connectionInputs = form.querySelectorAll('.s2j-inquiry-destination-service-connection-input');

	const selectedChannel = () => {
		const checked = form.querySelector('input[name="channel"]:checked');
		return checked ? checked.value : 'mail';
	};

	const selectedProduct = () => {
		const checked = form.querySelector('input[name="saas_product"]:checked');
		return checked ? checked.value : '';
	};

	const setHidden = (el, hidden) => {
		if (!el) {
			return;
		}
		el.classList.toggle('hidden', hidden);
	};

	const sync = () => {
		const isSaas = selectedChannel() === 'saas';
		const product = selectedProduct();
		const serverLocked = Boolean(serverNotice);

		productInputs.forEach((input) => {
			input.disabled = !isSaas;
		});

		setHidden(idleHint, isSaas);
		if (serverLocked) {
			setHidden(serverNotice, !isSaas);
			setHidden(panelsWrap, true);
		} else {
			setHidden(panelsWrap, !isSaas);
		}
		setHidden(verifyWrap, !isSaas);
		setHidden(mailAuthNotice, isSaas);

		connectionPanels.forEach((panel) => {
			const match = panel.getAttribute('data-s2j-product') === product;
			setHidden(panel, !isSaas || !match);
		});

		connectionInputs.forEach((input) => {
			const panel = input.closest('.s2j-inquiry-destination-service-connection-panel');
			const panelProduct = panel ? panel.getAttribute('data-s2j-product') : '';
			const activePanel = isSaas && product === panelProduct && !serverLocked;
			const lockedByServer = input.hasAttribute('data-s2j-server-locked');
			input.disabled = !activePanel || lockedByServer;
		});
	};

	channelInputs.forEach((input) => input.addEventListener('change', sync));
	productInputs.forEach((input) => input.addEventListener('change', sync));
	sync();
})();
JS;
    }

    private static function typesAddRowScript(): string
    {
        return <<<'JS'
(function () {
	let counter = 0;
	const button = document.getElementById('s2j-inquiry-destination-add-type-row');
	const table = document.querySelector('.s2j-inquiry-destination-settings .wp-list-table tbody');
	const labels = window.s2jInquiryDestinationTypesTab || {};
	if (!button || !table) {
		return;
	}

	const escapeAttr = (value) => String(value).replace(/&/g, '&amp;').replace(/"/g, '&quot;');

	button.addEventListener('click', () => {
		counter += 1;
		const key = '_new_' + counter;
		const colLabel = escapeAttr(labels.colLabel || 'Display name');
		const isSaas = labels.channel === 'saas';
		const isBacklog = labels.saasProduct === 'backlog';
		let tail = '';
		if (isSaas) {
			tail =
				'<td class="saas_project_id column-saas_project_id" data-colname="' + escapeAttr(labels.colProjectId || 'Project ID') + '">' +
				'<input type="text" class="regular-text s2j-inquiry-destination-types-input" name="types[' + key + '][saas_project_id]" autocomplete="off" /></td>';
			if (isBacklog) {
				tail +=
					'<td class="issue_type_id column-issue_type_id" data-colname="' + escapeAttr(labels.colIssueType || 'Issue type ID') + '">' +
					'<input type="text" class="regular-text s2j-inquiry-destination-types-input" name="types[' + key + '][issue_type_id]" autocomplete="off" /></td>' +
					'<td class="priority_id column-priority_id" data-colname="' + escapeAttr(labels.colPriorityId || 'Priority ID') + '">' +
					'<input type="text" class="regular-text s2j-inquiry-destination-types-input" name="types[' + key + '][priority_id]" autocomplete="off" /></td>';
			}
		} else {
			tail =
				'<td class="mail_to column-mail_to" data-colname="' + escapeAttr(labels.colMailTo || 'Mail to') + '">' +
				'<textarea class="large-text code" rows="3" name="types[' + key + '][mail_to]"></textarea></td>';
		}
		const row = document.createElement('tr');
		row.className = 's2j-inquiry-destination-type-new-row';
		row.innerHTML =
			'<td class="check-column"></td>' +
			'<th scope="row" class="slug column-slug column-primary has-row-actions" data-colname="' + escapeAttr(labels.colSlug || 'Type (slug)') + '">' +
			'<div class="s2j-inquiry-type-slug-cell">' +
			'<input type="text" class="regular-text code s2j-inquiry-destination-types-input" name="types[' + key + '][slug]" placeholder="support" autocomplete="off" /></div></th>' +
			'<td class="label column-label" data-colname="' + colLabel + '">' +
			'<input type="text" class="regular-text s2j-inquiry-destination-types-input" name="types[' + key + '][label]" autocomplete="off" /></td>' +
			tail;
		table.appendChild(row);
	});
})();
JS;
    }
}
