<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores an uploaded image to the public disk, compressing/resizing it via
 * ImageMagick when available so uploads stay small and web-ready. Falls back to
 * storing the original bytes if ImageMagick is missing or fails.
 */
class ImageCompressor
{
    public static function store(UploadedFile $file, string $directory = 'uploads', int $maxDimension = 1920, int $quality = 90): string
    {
        $disk = Storage::disk('public');

        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $ext = 'jpg';
        }

        // Preserve original filename, replacing spaces with hyphens
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $originalName = preg_replace('/\s+/', '-', $originalName);
        $originalName = preg_replace('/[^a-zA-Z0-9\-_]/', '', $originalName);
        if (empty($originalName)) {
            $originalName = 'image';
        }

        // Preserve animated GIFs untouched.
        if ($ext === 'gif') {
            $gifFilename = $directory . '/' . $originalName . '.gif';
            $counter = 1;
            while ($disk->exists($gifFilename)) {
                $gifFilename = $directory . '/' . $originalName . '-' . $counter . '.gif';
                $counter++;
            }
            $file->storeAs($directory, basename($gifFilename), 'public');
            return $gifFilename;
        }

        if (! is_dir($disk->path($directory))) {
            @mkdir($disk->path($directory), 0755, true);
        }

        $filename = $directory . '/' . $originalName . '.' . $ext;
        // Ensure unique filename
        $counter = 1;
        while ($disk->exists($filename)) {
            $filename = $directory . '/' . $originalName . '-' . $counter . '.' . $ext;
            $counter++;
        }
        $output = $disk->path($filename);

        $input = $file->getRealPath();
        $cmd = sprintf(
            'magick %s -auto-orient -resize %dx%d> -strip -quality %d %s 2>&1',
            escapeshellarg($input),
            $maxDimension,
            $maxDimension,
            $quality,
            escapeshellarg($output)
        );

        exec($cmd, $out, $code);

        if ($code === 0 && is_file($output) && filesize($output) > 0) {
            return $filename;
        }

        // Fallback: store the original upload untouched.
        @unlink($output);

        $fallbackFilename = $directory . '/' . $originalName . '.' . $ext;
        $counter = 1;
        while ($disk->exists($fallbackFilename)) {
            $fallbackFilename = $directory . '/' . $originalName . '-' . $counter . '.' . $ext;
            $counter++;
        }
        $file->storeAs($directory, basename($fallbackFilename), 'public');
        return $fallbackFilename;
    }
}
