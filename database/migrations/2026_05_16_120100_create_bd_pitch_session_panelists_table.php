<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bd_pitch_session_panelists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pitch_session_id')->constrained('bd_pitch_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('panel_role')->default('panelist');
            $table->boolean('is_chair')->default(false);
            $table->timestamps();

            $table->unique(['pitch_session_id', 'user_id'], 'bd_pitch_session_panel_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bd_pitch_session_panelists');
    }
};
