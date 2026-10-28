# Allpro Services

Website and back office for Allpro Security Services Ireland Ltd, trading as Allpro Services (company no. 433727), 12A Westlink Business Park, Oranmore, Co. Galway. The company provides contract cleaning, security and facilities management from offices in Galway, Dublin, Limerick and Cork, and was established in 2002. Its current site, allpro.ie, is a 2016 WordPress build. It serves a `*.wpengine.com` certificate, so HTTPS fails and the site only works over plain HTTP. It also shows a broken Instagram widget ("Please enter an Access Token…"), and the contact form needs JavaScript. All of this was checked on 27 October 2026.

This is an unsolicited pitch build. Every fact comes from allpro.ie or the company record. **No clients are named anywhere, and the site does not describe any particular contract or sector programme beyond the sector list on allpro.ie.**

## What it does

### Public site

- **Pages:**
  - home
  - services, with the eight service lines and lists from allpro.ie
  - about, with the mission and vision in the company's own words
  - client services
  - careers
  - contact
  - privacy notice (Irish GDPR / Data Protection Act 2018, DPC)
  - photo credits and a site map
- **Design:** mobile-first, 17px body text, light and dark themes, and every form works without JavaScript.
- **Redirects:** old WordPress addresses (`/cleaning`, `/security-services` and the rest) redirect to the new pages.

### Intake flows

Each flow gives a reference number and an email confirmation.

- **Quote and site-survey request (`QR-26-00014`).**
  - Asks for services, sector, organisation and site.
  - The county routes the request to the sales owner for that region (`User.region`). With no owner it waits unassigned and is flagged.
  - Eircode is optional and normalised.
  - The survey date must be a weekday at least two working days out and within three months.
  - Tender reference and closing date are optional.
  - Up to 3 floor plans or specifications can be attached.
- **Client service desk (`SR-…`).**
  - Each client site gets a signed link: ULID `public_id` plus a 40-character `access_key`, valid for 30, 90 or 365 days.
  - Through it the client logs a request, complaint or security incident with a priority and photos.
  - Response and resolution clocks are set from the priority:

    | Priority | Response | Resolve |
    |---|---|---|
    | Urgent | 1 h | 4 h |
    | High | 4 h | 24 h |
    | Normal | 24 h | 72 h |
    | Low | 48 h | 120 h |

  - The page lists the site's tickets with the updates staff chose to share. Internal notes never appear.
  - Withdrawing links rotates the key. Pages are `noindex` and `no-referrer`.
- **Job application (`JA-…`).**
  - For a posted opening or a general application. The opening sets the role and region.
  - A phone number and an availability grid are required (early, late and night, for each day of the week). No CV is needed; an optional CV is stored privately.
  - Security officers must give a current PSA licence number and expiry; expired licences are refused. Supervisors may give one. Cleaning roles never store one.
  - **Garda vetting is never collected on the public form.** HR records the vetting stage in the back office. The same applies to the right-to-work check and the PSA register check.
- **Contact form (`EN-…`).**

### Private files

- Uploads go to the private `local` disk (`storage/app/private/{quotes,tickets,cvs}`).
- If a form comes back with an error, the files that passed are kept (`held/`) so nobody attaches them twice.

### Licence reminders

- `php artisan app:remind-expiring` runs daily at 07:00 Irish time.
- It emails HR one list of active staff whose PSA licence has expired or expires within 60 days.
- It sends once per licence per stage (expiring, then expired). Entering a new expiry date re-arms it.
- `--dry-run` only lists.

### Back office

The back office is at `/admin`, with four roles:

- **Admin**: everything, including staff accounts.
- **Sales**: quote requests and enquiries.
- **HR**: applications, job postings and the staff licence register.
- **Operations**: the service desk and client sites.

How it works:

- The overview leads with the one number for the signed-in role:
  - tickets breaching or about to breach the SLA (operations and admin)
  - surveys requested for the next 7 days that are not booked (sales)
  - PSA licences expired or due within 60 days (HR)
- Every register has:
  - status tabs and filters
  - search
  - CSV export (formula-safe)
  - a notes timeline
- An applicant cannot be marked hired while a check is outstanding: vetting, right to work, or for security roles the PSA register check and a current licence.
- Once hired, an applicant converts to a staff record.
- Every opening of a private file is logged.

## Requirements

- PHP 8.3 or 8.4
- Composer 2
- Node 20+ for building assets, and Chrome for the proposal
- SQLite locally, or MySQL in production

## Local setup

```sh
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seeder=DemoSeeder   # demo staff, 7 postings, 28 applications, 28 staff, 14 quote requests, 5 client sites, 12 tickets, 5 enquiries
npm install && npm run build
php artisan serve --port=8114
```

Demo logins all use the password `password`:

- `admin@allpro.test`
- `sales@allpro.test`, plus `sales.dublin`, `sales.limerick` and `sales.cork` (regional owners)
- `hr@allpro.test`
- `operations@allpro.test`, plus `operations.dublin`

Every demo person, organisation, ticket and applicant is fictional and marked "(sample)". `DatabaseSeeder` only creates the first admin from `ADMIN_*`.

## Checks

```sh
php artisan test                 # feature tests for every form, role boundary, policy, signed link and the reminder
vendor/bin/phpstan analyse       # Larastan, analysed as PHP 8.3
vendor/bin/pint --test
```

The suite also passes on MySQL 8 (`DB_CONNECTION=mysql … php artisan test`).

## Deploying

See [INSTALL-HOSTINGER.md](INSTALL-HOSTINGER.md).

- `scripts/package-hostinger.sh` builds `dist/allpro-hostinger.zip`. It extracts straight into `public_html/`, with the app in `allpro-app/`.
- One cron job (`schedule:run` every minute) is needed for the licence reminder.
- Mail for allpro.ie is on Microsoft 365. Send through an M365 mailbox, and leave the MX, SPF and `audit.allpro.ie` records alone.

## Proposal

With the dev server on 8114 and demo data loaded, `npm run proposal` captures every screen and builds `dist/proposal/Allpro-Services-Website-Proposal.pdf`. It is A4, and the build fails if it is over 9.5 MB.

## Before launch: confirm with the client

### Contact and offices

- **Contact details.** `+353 91 381111` and `helpdesk@allpro.ie` are from allpro.ie/contact. A business directory lists a different number, 1850 255 776; it is not used.
- **Dublin, Limerick and Cork offices.** allpro.ie names "offices and depots in Galway, Limerick, Dublin and Cork" but publishes no address or phone for them. They are shown by name only.
- **County-to-region mapping** for routing quotes (`config/allpro.php`, `regions.*.counties`) is a placeholder and needs to be confirmed.
- **Registered office.** Only the Oranmore head office is shown. Confirm whether the registered office differs.
- **Inboxes** for `ALLPRO_NOTIFY_*`, plus the staff list, their roles and the regions of the sales owners.

### Services and security

- **Who provides security.** allpro.ie says its managed security services are "provided by our PSA-licensed outside contractor". The services page says the same, and makes no claim that Allpro itself holds a PSA licence.
  - The careers flow, however, recruits security officers and supervisors directly, and the licence register tracks their licences.
  - Confirm how security staff are employed, then adjust the copy or the roles to match.
  - The PSA public register could not be reached during research, so no licence is claimed.
- **SLA hours.** The response and resolution times per priority (table above) are demo defaults. Contracts may set their own.
- **PSA warning period.** The licence warning is set at 60 days (`StaffMember::EXPIRY_WARNING_DAYS`).

### Brand, photos and legal

- **Logo.** The header redraws the teal "APS" pebble and "Allpro services" wordmark from allpro.ie as SVG. Ask for the vector artwork.
- **Photos.**
  - The wet-floor cleaning photo is the company's own (allpro.ie, 2016); its use needs Allpro's confirmation.
  - Every other photo is from Wikimedia Commons, credited on `/photo-credits` and labelled "Illustrative photo". None shows Allpro staff, sites or clients. Replace them with the company's own.
- **Client audit system.** The site links to the existing `audit.allpro.ie` login from the old menu. That host serves a self-signed certificate, so browsers warn. It is outside this build.
- **Privacy notice and retention periods** need the company's sign-off. As drafted: applications and messages are kept up to 12 months, quote requests that do not become a contract up to 2 years, and service-desk records for the life of the contract plus 6 years. The notice names the DPC.
- **Not used:** client names, testimonials (the old site has unattributed quotes), contract awards, revenue and staff numbers. None of them appear on the public site.
