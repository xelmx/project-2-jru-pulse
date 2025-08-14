<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER["REQUEST_METHOD"] == 'OPTIONS') {
  http_response_code(200);
  exit();
}

require_once '../config/connection.php';

function respond($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        "success" => (bool)$success, // forces the value to be a true boolean
        "message" => $message,
        "data" => $data
    ]);
    exit;
}

// Get database connection
try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    respond(false, "Database connection failed", null, 500);
}

$method = $_SERVER["REQUEST_METHOD"];

switch($method) {
    //READ, Retireve or display
   case "GET":
        try {
            $active_state = (isset($_GET['show_archived_services']) && $_GET['show_archived_services'] == 'true') ? 0 : 1;
            
            $base_query = "SELECT s.*, o.name as office_name FROM services s 
                           LEFT JOIN offices o ON s.office_id = o.id 
                           WHERE s.is_active = :active_state";

            if (isset($_GET['office_id']) && !empty($_GET['office_id'])) {
                $query = $base_query . " AND s.office_id = :office_id ORDER BY s.name";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':office_id', $_GET['office_id']);
            } else {
                $query = $base_query . " ORDER BY o.name, s.name";
                $stmt = $db->prepare($query);
            }

            $stmt->bindParam(':active_state', $active_state);
            $stmt->execute();
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
            respond(true, "Services retrieved successfully", $services);
        } catch (PDOException $e) {
            respond(false, "Error retrieving services: " . $e->getMessage(), null, 500);
        }
        break;
        
    case "POST":
        //CREATE or Add
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['office_id']) || !isset($data['name']) || !isset($data['code'])) {
            respond(false, "Office ID, name, and code are required", null, 400);
        }
        
        try {
            $query = "INSERT INTO services (office_id, name, code, description) VALUES (:office_id, :name, :code, :description)";
            $stmt = $db->prepare($query);
            
            $description = $data['description'] ?? '';

            $stmt->bindValue(':office_id', $data['office_id']);
            $stmt->bindValue(':name', $data['name']);
            $stmt->bindValue(':code', $data['code']);
            $stmt->bindValue(':description', $description);
            
            $stmt->execute();
            $id = $db->lastInsertId();
            respond(true, "Service created successfully", ["id" => $id]);
        }  catch (PDOException $e) {
            respond(false, "Error creating service: " . $e->getMessage(), null, 500);
        }

    case "PUT":
        if (isset($_GET['action']) && $_GET['action'] == 'reactivate') {
            if (!isset($_GET['id'])) { respond(false, "ID required", null, 400); }
            $id = intval($_GET['id']);
            $query = "UPDATE services SET is_active = 1 WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindValue(':id', $id);
            if ($stmt->execute()) { respond(true, "Service reactivated"); } else { respond(false, "Failed to reactivate"); }
        } else {
            if (!isset($_GET['id'])) { respond(false, "ID required for update", null, 400); }
            $id = intval($_GET['id']);
            $data = json_decode(file_get_contents("php://input"), true);
            if (!isset($data['name']) || !isset($data['code'])) { respond(false, "Name and code required", null, 400); }
            
            $query = "UPDATE services SET name = :name, code = :code, description = :description WHERE id = :id";
            $stmt = $db->prepare($query);
            $description = $data['description'] ?? '';
            $stmt->bindValue(':name', $data['name']);
            $stmt->bindValue(':code', $data['code']);
            $stmt->bindValue(':description', $description);
            $stmt->bindValue(':id', $id);
            if ($stmt->execute()) { respond(true, "Service updated"); } else { respond(false, "Update failed"); }
        }
        break;

    case "DELETE":
        if (!isset($_GET['id'])) { respond(false, "ID required", null, 400); }
        $id = intval($_GET['id']);
        $query = "UPDATE services SET is_active = 0 WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindValue(':id', $id);
        if ($stmt->execute()) { respond(true, "Service archived"); } else { respond(false, "Archive failed"); }
        break;
        
    default:
        respond(false, "Method not allowed", null, 405);
}
?>
