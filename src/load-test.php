<?php

declare(strict_types=1);

// RNF-1 · Book search must answer in under 300 ms with 5,000 books.
// Creates books through the API until the catalog has TARGET books, then times searches.
// Env: API_URL, ADMIN_USERNAME, ADMIN_PASSWORD, TARGET (default 5000), RUNS (default 30)

require __DIR__.'/Client.php';

$apiUrl = env_or('API_URL', 'http://localhost:8000/api/v1');
$target = (int) env_or('TARGET', '5000');
$runs = (int) env_or('RUNS', '30');
$api = Client::login($apiUrl, env_or('ADMIN_USERNAME'), env_or('ADMIN_PASSWORD'));

$categories = $api->get('/categories')['items'];
$words = ['río', 'montaña', 'ciudad', 'noche', 'código', 'estrella', 'tiempo', 'mar', 'historia', 'jardín'];
$total = $api->get('/books?size=1')['total'];
for ($i = 900000; $total < $target; $i++) {
    try {
        $api->post('/books', [
            'title' => "{$words[$i % 10]} {$words[intdiv($i, 8) % 10]} {$i}", 'author' => 'Autor '.($i % 700),
            'isbn' => isbn($i), 'category_id' => $categories[$i % 5]['id'], 'total_copies' => 1 + $i % 4,
        ]);
        $total++;
    } catch (ApiError $e) {
        if ($e->status !== 409) {
            throw $e; // 409 = ISBN already loaded by a previous run
        }
    }
    if ($total % 250 === 0) {
        echo "\rcatalog: {$total} books";
    }
}
echo "\rcatalog: {$total} books\n";

$queries = ['río', 'código', 'Autor 12', 'noche estrella', 'xyz-no-match'];
$times = [];
for ($r = 0; $r < $runs; $r++) {
    $q = rawurlencode($queries[$r % count($queries)]);
    $t0 = hrtime(true);
    $api->get("/books?q={$q}&size=20&page=1");
    $times[] = (hrtime(true) - $t0) / 1e6;
}
sort($times);
$p95 = $times[(int) floor(count($times) * 0.95) - 1];
printf("searches: %d · median %.1f ms · p95 %.1f ms · max %.1f ms\n", $runs, $times[intdiv(count($times), 2)], $p95, end($times));
echo $p95 < 300 ? "OK: p95 under 300 ms (RNF-1)\n" : "FAIL: p95 is 300 ms or more\n";
exit($p95 < 300 ? 0 : 1);
