<nav class="tabbar">
    <a class="tab" href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>
        <x-ico name="home" :size="20" class="tab-ico" />Today
    </a>
    <a class="tab" href="{{ route('schedule') }}" @if(request()->routeIs('schedule')) aria-current="page" @endif>
        <x-ico name="calendar" :size="20" class="tab-ico" />Schedule
    </a>
    <a class="tab" href="{{ route('progress') }}" @if(request()->routeIs('progress') || request()->routeIs('history')) aria-current="page" @endif>
        <x-ico name="activity" :size="20" class="tab-ico" />Progress
    </a>
    <a class="tab" href="{{ route('profile.edit') }}" @if(request()->routeIs('profile.*')) aria-current="page" @endif>
        <x-ico name="user" :size="20" class="tab-ico" />Profile
    </a>
</nav>
