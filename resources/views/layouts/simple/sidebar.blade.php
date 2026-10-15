<!-- Page Sidebar Start-->
<div class="sidebar-wrapper" data-sidebar-layout="stroke-svg">
    <div>
        <div class="logo-wrapper">
            <a href="{{ route('admin.dashboard') }}">
                <img class="img-fluid for-light" style="max-height: 50px; width: auto;" src="{{ asset('assets/images/logo/logo.png') }}" alt="{{ config('app.name') }}">
                <img class="img-fluid for-dark" style="max-height: 50px; width: auto;" src="{{ asset('assets/images/logo/logo_dark.png') }}" alt="{{ config('app.name') }}">
            </a>
            <div class="back-btn"><i class="fa-solid fa-angle-left"></i></div>
        </div>
        <div class="logo-icon-wrapper">
            <a href="{{ route('admin.dashboard') }}">
                <img class="img-fluid" src="{{ asset('assets/images/logo/logo-icon.png') }}" alt="{{ config('app.name') }}">
            </a>
        </div>
        <nav class="sidebar-main">
            <div class="left-arrow" id="left-arrow"><i data-feather="arrow-left"></i></div>
            <div id="sidebar-menu">
                <ul class="sidebar-links" id="simple-bar">
                    <li class="back-btn">
                        <div class="mobile-back text-end"><span>Back</span><i class="fa-solid fa-angle-right ps-2"></i></div>
                    </li>
                    @foreach (config('menu') as $item)
                        @include('layouts.simple.partials.sidebar-item', ['item' => $item])
                    @endforeach
                </ul>
            </div>
            <div class="right-arrow" id="right-arrow"><i data-feather="arrow-right"></i></div>
        </nav>
    </div>
</div>
<!-- Page Sidebar Ends-->
