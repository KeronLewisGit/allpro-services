@props(['steps' => [], 'title' => 'Your progress'])
{{--
    Step guide for a long form (inside x-data="formSteps"). Links jump to each section; with
    JavaScript the current section is marked and finished ones are ticked, with a progress bar.
    Without JavaScript it is a plain list of links.
--}}
<nav {{ $attributes->merge(['class' => 'panel p-5']) }} aria-label="Form sections">
    <p class="kicker text-ink-3">{{ $title }}</p>
    <div class="mt-3 hidden [.js_&]:block">
        <div class="steps-bar" role="progressbar" aria-label="Sections complete" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="Math.round(pct * 100)"><span :style="`--p: ${pct}`"></span></div>
        <p class="mt-2 text-sm text-ink-2" aria-live="polite"><span x-text="Object.values(done).filter(v => v === true).length">0</span> of <span x-text="Object.values(done).filter(v => v !== null).length">{{ count($steps) }}</span> sections complete</p>
    </div>
    <ol class="steps mt-3">
        @foreach ($steps as $n => $label)
            <li x-show="done['{{ $n }}'] !== null">
                <a href="#step-{{ $n }}" :aria-current="current === '{{ $n }}' ? 'step' : null" :class="done['{{ $n }}'] === true && 'is-done'">
                    <span class="dot">{{ $n }}<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5" /></svg></span>
                    {{ $label }}<span class="sr-only" x-text="done['{{ $n }}'] === true ? ' (complete)' : ''"></span>
                </a>
            </li>
        @endforeach
    </ol>
</nav>
