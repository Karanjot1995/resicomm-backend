<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* Exception class. */
require __DIR__ . '/PHPMailer/src/Exception.php';

/* The main PHPMailer class. */
require __DIR__ . '/PHPMailer/src/PHPMailer.php';

/* SMTP class, needed if you want to use SMTP. */
require __DIR__ . '/PHPMailer/src/SMTP.php';


include 'DbConnect.php';
$objDb = new DbConnect;
$conn = $objDb->connect();
$base_url = 'http://localhost:3000/verify';

$method = $_SERVER['REQUEST_METHOD'];
switch ($method) {
    case "GET":
        $sql = "SELECT * FROM users";
        $path = explode('/', $_SERVER['REQUEST_URI']);
        // if(isset($path[2]) && is_numeric($path[3])){
        // }

        $data = (object) array();
        if (isset($path[3]) && $path[3] == "employees") {
            $sql = "SELECT * from employees, users where employees.user_id=users.id";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $managers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $all_managers = (object) array(
                'building' => array(),
                'pool' => array(),
                'security' => array(),
                'garden' => array()
            );
            foreach ($managers as $m) {
                $dept = $m['department'];
                array_push($all_managers->$dept, $m);
            }
            $data = $all_managers;
        } else if (isset($path[3]) && $path[3] == "users") {
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $data = $users;
        } else if (isset($path[3]) && $path[3] == "user" && isset($path[4]) && is_numeric($path[4])) {
            $sql .= " WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $path[4]);
            $stmt->execute();
            $users = $stmt->fetch(PDO::FETCH_ASSOC);
            $data = $users;
        } else if (isset($_GET['email']) && !empty($_GET['email']) && isset($_GET['hash']) && !empty($_GET['hash'])) {

            // Retrieve email and hash parameters from URL
            $email = $_GET['email'];
            $hash = $_GET['hash'];


            // Check if email and hash combination exist in database
            $sql = "SELECT * FROM users WHERE email='$email' AND hash='$hash'";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $users = $stmt->fetch(PDO::FETCH_ASSOC);
            $data = $users;

            if ($stmt->rowCount() == 1) {
                $userVerified = $data['verified'];
                if ($userVerified == 0) {
                    $sql = "UPDATE users SET verified='1' WHERE email='$email'";
                    $stmt = $conn->prepare($sql);
                    if ($stmt->execute() == true) {
                        http_response_code(200);
                        $response = ['status' => 200, 'message' => 'Your email has been verified!'];
                    } else {
                        http_response_code(400);
                        $response = ['status' => 400, 'message' => 'Unknown Server Error!'];
                    }
                } else {
                    http_response_code(404);
                    $response = ['status' => 404, 'message' => 'Your email is already verified!'];
                }

            } else {
                http_response_code(400);
                $response = ['status' => 400, 'message' => 'Unknown Server Error!'];
            }

            echo json_encode($response);
        } else {
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $data = $users;
        }
        // $api['users'] = $users

        // echo json_encode($data);
        break;

    case "POST":
        $user = json_decode(file_get_contents('php://input'));
        $path = explode('/', $_SERVER['REQUEST_URI']);
        // if(isset($path[2]) && is_numeric($path[3])){
        // }
        $data = (object) array();
        if (isset($path[3]) && $path[3] == "users" && isset($path[4]) && $path[4] == "login") {
            $email = $user->email;
            $sql = "SELECT * from users where email=" + $email + ";";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $user = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $data = $user;
        } else if (($path[3]) && $path[3] == "register") {
            // $email = $user->email;
            // $sql = "SELECT * from users where email=" + $email + ";";
            // $stmt = $conn->prepare($sql);
            // $stmt->execute();
            // $user = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // $data = $user;



            // Retrieve user data from request
            $json_data = file_get_contents('php://input');
            $_POST = json_decode($json_data, true);
            $fname = $_POST["first_name"];
            $lname = $_POST["last_name"];
            $email = $_POST["email"];
            $password = $_POST["password"];
            $phone = $_POST["phone"];
            $dob = $_POST["dob"];
            $userType = $_POST["isVisitor"] ? "visitor" : "user";
            $hash = md5(rand(0, 1000));

            // Prepare and bind SQL statement
            $stmt = $conn->prepare("INSERT INTO users (type, fname, lname, phone, email, password, dob, hash) VALUES (:uType, :first_name, :last_name, :phone, :email, :password, :dob, :hash)");
            $stmt->bindParam(":uType", $userType);
            $stmt->bindParam(":first_name", $fname);
            $stmt->bindParam(":last_name", $lname);
            $stmt->bindParam(":phone", $phone);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":password", $password);
            $stmt->bindParam(":dob", $dob);
            $stmt->bindParam(":hash", $hash);

            try {
                // Execute insert statement here
                $result = $stmt->execute();
                // Execute statement
                if ($result === true) {

                    $to = 'jatinrey@gmail.com';
                    $subject = 'Signup | Verification';
                    $message = ' 

                    Thank you for signing up on ResiComm! 
                    Your account has been created, you can login with the following credentials after you have activated your account by pressing the url below. 

                    ------------------------ 
                    Email: ' . $email . ' 
                    Password: ' . $password . ' 
                    ------------------------ 

                    Please click this link to activate your account: 
                    ' . $base_url . '?email=' . $email . '&hash=' . $hash . ' 

                    ';
                    $from = 'resicomm@jxs2011.uta.cloud';

                    // SMTP server details
                    $smtp_host = 'mail.jxs2011.uta.cloud';
                    $smtp_port = 465;
                    $smtp_username = 'resicomm@jxs2011.uta.cloud';
                    $smtp_password = 'Resicomm@123';

                    // Create a new PHPMailer instance
                    $mail = new PHPMailer();

                    // Enable SMTP debugging
                    $mail->SMTPDebug = 0;

                    // Set mailer to use SMTP
                    $mail->isSMTP();

                    // Specify SMTP server
                    $mail->Host = $smtp_host;

                    // Enable SMTP authentication
                    $mail->SMTPAuth = true;

                    // Set SMTP username and password
                    $mail->Username = $smtp_username;
                    $mail->Password = $smtp_password;

                    // Enable TLS encryption
                    $mail->SMTPSecure = 'ssl';

                    // Set SMTP port
                    $mail->Port = $smtp_port;

                    // Set email details
                    $mail->setFrom($from);
                    $mail->addAddress($to);
                    $mail->Subject = $subject;
                    $mail->Body = $message;

                    // Send the email
                    if (!$mail->send()) {
                        http_response_code(400);
                        $response = ['status' => 'Error', 'message' => 'Server Error.'];
                    } else {
                        http_response_code(200);
                        $response = ['status' => 'Success', 'message' => 'User registration successful! Please verify your email to continue.'];
                    }

                }
            } catch (PDOException $e) {
                if ($e->errorInfo[1] == 1062) {
                    // Handle duplicate entry error here
                    // You can access the error code using $e->errorInfo[1]

                    http_response_code(400);
                    $response = ['status' => 'Error', 'message' => 'Account with this email already exists!'];
                } else {
                    http_response_code(400);
                    $response = ['status' => 'Error', 'message' => 'Failed to create record.'];
                }
            }

            // Execute statement
            // if ($stmt->execute() === TRUE) {
            //     http_response_code(200);
            //     $response = ['status' => 'Success', 'message' => 'Record created successfully.'];
            // } else {
            //     echo "bcccc";
            //     $error_code = $stmt->errorCode();
            //     echo "error is " . $error_code;
            //     http_response_code(400);
            //     $response = ['status' => 'Error', 'message' => 'Failed to create record.'];
            // }

            // Close statement and connection
            // $stmt->close();
            // $conn->close();
        }
        // $sql = "INSERT INTO users(id, name, email, mobile, created_at) VALUES(null, :name, :email, :mobile, :created_at)";
        // $stmt = $conn->prepare($sql);
        // $created_at = date('Y-m-d');
        // $stmt->bindParam(':name', $user->name);
        // $stmt->bindParam(':email', $user->email);
        // $stmt->bindParam(':mobile', $user->mobile);
        // $stmt->bindParam(':created_at', $created_at);

        // if($stmt->execute()) {
        //     $response = ['status' => 1, 'message' => 'Record created successfully.'];
        // } else {
        //     $response = ['status' => 0, 'message' => 'Failed to create record.'];
        // }
        // $response = ['status' => 200, 'data' => $user];
        echo json_encode($response);
        break;

    case "PUT":
        $user = json_decode(file_get_contents('php://input'));
        $sql = "UPDATE users SET name= :name, email =:email, mobile =:mobile, updated_at =:updated_at WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $updated_at = date('Y-m-d');
        $stmt->bindParam(':id', $user->id);
        $stmt->bindParam(':name', $user->name);
        $stmt->bindParam(':email', $user->email);
        $stmt->bindParam(':mobile', $user->mobile);
        $stmt->bindParam(':updated_at', $updated_at);

        if ($stmt->execute()) {
            $response = ['status' => 1, 'message' => 'Record updated successfully.'];
        } else {
            $response = ['status' => 0, 'message' => 'Failed to update record.'];
        }
        echo json_encode($response);
        break;

    case "DELETE":
        $sql = "DELETE FROM users WHERE id = :id";
        $path = explode('/', $_SERVER['REQUEST_URI']);

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $path[3]);

        if ($stmt->execute()) {
            $response = ['status' => 1, 'message' => 'Record deleted successfully.'];
        } else {
            $response = ['status' => 0, 'message' => 'Failed to delete record.'];
        }
        echo json_encode($response);
        break;
}
// require __DIR__ . "/inc/bootstrap.php";
// $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// // echo($uri);
// $uri = explode( '/',$_SERVER['REQUEST_URI'] );
// // echo($uri[3]);
// if ((isset($uri[3]) && $uri[3] != 'user') || !isset($uri[4])) {
//     header("HTTP/1.1 404 Not Found");
//     exit();
// }
// require PROJECT_ROOT_PATH . "/Controller/Api/UserController.php";
// $objFeedController = new UserController();
// $strMethodName = $uri[4] . 'Action';
// $objFeedController->{$strMethodName}();
?>