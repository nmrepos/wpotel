#!/bin/bash

# WordPress OpenTelemetry Deployment Script
# Automates the setup of WordPress with microfrontend embedding and OTEL

set -e

echo "🚀 Starting WordPress OpenTelemetry Deployment..."

# Check if .env exists, if not create from example
if [ ! -f .env ]; then
    echo "📝 Creating .env from .env.example..."
    cp .env.example .env
    echo "⚠️  Please edit .env file with your specific configuration"
fi

# Source environment variables
if [ -f .env ]; then
    source .env
fi

# Stop any existing containers
echo "🛑 Stopping existing containers..."
docker compose down --remove-orphans || true

# Pull latest images
echo "📥 Pulling latest images..."
docker compose pull

# Build the WordPress image
echo "🔨 Building WordPress image with OpenTelemetry..."
docker compose build --no-cache

# Start the services
echo "🚀 Starting services..."
docker compose up -d

# Wait for services to be healthy
echo "⏳ Waiting for services to be ready..."
sleep 30

# Check service health
echo "🔍 Checking service health..."
docker compose ps

# Display service URLs
echo ""
echo "✅ Deployment completed!"
echo ""
echo "📱 Service URLs:"
echo "   WordPress: http://localhost:${WORDPRESS_PORT:-8086}"
echo "   WordPress Admin: http://localhost:${WORDPRESS_PORT:-8086}/wp-admin"
echo ""
echo "🔗 Microfrontend URLs (configure in Cloudflare tunnel):"
echo "   Second Microfrontend: ${SECOND_MICROFRONTEND_URL:-https://s8r.nidhun.me}"
echo "   PayPal Microfrontend: ${PAYPAL_MICROFRONTEND_URL:-https://p4l.nidhun.me}"
echo ""
echo "📊 OpenTelemetry Endpoint: ${OTEL_EXPORTER_OTLP_ENDPOINT:-https://otel.nidhun.me}"
echo ""
echo "📋 Next Steps:"
echo "   1. Go to WordPress admin and complete setup"
echo "   2. Activate the 'Microfrontend Embed' plugin"
echo "   3. Create a page and use shortcodes: [paypal_micro] [second_micro]"
echo "   4. Configure Cloudflare tunnel for public access"
echo ""

# Show logs
echo "📄 Showing recent logs..."
docker compose logs --tail=50
