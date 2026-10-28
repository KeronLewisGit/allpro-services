<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteRequest;
use App\Models\QuoteRequest;
use App\Support\Intake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

/** Quote and site-survey requests from prospective clients. */
class QuoteController extends Controller
{
    public function create(Request $request): View
    {
        $service = input_string($request->query('service'));

        return view('pages.quote', ['preset' => array_key_exists($service, (array) allpro('services')) ? $service : null]);
    }

    public function store(StoreQuoteRequest $request): RedirectResponse
    {
        if (Intake::isBot($request, QuoteRequest::fakeReference(), 'Quote request')) {
            $request->releaseHeldUploads();

            return to_route('thanks');
        }

        $quote = QuoteRequest::create($request->safe()->except(['files', 'consent']) + [
            'consented_at' => now(),
            'source_url' => Intake::source($request),
        ]);
        foreach ((array) $request->file('files', []) as $file) {
            if ($file instanceof UploadedFile) {
                $quote->files()->create([
                    'path' => $file->store('quotes/'.$quote->id, 'local'),
                    'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
                    'mime' => $file->getMimeType(),
                    'size' => (int) $file->getSize(),
                ]);
            }
        }

        $request->releaseHeldUploads();
        $quote->refresh()->load('assignee');
        Intake::complete($quote, $request);
        $quote->log($quote->assignee ? 'Assigned to '.$quote->assignee->name.' ('.$quote->region->label().' sales).' : 'No active sales owner covers '.$quote->region->label().'; assign one.', 'assign');

        return to_route('thanks');
    }
}
