<?php
// Reset all sequences via Supabase RPC
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    // Require POST to avoid accidental triggering via browser
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed. Use POST.']);
        exit;
    }

    // Optional confirmation guard
    $confirm = isset($_POST['confirm']) ? strtolower(trim($_POST['confirm'])) : '';
    if ($confirm !== 'true' && $confirm !== 'yes' && $confirm !== '1') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Confirmation required. Pass confirm=true']);
        exit;
    }

    // RPC requires an object payload; send empty object
    $payload = new stdClass();

    $result = supabase_request('rest/v1/rpc/reset_all_sequences', 'POST', $payload, [], true);

    if (!$result['success']) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to reset sequences',
            'status_code' => $result['status_code'] ?? null,
            'error' => $result['data'] ?? $result['raw_response'] ?? null
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Sequences reset successfully',
        'data' => $result['data']
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}


