<?php

namespace smovlad\netpeak_cli_parser;

/* Класс парсера */
class Parser extends AbstractParser
{
    private static $path = RESULT_PATH; // Название папки с результатами
    private $url; // Адрес страницы, которую нужно спарсить
    private $domain; // Название домена (без протокола)
    private $filename; // Путь к файлу с результатом парсинга

    /**
     * Parser constructor.
     */
    public function __construct($url)
    {
        $this->setUrl($url);
    }

    /**
     * Геттер URL
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Сеттер URL
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Геттер домена
     * @return mixed
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Сеттер домена
     * @param mixed $domain
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * Начать процедуру парсинга
     */
    public function startParsing()
    {
        // Приводим строку в нормальный URL с протоколом
        $url = $this->normalizeUrl($this->getUrl());

        // Выполняем парсинг
        $result = $this->parseUrl($url);

        // Выводим ссылку на результат
        if($result!=null && filesize($result)!=0) echo "\nПарсинг выполнен\nСсылка на CSV: {$result}";
        else echo "\nОшибка парсинга.";
    }

    /**
     * @param $str - строка с адресом
     * @return string - отформатированная строка с адресом
     */
    private function normalizeUrl($str)
    {
        // получаем отформатированный URL
        $urlArray = parse_url($str);
        $url = $urlArray['scheme'];
        if($url!="http" || $url!="https") $url = "https";
        $url .= "://".$urlArray['host'].$urlArray['path']."?".$urlArray['query'];

        // получаем домен
        $domain = $urlArray['host'];
        if($domain=="") {
            $domain = $urlArray['path'];
        }
        $this->setDomain($domain);

        return $url;
    }

    /**
     * Парсит заданную страницу
     * @param $url - адрес страницы
     * @param $recursive - указывает, выполнять ли рекуурсивный поиск страниц
     * @return string - ссылка на файл с результатом
     */
    private function parseUrl($url, $recursive=true)
    {
        echo "\nНачинаем парсинг страницы {$url}";

        // Получаем содержимое страницы
        $content = $this->getPageContent($url);

        // Создаём файл, в котором будет храниться результат
        $file = $this->createFile();

        // Выполним парсинг картинок и запишем в файл
        $images = $this->parseImages($content);
        $this->addToFile($images, $file);

        // Выполним парсинг ссылок на другие страницы и запустим парсинг на них
        $links = $this->parseLinks($content);
        if($recursive) {
            foreach ($links as $link) {
                $this->parseURL($link, false);
            }
            return $this->filename;
        }

    }

    /**
     * @param $url - URL страницы
     * @return string - Контент страницы
     */
    private function getPageContent($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        return curl_exec($curl);
    }

    /**
     * Создаёт файл с результатом
     * @return resource - файл
     */
    private function createFile()
    {
        // проверяем наличие папки и создаём, если её ещё нет
        $path = self::$path;
        if(!file_exists($path)) mkdir($path, 0755, true);

        // создаём файл
        $file = $this->filename;
        if($file==null){
            $md5 = md5($this->getDomain());
            $file = "{$path}/parsing-".$md5."-".date('Ymd-His').".csv";
            $this->filename = $file;
        }

        $filestream = fopen($file,"a+");
        return $filestream;
    }


    /**
     * Записывает содержимое в файл
     * @param $content - содержимое, которое нужно записать
     * @param $file - файл
     */
    private function addToFile($content, $file)
    {
        fwrite($file, $content);
    }


    /**
     * Получает список адресов с картинками
     * @param $content - исходный код
     * @return string - список адресов картинок
     */
    private function parseImages($content)
    {
        $result = '';
        $url = $this->getUrl();
        $regex='|<img.*?src="(.*?)"|';
        preg_match_all($regex,$content,$parts);
        $images=$parts[1];
        foreach($images as $image){
            //проверка на то, является ли путь абсолютным
            $absolute = $this->getAbsoluteURL($image);
            $result .= "{$absolute}, {$url}\n";
        }
        return $result;
    }

    /**
     * Получаем абсолютный URL для картинки
     * @param $img - относительная или абсолютная ссылка на картинку
     * @return string - абсолютная ссылка на изображение
     */
    private function getAbsoluteURL($img)
    {
        $url = $this->getUrl();
        $imgArray = parse_url($img);
        if($imgArray['host']==""){
            $urlArray = parse_url($url);
            return $urlArray['scheme']."://".$urlArray['host'].$imgArray['path'];
        } else return $img;
    }


    /**
     * Получает список адресов, на которые ведут ссылки
     * @param $content - исходный код
     * @return mixed
     */
    private function parseLinks($content)
    {
        $regex='|<a.*?href="(.*?)"|';
        preg_match_all($regex,$content,$parts);
        return $parts[1];
    }

}