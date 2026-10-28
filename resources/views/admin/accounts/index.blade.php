@php use App\Enums\Region; @endphp
<x-layouts.admin title="Client sites">
    <p class="measure text-ink-2">Each client site has a private service-desk link. Create a link to send to the site contact, and withdraw it at any time.</p>
    <nav class="tag-row mt-4" aria-label="Filter by region">
        <a href="{{ route('admin.accounts.index') }}" class="tag" @if(! $region) aria-current="true" @endif>All regions</a>
        @foreach (Region::cases() as $r)<a href="{{ route('admin.accounts.index', ['region' => $r->value]) }}" class="tag" @if($region === $r) aria-current="true" @endif>{{ $r->label() }}</a>@endforeach
    </nav>
    <div class="mt-5 grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
        <div class="a-card min-w-0">
            <div class="table-box">
                <table class="data-table">
                    <thead><tr><th>Client and site</th><th>Region</th><th>Link</th><th class="r">Open tickets</th></tr></thead>
                    <tbody>
                        @forelse ($accounts as $a)
                            <tr>
                                <td><a href="{{ route('admin.accounts.show', $a) }}" class="font-semibold text-link underline-offset-2 hover:underline">{{ $a->organisation }}</a><p class="text-xs text-ink-3">{{ $a->site_name }}</p></td>
                                <td>{{ $a->region->label() }}</td>
                                <td class="whitespace-nowrap">@if($a->linkIsLive())<x-chip tone="good" icon="link">Until {{ $a->link_expires_at?->format('j M Y') }}</x-chip>@else<x-chip tone="muted" icon="x">No live link</x-chip>@endif</td>
                                <td class="r num">{{ $a->open_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-10 text-center text-ink-3">No client sites yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.accounts.store') }}" class="a-card h-fit space-y-4 p-5">
            @csrf
            <h2 class="font-semibold">Add a client site</h2>
            @include('admin.accounts.fields', ['account' => new \App\Models\ClientAccount(['region' => Region::Galway, 'services' => []])])
            <button class="a-btn a-btn-primary w-full">Add client site</button>
        </form>
    </div>
</x-layouts.admin>
