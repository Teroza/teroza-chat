(() => {
  const boot = window.CHAT_BOOT || {
    channels: {
      main: "گروه عمومی",
      news: "اخبار آزاد",
      iin: "ویس و ویدیو",
      mime: "میم و ادیت",
      music: "موسیقی",
      ask: "سوالات متداول",
      misc: "متفرقه",
      conf: "کانفیگ و پروکسی",
      report: "گزارش باگ"
    },
    quickReactions: [
      "👍", "👎", "🖕", "🤣", "😂", "😢", "😭",
      "🔥", "❤️", "💔", "🤡", "👀", "💯", "🤝",
      "😱", "🤯", "😮", "🤓", "💩", "👻"
    ],
    maxUploadBytes: 12582912,
    maxProfileAvatarBytes: 2 * 1024 * 1024,
    maxMessageChars: 4096,
    messageCooldownSeconds: 1,
    enableRealtimeStream: false,
    enableLongPoll: false,
    liveHlsScriptUrl: "hls.light.min.js?v=1",
    defaultLiveEnabled: true,
    defaultLiveStreamUrl: "https://tv.zaringo.sbs/desktop/desktop.m3u8",
    defaultLiveRotation: 0
  };

  const PROFILE_KEY = "teroza_chat_profile_v2";
  const SEEN_KEY = "teroza_chat_seen_v2";
  const BADGE_ASSETS = Object.freeze({
    admin: "star.png?v=1",
    owner: "owner-crown.png?v=1"
  });
  const THEME_OPTIONS = ["light", "dark", "ultra"];
  const IS_TOUCH_DEVICE = window.matchMedia("(hover: none) and (pointer: coarse)").matches;
  const MAX_UPLOAD_BYTES = Number(boot.maxUploadBytes || (128 * 1024 * 1024));
  const MAX_PROFILE_AVATAR_BYTES = Number(boot.maxProfileAvatarBytes || (6 * 1024 * 1024));
  const MAX_MESSAGE_CHARS = Math.max(1, Number(boot.maxMessageChars || 4096));
  const MESSAGE_COOLDOWN_SECONDS = Math.max(0, Number(boot.messageCooldownSeconds || 1));
  const MAX_REPLY_ALIASES = 1000;
  const REALTIME_STREAM_ENABLED = boot.enableRealtimeStream === true;
  const LONG_POLL_ENABLED = boot.enableLongPoll === true;
  const HLS_SCRIPT_URL = String(boot.liveHlsScriptUrl || "hls.light.min.js?v=1").trim();
  const THEME_RESET_VERSION = 20260426;
  const DEFAULT_MESSAGE_PLACEHOLDER = "پیام بنویس...";
  const STAFF_ONLY_NEW_MESSAGES_HINT = "ارسال پیام جدید موقتاً فقط برای ادمین‌ها و اونرها فعاله.";
  const SUPPORT_CONFIG = Object.freeze({
    donateUrl: String(boot.support?.donateUrl || "").trim(),
    uploadUrl: String(boot.support?.uploadUrl || "").trim(),
    liveMessage: String(boot.support?.liveMessage || "").trim()
  });
  const DEFAULT_LIVE_MESSAGE = SUPPORT_CONFIG.liveMessage
    || "با توجه به هزینه های بالای استریم کردن اینترنشنال به صورت لایو فعلا برامون اینکار مقدور نیست. اما اگه حمایتاتون خوب باشه حتما لایو رو میاریم بالا که دوباره لذت ببرید. دم همگی گرم!";
  const LIVE_ROTATIONS = [0, 90, 180, 270];
  const DEFAULT_LIVE_ENABLED = boot.defaultLiveEnabled !== false;
  const DEFAULT_LIVE_STREAM_URL = String(boot.defaultLiveStreamUrl || "https://tv.zaringo.sbs/desktop/desktop.m3u8").trim();
  const DEFAULT_LIVE_ROTATION = LIVE_ROTATIONS.includes(Number(boot.defaultLiveRotation))
    ? Number(boot.defaultLiveRotation)
    : 0;
  const POLL_ACTIVE_MS = 12000;
  const POLL_TYPING_MS = 5000;
  const POLL_IDLE_MS = 25000;
  const POLL_HIDDEN_MS = 35000;
  const POLL_ERROR_MAX_MS = 30000;
  const LONG_POLL_TIMEOUT_MS = 20000;
  const USER_ACTIVE_WINDOW_MS = 45000;
  const META_REFRESH_MS = 30000;
  const USER_META_REFRESH_MS = 90000;
  const FULL_STATE_REFRESH_MS = 900000;
  const SIDEBAR_REFRESH_MS = 600000;
  const PRESENCE_TOUCH_MS = 45000;
  const TYPING_PING_MS = 5000;

  const MEDIA_UPLOAD_RULES = {
      jpg: ["image/jpeg"],
      jpeg: ["image/jpeg"],
      png: ["image/png"],
      webp: ["image/webp"],
      gif: ["image/gif"],
      avif: ["image/avif"],
      apng: ["image/apng"],
      bmp: ["image/bmp", "image/x-bmp"],
      svg: ["image/svg+xml"],
      ico: ["image/x-icon", "image/vnd.microsoft.icon"],
      tiff: ["image/tiff", "image/tif", "image/x-tiff"],
      tif: ["image/tiff", "image/tif", "image/x-tif"],
    
      mp4: ["video/mp4", "video/mpeg4"],
      webm: ["video/webm"],
      mov: ["video/quicktime"],
      m4v: ["video/x-m4v", "video/mp4"],
      ogv: ["video/ogg"],
      "3gp": ["video/3gpp", "video/3gp"],
      "3g2": ["video/3gpp2", "video/3g2"],
      mkv: ["video/x-matroska"], // Chrome/Edge only
      avi: ["video/x-msvideo"], // Limited support
    
      mp3: ["audio/mpeg", "audio/mp3", "audio/x-mpeg"],
      wav: ["audio/wav", "audio/wave", "audio/x-wav", "audio/vnd.wave"],
      flac: ["audio/flac", "audio/x-flac"],
      m4a: ["audio/mp4", "audio/m4a", "audio/x-m4a"],
      aac: ["audio/aac", "audio/aacp"],
      ogg: ["audio/ogg", "application/ogg"],
      oga: ["audio/ogg", "application/ogg"],
      opus: ["audio/opus", "audio/ogg", "application/ogg"],
      webm: ["audio/webm", "audio/webm;codecs=opus"],
      aiff: ["audio/aiff", "audio/x-aiff", "audio/aif", "audio/x-aif"],
      aif: ["audio/aiff", "audio/x-aiff", "audio/aif", "audio/x-aif"],
      mid: ["audio/midi", "audio/x-midi", "audio/mid"],
      midi: ["audio/midi", "audio/x-midi", "audio/mid"],
      amr: ["audio/amr", "audio/amr-wb"],
      ac3: ["audio/ac3"], // Some browsers
      eac3: ["audio/eac3"], // Some browsers
    };
  const ACTION_ICONS = {
    mic: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3z"/><path d="M19 11v1a7 7 0 0 1-14 0v-1"/><path d="M12 19v3"/><path d="M8 22h8"/></svg>',
    send: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4z"/></svg>',
    stop: '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="7" y="7" width="10" height="10" rx="2"/></svg>'
  };

  const EMOJI_PICKER_SET = [
    // احساسات و صورتک‌ها
    "😀", "😁", "😂", "🤣", "😊", "😇", "🙂", "😉", "😍", "😘", "😎", "🤔", "😮", "🥺", "😢", "😭", "😡", "🤬", "🤡", "👻",
    
    // دست‌ها و بدن
    "👍", "👎", "👏", "🙏", "👌", "✌️", "🤞", "🤟", "🤘", "🤙", "💪", "👀", "🧠", "👤",
    
    // قلب‌ها و نمادها
    "❤️", "🧡", "💛", "💚", "💙", "💜", "🖤", "🤍", "🤎", "💔", "💯", "✨", "🔥", "🌟", "💫", "💥", "🛑", "⛔", "🚫", "🔞",
    
    // طبیعت و آب و هوا
    "🌙", "🌈", "🍀", "☀️", "☁️", "🌧️", "⛈️", "❄️", "🌬️", "🌊", "🌹", "🌻", "🍁", "🍂", "🌍", "🌋",
    
    // حیوانات
    "🐶", "🐱", "🐭", "🐹", "🐰", "🦊", "🐻", "🐼", "🐨", "🐯", "🦁", "🐸", "🐮", "🦋", "🐬", "🐳",
    
    // غذا و نوشیدنی
    "☕", "🍎", "🍓", "🍇", "🍔", "🍕", "🍟", "🌮", "🍦", "🍩", "🍪", "🎂", "🍰", "🍵", "🥤",
    
    // اشیاء و تکنولوژی
    "📱", "💻", "🖥️", "⌨️", "🖱️", "🎧", "📷", "📸", "💡", "🔋", "🔌", "🛠️", "🔧", "🔨", "📌", "📍", "📎",
    
    // حمل و نقل
    "🚀", "🚗", "🚕", "🚌", "🏎️", "🚓", "🚑", "🚒", "✈️", "🚁", "🚲", "🏍️", "🚢",
    
    // ورزش و سرگرمی
    "⚽", "🏀", "🏈", "⚾", "🎾", "🏐", "🎱", "🏓", "🏸", "🎯", "🎲", "🧩", "🎭", "🎨", "🎬", "🎤",
    
    // متفرقه و خاص
    "🎉", "🎊", "🎈", "🎁", "🎀", "👑", "🎓", "🏅", "🏆", "🎫", "🎪", "🏰", "⛪", "🕌", "⛩️"
  ];
  
  const RANDOM_LEFT = ["برفی", "آبی", "شیشه", "سریع", "مهتاب", "ابر", "شب", "طلوع", "نقره", "خلاق"];
  const RANDOM_RIGHT = ["کاربر", "دوست", "مهمان", "چت", "پیام", "نوا", "صدا", "روشن", "نقش", "سبک"];

  const state = {
    profile: null,
    profileDraft: "",
    profileEditing: false,
    profileSheetOpen: false,

    mode: "channel",
    channel: "main",
    dmPeerUid: "",

    users: [],
    userMap: new Map(),
    isAdmin: false,
    isOwner: false,
    role: "user",
    canSendNewMessages: true,
    staffOnlyNewMessages: false,
    messagesStorage: "",
    storageInfo: null,
    onlineCount: 0,
    serverMessages: [],
    messages: [],
    pendingMessages: new Map(),
    localOnlyMessages: new Map(),
    replyAliases: new Map(),
    conversationCache: new Map(),
    typingUsers: [],
    userRevision: 0,
    unreadHints: { channels: {}, dms: {} },
    pinnedMessage: null,
    seenMap: {},
    seenDirtyMap: {},
    messageRevision: 0,
    typingRevision: 0,

    compose: {
      mode: "none",
      messageId: "",
      preview: ""
    },

    loading: false,
    pendingForceFetch: false,

    pollDelay: 0,
    pollTimer: null,
    fallbackPolling: false,
    eventSource: null,
    sseConnected: false,
    sseReconnectTimer: null,
    sseReconnectBackoff: 1200,
    realtimeRefreshTimer: null,
    realtimePulseTimer: null,
    safetyRefreshTimer: null,
    cooldownTimer: null,
    pinging: false,
    lastFullFetchAt: 0,
    lastUsersFetchAt: 0,
    lastSidebarFetchAt: 0,
    lastPresenceTouchAt: 0,
    activityBurstUntil: 0,
    userActiveUntil: Date.now() + USER_ACTIVE_WINDOW_MS,

    recording: null,
    mediaStream: null,
    pendingFile: null,
    pendingAvatarFile: null,
    pendingAvatarPreviewUrl: "",
    actionBusy: false,
    uploadVisible: false,
    nextSendAllowedAt: 0,

    typingAt: 0,
    typingTimer: null,

    lastRenderKey: "",
    lastMessageCount: 0,
    stickToBottom: true,
    forceScrollOnce: false,
    hasFetchedAtLeastOnce: false,
    suppressNextIncomingSound: true,
    seenIncomingSoundIds: new Set(),
    lastReceiveSoundAt: 0,
    lastHiddenNotifySoundAt: 0,
    audioCtx: null,
    audioUnlocked: false,

    support: {
      liveOpen: false,
      ownerPanelOpen: false,
      ownerPanelLoading: false,
      ownerData: null,
      avatarViewerOpen: false,
      avatarViewerData: null,
      focusRestoreEl: null,
      activeSheet: null,
      liveConfig: createDefaultLiveConfig(),
      liveFormClosed: !DEFAULT_LIVE_ENABLED,
      liveFormStreamUrl: DEFAULT_LIVE_STREAM_URL,
      liveFormRotation: DEFAULT_LIVE_ROTATION,
      liveFormDirty: false,
      liveSaving: false,
      livePlaybackError: "",
      livePlaybackReady: false,
      livePlaybackSource: "",
      liveHls: null,
      liveHlsScriptPromise: null,
      liveVideoCleanup: null,
      liveSetupToken: 0
    },

    contextMenu: {
      messageId: "",
      pressTimer: null,
      pressStartX: 0,
      pressStartY: 0
    }
  };

  const el = {
    profileBtn: document.getElementById("profileBtn"),
    profileAvatar: document.getElementById("profileAvatar"),
    profileName: document.getElementById("profileName"),

    currentMode: document.getElementById("currentMode"),
    typingIndicator: document.getElementById("typingIndicator"),
    onlineBadge: document.getElementById("onlineBadge"),
    donateBtnSidebar: document.getElementById("donateBtnSidebar"),
    donateBtnModal: document.getElementById("donateBtnModal"),
    uploadBtnSidebar: document.getElementById("uploadBtnSidebar"),
    liveBtnSidebar: document.getElementById("liveBtnSidebar"),
    ownerPanelBtnSidebar: document.getElementById("ownerPanelBtnSidebar"),
    staffOnlyBanner: document.getElementById("staffOnlyBanner"),

    channelTabs: document.getElementById("channelTabs"),
    dmUsers: document.getElementById("dmUsers"),
    pinnedBar: document.getElementById("pinnedBar"),
    pinnedSummary: document.getElementById("pinnedJumpBtn"),
    pinnedMeta: document.getElementById("pinnedMeta"),
    pinnedJumpBtn: document.getElementById("pinnedJumpBtn"),
    pinnedUnpinBtn: document.getElementById("pinnedUnpinBtn"),
    chatFeed: document.getElementById("chatFeed"),
    scrollToBottomBtn: document.getElementById('scrollToBottomBtn'),

    composeState: document.getElementById("composeState"),
    composePreview: document.getElementById("composePreview"),
    clearComposeBtn: document.getElementById("clearComposeBtn"),

    composerForm: document.getElementById("composerForm"),
    attachBtn: document.getElementById("attachBtn"),
    emojiBtn: document.getElementById("emojiBtn"),
    emojiPicker: document.getElementById("emojiPicker"),
    messageInput: document.getElementById("messageInput"),
    actionBtn: document.getElementById("actionBtn"),
    fileInput: document.getElementById("fileInput"),
    filePreview: document.getElementById("filePreview"),
    filePreviewName: document.getElementById("filePreviewName"),
    clearFileBtn: document.getElementById("clearFileBtn"),
    uploadProgress: document.getElementById("uploadProgress"),
    uploadProgressBar: document.getElementById("uploadProgressBar"),
    uploadProgressText: document.getElementById("uploadProgressText"),
    composerHint: document.getElementById("composerHint"),
    closeBtn: document.getElementById('composerHintClose'),
    profileSheet: document.getElementById("profileSheet"),
    closeProfileBtn: document.getElementById("closeProfileBtn"),
    profileAvatarLarge: document.getElementById("profileAvatarLarge"),
    profileAvatarInput: document.getElementById("profileAvatarInput"),
    profileNameLarge: document.getElementById("profileNameLarge"),
    profileUid: document.getElementById("profileUid"),
    profileNameInput: document.getElementById("profileNameInput"),
    saveProfileBtn: document.getElementById("saveProfileBtn"),
    themeChoices: Array.from(document.querySelectorAll("[data-theme-choice]")),
    avatarViewerSheet: document.getElementById("avatarViewerSheet"),
    closeAvatarViewerBtn: document.getElementById("closeAvatarViewerBtn"),
    avatarViewerAvatar: document.getElementById("avatarViewerAvatar"),
    avatarViewerName: document.getElementById("avatarViewerName"),
    avatarViewerUid: document.getElementById("avatarViewerUid"),
    liveSheet: document.getElementById("liveSheet"),
    closeLiveBtn: document.getElementById("closeLiveBtn"),
    liveSheetMessage: document.getElementById("liveSheetMessage"),
    liveSheetSubtitle: document.getElementById("liveSheetSubtitle"),
    liveStatusTitle: document.getElementById("liveStatusTitle"),
    liveStatusMeta: document.getElementById("liveStatusMeta"),
    livePlayerShell: document.getElementById("livePlayerShell"),
    liveVideo: document.getElementById("liveVideo"),
    livePlaceholder: document.getElementById("livePlaceholder"),
    livePlaceholderText: document.getElementById("livePlaceholderText"),
    liveClosedBox: document.getElementById("liveClosedBox"),
    liveFullscreenBtn: document.getElementById("liveFullscreenBtn"),
    liveOwnerPanel: document.getElementById("liveOwnerPanel"),
    liveOwnerForm: document.getElementById("liveOwnerForm"),
    liveOwnerMeta: document.getElementById("liveOwnerMeta"),
    liveClosedToggle: document.getElementById("liveClosedToggle"),
    liveStreamUrlInput: document.getElementById("liveStreamUrlInput"),
    liveRotationSelect: document.getElementById("liveRotationSelect"),
    saveLiveSettingsBtn: document.getElementById("saveLiveSettingsBtn"),
    ownerPanelSheet: document.getElementById("ownerPanelSheet"),
    closeOwnerPanelBtn: document.getElementById("closeOwnerPanelBtn"),
    ownerStaffOnlyToggle: document.getElementById("ownerStaffOnlyToggle"),
    ownerStaffOnlyMeta: document.getElementById("ownerStaffOnlyMeta"),
    ownerAddAdminForm: document.getElementById("ownerAddAdminForm"),
    ownerAdminUidInput: document.getElementById("ownerAdminUidInput"),
    ownerAdminsList: document.getElementById("ownerAdminsList"),
    ownerShadowbanList: document.getElementById("ownerShadowbanList"),
    ownerStatsGrid: document.getElementById("ownerStatsGrid"),
    ownerUsersList: document.getElementById("ownerUsersList"),
    ownerAuditList: document.getElementById("ownerAuditList"),

    messageMenu: document.getElementById("messageMenu"),
    messageMenuReactions: document.getElementById("messageMenuReactions"),
    messageMenuActions: document.getElementById("messageMenuActions"),

    messageTpl: document.getElementById("messageTpl")
  };

  function init() {
    state.profile = loadOrCreateProfile();
    state.profileDraft = state.profile.display_name;
    state.seenMap = {};
    state.seenDirtyMap = {};

    el.messageInput.maxLength = MAX_MESSAGE_CHARS;
    el.fileInput.setAttribute("accept", mediaAcceptList());

    setupViewportSync();
    applyTheme(state.profile.theme, true);

    renderEmojiPicker();
    if (IS_TOUCH_DEVICE) {
      el.emojiBtn.classList.add("hidden");
      el.emojiPicker.classList.add("hidden");
    }

    bindEvents();
    renderProfile();
    renderTabs();
    renderPinnedBar();
    renderPendingFile();
    renderSupportLinks();
    renderLiveSheet();
    renderStaffOnlyBanner();
    setUploadProgress(false, 0, "0%");
    updateActionButton();

    initRealtimeTransport();
    fetchState(true, { includeSidebar: true });
  }

  function bindEvents() {
    el.profileBtn.addEventListener("click", openProfileSheet);
    el.closeProfileBtn.addEventListener("click", closeProfileSheet);
    if (el.closeAvatarViewerBtn) {
      el.closeAvatarViewerBtn.addEventListener("click", closeAvatarViewer);
    }
    if (el.liveBtnSidebar) {
      el.liveBtnSidebar.addEventListener("click", () => openSupportSheet("live", el.liveBtnSidebar));
    }
    if (el.ownerPanelBtnSidebar) {
      el.ownerPanelBtnSidebar.addEventListener("click", () => openOwnerPanel(el.ownerPanelBtnSidebar));
    }
    if (el.closeLiveBtn) {
      el.closeLiveBtn.addEventListener("click", () => closeSupportSheet("live"));
    }
    if (el.liveOwnerForm) {
      el.liveOwnerForm.addEventListener("submit", handleLiveOwnerSave);
    }
    if (el.liveClosedToggle) {
      el.liveClosedToggle.addEventListener("change", handleLiveOwnerDraftChange);
    }
    if (el.liveStreamUrlInput) {
      el.liveStreamUrlInput.addEventListener("input", handleLiveOwnerDraftChange);
    }
    if (el.liveRotationSelect) {
      el.liveRotationSelect.addEventListener("change", handleLiveOwnerDraftChange);
    }
    if (el.liveFullscreenBtn) {
      el.liveFullscreenBtn.addEventListener("click", openLiveFullscreen);
    }
    if (el.closeOwnerPanelBtn) {
      el.closeOwnerPanelBtn.addEventListener("click", closeOwnerPanel);
    }
    if (el.liveSheet) {
      el.liveSheet.addEventListener("click", (event) => {
        if (event.target === el.liveSheet) {
          closeSupportSheet("live");
        }
      });
    }
    if (el.avatarViewerSheet) {
      el.avatarViewerSheet.addEventListener("click", (event) => {
        if (event.target === el.avatarViewerSheet) {
          closeAvatarViewer();
        }
      });
    }
    if (el.ownerPanelSheet) {
      el.ownerPanelSheet.addEventListener("click", (event) => {
        if (event.target === el.ownerPanelSheet) {
          closeOwnerPanel();
        }
      });
    }
    if (el.ownerStaffOnlyToggle) {
      el.ownerStaffOnlyToggle.addEventListener("change", handleOwnerStaffOnlyToggle);
    }
    if (el.ownerAddAdminForm) {
      el.ownerAddAdminForm.addEventListener("submit", handleOwnerAddAdmin);
    }
    if (el.ownerAdminsList) {
      el.ownerAdminsList.addEventListener("click", handleOwnerPanelActionClick);
    }
    if (el.ownerShadowbanList) {
      el.ownerShadowbanList.addEventListener("click", handleOwnerPanelActionClick);
    }
    if (el.ownerUsersList) {
      el.ownerUsersList.addEventListener("click", handleOwnerPanelActionClick);
    }

    if (el.pinnedJumpBtn) {
      el.pinnedJumpBtn.addEventListener("click", () => {
        const messageId = String(state.pinnedMessage?.message?.id || "");
        if (!messageId) {
          return;
        }
        jumpToMessage(messageId);
      });
    }

    if (el.pinnedUnpinBtn) {
      el.pinnedUnpinBtn.addEventListener("click", async () => {
        if (!state.isOwner || !state.pinnedMessage) {
          return;
        }

        const ok = window.confirm("این پین برداشته شود؟");
        if (!ok) {
          return;
        }

        const result = await clearPinnedMessage();
        if (result !== undefined) {
          applyPinnedMessage(result);
        }
      });
    }

    el.profileSheet.addEventListener("click", (event) => {
      if (event.target === el.profileSheet) {
        closeProfileSheet();
      }
    });

    el.profileNameInput.addEventListener("input", () => {
      state.profileDraft = el.profileNameInput.value;
      state.profileEditing = true;
    });

    el.profileNameInput.addEventListener("blur", () => {
      state.profileDraft = el.profileNameInput.value;
    });

    el.profileAvatarInput.addEventListener("change", () => {
      const file = el.profileAvatarInput.files && el.profileAvatarInput.files[0];
      if (!file) {
        return;
      }

      if (!String(file.type || "").startsWith("image/")) {
        setHint("فقط فایل تصویری برای عکس پروفایل مجاز است.", true);
        el.profileAvatarInput.value = "";
        return;
      }
      if (String(file.type || "").toLowerCase() === "image/svg+xml") {
        setHint("فرمت SVG برای عکس پروفایل مجاز نیست.", true);
        el.profileAvatarInput.value = "";
        return;
      }

      if (file.size > MAX_PROFILE_AVATAR_BYTES) {
        setHint("حداکثر حجم عکس پروفایل 6MB است.", true);
        el.profileAvatarInput.value = "";
        return;
      }

      setPendingAvatar(file);
      setHint("عکس پروفایل آماده شد. تغییرات را ذخیره کن.");
    });

    el.saveProfileBtn.addEventListener("click", saveProfile);

    el.themeChoices.forEach((button) => {
      button.addEventListener("click", () => {
        const choice = normalizeTheme(button.getAttribute("data-theme-choice") || "light");
        applyTheme(choice, true);
        renderProfile();
        setHint(`تم به ${themeLabel(choice)} تغییر کرد.`);
      });
    });

    el.clearComposeBtn.addEventListener("click", clearComposeState);

    el.attachBtn.addEventListener("click", () => {
      if (state.actionBusy || composerLockedForNewMessages()) {
        if (composerLockedForNewMessages()) {
          setHint(STAFF_ONLY_NEW_MESSAGES_HINT, true);
        }
        return;
      }
      el.fileInput.click();
    });

    el.fileInput.addEventListener("change", () => {
      if (composerLockedForNewMessages()) {
        el.fileInput.value = "";
        setHint(STAFF_ONLY_NEW_MESSAGES_HINT, true);
        return;
      }

      const file = el.fileInput.files && el.fileInput.files[0];
      if (!file) {
        return;
      }

      const ok = setPendingFile(file);
      el.fileInput.value = "";
      if (ok) {
        setHint("فایل پیوست شد. برای ارسال، دکمه ارسال را بزن.");
      }
    });

    el.clearFileBtn.addEventListener("click", () => {
      clearPendingFile();
      setHint("فایل پیوست‌شده حذف شد.");
    });

    if (!IS_TOUCH_DEVICE) {
      el.emojiBtn.addEventListener("click", () => {
        el.emojiPicker.classList.toggle("hidden");
      });
    }

    el.messageInput.addEventListener("input", () => {
      markUserInteraction();
      updateActionButton();
      maybeSendTyping();
    });

    el.messageInput.addEventListener("keydown", (event) => {
      if (event.key !== "Enter" || event.shiftKey) {
        return;
      }

      event.preventDefault();
      runPrimaryAction();
    });

    el.composerForm.addEventListener("submit", (event) => {
      event.preventDefault();
      runPrimaryAction();
    });

    el.actionBtn.addEventListener("click", () => {
      runPrimaryAction();
    });

    const handleChannelTabsClick = (event) => {
      const btn = event.target.closest("button[data-type]");
      if (!btn) {
        return;
      }

      markUserInteraction();
      const nextChannel = btn.getAttribute("data-key") || "main";
      if (state.mode === "channel" && state.channel === nextChannel) {
        return;
      }

      syncCurrentConversationCache();

      state.mode = "channel";
      state.channel = nextChannel;

      resetConversationView({ restoreCache: true });
      closeMessageMenu();
      clearComposeState();
      clearPendingFile();
      renderTabs();
      renderDmUsers();
      fetchState(true);
    };

    if (el.channelTabs) {
      el.channelTabs.addEventListener("click", handleChannelTabsClick);
    }

    if (el.dmUsers) {
      el.dmUsers.addEventListener("click", (event) => {
        const btn = event.target.closest("button[data-peer]");
        if (!btn) {
          return;
        }

        openDirectWith(btn.getAttribute("data-peer") || "");
      });
    }

    el.chatFeed.addEventListener("click", (event) => {
      const reactTrigger = event.target.closest("[data-react-id][data-react-emoji]");
      if (reactTrigger) {
        const id = reactTrigger.getAttribute("data-react-id") || "";
        const emoji = reactTrigger.getAttribute("data-react-emoji") || "";
        if (id && emoji) {
          reactToMessage(id, emoji).then((result) => {
            if (result) {
              applyReactionLocal(result.message_id, result.reactions);
            }
          });
        }
        return;
      }

      const replyTargetTrigger = event.target.closest("[data-reply-target]");
      if (replyTargetTrigger) {
        const replyTargetId = replyTargetTrigger.getAttribute("data-reply-target") || "";
        if (replyTargetId) {
          jumpToMessage(replyTargetId);
        }
        return;
      }

      const trigger = event.target.closest("[data-open-dm]");
      if (!trigger) {
        return;
      }

      openDirectWith(trigger.getAttribute("data-open-dm") || "");
    });

    el.chatFeed.addEventListener("keydown", (event) => {
      const target = event.target instanceof Element ? event.target : null;
      if (!target || (event.key !== "Enter" && event.key !== " ")) {
        return;
      }

      const replyTarget = target.closest("[data-reply-target]");
      if (replyTarget) {
        event.preventDefault();
        jumpToMessage(replyTarget.getAttribute("data-reply-target") || "");
        return;
      }

      const dmTarget = target.closest("[data-open-dm]");
      if (dmTarget) {
        event.preventDefault();
        openDirectWith(dmTarget.getAttribute("data-open-dm") || "");
      }
    });

    el.chatFeed.addEventListener("contextmenu", (event) => {
      const messageNode = event.target.closest(".msg[data-id]");
      if (!messageNode) {
        return;
      }

      event.preventDefault();
      openMessageMenu(messageNode.dataset.id || "", event.clientX, event.clientY);
    });

    el.chatFeed.addEventListener("pointerdown", (event) => {
      const messageNode = event.target.closest(".msg[data-id]");
      if (!messageNode || isInteractiveMessageTarget(event.target)) {
        cancelLongPress();
        return;
      }

      cancelLongPress();
      state.contextMenu.pressStartX = event.clientX;
      state.contextMenu.pressStartY = event.clientY;
      state.contextMenu.pressTimer = window.setTimeout(() => {
        openMessageMenu(messageNode.dataset.id || "", event.clientX, event.clientY);
      }, 420);
    });

    el.chatFeed.addEventListener("pointermove", (event) => {
      if (!state.contextMenu.pressTimer) {
        return;
      }

      const moved = Math.abs(event.clientX - state.contextMenu.pressStartX) + Math.abs(event.clientY - state.contextMenu.pressStartY);
      if (moved > 16) {
        cancelLongPress();
      }
    });

    ["pointerup", "pointercancel", "pointerleave"].forEach((name) => {
      el.chatFeed.addEventListener(name, cancelLongPress);
    });

    el.chatFeed.addEventListener("scroll", () => {
      updateStickToBottom();
      if (state.contextMenu.messageId) {
        closeMessageMenu();
      }
    }, { passive: true });

    el.messageMenu.addEventListener("click", async (event) => {
      const target = event.target.closest("button[data-menu-action]");
      if (!target) {
        return;
      }

      const action = target.getAttribute("data-menu-action") || "";
      const messageId = target.getAttribute("data-id") || state.contextMenu.messageId;
      const emoji = target.getAttribute("data-emoji") || "";
      const message = state.messages.find((item) => String(item.id || "") === messageId);
      if (!message) {
        closeMessageMenu();
        return;
      }

      if (action === "remove_local") {
        closeMessageMenu();
        removeMessageLocal(message.id);
        return;
      }

      if (action === "react") {
        closeMessageMenu();
        const result = await reactToMessage(message.id, emoji);
        if (result) {
          applyReactionLocal(result.message_id, result.reactions);
        }
        return;
      }

      if (action === "reply") {
        closeMessageMenu();
        const preview = message.text || describeFileMessage(message) || "پیام";
        setComposeState("reply", message.id, preview);
        el.messageInput.focus();
        return;
      }

      if (action === "direct") {
        closeMessageMenu();
        openDirectWith(String(message.from_uid || ""));
        return;
      }

      if (action === "copy") {
        closeMessageMenu();
        const text = message.text || describeFileMessage(message) || "";
        if (!text) {
          return;
        }

        await copyText(text);
        return;
      }

      if (action === "copy_uid") {
        closeMessageMenu();
        const uid = sanitizeUid(String(message.from_uid || ""));
        if (!uid) {
          return;
        }

        await copyText(uid);
        return;
      }

      if (action === "pin_toggle") {
        closeMessageMenu();
        if (!state.isOwner || message._localStatus || message.deleted_at) {
          return;
        }

        const isPinned = String(state.pinnedMessage?.message?.id || "") === String(message.id || "");
        const ok = window.confirm(isPinned ? "این پین برداشته شود؟" : "این پیام پین شود؟");
        if (!ok) {
          return;
        }

        const result = isPinned
          ? await clearPinnedMessage(message.id)
          : await pinMessage(message.id);
        if (result !== undefined) {
          applyPinnedMessage(result);
        }
        return;
      }

      if (action === "edit") {
        closeMessageMenu();
        if (String(message.from_uid) !== state.profile.uid || message.deleted_at) {
          return;
        }

        setComposeState("edit", message.id, "ویرایش پیام");
        el.messageInput.value = message.text || "";
        el.messageInput.focus();
        updateActionButton();
        return;
      }

      if (action === "admin_delete") {
        closeMessageMenu();
        if (!state.isAdmin || message._localStatus) {
          return;
        }

        const ok = window.confirm("این پیام به‌صورت کامل برای همه حذف شود؟");
        if (!ok) {
          return;
        }

        const deleted = await deleteMessage(message.id, { hard: true });
        applyDeleteMessageResult(deleted, message.id);
        return;
      }

      if (action === "admin_delete_user_messages") {
        closeMessageMenu();
        const targetUid = sanitizeUid(String(message.from_uid || ""));
        if (!state.isAdmin || !targetUid || targetUid === state.profile.uid) {
          return;
        }

        const ok = window.confirm("تمام پیام‌های این کاربر برای همه حذف شود؟");
        if (!ok) {
          return;
        }

        const result = await deleteAllMessagesByUser(targetUid);
        if (!result) {
          return;
        }

        removeMessagesByUserLocal(targetUid);
        applyPinnedMessage(result.pinnedMessage ?? null);
        fetchSidebar(true, { includeUsers: false });
        return;
      }

      if (action === "shadowban_toggle") {
        closeMessageMenu();
        const targetUid = sanitizeUid(String(message.from_uid || ""));
        if (!state.isAdmin || !targetUid || targetUid === state.profile.uid) {
          return;
        }

        const nextShadowbanned = !isUserShadowbanned(targetUid);
        const prompt = nextShadowbanned
          ? "این کاربر شادوبن شود؟"
          : "شادوبن این کاربر برداشته شود؟";
        if (!window.confirm(prompt)) {
          return;
        }

        const result = await toggleShadowban(targetUid, nextShadowbanned);
        if (!result) {
          return;
        }

        setUserShadowbanLocal(targetUid, result.shadowbanned);
        renderDmUsers();
        return;
      }

      if (action === "delete") {
        closeMessageMenu();
        if (String(message.from_uid) !== state.profile.uid) {
          return;
        }

        const ok = window.confirm("این پیام حذف شود؟");
        if (!ok) {
          return;
        }

        const deleted = await deleteMessage(message.id, { hard: Boolean(state.isAdmin) });
        applyDeleteMessageResult(deleted, message.id);
      }
    });

    document.addEventListener("click", (event) => {
      const target = event.target instanceof Element ? event.target : null;

      const avatarNode = target ? target.closest('.avatar[data-avatar-viewable="1"]') : null;
      if (avatarNode instanceof HTMLElement) {
        const interactiveParent = avatarNode.closest("button, a[href]");
        if (interactiveParent && interactiveParent !== avatarNode) {
          return;
        }
        event.preventDefault();
        event.stopPropagation();
        openAvatarViewer(avatarViewerPayloadFromNode(avatarNode), avatarNode);
        return;
      }

      if (!IS_TOUCH_DEVICE) {
        const insideEmoji = Boolean(target) && !el.emojiPicker.classList.contains("hidden") && el.emojiPicker.contains(target);
        const onEmojiButton = Boolean(target) && (target === el.emojiBtn || target.closest("#emojiBtn"));
        if (!insideEmoji && !onEmojiButton) {
          el.emojiPicker.classList.add("hidden");
        }
      }

      if (!target || !el.messageMenu.contains(target)) {
        closeMessageMenu();
      }
    });

    window.addEventListener("resize", () => {
      closeMessageMenu();
      updateLiveVideoTransform();
      if (state.stickToBottom) {
        scrollToBottom({ force: true });
      }
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Tab" && state.support.activeSheet) {
        trapSheetFocus(state.support.activeSheet, event);
      }

      const avatarNode = event.target instanceof Element ? event.target.closest('.avatar[data-avatar-viewable="1"]') : null;
      if (avatarNode instanceof HTMLElement && (event.key === "Enter" || event.key === " ")) {
        const interactiveParent = avatarNode.closest("button, a[href]");
        if (interactiveParent && interactiveParent !== avatarNode) {
          return;
        }
        event.preventDefault();
        openAvatarViewer(avatarViewerPayloadFromNode(avatarNode), avatarNode);
        return;
      }

      if (event.key === "Escape") {
        closeMessageMenu();
        el.emojiPicker.classList.add("hidden");
        if (state.support.avatarViewerOpen) {
          closeAvatarViewer();
        }
        if (state.support.liveOpen) {
          closeSupportSheet("live");
        }
        if (state.support.ownerPanelOpen) {
          closeOwnerPanel();
        }
      }
    });

    document.addEventListener("visibilitychange", () => {
      if (!document.hidden) {
        markUserInteraction();
        scheduleRealtimeRefresh(60);
        pollServerTick();
      }
    });

    window.addEventListener("beforeunload", () => {
      closeRealtimeTransport();
    });

    ["pointerdown", "keydown", "touchstart"].forEach((eventName) => {
      document.addEventListener(eventName, markUserInteraction);
      document.addEventListener(eventName, unlockAudio);
    });
  }

  function setupViewportSync() {
    let rafId = 0;

    const applyHeight = () => {
      const h = window.visualViewport ? window.visualViewport.height : window.innerHeight;
      const rounded = Math.max(320, Math.round(h));
      document.documentElement.style.setProperty("--app-height", `${rounded}px`);
    };

    const queueApplyHeight = () => {
      if (rafId) {
        return;
      }

      rafId = window.requestAnimationFrame(() => {
        rafId = 0;
        applyHeight();
      });
    };

    queueApplyHeight();
    window.addEventListener("resize", queueApplyHeight, { passive: true });

    if (window.visualViewport) {
      window.visualViewport.addEventListener("resize", queueApplyHeight, { passive: true });
    }
  }

  function createDefaultLiveConfig() {
    return {
      enabled: DEFAULT_LIVE_ENABLED,
      stream_url: DEFAULT_LIVE_STREAM_URL,
      has_stream_url: DEFAULT_LIVE_STREAM_URL !== "",
      rotation: DEFAULT_LIVE_ROTATION,
      updated_at: 0,
      updated_by: ""
    };
  }

  function normalizeLiveRotation(rotation) {
    const value = Number(rotation);
    return LIVE_ROTATIONS.includes(value) ? value : 0;
  }

  function normalizeLiveConfigPayload(payload) {
    const current = state.support?.liveConfig || createDefaultLiveConfig();
    const source = payload && typeof payload === "object" ? payload : {};
    const hasStreamUrl = Boolean(source.has_stream_url);
    const nextUrl = String(source.stream_url || "").trim();
    const preservedOwnerUrl = state.isOwner && hasStreamUrl ? String(current.stream_url || "") : "";

    return {
      enabled: Boolean(source.enabled),
      stream_url: nextUrl || preservedOwnerUrl,
      has_stream_url: hasStreamUrl || nextUrl !== "" || preservedOwnerUrl !== "",
      rotation: normalizeLiveRotation(source.rotation || 0),
      updated_at: Math.max(0, Number(source.updated_at || 0)),
      updated_by: sanitizeUid(String(source.updated_by || ""))
    };
  }

  function syncLiveFormFromConfig(force = false) {
    if (!state.isOwner) {
      return;
    }
    if (!force && state.support.liveFormDirty) {
      return;
    }

    const live = state.support.liveConfig || createDefaultLiveConfig();
    state.support.liveFormClosed = !Boolean(live.enabled);
    state.support.liveFormStreamUrl = String(live.stream_url || DEFAULT_LIVE_STREAM_URL);
    state.support.liveFormRotation = normalizeLiveRotation(live.rotation);
    state.support.liveFormDirty = false;

    if (el.liveClosedToggle) {
      el.liveClosedToggle.checked = state.support.liveFormClosed;
    }
    if (el.liveStreamUrlInput) {
      el.liveStreamUrlInput.value = state.support.liveFormStreamUrl;
    }
    if (el.liveRotationSelect) {
      el.liveRotationSelect.value = String(state.support.liveFormRotation);
    }
  }

  function liveStreamUrl() {
    return String(state.support.liveConfig?.stream_url || "").trim();
  }

  function livePlaybackAllowed() {
    return state.support.liveOpen && Boolean(state.support.liveConfig?.enabled) && liveStreamUrl() !== "";
  }

  function cleanupLiveVideoListeners() {
    if (typeof state.support.liveVideoCleanup === "function") {
      try {
        state.support.liveVideoCleanup();
      } catch (_) {
        // ignore
      }
    }
    state.support.liveVideoCleanup = null;
  }

  function loadLiveHlsScript() {
    if (window.Hls) {
      return Promise.resolve(window.Hls);
    }
    if (!HLS_SCRIPT_URL) {
      return Promise.resolve(null);
    }
    if (state.support.liveHlsScriptPromise) {
      return state.support.liveHlsScriptPromise;
    }

    state.support.liveHlsScriptPromise = new Promise((resolve, reject) => {
      const script = document.createElement("script");
      script.src = HLS_SCRIPT_URL;
      script.async = true;
      script.onload = () => resolve(window.Hls || null);
      script.onerror = () => {
        state.support.liveHlsScriptPromise = null;
        reject(new Error("فایل پخش HLS از روی سایت لود نشد."));
      };
      document.head.appendChild(script);
    });

    return state.support.liveHlsScriptPromise;
  }

  function teardownLivePlayer(options = {}) {
    const keepError = options.keepError === true;

    state.support.liveSetupToken += 1;
    cleanupLiveVideoListeners();

    if (state.support.liveHls && typeof state.support.liveHls.destroy === "function") {
      try {
        state.support.liveHls.destroy();
      } catch (_) {
        // ignore
      }
    }
    state.support.liveHls = null;

    if (el.liveVideo instanceof HTMLVideoElement) {
      try {
        el.liveVideo.pause();
      } catch (_) {
        // ignore
      }
      el.liveVideo.style.removeProperty("--live-video-transform");
      el.liveVideo.removeAttribute("src");
      el.liveVideo.load();
    }

    state.support.livePlaybackReady = false;
    state.support.livePlaybackSource = "";
    if (!keepError) {
      state.support.livePlaybackError = "";
    }
  }

  async function syncLivePlayerState() {
    if (!el.liveVideo) {
      return;
    }

    const shouldPlay = livePlaybackAllowed();
    if (!shouldPlay) {
      teardownLivePlayer();
      renderLiveSheet();
      return;
    }

    const url = liveStreamUrl();
    const sourceKey = url;
    if (state.support.livePlaybackSource === sourceKey && (state.support.livePlaybackReady || !state.support.livePlaybackError)) {
      return;
    }

    teardownLivePlayer();
    const token = state.support.liveSetupToken;
    state.support.livePlaybackSource = sourceKey;
    state.support.livePlaybackReady = false;
    state.support.livePlaybackError = "";
    renderLiveSheet();

    const markReady = () => {
      if (token !== state.support.liveSetupToken || state.support.livePlaybackSource !== sourceKey) {
        return;
      }
      state.support.livePlaybackReady = true;
      state.support.livePlaybackError = "";
      renderLiveSheet();
    };

    const failPlayback = (message) => {
      if (token !== state.support.liveSetupToken || state.support.livePlaybackSource !== sourceKey) {
        return;
      }
      state.support.livePlaybackReady = false;
      state.support.livePlaybackError = message || "پخش لایو شروع نشد.";
      teardownLivePlayer({ keepError: true });
      renderLiveSheet();
    };

    const video = el.liveVideo;
    const cleanupFns = [];
    const addVideoListener = (type, handler, options = { once: true }) => {
      video.addEventListener(type, handler, options);
      cleanupFns.push(() => video.removeEventListener(type, handler, options));
    };
    state.support.liveVideoCleanup = () => {
      while (cleanupFns.length) {
        const cleanup = cleanupFns.pop();
        try {
          cleanup();
        } catch (_) {
          // ignore
        }
      }
    };

    video.controls = true;
    video.playsInline = true;
    video.preload = "metadata";
    addVideoListener("loadedmetadata", () => {
      updateLiveVideoTransform();
      markReady();
    });
    addVideoListener("canplay", () => {
      updateLiveVideoTransform();
      markReady();
    });
    addVideoListener("error", () => {
      failPlayback("مرورگر نتوانست لایو را با این لینک پخش کند.");
    });

    try {
      const canPlayNative = video.canPlayType("application/vnd.apple.mpegurl") || video.canPlayType("application/x-mpegURL");

      if (canPlayNative) {
        video.src = url;
        video.load();
        video.play().catch(() => {});
        return;
      }

      const HlsCtor = window.Hls || await loadLiveHlsScript();
      if (token !== state.support.liveSetupToken) {
        return;
      }
      if (!HlsCtor || typeof HlsCtor.isSupported !== "function" || !HlsCtor.isSupported()) {
        failPlayback("این مرورگر امکان پخش HLS را ندارد یا فایل پلیر لود نشد.");
        return;
      }

      const hls = new HlsCtor({
        enableWorker: false,
        lowLatencyMode: true,
        manifestLoadingTimeOut: 8000,
        levelLoadingTimeOut: 8000,
        fragLoadingTimeOut: 10000
      });
      state.support.liveHls = hls;
      hls.attachMedia(video);
      hls.on(HlsCtor.Events.MEDIA_ATTACHED, () => {
        if (token !== state.support.liveSetupToken) {
          return;
        }
        hls.loadSource(url);
      });
      hls.on(HlsCtor.Events.MANIFEST_PARSED, () => {
        markReady();
        video.play().catch(() => {});
      });
      hls.on(HlsCtor.Events.ERROR, (_event, data) => {
        if (!data?.fatal) {
          return;
        }
        const details = String(data.details || data.type || "");
        const lowerDetails = details.toLowerCase();
        const message = lowerDetails.includes("timeout")
          ? "اتصال به استریم در زمان مجاز انجام نشد. ممکن است سرور HLS کند، مسدود یا خارج از دسترس باشد."
          : lowerDetails.includes("manifest")
          ? "پلیر نتوانست playlist لایو را بگیرد. لینک، CORS یا خود استریم را چک کن."
          : "لایو باز نشد. ممکن است لینک، CORS یا خود استریم مشکل داشته باشد.";
        failPlayback(message);
      });
    } catch (error) {
      failPlayback(error?.message || "راه‌اندازی پلیر لایو ناموفق بود.");
    }
  }

  function renderLiveSheet() {
    const live = state.support.liveConfig || createDefaultLiveConfig();
    const isLiveOpen = Boolean(live.enabled);
    const streamUrl = String(live.stream_url || "").trim();
    const hasStreamUrl = streamUrl !== "";
    const isPlayable = isLiveOpen && hasStreamUrl;
    const hasPlaybackError = Boolean(state.support.livePlaybackError);
    const showClosedMessage = !isLiveOpen && !state.isOwner;
    const showPlaceholder = !isPlayable || !state.support.livePlaybackReady || hasPlaybackError;

    if (el.liveSheetMessage) {
      el.liveSheetMessage.textContent = DEFAULT_LIVE_MESSAGE;
    }

    if (el.liveSheetSubtitle) {
      el.liveSheetSubtitle.textContent = state.isOwner
        ? "مدیریت لایو و پخش از همین پنجره انجام می‌شود."
        : (showClosedMessage
          ? "اگر لایو باز شود، همین‌جا به پلیر تبدیل می‌شود."
          : "وقتی لایو باز باشد، ویدیو از همین باکس با قابلیت فول اسکرین پخش می‌شود.");
    }

    if (el.liveStatusTitle) {
      if (!isLiveOpen) {
        el.liveStatusTitle.textContent = state.isOwner ? "لایو خاموش است" : "لایو بسته است";
      } else if (!hasStreamUrl) {
        el.liveStatusTitle.textContent = "لایو باز است اما لینک ثبت نشده";
      } else if (hasPlaybackError) {
        el.liveStatusTitle.textContent = "پخش لایو با خطا روبه‌رو شد";
      } else if (!state.support.livePlaybackReady) {
        el.liveStatusTitle.textContent = "در حال اتصال به لایو";
      } else {
        el.liveStatusTitle.textContent = "لایو در حال پخش است";
      }
    }

    if (el.liveStatusMeta) {
      if (!isLiveOpen) {
        el.liveStatusMeta.textContent = state.isOwner
          ? "لینک را وارد کن و بعد لایو را باز کن."
          : "لایو در حال حاضر برای کاربران بسته است.";
      } else if (!hasStreamUrl) {
        el.liveStatusMeta.textContent = "اونر هنوز لینک پخش را وارد نکرده است.";
      } else if (hasPlaybackError) {
        el.liveStatusMeta.textContent = state.support.livePlaybackError;
      } else if (!state.support.livePlaybackReady) {
        el.liveStatusMeta.textContent = "در حال آماده‌سازی پلیر و اتصال به استریم...";
      } else {
        el.liveStatusMeta.textContent = "از کنترل‌های خود ویدیو می‌توانی برای پخش و فول اسکرین هم استفاده کنی.";
      }
    }

    if (el.liveClosedBox) {
      el.liveClosedBox.classList.toggle("hidden", !showClosedMessage);
    }

    if (el.livePlayerShell) {
      el.livePlayerShell.dataset.rotation = String(normalizeLiveRotation(live.rotation));
    }

    if (el.liveVideo instanceof HTMLVideoElement) {
      el.liveVideo.classList.toggle("hidden", !isPlayable);
    }

    if (el.livePlaceholder) {
      el.livePlaceholder.classList.toggle("hidden", !showPlaceholder);
    }

    if (el.livePlaceholderText) {
      if (!isLiveOpen) {
        el.livePlaceholderText.textContent = state.isOwner
          ? "لایو خاموش است. لینک را ثبت کن و در صورت نیاز آن را باز کن."
          : "لایو فعلاً بسته است.";
      } else if (!hasStreamUrl) {
        el.livePlaceholderText.textContent = "لایو فعال شده ولی هنوز لینک استریم ثبت نشده است.";
      } else if (hasPlaybackError) {
        el.livePlaceholderText.textContent = state.support.livePlaybackError;
      } else {
        el.livePlaceholderText.textContent = "در حال اتصال به استریم زنده...";
      }
    }

    if (el.liveFullscreenBtn) {
      el.liveFullscreenBtn.classList.toggle("hidden", !isPlayable);
      el.liveFullscreenBtn.disabled = !isPlayable;
    }

    if (el.liveOwnerPanel) {
      el.liveOwnerPanel.classList.toggle("hidden", !state.isOwner);
    }

    if (!state.isOwner) {
      state.support.liveFormDirty = false;
    } else {
      syncLiveFormFromConfig();
      if (el.liveClosedToggle) {
        el.liveClosedToggle.disabled = state.support.liveSaving;
      }
      if (el.liveStreamUrlInput) {
        el.liveStreamUrlInput.disabled = state.support.liveSaving;
      }
      if (el.liveRotationSelect) {
        el.liveRotationSelect.disabled = state.support.liveSaving;
      }
      if (el.saveLiveSettingsBtn) {
        el.saveLiveSettingsBtn.disabled = state.support.liveSaving;
        el.saveLiveSettingsBtn.textContent = state.support.liveSaving ? "در حال ذخیره..." : "ذخیره تنظیمات لایو";
      }
      if (el.liveOwnerMeta) {
        const updater = live.updated_by ? ` · توسط ${live.updated_by}` : "";
        el.liveOwnerMeta.textContent = live.updated_at
          ? `آخرین تغییر ${formatRelativeTime(live.updated_at)}${updater}`
          : "فقط اونر می‌تواند وضعیت، لینک و چرخش لایو را تغییر دهد.";
      }
    }

    updateLiveVideoTransform();
  }

  function updateLiveVideoTransform() {
    if (!(el.liveVideo instanceof HTMLVideoElement) || !(el.livePlayerShell instanceof HTMLElement)) {
      return;
    }

    const rotation = normalizeLiveRotation(el.livePlayerShell.dataset.rotation || 0);
    const baseRotation = rotation === 0 ? "rotate(0deg)" : `rotate(${rotation}deg)`;
    if (rotation !== 90 && rotation !== 270) {
      el.liveVideo.style.setProperty("--live-video-transform", baseRotation);
      return;
    }

    const frame = el.livePlayerShell.querySelector(".live-player-frame");
    if (!(frame instanceof HTMLElement)) {
      el.liveVideo.style.setProperty("--live-video-transform", baseRotation);
      return;
    }

    const videoWidth = Math.max(0, Number(el.liveVideo.videoWidth) || 0);
    const videoHeight = Math.max(0, Number(el.liveVideo.videoHeight) || 0);
    const frameWidth = Math.max(0, frame.clientWidth || 0);
    const frameHeight = Math.max(0, frame.clientHeight || 0);
    if (!videoWidth || !videoHeight || !frameWidth || !frameHeight) {
      el.liveVideo.style.setProperty("--live-video-transform", baseRotation);
      return;
    }

    const fitScale = Math.min(frameWidth / videoHeight, frameHeight / videoWidth);
    const safeScale = Number.isFinite(fitScale) && fitScale > 0 ? Math.min(1, fitScale) : 1;
    el.liveVideo.style.setProperty("--live-video-transform", `${baseRotation} scale(${safeScale})`);
  }

  function applyLiveConfigLocal(payload, options = {}) {
    if (!payload || typeof payload !== "object") {
      return false;
    }

    state.support.liveConfig = normalizeLiveConfigPayload(payload);
    if (state.isOwner && (options.forceFormSync || !state.support.liveFormDirty)) {
      syncLiveFormFromConfig(Boolean(options.forceFormSync));
    }
    renderSupportLinks();
    renderLiveSheet();
    if (state.support.liveOpen) {
      syncLivePlayerState();
    }
    return true;
  }

  function handleLiveOwnerDraftChange() {
    if (!state.isOwner) {
      return;
    }

    state.support.liveFormClosed = Boolean(el.liveClosedToggle?.checked);
    state.support.liveFormStreamUrl = String(el.liveStreamUrlInput?.value || "").trim();
    state.support.liveFormRotation = normalizeLiveRotation(el.liveRotationSelect?.value || 0);
    state.support.liveFormDirty = true;
    renderLiveSheet();
  }

  async function handleLiveOwnerSave(event) {
    event.preventDefault();
    if (!state.isOwner || state.support.liveSaving) {
      if (!state.support.liveSaving) {
        setHint("برای ذخیره لایو باید با UID اونر وارد شده باشی.", true);
      }
      return;
    }

    state.support.liveFormClosed = Boolean(el.liveClosedToggle?.checked);
    state.support.liveFormStreamUrl = String(el.liveStreamUrlInput?.value || "").trim();
    state.support.liveFormRotation = normalizeLiveRotation(el.liveRotationSelect?.value || 0);

    const streamUrl = String(state.support.liveFormStreamUrl || "").trim();
    if (streamUrl && !/^https?:\/\//i.test(streamUrl)) {
      setHint("لینک لایو باید با http:// یا https:// شروع شود.", true);
      return;
    }

    state.support.liveSaving = true;
    renderLiveSheet();

    const formData = new FormData();
    baseIdentity(formData);
    formData.set("action", "owner_live");
    formData.set("closed", state.support.liveFormClosed ? "1" : "0");
    formData.set("stream_url", streamUrl);
    formData.set("rotation", String(normalizeLiveRotation(state.support.liveFormRotation)));
    const controller = typeof AbortController === "function" ? new AbortController() : null;
    const timeoutId = controller ? window.setTimeout(() => controller.abort(), 12000) : 0;

    try {
      const res = await fetch("api.php", {
        method: "POST",
        body: formData,
        signal: controller?.signal
      });
      const data = await readJsonResponse(res, "ذخیره تنظیمات لایو ناموفق بود.");
      if (!data.ok) {
        throw new Error(data.error || "ذخیره تنظیمات لایو ناموفق بود.");
      }

      if (data.me && typeof data.me === "object") {
        state.role = normalizeRole(String(data.me.role || state.role || "user"));
        state.isOwner = Boolean(data.me.is_owner);
        state.isAdmin = Boolean(data.me.is_admin);
        state.canSendNewMessages = data.me.can_send_new_messages !== false;
        state.staffOnlyNewMessages = Boolean(data.me.staff_only_new_messages);
      }

      if (data.live && typeof data.live === "object") {
        applyLiveConfigLocal(data.live, { forceFormSync: true });
      }
      setHint("تنظیمات لایو ذخیره شد.");
    } catch (error) {
      const message = error?.name === "AbortError"
        ? "ذخیره لایو بیشتر از حد طول کشید و متوقف شد."
        : (error.message || "ذخیره تنظیمات لایو ناموفق بود.");
      setHint(message, true);
    } finally {
      if (timeoutId) {
        window.clearTimeout(timeoutId);
      }
      state.support.liveSaving = false;
      renderLiveSheet();
    }
  }

  function openLiveFullscreen() {
    if (!livePlaybackAllowed()) {
      return;
    }

    const target = el.livePlayerShell || el.liveVideo;
    if (target && typeof target.requestFullscreen === "function") {
      target.requestFullscreen().catch(() => {});
      return;
    }
    if (el.liveVideo && typeof el.liveVideo.webkitEnterFullscreen === "function") {
      el.liveVideo.webkitEnterFullscreen();
    }
  }

  function renderSupportLinks() {
    const donateUrl = SUPPORT_CONFIG.donateUrl || "https://trzw.ir/donate/";
    const uploadUrl = SUPPORT_CONFIG.uploadUrl || "https://trzw.ir/up";

    [el.donateBtnSidebar, el.donateBtnModal].forEach((node) => {
      if (!(node instanceof HTMLAnchorElement)) {
        return;
      }
      node.href = donateUrl;
      node.textContent = "حمایت مالی";
    });

    if (el.uploadBtnSidebar instanceof HTMLAnchorElement) {
      el.uploadBtnSidebar.href = uploadUrl;
      el.uploadBtnSidebar.textContent = "آپلود سنتر";
    }

    if (el.liveBtnSidebar instanceof HTMLButtonElement) {
      const liveOpen = Boolean(state.support.liveConfig?.enabled);
      el.liveBtnSidebar.textContent = liveOpen ? "لایو استریم • روشن" : "لایو استریم";
      el.liveBtnSidebar.classList.toggle("is-active", liveOpen);
    }

    if (el.liveSheetMessage) {
      el.liveSheetMessage.textContent = DEFAULT_LIVE_MESSAGE;
    }

    if (el.ownerPanelBtnSidebar) {
      el.ownerPanelBtnSidebar.classList.toggle("hidden", !state.isOwner);
    }

    if (!state.isOwner && state.support.ownerPanelOpen) {
      closeOwnerPanel();
    }
  }

  function renderStaffOnlyBanner() {
    if (!el.staffOnlyBanner) {
      return;
    }

    const showBanner = Boolean(state.staffOnlyNewMessages);
    if (!showBanner) {
      el.staffOnlyBanner.classList.add("hidden");
      el.staffOnlyBanner.textContent = "";
      return;
    }

    el.staffOnlyBanner.textContent = state.canSendNewMessages
      ? "حالت استاف فعال است. در حال حاضر فقط اعضای استاف می‌توانند پیام جدید ارسال کنند."
      : "چت فعلاً در حالت استاف است و ارسال پیام جدید فقط برای staff / admin / owner فعال است.";
    el.staffOnlyBanner.classList.remove("hidden");
  }

  function focusableElements(container) {
    if (!(container instanceof HTMLElement)) {
      return [];
    }

    return Array.from(container.querySelectorAll(
      'button:not([disabled]), a[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
    )).filter((node) => node instanceof HTMLElement && !node.closest(".hidden"));
  }

  function focusSheet(sheet) {
    const focusables = focusableElements(sheet);
    const first = focusables[0];
    if (first) {
      first.focus();
    } else if (sheet instanceof HTMLElement) {
      sheet.focus();
    }
  }

  function sheetElementByName(sheetName) {
    if (sheetName === "owner") {
      return el.ownerPanelSheet;
    }
    if (sheetName === "avatar") {
      return el.avatarViewerSheet;
    }
    return el.liveSheet;
  }

  function trapSheetFocus(sheetName, event) {
    const sheet = sheetElementByName(sheetName);
    if (!(sheet instanceof HTMLElement) || sheet.classList.contains("hidden")) {
      return;
    }

    const focusables = focusableElements(sheet);
    if (!focusables.length) {
      event.preventDefault();
      sheet.focus();
      return;
    }

    const first = focusables[0];
    const last = focusables[focusables.length - 1];
    const active = document.activeElement;

    if (event.shiftKey && active === first) {
      event.preventDefault();
      last.focus();
      return;
    }

    if (!event.shiftKey && active === last) {
      event.preventDefault();
      first.focus();
    }
  }

  function avatarViewerPayloadFromNode(node) {
    if (!(node instanceof HTMLElement)) {
      return null;
    }

    const name = sanitizeName(String(node.dataset.avatarName || "")) || "کاربر";
    const uid = sanitizeUid(String(node.dataset.avatarUid || ""));
    return {
      uid,
      name,
      seed: String(node.dataset.avatarSeed || avatarSeedFromUid(uid || name)),
      url: String(node.dataset.avatarUrl || ""),
      updatedAt: Math.max(0, Number(node.dataset.avatarUpdatedAt || 0)),
      role: normalizeRole(String(node.dataset.avatarRole || "user"))
    };
  }

  function renderAvatarViewer() {
    const data = state.support.avatarViewerData;
    if (!data || !el.avatarViewerAvatar || !el.avatarViewerName || !el.avatarViewerUid) {
      return;
    }

    const safeName = sanitizeName(String(data.name || "")) || "کاربر";
    const safeUid = sanitizeUid(String(data.uid || ""));
    const safeRole = normalizeRole(String(data.role || "user"));
    setAvatar(
      el.avatarViewerAvatar,
      String(data.seed || avatarSeedFromUid(safeUid || safeName)),
      safeName,
      safeUid,
      String(data.url || ""),
      Math.max(0, Number(data.updatedAt) || 0),
      { eager: true, role: safeRole }
    );
    el.avatarViewerName.innerHTML = renderLabelWithBadge(safeName, safeRole, "profile-title");
    el.avatarViewerUid.textContent = safeUid || "";
    el.avatarViewerUid.classList.toggle("hidden", !safeUid);
  }

  function openAvatarViewer(payload, trigger = null) {
    if (!payload || !el.avatarViewerSheet) {
      return;
    }

    state.support.focusRestoreEl = trigger instanceof HTMLElement ? trigger : document.activeElement;
    state.support.avatarViewerOpen = true;
    state.support.avatarViewerData = payload;
    state.support.activeSheet = "avatar";
    renderAvatarViewer();
    el.avatarViewerSheet.classList.remove("hidden");
    el.avatarViewerSheet.setAttribute("aria-hidden", "false");
    document.body.classList.add("sheet-open");
    window.requestAnimationFrame(() => focusSheet(el.avatarViewerSheet));
  }

  function closeAvatarViewer() {
    if (!el.avatarViewerSheet) {
      return;
    }

    state.support.avatarViewerOpen = false;
    state.support.avatarViewerData = null;
    if (state.support.activeSheet === "avatar") {
      state.support.activeSheet = state.support.ownerPanelOpen
        ? "owner"
        : (state.support.liveOpen ? "live" : null);
    }
    el.avatarViewerSheet.classList.add("hidden");
    el.avatarViewerSheet.setAttribute("aria-hidden", "true");
    if (!state.support.ownerPanelOpen && !state.support.liveOpen) {
      document.body.classList.remove("sheet-open");
    }
    restoreSupportFocus();
  }

  function openSupportSheet(sheetName, trigger = null) {
    if (sheetName !== "live" || !el.liveSheet) {
      return;
    }

    state.support.focusRestoreEl = trigger instanceof HTMLElement ? trigger : document.activeElement;
    state.support.liveOpen = true;
    state.support.activeSheet = "live";
    if (state.isOwner) {
      syncLiveFormFromConfig(true);
    }
    el.liveSheet.classList.remove("hidden");
    el.liveSheet.setAttribute("aria-hidden", "false");
    document.body.classList.add("sheet-open");
    renderLiveSheet();
    syncLivePlayerState();
    window.requestAnimationFrame(() => focusSheet(el.liveSheet));
  }

  function closeSupportSheet(sheetName) {
    if (sheetName !== "live" || !el.liveSheet) {
      return;
    }

    state.support.liveOpen = false;
    if (state.support.activeSheet === "live") {
      state.support.activeSheet = state.support.ownerPanelOpen ? "owner" : null;
    }
    teardownLivePlayer();
    el.liveSheet.classList.add("hidden");
    el.liveSheet.setAttribute("aria-hidden", "true");
    if (!state.support.ownerPanelOpen && !state.support.avatarViewerOpen) {
      document.body.classList.remove("sheet-open");
    }
    renderLiveSheet();
    restoreSupportFocus();
  }

  function restoreSupportFocus() {
    const target = state.support.focusRestoreEl;
    state.support.focusRestoreEl = null;
    if (target instanceof HTMLElement) {
      window.requestAnimationFrame(() => target.focus());
    }
  }

  function openOwnerPanel(trigger = null) {
    if (!state.isOwner || !el.ownerPanelSheet) {
      return;
    }

    state.support.focusRestoreEl = trigger instanceof HTMLElement ? trigger : document.activeElement;
    state.support.ownerPanelOpen = true;
    state.support.activeSheet = "owner";
    el.ownerPanelSheet.classList.remove("hidden");
    el.ownerPanelSheet.setAttribute("aria-hidden", "false");
    document.body.classList.add("sheet-open");
    renderOwnerPanel();
    window.requestAnimationFrame(() => focusSheet(el.ownerPanelSheet));
    fetchOwnerPanel(true);
  }

  function closeOwnerPanel() {
    if (!el.ownerPanelSheet) {
      return;
    }

    state.support.ownerPanelOpen = false;
    if (state.support.activeSheet === "owner") {
      state.support.activeSheet = state.support.liveOpen ? "live" : null;
    }
    el.ownerPanelSheet.classList.add("hidden");
    el.ownerPanelSheet.setAttribute("aria-hidden", "true");
    if (!state.support.liveOpen && !state.support.avatarViewerOpen) {
      document.body.classList.remove("sheet-open");
    }
    restoreSupportFocus();
  }

  async function fetchOwnerPanel(force = false) {
    if (!state.isOwner) {
      return false;
    }
    if (state.support.ownerPanelLoading && !force) {
      return false;
    }

    state.support.ownerPanelLoading = true;
    renderOwnerPanel();

    const params = new URLSearchParams();
    params.set("action", "owner_panel");
    params.set("uid", state.profile.uid);
    params.set("display_name", state.profile.display_name);
    params.set("theme", state.profile.theme);

    try {
      const res = await fetch(`api.php?${params.toString()}`, { cache: "no-store" });
      const data = await readJsonResponse(res, "خطا در دریافت نوار کناری.");
      if (!data.ok) {
        throw new Error(data.error || "دریافت پنل اونر ناموفق بود.");
      }

      state.support.ownerData = data.panel && typeof data.panel === "object" ? data.panel : null;
      if (data.panel?.live && typeof data.panel.live === "object") {
        applyLiveConfigLocal(data.panel.live, { forceFormSync: true });
      }
      renderOwnerPanel();
      return true;
    } catch (error) {
      setHint(error.message || "خطا در دریافت پنل اونر", true);
      return false;
    } finally {
      state.support.ownerPanelLoading = false;
      renderOwnerPanel();
    }
  }

  function ownerPanelUsers() {
    const users = state.support.ownerData?.users;
    return Array.isArray(users) ? users : [];
  }

  function formatRelativeTime(timestamp) {
    const value = Math.max(0, Number(timestamp) || 0);
    if (!value) {
      return "نامشخص";
    }

    const diff = Math.max(0, Math.floor(Date.now() / 1000) - value);
    if (diff < 60) {
      return "لحظاتی پیش";
    }
    if (diff < 3600) {
      return `${Math.floor(diff / 60)} دقیقه پیش`;
    }
    if (diff < 86400) {
      return `${Math.floor(diff / 3600)} ساعت پیش`;
    }
    return `${Math.floor(diff / 86400)} روز پیش`;
  }

  function ownerActionButton(label, action, uid, variant = "") {
    const extraClass = variant ? ` ${variant}` : "";
    return `<button type="button" class="menu-action-btn${extraClass}" data-owner-action="${action}" data-uid="${escapeHtml(uid)}">${label}</button>`;
  }

  function renderOwnerPanel() {
    if (!el.ownerPanelSheet || !state.isOwner) {
      return;
    }

    const panel = state.support.ownerData;
    const loadingMarkup = '<p class="owner-empty">در حال دریافت اطلاعات...</p>';
    const admins = Array.isArray(panel?.admins) ? panel.admins : [];
    const shadowbanned = Array.isArray(panel?.shadowbanned_users) ? panel.shadowbanned_users : [];
    const users = ownerPanelUsers().slice(0, 16);
    const stats = panel?.stats && typeof panel.stats === "object" ? panel.stats : {};
    const audit = Array.isArray(panel?.audit) ? panel.audit : [];
    const settings = panel?.settings && typeof panel.settings === "object" ? panel.settings : {};

    if (el.ownerStaffOnlyToggle) {
      el.ownerStaffOnlyToggle.checked = Boolean(settings.staff_only_new_messages);
      el.ownerStaffOnlyToggle.disabled = state.support.ownerPanelLoading;
    }
    if (el.ownerStaffOnlyMeta) {
      el.ownerStaffOnlyMeta.textContent = state.support.ownerPanelLoading
        ? "در حال همگام‌سازی..."
        : (Boolean(settings.staff_only_new_messages) ? "در حال حاضر فقط استاف می‌تواند پیام جدید بفرستد." : "ارسال پیام جدید برای همه کاربران باز است.");
    }

    if (el.ownerAdminsList) {
      el.ownerAdminsList.innerHTML = !panel && state.support.ownerPanelLoading
        ? loadingMarkup
        : admins.length
        ? admins.map((user) => {
          const uid = sanitizeUid(String(user.uid || ""));
          const role = normalizeRole(String(user.role || "user"));
          const source = String(user.admin_source || "");
          const removable = role === "admin";
          const meta = source === "builtin" ? "ادمین ثابت" : (source === "runtime" ? "ادمین افزوده‌شده" : "اونر");
          return `
            <article class="owner-list-card">
              <div>
                <strong>${escapeHtml(String(user.display_name || uid || "Unknown"))}</strong>
                <span>${escapeHtml(uid)} · ${escapeHtml(meta)}</span>
              </div>
              ${removable ? ownerActionButton("Remove", "remove-admin", uid, "danger") : '<span class="owner-static-badge">Locked</span>'}
            </article>
          `;
        }).join("")
        : '<p class="owner-empty">هنوز ادمین جداگانه‌ای ثبت نشده.</p>';
    }

    if (el.ownerShadowbanList) {
      el.ownerShadowbanList.innerHTML = !panel && state.support.ownerPanelLoading
        ? loadingMarkup
        : shadowbanned.length
        ? shadowbanned.map((user) => {
          const uid = sanitizeUid(String(user.uid || ""));
          return `
            <article class="owner-list-card">
              <div>
                <strong>${escapeHtml(String(user.display_name || uid || "Unknown"))}</strong>
                <span>${escapeHtml(uid)} · ${escapeHtml(formatRelativeTime(Number(user.last_seen_at || 0)))}</span>
              </div>
              ${ownerActionButton("Unshadowban", "unshadowban", uid)}
            </article>
          `;
        }).join("")
        : '<p class="owner-empty">کاربر شادوبنی ثبت نشده.</p>';
    }

    if (el.ownerStatsGrid) {
      const statItems = [
        ["پیام‌ها", String(Math.max(0, Number(stats.messages_total) || 0))],
        ["پروفایل‌ها", String(Math.max(0, Number(stats.profiles_total) || 0))],
        ["آنلاین", String(Math.max(0, Number(stats.online_now) || 0))],
        ["شادوبن", String(Math.max(0, Number(stats.shadowban_total) || 0))],
        ["Storage", String(stats.storage_backend || "unknown")],
        ["Data Size", formatBytes(stats.data_size_bytes)],
        ["Uploads", formatBytes(stats.uploads_size_bytes)],
        ["DB / JSON", formatBytes((Number(stats.sqlite_size_bytes) || 0) + (Number(stats.json_messages_size_bytes) || 0))],
        ["Disk Free", formatBytes(stats.disk_free_bytes)],
        ["Memory Peak", formatBytes(stats.php_memory_peak_bytes)],
        ["CPU Load", stats.cpu_load_1m == null ? "ناموجود" : Number(stats.cpu_load_1m).toFixed(2)],
        ["آخرین فعالیت", formatRelativeTime(Number(stats.last_message_at || 0))]
      ];

      el.ownerStatsGrid.innerHTML = statItems.map(([label, value]) => `
        <article class="owner-stat-card">
          <span>${escapeHtml(label)}</span>
          <strong>${escapeHtml(value)}</strong>
        </article>
      `).join("");
    }

    if (el.ownerUsersList) {
      el.ownerUsersList.innerHTML = !panel && state.support.ownerPanelLoading
        ? loadingMarkup
        : users.length
        ? users.map((user) => {
          const uid = sanitizeUid(String(user.uid || ""));
          const role = normalizeRole(String(user.role || "user"));
          const actions = [];
          if (role === "user") {
            actions.push(ownerActionButton("Make Admin", "add-admin", uid));
          }
          if (Boolean(user.is_shadowbanned)) {
            actions.push(ownerActionButton("Unshadowban", "unshadowban", uid));
          }
          return `
            <article class="owner-list-card compact">
              <div>
                <strong>${escapeHtml(String(user.display_name || uid || "Unknown"))}</strong>
                <span>${escapeHtml(uid)} · ${user.online ? "آنلاین" : escapeHtml(formatRelativeTime(Number(user.last_seen_at || 0)))}</span>
              </div>
              <div class="owner-list-actions">
                ${actions.length ? actions.join("") : '<span class="owner-static-badge">No Action</span>'}
              </div>
            </article>
          `;
        }).join("")
        : '<p class="owner-empty">کاربری برای نمایش وجود ندارد.</p>';
    }

    if (el.ownerAuditList) {
      el.ownerAuditList.innerHTML = !panel && state.support.ownerPanelLoading
        ? loadingMarkup
        : audit.length
        ? audit.map((item) => {
          const details = item.details && typeof item.details === "object"
            ? Object.entries(item.details).map(([key, value]) => `${key}: ${value}`).join(" | ")
            : "";
          return `
            <article class="owner-audit-item">
              <strong>${escapeHtml(String(item.action || "owner.action"))}</strong>
              <span>${escapeHtml(formatRelativeTime(Number(item.at || 0)))}</span>
              ${details ? `<small>${escapeHtml(details)}</small>` : ""}
            </article>
          `;
        }).join("")
        : '<p class="owner-empty">لاگ جدیدی ثبت نشده.</p>';
    }
  }

  async function ownerPanelRequest(formData, successMessage, shouldRefreshSidebar = true) {
    baseIdentity(formData);
    const res = await fetch("api.php", { method: "POST", body: formData });
    const data = await res.json();
    if (!data.ok) {
      throw new Error(data.error || "درخواست مدیریتی انجام نشد.");
    }

    if (data.panel && typeof data.panel === "object") {
      state.support.ownerData = data.panel;
      renderOwnerPanel();
    }
    if (typeof data.enabled === "boolean") {
      state.staffOnlyNewMessages = Boolean(data.enabled);
      state.canSendNewMessages = state.staffOnlyNewMessages ? Boolean(state.isAdmin || state.isOwner) : true;
      renderStaffOnlyBanner();
      updateActionButton();
    }
    if (shouldRefreshSidebar) {
      await fetchSidebar(true, { includeUsers: shouldKeepUsersFresh() });
      await fetchState(true, { includeSidebar: false });
    }
    if (successMessage) {
      setHint(successMessage);
    }
    return data;
  }

  async function handleOwnerStaffOnlyToggle() {
    if (!el.ownerStaffOnlyToggle) {
      return;
    }

    const enabled = el.ownerStaffOnlyToggle.checked;
    const formData = new FormData();
    formData.set("action", "owner_staff_only");
    formData.set("enabled", enabled ? "1" : "0");

    try {
      await ownerPanelRequest(formData, enabled ? "حالت استاف فعال شد." : "حالت استاف غیرفعال شد.");
    } catch (error) {
      el.ownerStaffOnlyToggle.checked = !enabled;
      setHint(error.message || "تغییر حالت استاف انجام نشد.", true);
    }
  }

  async function handleOwnerAddAdmin(event) {
    event.preventDefault();
    if (!el.ownerAdminUidInput) {
      return;
    }

    const targetUid = sanitizeUid(el.ownerAdminUidInput.value);
    if (!targetUid) {
      setHint("UID معتبر وارد کن.", true);
      return;
    }

    const formData = new FormData();
    formData.set("action", "owner_admin");
    formData.set("mode", "add");
    formData.set("target_uid", targetUid);

    try {
      await ownerPanelRequest(formData, "ادمین اضافه شد.");
      el.ownerAdminUidInput.value = "";
    } catch (error) {
      setHint(error.message || "افزودن ادمین انجام نشد.", true);
    }
  }

  async function handleOwnerPanelActionClick(event) {
    const button = event.target instanceof Element ? event.target.closest("[data-owner-action]") : null;
    if (!(button instanceof HTMLButtonElement)) {
      return;
    }

    const action = String(button.dataset.ownerAction || "");
    const uid = sanitizeUid(String(button.dataset.uid || ""));
    if (!action || !uid) {
      return;
    }

    const formData = new FormData();
    let successMessage = "";

    if (action === "remove-admin") {
      formData.set("action", "owner_admin");
      formData.set("mode", "remove");
      formData.set("target_uid", uid);
      successMessage = "ادمین حذف شد.";
    } else if (action === "add-admin") {
      formData.set("action", "owner_admin");
      formData.set("mode", "add");
      formData.set("target_uid", uid);
      successMessage = "ادمین اضافه شد.";
    } else if (action === "unshadowban") {
      formData.set("action", "owner_unshadowban");
      formData.set("target_uid", uid);
      successMessage = "شادوبن کاربر برداشته شد.";
    } else {
      return;
    }

    try {
      await ownerPanelRequest(formData, successMessage);
    } catch (error) {
      setHint(error.message || "درخواست مدیریتی انجام نشد.", true);
    }
  }

  function isSseSupported() {
    return REALTIME_STREAM_ENABLED && typeof window.EventSource === "function";
  }

  function shouldUsePollingLoop() {
    return state.fallbackPolling || !isSseSupported();
  }

  function buildRealtimeStreamUrl() {
    const params = new URLSearchParams();
    params.set("action", "stream");
    params.set("uid", state.profile.uid);
    params.set("display_name", state.profile.display_name);
    params.set("theme", state.profile.theme);
    params.set("channel", state.channel);
    return `api.php?${params.toString()}`;
  }

  function currentFetchSignature() {
    return JSON.stringify({
      uid: sanitizeUid(state.profile?.uid || ""),
      channel: String(state.channel || "main")
    });
  }

  function conversationCacheKey(mode = state.mode, channel = state.channel, dmPeerUid = state.dmPeerUid) {
    if (mode === "dm") {
      const peerUid = sanitizeUid(dmPeerUid);
      return peerUid ? `dm:${peerUid}` : "";
    }
    return `ch:${String(channel || "main")}`;
  }

  function syncCurrentConversationCache() {
    const key = conversationCacheKey();
    if (!key) {
      return;
    }

    state.conversationCache.set(key, {
      serverMessages: Array.isArray(state.serverMessages) ? [...state.serverMessages] : [],
      typingUsers: Array.isArray(state.typingUsers) ? [...state.typingUsers] : [],
      pinnedMessage: state.pinnedMessage && typeof state.pinnedMessage === "object"
        ? { ...state.pinnedMessage, message: state.pinnedMessage.message ? { ...state.pinnedMessage.message } : state.pinnedMessage.message }
        : null,
      messageRevision: Math.max(0, Number(state.messageRevision) || 0),
      typingRevision: Math.max(0, Number(state.typingRevision) || 0)
    });
  }

  function restoreConversationFromCache(mode = state.mode, channel = state.channel, dmPeerUid = state.dmPeerUid) {
    const key = conversationCacheKey(mode, channel, dmPeerUid);
    if (!key || !state.conversationCache.has(key)) {
      return false;
    }

    const cached = state.conversationCache.get(key);
    if (!cached || typeof cached !== "object") {
      return false;
    }

    state.lastRenderKey = "";
    state.serverMessages = Array.isArray(cached.serverMessages) ? [...cached.serverMessages] : [];
    state.typingUsers = Array.isArray(cached.typingUsers) ? [...cached.typingUsers] : [];
    state.pinnedMessage = cached.pinnedMessage && typeof cached.pinnedMessage === "object"
      ? { ...cached.pinnedMessage, message: cached.pinnedMessage.message ? { ...cached.pinnedMessage.message } : cached.pinnedMessage.message }
      : null;
    state.messageRevision = Math.max(0, Number(cached.messageRevision) || 0);
    state.typingRevision = Math.max(0, Number(cached.typingRevision) || 0);
    renderMeta(state.onlineCount);
    renderPinnedBar();
    refreshVisibleMessages();
    return true;
  }

  function shouldKeepUsersFresh() {
    return state.mode === "dm";
  }

  function markUserInteraction(durationMs = USER_ACTIVE_WINDOW_MS) {
    const safeDuration = Math.max(5000, Number(durationMs) || USER_ACTIVE_WINDOW_MS);
    state.userActiveUntil = Math.max(Number(state.userActiveUntil || 0), Date.now() + safeDuration);
  }

  function getTargetPollDelay() {
    if (document.hidden) {
      return POLL_HIDDEN_MS;
    }
    if (Date.now() < Number(state.activityBurstUntil || 0) || state.typingUsers.length > 0) {
      return POLL_TYPING_MS;
    }
    if (Date.now() >= Number(state.userActiveUntil || 0)) {
      return POLL_IDLE_MS;
    }
    return POLL_ACTIVE_MS;
  }

  function markActivityBurst(durationMs = 15000) {
    const until = Date.now() + Math.max(1000, Number(durationMs) || 0);
    state.activityBurstUntil = Math.max(Number(state.activityBurstUntil || 0), until);
  }

  function shouldRunFullRefresh() {
    if (!state.hasFetchedAtLeastOnce) {
      return true;
    }
    if (!state.lastFullFetchAt) {
      return true;
    }
    return (Date.now() - state.lastFullFetchAt) >= FULL_STATE_REFRESH_MS;
  }

  function shouldRefreshUsersMeta(force = false) {
    if (force || !state.hasFetchedAtLeastOnce) {
      return true;
    }
    if (!state.lastUsersFetchAt) {
      return true;
    }
    return (Date.now() - state.lastUsersFetchAt) >= USER_META_REFRESH_MS;
  }

  function shouldRefreshSidebar(force = false) {
    if (force || !state.hasFetchedAtLeastOnce) {
      return true;
    }
    if (!state.lastSidebarFetchAt) {
      return true;
    }
    return (Date.now() - state.lastSidebarFetchAt) >= SIDEBAR_REFRESH_MS;
  }

  function shouldTouchPresenceNow(force = false) {
    if (force || !state.lastPresenceTouchAt) {
      return true;
    }

    return (Date.now() - state.lastPresenceTouchAt) >= PRESENCE_TOUCH_MS;
  }

  function flushRequestSideEffects(seenAt, touchedPresence) {
    const key = currentConversationKey();
    if (Number(seenAt) > 0 && Number(state.seenDirtyMap[key] || 0) <= Number(seenAt)) {
      delete state.seenDirtyMap[key];
    }

    if (touchedPresence) {
      state.lastPresenceTouchAt = Date.now();
    }
  }

  function safeJsonParse(raw, fallback = null) {
    try {
      return JSON.parse(String(raw || ""));
    } catch (_) {
      return fallback;
    }
  }

  async function readJsonResponse(res, fallbackMessage) {
    const raw = await res.text().catch(() => "");
    const data = safeJsonParse(raw, null);
    const message = fallbackMessage || "درخواست ناموفق بود.";

    if (!res.ok) {
      throw new Error((data && typeof data.error === "string" && data.error) || `${message} (HTTP ${res.status})`);
    }
    if (!data || typeof data !== "object") {
      throw new Error(raw.trim() ? message : "پاسخ سرور خالی بود.");
    }

    return data;
  }

  function isStreamPayloadRelevant(payload) {
    if (!payload || typeof payload !== "object") {
      return false;
    }

    const scope = String(payload.scope || "");
    if (scope === "dm") {
      return false;
    }

    if (scope === "channel") {
      return String(payload.channel || "") === state.channel;
    }

    return false;
  }

  function clearRealtimeReconnectTimer() {
    if (state.sseReconnectTimer) {
      window.clearTimeout(state.sseReconnectTimer);
      state.sseReconnectTimer = null;
    }
  }

  function closeRealtimeStream() {
    clearRealtimeReconnectTimer();
    if (state.eventSource) {
      try {
        state.eventSource.close();
      } catch (_) {
        // ignore
      }
      state.eventSource = null;
    }
    state.sseConnected = false;
  }

  function closeRealtimeTransport() {
    closeRealtimeStream();
    if (state.realtimeRefreshTimer) {
      window.clearTimeout(state.realtimeRefreshTimer);
      state.realtimeRefreshTimer = null;
    }
    if (state.realtimePulseTimer) {
      window.clearTimeout(state.realtimePulseTimer);
      state.realtimePulseTimer = null;
    }
    if (state.safetyRefreshTimer) {
      window.clearInterval(state.safetyRefreshTimer);
      state.safetyRefreshTimer = null;
    }
  }

  function disableFallbackPolling() {
    state.fallbackPolling = false;
    if (state.pollTimer) {
      window.clearTimeout(state.pollTimer);
      state.pollTimer = null;
    }
  }

  function enableFallbackPolling(delay = 0) {
    state.fallbackPolling = true;
    state.pollDelay = Math.max(state.pollDelay, delay);
    scheduleNextPoll(Math.max(getTargetPollDelay(), state.pollDelay));
  }

  function scheduleRealtimeRefresh(delay = 90) {
    if (state.realtimeRefreshTimer) {
      window.clearTimeout(state.realtimeRefreshTimer);
    }

    state.realtimeRefreshTimer = window.setTimeout(() => {
      state.realtimeRefreshTimer = null;
      fetchState(true);
    }, delay);
  }

  function scheduleRealtimePulse(delay = 90) {
    if (state.realtimePulseTimer) {
      window.clearTimeout(state.realtimePulseTimer);
    }

    state.realtimePulseTimer = window.setTimeout(() => {
      state.realtimePulseTimer = null;
      fetchPulse();
    }, delay);
  }

  function scheduleRealtimeReconnect() {
    clearRealtimeReconnectTimer();

    const delay = Math.max(800, Math.min(7000, Math.round(state.sseReconnectBackoff)));
    state.sseReconnectTimer = window.setTimeout(() => {
      state.sseReconnectTimer = null;
      if (!isSseSupported()) {
        enableFallbackPolling();
        return;
      }
      connectRealtimeStream();
    }, delay);

    state.sseReconnectBackoff = Math.min(9000, Math.round(delay * 1.7));
  }

  function connectRealtimeStream() {
    if (!isSseSupported()) {
      enableFallbackPolling();
      return;
    }

    closeRealtimeStream();

    let source = null;
    try {
      source = new EventSource(buildRealtimeStreamUrl());
    } catch (_) {
      enableFallbackPolling();
      scheduleRealtimeReconnect();
      return;
    }

    state.eventSource = source;

    source.addEventListener("open", () => {
      if (state.eventSource !== source) {
        return;
      }
      state.sseConnected = true;
      state.sseReconnectBackoff = 1200;
      disableFallbackPolling();
    });

    source.addEventListener("invalidate", (event) => {
      if (state.eventSource !== source) {
        return;
      }
      state.sseConnected = true;
      const payload = safeJsonParse(event.data, {});
      if (applyRealtimeMessageEvent(payload)) {
        scheduleRealtimePulse(80);
        return;
      }

      scheduleRealtimePulse(isStreamPayloadRelevant(payload) ? 120 : 260);
    });

    source.addEventListener("typing", (event) => {
      if (state.eventSource !== source) {
        return;
      }
      const payload = safeJsonParse(event.data, {});
      if (!isStreamPayloadRelevant(payload)) {
        return;
      }

      state.typingRevision = Math.max(0, Number(state.typingRevision) || 0) + 1;
      scheduleRealtimePulse(70);
    });

    source.addEventListener("ping", () => {
      if (state.eventSource !== source) {
        return;
      }
      state.sseConnected = true;
    });

    source.onerror = () => {
      if (state.eventSource !== source) {
        return;
      }
      closeRealtimeStream();
      enableFallbackPolling();
      scheduleRealtimeReconnect();
    };
  }

  function startSafetyRefreshLoop() {
    if (state.safetyRefreshTimer) {
      window.clearInterval(state.safetyRefreshTimer);
    }

    state.safetyRefreshTimer = window.setInterval(() => {
      if (!isSseSupported() || shouldUsePollingLoop()) {
        return;
      }
      if (shouldRunFullRefresh()) {
        fetchState(false);
        return;
      }
      fetchPulse();
    }, META_REFRESH_MS);
  }

  function initRealtimeTransport() {
    startSafetyRefreshLoop();
    if (isSseSupported()) {
      connectRealtimeStream();
      return;
    }
    enableFallbackPolling();
  }

  function restartRealtimeTransport() {
    if (isSseSupported()) {
      connectRealtimeStream();
      return;
    }
    enableFallbackPolling();
  }

  function ensureAudioContext() {
    if (state.audioCtx) {
      return state.audioCtx;
    }

    const Ctx = window.AudioContext || window.webkitAudioContext;
    if (!Ctx) {
      return null;
    }

    try {
      state.audioCtx = new Ctx();
      return state.audioCtx;
    } catch (_) {
      state.audioCtx = null;
      return null;
    }
  }

  function unlockAudio() {
    const ctx = ensureAudioContext();
    if (!ctx) {
      return;
    }

    if (ctx.state === "suspended" && typeof ctx.resume === "function") {
      ctx.resume().then(() => {
        state.audioUnlocked = true;
      }).catch(() => {});
    }

    state.audioUnlocked = ctx.state === "running" || state.audioUnlocked;
  }

  function playUiTap(kind = "receive") {
    const ctx = ensureAudioContext();
    if (!ctx || !state.audioUnlocked) {
      return;
    }

    const nowMs = Date.now();
    const minGap = kind === "notify" ? 900 : 70;
    if (kind === "notify") {
      if (nowMs - state.lastHiddenNotifySoundAt < minGap) {
        return;
      }
      state.lastHiddenNotifySoundAt = nowMs;
    } else if (kind === "receive") {
      if (nowMs - state.lastReceiveSoundAt < minGap) {
        return;
      }
      state.lastReceiveSoundAt = nowMs;
    }

    const now = ctx.currentTime + 0.002;
    const osc = ctx.createOscillator();
    const osc2 = ctx.createOscillator();
    const gain = ctx.createGain();
    const tone = kind === "send" ? 790 : (kind === "notify" ? 680 : 620);
    const tone2 = kind === "send" ? 1180 : (kind === "notify" ? 980 : 900);
    const volume = kind === "notify" ? 0.12 : 0.095;

    osc.type = "triangle";
    osc2.type = "sine";
    osc.frequency.setValueAtTime(tone, now);
    osc2.frequency.setValueAtTime(tone2, now);

    gain.gain.setValueAtTime(0.0001, now);
    gain.gain.exponentialRampToValueAtTime(volume, now + 0.01);
    gain.gain.exponentialRampToValueAtTime(volume * 0.35, now + 0.03);
    gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.08);

    osc.connect(gain);
    osc2.connect(gain);
    gain.connect(ctx.destination);

    osc.start(now);
    osc2.start(now);
    osc.stop(now + 0.085);
    osc2.stop(now + 0.065);
  }

  function markIncomingSoundIds(messages) {
    messages.forEach((message) => {
      const id = String(message?.id || "");
      if (id) {
        state.seenIncomingSoundIds.add(id);
      }
    });

    if (state.seenIncomingSoundIds.size > 900) {
      const keep = Array.from(state.seenIncomingSoundIds).slice(-500);
      state.seenIncomingSoundIds = new Set(keep);
    }
  }

  function maybePlayIncomingMessageSounds(previousMessages, nextMessages) {
    const prevIds = new Set(
      (Array.isArray(previousMessages) ? previousMessages : [])
        .map((msg) => String(msg?.id || ""))
        .filter(Boolean)
    );

    const addedFromOthers = (Array.isArray(nextMessages) ? nextMessages : [])
      .filter((message) => {
        const id = String(message?.id || "");
        if (!id || prevIds.has(id) || state.seenIncomingSoundIds.has(id)) {
          return false;
        }
        return String(message.from_uid || "") !== state.profile.uid;
      });

    if (!addedFromOthers.length) {
      markIncomingSoundIds(Array.isArray(nextMessages) ? nextMessages : []);
      return;
    }

    if (!state.hasFetchedAtLeastOnce || state.suppressNextIncomingSound) {
      markIncomingSoundIds(addedFromOthers);
      state.suppressNextIncomingSound = false;
      return;
    }

    markIncomingSoundIds(addedFromOthers);

    if (document.hidden) {
      playUiTap("notify");
      return;
    }

    playUiTap("receive");
  }

  function pollServerTick() {
    if (!shouldUsePollingLoop()) {
      return;
    }
    if (shouldRunFullRefresh()) {
      fetchState(false);
      return;
    }
    fetchPulse();
  }

  function scheduleNextPoll(delay = 0) {
    if (!shouldUsePollingLoop()) {
      if (state.pollTimer) {
        window.clearTimeout(state.pollTimer);
        state.pollTimer = null;
      }
      return;
    }

    if (state.pollTimer) {
      window.clearTimeout(state.pollTimer);
    }

    const nextDelay = Math.max(getTargetPollDelay(), Number(delay) || 0);
    state.pollTimer = window.setTimeout(() => {
      pollServerTick();
    }, nextDelay);
  }

  function normalizeTheme(theme) {
    return THEME_OPTIONS.includes(theme) ? theme : "ultra";
  }

  function themeLabel(theme) {
    if (theme === "light") {
      return "روشن";
    }
    if (theme === "ultra") {
      return "خیلی تاریک";
    }
    return "تاریک";
  }

  function applyTheme(theme, persist) {
    const safeTheme = normalizeTheme(theme);
    state.profile.theme = safeTheme;
    state.profile.theme_customized = safeTheme !== "ultra";
    state.profile.theme_reset_version = THEME_RESET_VERSION;

    document.body.setAttribute("data-theme", safeTheme);

    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) {
      const color = safeTheme === "light" ? "#f3f7ff" : (safeTheme === "ultra" ? "#000000" : "#080e1c");
      meta.setAttribute("content", color);
    }

    if (persist) {
      persistProfile();
    }
  }

  function loadOrCreateProfile() {
    try {
      const raw = localStorage.getItem(PROFILE_KEY);
      if (raw) {
        const parsed = JSON.parse(raw);
        const uid = sanitizeUid(parsed.uid || "");
        const displayName = sanitizeName(parsed.display_name || "");
        const avatarSeed = String(parsed.avatar_seed || "").slice(0, 24);
        const avatarUpdatedAt = Math.max(0, Number(parsed.avatar_updated_at) || 0);
        const avatarUrl = stripAvatarVersion(parsed.avatar_url || "");
        const themeResetVersion = Math.max(0, Number(parsed.theme_reset_version) || 0);
        const hasCurrentThemeReset = themeResetVersion >= THEME_RESET_VERSION;
        const hasThemeCustomization = hasCurrentThemeReset && parsed.theme_customized === true;
        const theme = hasThemeCustomization
          ? normalizeTheme(String(parsed.theme || "ultra"))
          : "ultra";

        if (uid) {
          const profile = {
            uid,
            display_name: displayName || randomDisplayName(),
            avatar_seed: avatarSeed || avatarSeedFromUid(uid),
            avatar_url: avatarUrl,
            avatar_updated_at: avatarUpdatedAt,
            theme,
            theme_customized: hasThemeCustomization && theme !== "ultra",
            theme_reset_version: THEME_RESET_VERSION
          };

          localStorage.setItem(PROFILE_KEY, JSON.stringify(profile));
          return profile;
        }
      }
    } catch (_) {
      // ignore
    }

    const uid = makeUid();
    const profile = {
      uid,
      display_name: randomDisplayName(),
      avatar_seed: avatarSeedFromUid(uid),
      avatar_url: defaultAvatarUrl(uid, avatarSeedFromUid(uid)),
      avatar_updated_at: 0,
      theme: "ultra",
      theme_customized: false,
      theme_reset_version: THEME_RESET_VERSION
    };

    localStorage.setItem(PROFILE_KEY, JSON.stringify(profile));
    return profile;
  }

  function persistProfile() {
    localStorage.setItem(PROFILE_KEY, JSON.stringify({
      ...state.profile,
      avatar_url: stripAvatarVersion(state.profile.avatar_url || "")
    }));
  }

  function loadSeenMap() {
    return {};
  }

  function saveSeenMap() {
    return undefined;
  }

  function makeUid() {
    const rand = cryptoRandomHex(8);
    return `u_${Date.now().toString(36)}_${rand}`;
  }

  function cryptoRandomHex(byteLength) {
    if (window.crypto && window.crypto.getRandomValues) {
      const arr = new Uint8Array(byteLength);
      window.crypto.getRandomValues(arr);
      return Array.from(arr, (b) => b.toString(16).padStart(2, "0")).join("");
    }

    return `${Math.random().toString(16).slice(2)}${Math.random().toString(16).slice(2)}`.slice(0, byteLength * 2);
  }

  function randomDisplayName() {
    const left = RANDOM_LEFT[Math.floor(Math.random() * RANDOM_LEFT.length)];
    const right = RANDOM_RIGHT[Math.floor(Math.random() * RANDOM_RIGHT.length)];
    const num = Math.floor(Math.random() * 90 + 10);
    return `${left}-${right}-${num}`;
  }

  function avatarSeedFromUid(uid) {
    return `av_${hashString(uid).toString(16)}${uid.slice(-4)}`;
  }

  function defaultAvatarUrl(uid, seed = "") {
    return ``;
  }

  function stripAvatarVersion(url) {
    const raw = String(url || "").trim();
    if (!raw || raw.startsWith("blob:") || raw.startsWith("data:")) {
      return raw;
    }

    try {
      const parsed = new URL(raw, window.location.href);
      parsed.searchParams.delete("v");

      if (/^https?:/i.test(raw)) {
        return parsed.toString();
      }

      const search = parsed.searchParams.toString();
      return `${parsed.pathname}${search ? `?${search}` : ""}${parsed.hash || ""}`;
    } catch (_) {
      const [withoutHash, hash = ""] = raw.split("#", 2);
      const [basePath, rawQuery = ""] = withoutHash.split("?", 2);
      const parts = rawQuery
        .split("&")
        .map((part) => part.trim())
        .filter(Boolean)
        .filter((part) => !part.startsWith("v="));
      return `${basePath}${parts.length ? `?${parts.join("&")}` : ""}${hash ? `#${hash}` : ""}`;
    }
  }

  function appendAvatarVersion(url, updatedAt = 0) {
    const version = Math.max(0, Number(updatedAt) || 0);
    if (!url || version <= 0 || url.startsWith("blob:") || url.startsWith("data:")) {
      return url;
    }

    try {
      const parsed = new URL(stripAvatarVersion(url), window.location.href);
      parsed.searchParams.set("v", String(version));

      if (/^https?:/i.test(url)) {
        return parsed.toString();
      }

      const search = parsed.searchParams.toString();
      return `${parsed.pathname}${search ? `?${search}` : ""}${parsed.hash || ""}`;
    } catch (_) {
      const [withoutHash, hash = ""] = stripAvatarVersion(url).split("#", 2);
      const [basePath, rawQuery = ""] = withoutHash.split("?", 2);
      const parts = rawQuery
        .split("&")
        .map((part) => part.trim())
        .filter(Boolean)
      parts.push(`v=${version}`);
      return `${basePath}?${parts.join("&")}${hash ? `#${hash}` : ""}`;
    }
  }

  function normalizeAvatarUrl(url, uid, seed = "", updatedAt = 0) {
    const raw = stripAvatarVersion(url);
    if (
      raw.startsWith("blob:")
      || raw.startsWith("data:")
      || raw.startsWith("uploads/")
      || raw.startsWith("/uploads/")
      || raw.startsWith("http://")
      || raw.startsWith("https://")
    ) {
      return appendAvatarVersion(raw, updatedAt);
    }
    return defaultAvatarUrl(uid, seed);
  }

  function latestAvatarStateForUid(uid, fallback = null) {
    const safeUid = sanitizeUid(uid);
    if (!safeUid) {
      return {
        seed: String(fallback?.from_avatar_seed || fallback?.avatar_seed || ""),
        url: String(fallback?.from_avatar_url || fallback?.avatar_url || ""),
        updatedAt: Math.max(0, Number(fallback?.avatar_updated_at || 0))
      };
    }

    if (safeUid === sanitizeUid(state.profile?.uid || "")) {
      return {
        seed: String(state.profile?.avatar_seed || fallback?.from_avatar_seed || ""),
        url: String(state.profile?.avatar_url || fallback?.from_avatar_url || ""),
        updatedAt: Math.max(0, Number(state.profile?.avatar_updated_at || fallback?.avatar_updated_at || 0))
      };
    }

    const user = state.userMap.get(safeUid);
    if (user) {
      return {
        seed: String(user.avatar_seed || fallback?.from_avatar_seed || ""),
        url: String(user.avatar_url || fallback?.from_avatar_url || ""),
        updatedAt: Math.max(0, Number(user.avatar_updated_at || fallback?.avatar_updated_at || 0))
      };
    }

    return {
      seed: String(fallback?.from_avatar_seed || fallback?.avatar_seed || ""),
      url: String(fallback?.from_avatar_url || fallback?.avatar_url || ""),
      updatedAt: Math.max(0, Number(fallback?.avatar_updated_at || 0))
    };
  }

  function latestIdentityStateForUid(uid, fallback = null) {
    const safeUid = sanitizeUid(uid);
    const fallbackName = sanitizeName(String(fallback?.from_name || fallback?.display_name || "")) || "";
    const avatarState = latestAvatarStateForUid(safeUid, fallback);

    if (!safeUid) {
      return {
        displayName: fallbackName || "Unknown",
        ...avatarState
      };
    }

    if (safeUid === sanitizeUid(state.profile?.uid || "")) {
      return {
        displayName: sanitizeName(String(state.profile?.display_name || fallbackName)) || fallbackName || "Unknown",
        ...avatarState
      };
    }

    const user = state.userMap.get(safeUid);
    if (user) {
      return {
        displayName: sanitizeName(String(user.display_name || fallbackName)) || fallbackName || "Unknown",
        ...avatarState
      };
    }

    return {
      displayName: fallbackName || "Unknown",
      ...avatarState
    };
  }

  function roleFromMessageFallback(message) {
    if (!message || typeof message !== "object") {
      return "user";
    }

    return normalizeRole(String(
      message.from_role
      || message.role
      || (message.from_is_owner ? "owner" : (message.from_is_admin ? "admin" : "user"))
    ));
  }

  function repairMojibakeText(value) {
    const raw = String(value || "");
    if (!/[ÃØÙÚÛâð]/.test(raw)) {
      return raw;
    }

    try {
      return decodeURIComponent(escape(raw));
    } catch (_) {
      return raw;
    }
  }

  function sanitizeUid(uid) {
    return String(uid || "").replace(/[^a-zA-Z0-9_-]/g, "").slice(0, 64);
  }

  function sanitizeName(name) {
    return repairMojibakeText(String(name || ""))
      .replace(/[^\p{L}\p{N}._\- ]/gu, "")
      .replace(/\s+/g, " ")
      .trim()
      .slice(0, 32);
  }

  function hashString(input) {
    let hash = 0;
    for (let i = 0; i < input.length; i += 1) {
      hash = ((hash << 5) - hash) + input.charCodeAt(i);
      hash |= 0;
    }
    return Math.abs(hash);
  }

  function openProfileSheet() {
    state.profileSheetOpen = true;
    state.profileEditing = false;
    state.profileDraft = state.profile.display_name;
    clearPendingAvatarSelection(false);

    el.profileSheet.classList.remove("hidden");
    el.profileSheet.setAttribute("aria-hidden", "false");
    renderProfile();
  }

  function closeProfileSheet() {
    state.profileSheetOpen = false;
    state.profileEditing = false;
    state.profileDraft = state.profile.display_name;
    clearPendingAvatarSelection();

    el.profileSheet.classList.add("hidden");
    el.profileSheet.setAttribute("aria-hidden", "true");
  }

  function clearPendingAvatarSelection(resetInput = true) {
    state.pendingAvatarFile = null;
    if (state.pendingAvatarPreviewUrl) {
      URL.revokeObjectURL(state.pendingAvatarPreviewUrl);
      state.pendingAvatarPreviewUrl = "";
    }
    if (resetInput) {
      el.profileAvatarInput.value = "";
    }
  }

  function setPendingAvatar(file) {
    clearPendingAvatarSelection(false);
    state.pendingAvatarFile = file;
    state.pendingAvatarPreviewUrl = URL.createObjectURL(file);
    setAvatar(
      el.profileAvatarLarge,
      state.profile.avatar_seed,
      state.profile.display_name,
      state.profile.uid,
      state.pendingAvatarPreviewUrl
    );
  }

  async function uploadProfileAvatar(file) {
    const formData = new FormData();
    baseIdentity(formData);
    formData.set("action", "profile_avatar");
    formData.set("avatar", file, file.name || "avatar");

    const res = await fetch("api.php", { method: "POST", body: formData });
    const data = await res.json();
    if (!data.ok) {
      throw new Error(data.error || "آپلود عکس پروفایل ناموفق بود.");
    }

    const safeUid = sanitizeUid(data.me?.uid || state.profile.uid);
    const nextSeed = String(data.me?.avatar_seed || state.profile.avatar_seed || avatarSeedFromUid(safeUid));
    const avatarUpdatedAt = Math.max(0, Number(data.me?.avatar_updated_at) || Date.now());
    state.profile.uid = safeUid || state.profile.uid;
    state.profile.avatar_seed = nextSeed;
    state.profile.avatar_updated_at = avatarUpdatedAt;
    state.profile.avatar_url = stripAvatarVersion(data.me?.avatar_url || "");
    if (data.me && typeof data.me === "object") {
      applyProfileUpdateLocal(data.me, state.profile.uid);
    }
  }

  async function saveProfile() {
    const nextName = sanitizeName(state.profileDraft || el.profileNameInput.value);
    if (!nextName) {
      setHint("نام نمایشی معتبر وارد کن.", true);
      return;
    }

    const formData = new FormData();
    baseIdentity(formData);
    formData.set("action", "profile");
    formData.set("display_name", nextName);
    formData.set("avatar_seed", String(state.profile.avatar_seed || ""));
    if (state.pendingAvatarFile) {
      formData.set("avatar", state.pendingAvatarFile, state.pendingAvatarFile.name || "avatar");
    }

    try {
      const res = await fetch("api.php", { method: "POST", body: formData });
      const data = await res.json();
      if (!data.ok) {
        throw new Error(data.error || "خطا در ذخیره پروفایل");
      }

      state.profile.display_name = sanitizeName(data.me?.display_name || nextName) || nextName;
      state.profile.avatar_seed = String(data.me?.avatar_seed || state.profile.avatar_seed || avatarSeedFromUid(state.profile.uid));
      state.profile.avatar_updated_at = Math.max(0, Number(data.me?.avatar_updated_at) || state.profile.avatar_updated_at || 0);
      state.profile.avatar_url = stripAvatarVersion(data.me?.avatar_url || "");
      state.profileDraft = state.profile.display_name;
      state.profileEditing = false;

      if (data.me && typeof data.me === "object") {
        applyProfileUpdateLocal(data.me, state.profile.uid);
      }

      persistProfile();
      syncOwnProfileLocally();
      renderProfile();
      renderDmUsers();
      refreshVisibleMessages();
      closeProfileSheet();
      setHint("پروفایل ذخیره شد.");
      fetchSidebar(true, { includeUsers: false });
    } catch (error) {
      setHint(error.message || "خطا در ذخیره پروفایل", true);
    }
  }

  function renderProfile() {
    const name = sanitizeName(state.profile.display_name || "") || "Guest";
    state.profile.display_name = name;
    state.profile.avatar_seed = String(state.profile.avatar_seed || avatarSeedFromUid(state.profile.uid));
    state.profile.avatar_updated_at = Math.max(0, Number(state.profile.avatar_updated_at) || 0);
    state.profile.avatar_url = stripAvatarVersion(state.profile.avatar_url || "");

    el.profileName.innerHTML = renderLabelWithBadge(name, state.role, "user-label");
    el.profileNameLarge.innerHTML = renderLabelWithBadge(name, state.role, "user-label");
    el.profileUid.textContent = state.profile.uid;

    const canSyncInput = !state.profileSheetOpen || !state.profileEditing || document.activeElement !== el.profileNameInput;
    if (canSyncInput) {
      state.profileDraft = name;
      el.profileNameInput.value = state.profileDraft;
    }

    setAvatar(
      el.profileAvatar,
      state.profile.avatar_seed,
      name,
      state.profile.uid,
      state.profile.avatar_url,
      state.profile.avatar_updated_at,
      { eager: true }
    );
    setAvatar(
      el.profileAvatarLarge,
      state.profile.avatar_seed,
      name,
      state.profile.uid,
      state.pendingAvatarPreviewUrl || state.profile.avatar_url,
      state.profile.avatar_updated_at,
      { eager: true, viewable: true, role: state.role }
    );

    el.themeChoices.forEach((btn) => {
      const choice = normalizeTheme(btn.getAttribute("data-theme-choice") || "light");
      const active = choice === state.profile.theme;
      btn.classList.toggle("active", active);
      btn.setAttribute("aria-pressed", active ? "true" : "false");
    });
  }

  function setAvatar(node, seed, name, uid = "", avatarUrl = "", avatarUpdatedAt = 0, options = {}) {
    if (!node) {
      return;
    }

    const safeUid = sanitizeUid(uid);
    const fallbackName = String(name || "").trim() || (safeUid ? `User-${safeUid.slice(-4).toUpperCase()}` : "Guest");
    const safeSeed = String(seed || (safeUid ? avatarSeedFromUid(safeUid) : avatarSeedFromUid(fallbackName)));
    const safeAvatarUrl = normalizeAvatarUrl(avatarUrl, safeUid, safeSeed, avatarUpdatedAt);
    const isViewable = options.viewable === true;
    const hash = hashString(safeSeed);
    const hue1 = hash % 360;
    const hue2 = (hue1 + 44) % 360;
    const gradient = `linear-gradient(142deg, hsl(${hue1} 82% 55%), hsl(${hue2} 74% 48%))`;

    node.style.setProperty("--av-bg1", `hsl(${hue1} 82% 55%)`);
    node.style.setProperty("--av-bg2", `hsl(${hue2} 74% 48%)`);
    node.style.backgroundImage = gradient;
    node.style.backgroundSize = "cover";
    node.style.backgroundPosition = "center";
    node.style.backgroundRepeat = "no-repeat";
    node.classList.remove("avatar-has-media");
    node.replaceChildren();
    node.dataset.avatarViewable = isViewable ? "1" : "";
    node.dataset.avatarUid = safeUid;
    node.dataset.avatarName = fallbackName;
    node.dataset.avatarSeed = safeSeed;
    node.dataset.avatarUrl = safeAvatarUrl;
    node.dataset.avatarUpdatedAt = String(Math.max(0, Number(avatarUpdatedAt) || 0));
    node.dataset.avatarRole = normalizeRole(String(options.role || "user"));
    if (isViewable && !node.closest("button")) {
      node.setAttribute("tabindex", "0");
      node.setAttribute("role", "button");
      node.setAttribute("aria-label", `نمایش پروفایل ${fallbackName}`);
    } else {
      node.removeAttribute("tabindex");
      node.removeAttribute("role");
      node.removeAttribute("aria-label");
    }

    const label = document.createElement("span");
    label.className = "avatar-fallback-label";
    label.textContent = getInitials(fallbackName);
    node.appendChild(label);

    if (!safeAvatarUrl) {
      return;
    }

    const img = document.createElement("img");
    img.className = "avatar-img";
    img.alt = "";
    img.decoding = "async";
    img.loading = options.eager === true ? "eager" : "lazy";
    img.referrerPolicy = "strict-origin-when-cross-origin";
    img.addEventListener("load", () => {
      node.classList.add("avatar-has-media");
    }, { once: true });
    img.addEventListener("error", () => {
      img.remove();
      node.classList.remove("avatar-has-media");
    }, { once: true });
    node.appendChild(img);
    img.src = safeAvatarUrl;
    if (img.complete && img.naturalWidth > 0) {
      node.classList.add("avatar-has-media");
    }
  }

  function getInitials(name) {
    const parts = String(name || "").trim().split(/\s+/).filter(Boolean);
    if (!parts.length) {
      return "G";
    }

    if (parts.length === 1) {
      return parts[0].slice(0, 2).toUpperCase();
    }

    return `${parts[0][0]}${parts[1][0]}`.toUpperCase();
  }

  function renderEmojiPicker() {
    el.emojiPicker.innerHTML = EMOJI_PICKER_SET
      .map((emoji) => `<button type="button" class="emoji-item" data-emoji="${emoji}">${emoji}</button>`)
      .join("");

    el.emojiPicker.querySelectorAll("[data-emoji]").forEach((btn) => {
      btn.addEventListener("click", () => {
        insertAtCursor(el.messageInput, btn.getAttribute("data-emoji") || "");
        updateActionButton();
        maybeSendTyping();
      });
    });
  }

  function insertAtCursor(input, text) {
    const start = input.selectionStart || 0;
    const end = input.selectionEnd || 0;
    const old = input.value;
    input.value = `${old.slice(0, start)}${text}${old.slice(end)}`;
    const pos = start + text.length;
    input.setSelectionRange(pos, pos);
    input.focus();
  }

  function baseIdentity(formData) {
    formData.set("uid", state.profile.uid);
    formData.set("display_name", state.profile.display_name);
    formData.set("theme", state.profile.theme);
  }

  function openDirectWith(uid) {
    return false;
  }

  function cancelPendingTypingTimer() {
    if (state.typingTimer) {
      window.clearTimeout(state.typingTimer);
      state.typingTimer = null;
    }
  }

  function getSendContext() {
    return {
      ok: true,
      scope: "channel",
      channel: state.channel,
      to_uid: ""
    };

    if (state.mode === "dm") {
      const peerUid = sanitizeUid(state.dmPeerUid);
      if (!peerUid || peerUid === state.profile.uid) {
        return { ok: false, error: "برای پیام خصوصی یک کاربر را انتخاب کن." };
      }

      return {
        ok: true,
        scope: "dm",
        channel: state.channel,
        to_uid: peerUid
      };
    }

    return {
      ok: true,
      scope: "channel",
      channel: state.channel,
      to_uid: ""
    };
  }

  function formatBytes(bytes) {
    const value = Number(bytes) || 0;
    if (value <= 0) {
      return "0 B";
    }

    if (value < 1024) {
      return `${value} B`;
    }
    if (value < 1024 * 1024) {
      return `${(value / 1024).toFixed(1)} KB`;
    }
    return `${(value / (1024 * 1024)).toFixed(1)} MB`;
  }

  function mediaAcceptList() {
    return Object.keys(MEDIA_UPLOAD_RULES).map((ext) => `.${ext}`).join(",");
  }

  function sanitizeMessageText(raw) {
    const normalized = repairMojibakeText(String(raw || "")).replace(/\r\n?/g, "\n").trim();
    if (normalized.length <= MAX_MESSAGE_CHARS) {
      return normalized;
    }
    return normalized.slice(0, MAX_MESSAGE_CHARS);
  }

  function fileExtension(name) {
    const raw = String(name || "");
    const idx = raw.lastIndexOf(".");
    if (idx < 0 || idx === raw.length - 1) {
      return "";
    }
    return raw.slice(idx + 1).toLowerCase();
  }

  function validateMediaFile(file) {
    if (!(file instanceof File)) {
      return { ok: false, error: "فایل معتبر نیست." };
    }

    const ext = fileExtension(file.name || "");
    const allowedMimes = MEDIA_UPLOAD_RULES[ext];
    if (!ext || !Array.isArray(allowedMimes)) {
      return { ok: false, error: "فقط فایل‌های مدیا (عکس، ویدیو، صوت) مجاز هستند." };
    }

    const mime = String(file.type || "").toLowerCase();
    if (!mime || !allowedMimes.includes(mime)) {
      return { ok: false, error: "نوع واقعی فایل با فرمت مجاز هم‌خوانی ندارد." };
    }

    return { ok: true };
  }

  function remainingSendCooldownMs() {
    return Math.max(0, Number(state.nextSendAllowedAt || 0) - Date.now());
  }

  function stopCooldownTicker() {
    if (state.cooldownTimer) {
      window.clearInterval(state.cooldownTimer);
      state.cooldownTimer = null;
    }
  }

  function ensureCooldownTicker() {
    stopCooldownTicker();

    if (remainingSendCooldownMs() <= 0) {
      updateActionButton();
      return;
    }

    updateActionButton();
    state.cooldownTimer = window.setInterval(() => {
      if (remainingSendCooldownMs() <= 0) {
        stopCooldownTicker();
      }
      updateActionButton();
    }, 240);
  }

  function setLocalMessageCooldown(seconds) {
    const secs = Math.max(0, Number(seconds) || 0);
    if (secs <= 0) {
      return;
    }

    const nextAllowedAt = Date.now() + (secs * 1000);
    state.nextSendAllowedAt = Math.max(state.nextSendAllowedAt, nextAllowedAt);
    ensureCooldownTicker();
  }

  function parseCooldownFromText(message) {
    const match = String(message || "").match(/(\d+)\s*second/i);
    if (!match) {
      return 0;
    }

    const value = Number(match[1] || 0);
    return Number.isFinite(value) && value > 0 ? value : 0;
  }

  function setPendingFile(file) {
    if (!(file instanceof File)) {
      return false;
    }

    const maxUploadMb = Math.round(MAX_UPLOAD_BYTES / (1024 * 1024));
    if ((file.size || 0) > MAX_UPLOAD_BYTES) {
      setHint(`حداکثر اندازه فایل ${maxUploadMb}MB است.`, true);
      return false;
    }

    const mediaValidation = validateMediaFile(file);
    if (!mediaValidation.ok) {
      setHint(mediaValidation.error || "فرمت فایل مجاز نیست.", true);
      return false;
    }

    state.pendingFile = file;
    renderPendingFile();
    updateActionButton();
    return true;
  }

  function clearPendingFile() {
    state.pendingFile = null;
    el.fileInput.value = "";
    renderPendingFile();
    updateActionButton();
  }

  function renderPendingFile() {
    if (!state.pendingFile) {
      el.filePreview.classList.add("hidden");
      el.filePreviewName.textContent = "";
      el.clearFileBtn.disabled = state.actionBusy;
      return;
    }

    const file = state.pendingFile;
    const fileName = String(file.name || "file");
    el.filePreviewName.textContent = `${fileName} (${formatBytes(file.size)})`;
    el.filePreview.classList.remove("hidden");
    el.clearFileBtn.disabled = state.actionBusy;
  }

  function setUploadProgress(visible, ratio = 0, label = "") {
    state.uploadVisible = visible;
    el.uploadProgress.classList.toggle("hidden", !visible);

    const pct = Math.max(0, Math.min(100, Math.round(ratio * 100)));
    el.uploadProgressBar.style.width = `${pct}%`;
    el.uploadProgressText.textContent = label || `${pct}%`;

    updateActionButton();
  }

  function setActionBusy(busy) {
    state.actionBusy = Boolean(busy);
    el.actionBtn.disabled = state.actionBusy;
    el.attachBtn.disabled = state.actionBusy || Boolean(state.recording);
    el.clearFileBtn.disabled = state.actionBusy;
    updateActionButton();
  }

  async function runPrimaryAction() {
    if (state.actionBusy) {
      return;
    }

    if (state.recording) {
      stopRecording();
      return;
    }

    cancelPendingTypingTimer();

    if (composerLockedForNewMessages()) {
      setHint(STAFF_ONLY_NEW_MESSAGES_HINT, true);
      return;
    }

    const text = sanitizeMessageText(el.messageInput.value);
    if (el.messageInput.value !== text) {
      el.messageInput.value = text;
    }
    const hasPendingFile = Boolean(state.pendingFile);

    if (state.compose.mode === "edit" && hasPendingFile) {
      setHint("برای ویرایش، فایل پیوست‌شده را اول حذف کن.", true);
      return;
    }

    if (state.compose.mode === "edit" && !text) {
      setHint("متن ویرایش نباید خالی باشد.", true);
      return;
    }

    const context = getSendContext();
    if (!context.ok) {
      setHint(context.error, true);
      return;
    }

    markUserInteraction();
    markActivityBurst();

    const replyTargetId = state.compose.mode === "reply" && state.compose.messageId
      ? resolveMessageIdAlias(state.compose.messageId)
      : "";

    if (state.compose.mode !== "edit") {
      const cooldownMs = remainingSendCooldownMs();
      if (cooldownMs > 0) {
        const remainingSec = Math.max(1, Math.ceil(cooldownMs / 1000));
        setHint(`برای ارسال بعدی ${remainingSec} ثانیه صبر کن.`, true);
        updateActionButton();
        return;
      }
    }

    if (!text && !hasPendingFile) {
      await startRecording();
      return;
    }

    if (state.compose.mode === "edit") {
      setActionBusy(true);
      const edited = await editMessage(state.compose.messageId, text);
      if (edited) {
        mergeMessageLocal(edited);
        clearComposeState();
        el.messageInput.value = "";
        updateActionButton();
      }
      setActionBusy(false);
      return;
    }

    if (hasPendingFile) {
      const file = state.pendingFile;
      const caption = text;
      const optimisticId = makeLocalOutgoingMessageId();
      const optimisticMessage = buildOutgoingFileMessage(optimisticId, file, caption, context, replyTargetId);
      const previousCompose = { ...state.compose };

      addPendingMessageLocal(optimisticMessage);
      clearPendingFile();
      clearComposeState();
      el.messageInput.value = "";
      updateActionButton();
      // setHint("");

      setActionBusy(true);
      const uploaded = await uploadFile(file, context, { caption, replyTo: replyTargetId });
      if (uploaded) {
        resolvePendingMessageLocal(optimisticId, uploaded);
        // setHint("");
      } else {
        discardPendingMessageLocal(optimisticId);
        setPendingFile(file);
        el.messageInput.value = caption;
        setComposeState(previousCompose.mode, previousCompose.messageId, previousCompose.preview);
        updateActionButton();
      }
      setActionBusy(false);
      return;
    }

    const optimisticId = makeLocalOutgoingMessageId();
    const optimisticMessage = buildOutgoingTextMessage(optimisticId, text, context, replyTargetId);
    const previousCompose = { ...state.compose };
    addPendingMessageLocal(optimisticMessage);
    el.messageInput.value = "";
    clearComposeState();
    updateActionButton();
    // setHint("");

    setActionBusy(true);
    const sent = await sendText(text, context, replyTargetId);
    if (sent) {
      resolvePendingMessageLocal(optimisticId, sent);
    } else {
      discardPendingMessageLocal(optimisticId);
      el.messageInput.value = text;
      setComposeState(previousCompose.mode, previousCompose.messageId, previousCompose.preview);
      updateActionButton();
    }
    setActionBusy(false);
  }

  async function sendText(text, context, replyTo = "") {
    const safeText = sanitizeMessageText(text);
    if (!safeText) {
      setHint("متن پیام معتبر نیست.", true);
      return null;
    }

    const formData = new FormData();
    baseIdentity(formData);

    formData.set("action", "send");
    formData.set("scope", context.scope);
    formData.set("channel", context.channel || "main");
    if (context.scope === "dm") {
      formData.set("to_uid", context.to_uid);
    }
    formData.set("text", safeText);

    const resolvedReplyTo = resolveMessageIdAlias(replyTo);
    if (resolvedReplyTo) {
      formData.set("reply_to", resolvedReplyTo);
    }

    try {
      const res = await fetch("api.php", { method: "POST", body: formData });
      const data = await res.json();
      const retryAfter = Number(res.headers.get("Retry-After") || 0);
      if (!data.ok) {
        if (retryAfter > 0) {
          setLocalMessageCooldown(retryAfter);
        } else {
          const parsedCooldown = parseCooldownFromText(data.error || "");
          if (parsedCooldown > 0) {
            setLocalMessageCooldown(parsedCooldown);
          }
        }
        throw new Error(data.error || "ارسال پیام ناموفق بود.");
      }

      setLocalMessageCooldown(MESSAGE_COOLDOWN_SECONDS);
      state.lastPresenceTouchAt = Date.now();
      playUiTap("send");
      scheduleNextPoll(POLL_TYPING_MS);
      return data.message && typeof data.message === "object" ? data.message : null;
    } catch (error) {
      const parsedCooldown = parseCooldownFromText(error.message || "");
      if (parsedCooldown > 0) {
        setLocalMessageCooldown(parsedCooldown);
      }
      setHint(error.message || "خطا در ارسال پیام", true);
      return null;
    }
  }

  async function uploadFile(file, context, options = {}) {
    const mediaValidation = validateMediaFile(file);
    if (!mediaValidation.ok) {
      setHint(mediaValidation.error || "فرمت فایل مجاز نیست.", true);
      return null;
    }

    const maxUploadMb = Math.round(MAX_UPLOAD_BYTES / (1024 * 1024));
    if ((file.size || 0) > MAX_UPLOAD_BYTES) {
      setHint(`حداکثر اندازه فایل ${maxUploadMb}MB است.`, true);
      return null;
    }

    const formData = new FormData();
    baseIdentity(formData);

    formData.set("action", "upload");
    formData.set("scope", context.scope);
    formData.set("channel", context.channel || "main");
    if (context.scope === "dm") {
      formData.set("to_uid", context.to_uid);
    }

    const rawCaption = Object.prototype.hasOwnProperty.call(options, "caption")
      ? String(options.caption || "")
      : String(el.messageInput.value || "");
    const caption = sanitizeMessageText(rawCaption);
    if (caption) {
      formData.set("text", caption);
    }

    const explicitReplyTo = Object.prototype.hasOwnProperty.call(options, "replyTo")
      ? String(options.replyTo || "")
      : (state.compose.mode === "reply" && state.compose.messageId ? String(state.compose.messageId) : "");
    const resolvedReplyTo = resolveMessageIdAlias(explicitReplyTo);
    if (resolvedReplyTo) {
      formData.set("reply_to", resolvedReplyTo);
    }

    formData.set("file", file, file.name || "file");

    try {
      setHint("در حال آپلود...");
      setUploadProgress(true, 0, "0%");

      const data = await new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "api.php", true);
        xhr.responseType = "json";

        xhr.upload.addEventListener("progress", (event) => {
          if (event.lengthComputable) {
            const ratio = event.total > 0 ? (event.loaded / event.total) : 0;
            setUploadProgress(true, ratio);
            return;
          }
          setUploadProgress(true, 0.15, "درحال آپلود...");
        });

        xhr.addEventListener("error", () => {
          reject(new Error("ارتباط آپلود قطع شد."));
        });

        xhr.addEventListener("abort", () => {
          reject(new Error("آپلود لغو شد."));
        });

        xhr.addEventListener("load", () => {
          let payload = xhr.response;
          const retryAfter = Number(xhr.getResponseHeader("Retry-After") || 0);
          if (!payload || typeof payload !== "object") {
            try {
              payload = JSON.parse(xhr.responseText || "{}");
            } catch (_) {
              payload = null;
            }
          }

          if (xhr.status < 200 || xhr.status >= 300 || !payload || payload.ok !== true) {
            if (retryAfter > 0) {
              setLocalMessageCooldown(retryAfter);
            } else {
              const parsedCooldown = parseCooldownFromText(payload?.error || "");
              if (parsedCooldown > 0) {
                setLocalMessageCooldown(parsedCooldown);
              }
            }
            const msg = payload && typeof payload.error === "string"
              ? payload.error
              : "آپلود ناموفق بود.";
            reject(new Error(msg));
            return;
          }

          resolve(payload);
        });

        xhr.send(formData);
      });

      setUploadProgress(true, 1, "100%");
      window.setTimeout(() => {
        setUploadProgress(false, 0, "");
      }, 260);

      const message = data && typeof data.message === "object" ? data.message : null;
      if (!message) {
        throw new Error("پاسخ آپلود معتبر نیست.");
      }

      setLocalMessageCooldown(MESSAGE_COOLDOWN_SECONDS);
      state.lastPresenceTouchAt = Date.now();
      playUiTap("send");
      scheduleNextPoll(POLL_TYPING_MS);

      return message;
    } catch (error) {
      const parsedCooldown = parseCooldownFromText(error.message || "");
      if (parsedCooldown > 0) {
        setLocalMessageCooldown(parsedCooldown);
      }
      setUploadProgress(false, 0, "");
      setHint(error.message || "خطا در آپلود فایل", true);
      return null;
    }
  }

  function cloneReactionBuckets(reactions) {
    const next = {};
    if (!reactions || typeof reactions !== "object") {
      return next;
    }

    Object.entries(reactions).forEach(([emojiKey, uidList]) => {
      const safeEmoji = String(emojiKey || "");
      if (!safeEmoji || !Array.isArray(uidList)) {
        return;
      }

      const safeUids = uidList
        .map((uid) => sanitizeUid(String(uid || "")))
        .filter(Boolean);
      if (safeUids.length) {
        next[safeEmoji] = safeUids;
      }
    });

    return next;
  }

  function buildNextReactionsState(reactions, uid, emoji) {
    const safeUid = sanitizeUid(String(uid || ""));
    const safeEmoji = String(emoji || "");
    const next = cloneReactionBuckets(reactions);

    if (!safeUid || !safeEmoji) {
      return next;
    }

    let currentEmoji = "";
    Object.entries(next).some(([emojiKey, uidList]) => {
      if (uidList.includes(safeUid)) {
        currentEmoji = emojiKey;
        return true;
      }
      return false;
    });

    Object.keys(next).forEach((emojiKey) => {
      next[emojiKey] = next[emojiKey].filter((item) => item !== safeUid);
      if (!next[emojiKey].length) {
        delete next[emojiKey];
      }
    });

    if (currentEmoji !== safeEmoji) {
      next[safeEmoji] = Array.isArray(next[safeEmoji]) ? next[safeEmoji] : [];
      if (!next[safeEmoji].includes(safeUid)) {
        next[safeEmoji].push(safeUid);
      }
    }

    return next;
  }

  async function reactToMessage(messageId, emoji) {
    const safeMessageId = String(messageId || "");
    const targetMessage = state.messages.find((message) => String(message.id || "") === safeMessageId);
    const rollbackReactions = cloneReactionBuckets(targetMessage?.reactions);
    const canOptimisticallyApply = Boolean(targetMessage && !targetMessage.deleted_at);

    markUserInteraction();
    markActivityBurst();

    if (canOptimisticallyApply) {
      applyReactionLocal(safeMessageId, buildNextReactionsState(rollbackReactions, state.profile.uid, emoji));
    }

    const formData = new FormData();
    baseIdentity(formData);

    formData.set("action", "react");
    formData.set("message_id", safeMessageId);
    formData.set("emoji", emoji);

    try {
      const res = await fetch("api.php", { method: "POST", body: formData });
      const data = await res.json();
      if (!data.ok) {
        throw new Error(data.error || "ثبت ری‌اکشن ناموفق بود.");
      }
      scheduleNextPoll(POLL_TYPING_MS);
      return data.result && typeof data.result === "object" ? data.result : null;
    } catch (error) {
      if (canOptimisticallyApply) {
        applyReactionLocal(safeMessageId, rollbackReactions);
      }
      setHint(error.message || "خطا در ری‌اکشن", true);
      return null;
    }
  }

  async function editMessage(messageId, text) {
    const safeText = sanitizeMessageText(text);
    if (!safeText) {
      setHint("متن ویرایش معتبر نیست.", true);
      return null;
    }

    const formData = new FormData();
    baseIdentity(formData);

    formData.set("action", "edit");
    formData.set("message_id", messageId);
    formData.set("text", safeText);

    try {
      const res = await fetch("api.php", { method: "POST", body: formData });
      const data = await res.json();
      if (!data.ok) {
        throw new Error(data.error || "ویرایش انجام نشد.");
      }

      setHint("پیام ویرایش شد.");
      return data.message && typeof data.message === "object" ? data.message : null;
    } catch (error) {
      setHint(error.message || "خطا در ویرایش", true);
      return null;
    }
  }

  function applyDeleteMessageResult(result, fallbackMessageId = "") {
    if (!result || typeof result !== "object") {
      return false;
    }

    if (Object.prototype.hasOwnProperty.call(result, "pinned_message")) {
      applyPinnedMessage(result.pinned_message);
    }

    if (result.hardDeleted) {
      const removeId = String(result.messageId || fallbackMessageId || "");
      if (removeId) {
        removeMessageLocal(removeId);
      }
      return true;
    }

    if (result.message && typeof result.message === "object") {
      mergeMessageLocal(result.message);
      return true;
    }

    return false;
  }

  function removeMessagesByUserLocal(targetUid) {
    const safeTargetUid = sanitizeUid(targetUid);
    if (!safeTargetUid) {
      return 0;
    }

    const before = state.serverMessages.length;
    state.serverMessages = state.serverMessages.filter((message) => sanitizeUid(String(message.from_uid || "")) !== safeTargetUid);

    let localOnlyChanged = 0;
    state.localOnlyMessages.forEach((message, id) => {
      if (sanitizeUid(String(message?.from_uid || "")) !== safeTargetUid) {
        return;
      }
      const maybeBlobUrl = String(message?.file?.url || "");
      if (maybeBlobUrl.startsWith("blob:")) {
        URL.revokeObjectURL(maybeBlobUrl);
      }
      state.localOnlyMessages.delete(id);
      localOnlyChanged += 1;
    });

    let pendingChanged = 0;
    state.pendingMessages.forEach((message, id) => {
      if (sanitizeUid(String(message?.from_uid || "")) !== safeTargetUid) {
        return;
      }
      state.pendingMessages.delete(id);
      pendingChanged += 1;
    });

    if (state.serverMessages.length !== before || pendingChanged > 0 || localOnlyChanged > 0) {
      refreshVisibleMessages();
    }

    return (before - state.serverMessages.length) + pendingChanged + localOnlyChanged;
  }

  async function deleteMessage(messageId, options = {}) {
    const formData = new FormData();
    baseIdentity(formData);

    formData.set("action", "delete");
    formData.set("message_id", messageId);
    if (options && options.hard) {
      formData.set("hard", "1");
    }

    try {
      const res = await fetch("api.php", { method: "POST", body: formData });
      const data = await res.json();
      if (!data.ok) {
        throw new Error(data.error || "حذف پیام ناموفق بود.");
      }

      const hardDeleted = Boolean(data.hard_deleted);
      if (hardDeleted) {
        setHint("پیام کامل حذف شد.");
        return {
          hardDeleted: true,
          messageId: String(data.message_id || messageId || ""),
          message: null
        };
      }

      setHint("پیام حذف شد.");
      return {
        hardDeleted: false,
        messageId: String(data.message_id || messageId || ""),
        message: data.message && typeof data.message === "object" ? data.message : null
      };
    } catch (error) {
      setHint(error.message || "خطا در حذف پیام", true);
      return null;
    }
  }

  async function deleteAllMessagesByUser(targetUid) {
    const safeTargetUid = sanitizeUid(targetUid);
    if (!safeTargetUid || safeTargetUid === state.profile.uid) {
      setHint("شناسه کاربر برای حذف گروهی معتبر نیست.", true);
      return null;
    }

    const formData = new FormData();
    baseIdentity(formData);
    formData.set("action", "delete_user_messages");
    formData.set("target_uid", safeTargetUid);

    try {
      const res = await fetch("api.php", { method: "POST", body: formData });
      const data = await res.json();
      if (!data.ok) {
        throw new Error(data.error || "حذف گروهی پیام‌ها انجام نشد.");
      }

      const deletedCount = Math.max(0, Number(data.deleted_count || 0));
      setHint(deletedCount > 0 ? `${deletedCount} پیام حذف شد.` : "پیامی برای حذف پیدا نشد.");
      return {
        targetUid: safeTargetUid,
        deletedCount,
        pinnedMessage: data.pinned_message ?? null
      };
    } catch (error) {
      setHint(error.message || "خطا در حذف گروهی پیام‌ها", true);
      return null;
    }
  }

  async function toggleShadowban(targetUid, shadowbanned) {
    const safeTargetUid = sanitizeUid(targetUid);
    if (!safeTargetUid || safeTargetUid === state.profile.uid) {
      setHint("شناسه کاربر برای شادوبن معتبر نیست.", true);
      return null;
    }

    const formData = new FormData();
    baseIdentity(formData);
    formData.set("action", "shadowban");
    formData.set("target_uid", safeTargetUid);
    formData.set("shadowbanned", shadowbanned ? "1" : "0");

    try {
      const res = await fetch("api.php", { method: "POST", body: formData });
      const data = await res.json();
      if (!data.ok) {
        throw new Error(data.error || "به‌روزرسانی شادوبن انجام نشد.");
      }

      const nextValue = Boolean(data.shadowbanned);
      setHint(nextValue ? "کاربر شادوبن شد." : "شادوبن کاربر برداشته شد.");
      if (state.support.ownerPanelOpen && state.isOwner) {
        fetchOwnerPanel(true);
      }
      return {
        targetUid: safeTargetUid,
        shadowbanned: nextValue
      };
    } catch (error) {
      setHint(error.message || "خطا در تغییر وضعیت شادوبن", true);
      return null;
    }
  }

  async function pinMessage(messageId) {
    const safeId = String(messageId || "");
    if (!safeId) {
      return undefined;
    }

    const formData = new FormData();
    baseIdentity(formData);
    formData.set("action", "pin");
    formData.set("mode", "set");
    formData.set("message_id", safeId);

    try {
      const res = await fetch("api.php", { method: "POST", body: formData });
      const data = await res.json();
      if (!data.ok) {
        throw new Error(data.error || "پین پیام انجام نشد.");
      }

      setHint("پیام پین شد.");
      return data.pinned_message ?? null;
    } catch (error) {
      setHint(error.message || "خطا در پین پیام", true);
      return undefined;
    }
  }

  async function clearPinnedMessage(messageId = "") {
    const formData = new FormData();
    baseIdentity(formData);
    formData.set("action", "pin");
    formData.set("mode", "clear");
    formData.set("channel", state.channel);

    if (messageId) {
      formData.set("message_id", String(messageId));
    }

    try {
      const res = await fetch("api.php", { method: "POST", body: formData });
      const data = await res.json();
      if (!data.ok) {
        throw new Error(data.error || "برداشتن پین انجام نشد.");
      }

      setHint("پین برداشته شد.");
      return data.pinned_message ?? null;
    } catch (error) {
      setHint(error.message || "خطا در برداشتن پین", true);
      return undefined;
    }
  }

  async function copyText(text) {
    try {
      if (navigator.clipboard && navigator.clipboard.writeText) {
        await navigator.clipboard.writeText(text);
      } else {
        const area = document.createElement("textarea");
        area.value = text;
        document.body.appendChild(area);
        area.select();
        document.execCommand("copy");
        area.remove();
      }

      setHint("کپی شد.");
      return true;
    } catch (_) {
      setHint("کپی انجام نشد.", true);
      return false;
    }
  }

  function applyUnreadHints(unread) {
    const next = unread && typeof unread === "object"
      ? unread
      : { channels: {}, dms: {} };

    state.unreadHints = {
      channels: next.channels && typeof next.channels === "object" ? next.channels : {},
      dms: next.dms && typeof next.dms === "object" ? next.dms : {}
    };
  }

  function pinnedPreviewText(payload) {
    const message = payload?.message;
    if (!message || typeof message !== "object") {
      return "";
    }

    if (message.deleted_at) {
      return "پیام حذف شده";
    }

    return String(message.text || "").trim() || describeFileMessage(message) || "پیام سنجاق‌شده";
  }

  function renderPinnedBar() {
    if (!el.pinnedBar || !el.pinnedSummary || !el.pinnedMeta || !el.pinnedJumpBtn || !el.pinnedUnpinBtn) {
      return;
    }

    const payload = state.pinnedMessage;
    const message = payload?.message;
    if (!payload || !message || typeof message !== "object") {
      el.pinnedBar.classList.add("hidden");
      el.pinnedSummary.textContent = "";
      el.pinnedMeta.textContent = "";
      el.pinnedUnpinBtn.classList.add("hidden");
      return;
    }

    const author = latestIdentityStateForUid(String(message.from_uid || ""), message).displayName;
    el.pinnedSummary.textContent = pinnedPreviewText(payload);
    el.pinnedMeta.textContent = `پین شده از ${author}`;
    el.pinnedBar.classList.remove("hidden");
    el.pinnedUnpinBtn.classList.toggle("hidden", !state.isOwner);
  }

  function applyPinnedMessage(payload) {
    if (
      payload
      && typeof payload === "object"
      && payload.message
      && typeof payload.message === "object"
      && isMessageInCurrentContext(payload.message)
    ) {
      state.pinnedMessage = payload;
    } else if (!payload) {
      state.pinnedMessage = null;
    } else if (payload && typeof payload === "object" && payload.message === null) {
      state.pinnedMessage = null;
    }

    syncCurrentConversationCache();
    renderPinnedBar();
  }

  function upsertLocalUser(nextUser) {
    const safeUid = sanitizeUid(String(nextUser?.uid || ""));
    if (!safeUid) {
      return;
    }

    const previous = state.userMap.get(safeUid) || {};
    const merged = {
      ...previous,
      ...nextUser,
      uid: safeUid
    };

    const existingIndex = state.users.findIndex((user) => sanitizeUid(String(user?.uid || "")) === safeUid);
    if (existingIndex >= 0) {
      state.users[existingIndex] = merged;
    } else {
      state.users.push(merged);
    }

    state.userMap.set(safeUid, merged);
    state.userRevision += 1;
  }

  function syncAuthorMetaFromMessage(message) {
    if (!message || typeof message !== "object") {
      return;
    }

    const safeUid = sanitizeUid(String(message.from_uid || ""));
    if (!safeUid) {
      return;
    }

    const existingUser = state.userMap.get(safeUid) || {};
    const existingAvatarUpdatedAt = Math.max(0, Number(existingUser.avatar_updated_at || 0));
    const incomingAvatarUpdatedAt = Math.max(
      0,
      Number(message.avatar_updated_at || message.from_avatar_updated_at || 0)
    );
    const incomingName = sanitizeName(String(message.from_name || "")) || "";
    const incomingSeed = String(message.from_avatar_seed || "").trim();
    const incomingUrl = stripAvatarVersion(String(message.from_avatar_url || ""));
    const incomingRole = roleFromMessageFallback(message);
    const existingRole = normalizeRole(String(existingUser.role || ""));
    const shouldApplyIncoming =
      !existingUser.uid
      || incomingAvatarUpdatedAt > existingAvatarUpdatedAt
      || (
        existingAvatarUpdatedAt === 0
        && incomingAvatarUpdatedAt === 0
        && (
          !String(existingUser.display_name || "").trim()
          || !String(existingUser.avatar_seed || "").trim()
          || !String(existingUser.avatar_url || "").trim()
        )
      );

    upsertLocalUser({
      ...existingUser,
      uid: safeUid,
      display_name: shouldApplyIncoming
        ? (incomingName || String(existingUser.display_name || ""))
        : String(existingUser.display_name || incomingName || ""),
      avatar_seed: shouldApplyIncoming
        ? (incomingSeed || String(existingUser.avatar_seed || avatarSeedFromUid(safeUid)))
        : String(existingUser.avatar_seed || incomingSeed || avatarSeedFromUid(safeUid)),
      avatar_url: shouldApplyIncoming
        ? (incomingUrl || String(existingUser.avatar_url || ""))
        : String(existingUser.avatar_url || incomingUrl || ""),
      avatar_updated_at: shouldApplyIncoming ? incomingAvatarUpdatedAt : existingAvatarUpdatedAt,
      role: incomingRole !== "user" ? incomingRole : existingRole,
      is_owner: incomingRole === "owner" || Boolean(existingUser.is_owner),
      is_admin: incomingRole === "owner" || incomingRole === "admin" || Boolean(existingUser.is_admin)
    });
  }

  function patchMessagesForUser(uid, profile) {
    const safeUid = sanitizeUid(uid);
    if (!safeUid || !profile || typeof profile !== "object") {
      return;
    }

    const patchMessage = (message) => {
      if (!message || sanitizeUid(String(message.from_uid || "")) !== safeUid) {
        return message;
      }

      return {
        ...message,
        from_name: String(profile.display_name || message.from_name || ""),
        from_avatar_seed: String(profile.avatar_seed || message.from_avatar_seed || ""),
        from_avatar_url: String(profile.avatar_url || message.from_avatar_url || ""),
        avatar_updated_at: Math.max(
          0,
          Number(profile.avatar_updated_at || 0),
          Number(message.avatar_updated_at || 0)
        )
      };
    };

    state.serverMessages = state.serverMessages.map(patchMessage);
    state.messages = state.messages.map(patchMessage);
    state.pendingMessages.forEach((message, id) => {
      state.pendingMessages.set(id, patchMessage(message));
    });
    state.localOnlyMessages.forEach((message, id) => {
      state.localOnlyMessages.set(id, patchMessage(message));
    });
  }

  function applyProfileUpdateLocal(profilePayload, targetUid = "") {
    const safeUid = sanitizeUid(targetUid || profilePayload?.uid || "");
    if (!safeUid || !profilePayload || typeof profilePayload !== "object") {
      return false;
    }

    const mergedProfile = {
      uid: safeUid,
      display_name: sanitizeName(String(profilePayload.display_name || "")) || `User-${safeUid.slice(-4).toUpperCase()}`,
      avatar_seed: String(profilePayload.avatar_seed || avatarSeedFromUid(safeUid)),
      avatar_url: stripAvatarVersion(String(profilePayload.avatar_url || "")),
      avatar_updated_at: Math.max(0, Number(profilePayload.avatar_updated_at || 0)),
      role: normalizeRole(String(profilePayload.role || state.userMap.get(safeUid)?.role || "user")),
      is_owner: Object.prototype.hasOwnProperty.call(profilePayload, "is_owner")
        ? Boolean(profilePayload.is_owner)
        : Boolean(state.userMap.get(safeUid)?.is_owner),
      is_admin: Object.prototype.hasOwnProperty.call(profilePayload, "is_admin")
        ? Boolean(profilePayload.is_admin)
        : Boolean(state.userMap.get(safeUid)?.is_admin)
    };

    upsertLocalUser({
      ...(state.userMap.get(safeUid) || {}),
      ...mergedProfile
    });
    patchMessagesForUser(safeUid, mergedProfile);

    if (safeUid === sanitizeUid(state.profile?.uid || "")) {
      state.profile.display_name = mergedProfile.display_name;
      state.profile.avatar_seed = mergedProfile.avatar_seed;
      state.profile.avatar_url = mergedProfile.avatar_url;
      state.profile.avatar_updated_at = mergedProfile.avatar_updated_at;
      state.role = mergedProfile.role;
      state.isOwner = mergedProfile.is_owner;
      state.isAdmin = mergedProfile.is_admin;
      persistProfile();
      renderProfile();
    }

    state.lastRenderKey = "";
    renderDmUsers();
    renderPinnedBar();
    refreshVisibleMessages();
    return true;
  }

  function syncOwnProfileLocally() {
    const safeUid = sanitizeUid(state.profile.uid);
    if (!safeUid) {
      return;
    }

    const existingUser = state.userMap.get(safeUid) || {};
    upsertLocalUser({
      ...existingUser,
      uid: safeUid,
      display_name: state.profile.display_name,
      avatar_seed: state.profile.avatar_seed,
      avatar_url: state.profile.avatar_url,
      avatar_updated_at: state.profile.avatar_updated_at,
      online: existingUser.online !== false,
      role: state.role,
      is_owner: state.isOwner,
      is_admin: state.isAdmin,
      can_send_new_messages: state.canSendNewMessages,
      staff_only_new_messages: state.staffOnlyNewMessages,
      is_shadowbanned: existingUser.is_shadowbanned === true
    });

    const patchMessage = (message) => {
      if (!message || sanitizeUid(String(message.from_uid || "")) !== safeUid) {
        return message;
      }

      return {
        ...message,
        from_name: state.profile.display_name,
        from_avatar_seed: state.profile.avatar_seed,
        from_avatar_url: state.profile.avatar_url
      };
    };

    state.serverMessages = state.serverMessages.map(patchMessage);
    state.messages = state.messages.map(patchMessage);

    state.pendingMessages.forEach((message, id) => {
      state.pendingMessages.set(id, patchMessage(message));
    });

    state.localOnlyMessages.forEach((message, id) => {
      state.localOnlyMessages.set(id, patchMessage(message));
    });

    state.userRevision += 1;
    state.lastRenderKey = "";
  }

  function setUserShadowbanLocal(uid, shadowbanned) {
    const safeUid = sanitizeUid(uid);
    if (!safeUid) {
      return;
    }

    const existingUser = state.userMap.get(safeUid);
    if (!existingUser) {
      return;
    }

    upsertLocalUser({
      ...existingUser,
      is_shadowbanned: Boolean(shadowbanned)
    });
  }

  function applyRealtimeMessageEvent(payload) {
    if (!payload || typeof payload !== "object") {
      return false;
    }

    const kind = String(payload.kind || "");
    const hasPinnedMessage = Object.prototype.hasOwnProperty.call(payload, "pinned_message");

    if (kind === "message_upsert") {
      const message = payload.message;
      if (!message || typeof message !== "object") {
        return false;
      }

      state.messageRevision = Math.max(0, Number(state.messageRevision) || 0) + 1;
      if (isMessageInCurrentContext(message)) {
        mergeMessageLocal(message);
      }
      if (hasPinnedMessage) {
        applyPinnedMessage(payload.pinned_message);
      }
      return true;
    }

    if (kind === "message_delete") {
      const messageId = String(payload.message_id || "");
      if (!messageId) {
        return false;
      }

      state.messageRevision = Math.max(0, Number(state.messageRevision) || 0) + 1;
      removeMessageLocal(messageId);
      if (hasPinnedMessage) {
        applyPinnedMessage(payload.pinned_message);
      }
      return true;
    }

    if (kind === "bulk_delete_user") {
      const targetUid = sanitizeUid(String(payload.target_uid || ""));
      if (!targetUid) {
        return false;
      }

      state.messageRevision = Math.max(0, Number(state.messageRevision) || 0) + 1;
      removeMessagesByUserLocal(targetUid);
      if (hasPinnedMessage) {
        applyPinnedMessage(payload.pinned_message);
      }
      return true;
    }

    if (kind === "pin_update") {
      state.messageRevision = Math.max(0, Number(state.messageRevision) || 0) + 1;
      applyPinnedMessage(payload.pinned_message ?? null);
      return true;
    }

    if (kind === "settings_update") {
      if (Object.prototype.hasOwnProperty.call(payload, "staff_only_new_messages")) {
        state.staffOnlyNewMessages = Boolean(payload.staff_only_new_messages);
        state.canSendNewMessages = state.staffOnlyNewMessages ? Boolean(state.isAdmin || state.isOwner) : true;
        renderStaffOnlyBanner();
        updateActionButton();
      }

      if (payload.live && typeof payload.live === "object") {
        applyLiveConfigLocal(payload.live);
      }

      if (payload.target_uid) {
        const targetUid = sanitizeUid(String(payload.target_uid || ""));
        if (targetUid) {
          const currentUser = state.userMap.get(targetUid) || {};
          const nextRole = normalizeRole(String(payload.role || currentUser.role || "user"));
          upsertLocalUser({
            ...currentUser,
            uid: targetUid,
            role: nextRole,
            is_owner: nextRole === "owner",
            is_admin: nextRole === "owner" || nextRole === "admin"
          });
          if (targetUid === sanitizeUid(state.profile?.uid || "")) {
            state.role = nextRole;
            state.isOwner = nextRole === "owner";
            state.isAdmin = nextRole === "owner" || nextRole === "admin";
            renderProfile();
            updateActionButton();
          }
          state.lastRenderKey = "";
          refreshVisibleMessages();
          renderDmUsers();
        }
      }
      return true;
    }

    if (kind === "profile_update") {
      return applyProfileUpdateLocal(payload.profile, payload.target_uid);
    }

    return false;
  }

  async function fetchSidebar(force = false, options = {}) {
    const includeUsers = options.includeUsers !== undefined
      ? Boolean(options.includeUsers)
      : shouldKeepUsersFresh();
    const needsSidebarMeta = shouldRefreshSidebar(force);
    const needsUsers = includeUsers && shouldRefreshUsersMeta(force);

    if (!force && !needsSidebarMeta && !needsUsers) {
      return true;
    }

    const requestSignature = currentFetchSignature();
    const shouldTouchPresence = shouldTouchPresenceNow(force);
    const params = new URLSearchParams();
    params.set("action", "sidebar");
    params.set("uid", state.profile.uid);
    params.set("display_name", state.profile.display_name);
    params.set("theme", state.profile.theme);
    params.set("channel", state.channel);
    params.set("include_users", includeUsers ? "1" : "0");
    params.set("touch_presence", shouldTouchPresence ? "1" : "0");

    try {
      const res = await fetch(`api.php?${params.toString()}`, { cache: "no-store" });
      const data = await readJsonResponse(res, "خطا در دریافت نوار کناری.");

      if (requestSignature !== currentFetchSignature()) {
        return false;
      }

      if (!data.ok) {
        throw new Error(data.error || "خطا در دریافت نوار کناری.");
      }

      flushRequestSideEffects(0, shouldTouchPresence);
      if (data.me && typeof data.me === "object") {
        state.role = normalizeRole(String(data.me.role || state.role || "user"));
        state.isOwner = Boolean(data.me.is_owner);
        state.isAdmin = Boolean(data.me.is_admin);
        state.canSendNewMessages = data.me.can_send_new_messages !== false;
        state.staffOnlyNewMessages = Boolean(data.me.staff_only_new_messages);
      }

      if (data.live && typeof data.live === "object") {
        applyLiveConfigLocal(data.live);
      }

      if (includeUsers && Array.isArray(data.users)) {
        state.users = data.users;
        state.userMap = new Map(
          state.users
            .map((user) => [sanitizeUid(String(user.uid || "")), user])
            .filter(([uid]) => uid)
        );
        state.userRevision += 1;
        state.lastUsersFetchAt = Date.now();
      }

      state.onlineCount = Math.max(0, Number(data.online_count) || 0);
      applyUnreadHints(data.unread_hints);
      state.lastSidebarFetchAt = Date.now();

      renderMeta(state.onlineCount);
      renderTabs();
      renderDmUsers();
      renderPinnedBar();
      renderSupportLinks();
      renderStaffOnlyBanner();
      if (state.support.ownerPanelOpen) {
        renderOwnerPanel();
      }
      refreshVisibleMessages();
      updateActionButton();
      return true;
    } catch (error) {
      setHint(error.message || "خطا در دریافت نوار کناری.", true);
      return false;
    }
  }

  async function fetchPulse() {
    if (state.loading || state.pinging) {
      return;
    }

    state.pinging = true;
    let escalatedToFullState = false;
    const requestSignature = currentFetchSignature();
    const shouldFetchSidebarMeta = shouldRefreshSidebar(false);
    const shouldFetchUsers = shouldKeepUsersFresh() && shouldRefreshUsersMeta(false);
    const shouldIncludeMeta = shouldFetchSidebarMeta;
    const shouldLongPoll = LONG_POLL_ENABLED && !document.hidden && !shouldIncludeMeta && state.typingUsers.length === 0;
    const shouldTouchPresence = shouldTouchPresenceNow(shouldIncludeMeta);
    const seenAt = currentSeenAt();

    const params = new URLSearchParams();
    params.set("action", "pulse");
    params.set("uid", state.profile.uid);
    params.set("display_name", state.profile.display_name);
    params.set("theme", state.profile.theme);
    params.set("channel", state.channel);
    params.set("message_revision", String(Math.max(0, Number(state.messageRevision) || 0)));
    params.set("typing_revision", String(Math.max(0, Number(state.typingRevision) || 0)));
    params.set("seen_at", String(seenAt));
    params.set("touch_presence", shouldTouchPresence ? "1" : "0");
    params.set("wait", shouldLongPoll ? "1" : "0");
    params.set("wait_timeout", String(Math.round(LONG_POLL_TIMEOUT_MS / 1000)));
    params.set("include_meta", shouldIncludeMeta ? "1" : "0");
    params.set("include_unread", shouldIncludeMeta ? "1" : "0");
    const controller = typeof AbortController === "function" ? new AbortController() : null;
    const timeoutMs = shouldLongPoll ? LONG_POLL_TIMEOUT_MS + 7000 : 12000;
    const timeoutId = controller ? window.setTimeout(() => controller.abort(), timeoutMs) : 0;

    try {
      const res = await fetch(`api.php?${params.toString()}`, {
        cache: "no-store",
        signal: controller?.signal
      });
      const data = await readJsonResponse(res, "خطا در بررسی وضعیت.");

      if (requestSignature !== currentFetchSignature()) {
        return;
      }

      if (!data.ok) {
        throw new Error(data.error || "خطا در بررسی وضعیت.");
      }

      flushRequestSideEffects(seenAt, shouldTouchPresence);
      state.pollDelay = 0;

      if (data.me && typeof data.me === "object") {
        state.role = normalizeRole(String(data.me.role || state.role || "user"));
        state.isOwner = Boolean(data.me.is_owner);
        state.isAdmin = Boolean(data.me.is_admin);
        state.canSendNewMessages = data.me.can_send_new_messages !== false;
        state.staffOnlyNewMessages = Boolean(data.me.staff_only_new_messages);
      }

      if (data.live && typeof data.live === "object") {
        applyLiveConfigLocal(data.live);
      }

      const nextMessageRevision = Math.max(0, Number(data.message_revision) || 0);
      const nextTypingRevision = Math.max(0, Number(data.typing_revision) || 0);
      if (Array.isArray(data.typing_users)) {
        state.typingUsers = data.typing_users;
      }
      if (Object.prototype.hasOwnProperty.call(data, "online_count")) {
        state.onlineCount = Math.max(0, Number(data.online_count) || 0);
      }
      if (data.unread_hints && typeof data.unread_hints === "object") {
        applyUnreadHints(data.unread_hints);
      }
      if (shouldIncludeMeta) {
        state.lastSidebarFetchAt = Date.now();
      }
      clearActiveUnreadLocal();
      renderMeta(state.onlineCount);
      updateActionButton();

      if (data.messages_changed) {
        const appliedDelta = !Boolean(data.message_gap) && applyRealtimeMessageEvent(data.message_event);
        if (!appliedDelta) {
          escalatedToFullState = true;
          await fetchState(true);
        } else {
          markActivityBurst();
        }
      }

      markCurrentSeenFromMessages();

      if (!escalatedToFullState && shouldFetchUsers) {
        await fetchSidebar(false, { includeUsers: true });
      }

      if (!escalatedToFullState) {
        state.messageRevision = nextMessageRevision;
        state.typingRevision = nextTypingRevision;
        syncCurrentConversationCache();
      }
    } catch (error) {
      const message = error?.name === "AbortError"
        ? "پالس سرور به‌موقع جواب نداد؛ موقتاً با polling سازگار ادامه می‌دهیم."
        : (error.message || "ارتباط با سرور برقرار نشد.");
      setHint(message, true);
      const nextBackoff = Math.max(getTargetPollDelay(), 2000);
      state.pollDelay = Math.min(POLL_ERROR_MAX_MS, Math.round(nextBackoff * 1.4));
      enableFallbackPolling(state.pollDelay);
    } finally {
      if (timeoutId) {
        window.clearTimeout(timeoutId);
      }
      state.pinging = false;
      if (!state.loading && !escalatedToFullState) {
        scheduleNextPoll();
      }
    }
  }

  async function fetchState(force, options = {}) {
    if (state.loading) {
      if (force) {
        state.pendingForceFetch = true;
      }
      return;
    }

    state.loading = true;
    if (!state.messages.length) {
      renderMessages();
      renderDmUsers();
    }
    const requestSignature = currentFetchSignature();
    const previousServerMessages = Array.isArray(state.serverMessages) ? [...state.serverMessages] : [];
    const includeSidebar = options.includeSidebar !== undefined
      ? Boolean(options.includeSidebar)
      : !state.hasFetchedAtLeastOnce;
    const shouldTouchPresence = shouldTouchPresenceNow(force || includeSidebar);
    const seenAt = currentSeenAt();

    const params = new URLSearchParams();
    params.set("action", "conversation");
    params.set("uid", state.profile.uid);
    params.set("display_name", state.profile.display_name);
    params.set("theme", state.profile.theme);
    params.set("channel", state.channel);
    params.set("seen_at", String(seenAt));
    params.set("touch_presence", shouldTouchPresence ? "1" : "0");

    try {
      const res = await fetch(`api.php?${params.toString()}`, { cache: "no-store" });
      const data = await readJsonResponse(res, "خطا در دریافت وضعیت.");

      if (requestSignature !== currentFetchSignature()) {
        return;
      }

      if (!data.ok) {
        throw new Error(data.error || "خطا در دریافت وضعیت.");
      }

      flushRequestSideEffects(seenAt, shouldTouchPresence);
      state.pollDelay = 0;
      state.lastFullFetchAt = Date.now();
      if (state.sseConnected && isSseSupported()) {
        disableFallbackPolling();
      }

      if (data.me && typeof data.me === "object") {
        const safeUid = sanitizeUid(data.me.uid || state.profile.uid);
        if (safeUid) {
          state.profile.uid = safeUid;
        }
        state.role = normalizeRole(String(data.me.role || "user"));
        state.isOwner = Boolean(data.me.is_owner);
        state.isAdmin = Boolean(data.me.is_admin);
        state.canSendNewMessages = data.me.can_send_new_messages !== false;
        state.staffOnlyNewMessages = Boolean(data.me.staff_only_new_messages);

        const incomingTheme = normalizeTheme(String(data.me.theme || state.profile.theme));
        if (incomingTheme !== state.profile.theme) {
          applyTheme(incomingTheme, true);
        } else {
          applyTheme(state.profile.theme, false);
        }

        const safeName = sanitizeName(data.me.display_name || "");
        if (safeName && !state.profileEditing) {
          state.profile.display_name = safeName;
          if (!state.profileSheetOpen) {
            state.profileDraft = safeName;
          }
        }

        const incomingSeed = String(data.me.avatar_seed || "").trim();
        if (incomingSeed !== "") {
          state.profile.avatar_seed = incomingSeed;
        }

        state.profile.avatar_updated_at = Math.max(0, Number(data.me.avatar_updated_at) || state.profile.avatar_updated_at || 0);
        state.profile.avatar_url = stripAvatarVersion(data.me.avatar_url || state.profile.avatar_url || "");

        persistProfile();
      } else {
        state.role = "user";
        state.isOwner = false;
        state.isAdmin = false;
        state.canSendNewMessages = true;
        state.staffOnlyNewMessages = false;
      }

      if (data.live && typeof data.live === "object") {
        applyLiveConfigLocal(data.live);
      }

      state.onlineCount = Math.max(0, Number(data.online_count) || 0);
      if (Array.isArray(data.messages)) {
        state.serverMessages = data.messages;
        state.serverMessages.forEach((message) => syncAuthorMetaFromMessage(message));
        maybePlayIncomingMessageSounds(previousServerMessages, state.serverMessages);
      }
      state.hasFetchedAtLeastOnce = true;
      state.suppressNextIncomingSound = false;
      state.typingUsers = Array.isArray(data.typing_users) ? data.typing_users : [];
      state.messageRevision = Math.max(0, Number(data.message_revision) || 0);
      state.typingRevision = Math.max(0, Number(data.typing_revision) || 0);
      applyPinnedMessage(data.pinned_message ?? null);

      renderMeta(state.onlineCount);
      renderProfile();
      renderSupportLinks();
      renderStaffOnlyBanner();

      clearActiveUnreadLocal();
      renderTabs();
      renderDmUsers();
      refreshVisibleMessages();
      markCurrentSeenFromMessages();
      if (state.support.ownerPanelOpen) {
        renderOwnerPanel();
      }
      updateActionButton();

      if (includeSidebar) {
        await fetchSidebar(true, { includeUsers: shouldKeepUsersFresh() });
      }
    } catch (error) {
      setHint(error.message || "ارتباط با سرور برقرار نشد.", true);
      const nextBackoff = Math.max(getTargetPollDelay(), 2200);
      state.pollDelay = Math.min(POLL_ERROR_MAX_MS, Math.round(nextBackoff * 1.4));
      enableFallbackPolling(state.pollDelay);
    } finally {
      state.loading = false;

      if (state.pendingForceFetch) {
        state.pendingForceFetch = false;
        fetchState(true);
      } else {
        scheduleNextPoll();
      }
    }
  }

  function renderMeta(onlineCount) {
    const activeChannelLabel = boot.channels[state.channel] || state.channel;
    if (el.currentMode) {
      el.currentMode.textContent = `${activeChannelLabel}`;
    }

    const badgeStorageLabel = state.messagesStorage ? `storage: ${state.messagesStorage}` : "";
    const badgeDiagReason = state.isAdmin && state.storageInfo?.diag?.reason
      ? ` | ${String(state.storageInfo.diag.reason)}`
      : "";
    if (el.onlineBadge) {
      el.onlineBadge.textContent = `${onlineCount} آنلاین`;
      el.onlineBadge.title = badgeStorageLabel ? `${badgeStorageLabel}${badgeDiagReason}` : "";
    }

    if (!state.typingUsers.length) {
      if (el.typingIndicator) {
        el.typingIndicator.textContent = "";
      }
      return;
    }

    const typingNames = state.typingUsers
      .map((item) => sanitizeName(item.display_name || ""))
      .filter(Boolean)
      .slice(0, 2);

    if (el.typingIndicator) {
      el.typingIndicator.textContent = typingNames.length ? `${typingNames.join("، ")} در حال تایپ...` : "";
    }
    return;

    if (state.mode === "dm") {
      const peerUid = sanitizeUid(state.dmPeerUid);
      const peer = peerUid ? state.userMap.get(peerUid) : null;
      const title = peer ? String(peer.display_name || "") : "بدون انتخاب";
      if (el.currentMode) {
        el.currentMode.textContent = `دایرکت: ${title || "بدون انتخاب"}`;
      }
    } else {
      const label = boot.channels[state.channel] || state.channel;
      if (el.currentMode) {
        el.currentMode.textContent = `${label}`;
      }
    }

    if (el.onlineBadge) {
      el.onlineBadge.textContent = `${onlineCount} آنلاین`;
      const storageLabel = state.messagesStorage ? `storage: ${state.messagesStorage}` : "";
      const diagReason = state.isAdmin && state.storageInfo?.diag?.reason
        ? ` | ${String(state.storageInfo.diag.reason)}`
        : "";
      el.onlineBadge.title = storageLabel ? `${storageLabel}${diagReason}` : "";
    }

    if (!state.typingUsers.length) {
      if (el.typingIndicator) {
        el.typingIndicator.textContent = "";
      }
      return;
    }

    const names = state.typingUsers
      .map((item) => sanitizeName(item.display_name || ""))
      .filter(Boolean)
      .slice(0, 2);

    if (el.typingIndicator) {
      el.typingIndicator.textContent = names.length ? `${names.join("، ")} در حال تایپ...` : "";
    }
  }

  function renderTabs() {
    const html = Object.keys(boot.channels || {})
      .map((channelKey) => {
        const active = state.mode === "channel" && state.channel === channelKey;
        const unread = active ? 0 : Number(state.unreadHints.channels?.[channelKey] || 0);

        return `
          <button type="button" class="tab-btn ${active ? "active" : ""}" data-type="channel" data-key="${escapeHtml(channelKey)}">
            <span>${escapeHtml(boot.channels[channelKey] || channelKey)}</span>
            ${unread > 0 ? `<span class="badge">${unread}</span>` : ""}
          </button>
        `;
      })
      .join("");

    if (el.channelTabs) {
      el.channelTabs.innerHTML = html;
    }
  }

  function renderDmUsers() {
    if (state.mode !== "dm") {
      el.dmUsers.innerHTML = "";
      return;
    }

    const peers = state.users
      .map((user) => {
        const uid = sanitizeUid(String(user.uid || ""));
        return uid
          ? {
            ...user,
            uid
          }
          : null;
      })
      .filter((user) => user && user.uid !== state.profile.uid)
      .sort((a, b) => {
        if (Boolean(a.online) !== Boolean(b.online)) {
          return a.online ? -1 : 1;
        }
        return String(a.display_name || "").localeCompare(String(b.display_name || ""), "fa");
      });

    if (!peers.length) {
      el.dmUsers.innerHTML = state.loading
        ? '<p class="dim">در حال بارگذاری...</p>'
        : '<p class="dim">کاربر دیگری برای دایرکت موجود نیست.</p>';
      return;
    }

    el.dmUsers.innerHTML = peers
      .map((peer) => {
        const uid = sanitizeUid(String(peer.uid || ""));
        if (!uid || uid === state.profile.uid) {
          return "";
        }
        const active = uid === state.dmPeerUid;
        const unread = active ? 0 : Number(state.unreadHints.dms?.[uid] || 0);
        const displayName = String(peer.display_name || "Unknown");
        const seed = String(peer.avatar_seed || avatarSeedFromUid(uid));
        const avatarUpdatedAt = Math.max(0, Number(peer.avatar_updated_at) || 0);
        const avatarUrl = normalizeAvatarUrl(String(peer.avatar_url || ""), uid, seed, avatarUpdatedAt);
        const roleMarkup = badgeMarkupForRole(peer.role, "role-badge role-badge-inline");

        return `
          <button type="button" class="peer-btn ${active ? "active" : ""}" data-peer="${escapeHtml(uid)}">
            <span class="avatar peer-avatar" data-avatar-seed="${escapeHtml(seed)}" data-avatar-name="${escapeHtml(displayName)}" data-avatar-url="${escapeHtml(avatarUrl)}" data-avatar-updated-at="${avatarUpdatedAt}" data-avatar-role="${escapeHtml(String(peer.role || "user"))}"></span>
            <span class="peer-text">${escapeHtml(displayName)}${roleMarkup}</span>
            ${peer.online ? '<span class="online-dot"></span>' : ""}
            ${unread > 0 ? `<span class="badge">${unread}</span>` : ""}
          </button>
        `;
      })
      .join("");

    el.dmUsers.querySelectorAll(".peer-avatar").forEach((node) => {
      setAvatar(
        node,
        node.getAttribute("data-avatar-seed") || "",
        node.getAttribute("data-avatar-name") || "",
        node.closest("[data-peer]")?.getAttribute("data-peer") || "",
        node.getAttribute("data-avatar-url") || "",
        Number(node.getAttribute("data-avatar-updated-at") || 0),
        { viewable: true, role: node.getAttribute("data-avatar-role") || "user" }
      );
    });
  }

  function captureScrollAnchor() {
    if (isNearBottom() || !el.chatFeed.children.length) {
      return null;
    }

    const feedRect = el.chatFeed.getBoundingClientRect();
    const nodes = el.chatFeed.querySelectorAll(".msg[data-id]");
    for (const node of nodes) {
      const rect = node.getBoundingClientRect();
      if (rect.bottom > feedRect.top + 8) {
        return {
          id: String(node.getAttribute("data-id") || ""),
          topOffset: rect.top - feedRect.top
        };
      }
    }

    return null;
  }

  function restoreScrollAnchor(anchor) {
    if (!anchor || !anchor.id) {
      return;
    }

    const node = el.chatFeed.querySelector(`.msg[data-id="${selectorEscape(anchor.id)}"]`);
    if (!node) {
      return;
    }

    const feedRect = el.chatFeed.getBoundingClientRect();
    const rect = node.getBoundingClientRect();
    const delta = (rect.top - feedRect.top) - Number(anchor.topOffset || 0);
    if (Math.abs(delta) > 0.5) {
      el.chatFeed.scrollTop += delta;
    }
  }

  function captureMediaPlaybackSnapshot() {
    const snapshot = new Map();
    el.chatFeed.querySelectorAll(".msg[data-id]").forEach((msgNode) => {
      const id = String(msgNode.getAttribute("data-id") || "");
      if (!id) {
        return;
      }

      const media = msgNode.querySelector("audio, video");
      if (!(media instanceof HTMLMediaElement)) {
        return;
      }

      snapshot.set(id, {
        currentTime: Number.isFinite(media.currentTime) ? media.currentTime : 0,
        wasPlaying: !media.paused && !media.ended,
        volume: media.volume,
        muted: media.muted
      });
    });

    return snapshot;
  }

  function restoreMediaPlaybackSnapshot(snapshot) {
    if (!(snapshot instanceof Map) || snapshot.size === 0) {
      return;
    }

    snapshot.forEach((saved, id) => {
      if (!saved) {
        return;
      }

      const msgNode = el.chatFeed.querySelector(`.msg[data-id="${selectorEscape(id)}"]`);
      if (!msgNode) {
        return;
      }

      const media = msgNode.querySelector("audio, video");
      if (!(media instanceof HTMLMediaElement)) {
        return;
      }

      const applyState = () => {
        try {
          if (Number.isFinite(saved.currentTime) && saved.currentTime > 0 && Math.abs((media.currentTime || 0) - saved.currentTime) > 0.2) {
            media.currentTime = saved.currentTime;
          }
        } catch (_) {
          // ignore seek failures
        }

        if (typeof saved.volume === "number") {
          media.volume = Math.max(0, Math.min(1, saved.volume));
        }
        media.muted = Boolean(saved.muted);

        if (saved.wasPlaying) {
          media.play().catch(() => {});
        }
      };

      if (media.readyState >= 1) {
        applyState();
        return;
      }

      media.addEventListener("loadedmetadata", applyState, { once: true });
    });
  }

  function renderMessages() {
    const previousStick = isNearBottom();
    const previousCount = state.lastMessageCount;

    const renderKey = JSON.stringify({
      mode: state.mode,
      channel: state.channel,
      dm: state.dmPeerUid,
      loadingEmpty: state.loading && state.messages.length === 0 ? 1 : 0,
      ids: state.messages.map((msg) => [
        msg.id,
        msg.time,
        msg.reply_to || "",
        msg.edited_at || 0,
        msg.deleted_at || 0,
        msg._localStatus || "",
        JSON.stringify(msg.reactions || {})
      ])
    });

    if (renderKey === state.lastRenderKey) {
      return;
    }

    const scrollAnchor = captureScrollAnchor();
    const mediaSnapshot = captureMediaPlaybackSnapshot();
    state.lastRenderKey = renderKey;

    if (state.loading && !state.messages.length) {
      el.chatFeed.replaceChildren();
      const loading = document.createElement("div");
      loading.className = "empty-feed empty-feed-loading";
      loading.innerHTML = '<span class="loading-spinner" aria-hidden="true"></span><span>در حال بارگذاری...</span>';
      el.chatFeed.appendChild(loading);

      state.lastMessageCount = 0;
      state.stickToBottom = true;
      return;
    }

    if (!state.messages.length) {
      el.chatFeed.replaceChildren();
      const empty = document.createElement("div");
      empty.className = "empty-feed";
      empty.textContent = state.mode === "dm" && !sanitizeUid(state.dmPeerUid)
        ? "برای شروع دایرکت یک کاربر را انتخاب کن."
        : "هنوز پیامی ارسال نشده.";
      el.chatFeed.appendChild(empty);

      state.lastMessageCount = 0;
      state.stickToBottom = true;
      return;
    }

    const fragment = document.createDocumentFragment();
    const byId = new Map(
      state.messages
        .map((item) => [String(item.id || ""), item])
        .filter(([id]) => Boolean(id))
    );

    state.messages.forEach((message) => {
      const node = el.messageTpl.content.firstElementChild.cloneNode(true);
      const isMine = String(message.from_uid || "") === state.profile.uid;
      const localStatus = String(message._localStatus || "");
      node.dataset.id = String(message.id || "");
      node.classList.toggle("me", isMine);
      node.classList.toggle("pending", localStatus === "sending");

      const avatarEl = node.querySelector(".msg-avatar");
      const userEl = node.querySelector(".msg-user");
      const timeEl = node.querySelector(".msg-time");
      const bodyEl = node.querySelector(".msg-body");
      const metaEl = node.querySelector(".msg-meta");
      const reactSummaryEl = node.querySelector(".msg-react-summary");
      const replyRefEl = node.querySelector(".msg-reply-ref");

      const fromUid = sanitizeUid(String(message.from_uid || ""));
      const identityState = latestIdentityStateForUid(fromUid, message);
      setAvatar(
        avatarEl,
        identityState.seed || String(message.from_avatar_seed || ""),
        identityState.displayName || String(message.from_name || ""),
        fromUid,
        identityState.url || String(message.from_avatar_url || ""),
        identityState.updatedAt,
        { viewable: true, role: roleForUid(fromUid, message) }
      );
      userEl.innerHTML = renderLabelWithBadge(
        identityState.displayName || String(message.from_name || "Unknown"),
        roleForUid(fromUid, message),
        "user-label"
      );
      timeEl.textContent = formatTime(Number(message.time || 0));

      // const canOpenDm = !isMine && fromUid !== "";
      // avatarEl.removeAttribute("data-open-dm");
      // userEl.removeAttribute("data-open-dm");
      // userEl.classList.remove("dm-openable");
      userEl.removeAttribute("role");
      userEl.removeAttribute("tabindex");

      // if (canOpenDm) {
      //   avatarEl.setAttribute("data-open-dm", fromUid);
      //   userEl.setAttribute("data-open-dm", fromUid);
      //   userEl.classList.add("dm-openable");
      //   userEl.setAttribute("role", "button");
      //   userEl.setAttribute("tabindex", "0");
      // }

      replyRefEl.classList.add("hidden");
      replyRefEl.classList.remove("jumpable");
      replyRefEl.removeAttribute("data-reply-target");
      replyRefEl.removeAttribute("role");
      replyRefEl.removeAttribute("tabindex");

      if (message.reply_to) {
        const rawReplyTo = String(message.reply_to || "");
        const resolvedReplyTo = resolveMessageIdAlias(rawReplyTo);
        const ref = byId.get(resolvedReplyTo) || byId.get(rawReplyTo);
        const refText = ref
          ? (ref.deleted_at ? "پیام حذف شده" : (ref.text || describeFileMessage(ref) || "پیام"))
          : "پاسخ به پیام قدیمی";

        replyRefEl.textContent = `↩ ${refText}`;
        replyRefEl.classList.remove("hidden");

        if (ref) {
          replyRefEl.classList.add("jumpable");
          replyRefEl.setAttribute("data-reply-target", String(ref.id || resolvedReplyTo || rawReplyTo));
          replyRefEl.setAttribute("role", "button");
          replyRefEl.setAttribute("tabindex", "0");
        }
      }

      if (message.deleted_at) {
        const p = document.createElement("p");
        p.className = "deleted-text";
        p.textContent = `پیام ${identityState.displayName || "کاربر"} حذف شد.`;
        bodyEl.appendChild(p);
      } else {
        bodyEl.appendChild(buildMessageBody(message));
      }

      const metaParts = [];
      if (localStatus === "sending") {
        metaParts.push("در حال ارسال...");
      }
      if (message.edited_at) {
        metaParts.push("ویرایش‌شده");
      }
      if (message.deleted_at) {
        metaParts.push("حذف‌شده");
      }
      metaEl.textContent = metaParts.join(" · ");

      reactSummaryEl.innerHTML = renderReactionSummary(message);
      reactSummaryEl.classList.toggle("hidden", reactSummaryEl.innerHTML.trim() === "");

      fragment.appendChild(node);
    });

    el.chatFeed.replaceChildren(fragment);

    const firstPaintForConversation = previousCount === 0 && state.messages.length > 0;
    const shouldAutoScroll = state.forceScrollOnce || previousStick || firstPaintForConversation;

    if (shouldAutoScroll) {
      scrollToBottom({ force: true, smooth: false });
    } else {
      restoreScrollAnchor(scrollAnchor);
    }

    state.lastMessageCount = state.messages.length;
    state.forceScrollOnce = false;

    requestAnimationFrame(() => {
      restoreMediaPlaybackSnapshot(mediaSnapshot);
      updateStickToBottom();
    });
  }

  function isMessageInCurrentContext(message) {
    if (!message || typeof message !== "object") {
      return false;
    }

    const scope = String(message.scope || "channel");
    if (scope === "dm") {
      return false;
    }

    return chatChannelKey(message) === state.channel;
  }

  function chatChannelKey(message) {
    const raw = String(message.channel || "main");
    return Object.prototype.hasOwnProperty.call(boot.channels || {}, raw) ? raw : "main";
  }

  function resolveMessageIdAlias(messageId) {
    let current = String(messageId || "");
    if (!current) {
      return "";
    }

    const visited = new Set();
    while (current && state.replyAliases.has(current) && !visited.has(current)) {
      visited.add(current);
      current = String(state.replyAliases.get(current) || "");
    }

    return current || String(messageId || "");
  }

  function upsertServerMessageLocal(incoming) {
    if (!incoming || typeof incoming !== "object") {
      return false;
    }

    const id = String(incoming.id || "");
    if (!id || !isMessageInCurrentContext(incoming)) {
      return false;
    }

    syncAuthorMetaFromMessage(incoming);

    const index = state.serverMessages.findIndex((msg) => String(msg.id || "") === id);
    if (index >= 0) {
      state.serverMessages[index] = { ...state.serverMessages[index], ...incoming };
    } else {
      state.serverMessages.push(incoming);
    }

    state.serverMessages.sort((a, b) => Number(a.time || 0) - Number(b.time || 0));
    return true;
  }

  function refreshVisibleMessages() {
    const combined = [...state.serverMessages];
    const seen = new Set(combined.map((message) => String(message.id || "")).filter(Boolean));

    state.localOnlyMessages.forEach((message, id) => {
      if (!isMessageInCurrentContext(message) || !id || seen.has(id)) {
        return;
      }

      combined.push(message);
      seen.add(id);
    });

    state.pendingMessages.forEach((message) => {
      if (!isMessageInCurrentContext(message)) {
        return;
      }

      const id = String(message.id || "");
      if (!id || seen.has(id)) {
        return;
      }

      combined.push(message);
      seen.add(id);
    });

    combined.sort((a, b) => Number(a.time || 0) - Number(b.time || 0));
    state.messages = combined;
    syncCurrentConversationCache();

    if (state.contextMenu.messageId && !state.messages.some((item) => String(item.id || "") === state.contextMenu.messageId)) {
      closeMessageMenu();
    }

    renderMessages();
  }

  function remapPendingReplyTargets(fromId, toId) {
    const safeFrom = String(fromId || "");
    const safeTo = String(toId || "");
    if (!safeFrom || !safeTo || safeFrom === safeTo) {
      return;
    }

    state.pendingMessages.forEach((item, itemId) => {
      if (String(item.reply_to || "") === safeFrom) {
        state.pendingMessages.set(itemId, { ...item, reply_to: safeTo });
      }
    });

    if (state.compose.mode === "reply" && String(state.compose.messageId || "") === safeFrom) {
      setComposeState("reply", safeTo, state.compose.preview);
    }
  }

  function addPendingMessageLocal(message) {
    if (!message || typeof message !== "object") {
      return false;
    }

    const id = String(message.id || "");
    if (!id) {
      return false;
    }

    state.pendingMessages.set(id, {
      ...message,
      _localStatus: "sending"
    });
    state.forceScrollOnce = true;
    refreshVisibleMessages();
    return true;
  }

  function upsertLocalOnlyMessage(message) {
    if (!message || typeof message !== "object") {
      return false;
    }

    const id = String(message.id || "");
    if (!id) {
      return false;
    }

    state.localOnlyMessages.set(id, {
      ...message,
      _localOnly: true
    });
    state.forceScrollOnce = true;
    refreshVisibleMessages();
    return true;
  }

  function removeLocalOnlyMessage(messageId) {
    const safeId = String(messageId || "");
    if (!safeId) {
      return false;
    }

    const message = state.localOnlyMessages.get(safeId);
    if (!message) {
      return false;
    }

    const maybeBlobUrl = String(message.file?.url || "");
    if (maybeBlobUrl.startsWith("blob:")) {
      URL.revokeObjectURL(maybeBlobUrl);
    }

    state.localOnlyMessages.delete(safeId);
    refreshVisibleMessages();
    return true;
  }

  function discardPendingMessageLocal(messageId) {
    const safeId = String(messageId || "");
    if (!safeId) {
      return false;
    }

    const pending = state.pendingMessages.get(safeId);
    if (!pending) {
      return false;
    }

    const maybeBlobUrl = String(pending.file?.url || "");
    if (maybeBlobUrl.startsWith("blob:")) {
      URL.revokeObjectURL(maybeBlobUrl);
    }

    state.pendingMessages.delete(safeId);
    refreshVisibleMessages();
    return true;
  }

  function resolvePendingMessageLocal(localId, incoming) {
    const safeLocalId = String(localId || "");
    if (!safeLocalId || !incoming || typeof incoming !== "object") {
      return false;
    }

    const safeIncomingId = String(incoming.id || "");
    const pending = state.pendingMessages.get(safeLocalId);
    const isLocalOnly = Boolean(incoming.local_only || incoming._localOnly);

    if (pending) {
      const maybeBlobUrl = String(pending.file?.url || "");
      if (isLocalOnly && pending.file && (!incoming.file || !String(incoming.file.url || "").trim())) {
        incoming = {
          ...incoming,
          file: {
            ...pending.file,
            ...(incoming.file && typeof incoming.file === "object" ? incoming.file : {})
          }
        };
      }

      if (maybeBlobUrl.startsWith("blob:") && !isLocalOnly) {
        URL.revokeObjectURL(maybeBlobUrl);
      }
      state.pendingMessages.delete(safeLocalId);
    }

    if (safeIncomingId && safeIncomingId !== safeLocalId) {
      rememberReplyAlias(safeLocalId, safeIncomingId);
      remapPendingReplyTargets(safeLocalId, safeIncomingId);
    }

    if (isLocalOnly) {
      upsertLocalOnlyMessage(incoming);
    } else {
      upsertServerMessageLocal(incoming);
    }
    state.forceScrollOnce = true;
    refreshVisibleMessages();
    return true;
  }

  function mergeMessageLocal(incoming) {
    if (!incoming || typeof incoming !== "object") {
      return false;
    }

    const id = String(incoming.id || "");
    if (!id) {
      return false;
    }

    if (state.pendingMessages.has(id)) {
      const current = state.pendingMessages.get(id);
      state.pendingMessages.set(id, { ...current, ...incoming });
      refreshVisibleMessages();
      return true;
    }

    if (!upsertServerMessageLocal(incoming)) {
      return false;
    }

    state.forceScrollOnce = shouldAutoFollowIncomingMessage(incoming);
    refreshVisibleMessages();
    return true;
  }

  function makeLocalOutgoingMessageId() {
    return `tmp_${Date.now().toString(36)}_${Math.random().toString(36).slice(2, 8)}`;
  }

  function buildOutgoingTextMessage(id, text, context, replyTo = "") {
    const item = {
      id,
      time: Math.floor(Date.now() / 1000),
      scope: context.scope,
      from_uid: state.profile.uid,
      from_name: state.profile.display_name,
      from_avatar_seed: state.profile.avatar_seed,
      from_avatar_url: state.profile.avatar_url,
      type: "text",
      text,
      reactions: {}
    };

    if (context.scope === "dm") {
      item.dm_to_uid = context.to_uid;
    } else {
      item.channel = context.channel || "main";
    }

    const resolvedReplyTo = resolveMessageIdAlias(replyTo);
    if (resolvedReplyTo) {
      item.reply_to = resolvedReplyTo;
    }

    return item;
  }

  function buildOutgoingFileMessage(id, file, caption, context, replyTo = "") {
    const mime = String(file?.type || "").toLowerCase();
    const type = mime.startsWith("image/")
      ? "image"
      : (mime.startsWith("video/") ? "video" : "voice");

    let previewUrl = "";
    try {
      previewUrl = URL.createObjectURL(file);
    } catch (_) {
      previewUrl = "";
    }

    const item = {
      id,
      time: Math.floor(Date.now() / 1000),
      scope: context.scope,
      from_uid: state.profile.uid,
      from_name: state.profile.display_name,
      from_avatar_seed: state.profile.avatar_seed,
      from_avatar_url: state.profile.avatar_url,
      type,
      text: caption || "",
      reactions: {},
      file: {
        url: previewUrl,
        name: String(file?.name || "file"),
        size: Math.max(0, Number(file?.size || 0)),
        mime: String(file?.type || "application/octet-stream")
      }
    };

    if (context.scope === "dm") {
      item.dm_to_uid = context.to_uid;
    } else {
      item.channel = context.channel || "main";
    }

    const resolvedReplyTo = resolveMessageIdAlias(replyTo);
    if (resolvedReplyTo) {
      item.reply_to = resolvedReplyTo;
    }

    return item;
  }

  function replaceMessageLocal(oldId, incoming) {
    return resolvePendingMessageLocal(oldId, incoming);
  }

  function removeMessageLocal(messageId) {
    const safeId = String(messageId || "");
    if (!safeId) {
      return false;
    }

    if (removeLocalOnlyMessage(safeId)) {
      return true;
    }

    if (state.pendingMessages.has(safeId)) {
      return discardPendingMessageLocal(safeId);
    }

    const before = state.serverMessages.length;
    state.serverMessages = state.serverMessages.filter((message) => String(message.id || "") !== safeId);
    if (state.serverMessages.length === before) {
      return false;
    }

    refreshVisibleMessages();
    return true;
  }

  function applyReactionLocal(messageId, reactions) {
    const safeId = String(messageId || "");
    if (!safeId) {
      return;
    }

    const index = state.serverMessages.findIndex((message) => String(message.id || "") === safeId);
    if (index < 0) {
      return;
    }

    const next = reactions && typeof reactions === "object" ? reactions : {};
    state.serverMessages[index] = {
      ...state.serverMessages[index],
      reactions: next
    };

    refreshVisibleMessages();
  }

  function buildMessageBody(message) {
    const wrap = document.createElement("div");

    // بخش‌های مربوط به انواع فایل‌ها (تصویر، صدا، ویدئو، فایل) بدون تغییر باقی می‌مانند
    if (message.type === "image" && message.file?.url) {
      const img = document.createElement("img");
      img.src = message.file.url;
      // img.alt = message.file.name || "image";
      img.loading = "lazy";
      img.className = "msg-image";

      img.setAttribute('data-full-src', message.file.url);

      img.addEventListener("load", () => {
        if (state.stickToBottom) {
          scrollToBottom({ force: true, smooth: false });
        }
      }, { once: true });
      wrap.appendChild(img);
    } else if ((message.type === "voice" || message.type === "audio") && message.file?.url) {
      const audio = document.createElement("audio");
      audio.controls = true;
      audio.preload = "metadata";
      audio.className = "msg-audio";
      audio.src = message.file.url;
      audio.addEventListener("loadedmetadata", () => {
        if (state.stickToBottom) {
          scrollToBottom({ force: true, smooth: false });
        }
      }, { once: true });
      wrap.appendChild(audio);
    } else if (message.type === "video" && message.file?.url) {
      const video = document.createElement("video");
      video.controls = true;
      video.preload = "metadata";
      video.className = "msg-video";
      video.src = message.file.url;
      video.addEventListener("loadedmetadata", () => {
        if (state.stickToBottom) {
          scrollToBottom({ force: true, smooth: false });
        }
      }, { once: true });
      wrap.appendChild(video);
    } else if (message.file?.url) {
      const link = document.createElement("a");
      link.href = message.file.url;
      link.target = "_blank";
      link.rel = "noopener noreferrer";
      link.className = "msg-file";
      link.textContent = `دانلود ${message.file.name || "فایل"}`;
      wrap.appendChild(link);
    }

    // بخش پردازش متن پیام (اینجا تغییرات اعمال شده است)
      if (message.text) {
        const text = message.text;
        const urlRegex = /(https?:\/\/[^\s]+|www\.[^\s]+)/g;
        let lastIndex = 0;
        let match;
        let hasLinks = false;
    
        // پردازش متن برای یاتن و تبدیل URLها
        while ((match = urlRegex.exec(text)) !== null) {
          hasLinks = true;
          if (match.index > lastIndex) {
            const textNode = document.createTextNode(text.substring(lastIndex, match.index));
            wrap.appendChild(textNode); // اضاه کردن متن معمولی به 'wrap'
          }
    
          const link = document.createElement("a");
          const url = match[0].startsWith('www.') ? 'http://' + match[0] : match[0];
          link.href = url;
          link.textContent = match[0];
          link.target = "_blank";
          link.rel = "noopener noreferrer";
          link.className = "msg-link";
          wrap.appendChild(link); // اضاه کردن لینک به 'wrap'
    
          lastIndex = urlRegex.lastIndex;
        }
    
        // اضاه کردن متن باقی‌مانده
        if (lastIndex < text.length) {
          const textNode = document.createTextNode(text.substring(lastIndex));
          wrap.appendChild(textNode); // اضاه کردن متن باقی‌مانده به 'wrap'
        }
      }
    
      // اگر هیچ محتوایی وجود نداشت
      if (!wrap.childNodes.length) {
        const p = document.createElement("p");
        p.textContent = "[بدون متن]";
        wrap.appendChild(p); // اضاه کردن [بدون متن] به 'wrap'
      }
    
      return wrap; // این 'wrap' که حاوی همه چیز است، برگردانده می‌شود
    
  }


  function renderReactionSummary(message) {
    const reactions = message.reactions && typeof message.reactions === "object" ? message.reactions : {};
    const messageId = String(message.id || "");

    return Object.entries(reactions)
      .map(([emoji, list]) => ({
        emoji,
        count: Array.isArray(list) ? list.length : 0,
        active: Array.isArray(list) ? list.includes(state.profile.uid) : false
      }))
      .filter((item) => item.count > 0)
      .slice(0, 6)
      .map((item) => `<button type="button" class="react-chip ${item.active ? "active" : ""}" data-react-id="${escapeHtml(messageId)}" data-react-emoji="${escapeHtml(item.emoji)}">${item.emoji} ${item.count}</button>`)
      .join("");
  }

  function setComposeState(mode, messageId, preview) {
    state.compose.mode = mode;
    state.compose.messageId = mode === "reply" ? resolveMessageIdAlias(messageId) : messageId;
    state.compose.preview = preview || "";

    if (mode === "none") {
      el.composeState.classList.add("hidden");
      el.composePreview.textContent = "";
      return;
    }

    const label = mode === "edit" ? "در حال ویرایش" : "پاسخ به";
    el.composePreview.textContent = `${label}: ${state.compose.preview}`;
    el.composeState.classList.remove("hidden");
  }

  function clearComposeState() {
    setComposeState("none", "", "");
    updateActionButton();
  }

  function composerLockedForNewMessages() {
    return !state.canSendNewMessages && state.compose.mode !== "edit";
  }

  function applyComposerAccess() {
    const locked = composerLockedForNewMessages();

    if (locked && document.activeElement === el.messageInput) {
      el.messageInput.blur();
    }

    el.messageInput.disabled = locked;
    el.messageInput.placeholder = locked ? STAFF_ONLY_NEW_MESSAGES_HINT : DEFAULT_MESSAGE_PLACEHOLDER;
    el.attachBtn.disabled = locked || state.actionBusy || Boolean(state.recording);
    el.emojiBtn.disabled = locked;

    if (locked) {
      el.emojiPicker.classList.add("hidden");
    }
  }

  function updateActionButton() {
    const applyActionVisual = (mode, title) => {
      el.actionBtn.dataset.mode = mode;
      el.actionBtn.title = title;
      el.actionBtn.setAttribute("aria-label", title);
      el.actionBtn.innerHTML = ACTION_ICONS[mode] || ACTION_ICONS.mic;
    };

    const hasText = Boolean(sanitizeMessageText(el.messageInput.value));
    const hasFile = Boolean(state.pendingFile);
    const busy = state.actionBusy || state.uploadVisible;
    const cooldownMs = state.compose.mode === "edit" ? 0 : remainingSendCooldownMs();
    const cooldownActive = cooldownMs > 0;
    const cooldownSeconds = Math.max(1, Math.ceil(cooldownMs / 1000));
    const lockedForNewMessages = composerLockedForNewMessages();

    applyComposerAccess();
    el.actionBtn.classList.toggle("busy", busy);

    if (state.recording) {
      applyActionVisual("stop", "توقف ضبط");
      el.actionBtn.disabled = false;
      return;
    }

    if (lockedForNewMessages) {
      applyActionVisual("send", "ارسال فقط برای ادمین‌ها و اونرها فعاله");
      el.actionBtn.disabled = true;
      return;
    }

    if (hasText || hasFile) {
      if (state.compose.mode === "edit") {
        applyActionVisual("send", "ذخیره ویرایش");
        el.actionBtn.disabled = busy;
        return;
      }

      applyActionVisual("send", cooldownActive ? `صبر: ${cooldownSeconds} ثانیه` : "ارسال پیام");
      el.actionBtn.disabled = busy || cooldownActive;
      return;
    }

    applyActionVisual("mic", cooldownActive ? `تا ${cooldownSeconds} ثانیه دیگر` : "ضبط ویس");
    el.actionBtn.disabled = busy || cooldownActive;
  }

  function maybeSendTyping() {
    const text = sanitizeMessageText(el.messageInput.value);
    if (!text || state.recording || composerLockedForNewMessages()) {
      cancelPendingTypingTimer();
      return;
    }

    const context = getSendContext();
    if (!context.ok) {
      cancelPendingTypingTimer();
      return;
    }

    const now = Date.now();
    if (now - state.typingAt > TYPING_PING_MS) {
      sendTyping(context);
      return;
    }

    cancelPendingTypingTimer();

    state.typingTimer = window.setTimeout(() => {
      sendTyping(context);
    }, TYPING_PING_MS);
  }

  async function sendTyping(context) {
    cancelPendingTypingTimer();
    state.typingAt = Date.now();

    const formData = new FormData();
    baseIdentity(formData);

    formData.set("action", "typing");
    formData.set("channel", context.channel || "main");
    markActivityBurst();

    try {
      await fetch("api.php", { method: "POST", body: formData });
      state.lastPresenceTouchAt = Date.now();
    } catch (_) {
      // ignore
    }
  }

  async function startRecording() {
    cancelPendingTypingTimer();
    if (composerLockedForNewMessages()) {
      setHint(STAFF_ONLY_NEW_MESSAGES_HINT, true);
      return;
    }

    const context = getSendContext();
    if (!context.ok) {
      setHint(context.error, true);
      return;
    }

    if (!navigator.mediaDevices || !window.MediaRecorder) {
      setHint("مرورگر شما ضبط ویس را پشتیبانی نمی‌کند.", true);
      return;
    }

    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      const chunks = [];
      const recorder = new MediaRecorder(stream);

      recorder.addEventListener("dataavailable", (event) => {
        if (event.data && event.data.size > 0) {
          chunks.push(event.data);
        }
      });

      recorder.addEventListener("stop", () => {
        stream.getTracks().forEach((track) => track.stop());
        state.mediaStream = null;

        if (!chunks.length) {
          setHint("ویسی ضبط نشد.", true);
          return;
        }

        const blob = new Blob(chunks, { type: recorder.mimeType || "audio/webm" });
        const file = new File([blob], `voice_${Date.now()}.webm`, { type: blob.type || "audio/webm" });
        const ok = setPendingFile(file);
        if (ok) {
          setHint("ویس ضبط شد. برای ارسال دکمه ارسال را بزن یا با حذف فایل لغوش کن.");
        } else {
          setHint("ویس ضبط شد اما به‌عنوان فایل پیوست نشد.", true);
        }
        updateActionButton();
      });

      state.mediaStream = stream;
      state.recording = recorder;
      recorder.start();
      el.attachBtn.disabled = true;
      el.clearFileBtn.disabled = true;
      updateActionButton();
      setHint("ضبط شروع شد، برای توقف دوباره بزن.");
    } catch (_) {
      setHint("دسترسی میکروفون رد شد یا خطا رخ داد.", true);
    }
  }

  function stopRecording() {
    if (!state.recording) {
      return;
    }

    const recorder = state.recording;
    state.recording = null;

    if (recorder.state !== "inactive") {
      recorder.stop();
    }

    if (state.mediaStream) {
      state.mediaStream.getTracks().forEach((track) => track.stop());
      state.mediaStream = null;
    }

    el.attachBtn.disabled = state.actionBusy;
    el.clearFileBtn.disabled = state.actionBusy;
    updateActionButton();
  }

  function currentConversationKey() {
    return `ch:${state.channel}`;
  }

  function currentSeenAt() {
    return Math.max(0, Number(state.seenDirtyMap[currentConversationKey()] || 0));
  }

  function latestMessageSeenAt(messages = state.messages) {
    return (Array.isArray(messages) ? messages : []).reduce((latest, message) => {
      const time = Math.max(0, Number(message?.time) || 0);
      return time > latest ? time : latest;
    }, 0);
  }

  function markCurrentSeen(serverTs) {
    const key = currentConversationKey();
    const ts = Number(serverTs) || Math.floor(Date.now() / 1000);
    const prev = Number(state.seenMap[key] || 0);

    if (ts > prev) {
      state.seenMap[key] = ts;
      state.seenDirtyMap[key] = Math.max(Number(state.seenDirtyMap[key] || 0), ts);
      saveSeenMap();
    }
  }

  function markCurrentSeenFromMessages() {
    const latest = latestMessageSeenAt();
    if (latest > 0) {
      markCurrentSeen(latest);
    }
  }

  function clearActiveUnreadLocal() {
    if (state.unreadHints.channels && state.channel in state.unreadHints.channels) {
      state.unreadHints.channels[state.channel] = 0;
    }
  }

  function getBottomFollowThreshold() {
    if (!el.chatFeed) {
      return 120;
    }

    const nodes = Array.from(el.chatFeed.querySelectorAll(".msg"));
    const lastTwo = nodes.slice(-2);
    if (!lastTwo.length) {
      return 120;
    }

    const totalHeight = lastTwo.reduce((sum, node) => sum + Math.max(0, node.offsetHeight || 0), 0);
    return Math.max(72, Math.min(220, totalHeight + 24));
  }

  function isNearBottom() {
    if (!el.chatFeed) {
      return true;
    }

    const gap = el.chatFeed.scrollHeight - el.chatFeed.scrollTop - el.chatFeed.clientHeight;
    return gap <= getBottomFollowThreshold();
  }

  function shouldAutoFollowIncomingMessage(message) {
    if (isNearBottom()) {
      return true;
    }

    return sanitizeUid(String(message?.from_uid || "")) === state.profile.uid;
  }

  function updateStickToBottom() {
    state.stickToBottom = isNearBottom();
  }

  function scrollToBottom({ force = false, smooth = false } = {}) {
    if (!force && !state.stickToBottom) {
      return;
    }

    const behavior = smooth ? "smooth" : "auto";
    el.chatFeed.scrollTo({ top: el.chatFeed.scrollHeight, behavior });
    state.stickToBottom = true;
  }

  function resetConversationView(options = {}) {
    cancelPendingTypingTimer();
    state.lastRenderKey = "";
    state.lastMessageCount = 0;
    state.stickToBottom = true;
    state.forceScrollOnce = true;
    state.suppressNextIncomingSound = true;
    if (options.restoreCache && restoreConversationFromCache()) {
      return;
    }

    state.messageRevision = 0;
    state.typingRevision = 0;
    state.serverMessages = [];
    state.typingUsers = [];
    state.pinnedMessage = null;
    renderMeta(state.onlineCount);
    renderPinnedBar();
    refreshVisibleMessages();
  }

  function rememberReplyAlias(fromId, toId) {
    const safeFrom = String(fromId || "");
    const safeTo = String(toId || "");
    if (!safeFrom || !safeTo) {
      return;
    }

    if (state.replyAliases.has(safeFrom)) {
      state.replyAliases.delete(safeFrom);
    }
    state.replyAliases.set(safeFrom, safeTo);

    while (state.replyAliases.size > MAX_REPLY_ALIASES) {
      const oldestKey = state.replyAliases.keys().next().value;
      if (!oldestKey) {
        break;
      }
      state.replyAliases.delete(oldestKey);
    }
  }

  function selectorEscape(value) {
    if (window.CSS && typeof window.CSS.escape === "function") {
      return window.CSS.escape(String(value || ""));
    }

    return String(value || "").replace(/["\\]/g, "\\$&");
  }

  function jumpToMessage(messageId) {
    const resolvedId = resolveMessageIdAlias(messageId);
    if (!resolvedId) {
      return false;
    }

    const node = el.chatFeed.querySelector(`.msg[data-id="${selectorEscape(resolvedId)}"]`);
    if (!node) {
      setHint("پیام مرجع در تاریخچه فعلی پیدا نشد.", true);
      return false;
    }

    node.scrollIntoView({ behavior: "smooth", block: "center", inline: "nearest" });
    node.classList.remove("jump-highlight");
    requestAnimationFrame(() => {
      node.classList.add("jump-highlight");
      window.setTimeout(() => {
        node.classList.remove("jump-highlight");
      }, 1300);
    });

    return true;
  }

  function describeFileMessage(message) {
    if (!message.file) {
      return "";
    }

    if (message.type === "image") {
      return "تصویر";
    }

    if (message.type === "voice") {
      return "ویس";
    }

    if (message.type === "audio") {
      return "صوت";
    }

    if (message.type === "video") {
      return "ویدیو";
    }

    return message.file.name || "فایل";
  }

  function formatTime(unixSec) {
    if (!unixSec) {
      return "--:--";
    }

    try {
      return new Date(unixSec * 1000).toLocaleTimeString("fa-IR", {
        hour: "2-digit",
        minute: "2-digit"
      });
    } catch (_) {
      return "--:--";
    }
  }

  function isInteractiveMessageTarget(target) {
    if (!(target instanceof Element)) {
      return false;
    }

    return Boolean(target.closest("a, button, audio, input, textarea, select"));
  }

  function cancelLongPress() {
    if (state.contextMenu.pressTimer) {
      window.clearTimeout(state.contextMenu.pressTimer);
      state.contextMenu.pressTimer = null;
    }
  }

  function closeMessageMenu() {
    cancelLongPress();
    state.contextMenu.messageId = "";
    el.messageMenu.classList.add("hidden");
    el.messageMenu.setAttribute("aria-hidden", "true");

    const card = el.messageMenu.querySelector(".msg-menu-card");
    if (card) {
      card.style.left = "";
      card.style.top = "";
    }
  }

  function openMessageMenu(messageId, x, y) {
    cancelLongPress();
    const message = state.messages.find((item) => String(item.id || "") === String(messageId || ""));
    if (!message) {
      return;
    }

    state.contextMenu.messageId = messageId;
    renderMessageMenu(message);

    el.messageMenu.classList.remove("hidden");
    el.messageMenu.setAttribute("aria-hidden", "false");

    const card = el.messageMenu.querySelector(".msg-menu-card");
    if (!card) {
      return;
    }

    if (window.innerWidth <= 760 || IS_TOUCH_DEVICE) {
      card.style.left = "";
      card.style.top = "";
      return;
    }

    const rect = card.getBoundingClientRect();
    const safeX = Math.max(12, Math.min(x - rect.width / 2, window.innerWidth - rect.width - 12));
    const safeY = Math.max(12, Math.min(y - 8, window.innerHeight - rect.height - 12));

    card.style.left = `${safeX}px`;
    card.style.top = `${safeY}px`;
  }

  function renderMessageMenu(message) {
    const isMine = String(message.from_uid || "") === state.profile.uid;
    const fromUid = sanitizeUid(String(message.from_uid || ""));
    const messageId = String(message.id || "");
    const isLocalOnly = Boolean(message._localStatus || message._localOnly || message.local_only);
    const isAdmin = Boolean(state.isAdmin);
    const isOwner = Boolean(state.isOwner);
    const canReact = !message.deleted_at && !isLocalOnly;
    const targetShadowbanned = isUserShadowbanned(fromUid);
    const isPinnedMessage = String(state.pinnedMessage?.message?.id || "") === messageId;
    // VAMPIRE ADD IT
    const selectedText = getSelectedText();

    el.messageMenuReactions.innerHTML = canReact
      ? boot.quickReactions.map((emoji) => {
        const list = Array.isArray(message.reactions?.[emoji]) ? message.reactions[emoji] : [];
        const active = list.includes(state.profile.uid);
        const count = list.length;

        return `
          <button type="button" class="menu-react-btn ${active ? "active" : ""}" data-menu-action="react" data-id="${escapeHtml(messageId)}" data-emoji="${emoji}">
            <span>${emoji}</span>
            ${count > 0 ? `<small>${count}</small>` : ""}
          </button>
        `;
      }).join("")
      : ""; 

    const actions = [
      `<button type="button" class="menu-action-btn" data-menu-action="reply" data-id="${escapeHtml(messageId)}">پاسخ</button>`,
      `<button type="button" class="menu-action-btn" data-menu-action="copy" data-id="${escapeHtml(messageId)}">کپی</button>` 
    ];

    // VAMPIRE ADD IT
    if (!isLocalOnly && selectedText) {
      actions.push(`<button type="button" class="menu-action-btn" data-menu-action="copy_selected_text" data-id="${escapeHtml(messageId)}">کپی متن انتخاب شده</button>`);
    }

    if (!isLocalOnly && fromUid && (isAdmin || isOwner)) {
      actions.push(`<button type="button" class="menu-action-btn" data-menu-action="copy_uid" data-id="${escapeHtml(messageId)}">کپی UID</button>`);
    }

    if (!isLocalOnly && isOwner && !message.deleted_at) {
      actions.push(`<button type="button" class="menu-action-btn" data-menu-action="pin_toggle" data-id="${escapeHtml(messageId)}">${isPinnedMessage ? "برداشتن پین" : "پین کردن"}</button>`);
    }


    // if (!isLocalOnly && !isMine) {
    //   actions.unshift(`<button type="button" class="menu-action-btn" data-menu-action="direct" data-id="${escapeHtml(messageId)}">دایرکت</button>`);
    // }

    if (!isLocalOnly && isMine && !message.deleted_at) {
      actions.push(`<button type="button" class="menu-action-btn" data-menu-action="edit" data-id="${escapeHtml(messageId)}">ویرایش</button>`);
    }

    const targetIsStaff = isUserStaff(fromUid);

    if (!isLocalOnly && isAdmin) {
      actions.push(`<button type="button" class="menu-action-btn danger" data-menu-action="admin_delete" data-id="${escapeHtml(messageId)}">حذف کامل پیام</button>`);
      if (!isMine && fromUid && fromUid !== state.profile.uid) {
        if (!targetIsStaff) {
          actions.push(`<button type="button" class="menu-action-btn danger" data-menu-action="shadowban_toggle" data-id="${escapeHtml(messageId)}" data-shadowbanned="${targetShadowbanned ? "1" : "0"}">${targetShadowbanned ? "برداشتن شادوبن" : "شادوبن"}</button>`);
        }
        actions.push(`<button type="button" class="menu-action-btn danger" data-menu-action="admin_delete_user_messages" data-id="${escapeHtml(messageId)}">حذف همه پیام‌های این کاربر</button>`);
      }
    } else if (!isLocalOnly && isMine && !message.deleted_at) {
      actions.push(`<button type="button" class="menu-action-btn danger" data-menu-action="delete" data-id="${escapeHtml(messageId)}">حذف</button>`);
    }

    if (isLocalOnly) {
      actions.push(`<button type="button" class="menu-action-btn danger" data-menu-action="remove_local" data-id="${escapeHtml(messageId)}">حذف محلی</button>`);
    }

    el.messageMenuActions.innerHTML = actions.join("");
  }


  // VAMPIRE ADD IT

  // Get Select Text func
  function getSelectedText() {
    if (window.getSelection) {
        return window.getSelection().toString();
    } else if (document.selection && document.selection.type !== "Control") {
        return document.selection.createRange().text;
    }
    return '';
  }

  
  el.messageMenuActions.addEventListener('click', function(event) {
      const target = event.target;
      const action = target.getAttribute('data-menu-action');
      const messageId = target.getAttribute('data-id'); 

      if (action === 'copy_selected_text') {
       
          const selectedText = getSelectedText(); 
          if (selectedText) {
              navigator.clipboard.writeText(selectedText).then(function() {
                  console.log('متن انتخاب شده کپی شد!');
                  setHint('متن انتخاب شده کپی شد!'); 
                  closeMessageMenu();
              }).catch(function(err) {
                  console.error('خطا در کپی کردن متن انتخاب شده: ', err);
                  setHint('متاسفانه کپی کردن متن انتخاب شده با خطا مواجه شد.');
                  closeMessageMenu();
              });
          } else {
              setHint('لطفاً ابتدا متنی را برای کپی کردن انتخاب کنید.');
              closeMessageMenu();
          }
      } 
  });



  function escapeHtml(value) {
    return String(value)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#39;");
  }

  function normalizeRole(role) {
    return role === "owner" || role === "admin" ? role : "user";
  }

  function roleForUid(uid, fallback = null) {
    const safeUid = sanitizeUid(uid);
    if (!safeUid) {
      return roleFromMessageFallback(fallback);
    }
    if (safeUid === sanitizeUid(state.profile?.uid || "")) {
      return normalizeRole(state.role);
    }
    const mappedRole = normalizeRole(String(state.userMap.get(safeUid)?.role || "user"));
    if (mappedRole !== "user") {
      return mappedRole;
    }
    return roleFromMessageFallback(fallback);
  }

  function badgeMarkupForRole(role, className = "role-badge") {
    const safeRole = normalizeRole(role);
    const asset = BADGE_ASSETS[safeRole];
    if (!asset) {
      return "";
    }

    const label = safeRole === "owner" ? "Owner" : "Admin";
    const fallback = safeRole === "owner" ? "♛" : "★";
    return `<img src="${asset}" class="${className}" alt="${label}" width="14" height="14" decoding="async" loading="eager" onerror="this.hidden=true; if (this.nextElementSibling) this.nextElementSibling.hidden=false;"><span class="role-badge-fallback" hidden aria-label="${label}">${fallback}</span>`;
  }

  function renderLabelWithBadge(label, role, className = "user-label") {
    return `
      <span class="${className}">
        <span class="${className}-text">${escapeHtml(label)}</span>
        ${badgeMarkupForRole(role)}
      </span>
    `;
  }

  function isUserShadowbanned(uid) {
    const safeUid = sanitizeUid(uid);
    if (!safeUid) {
      return false;
    }
    return Boolean(state.userMap.get(safeUid)?.is_shadowbanned);
  }

  function isUserStaff(uid) {
    const safeUid = sanitizeUid(uid);
    if (!safeUid) {
      return false;
    }

    if (safeUid === state.profile.uid) {
      return Boolean(state.isAdmin || state.isOwner);
    }

    const role = String(state.userMap.get(safeUid)?.role || "");
    return role === "admin" || role === "owner";
  }

  function setHint(message, isError = false) {
    if (isError) {
      console.error(message);
    } else {
      console.log(message);
    }

    if (!el.composerHint) {
      return;
    }

    const text = String(message || "").trim();
    if (!text) {
      el.composerHint.textContent = "";
      el.composerHint.classList.remove("is-visible", "error");
      return;
    }

    el.composerHint.textContent = text;
    el.composerHint.classList.toggle("error", isError);
    el.composerHint.classList.add("is-visible");

    if (setHint.timer) {
      window.clearTimeout(setHint.timer);
    }
    setHint.timer = window.setTimeout(() => {
      el.composerHint.classList.remove("is-visible");
    }, isError ? 5200 : 3200);
  }

  // VAMPIRE ADD IT

  // IMG POPUP
  document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById("imageModal");
    const modalImg = document.getElementById("modalImage");
    const captionText = document.getElementById("caption");
    const closeBtn = modal.querySelector(".close-button");
    const downloadLink = document.getElementById("downloadLink");

    const messagesContainer = document.querySelector('.msg-card') || document.body;

    messagesContainer.addEventListener('click', (event) => {
        if (event.target.classList.contains('msg-image')) {
            const clickedImg = event.target;
            const imageUrl = clickedImg.getAttribute('data-full-src');
            const imageAlt = clickedImg.alt;

            modal.style.display = "flex";
            modalImg.src = imageUrl;
            captionText.innerHTML = imageAlt || '';

            if (downloadLink) {
                downloadLink.href = imageUrl;
                let fileName = "downloaded_image";
                if (imageUrl) {
                    let urlParts = imageUrl.split('/');
                    let potentialFileName = urlParts[urlParts.length - 1];
                    if (potentialFileName && potentialFileName.includes('.')) {
                        fileName = potentialFileName;
                    }
                }
                downloadLink.setAttribute('download', fileName);
            }
        }
    });

    if (closeBtn) {
        closeBtn.onclick = function() {
            modal.style.display = "none";
            modalImg.src = "";
            if (downloadLink) {
                downloadLink.removeAttribute('download');
            }
        }
    }

    if (modal) {
        modal.onclick = function(event) {
            if (event.target === modal || event.target.classList.contains('modal-backdrop')) {
                modal.style.display = "none";
                modalImg.src = "";
                if (downloadLink) {
                    downloadLink.removeAttribute('download');
                }
            }
        }
    }
});

// Scroll To Bottom BTN
function isScrolledToBottom() {
  return isNearBottom();
}

function scrollFeedToBottomButton({ force = false, smooth = true } = {}) {
  if (!el.chatFeed) return;

  if (!force && !state.stickToBottom) {
    return;
  }

  const scrollOptions = { behavior: smooth ? 'smooth' : 'auto' };
  el.chatFeed.scrollTo(0, el.chatFeed.scrollHeight, scrollOptions);

  const delay = smooth ? 500 : 50; 
  setTimeout(() => {
      if (el.scrollToBottomBtn) {
          el.scrollToBottomBtn.classList.remove('visible');
      }
      state.stickToBottom = true; 
  }, delay);
}


function initializeScrollButton() {
  if (!el.chatFeed || !el.scrollToBottomBtn) {
      console.error("Chat feed or scroll button element not found.");
      return;
  }

  el.scrollToBottomBtn.addEventListener('click', () => {
      scrollToBottom({ force: true, smooth: true });
  });

  el.chatFeed.addEventListener('scroll', () => {
      const scrolledToBottom = isScrolledToBottom();

      if (scrolledToBottom) {
      
        if (el.scrollToBottomBtn.classList.contains('visible')) {
            el.scrollToBottomBtn.classList.remove('visible');
        }
        state.stickToBottom = true;
    } else {
        
        if (!el.scrollToBottomBtn.classList.contains('visible')) {
            el.scrollToBottomBtn.classList.add('visible');
        }
        state.stickToBottom = false;
    }
});

  if (isScrolledToBottom()) {
      el.scrollToBottomBtn.classList.remove('visible');
      state.stickToBottom = true;
  } else {
      el.scrollToBottomBtn.classList.add('visible');
      state.stickToBottom = false;
  }
}
document.addEventListener('DOMContentLoaded', initializeScrollButton);


// Chat Header



// Mobile Menu
const mobileMenu = document.getElementById('mobile-menu');
const sidebarMenu = document.getElementById('sidebar-menu');

mobileMenu.addEventListener('click', () => {
    mobileMenu.classList.toggle('is-active'); 
    sidebarMenu.classList.toggle('open'); 
    sidebarMenu.style.display = 'block';    
});

document.addEventListener('click', (event) => {
    if (!sidebarMenu.contains(event.target) && !mobileMenu.contains(event.target) && sidebarMenu.classList.contains('open')) {
        mobileMenu.classList.remove('is-active');
        sidebarMenu.classList.remove('open');
    }
});

function closeMobileMenu() {
  const mobileMenu = document.getElementById('mobile-menu');
  const sidebarMenu = document.getElementById('sidebar-menu');
  
  mobileMenu.classList.remove('is-active'); 
  sidebarMenu.classList.remove('open'); 
}

const tabsbtnsidebar = document.getElementById('channelTabs');
if (tabsbtnsidebar) {
  tabsbtnsidebar.addEventListener('click', closeMobileMenu);
}


const profileBtnSidebar = document.getElementById('profileBtn');
if (profileBtnSidebar) {
    profileBtnSidebar.addEventListener('click', closeMobileMenu);
}

const profileBtnDesktop = document.getElementById('profileBtn');
if (profileBtnDesktop) {
    profileBtnDesktop.addEventListener('click', function() {
        
        if (window.innerWidth <= 768) { 
            closeMobileMenu();
        }
    });
}



  init();
})();

