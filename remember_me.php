
<?php

/**
 * Remember-me helpers for Orchid Boutique.
 * Store only a hash of the long-lived validator in the database.
 */

function rememberCookieName(string $accountType): string
{
    return $accountType === 'admin'
        ? 'orchid_admin_remember'
        : 'orchid_user_remember';
}

function rememberCookieOptions(int $expires): array
{
    return [
        'expires' => $expires,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function forgetRememberToken(PDO $pdo, string $accountType): void
{
    if (!in_array($accountType, ['user', 'admin'], true)) {
        return;
    }

    $cookieName = rememberCookieName($accountType);
    $rawCookie = $_COOKIE[$cookieName] ?? '';

    if (is_string($rawCookie) && $rawCookie !== '') {
        $parts = explode(':', $rawCookie, 2);
        $selector = $parts[0] ?? '';

        if (preg_match('/^[a-f0-9]{32}$/i', $selector)) {
            $stmt = $pdo->prepare(
                'DELETE FROM remember_tokens
                 WHERE account_type = :type
                   AND token_selector = :selector'
            );

            $stmt->execute([
                'type' => $accountType,
                'selector' => $selector,
            ]);
        }
    }

    setcookie(
        $cookieName,
        '',
        rememberCookieOptions(time() - 3600)
    );

    unset($_COOKIE[$cookieName]);
}

function issueRememberToken(
    PDO $pdo,
    string $accountType,
    int $accountId,
    int $days
): void {
    if (
        !in_array($accountType, ['user', 'admin'], true) ||
        $accountId < 1 ||
        $days < 1
    ) {
        return;
    }

    // Replace any remember token belonging to this browser.
    forgetRememberToken($pdo, $accountType);

    $selector = bin2hex(random_bytes(16));
    $validator = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $validator);
    $expires = time() + ($days * 86400);

    $stmt = $pdo->prepare(
        'INSERT INTO remember_tokens
            (account_type, account_id, token_selector, token_hash, expires_at)
         VALUES
            (:type, :account_id, :selector, :token_hash, :expires_at)'
    );

    $stmt->execute([
        'type' => $accountType,
        'account_id' => $accountId,
        'selector' => $selector,
        'token_hash' => $tokenHash,
        'expires_at' => date('Y-m-d H:i:s', $expires),
    ]);

    $cookieValue = $selector . ':' . $validator;

    setcookie(
        rememberCookieName($accountType),
        $cookieValue,
        rememberCookieOptions($expires)
    );

    $_COOKIE[rememberCookieName($accountType)] = $cookieValue;
}

function restoreRememberedLogin(
    PDO $pdo,
    string $accountType
): bool {
    if (!in_array($accountType, ['user', 'admin'], true)) {
        return false;
    }

    $sessionKey = $accountType === 'admin' ? 'admin_id' : 'user_id';

    if (isset($_SESSION[$sessionKey])) {
        return true;
    }

    $cookieName = rememberCookieName($accountType);
    $rawCookie = $_COOKIE[$cookieName] ?? '';

    if (!is_string($rawCookie) || $rawCookie === '') {
        return false;
    }

    $parts = explode(':', $rawCookie, 2);
    $selector = $parts[0] ?? '';
    $validator = $parts[1] ?? '';

    if (
        !preg_match('/^[a-f0-9]{32}$/i', $selector) ||
        !preg_match('/^[a-f0-9]{64}$/i', $validator)
    ) {
        forgetRememberToken($pdo, $accountType);
        return false;
    }

    $stmt = $pdo->prepare(
        'SELECT id, account_id, token_hash,
                UNIX_TIMESTAMP(expires_at) AS expires_timestamp
         FROM remember_tokens
         WHERE account_type = :type
           AND token_selector = :selector
           AND expires_at > NOW()
         LIMIT 1'
    );

    $stmt->execute([
        'type' => $accountType,
        'selector' => $selector,
    ]);

    $token = $stmt->fetch(PDO::FETCH_ASSOC);

    if (
        !$token ||
        !hash_equals(
            (string) $token['token_hash'],
            hash('sha256', $validator)
        )
    ) {
        forgetRememberToken($pdo, $accountType);
        return false;
    }

    $accountStmt = $pdo->prepare(
        $accountType === 'admin'
            ? 'SELECT id, username FROM admin_users WHERE id = :id LIMIT 1'
            : 'SELECT id, full_name, email, phone, profile_image
               FROM users WHERE id = :id LIMIT 1'
    );

    $accountStmt->execute(['id' => $token['account_id']]);
    $account = $accountStmt->fetch(PDO::FETCH_ASSOC);

    $expires = (int) $token['expires_timestamp'];

    if (!$account || $expires <= time()) {
        forgetRememberToken($pdo, $accountType);
        return false;
    }

    // Rotate the token after successful restoration.
    $newSelector = bin2hex(random_bytes(16));
    $newValidator = bin2hex(random_bytes(32));
    $newHash = hash('sha256', $newValidator);

    $update = $pdo->prepare(
        'UPDATE remember_tokens
         SET token_selector = :selector,
             token_hash = :token_hash
         WHERE id = :id AND account_type = :type'
    );

    $update->execute([
        'selector' => $newSelector,
        'token_hash' => $newHash,
        'id' => $token['id'],
        'type' => $accountType,
    ]);

    if ($update->rowCount() !== 1) {
        forgetRememberToken($pdo, $accountType);
        return false;
    }

    session_regenerate_id(true);

    if ($accountType === 'admin') {
        $_SESSION['admin_id'] = (int) $account['id'];
        $_SESSION['admin_username'] = $account['username'];
    } else {
        $_SESSION['user_id'] = (int) $account['id'];
        $_SESSION['user_name'] = $account['full_name'];
        $_SESSION['user_email'] = $account['email'];
        $_SESSION['user_phone'] = $account['phone'] ?? '';
        $_SESSION['user_image'] = $account['profile_image'] ?? '';
    }

    $newCookieValue = $newSelector . ':' . $newValidator;

    setcookie(
        $cookieName,
        $newCookieValue,
        rememberCookieOptions($expires)
    );

    $_COOKIE[$cookieName] = $newCookieValue;

    return true;
}
