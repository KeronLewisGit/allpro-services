@props(['tone' => 'dark', 'small' => false])
{{--
    Redrawn as vector from the company's own mark on allpro.ie (2016): a tilted teal-blue
    pebble with "APS" reversed out in white, beside the "Allpro services" wordmark. A stand-in
    until Allpro supplies the original artwork (README, "Before launch").
--}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <svg viewBox="0 0 120 66" @class(['flex-none', 'h-9 w-[3.3rem]' => $small, 'h-11 w-[4.1rem]' => ! $small]) aria-hidden="true">
        <path d="M6 46C2 36 30 14 70 5c25-5 42-5 44 9 3 20-16 42-48 47-30 5-56-3-60-15z" fill="#00799e" @if($tone === 'light') stroke="#ffffff" stroke-opacity=".55" stroke-width="2" @endif/>
        <text x="60" y="45" text-anchor="middle" transform="rotate(-9 60 38)" font-family="'Encode Sans Semi Condensed', ui-sans-serif, sans-serif" font-weight="800" font-size="27" letter-spacing=".5" fill="#ffffff">APS</text>
    </svg>
    <span class="leading-none">
        <span @class(['block font-display font-extrabold tracking-tight', 'text-[1.2rem]' => $small, 'text-[1.45rem]' => ! $small, 'text-white' => $tone === 'light', 'text-primary' => $tone !== 'light'])>Allpro</span>
        <span @class(['block font-sans font-medium tracking-[0.02em]', 'text-[0.85rem]' => $small, 'text-[0.95rem]' => ! $small, 'text-white/80' => $tone === 'light', 'text-ink-2' => $tone !== 'light'])>services</span>
    </span>
</span>
