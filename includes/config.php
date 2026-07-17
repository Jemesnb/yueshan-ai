<?php
/**
 * 越山对话ai - 核心配置加载
 */

$models_file = __DIR__ . '/../models.json';

if (!file_exists($models_file)) {
    die("错误：找不到 models.json 配置文件。");
}

/**
 * 加载 .env 环境变量
 */
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
        putenv(trim($name) . "=" . trim($value));
    }
}

loadEnv(__DIR__ . '/../.env');

$models = [];
if (file_exists($models_file)) {
    $models = json_decode(file_get_contents($models_file), true) ?: [];
}
