import { reactive } from 'vue';

/**
 * Shared reactive store untuk proteksi anti-curang ujian siswa.
 *
 * SATU instance untuk seluruh halaman (module ES hanya diinisialisasi sekali
 * oleh bundler), dipakai bersama oleh 3 komponen tampilan (ExamStartGate,
 * ExamViolationBanner, ExamRulesCard) di resources/js/components/, DAN dibaca
 * dari komponen Alpine (cbtExam) di show.blade.php lewat callback
 * onExamStarted/onViolationsChanged (bukan CustomEvent, supaya tidak ada
 * nama-string yang bisa typo/di-listen script lain).
 *
 * PENTING (lihat rencana implementasi): seluruh listener/interval proteksi
 * (attachCommonHandlers) dipasang dari init()/startExam() DI SINI, BUKAN dari
 * lifecycle (onMounted/onUnmounted) komponen Vue manapun -- supaya listener
 * proteksi tidak ikut lepas kalau salah satu komponen tampilan disembunyikan
 * (siklus hidup komponen tampilan != siklus hidup sesi ujian).
 *
 * Logic di bawah ini adalah portingan LANGSUNG dari fungsi cbtExam() (Alpine)
 * yang sebelumnya ada di show.blade.php -- threshold, urutan try/catch, dan
 * kondisi mobile/desktop SENGAJA disalin persis (tidak "diperbaiki") supaya
 * tidak ada regresi perilaku baru akibat migrasi framework.
 */
export const examProtectionStore = reactive({
    // ---- state proteksi ----
    isMobile: false,
    needStart: false,
    isFullscreen: false,
    violations: 0,
    showWarning: false,
    lastViolation: '',
    savingViolation: false,

    // ---- config, di-set sekali lewat init() ----
    quizName: '',
    maxViolations: 0,
    protectionEnabled: false,
    violationUrl: '',
    blockedUrl: '',

    // ---- callback hook, di-set oleh Alpine (cbtExam) di show.blade.php ----
    onExamStarted: null,
    onViolationsChanged: null,

    _warningTimer: null,
    _initialized: false,

    /**
     * Dipanggil SEKALI secara eksplisit dari resources/js/app.js saat halaman
     * ujian dimuat (bukan dari lifecycle komponen manapun).
     */
    init(config) {
        if (this._initialized) return;
        this._initialized = true;

        this.quizName = config.quizName;
        this.maxViolations = config.maxViolations;
        this.protectionEnabled = config.protectionEnabled;
        this.violationUrl = config.violationUrl;
        this.blockedUrl = config.blockedUrl;
        this.violations = config.initialViolations || 0;

        this.isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
                        || (window.matchMedia && window.matchMedia('(pointer:coarse)').matches);

        // Cegah klik-kanan/copy/paste/cut selalu aktif SE-HALAMAN, terlepas dari
        // protectionEnabled -- persis seperti directive @contextmenu.prevent
        // dkk yang dulu ada di <body x-data> tanpa dibungkus @if($protectionEnabled).
        // logViolation() sendiri yang menahan diri (no-op) kalau protection off.
        this._attachAlwaysOnHandlers();

        this.needStart = this.protectionEnabled;

        if (!this.protectionEnabled) {
            this.needStart = false;
            this.onExamStarted?.();
            return;
        }
        // Saat protection on, kita TUNGGU user klik tombol "Mulai"
        // (requestFullscreen butuh user gesture langsung dari klik).
    },

    _attachAlwaysOnHandlers() {
        document.addEventListener('contextmenu', (e) => { e.preventDefault(); this.logViolation('right_click'); });
        document.addEventListener('copy', (e) => { e.preventDefault(); this.logViolation('copy'); });
        document.addEventListener('paste', (e) => { e.preventDefault(); this.logViolation('paste'); });
        document.addEventListener('cut', (e) => { e.preventDefault(); this.logViolation('cut'); });
    },

    async startExam() {
        this.needStart = false;

        if (!this.isMobile) {
            // === DESKTOP: WAJIB FULLSCREEN ===
            try {
                await document.documentElement.requestFullscreen({ navigationUI: 'hide' });
                this.isFullscreen = true;
            } catch (e) {
                this.logViolation('fullscreen_denied', 'Browser menolak fullscreen');
            }

            document.addEventListener('fullscreenchange', () => {
                this.isFullscreen = !!document.fullscreenElement;
                if (!this.isFullscreen) {
                    this.logViolation('fullscreen_exit');
                    // Coba paksa masuk fullscreen lagi
                    setTimeout(() => {
                        document.documentElement.requestFullscreen?.().catch(() => {});
                    }, 100);
                }
            });
        } else {
            // === MOBILE: deteksi orientasi & rotasi yang aneh ===
            if (screen.orientation && screen.orientation.lock) {
                try { await screen.orientation.lock('portrait'); } catch (e) {}
            }
            screen.orientation?.addEventListener('change', () => {
                this.logViolation('orientation_change', screen.orientation?.type);
            });

            // Mobile: deteksi touch dengan multi-finger (split-screen di Android sering memicu)
            document.addEventListener('touchstart', (e) => {
                if (e.touches.length > 2) this.logViolation('multi_touch');
            }, { passive: true });
        }

        this.attachCommonHandlers();
        this.onExamStarted?.();
    },

    attachCommonHandlers() {
        // 1. Tab / window blur
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) this.logViolation(this.isMobile ? 'app_switch' : 'tab_switch');
        });
        window.addEventListener('blur', () => this.logViolation('window_blur'));
        window.addEventListener('pageshow', (e) => {
            if (e.persisted) this.logViolation('back_forward_cache');
        });

        // 2. Prevent F12, Ctrl+Shift+I, Ctrl+U, Ctrl+S, Ctrl+P
        document.addEventListener('keydown', (e) => {
            const blocked =
                e.key === 'F12' ||
                (e.ctrlKey && e.shiftKey && ['I', 'J', 'C', 'K'].includes(e.key.toUpperCase())) ||
                (e.ctrlKey && ['U', 'S', 'P', 'A'].includes(e.key.toUpperCase())) ||
                (e.metaKey && ['I', 'U', 'S', 'P'].includes(e.key.toUpperCase())); // Mac
            if (blocked) {
                e.preventDefault();
                this.logViolation('blocked_key', e.key);
            }
        });

        // 3. DevTools detection (heuristic)
        setInterval(() => {
            const w = window.outerWidth - window.innerWidth;
            const h = window.outerHeight - window.innerHeight;
            if (!this.isMobile && (w > 200 || h > 200)) {
                this.logViolation('devtools');
            }
        }, 3000);

        // 4. Drag & drop / select text
        document.addEventListener('dragstart', (e) => { e.preventDefault(); });
        document.addEventListener('selectstart', (e) => {
            // bolehkan input field
            if (e.target.matches('input, textarea')) return;
            e.preventDefault();
        });
    },

    /** Pindah ke halaman blokir tanpa prompt browser */
    goToBlocked() {
        // pakai replace agar tidak bisa "back"
        window.location.replace(this.blockedUrl);
    },

    async logViolation(type, detail = null) {
        if (!this.protectionEnabled) return;
        if (this.savingViolation) return;

        this.violations++;
        this.lastViolation = type;
        this.showWarning = true;
        clearTimeout(this._warningTimer);
        this._warningTimer = setTimeout(() => { this.showWarning = false; }, 4000);

        this.onViolationsChanged?.(this.violations);

        this.savingViolation = true;
        try {
            const r = await fetch(this.violationUrl, {
                method: 'POST',
                headers: this._headers(),
                body: JSON.stringify({ type, detail }),
            });
            const data = await r.json();
            if (data.blocked) {
                // Mode "blokir" -> halaman blokir
                this.goToBlocked();
            } else if (data.logout) {
                // Mode "logout_otomatis" -> langsung ke halaman hasil (sudah di-submit di server)
                window.location.replace(window.location.pathname.replace(/\/[^\/]+$/, '') + '/result');
            }
        } catch (e) {
            console.error(e);
        } finally {
            this.savingViolation = false;
        }
    },

    _headers() {
        return {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            'Accept': 'application/json',
        };
    },
});
