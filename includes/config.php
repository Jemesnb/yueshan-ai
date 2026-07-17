<?php
/**
 * 越山对话ai - 核心配置加载
 */

$models_file = __DIR__ . '/../models.json';

if (!file_exists($models_file)) {
    die("错误：找不到 models.json 配置文件。");
}

$models = json_decode(file_get_contents($models_file), true);
if (!$models) {
    $models = [];
}
