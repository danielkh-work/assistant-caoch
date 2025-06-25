<aside class="main-sidebar sidebar-dark-primary elevation-4">
    {{-- <a href="index3.html" class="brand-link">
        <img src="{{ asset('assets/img/AdminLTELogo.png') }}" alt="AdminLTE Logo"
            class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">{{ env('APP_NAME') }}</span>
    </a> --}}

    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="{{ asset('assets/img/user1-128x128.jpg') }}" class="img-circle elevation-2" alt="User Image">
            </div>
            <div class="info">
                <a href="#" class="d-block">{{ Auth::user()->name }}</a>
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                data-accordion="false">
                <li class="nav-item">
                    <a 
                    {{-- href="{{ route('dashboard') }}" --}}
                        class="{{ Request::is('admin/dashboard') ? 'nav-link active' : 'nav-link' }}"
                        >
                        <i class="nav-icon fas fa-chart-pie"></i>
                        <p>
                            Dashboard
                        </p>
                    </a>
                </li>
                <li class="nav-item">
                    <a 
                    href="{{ route('players.index') }}"
                        class="{{ Request::is('players') || Request::is('players/*') ? 'nav-link active' : 'nav-link' }}"
                      
                        >
                        <i class="nav-icon fas fa-users"></i>
                        <p>
                            Players
                        </p>
                    </a>
                </li>
                <li class="nav-item">
                    <a 
                    {{-- href="{{ route('customers.index') }}"--}}
                        class="{{ Request::is('admin/customers') || Request::is('admin/customers/*') ? 'nav-link active' : 'nav-link' }}" 
                        >
                        <i class="nav-icon fas fa-video"></i>
                        <p>
                            Plays
                        </p>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a 
                    {{-- href="{{ route('guard.booking.index') }}"--}}
                        class="{{ Request::is('admin/guard/booking') || Request::is('admin/guard/*') ? 'nav-link active' : 'nav-link' }}"
                        
                        >
                        <i class="nav-icon fas fa-map"></i>
                        <p>
                          League
                        </p>
                    </a>
                </li>
                <li class="nav-item">
                    <a
                    {{-- href="{{ route('withdraw.index') }}"--}}
                        class="{{ Request::is('admin/withdraw/*') || Request::is('admin/withdraw') ? 'nav-link active' : 'nav-link' }}"
                         
                        >
                        <i class="nav-icon fas fa-list-ol"></i>
                        <p>
                           Team
                        </p>
                    </a>
                </li>
               

            </ul>
        </nav>
    </div>
</aside>
