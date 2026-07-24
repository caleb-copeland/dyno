<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('app:create-admin')]
#[Description('Create (or promote) an active admin — the invite-only bootstrap account')]
class CreateAdminUser extends Command
{
    public function handle(): int
    {
        $name = text('Name', required: true);
        $email = strtolower(trim(text('Email', required: true)));

        if (Validator::make(['email' => $email], ['email' => ['required', 'email']])->fails()) {
            $this->error('Invalid email address.');

            return self::FAILURE;
        }

        if ($existing = User::where('email', $email)->first()) {
            // role & active are not mass-assignable, so update() would silently
            // discard them — set them explicitly.
            $existing->forceFill(['role' => 'admin', 'active' => true])->save();
            $this->info("Existing user {$email} promoted to active admin.");

            return self::SUCCESS;
        }

        $pw = password('Password', required: true);

        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($pw);
        $user->role = 'admin';
        $user->active = true;
        $user->email_verified_at = now();
        $user->save();

        $this->info("Admin {$email} created. Sign in at /admin.");

        return self::SUCCESS;
    }
}
