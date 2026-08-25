@props([
    'codeAttributes' => null,
    'language',
])

@php($codeAttributes ??= new \Illuminate\View\ComponentAttributeBag)

<pre {{ $attributes->class('ndb-code ndb-scrollbar') }}>@isset($value)<code data-ndb-language="{{ $language }}" {{ $value->attributes }}>{{ $value }}</code>@else<code data-ndb-language="{{ $language }}" {{ $codeAttributes }}>{{ $slot }}</code>@endisset</pre>
