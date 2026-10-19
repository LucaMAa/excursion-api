# excursion-api

## Excursions

`GET /api/excursions` accepts optional `limit` and `offset` query parameters
(defaults: `10` and `0`) and returns a page of excursion response DTOs. The
limit must be positive and the offset cannot be negative. `POST /api/excursions`
accepts a JSON object with `title`, `description`, `city`, `country`, `date`
(`Y-m-d`), and `capacity`, then returns the created excursion response DTO
with HTTP `201`.

Controller actions can use the shared controller helpers to map query
parameters with `queryDto()`, JSON request bodies with `bodyDto()`, and DTOs to
JSON responses with `dtoResponse()`.
