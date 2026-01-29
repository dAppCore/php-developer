<?php

declare(strict_types=1);

/**
 * Developer Tools Module Configuration
 *
 * Settings for the developer tools, SSH connections, and Horizon notifications.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Hades (God-Mode) Token
    |--------------------------------------------------------------------------
    |
    | The token used to enable Hades (god-mode) access for developers.
    | This token is stored in an encrypted cookie when a Hades user logs in.
    | Changing this token will revoke access for all existing Hades sessions.
    |
    */

    'hades_token' => env('HADES_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | SSH Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for SSH connections to remote servers via the RemoteServerManager
    | trait. These timeouts help prevent hanging connections and runaway commands.
    |
    */

    'ssh' => [
        // Connection timeout in seconds (time to establish SSH connection)
        'connection_timeout' => env('DEVELOPER_SSH_CONNECTION_TIMEOUT', 30),

        // Command timeout in seconds (max time for individual commands)
        'command_timeout' => env('DEVELOPER_SSH_COMMAND_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Horizon Notifications
    |--------------------------------------------------------------------------
    |
    | Configure notification channels for Laravel Horizon alerts.
    | These notifications are sent when long wait times or job failures occur.
    |
    */

    'horizon' => [
        // SMS notification recipient (phone number)
        'sms_to' => env('HORIZON_SMS_TO'),

        // Email notification recipient
        'mail_to' => env('HORIZON_MAIL_TO'),

        // Slack webhook URL for notifications
        'slack_webhook' => env('HORIZON_SLACK_WEBHOOK'),

        // Slack channel for notifications (default: #alerts)
        'slack_channel' => env('HORIZON_SLACK_CHANNEL', '#alerts'),
    ],

];
