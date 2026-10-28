// Captures stills of the site's motion and interaction effects for the proposal's "Motion and
// interaction" page and, with VIDEO=path.mp4, records a short home-page video (needs ffmpeg).
// Runs with motion allowed (the other proposal shots use reduced motion so pages are settled).
// Usage: node scripts/proposal/motion.mjs (dev server on 8114 with demo data; env: BASE_URL, VIDEO)
import puppeteer from 'puppeteer-core';
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';

const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8114';
const OUT = path.resolve('dist/proposal/shots/motion');
const CHROME = process.env.CHROME_PATH ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const wait = (ms) => new Promise((r) => setTimeout(r, ms));

fs.rmSync(OUT, { recursive: true, force: true });
fs.mkdirSync(OUT, { recursive: true });

const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--hide-scrollbars', '--no-sandbox'] });
const page = await browser.newPage();
await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'no-preference' }, { name: 'prefers-color-scheme', value: 'light' }]);
await page.setViewport({ width: 1440, height: 900, deviceScaleFactor: 1 });

const stills = [];
// Jump (not smooth-scroll) so the page is where the screenshot expects it.
const jump = (selector, offset = 0) => page.evaluate((sel, o) => {
  const el = document.querySelector(sel);
  window.scrollTo({ top: el.getBoundingClientRect().top + window.scrollY - o, behavior: 'instant' });
}, selector, offset);
const hideHeader = () => page.addStyleTag({ content: 'body > header, .scroll-progress { visibility: hidden !important; }' });
// Screenshot the box of a selector (optionally padded / height-limited), in page coordinates.
async function still(name, selector, caption, { pad = 0, maxH = null, scroll = true } = {}) {
  if (scroll) await jump(selector, 60);
  await wait(60);
  const box = await page.$eval(selector, (e) => { const r = e.getBoundingClientRect(); return { x: r.left, y: r.top + window.scrollY, w: r.width, h: r.height }; });
  const clip = { x: Math.max(0, box.x - pad), y: Math.max(0, box.y - pad), width: Math.min(1440, box.w + pad * 2), height: Math.min(maxH ?? 99999, box.h + pad * 2) };
  const file = path.join(OUT, `${name}.jpg`);
  await page.screenshot({ path: file, type: 'jpeg', quality: 72, clip, captureBeyondViewport: true });
  stills.push({ name, file, caption, w: clip.width, h: clip.height });
  console.log('✓', name);
}

// 1. Hero entrance, caught mid-way: words rising, the APS pebble drawing itself.
await page.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
await wait(720);
await still('hero', 'main section.page-header', 'Hero entrance: the photograph settles, the headline rises word by word and the APS pebble draws itself in.', { scroll: false, maxH: 700 });

// 2. Count-up figures and the tagline marquee.
await page.goto(BASE + '/', { waitUntil: 'networkidle0' });
await hideHeader();
await jump('section[aria-label="Allpro at a glance"]', 200);
await wait(750);
{
  const y = await page.$eval('section[aria-label="Allpro at a glance"]', (e) => e.getBoundingClientRect().top + window.scrollY - 24);
  const y2 = await page.$eval('section[aria-labelledby="taglines-title"]', (e) => e.getBoundingClientRect().bottom + window.scrollY + 24);
  const file = path.join(OUT, 'counts.jpg');
  await page.screenshot({ path: file, type: 'jpeg', quality: 72, clip: { x: 0, y, width: 1440, height: y2 - y } });
  stills.push({ name: 'counts', file, caption: 'Sourced figures count up once when seen, above a slow marquee of the old site\'s own service taglines (it pauses on hover or focus).', w: 1440, h: y2 - y });
  console.log('✓ counts');
}

// 3. Services switcher, with Security chosen: the band washes to the security colour.
await page.click('#tab-security');
await wait(900);
await still('switcher', 'section.svc-switch', 'Services switcher: choosing a service slides its panel in and washes the whole band in that service\'s colour from the old allpro.ie.', { maxH: 900 });

// 4. Coverage map: routes drawn from Oranmore, Dublin's counties lit.
await jump('section[aria-labelledby="regions-title"]', 0);
await wait(2200);
await page.evaluate(() => document.querySelectorAll('.region-btn')[1].dispatchEvent(new MouseEvent('mouseenter')));
await wait(700);
await still('map', 'section[aria-labelledby="regions-title"]', 'Coverage map: routes draw out from the Oranmore head office, then each region lights up the counties it covers. Hover, tap or tab to choose one.', { scroll: false, maxH: 900 });

// 5. Quote form step guide, with the first two sections complete.
await page.goto(BASE + '/quote', { waitUntil: 'networkidle0' });
await page.click('input[name="services[]"][value="cleaning"]');
await page.click('input[name="services[]"][value="security"]');
await page.select('select[name=sector]', await page.$eval('select[name=sector] option:nth-child(2)', (o) => o.value));
await page.type('input[name=site_name]', 'Head office');
await page.type('input[name=site_address]', '1 Sample Street');
await page.type('input[name=town]', 'Galway');
await page.select('select[name=county]', 'Galway');
await jump('#step-2', 220);
await wait(800);
// A 2:1 crop around the step guide and the form beside it.
const aside = await page.$eval('nav[aria-label="Form sections"]', (e) => { const r = e.getBoundingClientRect(); return { x: r.right, y: r.top + window.scrollY }; });
const file5 = path.join(OUT, 'steps.jpg');
const clip5 = { x: Math.max(0, aside.x + 24 - 880), y: aside.y - 30, width: 880, height: 440 };
await page.screenshot({ path: file5, type: 'jpeg', quality: 72, clip: clip5 });
stills.push({ name: 'steps', file: file5, caption: 'Quote and job forms: a step guide follows you down the page, ticks off each section once its answers are valid and fills a progress bar.', w: clip5.width, h: clip5.height });
console.log('✓ steps');

// 6. Service-desk SLA clocks counting down.
await page.goto(BASE + '/client-services', { waitUntil: 'networkidle0' });
await hideHeader();
await jump('section[aria-labelledby="clocks-title"]', 120);
await wait(2600);
await still('sla', 'section[aria-labelledby="clocks-title"]', 'Response clocks: one ring per urgency, emptying in real time as an example of what a client sees on their service-desk link.', { scroll: false, pad: 24 });

// 7. Thank-you: the tick draws and a burst in the service colours (after a real demo enquiry).
await page.goto(BASE + '/contact', { waitUntil: 'networkidle0' });
await wait(300);
await page.evaluate(() => {
  const f = document.querySelector('form[action$="/contact"]');
  f.querySelector('[name=name]').value = 'Proposal capture (sample)';
  f.querySelector('[name=email]').value = 'sample@example.ie';
  f.querySelector('[name=topic][value=services]').checked = true;
  f.querySelector('[name=message]').value = 'Sample message sent while capturing the proposal.';
});
await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.$eval('form[action$="/contact"]', (f) => f.requestSubmit())]);
await wait(1250);
if (page.url().endsWith('/thanks')) {
  await page.evaluate(() => window.scrollTo(0, 0));
  const box = await page.$eval('.sent-check', (e) => { const r = e.getBoundingClientRect(); return { x: r.left, y: r.top + window.scrollY }; });
  const file7 = path.join(OUT, 'thanks.jpg');
  const clip7 = { x: Math.max(0, box.x - 60), y: box.y - 40, width: 880, height: 440 };
  await page.screenshot({ path: file7, type: 'jpeg', quality: 72, clip: clip7 });
  stills.push({ name: 'thanks', file: file7, caption: 'After sending: the tick draws itself and a short burst in the service colours, next to the reference number.', w: clip7.width, h: clip7.height });
  console.log('✓ thanks');
} else {
  console.warn('! contact form did not reach /thanks; skipping that still');
}

fs.writeFileSync(path.join(OUT, 'motion.json'), JSON.stringify(stills, null, 1));

// Optional: a short home-page video for email or LinkedIn.
if (process.env.VIDEO) {
  const vp = { width: 1280, height: 720 };
  const vpage = await browser.newPage();
  await vpage.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'no-preference' }, { name: 'prefers-color-scheme', value: 'light' }]);
  await vpage.setViewport({ ...vp, deviceScaleFactor: 1 });
  await vpage.goto(BASE + '/', { waitUntil: 'networkidle0' });
  await vpage.evaluate(() => window.scrollTo(0, 0));
  const webm = path.join(OUT, 'home.webm');
  await vpage.goto('about:blank');
  const rec = await vpage.screencast({ path: webm });
  await vpage.goto(BASE + '/', { waitUntil: 'networkidle0' });
  const glide = (sel, offset, ms) => vpage.evaluate((s, o, d) => new Promise((done) => {
    const el = s ? document.querySelector(s) : null;
    const to = el ? el.getBoundingClientRect().top + window.scrollY - o : o;
    const from = window.scrollY; const t0 = performance.now();
    const step = (t) => { const k = Math.min(1, (t - t0) / d); const e = k < 0.5 ? 2 * k * k : 1 - (-2 * k + 2) ** 2 / 2; window.scrollTo(0, from + (to - from) * e); k < 1 ? requestAnimationFrame(step) : done(); };
    requestAnimationFrame(step);
  }), sel, offset, ms);
  await wait(3200);                                         // hero entrance
  await glide('section[aria-label="Allpro at a glance"]', 220, 1500); await wait(1800); // count-up + marquee
  await glide('section.svc-switch', 40, 1600); await wait(900);
  await vpage.click('#tab-cleaning'); await wait(1200);
  await vpage.click('#tab-security'); await wait(1200);
  await vpage.click('#tab-landscaping'); await wait(1300);
  await glide('section[aria-labelledby="regions-title"]', 0, 1600); await wait(4800); // routes draw, regions cycle
  await glide('#how-title', 120, 1400); await wait(1800);
  await rec.stop();
  await vpage.close();
  fs.mkdirSync(path.dirname(path.resolve(process.env.VIDEO)), { recursive: true });
  execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-i', webm, '-vf', 'fps=30,format=yuv420p', '-c:v', 'libx264', '-preset', 'slow', '-crf', '26', '-movflags', '+faststart', '-an', path.resolve(process.env.VIDEO)]);
  const mb = fs.statSync(process.env.VIDEO).size / 1024 / 1024;
  console.log(`✓ video ${process.env.VIDEO} (${mb.toFixed(1)} MB)`);
}

await browser.close();

// Remove the demo enquiries this script sent, so they never show up in the back-office shots.
execFileSync('php', ['artisan', 'tinker', '--execute=App\\Models\\Enquiry::where("name", "Proposal capture (sample)")->get()->each->delete();']);
