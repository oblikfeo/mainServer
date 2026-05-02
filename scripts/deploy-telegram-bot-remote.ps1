# Deploy TELEGRAM_LINK_* to hub .env + systemd poller on NLtest (reads Bot token from tg.txt near workspace root).

$ErrorActionPreference = 'Stop'

$scriptsDir = $PSScriptRoot
$mainServerRoot = Split-Path -Parent $scriptsDir
$workspaceRoot = Split-Path -Parent $mainServerRoot

function Find-RepoFile([string]$name) {
    return Get-ChildItem -LiteralPath $workspaceRoot -Recurse -Filter $name -File -ErrorAction SilentlyContinue | Select-Object -First 1
}

function Write-Utf8NoBomLines([string]$path, [string[]]$lines) {
    $enc = New-Object System.Text.UTF8Encoding $false
    $payload = (($lines | ForEach-Object { $_ }) -join "`n") + "`n"
    [System.IO.File]::WriteAllText($path, $payload, $enc)
}

$tgTxt = Join-Path $workspaceRoot 'tg.txt'
if (-not (Test-Path -LiteralPath $tgTxt)) {
    throw 'Missing tg.txt next to mainServer folder.'
}
$content = Get-Content -LiteralPath $tgTxt -Raw
if ($content -notmatch '(\d+:[A-Za-z0-9_-]+)') {
    throw 'tg.txt: bot token line not found.'
}
$botToken = $Matches[1]

$bytes = New-Object byte[] 32
[System.Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($bytes)
$internalHex = -join ($bytes | ForEach-Object { $_.ToString('x2') })
$bytesIn = New-Object byte[] 32
[System.Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($bytesIn)
$botIncomingHex = -join ($bytesIn | ForEach-Object { $_.ToString('x2') })

$runBashOn = Find-RepoFile 'run-bash-on.ps1'
$vhodLib = Find-RepoFile '_lib.ps1'
$serverSource = Join-Path $mainServerRoot 'telegram-link-bot\server.mjs'
$templatesSource = Join-Path $mainServerRoot 'telegram-link-bot\templates.json'
$pkgSource = Join-Path $mainServerRoot 'telegram-link-bot\package.json'
$lockSource = Join-Path $mainServerRoot 'telegram-link-bot\package-lock.json'
$nKey = Find-RepoFile 'ssh-key-nltest-ed25519'

if (-not $runBashOn) { throw 'run-bash-on.ps1 not found.' }
if (-not $vhodLib) { throw '_lib.ps1 not found.' }
if (-not (Test-Path -LiteralPath $serverSource)) { throw ('server.mjs missing: ' + $serverSource) }
if (-not (Test-Path -LiteralPath $templatesSource)) { throw ('templates.json missing: ' + $templatesSource) }
if (-not $nKey -or $nKey.FullName.EndsWith('.pub')) { throw 'ssh-key-nltest-ed25519 private key not found.' }

$hubShBody = @"
#!/usr/bin/env bash
set -euo pipefail
python3 <<'EOS'
from pathlib import Path
import re
p = Path('/var/www/vpn-hub/.env')
text = p.read_text(encoding='utf-8') if p.exists() else ''
lines = text.splitlines()
kv = {
  'TELEGRAM_LINK_BOT_USERNAME': 'nadezhdavpn_bot',
  'TELEGRAM_LINK_INTERNAL_API_TOKEN': '$internalHex',
  'TELEGRAM_BOT_INCOMING_SECRET': '$botIncomingHex',
  'TELEGRAM_CABINET_MIRROR_URL': 'https://nadezhda.space',
  'TELEGRAM_BOT_NOTIFY_BASE_URL': '',
}
keys_done = set()
out = []
for line in lines:
  m = re.match(r'^([A-Z0-9_]+)=', line)
  if m and m.group(1) in kv:
    out.append(m.group(1) + '=' + kv[m.group(1)])
    keys_done.add(m.group(1))
  else:
    out.append(line)
for k, v in kv.items():
  if k not in keys_done:
    out.append(k + '=' + v)
p.parent.mkdir(parents=True, exist_ok=True)
p.write_text('\n'.join(out) + ('\n' if out else ''), encoding='utf-8')
EOS
cd /var/www/vpn-hub
php artisan config:clear
"@

$hubTmp = Join-Path $env:TEMP ('hub-tg-' + [Guid]::NewGuid().ToString('N').Substring(0, 12) + '.sh')
[System.IO.File]::WriteAllText($hubTmp, ($hubShBody -replace "`r`n","`n"), (New-Object System.Text.UTF8Encoding $false))

try {
    & $runBashOn.FullName -Target hub -ScriptPath $hubTmp
    if ($LASTEXITCODE -ne 0) { throw ('hub step failed exit ' + $LASTEXITCODE) }
} finally {
    Remove-Item -LiteralPath $hubTmp -Force -ErrorAction SilentlyContinue
}

$nlTmpDir = Join-Path $env:TEMP ('nl-bot-' + [Guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $nlTmpDir -Force | Out-Null
$nlEnv = Join-Path $nlTmpDir 'nl_telegram_bot.env'

Write-Utf8NoBomLines $nlEnv @(
    "TELEGRAM_BOT_TOKEN=$botToken",
    'TELEGRAM_LINK_SITE_URL=https://nadezhda.space',
    "TELEGRAM_LINK_INTERNAL_API_TOKEN=$internalHex",
    "TELEGRAM_BOT_INCOMING_SECRET=$botIncomingHex",
    'TELEGRAM_WEBHOOK_BASE_URL=',
    'TELEGRAM_SUPPORT_GROUP_ID=',
    'TELEGRAM_ADMIN_TELEGRAM_IDS=',
    'PORT=3850'
)

. $vhodLib.FullName
$nlPriv = Copy-NadezhdaSshKey -KeyFileName $nKey.Name -TempBasename 'nl-deploy'
$scpArgs = (Get-NadezhdaSshBaseArgs -KeyPath $nlPriv) + @('-q', '-B')
$nh = 'root@193.109.69.247'

& scp @($scpArgs + @($serverSource, "${nh}:/tmp/nl_server.mjs")); if ($LASTEXITCODE -ne 0) { throw ('scp server.mjs failed ' + $LASTEXITCODE) }
& scp @($scpArgs + @($templatesSource, "${nh}:/tmp/nl_templates.json")); if ($LASTEXITCODE -ne 0) { throw ('scp templates.json failed ' + $LASTEXITCODE) }
& scp @($scpArgs + @($pkgSource, "${nh}:/tmp/nl_pkg.json")); if ($LASTEXITCODE -ne 0) { throw ('scp package.json failed ' + $LASTEXITCODE) }
if (Test-Path -LiteralPath $lockSource) {
    & scp @($scpArgs + @($lockSource, "${nh}:/tmp/nl_pkg_lock.json")); if ($LASTEXITCODE -ne 0) { throw ('scp package-lock.json failed ' + $LASTEXITCODE) }
}
& scp @($scpArgs + @($nlEnv, "${nh}:/tmp/nl_telegram_bot.env")); if ($LASTEXITCODE -ne 0) { throw ('scp env failed ' + $LASTEXITCODE) }

$nlSetup = Join-Path $nlTmpDir 'nl_setup_remote.sh'

$nlBash = @'
#!/usr/bin/env bash
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive
if ! command -v node >/dev/null 2>&1; then
  curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
  apt-get update -y
  apt-get install -y nodejs
fi
mkdir -p /opt/telegram-link-bot
install -m 644 /tmp/nl_server.mjs /opt/telegram-link-bot/server.mjs
install -m 644 /tmp/nl_templates.json /opt/telegram-link-bot/templates.json
install -m 644 /tmp/nl_pkg.json /opt/telegram-link-bot/package.json
if [[ -f /tmp/nl_pkg_lock.json ]]; then install -m 644 /tmp/nl_pkg_lock.json /opt/telegram-link-bot/package-lock.json; fi
install -m 600 /tmp/nl_telegram_bot.env /etc/telegram-link-bot.env
cd /opt/telegram-link-bot
if [[ -f package-lock.json ]]; then npm ci --omit=dev; else npm install --omit=dev; fi
cat > /etc/systemd/system/telegram-link-bot.service <<'UNIT'
[Unit]
Description=Telegram bot Nadezhda (webhook + HTTP)
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
EnvironmentFile=/etc/telegram-link-bot.env
WorkingDirectory=/opt/telegram-link-bot
ExecStart=/usr/bin/node /opt/telegram-link-bot/server.mjs
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
UNIT

systemctl daemon-reload
systemctl enable telegram-link-bot.service
systemctl restart telegram-link-bot.service
systemctl --no-pager -l status telegram-link-bot.service || true
node --version || true

rm -f /tmp/nl_server.mjs /tmp/nl_templates.json /tmp/nl_pkg.json /tmp/nl_pkg_lock.json /tmp/nl_telegram_bot.env
'@

[System.IO.File]::WriteAllText($nlSetup, ($nlBash -replace "`r`n","`n"), (New-Object System.Text.UTF8Encoding $false))

Get-Content -LiteralPath $nlSetup -Raw | & ssh @((Get-NadezhdaSshBaseArgs -KeyPath $nlPriv) + @($nh)) 'bash -s'

if ($LASTEXITCODE -ne 0) { throw ('NL deploy failed exit ' + $LASTEXITCODE) }

Remove-Item -LiteralPath $nlTmpDir -Recurse -Force -ErrorAction SilentlyContinue

Write-Host 'OK: hub .env + telegram-link-bot.service on NLtest.'
