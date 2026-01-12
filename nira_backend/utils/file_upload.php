<?php
/**
 * File Upload Utility for NIRA System
 * Handles secure file uploads for citizen images and documents
 */

class FileUpload {
    // Allowed image MIME types
    private static $allowedImageTypes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp'
    ];
    
    // Allowed document MIME types
    private static $allowedDocumentTypes = [
        'application/pdf',
        'image/jpeg',
        'image/jpg',
        'image/png',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    // Maximum file sizes (in bytes)
    private static $maxImageSize = 5 * 1024 * 1024; // 5MB
    private static $maxDocumentSize = 10 * 1024 * 1024; // 10MB
    
    /**
     * Upload citizen image
     * @param array $file $_FILES['image'] array
     * @param string $nationalId National ID for file naming
     * @return string|false File path on success, false on failure
     */
    public static function uploadImage($file, $nationalId) {
        return self::uploadFile($file, $nationalId, 'image', self::$allowedImageTypes, self::$maxImageSize);
    }
    
    /**
     * Upload citizen document
     * @param array $file $_FILES['document'] array
     * @param string $nationalId National ID for file naming
     * @return string|false File path on success, false on failure
     */
    public static function uploadDocument($file, $nationalId) {
        return self::uploadFile($file, $nationalId, 'document', self::$allowedDocumentTypes, self::$maxDocumentSize);
    }
    
    /**
     * Upload user profile picture
     * @param array $file $_FILES['profilePicture'] array
     * @param int|string $userId User ID for file naming
     * @return string|false File path on success, false on failure
     */
    public static function uploadProfilePicture($file, $userId) {
        return self::uploadFile($file, (string)$userId, 'profile', self::$allowedImageTypes, self::$maxImageSize);
    }
    
    /**
     * Generic file upload handler
     * @param array $file $_FILES array
     * @param string $nationalId National ID for file naming
     * @param string $type 'image' or 'document'
     * @param array $allowedTypes Allowed MIME types
     * @param int $maxSize Maximum file size in bytes
     * @return string|false File path on success, false on failure
     */
    private static function uploadFile($file, $nationalId, $type, $allowedTypes, $maxSize) {
        // Check if file was uploaded
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                return null; // No file uploaded (optional field)
            }
            throw new Exception('File upload error: ' . self::getUploadErrorMessage($file['error']));
        }
        
        // Validate file size
        if ($file['size'] > $maxSize) {
            $maxSizeMB = round($maxSize / (1024 * 1024), 2);
            throw new Exception("File size exceeds maximum allowed size of {$maxSizeMB}MB");
        }
        
        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Invalid file type. Allowed types: ' . implode(', ', $allowedTypes));
        }
        
        // Create uploads directory structure
        $baseDir = __DIR__ . '/../uploads';
        $typeDir = $baseDir . '/' . $type . 's';
        
        // Create directories if they don't exist
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }
        if (!is_dir($typeDir)) {
            mkdir($typeDir, 0755, true);
        }
        
        // Generate unique filename: identifier_timestamp_rand.ext
        $extension = self::getFileExtension($file['name'], $mimeType);
        $timestamp = time();
        $random = substr(md5(uniqid(rand(), true)), 0, 8);
        $filename = $nationalId . '_' . $timestamp . '_' . $random . '.' . $extension;
        $filePath = $typeDir . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception('Failed to save uploaded file');
        }
        
        // Return relative path from uploads directory
        // For profile type, return "profiles/filename", for others return "types/filename"
        if ($type === 'profile') {
            return 'profiles/' . $filename;
        }
        return $type . 's/' . $filename;
    }
    
    /**
     * Get file extension from filename or MIME type
     * @param string $filename Original filename
     * @param string $mimeType MIME type
     * @return string File extension
     */
    private static function getFileExtension($filename, $mimeType) {
        // Try to get extension from filename first
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Map MIME types to extensions as fallback
        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
        ];
        
        if (empty($ext) && isset($mimeToExt[$mimeType])) {
            $ext = $mimeToExt[$mimeType];
        }
        
        return $ext ?: 'bin';
    }
    
    /**
     * Get upload error message
     * @param int $errorCode PHP upload error code
     * @return string Error message
     */
    private static function getUploadErrorMessage($errorCode) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        return $errors[$errorCode] ?? 'Unknown upload error';
    }
    
    /**
     * Delete file
     * @param string $filePath Relative path from uploads directory
     * @return bool Success
     */
    public static function deleteFile($filePath) {
        if (empty($filePath)) {
            return true; // No file to delete
        }
        
        $fullPath = __DIR__ . '/../uploads/' . $filePath;
        
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        
        return true; // File doesn't exist, consider it deleted
    }
    
    /**
     * Get full URL path for file
     * @param string $filePath Relative path from uploads directory
     * @return string|null Full URL or null if file doesn't exist
     */
    public static function getFileUrl($filePath) {
        if (empty($filePath)) {
            return null;
        }
        
        // Determine base URL from script location
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
        
        // Extract base path (e.g., /niraSystem/nira_backend)
        $basePath = '';
        if (strpos($scriptPath, '/niraSystem') !== false) {
            $basePath = '/niraSystem/nira_backend';
        } elseif (strpos($scriptPath, '/nira_system') !== false) {
            $basePath = '/nira_system/nira_backend';
        } else {
            $basePath = dirname($scriptPath);
        }
        
        // Use the file serving endpoint for security
        $encodedPath = urlencode($filePath);
        return $protocol . '://' . $host . $basePath . '/api/files/get.php?path=' . $encodedPath;
    }
}
