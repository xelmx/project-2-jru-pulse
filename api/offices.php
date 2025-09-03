<?php

// --- SECURITY: Only admins can access this API ---
session_start();
if (!isset($_SESSION['user_data']) || $_SESSION['user_data']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Forbidden: You do not have permission to access this resource."]);
    exit;
}

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Or specify your frontend domain
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Header: Content-Type, Authorization, X-Requested-With");

if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") { // This handles the browser's preflight "OPTIONS" request
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

try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    respond(false, "Database connection failed", null, 500);
}


$method = $_SERVER["REQUEST_METHOD"]; //REST API for Offices

switch($method) {
    case "GET":  //Read Retrieve
        try {
           if (isset($_GET['show_archived_offices']) && $_GET['show_archived_offices'] == 'true') {
                $query = "SELECT * FROM offices WHERE is_active = 0 ORDER BY name";
            } else {
                $query = "SELECT * FROM offices WHERE is_active = 1 ORDER BY name";
            }
            
            $stmt =  $db->prepare($query);
            $stmt->execute();

            $offices = $stmt->fetchAll(PDO::FETCH_ASSOC);

             error_log("Offices count: " . count($offices));
             error_log("Offices data: " . json_encode($offices));

            respond(true, "Offices retrieved successfully", $offices);
        
        } catch(PDOException $e) {
            respond(false, "Error retrieving offices: " . $e->getMessage(), null, 500);
        }
        break;
    
    case "POST":
    $data = json_decode(file_get_contents("php://input"), true);    //Create

    if(!isset($data["name"]) || empty($data["name"]) || !isset($data["code"]) || empty($data["code"])) {
        respond(false, "Name and code are required", null, 400);
    }

    try {
        $query = "INSERT INTO offices (name, code, description)
                  VALUES(:name, :code, :description)";
        $stmt = $db->prepare($query);

        $name = htmlspecialchars(strip_tags($data['name']));  // Sanitize and prepare variables
        $code = htmlspecialchars(strip_tags($data['code']));
        $description = isset($data['description']) ? htmlspecialchars(strip_tags($data['description'])) : '';

        $stmt->bindValue(":name", $name); // Use bindValue() for consistency and safety
        $stmt->bindValue(":code", $code);
        $stmt->bindValue(":description", $description);

        $stmt->execute();

        $id = $db->lastInsertId();
        
        respond(true, "Office created successfully", ["id" => $id]);

    } catch(PDOException $e) {
        if ($e->getCode() == 23000) {  // TTell if it's a database error like a duplicate entry // Code 23000 is for integrity constraint violations
            respond(false, "An office with this name or code already exists.", null, 409); // 409 Conflict
        }
        respond(false, "Error creating office: " . $e->getMessage(), null, 500);
    }
    break;

    case "PUT":
    
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { // The ID from the URL is always required for any PUT action.
        respond(false, "A valid Office ID is required.", null, 400);
    }
    $id = intval($_GET['id']);
    
    $data = json_decode(file_get_contents("php://input"), true); // Get the data from the request body.
    $action = $data['action'] ?? 'update_details';  // Determine the action. If no action is specified, it's a standard update.

    try {
        if ($action == 'reactivate') {
            $query = "UPDATE offices SET is_active = 1 WHERE id = :id AND is_active = 0"; // This query sets the office back to active (is_active = 1).
            $stmt = $db->prepare($query);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) { // Check if a row was actually changed.
                respond(true, "Office reactivated successfully.");
            } else {
                respond(false, "Office could not be reactivated (it may already be active).", null, 409);
            }

        } elseif ($action == 'update_details') {
            if (empty($data['name']) || empty($data['code'])) {  // This is the logic you already have for updating an office's details.
                respond(false, "Name and code are required for update.", null, 400);
            }
            
            $query = "UPDATE offices 
                      SET name = :name, code = :code, description = :description 
                      WHERE id = :id";
            $stmt = $db->prepare($query);
            
            $stmt->bindValue(':name', $data['name']);
            $stmt->bindValue(':code', $data['code']);
            $stmt->bindValue(':description', $data['description'] ?? '');
            $stmt->bindValue(':id', $id);
            
            $stmt->execute();
            respond(true, "Office updated successfully.");
            
        } else {
            respond(false, "Invalid action specified.", null, 400);  // If the action is something unknown.
        }
    } catch (PDOException $e) {
        respond(false, "Database error during update: " . $e->getMessage(), null, 500);
    }
    break; 
       

    case "DELETE" :
         if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { // Get ID from URL query string
                respond(false, "A valid Office ID is required for archival.", null, 400);
            }
              $id = intval($_GET['id']);

        try {
            $query = "UPDATE offices SET is_active = 0 WHERE id = :id";  //soft delete logic: gawing 0 yung is_active
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);

               if($stmt->execute()) {
                    respond(true, "Office archived successfully");
                } else {
                    respond(false, "Failed to archive office.", null, 500);
                }

            } catch (PDOException $e) {
                respond(false, "Database error: " . $e->getMessage(), null, 500);
        }
        break;
    
    default:
        respond(false, "Method not allowed", null, 405);
}

?>