<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_release_settings', function (Blueprint $table) {
            $table->id();
            $table->string('web_app_url')->nullable();
            $table->string('android_apk_path')->nullable();
            $table->string('android_version')->default('1.0.0');
            $table->unsignedInteger('android_version_code')->default(1);
            $table->string('ios_ipa_path')->nullable();
            $table->string('ios_version')->nullable();
            $table->unsignedInteger('ios_build_number')->nullable();
            $table->text('release_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_release_settings');
    }
};
