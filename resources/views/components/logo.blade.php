@props(['tone' => 'dark', 'small' => false, 'draw' => false])
{{--
    Vector redraw of the logo on allpro.ie (wp-content/uploads/2016/04/APS-blue.png and the white
    APS.png): the #00799e "APS" pebble, rounded and rising to the right, with upright reversed
    lettering, beside the "Allpro / services" wordmark in a Helvetica-style grotesque. A stand-in
    until Allpro supplies the original artwork (README, "Before launch"). draw = the pebble
    traces its outline on first paint (motion allowed only).
--}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
    <svg viewBox="0 0 100 62" @class(['flex-none', 'mark-draw' => $draw, 'h-9 w-[3.6rem]' => $small, 'h-11 w-[4.45rem]' => ! $small, 'text-white' => $tone === 'light', 'text-[var(--logo)]' => $tone !== 'light']) aria-hidden="true">
        <path class="pebble" d="M4.5 42.5C1 35 6 28.5 15 25.2L78.5 2.6C89.5-1.3 96.8 3.4 97.6 14.5l1.2 22.6c.6 12.5-6.4 21.4-19.2 22.3L22.5 61.5C12.8 62.2 7.7 49.2 4.5 42.5Z" fill="currentColor" />
        <text class="aps" x="51" y="45" text-anchor="middle" font-family="'Helvetica Neue', Helvetica, Arial, sans-serif" font-weight="700" font-size="25" letter-spacing="-1.4" fill="{{ $tone === 'light' ? '#00799e' : '#ffffff' }}">APS</text>
    </svg>
    <span class="leading-[0.95]" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif">
        <span @class(['block font-bold tracking-[-0.02em]', 'text-[1.3rem]' => $small, 'text-[1.6rem]' => ! $small, 'text-white' => $tone === 'light', 'text-[var(--logo)]' => $tone !== 'light'])>Allpro</span>
        <span @class(['block font-normal tracking-[0.01em]', 'text-[1.02rem]' => $small, 'text-[1.22rem]' => ! $small, 'text-white' => $tone === 'light', 'text-[var(--logo)]' => $tone !== 'light'])>services</span>
    </span>
</span>
