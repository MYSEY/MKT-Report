<?php

namespace App\Helpers;

class Helper
{
    static function getLang()
    {
        return app()->getLocale();
    }
    static function verifyPbkdf2($password, $hashed)
        {  if (!str_starts_with($hashed, 'pbkdf2:')) {
            return false;
        }

        // pbkdf2:sha1:1000$salt$hash
        [$meta, $salt, $hash] = explode('$', $hashed);
        [, $algo, $iterations] = explode(':', $meta);

        $computed = hash_pbkdf2(
            $algo,
            $password,
            $salt,
            (int) $iterations,
            0,
            false
        );

        return hash_equals($hash, $computed);
    }
}