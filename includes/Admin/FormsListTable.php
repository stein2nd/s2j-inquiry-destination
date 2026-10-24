<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Admin;

use S2J\InquiryDestination\Options\FormOptions;
use S2J\InquiryDestination\Options\TypeOptions;
use S2J\InquiryDestination\Support\SnowMonkeyForms;

final class FormsListTable extends \WP_List_Table
{
    /** @var array<string, array{label: string, mail_to: list<string>}> */
    private array $types;

    /** @var array<int, array{inquiry_type: string, fields: array{name: string, email: string, phone: string, message: string}}> */
    private array $savedForms;

    private string $defaultType;

    /**
     * @param array<string, array{label: string, mail_to: list<string>}> $types
     * @param array<int, array{inquiry_type: string, fields: array{name: string, email: string, phone: string, message: string}}> $savedForms
     */
    public function __construct(array $types, array $savedForms, string $defaultType)
    {
        parent::__construct(
            array(
                'singular' => 'smf_form',
                'plural'   => 'smf_forms',
                'ajax'     => false,
            )
        );

        $this->types        = $types;
        $this->savedForms   = $savedForms;
        $this->defaultType  = $defaultType;
    }

    public function prepare_items(): void
    {
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $all_rows     = $this->buildRows();
        $total        = count($all_rows);

        $offset = ( $current_page - 1 ) * $per_page;
        $this->items = array_slice($all_rows, $offset, $per_page);

        $this->set_pagination_args(
            array(
                'total_items' => $total,
                'per_page'    => $per_page,
                'total_pages' => (int) ceil($total / $per_page),
            )
        );

        $this->_column_headers = array(
            $this->get_columns(),
            array(),
            $this->get_sortable_columns(),
        );
    }

    public function get_columns(): array
    {
        return array(
            'cb'           => '<input type="checkbox" />',
            'title'        => __('Form', 's2j-inquiry-destination'),
            'inquiry_type' => __('Inquiry type', 's2j-inquiry-destination'),
            'fields'       => __('Field names (SMF control name)', 's2j-inquiry-destination'),
        );
    }

    /**
     * @return list<string>
     */
    protected function get_table_classes(): array
    {
        return array(
            'widefat',
            'striped',
            's2j-inquiry-forms-list-table',
        );
    }

    protected function get_sortable_columns(): array
    {
        return array(
            'title'        => array( 'title', false ),
            'inquiry_type' => array( 'inquiry_type', false ),
            'date'         => array( 'date', true ),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function get_views(): array
    {
        $current = $this->currentView();
        $base    = $this->baseUrl();
        $counts  = $this->viewCounts();

        return array(
            'all' => sprintf(
                '<a href="%1$s"%2$s>%3$s</a>',
                esc_url($base),
                'all' === $current ? ' class="current"' : '',
                sprintf(__('All (%d)', 's2j-inquiry-destination'), $counts['all'])
            ),
            'mapped' => sprintf(
                '<a href="%1$s"%2$s>%3$s</a>',
                esc_url(add_query_arg('form_view', 'mapped', $base)),
                'mapped' === $current ? ' class="current"' : '',
                sprintf(__('Mapped (%d)', 's2j-inquiry-destination'), $counts['mapped'])
            ),
            'unmapped' => sprintf(
                '<a href="%1$s"%2$s>%3$s</a>',
                esc_url(add_query_arg('form_view', 'unmapped', $base)),
                'unmapped' === $current ? ' class="current"' : '',
                sprintf(__('Unmapped (%d)', 's2j-inquiry-destination'), $counts['unmapped'])
            ),
        );
    }

    protected function get_bulk_actions(): array
    {
        return array(
            'clear_mapping' => __('Remove mapping', 's2j-inquiry-destination'),
            'change_type'   => __('Change inquiry type', 's2j-inquiry-destination'),
        );
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function column_cb($item): string
    {
        return sprintf(
            '<input type="checkbox" name="form[]" value="%d" />',
            (int) $item['form_id']
        );
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function column_title($item): string
    {
        return sprintf(
            '<strong>%1$s</strong><br />ID: %2$d',
            esc_html((string) $item['title']),
            (int) $item['form_id']
        );
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function column_inquiry_type($item): string
    {
        $form_id = (int) $item['form_id'];
        $current = (string) $item['inquiry_type'];

        $html = sprintf('<select name="forms[%1$d][inquiry_type]">', $form_id);
        foreach ($this->types as $type_key => $type_row) {
            $html .= sprintf(
                '<option value="%1$s" %2$s>%3$s (%1$s)</option>',
                esc_attr($type_key),
                selected($current, $type_key, false),
                esc_html($type_row['label'])
            );
        }
        $html .= '</select>';

        return $html;
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function column_fields($item): string
    {
        $form_id = (int) $item['form_id'];
        /** @var array{name: string, email: string, phone: string, message: string} $fields */
        $fields = $item['fields'];

        return $this->fieldInput($form_id, 'name', __('Name', 's2j-inquiry-destination'), $fields['name'], true)
            . $this->fieldInput($form_id, 'email', __('Email', 's2j-inquiry-destination'), $fields['email'], true)
            . $this->fieldInput($form_id, 'phone', __('Phone (optional)', 's2j-inquiry-destination'), $fields['phone'], false)
            . $this->fieldInput($form_id, 'message', __('Message', 's2j-inquiry-destination'), $fields['message'], true);
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function column_default($item, $column_name): string
    {
        return '';
    }

    public function no_items(): void
    {
        esc_html_e('No published Snow Monkey Forms found.', 's2j-inquiry-destination');
    }

    /**
     * @param string $which
     */
    protected function extra_tablenav($which): void
    {
        if ('top' !== $which) {
            return;
        }

        echo '<div class="alignleft actions">';
        echo '<label for="bulk-inquiry-type" class="screen-reader-text">' . esc_html__('Bulk inquiry type', 's2j-inquiry-destination') . '</label>';
        echo '<select name="bulk_inquiry_type" id="bulk-inquiry-type">';
        echo '<option value="">' . esc_html__('Bulk inquiry type…', 's2j-inquiry-destination') . '</option>';
        foreach ($this->types as $type_key => $type_row) {
            printf(
                '<option value="%1$s">%2$s (%1$s)</option>',
                esc_attr($type_key),
                esc_html($type_row['label'])
            );
        }
        echo '</select>';
        echo '</div>';
    }

    /**
     * @param string $text
     * @param string $input_id
     */
    public function search_box($text, $input_id): void
    {
        if (empty($_REQUEST['s']) && ! $this->has_items()) {
            return;
        }

        $input_id = $input_id . '-search-input';
        $query    = $this->searchQuery();
        $view     = $this->currentView();

        echo '<form method="get">';
        echo '<p class="search-box">';
        echo '<label class="screen-reader-text" for="' . esc_attr($input_id) . '">' . esc_html($text) . ':</label>';
        echo '<input type="search" id="' . esc_attr($input_id) . '" name="s" value="' . esc_attr($query) . '" />';
        submit_button($text, '', '', false, array( 'id' => 'search-submit-' . $input_id ));
        echo '<input type="hidden" name="page" value="' . esc_attr(SettingsPage::menuSlug()) . '" />';
        echo '<input type="hidden" name="tab" value="forms" />';
        if ('all' !== $view) {
            echo '<input type="hidden" name="form_view" value="' . esc_attr($view) . '" />';
        }
        echo '</p>';
        echo '</form>';
    }

    private function fieldInput(int $form_id, string $field, string $label, string $value, bool $required): string
    {
        return sprintf(
            '<p><label>%1$s<br /><input type="text" name="forms[%2$d][fields][%3$s]" value="%4$s" class="regular-text" %5$s /></label></p>',
            esc_html($label),
            $form_id,
            esc_attr($field),
            esc_attr($value),
            $required ? 'required' : ''
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildRows(): array
    {
        $posts = SnowMonkeyForms::publishedForms();
        $view  = $this->currentView();
        $search = strtolower($this->searchQuery());
        $orderby = $this->orderByColumn();
        $order   = $this->orderDirection();

        $rows = array();
        foreach ($posts as $post) {
            $form_id = (int) $post->ID;
            $title   = get_the_title($post);
            $mapped  = FormOptions::isMapped($form_id, $this->savedForms);

            if ('mapped' === $view && ! $mapped) {
                continue;
            }
            if ('unmapped' === $view && $mapped) {
                continue;
            }

            $haystack = strtolower($title . ' ' . (string) $form_id);
            if ('' !== $search && false === strpos($haystack, $search)) {
                continue;
            }

            $row = $this->savedForms[ $form_id ] ?? array(
                'inquiry_type' => $this->defaultType,
                'fields'       => array(
                    'name'    => '',
                    'email'   => '',
                    'phone'   => '',
                    'message' => '',
                ),
            );

            $rows[] = array(
                'form_id'      => $form_id,
                'title'        => $title,
                'inquiry_type' => $row['inquiry_type'],
                'fields'       => $row['fields'],
                'date'         => $post->post_date,
                'mapped'       => $mapped,
            );
        }

        usort(
            $rows,
            static function (array $a, array $b) use ($orderby, $order): int {
                if ('date' === $orderby) {
                    $cmp = strcmp((string) $a['date'], (string) $b['date']);
                } elseif ('inquiry_type' === $orderby) {
                    $cmp = strcasecmp((string) $a['inquiry_type'], (string) $b['inquiry_type']);
                } else {
                    $cmp = strcasecmp((string) $a['title'], (string) $b['title']);
                }

                return 'desc' === $order ? -$cmp : $cmp;
            }
        );

        return $rows;
    }

    /**
     * @return array{all: int, mapped: int, unmapped: int}
     */
    private function viewCounts(): array
    {
        $posts = SnowMonkeyForms::publishedForms();
        $mapped = 0;
        foreach ($posts as $post) {
            if (FormOptions::isMapped((int) $post->ID, $this->savedForms)) {
                ++$mapped;
            }
        }
        $all = count($posts);

        return array(
            'all'      => $all,
            'mapped'   => $mapped,
            'unmapped' => max(0, $all - $mapped),
        );
    }

    private function currentView(): string
    {
        $view = isset($_GET['form_view']) ? sanitize_key((string) wp_unslash($_GET['form_view'])) : 'all';
        if ('mapped' === $view || 'unmapped' === $view) {
            return $view;
        }

        return 'all';
    }

    private function searchQuery(): string
    {
        return isset($_GET['s']) ? sanitize_text_field((string) wp_unslash($_GET['s'])) : '';
    }

    private function orderByColumn(): string
    {
        $orderby = isset($_GET['orderby']) ? sanitize_key((string) wp_unslash($_GET['orderby'])) : 'title';
        if (in_array($orderby, array( 'title', 'inquiry_type', 'date' ), true)) {
            return $orderby;
        }

        return 'title';
    }

    private function orderDirection(): string
    {
        $order = isset($_GET['order']) ? strtolower((string) wp_unslash($_GET['order'])) : 'asc';

        return 'desc' === $order ? 'desc' : 'asc';
    }

    private function baseUrl(): string
    {
        return add_query_arg(
            array(
                'page' => SettingsPage::menuSlug(),
                'tab'  => 'forms',
            ),
            admin_url('options-general.php')
        );
    }
}
