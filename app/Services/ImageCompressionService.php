<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Exceptions\NotSupportedException;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\ImageManager;
use InvalidArgumentException;

class ImageCompressionService
{
    public const TARGET_MAX_SIZE = 1 * 1024 * 1024; // 1 MB

    public const QUALITY_STEPS = [95, 90, 85, 80, 75, 70, 65, 60, 55, 50, 45, 40, 35, 30];

    public const MIN_DIMENSION = 320;

    protected ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Compress an uploaded image to under 1 MB while preserving as much
     * quality as possible. The original file is never modified.
     *
     * @return array{
     *     data: string,
     *     original_size: int,
     *     compressed_size: int,
     *     compression_percent: int,
     *     dimensions: string,
     *     format: string,
     *     reduced: bool
     * }
     */
    public function compress(UploadedFile $file): array
    {
        $originalSize = $file->getSize();
        $format = $this->formatFromMime($file->getMimeType() ?? $file->guessExtension());

        $source = $file->getRealPath();

        try {
            $image = $this->manager->decodePath($source);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException('The uploaded file is not a valid image or is corrupted.');
        }

        $width = $image->width();
        $height = $image->height();

        // Apply EXIF orientation so previews look correct.
        try {
            $image->orient();
        } catch (\Throwable $e) {
            // Best effort; ignore orientation failures.
        }

        // Edge case: already at or below the 1 MB target.
        if ($originalSize <= self::TARGET_MAX_SIZE) {
            return [
                'data' => (string) $this->encode($source, $format, 95),
                'original_size' => $originalSize,
                'compressed_size' => $originalSize,
                'compression_percent' => 0,
                'dimensions' => "{$width}x{$height}",
                'format' => $format,
                'reduced' => false,
            ];
        }

        // Pass 1: high quality / lossless-style optimization.
        $candidate = $this->encode($source, $format, self::QUALITY_STEPS[0]);
        $size = strlen($candidate);

        // Pass 2: gradually walk down in quality until we fit the target.
        if ($size > self::TARGET_MAX_SIZE) {
            $candidate = $this->walkQuality($source, $format, $width, $height);
            $size = strlen($candidate);
        }

        // Pass 3: last resort - a modest, proportional downscale keeps quality
        // acceptable while still slashing the byte count.
        if ($size > self::TARGET_MAX_SIZE) {
            [$width, $height] = $this->scaledDimensions($width, $height);
            $candidate = $this->walkQuality($source, $format, $width, $height);
            $size = strlen($candidate);
        }

        $compressionPercent = $originalSize > 0
            ? (int) round(100 - ($size * 100 / $originalSize))
            : 0;

        return [
            'data' => $candidate,
            'original_size' => $originalSize,
            'compressed_size' => $size,
            'compression_percent' => max(0, $compressionPercent),
            'dimensions' => "{$width}x{$height}",
            'format' => $format,
            'reduced' => true,
        ];
    }

    /**
     * Re-encode the source at a series of decreasing quality levels and keep
     * the first result that fits the target size. If none fit, the smallest
     * result is returned.
     */
    protected function walkQuality(string $source, string $format, int $width, int $height): string
    {
        $best = '';
        $bestSize = PHP_INT_MAX;

        foreach (self::QUALITY_STEPS as $quality) {
            // Re-read from source each time so we never cascade quality loss.
            $image = $this->manager->decodePath($source);

            if ($width !== $image->width() || $height !== $image->height()) {
                $image->scale($width, $height);
            }

            $candidate = $this->encode($image, $format, $quality);
            $size = strlen($candidate);

            if ($size < $bestSize) {
                $best = $candidate;
                $bestSize = $size;
            }

            if ($size <= self::TARGET_MAX_SIZE) {
                return $candidate;
            }
        }

        return $best;
    }

    /**
     * Encode an image (or path) to the target format.
     *
     * @param  \Intervention\Image\Interfaces\ImageInterface|string  $image
     */
    protected function encode($image, string $format, int $quality): string
    {
        if (is_string($image)) {
            $image = $this->manager->decodePath($image);
        }

        $encoded = match ($format) {
            'webp' => $image->encode(new WebpEncoder(quality: $quality)),
            'png' => $image->encode(new PngEncoder()),
            default => $image->encode(new JpegEncoder(quality: $quality)),
        };

        return $encoded->toString();
    }

    /**
     * Compute a modestly downscaled dimension pair that keeps aspect ratio
     * and never goes below a usable minimum size.
     *
     * @return array{int, int}
     */
    protected function scaledDimensions(int $width, int $height): array
    {
        $scale = 0.8;

        $nextWidth = (int) max(round($width * $scale), self::MIN_DIMENSION);
        $nextHeight = (int) max(round($height * $scale), self::MIN_DIMENSION);

        if ($width > $height) {
            $nextHeight = (int) max(round($nextWidth * ($height / $width)), self::MIN_DIMENSION);
        } else {
            $nextWidth = (int) max(round($nextHeight * ($width / $height)), self::MIN_DIMENSION);
        }

        return [$nextWidth, $nextHeight];
    }

    protected function formatFromMime(?string $mime): string
    {
        return match (strtolower((string) $mime)) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/jpeg', 'image/jpg' => 'jpg',
            default => throw new InvalidArgumentException('Unsupported image format. Please upload a JPG, PNG, or WebP file.'),
        };
    }
}