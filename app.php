<?php
/**
 * ВЛАД СМОКОВ © 2018
 * Тестовое задание (Netpeak)
 */

/* Класс приложения */
class App {

    private $arg; //Массив аргументов
    private $csv_link = null; //Ссылка на CSV
    private $domain; //Название домена (без протокола)

    /**
     * Метод parse выполняет парсинг по url
     */
    public function parse(){
        echo "Выполняется парсинг...\n(наберитесь терпения, это занимает некоторое время)";
        if($this->getArgument(2)!=""){
            /* Приводим строку в нормальный URL с протоколом */
            $url = $this->getUrlFromString($this->getArgument(2));
            /* Выполняем парсинг */
            $this->parseURL($url);
            if($this->csv_link!=null && filesize($this->csv_link)!=0) echo "Парсинг выполнен\nСсылка на CSV: ".$this->csv_link;
            else echo "Ошибка парсинга.";

        } else echo "Ошибка парсинга. Введите валидный URL";
    }

    /**
     * @param $str - строка с адресом
     * @return string - отформатированная строка с адресом
     */
    private function getUrlFromString($str){
        $urlArray = parse_url($str);
        $url = $urlArray['scheme'];
        if($url!="http" || $url!="https") $url = "https";
        $url .= "://".$urlArray['host'].$urlArray['path']."?".$urlArray['query'];

        // получаем домен
        $domain = $urlArray['host'];
        if($domain=="") {
            $domain = $urlArray['path'];
            $this->domain = $domain;
        }

        return $url;
    }

    /**
     * Получаем абсолютный URL для картинки
     * @param $img - относительная или абсолютная ссылка на картинку
     * @param $url - ссылка на сайт, который парсится
     * @return абсолютная ссылка на изображение
     */
    private function getAbsoluteURL($img, $url){
        $imgArray = parse_url($img);
        if($imgArray['host']==""){
            $urlArray = parse_url($url);
            return $urlArray['scheme']."://".$urlArray['host'].$imgArray['path'];
        } else return $img;
    }

    /**
     * Парсит заданную страницу
     * @param $url - адрес страницы
     * @param $recursive - указывает, выполнять ли рекуурсивный поиск страниц
     */
    private function parseURL($url, $recursive=true){
        echo "\nНачинаем парсинг страницы ".$url;
        /* Получаем страницу с помощью curl */
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);

        /* Ищем все картинки и заносим в csv */
        // проверяем наличие папки с csv и создаём, если её ещё нет
        if(!file_exists("csv")) mkdir("csv", 0755, true);

        // создадим файл (md5 - хэш названия домена)
        if($this->csv_link==null){
            $md5 = md5($this->domain);
            $filename = "csv/parsing-".$md5."-".date('Ymd-His').".csv";
            $this->csv_link = $filename;
        } else $filename = $this->csv_link;

        $csv = fopen($filename,"a+");

        // выполним парсинг картинок
        $regex='|<img.*?src="(.*?)"|';
        preg_match_all($regex,$result,$parts);
        $imgs=$parts[1];
        foreach($imgs as $img){
            //проверка на то, является ли путь абсолютным
            $imgabs = $this->getAbsoluteURL($img, $url);
            fwrite($csv, $imgabs.", ".$url."\n");
        }
        fclose($csv);

        /* Ищем все ссылки */
        $regex='|<a.*?href="(.*?)"|';
        preg_match_all($regex,$result,$parts);
        $links=$parts[1];

        curl_close($curl);

        // выполняем парсинг на других страницах
        if($recursive)
        foreach($links as $link){
            $this->parseURL($link, false);
        }
    }

    /**
     * Выводит данные анализа по домену
     */
    public function report(){
        echo "Загрузка...\n";
        if($this->getArgument(2) != ""){
            /* Приводим строку в нормальный URL с протоколом */
            $this->getUrlFromString($this->getArgument(2));
            /* Получаем домен */
            $domain = $this->domain;
            /* Хэш домена */
            $md5 = md5($domain);
            /* Сканируем папку, ищем CSV для этого домена и выводим содержимое */
            if(file_exists("csv")){
                $dir = scandir("csv");
                $is = false;
                foreach($dir as $id => $item){
                    if(preg_match("/parsing-".$md5."/iu",$item)) {
                        if(filesize("csv/".$item)!=0) {
                            $is = true;
                            echo file_get_contents("csv/".$item);
                        }
                    }
                }
                if (!is) echo "По данному домену записей не найдено";
            } else "У вас нет записей. Сначала произведите парсинг при помощи команды parse.";
        } else echo "Ошибка. Введите корректный домен";
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
     * Устанавливает массив аргументов
     * @param $arg - массив аргументов, с которыми запущено CLI-приложение
     */
    private function setArguments($arg){
        $this->arg = $arg;
    }

    /**
     * Получает значение аргумента по порядковому номеру
     * @param $arg - номер аргумента
     */
    private function getArgument($arg){
        return $this->arg[$arg];
    }

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
    }
}

/* Точка входа приложения */
$App = new App($argv);