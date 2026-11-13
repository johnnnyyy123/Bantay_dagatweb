# Bantay-Dagat Marinduque

A static, responsive coastal-care website built with HTML, CSS, and vanilla JavaScript. No build step is required.

## Run locally

Open `index.html` in a current browser, or serve this folder with a static file server. For example, from the project directory:

```powershell
python -m http.server 8000
```

Then visit `http://localhost:8000`.

## Pages

- Public: `index.html`, `about.html`, `clean-our-seas.html`, `updates.html`, `announcement-details.html`, `marine-species.html`, `species-details.html`, `map.html`, `report.html`, `report-status.html`, `login.html`, `register.html`, `logout.html`, `profile.html`, `user-inbox.html`, and `user-inbox-message.html`.
- Administrator: `admin/login.html`, `admin/register.html`, `admin/dashboard.html`, `admin/manage-reports.html`, `admin/manage-map.html`, `admin/manage-announcements.html`, `admin/manage-species.html`, `admin/manage-users.html`, `admin/manage-activities.html`, `admin/messages.html`, `admin/profile.html`, `admin/evidence.html`, and `admin/logout.html`.

## Browser storage and services

Report entries, profile details, and administrator edits are stored in the browser's `localStorage`. A report reference can be used to review the entry in the same browser. Account pages support profile navigation and editing. Contact inquiries are prepared for sharing and are not submitted by the site.

The map uses Leaflet and OpenStreetMap tiles when an internet connection is available. Fonts, Bootstrap, and icons are loaded from their existing public CDNs.

## Deploy to static hosting

Publish the repository root as the site's document root; `index.html` is the entry point. On GitHub Pages, configure the repository's Pages source to the root of the publishing branch (or the root directory for a GitHub Actions static deployment). On Netlify or Cloudflare Pages, select the repository, set the publish directory to `.` and leave the build command empty.

For a custom domain, add the domain in the hosting provider's domain settings, configure the DNS records they provide (typically a CNAME for a subdomain or A/ALIAS records for an apex domain), and enable HTTPS. Keep the provider's domain-verification and DNS instructions as the source of truth.

## Notes

- The original ocean-coast and marine-species artwork and existing CSS branding are retained.
- User-submitted reports and administrator edits remain in the current browser and can be cleared by removing this site's local storage.
- For emergencies, contact local emergency services directly.
