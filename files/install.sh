#!/bin/bash

# White Label PBX System - Installation Script
# This script helps you set up the system quickly

set -e  # Exit on any error

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Helper functions
log_info() {
    echo -e "${BLUE}ℹ ${1}${NC}"
}

log_success() {
    echo -e "${GREEN}✓ ${1}${NC}"
}

log_error() {
    echo -e "${RED}✗ ${1}${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠ ${1}${NC}"
}

section() {
    echo ""
    echo -e "${BLUE}============================================${NC}"
    echo -e "${BLUE}  ${1}${NC}"
    echo -e "${BLUE}============================================${NC}"
    echo ""
}

# Check prerequisites
check_prerequisites() {
    section "Checking Prerequisites"
    
    # Check Node.js
    if command -v node &> /dev/null; then
        NODE_VERSION=$(node -v)
        log_success "Node.js installed: $NODE_VERSION"
    else
        log_error "Node.js is not installed"
        log_info "Install from: https://nodejs.org/"
        exit 1
    fi
    
    # Check npm/pnpm
    if command -v pnpm &> /dev/null; then
        PNPM_VERSION=$(pnpm -v)
        log_success "pnpm installed: $PNPM_VERSION"
    else
        log_warning "pnpm not found, installing..."
        npm install -g pnpm
        log_success "pnpm installed"
    fi
    
    # Check MySQL
    if command -v mysql &> /dev/null; then
        MYSQL_VERSION=$(mysql --version | awk '{print $3}')
        log_success "MySQL installed: $MYSQL_VERSION"
    else
        log_warning "MySQL not detected (may be remote)"
        log_info "Ensure MySQL is accessible at your DATABASE_URL"
    fi
}

# Setup environment file
setup_environment() {
    section "Environment Configuration"
    
    if [ -f .env ]; then
        log_warning ".env file already exists"
        read -p "Overwrite? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            log_info "Skipping .env setup"
            return
        fi
    fi
    
    log_info "Copying .env.example to .env"
    cp .env.example .env
    log_success ".env file created"
    
    echo ""
    log_warning "IMPORTANT: You must edit .env with your credentials!"
    log_info "Required values:"
    log_info "  - DATABASE_URL (MySQL connection string)"
    log_info "  - SIGNALWIRE_PROJECT_ID"
    log_info "  - SIGNALWIRE_API_TOKEN"
    log_info "  - SIGNALWIRE_SPACE_URL"
    log_info "  - JWT_SECRET (generate with: openssl rand -base64 32)"
    
    echo ""
    read -p "Press Enter to continue after editing .env..."
}

# Install dependencies
install_dependencies() {
    section "Installing Dependencies"
    
    log_info "Running pnpm install..."
    pnpm install
    log_success "Dependencies installed"
}

# Setup database
setup_database() {
    section "Database Setup"
    
    # Check if .env exists and has DATABASE_URL
    if [ ! -f .env ]; then
        log_error ".env file not found"
        log_info "Run setup_environment first"
        return 1
    fi
    
    # Source the .env file to get DATABASE_URL
    export $(cat .env | grep DATABASE_URL | xargs)
    
    if [ -z "$DATABASE_URL" ]; then
        log_error "DATABASE_URL not set in .env"
        return 1
    fi
    
    log_info "Running database migrations..."
    pnpm run db:push
    log_success "Database schema created"
}

# Test configuration
test_configuration() {
    section "Testing Configuration"
    
    log_info "Running diagnostic script..."
    node diagnose.js
}

# Main installation flow
main() {
    clear
    echo -e "${GREEN}"
    echo "╔══════════════════════════════════════════════╗"
    echo "║   White Label PBX System - Installer        ║"
    echo "╚══════════════════════════════════════════════╝"
    echo -e "${NC}"
    
    check_prerequisites
    setup_environment
    install_dependencies
    
    echo ""
    log_info "Would you like to set up the database now?"
    log_warning "Make sure MySQL is running and DATABASE_URL is configured"
    read -p "Setup database? (y/N): " -n 1 -r
    echo
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        setup_database
    else
        log_info "Skipping database setup"
        log_info "Run 'pnpm run db:push' manually when ready"
    fi
    
    echo ""
    log_info "Would you like to run diagnostic tests?"
    read -p "Run diagnostics? (y/N): " -n 1 -r
    echo
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        test_configuration
    else
        log_info "Skipping diagnostics"
        log_info "Run 'node diagnose.js' manually to test your setup"
    fi
    
    section "Installation Complete!"
    
    echo -e "${GREEN}Next steps:${NC}"
    echo "1. Review and edit .env file with your credentials"
    echo "2. Run 'pnpm run db:push' if you skipped database setup"
    echo "3. Run 'node diagnose.js' to verify configuration"
    echo "4. Run 'pnpm run dev' to start development server"
    echo ""
    echo "📖 For detailed setup instructions, see: SETUP_GUIDE.md"
    echo ""
}

# Run main function
main
