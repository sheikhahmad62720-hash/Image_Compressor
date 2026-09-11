<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use InvalidArgumentException;

class ImageCompressionService
{
    public const DEFAULT_TARGET_MAX_SIZE = 1 * 1024 * 1024; // 1 MB default

    public const MIN_TARGET_SIZE = 50 * 1024;     // 50 KB
    public const MAX_TARGET_SIZE = 20 * 1024 * 1024; // 20 MB

    public const QUALITY_STEPS = [95, 90, 85, 80, 75, 70, 65, 60, 55, 50, 45];

    public const MAX_LONG_EDGE = 4096;

    public const MIN_LONG_EDGE = 256;

    protected ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Compress an uploaded image to fit the requested target size while
     * preserving as much quality as possible. The original file is never
     * modified.
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
    public function compress(UploadedFile $file, ?int $targetSize = null): array
    {
        // Keep the target within sensible bounds.
        $targetSize = $targetSize === null
            ? self::DEFAULT_TARGET_MAX_SIZE
            : max(self::MIN_TARGET_SIZE, min($targetSize, self::MAX_TARGET_SIZE));

        // Image decoding/encoding is memory-hungry (a 24MP photo alone needs
        // ~190MB with the encode clone); raise the ceiling for this request.
        $currentLimit = (int) ini_get('memory_limit');
        if ($currentLimit > 0 && $currentLimit < 512) {
            ini_set('memory_limit', '512M');
        }

        // Large photos can easily need more than the default 30s budget.
        @set_time_limit(300);

        $originalSize = $file->getSize();
        $format = $this->formatFromMime($file->getMimeType() ?? $file->guessExtension());

        $source = $file->getRealPath();

        try {
            // Decode exactly once. The decoded buffer is reused across every
            // quality step, so memory stays flat (crucial under a 128M limit).
            $image = $this->manager->decodePath($source);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException('The uploaded file is not a valid image or is corrupted.');
        }

        try {
            // Apply EXIF orientation so previews look correct.
            $image->orient();
        } catch (\Throwable $e) {
            // Best effort; ignore orientation failures.
        }

        $width = $image->width();
        $height = $image->height();

        // Cap absurdly large dimensions before encoding so a giant photo can
        // never exhaust memory or balloon processing time.
        if ($width > self::MAX_LONG_EDGE || $height > self::MAX_LONG_EDGE) {
            [$width, $height] = $this->boxScaling($width, $height, self::MAX_LONG_EDGE);
            $image->scale($width, $height);
        }

        // Edge case: already at or below the target size.
        if ($originalSize <= $targetSize) {
            return [
                'data' => (string) $this->encode($image, $format, 95),
                'original_size' => $originalSize,
                'compressed_size' => $originalSize,
                'compression_percent' => 0,
                'dimensions' => "{$width}x{$height}",
                'format' => $format,
                'reduced' => false,
            ];
        }

        // Pass 1: high quality / lossless-style optimization.
        $candidate = $this->encode($image, $format, self::QUALITY_STEPS[0]);
        $size = strlen($candidate);

        // Pass 2: gradually walk down in quality until we fit the target.
        if ($size > $targetSize) {
            $candidate = $this->walkQuality($image, $format, $targetSize);
            $size = strlen($candidate);
        }

        // Pass 3: last resort - progressively downscale until the file fits.
        if ($size > $targetSize) {
            [$width, $height, $candidate] = $this->downscaleToFit($source, $format, $width, $height, $targetSize);
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
     * Re-encode the already-decoded image at a series of decreasing quality
     * levels and keep the first result that fits the target size. If none
     * fit, the smallest result is returned. No re-decoding is performed.
     */
    protected function walkQuality(ImageInterface $image, string $format, int $targetSize): string
    {
        // PNG encoding is lossless and quality-independent, so a single
        // encode yields the smallest result; looping would only repeat the
        // same expensive work 11 times.
        if ($format === 'png') {
            return $this->encode($image, $format, 0);
        }

        $best = '';
        $bestSize = PHP_INT_MAX;

        foreach (self::QUALITY_STEPS as $quality) {
            $candidate = $this->encode($image, $format, $quality);
            $size = strlen($candidate);

            if ($size < $bestSize) {
                $best = $candidate;
                $bestSize = $size;
            }

            if ($size <= $targetSize) {
                return $candidate;
            }
        }

        return $best;
    }

    /**
     * Repeatedly downscale the source by 0.85 (each time re-encoding from the
     * original file) until the result fits under the target size or we hit a
     * usable minimum dimension. Returns the final dimensions and encoded data.
     *
     * @return array{int, int, string}
     */
    protected function downscaleToFit(string $source, string $format, int $width, int $height): array
    {
        $best = '';
        $bestSize = PHP_INT_MAX;

        for (;;) {
            if ($width <= self::MIN_LONG_EDGE || $height <= self::MIN_LONG_EDGE) {
                break;
            }

            [$nextWidth, $nextHeight] = $this->scaledDimensions($width, $height);

            // No further progress possible; stop to avoid an infinite loop.
            if ($nextWidth === $width && $nextHeight === $height) {
                break;
            }

            [$width, $height] = [$nextWidth, $nextHeight];

            $image = $this->manager->decodePath($source);
            $image->scale($width, $height);

            $candidate = $this->walkQuality($image, $format);
            $size = strlen($candidate);

            if ($size < $bestSize) {
                $best = $candidate;
                $bestSize = $size;
            }

            if ($size <= self::TARGET_MAX_SIZE) {
                return [$width, $height, $candidate];
            }
        }

        // Absolute last resort: return whatever was smallest, even if it is
        // marginally above the target.
        if ($best !== '') {
            return [$width, $height, $best];
        }

        throw new InvalidArgumentException('Could not compress this image below the target size.');
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
        return $this->boxScaling($width, $height, (int) round(max($width, $height) * 0.85));
    }

    /**
     * Return proportional dimensions whose longest edge equals $maxEdge,
     * never going below a usable minimum.
     *
     * @return array{int, int}
     */
    protected function boxScaling(int $width, int $height, int $maxEdge): array
    {
        $long = max($width, $height);
        if ($long <= $maxEdge) {
            return [$width, $height];
        }

        $ratio = $maxEdge / $long;

        return [
            (int) max(round($width * $ratio), self::MIN_LONG_EDGE),
            (int) max(round($height * $ratio), self::MIN_LONG_EDGE),
        ];
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