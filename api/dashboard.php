<?php
// --- api/dashboard.php (FINAL & COMPLETE VERSION) ---

session_start();
header('Content-Type: application/json');
require_once '../config/connection.php';

// --- Helper Functions ---
function respond($success, $message, $data = null, $code = 200) { http_response_code($code); echo json_encode(["success" => (bool)$success, "message" => $message, "data" => $data]); exit; }
function calculate_change($current, $previous) { if ($previous == 0) return null; return round((($current - $previous) / $previous) * 100); }
function callAiApi($texts) {
    if (empty($texts)) return null;
    $ai_api_url = 'http://127.0.0.1:8000/analyze';
    $postData = json_encode(['texts' => $texts]);
    $ch = curl_init($ai_api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response ? json_decode($response, true) : null;
}
function calculatePredictedSatisfaction($aiData) {
    if (isset($aiData['items']) && is_array($aiData['items']) && count($aiData['items']) > 0) {
        $totalPredictedScore = 0;
        foreach ($aiData['items'] as $item) { $totalPredictedScore += $item['predicted_satisfaction']; }
        return round($totalPredictedScore / count($aiData['items']), 2);
    }
    return 0;
}

// --- DB Connection & User Permissions ---
try { $database = new Database(); $db = $database->getConnection(); } catch (Exception $e) { respond(false, "Database connection failed: " . $e->getMessage(), null, 500); }
if (!isset($_SESSION['user_data'])) { respond(false, "Authentication required.", null, 401); }
$user_role = $_SESSION['user_data']['role'];
$user_office_id = $_SESSION['user_data']['office_id'] ?? null;

// --- Date Filter Logic ---
$period = $_GET['period'] ?? 'this_week';
$startDate = $_GET['startDate'] ?? null;
$endDate = $_GET['endDate'] ?? null;
if (!$startDate || !$endDate) {
    $end_dt_preset = new DateTime();
    switch ($period) {
        case 'this_month': $start_dt_preset = new DateTime('first day of this month'); break;
        case 'this_quarter': $month = $end_dt_preset->format('n'); $quarter = ceil($month / 3); $start_month = ($quarter - 1) * 3 + 1; $start_dt_preset = new DateTime(date('Y') . "-$start_month-01"); break;
        case 'this_year': $start_dt_preset = new DateTime('first day of January this year'); break;
        case 'all_time': $start_dt_preset = new DateTime('2020-01-01'); break;
        case 'this_week': default: $start_dt_preset = new DateTime('monday this week'); break;
    }
    $startDate = $start_dt_preset->format('Y-m-d');
    $endDate = $end_dt_preset->format('Y-m-d');
}

// --- Reusable Data Fetching Function ---
function getDashboardDataForPeriod($db, $startDate, $endDate, $user_role, $user_office_id) {
    $endDatePlusOne = (new DateTime($endDate))->modify('+1 day')->format('Y-m-d');
    $dateFilterClause = "sr.submitted_at BETWEEN :startDate AND :endDatePlusOne";
    $params = [':startDate' => $startDate, ':endDatePlusOne' => $endDatePlusOne];
    $query = "SELECT sr.answers_json, s.questions_json, sr.submitted_at FROM survey_responses sr JOIN surveys s ON sr.survey_id = s.id WHERE {$dateFilterClause}";
    if ($user_role === 'office_head') {
        if (empty($user_office_id)) { throw new Exception("Office Head user is not assigned to an office.", 403); }
        $query .= " AND s.office_id = :user_office_id";
        $params[':user_office_id'] = $user_office_id;
    }
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $periodData = [ 'total_responses' => count($results), 'overall_satisfaction' => 0, 'rating_distribution' => array_fill_keys([5, 4, 3, 2, 1], 0), 'service_performance' => [], 'trends_labels' => [], 'trends_data' => [], 'suggestionsForAI' => [] ];
    if ($periodData['total_responses'] > 0) {
        $allRatings = [];
        foreach ($results as $row) {
            $questions = json_decode($row['questions_json'], true); $answers = json_decode($row['answers_json'], true); $questionMap = [];
            foreach ($questions as $q) { if (isset($q['id'])) { $questionMap[$q['id']] = ['type' => $q['type'] ?? 'unknown', 'title' => $q['title'] ?? 'Untitled']; } }
            foreach ($answers as $ans) {
                if (isset($ans['question_id'], $questionMap[$ans['question_id']])) {
                    $questionInfo = $questionMap[$ans['question_id']];
                    if (in_array($questionInfo['type'], ['likert', 'rating'])) {
                        $ratingValue = filter_var($ans['answer'], FILTER_VALIDATE_INT);
                        if ($ratingValue !== false) { $allRatings[] = ['rating' => $ratingValue, 'question_title' => $questionInfo['title'], 'submission_date' => (new DateTime($row['submitted_at']))->format('Y-m-d')]; }
                    } elseif ($questionInfo['title'] === 'Suggestions') {
                        $suggestionText = trim($ans['answer']); $cleanText = strtolower($suggestionText); $junkPhrases = ['na', 'n/a', 'none', 'wala', '.', 'no comment'];
                        if (!in_array($cleanText, $junkPhrases) && strlen($cleanText) > 5) { $periodData['suggestionsForAI'][] = $suggestionText; }
                    }
                }
            }
        }
        if (!empty($allRatings)) {
            $totalScore = 0;
            foreach ($allRatings as $r) { $totalScore += $r['rating']; $periodData['rating_distribution'][$r['rating']]++; }
            $periodData['overall_satisfaction'] = round($totalScore / count($allRatings), 2);
            $performance = [];
            foreach ($allRatings as $r) { if ($r['question_title'] !== 'Suggestions') { if (!isset($performance[$r['question_title']])) { $performance[$r['question_title']] = ['total' => 0, 'count' => 0]; } $performance[$r['question_title']]['total'] += $r['rating']; $performance[$r['question_title']]['count']++; } }
            foreach ($performance as $title => $values) { $periodData['service_performance'][] = ['label' => $title, 'value' => round($values['total'] / $values['count'], 1)]; }
            $trends = [];
            foreach ($allRatings as $r) { if (!isset($trends[$r['submission_date']])) { $trends[$r['submission_date']] = ['total' => 0, 'count' => 0]; } $trends[$r['submission_date']]['total'] += $r['rating']; $trends[$r['submission_date']]['count']++; }
            ksort($trends);
            foreach($trends as $date => $values) { $periodData['trends_labels'][] = $date; $periodData['trends_data'][] = round($values['total'] / $values['count'], 1); }
        }
    }
    return $periodData;
}

try {
    // --- Fetch Data for CURRENT and PREVIOUS periods ---
    $currentData = getDashboardDataForPeriod($db, $startDate, $endDate, $user_role, $user_office_id);
    $start_dt = new DateTime($startDate);
    $end_dt = new DateTime($endDate); 
    $interval = $start_dt->diff($end_dt);
    $prev_end_dt = (clone $start_dt)->modify('-1 day');
    $prev_start_dt = (clone $prev_end_dt)->modify('-' . $interval->format('%a') . ' days');
    $previousData = getDashboardDataForPeriod($db, $prev_start_dt->format('Y-m-d'), $prev_end_dt->format('Y-m-d'), $user_role, $user_office_id);

    // --- Initialize AI metrics in the main data array BEFORE the AI call ---
    // This GUARANTEES these keys will always exist in the final JSON response.
    $currentData['sentiment_analysis'] = ['Positive' => 0, 'Neutral' => 0, 'Negative' => 0];
    $currentData['common_concerns'] = [];
    $currentData['predicted_satisfaction'] = 0;
    
    // --- AI Integration ---
    $aiDataCurrent = callAiApi($currentData['suggestionsForAI']);
    if ($aiDataCurrent) {
        // If the AI call is successful, we OVERWRITE the default values.
        if (isset($aiDataCurrent['summary']['sentiment_counts'])) {
            $sentimentCounts = [];
            foreach ($aiDataCurrent['summary']['sentiment_counts'] as $label => $count) { $sentimentCounts[ucfirst(strtolower($label))] = $count; }
            $currentData['sentiment_analysis'] = array_merge($currentData['sentiment_analysis'], $sentimentCounts);
        }
        if (isset($aiDataCurrent['common_concerns'])) {
            $currentData['common_concerns'] = array_slice($aiDataCurrent['common_concerns'], 0, 5);
        }
        $currentData['predicted_satisfaction'] = calculatePredictedSatisfaction($aiDataCurrent);
    }

    // --- (The logic for the previous period's AI call is also now safe) ---
    $previousData['predicted_satisfaction'] = 0;
    $aiDataPrevious = callAiApi($previousData['suggestionsForAI']);
    if ($aiDataPrevious) {
        $previousData['predicted_satisfaction'] = calculatePredictedSatisfaction($aiDataPrevious);
    }

    // --- Calculate ALL Comparative Metrics ---
    $currentData['comparisons'] = [
        'overall_satisfaction_change' => calculate_change($currentData['overall_satisfaction'], $previousData['overall_satisfaction']),
        'total_responses_change' => calculate_change($currentData['total_responses'], $previousData['total_responses']),
        'predicted_satisfaction_change' => calculate_change($currentData['predicted_satisfaction'], $previousData['predicted_satisfaction'])
    ];
    
    unset($currentData['suggestionsForAI']);

    // --- Finalize and Respond ---
    $currentData['startDate'] = $startDate; $currentData['endDate'] = $endDate;
    respond(true, "Dashboard data retrieved successfully.", $currentData);

} catch (Exception $e) { respond(false, "An unexpected error occurred: " . $e->getMessage(), null, 500); }
?>