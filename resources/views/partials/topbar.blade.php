@php
    $topbarUser = Auth::user();
    $topbarNameParts = collect(preg_split('/\s+/', trim($topbarUser->name), -1, PREG_SPLIT_NO_EMPTY));
    $topbarInitials = $topbarNameParts
        ->take(2)
        ->map(fn ($word) => mb_substr($word, 0, 1))
        ->join('');
    $topbarRoleLabel = match (true) {
        $topbarUser->hasAnyRole(['administrador', 'admin']) => 'Administrador',
        $topbarUser->hasAnyRole(['asesores', 'asesor', 'advisor']) => 'Asesor',
        $topbarUser->hasAnyRole(['tecnico', 'technician']) => 'Técnico',
        $topbarUser->hasAnyRole(['inquilino', 'tenant']) => 'Inquilino',
        default => $topbarUser->getRoleNames()->first()
            ? ucfirst(str_replace('_', ' ', $topbarUser->getRoleNames()->first()))
            : 'Usuario',
    };
    $pendingTotal = (int) ($pendingNotifications['total'] ?? 0);
    $pendingItems = collect($pendingNotifications['items'] ?? [])->where('count', '>', 0)->values();
    $pendingRoute = $pendingNotifications['route'] ?? route('dashboard');
@endphp

<header class="su-desktop-topbar" aria-label="Barra superior">
    <div class="su-desktop-topbar__inner">
        <div class="su-desktop-topbar__spacer" aria-hidden="true"></div>

        <div class="su-desktop-topbar__actions">
            <div class="su-topbar-theme-switch" role="group" aria-label="Cambiar tema">
                <button type="button" class="su-topbar-theme-option" data-theme-mode-option="light"
                    aria-label="Usar tema claro" title="Tema claro">
                    <i class="bi bi-sun-fill"></i>
                </button>
                <button type="button" class="su-topbar-theme-option" data-theme-mode-option="dark"
                    aria-label="Usar tema oscuro" title="Tema oscuro">
                    <i class="bi bi-moon-stars-fill"></i>
                </button>
            </div>

            <div class="dropdown">
                <button type="button"
                    class="su-topbar-notification-trigger"
                    data-bs-toggle="dropdown"
                    data-bs-auto-close="outside"
                    aria-expanded="false"
                    aria-label="Abrir pendientes"
                    data-pending-count="{{ $pendingTotal }}">
                    <span class="su-topbar-action-icon">
                        <i class="bi bi-bell"></i>
                        @if ($pendingTotal > 0)
                            <span class="su-topbar-notification-dot"></span>
                        @endif
                    </span>
                    <span class="su-topbar-notification-copy">
                        <strong>{{ number_format($pendingTotal) }}</strong>
                        <small>{{ $pendingTotal === 1 ? 'pendiente' : 'pendientes' }}</small>
                    </span>
                </button>

                <div class="dropdown-menu dropdown-menu-end p-0 su-topbar-notification-menu">
                    <div class="su-topbar-dropdown-heading">
                        <div>
                            <div class="fw-bold text-gray-900">Pendientes</div>
                            <div class="text-muted fs-8">Elementos que requieren atención</div>
                        </div>
                        <span class="badge badge-light-primary">{{ number_format($pendingTotal) }}</span>
                    </div>

                    <div class="su-topbar-notification-list">
                        @forelse ($pendingItems as $item)
                            <a href="{{ $item['route'] }}" class="su-topbar-notification-item">
                                <span class="su-topbar-notification-item__icon">
                                    <i class="bi {{ $item['icon'] }}"></i>
                                </span>
                                <span class="min-w-0 flex-grow-1">
                                    <span class="d-block fw-bold text-gray-900 text-truncate">{{ $item['title'] }}</span>
                                    <span class="d-block text-muted fs-8 text-truncate">{{ $item['subtitle'] }}</span>
                                </span>
                                <span class="badge badge-light-primary">{{ number_format((int) $item['count']) }}</span>
                            </a>
                        @empty
                            <div class="su-topbar-notification-empty">
                                <i class="bi bi-check2-circle"></i>
                                <div>
                                    <div class="fw-bold text-gray-900">Todo al día</div>
                                    <div class="text-muted fs-8">No tienes pendientes para hoy.</div>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    <a href="{{ $pendingRoute }}" class="su-topbar-dropdown-footer">
                        Ver pendientes
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>

            <div class="dropdown">
                <button type="button"
                    class="su-topbar-profile-trigger"
                    data-bs-toggle="dropdown"
                    data-bs-auto-close="outside"
                    aria-expanded="false"
                    aria-label="Abrir menú de perfil">
                    <span class="su-topbar-profile-trigger__avatar">
                        @if ($topbarUser->profile_photo)
                            <img src="{{ $topbarUser->profilePhotoUrl() }}" alt="{{ $topbarUser->name }}">
                        @else
                            <span>{{ $topbarInitials ?: 'NB' }}</span>
                        @endif
                    </span>
                    <span class="su-topbar-profile-trigger__identity">
                        <strong>{{ $topbarUser->name }}</strong>
                        <small>{{ $topbarUser->email }}</small>
                    </span>
                    <i class="bi bi-chevron-down su-topbar-profile-trigger__chevron"></i>
                </button>

                <div class="dropdown-menu dropdown-menu-end p-0 su-topbar-profile-menu">
                    <div class="su-topbar-profile-card">
                        <div class="su-topbar-profile-avatar">
                            @if ($topbarUser->profile_photo)
                                <img src="{{ $topbarUser->profilePhotoUrl() }}" alt="{{ $topbarUser->name }}">
                            @else
                                <span>{{ $topbarInitials ?: 'NB' }}</span>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <div class="fw-bold text-gray-900 text-truncate">{{ $topbarUser->name }}</div>
                            <div class="text-muted fs-8 text-truncate">{{ $topbarUser->email }}</div>
                            <div class="su-topbar-profile-role">
                                <i class="bi bi-person-badge"></i>
                                {{ $topbarRoleLabel }}
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('profile.index') }}" class="dropdown-item px-4 py-3">
                        <i class="bi bi-person-circle me-2"></i> Mi perfil
                    </a>
                    <button type="button" class="dropdown-item px-4 py-3" data-sidebar-theme-toggle>
                        <i class="bi bi-circle-half me-2"></i> Cambiar modo
                    </button>
                    <div class="dropdown-divider my-0"></div>
                    <a href="#" class="dropdown-item text-danger px-4 py-3"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="bi bi-box-arrow-right me-2"></i> Cerrar sesión
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
