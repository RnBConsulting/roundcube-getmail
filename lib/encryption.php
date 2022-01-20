<?php
/**
 * A class to handle secure encryption and decryption of arbitrary data
 * Copyright http://stackoverflow.com/users/338665/ircmaxell
 * http://stackoverflow.com/questions/5089841/php-2-way-encryption-i-need-to-store-passwords-that-can-be-retrieved
 *
 *
 * Note that this is not just straight encryption.  It also has a few other
 *  features in it to make the encrypted data far more secure.  Note that any
 *  other implementations used to decrypt data will have to do the same exact
 *  operations.
 *
 * Security Benefits:
 *
 * - Uses Key stretching
 * - Hides the Initialization Vector
 * - Does HMAC verification of source data
 *
 */
class Encryption {

    /**
     * @var int $rounds The number of rounds to feed into PBKDF2 for key generation
     */
    protected $rounds = 100;

    /**
     * Constructor!
     *
     * @param int    $rounds The number of PBKDF2 rounds to do on the key
     */
    public function __construct($rounds = 100) {
        $this->rounds = (int) $rounds;
    }

    /**
     * Decrypt the data with the provided key
     *
     * @param string $data The encrypted datat to decrypt
     * @param string $key  The key to use for decryption
     *
     * @returns string|false The returned string if decryption is successful
     *                           false if it is not
     */
    public function decrypt($data, $key) {
        $salt = substr($data, 0, 128);
        $enc = substr($data, 128, -64);
        $mac = substr($data, -64);

        list ($cipherKey, $macKey, $iv) = $this->getKeys($salt, $key);

        if ($mac !== hash_hmac('sha512', $enc, $macKey, true)) {
             return false;
        }

        $dec = openssl_decrypt($enc, "BF-CBC", $cipherKey, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING | OPENSSL_DONT_ZERO_PAD_KEY, $iv);

        $data = $this->unpad($dec);

        return $data;
    }

    /**
     * Encrypt the supplied data using the supplied key
     *
     * @param string $data The data to encrypt
     * @param string $key  The key to encrypt with
     *
     * @returns string The encrypted data
     */
    public function encrypt($data, $key) {
        // The length of the salt is hardcoded
        $salt = openssl_random_pseudo_bytes(128);
        list ($cipherKey, $macKey, $iv) = $this->getKeys($salt, $key);

        $data = $this->pad($data);

        $enc = openssl_encrypt($data, "BF-CBC", $cipherKey, OPENSSL_RAW_DATA | OPENSSL_DONT_ZERO_PAD_KEY | OPENSSL_NO_PADDING, $iv);

        $mac = hash_hmac('sha512', $enc, $macKey, true);
        return $salt . $enc . $mac;
    }

    /**
     * Generates a set of keys given a random salt and a master key
     *
     * @param string $salt A random string to change the keys each encryption
     * @param string $key  The supplied key to encrypt with
     *
     * @returns array An array of keys (a cipher key, a mac key, and a IV)
     */
    protected function getKeys($salt, $key) {
        $ivSize = openssl_cipher_iv_length("BF-CBC");
        // This used to be mcrypt's key size; mcrypt returns the
        // maximum supported length by default, so we can hardcode it here.
        $keySize = 56;
        $length = 2 * $keySize + $ivSize;

        $key = $this->pbkdf2('sha512', $key, $salt, $this->rounds, $length);

        $cipherKey = substr($key, 0, $keySize);
        $macKey = substr($key, $keySize, $keySize);
        $iv = substr($key, 2 * $keySize);
        return array($cipherKey, $macKey, $iv);
    }

    /**
     * Stretch the key using the PBKDF2 algorithm
     *
     * @see http://en.wikipedia.org/wiki/PBKDF2
     *
     * @param string $algo   The algorithm to use
     * @param string $key    The key to stretch
     * @param string $salt   A random salt
     * @param int    $rounds The number of rounds to derive
     * @param int    $length The length of the output key
     *
     * @returns string The derived key.
     */
    protected function pbkdf2($algo, $key, $salt, $rounds, $length) {
        $size   = strlen(hash($algo, '', true));
        $len    = ceil($length / $size);
        $result = '';
        for ($i = 1; $i <= $len; $i++) {
            $tmp = hash_hmac($algo, $salt . pack('N', $i), $key, true);
            $res = $tmp;
            for ($j = 1; $j < $rounds; $j++) {
                 $tmp  = hash_hmac($algo, $tmp, $key, true);
                 $res ^= $tmp;
            }
            $result .= $res;
        }
        return substr($result, 0, $length);
    }

    protected function pad($data) {
        // This is not the same as mcrypt_get_block_size but works for
        // virtually all cases mcrypt supports. See
        // https://stackoverflow.com/questions/42350165/openssl-equivalent-to-mcrypt-get-block-size
        $length = openssl_cipher_iv_length("BF-CBC");
        $padAmount = $length - strlen($data) % $length;
        if ($padAmount == 0) {
            $padAmount = $length;
        }
        return $data . str_repeat(chr($padAmount), $padAmount);
    }

    protected function unpad($data) {
        // This is not the same as mcrypt_get_block_size but works for
        // virtually all cases mcrypt supports. See
        // https://stackoverflow.com/questions/42350165/openssl-equivalent-to-mcrypt-get-block-size
        $length = openssl_cipher_iv_length("BF-CBC");
        $last = ord($data[strlen($data) - 1]);
        if ($last > $length) return false;
        if (substr($data, -1 * $last) !== str_repeat(chr($last), $last)) {
            return false;
        }
        return substr($data, 0, -1 * $last);
    }
}
?>
