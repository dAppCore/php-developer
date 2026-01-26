<?php

declare(strict_types=1);

namespace Mod\Developer\Exceptions;

use Exception;

/**
 * Exception thrown when an SSH connection fails.
 */
class SshConnectionException extends Exception
{
    public function __construct(
        string $message = 'SSH connection failed.',
        public readonly ?string $serverName = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the server name that failed to connect.
     */
    public function getServerName(): ?string
    {
        return $this->serverName;
    }
}
