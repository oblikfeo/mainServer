# Hub → Yandex main + Yandex CDN

**Код:** `git pull origin/main` на сервере.  
**С Hostkey переносим только:** `.env`, `storage/`, дамп MySQL — напрямую `ssh/scp` Yandex→Hostkey.

## Подготовка (один раз)

```powershell
python доступы0/_run_yandex_cdn_mirror.py
```

Проверка зеркала (DNS ещё на Hostkey):

```powershell
curl.exe -sI -H "Host: nadezhda.space" http://158.160.200.205:8080/login
```

Обновить только код на main:

```bash
sudo bash /var/www/vpn-hub/scripts/hub-deploy-yandex-cdn.sh
```

## Cutover (1 щелчок)

1. DNS:
   - `@` → **A `158.160.200.205`** (apex на Yandex: webhook WATA, 301 браузеров на www)
   - **`www` → A `158.160.200.205`** — сайт, логин, админка, POST-формы (**обязательно**, CDN POST не поддерживает Laravel)
   - **`cdn` → CNAME yccdn** — VPN xhttp через CDN (обход белых списков)
2. CDN: cert CM с `www` + `cdn`, origin `158.160.200.205:443`, Host header `nadezhda.mooo.com`
3. Без кеша CDN для `/sub/*`, `/api/*`

```powershell
python доступы0/_cutover_cdn_hub.py
sudo bash /var/www/vpn-hub/scripts/migrate-yandex-cdn/13-nginx-apex-post.sh
sudo bash /var/www/vpn-hub/scripts/migrate-yandex-cdn/11-prod-www-env.sh
bash /var/www/vpn-hub/scripts/migrate-yandex-cdn/14-smoke-full.sh
```

Откат: DNS A → `82.24.19.230` (Hostkey backup).

## Скрипты

| Скрипт | Назначение |
|--------|------------|
| `02-bootstrap-yandex.sh` | PHP/MariaDB |
| `03-git-deploy.sh` | **git pull** + composer + npm |
| `04-import-hostkey-data.sh` | .env + storage + mysqldump с Hostkey |
| `04-nginx-cdn-mirror.sh` | Laravel :8080 + CDN xhttp |
| `07-cutover-cdn.sh` | Prod nginx после DNS |
| `11-prod-www-env.sh` | `.env`: APP_URL + WATA на www |
| `12-smoke-prod-cdn.sh` | Smoke через CDN (login, webhook POST) |
| `08-sync-db-only.sh` | Перед cutover: git pull + свежая БД |
