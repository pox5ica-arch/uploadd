#!/bin/bash

# ====================================
# SSL Certificate Setup with Let's Encrypt
# ====================================

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
DOMAIN="upload.poxica.com"
EMAIL=""

# Load environment variables
if [[ -f .env ]]; then
    source .env
    EMAIL=${ADMIN_EMAIL:-""}
    DOMAIN=${DOMAIN:-"upload.poxica.com"}
fi

# Functions
log() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
    exit 1
}

header() {
    echo -e "${BLUE}
====================================
    SSL CERTIFICATE SETUP
====================================${NC}"
}

# Check if running as root
check_root() {
    if [[ $EUID -ne 0 ]]; then
        error "This script must be run as root (use sudo)"
    fi
}

# Validate email
validate_email() {
    if [[ -z "$EMAIL" ]]; then
        echo -n "Enter your email for Let's Encrypt notifications: "
        read -r EMAIL
    fi
    
    if [[ ! "$EMAIL" =~ ^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$ ]]; then
        error "Invalid email format: $EMAIL"
    fi
    
    log "Using email: $EMAIL"
}

# Check domain DNS
check_dns() {
    log "Checking DNS configuration for $DOMAIN..."
    
    # Get server's public IP
    SERVER_IP=$(curl -s https://ifconfig.me || curl -s https://ipecho.net/plain || curl -s https://checkip.amazonaws.com)
    
    if [[ -z "$SERVER_IP" ]]; then
        error "Could not determine server's public IP"
    fi
    
    log "Server IP: $SERVER_IP"
    
    # Check DNS resolution
    DOMAIN_IP=$(dig +short "$DOMAIN" | tail -n1)
    
    if [[ -z "$DOMAIN_IP" ]]; then
        error "Domain $DOMAIN does not resolve to any IP. Please configure your DNS first."
    fi
    
    if [[ "$DOMAIN_IP" != "$SERVER_IP" ]]; then
        warn "Domain $DOMAIN resolves to $DOMAIN_IP but server IP is $SERVER_IP"
        echo -n "Continue anyway? (y/N): "
        read -r confirm
        if [[ ! "$confirm" =~ ^[Yy]$ ]]; then
            error "DNS configuration required before SSL setup"
        fi
    else
        log "DNS configuration is correct"
    fi
}

# Create nginx config for HTTP challenge
create_temp_nginx_config() {
    log "Creating temporary nginx configuration..."
    
    # Create minimal nginx config for ACME challenge
    cat > nginx/temp-ssl-setup.conf << EOF
server {
    listen 80;
    server_name $DOMAIN;
    
    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }
    
    location / {
        return 301 https://\$server_name\$request_uri;
    }
}
EOF
    
    # Backup current nginx config if exists
    if [[ -f nginx/sites-available/upload.poxica.com.conf ]]; then
        cp nginx/sites-available/upload.poxica.com.conf nginx/sites-available/upload.poxica.com.conf.backup
        log "Backed up existing nginx configuration"
    fi
    
    # Use temp config
    cp nginx/temp-ssl-setup.conf nginx/sites-available/upload.poxica.com.conf
}

# Start nginx for challenge
start_nginx_for_challenge() {
    log "Starting nginx for ACME challenge..."
    
    # Make sure certbot directories exist
    mkdir -p certbot/conf
    mkdir -p certbot/www
    
    # Start only nginx for the challenge
    docker-compose up -d nginx
    
    # Wait for nginx to be ready
    sleep 10
    
    # Test nginx is responding
    if ! curl -f "http://$DOMAIN/.well-known/acme-challenge/test" 2>/dev/null; then
        log "Nginx is ready for ACME challenge"
    fi
}

# Get SSL certificate
get_certificate() {
    log "Requesting SSL certificate from Let's Encrypt..."
    
    # Run certbot
    docker-compose run --rm certbot certonly \
        --webroot \
        -w /var/www/certbot \
        --force-renewal \
        --email "$EMAIL" \
        -d "$DOMAIN" \
        --agree-tos \
        --no-eff-email
    
    if [[ $? -eq 0 ]]; then
        log "SSL certificate obtained successfully"
    else
        error "Failed to obtain SSL certificate"
    fi
}

# Restore nginx config
restore_nginx_config() {
    log "Restoring production nginx configuration..."
    
    # Restore backup if exists, otherwise use the production config
    if [[ -f nginx/sites-available/upload.poxica.com.conf.backup ]]; then
        mv nginx/sites-available/upload.poxica.com.conf.backup nginx/sites-available/upload.poxica.com.conf
    fi
    
    # Remove temp config
    rm -f nginx/temp-ssl-setup.conf
}

# Test SSL certificate
test_certificate() {
    log "Testing SSL certificate..."
    
    # Restart nginx with new certificate
    docker-compose restart nginx
    
    # Wait for nginx to restart
    sleep 10
    
    # Test HTTPS
    if curl -f "https://$DOMAIN/health" 2>/dev/null; then
        log "SSL certificate is working correctly"
    else
        warn "SSL certificate might not be working properly"
        log "You can test manually with: curl -I https://$DOMAIN"
    fi
}

# Setup automatic renewal
setup_auto_renewal() {
    log "Setting up automatic SSL renewal..."
    
    # Create renewal script
    cat > /usr/local/bin/renew-poxica-ssl.sh << EOF
#!/bin/bash
cd /opt/poxica-upload-service
/usr/bin/docker-compose run --rm certbot renew --quiet
if [ \$? -eq 0 ]; then
    /usr/bin/docker-compose restart nginx
    echo "SSL certificate renewed successfully"
else
    echo "SSL certificate renewal failed" | mail -s "SSL Renewal Failed" $EMAIL
fi
EOF
    
    chmod +x /usr/local/bin/renew-poxica-ssl.sh
    
    # Add cron job for renewal (twice daily as recommended)
    (crontab -l 2>/dev/null; echo "30 2,14 * * * /usr/local/bin/renew-poxica-ssl.sh") | crontab -
    
    log "Automatic renewal configured (runs twice daily)"
}

# Verify installation
verify_installation() {
    log "Verifying SSL installation..."
    
    # Check certificate validity
    echo | openssl s_client -servername "$DOMAIN" -connect "$DOMAIN:443" 2>/dev/null | openssl x509 -noout -dates
    
    # Check SSL Labs rating (optional)
    log "You can check your SSL configuration at:"
    log "https://www.ssllabs.com/ssltest/analyze.html?d=$DOMAIN"
}

# Main function
main() {
    header
    
    check_root
    validate_email
    check_dns
    create_temp_nginx_config
    start_nginx_for_challenge
    get_certificate
    restore_nginx_config
    test_certificate
    setup_auto_renewal
    verify_installation
    
    echo -e "${GREEN}
====================================
   SSL SETUP COMPLETED SUCCESSFULLY!
====================================

Your SSL certificate has been installed and configured.

Certificate details:
- Domain: $DOMAIN
- Email: $EMAIL
- Auto-renewal: Enabled (twice daily)

Next steps:
1. Start your application: docker-compose up -d
2. Test HTTPS: curl -I https://$DOMAIN
3. Check SSL rating: https://www.ssllabs.com/ssltest/analyze.html?d=$DOMAIN

Certificate will be automatically renewed before expiration.
====================================
${NC}"
}

# Error handling
trap 'error "SSL setup failed at line $LINENO"' ERR

# Run main function
main "$@"