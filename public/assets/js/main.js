/* COSPARK — shared behaviour (header, modal, toast, reveal, forms → server)
   Header, footer and the appointment modal are rendered server-side (Blade). */

const LOGO = `<svg viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M16 2.5 4.5 7v8.2c0 7 4.9 12.4 11.5 14.3 6.6-1.9 11.5-7.3 11.5-14.3V7L16 2.5Z" stroke="currentColor" stroke-width="1.4"/><path d="m16 10 2 4 4 2-4 2-2 4-2-4-4-2 4-2 2-4Z" fill="#C8753D"/><circle cx="16" cy="16" r="1.6" fill="#F3E0CC"/></svg>`;

const ICONS = {
  home:'<path d="M3 10.5 12 3l9 7.5V21h-6v-6H9v6H3z"/>',
  car:'<path d="M5 16V11l2-5h10l2 5v5M3 16h18v3H3zM7 19v1M17 19v1M5 11h14"/>',
  heart:'<path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/>',
  briefcase:'<rect x="3" y="7" width="18" height="13" rx="1"/><path d="M8 7V4h8v3M3 12h18"/>',
  user:'<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/>',
  users:'<circle cx="9" cy="8" r="3.5"/><path d="M2 20c1-3.5 3.6-5.5 7-5.5s6 2 7 5.5M16 4.5a3.5 3.5 0 0 1 0 7M18 14.5c2 .8 3.4 2.6 4 5.5"/>',
  phone:'<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a1 1 0 0 1-1 1A16 16 0 0 1 4 5a1 1 0 0 1 1-1z"/>',
  gift:'<rect x="3" y="8" width="18" height="4"/><path d="M5 12v9h14v-9M12 8v13M12 8S10.5 3 8 3.5 7 8 12 8zm0 0s1.5-5 4-4.5S17 8 12 8z"/>',
  coin:'<circle cx="12" cy="12" r="9"/><path d="M14.5 9.5c-.5-1-1.5-1.5-2.5-1.5-1.5 0-2.5.8-2.5 2s1 1.7 2.5 2 2.5.8 2.5 2-1 2-2.5 2c-1 0-2-.5-2.5-1.5M12 6.5V8M12 16v1.5"/>',
  wallet:'<path d="M3 7h16a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2zM3 7l13-3v3"/><circle cx="16.5" cy="13.5" r="1.2"/>',
  card:'<rect x="2.5" y="5" width="19" height="14" rx="1.5"/><path d="M2.5 10h19M6 15h4"/>',
  cal:'<rect x="3" y="5" width="18" height="16" rx="1"/><path d="M3 10h18M8 3v4M16 3v4"/>',
  grid:'<rect x="4" y="4" width="6.5" height="6.5"/><rect x="13.5" y="4" width="6.5" height="6.5"/><rect x="4" y="13.5" width="6.5" height="6.5"/><rect x="13.5" y="13.5" width="6.5" height="6.5"/>',
  doc:'<path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 13h7M9 17h5"/>',
  shield:'<path d="M12 3 4.5 6v6c0 4.6 3.2 8.2 7.5 9.5 4.3-1.3 7.5-4.9 7.5-9.5V6z"/>',
  alert:'<path d="M12 3 2 20h20zM12 10v4M12 17.5v.5"/>',
  bell:'<path d="M6 16V11a6 6 0 0 1 12 0v5l2 2H4zM10 20a2 2 0 0 0 4 0"/>',
  palm:'<path d="M12 21V11M12 11c-2-3-6-3.5-8-2 3 0 5 1 8 2zm0 0c2-3 6-3.5 8-2-3 0-5 1-8 2zm0 0c-1-3.5-4-5.5-6.5-5 2.5 1 4.5 2.5 6.5 5zm0 0c1-3.5 4-5.5 6.5-5-2.5 1-4.5 2.5-6.5 5zM7 21h10"/>',
  qr:'<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3M21 14v7h-4M17 21v-2"/>',
  settings:'<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.6 1.6 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.6 1.6 0 0 0-2.7 1.1V21a2 2 0 1 1-4 0v-.1A1.6 1.6 0 0 0 7.5 19.4l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.6 1.6 0 0 0 3.6 14H3a2 2 0 1 1 0-4h.1A1.6 1.6 0 0 0 4.6 7.5l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A1.6 1.6 0 0 0 10 3.1V3a2 2 0 1 1 4 0v.1a1.6 1.6 0 0 0 2.6 1.4l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1A1.6 1.6 0 0 0 20.9 10H21a2 2 0 1 1 0 4h-.1a1.6 1.6 0 0 0-1.5 1z"/>',
  book:'<path d="M4 4h6a3 3 0 0 1 2 1 3 3 0 0 1 2-1h6v15h-6a2 2 0 0 0-2 2 2 2 0 0 0-2-2H4z"/><path d="M12 5v16"/>',
  chart:'<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
  lead:'<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4M11 8v6M8 11h6"/>',
  out:'<path d="M9 21H4V3h5M16 17l5-5-5-5M21 12H9"/>',
  menu:'<path d="M4 7h16M4 12h16M4 17h16"/>',
  transfer:'<path d="M4 8h14l-3-3M20 16H6l3 3"/>',
  boat:'<path d="M3 17c2 2 4 2 6 0 2 2 4 2 6 0 2 2 4 2 6 0M5 14l1-5h12l1 5M12 4v5"/>',
  building:'<path d="M4 21V5l8-2v18M12 8h8v13M8 8h.01M8 12h.01M8 16h.01M16 12h.01M16 16h.01M2 21h20"/>',
  leaf:'<path d="M5 21c0-9 5-15 15-16-1 10-7 15-15 16zM5 21l8-8"/>',
  check:'<path d="m5 12 4.5 4.5L19 7"/>',
  upload:'<path d="M12 16V4M7 9l5-5 5 5M4 20h16"/>',
  star:'<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
};
const ic = (n, cls = "icon") => `<svg class="${cls}" viewBox="0 0 24 24" aria-hidden="true">${ICONS[n] || ""}</svg>`;

const genId = p => `${p}-${new Date().getFullYear().toString().slice(2)}${Math.random().toString(36).slice(2, 8).toUpperCase()}`;
const fmt$ = n => "$" + Number(n).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
function toast(msg) {
  const t = document.getElementById("toast"); if (!t) return;
  t.textContent = msg; t.classList.add("show");
  clearTimeout(t._h); t._h = setTimeout(() => t.classList.remove("show"), 2800);
}
function validPhone(value) {
  const phone = String(value).trim(), digits = phone.replace(/\D/g, "");
  return /^\+?[0-9 ().-]+$/.test(phone) && digits.length >= 10 && digits.length <= 15 && !/^0+$/.test(digits);
}
const EMAIL_FORMAT_ERR = "Email format is incorrect.";
const validEmail = value => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value).trim());
function validate(scope) {
  let ok = true;
  scope.querySelectorAll('[required],input[type="tel"],input[type="email"]').forEach(el => {
    const f = el.closest(".field"); if (!f) return;
    // Each check sets its own message, so keep the field's original one for the empty case
    const m = f.querySelector(".err-msg"); if (m) m.dataset.msg ??= m.textContent;
    const say = msg => { if (m) m.textContent = msg; };
    say(m?.dataset.msg);
    let bad = el.type === "checkbox" ? !el.checked : el.required && !String(el.value).trim();
    if (!bad && el.type === "email" && String(el.value).trim()) {
      bad = !validEmail(el.value);
      if (bad) say(EMAIL_FORMAT_ERR);
    }
    if (!bad && el.type === "tel" && String(el.value).trim()) {
      bad = !validPhone(el.value);
      if (bad) say("Enter a valid phone number with 10 to 15 digits.");
    }
    if (!bad && el.pattern && el.value) {
      bad = !new RegExp(`^(?:${el.pattern})$`).test(el.value);
      if (bad) say(el.dataset.validationMessage || "Enter a value in the requested format.");
    }
    f.classList.toggle("err", bad); if (bad) ok = false;
  });
  const first = scope.querySelector(".field.err input,.field.err select,.field.err textarea");
  if (first) first.focus();
  return ok;
}
function openModal(id) { const m = document.getElementById(id); if (!m) return; m.classList.add("open"); document.body.style.overflow = "hidden"; setTimeout(() => m.querySelector("input,select,button:not(.modal-x)")?.focus(), 50); }
function closeModal(m) { m.classList.remove("open"); document.body.style.overflow = ""; }

/* ---------- Server requests ----------
   send(url, FormData|object) → resolves with the JSON body; rejects with an Error whose
   `.errors` holds Laravel validation messages ({ field: [msg] }) on a 422. */
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || "";
async function send(url, data, method = "POST") {
  const body = data instanceof FormData ? data : JSON.stringify(data);
  const headers = { "X-CSRF-TOKEN": csrf(), "Accept": "application/json", "X-Requested-With": "XMLHttpRequest" };
  if (!(data instanceof FormData)) headers["Content-Type"] = "application/json";
  let res;
  try { res = await fetch(url, { method, headers, body, credentials: "same-origin" }); }
  catch { throw Object.assign(new Error("We couldn't reach the server. Check your connection and try again."), { errors: {} }); }
  const json = await res.json().catch(() => ({}));
  if (res.ok) return json;
  if (res.status === 419) throw Object.assign(new Error("Your session expired. Refresh the page and try again."), { errors: {} });
  if (res.status === 429) throw Object.assign(new Error("Too many attempts. Please wait a minute and try again."), { errors: {} });
  const errors = json.errors || {};
  throw Object.assign(new Error(Object.values(errors)[0]?.[0] || json.message || "Something went wrong. Please try again."), { errors });
}
/* Mark the .field for each server-side error (inputs carry name="…") */
function showServerErrors(scope, errors) {
  Object.entries(errors).forEach(([name, msgs]) => {
    const el = scope.querySelector(`[name="${name}"]`), f = el?.closest(".field"); if (!f) return;
    f.classList.add("err"); const m = f.querySelector(".err-msg"); if (m) m.textContent = msgs[0];
  });
}
function busy(btn, on, label) {
  if (on) { btn._label = btn.innerHTML; btn.disabled = true; btn.textContent = label || "Sending…"; }
  else { btn.disabled = false; btn.innerHTML = btn._label; }
}

document.addEventListener("DOMContentLoaded", () => {
  // header scroll state
  const hdr = document.getElementById("siteHeader");
  if (hdr) { const s = () => hdr.classList.toggle("scrolled", scrollY > 10); s(); addEventListener("scroll", s, { passive: true }); }
  // website mobile menu: burger toggles (icon turns into ×); closes on link tap, Escape or widening the window
  const burger = document.getElementById("burger"), mn = document.getElementById("mobileNav");
  const setMenu = open => { if (!mn) return; mn.classList.toggle("open", open); burger.setAttribute("aria-expanded", open); document.body.style.overflow = open ? "hidden" : ""; };
  burger?.addEventListener("click", () => setMenu(!mn.classList.contains("open")));
  mn?.addEventListener("click", e => { if (e.target.closest("a")) setMenu(false); });

  // dashboard sidebar (member & producer): menu button opens it, backdrop / link / Escape close it
  const side = document.getElementById("dSide"), dMenu = document.getElementById("dMenu");
  let backdrop = null;
  const setSide = open => { if (!side) return; side.classList.toggle("open", open); backdrop.classList.toggle("on", open); dMenu.setAttribute("aria-expanded", open); document.body.style.overflow = open ? "hidden" : ""; };
  if (side && dMenu) {
    backdrop = document.createElement("div"); backdrop.className = "dash-backdrop"; document.body.appendChild(backdrop);
    dMenu.addEventListener("click", () => setSide(!side.classList.contains("open")));
    backdrop.addEventListener("click", () => setSide(false));
    side.insertAdjacentHTML("afterbegin", `<button type="button" class="dash-close" aria-label="Close menu">&times;</button>`);
    side.querySelector(".dash-close").addEventListener("click", () => setSide(false));
    side.addEventListener("click", e => { if (e.target.closest("a[href]")) setSide(false); });
  }
  document.addEventListener("keydown", e => { if (e.key === "Escape") { setMenu(false); if (side) setSide(false); } });
  addEventListener("resize", () => { if (innerWidth > 1180) { setMenu(false); if (side) setSide(false); } }, { passive: true });

  // modals
  document.addEventListener("click", e => {
    const a = e.target.closest("[data-appt]");
    if (a) {
      e.preventDefault();
      const m = document.getElementById("apptModal"); if (!m) return;
      m.querySelector("#apptForm").hidden = false; m.querySelector("#apptDone").hidden = true;
      if (a.dataset.appt === "inperson") m.querySelectorAll(".appt-type")[1].click();
      if (a.dataset.appt === "phone") m.querySelectorAll(".appt-type")[0].click();
      openModal("apptModal");
    }
    const c = e.target.closest("[data-close]"); if (c) closeModal(c.closest(".modal"));
    const o = e.target.closest("[data-open]"); if (o) { e.preventDefault(); openModal(o.dataset.open); }
    const t = e.target.closest("[data-toast]"); if (t) toast(t.dataset.toast);
    const out = e.target.closest("[data-logout]"); if (out) { e.preventDefault(); document.getElementById("logoutForm")?.submit(); }
  });
  document.addEventListener("keydown", e => { if (e.key === "Escape") document.querySelectorAll(".modal.open").forEach(closeModal); });

  // appointment modal (SOW: opens as a popup, phone or in-person in one form)
  const am = document.getElementById("apptModal");
  if (am) {
    const dIn = am.querySelector("[name=a_date]");
    const minD = new Date(); minD.setDate(minD.getDate() + 1); dIn.min = minD.toISOString().split("T")[0];
    let aType = "Phone call";
    const upd = () => am.querySelector("#apptSum").textContent = `${aType} · ${dIn.value || "choose a date"}${am.querySelector("[name=a_time]").value ? " · " + am.querySelector("[name=a_time]").value : ""}`;
    am.querySelectorAll(".appt-type").forEach(b => b.addEventListener("click", () => { am.querySelectorAll(".appt-type").forEach(x => x.classList.remove("on")); b.classList.add("on"); aType = b.dataset.atype; upd(); }));
    // Business-hours slots only on office days; other days keep the after-hours request (last option)
    const officeDays = (am.dataset.officeDays || "1,2,3,4").split(",").map(Number);
    dIn.addEventListener("change", () => {
      if (!dIn.value) return;
      const sel = am.querySelector("[name=a_time]"), opts = [...sel.options].filter(o => o.value), open = officeDays.includes(new Date(dIn.value + "T12:00").getDay() || 7);
      opts.slice(0, -1).forEach(o => o.disabled = !open);
      if (!open && sel.selectedOptions[0]?.disabled) sel.value = opts.at(-1).value;
      // Say why the business-hours slots are greyed out
      const days = officeDays.map(d => ["", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"][d]);
      const hint = am.querySelector("#apptClosed");
      hint.hidden = open;
      hint.textContent = `Our office is open ${days.length > 1 ? days.slice(0, -1).join(", ") + " and " + days.at(-1) : days[0]}. On other days, choose “${opts.at(-1).value}” or pick an office day.`;
    });
    am.addEventListener("change", upd);
    am.querySelector("#apptSubmit").addEventListener("click", async e => {
      const form = am.querySelector("#apptForm"); if (!validate(form)) return;
      const btn = e.currentTarget, val = n => am.querySelector(`[name=${n}]`).value;
      busy(btn, true);
      try {
        const r = await send(am.dataset.action, { type: am.querySelector(".appt-type.on").dataset.type, a_date: val("a_date"), a_time: val("a_time"), a_name: val("a_name"), a_email: val("a_email"), a_phone: val("a_phone"), a_reason: val("a_reason"), a_msg: val("a_msg") });
        const d = new Date(dIn.value + "T12:00").toLocaleDateString("en-US", { weekday: "long", month: "long", day: "numeric" });
        am.querySelector("#apptDoneTxt").textContent = `${aType} on ${d} at ${val("a_time")}. We'll confirm the exact time shortly.`;
        am.querySelector("#apptRef").textContent = r.reference;
        form.hidden = true; am.querySelector("#apptDone").hidden = false;
        document.dispatchEvent(new CustomEvent("cospark:appointment-created", { detail: { reference: r.reference } }));
      } catch (err) { showServerErrors(form, err.errors); toast(err.message); }
      finally { busy(btn, false); }
    });
  }

  // reveal on scroll
  const io = new IntersectionObserver(es => es.forEach(en => { if (en.isIntersecting) { en.target.classList.add("in"); io.unobserve(en.target); } }), { threshold: .12 });
  document.querySelectorAll(".rv").forEach(el => io.observe(el));

  // generic tabs: [data-tabs] > button[data-tab] ; panes [data-pane]
  document.querySelectorAll("[data-tabs]").forEach(t => {
    const grp = t.dataset.tabs;
    t.querySelectorAll("[data-tab]").forEach(b => b.addEventListener("click", () => {
      t.querySelectorAll("[data-tab]").forEach(x => x.classList.toggle("on", x === b));
      document.querySelectorAll(`[data-pane-group="${grp}"]`).forEach(p => p.hidden = p.dataset.pane !== b.dataset.tab);
    }));
  });

  // simple forms posted to their action: [data-simple-form]
  document.querySelectorAll("[data-simple-form]").forEach(fm => fm.addEventListener("submit", async e => {
    e.preventDefault(); if (!validate(fm)) return;
    const btn = fm.querySelector("[type=submit]");
    busy(btn, true);
    try {
      const r = await send(fm.action, new FormData(fm));
      const done = fm.parentElement.querySelector("[data-done]");
      if (done) { fm.hidden = true; done.hidden = false; done.querySelector("[data-ref]") && (done.querySelector("[data-ref]").textContent = r.reference || ""); }
      else toast("Sent. We'll be in touch shortly.");
    } catch (err) { showServerErrors(fm, err.errors); toast(err.message); }
    finally { busy(btn, false); }
  }));
  document.addEventListener("input", e => { const f = e.target.closest(".field.err"); if (f && String(e.target.value).trim()) f.classList.remove("err"); });
  // Phone inputs: only phone characters and at most 15 digits (the server's limit)
  document.addEventListener("input", e => {
    const el = e.target; if (el.type !== "tel") return;
    let n = 0; const v = el.value.replace(/[^\d+() .-]/g, "").replace(/\d/g, d => ++n > 15 ? "" : d);
    if (v !== el.value) el.value = v;
  });
});

/* Visual QR placeholder (deterministic pattern from a string; production uses a real QR library) */
function makeQR(str, n = 25) {
  let h = 2166136261; for (const c of str) { h ^= c.charCodeAt(0); h = Math.imul(h, 16777619); }
  const rnd = () => { h ^= h << 13; h ^= h >>> 17; h ^= h << 5; return ((h >>> 0) % 1000) / 1000; };
  const finder = (x, y) => [[x, y, 7], [x + 1, y + 1, 5, true], [x + 2, y + 2, 3]];
  const inF = (x, y) => [[0, 0], [n - 7, 0], [0, n - 7]].some(([a, b]) => x >= a - 1 && x <= a + 7 && y >= b - 1 && y <= b + 7);
  let r = "";
  for (let y = 0; y < n; y++) for (let x = 0; x < n; x++) if (!inF(x, y) && rnd() > .52) r += `<rect x="${x}" y="${y}" width="1" height="1"/>`;
  [[0, 0], [n - 7, 0], [0, n - 7]].forEach(([x, y]) => finder(x, y).forEach(([a, b, s, w]) => r += `<rect x="${a}" y="${b}" width="${s}" height="${s}" fill="${w ? "#fff" : "#0A111C"}"/>`));
  return `<svg viewBox="-1 -1 ${n + 2} ${n + 2}" shape-rendering="crispEdges" fill="#0A111C" role="img" aria-label="Member QR code">${r}</svg>`;
}

/* OTP inputs: auto-advance, backspace, paste */
function otpWire(box) {
  const ins = [...box.querySelectorAll("input")];
  ins.forEach((el, i) => {
    el.addEventListener("input", () => { el.value = el.value.replace(/\D/g, "").slice(-1); if (el.value && ins[i + 1]) ins[i + 1].focus(); });
    el.addEventListener("keydown", e => { if (e.key === "Backspace" && !el.value && ins[i - 1]) ins[i - 1].focus(); });
    el.addEventListener("paste", e => { const d = (e.clipboardData.getData("text").match(/\d/g) || []).slice(0, 6); if (!d.length) return; e.preventDefault(); d.forEach((v, k) => ins[k] && (ins[k].value = v)); ins[Math.min(d.length, 5)].focus(); });
  });
}

/* Password fields: an always-visible show/hide button (the browser's own reveal icon only appears
   while typing, and is hidden in CSS). Applied to fields rendered later too (wizard steps, modals). */
const pwEye = shown => `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true">${shown
  ? '<path d="M3 3l18 18M10.6 6.1A10 10 0 0 1 12 6c5 0 9 4.5 10 6-.5.8-1.6 2.2-3.2 3.6M6.6 6.6C4.4 8 2.8 10.1 2 12c1 1.5 5 6 10 6 1.6 0 3.1-.4 4.4-1.1M9.9 9.9a3 3 0 0 0 4.2 4.2"/>'
  : '<path d="M2 12c1-1.5 5-6 10-6s9 4.5 10 6c-1 1.5-5 6-10 6S3 13.5 2 12z"/><circle cx="12" cy="12" r="3"/>'}</svg>`;
function pwToggles(root = document) {
  root.querySelectorAll('input[type="password"]:not([data-eye])').forEach(el => {
    el.dataset.eye = "1";
    const wrap = document.createElement("span"); wrap.className = "pw-wrap";
    el.before(wrap); wrap.append(el);
    wrap.insertAdjacentHTML("beforeend", `<button type="button" class="pw-eye" aria-label="Show password" aria-pressed="false">${pwEye(false)}</button>`);
  });
}
document.addEventListener("click", e => {
  const b = e.target.closest(".pw-eye"); if (!b) return;
  const el = b.previousElementSibling, show = el.type === "password";
  el.type = show ? "text" : "password";
  b.innerHTML = pwEye(show); b.setAttribute("aria-pressed", show); b.setAttribute("aria-label", show ? "Hide password" : "Show password");
});
document.addEventListener("DOMContentLoaded", () => {
  pwToggles();
  new MutationObserver(() => pwToggles()).observe(document.body, { childList: true, subtree: true });
});

/* File pickers outside the step forms (portal and dashboard modals): the input sits hidden inside a
   .file-drop label, so show the chosen file name there. Step-form fields (data-path) render their own. */
document.addEventListener("change", e => {
  const el = e.target;
  if (el.type !== "file" || el.dataset.path || !el.closest(".file-drop")) return;
  const drop = el.closest(".file-drop");
  let name = drop.querySelector("[data-file-name]");
  if (!name) { name = document.createElement("b"); name.dataset.fileName = ""; name.style.cssText = "display:block;margin-top:6px;word-break:break-all;text-transform:none;letter-spacing:0"; drop.append(name); }
  name.textContent = [...el.files].map(f => f.name).join(", ");
});
