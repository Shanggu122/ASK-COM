<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneLoginAttempts extends Command
{
    protected $signature = 'login-attempts:prune';
    protected $description = 'Delete old login attempt audit records beyond retention window';

    public function handle(): int
    {
        $days = (int) config('auth_security.login_attempt_retention_days', 30);
        $cutoff = now()->subDays($days);
        $deleted = DB::table('login_attempts')
            ->where('created_at', '<', $cutoff)
            ->delete();
        $this->info("Pruned {$deleted} old login_attempts (>{ $days } days).");
        return self::SUCCESS;
    }
}
