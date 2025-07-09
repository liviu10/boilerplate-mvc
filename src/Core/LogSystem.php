<?php

namespace LiviuVoica\BoilerplateMVC\Core;

/**
 * Class LogSystem
 * 
 * Provides a simple file-based logging system with multiple log levels.
 * This class writes log entries to daily log files inside a specified directory.
 * Log files are named with an optional prefix, the current date, and a file extension.
 * Each log entry includes a timestamp, log level, and a JSON-encoded context payload.
 */
class LogSystem
{
    public const DEBUG_LEVEL = 'DEBUG';
    public const INFO_LEVEL = 'INFO';
    public const NOTICE_LEVEL = 'NOTICE';
    public const WARNING_LEVEL = 'WARNING';
    public const ERROR_LEVEL = 'ERROR';
    public const CRITICAL_LEVEL = 'CRITICAL';
    public const ALERT_LEVEL = 'ALERT';
    public const EMERGENCY_LEVEL = 'EMERGENCY';
    public const LOG_DIRECTORY = '/storage';
    public const DEFAULT_LOG_FILE = 'log';
    public const DEFAULT_LOG_FILE_EXTENSION = '.txt';
    private string $logDirectory;
    private string $lastLogDate = '';
    private string $defaultLogFile;
    private string $defaultLogFileExtension;

    public function __construct()
    {
        $this->logDirectory = dirname(__DIR__) . self::LOG_DIRECTORY;
        $this->defaultLogFile = self::DEFAULT_LOG_FILE;
        $this->defaultLogFileExtension = self::DEFAULT_LOG_FILE_EXTENSION;
        $this->lastLogDate = '';
    }

    /**
     * Handles logging by writing log entries to a specified log file.
     *
     * @param string $level The log level (e.g., 'DEBUG', 'INFO', etc.).
     * @param array $context The context of the log entry, which will be encoded as JSON.
     * @param string|null $fileName Optional log file name prefix.
     */
    public function handle(string $level, array $context, ?string $fileName = null): void
    {
        $currentDate = date('Y-m-d');

        if ($currentDate !== $this->lastLogDate) {
            $this->lastLogDate = $currentDate;
        }

        $filePrefix = $fileName && $fileName !== ''
            ? $fileName
            : $this->defaultLogFile;

        $logFile = "{$this->logDirectory}/{$filePrefix}_{$currentDate}{$this->defaultLogFileExtension}";

        if (!is_dir($this->logDirectory)) {
            mkdir($this->logDirectory, 0777, true);
            chmod($this->logDirectory, 0777);
        }

        if (!file_exists($logFile)) {
            file_put_contents($logFile, '');
            chmod($logFile, 0777);
        }

        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $timestamp = date('Y-m-d H:i:s');
        $logContent = "[{$timestamp}] {$level}\nPayload: {$contextJson}\n\n";

        file_put_contents($logFile, $logContent, FILE_APPEND);
    }
}
