<?php

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//   Простая онлайн фотогалерея "Photopad" // Трансляция RSS-канала          //
//   ----------------------------------------------------------------------  //
//   Copyright (C) 1998-2021 web-studio "Cherry-Design"                      //
//   URL: https://www.cherry-design.ru/                                      //
//   E-mail: mike@cherry-design.ru                                           //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

// Имя данного скрипта
$this_script = "rss.php"; 

// Производим инициализацию
require("includes/initialization.php"); 

// Если выключен режим трансляции RSS-потока, то делаем редирект на первую страницу
if (!$globals["rss_flag"]) {
    header("Location: ./");
}

// Количестве записей, транслируемых в RSS-канале
$globals["num_items"] = 5;

// Флаг кэширования RSS-потока
$globals["cache_flag"] = 1;

// Актуальное время жизни RSS-потока в кэше в секундах
$globals["cache_time"] = 600;

// Имя файла, хранящего кэшированное значение RSS-потока
$globals["cache_filename"] = "rss_last.dat";

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                 Функция получения данных для RSS-канала                   //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function get_rss_data() {

    global $globals;

    // Рассчитываем полный путь к файлу, хранящему кэш RSS-канала
    $cache_filename = $globals["path_temp"]."/".$globals["cache_filename"];

    // Сначала пробуем прочитать RSS-канал из кэша
    if ($globals["cache_flag"] && file_exists($cache_filename)) {

        // Рассчитываем время в секундах прошедшее с последнего обновления кэша
        $cache_time = time() - filemtime($cache_filename);

        // Проверяем актуальность кэша
        if ($cache_time < $globals["cache_time"]) {

            // Читаем из кэша строку с описанием RSS-канала
            $rss_data_serial = @file_get_contents($cache_filename);

            // Преобразуем строку в массив с данными RSS-канала
            $rss_data = unserialize($rss_data_serial);

            // Возвращаем кэшированное значение RSS-канала
            return $rss_data;
        }
    }

    // Рассчитываем полный URL сайта
    $website_url = "http://".$_SERVER["HTTP_HOST"].substr($_SERVER["REQUEST_URI"], 0, strrpos($_SERVER["REQUEST_URI"], "/")+1);

    // Формируем общие параметры канала
    $rss_data = array(
        "title"       => $globals["website_title"],
        "link"        => $website_url,
        "description" => $globals["website_words"],
        "items"       => ""
    );

    // Читаем список изображений в галерее
    $records = load_table($globals["path_data"]."images.txt");

    if ($records) {

        // Сортируем список фотографий по дате добавления
        $records = sort_table($records, "date", "alphabet", "desc");

        // Выбираем последние записи
        $records = array_slice($records, 0, $globals["num_items"]);
        
        // Формируем описание новостей для RSS-канала
        reset($records);
        while (list($id, $record) = each($records)) {

            // Рассчитываем адрес ссылки
            $link = $website_url."gallery.php?action=view&amp;id=".$record["id"];

            // Определяем дату модификации
            $date = date("Y-m-d H:i:s", strtotime($record["date"]));

            // Формируем описание фотографии
            if (!empty($record["text"])) {

                $description = $record["text"];
                $description = str_replace("\\n", " ", $description);

                // Укорачиваем описание до 450 символов
                if (strlen($description > 450)) {
                    $description = substr($description, 0, 450)."...";
                }

            } else {
                $description = $record["title"];
            }

            // Добавляем информацию в описание новости
            $items[] = array(
                "title"       => $record["title"],
                "link"        => $link,
                "description" => $description,
                "pubDate"     => $date
            );
        }

        // Добавляем описание новостей
        if (!empty($items)) {
            $rss_data["items"] = $items;
        }

        // Сохраняем RSS-канал в кэше
        if ($globals["cache_flag"] && !empty($rss_data)) {

            // Преобразуем массив в строку для сохранения в кэше
            $rss_data_serial = serialize($rss_data);

            // Сохраняем строку с данными RSS-канала в файле
            $fp = fopen($cache_filename,"w+");
            fwrite($fp, $rss_data_serial);
            fclose($fp);
        }
    }

    // Возвращаем рассчитанное значение RSS-канала
    return $rss_data;
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                         Функция печати RSS-канала                         //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function print_rss($channel) {

    // Формируем стандартный заголовок RSS-потока 
    $string  = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
    $string .= "<rss version=\"2.0\" xmlns:content=\"http://purl.org/rss/1.0/modules/content/\">\n";
    $string .= "<channel>\n";

    // Формируем общие параметры сайта
    $string .= "<title>".htmlspecialchars($channel["title"])."</title>\n";
    $string .= "<link>".$channel["link"]."</link>\n";
    $string .= "<description>".htmlspecialchars($channel["description"])."</description>\n";
    $string .= "<language>ru</language>\n";
    $string .= "<generator>Cherry-Design RSS-builder</generator>\n\n";

    // Формируем новости RSS-потока
    if (!empty($channel["items"])) {

        reset($channel["items"]);
        while (list($id, $item) = each($channel["items"])) {

            // Рассчитываем дату новости в формате RFC 2822
            $date = date("r", strtotime($item["pubDate"]));

            // Формируем параметры новости
            $string .= "<item>\n";
            $string .= "<title>".htmlspecialchars($item["title"])."</title>\n";
            $string .= "<link>".$item["link"]."</link>\n";
            $string .= "<description>".htmlspecialchars($item["description"])."</description>\n";
            $string .= "<pubDate>".$date."</pubDate>\n";
            $string .= "</item>\n\n";
        }
    }

    $string .= "</channel>\n";
    $string .= "</rss>";

    // Посылаем заголовок, определяющий, что далее пойдут XML-данные
    header("Content-type: text/xml");

    // Печатаем RSS-канал
    echo $string;
}

///////////////////////////////////////////////////////////////////////////////

// Осуществляем трансляцию RSS-канала
$rss = get_rss_data();
print_rss($rss);

?>