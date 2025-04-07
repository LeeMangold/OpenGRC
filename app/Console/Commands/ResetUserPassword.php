<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'opengrc:reset-password')]
class ResetUserPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opengrc:reset-password {email : The email of the user} {--generate : Generate a random password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset password for a user';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->components->error("User with email {$email} not found.");

            return;
        }

        if ($this->option('generate')) {
            $password = \Str::random(12);
        } else {
            $password = $this->secret('Enter new password');
            $confirmation = $this->secret('Confirm new password');

            if ($password !== $confirmation) {
                $this->components->error('Passwords do not match.');

                return;
            }
        }

        $user->password = Hash::make($password);
        $user->password_reset_required = false;
        $user->save();

        if ($this->option('generate')) {
            $this->components->info("Password has been reset. New password: {$password}");
        } else {
            $this->components->info('Password has been reset successfully.');
        }
    }
}
