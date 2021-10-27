<?php

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//   Простая онлайн фотогалерея "Photopad" // Просмотр галереи               //
//   ----------------------------------------------------------------------  //
//   Copyright (C) 1998-2021 web-studio "Cherry-Design"                      //
//   URL: https://www.cherry-design.ru/                                      //
//   E-mail: mike@cherry-design.ru                                           //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

// Имя данного скрипта
$this_script = "gallery.php"; 

// Производим инициализацию
require("includes/initialization.php"); 

// Определяем текущий тег для показа галереи
if (!empty($_REQUEST["tag"])) {
    $tag = $_REQUEST["tag"];
} else {
    $tag = "all";
}

// Количество миниатюр на страницу
$globals["records_per_page"] = 12;

// Регулярное выражения для нахождения ссылок в описании фотографии
$globals["regexp_url"] = "/((https?|ftp):\/\/[A-Z0-9-]+(\.[A-Z0-9-]+)*\/([A-Z0-9~\._-]+\/)*[A-ZА-ЯёЁІіЎўҐґЇї0-9.+*:;?\/&#%=_-]*)/ui";

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                 Функция формирования списка тегов из строки               //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function get_tags($string) {

    $tags = array();

    // Разбиваем строку на отдельные теги
    $tags_array = explode(",", $string);

    // Формируем список тегов с идентификаторами
    reset($tags_array);
    while (list($tag_id, $tag_title) = each($tags_array)) {

        // Формируем название и идентификатор тега
        $tag_title = trim($tag_title);
        $tag_id = calculate_ascii_string($tag_title);

        // Добавляем только уникальные теги
        if (!isset($tags[$tag_id])) {
            $tags[$tag_id]["title"] = $tag_title; 
            $tags[$tag_id]["frequency"] = 1; 
        } else {
            $tags[$tag_id]["frequency"]++; 
        }
    }

    // Сортируем список тегов
    ksort($tags); 

    return $tags;
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//              Функция чтения списка всех тегов из галереи                  //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function get_all_tags() {

    global $globals;

    // Читаем список изображений в галерее
    $records = load_table($globals["path_data"]."images.txt");

    if ($records) {

        // Формируем обобщенную строку из всех тегов
        $string = "";
        reset($records);
        while (list($id, $record) = each($records)) {
            if (!empty($record["tags"])) {
                $string .= $record["tags"].", ";
            }
        }
        $string = substr($string, 0, -2); 

        // Формируем список тегов из строки
        $tags = get_tags($string);

        // Возращаем готовый список тегов
        return $tags;

    } else {
        return "";
    }
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                         Функция печати списка тегов                       //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function print_tags($tags) {

    global $globals;

    $string  = "<div id=\"tags\">\n";
    $string .= "<h2>Ключевые слова</h2>\n";

    $string .= "<ul>\n";
    $string .= "<li><a href=\"gallery.php\">Все фотографии</a></li>\n";
    $string .= "<li><a href=\"gallery.php?action=tags\">Список тегов</a></li>\n";
    $string .= "</ul>\n";

    $string .= "<ul>\n";

    // Печатаем список тегов
    reset($tags);
    while (list($tag_id, $tag) = each($tags)) {

        // В случае необходимости рассчитываем число фотографий в категории
        if ($globals["frequency_flag"]) {
            $frequency_string = " <span class=\"frequency\">(".$tag["frequency"].")</span>";
        } else {
            $frequency_string = "";
        }

		// Формируем ссылку на категорию
		if ($globals["rewrite_flag"]) {
			$tag_url = "/".$tag_id.".htm";
		} else {
			$tag_url = "gallery.php?tag=".$tag_id;
		}

        // Формируем строку с названием категории
        $string .= "<li><a href=\"".$tag_url."\">".htmlspecialchars($tag["title"])."</a>".$frequency_string."</li>\n";
    }

    $string .= "</ul>\n";
    $string .= "</div>\n";
    
    echo $string; 
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                    Функция печати галереи для просмотра                   //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function print_gallery($tag) {

    global $this_script, $globals, $page;

    // Буферизируем вывод
    ob_start();

    // Читаем список изображений в галерее
    $records = load_table($globals["path_data"]."images.txt");

    if ($records) {

        // Сортируем список фотографий по дате добавления
        $records = sort_table($records, "date", "alphabet", "desc");

        // Читаем список всех тегов в галерее
        $gallery_tags = get_all_tags();

        // Формируем название страницы
        if (!empty($tag) && isset($gallery_tags[$tag])) {
            $title = htmlspecialchars($gallery_tags[$tag]["title"]);
            $title = strtoupper(substr($title, 0, 1)).substr($title, 1);
        } else {
            $title = "Все фотографии";
        }

        // Формируем список фотографий, подходящих под запрошенный тег
        if (!empty($tag) && isset($gallery_tags[$tag])) {

            reset($records);
            while (list($id, $record) = each($records)) {

                // Находим список тегов для данной фотографии
                $tags = get_tags($record["tags"]);

                // И если запрашиваемый тег не встречается, то удаляем фотографию из списка
                if (!isset($tags[$tag])) {
                    unset($records[$id]);
                }
            }
        }

        // Читаем общее количество записей
        $records_number = count($records);

        // Корректируем номер страницы
        if ($page < 1) {
            $page = 1;
        }
        if ($page > ceil($records_number / $globals["records_per_page"])) {
            $page = ceil($records_number / $globals["records_per_page"]);
        }

        // Выделяем из списка изображения, соответствующие текущей странице
        if ($records_number > $globals["records_per_page"]) {
            $records_offset = ($page - 1) * $globals["records_per_page"];
            $records = array_slice($records, $records_offset, $globals["records_per_page"]);
        }

        // Печатаем информацию о текущей категории
        echo "<address>Текущая категория</address>\n\n";

        // Печатаем список изображений
        $i = 0;
        echo "<div id=\"gallery\">\n";
        echo "<ul>\n";
        reset($records);
        while (list($id, $record) = each($records)) {

            // Рассчитываем ссылку для просмотра большого изображения
			if ($globals["rewrite_flag"]) {
	            $action_view = "/photo_".$record["id"].".htm";
			} else {
	            $action_view = $this_script."?action=view&amp;id=".$record["id"];
			}
			
            // Печатаем очередное изображение
            echo "<li><a href=\"".$action_view."\"><img src=\"".$globals["path_images"]."thumb_".$record["id_text"].".jpg\" alt=\"".htmlspecialchars($record["title"])."\" title=\"".htmlspecialchars($record["title"])."\" /></a></li>\n";

            $i++;
        }

        // Допечатываем ячейки-пустышки, чтобы всего их было 12 штук
        while ($i < $globals["records_per_page"]) {
            echo "<li class=\"empty\">&nbsp;</li>\n";
            $i++;
        }

        echo "</ul>\n";
        echo "</div>\n";

        // Печатаем список тегов в галерее
        print_tags($gallery_tags);

        // Печатаем навигацию по записям
        $query_string = "tag=".$tag;
        print_navigation($page, $globals["records_per_page"], $records_number, $query_string);

    } else {

        // Формируем название страницы
        $title = "Фотографии отсутствуют";

        // Печатаем сообщение об отсутствии странички
        echo "<p>В фотогалерею пока не загружено ни одного изображения. Попробуйте зайти чуть позже.</p>\n";
    }

    // Читаем буферизированный вывод в строку
    $content = ob_get_contents();
    ob_end_clean(); 

    // Формируем параметры страницы
    $globals["page"]["title"] = $title;
    $globals["page"]["content"] = $content;
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                     Функция печати списка всех тегов                      //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function print_all_tags() {

    global $this_script, $globals, $page;

    // Буферизируем вывод
    ob_start();

    // Читаем список изображений в галерее
    $records = load_table($globals["path_data"]."images.txt");

    if ($records) {

        // Читаем список всех тегов в галерее
        $gallery_tags = get_all_tags();

        // Находим максимальное значение частоты появления тега
        $frequency_max = 1;
        reset($gallery_tags);
        while (list($tag_id, $tag) = each($gallery_tags)) {
            if ($tag["frequency"] > $frequency_max) {
                $frequency_max = $tag["frequency"];
            }
        }

        // Формируем название страницы
        $title = "Список тегов";

        // Печатаем дополнительную информацию
        echo "<address>Ключевые слова</address>\n\n";

        // Печатаем список тегов
        echo "<p>\n";
        reset($gallery_tags);
        while (list($tag_id, $tag) = each($gallery_tags)) {

            // Рассчитываем высоту шрифта
            if ($frequency_max > 1) {
            	$fontsize = 100 + round((($tag["frequency"]-1) / ($frequency_max-1)) * 100);
            } else {
            	$fontsize = 100;
            }

			// Формируем ссылку на тег
			if ($globals["rewrite_flag"]) {
				$tag_url = "/".$tag_id.".htm";
			} else {
				$tag_url = "gallery.php?tag=".$tag_id;
			}

            // Печатаем очередной тег
            echo "<a href=\"".$tag_url."\"><span style=\"font-size: ".$fontsize."%;\">".htmlspecialchars($tag["title"])."</span></a> \n";
        }
        echo "</p>\n";

    } else {

        // Формируем название страницы
        $title = "Фотографии отсутствуют";

        // Печатаем сообщение об отсутствии странички
        echo "<p>В фотогалерею пока не загружено ни одного изображения. Попробуйте зайти чуть позже.</p>\n";
    }

    // Читаем буферизированный вывод в строку
    $content = ob_get_contents();
    ob_end_clean(); 

    // Формируем параметры страницы
    $globals["page"]["title"] = $title;
    $globals["page"]["content"] = $content;
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                   Функция вывода изображения для просмотра                //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function view_image($id) {

    global $globals;

    // Буферизируем вывод
    ob_start();

    // Читаем список изображений в галерее
    $records = load_table($globals["path_data"]."images.txt");

    // Читаем описание изображения
    $record = get_table_row($records, "id", $id);

    if ($record) {

        // Формируем название страницы
        $title = $record["title"];

        // Печатаем дату добавления фотографии
        echo "<address>Добавлено ".format_date($record["date"])."</address>\n\n";

        // Печатаем изображение для просмотра
        echo "<div id=\"gallery\">\n";
        echo "<p class=\"image\"><img src=\"".$globals["path_images"].$record["id_text"].".jpg\" alt=\"".htmlspecialchars($record["title"])."\" title=\"".htmlspecialchars($record["title"])."\" /></p>\n";

        // Печатаем комментарии к фотографии
        if (!empty($record["text"])) {

            // Делаем предварительную обработку переносов строк
            $record["text"] = str_replace("\\n", "\n", $record["text"]);

            // Преобразуем ссылки в тексте
            $record["text"] = nl2br(htmlspecialchars($record["text"]));
            $record["text"] = preg_replace($globals["regexp_url"], "<a href=\"\\1\">\\1</a>", $record["text"]);

            // Печатаем комментарий
            echo "<p>".$record["text"]."</p>\n";
        }

        echo "</div>\n";

        // Печатаем список тегов данной фотографии
        $tags = get_tags($record["tags"]);
        print_tags($tags);

    } else {

        // Формируем название страницы
        $title = "Фотография не найдена";

        // Печатаем сообщение об отсутствии странички
        echo "<p>Запрошенная Вами фотография отсутствует в системе.</p>\n";
    }

    // Читаем буферизированный вывод в строку
    $content = ob_get_contents();
    ob_end_clean(); 

    // Формируем параметры страницы
    $globals["page"]["title"] = $title;
    $globals["page"]["content"] = $content;
}

///////////////////////////////////////////////////////////////////////////////

if ($action == "view") { // Выводим изображение для просмотра

    view_image($id);
    print_page();

} elseif ($action == "tags") { // Печатаем полный список тегов

    print_all_tags();
    print_page();

} else { // Печатаем галерею для просмотра

    print_gallery($tag);
    print_page();
}

?>