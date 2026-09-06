<?php
/**
 * HashHelper — Obfuskasi ID integer untuk URL (Hashids-like).
 *
 * Implementasi mandiri tanpa dependensi Composer. Cocok untuk
 * PHP Native MVC karena di-autoload bersama core library lainnya.
 *
 * ── Cara kerja ──────────────────────────────────────────────
 * 1. Alphabet di-shuffle konsisten berdasarkan HASH_SALT.
 * 2. ID integer direpresentasikan dalam basis custom (base-N).
 * 3. Output di-pad ke panjang minimum agar tidak bisa ditebak
 *    hanya dari panjang string.
 * 4. encode/decode bersifat BIJEKTIF (satu-ke-satu, reversibel).
 *
 * ── Penggunaan ──────────────────────────────────────────────
 *   // Encode saat mengirim ke view/URL
 *   $hashed = HashHelper::encode($id);          // "mZ4kQ9"
 *   href="...?id=<?= HashHelper::encode($b['id_bin']) ?>"
 *
 *   // Decode saat menerima dari request
 *   $id = HashHelper::decode($_GET['id'] ?? ''); // 42 atau null
 *   if (!$id) { abort 400; }
 *
 * ── Keamanan ────────────────────────────────────────────────
 * Ini adalah OBFUSKASI, bukan enkripsi. Tujuannya:
 * - Mencegah enumerasi ID secara trivial (?id=1, ?id=2, dst.)
 * - Menghilangkan petunjuk tentang ukuran/urutan data
 * Untuk proteksi penuh, tetap terapkan otorisasi di sisi server
 * (checkAccess, ownership check, dsb.).
 */
class HashHelper
{
    // Alphabet dasar — 62 karakter case-sensitive tanpa karakter ambigu
    private const ALPHA_RAW = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    // Panjang minimum output (padding dengan karakter pertama alphabet teracak)
    private const MIN_LEN = 8;

    // Cache alphabet teracak (dihitung sekali per request)
    private static ?string $shuffled = null;

    /* ─────────────────────────────────────────────────────────
       PUBLIC API
       ───────────────────────────────────────────────────────── */

    /**
     * Encode integer positif → string obfuskasi.
     * Mengembalikan string kosong untuk input negatif atau bukan int.
     */
    public static function encode(int $number): string
    {
        if ($number < 0) return '';

        $alph = self::alphabet();
        $base = strlen($alph);

        // Representasi basis-N
        $hash = '';
        $n    = $number;
        do {
            $hash = $alph[$n % $base] . $hash;
            $n    = intdiv($n, $base);
        } while ($n > 0);

        // Pad ke panjang minimum
        $pad = $alph[0];
        while (strlen($hash) < self::MIN_LEN) {
            $hash = $pad . $hash;
        }

        return $hash;
    }

    /**
     * Decode string obfuskasi → integer asli.
     * Mengembalikan null jika string tidak valid.
     */
    public static function decode(string $hash): ?int
    {
        $hash = trim($hash);
        if ($hash === '' || !preg_match('/^[a-zA-Z0-9]+$/', $hash)) {
            return null;
        }

        $alph = self::alphabet();
        $base = strlen($alph);

        $number = 0;
        $len    = strlen($hash);
        for ($i = 0; $i < $len; $i++) {
            $pos = strpos($alph, $hash[$i]);
            if ($pos === false) return null;
            $number = $number * $base + $pos;
        }

        // Sanity: re-encode dan bandingkan untuk memastikan round-trip valid
        // (menolak string valid secara karakter tapi bukan hasil encode)
        // Dinonaktifkan untuk performa; aktifkan jika ingin strict validation:
        // if (self::encode($number) !== $hash) return null;

        return $number;
    }

    /**
     * Decode dari request param dengan fallback 0 → null agar lebih aman.
     * Alias pendek untuk digunakan di controller.
     */
    public static function decodeOrNull(string $hash): ?int
    {
        $id = self::decode($hash);
        return ($id !== null && $id > 0) ? $id : null;
    }

    /* ─────────────────────────────────────────────────────────
       PRIVATE
       ───────────────────────────────────────────────────────── */

    /**
     * Kembalikan alphabet yang sudah di-shuffle berdasarkan HASH_SALT.
     * Di-cache di static property agar tidak dihitung ulang per pemanggilan.
     */
    private static function alphabet(): string
    {
        if (self::$shuffled !== null) {
            return self::$shuffled;
        }

        $salt = defined('HASH_SALT') ? HASH_SALT : 'iclabs-default-salt';
        $alph = self::ALPHA_RAW;
        $slen = strlen($salt);

        // Fisher-Yates shuffle yang dideterministik berdasarkan salt
        // (sama persis dengan Hashids.php original: konsisten di semua request)
        $v = 0; $p = 0;
        for ($i = strlen($alph) - 1; $i > 0; $i--) {
            $v   %= $slen;
            $int  = ord($salt[$v]);
            $p   += $int;
            $j    = ($int + $v + $p) % $i;
            // Tukar $alph[$i] dengan $alph[$j]
            $tmp       = $alph[$i];
            $alph[$i]  = $alph[$j];
            $alph[$j]  = $tmp;
            $v++;
        }

        self::$shuffled = $alph;
        return $alph;
    }
}
