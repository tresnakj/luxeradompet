<?php
/**
 * Encryption Helper untuk Luxera Dompet Manager
 * FIXED: Support both BASE64 and BINARY data from database
 */

class WalletEncryption {
    private $key;
    private $algorithm = 'aes-256-gcm';
    
    public function __construct($key) {
        $this->key = hash('sha256', $key, true);
    }
    
    public function encrypt($plaintext) {
        $iv = random_bytes(16);
        $tag = '';
        
        $ciphertext = openssl_encrypt(
            $plaintext,
            $this->algorithm,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );
        
        if ($ciphertext === false) {
            throw new Exception('Enkripsi gagal: ' . openssl_error_string());
        }
        
        return [
            'ciphertext' => base64_encode($ciphertext),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag)
        ];
    }
    
    /**
     * FIXED: Auto-detect format (base64 or binary)
     */
    public function decrypt($ciphertext, $iv, $tag) {
        if (empty($ciphertext) || empty($iv) || empty($tag)) {
            return false;
        }
        
        // Cek apakah data sudah binary (dari database) atau base64 (dari encrypt())
        $cipherBin = $this->ensureBinary($ciphertext);
        $ivBin = $this->ensureBinary($iv);
        $tagBin = $this->ensureBinary($tag);
        
        if ($cipherBin === false || $ivBin === false || $tagBin === false) {
            return false;
        }
        
        $result = openssl_decrypt(
            $cipherBin,
            $this->algorithm,
            $this->key,
            OPENSSL_RAW_DATA,
            $ivBin,
            $tagBin
        );
        
        return $result !== false ? $result : false;
    }
    
    /**
     * Helper: Convert to binary (handle both base64 string and binary data)
     */
    private function ensureBinary($data) {
        if (empty($data)) return false;
        
        // Jika sudah binary dan panjangnya sesuai, pakai langsung
        if (is_string($data)) {
            // Cek apakah valid base64
            if ($this->isValidBase64($data)) {
                $decoded = base64_decode($data, true);
                if ($decoded !== false) return $decoded;
            }
            // Jika bukan base64, anggap sudah binary
            return $data;
        }
        
        return false;
    }
    
    /**
     * Check if string is valid base64
     */
    private function isValidBase64($string) {
        if (!is_string($string)) return false;
        
        // Base64 hanya mengandung A-Z, a-z, 0-9, +, /, =
        if (!preg_match('/^[A-Za-z0-9+\/]*={0,2}$/', $string)) {
            return false;
        }
        
        // Coba decode
        $decoded = base64_decode($string, true);
        if ($decoded === false) return false;
        
        // Re-encode dan bandingkan (cek validitas)
        return base64_encode($decoded) === $string;
    }
}

// Fungsi helper untuk session
function initDecryptionSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isWalletUnlocked($id) {
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['unlocked_wallets'][$id])) {
        return $_SESSION['unlocked_wallets'][$id] > time();
    }
    return false;
}

function unlockWallet($id, $duration = 300) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['unlocked_wallets'][$id] = time() + $duration;
    }
}
?>