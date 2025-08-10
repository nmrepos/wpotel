<?php
// Test page to trigger microfrontend traces
echo "Testing microfrontend traces...\n";

// Simulate WordPress environment
$_SERVER['HTTP_HOST'] = 'localhost:8086';
define('ABSPATH', '/var/local/wordpress/');

// Include WordPress
require_once '/var/local/wordpress/wp-config.php';
require_once '/var/local/wordpress/wp-load.php';

echo "WordPress loaded\n";

// Trigger the microfrontend shortcodes
echo "Triggering PayPal microfrontend...\n";
echo do_shortcode('[paypal_micro]');

echo "\nTriggering Second microfrontend...\n"; 
echo do_shortcode('[second_micro]');

echo "\nTraces sent to SigNoz!\n";
?>
