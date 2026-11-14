<aside class="admin-sidebar" id="admin-sidebar">
    <div class="sidebar-inner">
        {{-- Brand header seperti panel referensi --}}
        <div class="sidebar-brand">
            <a href="{{ route('dashboard') }}" class="sidebar-brand-link">
                <img src="{{ brand_logo_url() }}" alt="{{ brand_name() }}" class="sidebar-brand-logo">
                <div class="sidebar-brand-info">
                    <span class="sidebar-brand-text">{{ brand_name() }}</span>
                    <span class="sidebar-brand-sub">{{ brand_tagline() }}</span>
                </div>
            </a>
            <button type="button" class="btn-sidebar-close d-lg-none" id="btn-sidebar-close" aria-label="Tutup menu">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <ul class="sidebar-menu list-unstyled mb-0">
                @foreach (config('sidebar.menus') as $group)
                    @foreach ($group['items'] as $item)
                        @if (! empty($item['submenus']))
                            @php
                                $visibleSubmenus = collect($item['submenus'])->filter(function ($sub) {
                                    if (! empty($sub['submenus'])) {
                                        return collect($sub['submenus'])->some(
                                            fn ($c) => user_can_menu($c['permission'] ?? null)
                                        );
                                    }
                                    return user_can_menu($sub['permission'] ?? null);
                                });
                                $submenuId = 'submenu-' . str()->slug($item['label']);
                                $isOpen = is_active_submenu($item['submenus']) === ' active';
                            @endphp
                            @if ($visibleSubmenus->isNotEmpty())
                                <li class="sidebar-menu-item has-sub">
                                    <a class="sidebar-menu-link sidebar-parent {{ $isOpen ? 'is-active' : '' }}"
                                        data-bs-toggle="collapse" href="#{{ $submenuId }}"
                                        role="button" aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                                        title="{{ $item['label'] }}">
                                        <i class="bi {{ $item['icon'] }} sidebar-menu-icon"></i>
                                        <span class="sidebar-menu-label">{{ $item['label'] }}</span>
                                        <i class="bi bi-chevron-down sidebar-chevron"></i>
                                    </a>
                                    <div class="collapse {{ $isOpen ? 'show' : '' }}" id="{{ $submenuId }}"
                                        data-bs-parent=".sidebar-menu">
                                        <div class="sidebar-flyout-head">{{ $item['label'] }}</div>
                                        <ul class="sidebar-submenu list-unstyled">
                                            @foreach ($visibleSubmenus as $submenu)
                                                @if (! empty($submenu['submenus']))
                                                    @php
                                                        $visibleNested = collect($submenu['submenus'])->filter(
                                                            fn ($child) => user_can_menu($child['permission'] ?? null)
                                                        );
                                                        $nestedId = 'nested-' . str()->slug($item['label'] . '-' . $submenu['label']);
                                                        $isNestedOpen = is_active_submenu($submenu['submenus']) === ' active';
                                                    @endphp
                                                    @if ($visibleNested->isNotEmpty())
                                                        <li class="sidebar-subgroup-item">
                                                            <a class="sidebar-subgroup-toggle {{ $isNestedOpen ? 'is-active' : '' }}"
                                                                data-bs-toggle="collapse" href="#{{ $nestedId }}"
                                                                role="button" aria-expanded="{{ $isNestedOpen ? 'true' : 'false' }}">
                                                                <span class="sidebar-subgroup-label">{{ $submenu['label'] }}</span>
                                                                <i class="bi bi-chevron-down sidebar-chevron"></i>
                                                            </a>
                                                            <div class="collapse {{ $isNestedOpen ? 'show' : '' }}" id="{{ $nestedId }}">
                                                                <ul class="sidebar-nested-list list-unstyled">
                                                                    @foreach ($visibleNested as $nested)
                                                                        <li>
                                                                            <a href="{{ route($nested['route']) }}"
                                                                                class="sidebar-nested-link{{ is_active_menu($nested['route']) }}">
                                                                                {{ $nested['label'] }}
                                                                            </a>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                        </li>
                                                    @endif
                                                @else
                                                    <li>
                                                        <a href="{{ route($submenu['route']) }}"
                                                            class="sidebar-submenu-link{{ is_active_menu($submenu['route']) }}">
                                                            {{ $submenu['label'] }}
                                                        </a>
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    </div>
                                </li>
                            @endif
                        @elseif (user_can_menu($item['permission'] ?? null))
                            <li class="sidebar-menu-item">
                                <a href="{{ route($item['route']) }}"
                                    class="sidebar-menu-link{{ is_active_menu($item['route']) }}"
                                    title="{{ $item['label'] }}">
                                    <i class="bi {{ $item['icon'] }} sidebar-menu-icon"></i>
                                    <span class="sidebar-menu-label">{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endif
                    @endforeach
                @endforeach
            </ul>
        </nav>
    </div>
</aside>
