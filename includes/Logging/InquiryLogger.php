<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Logging;

final class InquiryLogger
{
    /**
     * @param array<string, mixed> $extra
     */
    public static function logAttempt(
        string $correlation_id,
        string $inquiry_type,
        int $form_id,
        bool $success,
        string $failure_code,
        string $channel = 'mail',
        array $extra = array()
    ): void {
        $record = array_merge(
            array(
                'ts'             => gmdate('c'),
                'correlation_id' => $correlation_id,
                'inquiry_type'   => $inquiry_type,
                'form_id'        => $form_id,
                'success'        => $success,
                'failure_code'   => $failure_code,
                'channel'        => $channel,
            ),
            $extra
        );

        error_log(wp_json_encode($record, JSON_UNESCAPED_UNICODE));
    }
}
