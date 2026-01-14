<?php 
$database_host = 'localhost';
$database_username = 'root';
$database_password = '';
$database_name = 'warung_maju';
$database_port = 3306;

$con = mysqli_connect($database_host, $database_username, $database_password, $database_name, $database_port);
 
if (!$con){
    die("Koneksi gagal: " . mysqli_connect_error());
}else{
    echo ""; 
}
?>