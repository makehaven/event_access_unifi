## Purpose
`event_access_unifi` provisions UniFi visitor credentials when people register for CiviCRM events and lets them retrieve QR/PIN details later.

## Key Services & Tables
- Service `event_access_unifi.manager` handles API calls and stores visitor data in `event_access_unifi_pass`.
- Service `event_access_unifi.api` wraps UniFi visitor endpoints.
- Routes:
  - `/admin/config/system/event-access-unifi` – settings form.
  - `/event-access/my-passes` – user list page (permission: `view own event access passes`).
  - `/event-access/pass/{participant_id}/{hash}` – token-protected pass view.

## Hooks & Triggers
- `hook_civicrm_post()` listens to Participant create/edit, filters out cancelled statuses, and creates or reuses visitors.
- Emails are only sent on first creation or when window/email changes.

## Configuration Highlights
- `api_host`, `api_token`, `verify_ssl`: UniFi visitor API credentials.
- `visitor_default_door_ids`: JSON array of door IDs applied to visitors.
- Offsets and window durations determine valid_from/valid_to.
- Email sender/subject/body (simple token replacement, not Twig evaluation).

## Testing Notes
- In staging, register a contact for an event and confirm a row appears in `event_access_unifi_pass` and email is sent once.
- To avoid duplicates, verify edits do not trigger new visitor creation unless status becomes eligible again.
