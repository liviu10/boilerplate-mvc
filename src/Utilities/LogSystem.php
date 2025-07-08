<?php

namespace LiviuVoica\BoilerplateMVC\Utils;

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
    private string $defaultLogFile;
    private string $defaultLogFileExtension;
    private string $lastLogDate;

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
     * @param string $level The log level (e.g., 'DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY').
     * @param array $context The context of the log entry, which will be encoded as JSON.
     * @param string|null $fileName The name of the log file. If not provided, the default log file is used.
     * 
     * @return void
     */
    public function handleLog($level, $context, $fileName): void
    {
        $currentDate = date('Y-m-d');

        if ($currentDate !== $this->lastLogDate) {
            $this->lastLogDate = $currentDate;
        }

        $logFile = '';
        $isFileName = gettype($fileName) === 'string' && $fileName !== null && $fileName !== '';

        if ($isFileName) {
            $logFile = "{$this->logDirectory}/{$fileName}_{$currentDate}{$this->defaultLogFileExtension}";
        } else {
            $logFile = "{$this->logDirectory}/{$this->defaultLogFile}_{$currentDate}{$this->defaultLogFileExtension}";
        }

        if (!is_dir($this->logDirectory)) {
            mkdir($this->logDirectory, 0777, true);
            chmod($this->logDirectory, 0777);
        }

        if (!file_exists($logFile)) {
            file_put_contents($logFile, '');
            chmod($logFile, 0777);
        }

        $contextJson = json_encode($context);
        $logContent = "[" . date('Y-m-d H:i:s') . "] $level\nPayload: $contextJson\n\n";

        file_put_contents($logFile, $logContent, FILE_APPEND);
    }
}
