<?php

namespace App\Jobs;

use App\Models\Activity;
use App\Models\WordPressSite;
use App\Services\WordPress\WordPressSetupService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class RunWordPressSetup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(
        public WordPressSite $site,
        public string $username,
        public string $encryptedPassword,
    ) {}

    public function handle(): void
    {
        // Never rethrow: the service persists the outcome and a throw here
        // would leave a copy of the credentials in the failed jobs table.
        try {
            $password = Crypt::decryptString($this->encryptedPassword);

            $service = app(WordPressSetupService::class, ['site' => $this->site]);
            $result = $service->setup($this->username, $password);

            if ($result->status === 'completed') {
                Activity::create([
                    'user_id' => $result->created_by,
                    'type' => 'wordpress_setup',
                    'description' => "Completed WordPress setup for {$result->site_url}",
                    'subject_id' => $result->id,
                    'subject_type' => WordPressSite::class,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('WordPress setup job failed for site '.$this->site->id.': '.$e->getMessage());
        }
    }
}
