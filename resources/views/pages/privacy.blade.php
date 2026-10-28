<x-layouts.site title="Privacy notice" description="How Allpro Services handles the personal data sent through this website, under the GDPR and the Data Protection Act 2018.">
    <x-page-header photo="scrubber-school" title="Privacy notice" kicker="Privacy" code="L-01" lead="What we keep when you request a quote, log a service request, apply for a job or send a message, why we keep it, and your rights under Irish data protection law." />
    <section class="wrap prose-copy measure mt-12 md:mt-16">
        <h2 class="!mt-0">Who we are</h2>
        <p>The controller of the personal data collected on this website is {{ allpro('name') }}, trading as {{ allpro('trading_as') }}, {{ allpro('address.street') }}, {{ allpro('address.town') }}, {{ allpro('address.county') }}, Ireland (company number {{ allpro('company_number') }}). Questions about your data: <a href="mailto:{{ allpro('email') }}">{{ allpro('email') }}</a> or {{ format_phone(allpro('phone')) }}.</p>
        <p>This notice is written under the General Data Protection Regulation (EU) 2016/679 (GDPR) and the Data Protection Act 2018.</p>

        <h2>What we collect, and our lawful basis</h2>
        <ul>
            <li><strong>Quote and site-survey requests:</strong> your organisation, name, role, email, phone, the site address and details, and any plans or specifications you attach. Basis: steps taken at your request before entering a contract (Article 6(1)(b)).</li>
            <li><strong>Client service-desk requests and complaints:</strong> the name and contact details of the person logging it, a description, and any photos. Basis: performing our contract with your organisation and our legitimate interest in running it well (Article 6(1)(b) and (f)).</li>
            <li><strong>Job applications:</strong> your name, phone, email, county, the role and region you want, experience, the shifts you can work, your right-to-work declaration, your PSA licence number and expiry (security roles), and a CV if you attach one. Basis: steps taken at your request before an employment contract (Article 6(1)(b)), and our legal obligation to employ only licensed staff in security roles under the Private Security Services Acts 2004 to 2011 (Article 6(1)(c)).</li>
            <li><strong>Messages:</strong> your name, contact details and message. Basis: our legitimate interest in replying (Article 6(1)(f)).</li>
        </ul>

        <h2>What we never collect here</h2>
        <p>We do not ask for, and you should not upload, Garda vetting forms, passports, ID cards, immigration documents or PPS numbers through this website. Garda vetting is carried out through the National Vetting Bureau's own e-vetting system, which HR invites you to if we offer you a role. Right-to-work documents are seen in person.</p>

        <h2>Who sees it</h2>
        <p>Each kind of submission is seen only by the staff who handle it: sales (quotes), operations (service requests), human resources (applications) and the site administrator. We do not sell your data or share it for marketing. Our website host and email provider process it on our behalf under contract.</p>

        <h2>Where it is kept</h2>
        <p>Uploads are stored on a private part of our web server that is not reachable from the internet; staff open them after signing in, and each opening is logged. Service-desk links are private, expire, and can be withdrawn at any time.</p>

        <h2>How long</h2>
        <ul>
            <li>Quote requests that do not become a contract: up to 2 years.</li>
            <li>Service-desk records: for the life of the contract and 6 years after, in line with contract-law limitation periods.</li>
            <li>Job applications that do not lead to a job: up to 12 months, unless you ask us to delete yours sooner.</li>
            <li>Messages: up to 12 months.</li>
        </ul>

        <h2>Your rights</h2>
        <p>You have the right to access, correct or erase your personal data, to restrict or object to its processing, and to data portability. Email <a href="mailto:{{ allpro('email') }}">{{ allpro('email') }}</a> and quote your reference number if you have one. We reply within one month.</p>
        <p>You also have the right to complain to the Data Protection Commission, 21 Fitzwilliam Square South, Dublin 2, D02 RD28, <a href="https://www.dataprotection.ie" rel="noopener" target="_blank">www.dataprotection.ie</a>.</p>

        <h2>Cookies</h2>
        <p>The site uses one strictly necessary session cookie to keep forms secure, and stores your light or dark mode choice in your browser. There is no advertising or tracking, so no cookie banner is needed.</p>
        <p class="hint">Last updated {{ now()->format('F Y') }}. To be reviewed and approved by Allpro before launch.</p>
    </section>
</x-layouts.site>
