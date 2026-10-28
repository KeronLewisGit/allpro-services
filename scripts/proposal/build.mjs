// Builds the client proposal (A4 PDF) from the captured screenshots, in the site's own palette and
// fonts (deep teal, Allpro blue and hi-vis yellow; Encode Sans Semi Condensed and Rethink Sans).
// Usage: npm run proposal (or node scripts/proposal/build.mjs after a capture).
import puppeteer from 'puppeteer-core';
import fs from 'node:fs';
import path from 'node:path';

const CHROME = process.env.CHROME_PATH ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const ROOT = path.resolve('.');
const SHOTS = path.resolve('dist/proposal/shots');
const chunks = JSON.parse(fs.readFileSync(path.join(SHOTS, 'chunks.json'), 'utf8'));
const OUT_HTML = path.resolve('dist/proposal/proposal.html');
const OUT_PDF = path.resolve('dist/proposal/Allpro-Services-Website-Proposal.pdf');
const MAX_MB = 9.5;
const DATE = new Date().toLocaleDateString('en-IE', { day: 'numeric', month: 'long', year: 'numeric' });
const TESTS = process.env.TEST_COUNT ?? '81';
const img = (f) => 'file://' + f;
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;');

// The site's self-hosted fonts (built by the Vite fonts plugin into public/build/assets).
const ASSETS = path.join(ROOT, 'public/build/assets');
const font = (prefix) => {
  const f = fs.readdirSync(ASSETS).find((n) => n.startsWith(prefix) && n.endsWith('.woff2'));
  if (!f) throw new Error(`Font ${prefix} not found in public/build/assets; run npm run build first.`);
  return 'file://' + path.join(ASSETS, f);
};
const FONTS = [
  ['Encode Sans Semi Condensed', 700, 'encode-sans-semi-condensed-700-normal'], ['Encode Sans Semi Condensed', 800, 'encode-sans-semi-condensed-800-normal'],
  ['Rethink Sans', 400, 'rethink-sans-400-normal'], ['Rethink Sans', 500, 'rethink-sans-500-normal'],
  ['Rethink Sans', 600, 'rethink-sans-600-normal'], ['Rethink Sans', 700, 'rethink-sans-700-normal'],
].map(([fam, w, p]) => `@font-face { font-family: "${fam}"; font-weight: ${w}; font-style: normal; src: url("${font(p)}") format("woff2"); }`).join('\n');

// The site's redrawn APS pebble (after the mark on the 2016 allpro.ie).
const MARK = (h = 34, ring = false) => `<span class="mark"><svg viewBox="0 0 120 66" height="${h}" width="${Math.round(h * 120 / 66)}"><path d="M6 46C2 36 30 14 70 5c25-5 42-5 44 9 3 20-16 42-48 47-30 5-56-3-60-15z" fill="#00799e"${ring ? ' stroke="#ffffff" stroke-opacity=".55" stroke-width="2"' : ''}/><text x="60" y="45" text-anchor="middle" transform="rotate(-9 60 38)" font-family="Encode Sans Semi Condensed" font-weight="800" font-size="27" fill="#ffffff">APS</text></svg></span>`;
const WORDMARK = (dark = true) => `<div class="brand">${MARK(dark ? 40 : 30, dark)}<div><span class="n ${dark ? '' : 'ink'}">Allpro</span><span class="s ${dark ? '' : 'ink'}">services · Oranmore, Co. Galway · Since 2002</span></div></div>`;
// Lanyard-tag label, as on the site.
const plate = (code, label, dark = false) => `<p class="plate ${dark ? 'dark' : ''}"><span class="c">${esc(code)}</span><span class="l">${esc(label)}</span></p>`;

/** Hand-written captions per slice (key = screen name, index = slice number), from what each slice shows. */
const CAPTIONS = {
  home: [
    'Hero with the company\'s own description of what it does, the two actions most visitors need (request a quote or site survey; log a request as an existing client) and a site-pass card with the head office, helpdesk number and email. Below it, three ways in: new sites, clients and jobs.',
    'One contract for everything a building needs: the eight service lines from allpro.ie, then the company\'s mission statement in its own words beside its own photograph.',
    'The four regional bases, each with its own sales owner, and how a quote works from first call to first shift.',
    'Contact strip and the footer, with the company registration number.',
  ],
  services: [
    'Services: every line from the old site with its list of what is covered, starting with facilities management and contract cleaning.',
    'Security, industrial and maintenance, and washroom and sanitary services.',
    'Consumables, landscaping and pest control, then the sectors the old site lists.',
    'Closing call to action: request a quote or a site survey.',
  ],
  about: ['About: established in 2002 as a contract cleaning company, the mission and vision in the company\'s own words, and the four regional bases.'],
  contact: ['Contact: the helpdesk number and email, the Oranmore head office with directions, and a message form that works without JavaScript and issues an EN- reference.'],
  quote: [
    'Request a quote and site survey: pick the services; the side panel explains what happens next and points existing clients to their service-desk link.',
    'The site (county routes the request to the regional sales team; Eircode checked) and a survey date: weekdays only, at least two working days out.',
    'Contact details and consent, then send. The sender gets a QR- reference on screen and by email.',
  ],
  clients: ['Client services: how the service-desk link works, what the client can log, and the response times each priority is measured against.'],
  portal: [
    'A client site\'s private service desk, opened from a signed link that expires and can be withdrawn. No login, no password to forget.',
    'Log a request, complaint or security incident with photos; on the right, the site\'s recent requests with the updates Allpro chose to share. Internal notes never appear here.',
  ],
  careers: [
    'Careers: openings by region and role (sample postings here), filterable, with hours and closing dates.',
    'Why apply: no CV needed, shifts that suit, and a plain statement that HR handles Garda vetting after an offer.',
  ],
  apply: [
    'Job application: the opening or role and region first. Security roles must give a current PSA licence number and expiry; cleaning roles never see those fields.',
    'The availability grid (early, late and night, seven days), experience and right to work.',
    'An optional CV, consent, and the reminder that vetting documents are never uploaded here.',
  ],
  'admin-login': ['Staff sign-in. Switched-off accounts are refused and signed out.'],
  'admin-dashboard': [
    'Overview for operations or an administrator: the number of service tickets that have breached, or will breach, their SLA within the hour comes first, with the most urgent listed. Below, tiles for every area and weekly charts. Figures are from the demo data.',
  ],
  'admin-dashboard-sales': ['The same overview signed in as sales: it leads with site surveys requested for the next seven days that are not yet booked.'],
  'admin-dashboard-hr': ['And as HR: it leads with PSA licences that have expired or expire within 60 days.'],
  'admin-tickets': [
    'Service desk: status tabs, filters by type, priority and region, an "at SLA risk" view, search and CSV export.',
    'Every ticket with its client site, priority, status, live SLA clock and owner.',
  ],
  'admin-ticket': ['A ticket: what was reported and by whom, the response and resolution clocks, owner and status, and an update to the client that is shown on their link and emailed to them. Internal notes stay internal.'],
  'admin-account': ['A client site: create a service-desk link valid for 30, 90 or 365 days, email it to the site contact, or withdraw every link at once. The site\'s tickets and timeline sit alongside.'],
  'admin-quotes': [
    'Quote requests: status tabs, filters by region, service and owner, and a "survey within 7 days, not booked" view.',
    'Each request with its services, region, sales owner and requested survey date.',
  ],
  'admin-quote': ['A quote request: the site, services, requested survey window and floor plans (opened privately, every opening logged), with owner, survey booking, quoted value and status on the right.'],
  'admin-applications': [
    'Applications: the pipeline by region and stage, then filters by role, region, shift and opening.',
    'Each applicant with role, region, shifts, PSA licence state, vetting stage and status.',
    'The register continues, paginated.',
  ],
  'admin-application': [
    'An applicant: one-tap call, the PSA licence and its expiry, the availability grid, and HR\'s checks panel: Garda vetting stage, right to work and PSA register check, each dated and initialled.',
    'Status, conversion to a staff record once hired, and the timeline. Hiring is blocked while a check is outstanding.',
  ],
  'admin-staff': ['Licence register: every security officer and supervisor with their PSA licence and expiry, here filtered to those due within 60 days.'],
  'admin-staff-member': ['A staff record: licence, vetting and region, with reminders reset automatically when a renewed expiry date is entered.'],
  'admin-users': ['Staff accounts with four roles (administrator, sales, HR and operations) and a home region for sales owners.'],
};

/** Screen definitions: section, key, title, bullets. */
const SECTIONS = [
  { id: 'public', eyebrow: 'Part 1', code: '01', title: 'The public website', intro: 'A new allpro.ie on HTTPS, written from the company\'s own website and its company record. Every inner page opens with the same deep-teal header and site-pass card, and every page works on a phone.', screens: [
    ['home', 'Home page', ['Leads with what Allpro does, a quote or survey request, and the helpdesk.', 'No broken widgets, no "© 2016", and every form works without JavaScript.']],
    ['services', 'Services', ['Eight service lines with the lists the current site publishes.', 'Old addresses such as /cleaning and /security-services redirect to the new pages.']],
    ['about', 'About', ['The 2002 story, the mission and the vision in the company\'s own words.']],
    ['contact', 'Contact', ['Helpdesk phone and email, head office and a message form.']],
  ]},
  { id: 'flows', eyebrow: 'Part 2', code: '02', title: 'Quotes, clients and recruitment', intro: 'Three flows replace phone tag and inbox searching: quote and site-survey requests routed to the right region, a private service desk for every client site, and recruitment built for cleaning and security shifts. Each gives the sender a reference number on screen and by email.', screens: [
    ['quote', 'Request a quote and site survey', ['The county decides which regional sales owner gets the request.', 'Surveys are booked on weekdays, at least two working days out.']],
    ['clients', 'Client services', ['Explains the service desk and the response time behind each priority.']],
    ['portal', 'A client\'s service desk', ['One signed link per client site: requests, complaints and incidents with photos, timed against the SLA.']],
    ['careers', 'Careers', ['Openings posted by HR by region and role, with Google job-posting data.']],
    ['apply', 'Job application', ['A phone number and the shifts you can work; no CV needed.', 'PSA licence for security roles; Garda vetting is handled by HR, never uploaded.']],
  ]},
  { id: 'admin', eyebrow: 'Part 3', code: '03', title: 'The back office', intro: 'A private, role-based back office for operations, sales and HR. Each role sees only its own registers, and the overview opens on the one number that role has to act on today.', screens: [
    ['admin-login', 'Staff sign-in', ['Individual accounts with four roles; switched-off accounts are refused.']],
    ['admin-dashboard', 'Overview for operations', ['Tickets at risk of breaching the SLA come first.']],
    ['admin-dashboard-sales', 'Overview for sales', ['Surveys requested this week that are not booked come first.']],
    ['admin-dashboard-hr', 'Overview for HR', ['PSA licences expired or due within 60 days come first, and a daily email lists them.']],
    ['admin-tickets', 'Service desk', ['Response and resolution clocks on every ticket.']],
    ['admin-ticket', 'Ticket detail', ['Updates to the client are shown on their link and emailed; internal notes stay internal.']],
    ['admin-account', 'Client site', ['Links valid for 30, 90 or 365 days, withdrawn in one click.']],
    ['admin-quotes', 'Quote requests', ['A sales owner per region; unassigned requests are flagged.']],
    ['admin-quote', 'Quote detail', ['Owner, survey booking, quoted value and status, with a full timeline.']],
    ['admin-applications', 'Applications', ['The pipeline by region and stage, filterable by shift.']],
    ['admin-application', 'Applicant detail', ['Vetting, right to work and the PSA register check recorded by HR.']],
    ['admin-staff', 'Licence register', ['Every PSA licence and its expiry, with a 60-day warning.']],
    ['admin-staff-member', 'Staff record', ['Created from the application, so nothing is typed twice.']],
    ['admin-users', 'Staff and roles', ['Add staff, set roles and regions, and switch accounts off.']],
  ]},
];
const SCREEN_COUNT = SECTIONS.reduce((n, s) => n + s.screens.length, 0);

const FEATURES = [
  ['Public website', ['Eight service lines', 'About, mission and vision', 'Four regional bases', 'Contact with directions', 'Redirects from the old site\'s links', 'Sitemap and Google structured data', 'Call, email and quote bar on phones', 'Irish GDPR privacy notice']],
  ['Intake', ['Quote and site-survey requests', 'Routing by county to a regional sales owner', 'Client service desk on signed links', 'Requests, complaints and incidents with photos', 'Careers with job postings', 'Applications with shift availability and PSA licence', 'Reference number on screen and by email', 'Alert email to each team\'s inbox']],
  ['Back office', ['Overview that leads with each role\'s number', 'Service desk with SLA clocks', 'Client sites and their links', 'Quote register with survey booking', 'Recruitment pipeline by region and stage', 'Vetting, right-to-work and PSA checks', 'PSA licence register with a daily reminder email', 'Notes, timeline and CSV export on every register']],
  ['Platform', ['Laravel 13 on PHP 8.3', 'HTTPS throughout', 'All uploads stored privately', 'Each team opens only its own files', 'Rate-limited, spam-protected forms', 'Light and dark themes', `${TESTS} automated tests`, 'Packaged for one-zip installation']],
];

/**
 * Pack a screen's slices onto pages. A slice's height ÷ width is its share of a page. Every slice keeps
 * its own caption; slices never break across pages and keep their order. The layout is chosen by cost:
 * one point per page, one more for a page left about a third empty, half a point for shrinking slices
 * below 90% of a 1440px screen to share a page (never below 85%), and a quarter point for moving the
 * bullets off the first page. So a short slice joins its neighbour instead of sitting alone on a
 * half-blank page; ties go to the layout whose emptiest page is fullest.
 */
function pages(c, hasBullets) {
  const sizes = c.sizes ?? c.chunks.map(() => [1440, 1440]);
  // Page capacity as image height ÷ text width: about 1.35 below the heading on A4, 1.10 when bullets sit on top.
  // The estimate leaves out caption height, so a page counts as gappy at 25% estimated (about 35% printed).
  const CAP = 0.08, GOOD_SCALE = 0.9, MIN_SCALE = 0.85, GAP = 0.25;
  const ratios = sizes.map(([w, h]) => h / w);
  const page = (from, to, bullets) => {
    const budget = bullets ? 1.10 : 1.35;
    const sum = ratios.slice(from, to).reduce((a, r) => a + r, 0);
    const n = to - from;
    const scale = Math.min(1, (budget - CAP * n) / sum);
    const readable = scale * 1440 / sizes[from][0]; // narrower captures carry larger type
    if (n > 1 && readable < MIN_SCALE) return null;
    const empty = 1 - (sum * scale + CAP * n) / budget;
    return { items: [...Array(n).keys()].map((k) => from + k), bullets, budget, scale, empty, cost: 1 + (empty > GAP ? 1 : 0) + (readable < GOOD_SCALE ? 0.5 : 0) + (bullets && from > 0 ? 0.25 : 0) };
  };
  // best(i, owed) = cheapest packing of slices i..end, where owed means the bullets still need a page.
  const n = ratios.length;
  const memo = new Map();
  const best = (i, owed) => {
    if (i === n) return owed ? null : { list: [], cost: 0, worst: 0 };
    const key = i + ':' + owed;
    if (memo.has(key)) return memo.get(key);
    let pick = null;
    for (const here of owed ? [true, false] : [false]) {
      for (let j = i + 1; j <= n; j++) {
        const pg = page(i, j, here);
        if (!pg) break;
        const rest = best(j, owed && !here);
        if (!rest) continue;
        const cand = { list: [pg, ...rest.list], cost: pg.cost + rest.cost, worst: Math.max(pg.empty, rest.worst) };
        if (!pick || cand.cost < pick.cost - 1e-9 || (Math.abs(cand.cost - pick.cost) < 1e-9 && cand.worst < pick.worst - 1e-9)) pick = cand;
      }
    }
    memo.set(key, pick);
    return pick;
  };
  return best(0, hasBullets).list;
}

function caption(key, i, c) {
  const text = CAPTIONS[key]?.[i];
  if (!text) return '';
  const n = c.chunks.length > 1 ? `${i + 1}/${c.chunks.length} · ` : '';
  return `<figcaption class="cap"><span class="n">${esc(n)}</span>${esc(text)}</figcaption>`;
}

function screenPages(sec, [key, title, bullets]) {
  const c = chunks[key];
  if (!c) throw new Error(`Screenshot missing: ${key}`);
  return pages(c, bullets.length > 0).map((p, pi) => `
    <section class="page shot">
      <header class="shot-head">
        <div><p class="eyebrow">${esc(sec.title)}${pi ? ' · continued' : ''}</p><h2>${esc(title)}</h2></div>
        <p class="url">${esc(c.url.split('?')[0].replace(/^(\/service-desk\/)[^/]+\/.+$/, '$1…'))}</p>
      </header>
      ${p.bullets ? `<ul class="bullets">${bullets.map((b) => `<li>${esc(b)}</li>`).join('')}</ul>` : ''}
      ${p.items.map((i) => `<figure><img style="width:${(p.scale * 100).toFixed(1)}%" src="${img(c.chunks[i])}" alt="${esc(title)}">${caption(key, i, c)}</figure>`).join('')}
      ${foot()}
    </section>`).join('');
}

const foot = () => `<footer class="foot"><span class="fl">${MARK(12)}Allpro Services · Website proposal</span><span>Code Canvas Consultants Ltd · ${esc(DATE)}</span></footer>`;
const notes = (list) => `<div class="phone-notes">${list.map(([t, d]) => `<div><b>${esc(t)}</b>${esc(d)}</div>`).join('')}</div>`;
const phones = (list) => `<div class="phones">${list.map(([k, c]) => `<figure><img src="${img(chunks[k].chunks[0])}" alt="${esc(c)}"><figcaption>${esc(c)}</figcaption></figure>`).join('')}</div>`;

const html = `<!doctype html><html lang="en-IE"><head><meta charset="utf-8"><title>Allpro Services – Website Proposal</title>
<style>
  ${FONTS}
  @page { size: A4; margin: 0.5in 0.6in 0.55in; }
  :root { --deep: #072b37; --deep-950: #041d26; --bg: #f2f6f7; --surface-2: #e6eef0; --ink: #0b2530; --ink-2: #2f4a55; --ink-3: #4f6670; --line: #d3dfe3; --hivis: #d7f23c; --primary: #00799e; --primary-text: #006a8b; --primary-soft: #dcf0f6; }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; font-family: "Rethink Sans", system-ui, sans-serif; color: var(--ink); font-size: 10.5pt; line-height: 1.45; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  h1, h2, h3, .display { font-family: "Encode Sans Semi Condensed", system-ui, sans-serif; font-weight: 800; letter-spacing: -0.02em; color: var(--ink); margin: 0; line-height: 1.05; }
  h3 { font-family: "Rethink Sans", system-ui, sans-serif; font-weight: 700; letter-spacing: 0; font-size: 12pt; }
  .page { page-break-after: always; break-after: page; height: 10.6in; display: flex; flex-direction: column; overflow: hidden; }
  .page:last-child { page-break-after: auto; }
  .eyebrow { font-size: 8pt; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: var(--primary-text); margin: 0 0 5px; }
  .muted { color: var(--ink-3); }
  /* lanyard-tag label, as on the site */
  .plate { align-self: flex-start; display: inline-flex; border: 1.5px solid currentColor; border-radius: 8px; padding: 2px; font-size: 7.5pt; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; line-height: 1; color: var(--ink-2); margin: 0 0 12px; }
  .plate span { display: inline-flex; align-items: center; padding: 5px 8px 4px; border-radius: 6px; }
  .plate .c { background: var(--ink); color: var(--bg); font-family: "Encode Sans Semi Condensed"; font-weight: 800; letter-spacing: .04em; }
  .plate .l { margin-left: 2px; }
  .plate.dark { color: rgba(255,255,255,.85); } .plate.dark .c { background: var(--hivis); color: var(--deep-950); }
  /* hi-vis band: the reflective stripe that closes every page header on the site */
  .hivis { height: 10px; background: linear-gradient(var(--hivis), var(--hivis)) top / 100% 6px no-repeat, linear-gradient(#c9d1d4, #c9d1d4) bottom / 100% 2px no-repeat; }
  .dark-page { background: var(--deep); color: #fff; padding: 0.6in 0.6in 0.5in; position: relative; overflow: hidden; }
  .dark-page h1 { color: #fff; }
  .cover { justify-content: space-between; background-image: linear-gradient(180deg, rgba(4,29,38,.55), rgba(4,29,38,.96) 60%), url("${img(path.join(ROOT, 'public/img/scrubber-1920.webp'))}"); background-size: cover; background-position: 50% 0; }
  .cover h1 { font-size: 40pt; margin: 14px 0 16px; max-width: 6.2in; }
  .cover .accent { color: var(--hivis); }
  .cover .lead { color: rgba(255,255,255,.86); font-size: 13pt; max-width: 5.8in; margin: 0; }
  .cover .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; border-top: 1px solid rgba(255,255,255,.2); padding-top: 16px; font-size: 9.5pt; color: rgba(255,255,255,.82); }
  .cover .meta b { display: block; color: #fff; font-size: 9pt; letter-spacing: .14em; text-transform: uppercase; margin-bottom: 4px; }
  .cover .photo-note { position: absolute; right: 0.6in; top: 0.6in; font-size: 7pt; color: rgba(255,255,255,.6); }
  .brand { display: flex; align-items: center; gap: 10px; } .brand .n { display: block; font-family: "Encode Sans Semi Condensed"; font-weight: 800; font-size: 19pt; color: #fff; line-height: 1; letter-spacing: -0.02em; } .brand .s { display: block; margin-top: 4px; font-size: 7pt; font-weight: 600; letter-spacing: .14em; text-transform: uppercase; color: rgba(255,255,255,.72); }
  .mark { display: inline-flex; align-items: center; flex: none; }
  .brand .n.ink { color: var(--primary); } .brand .s.ink { color: var(--ink-3); }
  .divider { justify-content: center; }
  .divider h1 { font-size: 38pt; margin: 6px 0 18px; } .divider p.intro { color: rgba(255,255,255,.86); font-size: 12.5pt; max-width: 6in; margin: 0; }
  .divider ol { color: rgba(255,255,255,.86); columns: 2; font-size: 10pt; margin: 26px 0 0; padding-left: 18px; } .divider li { break-inside: avoid; margin: 3px 0; }
  .divider .note { margin-top: 22px; font-size: 9pt; color: rgba(255,255,255,.68); max-width: 6in; }
  .divider .hivis, .cover .hivis { position: absolute; left: 0; right: 0; bottom: 0; }
  .prose h1 { font-size: 24pt; margin-bottom: 12px; }
  .prose h2 { font-size: 13pt; margin: 16px 0 6px; letter-spacing: -0.01em; }
  .prose p { margin: 0 0 8px; }
  .prose ul.plain { margin: 0 0 8px; padding-left: 16px; } .prose ul.plain li { margin: 2px 0; }
  .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .card { border: 1px solid var(--line); border-radius: 8px; padding: 11px 13px; background: #fff; break-inside: avoid; }
  .card h3 { margin-bottom: 5px; }
  .card ul { margin: 0; padding-left: 15px; } .card li { margin: 2px 0; font-size: 9.6pt; }
  .evidence { border-left: 4px solid var(--primary); background: var(--bg); border-radius: 0 8px 8px 0; padding: 10px 14px 10px 30px; margin: 8px 0 10px; }
  .evidence li { margin: 3px 0; }
  .evidence code { font-size: 8.8pt; background: #fff; border: 1px solid var(--line); border-radius: 4px; padding: 0 4px; }
  .src { font-size: 8pt; color: var(--ink-3); }
  .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin: 12px 0 6px; }
  .stat { background: var(--deep); color: #fff; border-radius: 8px; padding: 12px 12px 11px; }
  .stat b { display: block; font-family: "Encode Sans Semi Condensed"; font-weight: 800; font-size: 22pt; line-height: 1; color: #fff; border-left: 3px solid var(--hivis); padding-left: 8px; }
  .stat span { display: block; margin-top: 8px; font-size: 8.5pt; color: rgba(255,255,255,.8); line-height: 1.3; }
  table.feat { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
  table.feat th, table.feat td { border-bottom: 1px solid var(--line); padding: 8px 8px; text-align: left; vertical-align: top; }
  table.feat th { background: var(--surface-2); font-size: 8pt; text-transform: uppercase; letter-spacing: .1em; color: var(--ink-3); }
  .pill-row { display: flex; flex-wrap: wrap; gap: 5px; } .pill { border: 1px solid #a8d6e5; background: var(--primary-soft); color: var(--primary-text); border-radius: 999px; padding: 2px 9px; font-size: 8.5pt; font-weight: 600; }
  .shot-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; border-bottom: 2px solid var(--ink); padding-bottom: 6px; margin-bottom: 7px; }
  .shot-head h2 { font-size: 16pt; } .url { font-family: Menlo, Consolas, monospace; font-size: 8pt; color: var(--ink-3); margin: 0; white-space: nowrap; }
  .bullets { margin: 0 0 7px; padding-left: 16px; font-size: 9.3pt; } .bullets li { margin: 1px 0; } .bullets li::marker { color: var(--primary); }
  figure { margin: 0 0 8px; display: block; text-align: center; }
  figure img { display: block; margin: 0 auto; border: 1px solid var(--line); border-radius: 6px; }
  .cap { text-align: left; margin: 5px 0 0; font-size: 8.6pt; line-height: 1.35; color: var(--ink-2); border-left: 3px solid var(--primary); padding-left: 8px; }
  .cap .n { font-weight: 700; color: var(--ink-3); }
  .phones { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 12px; }
  .phones figure { margin: 0; }
  .phones img { width: 100%; border-radius: 14px; border: 7px solid var(--deep); }
  .phones figcaption { text-align: center; font-size: 9pt; color: var(--ink-2); margin-top: 6px; font-weight: 600; }
  .phone-notes { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 14px; }
  .phone-notes div { background: var(--bg); border-radius: 8px; padding: 9px 12px; font-size: 9pt; color: var(--ink-2); }
  .phone-notes b { display: block; color: var(--ink); font-size: 9.5pt; margin-bottom: 2px; }
  .price { display: grid; grid-template-columns: 1.45fr 1fr; gap: 14px; align-items: stretch; margin: 6px 0 4px; }
  .price .big { background: var(--deep); color: #fff; border-radius: 8px; padding: 16px 18px; }
  .price .big .amt { white-space: nowrap; font-family: "Encode Sans Semi Condensed"; font-weight: 800; font-size: 26pt; line-height: 1; color: #fff; } .price .big .amt small { font-size: 11pt; font-weight: 500; color: rgba(255,255,255,.75); }
  .price .big p { color: rgba(255,255,255,.84); margin: 8px 0 0; font-size: 9.6pt; }
  .price .year { border: 2px solid var(--primary); border-radius: 8px; padding: 14px 16px; background: var(--primary-soft); }
  .price .year div { display: flex; justify-content: space-between; gap: 8px; padding: 4px 0; border-bottom: 1px dotted #7fbcd0; font-size: 10pt; } .price .year div:last-child { border: 0; }
  .price .year b { font-family: "Encode Sans Semi Condensed"; font-weight: 800; }
  .terms li { margin: 3px 0; font-size: 9.6pt; }
  .sla { width: 100%; border-collapse: collapse; font-size: 9pt; margin-top: 4px; } .sla td, .sla th { border-bottom: 1px solid var(--line); padding: 4px 6px; text-align: left; } .sla th { font-size: 7.8pt; letter-spacing: .1em; text-transform: uppercase; color: var(--ink-3); background: var(--surface-2); }
  .weeks { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 6px 0 10px; }
  .week { border: 1px solid var(--line); border-radius: 8px; padding: 10px 12px; background: #fff; }
  .week b { display: inline-block; font-family: "Encode Sans Semi Condensed"; font-weight: 800; font-size: 8.5pt; background: var(--ink); color: var(--bg); border-radius: 4px; padding: 3px 6px; margin-bottom: 5px; }
  .week p { margin: 0; font-size: 9.5pt; }
  footer.foot { margin-top: auto; padding-top: 6px; font-size: 7.8pt; color: #7d8d93; display: flex; justify-content: space-between; border-top: 1px solid var(--line); align-items: center; } footer.foot .fl { display: inline-flex; align-items: center; gap: 6px; }
</style></head><body>

<section class="page dark-page cover">
  ${WORDMARK()}
  <span class="photo-note">Illustrative photo</span>
  <div>
    ${plate('APS', 'Website proposal · ' + DATE, true)}
    <h1>Quotes, client requests and new starters, <span class="accent">handled in one place.</span></h1>
    <p class="lead">A new allpro.ie on HTTPS, quote and site-survey requests routed to the right region, a private service desk for every client site with SLA clocks, recruitment built around shifts and PSA licences, and a back office for operations, sales and HR.</p>
  </div>
  <div class="meta">
    <div><b>Prepared for</b>Alan Connolly and Conor Nolan<br>Allpro Security Services Ireland Ltd<br>12A Westlink Business Park<br>Oranmore, Co. Galway</div>
    <div><b>Prepared by</b>Keron Lewis, Code Canvas Consultants Ltd<br>Port of Spain · codecanvastt.com<br>info@codecanvastt.com</div>
  </div>
  <div class="hivis"></div>
</section>

<section class="page prose">
  ${plate('01', 'Executive summary')}
  <h1>Why rebuild now</h1>
  <p>Allpro Services has been looking after Irish buildings since 2002 and now runs offices in Galway, Dublin, Limerick and Cork, with revenue of €39.6 million in the year to March 2025. The website a facilities manager or a job seeker finds today has not kept pace. When we checked on 27 October 2026:</p>
  <ul class="evidence">
    <li><b>https://allpro.ie does not open securely.</b> The server presents a certificate for <code>*.wpengine.com</code>, so browsers show a security warning and the site only works over plain HTTP, including its contact and careers forms.</li>
    <li><b>The footer still reads "© 2016 Allpro Services".</b></li>
    <li><b>A broken widget is visible on the home page:</b> "Please enter an Access Token on the Instagram Feed plugin Settings page."</li>
    <li><b>The contact form needs JavaScript.</b> Without it, the page shows "Notice: JavaScript is required for this content." where the form should be.</li>
  </ul>
  <p class="src">Sources: allpro.ie and allpro.ie/contact, checked 27 October 2026; Allpro Security Services Ireland Ltd (company no. 433727), accounts filed with the Companies Registration Office for the year to March 2025.</p>
  <p>This proposal replaces it with a site and back office built around how a contract services company actually works: quote and site-survey requests that reach the right regional sales owner, a private service-desk link for every client site with response and resolution clocks, and recruitment that collects shifts and PSA licences up front while HR handles Garda vetting. A working demo is built and can be shown live, on a laptop or a phone.</p>
  <div class="stat-grid">
    <div class="stat"><b>${SCREEN_COUNT}</b><span>screens designed, built and shown in this proposal</span></div>
    <div class="stat"><b>4</b><span>regions, each with its own sales owner and recruitment pipeline</span></div>
    <div class="stat"><b>4</b><span>staff roles, each seeing only its own registers</span></div>
    <div class="stat"><b>${TESTS}</b><span>automated tests run before every release</span></div>
  </div>
  <h2>What the company gains</h2>
  <div class="cols">
    <div class="card"><h3>Clients and prospects</h3><ul><li>A secure, current first impression</li><li>A quote or survey request in two minutes, with a reference number</li><li>Requests and complaints logged from site, with a visible response time</li></ul></div>
    <div class="card"><h3>Operations, sales and HR</h3><ul><li>Tickets at risk of breaching the SLA at the top of the screen</li><li>Unbooked surveys and unassigned requests flagged</li><li>PSA licences tracked, with a daily email before they lapse</li></ul></div>
  </div>
  ${foot()}
</section>

<section class="page prose">
  ${plate('02', 'At a glance')}
  <h1>What is included</h1>
  <table class="feat"><thead><tr><th style="width:20%">Area</th><th>Features</th></tr></thead><tbody>
    ${FEATURES.map(([a, f]) => `<tr><td><b>${esc(a)}</b></td><td><div class="pill-row">${f.map((x) => `<span class="pill">${esc(x)}</span>`).join('')}</div></td></tr>`).join('')}
  </tbody></table>
  <h2>How a client request flows</h2>
  <p>A facilities manager at a client site opens the service-desk link their account manager emailed them, and logs that the car-park bins were missed, as high priority, with a photo. They see a reference such as <b>SR-26-00041</b> straight away, and the operations inbox is alerted. In the back office the ticket shows a four-hour response clock. A supervisor takes it, sends an update ("Supervisor on site at 6 pm"), which appears on the client's link and is emailed to them, and resolves it. Whether each clock was met is recorded, so the monthly SLA figure is a count, not an estimate.</p>
  <h2>How a new starter flows</h2>
  <p>A security officer applies from a phone with a PSA licence number and expiry and ticks the shifts they can work. Expired licences are refused. HR moves them through phone screen and interview, records Garda vetting, right to work and the PSA register check, and only then can mark them hired, which creates their staff record. From then on the licence register warns HR 60 days before it lapses.</p>
  <h2>Content and imagery</h2>
  <p>The service lines, mission and vision come from the company's own website; the founding year, registration number and offices from the same site and the company record. Nothing is invented: no clients, figures, accreditations, staff or testimonials. One photograph is the company's own; the others are licensed from Wikimedia Commons, credited on the site and labelled "Illustrative photo" until Allpro supplies its own. Clients, tickets, applicants and staff in these screenshots are clearly marked samples with fictitious names.</p>
  ${foot()}
</section>

${SECTIONS.map((sec) => `
<section class="page dark-page divider">
  ${plate(sec.code, sec.eyebrow, true)}
  <h1>${esc(sec.title)}</h1>
  <p class="intro">${esc(sec.intro)}</p>
  <ol>${sec.screens.map((s) => `<li>${esc(s[1])}</li>`).join('')}</ol>
  <p class="note">Screens are from the working demo at 1440 pixels wide (back office at 1280). Clients, tickets, applicants, staff names and figures are sample data.</p>
  <div class="hivis"></div>
</section>
${sec.screens.map((s) => screenPages(sec, s)).join('')}
`).join('')}

<section class="page">
  <header class="shot-head"><div><p class="eyebrow">Responsive design</p><h2>Built for phones first</h2></div><p class="url">390 × 844</p></header>
  <p style="margin:0 0 4px">Site contacts, supervisors and job seekers mostly arrive on a phone. Every page adapts to a small screen, and a bar with Call, Email and Get a quote stays within thumb reach.</p>
  ${phones([['m-home', 'Home'], ['m-quote', 'Quote request'], ['m-portal', 'Client service desk']])}
  ${notes([['Thumb-sized controls', 'Every button and choice is at least 48 pixels tall.'], ['The right keyboard', 'Phone fields open the number pad; contact fields fill in from the phone\'s saved details.'], ['Photos from site', 'A client can attach a photo of the problem straight from the camera.']])}
  ${foot()}
</section>
<section class="page">
  <header class="shot-head"><div><p class="eyebrow">Responsive design · continued</p><h2>Applications and the back office on a phone</h2></div><p class="url">390 × 844</p></header>
  <p style="margin:0 0 4px">An applicant can apply between shifts, and a supervisor can see and answer tickets from the road.</p>
  ${phones([['m-apply', 'Job application'], ['m-admin-dashboard', 'Back-office overview'], ['m-admin-ticket', 'Ticket and SLA clocks']])}
  ${notes([['No CV needed', 'A phone number and the shifts they can work are enough to apply.'], ['At risk first', 'The overview opens on tickets close to breaching the SLA.'], ['One tap to call', 'Call or email the client contact or applicant from the record.']])}
  ${foot()}
</section>

<section class="page prose">
  ${plate('04', 'Technology, hosting & security')}
  <h1>Built properly, hosted for you</h1>
  <div class="cols">
    <div class="card"><h3>Platform</h3><ul><li>Laravel 13 on PHP 8.3 with a MySQL database: open-source and widely supported, with no proprietary page builder or plugins to break</li><li>Tailwind CSS and Alpine.js; fonts served from your own site, not a third-party font service</li><li>No background servers; emails send directly and one daily task sends the licence reminder</li><li>${TESTS} automated tests cover every form, role, register and private file</li></ul></div>
    <div class="card"><h3>Security and privacy</h3><ul><li>HTTPS enforced with a certificate for allpro.ie, and security headers on every page</li><li>CVs, floor plans and ticket photos are stored outside the public website and served only to signed-in staff with the right role; every opening is logged</li><li>Garda vetting documents are never collected online</li><li>Client service-desk links are signed, expire and are never indexed</li><li>Privacy notice under the GDPR and the Data Protection Act 2018, with the Data Protection Commission named</li></ul></div>
    <div class="card"><h3>Speed and search</h3><ul><li>Responsive WebP photography</li><li>XML sitemap, and schema.org data for the company and every job posting</li><li>The old WordPress addresses redirect permanently to the new pages</li><li>Accessible: 17px body text, keyboard navigation, labelled forms, contrast checked in light and dark themes, and every form works without JavaScript</li></ul></div>
    <div class="card"><h3>Ownership</h3><ul><li>Your content, photos, domain and data are always yours</li><li>Staff manage postings, client sites, links and users themselves</li><li>Hosting, backups, monitoring and updates are handled by Code Canvas under the monthly plan</li><li>Your Microsoft 365 email stays exactly where it is</li></ul></div>
  </div>
  <h2>Emails included</h2>
  <p>Every submission sends the sender a confirmation with their reference number and what happens next, and alerts the right team's inbox (operations, sales or HR) with a link to the record. Clients receive their service-desk link and every update Allpro chooses to share; HR receives a daily list of PSA licences that have expired or expire within 60 days. Private files are never attached to emails.</p>
  ${foot()}
</section>

<section class="page prose">
  ${plate('05', 'Investment')}
  <h1>The US$1,000 a month plan</h1>
  <div class="price">
    <div class="big"><div class="amt">US$1,000 <small>per month</small></div><p><b>US$0 for design and development.</b> No setup fee and no deposit. Invoiced in US dollars. Billing starts at the end of the month in which the site launches, prorated for the days it was live; monthly invoices are due within 14 days.</p></div>
    <div class="year"><div><span>Year one</span><b>US$12,000</b></div><div><span>Or prepaid annually (10% off)</span><b>US$10,800</b></div><div><span>Build cost</span><b>US$0</b></div></div>
  </div>
  <div class="cols" style="margin-top:8px">
    <div class="card"><h3>Included every month</h3><ul>
      <li>The complete design and build shown in this proposal</li>
      <li>Managed hosting with CDN and SSL</li>
      <li>Daily backups plus a weekly off-site copy</li>
      <li>Security patches and software updates</li>
      <li>24/7 uptime monitoring</li>
      <li><b>Up to 10 hours a month</b> of content changes and small improvements, such as new postings or service pages</li>
      <li>Staff training</li>
      <li>Google Analytics, Search Console and a monthly summary</li>
      <li>Support by email, WhatsApp and phone, plus a quarterly review call</li>
      <li>A 30-day post-launch warranty</li>
    </ul></div>
    <div class="card"><h3>Terms</h3><ul class="terms">
      <li>No minimum term. 30 days' notice by email from either side.</li>
      <li>Price fixed for the first 24 months.</li>
      <li>Optional annual prepayment at 10% off: US$10,800 a year.</li>
      <li><b>Handover fee US$7,000, only if you leave.</b> It buys the website outright and moves it to hosting in Allpro's name. It is waived if Code Canvas stops trading.</li>
      <li>Your content, domain and data are always yours.</li>
    </ul>
    <h3 style="margin-top:8px">Quoted separately, only with your approval</h3><ul>
      <li>Work beyond 10 hours in a month: US$50 an hour, or a fixed quote</li>
      <li>SMS alerts for urgent tickets or licence renewals</li>
      <li>Integrations with rostering, payroll, accounting or the client-audit system</li>
      <li>Paid advertising and SEO campaigns; professional photography</li>
    </ul></div>
  </div>
  <h2>Service levels</h2>
  <table class="sla"><thead><tr><th>Issue</th><th>Response</th><th>Fix target</th></tr></thead><tbody>
    <tr><td>Site down or forms not sending</td><td>1 hour, any day</td><td>4 hours</td></tr>
    <tr><td>Something on a page is broken</td><td>4 business hours</td><td>1 business day</td></tr>
    <tr><td>Content changes (text, photos, postings)</td><td>Acknowledged in 1 business day</td><td>Done in 2 business days</td></tr>
  </tbody></table>
  ${foot()}
</section>

<section class="page prose">
  ${plate('06', 'Timeline')}
  <h1>Six weeks to launch</h1>
  <div class="weeks">
    <div class="week"><b>Week 1</b><p>Kick-off, brand assets and content: your logo files, photos, and confirmation of every fact on the site.</p></div>
    <div class="week"><b>Week 2</b><p>Revision round 1. Forms, emails, regions and staff roles configured with your real inboxes.</p></div>
    <div class="week"><b>Week 3</b><p>Content load: open postings, client sites and their contacts, SLA times per contract.</p></div>
    <div class="week"><b>Week 4</b><p>Revision round 2.</p></div>
    <div class="week"><b>Week 5</b><p>Formal testing with your operations, sales and HR staff on every form and screen.</p></div>
    <div class="week"><b>Week 6</b><p>Launch on HTTPS, redirects from the old addresses, Search Console, and staff training.</p></div>
  </div>
  <h2>What we need from Allpro</h2>
  <ul class="plain">
    <li>Your logo as a vector file (the demo redraws the APS mark from the current website) and any brand guidelines.</li>
    <li>Photos of your teams and sites, so the illustrative photographs can be replaced, and confirmation of the rights to the one photograph used from the current site.</li>
    <li>Addresses and phone numbers for the Dublin, Limerick and Cork offices, and which counties each office covers.</li>
    <li>Your standard response and resolution times, if they differ from the demo's.</li>
    <li>The staff who need accounts, their roles and regions, and the inboxes for operations, sales and HR.</li>
    <li>Access to the allpro.ie DNS settings (your Microsoft 365 email records stay untouched).</li>
  </ul>
  <h2>When billing starts</h2>
  <p>Not on signing. The build, both revision rounds, testing and the preview site cost nothing. Billing starts at the end of the month in which the site launches, prorated for the days it was live. Nothing goes live until you approve it in writing.</p>
  ${foot()}
</section>

<section class="page prose">
  ${plate('07', 'Next steps')}
  <h1>From proposal to launch</h1>
  <ol class="plain" style="padding-left:18px">
    <li style="margin:5px 0"><b>See the demo live.</b> We walk through the website and the back office together, on a laptop and a phone.</li>
    <li style="margin:5px 0"><b>Tell us what to change.</b> Pages, wording, regions, roles or the intake flows; revision round 1 opens straight away.</li>
    <li style="margin:5px 0"><b>Sign a one-page agreement</b> with the terms in this proposal: US$1,000 a month, month to month on 30 days' notice, US$0 build cost, and a US$7,000 handover fee only if you leave.</li>
    <li style="margin:5px 0"><b>Kick-off the same week</b>, with launch six weeks later.</li>
  </ol>
  <div class="card" style="margin-top:18px; max-width:4.6in">
    <h3>Contact</h3>
    <p style="margin:0">Keron Lewis, Director<br>Code Canvas Consultants Ltd, Port of Spain<br>codecanvastt.com<br><a href="mailto:info@codecanvastt.com">info@codecanvastt.com</a><br><a href="https://calendly.com/keronlewis/30min">Book a call: calendly.com/keronlewis/30min</a></p>
  </div>
  <div style="margin-top:auto; margin-bottom:12px">${WORDMARK(false)}</div>
  ${foot()}
</section>
</body></html>`;

fs.writeFileSync(OUT_HTML, html);
const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox', '--allow-file-access-from-files'] });
const page = await browser.newPage();
await page.goto('file://' + OUT_HTML, { waitUntil: 'networkidle0' });
await page.evaluate(() => document.fonts.ready);
await page.pdf({ path: OUT_PDF, format: 'A4', printBackground: true, preferCSSPageSize: true, displayHeaderFooter: false });
await browser.close();
const mb = Math.round(fs.statSync(OUT_PDF).size / 1024 / 1024 * 10) / 10;
console.log('PDF written:', OUT_PDF, mb + ' MB');
if (mb > MAX_MB) {
  console.error(`The PDF is over ${MAX_MB} MB, too large for most email attachments. Lower the JPEG quality in capture.mjs.`);
  process.exit(1);
}
