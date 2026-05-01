<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
chat_bootstrap_storage();
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#080e1c">
  <link rel="icon" type="image/png" href="/icon.png">
  <title>TEROZA</title>
  <link rel="stylesheet" href="style.css?v=35">
</head>
<body data-theme="ultra">

  <div class="app-shell">

    <!-- نوار بالای چت -->
    <header id="chatHeader" class="chat-header glass">

      <span id="currentModeIcon" class="mode-icon">U</span>

      <span id="currentMode" class="mode-label">یک گفتگو را انتخاب کنید</span>

      <div class="meta-row">
        <div class="meta-right">
          <span id="onlineBadge" class="online-badge">0 آنلاین</span>
          <div id="typingIndicator" class="typing-indicator" aria-live="polite"></div>
        </div>
      </div>

      <div class="menu-toggle" id="mobile-menu">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
      </div>

    </header>
    
    <aside class="sidebar" id="sidebar-menu">
      <header class="topbar glass">
        <div class="title-row">

        <div class="title-right">
            <button id="profileBtn" class="profile-chip" type="button" title="پروفایل" aria-label="پروفایل">
              <span id="profileAvatar" class="avatar"></span>
              <span id="profileName" class="profile-name">Guest</span>
            </button>
          </div>


          <div class="brand">
            <h1>TEROZA</h1>
            <p>محفل گرم و مخفی دوران قطعی !</p>
          </div>


        </div>

        <section class="sidebar-support glass">
          <div class="support-copy">
            <strong>دسترسی‌های ویژه</strong>
          </div>

          <div class="support-actions">
            <a id="donateBtnSidebar" class="support-btn support-btn-donate support-btn-glow" href="#" target="_blank" rel="noopener noreferrer">حمایت مالی</a>
            <a id="uploadBtnSidebar" class="support-btn support-btn-upload" href="#" target="_blank" rel="noopener noreferrer">آپلود سنتر</a>
            <button id="liveBtnSidebar" class="support-btn support-btn-live" type="button">لایو استریم</button>
            <button id="ownerPanelBtnSidebar" class="support-btn support-btn-owner hidden" type="button">پنل اونر</button>
          </div>
        </section>

        <div class="tabs-row">
          <nav id="channelTabs" class="tabs"></nav>
        </div>
   

        <div id="dmUsers" class="dm-users"></div>

      </header>
    </aside>

    <main class="chat-stage">
      <div id="staffOnlyBanner" class="staff-only-banner hidden"></div>

      <section id="pinnedBar" class="pinned-bar hidden">
        <div class="pinned-copy">
          <span class="pinned-label">پیام پین‌شده</span>
          <button id="pinnedJumpBtn" class="pinned-summary" type="button"></button>
          <span id="pinnedMeta" class="pinned-meta"></span>
        </div>
        <button id="pinnedUnpinBtn" class="pinned-unpin hidden" type="button" aria-label="برداشتن پین">×</button>
      </section>

      <div id="chatFeed" class="chat-feed" aria-live="polite"></div>
    </main>

    <!-- دکمه بازگشت به آخرین پیام -->
    <button id="scrollToBottomBtn" class="scroll-to-bottom-btn" title="رفتن به آخرین پیام">
        &#8595;
    </button>


    <footer class="composer glass">
      <div id="composeState" class="compose-state hidden">
        <div id="composePreview" class="compose-preview"></div>
        <button id="clearComposeBtn" class="ghost-btn" type="button">لغو</button>
      </div>

      <div id="filePreview" class="file-preview hidden">
        <div class="file-preview-info">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          <span id="filePreviewName"></span>
        </div>
        <button id="clearFileBtn" class="file-preview-close" type="button" title="حذف فایل">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <div id="uploadProgress" class="upload-progress hidden">
        <div id="uploadProgressBar" class="upload-progress-bar"></div>
        <span id="uploadProgressText" class="upload-progress-text">0%</span>
      </div>

      <form id="composerForm" autocomplete="off">
        <div class="composer-row">
          <button id="attachBtn" class="icon-btn" type="button" title="پیوست فایل" aria-label="پیوست فایل">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
          </button>
          <button id="emojiBtn" class="icon-btn" type="button" title="ایموجی" aria-label="ایموجی">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
          </button>

          <input id="messageInput" type="text" maxlength="4096" placeholder="پیام بنویس...">

          <button id="actionBtn" class="action-btn" type="button" data-mode="mic" title="ضبط ویس" aria-label="ضبط ویس">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
          </button>
          <input id="fileInput" type="file" accept=".jpg,.jpeg,.png,.webp,.gif,.avif,.mp4,.webm,.mov,.m4v,.ogv,.mp3,.wav,.flac,.m4a,.aac,.ogg,.oga,.opus,.weba" hidden>
        </div>

        <div id="emojiPicker" class="emoji-picker hidden"></div>

      </form>

        <!-- Popup window -->
        <div id="composerHint" class="composer-hint">
          
        </div>



    </footer>
  </div>

  <aside id="profileSheet" class="sheet hidden" aria-hidden="true">
    <div class="sheet-card glass">
      <div class="sheet-head">
        <h2>پروفایل</h2>
        <button id="closeProfileBtn" class="icon-btn" type="button" aria-label="بستن">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <div class="sheet-body">
        <div class="profile-hero">
          <span id="profileAvatarLarge" class="avatar avatar-lg profile-avatar-large"></span>
          <div>
            <div id="profileNameLarge" class="profile-title"></div>
            <code id="profileUid" class="uid-code"></code>
            <div class="profile-avatar-actions">
              <label for="profileAvatarInput" class="profile-avatar-upload">تغییر دادن</label>
              <small>حداکثر 6MB</small>
            </div>
          </div>
        </div>
        <input id="profileAvatarInput" type="file" accept="image/*" hidden>

        <label for="profileNameInput" class="field-label">نام نمایشی</label>
        <input id="profileNameInput" class="field-input" type="text" maxlength="32" placeholder="نام نمایشی">

        <div class="theme-row">
          <span>حالت نمایش</span>
          <div id="themeSegment" class="theme-segment" role="group" aria-label="Theme">
            <button type="button" class="theme-choice" data-theme-choice="light">روشن</button>
            <button type="button" class="theme-choice" data-theme-choice="dark">تاریک</button>
            <button type="button" class="theme-choice" data-theme-choice="ultra">خیلی تاریک</button>
          </div>
        </div>

        <button id="saveProfileBtn" class="save-btn" type="button">ذخیره تغییرات</button>
      </div>
    </div>
  </aside>

  <aside id="liveSheet" class="sheet hidden" aria-hidden="true">
    <div class="sheet-card support-sheet-card glass" tabindex="-1">
      <div class="sheet-head">
        <div class="section-head">
          <div>
            <h2>لایو استریم</h2>
            <p id="liveSheetSubtitle">اگر لایو باز باشد همین‌جا پخش می‌شود.</p>
          </div>
        </div>
        <button id="closeLiveBtn" class="icon-btn" type="button" aria-label="بستن">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <div class="sheet-body live-sheet-body">
        <section class="live-player-panel">
          <div class="live-player-top">
            <div>
              <strong id="liveStatusTitle" class="live-status-title">لایو بسته است</strong>
              <p id="liveStatusMeta" class="sheet-subtitle">در صورت باز بودن لایو، پخش از همین باکس انجام می‌شود.</p>
            </div>
            <button id="liveFullscreenBtn" class="support-btn live-fullscreen-btn hidden" type="button">فول اسکرین</button>
          </div>

          <div id="livePlayerShell" class="live-player-shell" data-rotation="0">
            <div class="live-player-frame">
              <video id="liveVideo" class="live-video" controls playsinline preload="metadata"></video>
              <div id="livePlaceholder" class="live-placeholder">
                <p id="livePlaceholderText">در این بخش لایو نمایش داده می‌شود.</p>
              </div>
            </div>
          </div>
        </section>

        <div id="liveClosedBox" class="live-closed-box">
          <p id="liveSheetMessage" class="support-modal-text"></p>
          <a id="donateBtnModal" class="support-btn support-btn-donate support-btn-modal support-btn-glow" href="#" target="_blank" rel="noopener noreferrer">حمایت مالی</a>
        </div>

        <section id="liveOwnerPanel" class="owner-panel-section live-owner-panel hidden">
          <div class="section-head">
            <div>
              <h3>تنظیمات لایو</h3>
              <p id="liveOwnerMeta">فقط اونر می‌تواند وضعیت، لینک و چرخش لایو را تغییر دهد.</p>
            </div>
          </div>

          <form id="liveOwnerForm" class="live-owner-form">
            <label class="owner-toggle-row" for="liveClosedToggle">
              <input id="liveClosedToggle" type="checkbox">
              <span class="toggle-pill"></span>
              <span>لایو برای کاربران بسته باشد</span>
            </label>

            <label for="liveStreamUrlInput" class="field-label">لینک پخش لایو</label>
            <input id="liveStreamUrlInput" class="field-input" type="url" inputmode="url" dir="ltr" placeholder="https://example.com/live/index.m3u8" autocomplete="off">

            <label for="liveRotationSelect" class="field-label">چرخش تصویر</label>
            <select id="liveRotationSelect" class="field-input">
              <option value="0">0 درجه</option>
              <option value="90">90 درجه</option>
              <option value="180">180 درجه</option>
              <option value="270">270 درجه</option>
            </select>

            <button id="saveLiveSettingsBtn" class="save-btn" type="submit">ذخیره تنظیمات لایو</button>
          </form>
        </section>
      </div>
    </div>
  </aside>

  <aside id="ownerPanelSheet" class="sheet hidden" aria-hidden="true">
    <div class="sheet-card owner-sheet-card glass" tabindex="-1">
      <div class="sheet-head">
        <div class="section-head">
          <div>
            <h2>پنل اونر</h2>
            <p>مدیریت وضعیت استاف، ادمین‌ها، آمار و کاربران.</p>
          </div>
        </div>
        <button id="closeOwnerPanelBtn" class="icon-btn" type="button" aria-label="بستن">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <div class="sheet-body owner-panel-body">
        <section class="owner-panel-section">
          <div class="section-head">
            <div>
              <h3>حالت استاف</h3>
              <p id="ownerStaffOnlyMeta">در حال همگام‌سازی...</p>
            </div>
          </div>
          <label class="owner-toggle-row" for="ownerStaffOnlyToggle">
            <input id="ownerStaffOnlyToggle" type="checkbox">
            <span class="toggle-pill"></span>
            <span>ارسال پیام جدید فقط برای استاف فعال شود</span>
          </label>
        </section>

        <section class="owner-panel-section">
          <div class="section-head">
            <div>
              <h3>افزودن ادمین</h3>
              <p>شناسه کاربر را وارد کن تا دسترسی ادمین به او داده شود.</p>
            </div>
          </div>
          <form id="ownerAddAdminForm" class="owner-inline-form">
            <input id="ownerAdminUidInput" class="field-input" type="text" placeholder="UID کاربر" autocomplete="off">
            <button class="save-btn" type="submit">افزودن</button>
          </form>
        </section>

        <section class="owner-panel-section">
          <div class="section-head">
            <div>
              <h3>آمار کلی</h3>
              <p>نمای سریع از وضعیت ذخیره‌سازی و فعالیت چت روم.</p>
            </div>
          </div>
          <div id="ownerStatsGrid" class="owner-stats-grid"></div>
        </section>

        <section class="owner-panel-section">
          <div class="section-head">
            <div>
              <h3>ادمین‌ها</h3>
              <p>لیست ادمین‌های ثابت، افزوده‌شده و اونرها.</p>
            </div>
          </div>
          <div id="ownerAdminsList" class="owner-list"></div>
        </section>

        <section class="owner-panel-section">
          <div class="section-head">
            <div>
              <h3>شادوبن‌ها</h3>
              <p>مدیریت کاربران شادوبن‌شده.</p>
            </div>
          </div>
          <div id="ownerShadowbanList" class="owner-list"></div>
        </section>

        <section class="owner-panel-section">
          <div class="section-head">
            <div>
              <h3>کاربران</h3>
              <p>نمای فشرده از کاربران برای اقدامات سریع.</p>
            </div>
          </div>
          <div id="ownerUsersList" class="owner-list"></div>
        </section>

        <section class="owner-panel-section">
          <div class="section-head">
            <div>
              <h3>لاگ عملیات</h3>
              <p>آخرین تغییرات مدیریتی ثبت‌شده.</p>
            </div>
          </div>
          <div id="ownerAuditList" class="owner-audit-list"></div>
        </section>
      </div>
    </div>
  </aside>

  <template id="messageTpl">
    <article class="msg">
      
      <div class="msg-avatar-wrap">
        <span class="avatar msg-avatar"></span>
      </div>

      <div id="chat" class="msg-card">
        <header class="msg-head">
          <strong class="msg-user"></strong>
          <time class="msg-time"></time>
        </header>

        <div class="msg-reply-ref hidden"></div>
        <div class="msg-body"></div>
        <div class="msg-meta"></div>
        <div class="msg-react-summary hidden"></div>
      </div>

    </article>
  </template>

  <aside id="messageMenu" class="msg-menu hidden" aria-hidden="true">
    <div class="msg-menu-card glass">
      <div id="messageMenuReactions" class="msg-menu-reactions"></div>
      <div id="messageMenuActions" class="msg-menu-actions"></div>
    </div>
  </aside>

  <aside id="avatarViewerSheet" class="sheet avatar-viewer-sheet hidden" aria-hidden="true">
    <div class="sheet-card avatar-viewer-card glass" tabindex="-1">
      <div class="sheet-head avatar-viewer-head">
        <button id="closeAvatarViewerBtn" class="support-btn avatar-viewer-back" type="button">بازگشت</button>
      </div>

      <div class="sheet-body avatar-viewer-body">
        <span id="avatarViewerAvatar" class="avatar avatar-hero"></span>
        <strong id="avatarViewerName" class="avatar-viewer-name">کاربر</strong>
        <code id="avatarViewerUid" class="avatar-viewer-uid"></code>
      </div>
    </div>
  </aside>

  <div id="imageModal" class="modal">
    <div class="modal-controls">
      <span class="close-button">&times;</span>
      <a id="downloadLink" class="download-button" download>⬇︎</a>
    </div>
      <img class="modal-content" id="modalImage" src="">
      <div id="caption"></div>
  </div>

  <script>
    window.CHAT_BOOT = {
      channels: <?php echo json_encode(CHAT_CHANNELS, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
      quickReactions: <?php echo json_encode(CHAT_REACTION_SET, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
      maxUploadBytes: <?php echo (int) CHAT_MAX_UPLOAD_BYTES; ?>,
      maxProfileAvatarBytes: <?php echo (int) CHAT_MAX_PROFILE_AVATAR_BYTES; ?>,
      maxMessageChars: <?php echo (int) CHAT_MAX_MESSAGE_CHARS; ?>,
      messageCooldownSeconds: <?php echo (int) CHAT_MESSAGE_COOLDOWN_SECONDS; ?>,
      enableRealtimeStream: <?php echo CHAT_ENABLE_SSE ? 'true' : 'false'; ?>,
      enableLongPoll: false,
      liveHlsScriptUrl: "hls.light.min.js?v=1",
      defaultLiveEnabled: <?php echo CHAT_DEFAULT_LIVE_ENABLED ? 'true' : 'false'; ?>,
      defaultLiveStreamUrl: <?php echo json_encode(CHAT_DEFAULT_LIVE_STREAM_URL, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
      defaultLiveRotation: <?php echo (int) CHAT_DEFAULT_LIVE_ROTATION; ?>,
      support: {
        donateUrl: <?php echo json_encode(CHAT_SUPPORT_DONATE_URL, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        uploadUrl: <?php echo json_encode(CHAT_SUPPORT_UPLOAD_URL, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        liveMessage: <?php echo json_encode(CHAT_LIVE_MODAL_TEXT, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
      }
    };
  </script>

  <script src="app.js?v=37" charset="utf-8"></script>
</body>
</html>
