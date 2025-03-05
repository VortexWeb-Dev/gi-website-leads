<?php
require_once __DIR__ . '/crest/crest.php';

define('LISTINGS_ENTITY_TYPE_ID', 1084);
define('PROJECT_FIELD_ID', 'UF_CRM_1645008800');

function logData(string $message, string $logFile): void
{
    date_default_timezone_set('Asia/Kolkata');

    $logEntry = "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

function sendResponse(int $statusCode, array $data): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

function getNames($name)
{
    $nameParts = explode(' ', $name);

    $firstName = $nameParts[0];
    $secondName = isset($nameParts[1]) && count($nameParts) > 2 ? $nameParts[1] : '';
    $lastName = count($nameParts) > 2 ? $nameParts[count($nameParts) - 1] : (isset($nameParts[1]) ? $nameParts[1] : '');

    return [
        'firstName' => $firstName,
        'secondName' => $secondName,
        'lastName' => $lastName
    ];
}

function createContact($fields)
{
    $response = CRest::call('crm.contact.add', [
        'fields' => $fields
    ]);

    return $response['result'];
}

function getProperty($reference)
{
    $response = CRest::call('crm.item.list', [
        'entityTypeId' => LISTINGS_ENTITY_TYPE_ID,
        'filter' => [
            'ufCrm37ReferenceNumber' => $reference
        ]
    ]);

    return $response['result']['items'][0];
}

function getUserId(array $filter): ?int
{
    $response = CRest::call('user.get', [
        'filter' => array_merge($filter, ['ACTIVE' => 'Y']),
    ]);

    if (!empty($response['error'])) {
        error_log('Error getting user: ' . $response['error_description']);
        return null;
    }

    if (empty($response['result'])) {
        return null;
    }

    if (empty($response['result'][0]['ID'])) {
        return null;
    }

    return (int)$response['result'][0]['ID'];
}

function getPropertyLink($title)
{
    $url = "https://seocrm.gicrm.ae/property-detail/";
    $title = strtolower($title);
    $title = str_replace([" | ", " "], "-", $title);
    $title = preg_replace('/[^a-z0-9\-]/', '', $title);

    return $url . $title;
}

function getProjectId($topic)
{
    $projectName = explode(" - ", $topic)[1] ?? null;
    if (!$projectName) {
        return "Invalid topic format";
    }

    $response = CRest::call('crm.lead.userfield.list', [
        'filter' => ['FIELD_NAME' => PROJECT_FIELD_ID]
    ]);

    if (empty($response['result'][0]['ID']) || empty($response['result'][0]['LIST'])) {
        return "User field not found";
    }

    $fieldId = $response['result'][0]['ID'];
    $listItems = $response['result'][0]['LIST'];

    foreach ($listItems as $item) {
        if ($item['VALUE'] === $projectName) {
            return $item['ID'];
        }
    }

    $updateResponse = CRest::call('crm.lead.userfield.update', [
        'ID' => $fieldId,
        'fields' => [
            'LIST' => array_merge($listItems, [['VALUE' => $projectName]])
        ]
    ]);

    if ($updateResponse['result'] !== true) {
        return "Failed to add project";
    }

    $updatedResponse = CRest::call('crm.lead.userfield.list', [
        'filter' => ['FIELD_NAME' => PROJECT_FIELD_ID]
    ]);

    foreach ($updatedResponse['result'][0]['LIST'] ?? [] as $item) {
        if ($item['VALUE'] === $projectName) {
            return $item['ID'];
        }
    }

    return null;
}
