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
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    http_response_code(200);
    exit();
}

require_once '../config/connection.php';
function respond($s, $m, $d=null, $c=200){http_response_code($c);echo json_encode(["success"=>(bool)$s,"message"=>$m,"data"=>$d]);exit;}

try {
    $db = (new Database())->getConnection();
} catch (Exception $e) {
    respond(false, "DB Connection Failed", null, 500);
}

if ($_SERVER["REQUEST_METHOD"] == "DELETE") {
    
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        respond(false, "A valid Service ID is required.", null, 400);
    }
    $id = intval($_GET['id']);

    try {
        $query = "DELETE FROM services WHERE id = :id"; //SQL COMMAND FOR PERMANENT DELITION
        $stmt = $db->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            respond(true, "Service has been permanently deleted.");
        } else {
            respond(false, "Service not found. It may have already been deleted.", null, 404);
        }

    } catch (PDOException $e) { 
        if ($e->getCode() == '23000') {  // Catch the foreign key constraint violation error
            respond(false, "Cannot delete this service because surveys are still linked to it.", null, 409);
        }
        respond(false, "Database error: " . $e->getMessage(), null, 500);
    }
} else {
    respond(false, "Method Not Allowed.", null, 405);
}
?>