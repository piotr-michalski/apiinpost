<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

// === KONFIGURACJA ===
$apiToken = 'eyJhbGciOiJSUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICJkVzROZW9TeXk0OHpCOHg4emdZX2t5dFNiWHY3blZ0eFVGVFpzWV9TUFA4In0.eyJleHAiOjIwNjI0MDU3ODYsImlhdCI6MTc0NzA0NTc4NiwianRpIjoiNjQ5YWZlNmEtMmZlOC00ZDc1LTgxMzEtY2VlOGI2M2Q5YmEyIiwiaXNzIjoiaHR0cHM6Ly9zYW5kYm94LWxvZ2luLmlucG9zdC5wbC9hdXRoL3JlYWxtcy9leHRlcm5hbCIsInN1YiI6ImY6N2ZiZjQxYmEtYTEzZC00MGQzLTk1ZjYtOThhMmIxYmFlNjdiOnJIV3NYM2Y5RUVRaGFZZ0RETzJrUXROQVpWTFV4VUJ0SFlHMk5nUHNWQnciLCJ0eXAiOiJCZWFyZXIiLCJhenAiOiJzaGlweCIsInNlc3Npb25fc3RhdGUiOiJjM2FiMTY1Yy1lNmNhLTQyMDQtOGM5OS1hOWYxYTYyN2ZkZGQiLCJzY29wZSI6Im9wZW5pZCBhcGk6YXBpcG9pbnRzIGFwaTpzaGlweCIsInNpZCI6ImMzYWIxNjVjLWU2Y2EtNDIwNC04Yzk5LWE5ZjFhNjI3ZmRkZCIsImFsbG93ZWRfcmVmZXJyZXJzIjoiIiwidXVpZCI6ImFmMjM0YzFkLTgzYmUtNDI0OS1hZTRjLTJjNGJiMDZkZDZmYiIsImVtYWlsIjoicGlvdHIubWljaGFsc2tpOUBnbWFpbC5jb20ifQ.cY_CVB-Kf_ICeGOJcgFi-R-vhJXnC_bH52eZUyHsFiv4I1KLtg0J45KQbUuAJuwnsbqA_HGl_UxKLwTB_O5rbuXumKlqbltPn1VZLzUVXmqD35XHeCBY6UBq6D1dxCUZl2CQeti0Xvv7nKpjeaGw8irz5MpwT1Rwa4vjLEpqu5z7WJrXwYs0ajQy8bb0EhM2QnF3Ny6aJOsVp8WqdI0Jinf5v5ry0EP1IlbYySYB9wXIBU2akwYYqGFRqV6sgU284duIBYGxrnYhRQiB0ijdg8o-vwjbi2H92bP9PSfaW-mR2zDk1NqVNu9aOZa3o77HD3t_4drDn5hKJ_FtTSF_Hg'; // Podaj swój Bearer token
$baseUrl = 'https://sandbox-api-shipx-pl.easypack24.net';

// === UTWÓRZ KLIENTA GUZZLE ===
$client = new Client([
    'base_uri' => $baseUrl,
    'headers' => [
        'Authorization' => 'Bearer '.$apiToken,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ],
]);

// === DANE PRZESYŁKI ===
$shipmentData = [
    'sender' => [
        'company_name' => 'Company_name',
        'first_name' => 'first_name',
        'last_name' => 'last_name',
        'email' => 'test@grupainteger.pl',
        'phone' => '321321321',
        'address' => [
            'street' => 'Czerniakowska',
            'building_number' => '87A',
            'city' => 'Warszawa',
            'post_code' => '00-718',
            'country_code' => 'PL',
        ],
    ],
    'receiver' => [
        'company_name' => 'Company name',
        'first_name' => 'Jan',
        'last_name' => 'Kowalski',
        'email' => 'receiver@example.com',
        'phone' => '888000000',
        'address' => [
            'street' => 'Cybernetyki',
            'building_number' => '10',
            'city' => 'Warszawa',
            'post_code' => '02-677',
            'country_code' => 'PL',
        ],
    ],
    'parcels' => [
        [
            'id' => 'small package',
            'dimensions' => [
                'length' => '80',
                'width' => '360',
                'height' => '640',
                'unit' => 'mm',
            ],
            'weight' => [
                'amount' => '25',
                'unit' => 'kg',
            ],
            'is_non_standard' => false,
        ],
    ],
    'insurance' => [
        'amount' => 25,
        'currency' => 'PLN',
    ],
    'cod' => [
        'amount' => 12.50,
        'currency' => 'PLN',
    ],
    'service' => 'inpost_courier_c2c',
    'additional_services' => [],
    'custom_attributes' => [
        'sending_method' => 'dispatch_order',
    ],
    'reference' => 'Test',
    'comments' => 'dowolny komentarz',
];


// === FUNKCJA DO LOGOWANIA ===
/**
 * @param string $data
 * @return void
 */
function logToFile(string $data): void
{
    file_put_contents('log.txt', date('[Y-m-d H:i:s] ').$data.PHP_EOL, FILE_APPEND);
}

/**
 * Czeka maksymalnie $timeout sekund na zmianę statusu przesyłki.
 *
 * @param Client $client
 * @param int $shipmentId
 * @param string $finalStatus
 * @param int $timeout
 * @return string|null
 */
function waitForShipmentStatusChange(Client $client, int $shipmentId, string $finalStatus, int $timeout = 10): ?string
{
    $start = time();
    do {
        try {
            $response = $client->get('/v1/shipments/'.$shipmentId);
            $data = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $currentStatus = $data['status'] ?? null;
            logToFile('🔄 Status przesyłki: '.$currentStatus);

            if ($currentStatus && $currentStatus === $finalStatus) {
                return $currentStatus;
            }
        } catch (Throwable $exception) {
            logToFile('❌ Błąd podczas sprawdzania statusu: '.$exception->getMessage());

            return null;
        }

        sleep(1);
    } while (time() - $start < $timeout);

    return $currentStatus ?? null;
}

try {
    // === KROK 1: POBIERZ ID ORGANIZACJI ===
    $response = $client->get('/v1/organizations/');
    $organizations = json_decode(
        $response->getBody()->getContents(),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
    $organizationId = $organizations['items'][0]['id'] ?? null;
    if (!$organizationId) {
        throw new RuntimeException('Nie znaleziono organizacji');
    }

    // === KROK 2: UTWÓRZ PRZESYŁKĘ ===
    $response = $client->post('/v1/organizations/'.$organizationId.'/shipments', [
        'json' => $shipmentData,
    ]);

    $body = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    logToFile('Utworzono przesyłkę: '.json_encode($body, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
    echo '✅ Przesyłka utworzona. ID: '.$body['id'].PHP_EOL;

    // === KROK 3: ZACZEKAJ NA POTWIERDZENIE ===
    $shipmentId = $body['id'];

    $currentStatus = waitForShipmentStatusChange($client, $shipmentId, 'confirmed');
    if ($currentStatus !== 'confirmed') {
        throw new RuntimeException('Błąd zmiany statusu');
    }

    // === KROK 4: ZAMÓW KURIERA DLA PRZESYŁKI ===
    $dispatchOrderData = [
        'shipments' => [$shipmentId],
        'comment' => 'Dowolny komentarz do zlecenia odbioru',
        'name' => 'Przykładowa nazwa DispatchPoint',
        'phone' => '505404202',
        'email' => 'sample@email.com',
        'address' => [
            'street' => 'Malborska',
            'building_number' => '130',
            'city' => 'Krakow',
            'post_code' => '31-209',
            'country_code' => 'PL',
        ],
    ];
    $orderResponse = $client->post('/v1/organizations/'.$organizationId.'/dispatch_orders', [
        'json' => $dispatchOrderData,
    ]);

    $orderBody = json_decode($orderResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
    logToFile('Zamówiono kuriera: '.json_encode($orderBody, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
    echo '🚚 Kurier zamówiony'.PHP_EOL;
} catch (RequestException $exception) {
    $errorMsg = $exception->hasResponse()
        ? $exception->getResponse()->getBody()->getContents()
        : $exception->getMessage();

    logToFile('❌ RequestException, błąd: '.$errorMsg);
} catch (GuzzleException $exception) {
    logToFile('❌ GuzzleException, błąd: '.$exception->getMessage());
} catch (JsonException $exception) {
    logToFile('❌ JsonException, błąd: '.$exception->getMessage());
} catch (RuntimeException $exception) {
    logToFile('❌ RuntimeException, błąd: '.$exception->getMessage());
}
if (isset($exception)) {
    echo '❌ Wystąpił błąd. Szczegóły zapisano w log.txt'.PHP_EOL;
}
