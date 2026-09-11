<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImageCompressionTest extends TestCase
{
    /** @var string[] */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    public function test_the_endpoint_requires_the_target_size(): void
    {
        $file = $this->makeJpeg();

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withHeader('Accept', 'application/json')
            ->post('/compress', [
                'image' => $this->upload($file),
            ])
            ->assertStatus(422);
    }

    public function test_compresses_image_when_target_is_smaller(): void
    {
        $file = $this->makeJpeg(800, 600, 90);
        $originalSize = (int) filesize($file);
        $targetSize = max(51200, (int) round($originalSize * 0.5));

        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withHeader('Accept', 'application/json')
            ->post('/compress', [
                'image' => $this->upload($file),
                'target_size' => $targetSize,
            ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'mode' => 'compressed',
            ]);

        $data = $response->json();
        $this->assertSame($originalSize, $data['original_size']);
        $this->assertLessThanOrEqual($targetSize, $data['compressed_size']);
        $this->assertLessThan($originalSize, $data['compressed_size']);
    }

    public function test_enlarges_image_when_target_is_larger(): void
    {
        $file = $this->makeJpeg(600, 400, 85);
        $originalSize = (int) filesize($file);
        $targetSize = (int) round($originalSize * 2);

        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withHeader('Accept', 'application/json')
            ->post('/compress', [
                'image' => $this->upload($file),
                'target_size' => $targetSize,
            ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'mode' => 'enlarged',
            ]);

        $data = $response->json();
        $this->assertSame($originalSize, $data['original_size']);
        $this->assertGreaterThan($originalSize, $data['compressed_size']);
    }

    public function test_returns_none_when_target_matches_original(): void
    {
        $file = $this->makeJpeg(400, 300, 85);
        $originalSize = (int) filesize($file);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withHeader('Accept', 'application/json')
            ->post('/compress', [
                'image' => $this->upload($file),
                'target_size' => $originalSize,
            ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'mode' => 'none',
                'compressed_size' => $originalSize,
                'compression_percent' => 0,
            ]);
    }

    private function makeJpeg(int $width = 800, int $height = 600, int $quality = 85): string
    {
        $path = sys_get_temp_dir().'/ic-test-'.uniqid().'.jpg';
        $this->tempFiles[] = $path;

        $tile = imagecreatetruecolor(64, 64);
        for ($y = 0; $y < 64; $y++) {
            for ($x = 0; $x < 64; $x++) {
                imagesetpixel($tile, $x, $y, imagecolorallocate($tile, random_int(0, 255), random_int(0, 255), random_int(0, 255)));
            }
        }

        $image = imagecreatetruecolor($width, $height);
        for ($oy = 0; $oy < $height; $oy += 64) {
            for ($ox = 0; $ox < $width; $ox += 64) {
                imagecopy($image, $tile, $ox, $oy, 0, 0, 64, 64);
            }
        }

        imagejpeg($image, $path, $quality);

        imagedestroy($tile);
        imagedestroy($image);

        return $path;
    }

    private function upload(string $path): UploadedFile
    {
        return new UploadedFile($path, basename($path), 'image/jpeg', null, true);
    }
}