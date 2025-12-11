<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Only attempt for MySQL; skip for sqlite or others
        $driver = DB::getDriverName();
        if (!Schema::hasTable("t_consultation_bookings") || $driver !== "mysql") {
            return;
        }

        // Inspect the column type; if it's an ENUM and missing 'cancelled', append it
        $database = DB::getDatabaseName();
        $row = DB::table("information_schema.columns")
            ->select("COLUMN_TYPE")
            ->where("TABLE_SCHEMA", $database)
            ->where("TABLE_NAME", "t_consultation_bookings")
            ->where("COLUMN_NAME", "Status")
            ->first();

        if (!$row) {
            return;
        }

        $columnType = strtolower((string) ($row->COLUMN_TYPE ?? ""));
        if (!str_starts_with($columnType, "enum(")) {
            // Not an enum; nothing to change
            return;
        }

        // Extract enum values inside enum('a','b',...)
        $inside = substr($columnType, 5, -1); // drop enum( and trailing )
        // Split respecting quotes
        $values = [];
        $buffer = "";
        $inQuote = false;
        $len = strlen($inside);
        for ($i = 0; $i < $len; $i++) {
            $ch = $inside[$i];
            if ($ch === "'") {
                $inQuote = !$inQuote;
                continue;
            }
            if ($ch === "," && !$inQuote) {
                $values[] = $buffer;
                $buffer = "";
            } else {
                $buffer .= $ch;
            }
        }
        if ($buffer !== "") {
            $values[] = $buffer;
        }
        // Trim whitespace around values
        $values = array_map(static fn($v) => trim($v), $values);

        // Ensure 'cancelled' is present
        if (!in_array("cancelled", $values, true)) {
            $values[] = "cancelled";
        } else {
            return; // already present
        }

        // Build and run the ALTER statement preserving existing order and default
        // Keep 'pending' as default if present; otherwise keep current default
        $enumList = implode(
            ",",
            array_map(static fn($v) => "'" . str_replace("'", "''", $v) . "'", $values),
        );

        // Determine current default
        $defaultRow = DB::table("information_schema.columns")
            ->select("COLUMN_DEFAULT")
            ->where("TABLE_SCHEMA", $database)
            ->where("TABLE_NAME", "t_consultation_bookings")
            ->where("COLUMN_NAME", "Status")
            ->first();
        $currentDefault = $defaultRow?->COLUMN_DEFAULT;
        $default = in_array("pending", $values, true)
            ? " DEFAULT 'pending'"
            : ($currentDefault
                ? " DEFAULT '" . addslashes($currentDefault) . "'"
                : "");

        $sql = "ALTER TABLE `t_consultation_bookings` MODIFY COLUMN `Status` ENUM($enumList) NOT NULL$default";
        DB::statement($sql);
    }

    public function down(): void
    {
        // No-op: we do not remove enum values in down to avoid data loss
    }
};
