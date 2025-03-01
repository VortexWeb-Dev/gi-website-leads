<?php

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Authorization, Content-Type");
    http_response_code(200);
    exit;
}
?>

<?php
require_once __DIR__ . "/crest/crest.php";
require_once __DIR__ . "/utils.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(405, ['status' => 'error', 'message' => 'Method Not Allowed. Use POST.']);
    exit;
}

$postData = json_decode(file_get_contents("php://input"), true);

if (!$postData) {
    sendResponse(400, ['status' => 'error', 'message' => 'Invalid JSON payload.']);
    exit;
}

logData("Received data: " . json_encode($postData, JSON_PRETTY_PRINT), "data.log");

/*

if (empty($postData['name']) || empty($postData['email']) || empty($postData['phone'])) {
    sendResponse(400, ['status' => 'error', 'message' => 'Missing required fields (name, email, phone).']);
    exit;
}

$form_id = htmlspecialchars($postData['form_id'] ?? '');
$name = htmlspecialchars($postData['name']);
$email = filter_var($postData['email'], FILTER_SANITIZE_EMAIL);
$code = htmlspecialchars($postData['Code'] ?? '');
$phone = htmlspecialchars($postData['phone']);
$comments = $postData['comments'] ?? '';

$nameParts = getNames($name);

$assigned_by_id = $form_id === 'XMEDIALAB' || $form_id === 'XMEDIALAB-LP' ? 58 : 10;

$fields = [
    'TITLE' => $name,
    'NAME' => $nameParts['firstName'],
    'SECOND_NAME' => $nameParts['secondName'],
    'LAST_NAME' => $nameParts['lastName'],
    'EMAIL' => [
        [
            'VALUE' => $email,
            'VALUE_TYPE' => 'WORK',
            'TYPE_ID' => 'EMAIL'
        ],
    ],
    'PHONE' => [
        [
            'VALUE' => "+" . $code . $phone,
            'VALUE_TYPE' => 'WORK',
            'TYPE_ID' => 'PHONE'
        ]
    ],
    'UF_CRM_1733723186' => $comments,
    'UF_CRM_1733300250545' => $form_id,
    'UF_CRM_1733720194' => '186',
    'ASSIGNED_BY_ID' => $assigned_by_id
];

logData("Fields to be sent to Bitrix: " . json_encode($fields, JSON_PRETTY_PRINT), "fields.log");


$response = CRest::call('crm.lead.add', [
    'fields' => $fields
]);
logData("Response: " . json_encode($response, JSON_PRETTY_PRINT), "response.log");

if (isset($response['error'])) {
    logData("Error: " . json_encode($response['error'], JSON_PRETTY_PRINT), "error.log");
    sendResponse(500, [
        'status' => 'error',
        'message' => 'Data received but failed to create lead.',
        'details' => $response['error'],
    ]);
    exit;
}

sendResponse(200, ['status' => 'success', 'message' => 'Data received and lead created successfully.']);

?>

*/