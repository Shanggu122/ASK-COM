<?php

namespace App\Console\Commands;

use App\Support\ProfilePhotoPath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RepairProfilePictures extends Command
{
    protected $signature = "profiles:repair {--dry : Only report changes}";
    protected $description = "Normalize profile_picture paths to relative and report / fix missing files.";

    public function handle(): int
    {
        $tables = [
            ["table" => "professors", "key" => "Prof_ID"],
            ["table" => "t_student", "key" => "Stud_ID"],
            ["table" => "admin", "key" => "Admin_ID"],
        ];
        $disk = config("filesystems.default", "public");
        $storage = Storage::disk($disk);
        $dry = $this->option("dry");
        $updated = 0;
        $missing = 0;

        foreach ($tables as $t) {
            $rows = DB::table($t["table"])
                ->select($t["key"] . " as id", "profile_picture")
                ->get();
            foreach ($rows as $row) {
                if (!$row->profile_picture) {
                    continue;
                }
                $orig = $row->profile_picture;
                $relative = $this->toRelative($orig);
                if ($relative !== $orig) {
                    if (!$dry) {
                        DB::table($t["table"])
                            ->where($t["key"], $row->id)
                            ->update(["profile_picture" => $relative]);
                    }
                    $updated++;
                    $this->line("Updated {$t["table"]} {$row->id} -> {$relative}");
                }
                if (!$storage->exists($relative)) {
                    $missing++;
                    $this->warn("Missing file: {$relative} (table {$t["table"]} id {$row->id})");
                }
            }
        }
        $this->info("Done. Updated paths: {$updated}. Missing files: {$missing}.");
        if ($dry) {
            $this->info("Dry run only; rerun without --dry to apply changes.");
        }
        return Command::SUCCESS;
    }

    private function toRelative(string $path): string
    {
        $normalized = ProfilePhotoPath::normalize($path);
        return $normalized ?? $path;
    }
}
