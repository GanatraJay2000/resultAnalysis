<?php
ini_set('display_errors', 0);
function get_data()
{
    require 'conndb.php';
    if (isset($_FILES['myfile']['name'])) {
        $filename = $_FILES['myfile']['name'];
        $file_info = explode(".", $filename);
        $format = $_POST['format'];
        $excel_format = $_POST['excel_format'];
        $_SESSION['filename'] = $filename;

        $select = "SELECT filename FROM files WHERE filename='$filename'";
        $selecting = $conn->query($select);
        if ($selecting->num_rows < 1) {
            if ($format == 'pdf') {
                if (end($file_info) == 'pdf') {
                    $data = get_pdf_data();
                    $students = $data[0];
                    $subjects = $data[1];
                    $table_number = insert_into_database($filename, $students, $subjects);
                } else {
                    customError();
                }
            } elseif ($format == 'excel') {
                if (end($file_info) == 'xls' or end($file_info) == 'xlsx') {
                    if ($excel_format == '2019') {
                        $data = get_excel_data_2019();
                    } elseif ($excel_format == '2018') {
                        $data = get_excel_data_2018();
                    }
                    $students = $data[0];
                    $subjects = $data[1];
                    $table_number = insert_into_database($filename, $students, $subjects);
                } else {
                    customError();
                }
            }
            array_push($data, $table_number);
        } else {
            $data = extract_from_database($filename);
        }
    } elseif (isset($_POST['filename'])) {
        $filename = $_POST['filename'];
        $_SESSION['filename'] = $filename;
        $data = extract_from_database($filename);
    } else {
        $filename = $_SESSION['filename'];
        $data = extract_from_database($filename);
    }
    $error = '';
    $error = error_get_last();
    if ($error != '') {
        if ($error['type'] == 8) {
            customError();
        }
    }
    return $data;
}


function get_pdf_data()
{
    $text = extract_pdf_data();
    $dept = "IT";
    $first = $_POST['start'];
    $no_of_students = $_POST['no_of_students'] - 1;
    $subjects = get_subjects($dept, $text);
    $students = get_students($dept, $text, $first, $no_of_students);
    $data = array($students, $subjects);
    return $data;
}

function extract_pdf_data()
{
    include 'vendor/autoload.php';
    $file = $_FILES['myfile']['tmp_name'];

    $parser = new \Smalot\PdfParser\Parser();
    $pdf    = $parser->parseFile($file);

    $text = $pdf->getText();
    return $text;
}



function get_excel_data_2019()
{
    require 'student.php';
    $inputFile = $_FILES['myfile']['tmp_name'];
    $subjectsIndex = 1;
    $firstStudentIndex = 6;
    $numberOfStudents = 73;
    --$firstStudentIndex;

    $text = extract_excel_data($inputFile);
    $subjects = $text[$subjectsIndex];
    $students = [];
    for ($i = $firstStudentIndex; $i < $firstStudentIndex + $numberOfStudents; $i++) {
        if (!isset($text[$i])) {
            break;
        }
        $identity = [$text[$i][2]];
        $result = [$text[$i][6], $text[$i][90], $text[$i][5],  $text[$i][88], $text[$i][89]];
        $evaluation = [];
        for ($j = 7; $j <= 87; $j++) {
            array_push($evaluation, $text[$i][$j]);
        }
        $evaluation = array_chunk($evaluation, 9);
        $name = explode(" ", $text[$i][4]);
        $name = array_filter($name);
        $name = array_values($name);
        array_push($identity, ...$name);
        array_push($identity, $text[$i][0]);
        $details = [$identity, $result, $evaluation];
        $student = new Student($details);
        array_push($students, $student);
    }
    $data = array($students, $subjects);
    return $data;
}
function get_excel_data_2018()
{
    require 'student.php';
    $inputFile = $_FILES['myfile']['tmp_name'];
    $number_of_students = 90;
    $height_of_block = 5;
    $start = 8;
    --$start;
    $length = ($number_of_students * 5);

    $students = [];
    $data = extract_excel_data($inputFile);
    array_pop($data);
    $subjects = $data[5];
    $output = array_slice($data, $start, $length);
    foreach ($output as $key => $out) {
        array_shift($output[$key]);
        $output[$key] = array_values($output[$key]);
    }
    function array_move(&$a, $oldpos, $newpos)
    {
        if ($oldpos == $newpos) {
            return;
        }
        array_splice($a, max($newpos, 0), 0, array_splice($a, max($oldpos, 0), 1));
    }
    for ($i = 0; $i < count($output); $i = $i + 5) {
        $identity = array_shift($output[$i]);
        $identity = str_replace("/", "", $identity);
        $identity = str_replace("\n", " ", $identity);
        $identity = explode(" ", $identity);
        array_move($identity, count($identity) - 1, 0);
        $identity = array_filter($identity);
        $result = array_pop($output[$i]);
        array_splice($output[$i], 0, 1);
        $result = str_replace("\n", " ", $result);
        $result = explode(" ", $result);
        $result = array_filter($result);

        array_move($result, 1, 0);
        array_move($result, 4, 1);
        $j = 0;
        $k = 0;
        $evaluation = [];
        for ($l = 0; $l < (count($output[$i]) + count($output[$i + 1]) - 1); $l = $l + 2) {
            $evaluation[$l] = $output[$i][$j];
            $evaluation[$l + 1] = $output[$i + 1][$k];
            $j++;
            $k++;
        }
        $evaluation = array_chunk($evaluation, 6);
        foreach ($evaluation as $key => $eval) {
            array_push($evaluation[$key], $output[$i + 2][$key]);
            array_push($evaluation[$key], $output[$i + 3][$key]);
            array_push($evaluation[$key], $output[$i + 4][$key]);
        }
        foreach ($evaluation as $key => $eval) {
            array_move($evaluation[$key], 7, 5);
        }
        $details = [$identity, $result, $evaluation];
        $student = new Student($details);
        array_push($students, $student);
    }
    $data = array($students, $subjects);
    return $data;
}





function extract_excel_data($inputFile)
{
    require 'vendor/autoload.php';
    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
    $reader->setReadDataOnly(TRUE);
    $spreadsheet = $reader->load($inputFile);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = $worksheet->getHighestRow(); // e.g. 10
    $highestColumn = $worksheet->getHighestColumn();
    $lastcell = $highestColumn . $highestRow;
    $dataArray = $spreadsheet->getActiveSheet()->rangeToArray('A1:' . $lastcell, NULL, TRUE, TRUE, TRUE);

    $dataArray = array_filter($dataArray);
    $dataArray = array_values($dataArray);
    foreach ($dataArray as $key => $dataarr) {
        $dataArray[$key] = array_filter($dataArray[$key]);
        $dataArray[$key] = array_values($dataArray[$key]);
    }
    return $dataArray;
}






function get_students($dept, $text, $first, $no_of_students)
{
    require 'student.php';
    $array = $_POST['notarray'];
    $array =  str_replace(" ,", ",", $array);
    $array =  str_replace(", ", ",", $array);
    $array = explode(",", $array);
    $students = [];
    for ($i = $first; $i <= $first + $no_of_students; $i++) {
        for ($j = 0; $j < count($array); $j++) {
            if ($i == $array[$j])  continue 2;
        }
        $start = strval($i);
        $information = strstr($text, $start);
        $value = strstr($information, $_POST['stop'], true);
        if ($value == '') {
            break;
        }
        // Formatting value
        $value = str_replace(')', ') ', $value); //so that program does not think it is one elem with
        $value = str_replace('(', ' (', $value); //the prev or next elem due to mistyping / error

        $value = str_replace(')', '', $value);
        $value = str_replace('(', '', $value);
        $value = str_replace('|', '', $value);
        $value = str_replace('*', '', $value);
        $value = str_replace('/', '', $value);
        $value = trim(preg_replace('/\t+/', '', $value));
        $value = explode(" ", $value);
        $value = array_map('trim', $value);
        $value = array_filter($value);
        $value = array_values($value);


        $identity = [$value[0], $value[1], $value[2], $value[3], $value[4]];
        $result = [$value[count($value) - 99 + 23], $value[count($value) - 1], $value[count($value) - 2], $value[count($value) - 3]];
        $value = array_slice($value, -94, -3);
        unset($value[18]);
        $value = array_values($value);
        $evaluation = [];
        $j = 0;
        $k = 0;
        foreach ($value as $key => $valueq) {
            $mod = $key % 9;
            $evaluation[$j][$k] = $valueq;
            if ($mod == 8) {
                $j++;
                $k = 0;
            }
            $k++;
        }
        $evaluation = array_values($evaluation);
        foreach ($evaluation as $key => $score) {
            $evaluation[$key] = array_values($evaluation[$key]);
        }

        $details = [$identity, $result, $evaluation];
        $student = new Student($details);
        array_push($students, $student);
    }
    return $students;
}
function get_subjects($dept, $text)
{
    // print_r($students[10]);

    $start = "1." . $dept;
    $sub_text = strstr($text, $start);
    $subjects = strstr($sub_text, $_POST['stop'], true);
    $subjects = trim(preg_replace('/\t+/', '', $subjects));
    $pattern = "/\d\D/";
    $subjects = preg_split($pattern, $subjects);
    $subjects = array_map('trim', $subjects);
    $subjects = array_filter($subjects);
    foreach ($subjects as $key => $object) {
        $res = preg_match("/\w\w\w\d\d/", $object);
        if ($res) {
            unset($subjects[$key]);
        }
    }
    $subjects = array_values($subjects);
    return $subjects;
}























function  insert_into_database($filename, $students, $subjects)
{
    require 'conndb.php';
    $select = "SELECT * FROM files ORDER BY id DESC LIMIT 1;";
    $selecting = $conn->query($select);
    $table_number = 0;
    if ($selecting->num_rows == 0) {
        $table_number = 0;
    } else {
        $row = $selecting->fetch_assoc();
        $table_number = $row['table_number'];
        ++$table_number;
    }

    $subjects = json_encode($subjects);
    $create_student_table = "CREATE TABLE IF NOT EXISTS student_table_{$table_number}(
        id INT(10) PRIMARY KEY AUTO_INCREMENT,	
        identity json not null,
        result json not null,
        evaluation json not null
    );";
    if ($conn->query($create_student_table) == TRUE) {
        foreach ($students as $key => $student) {
            $identity = json_encode($student->identity);
            $result = json_encode($student->result);
            $evaluation = json_encode($student->evaluation);
            $insert_data = "INSERT INTO student_table_{$table_number}(identity,result, evaluation) values('$identity', '$result', '$evaluation');";
            if ($conn->query($insert_data) != TRUE) break;
        }
        if ($conn->query($insert_data) == TRUE) {
            $insert = "INSERT INTO files (filename, table_number, subjects) VALUES('$filename', '$table_number', '$subjects')";
            if ($conn->query($insert) != TRUE) {
                $conn->query("DROP TABLE student_table_{$table_number}");
                customError();
            }
        } else {
            customError();
        }
    } else {
        customError();
    }
    return $table_number;
}
function extract_from_database($filename)
{
    require 'student.php';
    require 'conndb.php';
    $select = "SELECT * FROM files WHERE filename='$filename';";
    $selecting = $conn->query($select);
    if ($selecting->num_rows == 1) {
        $row = $selecting->fetch_assoc();
        $table_number = $row['table_number'];
        $subjects = json_decode($row['subjects']);
        $students = [];
        $identity = [];
        $result = [];
        $evaluation = [];
        $select_data = "SELECT * FROM student_table_{$table_number}";
        $selecting_data = $conn->query($select_data);
        if ($selecting_data->num_rows > 0) {
            while ($row = $selecting_data->fetch_assoc()) {
                $identity[] = json_decode($row['identity']);
                $result[] = json_decode($row['result']);
                $evaluation[] = json_decode($row['evaluation']);
            }
            foreach ($identity as $key => $iden) {
                $details = [$iden, $result[$key], $evaluation[$key]];
                $student = new Student($details);
                array_push($students, $student);
            }
        } else {
            customError();
        }
    } else {
        customError();
    }
    $data = array($students, $subjects, $table_number);
    return $data;
}

function delete_from_database($filename)
{
    require 'conndb.php';
    $select = "SELECT * FROM files where filename='$filename';";
    $selecting = $conn->query($select);
    $row = $selecting->fetch_assoc();
    $table_number = $row['table_number'];

    $delete = "DELETE FROM files where filename='$filename';";
    $conn->query($delete);
    $delete_data = "DROP table student_table_{$table_number}";
    $conn->query($delete_data);
}


function print_this($data)
{

    foreach ($data as $value) {
        print_r($value);
        echo " <br><br>";
    }
}
function print_line($dataq)
{
    foreach ($dataq as $value) {
        print_r($value);
        echo ",  ";
    }
}



function customError()
{
    $filename = $_FILES['myfile']['name'];
    delete_from_database($filename);
    $_SESSION['error'] = ["danger", "A problem occured during the extraction of data! <br> Please check if file matches the format properly !!"];
    header('Location: dashboard.php');
}
