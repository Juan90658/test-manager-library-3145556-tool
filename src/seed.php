<?php

declare(strict_types=1);

// T-21 · Demo seeder through the API: >= 40 books, 10 members, 15 loans, exactly 3 overdue lines.
//
// Why two API URLs: due_at = loaned_at + 14 days and loaned_at is set by the server, so an overdue loan
// cannot be created "now" through the API. API_PAST_URL points to an API instance whose Clock port is
// shifted 20 days back (CLOCK_OFFSET_DAYS=-20); loans created there are 20 days old, i.e. 6 days overdue.
// No SQL and no backdoor endpoint are involved.
//
// Env: API_URL, API_PAST_URL, ADMIN_USERNAME, ADMIN_PASSWORD

require __DIR__.'/Client.php';
require __DIR__.'/data.php';

$apiUrl = env_or('API_URL', 'http://localhost:8000/api/v1');
$apiPastUrl = env_or('API_PAST_URL', 'http://localhost:8001/api/v1');
$user = env_or('ADMIN_USERNAME');
$pass = env_or('ADMIN_PASSWORD');

Client::waitForHealth($apiUrl);
Client::waitForHealth($apiPastUrl);
$now = Client::login($apiUrl, $user, $pass);
$past = Client::login($apiPastUrl, $user, $pass);

$existing = $now->get('/books?size=1');
if ($existing['total'] > 0) {
    echo "The catalog already has {$existing['total']} books: the seeder only runs on an empty database. Nothing done.\n";
    exit(0);
}

$categories = array_column($now->get('/categories')['items'], 'id', 'name');

$books = [];
foreach (BOOKS as $i => $b) {
    $books[] = $now->post('/books', [
        'title' => $b['title'], 'author' => $b['author'], 'isbn' => isbn(100000 + $i),
        'category_id' => $categories[$b['category']], 'total_copies' => $b['copies'],
    ]);
}
$members = [];
foreach (MEMBERS as $m) {
    $members[] = $now->post('/members', $m);
}
printf("Created %d books and %d members.\n", count($books), count($members));

$lend = fn (Client $api, int $member, array $bookIdx) => $api->post('/loans', [
    'member_id' => $members[$member]['id'],
    'items' => array_map(fn ($i) => ['book_id' => $books[$i]['id']], $bookIdx),
]);
$giveBack = function (Client $api, array $loan): void {
    foreach ($loan['items'] as $item) {
        $api->post("/loans/{$loan['id']}/items/{$item['id']}/return");
    }
};

// Plan (15 loans). Rules respected on every call: 1-3 items, no repeats, max 3 unreturned per member,
// a member with an overdue item never borrows again, copies always available.
$loans = 0;

// Phase 1 · 20 days ago (api-past)
// 1a. Three single-item loans never returned -> exactly 3 overdue lines (members 0, 1, 2).
foreach ([[0, 0], [1, 1], [2, 2]] as [$member, $book]) {
    $lend($past, $member, [$book]);
    $loans++;
}
// 1b. Five loans returned on time (members 3..7): history for the report.
foreach ([[3, [0, 3]], [4, [0, 4]], [5, [5, 6, 7]], [6, [3]], [7, [8, 9]]] as [$member, $idx]) {
    $giveBack($past, $lend($past, $member, $idx));
    $loans++;
}

// Phase 2 · today (api)
// 2a. Seven current loans (members 3..9). Every other one is returned; the rest stay open, not overdue yet.
$current = [[3, [0, 10]], [4, [11]], [5, [0, 12, 13]], [6, [14, 15]], [7, [3, 16]], [8, [17]], [9, [0, 18]]];
foreach ($current as $n => [$member, $idx]) {
    $loan = $lend($now, $member, $idx);
    $loans++;
    if ($n % 2 === 1) {
        $giveBack($now, $loan);
    }
}

$overdue = $now->get('/loans/overdue');
$from = gmdate('Y-m-d', time() - 30 * 86400);
$to = gmdate('Y-m-d');
$report = $now->get("/reports/most-borrowed?from={$from}&to={$to}");
printf("Created %d loans. Overdue lines: %d. Report rows (%s..%s): %d.\n", $loans, $overdue['total'], $from, $to, count($report['rows']));

if ($overdue['total'] !== 3 || $loans !== 15 || count($report['rows']) === 0) {
    fwrite(STDERR, "Seed check failed.\n");
    exit(1);
}
echo "OK: /loans/overdue returns 3 lines and the report has rows.\n";
