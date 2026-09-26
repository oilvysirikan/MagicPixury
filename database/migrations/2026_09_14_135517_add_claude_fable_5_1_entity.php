<?php

use App\Domains\Entity\Enums\EntityEnum;
use App\Domains\Entity\Models\Entity;
use App\Enums\StatusEnum;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $model = EntityEnum::CLAUDE_FABLE_5_1;

        Entity::query()->updateOrCreate(
            ['key' => $model->value],
            [
                'engine' => $model->engine(),
                'title'  => $model->label(),
                'status' => StatusEnum::ENABLED->value,
                'image'  => 'upload/enginelogo/claude_logo.svg',
            ]
        );
    }

    public function down(): void
    {
        Entity::query()
            ->where('key', EntityEnum::CLAUDE_FABLE_5_1->value)
            ->where('image', 'upload/enginelogo/claude_logo.svg')
            ->update(['image' => null]);
    }
};
