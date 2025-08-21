<?php
// --- api/guests.php ---

header("Content-Type: application/json");
require_once '../config/connection.php';

// Standard respond helper function
function respond($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode(["success" => (bool)$success, "message" => $message, "data" => $data]);
    exit;
}

// Standard database connection
try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    respond(false, "Database connection failed: " . $e->getMessage(), null, 500);
}

// Main switch to handle different request methods (GET, POST, PUT, DELETE)
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    // --- READ (Fetch Guests) ---
    case 'GET':
        $searchTerm = $_GET['search'] ?? '';
        $query = "SELECT * FROM guests WHERE first_name LIKE :searchTerm OR last_name LIKE :searchTerm OR email LIKE :searchTerm OR role LIKE :searchTerm ORDER BY last_name, first_name";
        
        try {
            $stmt = $db->prepare($query);
            $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
            $stmt->execute();
            $guests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            respond(true, "Guests retrieved successfully.", $guests);
        } catch (PDOException $e) {
            respond(false, "Error fetching guests: " . $e->getMessage(), null, 500);
        }
        break;

    // --- CREATE (Add New Guest) ---
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email']) || empty($data['role'])) {
            respond(false, "First name, last name, email, and role are required.", null, 400);
        }

        $query = "INSERT INTO guests (first_name, last_name, email, role) VALUES (:first_name, :last_name, :email, :role)";
        
        try {
            $stmt = $db->prepare($query);
            $stmt->bindValue(':first_name', $data['first_name']);
            $stmt->bindValue(':last_name', $data['last_name']);
            $stmt->bindValue(':email', $data['email']);
            $stmt->bindValue(':role', $data['role']);
            $stmt->execute();
            respond(true, "Guest added successfully.");
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Catch duplicate entry for email
                respond(false, "A guest with this email already exists.", null, 409);
            }
            respond(false, "Error adding guest: " . $e->getMessage(), null, 500);
        }
        break;

    // --- UPDATE (Edit Existing Guest) ---
    case 'PUT':
        $id = $_GET['id'] ?? null;
        if (!$id) {
            respond(false, "Guest ID is required for update.", null, 400);
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        $query = "UPDATE guests SET first_name = :first_name, last_name = :last_name, email = :email, role = :role WHERE id = :id";

        try {
            $stmt = $db->prepare($query);
            $stmt->bindValue(':id', $id);
            $stmt->bindValue(':first_name', $data['first_name']);
            $stmt->bindValue(':last_name', $data['last_name']);
            $stmt->bindValue(':email', $data['email']);
            $stmt->bindValue(':role', $data['role']);
            $stmt->execute();
            respond(true, "Guest updated successfully.");
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                respond(false, "A guest with this email already exists.", null, 409);
            }
            respond(false, "Error updating guest: " . $e->getMessage(), null, 500);
        }
        break;

    // --- DELETE ---
    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) {
            respond(false, "Guest ID is required for deletion.", null, 400);
        }

        // NOTE: This is a permanent delete. Ensure the front-end has a strong confirmation modal.
        $query = "DELETE FROM guests WHERE id = :id";
        
        try {
            $stmt = $db->prepare($query);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
            respond(true, "Guest deleted successfully.");
        } catch (PDOException $e) {
            // This can happen if the guest is linked in the 'respondents' table.
            if ($e->getCode() == 23000) {
                 respond(false, "Cannot delete this guest because they have existing survey responses. Please archive them instead.", null, 409);
            }
            respond(false, "Error deleting guest: " . $e->getMessage(), null, 500);
        }
        break;

    default:
        respond(false, "Method not allowed.", null, 405);
        break;
}
?>