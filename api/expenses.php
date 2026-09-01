<?php
require_once __DIR__ . '/bootstrap.php';

$expensesFile = dirname(__DIR__) . '/data/expenses.json';

function getExpensesData($file) {
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function saveExpensesData($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $expenses = getExpensesData($expensesFile);
    jsonResponse($expenses);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? $_GET['action'] ?? '';

    $expenses = getExpensesData($expensesFile);

    if ($action === 'add') {
        $description = trim($input['description'] ?? '');
        $amount      = floatval($input['amount'] ?? 0);
        $category    = trim($input['category'] ?? 'Général');
        $date        = trim($input['date'] ?? date('Y-m-d'));
        $propertyId  = trim($input['property_id'] ?? '');

        if (!$description || $amount <= 0) {
            jsonResponse(['error' => 'Description et montant requis.'], 400);
        }

        $newExp = [
            'id'          => 'exp-' . time() . '-' . rand(100,999),
            'date'        => $date,
            'category'    => $category,
            'description' => $description,
            'amount'      => $amount,
            'property_id' => $propertyId,
            'created_at'  => date('Y-m-d H:i:s')
        ];

        $expenses[] = $newExp;
        saveExpensesData($expensesFile, $expenses);
        jsonResponse(['ok' => true, 'expense' => $newExp]);
    }

    if ($action === 'delete') {
        $id = trim($input['id'] ?? '');
        $expenses = array_values(array_filter($expenses, fn($e) => $e['id'] !== $id));
        saveExpensesData($expensesFile, $expenses);
        jsonResponse(['ok' => true]);
    }
}
