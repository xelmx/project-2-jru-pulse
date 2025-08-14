<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
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

if ($_SERVER["REQUEST_METHOD"] == 'DELETE') { // This script ONLY handles DELETE requests.

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {  // Get the ID from the URL.
        respond(false, "A valid Office ID is required for permanent deletion.", null, 400);
    }
    $id = intval($_GET['id']);

    try {
        $query = "DELETE FROM offices WHERE id = :id";  // This is the physical DELETE query.
        $stmt = $db->prepare($query);
        $stmt->bindValue(':id', $id);
        
        $stmt->execute();

        if ($stmt->rowCount() > 0) { // Check if a row was actually deleted.
            respond(true, "Office has been permanently deleted.");
        } else {
            respond(false, "Office not found. It may have already been deleted.", null, 404);
        }

    } catch (PDOException $e) { // Catch the foreign key constraint violation error.
        if ($e->getCode() == '23000') { // The error code '23000' is standard for integrity constraint violations.
            respond(false, "Cannot delete this office because it still has services or surveys linked to it. Please reassign or delete them first.", null, 409); // 409 Conflict
        }
        
        respond(false, "Database error during deletion: " . $e->getMessage(), null, 500); // For any other database errors.
    }
} else {
    respond(false, "Method Not Allowed.", null, 405); // If any other method like GET or POST is sent to this file, reject it.
}
?>