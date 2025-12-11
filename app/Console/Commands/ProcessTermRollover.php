<?php

namespace App\Console\Commands;

use App\Models\Term;
use App\Services\AcademicTermService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessTermRollover extends Command
{
    protected $signature = "academic-terms:process-rollover {--force : Force activation even if the current term has not ended yet}";

    protected $description = "Evaluate active academic term and activate the next one when end dates lapse.";

    public function handle(AcademicTermService $termService): int
    {
        $force = (bool) $this->option("force");
        $active = $termService->getActiveTerm();
        $now = CarbonImmutable::now("Asia/Manila")->endOfDay();
        $adminId = $this->resolveSystemAdminId();

        if (!$active) {
            $this->activateNextPendingTerm($termService, $adminId, $force);
            return self::SUCCESS;
        }

        $end = CarbonImmutable::parse($active->end_at)->endOfDay();
        if ($force || $end->lte($now)) {
            $next = $this->nextDraftTerm($active);
            if ($next) {
                $termService->activateTerm($next, $adminId, $force);
                $this->info("Activated term {$next->name} ({$next->academicYear->label})");
            } else {
                $this->warn("Active term ended but no draft term is queued.");
            }
        } else {
            $this->line(
                "Current term {$active->name} is still ongoing until {$end->toFormattedDateString()}.",
            );
        }

        return self::SUCCESS;
    }

    protected function nextDraftTerm(Term $current): ?Term
    {
        return Term::query()
            ->where("academic_year_id", $current->academic_year_id)
            ->where("status", "draft")
            ->orderBy("sequence")
            ->first() ?? Term::query()->where("status", "draft")->orderBy("start_at")->first();
    }

    protected function activateNextPendingTerm(
        AcademicTermService $service,
        int $adminId,
        bool $force,
    ): void {
        $next = Term::query()->where("status", "draft")->orderBy("start_at")->first();
        if (!$next) {
            $this->warn("No draft term found to activate.");
            return;
        }

        $service->activateTerm($next, $adminId, $force);
        $this->info("Activated term {$next->name} ({$next->academicYear->label})");
    }

    protected function resolveSystemAdminId(): int
    {
        $id = DB::table("t_admin")->orderBy("Admin_ID")->value("Admin_ID");
        if ($id) {
            return (int) $id;
        }

        $fallback = DB::table("admin")->orderBy("Admin_ID")->value("Admin_ID");
        return (int) ($fallback ?? 0);
    }
}
