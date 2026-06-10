<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'user:admin {email} {--revoke : Remove admin access instead of granting it}';
    protected $description = 'Grant (or revoke) admin access for a user by email';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error("No user found with email {$this->argument('email')}.");
            return self::FAILURE;
        }

        $grant = ! $this->option('revoke');
        $user->forceFill(['is_admin' => $grant])->save();

        $this->info($grant
            ? "{$user->email} is now an admin."
            : "Admin access revoked for {$user->email}.");

        return self::SUCCESS;
    }
}
