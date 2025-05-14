$db_host = "localhost";
$db_user = "app_user";
$db_pass = "secure_password" //use environmental variables
$db_name = "zedauto_db";


function connectionToDatabase() {
    global $db_host, $db_name, $db_user, $db_pass;


    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    if($conn->connect_error) {
        die("Connection failed: " .$conn->connect_error);
    }

    return $conn;
}