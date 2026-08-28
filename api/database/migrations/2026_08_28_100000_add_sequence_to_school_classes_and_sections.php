<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->unsignedSmallInteger('sequence')->default(0)->after('name');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->unsignedSmallInteger('sequence')->default(0)->after('name');
        });

        $classSequence = 0;
        $currentAreaId = null;
        foreach (DB::table('school_classes')->orderBy('area_id')->orderBy('id')->get(['id', 'area_id']) as $row) {
            if ($row->area_id !== $currentAreaId) {
                $currentAreaId = $row->area_id;
                $classSequence = 0;
            }
            $classSequence++;
            DB::table('school_classes')->where('id', $row->id)->update(['sequence' => $classSequence]);
        }

        $sectionSequence = 0;
        $currentClassId = null;
        foreach (DB::table('sections')->orderBy('school_class_id')->orderBy('id')->get(['id', 'school_class_id']) as $row) {
            if ($row->school_class_id !== $currentClassId) {
                $currentClassId = $row->school_class_id;
                $sectionSequence = 0;
            }
            $sectionSequence++;
            DB::table('sections')->where('id', $row->id)->update(['sequence' => $sectionSequence]);
        }
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn('sequence');
        });

        Schema::table('school_classes', function (Blueprint $table) {
            $table->dropColumn('sequence');
        });
    }
};
