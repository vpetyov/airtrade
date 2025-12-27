## Docker (Bedrock)

### Start

```sh
docker compose up --build
```

Then open:
- http://localhost:8080

### Configure salts (required)

Replace the `change-me` values in `.env` with real salts:
- https://roots.io/salts.html

### WP-CLI inside container (optional)

```sh
docker compose exec php php -v
# example
# docker compose exec php ./vendor/bin/wp core version
```
