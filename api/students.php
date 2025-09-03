<?php 
// --- SECURITY: Only admins can access this API ---
session_start();
if (!isset($_SESSION['user_data']) || $_SESSION['user_data']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Forbidden: You do not have permission to access this resource."]);
    exit;
}

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
        
        case 'GET':
            // No changes needed for GET
            $searchTerm = $_GET['search'] ?? '';
            $query = "SELECT * FROM students WHERE student_number LIKE :searchTerm OR first_name LIKE :searchTerm OR last_name LIKE :searchTerm OR email LIKE :searchTerm ORDER BY last_name, first_name";
            try {
                $stmt = $db->prepare($query);
                $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
                $stmt->execute();
                respond(true, "Students retrieved successfully.", $stmt->fetchAll(PDO::FETCH_ASSOC));
            } catch (PDOException $e) {
                respond(false, "Error fetching students: " . $e->getMessage(), null, 500);
            }
            break;

        // --- CREATE Add New Student
        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);
            if (empty($data['student_number']) || empty($data['first_name']) || empty($data['last_name']) || empty($data['email'])) {
                respond(false, "All fields are required.", null, 400);
            }

            $query = "INSERT INTO students (student_number, first_name, last_name, email, division, course_or_strand) VALUES (:student_number, :first_name, :last_name, :email, :division, :course_or_strand)";

            try {
                $stmt = $db->prepare($query);
                $stmt->bindValue(':student_number', $data['student_number']);
                $stmt->bindValue(':first_name', $data['first_name']);
                $stmt->bindValue(':last_name', $data['last_name']);
                $stmt->bindValue(':email', $data['email']);
                $stmt->bindValue(':division', $data['division']);
                $stmt->bindValue(':course_or_strand', $data['course_or_strand']);
                $stmt->execute();
                respond(true, "Student added successfully.");
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // Catch duplicate entry
                    respond(false, "A student with this Student Number or Email already exists.", null, 409);
                }
                respond(false, "Error adding student: " . $e->getMessage(), null, 500);
            }
            break;

        // --- UPDATE (Edit Existing Student) ---
        case 'PUT':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                respond(false, "Student ID is required for update.", null, 400);
            }
            
            $data = json_decode(file_get_contents("php://input"), true);
            $query = "UPDATE students SET student_number = :student_number, first_name = :first_name, last_name = :last_name, email = :email, division = :division, course_or_strand = :course_or_strand WHERE id = :id";

            try {
                $stmt = $db->prepare($query);
                $stmt->bindValue(':id', $id);
                $stmt->bindValue(':student_number', $data['student_number']);
                $stmt->bindValue(':first_name', $data['first_name']);
                $stmt->bindValue(':last_name', $data['last_name']);
                $stmt->bindValue(':email', $data['email']);
                $stmt->bindValue(':division', $data['division']);
                $stmt->bindValue(':course_or_strand', $data['course_or_strand']);
                $stmt->execute();
                respond(true, "Student updated successfully.");
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    respond(false, "A student with this Student Number or Email already exists.", null, 409);
                }
                respond(false, "Error updating student: " . $e->getMessage(), null, 500);
            }
            break;

        // --- DELETE ---
        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                respond(false, "Student ID is required for deletion.", null, 400);
            }

            $query = "DELETE FROM students WHERE id = :id";
            
            try {
                $stmt = $db->prepare($query);
                $stmt->bindValue(':id', $id);
                $stmt->execute();
                respond(true, "Student deleted successfully.");
            } catch (PDOException $e) {
                respond(false, "Error deleting student: " . $e->getMessage(), null, 500);
            }
            break;

        default:
            respond(false, "Method not allowed.", null, 405);
            break;
    }
?>