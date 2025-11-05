# Event Access (UniFi Visitors)

Creates **UniFi visitor passes** (QR/PIN) automatically when someone registers for a **CiviCRM event**, and lets users view their passes in Drupal.

## Features
- On CiviCRM Participant create/edit (Registered), creates a **visitor** in UniFi:
  - Valid window defaults: **start - 60 min** to **start + 180 min**
  - Configurable offsets
  - Optional door scoping (array of UniFi door IDs)
- Stores visitor details in a local table keyed by **Civi Participant ID**
- Emails the registrant a link to a tokenized page and the QR/PIN
- Logged-in users can visit **/event-access/my-passes** to see upcoming passes

## Install
1. Copy to `/web/modules/custom/event_access_unifi/` (or unzip there)
2. `drush en event_access_unifi -y && drush cr`

## Configure
- **Config → System → Event Access (UniFi)**
  - API host: `https://<console-ip>:12445`
  - API token: developer token with *visitor create* scope
  - Verify SSL: uncheck if self-signed
  - Offsets: minutes before start / window length from start
  - Default door IDs: JSON array (optional)
  - Email sender (optional) / subject / body

## Pages
- **/event-access/my-passes** — for logged-in users (permission required)
- **/event-access/pass/{participant_id}/{hash}** — tokenized deep link sent by email

## Schema
A simple table `event_access_unifi_pass` stores:
- participant_id (int, PK)
- contact_id (int)
- email (varchar)
- event_id (int)
- visitor_id (varchar)
- qr_url (varchar)
- pin (varchar)
- valid_from (int, epoch seconds)
- valid_to (int, epoch seconds)
- token_hash (varchar) — opaque token for deeplink
- created (int), changed (int)

---

If your field names or Civi statuses differ, tweak `hook_civicrm_post()` logic in `event_access_unifi.module`.
