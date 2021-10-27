<?php

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//   Простая онлайн фотогалерея "Photopad" // Работа с файлами               //
//   ----------------------------------------------------------------------  //
//   Copyright (C) 1998-2021 web-studio "Cherry-Design"                      //
//   URL: https://www.cherry-design.ru/                                      //
//   E-mail: mike@cherry-design.ru                                           //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

// Имя данного скрипта
$this_script = "files.php"; 

// Производим инициализацию
require("includes/initialization.php"); 

// Если пользователь не авторизован, то делаем редирект на первую страницу
if (!$globals["user_entry_flag"]) {
    header("Location: ./");
}

// Максимальные размеры изображения по ширине и высоте
$globals["image_max_width"] = 580;
$globals["image_max_height"] = 580;

// Максимальные размеры миниатюры по ширине и высоте
$globals["thumb_max_width"] = 130;
$globals["thumb_max_height"] = 130;

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//       Функция масштабирования изображения и генерирования миниатюры       //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function processing_image($filename) {

    global $globals;

    // Находим размеры загруженного изображения
    $image_size = getimagesize($globals["path_images"].$filename); 
    $image_src_width = $image_size[0];
    $image_src_height = $image_size[1];

    // Проверяем необходимость масштабирования основного изображения
    if ($image_src_width > $globals["image_max_width"] || $image_src_height > $globals["image_max_height"]) {

        // Рассчитываем ширину и высоту нового изображения
        if ($image_src_width > $image_src_height) {
            $image_width = $globals["image_max_width"];
            $image_height = round($image_src_height / $image_src_width * $image_width);
        } else {
            $image_height = $globals["image_max_height"];
            $image_width = round($image_src_width / $image_src_height * $image_height);
        }

        // Читаем исходное изображение
        $image_src = @imagecreatefromjpeg($globals["path_images"].$filename);

        // Создаем новое изображение
        $image = @imagecreatetruecolor($image_width, $image_height);

        if ($image_src && $image) {

            // Производим масштабирование изображения
            imagecopyresampled($image, $image_src, 0, 0, 0, 0, $image_width, $image_height, $image_src_width, $image_src_height);

            // Сохраняем новое изображение вод старым именем
            imagejpeg($image, $globals["path_images"].$filename, 90);
            
            ImageDestroy($image);
        }
    }

    // Проверяем необходимость генерирования миниатюры
    if ($image_src_width > $globals["thumb_max_width"] || $image_src_height > $globals["thumb_max_height"]) {

        // Рассчитываем ширину и высоту нового изображения
        if ($image_src_width > $image_src_height) {
            $image_width = $globals["thumb_max_width"];
            $image_height = round($image_src_height / $image_src_width * $image_width);
        } else {
            $image_height = $globals["thumb_max_height"];
            $image_width = round($image_src_width / $image_src_height * $image_height);
        }

        // Читаем исходное изображение
        if (empty($image_src)) {
            $image_src = @imagecreatefromjpeg($globals["path_images"].$filename);
        }

        // Создаем новое изображение
        $image = @imagecreatetruecolor($image_width, $image_height);

        if ($image_src && $image) {

            // Производим масштабирование изображения
            imagecopyresampled($image, $image_src, 0, 0, 0, 0, $image_width, $image_height, $image_src_width, $image_src_height);

            // Сохраняем сгенерированную миниатюру
            imagejpeg($image, $globals["path_images"]."thumb_".$filename, 90);
            
            ImageDestroy($image);
            ImageDestroy($image_src);
        }
    }
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                        Функция добавления фотографии                      //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function add_record($record) {

    global $this_script, $globals;

    // Буферизируем вывод
    ob_start();

    // Флаг отрисовки формы
    $form_flag = 1;

    // Делаем предварительную обработку загруженного файла
    if (isset($_FILES["data"]["name"]["file"])) {

        $record["file"] = array(
            "name"     => $_FILES["data"]["name"]["file"],
            "type"     => $_FILES["data"]["type"]["file"],
            "size"     => $_FILES["data"]["size"]["file"],
            "tmp_name" => $_FILES["data"]["tmp_name"]["file"],
            "error"    => $_FILES["data"]["error"]["file"]
        );

    } else {
        $record["file"] = "";
    }

    // Печатаем форму добавления записи
    if (empty($record["file"])) {

        // Формируем сообщение для пользователя
        echo "<p>Выберите фотографию для загрузки в систему и укажите желаемое имя файла, под которым оно будет доступно на сайте. Если имя файла не указано, то оно будет сгенерировано автоматически, путем преобразования исходного имени.</p>\n";

        // Формируем параметры записи по умолчанию
        $record = array(
            "filename"  => "",
            "title"     => "",
            "text"      => "",
            "tags"      => ""
        );

    // Добавляем фотографию в галерею
    } else {

        // Проверяем, что заполнены все обязательные поля
        if (!empty($record["title"]) && !empty($record["tags"])) {

            // Рассчитываем расширение исходного файла
            $extension = substr(strrchr(strtolower($record["file"]["name"]), "."), 1);

            // Рассчитываем новое имя файла
            $name = substr($record["file"]["name"], 0, -(strlen($extension)+1));
            if (!empty($record["filename"])) {

                // Удаляем расширение из нового имени файла
                if (strrchr($record["filename"], ".")) {
                    $name = substr($record["filename"], 0, -strlen(strrchr($record["filename"], ".")));
                } else {
                    $name = $record["filename"];
                }
            }
            $name = calculate_ascii_string($name);
            $filename = $name.".".$extension;

            // Копируем загруженный файл в систему
            $result = move_uploaded_file($record["file"]["tmp_name"], $globals["path_images"].$filename);

            if ($result) {

                // Производим масштабирование изображения и генерирование миниатюры
                processing_image($filename);

                // Читаем список фотографий
                $gallery = load_table($globals["path_data"]."images.txt");

                // Рассчитываем первый свободный идентификатор
                $next_id = (int) get_table_value_last($gallery, "id") + 1;
                
                // Формируем текстовый идентификатор фотографии
                $id_text = $name;

                // Обрабатываем данные формы
                $record["title"] = stripslashes($record["title"]);
                $record["text"] = stripslashes($record["text"]);
                $record["tags"] = stripslashes($record["tags"]);

                // Преобразуем переносы строк в комментариях
                $record["text"] = str_replace("\r\n", "\n", $record["text"]);
                $record["text"] = str_replace("\n", "\\n", $record["text"]);

                // Формируем описание новой фотографии
                $photo = array (
                    "id"       => $next_id,
                    "id_text"  => $id_text,
                    "date"     => date("Y-m-d H:i:s"),
                    "tags"     => $record["tags"],
                    "title"    => $record["title"],
                    "text"     => $record["text"]
                );

                // Добавляем фотографию в галерею
                add_table_row($gallery, $photo);

                // Сохраняем информацию в таблице
               $result = save_table($gallery, $globals["path_data"]."images.txt");

                // Формируем сообщение для пользователя
                if ($result) {

                    // Осуществляем редирект на страничку просмотра фотографии
                    header("Location: gallery.php?action=view&id=".$next_id);
                    exit(); 

                } else {
                    echo "<p>Не удалось добавить фотографию в галерею. Попробуйте еще раз чуть позже.</p>\n";
                }

            } else {
                echo "<p>Не удалось добавить фотографию в галерею. Попробуйте еще раз чуть позже.</p>\n";
            }

            // Сбрасываем флаг отрисовки формы
            $form_flag = 0;

        } else {

            // Формируем сообщение об ошибке
            echo "<p>Вы не заполнили одно из обязательных полей. Пожалуйста, исправьте ошибку.</p>\n";
        }
    }

    // Отрисовываем форму
    if ($form_flag) {
?>
<form action="<?php echo $this_script; ?>?action=add" method="post" enctype="multipart/form-data">
<dl>
<dt><label>Фотография*</label></dt>
<dd><input type="file" size="30" name="data[file]" value="" /></dd> 
<dt><label>Новое имя файла**</label></dt>
<dd><input type="text" size="42" name="data[filename]" value="<?php echo htmlspecialchars(stripslashes($record["filename"])); ?>" /></dd>
</dl>
<p>Укажите название фотографии, а также список ключевых слов или фраз, описывающих фотографию, перечислив их через запятую. В случае необходимости, Вы можете также написать и дополнительные комментарии к фотографии.</p>
<dl>
<dt><label>Название*</label></dt>
<dd><input type="text" size="75" name="data[title]" value="<?php echo htmlspecialchars(stripslashes($record["title"])); ?>" /></dd> 
<dt><label>Комментарий</label></dt>
<dd><textarea rows="12" cols="75" name="data[text]"><?php echo htmlspecialchars(stripslashes($record["text"])); ?></textarea></dd> 
<dt><label>Ключевые слова*</label></dt>
<dd><input type="text" size="75" name="data[tags]" value="<?php echo htmlspecialchars(stripslashes($record["tags"])); ?>" /></dd> 
</dl>
<p class="button"><input type="submit" value=" Добавить " /></p>
</form>

<p><em>* Обязательные для заполнения поля<br />
** При указании нового имени файла, будет использоваться расширение исходного файла.</em></p>
<?php 
    }

    // Читаем буферизированный вывод в строку
    $content = ob_get_contents();
    ob_end_clean();

    // Формируем параметры страницы
    $globals["page"]["title"] = "Добавление фотографии";
    $globals["page"]["content"] = $content;
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                        Функция редактирования записи                      //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function edit_record($id, $record) {

    global $this_script, $globals;

    // Буферизируем вывод
    ob_start();

    // Флаг отрисовки формы
    $form_flag = 1;

    // Читаем список фотографий в галерее
    $gallery = load_table($globals["path_data"]."images.txt");

    // Читаем описание фотографии
    $photo = get_table_row($gallery, "id", $id);

    if (!empty($photo)) {

        // Печатаем форму добавления записи
        if (empty($record)) {

            // Формируем сообщение для пользователя
            echo "<p>Вы можете скорректировать название фотографии, описание или уточнить список ключевых слов и фраз. После внесения изменений они станут немедленно видны на сайте.</p>\n";

            // Делаем предварительную обработку переносов строк
            $photo["text"] = str_replace("\\n", "\n", $photo["text"]);

            // Формируем параметры записи по умолчанию
            $record = array(
                "title"     => addslashes($photo["title"]),
                "text"      => addslashes($photo["text"]),
                "tags"      => addslashes($photo["tags"])
            );

        // Добавляем фотографию в галерею
        } else {

            // Проверяем, что заполнены все обязательные поля
            if (!empty($record["title"]) && !empty($record["tags"])) {

                // Обновляем описание фотографии
                $photo["title"] = stripslashes($record["title"]);
                $photo["text"] = stripslashes($record["text"]);
                $photo["tags"] = stripslashes($record["tags"]);

                // Преобразуем переносы строк в комментариях
                $photo["text"] = str_replace("\r\n", "\n", $photo["text"]);
                $photo["text"] = str_replace("\n", "\\n", $photo["text"]);

                // Обновляем описание фотографии
                set_table_row($gallery, "id", $id, $photo);

                // Сохраняем информацию в таблице
                $result = save_table($gallery, $globals["path_data"]."images.txt");

                // Формируем сообщение для пользователя
                if ($result) {

                    // Осуществляем редирект на страничку просмотра фотографии
                    header("Location: gallery.php?action=view&id=".$id);
                    exit(); 

                } else {

                    // Печатаем сообщение о неудачном обновлении описания фотографии
                    echo "<p>Не удалось изменить описание фотографии. Попробуйте еще раз чуть позже.</p>\n";
                }

                // Сбрасываем флаг отрисовки формы
                $form_flag = 0;

            } else {
    
                // Формируем сообщение об ошибке
                echo "<p>Вы не заполнили одно из обязательных полей. Пожалуйста, исправьте ошибку.</p>\n";
            }
        }

    } else {

        // Печатаем сообщение об отсутствии странички
        echo "<p>Запрошенная Вами фотография отсутствует в системе.</p>\n";
        
        $form_flag = 0;

    }

    // Отрисовываем форму
    if ($form_flag) {
?>
<form action="<?php echo $this_script; ?>?action=edit&amp;id=<?php echo $id; ?>" method="post">
<dl>
<dt><label>Название*</label></dt>
<dd><input type="text" size="75" name="data[title]" value="<?php echo htmlspecialchars(stripslashes($record["title"])); ?>" /></dd> 
<dt><label>Комментарий</label></dt>
<dd><textarea rows="12" cols="75" name="data[text]"><?php echo htmlspecialchars(stripslashes($record["text"])); ?></textarea></dd> 
<dt><label>Ключевые слова*</label></dt>
<dd><input type="text" size="75" name="data[tags]" value="<?php echo htmlspecialchars(stripslashes($record["tags"])); ?>" /></dd> 
</dl>
<p class="button"><input type="submit" value=" Изменить " /></p>
</form>

<p><em>* Обязательные для заполнения поля</em></p>
<?php 
    }

    // Читаем буферизированный вывод в строку
    $content = ob_get_contents();
    ob_end_clean();

    // Формируем параметры страницы
    $globals["page"]["title"] = "Редактирование фотографии";
    $globals["page"]["content"] = $content;
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                           Функция удаления записи                         //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function delete_record($id, $record) {

    global $this_script, $globals;

    // Буферизируем вывод
    ob_start();

    // Флаг отрисовки формы
    $form_flag = 1;

    // Читаем список фотографий в галерее
    $gallery = load_table($globals["path_data"]."images.txt");

    // Читаем описание фотографии
    $photo = get_table_row($gallery, "id", $id);

    if (!empty($photo)) {

        // Печатаем форму добавления записи
        if (empty($record)) {

            // Печатаем миниатюру фотографии
            echo "<p class=\"thumbnail\"><img src=\"".$globals["path_images"]."thumb_".$photo["id_text"].".jpg\" alt=\"".htmlspecialchars($photo["title"])."\" title=\"".htmlspecialchars($photo["title"])."\"></p>\n";

            // Формируем сообщение для пользователя
            echo "<p>Вы уверены, что хотите удалить эту фотографию и ее описание из галереи?</p>\n";

        // Удаляем фотографию из галереи
        } else {

            // Проверяем, что пользователь подтвердил удаление
            if (isset($record["submit_confirm"])) {

                // Удаляем основное изображение
                $filename = $globals["path_images"].$photo["id_text"].".jpg";
                if (file_exists($filename)) {
                    $result = unlink($filename);
                }

                // Удаляем миниатюру
                $filename = $globals["path_images"]."thumb_".$photo["id_text"].".jpg";
                if (file_exists($filename)) {
                    $result = unlink($filename);
                }

                // Удаляем описание фотографии из системы
                remove_table_row($gallery, "id", $id);

                // Сохраняем информацию в таблице
                $result = save_table($gallery, $globals["path_data"]."images.txt");

                // Формируем сообщение для пользователя
                if ($result) {
                    echo "<p>Фотография успешно удалена из галереи.</p>\n";
                } else {
                    echo "<p>Не удалось удалить фотографию из галереи. Попробуйте еще раз чуть позже.</p>\n";
                }
    
                $form_flag = 0;

            } else {

                // В случае, если пользователь передумал удалять фотографию, то 
                // осуществляем редирект на страничку просмотра фотографии
                header("Location: gallery.php?action=view&id=".$id);
                exit(); 
            }
        }

    } else {

        // Печатаем сообщение об отсутствии странички
        echo "<p>Запрошенная Вами фотография отсутствует в системе.</p>\n";

        $form_flag = 0;
    }

    // Отрисовываем форму
    if ($form_flag) {
?>
<form action="<?php echo $this_script; ?>?action=delete&amp;id=<?php echo $id; ?>" method="post">
<p><input type="submit" name="data[submit_confirm]" value="  Да   " />
<input type="submit" name="data[submit_cancel]" value="   Нет   " /></p>
</form>
<?php
    }

    // Читаем буферизированный вывод в строку
    $content = ob_get_contents();
    ob_end_clean();

    // Формируем параметры страницы
    $globals["page"]["title"] = "Удаление фотографии";
    $globals["page"]["content"] = $content;
}

///////////////////////////////////////////////////////////////////////////////

if (!empty($_REQUEST["data"])) {
    $data = $_REQUEST["data"];
} else {
    $data = "";
}

if ($action == "delete") { // Удаляем фотографию

    delete_record($id, $data);
    print_page();

} elseif ($action == "edit") { // Редактируем описание фотографии

    edit_record($id, $data);
    print_page();

} else { // Добавляем фотографию

    add_record($data);
    print_page();
}

?>