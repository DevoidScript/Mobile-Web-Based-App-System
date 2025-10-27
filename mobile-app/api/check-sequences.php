<?php
// Check sequences status via Supabase RPC
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    // Require POST to avoid accidental triggering via browser
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed. Use POST.']);
        exit;
    }

    // RPC requires an object payload; send empty object
    $payload = new stdClass();

    $result = supabase_request('rest/v1/rpc/check_sequences', 'POST', $payload, [], true);

    if (!$result['success']) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to check sequences',
            'status_code' => $result['status_code'] ?? null,
            'error' => $result['data'] ?? $result['raw_response'] ?? null
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => $result['data']
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}


