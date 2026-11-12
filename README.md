<<<<<<< HEAD
# Bantay-Dagat Marinduque

A community coastal monitoring application for XAMPP, built with PHP, MySQL/PDO, Bootstrap, Leaflet, and OpenStreetMap.

## Install with XAMPP

1. Install and start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Copy this project folder into `C:\xampp\htdocs\bantay_dagat`. The project root should contain `index.php`, `database`, `admin`, and `assets`.
3. Open `http://localhost/phpmyadmin`, create/import the database by choosing **Import** and selecting `database\database.sql`. The SQL file creates the `bantay_dagat_marinduque` database and its tables.
4. Back up the database, validate the curated records with `C:\xampp\php\php.exe database\seed_marine_species.php --validate-only`, then run `C:\xampp\php\php.exe database\seed_marine_species.php` from the project root. The repeatable script adds any missing species metadata columns and inserts the curated marine-species records without deleting existing data. Use `--skip-images` to rerun the safe schema/data migration without contacting Wikimedia Commons.
5. Run `C:\xampp\php\php.exe database\migrate_user_auth.php` from the project root. This safely adds the username column and unique index to an existing `users` table, assigning unique usernames to existing accounts without deleting records. It is safe to rerun.
6. Run `C:\xampp\php\php.exe database\migrate_report_inbox.php` from the project root. It makes report references nullable for new submissions, adds send tracking, and creates the private user Inbox table without deleting existing reports or contact messages. It is safe to rerun.
7. The default connection in `config\database.php` expects MySQL at `127.0.0.1`, database `bantay_dagat_marinduque`, user `root`, and an empty password (the standard local XAMPP setup). If your MySQL credentials differ, set `BDM_DB_HOST`, `BDM_DB_NAME`, `BDM_DB_USER`, and `BDM_DB_PASSWORD` in the PHP/Apache environment.
8. Ensure the `uploads` directory and its subdirectories are writable by Apache. They are created as needed when an administrator or reporter uploads an image.
9. Visit `http://localhost/bantay_dagat/`.

The application requires PHP 8.1+ with PDO MySQL, Fileinfo, `mbstring`, and cURL enabled. Leaflet tiles, automatic marine-species image lookup, and the CDN-hosted Bootstrap, Font Awesome, Chart.js, and fonts require an internet connection.

The application uses the existing table-specific primary keys (`report_id`, `admin_id`, `species_id`, and others). Importing `database\database.sql` creates missing tables for a new installation; it does not alter existing tables or delete data.

## Administrator

- Login: `http://localhost/bantay_dagat/admin/login.php`
- Initial local credentials: `admin@bantaydagat.local` / `BantayDagat!2026`
- Change the starter password immediately under **Admin profile**. Choose at least 12 characters.

The seeded account is for local development only. Replace it before exposing an installation to a network.

## What is connected

- Public incident submission stores validated reports, optional image evidence, and an initial status-history entry. Signed-in reporters are linked to their existing account; guest reports remain unlinked.
- Public tracking uses the reference number and excludes reporter contact information.
- The public map fetches current geocoded reports from the database and excludes rejected incidents and private reporter data.
- Admin report status/remarks changes create status history and update public tracking.
- Admin pages manage announcements, marine species, clean-up activities, user account statuses, messages, report coordinates, and the admin profile.
- Public users can register and sign in at `register.php` and `login.php`, update their own name, email, and username at `profile.php`, and sign out at `logout.php`. New users use the existing `Active` default; inactive or suspended accounts cannot sign in. User and administrator session keys are separate, and each logout preserves the other account type's session.
- Admins review new reports in the Report Inbox and explicitly generate/send a unique Reference ID with a message. The recipient sees the message and unread count in their private Inbox. Guest reports are never linked to invented accounts; admins must follow up using the guest's submitted contact details.
- New marine species automatically search Wikimedia Commons for a licensed matching image when no image URL is supplied. Admins can enter a direct image URL; public species pages link to the image source and license. Existing installations should run the repeatable marine-species seed script before using the category filters.
- The marine-species seed adds 50 entries (48 fish and 2 giant clams) with OBIS occurrence notes and conservation references. Notes distinguish Marinduque records from regional-only records and identify historical or imprecise observations; a record is not evidence of abundance or present-day residency.
- Homepage statistics, charts, announcements, species cards, map pins, and activity listings query MySQL.

## Notes

- The site intentionally does not display reporter names, phone numbers, email addresses, or evidence on public report tracking/map pages. Report evidence is served only through the authenticated admin evidence endpoint.
- Public report submissions do not require an account; name and contact details are collected for follow-up and remain admin-only.
- To test the Inbox workflow, submit a report while signed in, review it in Admin > Report Inbox, generate a Reference ID, and send a message. The user can open it in Inbox, copy the ID, and track the report. Guest report submission remains available, but a guest has no private Inbox.
- To test user authentication, register with a new email and username, sign in with either identifier, update the profile, then sign out. In Admin, set a test user's status to Inactive or Suspended and verify that sign-in is denied; restore Active afterward. Admin login remains at `admin/login.php`.
- This project is a local starter and should receive deployment-specific review before public hosting (HTTPS, strong unique credentials, backups, SMTP/contact workflow, and server upload limits).
=======
# Bantay_dagatweb
>>>>>>> cad38fd1c603f4c9b1bf3aa58adf88bf0e6aa2ae
