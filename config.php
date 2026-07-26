<?php



define('DB_SERVER', 'localhost');    
define('DB_USERNAME', 'root');        
define('DB_PASSWORD', '');            
define('DB_NAME', 'campushub_db');    


$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);


if (!$conn) {
    
    die("Database Connection Failed: " . mysqli_connect_error());
}


?>