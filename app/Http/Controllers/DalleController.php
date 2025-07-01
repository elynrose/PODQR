<?php

namespace App\Http\Controllers;

use App\Services\CloudStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DalleController extends Controller
{
    /**
     * Test API key and DALL-E access
     */
    public function testApi(Request $request)
    {
        try {
            $apiKey = config('services.openai.api_key');
            
            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'OpenAI API key not configured'
                ], 500);
            }

            // Test with a simple models request to check API key validity
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->get('https://api.openai.com/v1/models');

            if ($response->successful()) {
                $data = $response->json();
                $models = collect($data['data'])->pluck('id')->toArray();
                
                $hasDalle3 = in_array('dall-e-3', $models);
                $hasDalle2 = in_array('dall-e-2', $models);
                
                return response()->json([
                    'success' => true,
                    'message' => 'API key is valid',
                    'has_dalle3' => $hasDalle3,
                    'has_dalle2' => $hasDalle2,
                    'available_models' => $models,
                    'api_key_length' => strlen($apiKey),
                    'api_key_prefix' => substr($apiKey, 0, 7) . '...'
                ]);
            } else {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? 'Unknown error occurred';
                
                return response()->json([
                    'success' => false,
                    'message' => 'API key test failed: ' . $errorMessage,
                    'status_code' => $response->status(),
                    'api_key_length' => strlen($apiKey),
                    'api_key_prefix' => substr($apiKey, 0, 7) . '...'
                ], $response->status());
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage(),
                'api_key_length' => strlen(config('services.openai.api_key') ?? ''),
                'api_key_prefix' => config('services.openai.api_key') ? substr(config('services.openai.api_key'), 0, 7) . '...' : 'None'
            ], 500);
        }
    }

    /**
     * Generate an image using DALL-E API
     */
    public function generateImage(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:1000',
            'size' => 'required|in:1024x1024,1792x1024,1024x1792',
            'quality' => 'required|in:standard,hd',
        ]);

        try {
            $apiKey = config('services.openai.api_key');
            
            if (!$apiKey) {
                Log::error('OpenAI API key not configured');
                return response()->json([
                    'success' => false,
                    'message' => 'AI image generation is not configured. Please contact the administrator.'
                ], 500);
            }

            // Validate prompt content
            if (strlen($request->prompt) < 10) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please provide a more detailed description (at least 10 characters).'
                ], 422);
            }

            // Check available models first
            $modelsResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->get('https://api.openai.com/v1/models');

            $useDalle3 = false;
            if ($modelsResponse->successful()) {
                $modelsData = $modelsResponse->json();
                $models = collect($modelsData['data'])->pluck('id')->toArray();
                $useDalle3 = in_array('dall-e-3', $models);
            }

            // Prepare request payload
            $payload = [
                'prompt' => $request->prompt,
                'n' => 1,
            ];

            if ($useDalle3) {
                $payload['model'] = 'dall-e-3';
                $payload['size'] = $request->size;
                $payload['quality'] = $request->quality;
            } else {
                // Fallback to DALL-E 2
                $payload['model'] = 'dall-e-2';
                // DALL-E 2 only supports 1024x1024, 512x512, 256x256
                $payload['size'] = '1024x1024';
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.openai.com/v1/images/generations', $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['data'][0]['url'])) {
                    $imageUrl = $data['data'][0]['url'];
                    
                    // Download and save the image
                    $savedImagePath = $this->saveImageFromUrl($imageUrl, $request->prompt);
                    
                    return response()->json([
                        'success' => true,
                        'image_url' => Storage::url($savedImagePath),
                        'image_path' => $savedImagePath,
                        'model_used' => $useDalle3 ? 'dall-e-3' : 'dall-e-2',
                        'message' => 'Image generated successfully using ' . ($useDalle3 ? 'DALL-E 3' : 'DALL-E 2') . '!'
                    ]);
                } else {
                    Log::error('DALL-E API response missing image URL', ['response' => $data]);
                    return response()->json([
                        'success' => false,
                        'message' => 'No image URL in response from AI service.'
                    ], 500);
                }
            } else {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? 'Unknown error occurred';
                
                Log::error('DALL-E API Error', [
                    'status' => $response->status(),
                    'error' => $errorMessage,
                    'prompt' => $request->prompt,
                    'model_attempted' => $useDalle3 ? 'dall-e-3' : 'dall-e-2'
                ]);
                
                // Provide user-friendly error messages
                $userMessage = 'Error generating image. ';
                if (str_contains($errorMessage, 'billing')) {
                    $userMessage .= 'AI service billing issue. Please add credits to your OpenAI account.';
                } elseif (str_contains($errorMessage, 'content_policy')) {
                    $userMessage .= 'The description contains content that violates AI service policies. Please try a different description.';
                } elseif (str_contains($errorMessage, 'rate_limit')) {
                    $userMessage .= 'Too many requests. Please wait a moment and try again.';
                } elseif (str_contains($errorMessage, 'quota')) {
                    $userMessage .= 'API quota exceeded. Please check your OpenAI account usage.';
                } else {
                    $userMessage .= 'Please try again or contact support if the problem persists.';
                }
                
                return response()->json([
                    'success' => false,
                    'message' => $userMessage
                ], $response->status());
            }
            
        } catch (\Exception $e) {
            Log::error('DALL-E Generation Exception', [
                'error' => $e->getMessage(),
                'prompt' => $request->prompt
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error generating image. Please try again later.'
            ], 500);
        }
    }

    /**
     * Download and save image from URL
     */
    private function saveImageFromUrl($imageUrl, $prompt)
    {
        try {
            $cloudStorage = new CloudStorageService();
            $fileName = 'dalle_' . time() . '_' . Str::random(10) . '.png';
            
            $path = $cloudStorage->storeImageFromUrl($imageUrl, 'dalle-images', $fileName);
            
            Log::info('DALL-E image saved to cloud storage', [
                'path' => $path,
                'prompt' => $prompt
            ]);
            
            return $path;
        } catch (\Exception $e) {
            Log::error('Error saving DALL-E image', [
                'error' => $e->getMessage(),
                'url' => $imageUrl
            ]);
            throw $e;
        }
    }
}
