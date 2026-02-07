// assets/tts-hover.js  (Option C: speak only current page language)

console.log("TTS hover loaded ✅");

const state = {
  enabled: JSON.parse(localStorage.getItem("tts_enabled") ?? "true"),
  timer: null,
  lastSpoken: "",
};

function getPageLang() {
  // best: <html lang="fr"> ... </html>
  const htmlLang = document.documentElement.getAttribute("lang");
  if (htmlLang && htmlLang.trim()) return htmlLang.trim();

  // fallback: browser language
  return (navigator.language || "fr-FR").trim();
}

function getLabel(el) {
  if (!el) return "";

  const aria = el.getAttribute?.("aria-label");
  if (aria) return aria.trim();

  if (el.tagName === "IMG") {
    const alt = el.getAttribute("alt");
    if (alt) return alt.trim();
  }

  if (el.tagName === "INPUT" || el.tagName === "TEXTAREA") {
    const value = el.value?.trim();
    const placeholder = el.getAttribute("placeholder")?.trim();
    return value || placeholder || "";
  }

  const text = (el.innerText || el.textContent || "").replace(/\s+/g, " ").trim();
  if (text) return text;

  const title = el.getAttribute?.("title");
  if (title) return title.trim();

  return "";
}

function pickTarget(node) {
  return node?.closest?.(
    "a, button, img, input, textarea, [role='button'], [aria-label], p, span, li, h1, h2, h3, h4, h5, h6"
  ) || node;
}

function speak(text) {
  if (!state.enabled) return;
  if (!text) return;
  if (!("speechSynthesis" in window)) return;

  const lang = getPageLang();

  // avoid repeating same phrase
  const key = `${lang}::${text}`;
  if (key === state.lastSpoken) return;

  window.speechSynthesis.cancel();
  const u = new SpeechSynthesisUtterance(text);
  u.lang = lang;

  window.speechSynthesis.speak(u);
  state.lastSpoken = key;
}

document.addEventListener("mouseover", (e) => {
  const target = pickTarget(e.target);
  const label = getLabel(target);

  clearTimeout(state.timer);
  state.timer = setTimeout(() => speak(label), 250);
});

window.addEventListener("blur", () => window.speechSynthesis?.cancel());

window.ttsHover = {
  enable() {
    state.enabled = true;
    localStorage.setItem("tts_enabled", "true");
  },
  disable() {
    state.enabled = false;
    localStorage.setItem("tts_enabled", "false");
    window.speechSynthesis?.cancel();
  },
  toggle() {
    state.enabled = !state.enabled;
    localStorage.setItem("tts_enabled", JSON.stringify(state.enabled));
    if (!state.enabled) window.speechSynthesis?.cancel();
    return state.enabled;
  },
  getState() {
    return { enabled: state.enabled, lang: getPageLang() };
  },
};
