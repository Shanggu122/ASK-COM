@props([
    'guard' => 'web', // 'web' | 'professor' | 'admin'
    'label' => null,
    'method' => 'GET', // could adapt later for POST logout endpoints
    'class' => 'text-red-600 hover:underline',
    'icon' => null,
    'iconClass' => 'nav-icon',
    'textClass' => null,
])
@php
    $routeMap = [
        'web' => ['url' => route('logout', [], false), 'method' => 'GET'],
        'professor' => ['url' => route('logout-professor', [], false), 'method' => 'GET'],
    'admin' => ['url' => route('logout.admin', [], false), 'method' => 'POST'],
    ];
    $cfg = $routeMap[$guard] ?? $routeMap['web'];
    $text = $label ?? __('Logout');
    $iconClass = $icon ? trim($iconClass ?? '') : null;
    $textClass = trim($textClass ?? '');
    $baseAttributes = $attributes->except('class');
    $resolvedClass = trim($attributes->get('class') ?? $class ?? '');
@endphp
@if($cfg['method'] === 'GET')
    <a href="{{ $cfg['url'] }}" {{ $baseAttributes->merge(['class' => $resolvedClass]) }} data-logout-guard="{{ $guard }}">
        @if($icon)
            <i class="{{ $icon }}{{ $iconClass ? ' '.$iconClass : '' }}" aria-hidden="true"></i>
        @endif
        <span class="{{ $textClass }}">{{ $text }}</span>
    </a>
@else
    <form action="{{ $cfg['url'] }}" method="POST" style="display:inline" data-logout-guard="{{ $guard }}">
        @csrf
        <button type="submit" {{ $baseAttributes->merge(['class' => $resolvedClass ?: 'inline']) }}>
            @if($icon)
                <i class="{{ $icon }}{{ $iconClass ? ' '.$iconClass : '' }}" aria-hidden="true"></i>
            @endif
            <span class="{{ $textClass }}">{{ $text }}</span>
        </button>
    </form>
@endif
