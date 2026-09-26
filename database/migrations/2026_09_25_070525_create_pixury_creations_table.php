<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pixury_creations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('template_id')->nullable()->index();
            $table->string('type')->default('image'); // 'image' or 'video'
            $table->string('status')->default('pending'); // 'pending', 'processing', 'completed', 'failed'
            $table->text('prompt')->nullable();
            $table->text('negative_prompt')->nullable();
            $table->json('options')->nullable(); // guidance_scale, steps, seed, aspect_ratio, etc.
            $table->string('product_image_path')->nullable(); // R2 path for uploaded product image
            $table->string('result_url')->nullable(); // R2 URL for generated result
            $table->string('thumb_url')->nullable(); // R2 URL for thumbnail
            $table->string('fal_request_id')->nullable()->index(); // FAL.ai request ID for polling
            $table->string('fal_entity')->nullable(); // Entity used (e.g., FLUX_PRO, KLING_VIDEO)
            $table->json('fal_result')->nullable(); // Full FAL.ai result payload
            $table->text('error_message')->nullable(); // Error message if failed
            $table->decimal('credits_used', 10, 4)->default(0); // Credits deducted
            $table->integer('generation_time')->nullable(); // Time in seconds
            $table->string('resolution')->nullable(); // e.g., '1024x1024'
            $table->string('aspect_ratio')->nullable(); // e.g., '1:1', '16:9'
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pixury_creations');
    }
};