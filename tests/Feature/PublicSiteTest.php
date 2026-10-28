<?php

namespace Tests\Feature;

use App\Models\JobPosting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function pages(): array
    {
        return [
            'home' => ['/'], 'services' => ['/services'], 'about' => ['/about'], 'contact' => ['/contact'],
            'quote' => ['/quote'], 'clients' => ['/client-services'], 'careers' => ['/careers'], 'apply' => ['/careers/apply'],
            'privacy' => ['/privacy'], 'credits' => ['/photo-credits'], 'sitemap' => ['/sitemap'], 'login' => ['/login'],
        ];
    }

    #[DataProvider('pages')]
    public function test_public_pages_render(string $url): void
    {
        $this->get($url)->assertOk()->assertSee('Allpro', false);
    }

    public function test_every_inner_page_opens_with_the_shared_header(): void
    {
        $job = JobPosting::factory()->create();
        foreach ([...array_column(self::pages(), 0), '/careers/'.$job->slug] as $url) {
            if (in_array($url, ['/', '/login'], true)) {
                continue;
            }
            $this->get($url)->assertSee('class="hivis"', false)->assertSee('class="sitecard', false)->assertSee('class="lanyard', false);
        }
        // The home hero is a variant of the same band.
        $this->get('/')->assertSee('class="hivis"', false)->assertSee('class="sitecard', false);
    }

    public function test_the_real_contact_details_are_on_every_page(): void
    {
        foreach (['/', '/contact', '/quote'] as $url) {
            $this->get($url)->assertSee('+353 91 381 111')->assertSee('helpdesk@allpro.ie')->assertSee('tel:+35391381111', false);
        }
        $this->get('/')->assertSee('Registered in Ireland no. 433727');
    }

    public function test_sensitive_subjects_and_people_never_appear_on_public_pages(): void
    {
        $job = JobPosting::factory()->create();
        $banned = ['IPAS', 'international protection', 'direct provision', 'asylum', 'refugee', 'accommodation centre',
            'Alan Connolly', 'Conor Nolan', 'Connolly', 'Nolan', 'testimonial', 'award-winning', 'ISO 9001'];
        foreach ([...array_column(self::pages(), 0), '/careers/'.$job->slug, '/sitemap.xml'] as $url) {
            $html = (string) $this->get($url)->getContent();
            foreach ($banned as $word) {
                $this->assertStringNotContainsStringIgnoringCase($word, strip_tags($html), "{$url} mentions {$word}");
            }
        }
    }

    public function test_every_photo_is_credited_and_exists(): void
    {
        $credits = config('photo-credits');
        foreach (array_keys((array) config('allpro.photos')) as $key) {
            $this->assertArrayHasKey($key, $credits, "{$key} has no credit");
            foreach ([480, 960, 1920] as $w) {
                $this->assertFileExists(public_path("img/{$key}-{$w}.webp"));
            }
        }
        foreach ((array) config('allpro.services') as $s) {
            $this->assertArrayHasKey($s['photo'], (array) config('allpro.photos'));
        }
        $this->get('/photo-credits')->assertSee('Wikimedia Commons')->assertSee('allpro.ie (2016)');
    }

    public function test_stock_photos_are_labelled_and_the_company_photo_is_not(): void
    {
        foreach (array_column(self::pages(), 0) as $url) {
            if ($url === '/login') {
                continue;
            }
            $this->get($url)->assertSee('/img/', false);
        }
        $this->get('/services')->assertSee('Illustrative photo');
        // The about page header uses a stock photo; the wet-floor sign is Allpro's own and says so.
        $this->get('/about')->assertSee('Allpro Services photograph.');
    }

    public function test_submit_buttons_read_correctly_without_javascript(): void
    {
        $html = (string) $this->get('/contact')->getContent();
        $this->assertMatchesRegularExpression('/<span hidden[^>]*>Sending…<\/span>/u', $html);
        $this->assertStringContainsString('method="POST" action="'.route('contact.store').'"', $html);
    }

    public function test_old_wordpress_urls_redirect(): void
    {
        $this->get('/cleaning')->assertRedirect('/services#cleaning')->assertStatus(301);
        $this->get('/security-services')->assertRedirect('/services#security');
        $this->get('/blog')->assertRedirect('/');
    }

    public function test_open_jobs_list_filter_and_closed_jobs_404(): void
    {
        $open = JobPosting::factory()->create(['title' => 'Cleaning operative, Oranmore']);
        $guard = JobPosting::factory()->security()->create(['region' => 'cork']);
        $closed = JobPosting::factory()->create(['title' => 'Old role', 'status' => 'closed']);
        $expired = JobPosting::factory()->create(['title' => 'Expired role', 'closes_on' => today()->subDay()]);
        $this->get('/careers')->assertSee($open->title)->assertSee($guard->title)->assertDontSee('Old role')->assertDontSee('Expired role');
        $this->get('/careers?region=cork')->assertSee($guard->title)->assertDontSee($open->title);
        $this->get('/careers?role=cleaning_operative&region=junk')->assertSee($open->title)->assertDontSee($guard->title);
        $this->get('/careers/'.$closed->slug)->assertNotFound();
        $this->get('/careers/'.$expired->slug)->assertNotFound();
        $this->get('/careers/'.$open->slug)->assertOk()->assertSee('"addressCountry":"IE"', false);
    }

    public function test_sitemap_lists_pages_and_jobs(): void
    {
        $job = JobPosting::factory()->create();
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')
            ->assertSee(route('quote.create'), false)->assertSee(route('careers.show', $job), false);
    }

    public function test_privacy_notice_is_irish(): void
    {
        $this->get('/privacy')->assertSee('Data Protection Act 2018')->assertSee('Data Protection Commission')->assertSee('Garda vetting');
    }

    public function test_quote_page_preselects_a_service_and_ignores_junk(): void
    {
        $this->get('/quote?service=security')->assertOk()->assertSee('value="security" checked', false);
        $this->get('/quote?service=<script>')->assertOk();
    }

    public function test_preview_mode_adds_banner_and_noindex(): void
    {
        config(['allpro.preview' => true]);
        $this->get('/')->assertSee('Concept preview')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        config(['allpro.preview' => false]);
        $this->get('/')->assertDontSee('Concept preview');
    }

    public function test_security_headers(): void
    {
        $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }
}
