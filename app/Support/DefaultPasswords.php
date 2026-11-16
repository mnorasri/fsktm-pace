<?php

namespace App\Support;

/**
 * The passwords accounts are created with. Signing in with any of these means
 * the account has never chosen a password of its own, so the user must pick a
 * new one before the rest of the portal opens.
 *
 * The legacy values cover accounts seeded or imported before the current
 * default was introduced (students used "P@ssw0rd", lecturers "R@hsi4#").
 */
class DefaultPasswords
{
    /**
     * The password new accounts start with.
     */
    public const CURRENT = 'R@hsi4';

    /**
     * Defaults used by older seeds and imports.
     *
     * @var list<string>
     */
    public const LEGACY = ['P@ssw0rd', 'R@hsi4#'];

    /**
     * Every password that identifies an account which never chose its own.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::CURRENT, ...self::LEGACY];
    }

    /**
     * Whether a plain password is one of the shared defaults.
     */
    public static function matches(?string $plain): bool
    {
        $plain = trim((string) $plain);

        return $plain !== '' && in_array($plain, self::all(), true);
    }
}
