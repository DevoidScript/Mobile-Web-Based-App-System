<?php
/**
 * Get VAPID Public Key API
 * 
 * Returns the VAPID public key for client-side push subscription.
 * This is safe to expose publicly.
 */

require_once '../config/push.php';

header('Content-Type: application/json');

$keys = get_vapid_keys();

echo json_encode([
    'success' => true,
    'publicKey' => $keys['public']
]);
?>




