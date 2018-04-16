<?php

/* Класс отчёта */
class Report {
    private static $path = RESULTPATH; // Название папки с результатами
    private $domain; // Домен, по которому нужно вывести отчёт

    public function __construct($url) {
        if($url != ""){
            // Приводим строку в нормальный URL с протоколом
            $domain = $this->normalizeDomain($url);

            // Сохраняем домен
            $this->setDomain($domain);

        } else echo "Ошибка. Введите корректный домен";
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
     * @param $url
     * @return string - отформатированный адрес домена
     */
    private function normalizeDomain($url){
        // получаем отформатированный домен
        $urlArray = parse_url($url);
        $domain = $urlArray['host'];
        if($domain=="") {
            $domain = $urlArray['path'];
        }
        return $domain;
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->makeReport();
    }

    /**
     * Создаёт отчёт о данном домене
     * @return string - отчёт
     */
    public function makeReport(){
        $report = '';

        // Хэш домена
        $domain = $this->getDomain();
        $md5 = md5($domain);

        // Сканируем папку, ищем CSV для этого домена и выводим содержимое
        $path = self::$path;
        if(file_exists($path)){
            $dir = scandir($path);
            foreach($dir as $id => $item){
                if(preg_match("/parsing-{$md5}/iu", $item)) {
                    if(filesize("{$path}/{$item}")!=0) {
                        $report .= file_get_contents("{$path}/{$item}");
                    }
                }
            }
        } else "У вас нет записей. Сначала произведите парсинг при помощи команды parse.";

        return $report;
    }
}