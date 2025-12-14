<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_artifacts', function ($table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();

    $table->string('type'); 
    // PRODUCT_UPDATE, MEETING_SCHEDULE, MOM_DRAFT, MOM_REFINED, MOM_FINAL, HR_UPDATE

    $table->string('tone')->default('formal');

    $table->json('input_json');
    $table->text('subject')->nullable();
    $table->longText('body_text')->nullable();

    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_artifacts');
    }
};
