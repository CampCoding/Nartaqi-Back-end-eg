<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->repointForeignKey('videos', 'lesson_id', 'lessons', 'id');
        $this->repointForeignKey('round_lives', 'lesson_id', 'lessons', 'id');
    }

    public function down(): void
    {
        $this->repointForeignKey('videos', 'lesson_id', 'round_contents', 'id');
        $this->repointForeignKey('round_lives', 'lesson_id', 'round_contents', 'id');
    }

    private function repointForeignKey(string $table, string $column, string $referencedTable, string $referencedColumn): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        // Drop existing foreign keys if they exist
        $constraintNames = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [DB::getDatabaseName(), $table, $column]
        );

        foreach ($constraintNames as $constraint) {
            try {
                Schema::table($table, function (Blueprint $blueprint) use ($constraint) {
                    $blueprint->dropForeign($constraint->CONSTRAINT_NAME);
                });
            } catch (\Throwable $e) {
                // ignore if constraint doesn't exist
            }
        }

        // Ensure the referenced table exists and has proper index
        if (!Schema::hasTable($referencedTable)) {
            return;
        }

        // Add the new foreign key constraint
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $referencedTable, $referencedColumn) {
                $blueprint->foreign($column)
                    ->references($referencedColumn)
                    ->on($referencedTable)
                    ->onDelete('cascade');
            });
        } catch (\Throwable $e) {
            \Log::error("Failed to add foreign key constraint: " . $e->getMessage());
            throw $e;
        }
    }
};


