import './bootstrap';
import Alpine from 'alpinejs';
import { createApp } from 'vue';
import { examProtectionStore } from './stores/examProtection';
import ExamStartGate from './components/ExamStartGate.vue';
import ExamViolationBanner from './components/ExamViolationBanner.vue';
import ExamRulesCard from './components/ExamRulesCard.vue';

window.Alpine = Alpine;

// Diakses dari <script> inline (non-module) di show.blade.php lewat cbtExam()
// -- classic script tidak bisa `import` module, jadi store diekspos lewat
// window, sama seperti pola window.Alpine / window.cbtExam yang sudah ada.
window.examProtectionStore = examProtectionStore;

// PENTING: Alpine.start() harus jalan DULU, sebelum examProtectionStore.init()
// di bawah. Alpine.start() men-scan DOM secara sinkron dan memanggil init() tiap
// x-data (termasuk cbtExam di show.blade.php), yang mendaftarkan callback
// onExamStarted/onViolationsChanged ke store. Kalau urutan dibalik, saat
// protectionEnabled=false, examProtectionStore.init() akan langsung memanggil
// onExamStarted?.() sebelum callback-nya sempat terdaftar -> timer ujian tidak
// pernah mulai.
Alpine.start();

// ---- Proteksi ujian (Vue) — hanya aktif di halaman ujian siswa ----
const examConfigEl = document.getElementById('exam-protection-config');
if (examConfigEl) {
    const config = JSON.parse(examConfigEl.textContent);
    examProtectionStore.init(config);
}

function mountIfPresent(selector, Component) {
    const el = document.querySelector(selector);
    if (el) createApp(Component).mount(el);
}

mountIfPresent('#exam-start-gate-root', ExamStartGate);
mountIfPresent('#exam-violation-banner-root', ExamViolationBanner);
mountIfPresent('#exam-rules-card-root', ExamRulesCard);
