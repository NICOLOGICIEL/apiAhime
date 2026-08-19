<?php
function request(string $method, string $url, array $data = [], array $headers = []): array {
    $options = ['http' => ['method' => $method, 'timeout' => 10, 'ignore_errors' => true, 'header' => $headers]];
    if ($method === 'POST') {
        $body = http_build_query($data);
        $options['http']['content'] = $body;
        $options['http']['header'][] = 'Content-Type: application/x-www-form-urlencoded';
    }

    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    $status = 'N/A';
    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $header) {
            if (preg_match('#^HTTP/\d\.\d\s+(\d+)#', $header, $m)) {
                $status = (int) $m[1];
                break;
            }
        }
    }

    return ['status' => $status, 'body' => $result, 'headers' => $http_response_header ?? []];
}

$base = 'http://127.0.0.1:8000/api';
$tests = [];

$tests[] = ['GET', "$base/", []];
$tests[] = ['GET', "$base/villes", []];
$tests[] = ['GET', "$base/metiers", []];
$tests[] = ['GET', "$base/categories", []];
$tests[] = ['GET', "$base/compagnies", []];
$tests[] = ['GET', "$base/hotels?limit=1", []];
$tests[] = ['GET', "$base/artisans?limit=1", []];
$tests[] = ['GET', "$base/transports?limit=1", []];

function printResult($name, $resp) {
    echo "=== $name ===\n";
    echo "Status: " . $resp['status'] . "\n";
    echo "Body: ";
    if ($resp['body'] === false) {
        echo "<error>\n";
    } else {
        echo substr($resp['body'], 0, 800) . (strlen($resp['body']) > 800 ? "...\n" : "\n");
    }
    echo "\n";
}

$results = [];

foreach ($tests as $index => [$method, $url, $data]) {
    $results[] = ['name' => "$method $url", 'resp' => request($method, $url, $data)];
}

$hotelId = null;
$artisanId = null;
$ligneId = null;
$departId = null;
$compagnieId = null;

if ($results[5]['resp']['status'] === 200) {
    $json = json_decode($results[5]['resp']['body'], true);
    $hotelId = $json['data'][0]['IDHOTEL'] ?? null;
}
if ($results[6]['resp']['status'] === 200) {
    $json = json_decode($results[6]['resp']['body'], true);
    $artisanId = $json['data'][0]['IDARTISANT'] ?? null;
}
if ($results[7]['resp']['status'] === 200) {
    $json = json_decode($results[7]['resp']['body'], true);
    $ligneId = $json['data'][0]['IDLIGNETRANSPORT'] ?? null;
}

if (isset($results[4]['resp']['status']) && $results[4]['resp']['status'] === 200) {
    $json = json_decode($results[4]['resp']['body'], true);
    $compagnieId = $json['data'][0]['IDCOMPAGNIE'] ?? null;
}

foreach ($results as $entry) {
    printResult($entry['name'], $entry['resp']);
}

if ($hotelId !== null) {
    $resp = request('GET', "$base/hotels/$hotelId", []);
    printResult("GET $base/hotels/$hotelId", $resp);
    $resp = request('GET', "$base/hotels/$hotelId/images", []);
    printResult("GET $base/hotels/$hotelId/images", $resp);
} else {
    echo "Skipping hotel detail/images because no hotel ID was found.\n\n";
}

if ($artisanId !== null) {
    $resp = request('GET', "$base/artisans/$artisanId", []);
    printResult("GET $base/artisans/$artisanId", $resp);
    $resp = request('GET', "$base/artisans/$artisanId/notations", []);
    printResult("GET $base/artisans/$artisanId/notations", $resp);
    $resp = request('POST', "$base/artisans/$artisanId/notations", [
        'nom_utilisateur' => 'TestUser',
        'titre' => 'Test notation',
        'note' => 4,
        'commentaire' => 'Route test',
    ]);
    printResult("POST $base/artisans/$artisanId/notations", $resp);
} else {
    echo "Skipping artisan detail/notations because no artisan ID was found.\n\n";
}

if ($ligneId !== null) {
    $resp = request('GET', "$base/transports/$ligneId/horaires", []);
    printResult("GET $base/transports/$ligneId/horaires", $resp);
    $json = json_decode($resp['body'], true);
    $departId = $json['data'][0]['IDDEPART'] ?? null;
    if ($departId !== null) {
        $resp = request('GET', "$base/transports/departs/$departId/escales", []);
        printResult("GET $base/transports/departs/$departId/escales", $resp);
    } else {
        echo "Skipping transport depart escales because no depart ID was found.\n\n";
    }
} else {
    echo "Skipping transport horaires/escales because no ligne transport ID was found.\n\n";
}

if ($compagnieId !== null) {
    $resp = request('GET', "$base/compagnies/$compagnieId/notations", []);
    printResult("GET $base/compagnies/$compagnieId/notations", $resp);
    $resp = request('POST', "$base/compagnies/$compagnieId/notations", [
        'nom_utilisateur' => 'TestUser',
        'titre' => 'Test notation compagnie',
        'note' => 5,
        'commentaire' => 'Route test compagnie',
    ]);
    printResult("POST $base/compagnies/$compagnieId/notations", $resp);
} else {
    echo "Skipping compagnie notations because no compagnie ID was found.\n\n";
}
