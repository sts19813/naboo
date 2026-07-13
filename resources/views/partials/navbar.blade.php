<!-- Navbar -->
<div class="bg-white border-bottom mb-8">
    <div class="container-fluid px-4">
        <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-6 fw-semibold border-0">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('properties.*') ? 'active' : '' }}"
                    href="{{ route('properties.index') }}">
                    Propiedades
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.index') }}">
                    Perfil
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('charges.*') ? 'active' : '' }}" href="{{ route('charges.index') }}">
                    Cobranza
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}" href="{{ route('expenses.index') }}">
                    Gastos
                </a>
            </li>
        </ul>
    </div>
</div>
<!-- End of Navbar -->
