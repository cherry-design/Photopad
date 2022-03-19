<?php

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//   Функции работы c файлами (v2.7)                                         //
//   ---------------------------------------------------------------------   //
//   Copyright (C) 1998-2022 Studio "Cherry-Design"                          //
//   URL: https://www.cherry-design.com/                                     //
//   E-mail: mike@cherry-design.com                                          //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

// Для корректного функционирования скриптов работы с файлами, необходимо
// предварительно определить переменную $globals["path_temp"] с адресом директории
// для временных файлов. Адрес должен быть без закрывающего слеша

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                        Функция разбора строки записи                      //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function explode_record($format_string, $string) {

    // Разбираем строку формата записи
    $fields = explode (" | ", chop($format_string));

    // Разбираем строку записи
    $temp_record = explode (" | ", chop($string)." ");

    // Формируем именованный массив с данными
    reset($fields);
    while (list($id, $name) = each($fields)) {
        if (!isset($temp_record[$id])) {
            $record[$name] = "";
        } else {
            $record[$name] = chop($temp_record[$id]);
        }
    }

    return $record;
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                     Функция создания строки записи                        //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function implode_record($format_string, $record) {

    // Разбираем строку формата записи
    $fields = explode (" | ", chop($format_string));

    // Формируем строку записи
    $string = "";
    reset($fields);
    while (list($id, $name) = each($fields)) {
        if (!isset($record[$name])) {
            $record[$name] = "";
        }
        $string .= trim($record[$name])." | ";
    }

    $string = substr($string, 0, strlen($string)-3);
    $string .= "\r\n";
    
    return $string;
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                     Функция загрузки таблицы из файла                     //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function load_table($filename, $key="", $key_var="") {

    // Читаем записи из файла
    $records = @file($filename);

    if ($records && count($records) > 1) {

        // Считываем форматную строку
        list($id, $value) = each($records);
        $format_string = chop($value);
        unset($records[$id]);

        $order_id = 1;
        $records_number = count($records);

        $table_array = "";

        while (list($id, $value) = each($records)) {

            // Разбираем строку записи
            $record = explode_record($format_string, $value);

            // Добавляем в массив только записи, удовлетворяющие условию
            if (!empty($key) && !empty($key_var)) {
                if (empty($record[$key]) ||  $record[$key] != $key_var) {
                    continue;
                }
            }

            // Если отсутствует ID записи, 
            // то добавляем номер строки в качестве идентификатора
            if (!isset($record["id"])) {
                $record["id"] = $order_id;
            }
            
            // Добавляем порядковый номер записи
            $record["order_id"] = $order_id;

            // Добавляем новую запись в таблицу
            $table_array[] = $record;

            // Увеличиваем номер записи по списку
            $order_id++;
        }
        
        return $table_array;

    } else {
        return "";
    }
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                     Функция сохранения таблицы в файл                     //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function save_table($table, $filename) {

    global $globals;

    // Читаем записи из файла
    clearstatcache();
    $records = @file($filename);

    if ($records) {

        // Считываем форматную строку
        list($key, $value) = each($records);
        $format_string = chop($value);
        unset($records[$key]);

        // Формируем временный файл для того, чтобы избежать ситуации
        // обнуления файла при одновременной работе нескольких скриптов
        $tempfile = tempnam($globals["path_temp"], "chr");

        // Открываем временный файл для записи
        $fp = fopen($tempfile,"w+");
		
        // Сохраняем во временном файле строку формата
        fwrite($fp, $format_string."\r\n");
        
        // Сохраняем построчно записи из таблицы (если она не пустая)
        if (!empty($table)) {

            reset ($table);
            while (list($key, $record) = each($table)) {
    
                // Формируем строку записи и сохраняем ее во временном файле
                $value = implode_record($format_string, $record);
                fwrite($fp, $value);
            }
        }

        fclose($fp);
        
        // Переименовываем временный файл в нужный
        if (@rename($tempfile, $filename) == false) {
            @unlink($filename);
            @rename($tempfile, $filename);
        }

        // Корректируем права файла
        @chmod($filename, 0666);

        return 1;

    } else {
        return 0;
    }
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                       Функция чтения строки таблицы                       //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function get_table_row($table, $key, $key_var) {

    if ($table) {

        $flag = 0;

        reset($table);
        while (list($id, $record) = each($table)) {
            if ($record[$key] == $key_var) {
                $flag = 1;
                break;
            }
        }

        if ($flag) {
            return $record;
        } else {
            return "";
        }
        
    } else {
        return "";
    }
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                       Функция изменения строки таблицы                    //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function set_table_row(&$table, $key, $key_var, $row) {

    if ($table) {

        $flag = 0;

        // Ищем нужную строку
        reset($table);
        while (list($id, $record) = each($table)) {
            if ($record[$key] == $key_var) {
                $flag = 1;
                break;
            }
        }

        // Если нашли, то меняем строку
        if ($flag) {
            $table[$id] = $row;
            return 1;
        } else {
            return 0;
        }
        
    } else {
        return 0;
    }
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                       Функция удаления строки таблицы                     //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function remove_table_row(&$table, $key, $key_var) {

    if ($table) {

        $flag = 0;

        // Ищем нужную строку
        reset($table);
        while (list($id, $record) = each($table)) {
            if ($record[$key] == $key_var) {
                $flag = 1;
                break;
            }
        }

        // Если нашли, то удаляем строку
        if ($flag) {
            unset($table[$id]);
            return 1;
        } else {
            return 0;
        }
        
    } else {
        return 0;
    }
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                       Функция добавления строки в таблицу                 //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function add_table_row(&$table, $row) {

    if (isset($table)) {

        // Добавляем строку к таблице
        $table[] = $row;
        return 1;

    } else {
        return 0;
    }
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//               Функция получения значения переменной из таблицы            //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function get_table_value($table, $key, $key_var, $value) {

    if ($table) {

        $value_var = "";

        reset($table);
        while (list($id, $record) = each($table)) {
            if (isset($record[$key]) &&  $record[$key] == $key_var) {
                if (isset($record[$value])) {
                    $value_var = $record[$value];
                    break;
                }
            }
        }

        return $value_var;
        
    } else {
        return "";
    }
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//               Функция установки значения переменной в таблице             //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function set_table_value(&$table, $key, $key_var, $value, $value_var) {

    if ($table) {

        $flag = 0;

        // Находим нужную запись
        reset($table);
        while (list($id, $record) = each($table)) {
            if (isset($record[$key]) &&  $record[$key] == $key_var) {
                if (isset($record[$value])) {
                    $flag = 1;
                    break;
                }
            }
        }

        // Если такая переменная найдена, то устанавливаем ее в новое значение
        if ($flag) {
            $table[$id][$value] = $value_var;
            return 1;
        } else {
            return 0;
        }
        
    } else {
        return 0;
    }
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//           Функция получения значения первой переменной из таблицы         //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function get_table_value_first($table, $key) {

    if ($table) {

        reset($table);
        list($id, $record) = each($table);

        if (isset($record[$key])) {
            return $record[$key];
        } else {
            return "";
        }
        
    } else {
        return "";
    }
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//          Функция получения значения последней переменной из таблицы       //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function get_table_value_last($table, $key) {

    if ($table) {

        end($table);
        list($id, $record) = each($table);

        if (isset($record[$key])) {
            return $record[$key];
        } else {
            return "";
        }
        
    } else {
        return "";
    }
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//         Функция получения значения предыдущей переменной из таблицы       //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function get_table_value_prev($table, $key, $key_var, $value) {

    if ($table) {

        $flag = 0;
        reset($table);
        while(list($id, $record) = each($table)) {
            if (isset($record[$key]) &&  $record[$key] == $key_var) {
                if (isset($record[$value])) {
                    $flag = 1;
                    break;
                }
            }

            $prev_value = $record[$value];
        }

        if ($flag && isset($prev_value)) {
            return $prev_value;
        } else {
            return "";
        }
        
    } else {
        return "";
    }
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//         Функция получения значения следующей переменной из таблицы        //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function get_table_value_next($table, $key, $key_var, $value) {

    if ($table) {

        $flag = 0;
        reset($table);
        while(list($id, $record) = each($table)) {
            if (isset($record[$key]) &&  $record[$key] == $key_var) {
                if (isset($record[$value])) {
                    $flag = 1;
                    break;
                }
            }
        }

        if ($flag && (list($id, $record) = each($table))) {
            return $record[$value];
        } else {
            return "";
        }
        
    } else {
        return "";
    }
}

///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//                          Функция сортировки таблицы                       //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

function sort_table($table, $field, $type="alphabet", $direction="asc") {

    // Формируем временный массив для сортировки списка
    reset($table);
    while (list($key, $record) = each($table)) {

        if ($type == "digit") {
            $temp_array[$key] = (int) $record[$field];
        } else {
            $temp_array[$key] = $record[$field];
        }
    }

    // Сортируем временный массив
    if ($direction == "asc") {
        asort($temp_array);
    } else {
        arsort($temp_array);
    }

    // Перестраиваем основной массив по результатам сортировки
    $i = 1;
    reset($temp_array);
    while (list($key, $value) = each($temp_array)) {
        $table[$key]["order_id"] = $i;
        $result_records[] = $table[$key];
        $i++;
    }

    return $result_records;
}

?>