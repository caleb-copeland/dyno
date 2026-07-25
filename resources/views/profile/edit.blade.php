@extends('layouts.dyno')

@section('title', 'Profile')

@push('head')
    @vite(['resources/css/app.css'])
@endpush

@section('content')
    <h1 style="font-size:28px;font-weight:800;letter-spacing:-0.03em;margin:4px 0 18px;">{{ __('Profile') }}</h1>

    <div class="card">
        <div class="max-w-xl">
            @include('profile.partials.update-profile-information-form')
        </div>
    </div>

    <div class="card">
        <div class="max-w-xl">
            @include('profile.partials.update-password-form')
        </div>
    </div>

    <div class="card">
        <div class="max-w-xl">
            @include('profile.partials.delete-user-form')
        </div>
    </div>

    <form method="POST" action="{{ route('logout') }}" style="margin-top:8px;">
        @csrf
        <button type="submit" class="btn btn--ghost btn--full">{{ __('Log Out') }}</button>
    </form>
@endsection
