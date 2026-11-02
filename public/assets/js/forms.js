/* COSPARK — reusable dynamic step-form engine
   One framework powers: insurance quotes, claims, producer application.
   Field: {name,label,type,options,required,half,placeholder,hint,show(state),min,max}
   Types: text,email,tel,number,date,select,textarea,chips,multi,file,check,group(+fields,repeat,itemLabel)
   Submission: opts.action (URL) receives `payload` (JSON state) + `files[path][]`; the server
   returns { reference }. Validation errors (keyed by field path) send the user back to that step.
*/
class StepForm {
  constructor(root, opts) {
    this.root = root; this.o = opts; this.state = opts.state || {}; this.i = opts.start || 0; this.agree = false; this.files = {};
    root.addEventListener("input", e => this.onInput(e));
    root.addEventListener("change", e => this.onInput(e, true));
    root.addEventListener("click", e => this.onClick(e));
    this.render();
  }
  get steps() { return this.o.getSteps(this.state); }
  get total() { return this.steps.length + 1; } // + review
  esc(s) { return String(s ?? "").replace(/[&<>"]/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c])); }
  get(path) { return path.split(".").reduce((o, k) => o == null ? o : o[k], this.state); }
  set(path, v) {
    const ks = path.split("."); let o = this.state;
    ks.slice(0, -1).forEach((k, n) => { if (o[k] == null) o[k] = /^\d+$/.test(ks[n + 1]) ? [] : {}; o = o[k]; });
    o[ks.at(-1)] = v;
  }
  visible(f) { return !f.show || f.show(this.state); }

  field(f, base = "") {
    if (!this.visible(f)) return "";
    const p = base + f.name, v = this.get(p), req = f.required ? `<span class="req">*</span>` : "";
    const cls = `field${f.half ? " half" : ""}`, err = `<span class="err-msg">${f.err || "This field is required."}</span>`;
    const hint = f.hint ? `<span class="hint">${f.hint}</span>` : "";
    const id = "f_" + p.replace(/\./g, "_");
    const attrs = `id="${id}" data-path="${p}" ${f.required ? 'data-req="1"' : ""} ${f.placeholder ? `placeholder="${this.esc(f.placeholder)}"` : ""} ${f.min != null ? `min="${f.min}"` : ""} ${f.max != null ? `max="${f.max}"` : ""}`;
    switch (f.type) {
      case "group": {
        const items = Array.isArray(v) && v.length ? v : [{}]; if (!Array.isArray(v)) this.set(p, items);
        return items.map((_, n) => `<div class="group-box"><div class="group-head"><b>${f.itemLabel || f.label} ${items.length > 1 || f.repeat ? n + 1 : ""}</b>${n > 0 ? `<button type="button" class="btn-x" data-remove="${p}" data-n="${n}">Remove</button>` : ""}</div><div class="form-grid">${f.fields.map(sf => this.field(sf, `${p}.${n}.`)).join("")}</div></div>`).join("")
          + (f.repeat ? `<div class="field"><button type="button" class="btn btn-line btn-sm" data-add="${p}" style="justify-self:start;width:max-content">+ Add another ${(f.itemLabel || f.label).toLowerCase()}</button></div>` : "");
      }
      case "chips": case "multi": {
        const sel = f.type === "multi" ? (v || []) : [v];
        return `<div class="${cls}" data-fpath="${p}"><span class="lbl">${f.label} ${req}</span><div class="chips" role="group" aria-label="${this.esc(f.label)}">${f.options.map(o => `<button type="button" class="chip" data-chip="${p}" data-multi="${f.type === "multi" ? 1 : ""}" data-val="${this.esc(o)}" aria-pressed="${sel.includes(o)}">${o}</button>`).join("")}</div>${hint}${err}</div>`;
      }
      case "select":
        return `<div class="${cls}" data-fpath="${p}"><label for="${id}">${f.label} ${req}</label><select ${attrs}><option value="">Select…</option>${f.options.map(o => `<option ${v === o ? "selected" : ""}>${o}</option>`).join("")}</select>${hint}${err}</div>`;
      case "textarea":
        return `<div class="${cls}" data-fpath="${p}"><label for="${id}">${f.label} ${req}</label><textarea ${attrs}>${this.esc(v)}</textarea>${hint}${err}</div>`;
      case "file":
        return `<div class="${cls}" data-fpath="${p}"><span class="lbl">${f.label} ${req}</span><label class="file-drop" for="${id}"><input type="file" ${attrs} ${f.multiple !== false ? "multiple" : ""} accept="${f.accept || ".pdf,.jpg,.jpeg,.png,.doc,.docx,.heic"}"><b>${v && v.length ? v.join(", ") : "Choose files"}</b><br><span class="small">${v && v.length ? "Click to replace" : `${f.formats || "PDF, JPG, PNG or DOC"} · up to 10 MB each`}</span></label>${hint}${err}</div>`;
      case "check":
        return `<div class="${cls}" data-fpath="${p}"><label class="checkline" style="font-family:var(--sans);text-transform:none;letter-spacing:0;font-size:14px"><input type="checkbox" ${attrs} ${v ? "checked" : ""}> <span>${f.label}</span></label>${err}</div>`;
      default:
        return `<div class="${cls}" data-fpath="${p}"><label for="${id}">${f.label} ${req}</label><input type="${f.type || "text"}" ${attrs} value="${this.esc(v)}" ${f.auto ? `autocomplete="${f.auto}"` : ""}>${hint}${err}</div>`;
    }
  }
  head(title, sub) {
    const n = this.i + 1, t = this.total;
    return `<div class="row-between"><span class="mono copper">Step ${String(n).padStart(2, "0")}</span><span class="mono muted">${String(n).padStart(2, "0")} / ${String(t).padStart(2, "0")}</span></div>
      <div class="steps-bar" aria-hidden="true">${Array.from({ length: t }, (_, k) => `<i class="${k <= this.i ? "on" : ""}"></i>`).join("")}</div>
      <h2 class="step-title" tabindex="-1">${title}</h2>${sub ? `<p class="step-sub">${sub}</p>` : `<div style="height:20px"></div>`}`;
  }
  nav(nextLabel) {
    return `<div class="form-nav">${this.i > 0 ? `<button type="button" class="btn btn-line" data-back>← Back</button>` : "<span></span>"}${this.o.onSaveDraft && this.i > 0 && this.i < this.steps.length ? `<button type="button" class="btn-x" data-save>Save & finish later</button>` : ""}<button type="button" class="btn btn-copper" data-next>${nextLabel || "Continue"} <span aria-hidden="true">→</span></button></div>`;
  }
  render(focus) {
    const steps = this.steps;
    if (this.i >= steps.length) return this.renderReview();
    const s = steps[this.i];
    this.root.innerHTML = this.head(s.title, s.sub) + `<div class="form-grid">${s.fields.map(f => this.field(f)).join("")}</div>` + (s.note ? `<div class="notice info" style="margin-top:22px">${s.note}</div>` : "") + this.nav();
    this.updateSummary();
    if (focus) this.root.querySelector(".step-title")?.focus({ preventScroll: true });
  }
  display(f, v) {
    if (v == null || v === "" || (Array.isArray(v) && !v.length)) return `<span class="muted">—</span>`;
    if (f.type === "check") return v ? "Yes" : "No";
    if (Array.isArray(v)) return this.esc(v.join(", "));
    return this.esc(v);
  }
  renderReview() {
    const blocks = this.steps.map((s, si) => {
      const rows = s.fields.filter(f => this.visible(f)).flatMap(f => {
        if (f.type === "group") return (this.get(f.name) || []).flatMap((it, n) => f.fields.filter(sf => !sf.show || sf.show(this.state)).map(sf => `<dt>${f.itemLabel || f.label} ${n + 1} · ${sf.label}</dt><dd>${this.display(sf, it?.[sf.name])}</dd>`));
        return [`<dt>${f.label}</dt><dd>${this.display(f, this.get(f.name))}</dd>`];
      }).join("");
      return `<div class="review-block"><header><b>${s.short || s.title}</b><button type="button" class="btn-x" data-goto="${si}">Edit</button></header><dl>${rows}</dl></div>`;
    }).join("");
    const terms = this.o.terms ? `<label class="checkline" style="margin-top:20px"><input type="checkbox" id="sfAgree" ${this.agree ? "checked" : ""}> <span>${this.o.terms}</span></label><p class="err-msg small" id="sfAgreeErr" style="color:var(--red);display:none">Accept the terms to continue.</p>` : "";
    this.root.innerHTML = this.head("Review & submit", this.o.reviewSub || "Check your details. Use Edit to change any section.") + blocks + terms + this.nav(this.o.submitLabel || "Submit");
    this.updateSummary();
    this.root.querySelector(".step-title")?.focus({ preventScroll: true });
  }
  validateStep() {
    let ok = true;
    const check = (f, base = "") => {
      if (!this.visible(f)) return;
      const p = base + f.name;
      if (f.type === "group") return (this.get(p) || []).forEach((_, n) => f.fields.forEach(sf => check(sf, `${p}.${n}.`)));
      let v = this.get(p), bad = false, message = f.err || "This field is required.";
      if (f.required) bad = v == null || (typeof v === "string" && !v.trim()) || v === false || (Array.isArray(v) && !v.length);
      if (!bad && f.type === "email" && v) { bad = !validEmail(v); if (bad) message = EMAIL_FORMAT_ERR; }
      if (!bad && v !== "" && v != null && ["number", "date"].includes(f.type)) {
        const value = f.type === "number" ? Number(v) : v;
        if (f.type === "number" && !Number.isFinite(value)) bad = true;
        if (f.min != null && value < f.min) bad = true;
        if (f.max != null && value > f.max) bad = true;
        if (bad && !f.err) message = f.type === "date" ? `Choose a date${f.min != null ? ' on or after ' + f.min : ''}${f.max != null ? ' on or before ' + f.max : ''}.` : `Enter a number${f.min != null ? ' at least ' + f.min : ''}${f.max != null ? ' and at most ' + f.max : ''}.`;
      }
      if (!bad && f.pattern && v) bad = !f.pattern.test(v);
      if (!bad && f.validate && v) bad = !f.validate(v, this.state, p);
      const el = this.root.querySelector(`[data-fpath="${p}"]`); el?.classList.toggle("err", bad);
      if (el?.querySelector(".err-msg")) el.querySelector(".err-msg").textContent = message;
      if (bad) ok = false;
    };
    this.steps[this.i].fields.forEach(f => check(f));
    if (!ok) this.root.querySelector(".field.err")?.scrollIntoView({ behavior: "smooth", block: "center" });
    return ok;
  }
  onInput(e, isChange) {
    const el = e.target, p = el.dataset.path; if (!p) return;
    if (el.type === "file") { this.files[p] = [...el.files]; this.set(p, this.files[p].map(f => f.name)); return this.render(); }
    this.set(p, el.type === "checkbox" ? el.checked : el.value);
    el.closest(".field")?.classList.remove("err");
    this.updateSummary();
    if (isChange && el.tagName === "SELECT" && this.hasConditional()) this.render();
  }
  hasConditional() { const s = this.steps[this.i]; return s && JSON.stringify(s.fields.map(f => !!f.show || (f.fields || []).some(x => x.show))).includes("true"); }
  onClick(e) {
    const t = e.target.closest("button"); if (!t) return;
    if (t.dataset.chip) {
      const p = t.dataset.chip, val = t.dataset.val;
      if (t.dataset.multi) { const cur = this.get(p) || []; this.set(p, cur.includes(val) ? cur.filter(x => x !== val) : [...cur, val]); }
      else this.set(p, val);
      const prevSteps = this.steps.length;
      if (this.o.onChip) this.o.onChip(p, val, this);
      this.render(); return;
    }
    if (t.dataset.add) { const a = this.get(t.dataset.add) || []; a.push({}); this.set(t.dataset.add, a); this.render(); return; }
    if (t.dataset.remove) { const a = this.get(t.dataset.remove); a.splice(+t.dataset.n, 1); this.render(); return; }
    if (t.dataset.goto) { this.i = +t.dataset.goto; this.render(true); this.scrollTop(); return; }
    if (t.hasAttribute("data-save")) return this.o.onSaveDraft(this);
    if (t.hasAttribute("data-back")) { this.i--; this.render(true); this.scrollTop(); return; }
    if (t.hasAttribute("data-next")) {
      if (this.i >= this.steps.length) return this.submit();
      if (!this.validateStep()) return;
      this.i++; this.render(true); this.scrollTop();
    }
  }
  scrollTop() { const y = this.root.getBoundingClientRect().top + scrollY - 110; if (scrollY > y) scrollTo({ top: y, behavior: "smooth" }); }
  submit() {
    if (this.o.terms) {
      const a = this.root.querySelector("#sfAgree"); this.agree = a.checked;
      if (!a.checked) { this.root.querySelector("#sfAgreeErr").style.display = "block"; return; }
    }
    const btn = this.root.querySelector("[data-next]"); busy(btn, true, "Submitting…");
    const fd = new FormData();
    fd.append("payload", JSON.stringify(this.state));
    Object.entries(this.files).forEach(([p, list]) => { if (this.get(p)?.length) list.forEach(f => fd.append(`files[${p}][]`, f)); });
    send(this.o.action, fd).then(r => {
      this.root.innerHTML = this.o.confirm(this.state, r.reference);
      if (this.o.onDone) this.o.onDone(this.state, r.reference);
      this.scrollTop();
    }).catch(err => {
      busy(btn, false); toast(err.message);
      const keys = Object.keys(err.errors || {}).map(k => k.replace(/^files\./, ""));
      if (keys.length) this.showErrors(keys, err.errors);
    });
  }
  /* Jump to the first step that owns an errored path and flag the fields */
  showErrors(keys, errors) {
    const owns = (s, k) => s.fields.some(f => k === f.name || k.startsWith(f.name + "."));
    const si = this.steps.findIndex(s => keys.some(k => owns(s, k)));
    if (si < 0) return;
    this.i = si; this.render(true); this.scrollTop();
    Object.entries(errors).forEach(([k, msgs]) => {
      const el = this.root.querySelector(`[data-fpath="${k.replace(/^files\./, "").replace(/\.\d+$/, "")}"]`); if (!el) return;
      el.classList.add("err"); const m = el.querySelector(".err-msg"); if (m) m.textContent = msgs[0];
    });
  }
  updateSummary() {
    if (!this.o.summary) return;
    this.o.summary.forEach(([sel, fn]) => { const el = document.querySelector(sel); if (el) el.textContent = fn(this.state) || "—"; });
  }
}

/* Shared field sets */
const US_STATES = "AL AK AZ AR CA CO CT DE FL GA HI ID IL IN IA KS KY LA ME MD MA MI MN MS MO MT NE NV NH NJ NM NY NC ND OH OK OR PA RI SC SD TN TX UT VT VA WA WV WI WY".split(" ");
const F = {
  first: { name: "first", label: "First name", required: true, half: true, auto: "given-name" },
  last: { name: "last", label: "Last name", required: true, half: true, auto: "family-name" },
  email: { name: "email", label: "Email", type: "email", required: true, half: true, auto: "email" },
  phone: { name: "phone", label: "Phone", type: "tel", required: true, half: true, auto: "tel", validate: validPhone, err: "Enter a valid phone number with 10 to 15 digits." },
  pref: { name: "pref", label: "Preferred contact method", type: "chips", options: ["Email", "Phone", "Text"], required: true },
  street: { name: "street", label: "Street address", required: true, auto: "street-address" },
  city: { name: "city", label: "City", required: true, half: true },
  state: { name: "st", label: "State", type: "select", options: US_STATES, required: true, half: true },
  zip: { name: "zip", label: "ZIP code", required: true, half: true, pattern: /^\d{5}(-\d{4})?$/, err: "Enter a 5-digit ZIP code." },
};
