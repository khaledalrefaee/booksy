# Platform config API

`GET /api/platform/config` — public, no token, throttled (60/min). Everything an app needs to know about the
platform, taken from **Owner → Settings** (same visibility rules as the website). Internal support details
(`support.internal_*`), notes and secrets are never included.

Language: `?lang=ar|en` or `Accept-Language`. Envelope: `{ status, message, data }` (see the Mobile API standard).

## Query

| param      | meaning                                                                         |
|------------|---------------------------------------------------------------------------------|
| `platform` | `android` \| `ios` \| `web` — optional, together with `version` adds `data.update` |
| `version`  | the caller's own version, e.g. `1.4.0`                                          |

## Response (`data`)

```jsonc
{
  "name": "GlowRez",
  "description": null,
  "website_url": null,
  "logo_url": null,
  "default_locale": null,
  "contact": {
    "email": null, "email_href": null, "phone": null, "phone_href": null,      // official, only if switched on
    "support_channels": [ { "key": "email|phone|whatsapp|help_center|faq|contact", "value": "…", "url": "…" } ],
    "support_hours": "09:00 – 17:00 · Sat · Sun",
    "support_message": null
  },
  "social_links": [ { "kind": "instagram", "label": "Instagram", "url": "https://…" } ],   // active + shown on site only
  "legal": { "privacy_url": "…", "terms_url": "…" },
  "status": { "mode": "normal|maintenance|paused", "message": null, "status_page_url": null, "changelog_url": null },
  "releases": {
    "web":     { "version": null, "min_version": null, "updated_on": null, "release_notes": null },
    "android": { "status": "in_development|available|unavailable", "version": null, "build": null, "min_version": null,
                 "updated_on": null, "release_notes": null, "google_play_url": null, "apk_url": null },
    "ios":     { "status": "…", "version": null, "build": null, "min_version": null, "updated_on": null,
                 "release_notes": null, "app_store_url": null }
  },
  "update": { "platform": "android", "update_required": false, "update_available": false }   // only with ?platform&version
}
```

* Store/APK links are returned **only while the app's status is `available`** — an in-development app never exposes a download.
* `update_required` = the caller's version is below the recorded minimum. `update_available` = a newer version is recorded **and**
  "New-update notice" is switched on. The app decides how to present either.
* These are the versions *recorded* in the dashboard. Recording a version does not publish or update anything.

## During maintenance

This endpoint stays reachable so apps can show why the service is closed (`status.mode`). Every other web/API route a
visitor reaches answers `503` with the unified envelope:

```json
{ "status": false, "message": "…", "data": { "maintenance": { "mode": "maintenance", "status_page_url": null } } }
```

with a `Retry-After` header. The owner console (`/owner/*`) is never blocked, and signed-in staff with dashboard access
can still browse the public site.

## Linking the recorded release to the real deployment (optional)

The Releases tab shows what the server **reports** next to what was **recorded**. To make the first one meaningful, have the
deploy pipeline set `APP_RELEASE` (for example the git commit SHA) in the environment. Nothing is auto-updated from it.
