<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

#[Signature('app:vapid')]
#[Description('Generate a VAPID key pair for Web Push — add the output to .env')]
class GenerateVapidKeys extends Command
{
    public function handle(): int
    {
        $keys = VAPID::createVapidKeys();

        $this->info('VAPID key pair generated. Add these to your .env:');
        $this->newLine();
        $this->line('VAPID_SUBJECT=mailto:you@example.com');
        $this->line('VAPID_PUBLIC_KEY='.$keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY='.$keys['privateKey']);
        $this->newLine();
        $this->warn('Keep the private key secret. Re-run only to rotate keys (invalidates existing subscriptions).');

        return self::SUCCESS;
    }
}
