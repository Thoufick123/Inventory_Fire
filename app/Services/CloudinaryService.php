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
     * Fast image optimization before upload:
     * 1. If file is small (<= 800KB) and dimensions <= 1600px, passes directly with ZERO CPU overhead.
     * 2. If oversized (> 1600px or > 1MB), resamples to max 1600px and saves as WebP/JPEG (taking ~20ms vs 15,000ms for AVIF).
     * 3. Documents (PDFs, text) are returned untouched.
     *
     * @param string $sourcePath Path to image on disk
     * @param string $originalName Original filename
     * @param string|null $mimeType Known MIME type
     * @return array [path => string, name => string, mime => string, converted => bool]
     */
    public static function optimizeImageForUpload(string $sourcePath, string $originalName, ?string $mimeType = null): array {
        if (!file_exists($sourcePath)) {
            $detected = $mimeType ?: 'application/octet-stream';
            return ['path' => $sourcePath, 'name' => $originalName, 'mime' => $detected, 'converted' => false];
        }

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $detectedMime = $mimeType ?: (@mime_content_type($sourcePath) ?: '');

        // Only process raster images (exclude PDFs, documents, text files, zip, etc.)
        $isImage = str_starts_with($detectedMime, 'image/') || in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'bmp', 'gif', 'avif'], true);
        if (!$isImage) {
            return ['path' => $sourcePath, 'name' => $originalName, 'mime' => $detectedMime ?: 'application/octet-stream', 'converted' => false];
        }

        $fileSize = @filesize($sourcePath);
        $imageInfo = @getimagesize($sourcePath);

        // Fast-path: If file is already small (<= 800KB) and reasonably sized (<= 1600x1600), skip re-encoding completely!
        if ($imageInfo && $fileSize && $fileSize <= 819200 && $imageInfo[0] <= 1600 && $imageInfo[1] <= 1600) {
            return ['path' => $sourcePath, 'name' => $originalName, 'mime' => $detectedMime, 'converted' => false];
        }

        if (!function_exists('imagecreatetruecolor')) {
            return ['path' => $sourcePath, 'name' => $originalName, 'mime' => $detectedMime, 'converted' => false];
        }

        try {
            $img = null;
            $imgType = $imageInfo[2] ?? 0;
            switch ($imgType) {
                case IMAGETYPE_JPEG:
                    if (function_exists('imagecreatefromjpeg')) $img = @imagecreatefromjpeg($sourcePath);
                    break;
                case IMAGETYPE_PNG:
                    if (function_exists('imagecreatefrompng')) $img = @imagecreatefrompng($sourcePath);
                    break;
                case IMAGETYPE_WEBP:
                    if (function_exists('imagecreatefromwebp')) $img = @imagecreatefromwebp($sourcePath);
                    break;
                default:
                    $data = @file_get_contents($sourcePath);
                    if ($data !== false) {
                        $img = @imagecreatefromstring($data);
                    }
                    break;
            }

            if (!$img) {
                return ['path' => $sourcePath, 'name' => $originalName, 'mime' => $detectedMime, 'converted' => false];
            }

            $origWidth = imagesx($img);
            $origHeight = imagesy($img);
            $maxDim = 1600;

            if ($origWidth > $maxDim || $origHeight > $maxDim) {
                $ratio = min($maxDim / $origWidth, $maxDim / $origHeight);
                $newWidth = max(1, (int)round($origWidth * $ratio));
                $newHeight = max(1, (int)round($origHeight * $ratio));

                $resized = imagecreatetruecolor($newWidth, $newHeight);
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                imagecopyresampled($resized, $img, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
                imagedestroy($img);
                $img = $resized;
            }

            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $tempDir = sys_get_temp_dir();

            // Prefer WebP (ultra-fast, lightweight, universal browser support)
            if (function_exists('imagewebp')) {
                $targetPath = $tempDir . DIRECTORY_SEPARATOR . 'opt_' . bin2hex(random_bytes(6)) . '.webp';
                if (@imagewebp($img, $targetPath, 82) && file_exists($targetPath) && filesize($targetPath) > 0) {
                    imagedestroy($img);
                    return [
                        'path'      => $targetPath,
                        'name'      => $baseName . '.webp',
                        'mime'      => 'image/webp',
                        'converted' => true,
                    ];
                }
            }

            // Fallback to high-speed JPEG (takes ~15ms)
            if (function_exists('imagejpeg')) {
                $targetPath = $tempDir . DIRECTORY_SEPARATOR . 'opt_' . bin2hex(random_bytes(6)) . '.jpg';
                if (@imagejpeg($img, $targetPath, 82) && file_exists($targetPath) && filesize($targetPath) > 0) {
                    imagedestroy($img);
                    return [
                        'path'      => $targetPath,
                        'name'      => $baseName . '.jpg',
                        'mime'      => 'image/jpeg',
                        'converted' => true,
                    ];
                }
            }

            imagedestroy($img);
        } catch (\Throwable $e) {
            error_log("[CloudinaryService] Optimization error: " . $e->getMessage());
        }

        return ['path' => $sourcePath, 'name' => $originalName, 'mime' => $detectedMime ?: 'image/jpeg', 'converted' => false];
    }

    /**
     * Backward-compatibility alias for convertImageToAvif.
     */
    public static function convertImageToAvif(string $sourcePath, string $originalName, ?string $mimeType = null): array {
        return self::optimizeImageForUpload($sourcePath, $originalName, $mimeType);
    }

    /**
     * Upload an image or file to Cloudinary.
     * Automatically optimizes large images in ~20ms before uploading.
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

        // Fast optimization
        $conversion = self::optimizeImageForUpload($tmpPath, $originalName, $mimeType);
        $uploadPath = $conversion['path'];
        $uploadName = $conversion['name'];
        $uploadMime = $conversion['mime'];
        $isConverted = $conversion['converted'];

        $effectiveResourceType = ($uploadMime === 'image/webp' || $uploadMime === 'image/avif' || str_starts_with($uploadMime, 'image/')) ? 'image' : $resourceType;

        try {
            // Upload to Cloudinary if configured
            if (self::isConfigured()) {
                $cloudinaryUrl = self::uploadToCloudinaryApi($uploadPath, $folder, $uploadName, $uploadMime, $effectiveResourceType);
                if ($cloudinaryUrl) {
                    return $cloudinaryUrl;
                }
            }

            // Reliable local storage fallback
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
                    CURLOPT_URL            => "https://api.cloudinary.com/v1_1/{$cloudName}/{$effectiveResourceType}/upload",
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => [
                        'file'      => $curlFile,
                        'api_key'   => $apiKey,
                        'timestamp' => $timestamp,
                        'folder'    => $cleanFolder,
                        'signature' => $signature,
                    ],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_TCP_NODELAY    => 1,
                    CURLOPT_IPRESOLVE      => defined('CURL_IPRESOLVE_V4') ? CURL_IPRESOLVE_V4 : 1,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_TIMEOUT        => 20,
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
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_TCP_NODELAY    => 1,
            CURLOPT_IPRESOLVE      => defined('CURL_IPRESOLVE_V4') ? CURL_IPRESOLVE_V4 : 1,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 20,
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
