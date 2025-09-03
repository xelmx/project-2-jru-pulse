<?php

// --- SECURITY: Only admins can access this API ---
session_start();
if (!isset($_SESSION['user_data']) || $_SESSION['user_data']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Forbidden: You do not have permission to access this resource."]);
    exit;
}

header("Content-Type: application/json");

// Security Check: Only admins can access this API
if (!isset($_SESSION['user_data']) || $_SESSION['user_data']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Forbidden: You do not have permission to perform this action."]);
    exit;
}

require_once '../config/connection.php';

// --- Helper & Connection ---
function respond($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode(["success" => (bool)$success, "message" => $message, "data" => $data]);
    exit;
}
try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    respond(false, "Database connection failed: " . $e->getMessage(), null, 500);
}

// --- Main API Logic ---
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    // --- READ (Fetch Users) ---
    case 'GET':
        $searchTerm = $_GET['search'] ?? '';
        // Join with the offices table to get the office name directly
        $query = "SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.is_active, u.office_id, o.name as office_name 
                  FROM users u
                  LEFT JOIN offices o ON u.office_id = o.id
                  WHERE u.first_name LIKE :searchTerm OR u.last_name LIKE :searchTerm OR u.email LIKE :searchTerm 
                  ORDER BY u.last_name, u.first_name";
        try {
            $stmt = $db->prepare($query);
            $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
            $stmt->execute();
            respond(true, "Users retrieved successfully.", $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) { respond(false, "Error: " . $e->getMessage(), null, 500); }
        break;

    // --- CREATE (Add New User) ---
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email']) || empty($data['role'])) {
            respond(false, "All fields are required.", null, 400);
        }
        if ($data['role'] === 'office_head' && empty($data['office_id'])) {
            respond(false, "An office must be assigned for the 'Office Head' role.", null, 400);
        }

        // Since login is via Google SSO, we create a secure, random placeholder for the password hash.
        $placeholder_hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $office_id_to_insert = ($data['role'] === 'office_head') ? $data['office_id'] : null;

        $query = "INSERT INTO users (first_name, last_name, email, role, office_id, password_hash) 
                  VALUES (:first_name, :last_name, :email, :role, :office_id, :password_hash)";
        try {
            $stmt = $db->prepare($query);
            $stmt->bindValue(':first_name', $data['first_name']);
            $stmt->bindValue(':last_name', $data['last_name']);
            $stmt->bindValue(':email', $data['email']);
            $stmt->bindValue(':role', $data['role']);
            $stmt->bindValue(':office_id', $office_id_to_insert, $office_id_to_insert ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':password_hash', $placeholder_hash);
            $stmt->execute();
            respond(true, "User created successfully.");
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { respond(false, "A user with this email already exists.", null, 409); }
            respond(false, "Error: " . $e->getMessage(), null, 500);
        }
        break;

    // --- UPDATE (Edit User or Toggle Status) ---
    case 'PUT':
        $id = $_GET['id'] ?? null;
        if (!$id) { respond(false, "User ID is required.", null, 400); }
        
        $data = json_decode(file_get_contents("php://input"), true);
        $action = $data['action'] ?? 'update_details';

        try {
            if ($action === 'toggle_status') {
                $query = "UPDATE users SET is_active = !is_active WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                $stmt->execute();
                respond(true, "User status updated successfully.");

            } elseif ($action === 'update_details') {
                if (empty($data['first_name']) || empty($data['last_name']) || empty($data['role'])) { respond(false, "First name, last name, and role are required.", null, 400); }
                if ($data['role'] === 'office_head' && empty($data['office_id'])) { respond(false, "An office must be assigned for the 'Office Head' role.", null, 400); }
                
                $office_id_to_update = ($data['role'] === 'office_head') ? $data['office_id'] : null;

                $query = "UPDATE users SET first_name = :first_name, last_name = :last_name, role = :role, office_id = :office_id WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                $stmt->bindValue(':first_name', $data['first_name']);
                $stmt->bindValue(':last_name', $data['last_name']);
                $stmt->bindValue(':role', $data['role']);
                $stmt->bindValue(':office_id', $office_id_to_update, $office_id_to_update ? PDO::PARAM_INT : PDO::PARAM_NULL);
                $stmt->execute();
                respond(true, "User details updated successfully.");
            } else {
                respond(false, "Invalid action specified.", null, 400);
            }
        } catch (PDOException $e) { respond(false, "Database Error: " . $e->getMessage(), null, 500); }
        break;

    default:
        respond(false, "Method not allowed.", null, 405);
        break;
}
?>