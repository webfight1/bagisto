# Merit Invoice Commands

## Tagantjärgi arvete genereerimine

Kasuta `merit:generate-invoices` commandi, et genereerida Merit arveid tellimustele, millel arve puudub või mille genereerimine ebaõnnestus.

### Põhikasutus

```bash
# Genereeri arved kõikidele "processing" staatuses tellimustele
php artisan merit:generate-invoices

# Genereeri arve konkreetsele tellimusele
php artisan merit:generate-invoices --order-id=150

# Genereeri arved alates kindlast kuupäevast
php artisan merit:generate-invoices --from-date=2026-04-01

# Genereeri arved "completed" staatuses tellimustele
php artisan merit:generate-invoices --status=completed

# Genereeri arved kõikidele tellimustele (sõltumata staatusest)
php artisan merit:generate-invoices --status=all

# Proovi uuesti ebaõnnestunud arveid
php artisan merit:generate-invoices --retry-failed

# Kombinatsioonid
php artisan merit:generate-invoices --from-date=2026-04-01 --retry-failed
php artisan merit:generate-invoices --status=completed --from-date=2026-03-01
```

### Parameetrid

- `--order-id=ID` - Genereeri arve ainult konkreetsele tellimusele
- `--from-date=YYYY-MM-DD` - Genereeri arved alates sellest kuupäevast
- `--status=STATUS` - Filtreeri tellimusi staatuse järgi (vaikimisi: `processing`)
  - `processing` - Töötlemisel olevad tellimused
  - `completed` - Lõpetatud tellimused  
  - `all` - Kõik tellimused
- `--retry-failed` - Kustuta ebaõnnestunud arve kirjed ja proovi uuesti

### Näited

**Probleem:** Tellimus #150 ja #151 said vea "liiga pikk kaubakoodiks"

```bash
# Pärast SKU pikkuse fixi, genereeri need arved uuesti
php artisan merit:generate-invoices --order-id=150 --retry-failed
php artisan merit:generate-invoices --order-id=151 --retry-failed
```

**Probleem:** Täna tehtud tellimused vajavad arveid

```bash
php artisan merit:generate-invoices --from-date=2026-04-11
```

**Probleem:** Kõik ebaõnnestunud arved vaja uuesti proovida

```bash
php artisan merit:generate-invoices --status=all --retry-failed
```

### Väljund

Command näitab progressi ja lõpus kokkuvõtet:

```
Found 5 order(s) to process.
 5/5 [============================] 100%

Results:
✓ Success: 3
⊘ Skipped: 1
✗ Failed: 1
```

- **Success** - Arve edukalt loodud
- **Skipped** - Arve juba eksisteerib või tingimused ei sobi
- **Failed** - Viga arve loomisel (vaata `storage/logs/laravel.log`)

### Logid

Kõik arve loomise detailid logitakse faili:
```
storage/logs/laravel.log
```

Otsi logidest:
- `Creating Merit invoice for order` - Arve loomine algas
- `Merit invoice created successfully` - Arve edukalt loodud
- `Merit invoice creation failed` - Arve loomine ebaõnnestus
