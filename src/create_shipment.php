<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

// === KONFIGURACJA ===
$apiToken = 'TWOJ_API_TOKEN_TUTAJ'; // Podaj swój Bearer token
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
