<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Region;
use App\Http\Controllers\Controller;
use App\Models\ClientAccount;
use App\Notifications\PortalLinkShared;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Client sites on contract, and the signed service-desk link each one's contact uses. */
class ClientAccountController extends Controller
{
    use Concerns;

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ClientAccount::class);
        $region = Region::tryFrom(input_string($request->query('region')));

        return view('admin.accounts.index', [
            'accounts' => ClientAccount::query()->when($region, fn ($q) => $q->where('region', $region))
                ->withCount(['tickets as open_count' => fn ($q) => $q->open()])->orderBy('organisation')->orderBy('site_name')->get(),
            'region' => $region,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', ClientAccount::class);
        $account = ClientAccount::create($this->validated($request) + ['created_by' => $this->user($request)->id]);
        $account->log('Client site added.', 'system', $this->user($request));

        return to_route('admin.accounts.show', $account)->with('status', 'Client site added. Create a link to send to the client contact.');
    }

    public function show(Request $request, ClientAccount $account): View
    {
        Gate::authorize('view', $account);

        return view('admin.accounts.show', [
            'account' => $account->load(['tickets', 'notes.user']),
            // A link made a moment ago is shown in full once, so it can be copied.
            'link' => $request->session()->get('portal_link'),
        ]);
    }

    public function update(Request $request, ClientAccount $account): RedirectResponse
    {
        Gate::authorize('update', $account);
        $account->update($this->validated($request));

        return back()->with('status', 'Client site saved.');
    }

    /** A new expiring link, optionally emailed to the client contact. */
    public function share(Request $request, ClientAccount $account): RedirectResponse
    {
        Gate::authorize('update', $account);
        $data = $request->validate(['days' => ['required', 'integer', Rule::in(ClientAccount::LINK_DAYS)], 'email' => ['nullable', 'boolean']]);
        $user = $this->user($request);
        $url = $account->shareLink((int) $data['days']);
        $account->log("Service-desk link created, valid until {$account->link_expires_at?->format('j M Y')}.", 'system', $user);

        if (! empty($data['email']) && $account->contact_email) {
            Notify::sender($account->contact_email, new PortalLinkShared($account, $url, $user->name));
            $account->log("Link emailed to {$account->contact_email}.", 'system', $user);
        }

        return back()->with('status', 'Link ready.')->with('portal_link', $url);
    }

    public function revoke(Request $request, ClientAccount $account): RedirectResponse
    {
        Gate::authorize('update', $account);
        $account->revoke();
        $account->log('All links withdrawn.', 'system', $this->user($request));

        return back()->with('status', 'Every link sent so far has stopped working.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'organisation' => ['required', 'string', 'max:160'],
            'site_name' => ['required', 'string', 'max:160'],
            'region' => ['required', Rule::enum(Region::class)],
            'address' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_email' => ['nullable', 'email:rfc', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'services' => ['nullable', 'array'],
            'services.*' => ['string', Rule::in(array_keys((array) allpro('services')))],
        ]);

        return $data + ['services' => []];
    }
}
