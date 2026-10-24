<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Dispatch;

use Snow_Monkey\Plugin\Forms\App\Model\Responser;
use Snow_Monkey\Plugin\Forms\App\Model\Setting;

final class RunContext
{
    /**
     * @param array{inquiry_type: string, fields: array{name: string, email: string, phone: string, message: string}} $form_row
     * @param list<string> $attachment_paths
     */
    public function __construct(
        public readonly Setting $setting,
        public readonly Responser $responser,
        public readonly int $form_id,
        public readonly array $form_row,
        public readonly array $attachment_paths,
    ) {
    }
}
