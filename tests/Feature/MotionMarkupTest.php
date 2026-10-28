<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * The motion layer is progressive: every effect decorates content that is already in the HTML.
 * These tests check the server-rendered markup, i.e. what a visitor without JavaScript gets.
 */
class MotionMarkupTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_headline_and_figures_are_complete_without_javascript(): void
    {
        $res = $this->get('/')->assertOk();
        // The headline is split into words for the entrance, but every word is in the page.
        $res->assertSeeInOrder(['class="w"', 'Facility', 'management,', 'from', 'the', 'inside', 'to', 'the', 'outside.'], false);
        // Count-up figures render their final value; JS only animates towards it.
        $res->assertSee('data-count="'.config('allpro.founded').'"', false)
            ->assertSee('data-count="4"', false)
            ->assertSee('data-count="'.count((array) config('allpro.services')).'"', false);
        $res->assertSee('class="scroll-progress"', false)->assertSee('mark-draw', false);
    }

    public function test_every_service_panel_is_in_the_switcher_html(): void
    {
        $res = $this->get('/')->assertSee('x-data="svcSwitch(', false)->assertSee('role="tablist"', false);
        foreach ((array) config('allpro.services') as $key => $s) {
            $res->assertSee('id="panel-'.$key.'"', false)->assertSee('id="tab-'.$key.'"', false)->assertSee(e($s['summary']), false);
        }
        // Only the first tab starts selected; panels are hidden only once the switcher script has run.
        $this->assertSame(1, substr_count($res->getContent(), 'aria-selected="true"'));
        $css = (string) file_get_contents(resource_path('css/app.css'));
        $this->assertStringContainsString(".js .svc-switch.is-ready [role='tabpanel']:not(.is-active)", $css);
    }

    public function test_the_marquee_duplicates_are_hidden_from_assistive_tech_and_keyboard(): void
    {
        $html = $this->get('/')->getContent();
        $services = count((array) config('allpro.services'));
        $this->assertSame($services, preg_match_all('/<li data-svc="[a-z]+"\s+aria-hidden="true"\s*><a [^>]*tabindex="-1"/', $html));
        $this->assertSame($services * 2, preg_match_all('/<li data-svc="[a-z]+"[^>]*><a [^>]*class="tagline/', $html));
        $this->assertStringContainsString('class="marquee"', $html);
    }

    public function test_map_shows_all_four_offices_and_their_counties(): void
    {
        $res = $this->get('/')->assertSee('class="ie-map"', false)->assertSee('id="ie-map-title"', false);
        foreach ((array) config('allpro.regions') as $key => $r) {
            $res->assertSee('data-region="'.$key.'"', false);
            foreach ($r['counties'] as $county) {
                $res->assertSee($county);
            }
        }
        $res->assertSee('Oranmore HQ')->assertSee('Areas are indicative');
        $this->get('/photo-credits')->assertSee('Ireland trad counties blank.svg')->assertSee('CC BY-SA 3.0');
    }

    public function test_long_forms_have_a_step_guide_that_links_to_real_sections(): void
    {
        foreach (['/quote' => 4, '/careers/apply' => 4] as $url => $steps) {
            $res = $this->get($url)->assertSee('x-data="formSteps"', false)->assertSee('class="steps', false);
            for ($n = 1; $n <= $steps; $n++) {
                $res->assertSee('href="#step-'.$n.'"', false)->assertSee('id="step-'.$n.'"', false);
            }
        }
    }

    public function test_sla_clocks_show_each_priority_window_in_the_html(): void
    {
        $res = $this->get('/client-services')->assertSee('class="sla-ring"', false);
        foreach (TicketPriority::cases() as $p) {
            $res->assertSee('slaClock('.$p->responseHours().')', false)->assertSee(sprintf('%02d:00:00', $p->responseHours()));
        }
    }

    public function test_thanks_page_has_the_sent_check(): void
    {
        $this->withSession(['last_submission' => ['reference' => 'QR-26-00001', 'kind' => 'Quote request']])
            ->get('/thanks')->assertOk()->assertSee('class="sent-check', false)->assertSee('QR-26-00001');
    }

    public function test_every_animation_is_gated_on_reduced_motion(): void
    {
        $css = (string) file_get_contents(resource_path('css/app.css'));
        // Keyframe animations only run inside @media (prefers-reduced-motion: no-preference) blocks.
        $outside = '';
        $offset = 0;
        $open = '@media (prefers-reduced-motion: no-preference)';
        while (($start = strpos($css, $open, $offset)) !== false) {
            $outside .= substr($css, $offset, $start - $offset);
            $i = (int) strpos($css, '{', $start);
            for ($depth = 0; $i < strlen($css); $i++) {
                $depth += $css[$i] === '{' ? 1 : ($css[$i] === '}' ? -1 : 0);
                if ($depth === 0) {
                    break;
                }
            }
            $offset = $i + 1;
        }
        $outside .= substr($css, $offset);
        $this->assertDoesNotMatchRegularExpression('/animation:\s*(?!none)[a-z]/', $outside);
        $this->assertStringContainsString('.marquee:hover .marquee-track', $css);
        $this->assertStringContainsString('.marquee:focus-within .marquee-track', $css);
    }
}
