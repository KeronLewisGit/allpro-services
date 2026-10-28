@php
    $val = fn (string $k) => input_string(old($k, $account->getAttribute($k) instanceof \BackedEnum ? $account->getAttribute($k)->value : $account->getAttribute($k)));
    $svc = (array) old('services', $account->services ?? []);
@endphp
<div><label for="c-org" class="mb-1 block text-sm font-semibold">Client organisation</label><input id="c-org" name="organisation" value="{{ $val('organisation') }}" class="a-input w-full" required maxlength="160"></div>
<div><label for="c-site" class="mb-1 block text-sm font-semibold">Site name</label><input id="c-site" name="site_name" value="{{ $val('site_name') }}" class="a-input w-full" required maxlength="160"></div>
<div><label for="c-region" class="mb-1 block text-sm font-semibold">Region</label><select id="c-region" name="region" class="a-input w-full">@foreach (\App\Enums\Region::cases() as $r)<option value="{{ $r->value }}" @selected($val('region') === $r->value)>{{ $r->label() }}</option>@endforeach</select></div>
<div><label for="c-addr" class="mb-1 block text-sm font-semibold">Address</label><input id="c-addr" name="address" value="{{ $val('address') }}" class="a-input w-full" maxlength="255"></div>
<div><label for="c-cn" class="mb-1 block text-sm font-semibold">Site contact</label><input id="c-cn" name="contact_name" value="{{ $val('contact_name') }}" class="a-input w-full" maxlength="120"></div>
<div class="grid gap-3 sm:grid-cols-2">
    <div><label for="c-ce" class="mb-1 block text-sm font-semibold">Contact email</label><input id="c-ce" type="email" name="contact_email" value="{{ $val('contact_email') }}" class="a-input w-full" maxlength="255"></div>
    <div><label for="c-cp" class="mb-1 block text-sm font-semibold">Contact phone</label><input id="c-cp" type="tel" name="contact_phone" value="{{ $val('contact_phone') }}" class="a-input w-full" maxlength="30"></div>
</div>
<fieldset><legend class="mb-1 text-sm font-semibold">Services on contract</legend>
    <div class="grid gap-1 sm:grid-cols-2">@foreach ((array) allpro('services') as $k => $s)<label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" name="services[]" value="{{ $k }}" class="h-4 w-4 accent-[var(--primary)]" @checked(in_array($k, $svc, true))>{{ $s['title'] }}</label>@endforeach</div>
</fieldset>
