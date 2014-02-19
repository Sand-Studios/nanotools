<?php

namespace nanotools;

class Hash {

    /**
     * Creates a hash constructed from the data and salt.
     * @param string $algo The algorithm to use
     * @param string $data The data to hash
     * @param string $salt The salt to use
     * @return string The hashed data
     */
    public static function create($algo, $data, $salt = '') {
        if (empty($salt)) {
            return hash($algo, $data);
        }
        $context = hash_init($algo, HASH_HMAC, $salt);
        hash_update($context, $data);
        return hash_final($context);
    }

}
