<?php
require 'conndb.php';
$filename = $_POST['filename'];

require 'conndb.php';
$select = "SELECT * FROM files where filename='$filename';";
$selecting = $conn->query($select);
$row = $selecting->fetch_assoc();
$table_number = $row['table_number'];

$delete = "DELETE FROM files where filename='$filename';";
$conn->query($delete);
$delete_data = "DROP table student_table_{$table_number}";
$conn->query($delete_data);
header('Location: index.php');
