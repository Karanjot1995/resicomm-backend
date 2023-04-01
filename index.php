<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");

include 'DbConnect.php';
$objDb = new DbConnect;
$conn = $objDb->connect();

$method = $_SERVER['REQUEST_METHOD'];
switch($method) {
    case "GET":
        $sql = "SELECT * FROM users";
        $path = explode('/', $_SERVER['REQUEST_URI']);
        // if(isset($path[2]) && is_numeric($path[3])){
        // }
        
        $data = (object) array();
        if(isset($path[3]) && $path[3]=="employees") {
            $sql = "SELECT * from employees, users where employees.user_id=users.id";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $managers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $all_managers = (object) array(
                'building'=> array(),
                'pool'=> array(),
                'security'=> array(),
                'garden' => array()
            );
            foreach($managers as $m){
                $dept = $m['department'];
                array_push($all_managers->$dept, $m);
            }
            $data = $all_managers;
        }else if(isset($path[3]) && $path[3]=="users") {
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $data = $users;
        }
        else if(isset($path[3]) && $path[3]=="user" && isset($path[4]) && is_numeric($path[4])) {
            $sql .= " WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $path[4]);
            $stmt->execute();
            $users = $stmt->fetch(PDO::FETCH_ASSOC);
            $data = $users;
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
        $user = json_decode( file_get_contents('php://input') );
        $path = explode('/', $_SERVER['REQUEST_URI']);
        // if(isset($path[2]) && is_numeric($path[3])){
        // }
        $data = (object) array();
        if(isset($path[3]) && $path[3]=="users" && isset($path[4]) && $path[4]=="login") {
            $email = $user->email;
            $sql = "SELECT * from users where email="+$email+";";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $user = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $data = $user;
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
        $response = ['status' => 200, 'data' => $user];
        echo json_encode($user->email);
        break;

    case "PUT":
        $user = json_decode( file_get_contents('php://input') );
        $sql = "UPDATE users SET name= :name, email =:email, mobile =:mobile, updated_at =:updated_at WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $updated_at = date('Y-m-d');
        $stmt->bindParam(':id', $user->id);
        $stmt->bindParam(':name', $user->name);
        $stmt->bindParam(':email', $user->email);
        $stmt->bindParam(':mobile', $user->mobile);
        $stmt->bindParam(':updated_at', $updated_at);

        if($stmt->execute()) {
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

        if($stmt->execute()) {
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