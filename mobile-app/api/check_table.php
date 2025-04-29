<?php
/**
 * Supabase Table Structure Verification Utility
 * 
 * This script checks the structure of the donors_detail table in Supabase
 * to ensure our field mappings are correct. It displays the existing table
 * structure to help diagnose data insertion issues.
 * 
 * Usage: Visit this script in a browser to view the table structure.
 */

// Include database configuration
require_once '../config/database.php';

// Set content type to plain text for better readability
header('Content-Type: text/plain');

echo "DONORS_DETAIL TABLE STRUCTURE CHECKER\n";
echo "====================================\n\n";

// Function to check if a table exists
function check_table($table_name) {
    // Use Supabase's REST API to get the table information
    // First check the schema information
    $schema_info = supabase_request('rest/v1/', 'GET', null, [], true);
    
    echo "1. Checking Schema Information:\n";
    echo json_encode($schema_info, JSON_PRETTY_PRINT) . "\n\n";
    
    // Try to get table definition directly
    echo "2. Checking Table Definition for '$table_name':\n";
    $table_def = supabase_request("rest/v1/$table_name?limit=0", 'GET', null, [
        'Prefer: count=exact'
    ], true);
    
    echo json_encode($table_def, JSON_PRETTY_PRINT) . "\n\n";
    
    // Try to fetch table columns
    echo "3. Attempting to retrieve one record to examine structure:\n";
    $sample_record = supabase_request("rest/v1/$table_name?limit=1", 'GET', null, [], true);
    
    if (isset($sample_record['data']) && !empty($sample_record['data'])) {
        echo "Found sample record:\n";
        echo json_encode($sample_record['data'][0], JSON_PRETTY_PRINT) . "\n\n";
        
        echo "4. Table columns (based on sample record):\n";
        $columns = array_keys($sample_record['data'][0]);
        foreach ($columns as $idx => $column) {
            echo ($idx + 1) . ". $column\n";
        }
    } else {
        echo "No records found in table. Cannot determine structure from data.\n";
    }
}

// Check if we have RLS permissions issue
echo "CHECKING TABLE ACCESS PERMISSIONS:\n";
try {
    // Check with anonymous key
    echo "\nTrying with anonymous key:\n";
    $anon_result = supabase_request('rest/v1/donors_detail?limit=1', 'GET', null, [], false);
    echo "Status code: " . ($anon_result['status_code'] ?? 'unknown') . "\n";
    
    // Check with service key (bypassing RLS if available)
    echo "\nTrying with service key (bypassing RLS):\n";
    $service_result = supabase_request('rest/v1/donors_detail?limit=1', 'GET', null, [], true);
    echo "Status code: " . ($service_result['status_code'] ?? 'unknown') . "\n";
    
    // Compare results
    if (($anon_result['status_code'] ?? 0) !== ($service_result['status_code'] ?? 0)) {
        echo "POTENTIAL RLS ISSUE: Different status codes between anonymous and service role\n";
    }
} catch (Exception $e) {
    echo "Error checking permissions: " . $e->getMessage() . "\n";
}

// Check the donors_detail table structure
echo "\n\nCHECKING DONORS_DETAIL TABLE STRUCTURE:\n";
check_table('donors_detail');

// Try creating a test record to see if insertion works
echo "\n\nTRYING TEST INSERTION:\n";
$test_data = [
    'id' => 'test-' . uniqid(),  // Use a random ID to avoid conflicts
    'surname' => 'TEST_SURNAME',
    'first_name' => 'TEST_FIRST_NAME',
    'email' => 'test-' . uniqid() . '@example.com'
];

echo "Test data: " . json_encode($test_data, JSON_PRETTY_PRINT) . "\n\n";

// Define headers for insertion
$insert_headers = [
    'Prefer: return=representation',
    'Content-Profile: public'
];

// Try inserting with service role
$insert_result = supabase_request("rest/v1/donors_detail", 'POST', $test_data, $insert_headers, true);

echo "Insert result:\n";
echo "Status code: " . ($insert_result['status_code'] ?? 'unknown') . "\n";
echo "Response: " . json_encode($insert_result, JSON_PRETTY_PRINT) . "\n";

echo "\n\nDONE\n";
?> 