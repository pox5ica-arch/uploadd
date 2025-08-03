#!/bin/bash

# ====================================
# Poxica Upload Service - Backup Script
# ====================================

set -e

# Configuration
BACKUP_DIR="/opt/poxica-upload-service/backups"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Functions
log() {
    echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')] $1${NC}"
}

warn() {
    echo -e "${YELLOW}[$(date '+%Y-%m-%d %H:%M:%S')] $1${NC}"
}

error() {
    echo -e "${RED}[$(date '+%Y-%m-%d %H:%M:%S')] $1${NC}"
    exit 1
}

# Create backup directory
create_backup_dir() {
    mkdir -p "$BACKUP_DIR/$DATE"
    log "Created backup directory: $BACKUP_DIR/$DATE"
}

# Backup database
backup_database() {
    log "Backing up PostgreSQL database..."
    
    docker-compose exec -T db pg_dump -U poxica_user poxica_upload | gzip > "$BACKUP_DIR/$DATE/database.sql.gz"
    
    if [[ ${PIPESTATUS[0]} -eq 0 ]]; then
        log "Database backup completed successfully"
    else
        error "Database backup failed"
    fi
}

# Backup uploaded files (if any local storage)
backup_files() {
    log "Backing up uploaded files..."
    
    if [[ -d "uploads" ]]; then
        tar -czf "$BACKUP_DIR/$DATE/uploads.tar.gz" uploads/
        log "Files backup completed"
    else
        log "No local uploads directory found, skipping file backup"
    fi
}

# Backup configuration
backup_config() {
    log "Backing up configuration files..."
    
    # Backup .env (without sensitive data)
    if [[ -f .env ]]; then
        # Create sanitized version
        grep -v -E "(PASSWORD|SECRET|KEY)" .env > "$BACKUP_DIR/$DATE/env.backup" || true
        log "Environment configuration backed up (sensitive data excluded)"
    fi
    
    # Backup docker-compose files
    cp docker-compose.yml "$BACKUP_DIR/$DATE/" 2>/dev/null || true
    cp docker-compose.prod.yml "$BACKUP_DIR/$DATE/" 2>/dev/null || true
    
    # Backup nginx configuration
    if [[ -d nginx ]]; then
        tar -czf "$BACKUP_DIR/$DATE/nginx_config.tar.gz" nginx/
        log "Nginx configuration backed up"
    fi
    
    # Backup SSL certificates (excluding private keys for security)
    if [[ -d certbot/conf/live ]]; then
        mkdir -p "$BACKUP_DIR/$DATE/ssl"
        cp -r certbot/conf/live/*/fullchain.pem "$BACKUP_DIR/$DATE/ssl/" 2>/dev/null || true
        log "SSL certificates backed up (private keys excluded for security)"
    fi
}

# Create backup summary
create_summary() {
    log "Creating backup summary..."
    
    cat > "$BACKUP_DIR/$DATE/backup_info.txt" << EOF
Poxica Upload Service Backup
============================

Backup Date: $(date)
Backup Location: $BACKUP_DIR/$DATE

Files included:
- database.sql.gz: PostgreSQL database dump
- uploads.tar.gz: Uploaded files (if any)
- env.backup: Environment configuration (sanitized)
- docker-compose.yml: Docker compose configuration
- nginx_config.tar.gz: Nginx configuration
- ssl/: SSL certificates (public keys only)

Restore Instructions:
1. Extract database: gunzip database.sql.gz
2. Restore database: docker-compose exec -T db psql -U poxica_user poxica_upload < database.sql
3. Extract files: tar -xzf uploads.tar.gz
4. Review and apply configuration changes

For full restore instructions, see: README.md
EOF

    # Calculate backup size
    BACKUP_SIZE=$(du -sh "$BACKUP_DIR/$DATE" | cut -f1)
    echo "Backup Size: $BACKUP_SIZE" >> "$BACKUP_DIR/$DATE/backup_info.txt"
    
    log "Backup summary created"
}

# Cleanup old backups
cleanup_old_backups() {
    log "Cleaning up backups older than $RETENTION_DAYS days..."
    
    find "$BACKUP_DIR" -type d -name "2*" -mtime +$RETENTION_DAYS -exec rm -rf {} + 2>/dev/null || true
    
    REMAINING_BACKUPS=$(find "$BACKUP_DIR" -type d -name "2*" | wc -l)
    log "Cleanup completed. Remaining backups: $REMAINING_BACKUPS"
}

# Verify backup
verify_backup() {
    log "Verifying backup integrity..."
    
    # Check if database backup is valid
    if [[ -f "$BACKUP_DIR/$DATE/database.sql.gz" ]]; then
        if gunzip -t "$BACKUP_DIR/$DATE/database.sql.gz" 2>/dev/null; then
            log "Database backup is valid"
        else
            error "Database backup is corrupted"
        fi
    fi
    
    # Check backup completeness
    REQUIRED_FILES=("backup_info.txt")
    for file in "${REQUIRED_FILES[@]}"; do
        if [[ ! -f "$BACKUP_DIR/$DATE/$file" ]]; then
            warn "Missing backup file: $file"
        fi
    done
    
    log "Backup verification completed"
}

# Send notification (if email is configured)
send_notification() {
    log "Sending backup notification..."
    
    # Load environment variables
    if [[ -f .env ]]; then
        source .env
    fi
    
    if [[ -n "$ADMIN_EMAIL" ]] && command -v mail >/dev/null 2>&1; then
        BACKUP_SIZE=$(du -sh "$BACKUP_DIR/$DATE" | cut -f1)
        
        cat << EOF | mail -s "Poxica Upload Service - Backup Completed" "$ADMIN_EMAIL"
Backup completed successfully!

Date: $(date)
Location: $BACKUP_DIR/$DATE
Size: $BACKUP_SIZE
Retention: $RETENTION_DAYS days

Files backed up:
- Database
- Configuration files
- SSL certificates (public only)
- Uploaded files (if any)

The backup is ready and verified.

--
Poxica Upload Service Backup System
EOF
        log "Notification sent to $ADMIN_EMAIL"
    else
        log "Email not configured or mail command not available, skipping notification"
    fi
}

# Main backup function
main() {
    log "Starting Poxica Upload Service backup..."
    
    # Change to project directory
    cd /opt/poxica-upload-service
    
    create_backup_dir
    backup_database
    backup_files
    backup_config
    create_summary
    verify_backup
    cleanup_old_backups
    send_notification
    
    BACKUP_SIZE=$(du -sh "$BACKUP_DIR/$DATE" | cut -f1)
    
    log "Backup completed successfully!"
    log "Backup location: $BACKUP_DIR/$DATE"
    log "Backup size: $BACKUP_SIZE"
}

# Error handling
trap 'error "Backup failed at line $LINENO"' ERR

# Run main function
main "$@"