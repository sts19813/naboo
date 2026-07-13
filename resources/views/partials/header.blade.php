@php
    $user = Auth::user();
    $name = trim($user->name);
    $nameParts = collect(preg_split('/\s+/', $name ?: '', -1, PREG_SPLIT_NO_EMPTY));
    $firstName = $nameParts->first() ?: $name;
    $initials = $nameParts
        ->map(fn($word) => mb_substr($word, 0, 1))
        ->join('');
    $isTenant = $user->hasRole('inquilino') || $user->hasRole('tenant');
    $isTechnician = $user->hasRole('tecnico') || $user->hasRole('technician');
    $canManageAccess = $user->can('usuarios.gestionar') || $user->hasRole('administrador') || $user->hasRole('admin');
    $canViewPropertyControl = $user->can('propiedades.control_ver') || $user->hasRole('administrador') || $user->hasRole('admin');
    $canConfigureDossiers = $user->can('expedientes.configurar') || $user->hasRole('administrador') || $user->hasRole('admin');
    $canConfigureNotifications = $user->can('notificaciones.configurar') || $user->hasRole('administrador') || $user->hasRole('admin');
    $homeRoute = ($isTenant || $isTechnician) ? 'maintenance.index' : 'dashboard';
    $roleLabel = $isTenant ? 'Panel de inquilino' : ($isTechnician ? 'Panel técnico' : 'Panel SuWork');
    $currentHour = now()->hour;
    $greeting = $currentHour < 12 ? 'Buenos días' : ($currentHour < 19 ? 'Buenas tardes' : 'Buenas noches');
    $menuItems = $isTenant
        ? [
            ['patterns' => ['charges.*'], 'route' => 'charges.index', 'label' => 'Cobranza', 'icon' => 'bi-wallet2'],
            ['patterns' => ['maintenance.*'], 'route' => 'maintenance.index', 'label' => 'Mantenimiento', 'icon' => 'bi-tools'],
            [
                'patterns' => ['profile.*'],
                'label' => 'Configuración',
                'icon' => 'bi-gear',
                'children' => [
                    ['patterns' => ['profile.*'], 'route' => 'profile.index', 'label' => 'Perfil', 'icon' => 'bi-person-circle'],
                ],
            ],
        ]
        : ($isTechnician
            ? [
                ['patterns' => ['maintenance.*'], 'route' => 'maintenance.index', 'label' => 'Mantenimiento', 'icon' => 'bi-tools'],
                ['patterns' => ['storage_items.*'], 'route' => 'storage_items.index', 'label' => 'Almacén', 'icon' => 'bi-box-seam'],
                [
                    'patterns' => ['profile.*'],
                    'label' => 'Configuración',
                    'icon' => 'bi-gear',
                    'children' => [
                        ['patterns' => ['profile.*'], 'route' => 'profile.index', 'label' => 'Perfil', 'icon' => 'bi-person-circle'],
                    ],
                ],
            ]
            : [
            ['patterns' => ['dashboard'], 'route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2'],
            ...($canViewPropertyControl ? [['patterns' => ['properties.control'], 'route' => 'properties.control', 'label' => 'Control propiedades', 'icon' => 'bi-clipboard-data']] : []),
            ['patterns' => ['properties.index', 'properties.create', 'properties.show', 'properties.edit', 'properties.inventory.edit'], 'route' => 'properties.index', 'label' => 'Propiedades', 'icon' => 'bi-house-door'],
            ['patterns' => ['owners.*'], 'route' => 'owners.index', 'label' => 'Propietarios', 'icon' => 'bi-person-vcard'],
            ['patterns' => ['tenants.*'], 'route' => 'tenants.index', 'label' => 'Inquilinos', 'icon' => 'bi-people'],
            ['patterns' => ['documents.*', 'dossiers.*'], 'route' => 'documents.index', 'label' => 'Documentos', 'icon' => 'bi-folder2-open'],
            ['patterns' => ['charges.*'], 'route' => 'charges.index', 'label' => 'Cobranza', 'icon' => 'bi-wallet2'],
            ['patterns' => ['expenses.*'], 'route' => 'expenses.index', 'label' => 'Gastos', 'icon' => 'bi-receipt'],
            ['patterns' => ['maintenance.*'], 'route' => 'maintenance.index', 'label' => 'Mantenimiento', 'icon' => 'bi-tools'],
            ['patterns' => ['storage_items.*'], 'route' => 'storage_items.index', 'label' => 'Almacén', 'icon' => 'bi-box-seam'],
            [
                'patterns' => ['settings.dossiers.*', 'settings.notifications.*', 'access.*', 'profile.*'],
                'label' => 'Configuración',
                'icon' => 'bi-gear',
                'children' => [
                    ...($canConfigureDossiers ? [
                        ['patterns' => ['settings.dossiers.index', 'settings.dossiers.requirements.*'], 'route' => 'settings.dossiers.index', 'label' => 'Expedientes', 'icon' => 'bi-sliders'],
                        ['patterns' => ['settings.dossiers.storage', 'settings.dossiers.storage.*'], 'route' => 'settings.dossiers.storage', 'label' => 'Almacenamiento', 'icon' => 'bi-hdd'],
                    ] : []),
                    ...($canConfigureNotifications ? [
                        ['patterns' => ['settings.notifications.*'], 'route' => 'settings.notifications.index', 'label' => 'Notificaciones', 'icon' => 'bi-bell'],
                    ] : []),
                    ...($canManageAccess ? [
                        ['patterns' => ['access.*'], 'route' => 'access.index', 'label' => 'Usuarios y permisos', 'icon' => 'bi-shield-lock'],
                    ] : []),
                    ['patterns' => ['profile.*'], 'route' => 'profile.index', 'label' => 'Perfil', 'icon' => 'bi-person-circle'],
                ],
            ],
        ]);

    $flatMenuItems = collect($menuItems)
        ->flatMap(fn ($item) => $item['children'] ?? [$item])
        ->values();

    $mobilePrimaryItems = $isTenant
        ? [
            ['patterns' => ['charges.*'], 'route' => 'charges.index', 'label' => 'Cobranza', 'icon' => 'bi-wallet2'],
            ['patterns' => ['maintenance.*'], 'route' => 'maintenance.index', 'label' => 'Tickets', 'icon' => 'bi-tools'],
        ]
        : ($isTechnician
            ? [
                ['patterns' => ['maintenance.*'], 'route' => 'maintenance.index', 'label' => 'Tickets', 'icon' => 'bi-tools'],
                ['patterns' => ['storage_items.*'], 'route' => 'storage_items.index', 'label' => 'Almacén', 'icon' => 'bi-box-seam'],
            ]
            : [
                ['patterns' => ['properties.index', 'properties.create', 'properties.show', 'properties.edit', 'properties.inventory.edit', 'dashboard'], 'route' => 'properties.index', 'label' => 'Propiedades', 'icon' => 'bi-house-door'],
                ['patterns' => ['charges.*'], 'route' => 'charges.index', 'label' => 'Cobranza', 'icon' => 'bi-wallet2'],
                ['patterns' => ['maintenance.*'], 'route' => 'maintenance.index', 'label' => 'Tickets', 'icon' => 'bi-tools'],
            ]);

    $mobileSecondaryItems = $flatMenuItems
        ->reject(function ($item) use ($mobilePrimaryItems) {
            return collect($mobilePrimaryItems)->contains(fn($primaryItem) => $primaryItem['route'] === $item['route']);
        })
        ->values();

    $currentSection = $flatMenuItems
        ->first(fn($item) => request()->routeIs(...$item['patterns']))['label'] ?? 'Tu espacio';

    $isMobileMoreActive = $mobileSecondaryItems->contains(
        fn($item) => request()->routeIs(...$item['patterns'])
    );
@endphp

<div class="su-mobile-topbar">
    <div class="su-mobile-topbar__content">
        <div class="su-mobile-topbar__copy">
            <span class="su-mobile-topbar__eyebrow">{{ $greeting }}, {{ $firstName }}</span>
            <strong class="su-mobile-topbar__title">{{ $currentSection }}</strong>
            <span class="su-mobile-topbar__subtitle">{{ $roleLabel }}</span>
        </div>

        <div class="su-mobile-topbar__actions">
            <button type="button" class="su-mobile-icon-btn is-disabled" aria-label="Notificaciones" disabled>
                <i class="bi bi-bell"></i>
            </button>

            <div class="dropdown">
                <button type="button" class="su-mobile-avatar" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Abrir menú de perfil">
                    @if ($user->profile_photo)
                        <img src="{{ $user->profilePhotoUrl() }}" alt="user">
                    @else
                        <span>{{ $initials }}</span>
                    @endif
                </button>

                <div class="dropdown-menu dropdown-menu-end p-0 shadow-sm su-mobile-profile-menu">
                    <div class="px-4 py-3 border-bottom d-flex align-items-center">
                        <div class="symbol symbol-45px me-3">
                            @if ($user->profile_photo)
                                <img src="{{ $user->profilePhotoUrl() }}" class="symbol-label" alt="avatar">
                            @else
                                <div class="symbol-label fw-bold d-flex justify-content-center align-items-center text-white"
                                    style="background:#0d6efd;">
                                    {{ $initials }}
                                </div>
                            @endif
                        </div>
                        <div>
                            <div class="fw-bold">{{ $user->name }}</div>
                            <div class="text-muted small">{{ $user->email }}</div>
                        </div>
                    </div>

                    <a href="{{ route('profile.index') }}" class="dropdown-item px-4 py-3">Mi perfil</a>

                    <div class="dropdown-divider my-0"></div>

                    <a href="#" class="dropdown-item text-danger px-4 py-3"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="ki-outline ki-exit-right me-2"></i> Cerrar sesión
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm py-0 su-desktop-header">
    <div class="container-fluid px-4">
        <a href="{{ route($homeRoute) }}" class="d-flex align-items-center py-2 me-8">
            <img src="{{ asset('assets/img/suhomes-app-logo.svg') }}" alt="Logo SuHomes" height="45">
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainHeaderNav"
            aria-controls="mainHeaderNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainHeaderNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-lg-2">
                @foreach ($menuItems as $item)
                    @php
                        $children = collect($item['children'] ?? []);
                    @endphp

                    @if ($children->isNotEmpty())
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle fw-semibold {{ request()->routeIs(...$item['patterns']) ? 'active text-primary' : 'text-gray-700' }}"
                                href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                {{ $item['label'] }}
                            </a>
                            <ul class="dropdown-menu">
                                @foreach ($children as $child)
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs(...$child['patterns']) ? 'active' : '' }}"
                                            href="{{ route($child['route']) }}">
                                            {{ $child['label'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link fw-semibold {{ request()->routeIs(...$item['patterns']) ? 'active text-primary' : 'text-gray-700' }}"
                                href="{{ route($item['route']) }}">
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>

            <div class="dropdown d-flex align-items-center gap-3">
                <div class="cursor-pointer symbol symbol-circle symbol-40px" data-bs-toggle="dropdown" aria-expanded="false">
                    @if ($user->profile_photo)
                        <img src="{{ $user->profilePhotoUrl() }}" alt="user" class="symbol-label"
                            style="object-fit: cover;">
                    @else
                        <div class="symbol-label fw-bold d-flex justify-content-center align-items-center text-white"
                            style="background:#0d6efd;">
                            {{ $initials }}
                        </div>
                    @endif
                </div>

                <span class="fw-semibold text-dark d-none d-md-inline">{{ $user->name }}</span>

                <div class="dropdown-menu dropdown-menu-end p-0 shadow-sm" style="width:280px">
                    <div class="px-4 py-3 border-bottom d-flex align-items-center">
                        <div class="symbol symbol-45px me-3">
                            @if ($user->profile_photo)
                                <img src="{{ $user->profilePhotoUrl() }}" class="symbol-label" alt="avatar">
                            @else
                                <div class="symbol-label fw-bold d-flex justify-content-center align-items-center text-white"
                                    style="background:#0d6efd;">
                                    {{ $initials }}
                                </div>
                            @endif
                        </div>
                        <div>
                            <div class="fw-bold">{{ $user->name }}</div>
                            <div class="text-muted small">{{ $user->email }}</div>
                        </div>
                    </div>

                    <a href="{{ route('profile.index') }}" class="dropdown-item px-4 py-2">Mi perfil</a>

                    <div class="dropdown-divider"></div>

                    <a href="#" class="dropdown-item text-danger px-4 py-2"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="ki-outline ki-exit-right me-2"></i> Cerrar sesión
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<div class="su-mobile-tabbar">
    <div class="su-mobile-tabbar__inner" style="--su-mobile-tab-count: {{ count($mobilePrimaryItems) + 1 }};">
        @foreach ($mobilePrimaryItems as $item)
            <a href="{{ route($item['route']) }}"
                class="su-mobile-tabbar__item {{ request()->routeIs(...$item['patterns']) ? 'is-active' : '' }}">
                <i class="bi {{ $item['icon'] }}"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach

        <button type="button" class="su-mobile-tabbar__item {{ $isMobileMoreActive ? 'is-active' : '' }}"
            data-bs-toggle="offcanvas" data-bs-target="#suMobileMoreMenu" aria-controls="suMobileMoreMenu">
            <i class="bi bi-grid"></i>
            <span>Más</span>
        </button>
    </div>
</div>

<div class="offcanvas offcanvas-bottom su-mobile-more-sheet" tabindex="-1" id="suMobileMoreMenu"
    aria-labelledby="suMobileMoreMenuLabel">
    <div class="offcanvas-header">
        <div>
            <div class="su-mobile-more-sheet__eyebrow">{{ $roleLabel }}</div>
            <h5 class="offcanvas-title mb-0" id="suMobileMoreMenuLabel">Accesos</h5>
        </div>
        <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="offcanvas" aria-label="Cerrar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="offcanvas-body pt-0">
        <div class="su-mobile-sheet-links">
            @foreach ($mobileSecondaryItems as $item)
                <a href="{{ route($item['route']) }}"
                    class="su-mobile-sheet-link {{ request()->routeIs(...$item['patterns']) ? 'is-active' : '' }}">
                    <span class="su-mobile-sheet-link__icon">
                        <i class="bi {{ $item['icon'] ?? 'bi-circle' }}"></i>
                    </span>
                    <span class="su-mobile-sheet-link__label">{{ $item['label'] }}</span>
                    <i class="bi bi-chevron-right su-mobile-sheet-link__arrow"></i>
                </a>
            @endforeach

            <a href="#" class="su-mobile-sheet-link text-danger"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <span class="su-mobile-sheet-link__icon">
                    <i class="bi bi-box-arrow-right"></i>
                </span>
                <span class="su-mobile-sheet-link__label">Cerrar sesión</span>
                <i class="bi bi-chevron-right su-mobile-sheet-link__arrow"></i>
            </a>
        </div>
    </div>
</div>

<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
</form>
