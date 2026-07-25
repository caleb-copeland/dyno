<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-[#F2F2F3]">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm text-[#8A8A90]">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <x-danger-button onclick="document.getElementById('confirm-user-deletion').showModal()">
        {{ __('Delete Account') }}
    </x-danger-button>

    <dialog id="confirm-user-deletion"
            @if($errors->userDeletion->isNotEmpty()) open @endif
            style="background:#141416;color:#F2F2F3;border:1px solid #26262A;border-radius:20px;padding:0;width:min(92vw,480px);">
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-[#F2F2F3]">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="mt-1 text-sm text-[#8A8A90]">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="{{ __('Password') }}"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" onclick="document.getElementById('confirm-user-deletion').close()">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button>
                    {{ __('Delete Account') }}
                </x-danger-button>
            </div>
        </form>
    </dialog>
</section>
