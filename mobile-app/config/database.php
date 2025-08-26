<?php
/**
 * Database configuration for Supabase
 * Contains credentials and connection settings
 */

/**
 * Supabase Configuration Enhancement
 * 
 * Updates the Supabase request function to handle RLS (Row Level Security) and
 * proper authentication when inserting data into the donors_detail table.
 * This ensures proper permissions for data operations.
 */

// Supabase credentials
define('SUPABASE_URL', 'https://nwakbxwglhxcpunrzstf.supabase.co');
define('SUPABASE_API_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im53YWtieHdnbGh4Y3B1bnJ6c3RmIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc0MjI4MDU3MiwiZXhwIjoyMDU3ODU2NTcyfQ.08faJBwJPY8rjRkd_gO2iCrHkiIuZjk6HD9cPzJlrGk');
define('SUPABASE_JWT_SECRET', '02QhuqPddDa6fpjMrdkHsgIIl8c9/ZhJvsEO/S7pvzSJP6oeMIbREBXvtigd2BS/VuIrghVX5OnQUTR2ErDi7A==');

// For backward compatibility, also define SUPABASE_KEY
define('SUPABASE_KEY', SUPABASE_API_KEY);

// Also define service role key for admin operations that bypass RLS
define('SUPABASE_SERVICE_KEY', SUPABASE_API_KEY); // Replace with actual service key if available

/**
 * Helper function to make Supabase API requests
 * 
 * @param string $endpoint The Supabase endpoint to call
 * @param string $method The HTTP method (GET, POST, etc.)
 * @param array $data Optional data to send in the request body
 * @param array $headers Optional additional headers
 * @param bool $use_service_role Whether to use the service role key for admin access
 * @return array The response data
 */
function supabase_request($endpoint, $method = 'GET', $data = null, $headers = [], $use_service_role = false) {
    $url = SUPABASE_URL . '/' . $endpoint;
    
    // Determine which key to use based on the operation
    $api_key = $use_service_role ? SUPABASE_SERVICE_KEY : SUPABASE_API_KEY;
    
    $default_headers = [
        'Content-Type: application/json',
        'apikey: ' . $api_key,
        'Authorization: Bearer ' . $api_key
    ];
    
    // Always include the Content-Profile header for public schema
    $profile_header = ['Content-Profile: public'];
    
    // Merge headers, ensuring our content-profile is included
    $headers = array_merge($default_headers, $profile_header, $headers);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data) {
        $json_data = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    }
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'error' => $error,
            'status_code' => $status_code
        ];
    }
    
    $decoded_response = json_decode($response, true);
    
    // Add debugging for create operations
    if ($method === 'POST') {
        error_log("Supabase POST request - Status: $status_code, Response: $response");
        error_log("Supabase POST request - URL: $url, Data: " . json_encode($data));
        error_log("Supabase POST request - Headers: " . json_encode($headers));
    }
    
    return [
        'success' => $status_code >= 200 && $status_code < 300,
        'data' => $decoded_response,
        'status_code' => $status_code,
        'raw_response' => $response
    ];
}

/**
 * Get a database record by ID
 * 
 * @param string $table The table name
 * @param int $id The record ID
 * @return array The response from Supabase
 */
function get_record($table, $id) {
    return supabase_request("rest/v1/$table?id=eq.$id", 'GET');
}

/**
 * Get all records from a table
 * 
 * @param string $table The table name
 * @param array $params Optional query parameters
 * @return array The response from Supabase
 */
function get_records($table, $params = []) {
    $query_string = '';
    if (!empty($params)) {
        $query_string = '?' . http_build_query($params);
    }
    
    return supabase_request("rest/v1/$table$query_string", 'GET');
}

/**
 * Create a new record
 * 
 * @param string $table The table name
 * @param array $data The data to insert
 * @return array The response from Supabase
 */
function create_record($table, $data) {
    // Enhanced headers for Supabase REST API
    $headers = [
        'Prefer: return=representation',
        'Content-Profile: public'
    ];
    
    // Make sure table doesn't already have schema prefix
    $tableName = str_replace('public.', '', $table);
    
    // Use service role key for donations table to bypass RLS
    if ($tableName === 'donations') {
        return supabase_request("rest/v1/$tableName", 'POST', $data, $headers, true);
    }
    
    return supabase_request("rest/v1/$tableName", 'POST', $data, $headers);
}

/**
 * Update a record
 * 
 * @param string $table The table name
 * @param int $id The record ID
 * @param array $data The data to update
 * @param string $primaryKey The primary key column name (default to 'id')
 * @return array The response from Supabase
 */
function update_record($table, $id, $data, $primaryKey = 'id') {
    $headers = ['Prefer: return=representation'];
    return supabase_request("rest/v1/$table?$primaryKey=eq.$id", 'PATCH', $data, $headers);
}

/**
 * Delete a record
 * 
 * @param string $table The table name
 * @param int $id The record ID
 * @return array The response from Supabase
 */
function delete_record($table, $id) {
    return supabase_request("rest/v1/$table?id=eq.$id", 'DELETE');
}

/**
 * Alias for create_record function for backward compatibility
 * 
 * @param string $table The table name
 * @param array $data The data to insert
 * @return array The response from Supabase
 */
function db_insert($table, $data) {
    // Set a global variable to track errors for db_error function
    global $last_db_error;
    
    $result = create_record($table, $data);
    
    if (!$result['success']) {
        $last_db_error = json_encode($result['data'] ?? ['error' => 'Unknown error']);
    }
    
    return $result;
}

/**
 * Get the last database error
 * 
 * @return string The last database error
 */
function db_error() {
    // Since we're using Supabase REST API, we don't have direct access to database errors
    global $last_db_error;
    return $last_db_error ?? 'Unknown database error';
}
?> 