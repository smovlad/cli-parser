<?php
/**
 * ВЛАД СМОКОВ © 2018
 * Тестовое задание (Netpeak)
 */
namespace smovlad\netpeak_cli_parser;

/* Подключаем Autoload */
require 'vendor/autoload.php';

/* Точка входа приложения */
const RESULT_PATH = 'result'; // Название папки в которой нужно хранить результаты
$App = new App($argv);