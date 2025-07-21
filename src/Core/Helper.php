<?php

namespace App\Core;

class Helper
{
    static public function Exception(\Exception $e, bool $exit = false)
    {
        if (!defined('APP_DEBUG')) {
            define('APP_DEBUG', true);
        }
        http_response_code(500);
        echo "<div style='font-family: monospace; padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;'>
                <h3 style='color: #721c24;'>Ошибка: " . htmlspecialchars($e->getMessage()) . "</h3>
                <h3 style='color: #721c24;'>Код ошибки: " . htmlspecialchars($e->getCode()) . "</h3>";
        if (APP_DEBUG === true) {
            $get_file_err_lines_fn = function($path, $err_line, $main_err) {
                $snippet = "<h4 style='color: #721c24;'>Файл: " . htmlspecialchars(basename($path)) . "</h4>
                            <p>Строка: " . htmlspecialchars($err_line) . "</p>";
                $snippet .= "<div style='
                                    background: #f5f5f5;
                                    boder: 1px solid #ddd;
                                    border-radius: 4px;
                                    padding: 10px;
                                    font-family: monospace;
                                '>";
                if (file_exists($path)) {
                    $fileLines = @file($path);
                    $prev_lines = $main_err ? 10 : 3;
                    $next_lines = $main_err ? 5 : 1;
                    $firstLine = max(1, $err_line - $prev_lines);
                    $lastLine = min(count($fileLines), $err_line + $next_lines);
                    if ($fileLines !== false) {
                        for ($i = $firstLine; $i <= $lastLine; $i++) {
                            $line = htmlspecialchars($fileLines[$i -1] ?? '');
                            $snippet .= "<div style='padding: 2px; " . (($i == $err_line) ? 'background: #ef4444;' : '') . "'>
                                            <span style='color: " . (($i == $err_line) ? 'black;' :' #999;') . " user-select:none;'>{$i}: </span>{$line}
                                        </div>";
                        }
                    } else {
                        $snippet .= "<div style='padding: 2px;'>
                                        <span style='color: #999;'>1:</span> Файл пуст!
                                    </div>";
                    }

                } else {
                    $snippet .= "<div style='padding: 2px;'>
                                    <span style='color: #999;'>1:</span> Файл не существует!
                                </div>";
                }
                $snippet .= "</div>";
                return $snippet;
            };
            echo "<pre style='color: #721c24;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "<div>
                    <ul class='nav nav-tabs' style='margin-bottom: 15px;'>
                        <li class='active'><a href='#trace' data-toggle='tab'>Путь ошибки</a></li>";
            foreach($GLOBALS as $key => $value) {
                if (in_array($key, ['_GET', '_POST', '_COOKIE', '_FILES', '_SERVER', '_SESSION', 'auth_status', 'page_content'])) {
                    echo "<li class=''><a href='#" . htmlspecialchars($key) . "' data-toggle='tab'>\${$key}</a></li>";
                }
            }
            echo "</ul>
                    <div class='tab-content'>
                        <div class='tab-pane fade active in' id='trace'>";
            echo $get_file_err_lines_fn($e->getFile(), $e->getLine() ,true);
            foreach ($e->getTrace() as $data) {
                echo $get_file_err_lines_fn($data['file'], $data['line'], false);
            }
            echo "</div>";
            foreach($GLOBALS as $key => $value) {
                if (in_array($key, ['_GET', '_POST', '_COOKIE', '_FILES', '_SERVER', '_SESSION'])) {
                    echo "<div class='tab-pane fade' id='" . htmlspecialchars($key) . "'>
                                <pre>";
                    var_dump($value);
                    echo "</pre>
                                </div>";
                }
            }
            echo "</div>
                    </div>
                </div>";

        }
        echo "</div>";
        if ($exit === true) {
            exit;
        }
    }
}