<?php
use PHPMailer\PHPMailer\PHPMailer;

error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");
include 'mail.php';
include 'DbConnect.php';
// /* Exception class. */
// require __DIR__ . '/PHPMailer/src/Exception.php';

// /* The main PHPMailer class. */
// require __DIR__ . '/PHPMailer/src/PHPMailer.php';

// /* SMTP class, needed if you want to use SMTP. */
// require __DIR__ . '/PHPMailer/src/SMTP.php';


$objDb = new DbConnect;
$email = new Mail();

$conn = $objDb->connect();
$base_url = 'http://localhost:3000/';

$method = $_SERVER['REQUEST_METHOD'];
switch ($method) {
    case "GET":
        $sql = "SELECT * FROM users";
        $path = explode('/', $_SERVER['REQUEST_URI']);

        // $to = 'karan.nanda97@gmail.com';
        // $from = 'resicomm@jxs2011.uta.cloud';
        // $subject = 'Signup | Verification';
        // $h = '1595af6435015c77a7149e92a551338e';
        // $em = 'karan.nanda97@gmail.com';
        // $message = 'Please click this link to activate your account: '. $base_url . '?email=' . $em . '&hash=' . $h ;

        // // $message = 'Hey there!';

        // $customMessage = (object) array(
        //     'to' => $to,
        //     'from' => $from,
        //     'subject' => $subject,
        //     'message'=>  $message
        // );

        // $email->transactionalEmail($customMessage);

        // if(isset($path[2]) && is_numeric($path[3])){
        // }

        $data = (object) array();
        if (isset($path[3]) && $path[3] == "employees") {
            $sql = "SELECT * from users where type='employee'";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $all_employees = (object) array(
                'building' => array(),
                'pool' => array(),
                'security' => array(),
                'garden' => array()
            );
            $emps = array();
            foreach ($employees as $emp) {
                $dept = $emp['department'];
                if ($dept == 'building') {
                    array_push($all_employees->building, $emp);
                } else if ($dept == 'pool') {
                    array_push($all_employees->pool, $emp);
                } else if ($dept == 'garden') {
                    array_push($all_employees->garden, $emp);
                } else if ($dept == 'security') {
                    array_push($all_employees->security, $emp);
                }
                array_push($emps, $emp);
            }
            $data = $all_employees;
        } else if (isset($path[3]) && $path[3] == "users") {
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $data = $users;
        } else if (isset($path[3]) && $path[3] == "amenities") {
            $stmt = $conn->prepare("SELECT * from amenities");
            $stmt->execute();
            $amenities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $data = $amenities;
        } else if (isset($path[3]) && (strpos($path[3], 'vehicles') !== false) && isset($_GET['id']) && !empty($_GET['id'])) {
            $vehicle_id = $_GET['id'];
            $stmt = $conn->prepare("SELECT * from vehicles where id='$vehicle_id'");
            $stmt->execute();
            $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $vehicle = $vehicles[0];
            $data = ['status' => 200, 'vehicle_details' => $vehicle];
        } else if (isset($path[3]) && $path[3] == "vehicles") {
            $stmt = $conn->prepare("SELECT * from vehicles");
            $stmt->execute();
            $amenities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $data = $amenities;
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

        echo json_encode($data);
        break;

    case "POST":
        $data = json_decode(file_get_contents('php://input'));
        $path = explode('/', $_SERVER['REQUEST_URI']);
        // if(isset($path[2]) && is_numeric($path[3])){
        // }
        // $data = (object) array();
        if (isset($path[3]) && $path[3] == "employee" && isset($path[4]) && $path[4] == "add") {
            // $email = $data->email;
            // $password = $data->password;
            $json_data = file_get_contents('php://input');
            $_POST = json_decode($json_data, true);
            $fname = $_POST["fname"];
            $lname = $_POST["lname"];
            $email = $_POST["email"];
            $password = $_POST["password"];
            $department = $_POST["department"];
            $phone = $_POST["phone"];
            $type = $_POST["type"];

            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $response = [];
            if($stmt->rowCount() == 0){
                $stmt = $conn->prepare("INSERT INTO users (type, fname, lname, phone, email, password, department) VALUES (:type, :fname, :lname, :phone, :email, :password, :department)");
                $stmt->bindParam(":type", $type);
                $stmt->bindParam(":fname", $fname);
                $stmt->bindParam(":lname", $lname);
                $stmt->bindParam(":phone", $phone);
                $stmt->bindParam(":email", $email);
                $stmt->bindParam(":password", $password);
                $stmt->bindParam(":department", $department);
                $result = $stmt->execute();
                if ($stmt->rowCount() == 1) {
                    // TODO: User authenticated, set session and redirect to dashboard
                    http_response_code(200);
                    $response = ['status' => 200, 'message' => 'Successful!', 'result' => $result];
                } else {
                    // TODO: Authentication failed, show error message
                    http_response_code(401);
                    $response = ['status' => 401, 'message' => 'Error!'];
                }
            }else{
                http_response_code(200);
                $response = ['status' => 200, 'message' => 'Employee with same email already exists!'];

            }
            echo json_encode($response);



           
            // $fname = $data->fname;
            // $lname = $data->lname;
            // $phone = $data->phone;
            // $type = $data->type;

            // $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email AND password = :password");
            // $stmt->bindParam(':email', $email);
            // $stmt->bindParam(':password', $password);

            


        }else if (isset($path[3]) && $path[3] == "employee" && isset($path[4]) && $path[4] == "edit") {
            // $email = $data->email;
            // $password = $data->password;
            $json_data = file_get_contents('php://input');
            $_POST = json_decode($json_data, true);
            $fname = $_POST["fname"];
            $lname = $_POST["lname"];
            $email = $_POST["email"];
            $phone = $_POST["phone"];

            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $response = [];
            if($stmt->rowCount() > 0){
                $stmt = $conn->prepare("UPDATE users SET fname='$fname', lname='$lname', phone='$phone' WHERE email ='$email'");
                $result = $stmt->execute();
                if ($stmt->rowCount() == 1) {
                    // TODO: User authenticated, set session and redirect to dashboard
                    http_response_code(200);
                    $response = ['status' => 200, 'message' => 'Successful!', 'result' => $result];
                } else {
                    // TODO: Authentication failed, show error message
                    http_response_code(401);
                    $response = ['status' => 401, 'message' => 'Error!'];
                }
            }else{
                http_response_code(200);
                $response = ['status' => 401, 'message' => 'Employee doesnot exist!'];

            }
            echo json_encode($response);

        }else if (isset($path[3]) && $path[3] == "user" && isset($path[4]) && $path[4] == "login" && isset($data->email) && isset($data->password)) {
            $email = $data->email;
            $password = $data->password;

            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email AND password = :password");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($stmt->rowCount() == 1) {
                // TODO: User authenticated, set session and redirect to dashboard
                http_response_code(200);
                $response = ['status' => 200, 'message' => 'Successful!', 'user' => $user];
            } else {
                // TODO: Authentication failed, show error message
                http_response_code(401);
                $response = ['status' => 401, 'message' => 'Email or Password incorrect!'];
            }

            echo json_encode($response);

        }else if (($path[3]) && $path[3] == "register") {

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

                    $to = $email;
                    $from = 'resicomm@jxs2011.uta.cloud';
                    $subject = 'Signup | Verification';
                    $message = ' 

                    Thank you for signing up on ResiComm! 
                    Your account has been created, you can login with the following credentials after you have activated your account by pressing the url below. 

                    ------------------------ 
                    Email: ' . $email . ' 
                    Password: ' . $password . ' 
                    ------------------------ 

                    Please click this link to activate your account: 
                    ' . $base_url . 'verify?email=' . $email . '&hash=' . $hash . ' 

                    ';

                    $customMessage = (object) array(
                        'to' => $to,
                        'from' => $from,
                        'subject' => $subject,
                        'message' => $message
                    );

                    $email->transactionalEmail($customMessage);
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
        } else if (($path[3]) && $path[3] == "resend-verification") {

            $email = $data->email;

            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);

            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($stmt->rowCount() == 1) {
                $hash = md5(rand(0, 1000));
                $password = $user['password'];



                try {
                    $sql = "UPDATE users SET hash='$hash' WHERE email ='$email'";
                    $stmt = $conn->prepare($sql);

                    $stmt->execute();

                    $to = $email;
                    $subject = 'Signup | Verification';
                    $message = ' 

                    Thank you for signing up on ResiComm! 
                    Your account has been created, you can login with the following credentials after you have activated your account by pressing the url below. 

                    ------------------------ 
                    Email: ' . $email . ' 
                    Password: ' . $password . ' 
                    ------------------------ 

                    Please click this link to activate your account: 
                    ' . $base_url . 'verify?email=' . $email . '&hash=' . $hash . ' 

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
                        $response = ['status' => 'Success', 'message' => 'Email sent successfully! Please verify your email to continue.'];
                    }

                } catch (Exception $e) {
                    http_response_code(400);
                    $response = ['status' => 'Error', 'message' => 'Server Error!'];
                }

            } else {
                http_response_code(400);
                $response = ['status' => 400, 'message' => 'Unknown Server Error!'];
            }

            echo json_encode($response);
        } else if (($path[3]) && $path[3] == "forget-password") {

            $email = $data->email;

            $stmt = $conn->prepare("SELECT * FROM users WHERE email ='$email'");

            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($stmt->rowCount() == 1) {
                $hash = md5(rand(0, 1000));

                try {
                    $sql = "UPDATE users SET hash='$hash' WHERE email ='$email'";
                    $stmt = $conn->prepare($sql);

                    $stmt->execute();

                    $to = $email;
                    $name = $user['fname'];
                    $subject = 'Forget Password';
                    $message = ' 

                    Hi ' . $name . ',
                    You recently requested to reset the password for your ResiComm portal account. Click the link below to proceed.
                    ' . $base_url . 'reset-password?email=' . $email . '&hash=' . $hash . ' 

                    If you did not request a password reset, please ignore this email or reply to let us know. This password reset link is only valid for the next 30 minutes.

                    Thanks, 
                    The ResiComm team';


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
                        $response = ['status' => 'Success', 'message' => 'Email sent successfully!'];
                    }

                } catch (Exception $e) {
                    http_response_code(400);
                    $response = ['status' => 'Error', 'message' => 'Server Error!'];
                }

            } else {
                http_response_code(400);
                $response = ['status' => 400, 'message' => 'User with this email does not exist!'];
            }

            echo json_encode($response);
        } else if (($path[3]) && $path[3] == "reset-password") {

            $email = $data->email;
            $hash = $data->hash;
            $password = $data->password;

            // Check if email and hash combination exist in database
            $sql = "SELECT * FROM users WHERE email='$email' AND hash='$hash'";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $users = $stmt->fetch(PDO::FETCH_ASSOC);
            $data = $users;

            if ($stmt->rowCount() == 1) {
                $sql = "UPDATE users SET password ='$password', hash = NULL WHERE email='$email'";
                $stmt = $conn->prepare($sql);
                if ($stmt->execute() == true) {
                    http_response_code(200);
                    $response = ['status' => 200, 'message' => 'Password reset successful!'];
                } else {
                    http_response_code(400);
                    $response = ['status' => 400, 'message' => 'Password reset failed!'];
                }

            } else {
                http_response_code(400);
                $response = ['status' => 400, 'message' => 'Unknown Server Error! Request a new verification mail.'];
            }

            echo json_encode($response);
        } else if (isset($path[3]) && $path[3] == "add-vehicle") {
            $json_data = file_get_contents('php://input');
            $_POST = json_decode($json_data, true);
            $make = $_POST["make"];
            $model = $_POST["model"];
            $number_plate = $_POST['number_plate'];
            $color = $_POST['color'];
            $type = $_POST['type'];
            $user_id = $_POST['user_id'];
           
            $stmt = $conn->prepare("INSERT INTO vehicles (make, model, number_plate, color, type, user_id) VALUES ('$make', '$model', '$number_plate', '$color', '$type', '$user_id')");
            
            if ($stmt->execute() == true) {
                $response = ['status' => 200, 'message' => 'Vehicle added successfully!'];
            } else {
                $response = ['status' => 400, 'message' => 'Failed to add vehicle!'];
            }

            echo json_encode($response);
        } else {
            $response = ['status' => 400, 'message' => 'Server Error'];
            echo json_encode($response);
            // $sql = "DELETE FROM users WHERE id = :id";
            // $path = explode('/', $_SERVER['REQUEST_URI']);

            // $stmt = $conn->prepare($sql);
            // $stmt->bindParam(':id', $path[3]);

            // if ($stmt->execute()) {
            //     $response = ['status' => 1, 'message' => 'Record deleted successfully.'];
            // } else {
            //     $response = ['status' => 0, 'message' => 'Failed to delete record.'];
            // }

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
        break;

    case "PUT":
        $path = explode('/', $_SERVER['REQUEST_URI']);
        if (isset($path[3]) && (strpos($path[3], 'vehicles') !== false) && isset($_GET['id']) && !empty($_GET['id'])) {
            $input_data = file_get_contents("php://input");
            $data = json_decode($input_data, true);
            $vehicle_id = $_GET['id'];
            $make = $data['make'];
            $model = $data['model'];
            $number_plate = $data['number_plate'];
            $color = $data['color'];
            $type = $data['type'];

            $sql = "UPDATE vehicles SET make = '$make', model = '$model', number_plate = '$number_plate', color = '$color', type = '$type' WHERE id = '$vehicle_id'";
            $stmt = $conn->prepare($sql);
            if ($stmt->execute() == true) {
                $response = ['status' => 200, 'message' => 'Vehicle details updated successfully!'];
            } else {
                $response = ['status' => 400, 'message' => 'Failed to update vehicle details!'];
            }
        } else {
            $response = ['status' => 400, 'message' => 'Server Error'];

            // $user = json_decode(file_get_contents('php://input'));
            // $sql = "UPDATE users SET name= :name, email =:email, mobile =:mobile, updated_at =:updated_at WHERE id = :id";
            // $stmt = $conn->prepare($sql);
            // $updated_at = date('Y-m-d');
            // $stmt->bindParam(':id', $user->id);
            // $stmt->bindParam(':name', $user->name);
            // $stmt->bindParam(':email', $user->email);
            // $stmt->bindParam(':mobile', $user->mobile);
            // $stmt->bindParam(':updated_at', $updated_at);

            // if ($stmt->execute()) {
            //     $response = ['status' => 1, 'message' => 'Record updated successfully.'];
            // } else {
            //     $response = ['status' => 0, 'message' => 'Failed to update record.'];
            // }

        }
        echo json_encode($response);
        break;

    case "DELETE":
        $path = explode('/', $_SERVER['REQUEST_URI']);
        if (isset($path[3]) && (strpos($path[3], 'vehicles') !== false) && isset($_GET['id']) && !empty($_GET['id'])) {
            $vehicle_id = $_GET['id'];
            $sql = "DELETE FROM vehicles WHERE id = '$vehicle_id'";
            $stmt = $conn->prepare($sql);
            if ($stmt->execute() == true) {
                $response = ['status' => 200, 'message' => 'Vehicle deleted successfully!'];
            } else {
                $response = ['status' => 400, 'message' => 'Failed to delete vehicle!'];
            }
        } else {
            $response = ['status' => 400, 'message' => 'Server Error'];

            // $sql = "DELETE FROM users WHERE id = :id";
            // $path = explode('/', $_SERVER['REQUEST_URI']);

            // $stmt = $conn->prepare($sql);
            // $stmt->bindParam(':id', $path[3]);

            // if ($stmt->execute()) {
            //     $response = ['status' => 1, 'message' => 'Record deleted successfully.'];
            // } else {
            //     $response = ['status' => 0, 'message' => 'Failed to delete record.'];
            // }

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