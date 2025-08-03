#!/bin/bash

# ====================================
# Poxica Upload Service - Installer
# ====================================

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging functions
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
  POXICA UPLOAD SERVICE INSTALLER
====================================${NC}"
}

# Check if running as root
check_root() {
    if [[ $EUID -ne 0 ]]; then
        error "This script must be run as root (use sudo)"
    fi
}

# Check OS compatibility
check_os() {
    log "Checking OS compatibility..."
    
    if [[ ! -f /etc/os-release ]]; then
        error "Cannot detect OS. This script supports Ubuntu 20.04+ and Debian 11+"
    fi
    
    . /etc/os-release
    
    case $ID in
        ubuntu)
            if [[ $(echo "$VERSION_ID >= 20.04" | bc -l) -eq 0 ]]; then
                error "Ubuntu 20.04 or higher required. Found: $VERSION_ID"
            fi
            ;;
        debian)
            if [[ $(echo "$VERSION_ID >= 11" | bc -l) -eq 0 ]]; then
                error "Debian 11 or higher required. Found: $VERSION_ID"
            fi
            ;;
        *)
            error "Unsupported OS: $ID. This script supports Ubuntu 20.04+ and Debian 11+"
            ;;
    esac
    
    log "OS check passed: $PRETTY_NAME"
}

# Update system packages
update_system() {
    log "Updating system packages..."
    apt-get update
    apt-get upgrade -y
    apt-get install -y \
        curl \
        wget \
        git \
        software-properties-common \
        apt-transport-https \
        ca-certificates \
        gnupg \
        lsb-release \
        bc \
        unzip \
        vim \
        htop \
        ufw
}

# Install Docker
install_docker() {
    log "Installing Docker..."
    
    # Remove old versions
    apt-get remove -y docker docker-engine docker.io containerd runc 2>/dev/null || true
    
    # Add Docker's official GPG key
    curl -fsSL https://download.docker.com/linux/$ID/gpg | gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
    
    # Add Docker repository
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/$ID $(lsb_release -cs) stable" > /etc/apt/sources.list.d/docker.list
    
    # Install Docker
    apt-get update
    apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
    
    # Start and enable Docker
    systemctl start docker
    systemctl enable docker
    
    # Add current user to docker group
    if [[ -n "$SUDO_USER" ]]; then
        usermod -aG docker "$SUDO_USER"
        log "Added $SUDO_USER to docker group"
    fi
    
    # Verify installation
    docker --version
    docker compose version
    
    log "Docker installed successfully"
}

# Configure firewall
setup_firewall() {
    log "Configuring firewall..."
    
    # Reset UFW
    ufw --force reset
    
    # Set default policies
    ufw default deny incoming
    ufw default allow outgoing
    
    # Allow SSH (be careful not to lock yourself out)
    ufw allow 22/tcp
    
    # Allow HTTP and HTTPS
    ufw allow 80/tcp
    ufw allow 443/tcp
    
    # Enable firewall
    ufw --force enable
    
    # Show status
    ufw status verbose
    
    log "Firewall configured successfully"
}

# Create application directories
create_directories() {
    log "Creating application directories..."
    
    # Create main directories
    mkdir -p /opt/poxica-upload-service
    mkdir -p /opt/poxica-upload-service/credentials
    mkdir -p /opt/poxica-upload-service/logs
    mkdir -p /opt/poxica-upload-service/backups
    mkdir -p /var/www/certbot
    
    # Set permissions
    if [[ -n "$SUDO_USER" ]]; then
        chown -R "$SUDO_USER:$SUDO_USER" /opt/poxica-upload-service
        log "Set ownership of /opt/poxica-upload-service to $SUDO_USER"
    fi
    
    log "Directories created successfully"
}

# Install additional tools
install_tools() {
    log "Installing additional tools..."
    
    # Install fail2ban for SSH protection
    apt-get install -y fail2ban
    systemctl enable fail2ban
    
    # Configure fail2ban for SSH
    cat > /etc/fail2ban/jail.local << EOF
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3

[sshd]
enabled = true
port = ssh
logpath = %(sshd_log)s
backend = %(sshd_backend)s
EOF
    
    systemctl restart fail2ban
    
    # Install unattended upgrades
    apt-get install -y unattended-upgrades
    
    # Configure automatic updates
    cat > /etc/apt/apt.conf.d/50unattended-upgrades << EOF
Unattended-Upgrade::Allowed-Origins {
    "\${distro_id}:\${distro_codename}";
    "\${distro_id}:\${distro_codename}-security";
    "\${distro_id}ESMApps:\${distro_codename}-apps-security";
    "\${distro_id}ESM:\${distro_codename}-infra-security";
};
Unattended-Upgrade::AutoFixInterruptedDpkg "true";
Unattended-Upgrade::MinimalSteps "true";
Unattended-Upgrade::Remove-Unused-Dependencies "true";
Unattended-Upgrade::Automatic-Reboot "false";
EOF
    
    cat > /etc/apt/apt.conf.d/20auto-upgrades << EOF
APT::Periodic::Update-Package-Lists "1";
APT::Periodic::Unattended-Upgrade "1";
APT::Periodic::AutocleanInterval "7";
EOF
    
    log "Security tools installed and configured"
}

# Setup log rotation
setup_logrotate() {
    log "Setting up log rotation..."
    
    cat > /etc/logrotate.d/poxica-upload << EOF
/opt/poxica-upload-service/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    copytruncate
    create 644 root root
}
EOF
    
    log "Log rotation configured"
}

# Create systemd service for auto-start
create_systemd_service() {
    log "Creating systemd service..."
    
    cat > /etc/systemd/system/poxica-upload.service << EOF
[Unit]
Description=Poxica Upload Service
Requires=docker.service
After=docker.service

[Service]
Type=oneshot
RemainAfterExit=yes
WorkingDirectory=/opt/poxica-upload-service
ExecStart=/usr/bin/docker compose up -d
ExecStop=/usr/bin/docker compose down
TimeoutStartSec=0

[Install]
WantedBy=multi-user.target
EOF
    
    systemctl daemon-reload
    systemctl enable poxica-upload.service
    
    log "Systemd service created and enabled"
}

# Final instructions
show_final_instructions() {
    echo -e "${BLUE}
====================================
     INSTALLATION COMPLETED!
====================================

Next steps:

1. ${GREEN}Configure your environment:${NC}
   cd /opt/poxica-upload-service
   cp .env.example .env
   nano .env

2. ${GREEN}Add Google Drive credentials:${NC}
   cp your-google-credentials.json credentials/google-credentials.json

3. ${GREEN}Configure DNS:${NC}
   Point upload.poxica.com to this server's IP

4. ${GREEN}Get SSL certificate:${NC}
   chmod +x scripts/setup-ssl.sh
   sudo ./scripts/setup-ssl.sh

5. ${GREEN}Start the application:${NC}
   docker compose up -d

${YELLOW}Important notes:${NC}
- If you added a user to docker group, they need to log out and back in
- Configure your .env file before starting services
- Make sure your domain points to this server before getting SSL
- Check logs with: docker compose logs -f

${GREEN}For support, visit: https://github.com/your-repo/poxica-upload-service${NC}
====================================
${NC}"
}

# Main installation function
main() {
    header
    
    log "Starting Poxica Upload Service installation..."
    
    check_root
    check_os
    update_system
    install_docker
    setup_firewall
    create_directories
    install_tools
    setup_logrotate
    create_systemd_service
    
    log "Installation completed successfully!"
    
    show_final_instructions
}

# Error handling
trap 'error "Installation failed at line $LINENO"' ERR

# Run main function
main "$@"