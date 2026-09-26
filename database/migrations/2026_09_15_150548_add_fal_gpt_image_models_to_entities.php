<?php

use App\Domains\Entity\Enums\EntityEnum;
use App\Domains\Entity\Models\Entity;
use App\Enums\AITokenType;
use App\Enums\StatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * @return array<int, EntityEnum>
     */
    private function models(): array
    {
        return [
            EntityEnum::FAL_GPT_IMAGE_2,
            EntityEnum::FAL_GPT_IMAGE_2_5_SUNBURST,
            EntityEnum::FAL_GPT_IMAGE_2_5_FLARE,
        ];
    }

    public function up(): void
    {
        foreach ($this->models() as $model) {
            $entity = Entity::query()->updateOrCreate(
                ['key' => $model->value],
                [
                    'engine' => $model->engine(),
                    'title'  => $model->label(),
                    'status' => StatusEnum::ENABLED->value,
                ]
            );

            $entity->tokens()->firstOrCreate([
                'type' => AITokenType::IMAGE->value,
            ]);
        }

        Cache::forget('entities');
    }

    public function down(): void
    {
        $models = collect($this->models());
        $storedModelKeys = $models->flatMap(
            static fn (EntityEnum $model): array => [$model->value, $model->slug()]
        );

        if ($storedModelKeys->contains(setting('fal_ai_default_model'))) {
            setting([
                'fal_ai_default_model' => EntityEnum::FLUX_PRO->slug(),
            ])->save();
        }

        Entity::query()
            ->whereIn('key', $models->map->value->all())
            ->delete();

        Cache::forget('entities');
    }
};
