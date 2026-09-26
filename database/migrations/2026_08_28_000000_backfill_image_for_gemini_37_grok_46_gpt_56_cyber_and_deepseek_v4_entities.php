<?php

use App\Domains\Entity\Enums\EntityEnum;
use App\Domains\Entity\Models\Entity;
use App\Enums\StatusEnum;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * EntitySeeder creates these rows with a null image, and it may run either side
     * of this migration, so create-or-update rather than relying on the row existing.
     *
     * @var array<int, array{model: EntityEnum, logo: string}>
     */
    private array $entities;

    /**
     * @var array<int, string>
     */
    private array $retiredDeepseekModels = ['deepseek-chat', 'deepseek-reasoner'];

    public function __construct()
    {
        $gemini = 'upload/enginelogo/gemini_logo.svg';
        $grok = 'upload/enginelogo/Grok_logo.svg';
        $openai = 'upload/enginelogo/openai.svg';
        $deepseek = 'upload/enginelogo/deepseek_logo.svg';

        $this->entities = [
            ['model' => EntityEnum::GEMINI_3_7_FLASH, 'logo' => $gemini],
            ['model' => EntityEnum::GROK_4_6, 'logo' => $grok],
            ['model' => EntityEnum::GPT_5_6_CYBER, 'logo' => $openai],
            ['model' => EntityEnum::DEEPSEEK_V4_FLASH, 'logo' => $deepseek],
            ['model' => EntityEnum::DEEPSEEK_V4_PRO, 'logo' => $deepseek],
            ['model' => EntityEnum::DEEPSEEK_V4_FLASH_VISION_EXP, 'logo' => $deepseek],
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

        $this->repointRetiredDeepseekDefault(EntityEnum::DEEPSEEK_V4_PRO->slug());
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

    /**
     * DeepSeek retired deepseek-chat and deepseek-reasoner on Jul 24, 2026. Installs
     * still storing one of them would keep calling a dead model id, so move them onto
     * the V4 flagship. Intentionally not reversed in down().
     */
    private function repointRetiredDeepseekDefault(string $replacement): void
    {
        if (! Schema::hasColumn('settings', 'deepseek_default_model')) {
            return;
        }

        Setting::query()
            ->whereIn('deepseek_default_model', $this->retiredDeepseekModels)
            ->update(['deepseek_default_model' => $replacement]);
    }
};
