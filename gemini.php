<?php
require_once 'gemini-config.php';

function callGemini($prompt)
{
    global $geminiKey, $geminiModel;

    $url = "https://generativelanguage.googleapis.com/v1beta/models/"
         . $geminiModel
         . ":generateContent?key="
         . $geminiKey;

    $requestBody = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'responseMimeType' => 'application/json'
        ]
    ];

    $curl = curl_init($url);

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($requestBody)
    ]);

    $response = curl_exec($curl);
    $curlError = curl_error($curl);
    $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    if ($curlError) {
        return [
            'success' => false,
            'error' => 'cURL error: ' . $curlError
        ];
    }

    $responseData = json_decode($response, true);

    if ($statusCode !== 200) {
        return [
            'success' => false,
            'http_status' => $statusCode,
            'error' => $responseData['error']['message'] ?? 'Gemini request failed.',
            'raw_response' => $responseData
        ];
    }

    $text = $responseData['candidates'][0]['content']['parts'][0]['text']
        ?? null;

    if (!$text) {
        return [
            'success' => false,
            'error' => 'Gemini returned no text.',
            'finish_reason' =>
                $responseData['candidates'][0]['finishReason'] ?? null,
            'prompt_feedback' =>
                $responseData['promptFeedback'] ?? null,
            'raw_response' => $responseData
        ];
    }

    $decodedText = json_decode($text, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'error' => 'Gemini response was not valid JSON: '
                . json_last_error_msg(),
            'model_text' => $text
        ];
    }

    return [
        'success' => true,
        'data' => $decodedText
    ];
}