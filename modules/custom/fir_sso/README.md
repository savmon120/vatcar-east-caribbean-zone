# FIR SSO — VATSIM Single Sign‑On Integration

The **FIR SSO** module provides a stable, service‑driven integration between  
**VATSIM OAuth2** and the Curaçao FIR Drupal CMS platform. It handles the  
complete profile‑mapping workflow after a user authenticates with VATSIM.

This module is intentionally minimal: it exposes no UI, no settings pages,  
and no permissions. All configuration is inherited from the  
**OAuth2 Client** contrib module.

---

## What this module does

- Receives the OAuth2 callback from VATSIM after login.
- Extracts the VATSIM profile payload (CID, name, rating, region/division/subdivision).
- Ensures **CID uniqueness** across all Drupal user accounts.
- Maps all VATSIM fields to Drupal user fields:
  - `field_vatsim_cid`
  - `field_vatsim_first_name`
  - `field_vatsim_last_name`
  - `field_vatsim_rating`
  - `field_vatsim_region`
  - `field_vatsim_division`
  - `field_vatsim_subdivision`
- Saves the updated user account.
- Exposes a stable service (`VatsimProfileSyncInterface`) for other FIR modules.

This module is the foundation for:

- FIR Bookings  
- FIR Training  
- FIR Events  
- FIR Rostering  
- FIR Dashboard  

Any module that needs VATSIM identity data depends on this one.

---

## How the OAuth2 callback works

1. The user clicks “Log in with VATSIM”.
2. The OAuth2 Client module redirects them to VATSIM.
3. VATSIM returns the user to the callback route:
/fir-sso/callback

Code
4. The callback controller:
- validates the OAuth2 response  
- loads the authenticated Drupal user  
- passes the VATSIM payload to the sync service  
5. The sync service updates the user account and returns control to the OAuth2 module.

This module **does not** modify the OAuth2 flow — it only handles the  
post‑authentication mapping.

---

## Field mapping

The following fields must exist on the user entity:

| VATSIM Payload Key | Drupal Field |
|--------------------|--------------|
| `cid` | `field_vatsim_cid` |
| `name_first` | `field_vatsim_first_name` |
| `name_last` | `field_vatsim_last_name` |
| `rating.id` | `field_vatsim_rating` |
| `region.id` | `field_vatsim_region` |
| `division.id` | `field_vatsim_division` |
| `subdivision.id` | `field_vatsim_subdivision` |

If any field is missing, the sync service will throw an exception.

---

## Service architecture

The core of this module is the service:

Drupal\fir_sso\Service\VatsimProfileSyncInterface

Code

Implemented by:

Drupal\fir_sso\Service\VatsimProfileSync

Code

This service:

- is dependency‑injected  
- is stateless  
- is safe to call from any module  
- guarantees consistent mapping logic across the platform  

Other modules **must not** implement their own VATSIM mapping logic.

---

## Overriding the sync logic

If a downstream FIR module needs to extend or modify the mapping logic:

1. Create a new class implementing `VatsimProfileSyncInterface`.
2. Add a service override in your module’s `services.yml`:
   ```yaml
   services:
     Drupal\fir_sso\Service\VatsimProfileSyncInterface:
       alias: my_module.custom_vatsim_sync
Clear caches.

This allows full replacement of the sync behavior without modifying this module.

Permissions
This module defines no permissions.

All configuration is inherited from the OAuth2 Client module, which already
restricts its admin UI to users with:

Code
administer site configuration
The OAuth callback route must remain publicly accessible for VATSIM to
redirect users back to the site.

Testing
Drupal CMS does not ship the Drupal testing framework.
Only pure PHP unit tests are supported.

This module may include placeholder test files for future expansion, but
functional and kernel tests are intentionally omitted.

Dependencies
drupal/oauth2_client

Drupal user entity fields listed above

Roadmap
Optional Drush command for manual sync

Optional status report entry

Optional logging improvements

Maintainers
https://www.drupal.org/u/savmonzac
https://github.com/savmon120