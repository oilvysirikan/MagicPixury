<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class TestR2 extends Command
{
    protected $signature = 'app:test-r2';

    protected $description = 'Test Cloudflare R2 connection';

    public function handle()
    {
        $key = 'magicai-selftest/probe.txt';

        $this->info('Testing R2 upload...');

        try {
            $result = Storage::disk('r2')->put($key, 'r2-probe');

            $this->line('PUT: ' . ($result ? 'SUCCESS' : 'FAILED'));

            if (!$result) {
                $this->error('R2 returned false without exception.');
                $this->line('throw=false may be hiding the real error.');
                return self::FAILURE;
            }

            $url = Storage::disk('r2')->url($key);

            $this->info("URL: {$url}");

            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->error('PUT: EXCEPTION');
            $this->error(get_class($e));
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}