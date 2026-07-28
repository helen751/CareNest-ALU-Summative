<?php

require_once 'config.php';
require_once 'gemini.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        registerUser($pdo);
        break;

    case 'login':
        loginUser($pdo);
        break;

    case 'get_dashboard':
        getDashboardData($pdo);
        break;

    case 'add_child':
        addChild($pdo);
        break;

    case 'update_child':
        updateChild($pdo);
        break;
    
    case 'generate_questions':
        generateQuestions($pdo);
        break;

    case 'analyse_assessment':
        analyseAssessment($pdo);
        break;

    case 'add_assessment':
        addAssessment($pdo);
        break;

    case 'add_appointment':
        addAppointment($pdo);
        break;

    case 'update_profile':
        updateProfile($pdo);
        break;
    
    case 'add_assessment_rating':
        addAssessmentRating($pdo);
        break;

    case 'logout':
        logoutUser();
        break;

    default:
        respond(false, 'Invalid action.');
}

function respond($success, $message, $data = [])
{
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));

    exit;
}

function registerUser($pdo)
{
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $country = trim($_POST['country'] ?? 'Unknown');

    try {
        $check = $pdo->prepare(
            'SELECT user_id FROM users WHERE email = ?'
        );
        $check->execute([$email]);

        if ($check->fetch()) {
            respond(false, 'An account already exists with this email.');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'INSERT INTO users (full_name, email, password_hash, country)
             VALUES (?, ?, ?, ?)'
        );

        $stmt->execute([$fullName, $email, $passwordHash, $country]);

        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['user_name'] = $fullName;

        respond(true, 'Account created successfully.');
    } catch (PDOException $e) {
        respond(false, 'Registration failed.');
    }
}


// code for processing user login
function loginUser($pdo)
{
    try {
        $stmt = $pdo->prepare(
            "SELECT user_id, full_name, password_hash
             FROM users
             WHERE email = ?"
        );

        $stmt->execute([trim($_POST['email'])]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($_POST['password'], $user['password_hash'])) {
            respond(false, 'Email or password is incorrect.');
        }

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['full_name'];

        respond(true, 'Login successful.', [
            'name' => $user['full_name']
        ]);
    } catch (PDOException $e) {
        respond(false, 'Login could not be completed.');
    }
}

function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        respond(false, 'Please log in first.');
    }

    return $_SESSION['user_id'];
}


function getDashboardData($pdo)
{
    $userId = requireLogin();

    try {
        $userQuery = $pdo->prepare(
            'SELECT user_id, full_name, email, country, created_at
             FROM users
             WHERE user_id = ?'
        );

        $userQuery->execute([$userId]);
        $user = $userQuery->fetch();

        $childrenQuery = $pdo->prepare(
            'SELECT
                child_id,
                user_id,
                child_name AS name,
                dob,
                gender,
                previous_diagnoses
             FROM children
             WHERE user_id = ?
             ORDER BY created_at DESC'
        );

        $childrenQuery->execute([$userId]);
        $children = $childrenQuery->fetchAll();

        $assessmentQuery = $pdo->prepare(
            'SELECT
                a.assessment_id,
                a.child_id,
                DATE(a.created_at) AS date,
                a.severity,
                a.recommendation,
                a.symptoms_json,
                a.satisfaction_rating
             FROM assessments a
             INNER JOIN children c ON c.child_id = a.child_id
             WHERE c.user_id = ?
             ORDER BY a.created_at ASC'
        );

        $assessmentQuery->execute([$userId]);
        $assessments = $assessmentQuery->fetchAll();

        $appointmentQuery = $pdo->prepare(
            'SELECT
                ap.appointment_id,
                ap.assessment_id,
                a.child_id,
                ap.appointment_date,
                TIME_FORMAT(ap.appointment_time, "%H:%i") AS appointment_time,
                ap.status
             FROM appointments ap
             INNER JOIN assessments a
                ON a.assessment_id = ap.assessment_id
             INNER JOIN children c
                ON c.child_id = a.child_id
             WHERE c.user_id = ?
             ORDER BY ap.appointment_date ASC, ap.appointment_time ASC'
        );

        $appointmentQuery->execute([$userId]);
        $appointments = $appointmentQuery->fetchAll();

        respond(true, 'Dashboard data loaded.', [
            'user' => $user,
            'children' => $children,
            'assessments' => $assessments,
            'appointments' => $appointments
        ]);
    } catch (PDOException $e) {
        respond(false, $e->getMessage());
    }
}


function addCalendarMonthsClamped(DateTimeImmutable $date, int $months)
{
    $day = (int) $date->format('d');
    $modifier = ($months >= 0 ? '+' : '') . $months . ' months';
    $targetMonth = $date
        ->modify('first day of this month')
        ->modify($modifier);

    return $targetMonth->setDate(
        (int) $targetMonth->format('Y'),
        (int) $targetMonth->format('m'),
        min($day, (int) $targetMonth->format('t'))
    );
}

function validateChildProfileDate($dateString)
{
    $profileDate = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        trim((string) $dateString)
    );
    $dateErrors = DateTimeImmutable::getLastErrors();

    if (
        !$profileDate ||
        ($dateErrors !== false &&
            ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) ||
        $profileDate->format('Y-m-d') !== trim((string) $dateString)
    ) {
        return [
            'valid' => false,
            'message' => 'Please enter a valid date.'
        ];
    }

    $today = new DateTimeImmutable('today');

    if ($profileDate > $today) {
        if ($profileDate > addCalendarMonthsClamped($today, 9)) {
            return [
                'valid' => false,
                'message' => 'The expected delivery date cannot be more than nine calendar months from today.'
            ];
        }

        return [
            'valid' => true,
            'is_pregnancy' => true,
            'date' => $profileDate
        ];
    }

    if ($profileDate < $today->modify('-12 years')) {
        return [
            'valid' => false,
            'message' => 'Born children must be 12 years old or younger.'
        ];
    }

    return [
        'valid' => true,
        'is_pregnancy' => false,
        'date' => $profileDate
    ];
}

function addChild($pdo)
{
    $userId = requireLogin();
    $dateValidation = validateChildProfileDate($_POST['dob'] ?? '');

    if (!$dateValidation['valid']) {
        respond(false, $dateValidation['message']);
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO children
             (user_id, child_name, dob, gender, previous_diagnoses)
             VALUES (?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $userId,
            $_POST['child_name'],
            $_POST['dob'],
            $_POST['gender'],
            $_POST['previous_diagnoses'] ?: null
        ]);

        respond(true, 'Child profile added successfully.');
    } catch (PDOException $e) {
        respond(false, 'Could not save the child profile.');
    }
}


function updateChild($pdo)
{
    $userId = requireLogin();
    $dateValidation = validateChildProfileDate($_POST['dob'] ?? '');

    if (!$dateValidation['valid']) {
        respond(false, $dateValidation['message']);
    }

    try {
        $stmt = $pdo->prepare(
            'UPDATE children
             SET child_name = ?,
                 dob = ?,
                 gender = ?,
                 previous_diagnoses = ?
             WHERE child_id = ? AND user_id = ?'
        );

        $stmt->execute([
            $_POST['child_name'],
            $_POST['dob'],
            $_POST['gender'],
            $_POST['previous_diagnoses'] ?: null,
            $_POST['child_id'],
            $userId
        ]);

        respond(true, 'Child profile updated successfully.');
    } catch (PDOException $e) {
        respond(false, 'Could not update the child profile.');
    }
}


function addAssessment($pdo){
    $userId = requireLogin();
    $childId = $_POST['child_id'];

    try {
        $childQuery = $pdo->prepare(
            'SELECT child_id
             FROM children
             WHERE child_id = ? AND user_id = ?'
        );

        $childQuery->execute([$childId, $userId]);

        if (!$childQuery->fetch()) {
            respond(false, 'Child profile not found.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO assessments
             (child_id, symptoms_json, severity, recommendation)
             VALUES (?, ?, ?, ?)'
        );

        $stmt->execute([
            $childId,
            $_POST['symptoms_json'],
            $_POST['severity'],
            $_POST['recommendation']
        ]);

        respond(true, 'Assessment saved successfully.', [
            'assessment_id' => $pdo->lastInsertId()
        ]);
    } catch (PDOException $e) {
        respond(false, 'Could not save the assessment.');
    }
}


function addAppointment($pdo){
    $userId = requireLogin();
    $assessmentId = $_POST['assessment_id'];

    try {
        $check = $pdo->prepare(
            'SELECT a.assessment_id
             FROM assessments a
             INNER JOIN children c ON c.child_id = a.child_id
             WHERE a.assessment_id = ? AND c.user_id = ?'
        );

        $check->execute([$assessmentId, $userId]);

        if (!$check->fetch()) {
            respond(false, 'Assessment record not found.');
        }
        if( strtotime($_POST['appointment_date']) < strtotime(date('Y-m-d')) || (strtotime($_POST['appointment_date']) == strtotime(date('Y-m-d')) && strtotime($_POST['appointment_time']) < strtotime(date('H:i'))) ){
            respond(false, 'Appointment date cannot be in the past.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO appointments
             (assessment_id, appointment_date, appointment_time)
             VALUES (?, ?, ?)'
        );

        $stmt->execute([
            $assessmentId,
            $_POST['appointment_date'],
            $_POST['appointment_time']
        ]);

        respond(true, 'Appointment booked successfully.');
    } catch (PDOException $e) {
        respond(false, 'Could not book the appointment.');
    }
}


function updateProfile($pdo){
    $userId = requireLogin();

    $fullName = trim($_POST['full_name']);
    $country = trim($_POST['country']);
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    try {
        if ($newPassword !== '') {
            if (strlen($newPassword) < 6) {
                respond(false, 'New password must be at least 6 characters.');
            }

            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user) {
                respond(false, 'User not found.');
            }

            if (!password_verify($currentPassword, $user['password_hash'])) {
                respond(false, 'Current password is incorrect.');
            }

            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, country = ?, password_hash = ? WHERE user_id = ?");
            $stmt->execute([$fullName, $country, $newHash, $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, country = ? WHERE user_id = ?");
            $stmt->execute([$fullName, $country, $userId]);
        }

        $_SESSION['user_name'] = $fullName;
        respond(true, 'Settings updated successfully.');
    } catch (PDOException $e) {
        respond(false, 'Could not update settings.');
    }
}

// gemini question generation function
function generateQuestions($pdo){
    $userId = requireLogin();
    $childId = $_POST['child_id'];

    $stmt = $pdo->prepare(
        'SELECT child_name, dob, gender, previous_diagnoses
         FROM children
         WHERE child_id = ? AND user_id = ?'
    );

    $stmt->execute([$childId, $userId]);
    $child = $stmt->fetch();

    if (!$child) {
        respond(false, 'Child profile not found.');
    }

    $birthDate = new DateTimeImmutable($child['dob']);
    $today = new DateTimeImmutable('today');
    $isPregnancy = $birthDate > $today;
    $ageInDays = $isPregnancy ? null : $birthDate->diff($today)->days;

    if ($isPregnancy) {
        $estimatedPregnancyStart = addCalendarMonthsClamped($birthDate, -9);
        $estimatedGestationDays = $today > $estimatedPregnancyStart
            ? $estimatedPregnancyStart->diff($today)->days
            : 0;
        $estimatedGestationWeeks = intdiv($estimatedGestationDays, 7);

        $prompt = "
Create 6 simple pregnancy health assessment questions.

The selected profile represents an unborn baby.
The stored date {$child['dob']} is the expected delivery date.
Estimated pregnancy stage: approximately {$estimatedGestationWeeks} weeks.
Previous maternal conditions or notes: {$child['previous_diagnoses']}

CareNest supports pregnancy profiles, allowing expecting mothers to record their daily symptoms and receive pregnancy-aware health guidance.

The questions must:
- assess the pregnant mother's current symptoms, comfort, warning signs, and wellbeing;
- use simple English;
- ask about relevant concerns such as pain, bleeding, dizziness, fever,
  vomiting, swelling, breathing difficulty, severe headache, and other urgent warning signs;
- ask about reduced fetal movement only when relevant to the estimated pregnancy stage;
- not ask normal child-health questions intended for a born child;
- not diagnose the mother or unborn baby;
- make clear that CareNest guidance does not replace antenatal care or a medical professional;
- provide 3 or 4 short answer choices.

Return only valid JSON using this structure:

{
  \"questions\": [
    {
      \"key\": \"bleeding\",
      \"question\": \"Are you experiencing any bleeding?\",
      \"options\": [\"No\", \"A little\", \"Heavy bleeding\"]
    }
  ]
}
";
    } else {
        $prompt = "
Create 6 simple child health assessment questions.

Child age: {$ageInDays} days old
Gender: {$child['gender']}
Previous diagnoses: {$child['previous_diagnoses']}

The questions must:
- be suitable for the child's age;
- use simple English for a mother or guardian;
- focus on current symptoms;
- cover fever, breathing, activity, feeding or hydration,
  pain, rash, vomiting, and important warning signs;
- not diagnose the child;
- provide 3 or 4 short answer choices.

Return only valid JSON using this structure:

{
  \"questions\": [
    {
      \"key\": \"fever\",
      \"question\": \"Does the child have a fever?\",
      \"options\": [\"No\", \"Mild\", \"High\"]
    }
  ]
}
";
    }

    $result = callGemini($prompt);

    if (
        empty($result['success']) ||
        empty($result['data']['questions'])
    ) {
        respond(
            false,
            $result['error'] ?? 'Could not generate the questions.'
        );
    }

    respond(true, 'Questions generated.', [
        'questions' => $result['data']['questions']
    ]);
}

function analyseAssessment($pdo)
{
    $userId = requireLogin();

    $childId = $_POST['child_id'] ?? '';
    $questionsJson = $_POST['questions_json'] ?? '[]';
    $answersJson = $_POST['answers_json'] ?? '[]';

    $questions = json_decode($questionsJson, true);
    $answers = json_decode($answersJson, true);

    if (
        !$childId ||
        !is_array($questions) ||
        !is_array($answers) ||
        empty($questions) ||
        empty($answers)
    ) {
        respond(false, 'Invalid assessment information.');
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT child_name, dob, gender, previous_diagnoses
             FROM children
             WHERE child_id = ? AND user_id = ?'
        );

        $stmt->execute([$childId, $userId]);
        $child = $stmt->fetch();

        if (!$child) {
            respond(false, 'Child profile not found.');
        }

        // Determine whether the stored date is a birth date or expected delivery date.
        $birthDate = new DateTimeImmutable($child['dob']);
        $today = new DateTimeImmutable('today');
        $isPregnancy = $birthDate > $today;
        $ageInDays = $isPregnancy ? null : $birthDate->diff($today)->days;

        // Combine each question with its selected answer.
        $assessmentDetails = '';

        foreach ($questions as $index => $question) {
            $questionText = $question['question'] ?? 'Question';

            $answerText =
                $answers[$index]['answer']
                ?? $answers[$index]
                ?? 'No answer';

            $assessmentDetails .=
                ($index + 1) . '. Question: ' . $questionText . "\n" .
                'Answer: ' . $answerText . "\n\n";
        }

        $previousDiagnoses = trim(
            $child['previous_diagnoses'] ?? ''
        );

        if ($previousDiagnoses === '') {
            $previousDiagnoses = 'No previous diagnoses provided';
        }

        if ($isPregnancy) {
            $estimatedPregnancyStart = addCalendarMonthsClamped($birthDate, -9);
            $estimatedGestationDays = $today > $estimatedPregnancyStart
                ? $estimatedPregnancyStart->diff($today)->days
                : 0;
            $estimatedGestationWeeks = intdiv($estimatedGestationDays, 7);

            $prompt = "
You are assisting with a pregnancy health assessment.

The selected profile represents an unborn baby.
The stored date {$child['dob']} is the expected delivery date.
Estimated pregnancy stage: approximately {$estimatedGestationWeeks} weeks.
The assessment responses describe the pregnant mother's current condition, not symptoms of the unborn baby.
Previous maternal conditions or notes: {$previousDiagnoses}

Assessment responses:
{$assessmentDetails}

Classify the assessment using only one of these severity levels:

Low:
No serious warning signs are reported, but routine antenatal care should continue.

Medium:
The pregnant mother should contact an antenatal or healthcare professional promptly.

High:
The pregnant mother may need urgent medical attention or emergency care.

Important rules:
- Do not diagnose the mother or unborn baby.
- Assess the mother's pain, bleeding, dizziness, fever, vomiting, swelling,
  breathing difficulty, severe headache, reduced fetal movement when relevant,
  and any other reported warning signs.
- Recommend urgent professional medical care when serious warning signs are reported.
- Make clear that CareNest provides guidance and does not replace antenatal care or a medical professional.
- Do not describe the result as a diagnosis of the unborn baby.
- Keep the recommendation under 120 words.
- Return only valid JSON.
- Do not include Markdown or code fences.

Return exactly this JSON structure:

{
  \"severity\": \"Low\",
  \"recommendation\": \"Clear pregnancy-aware guidance for the expecting mother.\"
}
";
        } else {
            $prompt = "
You are assisting with a basic child health assessment.

Child information:
Name: {$child['child_name']}
Age: {$ageInDays} days old
Gender: {$child['gender']}
Previous diagnoses: {$previousDiagnoses}

Interpret the child's developmental stage from the age in days.

Assessment responses:
{$assessmentDetails}

Classify the assessment using only one of these severity levels:

Low:
The symptoms appear mild and may be monitored with basic care.

Medium:
The child should have a non-emergency appointment with a healthcare professional.

High:
The child may need urgent medical attention or emergency care.

Important rules:
- Do not diagnose a medical condition.
- Consider the child's age, previous diagnoses and all answers.
- Give clear and simple guidance to the mother or guardian.
- Clearly state when urgent medical care is needed.
- Keep the recommendation under 120 words.
- Return only valid JSON.
- Do not include Markdown or code fences.

Return exactly this JSON structure:

{
  \"severity\": \"Low\",
  \"recommendation\": \"Clear guidance for the guardian.\"
}
";
        }

        $result = callGemini($prompt);

        if (
            empty($result['success']) ||
            empty($result['data']['severity']) ||
            empty($result['data']['recommendation'])
        ) {
            respond(
                false,
                $result['error']
                    ?? 'Could not analyse the assessment.'
            );
        }

        $severity = ucfirst(
            strtolower(trim($result['data']['severity']))
        );

        $allowedSeverities = [
            'Low',
            'Medium',
            'High'
        ];

        if (!in_array($severity, $allowedSeverities, true)) {
            respond(false, 'Gemini returned an invalid severity.');
        }

        $recommendation = trim(
            $result['data']['recommendation']
        );

        // Store the questions and answers together.
        $symptomsData = [
            'questions' => $questions,
            'answers' => $answers
        ];

        $insert = $pdo->prepare(
            'INSERT INTO assessments
             (child_id, symptoms_json, severity, recommendation)
             VALUES (?, ?, ?, ?)'
        );

        $insert->execute([
            $childId,
            json_encode(
                $symptomsData,
                JSON_UNESCAPED_UNICODE
            ),
            $severity,
            $recommendation
        ]);

        respond(true, 'Assessment completed successfully.', [
            'assessment_id' => $pdo->lastInsertId(),
            'severity' => $severity,
            'recommendation' => $recommendation,
            'age_in_days' => $ageInDays
        ]);
    } catch (PDOException $e) {
        respond(false, 'Could not save the assessment.');
    } catch (Exception $e) {
        respond(false, 'Could not complete the assessment.');
    }
}

function addAssessmentRating($pdo)
{
    $userId = requireLogin();

    $assessmentId = $_POST['assessment_id'] ?? '';
    $rating = $_POST['satisfaction_rating'] ?? '';

    if (!$assessmentId || !$rating) {
        respond(false, 'Missing assessment or rating.');
    }

    if ($rating < 1 || $rating > 5) {
        respond(false, 'Invalid rating.');
    }

    try {
        $check = $pdo->prepare(
            "SELECT a.assessment_id
             FROM assessments a
             INNER JOIN children c ON c.child_id = a.child_id
             WHERE a.assessment_id = ? AND c.user_id = ?"
        );

        $check->execute([$assessmentId, $userId]);

        if (!$check->fetch()) {
            respond(false, 'Assessment not found.');
        }

        $stmt = $pdo->prepare(
            "UPDATE assessments
             SET satisfaction_rating = ?
             WHERE assessment_id = ?"
        );

        $stmt->execute([$rating, $assessmentId]);

        respond(true, 'Rating saved successfully.');
    } catch (PDOException $e) {
        respond(false, 'Could not save rating.');
    }
}


function logoutUser()
{
    session_unset();
    session_destroy();

    respond(true, 'Logged out successfully.');
}
