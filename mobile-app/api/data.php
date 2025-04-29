<?php
/**
 * Data endpoints for the PWA
 * Handles CRUD operations for application data
 */

// Include necessary files
require_once '../config/database.php';
require_once '../includes/functions.php';

// Set headers for API
header('Content-Type: application/json');

// Handle CORS for PWA
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// If preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Authentication check (simple example)
function authenticate() {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Authorization header is required']);
        exit;
    }
    
    $token = str_replace('Bearer ', '', $headers['Authorization']);
    
    // TODO: Add Supabase client implementation to validate token
    
    // For demo purposes, we'll just check if the token exists
    if ($token !== 'sample_token') {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
    
    return true;
}

// Get the request path
$request = $_SERVER['REQUEST_URI'];
$request = explode('/', trim($request, '/'));
$endpoint = $request[count($request) - 1];

// Process based on the endpoint and request method
switch ($endpoint) {
    case 'items':
        authenticate();
        
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            // TODO: Add Supabase client implementation to fetch items
            
            // Sample data
            $items = [
                ['id' => 1, 'name' => 'Item 1', 'description' => 'Description for item 1'],
                ['id' => 2, 'name' => 'Item 2', 'description' => 'Description for item 2'],
                ['id' => 3, 'name' => 'Item 3', 'description' => 'Description for item 3']
            ];
            
            echo json_encode([
                'success' => true,
                'items' => $items
            ]);
        } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Get POST data
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate input
            if (!isset($data['name']) || !isset($data['description'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Name and description are required']);
                exit;
            }
            
            // TODO: Add Supabase client implementation to create item
            
            // Sample response
            echo json_encode([
                'success' => true,
                'message' => 'Item created successfully',
                'item' => [
                    'id' => 4,
                    'name' => $data['name'],
                    'description' => $data['description']
                ]
            ]);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    case 'item':
        authenticate();
        
        // Get the item ID
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Item ID is required']);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            // TODO: Add Supabase client implementation to fetch item by ID
            
            // Sample data
            echo json_encode([
                'success' => true,
                'item' => [
                    'id' => $id,
                    'name' => 'Item ' . $id,
                    'description' => 'Description for item ' . $id
                ]
            ]);
        } elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {
            // Get PUT data
            $data = json_decode(file_get_contents('php://input'), true);
            
            // TODO: Add Supabase client implementation to update item
            
            // Sample response
            echo json_encode([
                'success' => true,
                'message' => 'Item updated successfully',
                'item' => [
                    'id' => $id,
                    'name' => $data['name'] ?? 'Item ' . $id,
                    'description' => $data['description'] ?? 'Description for item ' . $id
                ]
            ]);
        } elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
            // TODO: Add Supabase client implementation to delete item
            
            // Sample response
            echo json_encode([
                'success' => true,
                'message' => 'Item deleted successfully'
            ]);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        break;
} 