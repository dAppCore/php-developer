<?php

declare(strict_types=1);

namespace Mod\Developer\Services;

/**
 * Service for reading and parsing Laravel log files.
 *
 * Provides memory-efficient methods for reading large log files
 * by reading from the end of the file rather than loading everything.
 * Automatically redacts sensitive information from log output.
 */
class LogReaderService
{
    /**
     * Patterns to redact from log output.
     * Keys are regex patterns, values are replacement text.
     */
    protected const REDACTION_PATTERNS = [
        // API keys and tokens (common formats)
        '/\b(sk_live_|sk_test_|pk_live_|pk_test_)[a-zA-Z0-9]{20,}\b/' => '[STRIPE_KEY_REDACTED]',
        '/\b(ghp_|gho_|ghu_|ghs_|ghr_)[a-zA-Z0-9]{36,}\b/' => '[GITHUB_TOKEN_REDACTED]',
        '/\bBearer\s+[a-zA-Z0-9\-_\.]{20,}\b/i' => 'Bearer [TOKEN_REDACTED]',
        '/\b(api[_-]?key|apikey)\s*[=:]\s*["\']?[a-zA-Z0-9\-_]{16,}["\']?/i' => '$1=[KEY_REDACTED]',
        '/\b(secret|token|password|passwd|pwd)\s*[=:]\s*["\']?[^\s"\']{8,}["\']?/i' => '$1=[REDACTED]',

        // AWS credentials
        '/\b(AKIA|ABIA|ACCA|ASIA)[A-Z0-9]{16}\b/' => '[AWS_KEY_REDACTED]',
        '/\b[a-zA-Z0-9\/+]{40}\b(?=.*aws)/i' => '[AWS_SECRET_REDACTED]',

        // Database connection strings
        '/mysql:\/\/[^:]+:[^@]+@/' => 'mysql://[USER]:[PASS]@',
        '/pgsql:\/\/[^:]+:[^@]+@/' => 'pgsql://[USER]:[PASS]@',
        '/mongodb:\/\/[^:]+:[^@]+@/' => 'mongodb://[USER]:[PASS]@',
        '/redis:\/\/[^:]+:[^@]+@/' => 'redis://[USER]:[PASS]@',

        // Email addresses (partial redaction)
        '/\b([a-zA-Z0-9._%+-]{2})[a-zA-Z0-9._%+-]*@([a-zA-Z0-9.-]+\.[a-zA-Z]{2,})\b/' => '$1***@$2',

        // IP addresses (partial redaction for privacy)
        '/\b(\d{1,3})\.(\d{1,3})\.\d{1,3}\.\d{1,3}\b/' => '$1.$2.xxx.xxx',

        // Credit card numbers (basic patterns)
        '/\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/' => '[CARD_REDACTED]',

        // JWT tokens
        '/\beyJ[a-zA-Z0-9\-_]+\.eyJ[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\b/' => '[JWT_REDACTED]',

        // Private keys
        '/-----BEGIN\s+(RSA\s+)?PRIVATE\s+KEY-----[\s\S]*?-----END\s+(RSA\s+)?PRIVATE\s+KEY-----/' => '[PRIVATE_KEY_REDACTED]',

        // Common env var patterns in stack traces
        '/(DB_PASSWORD|MAIL_PASSWORD|REDIS_PASSWORD|AWS_SECRET)["\']?\s*=>\s*["\']?[^"\'}\s]+["\']?/i' => '$1 => [REDACTED]',
    ];

    /**
     * Read the last N lines from a file efficiently.
     *
     * Uses a backwards-reading approach to avoid loading large files into memory.
     *
     * @param  string  $filepath  Path to the file
     * @param  int  $lines  Number of lines to read
     * @param  int  $bufferSize  Bytes to read at a time
     * @return array<string>
     */
    public function tailFile(string $filepath, int $lines = 100, int $bufferSize = 4096): array
    {
        if (! file_exists($filepath)) {
            return [];
        }

        $handle = fopen($filepath, 'r');
        if ($handle === false) {
            return [];
        }

        fseek($handle, 0, SEEK_END);
        $pos = ftell($handle);

        $result = [];
        $buffer = '';

        while ($pos > 0 && count($result) < $lines) {
            $readSize = min($bufferSize, $pos);
            $pos -= $readSize;
            fseek($handle, $pos);
            $buffer = fread($handle, $readSize).$buffer;

            $bufferLines = explode("\n", $buffer);
            $buffer = array_shift($bufferLines) ?? '';

            foreach (array_reverse($bufferLines) as $line) {
                if ($line !== '') {
                    array_unshift($result, $line);
                    if (count($result) >= $lines) {
                        break;
                    }
                }
            }
        }

        if ($buffer !== '' && count($result) < $lines) {
            array_unshift($result, $buffer);
        }

        fclose($handle);

        return array_slice($result, -$lines);
    }

    /**
     * Read and parse Laravel log entries from end of file.
     *
     * @param  string  $logFile  Path to the log file
     * @param  int  $maxLines  Maximum lines to read from file
     * @param  int  $maxBytes  Maximum bytes to read from end of file
     * @param  string|null  $levelFilter  Optional level filter (debug, info, warning, error, etc.)
     * @return array<array{time: string, level: string, message: string}>
     */
    public function readLogEntries(
        string $logFile,
        int $maxLines = 500,
        int $maxBytes = 102400,
        ?string $levelFilter = null
    ): array {
        if (! file_exists($logFile)) {
            return [];
        }

        $handle = fopen($logFile, 'r');
        if (! $handle) {
            return [];
        }

        $fileSize = filesize($logFile);

        if ($fileSize > $maxBytes) {
            fseek($handle, -$maxBytes, SEEK_END);
            fgets($handle); // Skip partial first line
        }

        $lines = [];
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if (! empty($line)) {
                $lines[] = $line;
            }
        }
        fclose($handle);

        $lines = array_slice($lines, -$maxLines);
        $logs = [];

        foreach ($lines as $line) {
            if (preg_match("/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(\w+): (.+)$/", $line, $matches)) {
                $level = strtolower($matches[2]);

                if ($levelFilter && $level !== $levelFilter) {
                    continue;
                }

                $logs[] = [
                    'time' => $matches[1],
                    'level' => $level,
                    'message' => $this->redactSensitiveData($matches[3]),
                ];
            }
        }

        return $logs;
    }

    /**
     * Redact sensitive data from a string.
     *
     * Applies all configured redaction patterns to protect sensitive
     * information from being displayed in logs.
     */
    public function redactSensitiveData(string $text): string
    {
        foreach (self::REDACTION_PATTERNS as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text) ?? $text;
        }

        return $text;
    }

    /**
     * Get the default Laravel log file path.
     */
    public function getDefaultLogPath(): string
    {
        return storage_path('logs/laravel.log');
    }

    /**
     * Get all available log files in the logs directory.
     *
     * Returns log files sorted by modification time (newest first).
     *
     * @return array<array{name: string, path: string, size: int, modified: int}>
     */
    public function getAvailableLogFiles(): array
    {
        $logDir = storage_path('logs');
        if (! is_dir($logDir)) {
            return [];
        }

        $files = [];
        $patterns = ['*.log', '*.log.*'];

        foreach ($patterns as $pattern) {
            $matches = glob("{$logDir}/{$pattern}");
            if ($matches) {
                foreach ($matches as $file) {
                    if (is_file($file)) {
                        $files[$file] = [
                            'name' => basename($file),
                            'path' => $file,
                            'size' => filesize($file),
                            'modified' => filemtime($file),
                        ];
                    }
                }
            }
        }

        // Sort by modification time, newest first
        usort($files, fn ($a, $b) => $b['modified'] <=> $a['modified']);

        return array_values($files);
    }

    /**
     * Get the current log file based on Laravel's logging configuration.
     *
     * Handles both single and daily log channels.
     */
    public function getCurrentLogPath(): string
    {
        $channel = config('logging.default');
        $channelConfig = config("logging.channels.{$channel}");

        $driver = $channelConfig['driver'] ?? null;

        // Single driver uses the configured path directly
        if ($driver === 'single') {
            return $channelConfig['path'] ?? storage_path('logs/laravel.log');
        }

        // Daily driver uses dated log files
        if ($driver === 'daily') {
            $path = $channelConfig['path'] ?? storage_path('logs/laravel.log');
            $baseName = pathinfo($path, PATHINFO_FILENAME);
            $dir = pathinfo($path, PATHINFO_DIRNAME);
            $ext = pathinfo($path, PATHINFO_EXTENSION);

            $todayLog = "{$dir}/{$baseName}-".date('Y-m-d').".{$ext}";

            if (file_exists($todayLog)) {
                return $todayLog;
            }

            // Fall back to configured path if today's log doesn't exist yet
            return $path;
        }

        // Fallback for other drivers or when no channel config exists
        return $this->getDefaultLogPath();
    }

    /**
     * Clear the contents of a log file.
     *
     * @return int|false The previous file size in bytes, or false on failure
     */
    public function clearLogFile(string $logFile): int|false
    {
        if (! file_exists($logFile)) {
            return false;
        }

        $previousSize = filesize($logFile);
        $result = file_put_contents($logFile, '');

        return $result !== false ? $previousSize : false;
    }
}
