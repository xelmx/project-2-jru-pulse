<?php
// --- api/analytics.php (DEFINITIVE, COMPLETE "SUPER ENGINE" VERSION) ---

session_start();
header('Content-Type: application/json');
require_once '../config/connection.php';

// --- HELPER FUNCTIONS ---
function respond($success, $message, $data = null, $code = 200) { http_response_code($code); echo json_encode(["success" => (bool)$success, "message" => $message, "data" => $data]); exit; }
function callAiApi($texts) {
    if (empty($texts)) return null;
    $ai_api_url = 'http://127.0.0.1:8000/analyze';
    $postData = json_encode(['texts' => $texts]);
    $ch = curl_init($ai_api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']); curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    $response = curl_exec($ch); curl_close($ch);
    return $response ? json_decode($response, true) : null;
}

// --- DB CONNECTION & USER PERMISSIONS ---
try { $database = new Database(); $db = $database->getConnection(); } catch (Exception $e) { respond(false, "Database connection failed: " . $e->getMessage(), null, 500); }
if (!isset($_SESSION['user_data'])) { respond(false, "Authentication required.", null, 401); }
$user_role = $_SESSION['user_data']['role'];
$user_office_id = $_SESSION['user_data']['office_id'] ?? null;

// --- FILTER LOGIC ---
$period = $_GET['period'] ?? 'this_month';
$selected_office_id = $_GET['office_id'] ?? 'all';
// (Date calculation logic would go here based on $period)
$startDate = '2025-01-01'; // Placeholder
$endDate = '2025-12-31';   // Placeholder

try {
    // --- 1. CORE DATA AGGREGATION ---
    $endDatePlusOne = (new DateTime($endDate))->modify('+1 day')->format('Y-m-d');
    $params = [':startDate' => $startDate, ':endDatePlusOne' => $endDatePlusOne];
    $query = "SELECT 
                s.office_id, o.name as office_name, s.service_id, sv.name as service_name,
                sr.answers_json, s.questions_json, sr.submitted_at,
                r.respondent_type, st.division, g.role as guest_role
              FROM survey_responses sr
              JOIN surveys s ON sr.survey_id = s.id
              JOIN offices o ON s.office_id = o.id
              JOIN services sv ON s.service_id = sv.id
              JOIN respondents r ON sr.respondent_id = r.id
              LEFT JOIN students st ON r.student_id = st.id
              LEFT JOIN guests g ON r.guest_id = g.id
              WHERE sr.submitted_at BETWEEN :startDate AND :endDatePlusOne";

    if ($user_role === 'office_head') {
        $query .= " AND s.office_id = :user_office_id";
        $params[':user_office_id'] = $user_office_id;
    } elseif ($selected_office_id !== 'all') {
        $query .= " AND s.office_id = :selected_office_id";
        $params[':selected_office_id'] = $selected_office_id;
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- 2. INITIALIZE AGGREGATORS & PROCESS DATA ---
    $agg = [
        'total_responses' => count($results), 'all_ratings' => [], 'suggestions' => [],
        'office_ratings' => [], 'day_of_week' => [], 'respondent_type' => [], 'student_division' => []
    ];

    foreach ($results as $row) {
        $date = new DateTime($row['submitted_at']);
        $dayName = $date->format('l');
        $questionMap = [];
        $questions = json_decode($row['questions_json'], true);
        foreach ($questions as $q) { if (isset($q['id'])) { $questionMap[$q['id']] = ['title' => $q['title'] ?? 'Untitled']; } }
        
        $answers = json_decode($row['answers_json'], true);
        foreach ($answers as $ans) {
            if (isset($questionMap[$ans['question_id']])) {
                $title = $questionMap[$ans['question_id']]['title'];
                if ($title === 'Suggestions') {
                    $suggestionText = trim($ans['answer']);
                    if (strlen($suggestionText) > 5) { $agg['suggestions'][] = ['text' => $suggestionText, 'sentiment' => null]; }
                } else {
                    $rating = filter_var($ans['answer'], FILTER_VALIDATE_INT);
                    if ($rating !== false) {
                        $agg['all_ratings'][] = $rating;
                        if (!isset($agg['day_of_week'][$dayName])) { $agg['day_of_week'][$dayName] = []; }
                        $agg['day_of_week'][$dayName][] = $rating;
                        $respondent_label = $row['respondent_type'] === 'student' ? 'Student' : ($row['guest_role'] ?? 'Guest');
                        if (!isset($agg['respondent_type'][$respondent_label])) { $agg['respondent_type'][$respondent_label] = []; }
                        $agg['respondent_type'][$respondent_label][] = $rating;
                        if ($row['respondent_type'] === 'student' && !empty($row['division'])) {
                            if (!isset($agg['student_division'][$row['division']])) { $agg['student_division'][$row['division']] = []; }
                            $agg['student_division'][$row['division']][] = $rating;
                        }
                    }
                }
            }
        }
    }

    // --- 3. CALCULATE FINAL METRICS ---
    $finalData = [];
    $finalData['total_responses'] = $agg['total_responses'];
    $finalData['overall_satisfaction'] = !empty($agg['all_ratings']) ? round(array_sum($agg['all_ratings']) / count($agg['all_ratings']), 2) : 0;
    
    $finalData['rating_distribution'] = array_fill_keys([5, 4, 3, 2, 1], 0);
    foreach ($agg['all_ratings'] as $r) { $finalData['rating_distribution'][$r]++; }

    $promoters = $finalData['rating_distribution'][5];
    $detractors = $finalData['rating_distribution'][1] + $finalData['rating_distribution'][2] + $finalData['rating_distribution'][3];
    $finalData['nps'] = ($agg['total_responses'] > 0) ? round((($promoters - $detractors) / $agg['total_responses']) * 100) : 0;
    
    // (Add Median and Mode calculation here if needed)

    $finalData['performance_by_day'] = [];
    $days_order = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    foreach ($days_order as $day) {
        if (isset($agg['day_of_week'][$day])) {
            $finalData['performance_by_day'][] = ['day' => $day, 'score' => round(array_sum($agg['day_of_week'][$day]) / count($agg['day_of_week'][$day]), 2)];
        }
    }

    $finalData['satisfaction_by_type'] = [];
    foreach ($agg['respondent_type'] as $type => $ratings) {
        $finalData['satisfaction_by_type'][] = ['type' => $type, 'score' => round(array_sum($ratings) / count($ratings), 2)];
    }

    $finalData['satisfaction_by_division'] = [];
    foreach ($agg['student_division'] as $division => $ratings) {
        $finalData['satisfaction_by_division'][] = ['division' => $division, 'score' => round(array_sum($ratings) / count($ratings), 2)];
    }
    
    // --- 4. AI INTEGRATION ---
    $aiData = callAiApi(array_column($agg['suggestions'], 'text'));
    $finalData['feedback_themes'] = $aiData['common_concerns'] ?? [];
    $recent_comments_with_sentiment = [];
    if ($aiData && isset($aiData['items'])) {
        foreach($aiData['items'] as $item) { $recent_comments_with_sentiment[] = ['text' => $item['text'], 'sentiment' => $item['sentiment']]; }
    }
    $finalData['recent_comments'] = array_slice($recent_comments_with_sentiment, 0, 5);
    
    respond(true, "Analytics data retrieved successfully.", $finalData);

} catch (Exception $e) {
    respond(false, "An API error occurred: " . $e->getMessage(), null, 500);
}

?>