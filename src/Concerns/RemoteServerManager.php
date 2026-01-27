<?php

declare(strict_types=1);

namespace Core\Developer\Concerns;

use Core\Developer\Exceptions\SshConnectionException;
use Core\Developer\Models\Server;
use Core\Helpers\CommandResult;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;

/**
 * Trait for managing SSH connections to remote servers.
 *
 * Recommended usage with automatic cleanup:
 *   class DeployApplication implements ShouldQueue {
 *       use RemoteServerManager;
 *
 *       public function handle(): void {
 *           $this->withConnection($this->server, function () {
 *               $this->run('cd /var/www && git pull');
 *               $this->run('docker-compose up -d');
 *           });
 *       }
 *   }
 */
trait RemoteServerManager
{
    protected ?SSH2 $connection = null;

    protected ?Server $currentServer = null;

    /**
     * Connect to a remote server via SSH.
     *
     * @throws SshConnectionException
     */
    protected function connect(Server $server): SSH2
    {
        // Verify workspace ownership before connecting
        if (! $server->belongsToCurrentWorkspace()) {
            throw new SshConnectionException(
                'Unauthorised access to server.',
                $server->name
            );
        }

        $ssh = new SSH2($server->ip, $server->port ?? 22);
        $ssh->setTimeout(config('developer.ssh.connection_timeout', 30));

        // Load the private key
        $privateKey = $server->getDecryptedPrivateKey();
        if (! $privateKey) {
            throw new SshConnectionException(
                'Server credentials not configured.',
                $server->name
            );
        }

        try {
            $key = PublicKeyLoader::load($privateKey);
        } catch (\Throwable) {
            throw new SshConnectionException(
                'Invalid server credentials.',
                $server->name
            );
        }

        $username = $server->user ?? 'root';

        if (! $ssh->login($username, $key)) {
            $ssh->disconnect(); // Clean up socket on auth failure
            throw new SshConnectionException(
                'SSH authentication failed.',
                $server->name
            );
        }

        $this->connection = $ssh;
        $this->currentServer = $server;

        // Update server connection status with cleanup on failure
        try {
            $server->update([
                'status' => 'connected',
                'last_connected_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $this->disconnect();
            throw $e;
        }

        return $ssh;
    }

    /**
     * Execute operations with guaranteed connection cleanup.
     *
     * Usage:
     *   $result = $this->withConnection($server, function () {
     *       $this->run('git pull');
     *       return $this->run('docker-compose up -d');
     *   });
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     *
     * @throws SshConnectionException
     */
    protected function withConnection(Server $server, callable $callback): mixed
    {
        try {
            $this->connect($server);

            return $callback();
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Execute a command on the remote server via SSH.
     *
     * Note: This uses phpseclib's SSH2::exec() method which executes
     * commands on the REMOTE server over SSH, not locally.
     *
     * @throws SshConnectionException
     */
    protected function run(string $command, ?int $timeout = null): CommandResult
    {
        if (! $this->connection) {
            throw new SshConnectionException('Not connected to any server.');
        }

        $timeout ??= config('developer.ssh.command_timeout', 60);
        $this->connection->setTimeout($timeout);

        // Execute command on remote server via SSH and capture output
        $output = $this->connection->exec($command);
        $exitCode = $this->connection->getExitStatus() ?? 0;

        return new CommandResult(
            output: $output ?: '',
            exitCode: $exitCode
        );
    }

    /**
     * Execute a command and return success/failure.
     *
     * @throws SshConnectionException
     */
    protected function runQuietly(string $command, ?int $timeout = null): bool
    {
        return $this->run($command, $timeout)->isSuccessful();
    }

    /**
     * Execute multiple commands in sequence.
     *
     * @param  array<string>  $commands
     * @return array<CommandResult>
     *
     * @throws SshConnectionException
     */
    protected function runMany(array $commands, ?int $timeout = null): array
    {
        $results = [];

        foreach ($commands as $command) {
            $result = $this->run($command, $timeout);
            $results[] = $result;

            // Stop if a command fails
            if ($result->isFailed()) {
                break;
            }
        }

        return $results;
    }

    /**
     * Check if a file exists on the remote server.
     *
     * @throws SshConnectionException
     */
    protected function fileExists(string $path): bool
    {
        $escapedPath = escapeshellarg($path);
        $result = $this->run("test -f {$escapedPath} && echo 'exists'");

        return $result->contains('exists');
    }

    /**
     * Check if a directory exists on the remote server.
     *
     * @throws SshConnectionException
     */
    protected function directoryExists(string $path): bool
    {
        $escapedPath = escapeshellarg($path);
        $result = $this->run("test -d {$escapedPath} && echo 'exists'");

        return $result->contains('exists');
    }

    /**
     * Read a file from the remote server.
     *
     * @throws SshConnectionException
     */
    protected function readFile(string $path): string
    {
        $escapedPath = escapeshellarg($path);
        $result = $this->run("cat {$escapedPath}");

        if ($result->isFailed()) {
            throw new SshConnectionException(
                "Failed to read file: {$path}",
                $this->currentServer?->name
            );
        }

        return $result->output;
    }

    /**
     * Write content to a file on the remote server.
     *
     * Uses base64 encoding to safely transfer content without shell injection.
     *
     * @throws SshConnectionException
     */
    protected function writeFile(string $path, string $content): bool
    {
        $escapedPath = escapeshellarg($path);
        $encoded = base64_encode($content);

        return $this->run("echo {$encoded} | base64 -d > {$escapedPath}")->isSuccessful();
    }

    /**
     * Get the current server's disk usage.
     *
     * @throws SshConnectionException
     */
    protected function getDiskUsage(string $path = '/'): array
    {
        $escapedPath = escapeshellarg($path);
        $result = $this->run("df -h {$escapedPath} | tail -1 | awk '{print \$2, \$3, \$4, \$5}'");

        if ($result->isFailed()) {
            return [];
        }

        $parts = preg_split('/\s+/', trim($result->output));

        return [
            'total' => $parts[0] ?? 'unknown',
            'used' => $parts[1] ?? 'unknown',
            'available' => $parts[2] ?? 'unknown',
            'percentage' => $parts[3] ?? 'unknown',
        ];
    }

    /**
     * Get the current server's memory usage.
     *
     * @throws SshConnectionException
     */
    protected function getMemoryUsage(): array
    {
        $result = $this->run("free -h | grep 'Mem:' | awk '{print \$2, \$3, \$4}'");

        if ($result->isFailed()) {
            return [];
        }

        $parts = preg_split('/\s+/', trim($result->output));

        return [
            'total' => $parts[0] ?? 'unknown',
            'used' => $parts[1] ?? 'unknown',
            'free' => $parts[2] ?? 'unknown',
        ];
    }

    /**
     * Disconnect from the remote server.
     */
    protected function disconnect(): void
    {
        if ($this->connection) {
            $this->connection->disconnect();
            $this->connection = null;
        }

        $this->currentServer = null;
    }

    /**
     * Check if currently connected to a server.
     */
    protected function isConnected(): bool
    {
        return $this->connection !== null && $this->connection->isConnected();
    }

    /**
     * Get the current SSH connection.
     */
    protected function getConnection(): ?SSH2
    {
        return $this->connection;
    }
}
