#!/bin/bash
# =============================================================================
# Web Hosting Security Agent - Installer for Linux/cPanel/WHM servers
#
# Usage:
#   sudo bash install.sh
#
# This script:
#   1. Installs the agent to /opt/hosting-agent
#   2. Copies config to /etc/hosting-agent
#   3. Configures and starts the systemd service
#   4. Optionally configures Telegram + API creds
# =============================================================================

set -euo pipefail

export SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
export INSTALL_DIR="/opt/hosting-agent"
export CONFIG_DIR="/etc/hosting-agent"
export LOG_DIR="/var/log/hosting-agent"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
err()   { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

# --- Preflight checks ---
[[ "$EUID" -eq 0 ]] || err "This script must be run as root. Use: sudo bash install.sh"
command -v python3 >/dev/null || err "python3 is required but not installed. Run: yum install -y python3"
command -v systemctl >/dev/null || warn "systemctl not found - writing files but not starting service"

# --- Create directories ---
info "Creating directories..."
mkdir -p "$INSTALL_DIR" "$CONFIG_DIR" "$LOG_DIR"
chmod 750 "$CONFIG_DIR"
chmod 750 "$LOG_DIR"

# --- Copy agent files ---
info "Copying agent files to $INSTALL_DIR..."
cp "$SCRIPT_DIR"/agent.py \
   "$SCRIPT_DIR"/reporter.py \
   "$SCRIPT_DIR"/ip_blocker.py \
   "$SCRIPT_DIR"/ssh_guard.py \
   "$SCRIPT_DIR"/apache_guard.py \
   "$SCRIPT_DIR"/port_scanner.py \
   "$SCRIPT_DIR"/process_monitor.py \
   "$SCRIPT_DIR"/file_integrity.py \
   "$SCRIPT_DIR"/server_shield.py \
   "$SCRIPT_DIR"/monitor.sh \
   "$SCRIPT_DIR"/requirements.txt "$INSTALL_DIR/"

cp "$SCRIPT_DIR"/config.ini "$CONFIG_DIR/config.ini"
cp "$SCRIPT_DIR"/whitelist.txt "$CONFIG_DIR/whitelist.txt"
chmod 600 "$CONFIG_DIR/config.ini"
chmod 600 "$CONFIG_DIR/whitelist.txt"

# --- Install systemd service ---
if command -v systemctl >/dev/null; then
    info "Installing systemd service..."
    cp "$SCRIPT_DIR"/hosting-agent.service /etc/systemd/system/hosting-agent.service
    systemctl daemon-reload
    systemctl enable hosting-agent.service
fi

# --- Python deps ---
info "Installing Python dependencies..."
pip3 install -r "$INSTALL_DIR/requirements.txt" >/dev/null 2>&1 || warn "pip install had warnings - check requirements manually"

# --- Configuration prompts ---
echo ""
echo "==============================="
echo " Agent Configuration"
echo "==============================="

read -rp "Server display name (default: $(hostname)): " SERVER_NAME
SERVER_NAME="${SERVER_NAME:-$(hostname)}"
sed -i "s/^server_name =.*/server_name = $SERVER_NAME/" "$CONFIG_DIR/config.ini"

read -rp "Dashboard API URL (empty to skip dashboard): " API_URL
if [[ -n "$API_URL" ]]; then
    sed -i "s|^api_url =.*|api_url = $API_URL|" "$CONFIG_DIR/config.ini"
    read -rp "Dashboard Agent API Key: " API_KEY
    if [[ -n "$API_KEY" ]]; then
        sed -i "s/^agent_api_key =.*/agent_api_key = $API_KEY/" "$CONFIG_DIR/config.ini"
    fi
fi

echo ""
echo "--- Telegram (optional) ---"
read -rp "Enable Telegram alerts? [y/N]: " TG_YES
if [[ "$TG_YES" =~ ^[Yy]$ ]]; then
    sed -i "s/^enabled =.*/enabled = true/" "$CONFIG_DIR/config.ini"
    read -rp "Telegram Bot Token (from @BotFather): " TG_TOKEN
    sed -i "s/^bot_token =.*/bot_token = $TG_TOKEN/" "$CONFIG_DIR/config.ini"
    read -rp "Telegram Chat ID: " TG_CHAT
    sed -i "s/^chat_id =.*/chat_id = $TG_CHAT/" "$CONFIG_DIR/config.ini"
fi

echo ""
echo "--- Firewall / Blocking method ---"
echo "Choose the method the agent uses to block IPs:"
echo "  1. csf (recommended for cPanel/WHM)"
echo "  2. firewalld (RHEL/CentOS 7+)"
echo "  3. iptables (any Linux)"
read -rp "Select [1-3, default 1]: " FM
case "${FM:-1}" in
    2) sed -i "s/^block_method =.*/block_method = firewalld/" "$CONFIG_DIR/config.ini";;
    3) sed -i "s/^block_method =.*/block_method = iptables/" "$CONFIG_DIR/config.ini";;
    *) sed -i "s/^block_method =.*/block_method = csf/" "$CONFIG_DIR/config.ini";;
esac

# --- Start service ---
if command -v systemctl >/dev/null; then
    info "Starting hosting-agent service..."
    systemctl start hosting-agent.service

    sleep 2
    if systemctl is-active --quiet hosting-agent.service; then
        info "Service is running."
    else
        warn "Service failed to start. Check: journalctl -u hosting-agent.service"
    fi
fi

# --- Final summary ---
echo ""
echo "=============================================="
echo "✅ Installation complete!"
echo "=============================================="
echo " Agent files:  $INSTALL_DIR"
echo " Config:       $CONFIG_DIR/config.ini"
echo " Log:          $LOG_DIR/agent.log"
echo ""
echo " Useful commands:"
echo "   sudo systemctl status hosting-agent     # check status"
echo "   sudo journalctl -u hosting-agent -f     # live logs"
echo "   python3 $INSTALL_DIR/agent.py --check   # run one check round"
echo ""
echo " IMPORTANT: Edit $CONFIG_DIR/whitelist.txt to add your own"
echo " trusted IPs that should never be blocked."
echo "=============================================="
