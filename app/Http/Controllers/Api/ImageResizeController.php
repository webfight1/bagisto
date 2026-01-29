<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Intervention\Image\ImageManager;

class ImageResizeController extends Controller
{
    public function resize(Request $request, $path)
    {
        $width = $request->query('width', 260);
        $height = $request->query('height', 220);
        
        // Validate dimensions
        $width = max(1, min(2000, (int)$width));
        $height = max(1, min(2000, (int)$height));
        
        // Remove leading 'storage/' from path if present
        $imagePath = ltrim($path, 'storage/');
        
        $sourcePath = storage_path('app/public/' . $imagePath);
        
        if (!file_exists($sourcePath)) {
            abort(404, 'Image not found');
        }

        // Generate cache filename
        $pathInfo = pathinfo($imagePath);
        $cacheDir = 'cache/' . $pathInfo['dirname'];
        $cacheName = $pathInfo['filename'] . "_{$width}x{$height}." . $pathInfo['extension'];
        $cachePath = $cacheDir . '/' . $cacheName;
        $cacheFullPath = storage_path('app/public/' . $cachePath);

        // Check if cached version exists
        if (!file_exists($cacheFullPath)) {
            // Create cache directory if it doesn't exist
            if (!file_exists(dirname($cacheFullPath))) {
                mkdir(dirname($cacheFullPath), 0755, true);
            }

            // Create resized image
            $img = Image::make($sourcePath);
            
            // Resize and crop from top to preserve upper part of image
            $img->resize($width, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            
            if ($img->height() > $height) {
                $img->crop($width, $height, 0, 0);
            }

            // Save optimized image
            $img->save($cacheFullPath);
        }

        // Return the cached image
        $imageData = file_get_contents($cacheFullPath);
        $mimeType = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $imageData);

        return response($imageData)
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', 'public, max-age=31536000') // Cache for 1 year
            ->header('Etag', md5($imageData));
    }
}
