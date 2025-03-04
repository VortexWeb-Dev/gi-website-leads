<?php

require_once __DIR__ . "/crest/crest.php";
require_once __DIR__ . "/utils.php";

define('DEFAULT_RESPONSIBLE_PERSON', 1721);
define('PRIMARY_CATEGORY_ID', 20);
define('SECONDARY_CATEGORY_ID', 24);

// Handle CORS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Authorization, Content-Type");
    http_response_code(200);
    exit;
}

// Ensure request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(405, ['status' => 'error', 'message' => 'Method Not Allowed. Use POST.']);
    exit;
}

// Get and decode JSON data
$postData = json_decode(file_get_contents("php://input"), true);

if (!$postData) {
    sendResponse(400, ['status' => 'error', 'message' => 'Invalid JSON payload.']);
    exit;
}

// Validate required fields
$requiredFields = ['name', 'email', 'phone'];
foreach ($requiredFields as $field) {
    if (empty($postData[$field])) {
        sendResponse(400, ['status' => 'error', 'message' => "Missing required field: $field."]);
        exit;
    }
}

// Sanitize inputs
$title   = htmlspecialchars($postData['title'] ?? '');
$message = htmlspecialchars($postData['message'] ?? '');
$name    = htmlspecialchars($postData['name']);
$email   = filter_var($postData['email'], FILTER_SANITIZE_EMAIL);
$phone   = htmlspecialchars($postData['phone']);
$topic   = htmlspecialchars($postData['topic'] ?? '');
$type    = htmlspecialchars($postData['type'] === 'primary' ? 5479 : 5480);
$reference = htmlspecialchars($postData['reference'] ?? '');

if (!empty($reference)) {
    $property = getProperty($reference);
    $property_link = getPropertyLink($property['ufCrm37TitleEn']);

    $owner_name = $property['ufCrm37ListingOwner'] ?? null;

    if ($owner_name) {
        $nameParts = explode(' ', trim($owner_name), 2);

        $firstName = $nameParts[0] ?? null;
        $lastName = $nameParts[1] ?? null;

        $owner_id = getUserId([
            '%NAME' => $firstName,
            '%LAST_NAME' => $lastName,
            '!ID' => [3, 268]
        ]);
    }

    $agent_name = $property['ufCrm37AgentName'] ?? null;
    if ($agent_name) {
        $nameParts = explode(' ', trim($agent_name), 2);

        $firstName = $nameParts[0] ?? null;
        $lastName = $nameParts[1] ?? null;

        $agent_id = getUserId([
            '%NAME' => $firstName,
            '%LAST_NAME' => $lastName,
            '!ID' => [3, 268]
        ]);
    }
}

// Log received data
logData("Received data: " . json_encode($postData, JSON_PRETTY_PRINT), "logs/data.log");

// Extract name parts
$nameParts = getNames($name);
$assigned_by_id = isset($owner_id) ? $owner_id : (isset($agent_id) ? $agent_id : DEFAULT_RESPONSIBLE_PERSON);

// Prepare contact data
$contactData = [
    'NAME'          => $nameParts['firstName'],
    'SECOND_NAME'   => $nameParts['secondName'],
    'LAST_NAME'     => $nameParts['lastName'],
    'EMAIL'         => [['VALUE' => $email, 'VALUE_TYPE' => 'WORK', 'TYPE_ID' => 'EMAIL']],
    'PHONE'         => [['VALUE' => $phone, 'VALUE_TYPE' => 'WORK', 'TYPE_ID' => 'PHONE']],
    'ASSIGNED_BY_ID' => $assigned_by_id
];

// Create contact and get ID
$contactId = createContact($contactData);

// Prepare lead fields
$leadFields = [
    'TITLE'                => $title,
    'NAME'                 => $nameParts['firstName'],
    'SECOND_NAME'          => $nameParts['secondName'],
    'LAST_NAME'            => $nameParts['lastName'],
    'EMAIL'                => [['VALUE' => $email, 'VALUE_TYPE' => 'WORK', 'TYPE_ID' => 'EMAIL']],
    'PHONE'                => [['VALUE' => $phone, 'VALUE_TYPE' => 'WORK', 'TYPE_ID' => 'PHONE']],
    'UF_CRM_1721198189214' => $name,
    'UF_CRM_1721198325274' => $email,
    'UF_CRM_1736406984'    => $phone,
    'COMMENTS'             => $message,
    'SOURCE_ID'            => 'WEB',
    'SOURCE_DESCRIPTION'   => $topic,
    'UF_CRM_660FC42189F9E' => $type,
    'ASSIGNED_BY_ID'       => $assigned_by_id,
    'CONTACT_ID'           => $contactId,
    'CATEGORY_ID'          => $type == 5479 ? PRIMARY_CATEGORY_ID : SECONDARY_CATEGORY_ID,
    'UF_CRM_1739890146108' => $reference,
    'UF_CRM_1735902375' => $owner_id ?? null,
    'UF_CRM_660FC4228ABC1' => $agent_id ?? null,
    'UF_CRM_1739945676' => $property_link
];

// Send request to Bitrix
if ($type == 5479) {
    unset($leadFields['CATEGORY_ID']);
    unset($leadFields['UF_CRM_660FC42189F9E']);
    unset($leadFields['UF_CRM_1739890146108']);
    unset($leadFields['UF_CRM_1739945676']);

    $leadFields['UF_CRM_1692121398282'] = 5344;

    $response = CRest::call('crm.lead.add', ['fields' => $leadFields]);
} else {
    $response = CRest::call('crm.deal.add', ['fields' => $leadFields]);
}

// Log lead fields
logData("Fields used: " . json_encode($leadFields, JSON_PRETTY_PRINT), "logs/fields.log");

// Log response
logData("Response: " . json_encode($response, JSON_PRETTY_PRINT), "logs/response.log");

// Handle response errors
if (isset($response['error'])) {
    logData("Error: " . json_encode($response['error'], JSON_PRETTY_PRINT), "logs/error.log");
    sendResponse(500, [
        'status'  => 'error',
        'message' => 'Data received but failed to create lead.',
        'details' => $response['error'],
    ]);
    exit;
}

// Success response
sendResponse(200, ['status' => 'success', 'message' => 'Data received and lead created successfully.']);
