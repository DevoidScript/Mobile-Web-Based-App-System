<?php
/**
 * Push Notifications Configuration (VAPID)
 *
 * Store your VAPID keys here. In production, prefer environment variables or
 * a secrets manager. The public key can be exposed to the client; the private
 * key must remain on the server.
 */

// Load from env if provided, else fallback to placeholders
$envPublic = getenv('VAPID_PUBLIC_KEY') ?: '';
$envPrivate = getenv('VAPID_PRIVATE_KEY') ?: '';

// Replace the placeholders below with your generated keys for local dev
if (!$envPublic || !$envPrivate) {
	// Generated VAPID keys for local development
	// In production, use environment variables or secure secrets manager
	define('VAPID_PUBLIC_KEY', 'BEl62iUYgUivxIkv69yViEuiBIa40HI8U7n7WtqswLwFwalz34a1fYFHvzktJj0p3Xz14pbS_IFVxS0xpaSgwfE');
	define('VAPID_PRIVATE_KEY', 'p256dhkey');
} else {
	define('VAPID_PUBLIC_KEY', $envPublic);
	define('VAPID_PRIVATE_KEY', $envPrivate);
}

/**
 * Returns the VAPID key pair.
 *
 * @return array{public:string, private:string}
 */
function get_vapid_keys() {
	return [
		'public' => VAPID_PUBLIC_KEY,
		'private' => VAPID_PRIVATE_KEY,
	];
}

/**
 * Build WebPush sender if the library is available; otherwise return null.
 * The caller can detect null and respond with an actionable error.
 *
 * @return object|null
 */
function build_webpush_sender() {
	// Expect composer autoload if Minishlink/web-push is installed
	$autoloadPath = __DIR__ . '/../vendor/autoload.php';
	if (!file_exists($autoloadPath)) {
		// Support project-level vendor as well
		$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
	}
	if (file_exists($autoloadPath)) {
		require_once $autoloadPath;
	}

	if (!class_exists('Minishlink\\WebPush\\WebPush')) {
		return null;
	}

	$keys = get_vapid_keys();
	$vapid = [
		'VAPID' => [
			'subject' => 'mailto:admin@example.com', // update as appropriate
			'publicKey' => $keys['public'],
			'privateKey' => $keys['private'],
		]
	];

	return new Minishlink\WebPush\WebPush($vapid);
}

?>