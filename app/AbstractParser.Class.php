<?php

namespace smovlad\netpeak_cli_parser;

/* Абстрактный класс парсинга */
abstract class AbstractParser
{
    abstract public function startParsing();
    abstract public function getUrl();
    abstract public function setUrl($url);
}