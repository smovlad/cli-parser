<?php

/* Класс приложения */
class App {

    private $arg; //Массив аргументов

    /**
     * App constructor.
     * @param $args - массив аргументов, с которыми запущено CLI-приложение
     * В зависимости от команды выполняет действие
     */
    public function __construct($args)
    {
        /* Сохраняем массив аргументов */
        $this->setArguments($args);
        /* Определяем наличие функции и вызываем её */
        $func = $this->getArgument(1);
        if(method_exists($this, $func)) $this->$func();
        else echo "Приложение запущено с неправильным аргументом или без аргумента. Введите php <название_приложения> help, чтобы посмотреть список доступных команд";
    }

    /**
     * Устанавливает массив аргументов
     * @param $arg - массив аргументов, с которыми запущено CLI-приложение
     */
    private function setArguments($arg){
        $this->arg = $arg;
    }

    /**
     * Получает значение аргумента по порядковому номеру
     * @param $number - номер аргумента
     */
    private function getArgument($number){
        return $this->arg[$number];
    }

    /**
     * Метод Help выводит список команд с пояснениями
     */
    public function help() {
        echo <<<END
        
Список доступных команд:
parse <url> - запускает парсер на странице <url>
report <domain> - выводит в консоль результаты анализа для домена <domain>
help - выводит данное сообщение с описанием функций

END;
    }

    /**
     * Метод parse выполняет парсинг по url
     */
    public function parse(){
        echo "Выполняется парсинг...\n(наберитесь терпения, это занимает некоторое время)";

        // Устанавливаем адрес парсинга
        $url = $this->getArgument(2);

        // Проверяем наличие адреса и запускаем парсинг
        if($url != ""){
            $parser = new Parser( $url );
            $parser->startParsing();
        } else echo "\nОшибка парсинга. Введите валидный URL";
    }

    /**
     * Выводит данные анализа по домену
     */
    public function report(){
        echo "Загрузка...\n";
        $url = $this->getArgument(2);
        $report = new Report($url);
        $output = $report->makeReport();
        if($output == '') echo "По данному домену информации не найдено";
        else echo $output;
    }

}