#!/usr/bin/env python3
"""Deploy NL admin monitoring: upload yandex11 SSH key to hub + env patch. No VPN server changes."""
from __future__ import annotations

import sys
from pathlib import Path

try:
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")
except Exception:
    pass

import paramiko

ROOT = Path(__file__).resolve().parents[1]
REPO = ROOT.parent
HUB_KEY = REPO / "доступы0" / "ssh-key-1775213440930"
YANDEX11_KEY = next(p for p in (REPO / "доступы11").glob("ssh-key-*") if not str(p).endswith(".pub"))
REMOTE_KEY = "/var/www/vpn-hub/storage/app/ssh/yandex11_ed25519"

c = paramiko.SSHClient()
c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect("158.160.252.139", username="oblik", key_filename=str(HUB_KEY), timeout=25, allow_agent=False, look_for_keys=False)

sftp = c.open_sftp()
sftp.file(REMOTE_KEY, "w").write(YANDEX11_KEY.read_text(encoding="utf-8"))
sftp.close()

cmds = [
    f"sudo chown www-data:www-data {REMOTE_KEY}",
    f"sudo chmod 600 {REMOTE_KEY}",
    "cd /var/www/vpn-hub && git fetch origin main && git reset --hard origin/main",
    "cd /var/www/vpn-hub && php scripts/hub-patch-nl-admin.php",
    "cd /var/www/vpn-hub && php artisan config:clear",
    "sudo -u www-data ssh -i /var/www/vpn-hub/storage/app/ssh/yandex11_ed25519 -o BatchMode=yes -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o ConnectTimeout=8 ubuntu@158.160.136.187 'systemctl is-active xray wg-quick@wg0' 2>&1",
]
for cmd in cmds:
    print(f">>> {cmd}")
    _, so, se = c.exec_command(cmd, timeout=120)
    out = so.read().decode("utf-8", errors="replace")
    err = se.read().decode("utf-8", errors="replace")
    if out.strip():
        print(out)
    if err.strip():
        print(err, file=sys.stderr)
    if so.channel.recv_exit_status() != 0 and "git reset" not in cmd and "ssh -i" not in cmd:
        c.close()
        sys.exit(1)

c.close()
print("DEPLOY_NL_ADMIN_OK")
