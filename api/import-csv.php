<?php
header('Content-Type: application/json');
require_once '../config/connection.php';

// --- Response trackers ---
$successCount = 0;
$updateCount = 0;
$errorCount = 0;
$errors = [];
$rowNumber = 1; // Start at 1 for the header row

// --- Basic File Upload Validation ---
if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File upload error. Please select a valid CSV file.']);
    exit;
}

$file = $_FILES['csv_file']['tmp_name'];
$fileHandle = fopen($file, "r");

if ($fileHandle === FALSE) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not open the uploaded file.']);
    exit;
}

// --- Database Connection ---
$db = new Database();
$conn = $db->getConnection();

try {
    // "SMART IMPORT" HEADER LOGIC

    // 1. Read the header row from the CSV
    $header = fgetcsv($fileHandle);
    if ($header === FALSE) {
        throw new Exception("Cannot read the CSV header. The file might be empty or corrupted.");
    }

    // Sanitize headers: trim whitespace and convert to lowercase for reliable matching
    $header = array_map(function($h) { return trim(strtolower($h)); }, $header);

    // 2. Define the columns in database REQUIRES
    $requiredColumns = [
        'student_number', 'first_name', 'last_name', 'email', 'division', 'course_or_strand'
    ];

    // 3. Validate that all required columns are present in the file's header
    $missingColumns = array_diff($requiredColumns, $header);
    if (!empty($missingColumns)) {
        $errorMessage = "Import failed. Missing required column(s) in the CSV header: " . implode(', ', $missingColumns);
        throw new Exception($errorMessage);
    }

    // 4. Create the "column map" that tells our script which column index corresponds to which data point
    $map = array_flip($header);

   
    // --- Prepare SQL statements once for performance ---
    $checkQuery = "SELECT id FROM students WHERE student_number = :student_number OR email = :email LIMIT 1";
    $checkStmt = $conn->prepare($checkQuery);

    $insertQuery = "INSERT INTO students (student_number, first_name, last_name, email, division, course_or_strand) 
                    VALUES (:student_number, :first_name, :last_name, :email, :division, :course_or_strand)";
    $insertStmt = $conn->prepare($insertQuery);

    $updateQuery = "UPDATE students SET first_name = :first_name, last_name = :last_name, email = :email, division = :division, course_or_strand = :course_or_strand
                    WHERE id = :id";
    $updateStmt = $conn->prepare($updateQuery);

    // --- Begin transaction for data integrity ---
    $conn->beginTransaction();

    // --- Process the data rows ---
    while (($data = fgetcsv($fileHandle, 1000, ",")) !== FALSE) {
        $rowNumber++;

        // --- Use the 'map' to get data reliably, regardless of column order ---
        $student_number = trim($data[$map['student_number']]);
        $first_name = trim($data[$map['first_name']]);
        $last_name = trim($data[$map['last_name']]);
        $email = trim($data[$map['email']]);
        $division = trim($data[$map['division']]);
        $course_or_strand = trim($data[$map['course_or_strand']]);
        
        // --- Validation for required fields ---
        if (empty($student_number) || empty($first_name) || empty($last_name) || empty($email) || empty($division) || empty($course_or_strand)) {
             $errorCount++;
             $errors[] = "Row $rowNumber: Skipped due to missing data in one or more required fields.";
             continue; // Skip this row and move to the next
        }

        // --- Check for duplicates to decide whether to INSERT or UPDATE ---
        $checkStmt->execute([':student_number' => $student_number, ':email' => $email]);
        $existingStudent = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingStudent) {
            // Student exists, perform an UPDATE
            $updateStmt->execute([
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':email' => $email, // Also update email in case it changed but student_number matched
                ':division' => $division,
                ':course_or_strand' => $course_or_strand,
                ':id' => $existingStudent['id']
            ]);
            $updateCount++;
        } else {
            // Student does not exist, perform an INSERT
            $insertStmt->execute([
                ':student_number' => $student_number,
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':email' => $email,
                ':division' => $division,
                ':course_or_strand' => $course_or_strand
            ]);
            $successCount++;
        }
    }

    $conn->commit();

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(400); // Use 400 for a bad request (e.g., bad file format)
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'errors' => $errors]);
    exit;
} finally {
    fclose($fileHandle);
}

// --- Send the final, detailed JSON response ---
$message = "Import complete. {$successCount} new students added, {$updateCount} students updated.";
echo json_encode([
    'success' => true,
    'message' => $message,
    'successCount' => $successCount,
    'updateCount' => $updateCount,
    'errorCount' => $errorCount,
    'errors' => $errors
]);
?>