<?php
// Simple test to check GitHub API
$github_url = 'https://api.github.com/repos/webdevarif/SohojSecureOrder/releases/latest';

echo "Testing GitHub API...\n";
echo "URL: " . $github_url . "\n";

$response = wp_remote_get($github_url, array(
    'timeout' => 15,
    'headers' => array(
        'User-Agent' => 'SohojSecureOrder/1.0.0'
    )
));

if (is_wp_error($response)) {
    echo "Error: " . $response->get_error_message() . "\n";
} else {
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    echo "Response code: " . wp_remote_retrieve_response_code($response) . "\n";
    echo "Response body: " . substr($body, 0, 500) . "\n";
    
    if ($data && isset($data['tag_name'])) {
        echo "Found version: " . $data['tag_name'] . "\n";
    } else {
        echo "No version found in response\n";
    }
} 