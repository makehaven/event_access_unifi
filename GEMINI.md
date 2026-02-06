# GEMINI Context: Event Access (UniFi Visitors)

This Drupal module integrates **CiviCRM Event Participants** with **UniFi Visitor Access**, automatically issuing electronic visitor passes (QR/PIN) when someone registers for an event.

## Project Overview

*   **Purpose:** Automate the creation of UniFi visitor passes based on CiviCRM event registrations and provide a way for users to view their passes.
*   **Key Technologies:**
    *   **Drupal 10/11:** Core framework.
    *   **CiviCRM:** Manages event participants.
    *   **UniFi API:** Issues the actual visitor credentials via the UniFi Console.
    *   **Guzzle:** Used for API requests to UniFi.
*   **Architecture:**
    *   **`UnifiApiService`:** Low-level service for interacting with the UniFi Developer API (`/api/v1/developer/visitors`).
    *   **`EventAccessManager`:** High-level service managing local pass records and business logic.
    *   **`hook_civicrm_post`:** (in `.module` file) Intercepts participant creation/editing to trigger pass generation.
    *   **Controllers:** `MyPassesController` (for logged-in users) and `TokenController` (for anonymous access via tokenized links).

## Key Files & Structure

*   `event_access_unifi.info.yml`: Module metadata and dependencies.
*   `event_access_unifi.module`: Contains `hook_civicrm_post` and mail logic.
*   `src/Service/UnifiApiService.php`: Handles UniFi API authentication and requests.
*   `src/Service/EventAccessManager.php`: Handles CRUD operations on the `event_access_unifi_pass` table.
*   `src/Controller/`: Contains page controllers for viewing passes.
*   `config/install/event_access_unifi.settings.yml`: Default configuration for API host, tokens, and email templates.

## Configuration

Settings are managed at: `/admin/config/system/event-access-unifi`
Key settings include:
*   `api_host`: URL of the UniFi console.
*   `api_token`: Developer token with visitor create scope.
*   `offset_before_start_minutes`: How many minutes before an event the pass becomes valid.
*   `window_from_start_minutes`: Duration from event start time that the pass remains valid.
*   `email_subject` / `email_body`: Templates for the automated notification email.

## Building and Running

*   **Enable Module:** `drush en event_access_unifi -y`
*   **Clear Cache:** `drush cr`
*   **Database Updates:** `drush updb` (if the `.install` schema changes).
*   **Run Tests:** `lando php vendor/bin/phpunit web/modules/custom/event_access_unifi`

## Development Conventions

*   **Service Injection:** Use Drupal's service container. Services are defined in `event_access_unifi.services.yml`.
*   **API Interactions:** All UniFi API calls should go through `UnifiApiService`.
*   **CiviCRM Integration:** Logic triggered by CiviCRM events should reside in `hook_civicrm_post` in the `.module` file.
*   **Error Logging:** Use the `event_access_unifi` logger channel.
