<?php
// Test POST ultra-simple
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $postData = file_get_contents('php://input');
    $_POST_data = $_POST;
    
    $result = [
        'method' => $method,
        'post_count' => count($_POST),
        'post_keys' => array_keys($_POST),
        'post_data' => $_POST_data,
        'raw_input' => $postData,
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
        'success' => true
    ];
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
} catch (Error $e) {
    echo json_encode(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}
