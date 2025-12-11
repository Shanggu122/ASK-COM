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
        if (!Schema::hasColumn("t_consultation_bookings", "Consult_type_ID")) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ["mysql", "mariadb"], true)) {
            $column = DB::selectOne(
                "SHOW COLUMNS FROM `t_consultation_bookings` LIKE 'Consult_type_ID'",
            );
            if ($column && strtolower((string) $column->Null) === "no") {
                $type = $column->Type;
                $defaultClause = $this->buildDefaultClause($column->Default ?? null);
                DB::statement(
                    "ALTER TABLE `t_consultation_bookings` MODIFY `Consult_type_ID` {$type} NULL{$defaultClause}",
                );
            }
        } elseif ($driver === "pgsql") {
            DB::statement(
                'ALTER TABLE "t_consultation_bookings" ALTER COLUMN "Consult_type_ID" DROP NOT NULL',
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable("t_consultation_bookings")) {
            return;
        }
        if (!Schema::hasColumn("t_consultation_bookings", "Consult_type_ID")) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ["mysql", "mariadb"], true)) {
            $column = DB::selectOne(
                "SHOW COLUMNS FROM `t_consultation_bookings` LIKE 'Consult_type_ID'",
            );
            if ($column && strtolower((string) $column->Null) === "yes") {
                $type = $column->Type;
                $defaultClause = $this->buildDefaultClause($column->Default ?? null);
                DB::statement(
                    "ALTER TABLE `t_consultation_bookings` MODIFY `Consult_type_ID` {$type} NOT NULL{$defaultClause}",
                );
            }
        } elseif ($driver === "pgsql") {
            DB::statement(
                'ALTER TABLE "t_consultation_bookings" ALTER COLUMN "Consult_type_ID" SET NOT NULL',
            );
        }
    }

    private function buildDefaultClause($default): string
    {
        if ($default === null) {
            return "";
        }

        if (is_numeric($default)) {
            return " DEFAULT " . $default;
        }

        $escaped = str_replace("'", "''", (string) $default);
        return " DEFAULT '" . $escaped . "'";
    }
};
