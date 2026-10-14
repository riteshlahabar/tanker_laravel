@php
    $children = $item['children'] ?? [];
    $href = \App\Modules\Core\Support\Menu::url($item['route'] ?? null);
    $active = \App\Modules\Core\Support\Menu::isActive($item);
@endphp

<li class="sidebar-list">
    <a class="sidebar-link sidebar-title {{ $children === [] ? 'link-nav' : '' }} {{ $active ? 'active' : '' }}"
        href="{{ $children === [] ? $href : 'javascript:void(0)' }}">
        <svg class="stroke-icon">
            <use href="{{ asset('assets/svg/icon-sprite.svg#stroke-'.$item['icon']) }}"></use>
        </svg>
        <svg class="fill-icon">
            <use href="{{ asset('assets/svg/icon-sprite.svg#fill-'.$item['icon']) }}"></use>
        </svg>
        <span>{{ $item['title'] }}</span>
    </a>

    @if ($children !== [])
        <ul class="sidebar-submenu" @if ($active) style="display: block;" @endif>
            @foreach ($children as $child)
                <li>
                    <a class="{{ \App\Modules\Core\Support\Menu::isActive($child) ? 'active' : '' }}"
                        href="{{ \App\Modules\Core\Support\Menu::url($child['route'] ?? null) }}">
                        {{ $child['title'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</li>
