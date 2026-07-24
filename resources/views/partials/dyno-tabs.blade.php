<nav class="tabbar">
    <a class="tab" href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>
        <span class="tab-ico">◎</span>Today
    </a>
    <a class="tab" href="{{ route('schedule') }}" @if(request()->routeIs('schedule')) aria-current="page" @endif>
        <span class="tab-ico">▦</span>Schedule
    </a>
    <a class="tab" href="{{ route('progress') }}" @if(request()->routeIs('progress') || request()->routeIs('history')) aria-current="page" @endif>
        <span class="tab-ico">≡</span>Progress
    </a>
    <a class="tab" href="{{ route('profile.edit') }}" @if(request()->routeIs('profile.*')) aria-current="page" @endif>
        <span class="tab-ico">◔</span>Profile
    </a>
</nav>
