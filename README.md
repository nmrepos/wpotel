# WordPress OpenTelemetry Microfrontend

🚀 Instrumented WordPress with microfrontend embedding and OpenTelemetry tracing for distributed observability.

## Features

- ✅ WordPress with OpenTelemetry auto-instrumentation
- ✅ Microfrontend embedding via shortcodes
- ✅ Browser-side OTEL tracing
- ✅ Docker containerized deployment
- ✅ Environment-based configuration

## Quick Start

### 1. Clone and Start

```bash
git clone <your-repo-url>
cd wpotel
docker compose up -d
```

### 2. Configure Environment (Optional)

```bash
cp .env.example .env
# Edit .env with your specific configuration
```

### 3. Access WordPress

- **WordPress**: http://localhost:8086
- **Admin Panel**: http://localhost:8086/wp-admin

### 4. Use Microfrontends

The plugin automatically installs. Use these shortcodes in pages/posts:

```wordpress
[second_micro]    <!-- Embeds your 2nd microfrontend -->
[paypal_micro]    <!-- Embeds PayPal microfrontend -->
```

## Configuration

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `WORDPRESS_PORT` | 8086 | WordPress port |
| `OTEL_EXPORTER_OTLP_ENDPOINT` | https://otel.nidhun.me | OTEL collector endpoint |
| `SECOND_MICROFRONTEND_URL` | https://s8r.nidhun.me | 2nd microfrontend URL |
| `PAYPAL_MICROFRONTEND_URL` | https://p4l.nidhun.me | PayPal microfrontend URL |

### Cloudflare Tunnel Configuration

```yaml
ingress:
  - hostname: s8r.nidhun.me
    service: http://localhost:8082
  - hostname: p4l.nidhun.me  
    service: http://localhost:8084
  - hostname: w7s.nidhun.me
    service: http://localhost:8086
  - service: http_status:404
```

## OpenTelemetry Integration

### Server-side Tracing
- Automatic WordPress instrumentation via `open-telemetry/opentelemetry-auto-wordpress`
- Custom spans for microfrontend rendering
- Database query tracing

### Browser-side Tracing
- JavaScript OTEL traces when microfrontends load
- Custom trace data sent to configured endpoint
- Distributed tracing across microfrontends

## Development

### Project Structure

```
wpotel/
├── docker-compose.yml      # Container orchestration
├── Dockerfile             # WordPress + OTEL image
├── wp-config.php          # WordPress configuration
├── microfrontend-embed.php # Plugin for embedding microfrontends
├── start_wordpress.sh     # Startup script
└── .env.example          # Environment template
```

### Plugin Development

The microfrontend plugin is automatically installed and provides:
- `[paypal_micro]` shortcode
- `[second_micro]` shortcode  
- Browser-side OTEL tracing
- Admin test page

## Assignment Requirements

This project fulfills:

1. ✅ **Instrumented WordPress** with SigNoz integration
2. ✅ **2nd Microfrontend** instrumentation and SigNoz connection
3. ✅ **SigNoz OTLP ingress** over HTTP
4. ✅ **Cloudflare tunnel ingress** for all services

## Troubleshooting

### Check Service Status
```bash
docker compose ps
docker compose logs wordpress
```

### Verify OTEL Configuration
```bash
docker compose exec wordpress env | grep OTEL
```

### Test Microfrontend URLs
```bash
curl -I https://s8r.nidhun.me/
curl -I https://p4l.nidhun.me/
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make changes
4. Test with `docker compose up --build`
5. Submit a pull request