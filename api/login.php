<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/connection.php';

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Email and password are required."]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if user exists and password matches

    if ($user && password_verify($password, $user['password_hash'])) {

        //PW is correct, Log in the user in.
        //Don't store the PW hash in the session.
        $_SESSION['user_data'] = [
            'id' => $user['id'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];

        respond(true, "Login success!", ['redirect' => 'dashboard.php']);
    } else {    
        //PW is incorrect. User does not exist
        respond(false, "Invalid email or PW.", null, 401);
    }
} catch (Exception $e) {
    respond(false, "An error occurred. ", null, 500);
}


?>