<?php
/**
 * Encryption Helper untuk Luxera Dompet Manager
 * FIXED VERSION: Support both BASE64 and BINARY data from database
 * 
 * CHANGELOG:
 * - Added auto-detection for binary vs base64 data
 * - Fixed decrypt() to handle raw binary from database
 * - Added better error handling
 */

class WalletEncryption {
    private $key;
    private $algorithm = 'aes-256-gcm';

    public function __construct($key) {
        // Ensure key is exactly 32 bytes for AES-256
        $this->key = hash('sha256', $key, true);
    }

    /**
     * Encrypt plaintext
     * Returns array with base64 encoded values
     */
    public function encrypt($plaintext) {
        if (empty($plaintext)) {
            throw new Exception('Plaintext cannot be empty');
        }

        $iv = random_bytes(16); // 16 bytes for AES
        $tag = ''; // Will be filled by openssl_encrypt

        $ciphertext = openssl_encrypt(
            $plaintext,
            $this->algorithm,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '', // AAD (optional)
            16  // Tag length
        );

        if ($ciphertext === false) {
            throw new Exception('Encryption failed: ' . openssl_error_string());
        }

        return [
            'ciphertext' => base64_encode($ciphertext),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag)
        ];
    }

    /**
     * Decrypt data
     * AUTO-DETECT: Handles both base64 strings and binary data
     * 
     * @param string $ciphertext Base64 string OR binary data
     * @param string $iv Base64 string OR binary data  
     * @param string $tag Base64 string OR binary data
     * @return string|false Decrypted text or false on failure
     */
    public function decrypt($ciphertext, $iv, $tag) {
        if (empty($ciphertext) || empty($iv) || empty($tag)) {
            return false;
        }

        try {
            // Convert inputs to binary (handle both base64 and raw binary)
            $cipherBin = $this->toBinary($ciphertext);
            $ivBin = $this->toBinary($iv);
            $tagBin = $this->toBinary($tag);

            if ($cipherBin === false || $ivBin === false || $tagBin === false) {
                return false;
            }

            // Validate lengths
            if (strlen($ivBin) !== 16) {
                error_log("Decrypt Error: IV length is " . strlen($ivBin) . ", expected 16");
                return false;
            }

            if (strlen($tagBin) !== 16) {
                error_log("Decrypt Error: Tag length is " . strlen($tagBin) . ", expected 16");
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

            return ($result !== false) ? $result : false;

        } catch (Exception $e) {
            error_log("Decrypt Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Convert data to binary
     * Handles: base64 strings, hex strings (0x...), or raw binary
     */
    private function toBinary($data) {
        if (empty($data)) return false;

        // If it's already binary (contains non-printable chars), return as-is
        if (!ctype_print($data) && !ctype_space($data)) {
            return $data;
        }

        // If it looks like base64 (only valid base64 chars)
        if (preg_match('/^[A-Za-z0-9+\/]*={0,2}$/', $data) && strlen($data) > 4) {
            $decoded = base64_decode($data, true);
            if ($decoded !== false && base64_encode($decoded) === $data) {
                return $decoded;
            }
        }

        // If it looks like hex with 0x prefix
        if (substr($data, 0, 2) === '0x' || substr($data, 0, 2) === '0X') {
            $hex = substr($data, 2);
            if (ctype_xdigit($hex) && strlen($hex) % 2 === 0) {
                return hex2bin($hex);
            }
        }

        // If it's plain hex (without 0x)
        if (ctype_xdigit($data) && strlen($data) % 2 === 0 && strlen($data) > 2) {
            return hex2bin($data);
        }

        // Assume it's raw binary data
        return $data;
    }

    /**
     * Check if a string is valid base64
     */
    private function isValidBase64($string) {
        if (!is_string($string)) return false;
        if (strlen($string) % 4 !== 0) return false;

        return preg_match('/^[A-Za-z0-9+\/]*={0,2}$/', $string) === 1;
    }
}

// Session helper functions
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

/**
 * Helper function to debug encryption issues
 */
function debugEncryptData($data, $label = 'Data') {
    $info = [
        'label' => $label,
        'length' => strlen($data),
        'hex' => bin2hex(substr($data, 0, 50)), // First 50 bytes as hex
        'is_binary' => !ctype_print($data),
        'base64_valid' => (base64_decode($data, true) !== false)
    ];
    error_log(print_r($info, true));
    return $info;
}
?>