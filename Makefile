# Poxica Upload Service - Makefile
# Commands for common development and production tasks

.PHONY: help install dev build up down logs test backup ssl clean

# Default command
help:
	@echo "Poxica Upload Service - Available Commands:"
	@echo ""
	@echo "  setup      - Initial setup (install dependencies)"
	@echo "  dev        - Start development environment"
	@echo "  build      - Build all containers"
	@echo "  up         - Start production environment"
	@echo "  down       - Stop all services"
	@echo "  restart    - Restart all services"
	@echo "  logs       - Show logs (add SERVICE=name for specific service)"
	@echo "  test       - Run system tests"
	@echo "  backup     - Create backup"
	@echo "  ssl        - Setup SSL certificate"
	@echo "  clean      - Clean up Docker resources"
	@echo "  status     - Show service status"
	@echo "  update     - Update and restart services"
	@echo ""

# Initial setup
setup:
	@echo "🚀 Setting up Poxica Upload Service..."
	chmod +x scripts/*.sh
	@if [ ! -f .env ]; then cp .env.example .env; echo "📝 Created .env file - please configure it"; fi
	@echo "✅ Setup complete! Next steps:"
	@echo "   1. Configure .env file"
	@echo "   2. Add Google Drive credentials"
	@echo "   3. Run 'make ssl' to setup HTTPS"
	@echo "   4. Run 'make up' to start services"

# Development environment
dev:
	@echo "🛠️ Starting development environment..."
	docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d
	@echo "✅ Development environment started!"
	@echo "   Frontend: http://localhost:3000"
	@echo "   Backend: http://localhost:8000"
	@echo "   Database: localhost:5432"

# Build containers
build:
	@echo "🔨 Building containers..."
	docker-compose build --no-cache

# Production environment
up:
	@echo "🚀 Starting production environment..."
	docker-compose up -d
	@echo "✅ Production environment started!"

# Stop services
down:
	@echo "🛑 Stopping services..."
	docker-compose down

# Restart services
restart:
	@echo "🔄 Restarting services..."
	docker-compose restart
	@echo "✅ Services restarted!"

# Show logs
logs:
	@if [ -z "$(SERVICE)" ]; then \
		echo "📋 Showing all logs..."; \
		docker-compose logs -f; \
	else \
		echo "📋 Showing logs for $(SERVICE)..."; \
		docker-compose logs -f $(SERVICE); \
	fi

# Run tests
test:
	@echo "🧪 Running system tests..."
	chmod +x scripts/test-system.sh
	sudo ./scripts/test-system.sh

# Create backup
backup:
	@echo "💾 Creating backup..."
	chmod +x scripts/backup.sh
	sudo ./scripts/backup.sh

# Setup SSL
ssl:
	@echo "🔒 Setting up SSL certificate..."
	chmod +x scripts/setup-ssl.sh
	sudo ./scripts/setup-ssl.sh

# Clean up Docker resources
clean:
	@echo "🧹 Cleaning up Docker resources..."
	docker system prune -f
	docker volume prune -f
	@echo "✅ Cleanup complete!"

# Show service status
status:
	@echo "📊 Service Status:"
	docker-compose ps
	@echo ""
	@echo "📈 Resource Usage:"
	docker stats --no-stream

# Update services
update:
	@echo "🔄 Updating services..."
	git pull
	docker-compose pull
	docker-compose up -d --build
	@echo "✅ Services updated!"

# Database operations
db-backup:
	@echo "💾 Backing up database..."
	docker-compose exec db pg_dump -U poxica_user poxica_upload > backup_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "✅ Database backup created!"

db-restore:
	@if [ -z "$(FILE)" ]; then echo "❌ Usage: make db-restore FILE=backup.sql"; exit 1; fi
	@echo "📥 Restoring database from $(FILE)..."
	docker-compose exec -T db psql -U poxica_user poxica_upload < $(FILE)
	@echo "✅ Database restored!"

# View health status
health:
	@echo "🏥 Checking service health..."
	@curl -s https://upload.poxica.com/health | python3 -m json.tool || echo "❌ Health check failed"

# Monitor logs in real-time
monitor:
	@echo "👀 Monitoring logs (Ctrl+C to stop)..."
	docker-compose logs -f --tail=100

# Quick deploy (for updates)
deploy:
	@echo "🚀 Quick deploy..."
	git pull
	docker-compose build
	docker-compose up -d
	@echo "✅ Deployment complete!"