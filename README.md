# test-manager-library-3145556-tool

> **Technical test · ADSO 3145556** — *Manager Library* utilities: demo data seeder and search load test.
> Both use **the API only**, never SQL (data-model §9). Node 20+, no dependencies.

## Demo seeder (T-21)

Creates 40 books, 10 members and 15 loans, leaving **exactly 3 overdue lines**.

```bash
# with infra (recommended)
docker compose --profile seed up --build seed

# or against running APIs
API_URL=http://localhost:8000/api/v1 API_PAST_URL=http://localhost:8001/api/v1 \
ADMIN_USERNAME=admin ADMIN_PASSWORD=… npm run seed
```

**Why two URLs.** `due_at = loaned_at + 14 days` and the server sets `loaned_at`, so an overdue loan cannot be created
"today" through the API. `API_PAST_URL` is a second API instance whose `Clock` port is shifted 20 days back
(`CLOCK_OFFSET_DAYS=-20`): loans created there are 20 days old, i.e. 6 days overdue. No SQL, no backdoor endpoint.

Loan plan (every call respects the rules: 1–3 items, max 3 unreturned per member, no borrowing with overdue items):

| Phase | Members | Loans | Result |
|---|---|---|---|
| 20 days ago | 1–3 | 3 single-item loans, never returned | the 3 overdue lines |
| 20 days ago | 4–8 | 5 loans, returned | history for the report |
| today | 4–10 | 7 loans, every other one returned | open loans, not overdue |

Evidence (run locally against an empty database):

```
Created 40 books and 10 members.
Created 15 loans. Overdue lines: 3. Report rows (2026-09-13..2026-10-13): 19.
OK: /loans/overdue returns 3 lines and the report has rows.
```

It only runs on an empty catalog; a second run does nothing.

## Search load test (RNF-1)

Fills the catalog up to 5,000 books through the API and times `GET /books?q=…`.

```bash
API_URL=http://localhost:8000/api/v1 ADMIN_USERNAME=admin ADMIN_PASSWORD=… npm run load-test
```

Local result: `searches: 30 · median 22.0 ms · p95 27.2 ms · max 27.8 ms` → **OK, under 300 ms**.
