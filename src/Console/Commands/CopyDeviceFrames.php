<?php

namespace Core\Developer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CopyDeviceFrames extends Command
{
    protected $signature = 'device-frames:copy {--force : Overwrite existing files}';

    protected $description = 'Copy device frame assets from source to public directory';

    public function handle(): int
    {
        $config = config('device-frames');
        $sourcePath = $config['source_path'];
        $publicPath = public_path($config['public_path']);

        if (! File::isDirectory($sourcePath)) {
            $this->error("Source directory not found: {$sourcePath}");

            return Command::FAILURE;
        }

        // Create destination directory
        if (! File::isDirectory($publicPath)) {
            File::makeDirectory($publicPath, 0755, true);
        }

        $copied = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($config['devices'] as $deviceSlug => $device) {
            $deviceDir = "{$publicPath}/{$deviceSlug}";

            if (! File::isDirectory($deviceDir)) {
                File::makeDirectory($deviceDir, 0755, true);
            }

            foreach ($device['variants'] as $variantSlug => $variant) {
                $extension = $device['format'];
                $sourceFile = "{$sourcePath}/{$device['path']}/{$variant['file']}.{$extension}";
                $destFile = "{$deviceDir}/{$variantSlug}.{$extension}";

                if (! File::exists($sourceFile)) {
                    $this->warn("Source not found: {$sourceFile}");
                    $failed++;

                    continue;
                }

                if (File::exists($destFile) && ! $this->option('force')) {
                    $this->line("<comment>Skipping:</comment> {$deviceSlug}/{$variantSlug}.{$extension}");
                    $skipped++;

                    continue;
                }

                File::copy($sourceFile, $destFile);
                $this->line("<info>Copied:</info> {$deviceSlug}/{$variantSlug}.{$extension}");
                $copied++;
            }
        }

        $this->newLine();
        $this->info("Done! Copied: {$copied}, Skipped: {$skipped}, Failed: {$failed}");

        return Command::SUCCESS;
    }
}
