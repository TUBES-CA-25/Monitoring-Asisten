<?php
/**
 * ImageHelper — Konversi otomatis gambar yang diupload ke format WebP, dan
 * (khusus foto presensi) menambahkan bingkai putih berisi lokasi & jam.
 *
 * Dipakai di SEMUA titik upload gambar (profil, presensi, bukti izin, dll)
 * baik di web controllers (app/controllers/*.php) maupun REST API mobile
 * (app/api/*.php) - class ini SENGAJA berdiri sendiri (bukan method di
 * Controller) karena app/api/*.php TIDAK extends Controller.
 *
 * WebP menghasilkan ukuran file jauh lebih kecil dari JPG/PNG pada kualitas
 * visual yang setara, mengurangi beban bandwidth & penyimpanan situs.
 *
 * ── Cara pakai (upload biasa via $_FILES) ──────────────────────
 *   $webpName = ImageHelper::convertUploadToWebp($_FILES['photo']['tmp_name'], $targetDir, $baseNameWithoutExt);
 *
 * ── Cara pakai (hasil crop Cropper.js / capture kamera, base64 data URL) ─
 *   $webpName = ImageHelper::convertDataToWebp($decodedBinaryData, $targetDir, $baseNameWithoutExt);
 *
 * ── Cara pakai untuk foto PRESENSI (bingkai putih + lokasi/jam) ─────────
 *   $webpName = ImageHelper::convertDataToWebp($decodedBinaryData, $targetDir, $baseNameWithoutExt, 82, [
 *       'location' => $lokasiText,  // tampil pojok KIRI-BAWAH bingkai
 *       'time'     => $jamText,     // tampil pojok KANAN-ATAS bingkai
 *   ]);
 *   Bingkai HANYA ditambahkan kalau parameter $frame diisi (location dan/atau
 *   time tidak kosong) - foto profil/bukti izin yang tidak mengirim $frame
 *   tetap tersimpan apa adanya, tanpa bingkai.
 *
 * Semua method mengembalikan null (tanpa efek samping) kalau GD/WebP tidak
 * tersedia atau datanya bukan raster image yang didukung (mis. PDF/DOC pada
 * bukti izin) - pemanggil lalu fallback ke cara simpan lama (move_uploaded_file
 * / file_put_contents) dengan ekstensi aslinya.
 */
class ImageHelper
{
    /**
     * Konversi file yang baru diupload ($_FILES[...]['tmp_name']) menjadi WebP.
     *
     * @param  string     $tmpPath            $_FILES['x']['tmp_name']
     * @param  string     $targetDir          Folder tujuan (harus sudah ada, diakhiri '/')
     * @param  string     $baseNameWithoutExt Nama file tanpa ekstensi
     * @param  int        $quality            Kualitas WebP 0-100 (default 82)
     * @param  array|null $frame              ['location' => ?string, 'time' => ?string] - lihat catatan di atas kelas
     * @return string|null                    Nama file final (dengan ".webp") jika sukses, null jika tidak didukung/gagal
     */
    public static function convertUploadToWebp(string $tmpPath, string $targetDir, string $baseNameWithoutExt, int $quality = 82, ?array $frame = null): ?string
    {
        if (!is_uploaded_file($tmpPath)) return null;
        $raw = @file_get_contents($tmpPath);
        if ($raw === false) return null;
        return self::convertDataToWebp($raw, $targetDir, $baseNameWithoutExt, $quality, $frame);
    }

    /**
     * Konversi data biner gambar mentah (mis. hasil base64_decode() dari
     * crop Cropper.js / capture kamera presensi) menjadi WebP.
     *
     * @param  string     $rawData            Isi file gambar mentah (bukan base64 - sudah didecode)
     * @param  string     $targetDir          Folder tujuan (harus sudah ada, diakhiri '/')
     * @param  string     $baseNameWithoutExt Nama file tanpa ekstensi
     * @param  int        $quality            Kualitas WebP 0-100 (default 82)
     * @param  array|null $frame              ['location' => ?string, 'time' => ?string] - lihat catatan di atas kelas
     * @return string|null                    Nama file final (dengan ".webp") jika sukses, null jika tidak didukung/gagal
     */
    public static function convertDataToWebp(string $rawData, string $targetDir, string $baseNameWithoutExt, int $quality = 82, ?array $frame = null): ?string
    {
        if (!function_exists('imagewebp') || !function_exists('imagecreatefromstring')) {
            return null;
        }

        // imagecreatefromstring() mendeteksi format otomatis dari isi data
        // (JPEG/PNG/GIF/WEBP/BMP) - mengembalikan false untuk data yang bukan
        // gambar (mis. PDF/DOC), jadi aman dipakai sebagai satu-satunya jalur
        // deteksi tanpa perlu tahu MIME type di muka.
        $image = @imagecreatefromstring($rawData);
        if (!$image) return null;

        // Pertahankan transparansi (PNG/GIF) & hindari latar hitam saat
        // dikonversi dari palet warna ke true color.
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        $location = trim((string) ($frame['location'] ?? ''));
        $time     = trim((string) ($frame['time'] ?? ''));
        if ($location !== '' || $time !== '') {
            $framed = self::drawFrame($image, $location ?: null, $time ?: null);
            if ($framed) {
                imagedestroy($image);
                $image = $framed;
            }
        }

        $finalName = $baseNameWithoutExt . '.webp';
        $ok = @imagewebp($image, $targetDir . $finalName, $quality);
        imagedestroy($image);

        return $ok ? $finalName : null;
    }

    /**
     * Tambahkan bingkai putih di sekeliling foto presensi (gaya kamera
     * timestamp/polaroid): lokasi presensi di pojok KIRI-BAWAH bingkai, jam
     * pengambilan/pengisian foto di pojok KANAN-ATAS bingkai. Dipakai KHUSUS
     * untuk foto presensi (check-in/check-out) - tidak untuk foto profil
     * atau bukti izin.
     *
     * Teks digambar dengan font bitmap bawaan GD (bukan file .ttf eksternal)
     * supaya berjalan portabel di instalasi PHP/GD mana pun tanpa perlu
     * membundel file font tambahan, lalu di-scale-up karena ukuran font
     * bawaan GD kecil & tetap.
     */
    private static function drawFrame($image, ?string $locationText, ?string $timeText)
    {
        $srcW = imagesx($image);
        $srcH = imagesy($image);
        $border = (int) max(56, round(min($srcW, $srcH) * 0.09));
        $pad = (int) round($border * 0.24);

        $framedW = $srcW + $border * 2;
        $framedH = $srcH + $border * 2;

        $framed = imagecreatetruecolor($framedW, $framedH);
        if (!$framed) return null;

        $white = imagecolorallocate($framed, 255, 255, 255);
        imagefill($framed, 0, 0, $white);
        imagecopy($framed, $image, $border, $border, 0, 0, $srcW, $srcH);

        $textColor = [30, 41, 59]; // slate-800, senada dengan tema biru/gelap situs
        $maxTextWidth = $framedW - $pad * 2;

        if ($locationText) {
            self::drawScaledText(
                $framed, 'Lokasi: ' . $locationText,
                $pad, $framedH - $border + $pad,
                $textColor, 'left', $border, $maxTextWidth
            );
        }

        if ($timeText) {
            self::drawScaledText(
                $framed, $timeText,
                $framedW - $pad, $pad,
                $textColor, 'right', $border, $maxTextWidth
            );
        }

        return $framed;
    }

    /**
     * Gambar teks dengan font bitmap bawaan GD, dipotong otomatis (dengan
     * "...") supaya tidak pernah melebihi $maxWidth, lalu di-scale-up ke
     * ukuran yang proporsional dengan $availableHeight (tinggi bingkai)
     * supaya cukup terbaca pada foto beresolusi tinggi.
     */
    private static function drawScaledText($destImage, string $text, int $x, int $y, array $rgb, string $align, int $availableHeight, int $maxWidth): void
    {
        $font  = 5; // font bitmap terbesar bawaan GD - tidak butuh file .ttf eksternal
        $charW = imagefontwidth($font);
        $charH = imagefontheight($font);

        $scale = max(1, (int) round(($availableHeight * 0.36) / $charH));
        $scale = min($scale, 4);

        // Potong teks supaya lebar akhirnya (setelah di-scale) tidak
        // melebihi $maxWidth.
        $maxChars = max(4, (int) floor($maxWidth / ($charW * $scale)));
        if (strlen($text) > $maxChars) {
            $text = substr($text, 0, max(1, $maxChars - 3)) . '...';
        }

        $textW = max(1, $charW * strlen($text));

        $tmp = imagecreatetruecolor($textW, $charH);
        $tmpWhite = imagecolorallocate($tmp, 255, 255, 255);
        imagefill($tmp, 0, 0, $tmpWhite);
        $tmpColor = imagecolorallocate($tmp, $rgb[0], $rgb[1], $rgb[2]);
        imagestring($tmp, $font, 0, 0, $text, $tmpColor);

        $scaledW = $textW * $scale;
        $scaledH = $charH * $scale;
        $destX = ($align === 'right') ? ($x - $scaledW) : $x;

        imagecopyresized($destImage, $tmp, $destX, $y, 0, 0, $scaledW, $scaledH, $textW, $charH);
        imagedestroy($tmp);
    }
}
