@props(['name' => 'availability', 'selected' => null, 'readonly' => false])
{{-- Seven days by three shifts. As a form field it posts availability[] = "mon-early"; read-only it shows ticks. --}}
@php
    use App\Support\Availability;
    $chosen = $selected ?? array_filter((array) old($name, []), is_scalar(...));
@endphp
<div class="table-box rounded-xl border border-line">
    <table class="w-full min-w-[34rem] border-collapse text-center text-[0.9375rem]">
        <caption class="sr-only">Shifts {{ $readonly ? 'available' : 'you can work' }}</caption>
        <thead>
            <tr class="bg-surface-2">
                <th scope="col" class="px-3 py-2 text-left font-semibold">Shift</th>
                @foreach (Availability::DAYS as $d => $label)<th scope="col" class="px-1 py-2 font-semibold">{{ $label }}</th>@endforeach
            </tr>
        </thead>
        <tbody>
            @foreach (Availability::SHIFTS as $s => [$label, $hours])
                <tr class="border-t border-line">
                    <th scope="row" class="px-3 py-1.5 text-left font-normal"><span class="block font-semibold">{{ $label }}</span><span class="block text-[0.8125rem] text-ink-3">{{ $hours }}</span></th>
                    @foreach (Availability::DAYS as $d => $dayLabel)
                        @php($key = $d.'-'.$s)
                        <td class="p-0.5">
                            @if ($readonly)
                                @if (in_array($key, $chosen, true))<span class="mx-auto grid h-9 w-9 place-items-center rounded-lg bg-primary text-primary-ink"><x-icon name="check" class="h-4 w-4" /><span class="sr-only">{{ $dayLabel }} {{ $label }}: yes</span></span>@else<span class="mx-auto block h-9 w-9 rounded-lg bg-surface-2"><span class="sr-only">{{ $dayLabel }} {{ $label }}: no</span></span>@endif
                            @else
                                <label class="mx-auto grid h-12 w-12 cursor-pointer place-items-center rounded-lg hover:bg-surface-2 has-[:checked]:bg-primary-soft">
                                    <input type="checkbox" name="{{ $name }}[]" value="{{ $key }}" class="h-5 w-5 accent-[var(--primary)]" @checked(in_array($key, array_map('strval', $chosen), true))>
                                    <span class="sr-only">{{ $dayLabel }}, {{ $label }} shift</span>
                                </label>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
