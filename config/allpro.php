<?php

/*
|--------------------------------------------------------------------------
| Allpro Services: facts the site is built from
|--------------------------------------------------------------------------
| Every fact here comes from the company's own website (allpro.ie, 2016 build, read on
| 27 October 2026) or the Companies Registration Office record. Nothing is invented: no staff
| numbers, client names, testimonials, accreditations or licences. No clients are named
| anywhere on the site. See "Before launch" in README.md. Read with allpro('key').
*/

return [

    'name' => 'Allpro Security Services Ireland Ltd',
    'short_name' => 'Allpro Services',
    'trading_as' => 'Allpro Services',
    'alternate_names' => ['Allpro Services', 'APS', 'Allpro Security Services Ireland Limited'],
    // CRO company number (solocheck.ie / CRO search).
    'company_number' => '433727',
    // "Established in 2002" (allpro.ie/cleaning).
    'founded' => 2002,
    'description' => 'Allpro Services provides contract cleaning, security and facilities management to companies and state bodies across Ireland, from offices in Galway, Dublin, Limerick and Cork.',
    'footer_line' => 'Contract cleaning, security and facilities management across Ireland since 2002.',

    'phone' => '+35391381111',
    'email' => env('ALLPRO_PUBLIC_EMAIL', 'helpdesk@allpro.ie'),
    'address' => [
        'street' => '12A Westlink Business Park',
        'town' => 'Oranmore',
        'county' => 'Co. Galway',
        'country' => 'Ireland',
    ],
    'website' => 'allpro.ie',
    'map_url' => 'https://www.google.com/maps/search/?api=1&query=Westlink+Business+Park+Oranmore+Galway',
    // The existing client-audit login linked from the old site's menu.
    'client_audit_url' => 'https://audit.allpro.ie/clientLogin/login.html',

    // Concept preview: banner + noindex until the client signs off.
    'preview' => (bool) env('ALLPRO_PREVIEW', true),

    // Who is emailed about new submissions. Each area falls back to the default.
    'notify' => [
        'default' => env('ALLPRO_NOTIFY_EMAIL', ''),
        'careers' => env('ALLPRO_NOTIFY_CAREERS', ''),
        'staff' => env('ALLPRO_NOTIFY_HR', ''),
        'quotes' => env('ALLPRO_NOTIFY_QUOTES', ''),
        'servicedesk' => env('ALLPRO_NOTIFY_SERVICEDESK', ''),
        'enquiries' => env('ALLPRO_NOTIFY_ENQUIRIES', ''),
    ],

    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrator'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    /*
    | About: the mission and vision are the company's own words (allpro.ie/about, 2016).
    */
    'about' => [
        'intro' => 'Allpro Services was established in 2002 as a contract cleaning company and grew into a facilities management provider, with offices and depots in Galway, Limerick, Dublin and Cork. Clients hand over the day-to-day running of their buildings so they can concentrate on their own business.',
        'mission' => 'Our purpose as a company is to provide full facilities management solutions to the corporate and private sectors in a professional manner with a focus on delivering outstanding customer service at every interaction between our staff and our clients.',
        'vision' => 'Our vision guides every aspect of our business by describing what we need to accomplish in order to continue achieving sustainable, quality growth. Allpro Services aims to be a great place to work with inspired staff. We will nurture our network of valued customers and suppliers to create enduring value.',
        'source' => 'allpro.ie, About (2016)',
    ],

    /*
    | Regions: the four offices the old site names ("offices and depots in Galway, Limerick,
    | Dublin and Cork"). Only the Galway head office address and phone are published. The
    | county lists route quote requests to a regional sales owner; they are a placeholder
    | until Allpro confirms which office covers which county.
    */
    'regions' => [
        'galway' => ['label' => 'Galway', 'office' => 'Head office, Oranmore', 'code' => 'GAL', 'photo' => 'galway', 'counties' => ['Galway', 'Mayo', 'Roscommon', 'Sligo', 'Leitrim', 'Donegal', 'Longford', 'Westmeath', 'Offaly']],
        'dublin' => ['label' => 'Dublin', 'office' => 'Dublin office', 'code' => 'DUB', 'photo' => 'dublin', 'counties' => ['Dublin', 'Kildare', 'Meath', 'Wicklow', 'Louth', 'Wexford', 'Carlow', 'Kilkenny', 'Laois', 'Cavan', 'Monaghan']],
        'limerick' => ['label' => 'Limerick', 'office' => 'Limerick office', 'code' => 'LMK', 'photo' => 'limerick', 'counties' => ['Limerick', 'Clare', 'Tipperary']],
        'cork' => ['label' => 'Cork', 'office' => 'Cork office', 'code' => 'CRK', 'photo' => 'cork', 'counties' => ['Cork', 'Kerry', 'Waterford']],
    ],

    /*
    | Services: the service lines on allpro.ie (2016) with the lists published there. The
    | uppercase taglines are the old site's own.
    */
    'services' => [
        'facilities' => [
            'title' => 'Facilities management',
            'tagline' => 'From the inside to the outside',
            'icon' => 'building',
            'photo' => 'business-park',
            'summary' => 'We look after the day-to-day management of your facility, so you can concentrate on your core business. One contract can bring together cleaning, security, washroom services, consumables, landscaping and pest control.',
            'items' => ['Cleaning', 'Security', 'Pest control', 'Washroom and sanitary services', 'Consumables', 'Landscaping'],
        ],
        'cleaning' => [
            'title' => 'Contract cleaning',
            'tagline' => 'Clean as a button',
            'icon' => 'sparkle',
            'photo' => 'scrubber',
            'summary' => 'Reliable, professional cleaning staff, attention to detail, a focus on customer service and competitive pricing, for offices, schools, shops, medical sites, hotels, state bodies, industry and construction.',
            'items' => ['Contract cleaning', 'Janitorial supply', 'Washroom services', 'Feminine hygiene services', 'Mat, towel and linen rental', 'Sector-specific cleaning', 'Cleaning and consumable supplies'],
        ],
        'security' => [
            'title' => 'Security services',
            'tagline' => 'Secure and confident',
            'icon' => 'shield',
            'photo' => 'guard-dusk',
            'summary' => 'A combination of manned services and technology to keep your premises secure at all times, for clients in the private and public sectors.',
            'items' => ['Static guarding', 'Mobile patrol and response', 'Key holding', 'Crisis prevention', 'CCTV systems', 'Access control'],
        ],
        'industrial' => [
            'title' => 'Industrial and maintenance',
            'tagline' => 'Leading the way',
            'icon' => 'ladder',
            'photo' => 'window-abseil',
            'summary' => 'Trained industrial teams for one-off and periodic work, carried out safely and on schedule.',
            'items' => ['Window cleaning, high and low level', 'Construction cleans', 'Carpet and upholstery cleaning', 'Polishing and sealing synthetic floors', 'Concrete and cobblestone cleaning, gum removal', 'Power washing and steam cleaning', 'Holiday let clean-up', 'Painting', 'Crime scene clean-up'],
        ],
        'washroom' => [
            'title' => 'Washroom and sanitary services',
            'tagline' => 'Optimum cleaning',
            'icon' => 'droplet',
            'photo' => 'aps-dispenser',
            'summary' => 'Washroom deep cleans with specialist chemicals that remove the dirt and grime everyday cleaning leaves behind, and the units that keep a washroom fresh between visits.',
            'items' => ['Washroom deep cleans', 'Feminine hygiene disposal units', 'Air care and ozone units', 'Water filtration and sanitiser systems', 'Toilet seat sanitisers', 'Hand drying systems'],
        ],
        'consumables' => [
            'title' => 'Consumables',
            'tagline' => 'Quality products',
            'icon' => 'box',
            'photo' => 'wet-floor',
            'summary' => 'Paper, liquid and janitorial products when you need them, or as part of your service contract, from established suppliers.',
            'items' => ['Paper products', 'Liquid products', 'Plastic products', 'Kitchen products', 'Laundry room products', 'Dispensers', 'Bin bags'],
        ],
        'landscaping' => [
            'title' => 'Landscaping and grounds maintenance',
            'tagline' => 'Presentation is everything',
            'icon' => 'leaf',
            'photo' => 'hedge',
            'summary' => 'First impressions count, and the first impression is often the outside of a building. Our landscaping and maintenance team keeps grounds to a high standard.',
            'items' => ['Lawn maintenance and care', 'Weed management', 'Litter collection and removal', 'Planting and shrubbery', 'Paving and decking', 'Walling and fencing', 'Decorative displays', 'Indoor plant supply and maintenance'],
        ],
        'pest' => [
            'title' => 'Pest control',
            'tagline' => 'Problem solved',
            'icon' => 'bug',
            'photo' => 'pressure-wash',
            'summary' => 'Pests on a premises are embarrassing and can cost you customers. Pest control is part of our facilities management service.',
            'items' => [],
        ],
    ],

    // The sectors listed on allpro.ie/cleaning.
    'sectors' => [
        'corporate' => 'Corporate',
        'education' => 'Education',
        'retail' => 'Retail',
        'medical' => 'Medical',
        'hospitality' => 'Hotels and hospitality',
        'state' => 'State bodies',
        'industrial' => 'Industrial',
        'construction' => 'Construction',
    ],

    'counties' => ['Carlow', 'Cavan', 'Clare', 'Cork', 'Donegal', 'Dublin', 'Galway', 'Kerry', 'Kildare', 'Kilkenny', 'Laois', 'Leitrim', 'Limerick', 'Longford', 'Louth', 'Mayo', 'Meath', 'Monaghan', 'Offaly', 'Roscommon', 'Sligo', 'Tipperary', 'Waterford', 'Westmeath', 'Wexford', 'Wicklow'],

    'careers' => [
        'points' => [
            ['title' => 'Apply by phone number', 'text' => 'Cleaning and security roles need no CV. Tell us where you live, when you can work and what you have done before.'],
            ['title' => 'Shifts that suit you', 'text' => 'Early, late and night cover across four regions. Tick the shifts you can do and we match you to sites nearby.'],
            ['title' => 'Vetting and licences handled properly', 'text' => 'Security roles need a current PSA licence. If we offer you a role, HR sends the Garda vetting invitation; never upload vetting documents here.'],
        ],
    ],

    /*
    | Photos: public/img/{key}-{480,960,1920}.webp. Credits in config/photo-credits.php.
    */
    'photos' => [
        'scrubber' => ['w' => 1920, 'h' => 1278],
        'wet-floor' => ['w' => 1920, 'h' => 1081],
        'guard-dusk' => ['w' => 1920, 'h' => 1316],
        'guard-gate' => ['w' => 1920, 'h' => 1280],
        'cctv' => ['w' => 1920, 'h' => 1440],
        'window-abseil' => ['w' => 1920, 'h' => 1280],
        'hedge' => ['w' => 1920, 'h' => 1440],
        'pressure-wash' => ['w' => 1920, 'h' => 1280],
        'business-park' => ['w' => 1920, 'h' => 1437],
        'aps-dispenser' => ['w' => 1920, 'h' => 1508],
        'aps-corridor' => ['w' => 1920, 'h' => 1281],
        'oranmore' => ['w' => 1920, 'h' => 1440],
        'galway' => ['w' => 1920, 'h' => 1440],
        'limerick' => ['w' => 1920, 'h' => 1440],
        'cork' => ['w' => 1920, 'h' => 879],
        'dublin' => ['w' => 1920, 'h' => 1280],
        'scrubber-school' => ['w' => 1920, 'h' => 1440],
    ],
    // Photographs taken from allpro.ie (2015-16), used first because they are the company's own
    // imagery. Rights to confirm before launch (README). Every other photo is illustrative.
    'own_photos' => ['wet-floor', 'aps-dispenser', 'aps-corridor'],
    'og_photo' => 'scrubber',

    // Pages on the old WordPress site that directories and bookmarks still link to.
    'legacy_redirects' => [
        '/facilities-management' => '/services#facilities',
        '/cleaning' => '/services#cleaning',
        '/security-services' => '/services#security',
        '/industrial-maintenance' => '/services#industrial',
        '/sanitary-services' => '/services#washroom',
        '/consumables' => '/services#consumables',
        '/landscaping-ground-maintenance' => '/services#landscaping',
        '/blog' => '/',
    ],
];
