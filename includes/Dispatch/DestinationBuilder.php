<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Dispatch;

use S2J\InquiryDestination\Options\DispatchOptions;
use S2J\InquiryDestination\Options\SaasOptions;

final class DestinationBuilder
{
    /**
     * @param array{label: string, mail_to: list<string>, saas_project_id: string, issue_type_id: string, priority_id: string} $type_row
     *
     * @return array<string, mixed>
     */
    public static function build(string $inquiry_type, array $type_row): array
    {
        $dispatch = DispatchOptions::get();

        if (DispatchOptions::CHANNEL_MAIL === $dispatch['channel']) {
            return array(
                'kind'            => 'mail',
                'inquiry_type'    => $inquiry_type,
                'mail_to'         => $type_row['mail_to'],
                'saas_endpoint'   => '',
                'saas_project_id' => '',
                'credentials'     => array(),
            );
        }

        $product = $dispatch['saas_product'];
        $kind    = DispatchOptions::destinationKind($product);

        $credentials = SaasOptions::runtimeCredentials($product);
        if (DispatchOptions::PRODUCT_BACKLOG === $product) {
            $credentials['issue_type_id'] = $type_row['issue_type_id'];
            $credentials['priority_id']   = $type_row['priority_id'];
        }

        return array(
            'kind'            => $kind,
            'inquiry_type'    => $inquiry_type,
            'mail_to'         => array(),
            'saas_endpoint'   => SaasOptions::runtimeEndpoint($product),
            'saas_project_id' => $type_row['saas_project_id'],
            'credentials'     => $credentials,
        );
    }
}
