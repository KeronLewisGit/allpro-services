<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Models\ClientAccount;
use App\Models\ServiceTicket;
use App\Support\Intake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * The client side of the service desk. Routes carry the `signed` middleware; the key in the
 * URL must also match the account's current access key, so revoking (a new key) kills old links.
 * No login: the link is the credential, so it expires and can be withdrawn.
 */
class PortalController extends Controller
{
    public function show(Request $request, ClientAccount $account, string $key): View
    {
        $this->guard($account, $key);
        if (! $account->last_opened_at || $account->last_opened_at->lt(now()->subMinutes(30))) {
            $account->log('Service desk opened with the client link.');
        }
        $account->forceFill(['last_opened_at' => now()])->save();
        $until = Carbon::createFromTimestamp((int) $request->query('expires'), config('app.timezone'));

        return view('pages.portal', [
            'account' => $account,
            'tickets' => $account->tickets()->latest('id')->with(['notes' => fn ($q) => $q->where('kind', 'client')])->limit(20)->get(),
            // The form posts to a link signed for the same time as the one opened.
            'action' => URL::temporarySignedRoute('portal.store', $until, ['account' => $account->public_id, 'key' => $key]),
            'until' => $until,
        ]);
    }

    public function store(StoreTicketRequest $request, ClientAccount $account, string $key): RedirectResponse
    {
        $this->guard($account, $key);
        if (Intake::isBot($request, ServiceTicket::fakeReference(), 'Service request')) {
            $request->releaseHeldUploads();

            return back();
        }

        /** @var ServiceTicket $ticket */
        $ticket = $account->tickets()->create($request->safe()->except(['photos']));
        foreach ((array) $request->file('photos', []) as $file) {
            if ($file instanceof UploadedFile) {
                $ticket->photos()->create([
                    'path' => $file->store('tickets/'.$ticket->id, 'local'),
                    'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
                    'mime' => $file->getMimeType(),
                    'size' => (int) $file->getSize(),
                ]);
            }
        }
        $request->releaseHeldUploads();
        Intake::complete($ticket->refresh()->load('account'), $request);

        return back()->with('logged', $ticket->reference);
    }

    private function guard(ClientAccount $account, string $key): void
    {
        abort_unless(hash_equals($account->access_key, $key) && $account->linkIsLive(), 403, 'This service-desk link has expired or been withdrawn. Ask your Allpro account manager for a new one, or call the helpdesk.');
    }
}
