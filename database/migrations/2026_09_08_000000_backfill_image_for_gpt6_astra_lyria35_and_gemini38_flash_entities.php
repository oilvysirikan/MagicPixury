<?php

use App\Domains\Entity\Enums\EntityEnum;
use App\Domains\Entity\Models\Entity;
use App\Enums\StatusEnum;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * @var array<int, array{model: EntityEnum, logo: string}>
     */
    private array $entities;

    public function __construct()
    {
        $gemini = 'upload/enginelogo/gemini_logo.svg';
        $openai = 'upload/enginelogo/openai.svg';

        $this->entities = [
            ['model' => EntityEnum::GPT_6_ASTRA, 'logo' => $openai],
            ['model' => EntityEnum::LYRIA_3_5, 'logo' => $gemini],
            ['model' => EntityEnum::GEMINI_3_8_FLASH, 'logo' => $gemini],
            ['model' => EntityEnum::GPT_IMAGE_2_5_SUNBURST, 'logo' => $openai],
            ['model' => EntityEnum::GPT_IMAGE_2_5_FLARE, 'logo' => $openai],
        ];
    }

    public function up(): void
    {
        foreach ($this->entities as $entity) {
            $model = $entity['model'];

            Entity::query()->updateOrCreate(
                ['key' => $model->value],
                [
                    'engine' => $model->engine(),
                    'title'  => $model->label(),
                    'status' => StatusEnum::ENABLED->value,
                    'image'  => $entity['logo'],
                ]
            );
        }
    }

    public function down(): void
    {
        foreach ($this->entities as $entity) {
            Entity::query()
                ->where('key', $entity['model']->value)
                ->where('image', $entity['logo'])
                ->update(['image' => null]);
        }
    }
};
