<?php

namespace App\Domains\Shared\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ImageService
{
    /**
     * Process an uploaded image or a path on a disk and generate responsive variants.
     *
     * @param string $disk      Storage disk, e.g. 'public'
     * @param string $folder    Base folder, e.g. 'news/2025/08'
     * @param UploadedFile|string $file UploadedFile or a path relative to the given disk
     * @param int[] $widths     List of widths to generate (jpg + webp for each width)
     * @param string|null $finalExtension  If set, force original extension (e.g. 'jpg'); otherwise keep source ext
     * @return string            Relative path of the original saved file within the disk
     */
    public function process(string $disk, string $folder, UploadedFile|string $file, array $widths = [400, 800], ?string $finalExtension = null): string
    {
        [$sourcePath, $origFilename, $origExt] = $this->resolveSource($disk, $file);
        $ext = strtolower($finalExtension ?: $origExt ?: 'jpg');

        // Ensure base folder exists
        if (!Storage::disk($disk)->exists($folder)) {
            Storage::disk($disk)->makeDirectory($folder);
        }

        // Save or copy original
        $originalPath = $folder . '/' . $origFilename . '.' . $ext;
        if ($file instanceof UploadedFile) {
            Storage::disk($disk)->putFileAs($folder, $file, $origFilename . '.' . $ext);
        } else {
            if (!Storage::disk($disk)->exists($originalPath)) {
                Storage::disk($disk)->copy($file, $originalPath);
            }
        }

        // Generate variants
        $this->generateVariants($disk, $folder, $origFilename, $sourcePath, $widths);

        return $originalPath;
    }

    /**
     * Delete original and variants for a given original relative path.
     */
    public function deleteWithVariants(string $disk, ?string $originalPath): void
    {
        if (empty($originalPath)) {
            return;
        }

        if (Storage::disk($disk)->exists($originalPath)) {
            Storage::disk($disk)->delete($originalPath);
        }

        $dir = pathinfo($originalPath, PATHINFO_DIRNAME);
        $name = pathinfo($originalPath, PATHINFO_FILENAME);

        // List files in directory and delete ones matching the variant pattern
        $files = Storage::disk($disk)->files($dir);
        foreach ($files as $f) {
            $base = pathinfo($f, PATHINFO_BASENAME);
            // Match name-<width>w.<ext> where ext is jpg|jpeg|png|webp
            if (preg_match('/^' . preg_quote($name, '/') . '-\\d+w\.(jpg|jpeg|png|webp)$/i', $base)) {
                Storage::disk($disk)->delete($f);
            }
        }
    }

    /**
     * @return array{0:string,1:string,2:string|null} [$sourcePath, $filename, $ext]
     */
    protected function resolveSource(string $disk, UploadedFile|string $file): array
    {
        if ($file instanceof UploadedFile) {
            $sourcePath = $file->getRealPath();
            $ext = strtolower($file->getClientOriginalExtension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION) ?: 'jpg');
            $filename = pathinfo($file->hashName(), PATHINFO_FILENAME);
            return [$sourcePath, $filename, $ext];
        }
        // $file is a path relative to the disk
        $sourcePath = Storage::disk($disk)->path($file);
        $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'jpg');
        $filename = pathinfo(basename($sourcePath), PATHINFO_FILENAME);
        return [$sourcePath, $filename, $ext];
    }

    protected function generateVariants(string $disk, string $folder, string $filename, string $sourcePath, array $widths): void
    {
        foreach ($widths as $width) {
            $img = Image::read($sourcePath)->scale(width: (int) $width);
            $jpgPath = sprintf('%s/%s-%dw.jpg', $folder, $filename, $width);
            Storage::disk($disk)->put($jpgPath, (string) $img->encodeByExtension('jpg', quality: 82));

            $webpPath = sprintf('%s/%s-%dw.webp', $folder, $filename, $width);
            Storage::disk($disk)->put($webpPath, (string) $img->encodeByExtension('webp', quality: 82));
        }
    }
}
