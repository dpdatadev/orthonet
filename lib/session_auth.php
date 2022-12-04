<?php
//login.php
//manage login with PDO
//manage sessions
require_once("DB.php");
//PDO singleton - Postgres dialect
use Postgres as DB;
//open up the session
session_start();
//home page
const LOGIN_SUCCESS_ROUTE = "../index.php";
//default message
$message = "";
try { //user is already logged in - send em' home
    if (isset($_SESSION["username"])) {
        header("location: " . LOGIN_SUCCESS_ROUTE);
    } else { //user isn't logged in - proceed
        if (isset($_POST["login"])) {//process the form request
            if (empty($_POST["username"]) || empty($_POST["password"])) {//check fields for user info
                $message = "<label style='color:red'>All fields are required</label>";
            } else {//send request for credentials to database
                $sql = "SELECT * FROM admin.users WHERE username = :username AND password = :password";
                $stmt = DB::prepare($sql);
                //TODO - the form values need to be bound
                //TODO - password hashing?
                //$stmt->bindValue($_POST['username'], "username", PDO::PARAM_STR);
                //$stmt->bindValue($_POST['password'], "password", PDO::PARAM_STR);
                $stmt->execute(
                    array(
                        'username' => $_POST["username"],
                        'password' => $_POST["password"]
                    )
                );
                $count = $stmt->rowCount();
                if ($count > 0) {//got a valid database hit
                    $_SESSION["username"] = $_POST["username"];//store the username for display
                    header("location: " . LOGIN_SUCCESS_ROUTE);//send home
                } else {//display if there is an error with the database request
                    $message = "<label style='color:red'>LOGIN FAILURE -- CONTACT ADMINISTRATOR</label>";
                }
            }
        } else { //display if there is an error with the form posting
            $message = "<label style='color:red'>LOGIN FAILURE -- CONTACT ADMINISTRATOR</label>";
        }
    }
} catch (PDOException $e) { //debug - show generic message but log the PDOException
    $message = $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css"/>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
</head>
<body>
<br/>
<div class="container" style="width:500px;">
    <?php
    if (isset($message)) {
        echo '<label class="text-danger">' . $message . '</label>';
    }
    ?>
    <h3 align="">PHP Login Script using PDO</h3><br/>
    <form method="post">
        <label>Username</label>
        <input type="text" name="username" class="form-control"/>
        <br/>
        <label>Password</label>
        <input type="password" name="password" class="form-control"/>
        <br/>
        <input type="submit" name="login" class="btn btn-info" value="Login"/>
    </form>
</div>
<br/>
</body>
</html>