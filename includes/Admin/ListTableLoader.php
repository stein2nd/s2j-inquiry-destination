<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Admin;

final class ListTableLoader
{
    public static function ensureLoaded(): void
    {
        if (class_exists('WP_List_Table')) {
            return;
        }

        $file = ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
        if (is_readable($file)) {
            require_once $file;
        }
    }
}
