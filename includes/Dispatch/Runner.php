<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Dispatch;

use S2J\InquiryDestination\Logging\InquiryLogger;
use S2J\InquiryDestination\Options\DispatchOptions;
use S2J\InquiryDestination\Options\TypeOptions;
use S2J\InquiryDestination\SnowMonkey\PayloadTranslator;
use S2J\InquiryDestination\SnowMonkey\RequestContext;
use S2J\InquiryDestination\Support\Json;

use function S2J\InquiryDestinationService\build_dispatch_material;
use function S2J\InquiryDestinationService\map_dispatch_result;
use function S2J\InquiryDestinationService\validate_inquiry_dispatch;

final class Runner
{
    public static function run(RunContext $ctx): void
    {
        $correlation_id = self::newCorrelationId();
        RequestContext::setCorrelationId($correlation_id);
        RequestContext::setFormId($ctx->form_id);

        $types = TypeOptions::get();
        $type  = $ctx->form_row['inquiry_type'];
        if (! isset($types[ $type ])) {
            self::fail($correlation_id, $type, $ctx->form_id, 'inquiry_type_invalid', 'mail');
        }

        $raw_payload = PayloadTranslator::buildRawPayload($ctx->responser, $ctx->setting, $ctx->form_row);
        $destination = DestinationBuilder::build($type, $types[ $type ]);
        $kind    = isset($destination['kind']) ? (string) $destination['kind'] : 'mail';
        $channel = self::channelForKind($kind);

        $validated = validate_inquiry_dispatch($raw_payload, $destination);
        if ($validated['deficiencies'] !== array()) {
            $code = $validated['deficiencies'][0];
            self::fail($correlation_id, $type, $ctx->form_id, $code, $channel);
        }

        $built    = build_dispatch_material($validated['payload'], $validated['destination']);
        $material = $built['material'];
        if ($built['deficiencies'] !== array()) {
            $code = $built['deficiencies'][0];
            self::fail($correlation_id, $type, $ctx->form_id, $code, $channel);
        }
        if ($material === array()) {
            self::fail($correlation_id, $type, $ctx->form_id, 'destination_empty', $channel);
        }

        if (self::materialHasHttpUrl($material)) {
            self::executeHttp($correlation_id, $type, $ctx->form_id, $kind, $channel, $material);
            return;
        }

        self::executeMail($correlation_id, $type, $ctx->form_id, $channel, $validated, $material, $ctx->attachment_paths);
    }

    /**
     * @param array<string, mixed> $validated
     * @param array<string, mixed> $material
     * @param list<string>         $attachment_paths
     */
    private static function executeMail(
        string $correlation_id,
        string $type,
        int $form_id,
        string $channel,
        array $validated,
        array $material,
        array $attachment_paths
    ): void {
        $to = self::materialArray($material, 'to');
        if ($to === array()) {
            self::fail($correlation_id, $type, $form_id, 'destination_empty', $channel);
        }

        $content_type = self::materialString($material, 'content_type', 'text/plain');

        $headers = array(
            'Content-Type: ' . $content_type . '; charset=UTF-8',
            'From: ' . get_option('admin_email'),
        );

        $reply_email = (string) ( $validated['payload']['email'] ?? '' );
        if ('' !== $reply_email && is_email($reply_email)) {
            $headers[] = 'Reply-To: ' . $reply_email;
        }

        $subject = self::materialString($material, 'subject');
        $body    = self::materialString($material, 'body');

        $sent = wp_mail($to, $subject, $body, $headers, $attachment_paths);

        $result = map_dispatch_result(
            'mail',
            $sent,
            array(
                'inquiry_type'   => $type,
                'correlation_id' => $correlation_id,
            )
        );

        if (empty($result['success'])) {
            $failure = self::materialString($result, 'failure_code', 'mail_rejected');
            self::fail($correlation_id, $type, $form_id, $failure, $channel);
        }

        InquiryLogger::logAttempt($correlation_id, $type, $form_id, true, '', $channel);
        RequestContext::markAdministratorMailSucceeded($correlation_id);
    }

    /**
     * @param array<string, mixed> $material
     */
    private static function executeHttp(
        string $correlation_id,
        string $type,
        int $form_id,
        string $kind,
        string $channel,
        array $material
    ): void {
        $method  = self::materialString($material, 'method', 'POST');
        $url     = self::materialString($material, 'url');
        $headers = self::materialArray($material, 'headers');
        $body    = self::materialString($material, 'body');

        $response = wp_remote_request(
            $url,
            array(
                'method'  => $method,
                'headers' => $headers,
                'body'    => $body,
                'timeout' => 30,
            )
        );

        $http_status = (int) wp_remote_retrieve_response_code($response);
        if (is_wp_error($response)) {
            $http_status = 0;
        }

        $raw_body = is_wp_error($response) ? '' : wp_remote_retrieve_body($response);
        $parsed   = Json::decodeAssoc($raw_body);

        $result = map_dispatch_result(
            $kind,
            array(
                'http_status' => $http_status,
                'parsed_body' => $parsed,
            ),
            array(
                'inquiry_type'   => $type,
                'correlation_id' => $correlation_id,
            )
        );

        if (empty($result['success'])) {
            $failure = self::materialString($result, 'failure_code', 'saas_http_error');
            InquiryLogger::logAttempt(
                $correlation_id,
                $type,
                $form_id,
                false,
                $failure,
                $channel,
                array( 'http_status' => $http_status )
            );
            self::abortDispatch($failure, $correlation_id);
        }

        InquiryLogger::logAttempt(
            $correlation_id,
            $type,
            $form_id,
            true,
            '',
            $channel,
            array( 'http_status' => $http_status )
        );
        RequestContext::markAdministratorMailSucceeded($correlation_id);
    }

    /**
     * @param array<string, mixed> $material
     */
    private static function materialHasHttpUrl(array $material): bool
    {
        return isset($material['url']) && ! \s2j_inquiry_destination_is_array($material['url']);
    }

    /**
     * @param array<string, mixed> $material
     *
     * @return array<mixed, mixed>
     */
    private static function materialArray(array $material, string $key): array
    {
        if (! isset($material[ $key ]) || ! \s2j_inquiry_destination_is_array($material[ $key ])) {
            return array();
        }

        return $material[ $key ];
    }

    /**
     * @param array<string, mixed> $material
     */
    private static function materialString(array $material, string $key, string $default = ''): string
    {
        if (! isset($material[ $key ]) || \s2j_inquiry_destination_is_array($material[ $key ])) {
            return $default;
        }

        return (string) $material[ $key ];
    }

    private static function channelForKind(string $kind): string
    {
        return self::kindHasSaasPrefix($kind) ? 'saas' : 'mail';
    }

    private static function kindHasSaasPrefix(string $kind): bool
    {
        return 's' === ( $kind[0] ?? '' )
            && 'a' === ( $kind[1] ?? '' )
            && 'a' === ( $kind[2] ?? '' )
            && 's' === ( $kind[3] ?? '' )
            && '_' === ( $kind[4] ?? '' );
    }

    private static function newCorrelationId(): string
    {
        return 'sid-' . bin2hex(random_bytes(16));
    }

    private static function fail(
        string $correlation_id,
        string $inquiry_type,
        int $form_id,
        string $failure_code,
        string $channel = 'mail'
    ): void {
        InquiryLogger::logAttempt($correlation_id, $inquiry_type, $form_id, false, $failure_code, $channel);
        self::abortDispatch($failure_code, $correlation_id);
    }

    /**
     * @return never
     */
    private static function abortDispatch(string $failure_code, string $correlation_id): void
    {
        throw new DispatchFailedException(
            '[S2J Inquiry Destination] dispatch failed (' . $failure_code . '). correlation_id=' . $correlation_id
        );
    }
}
