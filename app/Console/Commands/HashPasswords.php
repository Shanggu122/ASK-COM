<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;  // Or your custom model
use Illuminate\Support\Facades\Hash;

class HashPasswords extends Command
{
    // The name and signature of the console command.
    protected $signature = 'users:hash-passwords';

    // The console command description.
    protected $description = 'Hash all existing user passwords';

    // Execute the console command.
    public function handle()
    {
        $users = User::all(); // Use your custom model for t_student if not using User model

        foreach ($users as $user) {
            $user->Password = Hash::make($user->Password); // Hash the password
            $user->save(); // Save the updated user
            $this->info("Password hashed for user: " . $user->Stud_ID); // Optional: output info to console
        }
    }
}
