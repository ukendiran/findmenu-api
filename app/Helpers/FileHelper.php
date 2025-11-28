<?php

namespace App\Helpers;

class FileHelper
{
    /**
     * Check if image file exists in public directory
     * 
     * @param string|null $imagePath Relative path from public directory (e.g., "images/uploads/image.jpg")
     * @return bool
     */
    public static function imageExists($imagePath)
    {
        // Return false if path is empty or null
        if (empty($imagePath) || $imagePath === null || $imagePath === 'null' || $imagePath === 'undefined') {
            return false;
        }

        // Trim whitespace
        $imagePath = trim($imagePath);
        
        if (empty($imagePath)) {
            return false;
        }

        // Check if file exists
        $fullPath = public_path($imagePath);
        return file_exists($fullPath) && is_file($fullPath);
    }

    /**
     * Get image path or return default no-image path
     * 
     * @param string|null $imagePath
     * @param string $defaultPath Default: "images/no-image.png"
     * @return string
     */
    public static function getImagePath($imagePath, $defaultPath = "images/no-image.png")
    {
        if (self::imageExists($imagePath)) {
            return $imagePath;
        }
        return $defaultPath;
    }

    /**
     * Delete image if it exists
     * 
     * @param string|null $imagePath
     * @return bool True if deleted, false otherwise
     */
    public static function deleteImage($imagePath)
    {
        if (self::imageExists($imagePath)) {
            try {
                return @unlink(public_path($imagePath));
            } catch (\Exception $e) {
                \Log::warning("Failed to delete image: {$imagePath}", ['error' => $e->getMessage()]);
                return false;
            }
        }
        return false;
    }

    /**
     * Get full URL for image or return default no-image URL
     * 
     * @param string|null $imagePath
     * @param string $defaultPath Default: "images/no-image.png"
     * @return string
     */
    public static function getImageUrl($imagePath, $defaultPath = "images/no-image.png")
    {
        $path = self::getImagePath($imagePath, $defaultPath);
        return asset($path);
    }
}

