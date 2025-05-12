# InPost Test - Tworzenie Przesyłki i Zamówienie Kuriera

Ten prosty skrypt PHP tworzy przesyłkę kurierską typu `inpost_courier_c2c` przy użyciu API InPost, a następnie zamawia kuriera.

## Wymagania

- PHP 8.4+
- Composer (do zainstalowania Guzzle)

## Instalacja

1. Sklonuj repozytorium lub pobierz folder `apiinpost`.
2. Przejdź do katalogu:

```bash
cd apiinpost
composer require guzzlehttp/guzzle
```

## W pliku create_shipment.php, uzupełnij token API:
```php
$apiToken = 'TWOJ_API_TOKEN_TUTAJ';
```

## Uruchomienie
### Docker
```bash
docker compose up --build, 
docker compose exec -it php bash
composer require guzzlehttp/guzzle
```
```bash
php create_shipment.php 
```
Po uruchomieniu docker'a appk'a wystawiona będzie pod: http://localhost:8080/
Skrypt można uruchomić w kontenerze lub bezpośrednio z linii poleceń `php create_shipment.php`.

