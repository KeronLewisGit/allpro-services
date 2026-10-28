@php
    use App\Enums\Region;
    use App\Enums\RoleType;
    use App\Enums\VettingStatus;
    $editing = $staff->exists;
    $v = fn (string $k) => input_string(old($k, $staff->getAttribute($k) instanceof \Carbon\CarbonInterface ? $staff->getAttribute($k)->format('Y-m-d') : ($staff->getAttribute($k) instanceof \BackedEnum ? $staff->getAttribute($k)->value : $staff->getAttribute($k))));
@endphp
<x-layouts.admin :title="$editing ? 'Edit '.$staff->full_name : 'Add staff member'">
    <x-slot:actions><a href="{{ $editing ? route('admin.staff.show', $staff) : route('admin.staff.index') }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="h-4 w-4" />Back</a></x-slot:actions>
    <form method="POST" action="{{ $editing ? route('admin.staff.update', $staff) : route('admin.staff.store') }}" class="a-card max-w-3xl space-y-5 p-5 md:p-6">
        @csrf @if ($editing) @method('PUT') @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label for="s-no" class="mb-1 block font-semibold">Employee no.</label><input id="s-no" name="employee_no" value="{{ $v('employee_no') }}" class="a-input w-full" required maxlength="20"></div>
            <div><label for="s-start" class="mb-1 block font-semibold">Start date</label><input id="s-start" type="date" name="started_on" value="{{ $v('started_on') }}" class="a-input w-full"></div>
            <div><label for="s-first" class="mb-1 block font-semibold">First name</label><input id="s-first" name="first_name" value="{{ $v('first_name') }}" class="a-input w-full" required maxlength="80"></div>
            <div><label for="s-last" class="mb-1 block font-semibold">Last name</label><input id="s-last" name="last_name" value="{{ $v('last_name') }}" class="a-input w-full" required maxlength="80"></div>
            <div><label for="s-role" class="mb-1 block font-semibold">Role</label><select id="s-role" name="role_type" class="a-input w-full">@foreach (RoleType::cases() as $c)<option value="{{ $c->value }}" @selected($v('role_type') === $c->value)>{{ $c->label() }}</option>@endforeach</select></div>
            <div><label for="s-region" class="mb-1 block font-semibold">Region</label><select id="s-region" name="region" class="a-input w-full">@foreach (Region::cases() as $c)<option value="{{ $c->value }}" @selected($v('region') === $c->value)>{{ $c->label() }}</option>@endforeach</select></div>
            <div><label for="s-phone" class="mb-1 block font-semibold">Phone</label><input id="s-phone" name="phone" type="tel" value="{{ $v('phone') }}" class="a-input w-full" maxlength="30"></div>
            <div><label for="s-email" class="mb-1 block font-semibold">Email</label><input id="s-email" name="email" type="email" value="{{ $v('email') }}" class="a-input w-full" maxlength="255"></div>
        </div>
        <fieldset class="grid gap-4 border-t border-line pt-5 sm:grid-cols-2">
            <legend class="mb-2 font-semibold">PSA licence</legend>
            <div><label for="s-psa" class="mb-1 block text-sm text-ink-2">Licence number</label><input id="s-psa" name="psa_licence_number" value="{{ $v('psa_licence_number') }}" class="a-input w-full" inputmode="numeric" maxlength="12"></div>
            <div><label for="s-exp" class="mb-1 block text-sm text-ink-2">Expiry date</label><input id="s-exp" type="date" name="psa_licence_expires_on" value="{{ $v('psa_licence_expires_on') }}" class="a-input w-full"><p class="mt-1 text-xs text-ink-3">Changing the date restarts the reminders.</p></div>
        </fieldset>
        <fieldset class="grid gap-4 border-t border-line pt-5 sm:grid-cols-3">
            <legend class="mb-2 font-semibold">Checks</legend>
            <div><label for="s-vet" class="mb-1 block text-sm text-ink-2">Garda vetting</label><select id="s-vet" name="vetting_status" class="a-input w-full">@foreach (VettingStatus::cases() as $c)<option value="{{ $c->value }}" @selected($v('vetting_status') === $c->value)>{{ $c->label() }}</option>@endforeach</select></div>
            <div><label for="s-vetd" class="mb-1 block text-sm text-ink-2">Disclosure date</label><input id="s-vetd" type="date" name="vetting_cleared_on" value="{{ $v('vetting_cleared_on') }}" class="a-input w-full"></div>
            <div><label for="s-rtw" class="mb-1 block text-sm text-ink-2">Right to work checked</label><input id="s-rtw" type="date" name="rtw_checked_on" value="{{ $v('rtw_checked_on') }}" class="a-input w-full"></div>
        </fieldset>
        <label class="flex min-h-11 items-center gap-2"><input type="checkbox" name="active" value="1" class="h-5 w-5 accent-[var(--primary)]" @checked(old('active', $staff->active ?? true))>Currently employed</label>
        <button class="a-btn a-btn-primary">{{ $editing ? 'Save changes' : 'Add to the register' }}</button>
    </form>
</x-layouts.admin>
