# Flutter Offline-First Changes Required — 30 July 2026

This document is the implementation handoff for the Flutter developer. No Flutter source code is changed by this handoff.

## Objective

The app must allow users to read cached data and create or edit supported records without connectivity, then synchronize safely when connectivity returns.

Offline synchronization must guarantee:

- Tenant data never leaks between logins.
- A retry never creates the same record twice.
- Pending writes survive app restarts.
- Parent records synchronize before dependent transactions.
- Deleted records do not reappear.
- Conflicts are visible and require an explicit resolution.
- Failed mutations are never reported as successfully synchronized.

## Current Critical Gap

The Flutter app currently queues and replays normal REST requests:

```text
POST /accounts
PUT /parties/{id}
DELETE /items/{id}
```

The backend also exposes a separate batch synchronization API:

```text
POST /api/v1/sync/upload
POST /api/v1/sync/run
GET  /api/v1/sync/download
GET  /api/v1/sync/bootstrap
GET  /api/v1/sync/status
```

The Flutter app does not currently use these synchronization endpoints. Do not mix both mutation systems. The app must use one canonical synchronization pipeline.

## Required App Architecture

### 1. Local tenant isolation

Local cache, records, and mutation queues must be scoped by the authenticated company.

Required local fields:

```text
company_id
user_id
device_id
```

Apply the scope to:

- `api_cache`
- `offline_records`
- `sync_queue`
- Stored sync cursors

On login:

1. Read the company ID from the authenticated profile.
2. Open or select that company’s local data partition.
3. Never display records from another company partition.

On logout:

1. Attempt synchronization when online.
2. If unsynchronized writes remain, warn the user and require an explicit decision.
3. Never upload the previous company’s queue using a new company’s access token.

Affected Flutter files:

```text
lib/core/database/app_database_service.dart
lib/core/services/local_storage_service.dart
lib/presentation/controllers/auth/login_controller.dart
lib/presentation/controllers/settings/settings_controller.dart
lib/presentation/controllers/dashboard/dashboard_controller.dart
```

### 2. Stable local identity

Every locally created record must have a UUID before it enters the queue.

Store both identities:

```json
{
  "local_uuid": "0198...",
  "server_id": null,
  "server_uuid": null
}
```

Never use temporary negative numeric IDs. Numeric IDs are server-local database keys and cannot safely represent offline relationships.

### 3. Durable mutation identity

Every queued mutation must have a stable UUID generated once:

```json
{
  "mutation_uuid": "0198...",
  "record_uuid": "0198...",
  "entity": "parties",
  "operation": "create",
  "base_version": null,
  "payload": {}
}
```

The same `mutation_uuid` must be reused for every retry. Never create a new mutation UUID after a timeout.

### 4. Record versioning

Store these fields on every syncable local record:

```text
uuid
version
sync_status
is_dirty
last_synced_at
```

For updates and deletes, send the last server version as `base_version`.

Do not silently overwrite a newer server version. Mark the local mutation as `conflict` and display the authoritative server record.

## Supported Offline Modules

### Master data

These modules may be created and edited offline:

- Accounts
- Parties
- Items
- Item categories
- Tax rates

Deletion must still respect backend accounting rules:

- System accounts cannot be deleted.
- Accounts with real transactions cannot be deleted.
- Parties with real transactions cannot be deleted.
- Opening-balance-only accounts and parties may be deleted by the backend.

### Transactions

The app may create invoices and vouchers offline only when every referenced master already has a server ID.

Before allowing offline save, verify:

- Party has a server ID.
- Every item has a server ID.
- Every account has a server ID.
- Every tax rate has a server ID.
- Current financial year has a server ID.

If any dependency is still pending synchronization, block the transaction save and show:

```text
Sync the selected party/items/accounts before creating this transaction offline.
```

Affected Flutter file:

```text
lib/presentation/controllers/transactions/create/base_invoice_form_controller.dart
lib/presentation/controllers/transactions/create/base_voucher_form_controller.dart
```

## Synchronization Order

Upload creates and updates in this order:

1. Accounts
2. Item categories
3. Tax rates
4. Parties
5. Items
6. Sales invoices
7. Purchase invoices
8. Vouchers and settlements

Process deletes in reverse dependency order.

Do not process the queue only by creation timestamp.

## Queue State Machine

Use these states:

```text
pending
syncing
synced
failed
conflict
rejected
```

Required behavior:

- Reset stale `syncing` rows to `failed` when the app starts.
- Retry only transient network and server errors.
- Use exponential backoff.
- Stop automatic retry after five failures.
- Keep validation failures as `rejected`.
- Keep version conflicts as `conflict`.
- Delete a queue row only after confirmed server success.
- Treat repeated DELETE returning `404` as completed.

Manual synchronization must report real totals:

```json
{
  "synced": 8,
  "failed": 1,
  "conflicts": 1
}
```

Never show “sync successful” when any mutation failed.

Affected Flutter files:

```text
lib/core/services/sync_service.dart
lib/core/database/app_database_service.dart
lib/presentation/controllers/settings/settings_controller.dart
```

## Soft-Deleted Duplicate Decisions

### Accounts

`POST /api/v1/accounts` may return:

```json
{
  "success": false,
  "code": "SOFT_DELETED_ACCOUNT_EXISTS",
  "message": "A deleted account with this name and type already exists.",
  "data": {
    "account_code": "1001",
    "account_name": "Petty Cash",
    "account_type": "asset"
  }
}
```

### Parties

`POST /api/v1/parties` may return:

```json
{
  "success": false,
  "code": "SOFT_DELETED_PARTY_EXISTS",
  "message": "A deleted party with this name already exists.",
  "data": {
    "party_code": "AR001",
    "party_name": "ABC Customer"
  }
}
```

On either `409` response:

1. Pause the mutation.
2. Ask the user to choose Restore or Create New.
3. Retry the same mutation with the same mutation UUID.

Restore:

```json
{
  "duplicate_action": "restore"
}
```

Create new:

```json
{
  "duplicate_action": "new_entry"
}
```

Do not automatically choose either option.

## Sales Invoice Accounting Rules

The app does not select the final system posting ledger. The backend performs the accounting split.

System income ledgers:

```text
1501 Sales Revenue
1502 Service Revenue
```

Posting behavior:

- Goods taxable amount posts to Sales Revenue (`1501`).
- Service taxable amount posts to Service Revenue (`1502`).
- Tax posts separately to the configured sales tax ledger.
- Mixed goods/service invoices are supported.

The app must correctly identify service items using the item `type` returned by the API.

## Invoice Number Rules

Sales and purchase invoice numbers are server generated.

Do not send or reserve the displayed preview number during create.

The number shown on the form is a preview only:

```text
INV-202627/0004
PUR-202627/0002
```

After synchronization, replace the local temporary number with the number returned by the server.

Offline temporary display numbers must be clearly marked:

```text
LOCAL-SALE-{uuid}
LOCAL-PURCHASE-{uuid}
```

Never treat a temporary number as an accounting document number.

## Initial Bootstrap

After the first authenticated login on a device:

```http
GET /api/v1/sync/bootstrap
Authorization: Bearer {token}
```

Persist the returned records transactionally. Do not render a partially imported bootstrap.

Store the returned synchronization timestamp/cursor only after every table is committed locally.

## Incremental Download

Current endpoint:

```http
GET /api/v1/sync/download
  ?since={ISO-8601 timestamp}
  &page=1
  &per_page=100
```

Download and apply remote changes before uploading dependent transactions.

Do not advance the local cursor until the complete response page has been committed.

The current backend response does not include deletion tombstones. Until the backend provides tombstones, perform a full master refresh after synchronization and reconcile clean local rows against the server snapshot.

Never remove dirty local records during reconciliation.

## Device Registration

Generate and persist a unique installation ID. Do not use a hardcoded device ID.

Register it after login:

```http
POST /api/v1/devices/register
Authorization: Bearer {token}
Content-Type: application/json
```

Use the registered device ID on every synchronization request.

## Authentication While Offline

- Keep the last authenticated profile for offline startup.
- If connectivity is unavailable, allow entry only when a token and cached profile exist.
- A temporary `/me` timeout must not erase the local session.
- A confirmed `401` response invalidates the session.
- Background synchronization must not show repeated global authentication dialogs.

## Required UI

Add a synchronization status panel with:

- Online/offline status
- Last successful synchronization time
- Pending count
- Failed count
- Conflict count
- Retry action
- Conflict resolution action
- Last safe error message

Each locally modified row should display:

```text
Pending
Syncing
Failed
Conflict
Synced
```

## Required Tests

Add Flutter tests for:

- Offline create survives app restart.
- Interrupted `syncing` mutation is recovered.
- Retry uses the same mutation UUID.
- A timed-out create is not duplicated.
- Queue dependencies are processed in order.
- Unsynced party/item cannot be used in an offline invoice.
- Account and party `409` conflicts require user selection.
- DELETE `404` completes the local tombstone.
- Failed mutations are not reported as successful.
- Retry stops at the configured maximum.
- Login to another company cannot read or upload previous tenant data.
- Logout warns when unsynchronized writes exist.
- Offline startup works with a cached authenticated profile.
- Remote merge does not overwrite dirty local data.
- Server IDs are attached to the original local UUID record after create.

## Backend Limitations the App Developer Must Not Hide

The current backend sync implementation still requires hardening before production rollout:

- Sync writes currently bypass some domain services.
- Server versions are not consistently incremented.
- Download uses timestamp pagination instead of a monotonic cursor.
- Soft-deleted records are not returned as tombstones.
- Device ownership is not enforced by the sync endpoint.
- Queue upload does not currently provide permanent mutation idempotency.
- Only accounts, parties, items, item categories, and tax rates are batch-syncable.

Do not simulate successful synchronization around these limitations. Keep unsupported transaction mutations pending until their normal API request succeeds.

## Completion Checklist

- [ ] Company-scoped local database partitioning
- [ ] Real installation/device ID
- [ ] Stable record UUID and mutation UUID
- [ ] Version stored and sent on update/delete
- [ ] Queue crash recovery and retry cap
- [ ] Dependency-aware upload order
- [ ] Explicit `409` restore/new-entry flow
- [ ] Transaction dependency validation
- [ ] Server-generated invoice number replacement
- [ ] Accurate sync status UI
- [ ] Offline authenticated startup
- [ ] Tenant-safe logout/login handling
- [ ] Full Flutter sync test suite

