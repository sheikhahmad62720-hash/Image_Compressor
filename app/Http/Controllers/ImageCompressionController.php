<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ImageCompressionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ImageCompressionController extends Controller
{
    protected $compressionService;

    public function __construct(ImageCompressionService $compressionService)
    {
        $this->compressionService = $compressionService;
    }

    /**
     * Upload and compress an image.
     */
    public function compress(Request $request)
    {
        $request->validate([
            'image' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:20MB',
            ],
        ]);

        $file = $request->file('image');

        try {
            $result = $this->compressionService->compress($file);

            // Store compressed file temporarily and return path
            $compressedFile = $result['compressed_path'];
            $extension = $result['format'];

            // Read the compressed file and store it
            $compressedContent = File::get($compressedFile);

            // Generate a clean filename
            $fileName = 'compressed-image.'.$extension;

            // Return the result with the compressed file data
            return response()->json([
                'success' => true,
                'original_size' => $result['original_size'],
                'compressed_size' => $result['compressed_size'],
                'compression_percent' => $result['compression_percent'],
                'dimensions' => $result['dimensions'],
                'format' => $extension,
                'file_name' => $fileName,
                'compressed_data' => base64_encode($compressedContent),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An error occurred during compression. Please try again.',
            ], 500);
        }
    }

    /**
     * Download the compressed image.
     */
    public function download(string $fileName, string $format)
    {
        // Create a temporary file for download
        $tempPath = tempnam(sys_get_temp_dir(), 'download_') . '.'.$format;

        // For now, we'll just serve the file from the request
        // In a full implementation, you'd store the compressed file and serve it
        return response()->download($tempPath, $fileName);
    }

    /**
     * Serve the compressed image data.
     */
    public function serveCompressed(Request $request)
    {
        $format = $request->query('format', 'jpg');
        $fileName = $request->query('file_name', 'compressed-image.'.$format);

        // Read the temporary compressed file
        $tempPath = tempnam(sys_get_temp_dir(), 'img_comp_') . '.' . $format;

        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'image/'.$format,
        ]);
    }
}