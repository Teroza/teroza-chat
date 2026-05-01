<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

chat_bootstrap_storage();
$requestedAction = (string)($_REQUEST['action'] ?? 'state');
if ($requestedAction === 'stream') {
    if (!CHAT_ENABLE_SSE) {
        http_response_code(204);
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        exit;
    }

    try {
        chat_handle_stream_request();
    } catch (Throwable $exception) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo "stream error\n";
        error_log('Chat stream error: ' . $exception->getMessage());
        exit;
    }
}

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function chat_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function chat_error(string $message, int $status = 400): never
{
    chat_response([
        'ok' => false,
        'error' => $message,
    ], $status);
}

function chat_require_new_message_permission(string $uid): void
{
    if (!chat_user_can_send_new_messages($uid)) {
        throw new RuntimeException('New messages are temporarily restricted to admins and owners.', 403);
    }
}

function chat_require_owner_access(string $uid): void
{
    if (!chat_is_owner_uid($uid)) {
        throw new RuntimeException('Owner access required.', 403);
    }
}

function chat_request_bool_value(string $value): bool
{
    $normalized = strtolower(trim($value));
    return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
}

function chat_identity_claims_file_path(): string
{
    return CHAT_DATA_DIR . '/identity-claims.json';
}

function chat_request_is_secure(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    return (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

function chat_random_hex(int $bytes = 32): string
{
    $bytes = max(16, min(64, $bytes));
    try {
        return bin2hex(random_bytes($bytes));
    } catch (Throwable $exception) {
        return hash('sha256', uniqid('chat_identity_', true) . '|' . microtime(true));
    }
}

function chat_identity_cookie_token(): string
{
    $raw = strtolower(trim((string)($_COOKIE['chat_identity'] ?? '')));
    if ($raw === '' || !preg_match('/^[a-f0-9]{32,128}$/', $raw)) {
        return '';
    }
    return $raw;
}

function chat_set_identity_cookie_token(string $token): void
{
    if ($token === '' || headers_sent()) {
        return;
    }

    $expiresAt = time() + (86400 * 365 * 2);
    $secure = chat_request_is_secure();
    $set = false;

    if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70300) {
        try {
            $set = setcookie('chat_identity', $token, [
                'expires' => $expiresAt,
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } catch (Throwable $exception) {
            $set = false;
        }
    } else {
        try {
            $set = setcookie('chat_identity', $token, $expiresAt, '/; samesite=Lax', '', $secure, true);
        } catch (Throwable $exception) {
            $set = false;
        }
        if (!$set) {
            try {
                $set = setcookie('chat_identity', $token, $expiresAt, '/', '', $secure, true);
            } catch (Throwable $exception) {
                $set = false;
            }
        }
    }

    if (!$set && !headers_sent()) {
        $parts = [
            'chat_identity=' . rawurlencode($token),
            'Expires=' . gmdate('D, d M Y H:i:s T', $expiresAt),
            'Max-Age=' . max(0, $expiresAt - time()),
            'Path=/',
            'HttpOnly',
            'SameSite=Lax',
        ];
        if ($secure) {
            $parts[] = 'Secure';
        }
        header('Set-Cookie: ' . implode('; ', $parts), false);
    }
    $_COOKIE['chat_identity'] = $token;
}

function chat_identity_claim_cookie_name(): string
{
    return 'chat_identity_uid';
}

function chat_identity_claim_cookie_read(string $tokenHash): string
{
    $raw = trim((string)($_COOKIE[chat_identity_claim_cookie_name()] ?? ''));
    if ($raw === '' || !str_contains($raw, '.')) {
        return '';
    }

    [$uidPart, $signature] = explode('.', $raw, 2);
    $uid = chat_sanitize_uid($uidPart);
    $signature = strtolower(trim($signature));
    if ($uid === '' || !preg_match('/^[a-f0-9]{64}$/', $signature)) {
        return '';
    }

    $expected = hash_hmac('sha256', $uid, $tokenHash);
    if (!hash_equals($expected, $signature)) {
        return '';
    }

    return $uid;
}

function chat_set_identity_claim_cookie(string $uid, string $tokenHash): void
{
    $safeUid = chat_sanitize_uid($uid);
    if ($safeUid === '' || $tokenHash === '' || headers_sent()) {
        return;
    }

    $value = $safeUid . '.' . hash_hmac('sha256', $safeUid, $tokenHash);
    $expiresAt = time() + (86400 * 365 * 2);
    $secure = chat_request_is_secure();
    $set = false;

    if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70300) {
        try {
            $set = setcookie(chat_identity_claim_cookie_name(), $value, [
                'expires' => $expiresAt,
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } catch (Throwable $exception) {
            $set = false;
        }
    } else {
        try {
            $set = setcookie(chat_identity_claim_cookie_name(), $value, $expiresAt, '/; samesite=Lax', '', $secure, true);
        } catch (Throwable $exception) {
            $set = false;
        }
        if (!$set) {
            try {
                $set = setcookie(chat_identity_claim_cookie_name(), $value, $expiresAt, '/', '', $secure, true);
            } catch (Throwable $exception) {
                $set = false;
            }
        }
    }

    if (!$set && !headers_sent()) {
        $parts = [
            chat_identity_claim_cookie_name() . '=' . rawurlencode($value),
            'Expires=' . gmdate('D, d M Y H:i:s T', $expiresAt),
            'Max-Age=' . max(0, $expiresAt - time()),
            'Path=/',
            'HttpOnly',
            'SameSite=Lax',
        ];
        if ($secure) {
            $parts[] = 'Secure';
        }
        header('Set-Cookie: ' . implode('; ', $parts), false);
    }

    $_COOKIE[chat_identity_claim_cookie_name()] = $value;
}

function chat_identity_claims_read(): array
{
    try {
        $claims = chat_json_read(chat_identity_claims_file_path(), []);
    } catch (Throwable $exception) {
        $claims = [];
    }
    if (!is_array($claims)) {
        $claims = [];
    }

    $rawUids = is_array($claims['uids'] ?? null) ? $claims['uids'] : [];
    $uids = [];
    $tokens = [];
    foreach ($rawUids as $uid => $entry) {
        $safeUid = chat_sanitize_uid((string)$uid);
        if ($safeUid === '' || !is_array($entry)) {
            continue;
        }

        $tokenHash = strtolower(trim((string)($entry['token_hash'] ?? '')));
        if (!preg_match('/^[a-f0-9]{64}$/', $tokenHash)) {
            continue;
        }

        $uids[$safeUid] = [
            'token_hash' => $tokenHash,
            'updated_at' => max(0, (int)($entry['updated_at'] ?? 0)),
        ];
        $tokens[$tokenHash] = $safeUid;
    }

    return [
        'uids' => $uids,
        'tokens' => $tokens,
    ];
}

function chat_identity_claims_write(array $claims): void
{
    try {
        chat_json_write(chat_identity_claims_file_path(), [
            'uids' => is_array($claims['uids'] ?? null) ? $claims['uids'] : [],
        ]);
    } catch (Throwable $exception) {
        // Identity claims are a hardening layer; request flow must keep working even if this write fails.
    }
}

function chat_identity_fallback_uid(string $tokenHash, int $attempt = 0): string
{
    $base = 'anon_' . substr(hash('sha256', $tokenHash . '|fallback'), 0, 14);
    if ($attempt <= 0) {
        return $base;
    }

    return substr($base, 0, 56) . '_' . dechex($attempt);
}

function chat_identity_claim_uid(string $requestedUid, string $tokenHash): string
{
    $claims = chat_identity_claims_read();
    $uids = is_array($claims['uids'] ?? null) ? $claims['uids'] : [];
    $tokens = is_array($claims['tokens'] ?? null) ? $claims['tokens'] : [];
    $candidateUid = chat_sanitize_uid($requestedUid);

    if ($candidateUid !== '' && chat_is_admin_uid($candidateUid)) {
        $changed = false;
        foreach ($uids as $uid => $entry) {
            if ($uid !== $candidateUid && hash_equals((string)($entry['token_hash'] ?? ''), $tokenHash)) {
                unset($uids[$uid]);
                $changed = true;
            }
        }
        $current = $uids[$candidateUid] ?? null;
        if (!is_array($current) || !hash_equals((string)($current['token_hash'] ?? ''), $tokenHash)) {
            $uids[$candidateUid] = [
                'token_hash' => $tokenHash,
                'updated_at' => chat_now(),
            ];
            $changed = true;
        }
        if ($changed) {
            chat_identity_claims_write(['uids' => $uids]);
        }
        return $candidateUid;
    }

    $alreadyBoundUid = chat_sanitize_uid((string)($tokens[$tokenHash] ?? ''));
    if ($alreadyBoundUid !== '' && isset($uids[$alreadyBoundUid])) {
        return $alreadyBoundUid;
    }

    if ($candidateUid === '') {
        $candidateUid = chat_identity_fallback_uid($tokenHash);
    }

    $ownerHash = strtolower((string)($uids[$candidateUid]['token_hash'] ?? ''));
    if ($ownerHash !== '' && !hash_equals($ownerHash, $tokenHash)) {
        $attempt = 0;
        do {
            $candidateUid = chat_identity_fallback_uid($tokenHash, $attempt);
            $ownerHash = strtolower((string)($uids[$candidateUid]['token_hash'] ?? ''));
            $attempt++;
        } while ($ownerHash !== '' && !hash_equals($ownerHash, $tokenHash) && $attempt < 64);

        if ($ownerHash !== '' && !hash_equals($ownerHash, $tokenHash)) {
            do {
                $candidateUid = 'anon_' . substr(chat_random_hex(20), 0, 14);
                $ownerHash = strtolower((string)($uids[$candidateUid]['token_hash'] ?? ''));
            } while ($ownerHash !== '' && !hash_equals($ownerHash, $tokenHash));
        }
    }

    $current = $uids[$candidateUid] ?? null;
    if (!is_array($current) || !hash_equals((string)($current['token_hash'] ?? ''), $tokenHash)) {
        $uids[$candidateUid] = [
            'token_hash' => $tokenHash,
            'updated_at' => chat_now(),
        ];
        chat_identity_claims_write(['uids' => $uids]);
    }

    return $candidateUid;
}

function chat_request_identity(): array
{
    $requestedUid = chat_sanitize_uid((string)($_REQUEST['uid'] ?? ''));
    $identityToken = chat_identity_cookie_token();
    if ($identityToken === '') {
        $identityToken = chat_random_hex(32);
        chat_set_identity_cookie_token($identityToken);
    } else {
        chat_set_identity_cookie_token($identityToken);
    }

    $tokenHash = hash('sha256', $identityToken);
    $uid = '';
    $claimedUid = chat_identity_claim_cookie_read($tokenHash);
    if ($claimedUid !== '') {
        $wantsAdminOverride = $requestedUid !== '' && $requestedUid !== $claimedUid && chat_is_admin_uid($requestedUid);
        if (!$wantsAdminOverride) {
            $uid = $claimedUid;
        }
    }

    if ($uid === '') {
        try {
            $uid = chat_with_lock(static function () use ($requestedUid, $tokenHash): string {
                return chat_identity_claim_uid($requestedUid, $tokenHash);
            });
        } catch (Throwable $exception) {
            $uid = '';
        }
    }

    if ($uid !== '') {
        chat_set_identity_claim_cookie($uid, $tokenHash);
    }
    if ($uid === '') {
        $uid = chat_identity_fallback_uid($tokenHash !== '' ? $tokenHash : hash('sha256', chat_random_hex(16)));
    }
    if ($uid !== '') {
        chat_set_identity_claim_cookie($uid, $tokenHash);
    }

    $displayName = chat_sanitize_name((string)($_REQUEST['display_name'] ?? ''));
    $theme = chat_sanitize_theme((string)($_REQUEST['theme'] ?? 'ultra'));

    return [
        'uid' => $uid,
        'display_name' => $displayName,
        'theme' => $theme,
    ];
}

function chat_pinned_payload_for_requested_context(string $uid, string $scope, string $channel, string $dmUid = ''): ?array
{
    return chat_pinned_message_for_context(
        $uid,
        $scope === 'dm' ? 'dm' : 'channel',
        $channel,
        $dmUid !== '' ? $dmUid : null
    );
}

function chat_pinned_payload_for_message_context(array $message): ?array
{
    $scope = (string)($message['scope'] ?? 'channel');
    if ($scope === 'dm') {
        $fromUid = chat_sanitize_uid((string)($message['from_uid'] ?? ''));
        $toUid = chat_sanitize_uid((string)($message['dm_to_uid'] ?? ''));
        if ($fromUid === '' || $toUid === '') {
            return null;
        }

        return chat_pinned_message_for_context($fromUid, 'dm', 'main', $toUid);
    }

    $viewerUid = chat_sanitize_uid((string)($message['from_uid'] ?? ''));
    if ($viewerUid === '') {
        return null;
    }

    return chat_pinned_message_for_context(
        $viewerUid,
        'channel',
        chat_valid_channel((string)($message['channel'] ?? 'main')),
        null
    );
}

function chat_store_messages(array $messages): void
{
    chat_messages_write_all($messages);
}

function chat_load_messages(): array
{
    return chat_messages_read_all();
}

function chat_request_context(): array
{
    $channel = chat_valid_channel((string)($_REQUEST['channel'] ?? 'main'));

    return [
        'scope' => 'channel',
        'channel' => $channel,
        'dm_uid' => '',
    ];
}

function chat_request_seen_at(): int
{
    return max(0, (int)($_REQUEST['seen_at'] ?? 0));
}

function chat_request_should_touch_presence(bool $default = true): bool
{
    if (!array_key_exists('touch_presence', $_REQUEST)) {
        return $default;
    }

    return chat_request_bool_value((string)$_REQUEST['touch_presence']);
}

function chat_me_payload(array $profile, string $uid, string $theme): array
{
    return chat_profile_payload($profile) + [
        'theme' => $theme,
        'role' => chat_user_role($uid),
        'is_owner' => chat_is_owner_uid($uid),
        'is_admin' => chat_is_admin_uid($uid),
        'can_send_new_messages' => chat_user_can_send_new_messages($uid),
        'staff_only_new_messages' => chat_staff_only_new_messages_enabled(),
    ];
}

function chat_touch_request_presence(array $profile, string $scope, string $channel, string $dmUid): void
{
    chat_presence_touch_stored(
        (string)($profile['uid'] ?? ''),
        (string)($profile['display_name'] ?? ''),
        (string)($profile['avatar_seed'] ?? ''),
        (string)($profile['avatar_url'] ?? ''),
        false,
        'channel',
        $channel,
        ''
    );
}

function chat_message_apply_authoritative_profile(array $message, array $profiles): array
{
    $fromUid = chat_sanitize_uid((string)($message['from_uid'] ?? ''));
    if ($fromUid === '') {
        return $message;
    }

    $rolePayload = chat_role_payload_for_uid($fromUid);
    $message['from_role'] = (string)($rolePayload['role'] ?? 'user');
    $message['from_is_owner'] = !empty($rolePayload['is_owner']);
    $message['from_is_admin'] = !empty($rolePayload['is_admin']);

    $profile = $profiles[$fromUid] ?? null;
    if (!is_array($profile)) {
        return $message;
    }

    $profilePayload = chat_profile_payload($profile);
    $message['from_name'] = (string)($profilePayload['display_name'] ?? (string)($message['from_name'] ?? ''));
    $message['from_avatar_seed'] = (string)($profilePayload['avatar_seed'] ?? (string)($message['from_avatar_seed'] ?? ''));
    $message['from_avatar_url'] = (string)($profilePayload['avatar_url'] ?? (string)($message['from_avatar_url'] ?? ''));
    $message['avatar_updated_at'] = max(0, (int)($profilePayload['avatar_updated_at'] ?? 0));

    return $message;
}

function chat_messages_apply_authoritative_profiles(array $messages): array
{
    $profiles = chat_profiles_read_all();
    if (!is_array($messages) || $messages === [] || !is_array($profiles) || $profiles === []) {
        return array_values(is_array($messages) ? $messages : []);
    }

    $result = [];
    foreach ($messages as $message) {
        if (!is_array($message)) {
            continue;
        }
        $result[] = chat_message_apply_authoritative_profile($message, $profiles);
    }

    return $result;
}

function chat_stream_event_apply_authoritative_profiles(?array $event): ?array
{
    if (!is_array($event)) {
        return null;
    }

    $kind = (string)($event['kind'] ?? '');
    if ($kind !== 'message_upsert') {
        return $event;
    }

    if (!is_array($event['message'] ?? null)) {
        return $event;
    }

    $profiles = chat_profiles_read_all();
    if (!is_array($profiles) || $profiles === []) {
        return $event;
    }

    $event['message'] = chat_message_apply_authoritative_profile($event['message'], $profiles);
    return $event;
}

function chat_conversation_payload(array $profile, string $uid, string $theme, string $scope, string $channel, string $dmUid): array
{
    $streamState = chat_stream_read_state();
    $safeChannel = chat_valid_channel($channel);
    $messages = chat_messages_apply_authoritative_profiles(
        chat_messages_list_visible($uid, 'channel', $safeChannel, null)
    );

    return [
        'me' => chat_me_payload($profile, $uid, $theme),
        'active_scope' => 'channel',
        'active_channel' => $safeChannel,
        'messages' => array_values(is_array($messages) ? $messages : []),
        'typing_users' => chat_collect_typing_users_fast($uid, 'channel', $safeChannel, null),
        'pinned_message' => chat_pinned_payload_for_requested_context($uid, 'channel', $safeChannel, ''),
        'live' => chat_live_payload_for_uid($uid),
        'message_revision' => (int)($streamState['message_revision'] ?? 0),
        'typing_revision' => (int)($streamState['typing_revision'] ?? 0),
        'online_count' => chat_presence_count_active(),
        'server_time' => chat_now(),
    ];
}

function chat_sidebar_payload(array $profile, string $uid, string $theme, array $seenMap, bool $includeUsers = true): array
{
    $users = null;
    if ($includeUsers) {
        $profiles = chat_profiles_read_all();
        $includeShadowbanState = chat_is_admin_uid($uid);
        $shadowbans = $includeShadowbanState ? chat_shadowbans_read_all() : [];
        $users = chat_collect_users([], [], $profiles, $uid, $shadowbans, $includeShadowbanState);
    }

    return [
        'me' => chat_me_payload($profile, $uid, $theme),
        'users' => $users,
        'unread_hints' => chat_compute_unread_hints_fast($uid, []),
        'live' => chat_live_payload_for_uid($uid),
        'online_count' => chat_presence_count_active(),
        'server_time' => chat_now(),
    ];
}

function chat_sse_flush(): void
{
    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
    }

    @flush();
}

function chat_sse_send(string $event, array $payload): void
{
    $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        $encoded = '{}';
    }

    echo "event: {$event}\n";
    echo "data: {$encoded}\n\n";
    chat_sse_flush();
}

function chat_handle_stream_request(): never
{
    $identity = chat_request_identity();
    $uid = (string)$identity['uid'];
    $displayName = (string)$identity['display_name'];

    $channel = chat_valid_channel((string)($_GET['channel'] ?? 'main'));
    $scope = 'channel';
    $dmUid = '';

    $profile = chat_profile_ensure_stored($uid, $displayName);
    chat_presence_touch_stored(
        $uid,
        (string)$profile['display_name'],
        (string)$profile['avatar_seed'],
        (string)($profile['avatar_url'] ?? ''),
        false,
        'channel',
        $channel,
        ''
    );

    http_response_code(200);
    header('Content-Type: text/event-stream; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('X-Accel-Buffering: no');
    header('Connection: keep-alive');

    @ini_set('zlib.output_compression', '0');
    @ini_set('output_buffering', 'off');
    @set_time_limit(0);
    ignore_user_abort(true);

    echo "retry: 1800\n\n";
    chat_sse_flush();

    $startedAt = microtime(true);
    $lastHeartbeatAt = chat_now();
    $lastState = chat_stream_read_state();

    chat_sse_send('ping', [
        'at' => $lastHeartbeatAt,
        'scope' => 'channel',
        'channel' => $channel,
    ]);

    while (!connection_aborted() && (microtime(true) - $startedAt) < CHAT_SSE_MAX_SECONDS) {
        usleep(600000);

        $state = chat_stream_read_state();

        if ((int)($state['message_revision'] ?? 0) !== (int)($lastState['message_revision'] ?? 0)) {
            $event = is_array($state['message_event'] ?? null)
                ? (chat_stream_event_apply_authoritative_profiles($state['message_event']) ?? [])
                : [];
            chat_sse_send('invalidate', $event + ['at' => chat_now()]);
        }

        if ((int)($state['typing_revision'] ?? 0) !== (int)($lastState['typing_revision'] ?? 0)) {
            $event = is_array($state['typing_event'] ?? null) ? $state['typing_event'] : [];
            chat_sse_send('typing', $event + ['at' => chat_now()]);
        }

        $lastState = $state;

        $now = chat_now();
        if (($now - $lastHeartbeatAt) >= CHAT_SSE_HEARTBEAT_SECONDS) {
            $lastHeartbeatAt = $now;
            chat_sse_send('ping', ['at' => $now]);
        }
    }

    exit;
}

function chat_find_message_index(array $messages, string $messageId): int
{
    foreach ($messages as $index => $message) {
        if ((string)($message['id'] ?? '') === $messageId) {
            return $index;
        }
    }

    return -1;
}

function chat_store_avatar_file(array $file): array
{
    $errorCode = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($errorCode !== UPLOAD_ERR_OK) {
        chat_error('Avatar upload failed with code ' . $errorCode . '.', 422);
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        chat_error('Invalid avatar upload source.', 422);
    }

    $size = (int)($file['size'] ?? 0);
    if ($size <= 0) {
        chat_error('Empty avatar is not allowed.', 422);
    }
    if ($size > CHAT_MAX_PROFILE_AVATAR_BYTES) {
        chat_error('Avatar is too large. Max 6MB.', 422);
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? (string)finfo_file($finfo, $tmpName) : 'application/octet-stream';
    if ($finfo) {
        finfo_close($finfo);
    }

    if (!str_starts_with($mime, 'image/')) {
        chat_error('Avatar must be an image.', 422);
    }
    if ($mime === 'image/svg+xml') {
        chat_error('SVG avatar is not allowed.', 422);
    }

    $originalName = trim((string)($file['name'] ?? 'avatar'));
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($ext === '') {
        $ext = 'jpg';
    }

    $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];
    if (!in_array($ext, $allowedExt, true)) {
        $ext = 'jpg';
    }

    $subFolder = 'avatars/' . date('Y-m');
    $uploadDir = CHAT_UPLOAD_DIR . '/' . $subFolder;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $storedBase = 'av_' . date('Ymd_His') . '_' . substr(chat_make_id(), 0, 10);
    $optimized = chat_try_optimize_avatar_upload($tmpName, $mime, $uploadDir, $storedBase);
    if (is_array($optimized)) {
        return [
            'url' => 'uploads/' . $subFolder . '/' . (string)$optimized['stored_name'],
            'mime' => (string)$optimized['mime'],
            'size' => (int)$optimized['size'],
        ];
    }

    $storedName = $storedBase . '.' . $ext;
    $destination = $uploadDir . '/' . $storedName;
    if (!move_uploaded_file($tmpName, $destination)) {
        chat_error('Unable to save avatar.', 500);
    }

    return [
        'url' => 'uploads/' . $subFolder . '/' . $storedName,
        'mime' => $mime,
        'size' => $size,
    ];
}

function chat_avatar_source_image_from_file(string $tmpName, string $mime)
{
    if (!function_exists('getimagesize')) {
        return null;
    }

    return match ($mime) {
        'image/jpeg', 'image/jpg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($tmpName) : null,
        'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($tmpName) : null,
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmpName) : null,
        default => null,
    };
}

function chat_try_optimize_avatar_upload(string $tmpName, string $mime, string $uploadDir, string $storedBase): ?array
{
    $source = chat_avatar_source_image_from_file($tmpName, $mime);
    if (!$source) {
        return null;
    }

    $width = (int)imagesx($source);
    $height = (int)imagesy($source);
    if ($width <= 0 || $height <= 0) {
        imagedestroy($source);
        return null;
    }

    $maxSide = 512;
    $scale = min(1, $maxSide / max($width, $height));
    $targetWidth = max(1, (int)round($width * $scale));
    $targetHeight = max(1, (int)round($height * $scale));

    $target = imagecreatetruecolor($targetWidth, $targetHeight);
    if (!$target) {
        imagedestroy($source);
        return null;
    }

    imagealphablending($target, false);
    imagesavealpha($target, true);
    $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
    imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);

    imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

    $destination = '';
    $storedName = '';
    $storedMime = $mime;

    if (function_exists('imagewebp')) {
        $storedName = $storedBase . '.webp';
        $destination = $uploadDir . '/' . $storedName;
        if (!@imagewebp($target, $destination, 78)) {
            $destination = '';
            $storedName = '';
        } else {
            $storedMime = 'image/webp';
        }
    }

    if ($destination === '' && ($mime === 'image/png') && function_exists('imagepng')) {
        $storedName = $storedBase . '.png';
        $destination = $uploadDir . '/' . $storedName;
        if (!@imagepng($target, $destination, 6)) {
            $destination = '';
            $storedName = '';
        } else {
            $storedMime = 'image/png';
        }
    }

    if ($destination === '' && function_exists('imagejpeg')) {
        $storedName = $storedBase . '.jpg';
        $destination = $uploadDir . '/' . $storedName;
        if (!@imagejpeg($target, $destination, 82)) {
            $destination = '';
            $storedName = '';
        } else {
            $storedMime = 'image/jpeg';
        }
    }

    imagedestroy($target);
    imagedestroy($source);

    if ($destination === '' || !is_file($destination)) {
        return null;
    }

    return [
        'stored_name' => $storedName,
        'mime' => $storedMime,
        'size' => max(0, (int)@filesize($destination)),
    ];
}

function chat_sanitize_avatar_seed_input(string $seed): string
{
    $clean = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($seed)) ?? '';
    return substr($clean, 0, 32);
}

function chat_build_local_only_message(
    array $profile,
    string $scope,
    string $channel,
    string $uid,
    string $text,
    string $replyTo = '',
    string $toUid = '',
    string $type = 'text',
    array $file = []
): array {
    // Shadowbanned messages are returned only to the sender and never stored/broadcast.
    $message = [
        'id' => chat_make_id(),
        'time' => chat_now(),
        'scope' => $scope === 'dm' ? 'dm' : 'channel',
        'from_uid' => $uid,
        'from_name' => (string)($profile['display_name'] ?? chat_default_name_from_uid($uid)),
        'from_avatar_seed' => (string)($profile['avatar_seed'] ?? chat_avatar_seed_from_uid($uid)),
        'from_avatar_url' => (string)($profile['avatar_url'] ?? chat_default_avatar_url($uid)),
        'type' => $type,
        'text' => $text,
        'reactions' => [],
        'local_only' => true,
    ];

    if ($scope === 'dm') {
        $message['dm_to_uid'] = $toUid;
    } else {
        $message['channel'] = $channel;
    }

    if ($replyTo !== '') {
        $message['reply_to'] = $replyTo;
    }

    if ($file !== []) {
        $message['file'] = $file;
    }

    return $message;
}

function chat_media_upload_rules(): array
{
    static $rules = null;
    if ($rules !== null) {
        return $rules;
    }

    $rules = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'gif' => ['image/gif'],
        'avif' => ['image/avif'],

        'mp4' => ['video/mp4'],
        'webm' => ['video/webm', 'audio/webm'],
        'mov' => ['video/quicktime'],
        'm4v' => ['video/x-m4v', 'video/mp4'],
        'ogv' => ['video/ogg'],

        'mp3' => ['audio/mpeg', 'audio/mp3', 'audio/x-mpeg'],
        'wav' => ['audio/wav', 'audio/wave', 'audio/x-wav', 'audio/vnd.wave'],
        'flac' => ['audio/flac', 'audio/x-flac'],
        'm4a' => ['audio/mp4', 'audio/m4a', 'audio/x-m4a'],
        'aac' => ['audio/aac'],
        'ogg' => ['audio/ogg', 'application/ogg'],
        'oga' => ['audio/ogg', 'application/ogg'],
        'opus' => ['audio/opus', 'audio/ogg', 'application/ogg'],
        'weba' => ['audio/webm'],
    ];

    return $rules;
}

function chat_validate_media_upload(string $tmpName, string $originalName): array
{
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        chat_error('Invalid upload source.', 422);
    }

    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $rules = chat_media_upload_rules();
    if ($ext === '' || !isset($rules[$ext])) {
        chat_error('Only media files are allowed (image/video/audio).', 422);
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? strtolower((string)finfo_file($finfo, $tmpName)) : 'application/octet-stream';
    if ($finfo) {
        finfo_close($finfo);
    }

    if (!in_array($mime, $rules[$ext], true)) {
        chat_error('File format validation failed. Upload a valid media file.', 422);
    }

    $kind = '';
    if (str_starts_with($mime, 'image/')) {
        $kind = 'image';
    } elseif (str_starts_with($mime, 'video/')) {
        $kind = 'video';
    } elseif (str_starts_with($mime, 'audio/')) {
        $kind = 'audio';
    }

    if ($kind === '') {
        chat_error('Only media files are allowed (image/video/audio).', 422);
    }

    $baseName = pathinfo($originalName, PATHINFO_FILENAME);
    $baseName = preg_replace('/[^\p{L}\p{N}._\- ]/u', '_', (string)$baseName) ?? 'media';
    $baseName = trim($baseName) !== '' ? trim($baseName) : 'media';
    $baseName = mb_substr($baseName, 0, 36);

    return [
        'kind' => $kind,
        'mime' => $mime,
        'ext' => $ext,
        'base_name' => $baseName,
        'safe_name' => $baseName . '.' . $ext,
    ];
}

try {
    $identity = chat_request_identity();
    $uid = $identity['uid'];
    $displayName = $identity['display_name'];
    $theme = $identity['theme'];

    $action = $requestedAction;

    if ($action === 'profile') {
        $requestedAvatarSeed = chat_sanitize_avatar_seed_input((string)($_POST['avatar_seed'] ?? ''));
        $avatarUrl = '';

        if (!empty($_FILES['avatar']) && is_array($_FILES['avatar'])) {
            $errorCode = (int)($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($errorCode !== UPLOAD_ERR_NO_FILE) {
                if ($errorCode !== UPLOAD_ERR_OK) {
                    chat_error('Avatar upload failed.', 422);
                }

                $stored = chat_store_avatar_file($_FILES['avatar']);
                $avatarUrl = (string)($stored['url'] ?? '');
            }
        }

        $result = chat_db_transaction(function () use ($uid, $displayName, $requestedAvatarSeed, $avatarUrl): array {
            $profile = chat_profile_ensure_stored($uid, $displayName);

            $changes = [];
            if ($displayName !== '') {
                $changes['display_name'] = $displayName;
            }
            if ($requestedAvatarSeed !== '' && $requestedAvatarSeed !== (string)($profile['avatar_seed'] ?? '')) {
                $changes['avatar_seed'] = $requestedAvatarSeed;
                $changes['avatar_url'] = chat_default_avatar_url($uid, $requestedAvatarSeed);
            }
            if ($avatarUrl !== '') {
                $changes['avatar_url'] = $avatarUrl;
            }

            if ($changes !== []) {
                $profile = chat_profile_update_stored($uid, $changes);
            }

            chat_presence_touch_stored(
                $uid,
                (string)$profile['display_name'],
                (string)$profile['avatar_seed'],
                (string)($profile['avatar_url'] ?? ''),
                false,
                'channel',
                'main',
                ''
            );

            return $profile;
        });

        $profilePayload = chat_profile_payload($result);
        chat_stream_bump('profile_update', 'channel', 'main', [], [
            'target_uid' => $uid,
            'profile' => $profilePayload,
        ]);

        chat_response([
            'ok' => true,
            'me' => $profilePayload + ['theme' => $theme],
        ]);
    }

    if ($action === 'profile_avatar') {
        if (empty($_FILES['avatar']) || !is_array($_FILES['avatar'])) {
            chat_error('No avatar uploaded.', 422);
        }

        $stored = chat_store_avatar_file($_FILES['avatar']);
        $avatarUrl = (string)$stored['url'];

        $result = chat_db_transaction(function () use ($uid, $displayName, $avatarUrl): array {
            $profile = chat_profile_ensure_stored($uid, $displayName);
            $profile = chat_profile_update_stored($uid, [
                'avatar_url' => $avatarUrl,
            ]);

            chat_presence_touch_stored(
                $uid,
                (string)$profile['display_name'],
                (string)$profile['avatar_seed'],
                (string)$profile['avatar_url'],
                false,
                'channel',
                'main',
                ''
            );

            return $profile;
        });

        $profilePayload = chat_profile_payload($result);
        chat_stream_bump('profile_update', 'channel', 'main', [], [
            'target_uid' => $uid,
            'profile' => $profilePayload,
        ]);

        chat_response([
            'ok' => true,
            'me' => $profilePayload + ['theme' => $theme],
        ]);
    }

    if ($action === 'typing') {
        $channel = chat_valid_channel((string)($_POST['channel'] ?? 'main'));

        chat_db_transaction(function () use ($uid, $displayName, $channel): void {
            $profile = chat_profile_ensure_stored($uid, $displayName);
            chat_presence_touch_stored(
                $uid,
                (string)$profile['display_name'],
                (string)$profile['avatar_seed'],
                (string)($profile['avatar_url'] ?? ''),
                true,
                'channel',
                $channel,
                ''
            );
            chat_stream_bump('typing', 'channel', $channel, []);
        });

        chat_response([
            'ok' => true,
            'typing_until' => chat_now() + CHAT_TYPING_TTL,
        ]);
    }

    if ($action === 'send') {
        chat_require_new_message_permission($uid);

        $text = chat_sanitize_text((string)($_POST['text'] ?? ''));
        if ($text === '') {
            chat_error('Message text is required.', 422);
        }

        $channel = chat_valid_channel((string)($_POST['channel'] ?? 'main'));
        $replyTo = trim((string)($_POST['reply_to'] ?? ''));

        $message = chat_db_transaction(function () use ($uid, $displayName, $channel, $text, $replyTo): array {
            $profile = chat_profile_ensure_stored($uid, $displayName);
            $presenceEntry = chat_presence_touch_stored(
                $uid,
                (string)$profile['display_name'],
                (string)$profile['avatar_seed'],
                (string)($profile['avatar_url'] ?? ''),
                false,
                'channel',
                $channel,
                ''
            );

            $now = chat_now();
            $cooldownRemaining = chat_message_cooldown_remaining([$uid => $presenceEntry], $uid, $now);
            if ($cooldownRemaining > 0) {
                throw new RuntimeException('Slow down. Wait ' . $cooldownRemaining . ' second(s) before sending again.', 429);
            }

            if (chat_shadowban_is_active($uid)) {
                return chat_build_local_only_message(
                    $profile,
                    'channel',
                    $channel,
                    $uid,
                    $text,
                    $replyTo,
                    ''
                );
            }

            $item = [
                'id' => chat_make_id(),
                'time' => $now,
                'scope' => 'channel',
                'from_uid' => $uid,
                'from_name' => (string)$profile['display_name'],
                'from_avatar_seed' => (string)$profile['avatar_seed'],
                'from_avatar_url' => (string)($profile['avatar_url'] ?? ''),
                'type' => 'text',
                'text' => $text,
                'reactions' => [],
                'channel' => $channel,
            ];

            if ($replyTo !== '') {
                $item['reply_to'] = $replyTo;
            }

            chat_messages_append($item);
            chat_presence_mark_message_sent_stored($uid, $now);
            chat_stream_bump('message_upsert', 'channel', $channel, [], [
                'message' => $item,
            ]);

            return $item;
        });

        chat_response([
            'ok' => true,
            'message' => $message,
        ]);
    }

    if ($action === 'upload') {
        chat_require_new_message_permission($uid);

        if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
            chat_error('No file uploaded.', 422);
        }

        $file = $_FILES['file'];
        $errorCode = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode !== UPLOAD_ERR_OK) {
            chat_error('Upload failed with code ' . $errorCode . '.', 422);
        }

        $tmpName = (string)($file['tmp_name'] ?? '');
        $size = (int)($file['size'] ?? 0);
        if ($size <= 0) {
            chat_error('Empty file is not allowed.', 422);
        }
        if ($size > CHAT_MAX_UPLOAD_BYTES) {
            chat_error('File is too large. Max 12MB.', 422);
        }

        $channel = chat_valid_channel((string)($_POST['channel'] ?? 'main'));

        $caption = chat_sanitize_text((string)($_POST['text'] ?? ''));
        $replyTo = trim((string)($_POST['reply_to'] ?? ''));

        $originalName = trim((string)($file['name'] ?? 'file'));
        $validatedUpload = chat_validate_media_upload($tmpName, $originalName);
        $safeOriginal = (string)$validatedUpload['safe_name'];
        $mime = (string)$validatedUpload['mime'];
        $ext = (string)$validatedUpload['ext'];
        $baseName = (string)$validatedUpload['base_name'];
        $kind = (string)$validatedUpload['kind'];

        $type = $kind === 'image' ? 'image' : ($kind === 'video' ? 'video' : 'voice');
        $message = chat_db_transaction(function () use ($uid, $displayName, $channel, $caption, $replyTo, $safeOriginal, $size, $mime, $tmpName, $baseName, $ext, $type): array {
            $profile = chat_profile_ensure_stored($uid, $displayName);
            $presenceEntry = chat_presence_touch_stored(
                $uid,
                (string)$profile['display_name'],
                (string)$profile['avatar_seed'],
                (string)($profile['avatar_url'] ?? ''),
                false,
                'channel',
                $channel,
                ''
            );

            $now = chat_now();
            $cooldownRemaining = chat_message_cooldown_remaining([$uid => $presenceEntry], $uid, $now);
            if ($cooldownRemaining > 0) {
                throw new RuntimeException('Slow down. Wait ' . $cooldownRemaining . ' second(s) before sending again.', 429);
            }

            if (chat_shadowban_is_active($uid)) {
                return chat_build_local_only_message(
                    $profile,
                    'channel',
                    $channel,
                    $uid,
                    $caption,
                    $replyTo,
                    '',
                    $type,
                    [
                        'url' => '',
                        'name' => $safeOriginal,
                        'size' => $size,
                        'mime' => $mime,
                    ]
                );
            }

            $subFolder = date('Y-m');
            $uploadDir = CHAT_UPLOAD_DIR . '/' . $subFolder;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $storedName = date('Ymd_His') . '_' . substr(chat_make_id(), 0, 8) . '_' . $baseName . '.' . $ext;
            $destination = $uploadDir . '/' . $storedName;
            if (!move_uploaded_file($tmpName, $destination)) {
                throw new RuntimeException('Unable to save uploaded file.', 500);
            }

            $item = [
                'id' => chat_make_id(),
                'time' => $now,
                'scope' => 'channel',
                'from_uid' => $uid,
                'from_name' => (string)$profile['display_name'],
                'from_avatar_seed' => (string)$profile['avatar_seed'],
                'from_avatar_url' => (string)($profile['avatar_url'] ?? ''),
                'type' => $type,
                'text' => $caption,
                'reactions' => [],
                'channel' => $channel,
                'file' => [
                    'url' => 'uploads/' . $subFolder . '/' . $storedName,
                    'name' => $safeOriginal,
                    'size' => $size,
                    'mime' => $mime,
                ],
            ];

            if ($replyTo !== '') {
                $item['reply_to'] = $replyTo;
            }

            chat_messages_append($item);
            chat_presence_mark_message_sent_stored($uid, $now);
            chat_stream_bump('message_upsert', 'channel', $channel, [], [
                'message' => $item,
            ]);

            return $item;
        });

        chat_response([
            'ok' => true,
            'message' => $message,
        ]);
    }

    if ($action === 'react') {
        $messageId = trim((string)($_POST['message_id'] ?? ''));
        $emoji = chat_sanitize_emoji((string)($_POST['emoji'] ?? ''));

        if ($messageId === '' || $emoji === '') {
            chat_error('Message and emoji are required.', 422);
        }

        $result = chat_db_transaction(function () use ($uid, $displayName, $messageId, $emoji): array {
            $profile = chat_profile_ensure_stored($uid, $displayName);
            chat_presence_touch_stored(
                $uid,
                (string)$profile['display_name'],
                (string)$profile['avatar_seed'],
                (string)($profile['avatar_url'] ?? ''),
                false,
                'channel',
                'main',
                ''
            );

            $message = chat_message_find($messageId);
            if (!is_array($message)) {
                throw new RuntimeException('Message not found.', 404);
            }

            if (!chat_user_can_view_message($message, $uid)) {
                throw new RuntimeException('Access denied.', 403);
            }

            chat_toggle_reaction($message, $uid, $emoji);
            chat_messages_upsert($message);

            $msgScope = (string)($message['scope'] ?? 'channel');
            $msgChannel = (string)($message['channel'] ?? 'main');
            $dmUsers = $msgScope === 'dm'
                ? [(string)($message['from_uid'] ?? ''), (string)($message['dm_to_uid'] ?? '')]
                : [];
            chat_stream_bump('message_upsert', $msgScope, $msgChannel, $dmUsers, [
                'message' => $message,
            ]);

            return [
                'message_id' => $messageId,
                'reactions' => $message['reactions'] ?? [],
            ];
        });

        chat_response([
            'ok' => true,
            'result' => $result,
        ]);
    }

    if ($action === 'edit') {
        $messageId = trim((string)($_POST['message_id'] ?? ''));
        $text = chat_sanitize_text((string)($_POST['text'] ?? ''));

        if ($messageId === '' || $text === '') {
            chat_error('Message and text are required.', 422);
        }

        $updated = chat_db_transaction(function () use ($uid, $displayName, $messageId, $text): array {
            $profile = chat_profile_ensure_stored($uid, $displayName);
            chat_presence_touch_stored(
                $uid,
                (string)$profile['display_name'],
                (string)$profile['avatar_seed'],
                (string)($profile['avatar_url'] ?? ''),
                false,
                'channel',
                'main',
                ''
            );

            $message = chat_message_find($messageId);
            if (!is_array($message)) {
                throw new RuntimeException('Message not found.', 404);
            }

            if ((string)($message['from_uid'] ?? '') !== $uid) {
                throw new RuntimeException('You can edit only your own messages.', 403);
            }

            if (!empty($message['deleted_at'])) {
                throw new RuntimeException('Deleted messages can not be edited.', 422);
            }

            $message['from_name'] = (string)$profile['display_name'];
            $message['from_avatar_seed'] = (string)$profile['avatar_seed'];
            $message['from_avatar_url'] = (string)($profile['avatar_url'] ?? '');
            $message['text'] = $text;
            $message['edited_at'] = chat_now();

            chat_messages_upsert($message);
            $msgScope = (string)($message['scope'] ?? 'channel');
            $msgChannel = (string)($message['channel'] ?? 'main');
            $dmUsers = $msgScope === 'dm'
                ? [(string)($message['from_uid'] ?? ''), (string)($message['dm_to_uid'] ?? '')]
                : [];
            chat_stream_bump('message_upsert', $msgScope, $msgChannel, $dmUsers, [
                'message' => $message,
            ]);

            return $message;
        });

        chat_response([
            'ok' => true,
            'message' => $updated,
        ]);
    }

    if ($action === 'delete') {
        $messageId = trim((string)($_POST['message_id'] ?? ''));
        if ($messageId === '') {
            chat_error('Message id is required.', 422);
        }

        $requesterIsAdmin = chat_is_admin_uid($uid);

        $result = chat_db_transaction(function () use ($uid, $displayName, $messageId, $requesterIsAdmin): array {
            $profile = chat_profile_ensure_stored($uid, $displayName);
            chat_presence_touch_stored(
                $uid,
                (string)$profile['display_name'],
                (string)$profile['avatar_seed'],
                (string)($profile['avatar_url'] ?? ''),
                false,
                'channel',
                'main',
                ''
            );

            $message = chat_message_find($messageId);
            if (!is_array($message)) {
                throw new RuntimeException('Message not found.', 404);
            }

            $ownerUid = (string)($message['from_uid'] ?? '');
            if (!$requesterIsAdmin && $ownerUid !== $uid) {
                throw new RuntimeException('You can delete only your own messages.', 403);
            }

            $msgScope = (string)($message['scope'] ?? 'channel');
            $msgChannel = (string)($message['channel'] ?? 'main');
            $dmUsers = $msgScope === 'dm'
                ? [(string)($message['from_uid'] ?? ''), (string)($message['dm_to_uid'] ?? '')]
                : [];

            if ($requesterIsAdmin) {
                chat_message_delete_hard($messageId);
                $pinnedPayload = chat_pinned_payload_for_message_context($message);
                chat_stream_bump('message_delete', $msgScope, $msgChannel, $dmUsers, [
                    'message_id' => $messageId,
                    'hard_deleted' => true,
                    'pinned_message' => $pinnedPayload,
                ]);

                return [
                    'hard_deleted' => true,
                    'message_id' => $messageId,
                    'pinned_message' => $pinnedPayload,
                ];
            }

            $message['deleted_at'] = chat_now();
            $message['from_name'] = (string)$profile['display_name'];
            $message['from_avatar_seed'] = (string)$profile['avatar_seed'];
            $message['from_avatar_url'] = (string)($profile['avatar_url'] ?? '');
            $message['text'] = '';
            $message['reactions'] = [];
            unset($message['file']);

            chat_messages_upsert($message);
            chat_pins_clear_by_message_id($messageId);
            chat_stream_bump('message_upsert', $msgScope, $msgChannel, $dmUsers, [
                'message' => $message,
                'pinned_message' => chat_pinned_payload_for_message_context($message),
            ]);

            return [
                'hard_deleted' => false,
                'message' => $message,
                'pinned_message' => chat_pinned_payload_for_message_context($message),
            ];
        });

        chat_response(['ok' => true] + $result);
    }

    if ($action === 'delete_user_messages') {
        if (!chat_is_admin_uid($uid)) {
            chat_error('Admin access required.', 403);
        }

        $targetUid = chat_sanitize_uid((string)($_POST['target_uid'] ?? ''));
        if ($targetUid === '') {
            chat_error('Target user id is required.', 422);
        }
        if ($targetUid === $uid) {
            chat_error('You can not bulk-delete your own messages with this action.', 422);
        }

        $result = chat_db_transaction(function () use ($uid, $displayName, $targetUid): array {
            $profile = chat_profile_ensure_stored($uid, $displayName);
            chat_presence_touch_stored(
                $uid,
                (string)$profile['display_name'],
                (string)$profile['avatar_seed'],
                (string)($profile['avatar_url'] ?? ''),
                false,
                'channel',
                'main',
                ''
            );

            $deletedCount = chat_messages_delete_by_user_hard($targetUid);
            if ($deletedCount > 0) {
                chat_stream_bump('bulk_delete_user', 'channel', 'main', [], [
                    'target_uid' => $targetUid,
                    'deleted_count' => $deletedCount,
                    'pinned_message' => chat_pinned_payload_for_requested_context($uid, 'channel', 'main'),
                ]);
            }

            return [
                'target_uid' => $targetUid,
                'deleted_count' => $deletedCount,
                'pinned_message' => chat_pinned_payload_for_requested_context($uid, 'channel', 'main'),
            ];
        });

        chat_response(['ok' => true] + $result);
    }

    if ($action === 'pin') {
        if (!chat_is_owner_uid($uid)) {
            chat_error('Owner access required.', 403);
        }

        $mode = (string)($_POST['mode'] ?? 'set');
        $mode = $mode === 'clear' ? 'clear' : 'set';
        $messageId = trim((string)($_POST['message_id'] ?? ''));
        $scope = 'channel';
        $channel = chat_valid_channel((string)($_POST['channel'] ?? 'main'));
        $toUid = '';

        $result = chat_db_transaction(function () use ($uid, $displayName, $mode, $messageId, $scope, $channel, $toUid): array {
            $profile = chat_profile_ensure_stored($uid, $displayName);
            chat_presence_touch_stored(
                $uid,
                (string)$profile['display_name'],
                (string)$profile['avatar_seed'],
                (string)($profile['avatar_url'] ?? ''),
                false,
                $scope,
                $channel,
                $toUid
            );

            if ($mode === 'set') {
                if ($messageId === '') {
                    throw new RuntimeException('Message id is required.', 422);
                }

                $message = chat_message_find($messageId);
                if (!is_array($message)) {
                    throw new RuntimeException('Message not found.', 404);
                }
                if (!chat_user_can_view_message($message, $uid)) {
                    throw new RuntimeException('Access denied.', 403);
                }
                if ((string)($message['scope'] ?? 'channel') !== 'channel') {
                    throw new RuntimeException('Only channel messages can be pinned.', 422);
                }
                if (!empty($message['deleted_at'])) {
                    throw new RuntimeException('Deleted messages can not be pinned.', 422);
                }

                $entry = chat_pin_entry_from_message($message, $uid);
                if (!is_array($entry)) {
                    throw new RuntimeException('Unable to pin this message.', 422);
                }

                chat_pin_upsert($entry);

                $msgScope = (string)($message['scope'] ?? 'channel');
                $msgChannel = (string)($message['channel'] ?? 'main');
                $payload = chat_pin_payload_from_entry($entry, $message);

                chat_stream_bump('pin_update', $msgScope, $msgChannel, [], [
                    'pinned_message' => $payload,
                ]);

                return [
                    'pinned_message' => $payload,
                ];
            }

            $dmUsers = [];
            if ($messageId !== '') {
                $message = chat_message_find($messageId);
                if (is_array($message)) {
                    $entry = chat_pin_entry_from_message($message, $uid);
                    if (is_array($entry)) {
                        $scope = (string)($entry['scope'] ?? $scope);
                        $channel = (string)($entry['channel'] ?? $channel);
                        chat_pin_clear_by_conversation_key((string)($entry['conversation_key'] ?? ''));
                    }
                } else {
                    chat_pin_clear_for_context($scope, $channel, $uid, $toUid);
                }
            } else {
                chat_pin_clear_for_context($scope, $channel, $uid, $toUid);
            }

            $payload = chat_pinned_payload_for_requested_context($uid, $scope, $channel, $toUid);
            chat_stream_bump('pin_update', $scope, $channel, $dmUsers, [
                'pinned_message' => $payload,
            ]);

            return [
                'pinned_message' => $payload,
            ];
        });

        chat_response(['ok' => true] + $result);
    }

    if ($action === 'shadowban') {
        if (!chat_is_admin_uid($uid)) {
            chat_error('Admin access required.', 403);
        }

        $targetUid = chat_sanitize_uid((string)($_POST['target_uid'] ?? ''));
        if ($targetUid === '') {
            chat_error('Target user id is required.', 422);
        }
        if ($targetUid === $uid) {
            chat_error('You can not shadowban yourself.', 422);
        }
        if (chat_is_admin_uid($targetUid)) {
            chat_error('Owners and admins can not be shadowbanned.', 422);
        }

        $shadowbanned = (string)($_POST['shadowbanned'] ?? '1') !== '0';
        $result = chat_db_transaction(function () use ($uid, $displayName, $targetUid, $shadowbanned): array {
            $profile = chat_profile_ensure_stored($uid, $displayName);
            chat_presence_touch_stored(
                $uid,
                (string)$profile['display_name'],
                (string)$profile['avatar_seed'],
                (string)($profile['avatar_url'] ?? ''),
                false,
                'channel',
                'main',
                ''
            );

            return chat_shadowban_set($targetUid, $shadowbanned, $uid);
        });

        chat_response([
            'ok' => true,
            'target_uid' => (string)($result['uid'] ?? $targetUid),
            'shadowbanned' => (bool)($result['shadowbanned'] ?? false),
        ]);
    }

    if ($action === 'owner_panel') {
        chat_require_owner_access($uid);

        $profile = chat_profile_ensure_stored($uid, $displayName);
        chat_touch_request_presence($profile, 'channel', 'main', '');

        chat_response([
            'ok' => true,
            'me' => chat_me_payload($profile, $uid, $theme),
            'panel' => chat_owner_panel_payload(),
        ]);
    }

    if ($action === 'owner_staff_only') {
        chat_require_owner_access($uid);

        $enabled = chat_request_bool_value((string)($_POST['enabled'] ?? '0'));
        $profile = chat_profile_ensure_stored($uid, $displayName);
        chat_touch_request_presence($profile, 'channel', 'main', '');

        $result = chat_set_staff_only_new_messages($enabled, $uid);
        chat_owner_audit_log($uid, 'owner.staff_only', [
            'enabled' => (bool)($result['enabled'] ?? false),
        ]);
        chat_stream_bump('settings_update', 'channel', 'main', [], [
            'staff_only_new_messages' => (bool)($result['enabled'] ?? false),
        ]);

        chat_response([
            'ok' => true,
            'enabled' => (bool)($result['enabled'] ?? false),
            'panel' => chat_owner_panel_payload(),
        ]);
    }

    if ($action === 'owner_live') {
        chat_require_owner_access($uid);

        $rawStreamUrl = trim((string)($_POST['stream_url'] ?? ''));
        $streamUrl = chat_sanitize_live_stream_url($rawStreamUrl);
        if ($rawStreamUrl !== '' && $streamUrl === '') {
            chat_error('لینک لایو معتبر نیست. لینک باید با http:// یا https:// شروع شود.', 422);
        }

        $rotation = chat_sanitize_live_rotation((int)($_POST['rotation'] ?? 0));
        $enabled = !chat_request_bool_value((string)($_POST['closed'] ?? '0'));

        $profile = chat_profile_ensure_stored($uid, $displayName);
        chat_touch_request_presence($profile, 'channel', 'main', '');

        $live = chat_live_update($enabled, $streamUrl, $rotation, $uid);
        chat_owner_audit_log($uid, 'owner.live_update', [
            'enabled' => (bool)($live['enabled'] ?? false),
            'rotation' => (int)($live['rotation'] ?? 0),
            'has_stream_url' => (bool)($live['has_stream_url'] ?? false),
        ]);
        chat_stream_bump('settings_update', 'channel', 'main', [], [
            'live' => chat_live_public_payload(),
        ]);

        chat_response([
            'ok' => true,
            'me' => chat_me_payload($profile, $uid, $theme),
            'live' => $live,
            'panel' => chat_owner_panel_payload(),
        ]);
    }

    if ($action === 'owner_admin') {
        chat_require_owner_access($uid);

        $targetUid = chat_sanitize_uid((string)($_POST['target_uid'] ?? ''));
        if ($targetUid === '') {
            chat_error('Target user id is required.', 422);
        }

        $mode = (string)($_POST['mode'] ?? 'add');
        $enable = $mode !== 'remove';

        $profile = chat_profile_ensure_stored($uid, $displayName);
        chat_touch_request_presence($profile, 'channel', 'main', '');

        $result = chat_admin_set($targetUid, $enable, $uid);
        chat_owner_audit_log($uid, $enable ? 'owner.admin_add' : 'owner.admin_remove', [
            'target_uid' => $targetUid,
            'source' => (string)($result['source'] ?? ''),
        ]);
        chat_stream_bump('settings_update', 'channel', 'main', [], [
            'target_uid' => $targetUid,
            'role' => (string)($result['role'] ?? 'user'),
        ]);

        chat_response([
            'ok' => true,
            'target_uid' => $targetUid,
            'enabled' => (bool)($result['enabled'] ?? false),
            'role' => (string)($result['role'] ?? 'user'),
            'panel' => chat_owner_panel_payload(),
        ]);
    }

    if ($action === 'owner_unshadowban') {
        chat_require_owner_access($uid);

        $targetUid = chat_sanitize_uid((string)($_POST['target_uid'] ?? ''));
        if ($targetUid === '') {
            chat_error('Target user id is required.', 422);
        }

        $profile = chat_profile_ensure_stored($uid, $displayName);
        chat_touch_request_presence($profile, 'channel', 'main', '');

        $result = chat_shadowban_set($targetUid, false, $uid);
        chat_owner_audit_log($uid, 'owner.unshadowban', [
            'target_uid' => $targetUid,
        ]);

        chat_response([
            'ok' => true,
            'target_uid' => (string)($result['uid'] ?? $targetUid),
            'shadowbanned' => false,
            'panel' => chat_owner_panel_payload(),
        ]);
    }

    if ($action === 'bootstrap') {
        $context = chat_request_context();
        $channel = (string)$context['channel'];
        $seenAt = chat_request_seen_at();

        $profile = chat_profile_ensure_stored($uid, $displayName);
        if (chat_request_should_touch_presence(true)) {
            chat_touch_request_presence($profile, 'channel', $channel, '');
        }
        if ($seenAt > 0) {
            chat_seen_mark_channel($uid, $channel, $seenAt);
        }

        $includeUsers = chat_request_bool_value((string)($_GET['include_users'] ?? '0'));
        $payload = chat_sidebar_payload($profile, $uid, $theme, [], $includeUsers)
            + chat_conversation_payload($profile, $uid, $theme, 'channel', $channel, '');

        chat_response(['ok' => true] + $payload);
    }

    if ($action === 'sidebar') {
        $context = chat_request_context();
        $channel = (string)$context['channel'];
        $includeUsers = chat_request_bool_value((string)($_GET['include_users'] ?? '0'));

        $profile = chat_profile_ensure_stored($uid, $displayName);
        if (chat_request_should_touch_presence(true)) {
            chat_touch_request_presence($profile, 'channel', $channel, '');
        }

        chat_response(['ok' => true] + chat_sidebar_payload($profile, $uid, $theme, [], $includeUsers));
    }

    if ($action === 'conversation') {
        $context = chat_request_context();
        $channel = (string)$context['channel'];
        $seenAt = chat_request_seen_at();

        $profile = chat_profile_ensure_stored($uid, $displayName);
        if (chat_request_should_touch_presence(true)) {
            chat_touch_request_presence($profile, 'channel', $channel, '');
        }
        if ($seenAt > 0) {
            chat_seen_mark_channel($uid, $channel, $seenAt);
        }

        chat_response(['ok' => true] + chat_conversation_payload($profile, $uid, $theme, 'channel', $channel, ''));
    }

    if ($action === 'pulse') {
        $context = chat_request_context();
        $channel = (string)$context['channel'];
        $seenAt = chat_request_seen_at();
        $requestedMessageRevision = max(0, (int)($_GET['message_revision'] ?? 0));
        $requestedTypingRevision = max(0, (int)($_GET['typing_revision'] ?? 0));
        $includeMeta = !isset($_GET['include_meta']) || chat_request_bool_value((string)$_GET['include_meta']);
        $includeUnread = !isset($_GET['include_unread']) || chat_request_bool_value((string)$_GET['include_unread']);
        $waitForChange = chat_request_bool_value((string)($_GET['wait'] ?? '0'));
        $waitTimeoutSeconds = max(1, min(20, (int)($_GET['wait_timeout'] ?? 20)));

        $profile = chat_profile_ensure_stored($uid, $displayName);
        if (chat_request_should_touch_presence(true)) {
            chat_touch_request_presence($profile, 'channel', $channel, '');
        }
        if ($seenAt > 0) {
            chat_seen_mark_channel($uid, $channel, $seenAt);
        }

        $streamState = chat_stream_read_state();
        $currentMessageRevision = (int)($streamState['message_revision'] ?? 0);
        $currentTypingRevision = (int)($streamState['typing_revision'] ?? 0);
        if (
            $waitForChange
            && $requestedMessageRevision > 0
            && $requestedTypingRevision >= 0
            && $requestedMessageRevision === $currentMessageRevision
            && $requestedTypingRevision === $currentTypingRevision
        ) {
            $deadline = microtime(true) + $waitTimeoutSeconds;
            while (!connection_aborted() && microtime(true) < $deadline) {
                usleep(500000);
                $streamState = chat_stream_read_state();
                $currentMessageRevision = (int)($streamState['message_revision'] ?? 0);
                $currentTypingRevision = (int)($streamState['typing_revision'] ?? 0);
                if ($requestedMessageRevision !== $currentMessageRevision || $requestedTypingRevision !== $currentTypingRevision) {
                    break;
                }
            }
        }

        $messageGap = $requestedMessageRevision > 0 && $currentMessageRevision > ($requestedMessageRevision + 1);
        $typingGap = $requestedTypingRevision > 0 && $currentTypingRevision > ($requestedTypingRevision + 1);
        $messagesChanged = $requestedMessageRevision !== $currentMessageRevision;
        $typingChanged = $requestedTypingRevision !== $currentTypingRevision;

        $payload = [
            'active_scope' => 'channel',
            'active_channel' => $channel,
            'messages_changed' => $messagesChanged,
            'typing_changed' => $typingChanged,
            'message_gap' => $messageGap,
            'typing_gap' => $typingGap,
            'message_event' => (!$messageGap && $messagesChanged && is_array($streamState['message_event'] ?? null))
                ? chat_stream_event_apply_authoritative_profiles($streamState['message_event'])
                : null,
            'typing_event' => (!$typingGap && $typingChanged && is_array($streamState['typing_event'] ?? null))
                ? $streamState['typing_event']
                : null,
            'message_revision' => $currentMessageRevision,
            'typing_revision' => $currentTypingRevision,
            'server_time' => chat_now(),
        ];

        if ($includeMeta) {
            $payload['me'] = chat_me_payload($profile, $uid, $theme);
            $payload['live'] = chat_live_payload_for_uid($uid);
        } elseif ($messagesChanged && is_array($payload['message_event'] ?? null) && ($payload['message_event']['kind'] ?? '') === 'settings_update') {
            $payload['live'] = chat_live_payload_for_uid($uid);
        }

        if ($includeMeta || $messagesChanged || $typingChanged) {
            $payload['online_count'] = chat_presence_count_active();
        }

        if ($includeMeta || $typingChanged) {
            $payload['typing_users'] = chat_collect_typing_users_fast($uid, 'channel', $channel, null);
        }

        if ($includeUnread) {
            $payload['unread_hints'] = chat_compute_unread_hints_fast($uid, []);
        }

        chat_response(['ok' => true] + $payload);
    }

    if ($action === 'ping') {
        $channel = chat_valid_channel((string)($_GET['channel'] ?? 'main'));
        $seenAt = chat_request_seen_at();
        $requestedMessageRevision = max(0, (int)($_GET['message_revision'] ?? 0));
        $requestedTypingRevision = max(0, (int)($_GET['typing_revision'] ?? 0));

        $profile = chat_profile_ensure_stored($uid, $displayName);
        if (chat_request_should_touch_presence(true)) {
            chat_presence_touch_stored(
                $uid,
                (string)$profile['display_name'],
                (string)$profile['avatar_seed'],
                (string)($profile['avatar_url'] ?? ''),
                false,
                'channel',
                $channel,
                ''
            );
        }
        if ($seenAt > 0) {
            chat_seen_mark_channel($uid, $channel, $seenAt);
        }

        $streamState = chat_stream_read_state();
        $presence = chat_presence_read_all();

        $currentMessageRevision = (int)($streamState['message_revision'] ?? 0);
        $currentTypingRevision = (int)($streamState['typing_revision'] ?? 0);
        $messageGap = $requestedMessageRevision > 0 && $currentMessageRevision > ($requestedMessageRevision + 1);
        $typingGap = $requestedTypingRevision > 0 && $currentTypingRevision > ($requestedTypingRevision + 1);

        $payload = [
            'me' => [
                'uid' => (string)($profile['uid'] ?? $uid),
                'role' => chat_user_role($uid),
                'is_owner' => chat_is_owner_uid($uid),
                'is_admin' => chat_is_admin_uid($uid),
                'can_send_new_messages' => chat_user_can_send_new_messages($uid),
                'staff_only_new_messages' => chat_staff_only_new_messages_enabled(),
            ],
            'active_scope' => 'channel',
            'active_channel' => $channel,
            'pinned_message' => chat_pinned_payload_for_requested_context($uid, 'channel', $channel, ''),
            'live' => chat_live_payload_for_uid($uid),
            'messages_changed' => $requestedMessageRevision !== $currentMessageRevision,
            'typing_changed' => $requestedTypingRevision !== $currentTypingRevision,
            'message_gap' => $messageGap,
            'typing_gap' => $typingGap,
            'message_event' => (!$messageGap && $requestedMessageRevision !== $currentMessageRevision && is_array($streamState['message_event'] ?? null))
                ? chat_stream_event_apply_authoritative_profiles($streamState['message_event'])
                : null,
            'typing_event' => (!$typingGap && $requestedTypingRevision !== $currentTypingRevision && is_array($streamState['typing_event'] ?? null))
                ? $streamState['typing_event']
                : null,
            'typing_users' => chat_collect_typing_users($presence, $uid, 'channel', $channel, null),
            'online_count' => count($presence),
            'unread_hints' => chat_compute_unread_hints_fast($uid, []),
            'message_revision' => $currentMessageRevision,
            'typing_revision' => $currentTypingRevision,
            'server_time' => chat_now(),
        ];

        chat_response(['ok' => true] + $payload);
    }

    if ($action === 'state') {
        $channel = chat_valid_channel((string)($_GET['channel'] ?? 'main'));
        $seenAt = chat_request_seen_at();
        $includeUsers = isset($_GET['include_users']) && $_GET['include_users'] !== '0';
        $includeStorage = isset($_GET['include_storage']) && $_GET['include_storage'] !== '0';
        $requestedMessageRevision = max(0, (int)($_GET['message_revision'] ?? 0));
        $requestedTypingRevision = max(0, (int)($_GET['typing_revision'] ?? 0));

        $profile = chat_profile_ensure_stored($uid, $displayName);
        if (chat_request_should_touch_presence(true)) {
            chat_presence_touch_stored(
                $uid,
                (string)$profile['display_name'],
                (string)$profile['avatar_seed'],
                (string)($profile['avatar_url'] ?? ''),
                false,
                'channel',
                $channel,
                ''
            );
        }
        if ($seenAt > 0) {
            chat_seen_mark_channel($uid, $channel, $seenAt);
        }

        $streamState = chat_stream_read_state();
        $messagesChanged = $requestedMessageRevision !== (int)($streamState['message_revision'] ?? 0);
        $typingChanged = $requestedTypingRevision !== (int)($streamState['typing_revision'] ?? 0);

        $presence = chat_presence_read_all();
        $includeShadowbanState = chat_is_admin_uid($uid);
        $profiles = $includeUsers ? chat_profiles_read_all() : [];
        $shadowbans = ($includeUsers && $includeShadowbanState) ? chat_shadowbans_read_all() : [];

        $visible = $messagesChanged
            ? chat_messages_apply_authoritative_profiles(chat_messages_list_visible($uid, 'channel', $channel, null))
            : null;
        $users = $includeUsers
            ? chat_collect_users([], [], $profiles, $uid, $shadowbans, $includeShadowbanState)
            : null;
        $typingUsers = chat_collect_typing_users($presence, $uid, 'channel', $channel, null);
        $unreadHints = chat_compute_unread_hints_fast($uid, []);

        $payload = [
            'me' => chat_profile_payload($profile) + ['theme' => $theme],
            'active_scope' => 'channel',
            'active_channel' => $channel,
            'pinned_message' => chat_pinned_payload_for_requested_context($uid, 'channel', $channel, ''),
            'live' => chat_live_payload_for_uid($uid),
            'messages' => $messagesChanged ? array_values(is_array($visible) ? $visible : []) : null,
            'messages_changed' => $messagesChanged,
            'typing_changed' => $typingChanged,
            'users' => $users,
            'online_count' => count($presence),
            'typing_users' => $typingUsers,
            'unread_hints' => $unreadHints,
            'messages_storage' => $includeStorage ? chat_messages_backend_name() : null,
            'storage_info' => ($includeStorage && chat_is_admin_uid($uid)) ? chat_messages_backend_info() : null,
            'message_revision' => (int)($streamState['message_revision'] ?? 0),
            'typing_revision' => (int)($streamState['typing_revision'] ?? 0),
            'server_time' => chat_now(),
        ];

        chat_response(['ok' => true] + $payload);
    }

    chat_error('Unknown action.', 404);
} catch (RuntimeException $exception) {
    $code = $exception->getCode();
    if ($code < 400 || $code > 599) {
        $code = 400;
    }

    if ($code === 429) {
        header('Retry-After: ' . CHAT_MESSAGE_COOLDOWN_SECONDS);
    }

    chat_error($exception->getMessage(), $code);
} catch (Throwable $exception) {
    chat_error('Server error: ' . $exception->getMessage(), 500);
}
