// T-21 · Demo seeder through the API: >= 40 books, 10 members, 15 loans, exactly 3 overdue lines.
//
// Why two API URLs: due_at = loaned_at + 14 days and loaned_at is set by the server, so an overdue loan
// cannot be created "now" through the API. API_PAST_URL points to an API instance whose Clock port is
// shifted 20 days back (CLOCK_OFFSET_DAYS=-20); loans created there are 20 days old, i.e. 6 days overdue.
// No SQL and no backdoor endpoint are involved.
//
// Env: API_URL, API_PAST_URL, ADMIN_USERNAME, ADMIN_PASSWORD
import { isbn, login, waitForHealth } from './client.mjs';
import { BOOKS, MEMBERS } from './data.mjs';

const API_URL = process.env.API_URL ?? 'http://localhost:8000/api/v1';
const API_PAST_URL = process.env.API_PAST_URL ?? 'http://localhost:8001/api/v1';
const { ADMIN_USERNAME, ADMIN_PASSWORD } = process.env;

if (!ADMIN_USERNAME || !ADMIN_PASSWORD) {
  console.error('Set ADMIN_USERNAME and ADMIN_PASSWORD.');
  process.exit(1);
}

await waitForHealth(API_URL);
await waitForHealth(API_PAST_URL);
const now = await login(API_URL, ADMIN_USERNAME, ADMIN_PASSWORD);
const past = await login(API_PAST_URL, ADMIN_USERNAME, ADMIN_PASSWORD);

const existing = await now.get('/books?size=1');
if (existing.total > 0) {
  console.log(`The catalog already has ${existing.total} books: the seeder only runs on an empty database. Nothing done.`);
  process.exit(0);
}

const categories = Object.fromEntries((await now.get('/categories')).items.map((c) => [c.name, c.id]));

const books = [];
for (const [i, b] of BOOKS.entries()) {
  books.push(await now.post('/books', {
    title: b.title, author: b.author, isbn: isbn(100000 + i), category_id: categories[b.category], total_copies: b.copies,
  }));
}
const members = [];
for (const m of MEMBERS) members.push(await now.post('/members', m));
console.log(`Created ${books.length} books and ${members.length} members.`);

const lend = (api, member, bookIdx) => api.post('/loans', { member_id: members[member].id, items: bookIdx.map((i) => ({ book_id: books[i].id })) });
const giveBack = async (api, loan) => {
  for (const item of loan.items) await api.post(`/loans/${loan.id}/items/${item.id}/return`);
};

// Plan (15 loans). Rules respected on every call: 1-3 items, no repeats, max 3 unreturned per member,
// a member with an overdue item never borrows again, copies always available.
let loans = 0;

// Phase 1 · 20 days ago (api-past)
// 1a. Three single-item loans never returned -> exactly 3 overdue lines (members 0, 1, 2).
for (const [member, book] of [[0, 0], [1, 1], [2, 2]]) { await lend(past, member, [book]); loans++; }
// 1b. Five loans that were returned on time (members 3..7): history for the report.
for (const [member, idx] of [[3, [0, 3]], [4, [0, 4]], [5, [5, 6, 7]], [6, [3]], [7, [8, 9]]]) {
  const loan = await lend(past, member, idx); loans++;
  await giveBack(past, loan);
}

// Phase 2 · today (api)
// 2a. Seven current loans (members 3..9). Some are returned, some stay open (not overdue yet).
const current = [[3, [0, 10]], [4, [11]], [5, [0, 12, 13]], [6, [14, 15]], [7, [3, 16]], [8, [17]], [9, [0, 18]]];
for (const [n, [member, idx]] of current.entries()) {
  const loan = await lend(now, member, idx); loans++;
  if (n % 2 === 1) await giveBack(now, loan);
}

const overdue = await now.get('/loans/overdue');
const from = new Date(Date.now() - 30 * 86400e3).toISOString().slice(0, 10);
const to = new Date().toISOString().slice(0, 10);
const report = await now.get(`/reports/most-borrowed?from=${from}&to=${to}`);
console.log(`Created ${loans} loans. Overdue lines: ${overdue.total}. Report rows (${from}..${to}): ${report.rows.length}.`);
if (overdue.total !== 3 || loans !== 15 || report.rows.length === 0) {
  console.error('Seed check failed.');
  process.exit(1);
}
console.log('OK: /loans/overdue returns 3 lines and the report has rows.');
