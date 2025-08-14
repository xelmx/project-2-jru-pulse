<?php
header("Content-Type: apllication/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization, x-requested-with");

require_once '../config/connection.php';

// Use the same respond helper function from your other API files
function respond($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode(["success" => (bool)$success, "message" => $message, "data" => $data]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    respond(false, "Database connection failed", null, 500);
}

$method = $_SERVER["REQUEST_METHOD"];

switch($method) {
    case "POST":
        // This handles saving a new template
        $data = json_decode(file_get_contents("php://input"), true);

        // Basic validation
        if (empty($data['template_name']) || empty($data['questions'])) {
            respond(false, "Template Name and at least one question are required.", null, 400);
        }

        try {
            $query = "INSERT INTO survey_templates (template_name, description, questions_json) 
                      VALUES (:template_name, :description, :questions_json)";
            
            $stmt = $db->prepare($query);

            // The 'questions' property should already be an array of question objects.
            // We just need to encode it into a JSON string for the database.
            $questions_json = json_encode($data['questions']);

            $stmt->bindValue(':template_name', $data['template_name']);
            $stmt->bindValue(':description', $data['description'] ?? null);
            $stmt->bindValue(':questions_json', $questions_json);
            
            $stmt->execute();
            
            respond(true, "Template saved successfully.", ["id" => $db->lastInsertId()], 201);

        } catch (PDOException $e) {
            respond(false, "Database error: " . $e->getMessage(), null, 500);
        }
        break;

   case "GET":
    // Check if the request is for a SINGLE template by ID
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id = intval($_GET['id']);
        try {
            // This query fetches the full template data, including the questions
            $query = "SELECT * FROM survey_templates WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
            $template = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($template) {
                respond(true, "Template retrieved successfully.", $template);
            } else {
                respond(false, "Template not found.", null, 404);
            }
        } catch (PDOException $e) {
            respond(false, "Database error: " . $e->getMessage(), null, 500);
        }
    } else {
        // This is the original logic to get a LIST of all templates for the dropdown
        try {
            $query = "SELECT id, template_name FROM survey_templates ORDER BY template_name ASC";
            $stmt = $db->query($query);
            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            respond(true, "Templates retrieved successfully.", $templates);
        } catch (PDOException $e) {
            respond(false, "Database error: " . $e->getMessage(), null, 500);
        }
    }
    break;
    default:
        respond(false, "Method not allowed", null, 405);
}
?>