<?php

session_start();
header("Content-Type: application/json");

// Security Check: Only admins can access this API
if (!isset($_SESSION['user_data']) || $_SESSION['user_data']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Forbidden: You do not have permission to perform this action."]);
    exit;
}

require_once '../config/connection.php';

// --- Helper & Connection ---
// A standardized function to send back responses.
function respond($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        "success" => (bool)$success,
        "message" => $message,
        "data" => $data
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    respond(false, "Database connection failed", null, 500);
}

// --- Main API Logic ---
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    // --- READ (Fetch Users) ---
    case 'GET':
        $searchTerm = $_GET['search'] ?? '';
        $query = "SELECT id, first_name, last_name, email, role, is_active FROM users WHERE first_name LIKE :searchTerm OR last_name LIKE :searchTerm OR email LIKE :searchTerm ORDER BY last_name, first_name";
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
        // We set a placeholder for password_hash. In a real system, you'd generate a random, secure password and email it to the user.
        // For this project, since login is handled by Google SSO, a non-null placeholder is sufficient.
        $placeholder_hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

        $query = "INSERT INTO users (first_name, last_name, email, role, password_hash) VALUES (:first_name, :last_name, :email, :role, :password_hash)";
        try {
            $stmt = $db->prepare($query);
            $stmt->bindValue(':first_name', $data['first_name']);
            $stmt->bindValue(':last_name', $data['last_name']);
            $stmt->bindValue(':email', $data['email']);
            $stmt->bindValue(':role', $data['role']);
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
                // This action flips the is_active flag (e.g., from 1 to 0 or 0 to 1)
                $query = "UPDATE users SET is_active = !is_active WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindValue(':id', $id);
                $stmt->execute();
                respond(true, "User status updated successfully.");

            } elseif ($action === 'update_details') {
                if (empty($data['first_name']) || empty($data['last_name']) || empty($data['role'])) {
                    respond(false, "First name, last name, and role are required.", null, 400);
                }
                // Note: We do not allow editing the email for security and simplicity.
                $query = "UPDATE users SET first_name = :first_name, last_name = :last_name, role = :role WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindValue(':id', $id);
                $stmt->bindValue(':first_name', $data['first_name']);
                $stmt->bindValue(':last_name', $data['last_name']);
                $stmt->bindValue(':role', $data['role']);
                $stmt->execute();
                respond(true, "User details updated successfully.");
            } else {
                respond(false, "Invalid action specified.", null, 400);
            }
        } catch (PDOException $e) { respond(false, "Error: " . $e->getMessage(), null, 500); }
        break;

    default:
        respond(false, "Method not allowed.", null, 405);
        break;
}
