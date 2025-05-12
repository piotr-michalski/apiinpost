# InPost Test - Tworzenie Przesyłki i Zamówienie Kuriera

Ten prosty skrypt PHP tworzy przesyłkę kurierską typu `inpost_courier_c2c` przy użyciu API InPost, a następnie zamawia kuriera.

## Wymagania

- PHP 8.4+
- Composer (do zainstalowania Guzzle)

## Instalacja

1. Sklonuj repozytorium.
2. Przejdź do katalogu:

## Uruchomienie
### Docker
```bash
docker run -e API_KEY="xxx" -it $(docker build -q -f php/Dockerfile -t poc-inpost-php .)
```

