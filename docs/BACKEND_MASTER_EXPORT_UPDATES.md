# Backend Master Export Updates

## What Was Audited

The app master export buttons were checked against the existing backend API routes:

- `GET /api/v1/export/masters/accounts/excel`
- `GET /api/v1/export/masters/accounts/pdf`
- `GET /api/v1/export/masters/parties/excel`
- `GET /api/v1/export/masters/parties/pdf`
- `GET /api/v1/export/masters/items/excel`
- `GET /api/v1/export/masters/items/pdf`
- `GET /api/v1/export/masters/item-categories/excel`
- `GET /api/v1/export/masters/item-categories/pdf`
- `GET /api/v1/export/masters/tax-rates/excel`
- `GET /api/v1/export/masters/tax-rates/pdf`

## Backend Runtime Changes

No runtime backend controller, service, route, or admin UI behavior was changed.

The existing backend already supports master Excel/PDF exports through:

- `routes/api.php`
- `app/Http/Controllers/Api/ExportApiController.php`
- `app/Services/ExportService.php`
- `resources/views/exports/master-list.blade.php`

The API returns export files as JSON with:

- `filename`
- `content_type`
- `content_base64`
- `path`

## Backend Documentation Change

Swagger/OpenAPI documentation was updated in:

- `app/Docs/ExportDocs.php`

Added documentation for:

- `GET /export/masters/{type}/excel`
- `GET /export/masters/{type}/pdf`

Supported `{type}` values:

- `accounts`
- `parties`
- `items`
- `item-categories`
- `tax-rates`

## App-Side Fix

The Flutter app already used the correct backend endpoints. The app was improved so that all master PDF buttons have local fallback data if the server export fails or is unavailable:

- Ledgers / Accounts
- AR / AP / Parties
- Items
- Item Categories
- Tax Rates

Excel already had fallback CSV support; PDF fallback is now aligned across all master tabs.

## Deploy Notes

After deploying backend changes, regenerate Swagger docs if your deployment process does not do it automatically.

Suggested command:

```bash
php artisan l5-swagger:generate
```

No database migration is required for this update.
