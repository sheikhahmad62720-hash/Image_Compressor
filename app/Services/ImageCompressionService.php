<?php

namespace App\Services;

use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use Intervention\Image\Drivers\Gd\Driver;

class ImageCompressionService
{
    public const TARGET_MAX_SIZE = 1 * 1024 * 1024; // 1 MB in bytes
    public const QUALITY_STEP = 5;
    public const MIN_QUALITY = 30;
    public const MAX_QUALITY = 95;

    /**
     * Compress an image to under 1 MB while preserving quality.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return array{compressed_path: string, original_size: int, compressed_size: int, compression_percent: int, dimensions: string, format: string}
     */
    public function compress($file)
    {
        $originalPath = $file->getRealPath();
        $originalName = $file->getClientOriginalName();
        $originalSize = $file->getSize();
        $originalMime = $file->getMimeType();

        // Validate format
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!in_array($originalMime, $allowedMime, true)) {
            throw new \InvalidArgumentException('Unsupported image format');
        }

        // Determine format and extension
        $format = $this->determineFormat($originalMime);
        $extension = $format === 'jpeg' ? 'jpg' : $format;

        // Get original dimensions
        $originalImage = Image::make($originalPath);
        $width = $originalImage->width();
        $height = $originalImage->height();

        // Try lossless optimization first
        $compressedPath = tempnam(sys_get_temp_dir(), 'img_comp_') . '.'.$extension;
        $compressedSize = $this->tryLosslessOptimization($originalPath, $compressedPath, $format);

        // If already under 1 MB, return
        if ($compressedSize <= self::TARGET_MAX_SIZE) {
            $dimensions = "{$width}x{$height}";
            return [
                'compressed_path' => $compressedPath,
                'original_size' => $originalSize,
                'compressed_size' => $compressedSize,
                'compression_percent' => round(100 - ($compressedSize * 100 / $originalSize)),
                'dimensions' => $dimensions,
                'format' => $extension,
            ];
        }

        // Gradually reduce quality if still over 1 MB
        $quality = self::MAX_QUALITY;
        while ($quality >= self::MIN_QUALITY) {
            $tempCompressed = tempnam(sys_get_temp_dir(), 'img_comp_') . '.'.$extension;
            $size = $this->compressWithQuality($originalPath, $tempCompressed, $quality, $format);

            if ($size <= self::TARGET_MAX_SIZE) {
                $compressedPath = $tempCompressed;
                $compressedSize = $size;
                break;
            }
            $quality -= self::QUALITY_STEP;
        }

        // If we've exhausted quality and still over 1 MB, do minimal resize as last resort
        if (!isset($compressedPath) || $compressedSize > self::TARGET_MAX_SIZE) {
            $compressedPath = $this->tryResizing($originalPath, $extension);
            $compressedSize = filesize($compressedPath);
        }

        // If still over 1 MB, note it
        $compressionPercent = round(100 - ($compressedSize * 100 / $originalSize));

        $dimensions = "{$width}x{$height}";

        return [
            'compressed_path' => $compressedPath,
            'original_size' => $originalSize,
            'compressed_size' => $compressedSize,
            'compression_percent' => $compressionPercent,
            'dimensions' => $dimensions,
            'format' => $extension,
        ];
    }

    /**
     * Try lossless optimization first.
     */
    protected function tryLosslessOptimization(string $source, string $destination, string $format): int
    {
        try {
            $image = Image::make($source);
            $image->optimize();
            $image->save($destination);
            return filesize($destination);
        } catch (\Exception $e) {
            // Fall back to saving without optimization
            $this->saveImage($source, $destination, $format, 100);
            return filesize($destination);
        }
    }

    /**
     * Compress image with specific quality.
     */
    protected function compressWithQuality(string $source, string $destination, int $quality, string $format): int
    {
        $this->saveImage($source, $destination, $format, $quality);
        return filesize($destination);
    }

    /**
     * Save image with specified quality.
     */
    protected function saveImage(string $source, string $destination, string $format, int $quality): void
    {
        $image = Image::make($source);

        // Apply format-specific settings
        if ($format === 'jpeg' || $format === 'jpg') {
            $image->encode('quality', $quality);
            $image->orientate(); // auto-orient based on EXIF
        }

        $image->save($destination);
    }

    /**
     * Determine image format from MIME type.
     */
    protected function determineFormat(string $mime): string
    {
        return match($mime) {
            'image/jpeg', 'image/jpg' => 'jpeg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpeg',
        };
    }

    /**
     * Try resizing as last resort to meet size target.
     */
    protected function tryResizing(string $source, string $extension): string
    {
        $image = Image::make($source);
        $width = $image->width();
        $height = $image->height();

        // Calculate new dimensions maintaining aspect ratio for 1MB target
        $targetRatio = 1.0; // square target for simplicity
        $imageRatio = $width / $height;

        if ($imageRatio > $targetRatio) {
            $newWidth = intval($height * $targetRatio);
            $newHeight = $height;
        } else {
            $newWidth = $width;
            $newHeight = intval($width / $targetRatio);
        }

        // Ensure minimum dimensions
        $newWidth = max($newWidth, 400);
        $newHeight = max($newHeight, 400);

        $image->resize($newWidth, $newHeight, fn ($constraint) => $constraint->aspectRatio());
        $image->save($destination);

        return $destination;
    }
}