<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Admin;

use S2J\InquiryDestination\Options\DispatchOptions;
use S2J\InquiryDestination\Options\TypeOptions;

final class TypesListTable extends \WP_List_Table
{
    private string $dispatch_channel;

    private string $saas_product;

    public function __construct(string $dispatch_channel = DispatchOptions::CHANNEL_MAIL, string $saas_product = '')
    {
        $this->dispatch_channel = $dispatch_channel;
        $this->saas_product     = $saas_product;

        parent::__construct(
            array(
                'singular' => 'inquiry_type',
                'plural'   => 'inquiry_types',
                'ajax'     => false,
            )
        );
    }

    public function prepare_items(): void
    {
        $this->_column_headers = array(
            $this->get_columns(),
            array(),
            $this->get_sortable_columns(),
        );

        $this->items = $this->filterAndSort(TypeOptions::get());
    }

    public function get_columns(): array
    {
        $columns = array(
            'cb'    => '<input type="checkbox" />',
            'slug'  => __('Type (slug)', 's2j-inquiry-destination'),
            'label' => __('Display name', 's2j-inquiry-destination'),
        );

        if (DispatchOptions::CHANNEL_SAAS === $this->dispatch_channel) {
            $columns['saas_project_id'] = __('Project ID', 's2j-inquiry-destination');
            if (DispatchOptions::PRODUCT_BACKLOG === $this->saas_product) {
                $columns['issue_type_id'] = __('Issue type ID', 's2j-inquiry-destination');
                $columns['priority_id']   = __('Priority ID', 's2j-inquiry-destination');
            }
        } else {
            $columns['mail_to'] = __('Mail to (one address per line)', 's2j-inquiry-destination');
        }

        return $columns;
    }

    /**
     * @return list<string>
     */
    protected function get_table_classes(): array
    {
        return array(
            'widefat',
            'striped',
            's2j-inquiry-types-list-table',
        );
    }

    protected function get_primary_column_name(): string
    {
        return 'slug';
    }

    protected function get_sortable_columns(): array
    {
        return array(
            'slug'  => array( 'slug', false ),
            'label' => array( 'label', false ),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function get_views(): array
    {
        $current = $this->currentView();
        $base    = $this->baseUrl();

        $all_count = $this->countItems($this->allTypes());
        $empty_view = DispatchOptions::CHANNEL_SAAS === $this->dispatch_channel ? 'empty_saas' : 'empty_mail';
        $empty      = $this->countItems($this->filterAndSort($this->allTypes(), $empty_view, '', '', ''));

        $views = array(
            'all' => $this->viewLink(
                $base,
                'all' === $current,
                $this->countLabel(
                    /* translators: %d: number of inquiry types */
                    __('All (%d)', 's2j-inquiry-destination'),
                    $all_count
                )
            ),
        );

        if ($empty > 0) {
            if ('empty_saas' === $empty_view) {
                $views['empty_saas'] = $this->viewLink(
                    add_query_arg('type_view', 'empty_saas', $base),
                    'empty_saas' === $current,
                    $this->countLabel(
                        /* translators: %d: number of types without SaaS project */
                        __('No SaaS project (%d)', 's2j-inquiry-destination'),
                        $empty
                    )
                );
            } else {
                $views['empty_mail'] = $this->viewLink(
                    add_query_arg('type_view', 'empty_mail', $base),
                    'empty_mail' === $current,
                    $this->countLabel(
                        /* translators: %d: number of types without mail */
                        __('No mail destination (%d)', 's2j-inquiry-destination'),
                        $empty
                    )
                );
            }
        }

        return $views;
    }

    protected function get_bulk_actions(): array
    {
        return array(
            'delete' => __('Delete', 's2j-inquiry-destination'),
        );
    }

    /**
     * @param array{slug: string, label: string, mail_to: list<string>} $item
     */
    protected function single_row_columns($item): void
    {
        list($columns, $hidden, $sortable, $primary) = $this->get_column_info();

        foreach ($columns as $column_name => $column_display_name) {
            $classes = $column_name . ' column-' . $column_name;
            if ($primary === $column_name) {
                $classes .= ' has-row-actions column-primary';
            }
            if ($this->columnIsHidden($column_name, $hidden)) {
                $classes .= ' hidden';
            }

            $data       = 'data-colname="' . esc_attr(wp_strip_all_tags($column_display_name)) . '"';
            $attributes = "class='" . esc_attr($classes) . "' " . $data;

            if ('cb' === $column_name) {
                echo '<td class="check-column">';
                echo '<input type="checkbox" name="type[]" value="' . esc_attr($item['slug']) . '" />';
                echo '</td>';
                continue;
            }

            $is_primary = ($primary === $column_name);
            $tag        = $is_primary ? 'th' : 'td';
            $scope      = $is_primary ? ' scope="row"' : '';

            echo '<' . $tag . ' ' . $attributes . $scope . '>';

            echo $this->renderColumnCell($column_name, $item);

            echo '</' . $tag . '>';
        }
    }

    /**
     * @param array<string, mixed> $item
     */
    private function renderColumnCell(string $column_name, array $item): string
    {
        return match ($column_name) {
            'slug'              => $this->column_slug($item),
            'label'             => $this->column_label($item),
            'mail_to'           => $this->column_mail_to($item),
            'saas_project_id'   => $this->column_saas_project_id($item),
            'issue_type_id'     => $this->column_issue_type_id($item),
            'priority_id'       => $this->column_priority_id($item),
            default             => $this->column_default($item, $column_name),
        };
    }

    /**
     * @param array{slug: string, label: string, mail_to: list<string>} $item
     */
    protected function column_slug($item): string
    {
        $warning = '';
        if (DispatchOptions::CHANNEL_SAAS === $this->dispatch_channel) {
            if (! TypeOptions::hasValidSaasDestination($item, $this->saas_product)) {
                $warning = '<div class="s2j-inquiry-type-slug-warning"><span class="notice notice-warning inline"><strong>'
                    . esc_html__('SaaS destination incomplete', 's2j-inquiry-destination')
                    . '</strong></span></div>';
            }
        } elseif (! TypeOptions::hasValidMailTo($item)) {
            $warning = '<div class="s2j-inquiry-type-slug-warning"><span class="notice notice-warning inline"><strong>'
                . esc_html__('No valid mail address', 's2j-inquiry-destination')
                . '</strong></span></div>';
        }

        return '<div class="s2j-inquiry-type-slug-cell"><code>' . esc_html($item['slug']) . '</code>' . $warning . '</div>';
    }

    /**
     * @param array{slug: string, label: string, mail_to: list<string>} $item
     */
    protected function column_label($item): string
    {
        return '<input type="text" class="regular-text s2j-inquiry-destination-types-input" name="types['
            . esc_attr($item['slug'])
            . '][label]" value="'
            . esc_attr($item['label'])
            . '" />';
    }

    /**
     * @param array{slug: string, label: string, mail_to: list<string>} $item
     */
    protected function column_mail_to($item): string
    {
        return '<textarea class="large-text code" rows="3" name="types['
            . esc_attr($item['slug'])
            . '][mail_to]">'
            . esc_textarea($this->joinLines($item['mail_to']))
            . '</textarea>';
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function column_saas_project_id($item): string
    {
        return '<input type="text" class="regular-text s2j-inquiry-destination-types-input" name="types['
            . esc_attr($item['slug'])
            . '][saas_project_id]" value="'
            . esc_attr($item['saas_project_id'] ?? '')
            . '" />';
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function column_issue_type_id($item): string
    {
        return '<input type="text" class="regular-text s2j-inquiry-destination-types-input" name="types['
            . esc_attr($item['slug'])
            . '][issue_type_id]" value="'
            . esc_attr($item['issue_type_id'] ?? '')
            . '" />';
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function column_priority_id($item): string
    {
        return '<input type="text" class="regular-text s2j-inquiry-destination-types-input" name="types['
            . esc_attr($item['slug'])
            . '][priority_id]" value="'
            . esc_attr($item['priority_id'] ?? '')
            . '" />';
    }

    /**
     * @param array{slug: string, label: string, mail_to: list<string>} $item
     */
    protected function column_default($item, $column_name): string
    {
        return '';
    }

    public function no_items(): void
    {
        esc_html_e('No inquiry types found.', 's2j-inquiry-destination');
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

        echo '<form method="get">';
        echo '<p class="search-box">';
        echo '<label class="screen-reader-text" for="' . esc_attr($input_id) . '">' . esc_html($text) . ':</label>';
        echo '<input type="search" id="' . esc_attr($input_id) . '" name="s" value="' . esc_attr($query) . '" />';
        submit_button($text, '', '', false, array( 'id' => 'search-submit-' . $input_id ));
        echo '<input type="hidden" name="page" value="' . esc_attr(SettingsPage::menuSlug()) . '" />';
        echo '<input type="hidden" name="tab" value="types" />';
        if ('empty_mail' === $this->currentView()) {
            echo '<input type="hidden" name="type_view" value="empty_mail" />';
        }
        echo '</p>';
        echo '</form>';
    }

    /**
     * @param array<string, array{label: string, mail_to: list<string>}> $types
     *
     * @return list<array{slug: string, label: string, mail_to: list<string>}>
     */
    private function filterAndSort(
        array $types,
        ?string $view = null,
        ?string $search = null,
        ?string $orderby = null,
        ?string $order = null
    ): array {
        $view = $view ?? $this->currentView();
        $search = null !== $search ? $search : $this->searchQuery();
        $orderby = null !== $orderby ? $orderby : $this->orderByColumn();
        $order = null !== $order ? $order : $this->orderDirection();

        $rows = array();
        foreach ($types as $slug => $row) {
            if ('empty_mail' === $view && TypeOptions::hasValidMailTo($row)) {
                continue;
            }
            if ('empty_saas' === $view && TypeOptions::hasValidSaasDestination($row, $this->saas_product)) {
                continue;
            }

            $haystack = $slug . ' ' . $row['label'];
            if (! \s2j_inquiry_destination_text_matches_search($haystack, $search)) {
                continue;
            }

            $rows[] = array( 'slug' => $slug ) + $row;
        }

        return $this->sortTypeRows($rows, $orderby, $order);
    }

    private function currentView(): string
    {
        $view = isset($_GET['type_view']) ? sanitize_key((string) wp_unslash($_GET['type_view'])) : 'all';

        if ('empty_mail' === $view || 'empty_saas' === $view) {
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
        $orderby = isset($_GET['orderby']) ? sanitize_key((string) wp_unslash($_GET['orderby'])) : 'slug';

        return 'label' === $orderby ? 'label' : 'slug';
    }

    private function orderDirection(): string
    {
        $order = isset($_GET['order']) ? sanitize_key((string) wp_unslash($_GET['order'])) : 'asc';

        return 'desc' === $order ? 'desc' : 'asc';
    }

    /**
     * @return array<string, array{label: string, mail_to: list<string>}>
     */
    private function allTypes(): array
    {
        /** @var array<string, array{label: string, mail_to: list<string>}> $types */
        $types = TypeOptions::get();

        return $types;
    }

    private function baseUrl(): string
    {
        return add_query_arg(
            array(
                'page' => SettingsPage::menuSlug(),
                'tab'  => 'types',
            ),
            admin_url('options-general.php')
        );
    }

    /**
     * @param array<mixed, mixed> $items
     */
    private function countItems(array $items): int
    {
        $total = 0;
        foreach ($items as $_ignored) {
            ++$total;
        }

        return $total;
    }

    private function viewLink(string $url, bool $is_current, string $label): string
    {
        $current = $is_current ? ' class="current"' : '';

        return '<a href="' . esc_url($url) . '"' . $current . '>' . esc_html($label) . '</a>';
    }

    private function countLabel(string $template, int $count): string
    {
        return \s2j_inquiry_destination_count_label($template, $count);
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array<string, mixed>>
     */
    private function sortTypeRows(array $rows, string $orderby, string $order): array
    {
        $total = $this->countItems($rows);
        for ($i = 0; $i < $total; ++$i) {
            for ($j = $i + 1; $j < $total; ++$j) {
                if ($this->compareTypeRows($rows[ $i ], $rows[ $j ], $orderby, $order) > 0) {
                    $swap         = $rows[ $i ];
                    $rows[ $i ]   = $rows[ $j ];
                    $rows[ $j ]   = $swap;
                }
            }
        }

        return $rows;
    }

    /**
     * @param array{slug: string, label: string} $a
     * @param array{slug: string, label: string} $b
     */
    private function compareTypeRows(array $a, array $b, string $orderby, string $order): int
    {
        $left  = 'label' === $orderby ? $a['label'] : $a['slug'];
        $right = 'label' === $orderby ? $b['label'] : $b['slug'];
        $cmp   = \s2j_inquiry_destination_strcasecmp($left, $right);

        return 'desc' === $order ? -$cmp : $cmp;
    }

    /**
     * @param list<string> $lines
     */
    private function joinLines(array $lines): string
    {
        $out = '';
        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $out .= "\n";
            }
            $out .= $line;
        }

        return $out;
    }

    /**
     * @param list<string> $hidden
     */
    private function columnIsHidden(string $column_name, array $hidden): bool
    {
        foreach ($hidden as $hidden_name) {
            if ($column_name === $hidden_name) {
                return true;
            }
        }

        return false;
    }
}
