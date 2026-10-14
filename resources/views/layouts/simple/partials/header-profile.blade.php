<li class="profile-nav onhover-dropdown pe-0 py-0">
    <div class="d-flex profile-media">
        <img class="b-r-10" src="{{ asset('assets/images/dashboard/profile.png') }}" alt="">
        <div class="flex-grow-1">
            <span>{{ auth()->user()->full_name }}</span>
            <p class="mb-0">{{ ucfirst((string) auth()->user()->roleName()) }}
                <i class="middle fa-solid fa-angle-down"></i>
            </p>
        </div>
    </div>
    <ul class="profile-dropdown onhover-show-div">
        <li>
            <a href="{{ route('admin.dashboard') }}"><i data-feather="home"></i><span>Dashboard</span></a>
        </li>
        <li>
            <a href="{{ route('logout') }}"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i data-feather="log-out"></i><span>Log out</span>
            </a>
        </li>
    </ul>
    <form action="{{ route('logout') }}" method="POST" class="d-none" id="logout-form">@csrf</form>
</li>
