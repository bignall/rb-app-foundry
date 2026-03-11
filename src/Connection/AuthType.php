<?php

declare(strict_types=1);


namespace RBCS\AppForge\Connection;

defined( 'ABSPATH' ) || exit;

/**
 * Authentication types supported by connections.
 *
 * @package RBCS\AppForge\Connection
 */
enum AuthType: string
{
    case OAuth2 = 'oauth2';
    case APIKey = 'api_key';
    case Bearer = 'bearer';
    case Basic = 'basic';
    case Webhook = 'webhook';
    case Custom = 'custom';

    /**
     * Get a human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::OAuth2  => 'OAuth 2.0',
            self::APIKey  => 'API Key',
            self::Bearer  => 'Bearer Token',
            self::Basic   => 'Basic Auth',
            self::Webhook => 'Webhook',
            self::Custom  => 'Custom',
        };
    }
}
