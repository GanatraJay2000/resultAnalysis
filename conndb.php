<?php
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "resultAnalysis";
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}
$create_files_table = "CREATE TABLE IF NOT EXISTS files (
id INT(6) PRIMARY KEY AUTO_INCREMENT,
filename TEXT NOT NULL,
table_number varchar(10) NOT NULL,
subjects json NOT NULL)";
$conn->query($create_files_table);


$create_users_table = "CREATE TABLE IF NOT EXISTS users(
	id INT(10) PRIMARY KEY AUTO_INCREMENT,
	username VARCHAR(255),
	password VARCHAR(255)
);";
$creating_users_table = $conn->query($create_users_table);


// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }
// $create_files_table = "CREATE TABLE IF NOT EXISTS files (
// id INT(6) PRIMARY KEY AUTO_INCREMENT,
// filename TEXT NOT NULL,
// students_table_number NUMERIC NOT NULL,
// subjects json NOT NULL);";
// $conn->query($create_files_table);
