<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class GenerateUuidCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uuid:generate {--key=MY_UUID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a UUID and save it to the .env file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $uuid = (string) Str::uuid();
        // $key = $this->option('key');
        $key = 'APP_UUID';
        $path = base_path('.env');

        if (!File::exists($path)) {
            return $this->error('.env file not found.');
        }

        $content = File::get($path);

        // Check if the key already exists
        if (str_contains($content, "{$key}=")) {
            // Replace existing value
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$uuid}", $content);
        } else {
            // Append new value
            $content .= "\n{$key}={$uuid}";
        }

        File::put($path, $content);

        $this->info("Successfully set {$key} to: {$uuid}");
    }
}
