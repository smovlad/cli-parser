<?php
/**
 * ВЛАД СМОКОВ © 2018
 * Тестовое задание (Netpeak)
 */

/* Подключаем Autoload */
require 'vendor/autoload.php';

/* Точка входа приложения */
const RESULTPATH = 'result'; // Название папки в которой нужно хранить результаты
$App = new App($argv);