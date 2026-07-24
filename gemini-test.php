<?php

require_once 'gemini.php';

$result = callGemini(
    'Return valid JSON with one field called message and the value Hello CareNest.'
);

header('Content-Type: application/json');
echo json_encode($result);

?>
