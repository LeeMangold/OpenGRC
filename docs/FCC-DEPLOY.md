# OpenGRC FCC Compliance — Full-USA Deploy

This guide gets the FCC reskin running on a server with **every U.S.
broadcast station** populated as real data from FCC's public sources.

## Prerequisites

- PHP 8.4 (Composer requires it)
- Node 18+ (for Vite)
- SQLite or MySQL (default config uses SQLite)
- Outbound HTTPS to `transition.fcc.gov` and `data.fcc.gov`
  (verify with: `curl -sI -A curl/8.5.0 https://transition.fcc.gov/Bureaus/MB/Databases/cdbs/facility.zip`)

## One-time setup

```bash
cd /var/www/opengrc
sudo -u www-data git fetch chelstein claude/reskin-open-grc-ZQtUH
sudo -u www-data git checkout -b fcc-reskin chelstein/claude/reskin-open-grc-ZQtUH

sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm ci
sudo -u www-data NODE_OPTIONS=--max-old-space-size=2048 npx vite build

sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan filament:cache-components
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan view:cache
sudo systemctl reload php8.4-fpm
```

## Populate real FCC data

### Quick smoke test (1-2 minutes, ~600 stations)

```bash
sudo -u www-data php artisan fcc:sync --quick
```

### Full sync — every U.S. broadcast station (~10 minutes, ~30K stations)

```bash
sudo -u www-data php artisan fcc:sync
```

This pulls:
- **CDBS** (`transition.fcc.gov/Bureaus/MB/Databases/cdbs/`):
  - `facility.zip` (~3 MB) — every broadcast facility
  - `am_eng_data.zip`, `fm_eng_data.zip`, `tv_eng_data.zip` — engineering
    (lat/lon, ERP, HAAT, AMSL)
  - `party.zip` + `fac_party.zip` (~16 MB) — licensee names
- **ULS** (`data.fcc.gov/download/pub/uls/complete/`):
  - `r_tower.zip`, `a_tower.zip` — every Antenna Structure Registration
- Operational seeder generates realistic EAS / Issues-Programs / Public
  File / station-log / political-file / reg-fee / form-filing records
  for a representative 30-station sample.

### Individual commands

```bash
# All ~30K broadcast facilities + licenses
sudo -u www-data php artisan fcc:import-bulk
sudo -u www-data php artisan fcc:import-bulk --service=fm        # FM only
sudo -u www-data php artisan fcc:import-bulk --limit=500         # smoke test
sudo -u www-data php artisan fcc:import-bulk --skip-licensees    # faster
sudo -u www-data php artisan fcc:import-bulk --skip-download     # use cache

# All Antenna Structure Registrations
sudo -u www-data php artisan fcc:import-asr
sudo -u www-data php artisan fcc:import-asr --limit=200          # smoke test

# LMS augmentation — adds FRN per facility (rate-limited, resumable)
sudo -u www-data php artisan fcc:import-lms                       # all missing FRN
sudo -u www-data php artisan fcc:import-lms --limit=1000          # one batch
sudo -u www-data php artisan fcc:import-lms --calls=KQED,WTOP     # specific
sudo -u www-data php artisan fcc:import-lms --sleep=500           # 500ms between requests

# Single-station live lookup (transition.fcc.gov FM/AM/TV Query — also LMS-backed)
sudo -u www-data php artisan fcc:import --calls=KQED,WTOP-FM,WBZ-AM
```

## Verify

```bash
sudo -u www-data sqlite3 database/opengrc.sqlite "SELECT COUNT(*) FROM fcc_licenses;"
sudo -u www-data sqlite3 database/opengrc.sqlite "SELECT COUNT(*) FROM fcc_facilities;"
sudo -u www-data sqlite3 database/opengrc.sqlite "SELECT COUNT(*) FROM fcc_asr_registrations;"
sudo -u www-data sqlite3 database/opengrc.sqlite \
  "SELECT call_sign, service, channel_or_frequency, licensee FROM fcc_licenses LIMIT 10;"
```

After hard-refresh of `https://your-server/app/dashboard` you should see:
- **Licenses** count ~30K (real)
- **Facilities** ~30K with HAAT / AMSL / coordinates from CDBS
- **ASR Registrations** ~100K real towers
- License Compliance table on dashboard shows the 25 worst-compliance
  stations (sorted non_compliant → at_risk → expiring_soon → active)
- The full list paginated under FCC Compliance > Licenses

## What's real vs synthetic

| Data | Source | Real? |
|------|--------|-------|
| Call signs / facility IDs | CDBS facility.dat | ✅ Real |
| Frequency / channel | CDBS facility.dat | ✅ Real |
| Lat/lon / HAAT / AMSL | CDBS {fm,am,tv}_eng_data.dat | ✅ Real |
| Licensee names | CDBS party.dat + fac_party.dat | ✅ Real |
| License expiration dates | CDBS facility.dat | ✅ Real |
| ASR # / structure type / height | ULS r_tower.zip | ✅ Real |
| FRN per license | LMS publicFacilityDetails (per-facility scrape) | ✅ Real (run fcc:import-lms) |
| 47 CFR rule numbers + titles | Hand-coded from CFR | ✅ Real |
| Compliance scores | Synthetic (so dashboard renders mix) | ⚠️ Demo |
| EAS test logs | Synthetic per sample station | ⚠️ Demo |
| Issues/Programs lists | Synthetic per sample station | ⚠️ Demo |
| Political file entries | Synthetic per sample station | ⚠️ Demo |
| Tower inspections | Synthetic per ASR sample | ⚠️ Demo |

The compliance/operational data is illustrative — a real deployment
would replace those seeders with a feed from the station's own EAS
ENDEC, public file ingest, etc.

## Refreshing data

CDBS dumps are updated nightly by the FCC. Re-run `fcc:sync` weekly
(or daily via cron) to keep the database current:

```cron
# /etc/cron.d/fcc-sync
0 6 * * * www-data /usr/bin/php /var/www/opengrc/artisan fcc:sync >> /var/log/fcc-sync.log 2>&1
```
