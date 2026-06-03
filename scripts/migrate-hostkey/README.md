# Hub Yandex → Hostkey (82.24.19.230)

Зеркало без переключения DNS. Боевой hub `158.160.200.205` не меняется.

## Порядок

1. `01-backup-hub.sh` — на **hub** (sudo/root)
2. Скопировать `/home/oblik/hub-mirror-latest.tar.gz` на Hostkey → `/root/`
3. `02-bootstrap-hostkey.sh` — на **Hostkey** (root)
4. `03-restore-mirror.sh` — на **Hostkey**
5. `04-nginx-mirror.sh` — на **Hostkey**
6. `05-smoke-mirror.sh` — проверка: `curl -H 'Host: nadezhda.space' http://82.24.19.230/`

## Переключение в 1 шаг (после DNS A → 82.24.19.230)

7. `06-cutover-dns.sh` — TLS, nginx prod, stop WG

## Оркестратор с ПК

```powershell
python доступы1/_run_hub_mirror.py
```

## Не трогаем до cutover

- DNS `nadezhda.space`
- `.env` на боевом hub
- WG на Hostkey (пока жив Yandex `.164.110`)
