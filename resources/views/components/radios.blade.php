@props(['name', 'label', 'options', 'value' => null, 'cols' => 'sm:grid-cols-2', 'required' => false, 'model' => null])
@php($current = input_string(old($name, $value)))
<fieldset>
    <legend class="label">{{ $label }}</legend>
    <div class="grid gap-2 {{ $cols }}">
        @foreach ($options as $v => $t)
            <label class="choice"><input type="radio" name="{{ $name }}" value="{{ $v }}" @checked($current === (string) $v) @required($required) @if($model) x-model="{{ $model }}" @endif><span>{{ $t }}</span></label>
        @endforeach
    </div>
    <x-error :for="$name" />
</fieldset>
