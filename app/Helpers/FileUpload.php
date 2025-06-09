<?php
/**
 * File Upload Helper
 * Handles secure file uploads with validation
 */
class FileUpload {
    
    private $allowedExtensions;
    private $allowedMimeTypes;
    private $maxFileSize;
    private $uploadPath;
    
    public function __construct() {
        $this->allowedExtensions = explode(',', $_ENV['ALLOWED_EXTENSIONS'] ?? 'jpg,jpeg,png,pdf');
        $this->maxFileSize = (int)($_ENV['MAX_FILE_SIZE'] ?? 5242880); // 5MB
        $this->uploadPath = dirname(dirname(dirname(__FILE__))) . '/storage/uploads/';
        
        $this->allowedMimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        
        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }
    
    /**
     * Upload a single file
     */
    public function uploadFile($file, $customPath = null) {
        try {
            // Validate file
            $validation = $this->validateFile($file);
            if (!$validation['valid']) {
                return ['success' => false, 'error' => $validation['error']];
            }
            
            // Generate unique filename
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = $this->generateUniqueFilename($extension);
            
            // Determine upload path
            $uploadPath = $customPath ?? $this->uploadPath;
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            $fullPath = $uploadPath . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $fullPath)) {
                // Set proper permissions
                chmod($fullPath, 0644);
                
                return [
                    'success' => true,
                    'filename' => $filename,
                    'path' => $fullPath,
                    'size' => $file['size'],
                    'type' => $file['type'],
                    'original_name' => $file['name']
                ];
            } else {
                return ['success' => false, 'error' => 'Erreur lors du déplacement du fichier'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Upload multiple files
     */
    public function uploadMultipleFiles($files, $customPath = null) {
        $results = [];
        
        // Normalize files array structure
        $normalizedFiles = $this->normalizeFilesArray($files);
        
        foreach ($normalizedFiles as $file) {
            $results[] = $this->uploadFile($file, $customPath);
        }
        
        return $results;
    }
    
    /**
     * Validate uploaded file
     */
    public function validateFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => $this->getUploadErrorMessage($file['error'])];
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            $maxSizeMB = round($this->maxFileSize / 1024 / 1024, 2);
            return ['valid' => false, 'error' => "Fichier trop volumineux. Taille maximum: {$maxSizeMB}MB"];
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            $allowedList = implode(', ', $this->allowedExtensions);
            return ['valid' => false, 'error' => "Type de fichier non autorisé. Types autorisés: {$allowedList}"];
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!isset($this->allowedMimeTypes[$extension]) || 
            $mimeType !== $this->allowedMimeTypes[$extension]) {
            return ['valid' => false, 'error' => 'Type MIME du fichier invalide'];
        }
        
        // Additional security checks
        if (!$this->isFileSecure($file['tmp_name'], $extension)) {
            return ['valid' => false, 'error' => 'Fichier potentiellement dangereux détecté'];
        }
        
        return ['valid' => true, 'error' => null];
    }
    
    /**
     * Generate unique filename
     */
    private function generateUniqueFilename($extension) {
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        return "{$timestamp}_{$random}.{$extension}";
    }
    
    /**
     * Normalize files array for multiple uploads
     */
    private function normalizeFilesArray($files) {
        $normalized = [];
        
        if (isset($files['name']) && is_array($files['name'])) {
            // Multiple files with same input name
            for ($i = 0; $i < count($files['name']); $i++) {
                $normalized[] = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
            }
        } else {
            // Single file or multiple files with different input names
            $normalized[] = $files;
        }
        
        return $normalized;
    }
    
    /**
     * Get upload error message
     */
    private function getUploadErrorMessage($errorCode) {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximum autorisée par le serveur',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximum autorisée par le formulaire',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Impossible d\'écrire le fichier sur le disque',
            UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté le téléchargement'
        ];
        
        return $messages[$errorCode] ?? 'Erreur de téléchargement inconnue';
    }
    
    /**
     * Additional security checks for uploaded files
     */
    private function isFileSecure($filePath, $extension) {
        // Check for embedded PHP code in image files
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            $content = file_get_contents($filePath);
            if (strpos($content, '<?php') !== false || strpos($content, '<script') !== false) {
                return false;
            }
        }
        
        // Check file size (additional check)
        if (filesize($filePath) === 0) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Delete uploaded file
     */
    public function deleteFile($filename) {
        $filePath = $this->uploadPath . $filename;
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
    
    /**
     * Get file URL for public access
     */
    public function getFileUrl($filename) {
        return '/uploads/' . $filename;
    }
    
    /**
     * Get file info
     */
    public function getFileInfo($filename) {
        $filePath = $this->uploadPath . $filename;
        if (file_exists($filePath)) {
            return [
                'filename' => $filename,
                'size' => filesize($filePath),
                'modified' => filemtime($filePath),
                'type' => mime_content_type($filePath)
            ];
        }
        return null;
    }
}