<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Support;

final class SnowMonkeyForms
{
    public static function isActive(): bool
    {
        return defined('SNOW_MONKEY_FORMS_URL') || class_exists('\Snow_Monkey\Plugin\Forms\App\Model\Setting');
    }

    /**
     * @return list<\WP_Post>
     */
    public static function publishedForms(): array
    {
        $posts = get_posts(
            array(
                'post_type'      => 'snow-monkey-forms',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
            )
        );

        return $posts;
    }
}
