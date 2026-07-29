<?php

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

//Send one predictable JSON response to the analytics client.
function impactRespond(bool $success, array $data = [], string $message = ''): void
{
    http_response_code($success ? 200 : 400);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

//Return a safe SQL date condition for the selected reporting period.
function impactTimeCondition(string $range, string $column): string
{
    $conditions = [
        'all' => '',
        'year' => " AND {$column} >= DATE_SUB(NOW(), INTERVAL 12 MONTH)",
        'quarter' => " AND {$column} >= DATE_SUB(NOW(), INTERVAL 3 MONTH)",
        'month' => " AND {$column} >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
    ];

    return $conditions[$range] ?? $conditions['all'];
}

//Add the optional guardian-country condition and its bound value.
function impactCountryFilter(string $country): array
{
    if ($country === 'all') {
        return ['', []];
    }

    return [
        " AND COALESCE(NULLIF(TRIM(u.country), ''), 'Unknown') = :country",
        [':country' => $country]
    ];
}

function impactGrowthWindow(string $range): array
{
    $windows = [
        'all' => ['30 DAY', '60 DAY'],
        'month' => ['30 DAY', '60 DAY'],
        'quarter' => ['3 MONTH', '6 MONTH'],
        'year' => ['12 MONTH', '24 MONTH']
    ];

    return $windows[$range] ?? $windows['all'];
}

function impactFetchRow(PDO $pdo, string $sql, array $params = []): array
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    return $statement->fetch() ?: [];
}

function impactFetchAll(PDO $pdo, string $sql, array $params = []): array
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    return $statement->fetchAll();
}

function impactPercent(int $value, int $total): float
{
    return $total > 0 ? round(($value / $total) * 100, 1) : 0.0;
}

/**
 * Build the complete dashboard payload. Each table is aggregated before it is
 * returned, keeping both the database work and the JSON response small.
 */
function getImpactAnalytics(PDO $pdo, string $country, string $range): array
{
    [$countrySql, $countryParams] = impactCountryFilter($country);

    $guardianRow = impactFetchRow(
        $pdo,
        "SELECT COUNT(*) AS total
         FROM users u
         WHERE 1 = 1" .
        $countrySql .
        impactTimeCondition($range, 'u.created_at'),
        $countryParams
    );

    $childrenRow = impactFetchRow(
        $pdo,
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN c.dob <= CURDATE() THEN 1 ELSE 0 END) AS born_total,
            SUM(CASE WHEN c.dob > CURDATE() THEN 1 ELSE 0 END) AS expected_total
         FROM children c
         INNER JOIN users u ON u.user_id = c.user_id
         WHERE 1 = 1" .
        $countrySql .
        impactTimeCondition($range, 'c.created_at'),
        $countryParams
    );

    $assessmentRow = impactFetchRow(
        $pdo,
        "SELECT
            COUNT(*) AS total,
            COUNT(a.satisfaction_rating) AS rating_total,
            COALESCE(AVG(a.satisfaction_rating), 0) AS average_rating,
            SUM(CASE WHEN a.satisfaction_rating >= 4 THEN 1 ELSE 0 END) AS positive_ratings,
            SUM(CASE WHEN a.severity = 'Low' THEN 1 ELSE 0 END) AS low_total,
            SUM(CASE WHEN a.severity = 'Medium' THEN 1 ELSE 0 END) AS medium_total,
            SUM(CASE WHEN a.severity = 'High' THEN 1 ELSE 0 END) AS high_total,
            SUM(CASE WHEN a.satisfaction_rating = 1 THEN 1 ELSE 0 END) AS rating_1,
            SUM(CASE WHEN a.satisfaction_rating = 2 THEN 1 ELSE 0 END) AS rating_2,
            SUM(CASE WHEN a.satisfaction_rating = 3 THEN 1 ELSE 0 END) AS rating_3,
            SUM(CASE WHEN a.satisfaction_rating = 4 THEN 1 ELSE 0 END) AS rating_4,
            SUM(CASE WHEN a.satisfaction_rating = 5 THEN 1 ELSE 0 END) AS rating_5
         FROM assessments a
         INNER JOIN children c ON c.child_id = a.child_id
         INNER JOIN users u ON u.user_id = c.user_id
         WHERE 1 = 1" .
        $countrySql .
        impactTimeCondition($range, 'a.created_at'),
        $countryParams
    );

    $ageRows = impactFetchAll(
        $pdo,
        "SELECT TIMESTAMPDIFF(YEAR, c.dob, CURDATE()) AS age, COUNT(*) AS total
         FROM children c
         INNER JOIN users u ON u.user_id = c.user_id
         WHERE c.dob <= CURDATE()
           AND TIMESTAMPDIFF(YEAR, c.dob, CURDATE()) BETWEEN 0 AND 12" .
        $countrySql .
        impactTimeCondition($range, 'c.created_at') .
        " GROUP BY age
          ORDER BY age",
        $countryParams
    );

    // Compare the selected period with the equally sized period before it.
    [$currentGrowthWindow, $previousGrowthWindow] =
        impactGrowthWindow($range);
    $growthRow = impactFetchRow(
        $pdo,
        "SELECT
            SUM(CASE WHEN u.created_at >= DATE_SUB(NOW(), INTERVAL {$currentGrowthWindow}) THEN 1 ELSE 0 END) AS current_total,
            SUM(CASE
                WHEN u.created_at >= DATE_SUB(NOW(), INTERVAL {$previousGrowthWindow})
                 AND u.created_at < DATE_SUB(NOW(), INTERVAL {$currentGrowthWindow})
                THEN 1 ELSE 0
            END) AS previous_total
         FROM users u
         WHERE 1 = 1" . $countrySql,
        $countryParams
    );

    $countryRows = impactFetchAll(
        $pdo,
        "SELECT
            COALESCE(NULLIF(TRIM(u.country), ''), 'Unknown') AS country,
            COUNT(*) AS guardians
         FROM users u
         WHERE 1 = 1" .
        $countrySql .
        impactTimeCondition($range, 'u.created_at') .
        " GROUP BY COALESCE(NULLIF(TRIM(u.country), ''), 'Unknown')
          ORDER BY guardians DESC, country ASC",
        $countryParams
    );

    $guardians = (int) ($guardianRow['total'] ?? 0);
    $children = (int) ($childrenRow['total'] ?? 0);
    $bornChildren = (int) ($childrenRow['born_total'] ?? 0);
    $expectedProfiles = (int) ($childrenRow['expected_total'] ?? 0);
    $assessments = (int) ($assessmentRow['total'] ?? 0);
    $ratingTotal = (int) ($assessmentRow['rating_total'] ?? 0);
    $positiveRatings = (int) ($assessmentRow['positive_ratings'] ?? 0);

    $ages = array_fill(0, 13, 0);
    foreach ($ageRows as $row) {
        $age = (int) $row['age'];
        if ($age >= 0 && $age <= 12) {
            $ages[$age] = (int) $row['total'];
        }
    }

    $severity = [
        'Low' => (int) ($assessmentRow['low_total'] ?? 0),
        'Medium' => (int) ($assessmentRow['medium_total'] ?? 0),
        'High' => (int) ($assessmentRow['high_total'] ?? 0)
    ];

    $ratings = [];
    for ($rating = 1; $rating <= 5; $rating++) {
        $ratings[$rating] = (int) ($assessmentRow["rating_{$rating}"] ?? 0);
    }

    $currentGuardians = (int) ($growthRow['current_total'] ?? 0);
    $previousGuardians = (int) ($growthRow['previous_total'] ?? 0);
    $guardianGrowth = $previousGuardians > 0
        ? round((($currentGuardians - $previousGuardians) / $previousGuardians) * 100, 1)
        : ($currentGuardians > 0 ? 100.0 : 0.0);

    $countryData = [];
    foreach ($countryRows as $row) {
        $count = (int) $row['guardians'];
        $countryData[] = [
            'country' => $row['country'],
            'guardians' => $count,
            'share' => impactPercent($count, $guardians)
        ];
    }

    $ageTotal = array_sum($ages);
    $underSix = array_sum(array_slice($ages, 0, 6));
    $severityTotal = array_sum($severity);

    return [
        'synced_at' => date(DATE_ATOM),
        'filters' => [
            'country' => $country,
            'range' => $range
        ],
        'kpis' => [
            'guardians' => $guardians,
            'children' => $children,
            'born_children' => $bornChildren,
            'expected_profiles' => $expectedProfiles,
            'assessments' => $assessments,
            'average_rating' => round((float) ($assessmentRow['average_rating'] ?? 0), 2),
            'rating_total' => $ratingTotal,
            'positive_percentage' => impactPercent($positiveRatings, $ratingTotal),
            'children_per_guardian' => $guardians > 0
                ? round($children / $guardians, 2)
                : 0,
            'guardian_growth' => $guardianGrowth
        ],
        'age_distribution' => [
            'counts' => array_values($ages),
            'under_six_percentage' => impactPercent($underSix, $ageTotal),
            'six_to_twelve_percentage' => impactPercent($ageTotal - $underSix, $ageTotal)
        ],
        'severity' => [
            'counts' => array_values($severity),
            'percentages' => [
                impactPercent($severity['Low'], $severityTotal),
                impactPercent($severity['Medium'], $severityTotal),
                impactPercent($severity['High'], $severityTotal)
            ]
        ],
        'ratings' => [
            'counts' => array_values($ratings)
        ],
        'countries' => $countryData
    ];
}

try {
    $action = $_GET['action'] ?? 'analytics';
    if ($action !== 'analytics') {
        impactRespond(false, [], 'Unsupported analytics action.');
    }

    $range = $_GET['range'] ?? 'all';
    if (!in_array($range, ['all', 'year', 'quarter', 'month'], true)) {
        $range = 'all';
    }

    $country = trim((string) ($_GET['country'] ?? 'all'));
    if ($country === '' || strlen($country) > 100) {
        $country = 'all';
    }

    impactRespond(true, getImpactAnalytics($pdo, $country, $range));
} catch (Throwable $error) {
    error_log('Impact analytics error: ' . $error->getMessage());
    impactRespond(false, [], 'Impact analytics could not be loaded.');
}
