// Dev helper: screenshots of every key page, phone + desktop, light + dark, and an overflow check.
// node scripts/screens.mjs <outdir>   (dev server on 8114 with demo data)
import puppeteer from 'puppeteer-core';
import fs from 'node:fs';
const OUT = process.argv[2] || 'dist/screens';
const BASE = process.env.BASE || 'http://127.0.0.1:8114';
fs.mkdirSync(OUT, { recursive: true });
const pub = ['/', '/services', '/about', '/contact', '/quote', '/client-services', '/careers', '/careers/security-officer-galway-city', '/careers/apply', '/privacy', '/photo-credits', 'PORTAL'];
// "list>" opens the first record linked from that list, so no ids are assumed.
const adm = ['/admin', '/admin/tickets', '/admin/tickets>', '/admin/clients>', '/admin/quotes', '/admin/quotes>', '/admin/applications', '/admin/applications>', '/admin/staff?due=1', '/admin/staff>'];
// A live client service-desk link from the demo data (php artisan tinker prints one).
const PORTAL = process.env.PORTAL || null;
const browser = await puppeteer.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: 'new' });
const problems = [];
for (const [vw, vh, tag] of [[390, 844, 'phone'], [1440, 900, 'desk']]) {
  for (const scheme of ['light', 'dark']) {
    const page = await browser.newPage();
    await page.setViewport({ width: vw, height: vh, deviceScaleFactor: 1 });
    await page.emulateMediaFeatures([{ name: 'prefers-color-scheme', value: scheme }]);
    for (const [i, url] of [...pub, 'LOGIN', ...adm].entries()) {
      if (url === 'LOGIN') {
        await page.goto(BASE + '/login');
        if (!page.url().endsWith('/login')) continue; // already signed in from an earlier pass
        await page.type('input[name=email]', 'admin@allpro.test');
        await page.type('input[name=password]', 'password');
        await Promise.all([page.waitForNavigation(), page.click('form .a-btn-primary')]);
        continue;
      }
      if (url === 'PORTAL') {
        if (!PORTAL) continue;
        await page.goto(PORTAL, { waitUntil: 'networkidle0' });
      } else if (url.endsWith('>')) {
        const list = url.slice(0, -1);
        await page.goto(BASE + list, { waitUntil: 'networkidle0' });
        const href = await page.evaluate((l) => [...document.querySelectorAll('main a')].map((a) => a.href).find((h) => new RegExp(l + '/(?!export|create)[A-Za-z0-9]+$').test(new URL(h).pathname)), list);
        if (!href) continue;
        await page.goto(href, { waitUntil: 'networkidle0' });
      } else {
        await page.goto(BASE + url, { waitUntil: 'networkidle0' });
      }
      await page.addStyleTag({ content: '[data-reveal]{opacity:1!important;transform:none!important;transition:none!important}' });
      await page.evaluate(async () => {
        document.querySelectorAll('img[loading=lazy]').forEach((i) => { i.loading = 'eager'; });
        await Promise.all([...document.images].map((i) => (i.complete ? 0 : new Promise((r) => { i.onload = i.onerror = r; }))));
      });
      const over = await page.evaluate(() => {
        const w = document.documentElement.clientWidth; const bad = [];
        document.querySelectorAll('body *').forEach((e) => { const r = e.getBoundingClientRect(); if (r.right > w + 1 && r.width > 0 && !e.closest('.table-box,.tag-row,.overflow-x-auto,.hero-photo,[aria-hidden=true]')) bad.push(e.tagName + '.' + [...e.classList].slice(0, 3).join('.')); });
        return { scroll: document.documentElement.scrollWidth > w, bad: bad.slice(0, 5) };
      });
      if (over.scroll || over.bad.length) problems.push(`${tag}/${scheme} ${url}: ${JSON.stringify(over)}`);
      const name = `${tag}-${scheme}-${String(i).padStart(2, '0')}-${url.replace(/\W+/g, '_') || 'home'}.png`;
      await page.screenshot({ path: `${OUT}/${name}`, fullPage: true });
    }
    await page.close();
  }
}
await browser.close();
console.log(problems.length ? problems.join('\n') : 'no overflow');
