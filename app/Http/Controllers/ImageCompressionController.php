<?php

namespace App\Http\Controllers;

use App\Services\ImageCompressionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ImageCompressionController extends Controller
{
    public function __construct(protected ImageCompressionService $compressionService)
    {
    }

    /**
     * Validate and compress an uploaded image to under 1 MB.
     */
    public function compress(Request $request): JsonResponse
    {
        $request->validate([
            'image' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:20480', // 20MB
            ],
            'target_size' => [
                'required',
                'integer',
                'min:51200',    // 50KB minimum
                'max:20971520', // 20MB maximum
            ],
        ]);

        try {
            $targetSize = (int) $request->input('target_size');
            $result = $this->compressionService->compress($request->file('image'), $targetSize);

            return response()->json([
                'success' => true,
                'compressed_data' => base64_encode($result['data']),
                'original_size' => $result['original_size'],
                'compressed_size' => $result['compressed_size'],
                'compression_percent' => $result['compression_percent'],
                'dimensions' => $result['dimensions'],
                'format' => $result['format'],
                'reduced' => $result['reduced'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while compressing your image. Please try again.',
            ], 500);
        }
    }
}