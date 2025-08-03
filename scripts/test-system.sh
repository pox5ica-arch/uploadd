#!/bin/bash

# ====================================
# Poxica Upload Service - System Tests
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
TEST_EMAIL="test@example.com"

# Counters
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Functions
log() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

test_passed() {
    echo -e "${GREEN}✓${NC} $1"
    ((PASSED_TESTS++))
    ((TOTAL_TESTS++))
}

test_failed() {
    echo -e "${RED}✗${NC} $1"
    ((FAILED_TESTS++))
    ((TOTAL_TESTS++))
}

run_test() {
    local test_name="$1"
    local test_command="$2"
    
    echo -n "Testing $test_name... "
    
    if eval "$test_command" >/dev/null 2>&1; then
        test_passed "$test_name"
        return 0
    else
        test_failed "$test_name"
        return 1
    fi
}

header() {
    echo -e "${BLUE}
====================================
    SYSTEM TESTS - POXICA UPLOAD
====================================${NC}"
}

# Test Docker installation
test_docker() {
    echo -e "\n${BLUE}Testing Docker Installation:${NC}"
    
    run_test "Docker is installed" "docker --version"
    run_test "Docker is running" "docker info"
    run_test "Docker Compose is available" "docker compose version"
    
    # Test Docker permissions
    if docker ps >/dev/null 2>&1; then
        test_passed "Docker permissions"
    else
        test_failed "Docker permissions (try: sudo usermod -aG docker \$USER)"
    fi
}

# Test services are running
test_services() {
    echo -e "\n${BLUE}Testing Services:${NC}"
    
    # Change to project directory
    cd /opt/poxica-upload-service 2>/dev/null || cd .
    
    run_test "Database container" "docker-compose ps | grep -q 'db.*Up'"
    run_test "Redis container" "docker-compose ps | grep -q 'redis.*Up'"
    run_test "Backend container" "docker-compose ps | grep -q 'backend.*Up'"
    run_test "Frontend container" "docker-compose ps | grep -q 'frontend.*Up'"
    run_test "Nginx container" "docker-compose ps | grep -q 'nginx.*Up'"
}

# Test network connectivity
test_network() {
    echo -e "\n${BLUE}Testing Network Connectivity:${NC}"
    
    run_test "Internet connectivity" "curl -s --connect-timeout 5 https://google.com"
    run_test "DNS resolution" "nslookup $DOMAIN"
    
    # Test local services
    run_test "Database port (5432)" "nc -z localhost 5432"
    run_test "Redis port (6379)" "nc -z localhost 6379"
    run_test "HTTP port (80)" "nc -z localhost 80"
    run_test "HTTPS port (443)" "nc -z localhost 443"
}

# Test HTTP/HTTPS endpoints
test_endpoints() {
    echo -e "\n${BLUE}Testing HTTP/HTTPS Endpoints:${NC}"
    
    # Test health endpoint
    if curl -f -s "https://$DOMAIN/health" >/dev/null 2>&1; then
        test_passed "HTTPS health endpoint"
    else
        test_failed "HTTPS health endpoint"
        
        # Fallback to HTTP
        if curl -f -s "http://$DOMAIN/health" >/dev/null 2>&1; then
            test_passed "HTTP health endpoint (HTTPS not working)"
        else
            test_failed "HTTP health endpoint"
        fi
    fi
    
    # Test HTTPS redirect
    HTTP_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "http://$DOMAIN" 2>/dev/null || echo "000")
    if [[ "$HTTP_RESPONSE" == "301" ]] || [[ "$HTTP_RESPONSE" == "302" ]]; then
        test_passed "HTTP to HTTPS redirect"
    else
        test_failed "HTTP to HTTPS redirect (got: $HTTP_RESPONSE)"
    fi
    
    # Test frontend
    if curl -f -s "https://$DOMAIN/" >/dev/null 2>&1; then
        test_passed "Frontend accessibility"
    else
        test_failed "Frontend accessibility"
    fi
    
    # Test API endpoint
    if curl -f -s "https://$DOMAIN/api/health" >/dev/null 2>&1; then
        test_passed "API endpoint"
    else
        test_failed "API endpoint"
    fi
}

# Test SSL certificate
test_ssl() {
    echo -e "\n${BLUE}Testing SSL Certificate:${NC}"
    
    # Test SSL certificate validity
    if echo | openssl s_client -servername "$DOMAIN" -connect "$DOMAIN:443" 2>/dev/null | openssl x509 -noout -dates >/dev/null 2>&1; then
        test_passed "SSL certificate is valid"
        
        # Check expiration
        CERT_EXPIRY=$(echo | openssl s_client -servername "$DOMAIN" -connect "$DOMAIN:443" 2>/dev/null | openssl x509 -noout -enddate | cut -d= -f2)
        EXPIRY_EPOCH=$(date -d "$CERT_EXPIRY" +%s 2>/dev/null || echo "0")
        CURRENT_EPOCH=$(date +%s)
        DAYS_UNTIL_EXPIRY=$(( (EXPIRY_EPOCH - CURRENT_EPOCH) / 86400 ))
        
        if [[ $DAYS_UNTIL_EXPIRY -gt 30 ]]; then
            test_passed "SSL certificate expiration ($DAYS_UNTIL_EXPIRY days remaining)"
        elif [[ $DAYS_UNTIL_EXPIRY -gt 7 ]]; then
            warn "SSL certificate expires in $DAYS_UNTIL_EXPIRY days"
            test_passed "SSL certificate expiration (warning: $DAYS_UNTIL_EXPIRY days)"
        else
            test_failed "SSL certificate expires soon ($DAYS_UNTIL_EXPIRY days)"
        fi
    else
        test_failed "SSL certificate is invalid"
    fi
    
    # Test SSL configuration
    if curl -s --tlsv1.2 "https://$DOMAIN/health" >/dev/null 2>&1; then
        test_passed "TLS 1.2 support"
    else
        test_failed "TLS 1.2 support"
    fi
}

# Test database connectivity
test_database() {
    echo -e "\n${BLUE}Testing Database:${NC}"
    
    cd /opt/poxica-upload-service 2>/dev/null || cd .
    
    # Test database connection
    if docker-compose exec -T db psql -U poxica_user -d poxica_upload -c "SELECT 1;" >/dev/null 2>&1; then
        test_passed "Database connection"
        
        # Test tables exist
        TABLES=$(docker-compose exec -T db psql -U poxica_user -d poxica_upload -t -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='public';" 2>/dev/null | tr -d ' ')
        if [[ "$TABLES" -gt 0 ]]; then
            test_passed "Database tables ($TABLES tables found)"
        else
            test_failed "Database tables (no tables found)"
        fi
    else
        test_failed "Database connection"
    fi
}

# Test Redis connectivity
test_redis() {
    echo -e "\n${BLUE}Testing Redis:${NC}"
    
    cd /opt/poxica-upload-service 2>/dev/null || cd .
    
    if docker-compose exec -T redis redis-cli ping | grep -q "PONG"; then
        test_passed "Redis connection"
    else
        test_failed "Redis connection"
    fi
}

# Test Google Drive integration
test_google_drive() {
    echo -e "\n${BLUE}Testing Google Drive Integration:${NC}"
    
    cd /opt/poxica-upload-service 2>/dev/null || cd .
    
    # Check if credentials file exists
    if [[ -f credentials/google-credentials.json ]]; then
        test_passed "Google credentials file exists"
        
        # Test credentials validity (basic JSON check)
        if python3 -c "import json; json.load(open('credentials/google-credentials.json'))" 2>/dev/null; then
            test_passed "Google credentials file is valid JSON"
        else
            test_failed "Google credentials file is invalid JSON"
        fi
    else
        test_failed "Google credentials file missing"
    fi
    
    # Test Google Drive API (through backend health check)
    HEALTH_RESPONSE=$(curl -s "https://$DOMAIN/health" 2>/dev/null || echo '{}')
    if echo "$HEALTH_RESPONSE" | grep -q '"google_drive":"healthy"'; then
        test_passed "Google Drive API connection"
    else
        test_failed "Google Drive API connection"
    fi
}

# Test email configuration
test_email() {
    echo -e "\n${BLUE}Testing Email Configuration:${NC}"
    
    cd /opt/poxica-upload-service 2>/dev/null || cd .
    
    # Check environment variables
    if [[ -f .env ]]; then
        source .env
        
        if [[ -n "$SMTP_USERNAME" ]] && [[ -n "$SMTP_PASSWORD" ]]; then
            test_passed "SMTP credentials configured"
        else
            test_failed "SMTP credentials not configured"
        fi
        
        if [[ -n "$ADMIN_EMAIL" ]]; then
            test_passed "Admin email configured"
        else
            test_failed "Admin email not configured"
        fi
    else
        test_failed "Environment file not found"
    fi
    
    # Test email service through health check
    HEALTH_RESPONSE=$(curl -s "https://$DOMAIN/health" 2>/dev/null || echo '{}')
    if echo "$HEALTH_RESPONSE" | grep -q '"email":"healthy"'; then
        test_passed "Email service connection"
    else
        test_failed "Email service connection"
    fi
}

# Test file permissions
test_permissions() {
    echo -e "\n${BLUE}Testing File Permissions:${NC}"
    
    cd /opt/poxica-upload-service 2>/dev/null || cd .
    
    # Test write permissions for backups
    if [[ -w backups ]]; then
        test_passed "Backup directory writable"
    else
        test_failed "Backup directory not writable"
    fi
    
    # Test log directory
    if [[ -w logs ]] || mkdir -p logs 2>/dev/null; then
        test_passed "Log directory accessible"
    else
        test_failed "Log directory not accessible"
    fi
    
    # Test credentials directory
    if [[ -r credentials ]]; then
        test_passed "Credentials directory readable"
    else
        test_failed "Credentials directory not readable"
    fi
}

# Test system resources
test_resources() {
    echo -e "\n${BLUE}Testing System Resources:${NC}"
    
    # Check disk space
    DISK_USAGE=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
    if [[ $DISK_USAGE -lt 80 ]]; then
        test_passed "Disk space ($DISK_USAGE% used)"
    elif [[ $DISK_USAGE -lt 90 ]]; then
        warn "Disk space is getting low ($DISK_USAGE% used)"
        test_passed "Disk space ($DISK_USAGE% used, warning)"
    else
        test_failed "Disk space critically low ($DISK_USAGE% used)"
    fi
    
    # Check memory
    MEMORY_USAGE=$(free | awk 'NR==2{printf "%.0f", $3*100/$2}')
    if [[ $MEMORY_USAGE -lt 80 ]]; then
        test_passed "Memory usage ($MEMORY_USAGE% used)"
    elif [[ $MEMORY_USAGE -lt 90 ]]; then
        warn "Memory usage is high ($MEMORY_USAGE% used)"
        test_passed "Memory usage ($MEMORY_USAGE% used, warning)"
    else
        test_failed "Memory usage critically high ($MEMORY_USAGE% used)"
    fi
    
    # Check load average
    LOAD_AVG=$(uptime | awk -F'load average:' '{print $2}' | awk '{print $1}' | sed 's/,//')
    CPU_CORES=$(nproc)
    if (( $(echo "$LOAD_AVG < $CPU_CORES" | bc -l) )); then
        test_passed "System load ($LOAD_AVG on $CPU_CORES cores)"
    else
        test_failed "System load high ($LOAD_AVG on $CPU_CORES cores)"
    fi
}

# Show summary
show_summary() {
    echo -e "\n${BLUE}====================================
    TEST SUMMARY
====================================${NC}"
    
    echo -e "Total tests: $TOTAL_TESTS"
    echo -e "${GREEN}Passed: $PASSED_TESTS${NC}"
    echo -e "${RED}Failed: $FAILED_TESTS${NC}"
    
    if [[ $FAILED_TESTS -eq 0 ]]; then
        echo -e "\n${GREEN}🎉 All tests passed! Your system is working correctly.${NC}"
        exit 0
    else
        echo -e "\n${RED}⚠️  Some tests failed. Please check the issues above.${NC}"
        exit 1
    fi
}

# Main function
main() {
    header
    
    log "Starting system tests for Poxica Upload Service..."
    
    test_docker
    test_services
    test_network
    test_endpoints
    test_ssl
    test_database
    test_redis
    test_google_drive
    test_email
    test_permissions
    test_resources
    
    show_summary
}

# Run tests
main "$@"