<?php

namespace App\Http\Controllers;

use App\Models\JobPosting;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home', ['openJobs' => JobPosting::open()->count()]);
    }

    public function services(): View
    {
        return view('pages.services');
    }

    public function about(): View
    {
        return view('pages.about');
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    /** For existing clients: how the private service-desk link works. */
    public function clients(): View
    {
        return view('pages.clients');
    }

    public function privacy(): View
    {
        return view('pages.privacy');
    }

    public function credits(): View
    {
        return view('pages.credits');
    }

    public function sitemapPage(): View
    {
        return view('pages.sitemap', ['jobs' => JobPosting::open()->orderBy('title')->get(['slug', 'title'])]);
    }

    public function sitemap(): Response
    {
        $urls = collect(['home', 'services', 'about', 'contact', 'quote.create', 'clients', 'careers.index', 'careers.apply', 'privacy', 'credits'])
            ->map(fn (string $r) => ['loc' => route($r), 'lastmod' => null])
            ->concat(JobPosting::open()->get(['slug', 'updated_at'])->map(fn (JobPosting $j) => ['loc' => route('careers.show', $j), 'lastmod' => $j->updated_at->toAtomString()]));

        return response()->view('sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
    }
}
