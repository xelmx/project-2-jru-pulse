<?php
session_start();
header('Content-Type: application/json');

require_once '../config/connection.php';

function respond($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode(["success" => (bool)$success, "message" => $message, "data" => $data]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    respond(false, "Database connection failed: " . $e->getMessage(), null, 500);
}

// --- 1. Date Filter Logic ---
$period = $_GET['period'] ?? 'this_week';
$startDate = $_GET['startDate'] ?? null;
$endDate = $_GET['endDate'] ?? null;

if (!$startDate || !$endDate) {
    $end_dt_preset = new DateTime();
    switch ($period) {
        case 'this_month': $start_dt_preset = new DateTime('first day of this month'); break;
        case 'this_quarter':
            $month = $end_dt_preset->format('n');
            $quarter = ceil($month / 3);
            $start_month = ($quarter - 1) * 3 + 1;
            $start_dt_preset = new DateTime(date('Y') . "-$start_month-01");
            break;
        case 'this_year': $start_dt_preset = new DateTime('first day of January this year'); break;
        case 'all_time': $start_dt_preset = new DateTime('2020-01-01'); break;
        case 'this_week': default: $start_dt_preset = new DateTime('monday this week'); break;
    }
    $startDate = $start_dt_preset->format('Y-m-d');
    $endDate = $end_dt_preset->format('Y-m-d');
}
$endDatePlusOne = (new DateTime($endDate))->modify('+1 day')->format('Y-m-d');
$dateFilterClause = "sr.submitted_at BETWEEN :startDate AND :endDatePlusOne";
$params = [':startDate' => $startDate, ':endDatePlusOne' => $endDatePlusOne];


// --- 2. Data Calculation & AI Integration ---
$data = [];

try {
    // Step 1: Fetch all survey response data
    $query = "SELECT sr.answers_json, s.questions_json, sr.submitted_at FROM survey_responses sr JOIN surveys s ON sr.survey_id = s.id WHERE {$dateFilterClause}";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Step 2: Initialize all metrics
    $data['total_responses'] = count($results);
    $data['overall_satisfaction'] = 0;
    $data['rating_distribution'] = array_fill_keys([5, 4, 3, 2, 1], 0);
    $data['service_performance'] = [];
    $data['trends_labels'] = [];
    $data['trends_data'] = [];
    $data['sentiment_analysis'] = ['Positive' => 0, 'Neutral' => 0, 'Negative' => 0];
    $data['common_concerns'] = [];
    
    $allRatings = [];
    $suggestionsForAI = [];

    if ($data['total_responses'] > 0) {
        // Step 3: Process database results
        foreach ($results as $row) {
            $questions = json_decode($row['questions_json'], true);
            $answers = json_decode($row['answers_json'], true);
            $questionMap = [];
            foreach ($questions as $q) { if (isset($q['id'])) { $questionMap[$q['id']] = ['type' => $q['type'] ?? 'unknown', 'title' => $q['title'] ?? 'Untitled']; } }
            
            foreach ($answers as $ans) {
                if (isset($ans['question_id'], $questionMap[$ans['question_id']])) {
                    $questionInfo = $questionMap[$ans['question_id']];
                    if (in_array($questionInfo['type'], ['likert', 'rating'])) {
                        $ratingValue = filter_var($ans['answer'], FILTER_VALIDATE_INT);
                        if ($ratingValue !== false) {
                            $allRatings[] = ['rating' => $ratingValue, 'question_title' => $questionInfo['title'], 'submission_date' => (new DateTime($row['submitted_at']))->format('Y-m-d')];
                        }
                    }
                    if ($questionInfo['title'] === 'Suggestions') {
                        $suggestionText = trim($ans['answer']);
                        $cleanText = strtolower($suggestionText);
                        $junkPhrases = ['na', 'n/a', 'none', 'wala', '.', 'no comment', 'none.', 'n.a', 'nan'];
                        if (!in_array($cleanText, $junkPhrases) && strlen($cleanText) > 5) {
                            $suggestionsForAI[] = $suggestionText;
                        }
                    }
                }
            }
        }
        
        // --- Step 4: Aggregate standard metrics ---
        if (!empty($allRatings)) {
            // Overall Satisfaction & Rating Distribution
            $totalScore = 0;
            foreach ($allRatings as $r) {
                $totalScore += $r['rating'];
                $data['rating_distribution'][$r['rating']]++;
            }
            $data['overall_satisfaction'] = round($totalScore / count($allRatings), 1);

            // Service Performance Calculation
            $performance = [];
            foreach ($allRatings as $r) {
                if (!isset($performance[$r['question_title']])) { $performance[$r['question_title']] = ['total' => 0, 'count' => 0]; }
                $performance[$r['question_title']]['total'] += $r['rating'];
                $performance[$r['question_title']]['count']++;
            }
            foreach ($performance as $title => $values) {
                if ($title !== 'Suggestions') { // Exclude 'Suggestions' from performance metrics
                    $data['service_performance'][] = ['label' => $title, 'value' => round($values['total'] / $values['count'], 1)];
                }
            }

            // Satisfaction Trends Calculation
            $trends = [];
            foreach ($allRatings as $r) {
                if (!isset($trends[$r['submission_date']])) { $trends[$r['submission_date']] = ['total' => 0, 'count' => 0]; }
                $trends[$r['submission_date']]['total'] += $r['rating'];
                $trends[$r['submission_date']]['count']++;
            }
            ksort($trends);
            foreach($trends as $date => $values) {
                $data['trends_labels'][] = $date;
                $data['trends_data'][] = round($values['total'] / $values['count'], 1);
            }
        }

        // --- Step 5: AI Integration ---
        if (!empty($suggestionsForAI)) {
            $ai_api_url = 'http://127.0.0.1:8000/analyze';
            $postData = json_encode(['texts' => $suggestionsForAI]);
            $ch = curl_init($ai_api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5-minute timeout

            $aiResponse = curl_exec($ch);
            curl_close($ch);

            if ($aiResponse) {
                $aiData = json_decode($aiResponse, true);
                if (isset($aiData['summary']['sentiment_counts'])) {
                    $sentimentCounts = [];
                    foreach ($aiData['summary']['sentiment_counts'] as $label => $count) { $sentimentCounts[ucfirst(strtolower($label))] = $count; }
                    $data['sentiment_analysis'] = array_merge($data['sentiment_analysis'], $sentimentCounts);
                }
                if (isset($aiData['common_concerns'])) {
                    $data['common_concerns'] = $aiData['common_concerns'];
                }
            }
        }
    }
    
    // Step 6: Finalize and Respond
    $data['startDate'] = $startDate;
    $data['endDate'] = $endDate;
    respond(true, "Dashboard data retrieved successfully.", $data);

} catch (Exception $e) {
    respond(false, "An unexpected error occurred: " . $e->getMessage(), null, 500);
}
?>