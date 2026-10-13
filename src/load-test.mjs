// RNF-1 · Book search must answer in under 300 ms with 5,000 books.
// Creates books through the API until the catalog has TARGET books, then times searches.
// Env: API_URL, ADMIN_USERNAME, ADMIN_PASSWORD, TARGET (default 5000), RUNS (default 30)
import { isbn, login } from './client.mjs';

const API_URL = process.env.API_URL ?? 'http://localhost:8000/api/v1';
const TARGET = Number(process.env.TARGET ?? 5000);
const RUNS = Number(process.env.RUNS ?? 30);
const api = await login(API_URL, process.env.ADMIN_USERNAME, process.env.ADMIN_PASSWORD);

const categories = (await api.get('/categories')).items;
let total = (await api.get('/books?size=1')).total;
const words = ['río', 'montaña', 'ciudad', 'noche', 'código', 'estrella', 'tiempo', 'mar', 'historia', 'jardín'];
for (let n = 0; total < TARGET; n++) {
  const batch = [];
  for (let k = 0; k < 20 && total + batch.length < TARGET; k++) {
    const i = 900000 + n * 20 + k;
    batch.push(api.post('/books', {
      title: `${words[i % 10]} ${words[(i >> 3) % 10]} ${i}`, author: `Autor ${i % 700}`, isbn: isbn(i),
      category_id: categories[i % 5].id, total_copies: 1 + (i % 4),
    }).catch((e) => { if (e.status !== 409) throw e; }));
  }
  await Promise.all(batch);
  total = (await api.get('/books?size=1')).total;
  process.stdout.write(`\rcatalog: ${total} books`);
}
console.log();

const queries = ['río', 'código', 'Autor 12', 'noche estrella', 'xyz-no-match'];
const times = [];
for (let r = 0; r < RUNS; r++) {
  const q = encodeURIComponent(queries[r % queries.length]);
  const t0 = performance.now();
  await api.get(`/books?q=${q}&size=20&page=1`);
  times.push(performance.now() - t0);
}
times.sort((a, b) => a - b);
const p95 = times[Math.floor(times.length * 0.95) - 1];
console.log(`searches: ${RUNS} · median ${times[Math.floor(times.length / 2)].toFixed(1)} ms · p95 ${p95.toFixed(1)} ms · max ${times.at(-1).toFixed(1)} ms`);
console.log(p95 < 300 ? 'OK: p95 under 300 ms (RNF-1)' : 'FAIL: p95 is 300 ms or more');
process.exit(p95 < 300 ? 0 : 1);
