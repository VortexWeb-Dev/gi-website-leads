<?php

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
