#!/bin/sh

echo "🚀 Starting WordPress with OpenTelemetry and Microfrontend support..."

# Initialize wp-content if needed
if [ ! -f "wp-content/index.php" ]; then
    echo "📁 Initializing wp-content directory..."
    cp -r wp-content.bak/* wp-content/
fi

# Create plugins directory if it doesn't exist
mkdir -p wp-content/plugins/microfrontend-embed

# Install the microfrontend plugin
if [ -f "/tmp/microfrontend-embed.php" ]; then
    echo "🔌 Installing Microfrontend Embed plugin..."
    cp /tmp/microfrontend-embed.php wp-content/plugins/microfrontend-embed/microfrontend-embed.php
fi

# Wait for database to be ready
echo "⏳ Waiting for database connection..."
while ! mysqladmin ping -h"$WORDPRESS_DB_HOST" --silent; do
    sleep 1
done

echo "✅ Database is ready!"

# Set OpenTelemetry environment variables for AUTO-INSTRUMENTATION
export OTEL_SERVICE_NAME="${OTEL_SERVICE_NAME:-wordpress-microfrontend}"
export OTEL_SERVICE_VERSION="${OTEL_SERVICE_VERSION:-1.0.0}"
export OTEL_EXPORTER_OTLP_ENDPOINT="${OTEL_EXPORTER_OTLP_ENDPOINT:-https://otel.nidhun.me}"
export OTEL_EXPORTER_OTLP_TRACES_ENDPOINT="${OTEL_EXPORTER_OTLP_TRACES_ENDPOINT:-https://otel.nidhun.me/v1/traces}"
export OTEL_EXPORTER_OTLP_METRICS_ENDPOINT="${OTEL_EXPORTER_OTLP_METRICS_ENDPOINT:-https://otel.nidhun.me/v1/metrics}"
export OTEL_EXPORTER_OTLP_PROTOCOL="${OTEL_EXPORTER_OTLP_PROTOCOL:-http/protobuf}"
export OTEL_TRACES_EXPORTER="${OTEL_TRACES_EXPORTER:-otlp}"
export OTEL_METRICS_EXPORTER="${OTEL_METRICS_EXPORTER:-otlp}"
export OTEL_LOGS_EXPORTER="${OTEL_LOGS_EXPORTER:-otlp}"
export OTEL_PHP_AUTOLOAD_ENABLED=true
export OTEL_INSTRUMENTATION_HTTP_ENABLED=true
export OTEL_INSTRUMENTATION_COMMON_DEFAULT_ENABLED=true
export OTEL_RESOURCE_ATTRIBUTES="service.name=${OTEL_SERVICE_NAME},service.version=${OTEL_SERVICE_VERSION}"

# Enable OTEL debugging
export OTEL_LOG_LEVEL=debug

echo "📊 OpenTelemetry configured:"
echo "   Service: $OTEL_SERVICE_NAME v$OTEL_SERVICE_VERSION"
echo "   Endpoint: $OTEL_EXPORTER_OTLP_ENDPOINT"

echo "🌐 Starting WordPress server on port 8000..."
echo "📱 Access WordPress at: http://localhost:8086"
echo "🔧 WordPress Admin: http://localhost:8086/wp-admin"
echo ""
echo "🎯 Microfrontend URLs:"
echo "   Second: ${SECOND_MICROFRONTEND_URL:-https://s8r.nidhun.me}"
echo "   PayPal: ${PAYPAL_MICROFRONTEND_URL:-https://p4l.nidhun.me}"
echo ""
echo "📋 Plugin shortcodes available: [second_micro] [paypal_micro]"

php -S 0.0.0.0:8000