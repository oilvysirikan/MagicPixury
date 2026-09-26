# MAGICAI v11.20 ARCHITECTURE DISCOVERY REPORT
## For Pixury Creative Layer Integration

**Date**: 2025-09-25  
**Phase**: Pre-Phase 2 Planning  
**Status**: Complete - Ready for Implementation Planning

---

## Executive Summary

Pixury is **not a second AI platform** - it's a premium creative experience layer built on top of MagicAI's existing infrastructure. This report documents the complete MagicAI v11.20 architecture to enable maximum reuse and zero duplication.

**Key Principle**: `MagicAI Core + AI Infrastructure + CRM + Credits + R2 + Pixury Template Layer`

---

## A. AI VIDEO ARCHITECTURE

### Video Extensions (Marketplace Packages)
Dynamically loaded via `MarketplaceServiceProvider` when installed:

| Extension | ServiceProvider | Key Models |
|-----------|----------------|------------|
| **AiVideoPro** | `AiVideoProServiceProvider` | `UserFall` |
| **VideoAgent** | `VideoAgentServiceProvider` | `VideoAgentVideo` |
| **AutoEditVideos** | `AutoEditVideosServiceProvider` | `AutoEditRender` |
| **VideoEditor** | `VideoEditorServiceProvider` | `VideoEditorExportJob` |
| **VideoDubbing** | `VideoDubbingServiceProvider` | `VideoDubbing` |
| **AiCaptions** | `AiCaptionsServiceProvider` | `AiCaptionVideo` |
| **UGCCreator** | `UGCCreatorServiceProvider` | UGC Creator models |
| **UGCFactory** | `UGCFactoryServiceProvider` | UGC Factory models |
| **AiViralClips** | `AiViralClipsServiceProvider` | Viral clips models |

> **Note**: Models don't exist in source - created when extensions installed from marketplace.

### Entity Drivers (Video Generation)
`app/Domains/Entity/Drivers/`:

| Driver | Entity | Engine | Credit Model |
|--------|--------|--------|--------------|
| `HeygenVideoAgentDriver` | `HEYGEN_VIDEO_AGENT` | HeyGen | 3.0 credits/sec |
| `HeygenVideoV3Driver` | `HEYGEN_VIDEO_V3` | HeyGen | 5.0 credits/sec |
| `VideoDubbingDriver` | `VIDEO_DUBBING` | Custom | 1.0 credits/sec |
| `RendleyAutoEditDriver` | Auto-edit | Rendley | Per second |

### AI Provider Services (Fal.ai)
`app/Domains/Engine/Services/FalAIService.php` - **Primary image/video generation service**

**Endpoints:**
- **Sync**: `https://fal.run/{model}` (Grok, fast models)
- **Async**: `https://queue.fal.run/fal-ai/{model}` (Flux, Kling, Luma, Veo, Minimax)
- **Check**: `https://queue.fal.run/fal-ai/{model}/requests/{request_id}`

**Supported Video Models:**
- Kling v1.6/v2.1, Luma Dream Machine, Veo 2, Minimax, Haiper
- Ideogram, Nano Banana, Flux Pro/Kontext, Seedream 4

### Generation Flow
```
User Request → Controller → Entity Driver → FalAIService::generate()
    → Returns request_id → Poll via FalAIService::check()
    → Result stored → Credit deducted via HasCreditLimit::decreaseCredit()
```

### Database Tables (when extensions installed)
- `user_falls` (AiVideoPro)
- `video_agent_videos` (VideoAgent)
- `auto_edit_renders` (AutoEditVideos)
- `video_editor_export_jobs` (VideoEditor)
- `video_dubbings` (VideoDubbing)
- `ai_caption_videos` (AiCaptions)

---

## B. CRM ARCHITECTURE

### Core Models (`app/Models/`)

| Model | Table | Key Fields |
|-------|-------|------------|
| **Company** | `companies` | name, industry, description, website, logo, brand_color, user_id |
| **Product** | `products` | name, type, description, key_features, user_id, company_id |

### Relationships
```
User (1) → (N) Company
Company (1) → (N) Product
User (1) → (N) Product
```

### CRM Extension (Marketplace)
- `CrmServiceProvider` registered in MarketplaceServiceProvider
- Full CRM: Deals, Pipeline, Invoices, Payments, Tasks, Reports
- AI CRM Assistant via `AIAgentToolCrm` extension

### Pixury Integration
```php
// Get user's companies
$companies = Company::getMyCompanies();

// Get products for a company
$products = $company->products;
```

---

## C. CREDIT / BILLING SYSTEM

### Credit Types
1. **Entity Credits** (`entity_credits` JSON on User/Team) - Per-engine/entity
2. **Shared Credits** (`shared_credits` float) - Unified pool for shared-credit plans

### Key Files

| File | Purpose |
|------|---------|
| `Plan.php` | Plan definitions with `ai_models` JSON (engine→entity→credit) |
| `HasCredit.php` (trait) | User credit accessors |
| `HasCreditLimit.php` (trait) | Driver-level credit checking/deduction |
| `SharedCreditService.php` | Shared credit pool management |
| `SharedCreditTransaction.php` | Transaction log |
| `UserUsageCredit.php` | Per-generation usage log |

### Credit Deduction Flow
```php
// In Entity Driver (BaseDriver::decreaseCredit())
$driver->calculateCredit();           // Calculate cost
$driver->hasCreditBalance();          // Check balance
$driver->decreaseCredit($value);      // Deduct & log

// Creates UserUsageCredit record:
UserUsageCredit::create([
    'user_id' => $user->id,
    'model_key' => $entity->slug(),
    'credit' => $value,
    'unit_price' => $entity->unitPrice(),
    'total' => $value * $unitPrice,
]);

// Updates total_spend setting
setting(['total_spend' => $previous + $current])->save();
```

### Plan Credit Configuration
```php
// Plan::ai_models structure:
[
    'openai' => [
        'gpt-4o' => ['credit' => 0.01, 'isUnlimited' => false],
        'gpt-4o-mini' => ['credit' => 0.001, 'isUnlimited' => true],
    ],
    'fal' => [
        'flux-pro' => ['credit' => 0.05, 'isUnlimited' => false],
        'kling-video' => ['credit' => 2.0, 'isUnlimited' => false],
    ],
]
```

### Pixury Credit Mapping

| Pixury Operation | Engine | Entity | Est. Credits |
|------------------|--------|--------|--------------|
| Image Generation (Flux) | `fal` | `FLUX_PRO` | 0.05-0.10 |
| Image Generation (Nano Banana) | `fal` | `NANO_BANANA` | 0.05-0.10 |
| Video Generation (Kling) | `fal` | `KLING_VIDEO` | 2.0-5.0/sec |
| Video Generation (Veo) | `fal` | `VEO_3_1_TEXT_TO_VIDEO` | 2.0-200.0 |

---

## D. R2 / STORAGE ARCHITECTURE

### Filesystem Disks (`config/filesystems.php`)

| Disk | Driver | Purpose |
|------|--------|---------|
| `local` | local | `storage/app` |
| `public` | local | `public/uploads` (URL: `APP_URL/public`) |
| `uploads` | local | `public/uploads` |
| `thumbs` | local | `public/uploads/thumbnail/default` |
| `themes` | local | `public/themes` |
| `s3` | s3 | AWS S3 |
| `r2` | s3 | **Cloudflare R2** |

### R2 Configuration
```php
'r2' => [
    'driver' => 's3',
    'key' => env('CLOUDFLARE_R2_ACCESS_KEY_ID'),
    'secret' => env('CLOUDFLARE_R2_SECRET_ACCESS_KEY'),
    'region' => env('CLOUDFLARE_R2_DEFAULT_REGION', 'us-east-1'),
    'bucket' => env('CLOUDFLARE_R2_BUCKET'),
    'url' => env('CLOUDFLARE_R2_URL'),
    'endpoint' => env('CLOUDFLARE_R2_ENDPOINT'),
    'use_path_style_endpoint' => env('CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT', false),
]
```

### Current Usage in Codebase
```php
// Image generation stores to 'uploads' (public disk)
Storage::disk('uploads')->put($imageName, $data);

// FalAIService uploads reference images to 'public' disk
$image->store('falai', 'public');
```

### Pixury Storage Strategy

| Asset Type | Disk | Path Pattern |
|------------|------|--------------|
| Original product images | `uploads` (or `r2`) | `pixury/products/{user_id}/{uuid}.{ext}` |
| Generated images | `r2` | `pixury/generations/{user_id}/{uuid}.{ext}` |
| Generated videos | `r2` | `pixury/videos/{user_id}/{uuid}.mp4` |
| Thumbnails | `thumbs` | `pixury/thumbs/{user_id}/{uuid}.webp` |
| Temp files | `local` | `pixury/temp/{uuid}` |

---

## E. QUEUE / JOB SYSTEM

### Current Jobs
- **Jobs**: `app/Jobs/` (emails, payment processing)
- **Voice**: `ElevenlabVoiceCreateJob.php`
- **No video generation jobs in core** - extensions handle their own queuing

### Pixury Job Implementation (NEW NEEDED)
```php
// app/Jobs/PixuryGenerationJob.php
class PixuryGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function __construct(
        public int $userId,
        public string $templateId,
        public string $prompt,
        public string $productImagePath,
        public array $options = [],
        public string $type = 'image'
    ) {}
    
    public function handle(): void
    {
        // 1. Check credits
        // 2. Call FalAIService::generate()
        // 3. Poll FalAIService::check()
        // 4. Download result → Store on R2
        // 5. Create Creation record
        // 6. Deduct credits
        // 7. Notify user
    }
}
```

---

## F. EXISTING AI INFLUENCER CAPABILITIES

### Available Extensions

| Extension | ServiceProvider | Capability |
|-----------|----------------|------------|
| **InfluencerAvatar** | `InfluencerAvatarServiceProvider` | Digital twin/avatar creation |
| **AiPersona** | `AiPersonaServiceProvider` | AI persona/character |
| **AiAvatar** | `AiAvatarServiceProvider` | Avatar generation |
| **UGCCreator** | `UGCCreatorServiceProvider` | UGC with realistic actors |

### Entity Drivers
- `HeygenVideoAgentDriver` - HeyGen talking avatars
- `HeygenVideoV3Driver` - HeyGen video generation
- `SynthesiaDriver` - Synthesia avatars
- `CreatifyDriver` - Creatify video ads

### Pixury Implication
**DO NOT BUILD NEW INFLUENCER ENGINE** - Reuse:
- `InfluencerAvatar` for digital twins
- `HeygenVideoAgentDriver` for talking head videos
- `UGCCreator` for UGC-style content
- `AiPersona` for character consistency

---

## G. PIXURY INTEGRATION MAP

| # | Pixury Feature | MagicAI System | Action | Files Involved |
|---|---|---|---|---|
| 1 | **Product Upload** | Storage (R2/uploads) | REUSE | `FalAIService::createImageUrl()`, `Storage::disk('r2')` |
| 2 | **Template** | Pixury (NEW) | NEW | `TemplateRepository`, `TemplateService`, 4 blade views |
| 3 | **Prompt** | Entity Driver / FalAIService | REUSE | `BaseDriver::calculate()`, `FalAIService::generate()` |
| 4 | **Image Generation** | FalAIService + Entity System | REUSE + EXTEND | `FalAIService::generate()`, `Entity::driver('FLUX_PRO')` |
| 5 | **Video Generation** | FalAIService + Video Extensions | REUSE | `FalAIService::klingGenerate()`, `HeygenVideoAgentDriver` |
| 6 | **Video Editing** | AutoEditVideos / VideoEditor | REUSE (when installed) | `AutoEditVideosServiceProvider`, `VideoEditorServiceProvider` |
| 7 | **Generation Job** | Queue System | NEW | `PixuryGenerationJob` (new), `queue:work` |
| 8 | **Credits** | Entity Credit System | REUSE | `HasCreditLimit::decreaseCredit()`, `UserUsageCredit` |
| 9 | **R2 Storage** | Filesystem (R2 disk) | REUSE | `config/filesystems.php`, `Storage::disk('r2')` |
| 10 | **My Creations** | Pixury + Media Manager | EXTEND | `MediaManagerModal`, new `Creation` model |
| 11 | **CRM Data** | Company/Product models | REUSE | `Company::getMyCompanies()`, `Product` model |
| 12 | **AI Influencer** | InfluencerAvatar / AiPersona | REUSE | `InfluencerAvatarServiceProvider`, `HeygenVideoAgentDriver` |
| 13 | **AI Presentation** | AiPresentation extension | REUSE | `AiPresentationServiceProvider` |

---

## H. ARCHITECTURE RULES FOR PIXURY PHASE 2

### DO ✅
- Use `Entity::driver(EntityEnum::FLUX_PRO)->forUser($user)->generate($prompt)`
- Use `FalAIService::generate()` for async FAL.ai calls
- Use `HasCreditLimit::decreaseCredit()` for credit deduction
- Store assets on `Storage::disk('r2')` with `pixury/` prefix
- Dispatch `PixuryGenerationJob` for async processing
- Read CRM data via `Company` / `Product` models
- Reuse `InfluencerAvatar` for avatar/talking-head features

### DO NOT ❌
- Create new credit tables or deduction logic
- Create new storage abstraction
- Build new video generation from scratch
- Build new influencer/avatar system
- Create separate queue workers
- Duplicate Plan/credit configuration

---

## I. PHASE 2 IMPLEMENTATION PRIORITIES

### Phase 2A - Core Generation (Week 1-2)
1. Create `PixuryGenerationJob` with async FAL.ai polling
2. Create `Creation` model + migration (polymorphic: image/video)
3. Wire `PixuryController::create()` → dispatch job → poll status
4. Integrate credit deduction via existing Entity system

### Phase 2B - Template Engine (Week 2-3)
1. Convert template configs to FAL.ai parameters
2. Support image-to-image (product photo → styled)
3. Add aspect ratio / resolution mapping
4. Template preview generation

### Phase 2C - Video Generation (Week 3-4)
1. Integrate `Kling`, `Luma`, `Veo` via FalAIService
2. Reuse `HeygenVideoAgentDriver` for talking avatars
3. Add video-to-video (AutoEditVideos when installed)

### Phase 2D - CRM Integration (Week 4)
1. Product picker modal in create view
2. Auto-populate prompt from product data
3. Brand kit integration (colors, logo, guidelines)

---

## J. CONFIGURATION CHECKLIST FOR PRODUCTION

```env
# R2 (Required for production)
CLOUDFLARE_R2_ACCESS_KEY_ID=xxx
CLOUDFLARE_R2_SECRET_ACCESS_KEY=xxx
CLOUDFLARE_R2_BUCKET=magicai-pixury
CLOUDFLARE_R2_ENDPOINT=https://<account>.r2.cloudflarestorage.com
CLOUDFLARE_R2_URL=https://pub-<bucket>.r2.dev
CLOUDFLARE_R2_DEFAULT_REGION=us-east-1
CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT=true

# FAL.ai (Required)
FAL_AI_KEY=xxx

# Queue (Required for async)
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Credits
FILESYSTEM_DISK=r2
```

---

## Appendix: Phase 1 Deliverables (Complete)

### Files Created
- `app/Pixury/Templates/TemplateRepository.php` - 10 seed templates, 3 categories
- `app/Pixury/Templates/TemplateService.php` - Business logic
- `app/Pixury/Creative/GenerationService.php` - Placeholder for Phase 2
- `app/Pixury/PixuryServiceProvider.php` - Route/view registration
- `app/Http/Controllers/Pixury/PixuryController.php` - 4 actions
- `routes/pixury.php` - 4 routes under `dashboard/pixury`
- `resources/views/default/pixury/` - 4 blade views (index, templates, create, creations)

### Config Modified
- `config/app.php` - Added `App\Pixury\PixuryServiceProvider::class`

### Verification
```
php artisan optimize:clear  ✅
php artisan config:cache    ✅
php artisan route:cache     ✅
php artisan view:cache      ✅
Route lookup: 4 routes ✅
View lookup: 4 views ✅
Template service: 10 templates, 3 categories ✅
```

---

**END OF REPORT**