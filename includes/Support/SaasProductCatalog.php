<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Support;

use S2J\InquiryDestination\Options\DispatchOptions;

final class SaasProductCatalog
{
    /**
     * @return array<string, string> Product slug => localized display name.
     */
    public static function labels(): array
    {
        return array(
            DispatchOptions::PRODUCT_BACKLOG => \__('Backlog', 's2j-inquiry-destination'),
            DispatchOptions::PRODUCT_ASANA   => \__('Asana', 's2j-inquiry-destination'),
        );
    }

    public static function isSupported(string $product): bool
    {
        return array_key_exists($product, self::labels());
    }

    public static function choicePhrase(): string
    {
        $labels = array_values(self::labels());
        if ($labels === array()) {
            return '';
        }
        if (1 === count($labels)) {
            return $labels[0];
        }

        $last = array_pop($labels);

        return implode(
            /* translators: separator between SaaS product names in a list (e.g. "Backlog, Asana") */
            \_x(', ', 'SaaS product list separator', 's2j-inquiry-destination'),
            $labels
        )
            . \_x(' or ', 'SaaS product list final separator', 's2j-inquiry-destination')
            . $last;
    }
}
