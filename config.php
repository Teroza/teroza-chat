<?php
declare(strict_types=1);

const CHAT_BASE_DIR = __DIR__;
const CHAT_DATA_DIR = CHAT_BASE_DIR . '/data';
const CHAT_UPLOAD_DIR = CHAT_BASE_DIR . '/uploads';
const CHAT_LOCK_FILE = CHAT_DATA_DIR . '/chat.lock';
const CHAT_MESSAGES_FILE = CHAT_DATA_DIR . '/messages.json';
const CHAT_SQLITE_FILE = CHAT_DATA_DIR . '/chat.db';
const CHAT_STREAM_STATE_FILE = CHAT_DATA_DIR . '/stream-state.json';
const CHAT_PRESENCE_FILE = CHAT_DATA_DIR . '/presence.json';
const CHAT_PROFILES_FILE = CHAT_DATA_DIR . '/profiles.json';
const CHAT_SEEN_FILE = CHAT_DATA_DIR . '/seen.json';
const CHAT_SHADOWBANS_FILE = CHAT_DATA_DIR . '/shadowbans.json';
const CHAT_PINS_FILE = CHAT_DATA_DIR . '/pins.json';
const CHAT_RUNTIME_STATE_FILE = CHAT_DATA_DIR . '/runtime-state.json';
const CHAT_OWNER_AUDIT_LOG_FILE = CHAT_DATA_DIR . '/owner-actions.log';
const CHAT_UPLOAD_HTACCESS_FILE = CHAT_UPLOAD_DIR . '/.htaccess';
const CHAT_UPLOAD_INDEX_FILE = CHAT_UPLOAD_DIR . '/index.html';

const CHAT_MAX_UPLOAD_BYTES = 128 * 1024 * 1024;
const CHAT_MAX_PROFILE_AVATAR_BYTES = 2 * 1024 * 1024;
const CHAT_MAX_MESSAGE_CHARS = 1024;
const CHAT_MESSAGE_COOLDOWN_SECONDS = 1;
const CHAT_PRESENCE_TTL = 120;
const CHAT_ONLINE_WINDOW = 50;
const CHAT_TYPING_TTL = 12;
const CHAT_HISTORY_LIMIT = 128;
const CHAT_SSE_MAX_SECONDS = 60;
const CHAT_SSE_HEARTBEAT_SECONDS = 30;
const CHAT_TRIM_LIMIT = 1000; // DO NOT TOUCH THIS
const CHAT_PRESENCE_WRITE_INTERVAL = 60;
const CHAT_SQLITE_WAL_AUTOCHECKPOINT = 400;
const CHAT_SQLITE_JOURNAL_SIZE_LIMIT = 16 * 1024 * 1024;
const CHAT_SQLITE_MAINTENANCE_INTERVAL = 60;
const CHAT_SQLITE_INCREMENTAL_VACUUM_PAGES = 64;
const CHAT_SQLITE_TRANSACTION_RETRIES = 3;
const CHAT_ENABLE_SSE = false;

const CHAT_CHANNELS = [
    'main' => 'گروه عمومی',
    'news' => 'اخبار آزاد',
    'iin' => 'ویس و ویدیو',
    'mime' => 'میم و ادیت',
    'music' => 'موسیقی',
    'conf' => 'کانفیگ و پروکسی',
    'ask' => 'سوالات متداول',
    'misc' => 'متفرقه',
    'report' => 'گزارش باگ',
];

const CHAT_REACTION_SET = [
    '👍', '👎', '🖕', '🤣', '😂', '😢', '😭',
    '🔥', '❤️', '💔', '🤡', '👀', '💯', '🤝',
    '😱', '🤯', '😮', '🤓', '💩', '👻',
];
const CHAT_SUPPORT_DONATE_URL = 'https://trzw.ir/donate/';
const CHAT_SUPPORT_UPLOAD_URL = 'https://trzw.ir/up';
const CHAT_LIVE_MODAL_TEXT = 'با توجه به هزینه های بالای استریم کردن اینترنشنال به صورت لایو فعلا برامون اینکار مقدور نیست. اما اگه حمایتاتون خوب باشه حتما لایو رو میاریم بالا که دوباره لذت ببرید. دم همگی گرم!';
const CHAT_LIVE_ALLOWED_ROTATIONS = [0, 90, 180, 270];
const CHAT_DEFAULT_LIVE_ENABLED = true;
const CHAT_DEFAULT_LIVE_STREAM_URL = 'https://tv.zaringo.sbs/desktop/desktop.m3u8';
const CHAT_DEFAULT_LIVE_ROTATION = 0;
require_once CHAT_BASE_DIR . '/staff-config.php';

function chat_safe_mkdir(string $path): bool
{
    return is_dir($path) || @mkdir($path, 0775, true) || is_dir($path);
}

function chat_safe_file_write(string $path, string $contents, int $flags = 0): bool
{
    $directory = dirname($path);
    if ($directory !== '' && $directory !== '.' && !is_dir($directory) && !chat_safe_mkdir($directory)) {
        return false;
    }

    return @file_put_contents($path, $contents, $flags) !== false;
}

function chat_bootstrap_storage(): void
{
    static $bootstrapped = false;
    if ($bootstrapped) {
        return;
    }
    $bootstrapped = true;

    chat_safe_mkdir(CHAT_DATA_DIR);
    chat_safe_mkdir(CHAT_UPLOAD_DIR);

    if (!file_exists(CHAT_UPLOAD_HTACCESS_FILE)) {
        $htaccess = <<<TXT
            Options -Indexes
            <FilesMatch "\\.(php|phtml|phar|pl|py|jsp|asp|sh|cgi)$">
            Require all denied
            </FilesMatch>
            AddType text/plain .php .phtml .phar .pl .py .jsp .asp .sh .cgi
        TXT;
        chat_safe_file_write(CHAT_UPLOAD_HTACCESS_FILE, $htaccess . "\n", LOCK_EX);
    }

    if (!file_exists(CHAT_UPLOAD_INDEX_FILE)) {
        chat_safe_file_write(CHAT_UPLOAD_INDEX_FILE, "Forbidden\n", LOCK_EX);
    }
}

function chat_with_lock(callable $callback): mixed
{
    static $depth = 0;

    if ($depth > 0) {
        return $callback();
    }

    chat_bootstrap_storage();

    $handle = fopen(CHAT_LOCK_FILE, 'c+');
    if ($handle === false) {
        throw new RuntimeException('Unable to open lock file.');
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('Unable to acquire lock.');
        }

        $depth++;
        return $callback();
    } finally {
        $depth = max(0, $depth - 1);
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function chat_json_read(string $path, mixed $default): mixed
{
    if (!file_exists($path)) {
        return $default;
    }

    $raw = file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return $default;
    }

    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return $default;
    }

    return $decoded;
}

function chat_json_write(string $path, mixed $value): void
{
    $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        throw new RuntimeException('Unable to encode JSON payload.');
    }

    chat_safe_file_write($path, $encoded . "\n", LOCK_EX);
}

function chat_stream_read_state(): array
{
    $pdo = chat_sqlite_connection();
    if ($pdo instanceof PDO) {
        $row = $pdo->query(
            'SELECT revision, message_revision, typing_revision, updated_at, message_event_json, typing_event_json
             FROM stream_state
             WHERE id = 1
             LIMIT 1'
        )->fetch();

        if (is_array($row)) {
            $messageEvent = json_decode((string)($row['message_event_json'] ?? 'null'), true);
            $typingEvent = json_decode((string)($row['typing_event_json'] ?? 'null'), true);

            return [
                'revision' => max(0, (int)($row['revision'] ?? 0)),
                'message_revision' => max(0, (int)($row['message_revision'] ?? 0)),
                'typing_revision' => max(0, (int)($row['typing_revision'] ?? 0)),
                'updated_at' => max(0, (int)($row['updated_at'] ?? 0)),
                'message_event' => is_array($messageEvent) ? $messageEvent : null,
                'typing_event' => is_array($typingEvent) ? $typingEvent : null,
            ];
        }
    }

    $state = chat_json_read(CHAT_STREAM_STATE_FILE, []);
    if (!is_array($state)) {
        $state = [];
    }

    return [
        'revision' => max(0, (int)($state['revision'] ?? 0)),
        'message_revision' => max(0, (int)($state['message_revision'] ?? 0)),
        'typing_revision' => max(0, (int)($state['typing_revision'] ?? 0)),
        'updated_at' => max(0, (int)($state['updated_at'] ?? 0)),
        'message_event' => is_array($state['message_event'] ?? null) ? $state['message_event'] : null,
        'typing_event' => is_array($state['typing_event'] ?? null) ? $state['typing_event'] : null,
    ];
}

function chat_stream_bump(
    string $kind,
    string $scope = 'channel',
    string $channel = 'main',
    array $dmUsers = [],
    array $extra = []
): array
{
    $scope = $scope === 'dm' ? 'dm' : 'channel';
    $channel = chat_valid_channel($channel);

    $cleanDmUsers = [];
    foreach ($dmUsers as $dmUid) {
        $safeUid = chat_sanitize_uid((string)$dmUid);
        if ($safeUid !== '') {
            $cleanDmUsers[$safeUid] = true;
        }
    }
    $dmUsers = array_values(array_keys($cleanDmUsers));
    sort($dmUsers);

    $state = chat_stream_read_state();
    $state['revision'] = max(0, (int)($state['revision'] ?? 0)) + 1;
    $state['updated_at'] = chat_now();

    $event = $extra;
    $event['kind'] = $kind;
    $event['scope'] = $scope;
    $event['channel'] = $channel;
    $event['dm_users'] = $dmUsers;
    $event['at'] = chat_now();

    $pdo = chat_sqlite_connection();
    if ($pdo instanceof PDO) {
        $eventJson = json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($eventJson === false) {
            $eventJson = '{}';
        }

        if ($kind === 'typing') {
            $stmt = $pdo->prepare(
                'UPDATE stream_state
                 SET revision = revision + 1,
                     typing_revision = typing_revision + 1,
                     updated_at = :updated_at,
                     typing_event_json = :event_json
                 WHERE id = 1'
            );
        } else {
            $stmt = $pdo->prepare(
                'UPDATE stream_state
                 SET revision = revision + 1,
                     message_revision = message_revision + 1,
                     updated_at = :updated_at,
                     message_event_json = :event_json
                 WHERE id = 1'
            );
        }

        $stmt->bindValue(':updated_at', (int)$event['at'], PDO::PARAM_INT);
        $stmt->bindValue(':event_json', $eventJson, PDO::PARAM_STR);
        $stmt->execute();

        return chat_stream_read_state();
    }

    if ($kind === 'typing') {
        $state['typing_revision'] = max(0, (int)($state['typing_revision'] ?? 0)) + 1;
        $state['typing_event'] = $event;
    } else {
        $state['message_revision'] = max(0, (int)($state['message_revision'] ?? 0)) + 1;
        $state['message_event'] = $event;
    }

    chat_json_write(CHAT_STREAM_STATE_FILE, $state);
    return $state;
}

function chat_sqlite_supported(): bool
{
    static $supported = null;
    if ($supported !== null) {
        return $supported;
    }

    if (!class_exists('PDO')) {
        $supported = false;
        return $supported;
    }

    try {
        $drivers = PDO::getAvailableDrivers();
    } catch (Throwable) {
        $supported = false;
        return $supported;
    }

    $supported = in_array('sqlite', $drivers, true);
    return $supported;
}

function chat_sqlite_diag_set(array $diag): void
{
    $GLOBALS['CHAT_SQLITE_DIAG'] = $diag;
}

function chat_sqlite_diag_get(): array
{
    $diag = $GLOBALS['CHAT_SQLITE_DIAG'] ?? null;
    return is_array($diag) ? $diag : [];
}

function chat_sqlite_meta_get(PDO $pdo, string $key): ?string
{
    $stmt = $pdo->prepare('SELECT value FROM meta WHERE key = :key LIMIT 1');
    $stmt->bindValue(':key', $key, PDO::PARAM_STR);
    $stmt->execute();
    $value = $stmt->fetchColumn();
    return $value === false ? null : (string)$value;
}

function chat_sqlite_meta_set(PDO $pdo, string $key, string $value): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO meta (key, value) VALUES (:key, :value)
         ON CONFLICT(key) DO UPDATE SET value = excluded.value'
    );
    $stmt->bindValue(':key', $key, PDO::PARAM_STR);
    $stmt->bindValue(':value', $value, PDO::PARAM_STR);
    $stmt->execute();
}

function chat_sqlite_safe_exec(PDO $pdo, string $sql): void
{
    try {
        $pdo->exec($sql);
    } catch (Throwable) {
        // Ignore hosts that disallow or partially support this pragma.
    }
}

function chat_sqlite_pragma_int(PDO $pdo, string $name, int $fallback = 0): int
{
    try {
        $value = $pdo->query('PRAGMA ' . $name)->fetchColumn();
    } catch (Throwable) {
        return $fallback;
    }

    return $value === false ? $fallback : (int)$value;
}

function chat_sqlite_apply_pragmas(PDO $pdo): void
{
    chat_sqlite_safe_exec($pdo, 'PRAGMA journal_mode = WAL');
    chat_sqlite_safe_exec($pdo, 'PRAGMA synchronous = NORMAL');
    chat_sqlite_safe_exec($pdo, 'PRAGMA busy_timeout = 15000');
    chat_sqlite_safe_exec($pdo, 'PRAGMA wal_autocheckpoint = ' . CHAT_SQLITE_WAL_AUTOCHECKPOINT);
    chat_sqlite_safe_exec($pdo, 'PRAGMA journal_size_limit = ' . CHAT_SQLITE_JOURNAL_SIZE_LIMIT);
    chat_sqlite_safe_exec($pdo, 'PRAGMA temp_store = MEMORY');
}

function chat_sqlite_run_maintenance(PDO $pdo): void
{
    $now = chat_now();
    $lastMaintenanceAt = max(0, (int)(chat_sqlite_meta_get($pdo, 'sqlite_maintenance_at') ?? '0'));
    if ($lastMaintenanceAt > 0 && ($now - $lastMaintenanceAt) < CHAT_SQLITE_MAINTENANCE_INTERVAL) {
        return;
    }

    chat_sqlite_safe_exec($pdo, 'DELETE FROM presence WHERE ts < ' . max(0, $now - CHAT_PRESENCE_TTL));
    try {
        $pdo->query('PRAGMA wal_checkpoint(PASSIVE)')->fetchAll();
    } catch (Throwable) {
        // Ignore checkpoint failures on restricted hosts.
    }

    if (chat_sqlite_pragma_int($pdo, 'auto_vacuum', 0) === 2) {
        chat_sqlite_safe_exec($pdo, 'PRAGMA incremental_vacuum(' . CHAT_SQLITE_INCREMENTAL_VACUUM_PAGES . ')');
    }

    chat_sqlite_meta_set($pdo, 'sqlite_maintenance_at', (string)$now);
}

function chat_sqlite_init_schema(PDO $pdo): void
{
    chat_sqlite_safe_exec($pdo, 'PRAGMA auto_vacuum = INCREMENTAL');
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS meta (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL
        )'
    );

    if (chat_sqlite_meta_get($pdo, 'schema_version') === '3') {
        return;
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS messages (
            id TEXT PRIMARY KEY,
            time INTEGER NOT NULL,
            scope TEXT NOT NULL,
            channel TEXT NOT NULL DEFAULT "",
            dm_to_uid TEXT NOT NULL DEFAULT "",
            dm_pair_key TEXT NOT NULL DEFAULT "",
            from_uid TEXT NOT NULL,
            from_name TEXT NOT NULL,
            from_avatar_seed TEXT NOT NULL,
            from_avatar_url TEXT NOT NULL,
            type TEXT NOT NULL,
            text TEXT NOT NULL,
            reactions_json TEXT NOT NULL,
            file_json TEXT NOT NULL,
            reply_to TEXT NOT NULL DEFAULT "",
            edited_at INTEGER NOT NULL DEFAULT 0,
            deleted_at INTEGER NOT NULL DEFAULT 0,
            imported_from_json INTEGER NOT NULL DEFAULT 0
        )'
    );

    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_messages_time ON messages (time)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_messages_channel ON messages (scope, channel, time)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_messages_dm_pair ON messages (scope, dm_pair_key, time)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_messages_dm_to_time ON messages (scope, dm_to_uid, time)');

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS profiles (
            uid TEXT PRIMARY KEY,
            display_name TEXT NOT NULL,
            avatar_seed TEXT NOT NULL,
            avatar_url TEXT NOT NULL,
            updated_at INTEGER NOT NULL DEFAULT 0
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS seen_state (
            uid TEXT NOT NULL,
            conversation_key TEXT NOT NULL,
            seen_at INTEGER NOT NULL DEFAULT 0,
            updated_at INTEGER NOT NULL DEFAULT 0,
            PRIMARY KEY (uid, conversation_key)
        )'
    );
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_seen_state_uid ON seen_state (uid, updated_at)');

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS shadowbans (
            uid TEXT PRIMARY KEY,
            shadowbanned_at INTEGER NOT NULL DEFAULT 0,
            updated_at INTEGER NOT NULL DEFAULT 0,
            updated_by TEXT NOT NULL DEFAULT ""
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS pins (
            conversation_key TEXT PRIMARY KEY,
            scope TEXT NOT NULL,
            channel TEXT NOT NULL DEFAULT "",
            dm_pair_key TEXT NOT NULL DEFAULT "",
            message_id TEXT NOT NULL,
            pinned_by TEXT NOT NULL DEFAULT "",
            pinned_at INTEGER NOT NULL DEFAULT 0
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS presence (
            uid TEXT PRIMARY KEY,
            ts INTEGER NOT NULL DEFAULT 0,
            display_name TEXT NOT NULL,
            avatar_seed TEXT NOT NULL,
            avatar_url TEXT NOT NULL,
            typing_until INTEGER NOT NULL DEFAULT 0,
            typing_scope TEXT NOT NULL DEFAULT "",
            typing_channel TEXT NOT NULL DEFAULT "",
            typing_to_uid TEXT NOT NULL DEFAULT "",
            last_message_at INTEGER NOT NULL DEFAULT 0
        )'
    );
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_presence_ts ON presence (ts)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_presence_typing ON presence (typing_until, typing_scope, typing_channel, typing_to_uid)');

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS stream_state (
            id INTEGER PRIMARY KEY CHECK (id = 1),
            revision INTEGER NOT NULL DEFAULT 0,
            message_revision INTEGER NOT NULL DEFAULT 0,
            typing_revision INTEGER NOT NULL DEFAULT 0,
            updated_at INTEGER NOT NULL DEFAULT 0,
            message_event_json TEXT NOT NULL DEFAULT "null",
            typing_event_json TEXT NOT NULL DEFAULT "null"
        )'
    );
    $pdo->exec(
        'INSERT OR IGNORE INTO stream_state (
            id, revision, message_revision, typing_revision, updated_at, message_event_json, typing_event_json
        ) VALUES (1, 0, 0, 0, 0, "null", "null")'
    );

    chat_sqlite_meta_set($pdo, 'schema_version', '3');
}

function chat_dm_pair_key(string $uidA, string $uidB): string
{
    $a = chat_sanitize_uid($uidA);
    $b = chat_sanitize_uid($uidB);
    if ($a === '' || $b === '') {
        return '';
    }

    $pair = [$a, $b];
    sort($pair, SORT_STRING);
    return $pair[0] . '|' . $pair[1];
}

function chat_pin_conversation_key(string $scope, string $channel = 'main', string $uidA = '', string $uidB = ''): string
{
    if ($scope === 'dm') {
        $pairKey = chat_dm_pair_key($uidA, $uidB);
        return $pairKey === '' ? '' : 'dm:' . $pairKey;
    }

    return 'channel:' . chat_valid_channel($channel);
}

function chat_pin_entry_from_message(array $message, string $pinnedBy, int $pinnedAt = 0): ?array
{
    $scope = (string)($message['scope'] ?? 'channel');
    $scope = $scope === 'dm' ? 'dm' : 'channel';
    $channel = $scope === 'channel' ? chat_valid_channel((string)($message['channel'] ?? 'main')) : '';
    $fromUid = chat_sanitize_uid((string)($message['from_uid'] ?? ''));
    $toUid = $scope === 'dm' ? chat_sanitize_uid((string)($message['dm_to_uid'] ?? '')) : '';
    $conversationKey = chat_pin_conversation_key($scope, $channel, $fromUid, $toUid);
    if ($conversationKey === '') {
        return null;
    }

    return [
        'conversation_key' => $conversationKey,
        'scope' => $scope,
        'channel' => $channel,
        'dm_pair_key' => $scope === 'dm' ? chat_dm_pair_key($fromUid, $toUid) : '',
        'message_id' => (string)($message['id'] ?? ''),
        'pinned_by' => chat_sanitize_uid($pinnedBy),
        'pinned_at' => max(1, $pinnedAt > 0 ? $pinnedAt : chat_now()),
    ];
}

function chat_pin_payload_from_entry(array $entry, array $message): array
{
    return [
        'message' => $message,
        'pinned_by' => chat_sanitize_uid((string)($entry['pinned_by'] ?? '')),
        'pinned_at' => max(0, (int)($entry['pinned_at'] ?? 0)),
    ];
}

function chat_pins_read_all(): array
{
    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        $pins = chat_json_read(CHAT_PINS_FILE, []);
        return is_array($pins) ? $pins : [];
    }

    $rows = $pdo->query(
        'SELECT conversation_key, scope, channel, dm_pair_key, message_id, pinned_by, pinned_at
         FROM pins
         ORDER BY pinned_at DESC, conversation_key ASC'
    )->fetchAll();

    $pins = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $key = (string)($row['conversation_key'] ?? '');
        if ($key === '') {
            continue;
        }

        $pins[$key] = [
            'conversation_key' => $key,
            'scope' => (string)($row['scope'] ?? 'channel'),
            'channel' => (string)($row['channel'] ?? ''),
            'dm_pair_key' => (string)($row['dm_pair_key'] ?? ''),
            'message_id' => (string)($row['message_id'] ?? ''),
            'pinned_by' => chat_sanitize_uid((string)($row['pinned_by'] ?? '')),
            'pinned_at' => max(0, (int)($row['pinned_at'] ?? 0)),
        ];
    }

    return $pins;
}

function chat_pin_upsert(array $entry): void
{
    $key = (string)($entry['conversation_key'] ?? '');
    if ($key === '') {
        return;
    }

    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        $pins = chat_pins_read_all();
        $pins[$key] = $entry;
        chat_json_write(CHAT_PINS_FILE, $pins);
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO pins (conversation_key, scope, channel, dm_pair_key, message_id, pinned_by, pinned_at)
         VALUES (:conversation_key, :scope, :channel, :dm_pair_key, :message_id, :pinned_by, :pinned_at)
         ON CONFLICT(conversation_key) DO UPDATE SET
             scope = excluded.scope,
             channel = excluded.channel,
             dm_pair_key = excluded.dm_pair_key,
             message_id = excluded.message_id,
             pinned_by = excluded.pinned_by,
             pinned_at = excluded.pinned_at'
    );
    $stmt->bindValue(':conversation_key', $key, PDO::PARAM_STR);
    $stmt->bindValue(':scope', (string)($entry['scope'] ?? 'channel'), PDO::PARAM_STR);
    $stmt->bindValue(':channel', (string)($entry['channel'] ?? ''), PDO::PARAM_STR);
    $stmt->bindValue(':dm_pair_key', (string)($entry['dm_pair_key'] ?? ''), PDO::PARAM_STR);
    $stmt->bindValue(':message_id', (string)($entry['message_id'] ?? ''), PDO::PARAM_STR);
    $stmt->bindValue(':pinned_by', chat_sanitize_uid((string)($entry['pinned_by'] ?? '')), PDO::PARAM_STR);
    $stmt->bindValue(':pinned_at', max(0, (int)($entry['pinned_at'] ?? 0)), PDO::PARAM_INT);
    $stmt->execute();
}

function chat_pin_clear_by_conversation_key(string $conversationKey): bool
{
    if ($conversationKey === '') {
        return false;
    }

    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        $pins = chat_pins_read_all();
        if (!array_key_exists($conversationKey, $pins)) {
            return false;
        }
        unset($pins[$conversationKey]);
        chat_json_write(CHAT_PINS_FILE, $pins);
        return true;
    }

    $stmt = $pdo->prepare('DELETE FROM pins WHERE conversation_key = :conversation_key');
    $stmt->bindValue(':conversation_key', $conversationKey, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

function chat_pin_clear_for_context(string $scope, string $channel, string $uidA, string $uidB = ''): bool
{
    return chat_pin_clear_by_conversation_key(chat_pin_conversation_key($scope, $channel, $uidA, $uidB));
}

function chat_pins_clear_by_message_id(string $messageId): bool
{
    $safeMessageId = trim($messageId);
    if ($safeMessageId === '') {
        return false;
    }

    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        $pins = chat_pins_read_all();
        $changed = false;
        foreach ($pins as $key => $entry) {
            if ((string)($entry['message_id'] ?? '') !== $safeMessageId) {
                continue;
            }
            unset($pins[$key]);
            $changed = true;
        }
        if ($changed) {
            chat_json_write(CHAT_PINS_FILE, $pins);
        }
        return $changed;
    }

    $stmt = $pdo->prepare('DELETE FROM pins WHERE message_id = :message_id');
    $stmt->bindValue(':message_id', $safeMessageId, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

function chat_pins_clear_by_user(string $uid): int
{
    $safeUid = chat_sanitize_uid($uid);
    if ($safeUid === '') {
        return 0;
    }

    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        $messages = chat_messages_read_all();
        $messageIds = [];
        foreach ($messages as $message) {
            if ((string)($message['from_uid'] ?? '') === $safeUid) {
                $messageIds[(string)($message['id'] ?? '')] = true;
            }
        }

        if ($messageIds === []) {
            return 0;
        }

        $pins = chat_pins_read_all();
        $removed = 0;
        foreach ($pins as $key => $entry) {
            $messageId = (string)($entry['message_id'] ?? '');
            if ($messageId === '' || !isset($messageIds[$messageId])) {
                continue;
            }
            unset($pins[$key]);
            $removed++;
        }
        if ($removed > 0) {
            chat_json_write(CHAT_PINS_FILE, $pins);
        }
        return $removed;
    }

    $stmt = $pdo->prepare(
        'DELETE FROM pins
         WHERE message_id IN (
             SELECT id FROM messages WHERE from_uid = :uid
         )'
    );
    $stmt->bindValue(':uid', $safeUid, PDO::PARAM_STR);
    $stmt->execute();
    return max(0, (int)$stmt->rowCount());
}

function chat_pinned_message_for_context(string $viewerUid, string $scope, string $channel = 'main', ?string $dmUid = null): ?array
{
    $safeViewerUid = chat_sanitize_uid($viewerUid);
    $safeDmUid = chat_sanitize_uid((string)$dmUid);
    $conversationKey = chat_pin_conversation_key($scope, $channel, $safeViewerUid, $safeDmUid);
    if ($conversationKey === '') {
        return null;
    }

    $entry = null;
    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        $pins = chat_pins_read_all();
        $entry = $pins[$conversationKey] ?? null;
    } else {
        $stmt = $pdo->prepare(
            'SELECT conversation_key, scope, channel, dm_pair_key, message_id, pinned_by, pinned_at
             FROM pins
             WHERE conversation_key = :conversation_key
             LIMIT 1'
        );
        $stmt->bindValue(':conversation_key', $conversationKey, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();
        $entry = is_array($row) ? $row : null;
    }

    if (!is_array($entry)) {
        return null;
    }

    $messageId = trim((string)($entry['message_id'] ?? ''));
    if ($messageId === '') {
        chat_pin_clear_by_conversation_key($conversationKey);
        return null;
    }

    $message = chat_message_find($messageId);
    if (!is_array($message) || !chat_user_can_view_message($message, $safeViewerUid) || !empty($message['deleted_at'])) {
        chat_pin_clear_by_conversation_key($conversationKey);
        return null;
    }

    return chat_pin_payload_from_entry($entry, $message);
}

function chat_sqlite_message_upsert(PDO $pdo, array $message, int $importedFromJson = 0): void
{
    $normalized = chat_normalize_message($message);
    if ($normalized === null) {
        return;
    }

    $scope = (string)($normalized['scope'] ?? 'channel');
    $channel = $scope === 'channel' ? chat_valid_channel((string)($normalized['channel'] ?? 'main')) : '';
    $dmToUid = $scope === 'dm' ? chat_sanitize_uid((string)($normalized['dm_to_uid'] ?? '')) : '';
    $dmPairKey = $scope === 'dm'
        ? chat_dm_pair_key((string)($normalized['from_uid'] ?? ''), $dmToUid)
        : '';

    $reactionsJson = json_encode(
        chat_normalize_reactions($normalized['reactions'] ?? []),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    if ($reactionsJson === false) {
        $reactionsJson = '{}';
    }

    $filePayload = [];
    if (!empty($normalized['file']) && is_array($normalized['file'])) {
        $filePayload = [
            'url' => (string)($normalized['file']['url'] ?? ''),
            'name' => (string)($normalized['file']['name'] ?? 'file'),
            'size' => max(0, (int)($normalized['file']['size'] ?? 0)),
            'mime' => (string)($normalized['file']['mime'] ?? 'application/octet-stream'),
        ];
    }
    $fileJson = json_encode($filePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($fileJson === false) {
        $fileJson = '{}';
    }

    $stmt = $pdo->prepare(
        'INSERT INTO messages (
            id, time, scope, channel, dm_to_uid, dm_pair_key,
            from_uid, from_name, from_avatar_seed, from_avatar_url,
            type, text, reactions_json, file_json, reply_to, edited_at, deleted_at, imported_from_json
        ) VALUES (
            :id, :time, :scope, :channel, :dm_to_uid, :dm_pair_key,
            :from_uid, :from_name, :from_avatar_seed, :from_avatar_url,
            :type, :text, :reactions_json, :file_json, :reply_to, :edited_at, :deleted_at, :imported_from_json
        )
        ON CONFLICT(id) DO UPDATE SET
            time = excluded.time,
            scope = excluded.scope,
            channel = excluded.channel,
            dm_to_uid = excluded.dm_to_uid,
            dm_pair_key = excluded.dm_pair_key,
            from_uid = excluded.from_uid,
            from_name = excluded.from_name,
            from_avatar_seed = excluded.from_avatar_seed,
            from_avatar_url = excluded.from_avatar_url,
            type = excluded.type,
            text = excluded.text,
            reactions_json = excluded.reactions_json,
            file_json = excluded.file_json,
            reply_to = excluded.reply_to,
            edited_at = excluded.edited_at,
            deleted_at = excluded.deleted_at,
            imported_from_json = excluded.imported_from_json'
    );

    $stmt->bindValue(':id', (string)($normalized['id'] ?? ''), PDO::PARAM_STR);
    $stmt->bindValue(':time', max(1, (int)($normalized['time'] ?? chat_now())), PDO::PARAM_INT);
    $stmt->bindValue(':scope', $scope, PDO::PARAM_STR);
    $stmt->bindValue(':channel', $channel, PDO::PARAM_STR);
    $stmt->bindValue(':dm_to_uid', $dmToUid, PDO::PARAM_STR);
    $stmt->bindValue(':dm_pair_key', $dmPairKey, PDO::PARAM_STR);
    $stmt->bindValue(':from_uid', (string)($normalized['from_uid'] ?? ''), PDO::PARAM_STR);
    $stmt->bindValue(':from_name', (string)($normalized['from_name'] ?? ''), PDO::PARAM_STR);
    $stmt->bindValue(':from_avatar_seed', (string)($normalized['from_avatar_seed'] ?? ''), PDO::PARAM_STR);
    $stmt->bindValue(':from_avatar_url', (string)($normalized['from_avatar_url'] ?? ''), PDO::PARAM_STR);
    $stmt->bindValue(':type', (string)($normalized['type'] ?? 'text'), PDO::PARAM_STR);
    $stmt->bindValue(':text', (string)($normalized['text'] ?? ''), PDO::PARAM_STR);
    $stmt->bindValue(':reactions_json', $reactionsJson, PDO::PARAM_STR);
    $stmt->bindValue(':file_json', $fileJson, PDO::PARAM_STR);
    $stmt->bindValue(':reply_to', (string)($normalized['reply_to'] ?? ''), PDO::PARAM_STR);
    $stmt->bindValue(':edited_at', max(0, (int)($normalized['edited_at'] ?? 0)), PDO::PARAM_INT);
    $stmt->bindValue(':deleted_at', max(0, (int)($normalized['deleted_at'] ?? 0)), PDO::PARAM_INT);
    $stmt->bindValue(':imported_from_json', $importedFromJson > 0 ? 1 : 0, PDO::PARAM_INT);
    $stmt->execute();
}

function chat_sqlite_trim_messages(PDO $pdo): void
{
    $count = (int)$pdo->query('SELECT COUNT(*) FROM messages')->fetchColumn();
    if ($count <= (int) CHAT_TRIM_LIMIT) {
        return;
    }

    $deleteCount = max(0, $count - 2600);
    if ($deleteCount <= 0) {
        return;
    }

    $pdo->exec(
        'DELETE FROM messages WHERE rowid IN (
            SELECT rowid FROM messages ORDER BY time ASC, rowid ASC LIMIT ' . $deleteCount . '
        )'
    );
}

function chat_sqlite_row_to_message(array $row): ?array
{
    $reactions = [];
    if (isset($row['reactions_json'])) {
        $decoded = json_decode((string)$row['reactions_json'], true);
        $reactions = is_array($decoded) ? $decoded : [];
    }

    $filePayload = [];
    if (isset($row['file_json'])) {
        $decoded = json_decode((string)$row['file_json'], true);
        $filePayload = is_array($decoded) ? $decoded : [];
    }

    $message = [
        'id' => (string)($row['id'] ?? ''),
        'time' => max(1, (int)($row['time'] ?? chat_now())),
        'scope' => (string)($row['scope'] ?? 'channel'),
        'from_uid' => (string)($row['from_uid'] ?? ''),
        'from_name' => (string)($row['from_name'] ?? ''),
        'from_avatar_seed' => (string)($row['from_avatar_seed'] ?? ''),
        'from_avatar_url' => (string)($row['from_avatar_url'] ?? ''),
        'type' => (string)($row['type'] ?? 'text'),
        'text' => (string)($row['text'] ?? ''),
        'reactions' => $reactions,
    ];

    if (($message['scope'] ?? 'channel') === 'dm') {
        $message['dm_to_uid'] = (string)($row['dm_to_uid'] ?? '');
    } else {
        $message['channel'] = chat_valid_channel((string)($row['channel'] ?? 'main'));
    }

    if (!empty($filePayload['url'])) {
        $message['file'] = [
            'url' => (string)($filePayload['url'] ?? ''),
            'name' => (string)($filePayload['name'] ?? 'file'),
            'size' => max(0, (int)($filePayload['size'] ?? 0)),
            'mime' => (string)($filePayload['mime'] ?? 'application/octet-stream'),
        ];
    }

    $replyTo = trim((string)($row['reply_to'] ?? ''));
    if ($replyTo !== '') {
        $message['reply_to'] = $replyTo;
    }

    $editedAt = max(0, (int)($row['edited_at'] ?? 0));
    if ($editedAt > 0) {
        $message['edited_at'] = $editedAt;
    }

    $deletedAt = max(0, (int)($row['deleted_at'] ?? 0));
    if ($deletedAt > 0) {
        $message['deleted_at'] = $deletedAt;
    }

    return chat_normalize_message($message);
}

function chat_sqlite_import_messages_json_once(PDO $pdo): void
{
    if (chat_sqlite_meta_get($pdo, 'messages_json_imported_at') !== null) {
        return;
    }

    $messagesRaw = chat_json_read(CHAT_MESSAGES_FILE, []);
    $messagesRaw = is_array($messagesRaw) ? $messagesRaw : [];
    $normalized = chat_normalize_messages($messagesRaw);

    $backupPath = '';
    if (file_exists(CHAT_MESSAGES_FILE)) {
        $stamp = date('Ymd_His');
        $backupPath = CHAT_DATA_DIR . '/messages.imported-backup.' . $stamp . '.json';
        @copy(CHAT_MESSAGES_FILE, $backupPath);
    }

    $pdo->beginTransaction();
    try {
        foreach ($normalized as $message) {
            chat_sqlite_message_upsert($pdo, $message, 1);
        }

        $size = file_exists(CHAT_MESSAGES_FILE) ? (int)(filesize(CHAT_MESSAGES_FILE) ?: 0) : 0;
        $hash = file_exists(CHAT_MESSAGES_FILE) ? (string)(sha1_file(CHAT_MESSAGES_FILE) ?: '') : '';
        chat_sqlite_meta_set($pdo, 'messages_json_imported_at', (string)chat_now());
        chat_sqlite_meta_set($pdo, 'messages_json_imported_size', (string)$size);
        chat_sqlite_meta_set($pdo, 'messages_json_imported_hash', $hash);
        if ($backupPath !== '') {
            chat_sqlite_meta_set($pdo, 'messages_json_imported_backup', basename($backupPath));
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function chat_sqlite_connection(): ?PDO
{
    static $pdo = null;
    static $attempted = false;

    if ($attempted) {
        return $pdo;
    }
    $attempted = true;

    if (!chat_sqlite_supported()) {
        chat_sqlite_diag_set([
            'backend' => 'json',
            'reason' => 'pdo_sqlite driver unavailable',
            'pdo' => class_exists('PDO'),
            'drivers' => class_exists('PDO') ? PDO::getAvailableDrivers() : [],
            'sqlite_file' => CHAT_SQLITE_FILE,
            'data_dir' => CHAT_DATA_DIR,
            'data_dir_writable' => is_dir(CHAT_DATA_DIR) ? is_writable(CHAT_DATA_DIR) : false,
        ]);
        return null;
    }

    try {
        chat_bootstrap_storage();

        $pdo = new PDO('sqlite:' . CHAT_SQLITE_FILE);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        chat_sqlite_apply_pragmas($pdo);

        chat_sqlite_init_schema($pdo);
        chat_sqlite_import_messages_json_once($pdo);
        chat_sqlite_import_profiles_json_once($pdo);
        chat_sqlite_import_presence_json_once($pdo);
        chat_sqlite_import_shadowbans_json_once($pdo);

        chat_sqlite_diag_set([
            'backend' => 'sqlite',
            'reason' => 'ok',
            'sqlite_file' => CHAT_SQLITE_FILE,
            'sqlite_file_exists' => file_exists(CHAT_SQLITE_FILE),
            'sqlite_file_readable' => file_exists(CHAT_SQLITE_FILE) ? is_readable(CHAT_SQLITE_FILE) : false,
            'sqlite_file_writable' => file_exists(CHAT_SQLITE_FILE) ? is_writable(CHAT_SQLITE_FILE) : false,
            'data_dir' => CHAT_DATA_DIR,
            'data_dir_writable' => is_dir(CHAT_DATA_DIR) ? is_writable(CHAT_DATA_DIR) : false,
            'journal_mode' => 'wal',
            'wal_autocheckpoint' => CHAT_SQLITE_WAL_AUTOCHECKPOINT,
            'journal_size_limit' => CHAT_SQLITE_JOURNAL_SIZE_LIMIT,
            'auto_vacuum' => chat_sqlite_pragma_int($pdo, 'auto_vacuum', 0),
        ]);

        return $pdo;
    } catch (Throwable $e) {
        chat_sqlite_diag_set([
            'backend' => 'json',
            'reason' => 'sqlite connection failed',
            'error' => $e->getMessage(),
            'sqlite_file' => CHAT_SQLITE_FILE,
            'sqlite_file_exists' => file_exists(CHAT_SQLITE_FILE),
            'data_dir' => CHAT_DATA_DIR,
            'data_dir_exists' => is_dir(CHAT_DATA_DIR),
            'data_dir_writable' => is_dir(CHAT_DATA_DIR) ? is_writable(CHAT_DATA_DIR) : false,
        ]);
        $pdo = null;
        return null;
    }
}

function chat_messages_use_sqlite(): bool
{
    return chat_sqlite_connection() instanceof PDO;
}

function chat_messages_backend_name(): string
{
    return chat_messages_use_sqlite() ? 'sqlite' : 'json';
}

function chat_messages_backend_info(): array
{
    $backend = chat_messages_backend_name();
    $diag = chat_sqlite_diag_get();

    return [
        'backend' => $backend,
        'sqlite_supported' => chat_sqlite_supported(),
        'sqlite_file_exists' => file_exists(CHAT_SQLITE_FILE),
        'sqlite_size_bytes' => file_exists(CHAT_SQLITE_FILE) ? max(0, (int)filesize(CHAT_SQLITE_FILE)) : 0,
        'data_dir_writable' => is_dir(CHAT_DATA_DIR) ? is_writable(CHAT_DATA_DIR) : false,
        'diag' => $diag,
    ];
}

function chat_runtime_state_read(): array
{
    $state = chat_json_read(CHAT_RUNTIME_STATE_FILE, []);
    $state = is_array($state) ? $state : [];

    $admins = [];
    foreach (($state['admins'] ?? []) as $uid) {
        $safeUid = chat_sanitize_uid((string)$uid);
        if ($safeUid !== '') {
            $admins[$safeUid] = true;
        }
    }

    $removedAdmins = [];
    foreach (($state['removed_admins'] ?? []) as $uid) {
        $safeUid = chat_sanitize_uid((string)$uid);
        if ($safeUid !== '') {
            $removedAdmins[$safeUid] = true;
        }
    }

    $staffOnly = $state['staff_only_new_messages'] ?? null;
    if ($staffOnly !== null) {
        $staffOnly = (bool)$staffOnly;
    }

    $updatedAt = max(0, (int)($state['updated_at'] ?? 0));
    $storedLiveStreamUrl = chat_sanitize_live_stream_url((string)($state['live_stream_url'] ?? ''));
    $hasStoredLiveStreamUrl = $storedLiveStreamUrl !== '';
    $usesStoredLiveConfig = $updatedAt > 0 && ($hasStoredLiveStreamUrl || !empty($state['live_enabled']));
    $liveEnabled = $usesStoredLiveConfig ? !empty($state['live_enabled']) : CHAT_DEFAULT_LIVE_ENABLED;
    $liveStreamUrl = $hasStoredLiveStreamUrl ? $storedLiveStreamUrl : CHAT_DEFAULT_LIVE_STREAM_URL;
    $liveRotation = $usesStoredLiveConfig
        ? chat_sanitize_live_rotation((int)($state['live_rotation'] ?? CHAT_DEFAULT_LIVE_ROTATION))
        : CHAT_DEFAULT_LIVE_ROTATION;

    return [
        'admins' => array_values(array_keys($admins)),
        'removed_admins' => array_values(array_keys($removedAdmins)),
        'staff_only_new_messages' => $staffOnly,
        'live_enabled' => $liveEnabled,
        'live_stream_url' => $liveStreamUrl,
        'live_rotation' => $liveRotation,
        'updated_at' => $updatedAt,
        'updated_by' => chat_sanitize_uid((string)($state['updated_by'] ?? '')),
    ];
}

function chat_runtime_state_write(array $state): array
{
    $normalized = chat_runtime_state_read();
    $normalized['admins'] = array_values(array_unique(array_map(
        static fn(string $uid): string => chat_sanitize_uid($uid),
        array_filter(array_map('strval', (array)($state['admins'] ?? [])))
    )));
    $normalized['admins'] = array_values(array_filter($normalized['admins']));

    $normalized['removed_admins'] = array_values(array_unique(array_map(
        static fn(string $uid): string => chat_sanitize_uid($uid),
        array_filter(array_map('strval', (array)($state['removed_admins'] ?? [])))
    )));
    $normalized['removed_admins'] = array_values(array_filter($normalized['removed_admins']));

    $staffOnly = $state['staff_only_new_messages'] ?? null;
    $normalized['staff_only_new_messages'] = $staffOnly === null ? null : (bool)$staffOnly;
    if (array_key_exists('live_enabled', $state)) {
        $normalized['live_enabled'] = !empty($state['live_enabled']);
    }
    if (array_key_exists('live_stream_url', $state)) {
        $normalized['live_stream_url'] = chat_sanitize_live_stream_url((string)$state['live_stream_url']);
    }
    if (array_key_exists('live_rotation', $state)) {
        $normalized['live_rotation'] = chat_sanitize_live_rotation((int)$state['live_rotation']);
    }
    $normalized['updated_at'] = max(0, (int)($state['updated_at'] ?? chat_now()));
    $normalized['updated_by'] = chat_sanitize_uid((string)($state['updated_by'] ?? ''));

    chat_json_write(CHAT_RUNTIME_STATE_FILE, $normalized);
    return $normalized;
}

function chat_live_owner_payload(): array
{
    $runtime = chat_runtime_state_read();
    $streamUrl = (string)($runtime['live_stream_url'] ?? '');

    return [
        'enabled' => !empty($runtime['live_enabled']),
        'stream_url' => $streamUrl,
        'has_stream_url' => $streamUrl !== '',
        'rotation' => chat_sanitize_live_rotation((int)($runtime['live_rotation'] ?? 0)),
        'updated_at' => max(0, (int)($runtime['updated_at'] ?? 0)),
        'updated_by' => chat_sanitize_uid((string)($runtime['updated_by'] ?? '')),
    ];
}

function chat_live_public_payload(): array
{
    $live = chat_live_owner_payload();

    return [
        'enabled' => (bool)$live['enabled'],
        'stream_url' => (bool)$live['enabled'] ? (string)$live['stream_url'] : '',
        'has_stream_url' => (bool)$live['has_stream_url'],
        'rotation' => (int)$live['rotation'],
        'updated_at' => (int)$live['updated_at'],
        'updated_by' => (string)$live['updated_by'],
    ];
}

function chat_live_payload_for_uid(string $uid): array
{
    return chat_is_owner_uid($uid) ? chat_live_owner_payload() : chat_live_public_payload();
}

function chat_live_update(bool $enabled, string $streamUrl, int $rotation, string $actorUid = ''): array
{
    $runtime = chat_runtime_state_read();
    $streamUrl = $streamUrl !== '' ? $streamUrl : CHAT_DEFAULT_LIVE_STREAM_URL;
    chat_runtime_state_write([
        'admins' => $runtime['admins'],
        'removed_admins' => $runtime['removed_admins'],
        'staff_only_new_messages' => $runtime['staff_only_new_messages'],
        'live_enabled' => $enabled,
        'live_stream_url' => $streamUrl,
        'live_rotation' => $rotation,
        'updated_at' => chat_now(),
        'updated_by' => $actorUid,
    ]);

    return chat_live_owner_payload();
}

function chat_owner_audit_log(string $actorUid, string $action, array $details = []): void
{
    $entry = [
        'at' => chat_now(),
        'actor_uid' => chat_sanitize_uid($actorUid),
        'action' => preg_replace('/[^a-z0-9_.-]/i', '_', trim($action)) ?: 'owner.action',
        'details' => $details,
    ];

    $encoded = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        return;
    }

    chat_safe_file_write(CHAT_OWNER_AUDIT_LOG_FILE, $encoded . "\n", FILE_APPEND | LOCK_EX);
}

function chat_owner_audit_recent(int $limit = 12): array
{
    $safeLimit = max(1, min(50, $limit));
    if (!file_exists(CHAT_OWNER_AUDIT_LOG_FILE)) {
        return [];
    }

    $lines = @file(CHAT_OWNER_AUDIT_LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines) || !$lines) {
        return [];
    }

    $slice = array_slice($lines, -$safeLimit);
    $items = [];
    foreach (array_reverse($slice) as $line) {
        $decoded = json_decode((string)$line, true);
        if (!is_array($decoded)) {
            continue;
        }

        $items[] = [
            'at' => max(0, (int)($decoded['at'] ?? 0)),
            'actor_uid' => chat_sanitize_uid((string)($decoded['actor_uid'] ?? '')),
            'action' => (string)($decoded['action'] ?? ''),
            'details' => is_array($decoded['details'] ?? null) ? $decoded['details'] : [],
        ];
    }

    return $items;
}

function chat_admin_membership_map(): array
{
    $runtime = chat_runtime_state_read();
    $owners = [];
    foreach (CHAT_OWNER_UIDS as $uid) {
        $safeUid = chat_sanitize_uid((string)$uid);
        if ($safeUid !== '') {
            $owners[$safeUid] = 'owner';
        }
    }

    $admins = [];
    foreach (CHAT_ADMIN_UIDS as $uid) {
        $safeUid = chat_sanitize_uid((string)$uid);
        if ($safeUid !== '' && !isset($owners[$safeUid])) {
            $admins[$safeUid] = 'builtin';
        }
    }

    foreach ($runtime['removed_admins'] as $uid) {
        unset($admins[$uid]);
    }

    foreach ($runtime['admins'] as $uid) {
        if (!isset($owners[$uid])) {
            $admins[$uid] = 'runtime';
        }
    }

    return [
        'owners' => $owners,
        'admins' => $admins,
        'runtime' => $runtime,
    ];
}

function chat_admin_source_for_uid(string $uid): string
{
    $safeUid = chat_sanitize_uid($uid);
    if ($safeUid === '') {
        return 'user';
    }

    $membership = chat_admin_membership_map();
    if (isset($membership['owners'][$safeUid])) {
        return 'owner';
    }
    if (isset($membership['admins'][$safeUid])) {
        return (string)$membership['admins'][$safeUid];
    }
    return 'user';
}

function chat_admin_set(string $uid, bool $enabled, string $actorUid = ''): array
{
    $safeUid = chat_sanitize_uid($uid);
    $safeActorUid = chat_sanitize_uid($actorUid);
    if ($safeUid === '') {
        throw new RuntimeException('Invalid user id.', 422);
    }
    if (chat_is_owner_uid($safeUid)) {
        throw new RuntimeException('Owner accounts can not be changed here.', 422);
    }

    $runtime = chat_runtime_state_read();
    $admins = array_fill_keys($runtime['admins'], true);
    $removed = array_fill_keys($runtime['removed_admins'], true);
    $builtin = in_array($safeUid, array_map('chat_sanitize_uid', CHAT_ADMIN_UIDS), true);

    if ($enabled) {
        $admins[$safeUid] = true;
        unset($removed[$safeUid]);
    } else {
        unset($admins[$safeUid]);
        if ($builtin) {
            $removed[$safeUid] = true;
        } else {
            unset($removed[$safeUid]);
        }
    }

    $runtime = chat_runtime_state_write([
        'admins' => array_keys($admins),
        'removed_admins' => array_keys($removed),
        'staff_only_new_messages' => $runtime['staff_only_new_messages'],
        'updated_at' => chat_now(),
        'updated_by' => $safeActorUid,
    ]);

    $role = chat_user_role($safeUid);
    return [
        'uid' => $safeUid,
        'enabled' => $role === 'admin',
        'role' => $role,
        'source' => chat_admin_source_for_uid($safeUid),
        'updated_at' => (int)$runtime['updated_at'],
        'updated_by' => $safeActorUid,
    ];
}

function chat_set_staff_only_new_messages(bool $enabled, string $actorUid = ''): array
{
    $runtime = chat_runtime_state_read();
    $runtime = chat_runtime_state_write([
        'admins' => $runtime['admins'],
        'removed_admins' => $runtime['removed_admins'],
        'staff_only_new_messages' => $enabled,
        'live_enabled' => $runtime['live_enabled'],
        'live_stream_url' => $runtime['live_stream_url'],
        'live_rotation' => $runtime['live_rotation'],
        'updated_at' => chat_now(),
        'updated_by' => $actorUid,
    ]);

    return [
        'enabled' => (bool)$runtime['staff_only_new_messages'],
        'updated_at' => (int)$runtime['updated_at'],
        'updated_by' => chat_sanitize_uid((string)$runtime['updated_by']),
    ];
}

function chat_user_role(string $uid): string
{
    $safeUid = chat_sanitize_uid($uid);
    if ($safeUid === '') {
        return 'user';
    }

    $membership = chat_admin_membership_map();
    if (isset($membership['owners'][$safeUid])) {
        return 'owner';
    }
    if (isset($membership['admins'][$safeUid])) {
        return 'admin';
    }

    return 'user';
}

function chat_is_owner_uid(string $uid): bool
{
    return chat_user_role($uid) === 'owner';
}

function chat_is_admin_uid(string $uid): bool
{
    $role = chat_user_role($uid);
    return $role === 'owner' || $role === 'admin';
}

function chat_staff_only_new_messages_enabled(): bool
{
    $runtime = chat_runtime_state_read();
    if ($runtime['staff_only_new_messages'] !== null) {
        return (bool)$runtime['staff_only_new_messages'];
    }

    return CHAT_STAFF_ONLY_NEW_MESSAGES;
}

function chat_user_can_send_new_messages(string $uid): bool
{
    return !chat_staff_only_new_messages_enabled() || chat_is_admin_uid($uid);
}

function chat_role_payload_for_uid(string $uid): array
{
    $role = chat_user_role($uid);
    return [
        'role' => $role,
        'is_owner' => $role === 'owner',
        'is_admin' => $role === 'owner' || $role === 'admin',
        'can_send_new_messages' => chat_user_can_send_new_messages($uid),
        'staff_only_new_messages' => chat_staff_only_new_messages_enabled(),
    ];
}

function chat_messages_read_all(): array
{
    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        $messages = chat_json_read(CHAT_MESSAGES_FILE, []);
        return chat_normalize_messages(is_array($messages) ? $messages : []);
    }

    $rows = $pdo->query('SELECT * FROM messages ORDER BY time ASC, rowid ASC')->fetchAll();
    $messages = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $item = chat_sqlite_row_to_message($row);
        if ($item !== null) {
            $messages[] = $item;
        }
    }

    return $messages;
}

function chat_messages_write_all(array $messages): void
{
    $messages = chat_normalize_messages($messages);

    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        if (count($messages) > 3000) {
            $messages = array_slice($messages, -2600);
        }
        chat_json_write(CHAT_MESSAGES_FILE, array_values($messages));
        return;
    }

    $pdo->beginTransaction();
    try {
        $pdo->exec('DELETE FROM messages');
        foreach ($messages as $message) {
            chat_sqlite_message_upsert($pdo, $message, 0);
        }
        chat_sqlite_trim_messages($pdo);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function chat_messages_append(array $message): void
{
    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        $messages = chat_messages_read_all();
        $messages[] = $message;
        chat_messages_write_all($messages);
        return;
    }

    chat_sqlite_message_upsert($pdo, $message, 0);
    chat_sqlite_trim_messages($pdo);
}

function chat_messages_upsert(array $message): void
{
    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        $messages = chat_messages_read_all();
        $id = (string)($message['id'] ?? '');
        $replaced = false;
        foreach ($messages as $index => $item) {
            if ((string)($item['id'] ?? '') === $id) {
                $messages[$index] = $message;
                $replaced = true;
                break;
            }
        }
        if (!$replaced) {
            $messages[] = $message;
        }
        chat_messages_write_all($messages);
        return;
    }

    chat_sqlite_message_upsert($pdo, $message, 0);
}

function chat_sqlite_should_retry_exception(Throwable $exception): bool
{
    $message = mb_strtolower((string)$exception->getMessage(), 'UTF-8');
    return str_contains($message, 'database is locked')
        || str_contains($message, 'database table is locked')
        || str_contains($message, 'busy');
}

function chat_sqlite_best_effort(callable $callback): bool
{
    $attempts = max(1, CHAT_SQLITE_TRANSACTION_RETRIES);

    for ($attempt = 1; $attempt <= $attempts; $attempt++) {
        try {
            $callback();
            return true;
        } catch (Throwable $exception) {
            $canRetry = $attempt < $attempts && chat_sqlite_should_retry_exception($exception);
            if (!$canRetry) {
                return false;
            }

            usleep($attempt * 150000);
        }
    }

    return false;
}

function chat_db_transaction(callable $callback): mixed
{
    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        return chat_with_lock($callback);
    }

    $outer = $pdo->inTransaction();
    $attempts = $outer ? 1 : max(1, CHAT_SQLITE_TRANSACTION_RETRIES);

    for ($attempt = 1; $attempt <= $attempts; $attempt++) {
        if (!$outer) {
            $pdo->beginTransaction();
        }

        try {
            $result = $callback($pdo);
            if (!$outer && $pdo->inTransaction()) {
                $pdo->commit();
            }
            return $result;
        } catch (Throwable $e) {
            if (!$outer && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $canRetry = !$outer
                && $attempt < $attempts
                && chat_sqlite_should_retry_exception($e);

            if (!$canRetry) {
                throw $e;
            }

            usleep($attempt * 150000);
        }
    }

    throw new RuntimeException('SQLite transaction retries exhausted.');
}

function chat_sqlite_profile_upsert(PDO $pdo, array $profile): void
{
    $uid = chat_sanitize_uid((string)($profile['uid'] ?? ''));
    if ($uid === '') {
        return;
    }

    $displayName = chat_sanitize_name((string)($profile['display_name'] ?? ''));
    if ($displayName === '') {
        $displayName = chat_default_name_from_uid($uid);
    }

    $avatarSeed = (string)($profile['avatar_seed'] ?? chat_avatar_seed_from_uid($uid));
    $avatarUrl = chat_sanitize_avatar_url((string)($profile['avatar_url'] ?? ''), $uid, $avatarSeed);
    $updatedAt = max(0, (int)($profile['updated_at'] ?? chat_now()));

    $stmt = $pdo->prepare(
        'INSERT INTO profiles (uid, display_name, avatar_seed, avatar_url, updated_at)
         VALUES (:uid, :display_name, :avatar_seed, :avatar_url, :updated_at)
         ON CONFLICT(uid) DO UPDATE SET
             display_name = excluded.display_name,
             avatar_seed = excluded.avatar_seed,
             avatar_url = excluded.avatar_url,
             updated_at = excluded.updated_at'
    );
    $stmt->bindValue(':uid', $uid, PDO::PARAM_STR);
    $stmt->bindValue(':display_name', $displayName, PDO::PARAM_STR);
    $stmt->bindValue(':avatar_seed', $avatarSeed, PDO::PARAM_STR);
    $stmt->bindValue(':avatar_url', $avatarUrl, PDO::PARAM_STR);
    $stmt->bindValue(':updated_at', $updatedAt, PDO::PARAM_INT);
    $stmt->execute();
}

function chat_sqlite_presence_upsert(PDO $pdo, string $uid, array $entry): void
{
    $safeUid = chat_sanitize_uid($uid);
    if ($safeUid === '') {
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO presence (
            uid, ts, display_name, avatar_seed, avatar_url,
            typing_until, typing_scope, typing_channel, typing_to_uid, last_message_at
        ) VALUES (
            :uid, :ts, :display_name, :avatar_seed, :avatar_url,
            :typing_until, :typing_scope, :typing_channel, :typing_to_uid, :last_message_at
        )
        ON CONFLICT(uid) DO UPDATE SET
            ts = excluded.ts,
            display_name = excluded.display_name,
            avatar_seed = excluded.avatar_seed,
            avatar_url = excluded.avatar_url,
            typing_until = excluded.typing_until,
            typing_scope = excluded.typing_scope,
            typing_channel = excluded.typing_channel,
            typing_to_uid = excluded.typing_to_uid,
            last_message_at = excluded.last_message_at'
    );
    $stmt->bindValue(':uid', $safeUid, PDO::PARAM_STR);
    $stmt->bindValue(':ts', max(0, (int)($entry['ts'] ?? 0)), PDO::PARAM_INT);
    $stmt->bindValue(':display_name', chat_sanitize_name((string)($entry['display_name'] ?? '')), PDO::PARAM_STR);
    $stmt->bindValue(':avatar_seed', (string)($entry['avatar_seed'] ?? chat_avatar_seed_from_uid($safeUid)), PDO::PARAM_STR);
    $stmt->bindValue(':avatar_url', chat_sanitize_avatar_url((string)($entry['avatar_url'] ?? ''), $safeUid, (string)($entry['avatar_seed'] ?? chat_avatar_seed_from_uid($safeUid))), PDO::PARAM_STR);
    $stmt->bindValue(':typing_until', max(0, (int)($entry['typing_until'] ?? 0)), PDO::PARAM_INT);
    $stmt->bindValue(':typing_scope', (string)($entry['typing_scope'] ?? ''), PDO::PARAM_STR);
    $stmt->bindValue(':typing_channel', chat_valid_channel((string)($entry['typing_channel'] ?? 'main')), PDO::PARAM_STR);
    $stmt->bindValue(':typing_to_uid', chat_sanitize_uid((string)($entry['typing_to_uid'] ?? '')), PDO::PARAM_STR);
    $stmt->bindValue(':last_message_at', max(0, (int)($entry['last_message_at'] ?? 0)), PDO::PARAM_INT);
    $stmt->execute();
}

function chat_sqlite_import_profiles_json_once(PDO $pdo): void
{
    if (chat_sqlite_meta_get($pdo, 'profiles_json_imported_at') !== null) {
        return;
    }

    $profiles = chat_json_read(CHAT_PROFILES_FILE, []);
    $profiles = is_array($profiles) ? $profiles : [];

    foreach ($profiles as $uid => $profile) {
        if (!is_array($profile)) {
            continue;
        }
        $profile['uid'] = (string)($profile['uid'] ?? $uid);
        chat_sqlite_profile_upsert($pdo, $profile);
    }

    chat_sqlite_meta_set($pdo, 'profiles_json_imported_at', (string)chat_now());
}

function chat_sqlite_import_presence_json_once(PDO $pdo): void
{
    if (chat_sqlite_meta_get($pdo, 'presence_json_imported_at') !== null) {
        return;
    }

    $presence = chat_json_read(CHAT_PRESENCE_FILE, []);
    $presence = chat_normalize_presence(is_array($presence) ? $presence : []);

    foreach ($presence as $uid => $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $entry['typing_channel'] = (string)($entry['typing_channel'] ?? 'main');
        chat_sqlite_presence_upsert($pdo, (string)$uid, $entry);
    }

    chat_sqlite_meta_set($pdo, 'presence_json_imported_at', (string)chat_now());
}

function chat_sqlite_import_shadowbans_json_once(PDO $pdo): void
{
    if (chat_sqlite_meta_get($pdo, 'shadowbans_json_imported_at') !== null) {
        return;
    }

    $shadowbans = chat_json_read(CHAT_SHADOWBANS_FILE, []);
    $shadowbans = is_array($shadowbans) ? $shadowbans : [];

    foreach ($shadowbans as $uid => $entry) {
        $safeUid = chat_sanitize_uid((string)$uid);
        if ($safeUid === '') {
            continue;
        }

        $shadowbannedAt = max(0, (int)($entry['shadowbanned_at'] ?? chat_now()));
        $updatedAt = max($shadowbannedAt, (int)($entry['updated_at'] ?? $shadowbannedAt));
        $updatedBy = chat_sanitize_uid((string)($entry['updated_by'] ?? ''));

        $stmt = $pdo->prepare(
            'INSERT INTO shadowbans (uid, shadowbanned_at, updated_at, updated_by)
             VALUES (:uid, :shadowbanned_at, :updated_at, :updated_by)
             ON CONFLICT(uid) DO UPDATE SET
                 shadowbanned_at = excluded.shadowbanned_at,
                 updated_at = excluded.updated_at,
                 updated_by = excluded.updated_by'
        );
        $stmt->bindValue(':uid', $safeUid, PDO::PARAM_STR);
        $stmt->bindValue(':shadowbanned_at', $shadowbannedAt, PDO::PARAM_INT);
        $stmt->bindValue(':updated_at', $updatedAt, PDO::PARAM_INT);
        $stmt->bindValue(':updated_by', $updatedBy, PDO::PARAM_STR);
        $stmt->execute();
    }

    chat_sqlite_meta_set($pdo, 'shadowbans_json_imported_at', (string)chat_now());
}

function chat_profiles_read_all(): array
{
    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        $profiles = chat_json_read(CHAT_PROFILES_FILE, []);
        return is_array($profiles) ? $profiles : [];
    }

    $rows = $pdo->query('SELECT uid, display_name, avatar_seed, avatar_url, updated_at FROM profiles ORDER BY uid ASC')->fetchAll();
    $profiles = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $uid = chat_sanitize_uid((string)($row['uid'] ?? ''));
        if ($uid === '') {
            continue;
        }
        $profiles[$uid] = [
            'uid' => $uid,
            'display_name' => chat_sanitize_name((string)($row['display_name'] ?? '')),
            'avatar_seed' => (string)($row['avatar_seed'] ?? chat_avatar_seed_from_uid($uid)),
            'avatar_url' => chat_sanitize_avatar_url((string)($row['avatar_url'] ?? ''), $uid, (string)($row['avatar_seed'] ?? chat_avatar_seed_from_uid($uid))),
            'updated_at' => max(0, (int)($row['updated_at'] ?? 0)),
        ];
    }

    return $profiles;
}

function chat_seen_read_map(string $uid): array
{
    $safeUid = chat_sanitize_uid($uid);
    if ($safeUid === '') {
        return [];
    }

    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        $seen = chat_json_read(CHAT_SEEN_FILE, []);
        $entries = is_array($seen) ? ($seen[$safeUid] ?? []) : [];
        if (!is_array($entries)) {
            return [];
        }

        $out = [];
        foreach ($entries as $conversationKey => $seenAt) {
            if (!is_string($conversationKey)) {
                continue;
            }
            $out[$conversationKey] = max(0, (int)$seenAt);
        }
        return $out;
    }

    $stmt = $pdo->prepare(
        'SELECT conversation_key, seen_at
         FROM seen_state
         WHERE uid = :uid
         ORDER BY conversation_key ASC'
    );
    $stmt->bindValue(':uid', $safeUid, PDO::PARAM_STR);
    $stmt->execute();

    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        if (!is_array($row)) {
            continue;
        }

        $conversationKey = (string)($row['conversation_key'] ?? '');
        if ($conversationKey === '') {
            continue;
        }
        $out[$conversationKey] = max(0, (int)($row['seen_at'] ?? 0));
    }

    return $out;
}

function chat_seen_mark_channel(string $uid, string $channel, int $seenAt = 0): int
{
    $safeUid = chat_sanitize_uid($uid);
    if ($safeUid === '') {
        return 0;
    }

    $safeChannel = chat_valid_channel($channel);
    $safeSeenAt = max(0, $seenAt > 0 ? $seenAt : chat_now());
    $conversationKey = chat_conversation_key('channel', $safeChannel);

    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        try {
            return chat_with_lock(static function () use ($safeUid, $conversationKey, $safeSeenAt): int {
                $seen = chat_json_read(CHAT_SEEN_FILE, []);
                $seen = is_array($seen) ? $seen : [];
                $entries = $seen[$safeUid] ?? [];
                $entries = is_array($entries) ? $entries : [];
                $previousSeenAt = max(0, (int)($entries[$conversationKey] ?? 0));
                if ($safeSeenAt <= $previousSeenAt) {
                    return $previousSeenAt;
                }
                $entries[$conversationKey] = max($safeSeenAt, max(0, (int)($entries[$conversationKey] ?? 0)));
                $seen[$safeUid] = $entries;
                chat_json_write(CHAT_SEEN_FILE, $seen);
                return $entries[$conversationKey];
            });
        } catch (Throwable) {
            return 0;
        }
    }

    try {
        $existingStmt = $pdo->prepare(
            'SELECT seen_at
             FROM seen_state
             WHERE uid = :uid AND conversation_key = :conversation_key
             LIMIT 1'
        );
        $existingStmt->bindValue(':uid', $safeUid, PDO::PARAM_STR);
        $existingStmt->bindValue(':conversation_key', $conversationKey, PDO::PARAM_STR);
        $existingStmt->execute();
        $previousSeenAt = max(0, (int)$existingStmt->fetchColumn());
        if ($safeSeenAt <= $previousSeenAt) {
            return $previousSeenAt;
        }

        $stored = chat_sqlite_best_effort(static function () use ($pdo, $safeUid, $conversationKey, $safeSeenAt): void {
            $stmt = $pdo->prepare(
                'INSERT INTO seen_state (uid, conversation_key, seen_at, updated_at)
                 VALUES (:uid, :conversation_key, :seen_at, :updated_at)
                 ON CONFLICT(uid, conversation_key) DO UPDATE SET
                     seen_at = CASE WHEN excluded.seen_at > seen_state.seen_at THEN excluded.seen_at ELSE seen_state.seen_at END,
                     updated_at = CASE WHEN excluded.updated_at > seen_state.updated_at THEN excluded.updated_at ELSE seen_state.updated_at END'
            );
            $stmt->bindValue(':uid', $safeUid, PDO::PARAM_STR);
            $stmt->bindValue(':conversation_key', $conversationKey, PDO::PARAM_STR);
            $stmt->bindValue(':seen_at', $safeSeenAt, PDO::PARAM_INT);
            $stmt->bindValue(':updated_at', chat_now(), PDO::PARAM_INT);
            $stmt->execute();
        });

        return $stored ? $safeSeenAt : $previousSeenAt;
    } catch (Throwable) {
        return 0;
    }
}

function chat_profile_get_stored(string $uid): ?array
{
    $safeUid = chat_sanitize_uid($uid);
    if ($safeUid === '') {
        return null;
    }

    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        $profiles = chat_profiles_read_all();
        $profile = $profiles[$safeUid] ?? null;
        return is_array($profile) ? $profile : null;
    }

    $stmt = $pdo->prepare(
        'SELECT uid, display_name, avatar_seed, avatar_url, updated_at
         FROM profiles
         WHERE uid = :uid
         LIMIT 1'
    );
    $stmt->bindValue(':uid', $safeUid, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch();
    if (!is_array($row)) {
        return null;
    }

    return [
        'uid' => $safeUid,
        'display_name' => chat_sanitize_name((string)($row['display_name'] ?? '')),
        'avatar_seed' => (string)($row['avatar_seed'] ?? chat_avatar_seed_from_uid($safeUid)),
        'avatar_url' => chat_sanitize_avatar_url((string)($row['avatar_url'] ?? ''), $safeUid, (string)($row['avatar_seed'] ?? chat_avatar_seed_from_uid($safeUid))),
        'updated_at' => max(0, (int)($row['updated_at'] ?? 0)),
    ];
}

function chat_profiles_write_all(array $profiles): void
{
    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        chat_json_write(CHAT_PROFILES_FILE, $profiles);
        return;
    }

    foreach ($profiles as $uid => $profile) {
        if (!is_array($profile)) {
            continue;
        }
        $profile['uid'] = (string)($profile['uid'] ?? $uid);
        chat_sqlite_profile_upsert($pdo, $profile);
    }
}

function chat_shadowbans_read_all(): array
{
    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        $shadowbans = chat_json_read(CHAT_SHADOWBANS_FILE, []);
        $shadowbans = is_array($shadowbans) ? $shadowbans : [];
        foreach (array_keys($shadowbans) as $uid) {
            $safeUid = chat_sanitize_uid((string)$uid);
            if ($safeUid === '' || chat_is_admin_uid($safeUid)) {
                unset($shadowbans[$uid]);
            }
        }
        return $shadowbans;
    }

    $rows = $pdo->query('SELECT uid, shadowbanned_at, updated_at, updated_by FROM shadowbans ORDER BY uid ASC')->fetchAll();
    $shadowbans = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $uid = chat_sanitize_uid((string)($row['uid'] ?? ''));
        if ($uid === '' || chat_is_admin_uid($uid)) {
            continue;
        }

        $shadowbans[$uid] = [
            'uid' => $uid,
            'shadowbanned_at' => max(0, (int)($row['shadowbanned_at'] ?? 0)),
            'updated_at' => max(0, (int)($row['updated_at'] ?? 0)),
            'updated_by' => chat_sanitize_uid((string)($row['updated_by'] ?? '')),
        ];
    }

    return $shadowbans;
}

function chat_shadowban_is_active(string $uid): bool
{
    $safeUid = chat_sanitize_uid($uid);
    if ($safeUid === '' || chat_is_admin_uid($safeUid)) {
        return false;
    }

    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        $shadowbans = chat_shadowbans_read_all();
        return isset($shadowbans[$safeUid]);
    }

    $stmt = $pdo->prepare('SELECT 1 FROM shadowbans WHERE uid = :uid LIMIT 1');
    $stmt->bindValue(':uid', $safeUid, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchColumn() !== false;
}

function chat_shadowban_set(string $uid, bool $shadowbanned, string $actorUid = ''): array
{
    $safeUid = chat_sanitize_uid($uid);
    $safeActorUid = chat_sanitize_uid($actorUid);
    if ($safeUid === '') {
        throw new RuntimeException('Invalid user id.');
    }
    if ($shadowbanned && chat_is_admin_uid($safeUid)) {
        throw new RuntimeException('Owners and admins can not be shadowbanned.', 422);
    }

    $now = chat_now();
    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        $shadowbans = chat_shadowbans_read_all();
        if ($shadowbanned) {
            $shadowbans[$safeUid] = [
                'uid' => $safeUid,
                'shadowbanned_at' => $now,
                'updated_at' => $now,
                'updated_by' => $safeActorUid,
            ];
        } else {
            unset($shadowbans[$safeUid]);
        }
        chat_json_write(CHAT_SHADOWBANS_FILE, $shadowbans);
    } else {
        if ($shadowbanned) {
            $stmt = $pdo->prepare(
                'INSERT INTO shadowbans (uid, shadowbanned_at, updated_at, updated_by)
                 VALUES (:uid, :shadowbanned_at, :updated_at, :updated_by)
                 ON CONFLICT(uid) DO UPDATE SET
                     shadowbanned_at = excluded.shadowbanned_at,
                     updated_at = excluded.updated_at,
                     updated_by = excluded.updated_by'
            );
            $stmt->bindValue(':uid', $safeUid, PDO::PARAM_STR);
            $stmt->bindValue(':shadowbanned_at', $now, PDO::PARAM_INT);
            $stmt->bindValue(':updated_at', $now, PDO::PARAM_INT);
            $stmt->bindValue(':updated_by', $safeActorUid, PDO::PARAM_STR);
            $stmt->execute();
        } else {
            $stmt = $pdo->prepare('DELETE FROM shadowbans WHERE uid = :uid');
            $stmt->bindValue(':uid', $safeUid, PDO::PARAM_STR);
            $stmt->execute();
        }
    }

    return [
        'uid' => $safeUid,
        'shadowbanned' => $shadowbanned,
        'updated_at' => $now,
        'updated_by' => $safeActorUid,
    ];
}

function chat_presence_read_all(): array
{
    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        $presence = chat_json_read(CHAT_PRESENCE_FILE, []);
        $presence = chat_normalize_presence(is_array($presence) ? $presence : []);
        $minTs = chat_now() - CHAT_ONLINE_WINDOW;
        return array_filter(
            $presence,
            static fn(array $entry): bool => max(0, (int)($entry['ts'] ?? 0)) >= $minTs
        );
    }

    $stmt = $pdo->prepare(
        'SELECT uid, ts, display_name, avatar_seed, avatar_url, typing_until, typing_scope, typing_channel, typing_to_uid, last_message_at
         FROM presence
         WHERE ts >= :min_ts
         ORDER BY display_name ASC, uid ASC'
    );
    $stmt->bindValue(':min_ts', chat_now() - CHAT_ONLINE_WINDOW, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $presence = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $uid = chat_sanitize_uid((string)($row['uid'] ?? ''));
        if ($uid === '') {
            continue;
        }
        $presence[$uid] = [
            'ts' => max(0, (int)($row['ts'] ?? 0)),
            'display_name' => chat_sanitize_name((string)($row['display_name'] ?? '')),
            'avatar_seed' => (string)($row['avatar_seed'] ?? chat_avatar_seed_from_uid($uid)),
            'avatar_url' => chat_sanitize_avatar_url((string)($row['avatar_url'] ?? ''), $uid, (string)($row['avatar_seed'] ?? chat_avatar_seed_from_uid($uid))),
            'typing_until' => max(0, (int)($row['typing_until'] ?? 0)),
            'typing_scope' => (string)($row['typing_scope'] ?? ''),
            'typing_channel' => chat_valid_channel((string)($row['typing_channel'] ?? 'main')),
            'typing_to_uid' => chat_sanitize_uid((string)($row['typing_to_uid'] ?? '')),
            'last_message_at' => max(0, (int)($row['last_message_at'] ?? 0)),
        ];
    }

    return chat_normalize_presence($presence);
}

function chat_presence_get_stored(string $uid): ?array
{
    $safeUid = chat_sanitize_uid($uid);
    if ($safeUid === '') {
        return null;
    }

    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        $presence = chat_presence_read_all();
        $entry = $presence[$safeUid] ?? null;
        return is_array($entry) ? $entry : null;
    }

    $stmt = $pdo->prepare(
        'SELECT uid, ts, display_name, avatar_seed, avatar_url, typing_until, typing_scope, typing_channel, typing_to_uid, last_message_at
         FROM presence
         WHERE uid = :uid
         LIMIT 1'
    );
    $stmt->bindValue(':uid', $safeUid, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch();
    if (!is_array($row)) {
        return null;
    }

    return chat_normalize_presence([
        $safeUid => [
            'ts' => max(0, (int)($row['ts'] ?? 0)),
            'display_name' => chat_sanitize_name((string)($row['display_name'] ?? '')),
            'avatar_seed' => (string)($row['avatar_seed'] ?? chat_avatar_seed_from_uid($safeUid)),
            'avatar_url' => chat_sanitize_avatar_url((string)($row['avatar_url'] ?? ''), $safeUid, (string)($row['avatar_seed'] ?? chat_avatar_seed_from_uid($safeUid))),
            'typing_until' => max(0, (int)($row['typing_until'] ?? 0)),
            'typing_scope' => (string)($row['typing_scope'] ?? ''),
            'typing_channel' => (string)($row['typing_channel'] ?? ''),
            'typing_to_uid' => chat_sanitize_uid((string)($row['typing_to_uid'] ?? '')),
            'last_message_at' => max(0, (int)($row['last_message_at'] ?? 0)),
        ],
    ])[$safeUid] ?? null;
}

function chat_presence_count_active(): int
{
    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        return count(chat_presence_read_all());
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM presence WHERE ts >= :min_ts');
    $stmt->bindValue(':min_ts', chat_now() - CHAT_ONLINE_WINDOW, PDO::PARAM_INT);
    $stmt->execute();
    return max(0, (int)$stmt->fetchColumn());
}

function chat_message_count_total(): int
{
    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        return count(chat_messages_read_all());
    }

    return max(0, (int)$pdo->query('SELECT COUNT(*) FROM messages')->fetchColumn());
}

function chat_message_last_activity_at(): int
{
    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        $messages = chat_messages_read_all();
        $last = end($messages);
        return max(0, (int)($last['time'] ?? 0));
    }

    $value = $pdo->query('SELECT MAX(time) FROM messages')->fetchColumn();
    return max(0, (int)$value);
}

function chat_directory_size(string $dir, int $maxFiles = 10000): int
{
    if (!is_dir($dir)) {
        return 0;
    }

    $size = 0;
    $count = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || !$file->isFile()) {
            continue;
        }
        $size += max(0, (int)$file->getSize());
        $count += 1;
        if ($count >= $maxFiles) {
            break;
        }
    }

    return $size;
}

function chat_owner_users_snapshot(): array
{
    $profiles = chat_profiles_read_all();
    $presence = chat_presence_read_all();
    $shadowbans = chat_shadowbans_read_all();
    $snapshot = [];

    foreach ($profiles as $uid => $profile) {
        $safeUid = chat_sanitize_uid((string)$uid);
        if ($safeUid === '') {
            continue;
        }
        $snapshot[$safeUid] = [
            'uid' => $safeUid,
            'display_name' => chat_sanitize_name((string)($profile['display_name'] ?? chat_default_name_from_uid($safeUid))),
            'avatar_seed' => (string)($profile['avatar_seed'] ?? chat_avatar_seed_from_uid($safeUid)),
            'avatar_url' => chat_sanitize_avatar_url((string)($profile['avatar_url'] ?? ''), $safeUid, (string)($profile['avatar_seed'] ?? chat_avatar_seed_from_uid($safeUid))),
            'avatar_updated_at' => max(0, (int)($profile['updated_at'] ?? 0)),
            'online' => isset($presence[$safeUid]),
            'last_seen_at' => max(0, (int)($presence[$safeUid]['ts'] ?? 0)),
            'last_message_at' => max(0, (int)($presence[$safeUid]['last_message_at'] ?? 0)),
            'role' => chat_user_role($safeUid),
            'admin_source' => chat_admin_source_for_uid($safeUid),
            'is_shadowbanned' => isset($shadowbans[$safeUid]),
        ];
    }

    foreach ($presence as $uid => $entry) {
        $safeUid = chat_sanitize_uid((string)$uid);
        if ($safeUid === '') {
            continue;
        }
        if (!isset($snapshot[$safeUid])) {
            $snapshot[$safeUid] = [
                'uid' => $safeUid,
                'display_name' => chat_sanitize_name((string)($entry['display_name'] ?? chat_default_name_from_uid($safeUid))),
                'avatar_seed' => (string)($entry['avatar_seed'] ?? chat_avatar_seed_from_uid($safeUid)),
                'avatar_url' => chat_sanitize_avatar_url((string)($entry['avatar_url'] ?? ''), $safeUid, (string)($entry['avatar_seed'] ?? chat_avatar_seed_from_uid($safeUid))),
                'avatar_updated_at' => 0,
                'online' => true,
                'last_seen_at' => max(0, (int)($entry['ts'] ?? 0)),
                'last_message_at' => max(0, (int)($entry['last_message_at'] ?? 0)),
                'role' => chat_user_role($safeUid),
                'admin_source' => chat_admin_source_for_uid($safeUid),
                'is_shadowbanned' => isset($shadowbans[$safeUid]),
            ];
            continue;
        }

        $snapshot[$safeUid]['online'] = true;
        $snapshot[$safeUid]['last_seen_at'] = max(0, (int)($entry['ts'] ?? 0));
        $snapshot[$safeUid]['last_message_at'] = max(
            (int)$snapshot[$safeUid]['last_message_at'],
            max(0, (int)($entry['last_message_at'] ?? 0))
        );
    }

    $users = array_values($snapshot);
    usort($users, static function (array $a, array $b): int {
        if ((bool)$a['online'] !== (bool)$b['online']) {
            return !empty($a['online']) ? -1 : 1;
        }
        if ((int)$a['last_message_at'] !== (int)$b['last_message_at']) {
            return (int)$b['last_message_at'] <=> (int)$a['last_message_at'];
        }
        return strcmp((string)$a['uid'], (string)$b['uid']);
    });

    return $users;
}

function chat_owner_panel_payload(): array
{
    $users = chat_owner_users_snapshot();
    $admins = array_values(array_filter($users, static fn(array $user): bool => in_array((string)($user['role'] ?? 'user'), ['admin', 'owner'], true)));
    $shadowbanned = array_values(array_filter($users, static fn(array $user): bool => !empty($user['is_shadowbanned'])));
    $runtime = chat_runtime_state_read();
    $messagesBackend = chat_messages_backend_name();
    $jsonBytes = file_exists(CHAT_MESSAGES_FILE) ? max(0, (int)filesize(CHAT_MESSAGES_FILE)) : 0;
    $sqliteBytes = file_exists(CHAT_SQLITE_FILE) ? max(0, (int)filesize(CHAT_SQLITE_FILE)) : 0;
    $dataBytes = chat_directory_size(CHAT_DATA_DIR);
    $uploadsBytes = chat_directory_size(CHAT_UPLOAD_DIR);
    $cpuLoad = function_exists('sys_getloadavg') ? sys_getloadavg() : false;

    return [
        'settings' => [
            'staff_only_new_messages' => chat_staff_only_new_messages_enabled(),
            'runtime_updated_at' => (int)$runtime['updated_at'],
            'runtime_updated_by' => (string)$runtime['updated_by'],
        ],
        'live' => chat_live_owner_payload(),
        'admins' => $admins,
        'shadowbanned_users' => $shadowbanned,
        'users' => $users,
        'stats' => [
            'messages_total' => chat_message_count_total(),
            'profiles_total' => count(chat_profiles_read_all()),
            'online_now' => chat_presence_count_active(),
            'shadowban_total' => count($shadowbanned),
            'last_message_at' => chat_message_last_activity_at(),
            'storage_backend' => $messagesBackend,
            'sqlite_size_bytes' => $sqliteBytes,
            'json_messages_size_bytes' => $jsonBytes,
            'data_size_bytes' => $dataBytes,
            'uploads_size_bytes' => $uploadsBytes,
            'disk_free_bytes' => is_dir(CHAT_BASE_DIR) ? max(0, (int)@disk_free_space(CHAT_BASE_DIR)) : 0,
            'disk_total_bytes' => is_dir(CHAT_BASE_DIR) ? max(0, (int)@disk_total_space(CHAT_BASE_DIR)) : 0,
            'php_memory_usage_bytes' => max(0, (int)memory_get_usage(true)),
            'php_memory_peak_bytes' => max(0, (int)memory_get_peak_usage(true)),
            'php_memory_limit' => (string)ini_get('memory_limit'),
            'cpu_load_1m' => is_array($cpuLoad) ? (float)($cpuLoad[0] ?? 0.0) : null,
        ],
        'audit' => chat_owner_audit_recent(),
    ];
}

function chat_collect_typing_users_fast(string $uid, string $scope, string $channel, ?string $dmUid): array
{
    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        return chat_collect_typing_users(chat_presence_read_all(), $uid, $scope, $channel, $dmUid);
    }

    $safeScope = $scope === 'dm' ? 'dm' : 'channel';
    $now = chat_now();

    if ($safeScope === 'dm') {
        $safeDmUid = chat_sanitize_uid((string)$dmUid);
        if ($safeDmUid === '' || $safeDmUid === $uid) {
            return [];
        }

        $stmt = $pdo->prepare(
            'SELECT uid, display_name
             FROM presence
             WHERE typing_until >= :now
               AND typing_scope = :scope
               AND uid = :peer_uid
               AND typing_to_uid = :uid
             ORDER BY display_name ASC, uid ASC'
        );
        $stmt->bindValue(':now', $now, PDO::PARAM_INT);
        $stmt->bindValue(':scope', 'dm', PDO::PARAM_STR);
        $stmt->bindValue(':peer_uid', $safeDmUid, PDO::PARAM_STR);
        $stmt->bindValue(':uid', $uid, PDO::PARAM_STR);
        $stmt->execute();
    } else {
        $safeChannel = chat_valid_channel($channel);
        $stmt = $pdo->prepare(
            'SELECT uid, display_name
             FROM presence
             WHERE typing_until >= :now
               AND typing_scope = :scope
               AND typing_channel = :channel
               AND uid != :uid
             ORDER BY display_name ASC, uid ASC'
        );
        $stmt->bindValue(':now', $now, PDO::PARAM_INT);
        $stmt->bindValue(':scope', 'channel', PDO::PARAM_STR);
        $stmt->bindValue(':channel', $safeChannel, PDO::PARAM_STR);
        $stmt->bindValue(':uid', $uid, PDO::PARAM_STR);
        $stmt->execute();
    }

    $typingUsers = [];
    foreach ($stmt->fetchAll() as $row) {
        if (!is_array($row)) {
            continue;
        }

        $entryUid = chat_sanitize_uid((string)($row['uid'] ?? ''));
        if ($entryUid === '' || $entryUid === $uid) {
            continue;
        }

        $typingUsers[] = [
            'uid' => $entryUid,
            'display_name' => (string)($row['display_name'] ?? chat_default_name_from_uid($entryUid)),
        ];
    }

    return $typingUsers;
}

function chat_presence_write_all(array $presence): void
{
    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        chat_json_write(CHAT_PRESENCE_FILE, $presence);
        return;
    }

    $presence = chat_normalize_presence($presence);
    foreach ($presence as $uid => $entry) {
        if (!is_array($entry)) {
            continue;
        }
        chat_sqlite_presence_upsert($pdo, (string)$uid, $entry);
    }
}

function chat_profile_ensure_stored(string $uid, string $displayName = ''): array
{
    $safeUid = chat_sanitize_uid($uid);
    if ($safeUid === '') {
        throw new RuntimeException('Invalid user id.');
    }

    $profile = chat_profile_get_stored($safeUid);
    if (is_array($profile)) {
        return $profile;
    }

    $seed = chat_avatar_seed_from_uid($safeUid);
    $profile = [
        'uid' => $safeUid,
        'display_name' => $displayName !== '' ? chat_sanitize_name($displayName) : chat_default_name_from_uid($safeUid),
        'avatar_seed' => $seed,
        'avatar_url' => chat_default_avatar_url($safeUid, $seed),
        'updated_at' => chat_now(),
    ];

    $pdo = chat_sqlite_connection();
    if ($pdo instanceof PDO) {
        chat_sqlite_best_effort(static function () use ($pdo, $profile): void {
            chat_sqlite_profile_upsert($pdo, $profile);
        });
    } else {
        $profiles = chat_profiles_read_all();
        $profiles[$safeUid] = $profile;
        chat_profiles_write_all($profiles);
    }
    return $profile;
}

function chat_profile_update_stored(string $uid, array $changes): array
{
    $safeUid = chat_sanitize_uid($uid);
    if ($safeUid === '') {
        throw new RuntimeException('Invalid user id.');
    }

    $pdo = chat_sqlite_connection();
    if ($pdo instanceof PDO) {
        $profile = chat_profile_get_stored($safeUid);
        if (!is_array($profile)) {
            $profile = chat_profile_ensure_stored($safeUid);
        }
    } else {
        $profiles = chat_profiles_read_all();
        $profile = chat_ensure_profile($profiles, $safeUid);
    }

    if (array_key_exists('display_name', $changes)) {
        $nextName = chat_sanitize_name((string)($changes['display_name'] ?? ''));
        if ($nextName !== '') {
            $profile['display_name'] = $nextName;
        }
    }
    if (array_key_exists('avatar_seed', $changes)) {
        $profile['avatar_seed'] = (string)($changes['avatar_seed'] ?? chat_avatar_seed_from_uid($safeUid));
    }
    if (array_key_exists('avatar_url', $changes)) {
        $profile['avatar_url'] = chat_sanitize_avatar_url((string)($changes['avatar_url'] ?? ''), $safeUid, (string)($profile['avatar_seed'] ?? chat_avatar_seed_from_uid($safeUid)));
    }

    $profile['updated_at'] = chat_now();
    if ($pdo instanceof PDO) {
        chat_sqlite_best_effort(static function () use ($pdo, $profile): void {
            chat_sqlite_profile_upsert($pdo, $profile);
        });
    } else {
        $profiles[$safeUid] = $profile;
        chat_profiles_write_all($profiles);
    }
    return $profile;
}

function chat_collect_users_from_storage(string $selfUid): array
{
    return chat_collect_users([], chat_presence_read_all(), chat_profiles_read_all(), $selfUid);
}

function chat_presence_touch_stored(
    string $uid,
    string $displayName,
    string $avatarSeed,
    string $avatarUrl,
    bool $typing,
    string $scope,
    string $channel,
    string $toUid
): array {
    $safeUid = chat_sanitize_uid($uid);
    if ($safeUid === '') {
        throw new RuntimeException('Invalid user id.');
    }

    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        try {
            return chat_with_lock(static function () use ($safeUid, $displayName, $avatarSeed, $avatarUrl, $typing, $scope, $channel, $toUid): array {
                $presence = chat_presence_read_all();
                $existingEntry = is_array($presence[$safeUid] ?? null) ? $presence[$safeUid] : [];
                $now = chat_now();
                $safeAvatarUrl = chat_sanitize_avatar_url($avatarUrl, $safeUid, $avatarSeed);

                if (!$typing && $existingEntry !== []) {
                    $previousTs = max(0, (int)($existingEntry['ts'] ?? 0));
                    $profileUnchanged = chat_sanitize_name((string)($existingEntry['display_name'] ?? '')) === chat_sanitize_name($displayName)
                        && (string)($existingEntry['avatar_seed'] ?? '') === $avatarSeed
                        && (string)($existingEntry['avatar_url'] ?? '') === $safeAvatarUrl;

                    if (($now - $previousTs) < CHAT_PRESENCE_WRITE_INTERVAL && $profileUnchanged) {
                        return $existingEntry;
                    }
                }

                chat_touch_presence($presence, $safeUid, $displayName, $avatarSeed, $typing, $scope, $channel, $toUid);
                $presence[$safeUid]['avatar_url'] = $safeAvatarUrl;
                chat_presence_write_all($presence);
                return $presence[$safeUid];
            });
        } catch (Throwable) {
            return [
                'ts' => chat_now(),
                'display_name' => chat_sanitize_name($displayName),
                'avatar_seed' => $avatarSeed,
                'avatar_url' => chat_sanitize_avatar_url($avatarUrl, $safeUid, $avatarSeed),
                'typing_until' => $typing ? (chat_now() + CHAT_TYPING_TTL) : 0,
                'typing_scope' => $typing ? ($scope === 'dm' ? 'dm' : 'channel') : '',
                'typing_channel' => $typing && $scope !== 'dm' ? chat_valid_channel($channel) : '',
                'typing_to_uid' => $typing && $scope === 'dm' ? chat_sanitize_uid($toUid) : '',
                'last_message_at' => 0,
            ];
        }
    }

    $now = chat_now();
    $entry = [
        'ts' => $now,
        'display_name' => $displayName,
        'avatar_seed' => $avatarSeed,
        'avatar_url' => chat_sanitize_avatar_url($avatarUrl, $safeUid, $avatarSeed),
        'typing_until' => $typing ? ($now + CHAT_TYPING_TTL) : 0,
        'typing_scope' => $typing ? ($scope === 'dm' ? 'dm' : 'channel') : '',
        'typing_channel' => $typing && $scope !== 'dm' ? chat_valid_channel($channel) : '',
        'typing_to_uid' => $typing && $scope === 'dm' ? chat_sanitize_uid($toUid) : '',
        'last_message_at' => 0,
    ];

    $existingEntry = chat_presence_get_stored($safeUid);
    $existingEntry = is_array($existingEntry) ? $existingEntry : [];
    if (!$typing && $existingEntry !== []) {
        $previousTs = max(0, (int)($existingEntry['ts'] ?? 0));
        $profileUnchanged = chat_sanitize_name((string)($existingEntry['display_name'] ?? '')) === chat_sanitize_name($displayName)
            && (string)($existingEntry['avatar_seed'] ?? '') === $avatarSeed
            && (string)($existingEntry['avatar_url'] ?? '') === chat_sanitize_avatar_url($avatarUrl, $safeUid, $avatarSeed);

        if (($now - $previousTs) < CHAT_PRESENCE_WRITE_INTERVAL && $profileUnchanged) {
            return $existingEntry;
        }
    }

    if (!$typing) {
        $entry['typing_until'] = max(0, (int)($existingEntry['typing_until'] ?? 0));
        $entry['typing_scope'] = (int)($entry['typing_until'] ?? 0) > $now ? (string)($existingEntry['typing_scope'] ?? '') : '';
        $entry['typing_channel'] = (int)($entry['typing_until'] ?? 0) > $now ? (string)($existingEntry['typing_channel'] ?? '') : '';
        $entry['typing_to_uid'] = (int)($entry['typing_until'] ?? 0) > $now ? (string)($existingEntry['typing_to_uid'] ?? '') : '';
    }
    $entry['last_message_at'] = max(0, (int)($existingEntry['last_message_at'] ?? 0));

    $stored = chat_sqlite_best_effort(static function () use ($pdo, $safeUid, $entry): void {
        chat_sqlite_presence_upsert($pdo, $safeUid, $entry);
    });

    if (!$stored) {
        return $existingEntry !== [] ? $existingEntry : [
            'ts' => $entry['ts'],
            'display_name' => chat_sanitize_name($entry['display_name']),
            'avatar_seed' => $entry['avatar_seed'],
            'avatar_url' => $entry['avatar_url'],
            'typing_until' => $entry['typing_until'],
            'typing_scope' => $entry['typing_scope'],
            'typing_channel' => $entry['typing_channel'],
            'typing_to_uid' => $entry['typing_to_uid'],
            'last_message_at' => $entry['last_message_at'],
        ];
    }

    return [
        'ts' => $entry['ts'],
        'display_name' => chat_sanitize_name($entry['display_name']),
        'avatar_seed' => $entry['avatar_seed'],
        'avatar_url' => $entry['avatar_url'],
        'typing_until' => $entry['typing_until'],
        'typing_scope' => $entry['typing_scope'],
        'typing_channel' => $entry['typing_channel'],
        'typing_to_uid' => $entry['typing_to_uid'],
        'last_message_at' => $entry['last_message_at'],
    ];
}

function chat_presence_mark_message_sent_stored(string $uid, int $time): void
{
    $safeUid = chat_sanitize_uid($uid);
    if ($safeUid === '') {
        return;
    }

    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        $presence = chat_presence_read_all();
        chat_mark_message_sent($presence, $safeUid, $time);
        chat_presence_write_all($presence);
        return;
    }

    chat_sqlite_best_effort(static function () use ($pdo, $safeUid, $time): void {
        $stmt = $pdo->prepare(
            'UPDATE presence
             SET last_message_at = :last_message_at,
                 typing_until = 0,
                 typing_scope = "",
                 typing_channel = "",
                 typing_to_uid = ""
             WHERE uid = :uid'
        );
        $stmt->bindValue(':last_message_at', max(1, $time), PDO::PARAM_INT);
        $stmt->bindValue(':uid', $safeUid, PDO::PARAM_STR);
        $stmt->execute();
    });
}

function chat_compute_unread_hints_fast(string $uid, array $seenMap): array
{
    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        return chat_compute_unread_hints(chat_messages_read_all(), $uid, $seenMap);
    }

    $seenMap = chat_seen_read_map($uid);
    $shadowbans = chat_shadowbans_read_all();
    $channels = [];
    foreach (array_keys(CHAT_CHANNELS) as $channel) {
        $channels[$channel] = 0;
    }

    $channelSeen = [];
    foreach (array_keys(CHAT_CHANNELS) as $channel) {
        $channelSeen[$channel] = max(0, (int)($seenMap[chat_conversation_key('channel', $channel)] ?? 0));
    }
    $minChannelSeen = $channelSeen !== [] ? min($channelSeen) : 0;

    $stmt = $pdo->prepare(
        'SELECT channel, time, from_uid
         FROM messages
         WHERE scope = :scope
           AND from_uid != :uid
           AND time > :min_seen
         ORDER BY time ASC'
    );
    $stmt->bindValue(':scope', 'channel', PDO::PARAM_STR);
    $stmt->bindValue(':uid', $uid, PDO::PARAM_STR);
    $stmt->bindValue(':min_seen', $minChannelSeen, PDO::PARAM_INT);
    $stmt->execute();
    foreach ($stmt->fetchAll() as $row) {
        if (!is_array($row)) {
            continue;
        }
        $fromUid = chat_sanitize_uid((string)($row['from_uid'] ?? ''));
        if ($fromUid !== '' && isset($shadowbans[$fromUid])) {
            continue;
        }
        $channel = chat_valid_channel((string)($row['channel'] ?? 'main'));
        $time = max(0, (int)($row['time'] ?? 0));
        if ($time > ($channelSeen[$channel] ?? 0)) {
            $channels[$channel]++;
        }
    }

    return [
        'channels' => $channels,
        'dms' => [],
    ];
}

function chat_messages_list_visible(string $uid, string $scope, string $channel = 'main', ?string $dmUid = null, int $limit = CHAT_HISTORY_LIMIT): array
{
    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        return chat_filter_visible_messages(chat_messages_read_all(), $uid, $channel, $dmUid);
    }

    $safeScope = $scope === 'dm' ? 'dm' : 'channel';
    $safeLimit = max(1, min(500, $limit));

    if ($safeScope === 'dm') {
        $peerUid = chat_sanitize_uid((string)$dmUid);
        if ($peerUid === '' || $peerUid === $uid) {
            return [];
        }
        $pairKey = chat_dm_pair_key($uid, $peerUid);
        if ($pairKey === '') {
            return [];
        }

        $stmt = $pdo->prepare(
            'SELECT *
             FROM messages
             WHERE scope = :scope
               AND dm_pair_key = :pair_key
             ORDER BY time DESC, rowid DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':scope', 'dm', PDO::PARAM_STR);
        $stmt->bindValue(':pair_key', $pairKey, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $safeLimit, PDO::PARAM_INT);
    } else {
        $safeChannel = chat_valid_channel($channel);
        $stmt = $pdo->prepare(
            'SELECT *
             FROM messages
             WHERE scope = :scope
               AND channel = :channel
             ORDER BY time DESC, rowid DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':scope', 'channel', PDO::PARAM_STR);
        $stmt->bindValue(':channel', $safeChannel, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $safeLimit, PDO::PARAM_INT);
    }

    $stmt->execute();
    $rows = array_reverse($stmt->fetchAll());
    $messages = [];
    $shadowbans = chat_shadowbans_read_all();
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $message = chat_sqlite_row_to_message($row);
        $fromUid = chat_sanitize_uid((string)($message['from_uid'] ?? ''));
        if ($message !== null && ($fromUid === '' || $fromUid === $uid || !isset($shadowbans[$fromUid]))) {
            $messages[] = $message;
        }
    }

    return $messages;
}

function chat_message_find(string $messageId): ?array
{
    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        $messages = chat_messages_read_all();
        $index = -1;
        foreach ($messages as $i => $message) {
            if ((string)($message['id'] ?? '') === $messageId) {
                $index = $i;
                break;
            }
        }
        return $index >= 0 ? $messages[$index] : null;
    }

    $stmt = $pdo->prepare('SELECT * FROM messages WHERE id = :id LIMIT 1');
    $stmt->bindValue(':id', $messageId, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch();
    return is_array($row) ? chat_sqlite_row_to_message($row) : null;
}

function chat_message_delete_hard(string $messageId): bool
{
    chat_pins_clear_by_message_id($messageId);

    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        $messages = chat_messages_read_all();
        $before = count($messages);
        $messages = array_values(array_filter(
            $messages,
            static fn(array $message): bool => (string)($message['id'] ?? '') !== $messageId
        ));
        if (count($messages) === $before) {
            return false;
        }
        chat_messages_write_all($messages);
        return true;
    }

    $stmt = $pdo->prepare('DELETE FROM messages WHERE id = :id');
    $stmt->bindValue(':id', $messageId, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

function chat_messages_delete_by_user_hard(string $targetUid): int
{
    chat_pins_clear_by_user($targetUid);

    $pdo = chat_sqlite_connection();
    if (!$pdo instanceof PDO) {
        $messages = chat_messages_read_all();
        $remaining = [];
        $deletedCount = 0;
        foreach ($messages as $message) {
            if ((string)($message['from_uid'] ?? '') === $targetUid) {
                $deletedCount++;
                continue;
            }
            $remaining[] = $message;
        }
        if ($deletedCount > 0) {
            chat_messages_write_all($remaining);
        }
        return $deletedCount;
    }

    $stmt = $pdo->prepare('DELETE FROM messages WHERE from_uid = :uid');
    $stmt->bindValue(':uid', $targetUid, PDO::PARAM_STR);
    $stmt->execute();
    return max(0, (int)$stmt->rowCount());
}

function chat_now(): int
{
    return time();
}

function chat_make_id(int $bytes = 8): string
{
    return bin2hex(random_bytes($bytes));
}

function chat_valid_channel(string $channel): string
{
    return array_key_exists($channel, CHAT_CHANNELS) ? $channel : 'main';
}

function chat_sanitize_uid(string $uid): string
{
    $uid = trim($uid);
    $uid = preg_replace('/[^a-zA-Z0-9_-]/', '', $uid) ?? '';

    if (strlen($uid) < 6) {
        return '';
    }

    return substr($uid, 0, 64);
}

function chat_sanitize_name(string $name): string
{
    $name = chat_repair_mojibake_text($name);
    $name = trim($name);
    $name = preg_replace('/\s+/u', ' ', $name) ?? '';
    $name = preg_replace('/[^\p{L}\p{N}._\- ]/u', '', $name) ?? '';
    $name = trim($name);

    if ($name === '') {
        return '';
    }

    return mb_substr($name, 0, 32);
}

function chat_sanitize_theme(string $theme): string
{
    if ($theme === 'light' || $theme === 'dark' || $theme === 'ultra') {
        return $theme;
    }

    return 'ultra';
}

function chat_sanitize_live_rotation(int $rotation): int
{
    return in_array($rotation, CHAT_LIVE_ALLOWED_ROTATIONS, true) ? $rotation : 0;
}

function chat_sanitize_live_stream_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }

    if (strlen($url) > 2048) {
        return '';
    }

    if (!preg_match('/^https?:\/\//i', $url)) {
        return '';
    }

    return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
}

function chat_sanitize_text(string $text): string
{
    $text = chat_repair_mojibake_text($text);
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $text = trim($text);

    if (mb_strlen($text) > CHAT_MAX_MESSAGE_CHARS) {
        $text = mb_substr($text, 0, CHAT_MAX_MESSAGE_CHARS);
    }

    return $text;
}

function chat_repair_mojibake_text(string $value): string
{
    if ($value === '') {
        return '';
    }

    if (!preg_match('/[\x{00C3}\x{00D8}\x{00D9}\x{00DA}\x{00DB}\x{00E2}\x{00F0}\x{FFFD}]/u', $value)) {
        return $value;
    }

    $fixed = @iconv('UTF-8', 'Windows-1252//IGNORE', $value);
    if (!is_string($fixed) || $fixed === '' || !mb_check_encoding($fixed, 'UTF-8')) {
        return $value;
    }

    return $fixed;
}

function chat_sanitize_emoji(string $emoji): string
{
    $emoji = trim($emoji);
    return in_array($emoji, CHAT_REACTION_SET, true) ? $emoji : '';
}

function chat_avatar_seed_from_uid(string $uid): string
{
    return substr(sha1('avatar:' . $uid), 0, 12);
}

function chat_default_avatar_url(string $uid, string $seed = ''): string
{
    return '';
}

function chat_sanitize_avatar_url(string $url, string $uid, string $seed = ''): string
{
    $url = trim($url);
    if ($url === '') {
        return chat_default_avatar_url($uid, $seed);
    }

    $allowedPrefix = str_starts_with($url, 'uploads/')
        || str_starts_with($url, '/uploads/')
        || str_starts_with($url, 'http://')
        || str_starts_with($url, 'https://');

    return $allowedPrefix ? $url : chat_default_avatar_url($uid, $seed);
}

function chat_legacy_uid_from_name(string $name): string
{
    $clean = mb_strtolower(chat_sanitize_name($name));
    if ($clean === '') {
        $clean = 'guest';
    }

    return 'legacy_' . substr(sha1($clean), 0, 12);
}

function chat_default_name_from_uid(string $uid): string
{
    $tail = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $uid) ?? '', -4));
    $tail = $tail === '' ? strtoupper(substr(sha1($uid), 0, 4)) : $tail;
    return 'Guest-' . $tail;
}

function chat_profile_payload(array $profile): array
{
    $uid = (string)($profile['uid'] ?? '');
    $seed = (string)($profile['avatar_seed'] ?? chat_avatar_seed_from_uid($uid));
    $avatarUrl = chat_sanitize_avatar_url((string)($profile['avatar_url'] ?? ''), $uid, $seed);
    $updatedAt = max(0, (int)($profile['updated_at'] ?? 0));

    return [
        'uid' => $uid,
        'display_name' => (string)($profile['display_name'] ?? ''),
        'avatar_seed' => $seed,
        'avatar_url' => $avatarUrl,
        'avatar_updated_at' => $updatedAt,
    ] + chat_role_payload_for_uid($uid);
}

function chat_ensure_profile(array &$profiles, string $uid, string $displayName = ''): array
{
    if (!isset($profiles[$uid]) || !is_array($profiles[$uid])) {
        $seed = chat_avatar_seed_from_uid($uid);
        $profiles[$uid] = [
            'uid' => $uid,
            'display_name' => $displayName !== '' ? $displayName : chat_default_name_from_uid($uid),
            'avatar_seed' => $seed,
            'avatar_url' => chat_default_avatar_url($uid, $seed),
            'updated_at' => chat_now(),
        ];
    }

    if ($displayName !== '' && $displayName !== ($profiles[$uid]['display_name'] ?? '')) {
        $profiles[$uid]['display_name'] = $displayName;
        $profiles[$uid]['updated_at'] = chat_now();
    }

    if (empty($profiles[$uid]['avatar_seed'])) {
        $profiles[$uid]['avatar_seed'] = chat_avatar_seed_from_uid($uid);
    }

    $profiles[$uid]['avatar_url'] = chat_sanitize_avatar_url(
        (string)($profiles[$uid]['avatar_url'] ?? ''),
        $uid,
        (string)$profiles[$uid]['avatar_seed']
    );

    return $profiles[$uid];
}

function chat_normalize_presence(array $presence): array
{
    $now = chat_now();

    foreach ($presence as $uid => $entry) {
        if (!is_array($entry)) {
            unset($presence[$uid]);
            continue;
        }

        $ts = (int)($entry['ts'] ?? 0);
        if (($now - $ts) > CHAT_PRESENCE_TTL) {
            unset($presence[$uid]);
            continue;
        }

        $entry['display_name'] = chat_sanitize_name((string)($entry['display_name'] ?? ''));
        $entry['avatar_seed'] = (string)($entry['avatar_seed'] ?? chat_avatar_seed_from_uid((string)$uid));
        $entry['avatar_url'] = chat_sanitize_avatar_url(
            (string)($entry['avatar_url'] ?? ''),
            (string)$uid,
            (string)$entry['avatar_seed']
        );

        $typingUntil = (int)($entry['typing_until'] ?? 0);
        if ($typingUntil < $now) {
            $entry['typing_until'] = 0;
            $entry['typing_scope'] = '';
            $entry['typing_channel'] = '';
            $entry['typing_to_uid'] = '';
        }

        $entry['last_message_at'] = max(0, (int)($entry['last_message_at'] ?? 0));

        $presence[$uid] = $entry;
    }

    return $presence;
}

function chat_touch_presence(
    array &$presence,
    string $uid,
    string $displayName,
    string $avatarSeed,
    bool $typing,
    string $scope,
    string $channel,
    string $toUid
): void {
    $now = chat_now();

    if (!isset($presence[$uid]) || !is_array($presence[$uid])) {
        $presence[$uid] = [];
    }

    $presence[$uid]['ts'] = $now;
    $presence[$uid]['display_name'] = $displayName;
    $presence[$uid]['avatar_seed'] = $avatarSeed;
    $presence[$uid]['avatar_url'] = chat_sanitize_avatar_url(
        (string)($presence[$uid]['avatar_url'] ?? ''),
        $uid,
        $avatarSeed
    );

    if ($typing) {
        $presence[$uid]['typing_until'] = $now + CHAT_TYPING_TTL;
        $presence[$uid]['typing_scope'] = $scope;
        $presence[$uid]['typing_channel'] = $channel;
        $presence[$uid]['typing_to_uid'] = $toUid;
    } elseif (!isset($presence[$uid]['typing_until'])) {
        $presence[$uid]['typing_until'] = 0;
        $presence[$uid]['typing_scope'] = '';
        $presence[$uid]['typing_channel'] = '';
        $presence[$uid]['typing_to_uid'] = '';
    }
}

function chat_message_cooldown_remaining(array $presence, string $uid, int $now = 0): int
{
    $safeNow = $now > 0 ? $now : chat_now();
    $lastMessageAt = max(0, (int)($presence[$uid]['last_message_at'] ?? 0));
    if ($lastMessageAt <= 0) {
        return 0;
    }

    $elapsed = $safeNow - $lastMessageAt;
    $remaining = CHAT_MESSAGE_COOLDOWN_SECONDS - $elapsed;
    return $remaining > 0 ? $remaining : 0;
}

function chat_mark_message_sent(array &$presence, string $uid, int $time): void
{
    if (!isset($presence[$uid]) || !is_array($presence[$uid])) {
        $presence[$uid] = [];
    }

    $presence[$uid]['last_message_at'] = max(1, $time);
    // Sending a message means typing has finished; clear it immediately.
    $presence[$uid]['typing_until'] = 0;
    $presence[$uid]['typing_scope'] = '';
    $presence[$uid]['typing_channel'] = '';
    $presence[$uid]['typing_to_uid'] = '';
}

function chat_normalize_reactions(mixed $raw): array
{
    if (!is_array($raw)) {
        return [];
    }

    $out = [];

    foreach ($raw as $emoji => $uids) {
        $safeEmoji = chat_sanitize_emoji((string)$emoji);
        if ($safeEmoji === '' || !is_array($uids)) {
            continue;
        }

        $safeList = [];
        foreach ($uids as $uid) {
            $safeUid = chat_sanitize_uid((string)$uid);
            if ($safeUid !== '') {
                $safeList[$safeUid] = true;
            }
        }

        if ($safeList !== []) {
            $out[$safeEmoji] = array_values(array_keys($safeList));
        }
    }

    return $out;
}

function chat_normalize_message(array $message): ?array
{
    $scopeRaw = (string)($message['scope'] ?? '');
    $hasExplicitDmTarget = trim((string)($message['dm_to_uid'] ?? '')) !== '';
    $hasLegacyToField = trim((string)($message['to'] ?? '')) !== '';
    $hasChannelField = trim((string)($message['channel'] ?? '')) !== '';
    $hasLegacyDmTarget = $hasExplicitDmTarget || ($hasLegacyToField && !$hasChannelField);
    $scope = ($scopeRaw === 'dm' || ($scopeRaw === '' && $hasLegacyDmTarget)) ? 'dm' : 'channel';

    $fromUid = chat_sanitize_uid((string)($message['from_uid'] ?? ''));
    $fromName = chat_sanitize_name((string)($message['from_name'] ?? ''));

    if ($fromUid === '') {
        $legacyName = (string)($message['from'] ?? 'Guest');
        $fromUid = chat_legacy_uid_from_name($legacyName);
        if ($fromName === '') {
            $fromName = chat_sanitize_name($legacyName);
        }
    }

    if ($fromName === '') {
        $fromName = chat_default_name_from_uid($fromUid);
    }

    $fromSeed = (string)($message['from_avatar_seed'] ?? chat_avatar_seed_from_uid($fromUid));
    $normalized = [
        'id' => (string)($message['id'] ?? chat_make_id()),
        'time' => max(1, (int)($message['time'] ?? chat_now())),
        'scope' => $scope,
        'from_uid' => $fromUid,
        'from_name' => $fromName,
        'from_avatar_seed' => $fromSeed,
        'from_avatar_url' => chat_sanitize_avatar_url((string)($message['from_avatar_url'] ?? ''), $fromUid, $fromSeed),
        'type' => (string)($message['type'] ?? 'text'),
        'text' => chat_sanitize_text((string)($message['text'] ?? '')),
        'reactions' => chat_normalize_reactions($message['reactions'] ?? []),
    ];

    if ($normalized['type'] === '') {
        $normalized['type'] = 'text';
    }

    if ($scope === 'dm') {
        $toUid = chat_sanitize_uid((string)($message['dm_to_uid'] ?? ''));
        if ($toUid === '') {
            $legacyTo = (string)($message['to'] ?? '');
            $toUid = $legacyTo !== '' ? chat_legacy_uid_from_name($legacyTo) : '';
        }

        if ($toUid === '') {
            return null;
        }

        $normalized['dm_to_uid'] = $toUid;
    } else {
        $normalized['channel'] = chat_valid_channel((string)($message['channel'] ?? 'main'));
    }

    if (!empty($message['file']) && is_array($message['file'])) {
        $file = $message['file'];
        if (!empty($file['url']) && is_string($file['url'])) {
            $normalized['file'] = [
                'url' => (string)$file['url'],
                'name' => (string)($file['name'] ?? 'file'),
                'size' => max(0, (int)($file['size'] ?? 0)),
                'mime' => (string)($file['mime'] ?? 'application/octet-stream'),
            ];
        }
    }

    $replyTo = trim((string)($message['reply_to'] ?? ''));
    if ($replyTo !== '') {
        $normalized['reply_to'] = $replyTo;
    }

    if (!empty($message['edited_at'])) {
        $normalized['edited_at'] = (int)$message['edited_at'];
    }

    if (!empty($message['deleted_at'])) {
        $normalized['deleted_at'] = (int)$message['deleted_at'];
        $normalized['text'] = '';
        unset($normalized['file']);
    }

    return $normalized;
}

function chat_normalize_messages(array $messages): array
{
    $normalized = [];

    foreach ($messages as $message) {
        if (!is_array($message)) {
            continue;
        }

        $item = chat_normalize_message($message);
        if ($item !== null) {
            $normalized[] = $item;
        }
    }

    return $normalized;
}

function chat_filter_visible_messages(array $messages, string $uid, string $channel, ?string $dmUid): array
{
    $visible = [];
    $shadowbans = chat_shadowbans_read_all();

    foreach ($messages as $message) {
        $fromUid = chat_sanitize_uid((string)($message['from_uid'] ?? ''));
        if ($fromUid !== '' && $fromUid !== $uid && isset($shadowbans[$fromUid])) {
            continue;
        }

        $scope = (string)($message['scope'] ?? 'channel');
        if ($scope === 'dm') {
            if ($dmUid === null || $dmUid === '') {
                continue;
            }

            $toUid = (string)($message['dm_to_uid'] ?? '');
            $pairMatches = ($fromUid === $uid && $toUid === $dmUid) || ($fromUid === $dmUid && $toUid === $uid);
            if ($pairMatches) {
                $visible[] = $message;
            }

            continue;
        }

        if ((string)($message['channel'] ?? '') === $channel) {
            $visible[] = $message;
        }
    }

    if (count($visible) > CHAT_HISTORY_LIMIT) {
        $visible = array_slice($visible, -CHAT_HISTORY_LIMIT);
    }

    return $visible;
}

function chat_toggle_reaction(array &$message, string $uid, string $emoji): bool
{
    $emoji = chat_sanitize_emoji($emoji);
    if ($emoji === '' || isset($message['deleted_at'])) {
        return false;
    }

    $reactions = chat_normalize_reactions($message['reactions'] ?? []);

    $currentEmoji = '';
    foreach ($reactions as $emojiKey => $uids) {
        if (in_array($uid, $uids, true)) {
            $currentEmoji = $emojiKey;
            break;
        }
    }

    foreach ($reactions as $emojiKey => $uids) {
        $reactions[$emojiKey] = array_values(array_filter(
            $uids,
            static fn(string $item): bool => $item !== $uid
        ));

        if ($reactions[$emojiKey] === []) {
            unset($reactions[$emojiKey]);
        }
    }

    if ($currentEmoji !== $emoji) {
        if (!isset($reactions[$emoji])) {
            $reactions[$emoji] = [];
        }
        $reactions[$emoji][] = $uid;
        $reactions[$emoji] = array_values(array_unique($reactions[$emoji]));
    }

    $message['reactions'] = $reactions;
    return true;
}

function chat_user_can_view_message(array $message, string $uid): bool
{
    $scope = (string)($message['scope'] ?? 'channel');
    if ($scope !== 'dm') {
        return true;
    }

    $fromUid = (string)($message['from_uid'] ?? '');
    $toUid = (string)($message['dm_to_uid'] ?? '');
    return $fromUid === $uid || $toUid === $uid;
}

function chat_collect_users(
    array $messages,
    array $presence,
    array $profiles,
    string $selfUid,
    array $shadowbans = [],
    bool $includeShadowbanState = false
): array
{
    $users = [];

    foreach ($profiles as $uid => $profile) {
        $safeUid = chat_sanitize_uid((string)$uid);
        if ($safeUid === '') {
            continue;
        }

        $users[$safeUid] = [
            'uid' => $safeUid,
            'display_name' => chat_sanitize_name((string)($profile['display_name'] ?? '')),
            'avatar_seed' => (string)($profile['avatar_seed'] ?? chat_avatar_seed_from_uid($safeUid)),
            'avatar_url' => chat_sanitize_avatar_url(
                (string)($profile['avatar_url'] ?? ''),
                $safeUid,
                (string)($profile['avatar_seed'] ?? chat_avatar_seed_from_uid($safeUid))
            ),
            'avatar_updated_at' => max(0, (int)($profile['updated_at'] ?? 0)),
        ] + chat_role_payload_for_uid($safeUid);

        if ($users[$safeUid]['display_name'] === '') {
            $users[$safeUid]['display_name'] = chat_default_name_from_uid($safeUid);
        }
    }

    foreach ($messages as $message) {
        $fromUid = (string)($message['from_uid'] ?? '');
        if ($fromUid !== '' && !isset($users[$fromUid])) {
            $users[$fromUid] = [
                'uid' => $fromUid,
                'display_name' => (string)($message['from_name'] ?? chat_default_name_from_uid($fromUid)),
                'avatar_seed' => (string)($message['from_avatar_seed'] ?? chat_avatar_seed_from_uid($fromUid)),
                'avatar_url' => chat_sanitize_avatar_url(
                    (string)($message['from_avatar_url'] ?? ''),
                    $fromUid,
                    (string)($message['from_avatar_seed'] ?? chat_avatar_seed_from_uid($fromUid))
                ),
                'avatar_updated_at' => 0,
            ] + chat_role_payload_for_uid($fromUid);
        }
    }

    if (!isset($users[$selfUid])) {
        $users[$selfUid] = [
            'uid' => $selfUid,
            'display_name' => chat_default_name_from_uid($selfUid),
            'avatar_seed' => chat_avatar_seed_from_uid($selfUid),
            'avatar_url' => chat_default_avatar_url($selfUid, chat_avatar_seed_from_uid($selfUid)),
            'avatar_updated_at' => 0,
        ] + chat_role_payload_for_uid($selfUid);
    }

    if ($includeShadowbanState) {
        foreach ($users as $uid => $user) {
            $users[$uid]['is_shadowbanned'] = isset($shadowbans[$uid]);
        }
    }

    uasort(
        $users,
        static fn(array $a, array $b): int => strnatcasecmp((string)$a['display_name'], (string)$b['display_name'])
    );

    return array_values($users);
}

function chat_collect_typing_users(array $presence, string $uid, string $scope, string $channel, ?string $dmUid): array
{
    $typingUsers = [];

    foreach ($presence as $entryUid => $entry) {
        if ($entryUid === $uid) {
            continue;
        }

        $typingUntil = (int)($entry['typing_until'] ?? 0);
        if ($typingUntil < chat_now()) {
            continue;
        }

        $entryScope = (string)($entry['typing_scope'] ?? '');
        if ($entryScope !== $scope) {
            continue;
        }

        if ($scope === 'channel') {
            if ((string)($entry['typing_channel'] ?? '') !== $channel) {
                continue;
            }
        } else {
            $entryToUid = (string)($entry['typing_to_uid'] ?? '');
            if ($dmUid === null || $dmUid === '') {
                continue;
            }

            if ($entryUid !== $dmUid || $entryToUid !== $uid) {
                continue;
            }
        }

        $typingUsers[] = [
            'uid' => $entryUid,
            'display_name' => (string)($entry['display_name'] ?? chat_default_name_from_uid($entryUid)),
        ];
    }

    return $typingUsers;
}

function chat_parse_seen_map(string $raw): array
{
    if ($raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [];
    }

    $out = [];
    foreach ($decoded as $key => $value) {
        if (!is_string($key)) {
            continue;
        }
        $out[$key] = max(0, (int)$value);
    }

    return $out;
}

function chat_conversation_key(string $scope, string $channel = '', string $peerUid = ''): string
{
    if ($scope === 'dm') {
        return 'dm:' . $peerUid;
    }

    return 'ch:' . $channel;
}

function chat_compute_unread_hints(array $messages, string $uid, array $seenMap): array
{
    $seenMap = chat_seen_read_map($uid);
    $channels = [];
    foreach (array_keys(CHAT_CHANNELS) as $channel) {
        $channels[$channel] = 0;
    }

    $shadowbans = chat_shadowbans_read_all();

    foreach ($messages as $message) {
        if (!is_array($message)) {
            continue;
        }

        $fromUid = chat_sanitize_uid((string)($message['from_uid'] ?? ''));
        if ($fromUid === $uid) {
            continue;
        }
        if ($fromUid !== '' && isset($shadowbans[$fromUid])) {
            continue;
        }

        $time = (int)($message['time'] ?? 0);
        if (($message['scope'] ?? '') === 'dm') {
            continue;
        }

        $channel = chat_valid_channel((string)($message['channel'] ?? 'main'));
        $key = chat_conversation_key('channel', $channel);
        $seenTs = (int)($seenMap[$key] ?? 0);
        if ($time > $seenTs) {
            $channels[$channel]++;
        }
    }

    return [
        'channels' => $channels,
        'dms' => [],
    ];
}
