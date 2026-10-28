{{-- PSA licence state chip: n/a | missing | expired | expiring | valid --}}
@switch($state)
    @case('valid')<x-chip tone="good" icon="shield">Valid</x-chip>@break
    @case('expiring')<x-chip tone="attention" icon="clock">Expiring</x-chip>@break
    @case('expired')<x-chip tone="critical" icon="alert">Expired</x-chip>@break
    @case('missing')<x-chip tone="critical" icon="x">None given</x-chip>@break
    @default<span class="text-ink-3">n/a</span>
@endswitch
