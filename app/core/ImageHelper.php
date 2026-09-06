<?php
/**
 * ImageHelper — Utility Pengolahan & Konversi Gambar ICLABS
 *
 * Mengoptimalkan ukuran penyimpanan foto dengan mengonversi berbagai
 * format input (JPG, JPEG, PNG, WEBP, maupun base64 data URI dari Cropper.js)
 * menjadi format WebP terkompresi berkualitas tinggi (default: 85%).
 */
class ImageHelper {

    /**
     * Konversi data gambar (file upload, path lokal, atau data URI base64)
     * ke format WebP dan simpan ke file tujuan.
     *
     * @param  string $source          Path file lokal atau data URI base64 (data:image/...)
     * @param  string $destinationPath Path absolut tujuan penyimpanan file .webp
     * @param  int    $quality         Kualitas kompresi WebP (0 - 100, default: 85)
     * @return bool                    True jika berhasil, false jika gagal
     */
    public static function convertToWebp(string $source, string $destinationPath, int $quality = 85): bool
    {
        $img = null;

        // 1. Baca sumber gambar: base64 data URI vs file path
        if (strpos($source, 'data:image/') === 0) {
            $commaPos = strpos($source, ',');
            if ($commaPos === false) return false;
            $binary = base64_decode(substr($source, $commaPos + 1));
            if ($binary === false) return false;
            $img = @imagecreatefromstring($binary);
        } elseif (file_exists($source)) {
            $content = @file_get_contents($source);
            if ($content === false) return false;
            $img = @imagecreatefromstring($content);
        }

        if (!$img) {
            return false;
        }

        // 2. Pastikan direktori tujuan tersedia
        $dir = dirname($destinationPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        // 3. Tangani transparansi (alpha channel) untuk format PNG/WEBP
        imagealphablending($img, false);
        imagesavealpha($img, true);

        // 4. Konversi & simpan ke WebP jika didukung GD, atau fallback
        if (function_exists('imagewebp')) {
            $success = @imagewebp($img, $destinationPath, $quality);
        } else {
            // Fallback jika lingkungan PHP tidak memiliki imagewebp
            $success = @imagejpeg($img, $destinationPath, $quality);
        }

        imagedestroy($img);

        return $success && file_exists($destinationPath);
    }

    /**
     * Simpan foto profil baru dalam format WebP dan hapus foto lama (jika bukan default.jpg).
     *
     * Mendukung:
     *   - String base64 dari Cropper.js ($input = $_POST['cropped_image'])
     *   - Array file upload standar ($input = $_FILES['photo'])
     *   - Path file sementara ($input = $_FILES['photo']['tmp_name'])
     *
     * @param  string|array $input     Sumber data gambar
     * @param  string       $targetDir Folder penyimpanan (mis. UPLOAD_PATH . 'profile/')
     * @param  string|null  $oldPhoto  Nama file foto profil lama untuk dihapus
     * @param  int          $quality   Kualitas WebP (default 85)
     * @return string|null             Nama file baru yang tersimpan (mis. '1725580000_66d9f.webp'), atau null jika gagal
     */
    public static function saveProfilePhotoAsWebp($input, string $targetDir, ?string $oldPhoto = null, int $quality = 85): ?string
    {
        $ext = function_exists('imagewebp') ? 'webp' : 'jpg';
        $newFileName = time() . '_' . uniqid() . '.' . $ext;
        $destPath = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $newFileName;

        $converted = false;

        if (is_string($input) && !empty($input)) {
            // Input berupa data URI base64 atau path file lokal
            $converted = self::convertToWebp($input, $destPath, $quality);
        } elseif (is_array($input) && isset($input['tmp_name']) && (is_uploaded_file($input['tmp_name']) || file_exists($input['tmp_name']))) {
            // Input berupa array $_FILES
            $converted = self::convertToWebp($input['tmp_name'], $destPath, $quality);
        }

        if ($converted) {
            // Hapus foto profil lama jika bukan default.jpg
            if ($oldPhoto && $oldPhoto !== 'default.jpg') {
                $oldPath = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $oldPhoto;
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            return $newFileName;
        }

        return null;
    }
}
