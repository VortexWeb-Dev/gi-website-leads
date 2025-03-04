<?php
require_once __DIR__ . '/crest/crest.php';

define('LISTINGS_ENTITY_TYPE_ID', 1084);

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
