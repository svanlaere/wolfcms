<?php
/*
 * Wolf CMS - Content Management Simplified. <http://www.wolfcms.org>
 * Copyright (C) 2010 Martijn van der Kleijn <martijn.niji@gmail.com>
 *
 * This file is part of Wolf CMS. Wolf CMS is licensed under the GNU GPLv3 license.
 * Please see license.txt for the full license text.
 */

// Making sure all constants are defined to their safe defaults
if (!defined('SECURE_TOKEN_EXPIRY')) define ('SECURE_TOKEN_EXPIRY', 900);  // 15 minutes

/**
 * SecureToken model for generating and validating secure tokens.
 *
 * @package Models
 *
 * @author Martijn van der Kleijn <martijn.niji@gmail.com>
 * @copyright Martijn van der Kleijn, 2010
 * @license http://www.gnu.org/licenses/gpl.html GPLv3 license
 *
 * @phpstan-type TokenArray array{
 *     where: string,
 *     values: array<string, mixed>
 * }
 */
final class SecureToken extends Record
{
    public const TABLE_NAME = 'secure_token';

    /** @var int|null */
    public $id;

    /** @var string|null */
    public $username;

    /** @var string|null */
    public $url;

    /** @var float|int|null */
    public $time;

    /**
     * Generates a security token for use in forms.
     *
     * The token is generated to be as secure as possible. It consists of:
     * - the username,
     * - the time at which the token was generated,
     * - a partial sha256 result of the user's password,
     * - the url for which the token is valid,
     * - a random salt generated during user creation
     *
     * The token is the sha256 of: <username>.<time>.<url>.<salt>.<partial_pwd>
     *
     * The validateToken() method should always be used to check a token's validity.
     *
     * @see Hash Helper
     *
     * @param string $url
     * @return string Returns a valid token or false upon error.
     */
    /**
     * @param string $url
     * @return string|false
     */
    public static function generateToken($url)
    {
        AuthUser::load();

        if (AuthUser::isLoggedIn()) {
            /** @var object{username: string, password: string, salt?: string} $user */
            $user = AuthUser::getRecord();
            $time = microtime(true);
            $target_url = str_replace('&amp;', '&', $url);
            $pwd = substr(bin2hex(hash('sha256', (string)$user->password, true)), 5, 20);

            $oldtoken = SecureToken::getToken($user->username, $target_url);

            if (false === $oldtoken) {
                $oldtoken = new SecureToken();

                $oldtoken->username = $user->username;
                $oldtoken->url = bin2hex(hash('sha256', $target_url, true));
                $oldtoken->time = $time;

                $oldtoken->save();
            } else {
                $oldtoken->username = $user->username;
                $oldtoken->url = bin2hex(hash('sha256', $target_url, true));
                $oldtoken->time = $time;

                $oldtoken->save();
            }

            return bin2hex(hash('sha256', $user->username . $time . $target_url . $pwd . ($user->salt ?? ''), true));
        }

        return false;
    }

    /**
     * Validates whether a given secure token is still valid.
     *
     * The validateToken() method validates the token is valid by checking:
     * - that the token is not expired (through the time),
     * - the token is valid for this user,
     * - the token is valid for this url
     *
     * It does so by reconstructing the token. If at any time during the valid
     * period of the token, the username, user password or the url changed, the
     * token is considered invalid.
     *
     * The token is also considered invalid if more than SECURE_TOKEN_EXPIRY seconds
     * have passed.
     *
     * @param string $token The token.
     * @param string $url   The url for which the token was generated.
     * @return bool         True if the token is valid, otherwise false.
     */
    public static final function validateToken($token, $url)
    {
        AuthUser::load();

        if (AuthUser::isLoggedIn()) {
            /** @var object{username: string, password: string, salt?: string} $user */
            $user = AuthUser::getRecord();
            $target_url = str_replace('&amp;', '&', $url);
            $pwd = substr(bin2hex(hash('sha256', (string)$user->password, true)), 5, 20);

            $time = SecureToken::getTokenTime($user->username, $target_url);

            if ((microtime(true) - $time) > SECURE_TOKEN_EXPIRY) {
                return false;
            }

            if (!isset($user->salt)) {
                return (bin2hex(hash('sha256', $user->username . $time . $target_url . $pwd, true)) === $token);
            } else {
                return (bin2hex(hash('sha256', $user->username . $time . $target_url . $pwd . $user->salt, true)) === $token);
            }
        }

        return false;
    }

    /**
     * Get the SecureToken record for a username and url.
     *
     * @param string $username
     * @param string $url
     * @return SecureToken|false
     */
    private static function getToken($username, $url)
    {
        /** @var SecureToken|false|null $token */
        $token = self::findOne([
            'where' => 'username = :username AND url = :url',
            'values' => [
                ':username' => $username,
                ':url' => bin2hex(hash('sha256', $url, true))
            ]
        ]);

        if ($token !== null && $token !== false && $token instanceof SecureToken) {
            return $token;
        }

        return false;
    }

    /**
     * Get the token time for a username and url.
     *
     * @param string $username
     * @param string $url
     * @return float|int
     */
    private static function getTokenTime($username, $url)
    {
        $time = 0;

        /** @var SecureToken|false|null $token */
        $token = self::findOne([
            'where' => 'username = :username AND url = :url',
            'values' => [
                ':username' => $username,
                ':url' => bin2hex(hash('sha256', $url, true))
            ]
        ]);

        if ($token) {
            $time = $token->time;
        }

        return $time;
    }
}
