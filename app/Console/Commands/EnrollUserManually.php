<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Enrollment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class EnrollUserManually extends Command
{
    /**
     * Usage: php artisan enroll:user {email} {name} {amount=59000} {currency=NGN}
     */
    protected $signature = 'enroll:user {email} {name} {amount=59000} {currency=NGN}';
    protected $description = 'Manually enroll a student who paid offline and trigger welcome automation';

    public function handle()
    {
        $email = $this->argument('email');
        $name = $this->argument('name');
        $amount = $this->argument('amount');
        $currency = $this->argument('currency');

        $this->info("Initializing manual enrollment for: {$email}...");

        // 1. Create or Update User
        $tempPassword = Str::random(12);
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($tempPassword),
            ]
        );

        // 2. Create Paid Enrollment Record
        $reference = 'MAN_' . strtoupper(Str::random(8));
        Enrollment::create([
            'full_name' => $name,
            'email' => $email,
            'payment_reference' => $reference,
            'amount' => $amount,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->info("User record and enrollment created. Triggering automation...");

        // 3. Trigger n8n Welcome Automation
        $webhookUrl = config('services.n8n.enrollment_webhook');

        if (!$webhookUrl) {
            $this->error("n8n webhook URL not found in config/services.php");
            $this->line("Temp Password for manual send: {$tempPassword}");
            return;
        }

        try {
            $response = Http::post($webhookUrl, [
                'event' => 'enrollment_finalized',
                'full_name' => $name,
                'email' => $email,
                'temp_password' => $tempPassword,
                'login_url' => url('/login'),
                'amount' => $amount,
                'currency' => $currency,
                'reference' => $reference,
            ]);

            if ($response->successful()) {
                $this->info("Successfully enrolled! Welcome email and WhatsApp should be on their way.");
            } else {
                $this->warn("Automation trigger failed, but user was created. Manual follow-up required.");
                $this->line("Temp Password: {$tempPassword}");
            }
        } catch (\Exception $e) {
            $this->error("Connection Error: " . $e->getMessage());
            $this->line("Temp Password: {$tempPassword}");
        }
    }
}