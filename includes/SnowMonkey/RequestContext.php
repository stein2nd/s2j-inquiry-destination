<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\SnowMonkey;

final class RequestContext
{
    private static bool $administrator_mail_succeeded = false;

    private static bool $auto_reply_phase = false;

    private static bool $autoreply_failed = false;

    private static string $correlation_id = '';

    private static int $form_id = 0;

    public static function reset(): void
    {
        self::$administrator_mail_succeeded = false;
        self::$auto_reply_phase             = false;
        self::$autoreply_failed             = false;
        self::$correlation_id               = '';
        self::$form_id                      = 0;
    }

    public static function setFormId(int $form_id): void
    {
        self::$form_id = $form_id;
    }

    public static function getFormId(): int
    {
        return self::$form_id;
    }

    public static function setCorrelationId(string $correlation_id): void
    {
        self::$correlation_id = $correlation_id;
    }

    public static function getCorrelationId(): string
    {
        return self::$correlation_id;
    }

    public static function markAdministratorMailSucceeded(string $correlation_id): void
    {
        self::$administrator_mail_succeeded = true;
        self::$correlation_id              = $correlation_id;
    }

    public static function administratorMailSucceeded(): bool
    {
        return self::$administrator_mail_succeeded;
    }

    public static function beginAutoReplyPhase(): void
    {
        self::$auto_reply_phase = true;
    }

    public static function inAutoReplyPhase(): bool
    {
        return self::$auto_reply_phase;
    }

    public static function markAutoreplyFailed(): void
    {
        self::$autoreply_failed = true;
    }

    public static function autoreplyFailed(): bool
    {
        return self::$autoreply_failed;
    }
}
