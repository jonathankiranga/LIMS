# COA Email Delivery Setup Manual

This document explains how the Certificate of Analysis email delivery works after the live/queue fallback update.

## Overview

The COA email sender now supports 3 delivery modes:

- `live`
  Sends the email immediately during the user's request.

- `queue`
  Stores the request in a queue table and waits for a background worker to process it.

- `auto`
  Uses the queue when a healthy worker heartbeat is detected.
  Falls back to live sending when no worker is available.

The default mode is `auto`.

## Current Behavior

When a user sends a COA from:

- [CertificateOfAssesment.php](/e:/limsISO/blockchain/CertificateOfAssesment.php)

the request goes to:

- [email_certificateofanalysis.php](/e:/limsISO/blockchain/functions/email_certificateofanalysis.php)

That endpoint checks the delivery mode and either:

- sends the email immediately, or
- queues the job for background processing.

## Main Files

- [certificate_email_support.php](/e:/limsISO/blockchain/functions/certificate_email_support.php)
  Contains queue helpers, config helpers, heartbeat logic, and the shared delivery logic.

- [email_certificateofanalysis.php](/e:/limsISO/blockchain/functions/email_certificateofanalysis.php)
  Chooses between live send and queued send.

- [process_certificate_email_queue.php](/e:/limsISO/blockchain/functions/process_certificate_email_queue.php)
  Background worker script for queued jobs.

- [CertificateOfAssesment.php](/e:/limsISO/blockchain/CertificateOfAssesment.php)
  Shows the response in the UI and indicates queued/live status.

## Database Objects Used

### Config table entries

The system uses the `config` table and may create these keys automatically:

- `coa_email_delivery_mode`
- `coa_email_worker_last_seen_at`

### Queue table

The system also creates this table automatically when needed:

- `certificate_email_queue`

## Delivery Mode Setup

### Option 1: Automatic fallback mode

Recommended setting:

```sql
UPDATE config
SET confvalue = 'auto'
WHERE confname = 'coa_email_delivery_mode';
```

If the row does not exist, it will usually be created automatically the first time the feature runs.

Behavior:

- worker healthy -> queue send
- worker missing/stale -> live send

### Option 2: Always send live

```sql
UPDATE config
SET confvalue = 'live'
WHERE confname = 'coa_email_delivery_mode';
```

Behavior:

- every COA email sends immediately
- no queue dependency

### Option 3: Always use queue

```sql
UPDATE config
SET confvalue = 'queue'
WHERE confname = 'coa_email_delivery_mode';
```

Behavior:

- every COA email is queued
- requires the queue worker to run

## Queue Worker Setup

The worker script is:

- [process_certificate_email_queue.php](/e:/limsISO/blockchain/functions/process_certificate_email_queue.php)

### Preferred way: run from CLI

Example:

```powershell
php e:\limsISO\blockchain\functions\process_certificate_email_queue.php
```

To process more jobs in one run:

```powershell
php e:\limsISO\blockchain\functions\process_certificate_email_queue.php 10
```

The numeric argument is the maximum number of queued jobs to process in that run.

### Windows Task Scheduler

If you later have permission to use Task Scheduler:

1. Create a scheduled task.
2. Run `php.exe`.
3. Pass this script path as the argument:

```text
e:\limsISO\blockchain\functions\process_certificate_email_queue.php
```

4. Schedule it at the interval you want, for example every 1 minute.

## Worker Heartbeat

Every time the worker runs, it updates:

- `coa_email_worker_last_seen_at`

The application uses that heartbeat to decide whether the worker is healthy.

In `auto` mode:

- recent heartbeat -> queue
- old/missing heartbeat -> live

## No Admin Rights Scenario

If Task Scheduler is not available:

- keep `coa_email_delivery_mode = auto`
- users can continue sending normally
- the system will fall back to live sending

This means the feature remains usable even before background processing is officially set up.

## UI Behavior

In the COA screen:

- live send success shows normal success feedback
- queue send shows a `queued` status and an informational Toastr message
- mail log output shows the selected report only

## Recommended Rollout

### Phase 1

- Leave mode as `auto`
- Do not set up Task Scheduler yet
- Users continue working with live fallback

### Phase 2

- Test the worker manually from CLI
- Confirm jobs are processed correctly

### Phase 3

- Add Task Scheduler
- Keep mode as `auto` or switch to `queue`

## Notes About Session Dependency

The queue foundation is implemented, but some report generation code still references `$_SESSION` for assets like logos and lab code image paths.

That means:

- live mode works as expected now
- queue mode is in place
- for a fully detached background worker, the remaining report/session dependencies should be refactored into request-local variables instead of session values

This is the next recommended technical cleanup before relying heavily on queued mode.

## Security Note

The worker script can currently be called from web as well as CLI.

Recommended next step:

- restrict [process_certificate_email_queue.php](/e:/limsISO/blockchain/functions/process_certificate_email_queue.php) to CLI only, or
- protect it behind authentication/authorization if it must remain web-accessible

## Quick Test Checklist

1. Send a COA with mode `live`.
2. Confirm the email sends immediately.
3. Set mode to `auto`.
4. Run the worker manually once from CLI.
5. Send another COA.
6. Confirm the UI shows `queued`.
7. Run the worker again.
8. Confirm the queued job is processed successfully.

