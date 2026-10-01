# EPA lab limits — automated refresh

Replaces the hardcoded `$epaLimits` array in `fill_null_limits.php` with a
cache that's automatically refreshed from EPA's own published tables.

## Why this shape

EPA does **not** publish MCLs as a clean JSON/CSV feed. The authoritative
source is two HTML pages EPA maintains:

- NPDWR (primary, enforceable standards):
  `https://www.epa.gov/ground-water-and-drinking-water/national-primary-drinking-water-regulations`
- SMCL (secondary, aesthetic standards — pH, TDS, iron, sulfate, etc.):
  `https://www.epa.gov/sdwa/secondary-drinking-water-standards-guidance-nuisance-chemicals`

So "automating the download" means scraping those tables on a schedule,
not hitting a REST endpoint. A handful of parameters your original array
included (sodium, ammonia, calcium, alkalinity, the exact turbidity/HPC
numbers, coliform pass/fail) either have no federal MCL or only appear in
a footnote rather than the table cell — those stay in a small hand-curated
file (`epa_limits_overrides.json`) since they can't be scraped, and are
flagged with a `note` explaining why.

## Pipeline

```
fetch_epa_limits.php          → scrapes both pages → data/epa_limits_raw.json
build_epa_limits_cache.php    → normalizes + merges overrides → data/epa_limits_cache.json
fill_null_limits.php          → reads data/epa_limits_cache.json (was: hardcoded array)
```

`fetch_epa_limits.php` never overwrites a good cache with a bad one — if
both fetches fail or parsing finds nothing, it exits 1 and leaves the
existing cache alone. `fill_null_limits.php` falls back to a tiny built-in
safety net (5 entries) if the cache is missing entirely, and logs a
warning if the cache is more than 90 days old, so a broken cron job
degrades loudly instead of silently going stale forever.

## Deploy

1. Copy `epa_config.php`, `epa_scraper_lib.php`, `fetch_epa_limits.php`,
   `build_epa_limits_cache.php`, `epa_limits_overrides.json`, and the
   updated `fill_null_limits.php` into your scripts directory (same
   layout as the original — one level below `db_connection.php`).
2. Requires PHP's `curl` and `dom` extensions (`php-curl`, `php-dom`).
3. Cron — EPA revises these tables occasionally (new NPDWRs, six-year
   reviews), so monthly is plenty:

```cron
# 1st of each month, 3am: refresh EPA limits cache
0 3 1 * * php /path/to/scripts/fetch_epa_limits.php && php /path/to/scripts/build_epa_limits_cache.php >> /path/to/logs/epa_cron.log 2>&1
```

Run `fill_null_limits.php` on whatever schedule you already use (e.g.
after each import job) — it just reads the cache now instead of scraping
live, so it stays fast.

## Maintaining `epa_limits_overrides.json`

- `aliases`: lab-side spelling → the exact contaminant name as it appears
  on the EPA page (e.g. `"tce": "Trichloroethylene"`). Only needed when
  the alias isn't already a substring match of the real name — the
  existing fuzzy-matching in `fill_null_limits.php` handles simple cases
  like `"nitrate"` matching `"Nitrate (measured as Nitrogen)"` on its own.
- `supplemental`: parameters with no federal MCL, or where EPA's number
  lives in a footnote rather than a table cell. Each has a `note`
  explaining why it's hand-maintained — review these periodically since
  they silently won't update even after `fetch_epa_limits.php` runs.

## Testing without hitting epa.gov

`tests/run_parser_test.php` runs the real `parseTables()` function against
local HTML fixtures (`tests/fixture_npdwr.html`, `fixture_smcl.html`) that
mirror the live page structure, then `build_epa_limits_cache.php` runs on
the result — so you can validate the parsing/normalization logic changes
without spamming EPA's site:

```
php tests/run_parser_test.php
php build_epa_limits_cache.php
php tests/run_loader_test.php
```
