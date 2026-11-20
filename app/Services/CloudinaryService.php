<?php
/**
 * Cloudinary Service
 * Handles uploading, deleting, and managing images and documents via Cloudinary REST API.
 * Automatically falls back to local disk storage if Cloudinary is unavailable.
 */

class CloudinaryService {

    /**
     * Check if Cloudinary credentials are fully configured.
     */
    public static function isConfigured(): bool {
        $cloudName = env('CLOUDINARY_CLOUD_NAME', '');
        $apiKey    = env('CLOUDINARY_API_KEY', '');
        $apiSecret = env('CLOUDINARY_API_SECRET', '');

        return !empty($cloudName) && !empty($apiKey) && !empty($apiSecret)
            && $cloudName !== 'your_cloud_name'
            && $apiKey !== 'your_api_key'
            && $apiSecret !== 'your_api_secret';
    }

    /**
     * Convert an image file to AVIF format if supported and file is an image.
     * Automatically converts JPG, PNG, WEBP, BMP, etc. into high-efficiency AVIF.
     *
     * @param string $sourcePath Path to image on disk
     * @param string $originalName Original filename
     * @param string|null $mimeType Known MIME type
     * @return array [path => string, name => string, mime => string, converted => bool]
     */
    public static function convertImageToAvif(string $sourcePath, string $originalName, ?string $mimeType = null): array {
        if (!file_exists($sourcePath) || !function_exists('imageavif')) {
            $detected = @mime_content_type($sourcePath) ?: ($mimeType ?: 'application/octet-stream');
            return ['path' => $sourcePath, 'name' => $originalName, 'mime' => $detected, 'converted' => false];
        }

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $detectedMime = $mimeType ?: (@mime_content_type($sourcePath) ?: '');

        // Already AVIF
        if ($ext === 'avif' || $detectedMime === 'image/avif') {
            return ['path' => $sourcePath, 'name' => $originalName, 'mime' => 'image/avif', 'converted' => false];
        }

        // Only convert raster images (exclude PDFs, documents, text files, zip, etc.)
        $isImage = str_starts_with($detectedMime, 'image/') || in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'bmp', 'gif'], true);
        if (!$isImage) {
            return ['path' => $sourcePath, 'name' => $originalName, 'mime' => $detectedMime ?: 'application/octet-stream', 'converted' => false];
        }

        try {
            $data = @file_get_contents($sourcePath);
            if ($data === false) {
                return ['path' => $sourcePath, 'name' => $originalName, 'mime' => $detectedMime, 'converted' => false];
            }

            $img = @imagecreatefromstring($data);
            if ($img === false) {
                return ['path' => $sourcePath, 'name' => $originalName, 'mime' => $detectedMime, 'converted' => false];
            }

            // Preserve alpha channel and truecolor
            imagepalettetotruecolor($img);
            imagealphablending($img, true);
            imagesavealpha($img, true);

            $avifTempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'avif_' . bin2hex(random_bytes(8)) . '.avif';
            $success = @imageavif($img, $avifTempPath, 80);
            imagedestroy($img);

            if ($success && file_exists($avifTempPath) && filesize($avifTempPath) > 0) {
                $baseName = pathinfo($originalName, PATHINFO_FILENAME);
                return [
                    'path' => $avifTempPath,
                    'name' => $baseName . '.avif',
                    'mime' => 'image/avif',
                    'converted' => true
                ];
            }
        } catch (\Throwable $e) {
            error_log("[CloudinaryService] AVIF conversion error: " . $e->getMessage());
        }

        return ['path' => $sourcePath, 'name' => $originalName, 'mime' => $detectedMime ?: 'image/jpeg', 'converted' => false];
    }

    /**
     * Upload an image or file to Cloudinary.
     * Automatically converts any uploaded image to AVIF before uploading.
     * If Cloudinary is not configured or network fails, falls back gracefully.
     *
     * @param array|string $file Either $_FILES array item or absolute local filepath
     * @param string $folder Cloudinary folder path (e.g. 'grm_inventory/sellers/rolls')
     * @param string $resourceType 'image', 'raw', or 'auto'
     * @return string|null The uploaded secure URL or local relative path, or null on complete failure
     */
    public static function upload($file, string $folder = 'grm_inventory', string $resourceType = 'auto'): ?string {
        $tmpPath = null;
        $originalName = 'upload';
        $mimeType = null;

        if (is_array($file)) {
            if (empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                return null;
            }
            $tmpPath = $file['tmp_name'];
            $originalName = $file['name'] ?? 'upload';
            $mimeType = $file['type'] ?? mime_content_type($tmpPath);
        } elseif (is_string($file)) {
            if (!file_exists($file)) {
                return null;
            }
            $tmpPath = $file;
            $originalName = basename($file);
            $mimeType = mime_content_type($file);
        } else {
            return null;
        }

        // Convert image to AVIF format across the entire website
        $conversion = self::convertImageToAvif($tmpPath, $originalName, $mimeType);
        $uploadPath = $conversion['path'];
        $uploadName = $conversion['name'];
        $uploadMime = $conversion['mime'];
        $isConverted = $conversion['converted'];

        $effectiveResourceType = ($uploadMime === 'image/avif' || str_starts_with($uploadMime, 'image/')) ? 'image' : $resourceType;

        try {
            // Upload to Cloudinary if configured
            if (self::isConfigured()) {
                $cloudinaryUrl = self::uploadToCloudinaryApi($uploadPath, $folder, $uploadName, $uploadMime, $effectiveResourceType);
                if ($cloudinaryUrl) {
                    return $cloudinaryUrl;
                }
            }

            // Reliable local storage fallback (stores in AVIF format)
            $cleanFolder = trim(str_replace('..', '', $folder), '/');
            $uploadDir = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2)) . '/public/uploads/' . $cleanFolder;
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $uniqueName = time() . '_' . bin2hex(random_bytes(4)) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $uploadName);
            $destination = $uploadDir . '/' . $uniqueName;
            if (@copy($uploadPath, $destination)) {
                return 'uploads/' . $cleanFolder . '/' . $uniqueName;
            }

            error_log("[CloudinaryService] Upload failed or Cloudinary not configured. File not saved locally.");
            return null;
        } finally {
            if ($isConverted && file_exists($uploadPath)) {
                @unlink($uploadPath);
            }
        }
    }

    /**
     * Upload several files concurrently and return URLs keyed by the input keys.
     */
    public static function uploadMany(array $files, string $folder = 'grm_inventory', string $resourceType = 'auto'): array {
        $results = array_fill_keys(array_keys($files), null);
        if (!$files || !self::isConfigured()) {
            if ($files) {
                error_log("[CloudinaryService] Batch upload unavailable or Cloudinary not configured.");
            }
            return $results;
        }

        if (!function_exists('curl_multi_init')) {
            foreach ($files as $key => $file) {
                $results[$key] = self::upload($file, $folder, $resourceType);
            }
            return $results;
        }

        $cloudName = env('CLOUDINARY_CLOUD_NAME');
        $apiKey = env('CLOUDINARY_API_KEY');
        $apiSecret = env('CLOUDINARY_API_SECRET');
        $cleanFolder = trim($folder, '/');
        $multiHandle = curl_multi_init();
        $handles = [];
        $tempFilesToClean = [];

        try {
            foreach ($files as $key => $file) {
                if (!is_array($file) || empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                    continue;
                }

                $sourcePath = $file['tmp_name'];
                $sourceName = $file['name'] ?? 'upload';
                $sourceMime = $file['type'] ?? mime_content_type($sourcePath);

                // Convert image to AVIF format
                $conversion = self::convertImageToAvif($sourcePath, $sourceName, $sourceMime);
                $uploadPath = $conversion['path'];
                $uploadName = $conversion['name'];
                $uploadMime = $conversion['mime'];
                if ($conversion['converted']) {
                    $tempFilesToClean[] = $uploadPath;
                }

                $effectiveResourceType = ($uploadMime === 'image/avif' || str_starts_with($uploadMime, 'image/')) ? 'image' : $resourceType;

                $timestamp = time();
                $params = ['folder' => $cleanFolder, 'timestamp' => $timestamp];
                ksort($params);
                $paramString = '';
                foreach ($params as $paramName => $paramValue) {
                    $paramString .= ($paramString !== '' ? '&' : '') . $paramName . '=' . $paramValue;
                }
                $signature = sha1($paramString . $apiSecret);
                $curlFile = new CURLFile(
                    $uploadPath,
                    $uploadMime ?: 'application/octet-stream',
                    $uploadName
                );
                $handle = curl_init();
                curl_setopt_array($handle, [
                    CURLOPT_URL => "https://api.cloudinary.com/v1_1/{$cloudName}/{$effectiveResourceType}/upload",
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => [
                        'file' => $curlFile,
                        'api_key' => $apiKey,
                        'timestamp' => $timestamp,
                        'folder' => $cleanFolder,
                        'signature' => $signature,
                    ],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_TIMEOUT => 30,
                ]);
                curl_multi_add_handle($multiHandle, $handle);
                $handleId = is_object($handle) ? spl_object_id($handle) : (int)$handle;
                $handles[$handleId] = ['key' => $key, 'handle' => $handle];
            }

            $running = null;
            do {
                $multiStatus = curl_multi_exec($multiHandle, $running);
                if ($running && $multiStatus === CURLM_OK && curl_multi_select($multiHandle, 1.0) === -1) {
                    usleep(100000);
                }
            } while ($running && $multiStatus === CURLM_OK);

            foreach ($handles as $entry) {
                $handle = $entry['handle'];
                $response = curl_multi_getcontent($handle);
                $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
                if ($httpCode === 200 && $response) {
                    $result = json_decode($response, true);
                    $results[$entry['key']] = $result['secure_url'] ?? $result['url'] ?? null;
                }
                if ($results[$entry['key']] === null && env('APP_DEBUG', false)) {
                    error_log("[CloudinaryService] Batch upload failed (HTTP {$httpCode}): " . (string)$response);
                }
                curl_multi_remove_handle($multiHandle, $handle);
                curl_close($handle);
            }
            curl_multi_close($multiHandle);
        } finally {
            foreach ($tempFilesToClean as $tempFile) {
                if (file_exists($tempFile)) {
                    @unlink($tempFile);
                }
            }
        }

        return $results;
    }

    /**
     * Perform direct signed upload to Cloudinary REST API via cURL.
     */
    private static function uploadToCloudinaryApi(string $filePath, string $folder, string $originalName, ?string $mimeType, string $resourceType): ?string {
        $cloudName = env('CLOUDINARY_CLOUD_NAME');
        $apiKey    = env('CLOUDINARY_API_KEY');
        $apiSecret = env('CLOUDINARY_API_SECRET');

        $timestamp = time();
        $cleanFolder = trim($folder, '/');

        // Parameters to sign (sorted alphabetically)
        $params = [
            'folder'    => $cleanFolder,
            'timestamp' => $timestamp,
        ];

        ksort($params);
        $paramStr = '';
        foreach ($params as $k => $v) {
            $paramStr .= ($paramStr !== '' ? '&' : '') . $k . '=' . $v;
        }
        $signature = sha1($paramStr . $apiSecret);

        $cFile = new CURLFile($filePath, $mimeType ?: 'application/octet-stream', $originalName);

        $postFields = [
            'file'      => $cFile,
            'api_key'   => $apiKey,
            'timestamp' => $timestamp,
            'folder'    => $cleanFolder,
            'signature' => $signature,
        ];

        $apiUrl = "https://api.cloudinary.com/v1_1/{$cloudName}/{$resourceType}/upload";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $apiUrl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $result = json_decode($response, true);
            if (!empty($result['secure_url'])) {
                return $result['secure_url'];
            }
            if (!empty($result['url'])) {
                return $result['url'];
            }
        }

        // Log error if debug mode is on
        if (env('APP_DEBUG', false)) {
            error_log("[CloudinaryService] Upload failed (HTTP $httpCode): " . $response);
        }

        return null;
    }

    /**
     * Extract public ID from a Cloudinary URL or return the public ID directly.
     *
     * @param string|null $publicIdOrUrl
     * @return string|null
     */
    public static function extractPublicId(?string $publicIdOrUrl): ?string {
        if (empty($publicIdOrUrl)) {
            return null;
        }

        if (str_starts_with($publicIdOrUrl, 'http://') || str_starts_with($publicIdOrUrl, 'https://')) {
            // Match Cloudinary URL pattern: /v1234567890/folder/subfolder/file.ext or /upload/folder/subfolder/file.ext
            if (preg_match('#/(?:v\d+/)?(grm_inventory/[^.\s]+)#', $publicIdOrUrl, $matches)) {
                return $matches[1];
            }
            if (preg_match('#/(?:v\d+/|upload/)?([^/]+/[^.\s]+)\.[a-zA-Z0-9]+$#', $publicIdOrUrl, $matches)) {
                return $matches[1];
            }
            if (preg_match('#/v\d+/(.+?)\.[a-zA-Z0-9]+$#', $publicIdOrUrl, $matches)) {
                return $matches[1];
            }
            return pathinfo(parse_url($publicIdOrUrl, PHP_URL_PATH) ?? $publicIdOrUrl, PATHINFO_FILENAME);
        }

        return $publicIdOrUrl;
    }

    /**
     * Delete an asset from Cloudinary by public ID or URL.
     */
    public static function delete(string $publicIdOrUrl, string $resourceType = 'image'): bool {
        if (!self::isConfigured() || empty($publicIdOrUrl)) {
            return false;
        }

        $publicId = self::extractPublicId($publicIdOrUrl) ?? $publicIdOrUrl;

        $cloudName = env('CLOUDINARY_CLOUD_NAME');
        $apiKey    = env('CLOUDINARY_API_KEY');
        $apiSecret = env('CLOUDINARY_API_SECRET');

        $timestamp = time();
        $params = [
            'public_id' => $publicId,
            'timestamp' => $timestamp,
        ];
        ksort($params);
        $paramStr = "public_id={$publicId}&timestamp={$timestamp}";
        $signature = sha1($paramStr . $apiSecret);

        $postFields = [
            'public_id' => $publicId,
            'api_key'   => $apiKey,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];

        $apiUrl = "https://api.cloudinary.com/v1_1/{$cloudName}/{$resourceType}/destroy";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $apiUrl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 20,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $result = json_decode($response, true);
            return ($result['result'] ?? '') === 'ok';
        }

        return false;
    }
}

if (!class_exists('App\Services\CloudinaryService', false)) {
    class_alias('CloudinaryService', 'App\Services\CloudinaryService');
}
