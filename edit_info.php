<?php
require 'conndb.php';
$table_number = $_POST['table_number'];
$key = $_POST['key'] + 1;

$seat_no = $_POST['seat_no'];
$fname = $_POST['first_name'];
$father = $_POST['fathers_name'];
$lname = $_POST['last_name'];
$mother = $_POST['mothers_name'];
$identity = [$seat_no, $lname, $fname, $father, $mother];
if (isset($_POST['gr_no'])) {
    $gr_no = $_POST['gr_no'];
    $identity = [$seat_no, $lname, $fname, $father, $mother, $gr_no];
}
$identity = json_encode($identity);
$update = "UPDATE student_table_{$table_number} SET identity='$identity' WHERE id='$key'";
$updating = $conn->query($update);
header('Location: logic.php');
