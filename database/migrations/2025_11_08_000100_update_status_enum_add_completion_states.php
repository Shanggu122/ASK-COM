<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable("t_consultation_bookings")) {
            return;
        }

        $driver = DB::getDriverName();
        if ($driver !== "mysql") {
            return;
        }

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
            return;
        }

        $inside = substr($columnType, 5, -1);
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
        $values = array_map(static fn($v) => trim($v), $values);

        $extras = ["completion_pending", "completion_declined"];
        $changed = false;
        foreach ($extras as $extra) {
            if (!in_array($extra, $values, true)) {
                $values[] = $extra;
                $changed = true;
            }
        }

        if (!$changed) {
            return;
        }

        $enumList = implode(
            ",",
            array_map(static fn($v) => "'" . str_replace("'", "''", $v) . "'", $values),
        );

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
        // No rollback to avoid data loss for enum values
    }
};
