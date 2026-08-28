<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->unsignedSmallInteger('sequence')->default(0)->after('name');
        });

        $areaSequence = 0;
        $currentYearId = null;
        foreach (DB::table('areas')->orderBy('academic_year_id')->orderBy('id')->get(['id', 'academic_year_id']) as $row) {
            if ($row->academic_year_id !== $currentYearId) {
                $currentYearId = $row->academic_year_id;
                $areaSequence = 0;
            }
            $areaSequence++;
            DB::table('areas')->where('id', $row->id)->update(['sequence' => $areaSequence]);
        }
    }

    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropColumn('sequence');
        });
    }
};
