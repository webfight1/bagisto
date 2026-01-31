# Omniva locations (DB sync + API)

## Eesmärk
Omniva `locations.json` fail on suur. Selle asemel, et seda iga checkouti/API päringu ajal Omniva serverist uuesti tõmmata, salvestame pakiautomaatide asukohad lokaalsesse andmebaasi ja uuendame neid automaatselt 1x päevas.

## Mis tehti

### 1) DB tabel `omniva_locations`
- Lisatud migration:
  - `database/migrations/2026_01_31_151500_create_omniva_locations_table.php`
- Tabel hoiab Omniva location kirjeid (unikaalne `zip`) + aadress/koordinaadid + `raw` JSON.

### 2) Model `OmnivaLocation`
- Lisatud:
  - `app/Models/OmnivaLocation.php`

### 3) Artisan command: `omniva:sync-locations`
- Lisatud:
  - `app/Console/Commands/SyncOmnivaLocations.php`
- Command:
  - Tõmbab `https://www.omniva.ee/locations.json`
  - Filtreerib:
    - `A0_NAME === 'EE'` (Eesti)
    - `TYPE === '0'` (pakiautomaadid)
  - Salvestab DB-sse `upsert` abil `zip` alusel.

### 4) Scheduler (1x päevas)
- Muudetud:
  - `bootstrap/app.php`
- Lisatud schedule:
  - `omniva:sync-locations` jookseb iga päev `03:00`.

### 5) API endpoint WordPress / frontend jaoks
- Lisatud controller:
  - `app/Http/Controllers/Api/OmnivaController.php`
- Lisatud route:
  - `routes/api.php`
- Endpoint:
  - `GET /api/v1/omniva/locations`
- Tagastab DB-st EE pakiautomaadid sorteerituna nime järgi.

## Kuidas käivitada / deploy
Pärast `git pull` serveris:

1) Migrationid
- `php artisan migrate`

2) Esmane sünk
- `php artisan omniva:sync-locations`

3) Cron (vajalik, et scheduler töötaks)
Lisa serveris crontab’i (tee kohanda vastavalt päris `artisan` path’ile):

- `* * * * * php /var/www/bagisto/artisan schedule:run >> /dev/null 2>&1`

## Märkused
- Kood ja migrationid tulevad gitiga.
- Cron on serveri seadistus ja ei tule repo kaudu.
- Hetkel salvestame ainult EE pakiautomaadid (postipunkte `TYPE=1` ei lisa).
