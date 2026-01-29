<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class GenerateProductImageCache extends Command
{
    protected $signature = 'images:generate-cache';
    protected $description = 'Pre-generate WebP image cache for all product images';

    public function handle()
    {
        ini_set('memory_limit', '1G');
        set_time_limit(0);

        $this->info('Starting image cache generation...');

        // Get all product images
        $images = DB::table('product_images')
            ->orderBy('id')
            ->get();

        $total = $images->count();
        $this->info("Found {$total} images to process");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $generated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($images as $image) {
            try {
                // Generate thumbnail (80x80)
                $thumbnail = $this->generateOptimizedImage($image->path, 80, 80, 'webp');
                if ($thumbnail === 'generated') {
                    $generated++;
                } elseif ($thumbnail === 'exists') {
                    $skipped++;
                }

                // Generate large (800x800)
                $large = $this->generateOptimizedImage($image->path, 800, 800, 'webp');
                if ($large === 'generated') {
                    $generated++;
                } elseif ($large === 'exists') {
                    $skipped++;
                }

            } catch (\Exception $e) {
                $errors++;
                $this->error("\nError processing {$image->path}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Cache generation complete!");
        $this->info("Generated: {$generated} images");
        $this->info("Skipped (already cached): {$skipped} images");
        if ($errors > 0) {
            $this->warn("Errors: {$errors} images");
        }

        return 0;
    }

    private function generateOptimizedImage($originalPath, $width, $height, $format)
    {
        if (!$originalPath) {
            return 'skip';
        }

        $pathInfo = pathinfo($originalPath);
        $filename = $pathInfo['filename'];
        $directory = $pathInfo['dirname'];

        $cachedFilename = "{$filename}_{$width}x{$height}.{$format}";
        $cachedPath = "cache/{$directory}/{$cachedFilename}";

        // Check if already cached
        if (Storage::disk('public')->exists($cachedPath)) {
            return 'exists';
        }

        // Check if original exists
        if (!Storage::disk('public')->exists($originalPath)) {
            return 'skip';
        }

        try {
            $fullPath = Storage::disk('public')->path($originalPath);
            $image = Image::make($fullPath);

            // Resize and crop from top to preserve upper part of image
            $image->resize($width, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            
            if ($image->height() > $height) {
                $image->crop($width, $height, 0, 0);
            }

            if ($format === 'webp') {
                $image->encode('webp', 90);
            } elseif ($format === 'jpg' || $format === 'jpeg') {
                $image->encode('jpg', 90);
            } elseif ($format === 'png') {
                $image->encode('png');
            }

            $cachedFullPath = Storage::disk('public')->path($cachedPath);
            $cachedDirectory = dirname($cachedFullPath);

            if (!file_exists($cachedDirectory)) {
                mkdir($cachedDirectory, 0755, true);
            }

            $image->save($cachedFullPath);

            return 'generated';
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
