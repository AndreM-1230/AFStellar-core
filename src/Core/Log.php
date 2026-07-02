<?php

namespace App\Core;

use DateTime;
use DateTimeZone;
use Exception;
use SplFileObject;

class Log
{
    protected static $logPath = _ROOT . "/logs/app.log";
    protected static $logLevel = _LOG_LEVEL ?? 'INFO';

    public static function setPath(string $logFileName = 'app')
    {
        self::$logPath = _ROOT . "/logs/{$logFileName}.log";
    }

    public static function setLogLevel(string $logLevel = 'INFO')
    {
        self::$logLevel = $logLevel;
    }

    public static function write(string $message, string $logLevel = 'INFO', array $data = [], bool $subData = false)
    {
        if (
            (self::$logLevel != 'DEBUG' && $logLevel == 'DEBUG')
            ||(!in_array(self::$logLevel, ['DEBUG', 'WARNING']) && $logLevel == 'WARNING')
        ) return;
        
        $logDir = dirname(self::$logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir);
        }
        $date = (new DateTime())->format(DateTime::ATOM);
        if ($subData) {
            $data = array_merge($data, [
                'user-request-data' => [
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'method' => $_SERVER['REQUEST_METHOD'] ?? null,
                    'user-agent' => $_SERVER["HTTP_USER_AGENT"] ?? null,
                    'accept' => $_SERVER["HTTP_ACCEPT"] ?? null
                ]
            ]);
        }
        $context  =!empty($data) ? ' ' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
        $logEntry = "[{$date}] {$logLevel}: {$message}{$context}\n";
        file_put_contents(self::$logPath, $logEntry, FILE_APPEND | LOCK_EX);
    }

    public static function read($lenght = false, $type = false, $logFileName = "app")
    {
        $rows = [];
        if ($type !== false && !in_array($type, ['INFO', 'ERROR', 'WARNING'])) return;
        $logFile = _ROOT . "/logs/{$logFileName}.log";
        if (!file_exists($logFile)) {
            return $rows;
        }
        $logData = self::tailLines($logFile, $lenght, $type);
        foreach ($logData as $item) {
            $row = $item['content'];
            $line = $item['line'];
            if (trim($row) !== ''
                && preg_match('/^\[(.*?)\]\s+(.*)$/', $row, $matchesDate)
            ) {
                $jsonStart = strpos($matchesDate[2], '{');
                $rowTypeName = substr($matchesDate[2], 0, $jsonStart);
                $rowTypeNameSpace = strpos($rowTypeName, ' ');
                $rowType = str_replace(':', '', substr($rowTypeName, 0, $rowTypeNameSpace));
                $rowName = substr($rowTypeName, $rowTypeNameSpace);
                $date = DateTime::createFromFormat(DateTime::ATOM, $matchesDate[1]);
                if ($date) {
                    $date->setTimezone(new DateTimeZone('UTC'));
                }
                $rows[$line] = [
                    'date' => date('Y-m-d', $date->getTimestamp()),
                    'datetime' => date('Y-m-d H:i:s', $date->getTimestamp()),
                    'time' => date('H:i:s', $date->getTimestamp()),
                    'unixtime' => $date->getTimestamp(),
                    'type' => $rowType,
                    'title' => trim($rowName),
                    'data' => substr($matchesDate[2], $jsonStart-1)
                ];
            }
        }
        krsort($rows);
        return $rows;
    }

    public static function info($message, $data = [], $subData = false)
    {
        self::write($message, 'INFO', $data, $subData);
    }

    public static function error($message, $data = [], $subData = false)
    {
        self::write($message, 'ERROR', $data, $subData);
    }

    public static function warning($message, $data = [], $subData = false)
    {
        self::write($message, 'WARNING', $data, $subData);
    }

    public static function debug($message, $data = [], $subData = false)
    {
        self::write($message, 'DEBUG', $data, $subData);
    }

    public static function fatal($message, $data = [], $subData = false)
    {
        self::write($message, 'FATAL', $data, $subData);
    }

    private static function tailLines(string $path, int $limit, string $type): array
    {
        $result = [];
        $totalLines = self::countLines($path);
        $currentLine = $totalLines;
        $buffer = '';
        $chunkSize = 4096;
        $fh = fopen($path, 'r');
        fseek($fh, 0, SEEK_END);
        $pos = ftell($fh);
        $leftover = '';
        while($pos > 0 && count($result) <= $limit) {
            $readSize = min($chunkSize, $pos);
            $pos -= $readSize;
            fseek($fh, $pos);
            $buffer = fread($fh, $readSize) . $leftover;
            $splitLines = explode("\n", $buffer);
            $leftover = $pos > 0 ? array_shift($splitLines) : '';
            for ($i = count($splitLines) - 1; $i >= 0; $i--) {
                $line = $splitLines[$i];
                $currentLine--;
                if (trim($line) === '') continue;
                if (stripos($line, $type) !== false) {
                    $result[] = [
                        'line' => $currentLine,
                        'content' => $line,
                    ];
                    if (count($result) >= $limit) break;
                }
            }
        }
        fclose($fh);
        return $result;
    }

    private static function countLines(string $path): int
    {
        $fh = fopen($path, 'r');
        $count = 0;
        while (!feof($fh)) {
            $count += substr_count(fread($fh, 8192), "\n");
        }
        fclose($fh);
        return $count+2;
    }
}