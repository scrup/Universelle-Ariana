(() => {
  const STORAGE_KEY = "uc_headnav_v3";

  // Defaults (user can change in UI)
  const defaults = {
    enabled: false,
    showPreview: true,
    yawThreshold: 0.18,     // head left/right sensitivity
    blinkThreshold: 0.22,   // lower => more sensitive
    switchCooldown: 420,    // ms
    blinkCooldown: 900,     // ms
    smoothing: 0.7          // 0..0.9
  };

  let cfg = { ...defaults };
  let enabled = false;

  let videoEl = null;
  let panelEl = null;
  let toggleBtn = null;

  let faceMesh = null;
  let camera = null;

  let smoothedYaw = 0;
  let lastSwitchAt = 0;
  let lastBlinkAt = 0;

  const now = () => Date.now();
  const clamp = (n, a, b) => Math.max(a, Math.min(b, n));

  function loadCfg() {
    try {
      const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || "{}");
      cfg = { ...defaults, ...saved };
    } catch {
      cfg = { ...defaults };
    }
    enabled = !!cfg.enabled;
  }

  function saveCfg() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(cfg));
  }

  // ---------------- Focus navigation helpers ----------------
  function getFocusableElements() {
    const selector = [
      "a[href]",
      "button:not([disabled])",
      "input:not([disabled]):not([type='hidden'])",
      "select:not([disabled])",
      "textarea:not([disabled])",
      "[tabindex]:not([tabindex='-1'])"
    ].join(",");

    return Array.from(document.querySelectorAll(selector)).filter(el => {
      const style = getComputedStyle(el);
      const rect = el.getBoundingClientRect();
      return style.visibility !== "hidden" && style.display !== "none" && rect.width > 0 && rect.height > 0;
    });
  }

  function focusNext(dir) {
    const focusables = getFocusableElements();
    if (!focusables.length) return;

    const active = document.activeElement;
    let idx = focusables.indexOf(active);

    if (idx === -1) idx = 0;
    else idx = (idx + dir + focusables.length) % focusables.length;

    const el = focusables[idx];
    el.focus({ preventScroll: false });
    el.scrollIntoView({ block: "center", inline: "nearest", behavior: "smooth" });
  }

  function clickFocused() {
    const el = document.activeElement;
    if (!el) return;

    // Avoid clicking the panel controls themselves
    if (panelEl && panelEl.contains(el)) return;

    if (typeof el.click === "function") el.click();
  }

  // ---------------- Face math (MediaPipe) ----------------
  function dist(a, b) {
    const dx = a.x - b.x, dy = a.y - b.y;
    return Math.hypot(dx, dy);
  }

  function ear(p1, p2, p3, p4, p5, p6) {
    const num = dist(p2, p6) + dist(p3, p5);
    const den = 2 * dist(p1, p4);
    return den ? num / den : 1;
  }

  function bothEyesBlinking(lm) {
    // Left eye indices
    const L = { p1: lm[33], p2: lm[160], p3: lm[158], p4: lm[133], p5: lm[153], p6: lm[144] };
    // Right eye indices
    const R = { p1: lm[362], p2: lm[385], p3: lm[387], p4: lm[263], p5: lm[373], p6: lm[380] };
    if (!L.p1 || !R.p1) return false;

    const leftEAR = ear(L.p1, L.p2, L.p3, L.p4, L.p5, L.p6);
    const rightEAR = ear(R.p1, R.p2, R.p3, R.p4, R.p5, R.p6);

    return leftEAR < cfg.blinkThreshold && rightEAR < cfg.blinkThreshold;
  }

  function estimateYaw(lm) {
    // Nose tip vs face center as a proxy for yaw
    const nose = lm[1];
    const left = lm[234];
    const right = lm[454];
    if (!nose || !left || !right) return 0;
    const centerX = (left.x + right.x) / 2;
    return nose.x - centerX;
  }

  // ---------------- UI ----------------
  function injectStyles() {
    const css = `
      .uc-camBtn{
        position: fixed;
        right: 16px;
        top: 40%;
        transform: translateY(-50%);
        z-index: 999999;
        background: #8a232e;
        color: #fff;
        border: none;
        border-radius: 999px;
        padding: .75rem 1rem;
        font-weight: 900;
        box-shadow: 0 10px 24px rgba(0,0,0,.18);
        cursor: pointer;
      }
      .uc-camBtn:focus{ outline: 3px solid rgba(255,255,255,.7); outline-offset: 3px; }

      .uc-camPanel{
        position: fixed;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        z-index: 999999;
        width: min(420px, calc(100vw - 32px));
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 16px 40px rgba(0,0,0,.22);
        overflow: hidden;
        display: none;
      }
      .uc-camPanel[aria-hidden="false"]{ display:block; }

      .uc-camHeader{
        display:flex;align-items:center;justify-content:space-between;
        padding: 12px 14px;
        background: #F6FAFD;
        border-bottom: 1px solid #EEF4FA;
      }
      .uc-camTitle{ font-weight: 900; color:#0B1F2D; }
      .uc-camClose{ background:transparent;border:none;font-size:20px;cursor:pointer; }

      .uc-camBody{ padding: 14px; display:grid; gap: 12px; }
      .uc-camRow{ display:flex; align-items:center; justify-content:space-between; gap:12px; }
      .uc-camRow label{ font-weight: 800; color:#0B1F2D; }
      .uc-camHint{ font-size: 12px; color:#4B6A80; }

      .uc-camVideo{
        width: 100%;
        height: 220px;
        background: #000;
        border-radius: 14px;
        object-fit: cover;
      }

      .uc-switch{
        display:flex; align-items:center; gap:10px;
        font-weight:800; color:#0B1F2D;
      }
      .uc-switch input{ width: 18px; height: 18px; }

      .uc-slider{
        width: 180px;
      }

      .uc-btn{
        background:#2172A8;color:#fff;border:none;border-radius:12px;
        padding:.6rem .9rem;font-weight:900;cursor:pointer;
      }
      .uc-btn:disabled{opacity:.5;cursor:not-allowed}
      .uc-btnRow{display:flex; gap:10px; flex-wrap:wrap;}
    `;
    const style = document.createElement("style");
    style.textContent = css;
    document.head.appendChild(style);
  }

  function createUI() {
    // Toggle button
    toggleBtn = document.createElement("button");
    toggleBtn.className = "uc-camBtn";
    toggleBtn.type = "button";
    toggleBtn.id = "uc-headnav-toggle";
    toggleBtn.textContent = "🎥 Caméra";
    toggleBtn.setAttribute("aria-expanded", "false");
    document.body.appendChild(toggleBtn);

    // Panel
    panelEl = document.createElement("div");
    panelEl.className = "uc-camPanel";
    panelEl.id = "uc-headnav-panel";
    panelEl.setAttribute("role", "dialog");
    panelEl.setAttribute("aria-label", "Navigation caméra");
    panelEl.setAttribute("aria-hidden", "true");

    panelEl.innerHTML = `
      <div class="uc-camHeader">
        <div>
          <div class="uc-camTitle">Navigation Caméra</div>
          <div class="uc-camHint">Tête gauche/droite = focus • Blink (2 yeux) = clic</div>
        </div>
        <button class="uc-camClose" type="button" aria-label="Fermer">✕</button>
      </div>

      <div class="uc-camBody">
        <video class="uc-camVideo" id="uc-headnav-video" autoplay playsinline muted></video>

        <div class="uc-camRow">
          <span class="uc-switch">
            <input id="uc-enabled" type="checkbox">
            <label for="uc-enabled">Activer</label>
          </span>

          <span class="uc-switch">
            <input id="uc-preview" type="checkbox">
            <label for="uc-preview">Aperçu</label>
          </span>
        </div>

        <div class="uc-camRow">
          <label for="uc-yaw">Sensibilité tête</label>
          <input class="uc-slider" id="uc-yaw" type="range" min="0.10" max="0.35" step="0.01">
        </div>

        <div class="uc-camRow">
          <label for="uc-blink">Sensibilité blink</label>
          <input class="uc-slider" id="uc-blink" type="range" min="0.16" max="0.30" step="0.01">
        </div>

        <div class="uc-btnRow">
          <button class="uc-btn" type="button" id="uc-focus-first">Focus premier élément</button>
          <button class="uc-btn" type="button" id="uc-reset">Réinitialiser</button>
        </div>

        <div class="uc-camHint">
          Astuce: si ça clique trop facilement, augmente “Sensibilité blink” (vers 0.26-0.30).<br>
          Si ça ne clique jamais, diminue-le (vers 0.18-0.21).
        </div>
      </div>
    `;

    document.body.appendChild(panelEl);

    videoEl = panelEl.querySelector("#uc-headnav-video");

    // Fill UI values from cfg
    panelEl.querySelector("#uc-enabled").checked = enabled;
    panelEl.querySelector("#uc-preview").checked = !!cfg.showPreview;
    panelEl.querySelector("#uc-yaw").value = String(cfg.yawThreshold);
    panelEl.querySelector("#uc-blink").value = String(cfg.blinkThreshold);

    // Open/close panel
    const closeBtn = panelEl.querySelector(".uc-camClose");
    const openPanel = () => {
      panelEl.setAttribute("aria-hidden", "false");
      toggleBtn.setAttribute("aria-expanded", "true");
      // preview only if enabled and showPreview
      videoEl.style.display = cfg.showPreview ? "block" : "none";
      closeBtn.focus();
    };
    const closePanel = () => {
      panelEl.setAttribute("aria-hidden", "true");
      toggleBtn.setAttribute("aria-expanded", "false");
      toggleBtn.focus();
    };

    toggleBtn.addEventListener("click", () => {
      const isHidden = panelEl.getAttribute("aria-hidden") === "true";
      isHidden ? openPanel() : closePanel();
    });
    closeBtn.addEventListener("click", closePanel);
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && panelEl.getAttribute("aria-hidden") === "false") closePanel();
    });

    // Enable toggle
    panelEl.querySelector("#uc-enabled").addEventListener("change", async (e) => {
      await setEnabled(!!e.target.checked);
    });

    // Preview toggle
    panelEl.querySelector("#uc-preview").addEventListener("change", (e) => {
      cfg.showPreview = !!e.target.checked;
      saveCfg();
      videoEl.style.display = cfg.showPreview ? "block" : "none";
    });

    // Sliders
    panelEl.querySelector("#uc-yaw").addEventListener("input", (e) => {
      cfg.yawThreshold = parseFloat(e.target.value);
      saveCfg();
    });
    panelEl.querySelector("#uc-blink").addEventListener("input", (e) => {
      cfg.blinkThreshold = parseFloat(e.target.value);
      saveCfg();
    });

    // Buttons
    panelEl.querySelector("#uc-focus-first").addEventListener("click", () => {
      const focusables = getFocusableElements();
      if (focusables[0]) focusables[0].focus();
    });
    panelEl.querySelector("#uc-reset").addEventListener("click", async () => {
      cfg = { ...defaults, enabled: false };
      saveCfg();
      enabled = false;
      panelEl.querySelector("#uc-enabled").checked = false;
      panelEl.querySelector("#uc-preview").checked = true;
      panelEl.querySelector("#uc-yaw").value = String(cfg.yawThreshold);
      panelEl.querySelector("#uc-blink").value = String(cfg.blinkThreshold);
      await stopCamera();
    });
  }

  // ---------------- Camera start/stop ----------------
  async function startCamera() {
    if (!videoEl) return;

    // Must be https or localhost
    // If scripts not loaded:
    if (typeof FaceMesh === "undefined" || typeof Camera === "undefined") {
      alert("MediaPipe not loaded. Check script tags order in base.html.twig.");
      cfg.enabled = false; enabled = false; saveCfg();
      panelEl?.querySelector("#uc-enabled") && (panelEl.querySelector("#uc-enabled").checked = false);
      return;
    }

    faceMesh = new FaceMesh({
      locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/${file}`
    });

    faceMesh.setOptions({
      maxNumFaces: 1,
      refineLandmarks: true,
      minDetectionConfidence: 0.6,
      minTrackingConfidence: 0.6
    });

    faceMesh.onResults((results) => {
      if (!enabled) return;
      const lm = results.multiFaceLandmarks?.[0];
      if (!lm) return;

      const yaw = estimateYaw(lm);
      smoothedYaw = cfg.smoothing * smoothedYaw + (1 - cfg.smoothing) * yaw;

      const t = now();

      // Move focus with head direction
      if (t - lastSwitchAt > cfg.switchCooldown) {
        if (smoothedYaw > cfg.yawThreshold) {
          focusNext(+1);
          lastSwitchAt = t;
        } else if (smoothedYaw < -cfg.yawThreshold) {
          focusNext(-1);
          lastSwitchAt = t;
        }
      }

      // Blink => click
      if (t - lastBlinkAt > cfg.blinkCooldown) {
        if (bothEyesBlinking(lm)) {
          clickFocused();
          lastBlinkAt = t;
        }
      }
    });

    camera = new Camera(videoEl, {
      onFrame: async () => {
        await faceMesh.send({ image: videoEl });
      },
      width: 640,
      height: 480
    });

    try {
      await camera.start();
    } catch (e) {
      console.error(e);
      alert("Camera permission denied or camera not available.");
      cfg.enabled = false;
      enabled = false;
      saveCfg();
      panelEl?.querySelector("#uc-enabled") && (panelEl.querySelector("#uc-enabled").checked = false);
      await stopCamera();
    }
  }

  async function stopCamera() {
    try { if (camera) camera.stop(); } catch {}
    camera = null;
    faceMesh = null;
  }

  async function setEnabled(v) {
    enabled = v;
    cfg.enabled = v;
    saveCfg();

    if (enabled) {
      // show preview only if enabled + user wants preview
      videoEl.style.display = cfg.showPreview ? "block" : "none";
      await startCamera();

      // focus first element so user can start immediately
      const focusables = getFocusableElements();
      if (focusables[0]) focusables[0].focus();
    } else {
      await stopCamera();
    }
  }

  // ---------------- Boot ----------------
  document.addEventListener("DOMContentLoaded", async () => {
    loadCfg();
    injectStyles();
    createUI();

    // Restore enabled state
    if (enabled) {
      panelEl.querySelector("#uc-enabled").checked = true;
      await setEnabled(true);
    }

    // ESC safety: if enabled, ESC turns it off (even if panel closed)
    document.addEventListener("keydown", async (e) => {
      if (e.key === "Escape" && enabled) {
        panelEl.querySelector("#uc-enabled").checked = false;
        await setEnabled(false);
      }
    });
  });
})();
