<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'core_user_id')) {
                $table->unsignedBigInteger('core_user_id')->nullable()->after('remember_token')->index();
            }

            $this->addSyncColumns($table, 'users');
        });

        Schema::table('students', function (Blueprint $table): void {
            if (! Schema::hasColumn('students', 'core_student_id')) {
                $table->unsignedBigInteger('core_student_id')->nullable()->after('status')->index();
            }

            $this->addSyncColumns($table, 'students');
        });

        Schema::table('lecturers', function (Blueprint $table): void {
            if (! Schema::hasColumn('lecturers', 'core_lecturer_id')) {
                $table->unsignedBigInteger('core_lecturer_id')->nullable()->after('status')->index();
            }

            $this->addSyncColumns($table, 'lecturers');
        });

        Schema::table('field_supervisors', function (Blueprint $table): void {
            if (! Schema::hasColumn('field_supervisors', 'core_user_id')) {
                $table->unsignedBigInteger('core_user_id')->nullable()->after('status')->index();
            }

            $this->addSyncColumns($table, 'field_supervisors');
        });
    }

    public function down(): void
    {
        Schema::table('field_supervisors', function (Blueprint $table): void {
            $this->dropSyncColumns($table, 'field_supervisors', 'core_user_id');
        });

        Schema::table('lecturers', function (Blueprint $table): void {
            $this->dropSyncColumns($table, 'lecturers', 'core_lecturer_id');
        });

        Schema::table('students', function (Blueprint $table): void {
            $this->dropSyncColumns($table, 'students', 'core_student_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $this->dropSyncColumns($table, 'users', 'core_user_id');
        });
    }

    private function addSyncColumns(Blueprint $table, string $tableName): void
    {
        if (! Schema::hasColumn($tableName, 'core_synced_at')) {
            $table->timestamp('core_synced_at')->nullable()->after($this->afterColumn($tableName));
        }

        if (! Schema::hasColumn($tableName, 'core_sync_status')) {
            $table->string('core_sync_status')->nullable()->after('core_synced_at');
        }

        if (! Schema::hasColumn($tableName, 'core_sync_note')) {
            $table->text('core_sync_note')->nullable()->after('core_sync_status');
        }
    }

    private function dropSyncColumns(Blueprint $table, string $tableName, string $mappingColumn): void
    {
        foreach (['core_sync_note', 'core_sync_status', 'core_synced_at', $mappingColumn] as $column) {
            if (Schema::hasColumn($tableName, $column)) {
                $table->dropColumn($column);
            }
        }
    }

    private function afterColumn(string $tableName): string
    {
        return match ($tableName) {
            'users', 'field_supervisors' => 'core_user_id',
            'students' => 'core_student_id',
            'lecturers' => 'core_lecturer_id',
        };
    }
};
