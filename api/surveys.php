<?php

// --- SECURITY: Only admins can access this API ---
session_start();
if (!isset($_SESSION['user_data']) || $_SESSION['user_data']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Forbidden: You do not have permission to access this resource."]);
    exit;
}

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-control-allow-headers: content-type, authorization, x-requested-with");

if ($_SERVER["REQUEST_METHOD"] == 'OPTIONS') {
  http_response_code(200);
  exit();
}

require_once '../config/connection.php';

// --- Helper & Connection ---
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

$method = $_SERVER["REQUEST_METHOD"];

error_log("API api/surveys.php received a request with method: " . $method);

switch($method) {
      case "GET":
            if (isset($_GET['id']) && is_numeric($_GET['id'])) { // The GET request for a specific survey MUST have an ID.
                $id = intval($_GET['id']);
                try {
                    
                    $query = "SELECT * FROM surveys WHERE id = :id"; // Logic for the "take-survey" page.
                    $stmt = $db->prepare($query);
                    $stmt->execute([':id' => $id]);
                    $survey = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($survey) {
                        respond(true, "Single survey retrieved successfully.", $survey); // Use a specific success message for a single survey.
                    } else {
                        respond(false, "Survey not found with this ID.", null, 404);
                    }
                } catch (PDOException $e) {
                    respond(false, "DB error: " . $e->getMessage(), null, 500);
                }
            } 
        
            else if (isset($_GET['dashboard'])) {  // This part is ONLY for the admin dashboard.
                try {
                    // This logic is for the admin dashboard to get lists of surveys.
                    if (isset($_GET['show_archived']) && $_GET['show_archived'] == 'true') {
                        $query = "SELECT s.*, o.name as office_name, se.name as service_name, (SELECT COUNT(*) FROM survey_responses WHERE survey_id = s.id) as response_count FROM surveys s LEFT JOIN offices o ON s.office_id = o.id LEFT JOIN services se ON s.service_id = se.id WHERE s.status = 'archived' ORDER BY s.updated_at DESC";
                    } else {
                        $query = "SELECT s.*, o.name as office_name, se.name as service_name, (SELECT COUNT(*) FROM survey_responses WHERE survey_id = s.id) as response_count FROM surveys s LEFT JOIN offices o ON s.office_id = o.id LEFT JOIN services se ON s.service_id = se.id WHERE s.status IN ('draft', 'active', 'inactive') ORDER BY s.created_at DESC";
                    }
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    respond(true, "Survey list retrieved successfully.", $stmt->fetchAll(PDO::FETCH_ASSOC));
                } catch (PDOException $e) {
                    respond(false, "DB error: " . $e->getMessage(), null, 500);
                }
            }
            // If neither of the above conditions are met, it's a bad request.
            else {
                respond(false, "A valid Survey ID is required for this page.", null, 400);
            }
    break;

   case "POST": // It correctly sets the status to 'active' if the action is 'publish'.
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['title']) || empty($data['office_id']) || empty($data['service_id'])) {
            respond(false, "Title, office, and service are required.", null, 400);
        }
        
        $statusToSet = (isset($data['action']) && $data['action'] === 'publish') ? 'active' : 'draft';
        
        try {
            $query = "INSERT INTO surveys (title, description, office_id, service_id, status, questions_json, is_locked) VALUES (:title, :description, :office_id, :service_id, :status, :questions_json, 0)";
            $stmt = $db->prepare($query);
            $questions_json = json_encode($data['questions'] ?? []);
            
            $stmt->bindValue(':title', $data['title']);
            $stmt->bindValue(':description', $data['description'] ?? '');
            $stmt->bindValue(':office_id', $data['office_id']);
            $stmt->bindValue(':service_id', $data['service_id']);
            $stmt->bindValue(':status', $statusToSet);
            $stmt->bindValue(':questions_json', $questions_json);
            
            $stmt->execute();

            $message = ($statusToSet === 'active') ? "Survey published successfully." : "Draft created successfully.";
            respond(true, $message, ["new_id" => $db->lastInsertId()], 201);
            
        } catch (PDOException $e) { respond(false, "DB error on create: " . $e->getMessage(), null, 500); }
    break;
    
    case "PUT": //the main router for any "update" action. First thing to do is get the survey's ID from the URL and the 'action' command from the JSON data sent by the JavaScript. If either is missing, we can't do anything, so we stop early.
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            respond(false, "A valid Survey ID is required in the URL.", null, 400);
        }
        $id = intval($_GET['id']);
        $data = json_decode(file_get_contents("php://input"), true);
        $action = $data['action'] ?? null;

        if (!$action) {
            respond(false, "An 'action' command is required in the request body.", null, 400);
        }

        try {
            switch ($action) {
                case 'update_details':  //Handles updating the content of a draft survey. This action is triggered by the "Save" button in the survey builder. It protects data integrity by only allowing updates on surveys that are NOT locked.
                    $query = "UPDATE surveys SET title = :title, description = :description, office_id = :office_id, service_id = :service_id, questions_json = :questions_json WHERE id = :id AND is_locked = 0";
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        ':title' => $data['title'],
                        ':description' => $data['description'] ?? '',
                        ':office_id' => $data['office_id'],
                        ':service_id' => $data['service_id'],
                        ':questions_json' => json_encode($data['questions'] ?? []),
                        ':id' => $id
                    ]);
                    if ($stmt->rowCount() > 0) {
                        respond(true, "Draft saved successfully.");
                    } else {
                        respond(false, "Update failed. The survey may be locked or you didn't make any changes.", 409);
                    }
                    break;
                case 'publish': //Handles publishing a draft survey. This action is triggered by the "Publish" button in the survey builder. It updates the survey's content and atomically sets its status to 'active'.
                    $query = "UPDATE surveys SET title = :title, description = :description, office_id = :office_id, service_id = :service_id, questions_json = :questions_json, status = 'active' WHERE id = :id AND status = 'draft'";
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                         ':title' => $data['title'],
                        ':description' => $data['description'] ?? '',
                        ':office_id' => $data['office_id'],
                        ':service_id' => $data['service_id'],
                        ':questions_json' => json_encode($data['questions'] ?? []),
                        ':id' => $id
                    ]);
                    if ($stmt->rowCount() > 0) {
                        respond(true, "Survey published successfully.");
                    } else {
                        respond(false, "Publish failed. The survey may already be active or locked.", 409);
                    }
                    break;
                case 'deactivate': // Sets an 'active' survey to 'inactive'. a reversible action from the management dashboard to temporarily pause a survey.
                    $query = "UPDATE surveys SET status = 'inactive' WHERE id = :id AND status = 'active'";
                    $stmt = $db->prepare($query);
                    $stmt->execute([':id' => $id]);
                    respond(true, "Survey has been deactivated.");
                    break;
                case 'reactivate': //Sets an 'inactive' survey back to 'active'. This is a reversible action from the management dashboard.
                    $query = "UPDATE surveys SET status = 'active' WHERE id = :id AND status = 'inactive'";
                    $stmt = $db->prepare($query);
                    $stmt->execute([':id' => $id]);
                    respond(true, "Survey has been reactivated.");
                    break;
                case 'archive': //When we archive something, we first COPY its current status ('draft', 'active', etc.)into the `status_before_archived` column, and THEN we set the main status to 'archived'.
                    $query = "UPDATE surveys SET status_before_archived = status, status = 'archived' WHERE id = :id AND status != 'archived'";
                    $stmt = $db->prepare($query);
                    $stmt->execute([':id' => $id]);
                    respond(true, "Survey has been archived.");
                    break;
                case 'unarchive': //Set `status_before_archived` back to NULL to clean it up for the future. Smart Unarchive" feature in action.
                    $query = "UPDATE surveys SET status = status_before_archived, status_before_archived = NULL WHERE id = :id AND status = 'archived'";
                    $stmt = $db->prepare($query);
                    $stmt->execute([':id' => $id]);
                    respond(true, "Survey has been restored from the archive.");
                    break;
                    
                default:
                    respond(false, "Invalid action specified.", null, 400);  // A fallback for any action command we don't recognize.
                    break;
            }
        } catch (PDOException $e) {
            respond(false, "Database error: " . $e->getMessage(), null, 500);
        }
    break;
    
   case "DELETE":
    // This action should be handled by a different, dedicated API endpoint like `api/permanent-delete-survey.php`
    respond(false, "Permanent deletion is not supported by this endpoint. Use archive instead.", 403);
    break;

    default:
        respond(false, "Method not allowed", null, 405);
}
?>