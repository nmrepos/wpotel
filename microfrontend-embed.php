<?php
/**
 * Plugin Name: Microfrontend Embed
 * Description: Embeds PayPal and Second microfrontends with OpenTelemetry tracing
 * Version: 1.0
 * Author: Your Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Configure OTEL exporter endpoint
if (!defined('OTEL_EXPORTER_OTLP_ENDPOINT')) {
    define('OTEL_EXPORTER_OTLP_ENDPOINT', 'https://otel.nidhun.me/v1/traces');
}

// Set environment variables for OpenTelemetry
if (!getenv('OTEL_EXPORTER_OTLP_ENDPOINT')) {
    putenv('OTEL_EXPORTER_OTLP_ENDPOINT=https://otel.nidhun.me');
    putenv('OTEL_EXPORTER_OTLP_TRACES_ENDPOINT=https://otel.nidhun.me/v1/traces');
    putenv('OTEL_EXPORTER_OTLP_METRICS_ENDPOINT=https://otel.nidhun.me/v1/metrics');
    putenv('OTEL_SERVICE_NAME=wordpress-microfrontend-embed');
    putenv('OTEL_SERVICE_VERSION=1.0.0');
}

class MicrofrontendEmbed {
    
    private $otel_available = false;
    
    public function __construct() {
        // Check if OpenTelemetry is available
        $this->check_otel_availability();
        
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_head', array($this, 'add_otel_javascript'));
    }
    
    private function check_otel_availability() {
        // Check if OTEL classes are available without fatal error
        $this->otel_available = class_exists('OpenTelemetry\API\Common\Instrumentation\Globals');
        
        if ($this->otel_available) {
            error_log('OpenTelemetry classes are available');
        } else {
            error_log('OpenTelemetry classes not available, using basic logging');
        }
    }
    
    private function log_microfrontend_event($event, $data = []) {
        // Send trace to SigNoz manually
        $this->send_trace_to_signoz($event, $data);
        
        // Also log locally
        $log_entry = [
            'timestamp' => date('c'),
            'event' => $event,
            'service' => 'wordpress-microfrontend-embed',
            'data' => $data
        ];
        
        error_log('MICROFRONTEND_EVENT: ' . json_encode($log_entry));
    }
    
    private function send_trace_to_signoz($spanName, $attributes = []) {
        $traceData = [
            'resourceSpans' => [
                [
                    'resource' => [
                        'attributes' => [
                            ['key' => 'service.name', 'value' => ['stringValue' => 'wordpress-microfrontend']],
                            ['key' => 'service.version', 'value' => ['stringValue' => '1.0.0']]
                        ]
                    ],
                    'scopeSpans' => [
                        [
                            'scope' => ['name' => 'wordpress-microfrontend-embed', 'version' => '1.0.0'],
                            'spans' => [
                                [
                                    'traceId' => bin2hex(random_bytes(16)),
                                    'spanId' => bin2hex(random_bytes(8)),
                                    'name' => $spanName,
                                    'kind' => 1, // SPAN_KIND_INTERNAL
                                    'startTimeUnixNano' => (int)(microtime(true) * 1000000000),
                                    'endTimeUnixNano' => (int)((microtime(true) + 0.01) * 1000000000),
                                    'attributes' => array_map(function($key, $value) {
                                        return ['key' => $key, 'value' => ['stringValue' => (string)$value]];
                                    }, array_keys($attributes), array_values($attributes))
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        // Send asynchronously to avoid blocking the page
        $jsonData = json_encode($traceData);
        $endpoint = 'https://otel.nidhun.me/v1/traces';
        
        // Use a quick non-blocking request
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT => 1, // Quick timeout
            CURLOPT_CONNECTTIMEOUT => 1
        ]);
        
        curl_exec($ch);
        curl_close($ch);
    }
    
    public function init() {
        // Add shortcodes to embed microfrontends
        add_shortcode('paypal_micro', array($this, 'paypal_microfrontend'));
        add_shortcode('second_micro', array($this, 'second_microfrontend'));
    }
    
    public function add_otel_javascript() {
        // Add JavaScript to send OTEL traces from browser
        ?>
        <script>
        // Browser-side OpenTelemetry configuration
        window.otelConfig = {
            endpoint: 'https://otel.nidhun.me/v1/traces',
            serviceName: 'wordpress-microfrontend-browser',
            serviceVersion: '1.0.0'
        };
        
        // Function to send custom traces
        function sendOTELTrace(spanName, attributes = {}) {
            const traceData = {
                resourceSpans: [{
                    resource: {
                        attributes: [
                            { key: 'service.name', value: { stringValue: window.otelConfig.serviceName } },
                            { key: 'service.version', value: { stringValue: window.otelConfig.serviceVersion } }
                        ]
                    },
                    scopeSpans: [{
                        scope: { name: 'wordpress-microfrontend-browser', version: '1.0.0' },
                        spans: [{
                            traceId: generateTraceId(),
                            spanId: generateSpanId(),
                            name: spanName,
                            kind: 1, // SPAN_KIND_INTERNAL
                            startTimeUnixNano: Date.now() * 1000000,
                            endTimeUnixNano: (Date.now() + 1) * 1000000,
                            attributes: Object.entries(attributes).map(([key, value]) => ({
                                key: key,
                                value: { stringValue: String(value) }
                            }))
                        }]
                    }]
                }]
            };
            
            fetch(window.otelConfig.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(traceData)
            }).catch(error => console.warn('OTEL trace failed:', error));
        }
        
        function generateTraceId() {
            return Array.from({length: 32}, () => Math.floor(Math.random() * 16).toString(16)).join('');
        }
        
        function generateSpanId() {
            return Array.from({length: 16}, () => Math.floor(Math.random() * 16).toString(16)).join('');
        }
        </script>
        <?php
    }
    
    public function enqueue_scripts() {
        // Enqueue custom JavaScript for microfrontend loading
        wp_enqueue_script(
            'microfrontend-loader',
            plugin_dir_url(__FILE__) . 'microfrontend-loader.js',
            array('jquery'),
            '1.0',
            true
        );
        
        // Localize script to pass AJAX URL and nonce
        wp_localize_script('microfrontend-loader', 'microfrontend_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('microfrontend_nonce')
        ));
    }
    
    public function paypal_microfrontend($atts) {
        // Log microfrontend rendering event
        $this->log_microfrontend_event('microfrontend.paypal.render', [
            'microfrontend.type' => 'paypal',
            'microfrontend.url' => 'https://p4l.nidhun.me/',
            'wp.shortcode' => 'paypal_micro'
        ]);
        
        $atts = shortcode_atts(array(
            'height' => '500px',
            'width' => '100%'
        ), $atts);
        
        $output = sprintf(
            '<div id="paypal-microfrontend" style="width:%s; height:%s; border:1px solid #ccc;">
                <iframe src="https://p4l.nidhun.me/" width="100%%" height="100%%" frameborder="0" 
                        onload="sendOTELTrace(\'microfrontend.paypal.loaded\', {\'url\': \'https://p4l.nidhun.me/\', \'type\': \'paypal\'})"></iframe>
            </div>',
            esc_attr($atts['width']),
            esc_attr($atts['height'])
        );
        
        return $output;
    }
    
    public function second_microfrontend($atts) {
        // Log microfrontend rendering event
        $this->log_microfrontend_event('microfrontend.second.render', [
            'microfrontend.type' => 'second',
            'microfrontend.url' => 'https://s8r.nidhun.me/',
            'wp.shortcode' => 'second_micro'
        ]);
        
        $atts = shortcode_atts(array(
            'height' => '500px',
            'width' => '100%'
        ), $atts);
        
        $output = sprintf(
            '<div id="second-microfrontend" style="width:%s; height:%s; border:1px solid #ccc;">
                <iframe src="https://s8r.nidhun.me/" width="100%%" height="100%%" frameborder="0"
                        onload="sendOTELTrace(\'microfrontend.second.loaded\', {\'url\': \'https://s8r.nidhun.me/\', \'type\': \'second\'})"></iframe>
            </div>',
            esc_attr($atts['width']),
            esc_attr($atts['height'])
        );
        
        return $output;
    }
}

// Initialize the plugin
new MicrofrontendEmbed();

// Add admin menu for testing
add_action('admin_menu', function() {
    add_menu_page(
        'Microfrontend Test',
        'Microfrontend Test',
        'manage_options',
        'microfrontend-test',
        function() {
            echo '<div class="wrap">';
            echo '<h1>Microfrontend Test Page</h1>';
            echo '<h2>PayPal Microfrontend</h2>';
            echo do_shortcode('[paypal_micro]');
            echo '<h2>Second Microfrontend</h2>';
            echo do_shortcode('[second_micro]');
            echo '</div>';
        }
    );
});
