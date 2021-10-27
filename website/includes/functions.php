<?php

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//   Простая онлайн фотогалерея "Photopad" // Общие функции                  //
//   ----------------------------------------------------------------------  //
//   Copyright (C) 1998-2021 web-studio "Cherry-Design"                      //
//   URL: https://www.cherry-design.ru/                                      //
//   E-mail: mike@cherry-design.ru                                           //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

// Таблица преобразования русского, белорусского и украинского текста в транслитерацию
$globals["transliteration"] = array (
    "а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d","е"=>"e","ё"=>"yo","ж"=>"j","з"=>"z",
    "и"=>"i","й"=>"i","к"=>"k","л"=>"l","м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
    "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h","ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch",
    "ъ"=>"","ы"=>"y","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya","і"=>"i","ў"=>"y","ґ"=>"g","ї"=>"i"
);

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//               Функция вывода содержимого страницы в браузер               //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function print_page() {

    global $globals;

    // Посылаем заголовки, запрещающие кэширование
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");

    // Печатаем дополнительные заголовки
    print_website_headers();

    // Печатаем основное меню
    print_main_menu();

    // Печатаем меню пользователя
    print_user_menu();

    // Формируем общие параметры страницы
    $globals["page"]["website_title"] = $globals["website_title"];
    $globals["page"]["website_words"] = $globals["website_words"];
    $globals["page"]["version"] = $globals["version"];

    // Производим парсинг переменных в шаблон
    $string = parse_template("main", $globals["page"]);

    // Выводим страницу в браузер
    echo $string;
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                    Функция печати дополнительных заголовков               //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function print_website_headers() {

    global $globals;

    // Если включен режим трансляции RSS-канала, то добавляем ссылку на канал в заголовке страницы
    if ($globals["rss_flag"]) {
        $string = "\n<link rel=\"alternate\" type=\"application/rss+xml\" title=\"".htmlspecialchars($globals["website_title"])."\" href=\"rss.php\" />";
    } else {
        $string = "";
    }

    // Сохраняем дополнительные заголовки в переменной
    $globals["page"]["website_headers"] = $string;
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                       Функция печати основного меню                       //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function print_main_menu() {

    global $this_script, $action, $globals, $id;

    // Формируем меню сайта
    $string  = "<ul>\n";
    $string .= "<li><a href=\"gallery.php\">Первая страница</a></li>\n";
    $string .= "<li><a href=\"gallery.php?action=tags\">Список тегов</a></li>\n";

    // В случае, если пользователь авторизовался
    if ($globals["user_entry_flag"]) {

        // Печатаем команду "Добавление" только в режиме просмотра странички
        if ($this_script == "gallery.php" && (empty($action) || $action == "tags")) {
            $string .= "<li><a href=\"files.php\">Добавление фотографии</a></li>\n";
        }

        // Печатаем команду "Редактирование" только в режиме просмотра
        if ($this_script == "gallery.php" && $action == "view") {
            $string .= "<li><a href=\"files.php?action=edit&amp;id=".$id."\">Редактирование</a></li>\n";
            $string .= "<li><a href=\"files.php?action=delete&amp;id=".$id."\">Удаление</a></li>\n";
        }

        // Печатаем команду "Просмотр" только в режиме редактирования
        if ($this_script == "files.php" && ($action == "edit" || $action == "delete")) {
            $string .= "<li><a href=\"gallery.php?action=view&amp;id=".$id."\">Просмотр</a></li>\n";
        }

        // Печатаем ссылку на страничку помощи
        $string .= "<li><a href=\"help.php\">Помощь</a></li>\n";

    } else {

        // Печатаем ссылку на страничку о проекте
        $string .= "<li><a href=\"about.php\">О проекте</a></li>\n";
    }

    $string .= "</ul>";

    // Сохраняем основное меню в переменной
    $globals["page"]["main_menu"] = $string;
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                     Функция печати меню пользователя                      //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function print_user_menu() {

    global $globals;

    // Формируем меню сайта
    $string  = "<ul>\n";

    if ($globals["user_entry_flag"]) {

        // В случае, если пользователь авторизовался
        $string .= "<li><a href=\"logout.php\">Выйти из системы</a></li>\n";

    } else {

        // В случае, если пользователь еще не был авторизован
        $string .= "<li><a href=\"login.php\">Войти в систему</a></li>\n";
    }

    $string .= "</ul>";

    // Сохраняем меню пользователя в переменной
    $globals["page"]["user_menu"] = $string;
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                   Функция добавления "волшебных кавычек"                  //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function add_magic_quotes(&$data) {

    reset($data);
    while(list($key, $value) = each($data)) {

        // Если переданные данные являются массивом, 
        // то вызываем функцию рекурсивно
        if (is_array($value)) {
            $data[$key] = add_magic_quotes($value);
        } else {
            $data[$key] = addslashes($value);
        }
    }

    return $data;
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                  Функция транслитерации строки в ASCII-код                //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function calculate_ascii_string($string) {

    global $globals;

    // Преобразуем строку в нижний регистр
    $string = strtolower(trim($string));

    // Преобразуем русские буквы в латинские
    $string = strtr($string, $globals["transliteration"]);

    // Убираем из строки все спецсимволы
    $string = preg_replace("/[^a-z0-9_ -]/u", "", $string);

    // Заменяем все двойные пробелы на одинарные
    $string = str_replace("  ", " ", $string);

    // Заменяем все пробелы на подчеркивание
    $string = str_replace(" ", "_", $string);

    // Ограничиваем длину строки 100 символами
    $string = substr($string, 0, 100);

    return $string;
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                      Функция печати ссылок навигаций                      //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function print_navigation($page, $records_per_page, $records_number, $query_string="") {

    $string = "<p class=\"navigation\">";

    // Количество страниц на секцию
    $pages_per_section = 20;

    // Находим число получившихся страниц
    $pages_number = ceil($records_number / $records_per_page);
    
    // Корректируем номер странички
    if ($page > $pages_number) {
        $page = $pages_number;
    }
    
    // Находим число получившихся секций
    $sections_number = ceil($pages_number / $pages_per_section);

    // Находим номер текущей секции
    $section = ceil($page / $pages_per_section);

    // Формируем навигацию в секции слева
    $prev_section_page = ($section-1) * $pages_per_section;
    if ($section > 1) {

        if ($query_string == "") {
            $string .= "<a href=\"?page=".$prev_section_page."\">[&lt;&lt;]</a>";
        } else {
            $string .= "<a href=\"?".$query_string."&amp;page=".$prev_section_page."\">[&lt;&lt;]</a>";
        }

        $string .= " &nbsp;";
    }

    // Рассчитываем начало и конец диапазона печатаемых страничек
    $start = ($section-1) * $pages_per_section + 1;
    $end = $start + $pages_per_section;
    if ($end > $pages_number + 1) {
        $end = $pages_number + 1;
    }

    // Печатаем странички со ссылками    
	for ($i = $start; $i < $end; $i++) {

        if ($i == $page) {
            $string .= "[".$i."]";
        } else {

            if ($query_string == "") {
                $string .= "<a href=\"?page=".$i."\">".$i."</a>";
            } else {
                $string .= "<a href=\"?".$query_string."&amp;page=".$i."\">".$i."</a>";
            }
        }
        $string .= " &nbsp;";
	}

    // Формируем навигацию в секции справа
    if ($section < $sections_number) {
        $next_section_page = $section * $pages_per_section + 1;
        if ($query_string == "") {
            $string .= "<a href=\"?page=".$next_section_page."\">[&gt;&gt;]</a>";
        } else {
            $string .= "<a href=\"?".$query_string."&amp;page=".$next_section_page."\">[&gt;&gt;]</a>";
        }
        $string .= " &nbsp;";
    }

    $string .= "// всего фотографий: ".$records_number."</p>\n";

    // Печатаем общее число записей
    echo $string;
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//    Форматирование даты из 'YYYY-MM-DD HH:MM:SS' в 'D/M/YYYY HH:MM:SS'     //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function format_date($date, $time_flag=1) {

    // Вычленяем из строки необходимые данные
    $year   = (int) substr($date,0,4);
    $month  = (int) substr($date,5,2);
    $day    = (int) substr($date,8,2);

    $hour   = (int) substr($date,11,2);
    $minute = (int) substr($date,14,2);
    $second = (int) substr($date,17,2);

    // Рассчитываем дату в формате UNIX-stamp
    $time_stamp = mktime($hour, $minute, $second, $month, $day, $year);

    // Формируем строку с датой и временем
    if ($time_flag) {
        $format_date = date("d/m/Y H:i:s", $time_stamp);
    } else {
        $format_date = date("d/m/Y", $time_stamp);
    }

    return $format_date;
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                     Функция парсинга переменных в шаблон                  //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function parse_template($template, $vars) {

    global $globals;
    
    // Читаем шаблон
    $string = @file_get_contents($globals["path_templates"].$template.".tpl");

    if ($string) {
    
        reset($vars);
        while (list($key, $value) = each($vars)) {

            // Производим замену всех переменных их значениями
            $string = str_replace("{".strtoupper($key)."}", $value, $string);
        }

    } else {
        $string = "<p>Шаблон с именем <em>".$template."</em> не обнаружен.</p>";
    }

    return $string;
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//            Функция расчета ссылки на страничку с которой пришли           //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function get_referer_link() {

    global $globals;

    // Определяем с какой странички пришел пользователь
    if (!empty($_SERVER["HTTP_REFERER"])) {

        // Находим адрес странички для возврата
        $link = substr(strrchr($_SERVER["HTTP_REFERER"], "/"), 1);

        // Обрабатываем включенный режим преобразования ссылок
        if ($globals["rewrite_flag"]) {

            // Обрабатываем статические ссылки
            if (!preg_match("/[a-z0-9_-]+\.htm$/ui", $link)) {
                $link = "./";
            }

        } else {

            // Обрабатываем динамические ссылки
            if (strstr($_SERVER["HTTP_REFERER"], "gallery.php?action=view&id=")) {
                $link = "gallery.php?action=view&id=".substr(strrchr($_SERVER["HTTP_REFERER"], "="), 1);
            } elseif (strstr($_SERVER["HTTP_REFERER"], "gallery.php?tag=")) {
                $link = "gallery.php?tag=".substr(strrchr($_SERVER["HTTP_REFERER"], "="), 1);
            } else {
                $link = "./";
            }
        }

    } else {
        $link = "./";
    }

    return $link;
}

?>