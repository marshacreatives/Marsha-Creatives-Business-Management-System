#!/bin/bash
# =============================================================================
# General server health monitor for web hosting
# Lightweight check that runs via cron or watchdog - no Python required.
# Returns metrics in a machine-readable format or human-readable.
# =============================================================================

set -euo pipefail

TIMESTAMP=$(date -u +"%Y-%m-%dT%H:%M:%S.%3NZ")

get_cpu_usage() {
    # Calculate 1-second CPU sample
    local cpu_line1=$(head -n1 /proc/stat)
    local total1=$(echo "$cpu_line1" | awk '{for (i=2;i<=NF;i++) sum+=$i; print sum}')
    local idle1=$(echo "$cpu_line1" | awk '{print $5}')
    sleep 1
    local cpu_line2=$(head -n1 /proc/stat)
    local total2=$(echo "$cpu_line2" | awk '{for (i=2;i<=NF;i++) sum+=$i; print sum}')
    local idle2=$(echo "$cpu_line2" | awk '{print $5}')
    local total_delta=$((total2 - total1))
    local idle_delta=$((idle2 - idle1))
    local usage=$((100 * (total_delta - idle_delta) / total_delta))
    echo "$usage"
}

get_ram_usage() {
    local meminfo=$(grep -E '^(MemTotal|MemAvailable):' /proc/meminfo)
    local total_kb=$(echo "$meminfo" | grep MemTotal | awk '{print $2}')
    local avail_kb=$(echo "$meminfo" | grep MemAvailable | awk '{print $2}')
    local used_kb=$((total_kb - avail_kb))
    local total_gb=$(echo "scale=2; $total_kb / 1048576" | bc)
    local used_gb=$(echo "scale=2; $used_kb / 1048576" | bc)
    local usage_pct=$(echo "scale=2; ($used_kb * 100) / $total_kb" | bc)
    echo "$usage_pct $total_gb $used_gb"
}

get_disk_usage() {
    local disk_data=$(df -P / | tail -1)
    local used_pct=$(echo "$disk_data" | awk '{print $5}' | sed 's/%//')
    local total_gb=$(echo "$disk_data" | awk '{print $2 / 1048576}')
    local used_gb=$(echo "$disk_data" | awk '{print $3 / 1048576}')
    echo "$used_pct $total_gb $used_gb"
}

get_load_average() {
    cat /proc/loadavg
}

get_active_connections() {
    ss -tan state established 2>/dev/null | wc -l || netstat -tan 2>/dev/null | grep ESTABLISHED | wc -l
}

get_total_processes() {
    ps -e --no-headers 2>/dev/null | wc -l
}

get_uptime() {
    awk '{print int($1)}' /proc/uptime
}

check_services() {
    local services="httpd:apache2:nginx:mysqld:mysql:exim:cpanel:lfd:ftpd:pure-ftpd:vsftpd"
    IFS=':' read -r -a svc_array <<< "$services"
    local status_json="{"
    local first=true
    for svc in "${svc_array[@]}"; do
        if systemctl is-active --quiet "$svc.service" 2>/dev/null; then
            if [ "$first" = false ]; then status_json+=","; fi
            status_json+="\"$svc\":\"running\""
            first=false
        fi
    done
    status_json+="}"
    echo "$status_json"
}

# --- Gather all metrics ---
CPU=$(get_cpu_usage)
RAM_RAW=$(get_ram_usage)
DISK_RAW=$(get_disk_usage)
LOAD_RAW=$(get_load_average)
CONN=$(get_active_connections)
PROCS=$(get_total_processes)
UPTIME=$(get_uptime)
SERVICES=$(check_services)

RAM_PCT=$(echo "$RAM_RAW" | awk '{print $1}')
RAM_TOTAL=$(echo "$RAM_RAW" | awk '{print $2}')
RAM_USED=$(echo "$RAM_RAW" | awk '{print $3}')
DISK_PCT=$(echo "$DISK_RAW" | awk '{print $1}')
DISK_TOTAL=$(echo "$DISK_RAW" | awk '{print $2}')
DISK_USED=$(echo "$DISK_RAW" | awk '{print $3}')
LOAD1=$(echo "$LOAD_RAW" | awk '{print $1}')
LOAD5=$(echo "$LOAD_RAW" | awk '{print $2}')
LOAD15=$(echo "$LOAD_RAW" | awk '{print $3}')

# JSON output (default)
cat <<EOF
{
  "server_name": "$(hostname)",
  "cpu_usage": $CPU,
  "ram_usage": $RAM_PCT,
  "ram_total_gb": $RAM_TOTAL,
  "ram_used_gb": $RAM_USED,
  "disk_usage": $DISK_PCT,
  "disk_total_gb": $DISK_TOTAL,
  "disk_used_gb": $DISK_USED,
  "load_average_1": $LOAD1,
  "load_average_5": $LOAD5,
  "load_average_15": $LOAD15,
  "active_connections": $CONN,
  "total_processes": $PROCS,
  "uptime_seconds": $UPTIME,
  "services_status": $SERVICES,
  "checked_at": "$TIMESTAMP"
}
EOF
