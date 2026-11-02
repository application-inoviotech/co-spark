/* COSPARK — member registration (SOW: Member Registration, Membership Selection, Email Verification)
   plan → personal → address → security (account created) → verify (email OTP) → payment (paid plans) → done
   REGISTER = { plans, freq, states, state } is provided by the page. Card numbers never leave the browser:
   the card is tokenized by Authorize.net Accept.js (card.js) and only the token is sent. */
document.addEventListener("DOMContentLoaded", () => {
  const { plans: PLANS, freq: FREQ, states: US_STATES } = REGISTER;
  const box = document.getElementById("register"), root = document.getElementById("rStep"), bar = document.getElementById("rBar");
  const st = { plan: "premium", freq: "monthly", ...REGISTER.state };
  const steps = () => ["plan", "personal", "address", "security", "verify", ...(PLANS[st.plan].p ? ["payment"] : []), "done"];
  let i = Math.max(0, steps().indexOf(st.step || "plan"));
  const months = () => FREQ[st.freq].months;
  const dtFor = () => PLANS[st.plan].dt * months();
  const esc = s => String(s ?? "").replace(/[&<>"]/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]));

  const side = () => { const p = PLANS[st.plan]; document.getElementById("selPlan").textContent = p.n; document.getElementById("selPrice").textContent = p.p ? `${fmt$(p.p * months())} / ${FREQ[st.freq].label.toLowerCase()}` : "$0 — always free"; document.getElementById("selPerks").innerHTML = p.perks.map(x => `<div style="padding:4px 0">✓ ${x}</div>`).join(""); };
  const fld = (id, l, t = "text", half, extra = "") => `<div class="field ${half ? 'half' : ''}"><label for="${id}">${l} <span class="req">*</span></label><input id="${id}" name="${id}" type="${t}" required value="${t === "password" ? "" : esc(st[id])}" ${extra}><span class="err-msg">Required.</span></div>`;
  const nav = (next = "Continue") => `<div class="form-nav">${i && !st.locked ? '<button class="btn btn-line" data-b>← Back</button>' : '<span></span>'}<button class="btn btn-copper" data-n>${next} →</button></div>`;
  const V = {
    plan: () => `<h1 style="font-size:44px">Choose your membership</h1><p class="muted" style="margin:10px 0 26px">You can upgrade or change later.</p><div style="display:grid;gap:12px">${Object.entries(PLANS).map(([k, p]) => `<button type="button" class="appt-type ${st.plan === k ? 'on' : ''}" data-plan="${k}" style="display:flex;justify-content:space-between;align-items:center;gap:20px"><div><b>${p.n}</b><span>${p.perks.join(" · ")}</span></div><span style="font-family:var(--serif);font-size:30px;white-space:nowrap">${p.p ? "$" + p.p : "$0"}<small class="mono muted" style="font-size:9px"> /MO</small></span></button>`).join("")}</div>${nav()}`,
    personal: () => `<h1 style="font-size:44px">Your details</h1><p class="muted" style="margin:10px 0 26px">Already a member? <a class="copper" href="${box.dataset.login}">Sign in</a></p><div class="form-grid">${fld("first", "First name", "text", 1, 'autocomplete="given-name"')}${fld("last", "Last name", "text", 1, 'autocomplete="family-name"')}${fld("email", "Email", "email", 0, 'autocomplete="email"')}${fld("phone", "Phone", "tel", 0, 'autocomplete="tel"')}</div>${nav()}`,
    address: () => `<h1 style="font-size:44px">Your address</h1><p class="muted" style="margin:10px 0 26px">Used for your policies and billing.</p><div class="form-grid">${fld("street", "Street address")}<div class="field"><label for="street2">Address line 2</label><input id="street2" name="street2" value="${esc(st.street2)}"></div>${fld("city", "City", "text", 1)}<div class="field half"><label for="stt">State <span class="req">*</span></label><select id="stt" name="stt" required><option value="">Select…</option>${US_STATES.map(s => `<option ${st.stt === s ? 'selected' : ''}>${s}</option>`).join("")}</select><span class="err-msg">Required.</span></div>${fld("zip", "ZIP code", "text", 1, 'data-validation-message="Enter a 5-digit ZIP code." inputmode="numeric" pattern="\\d{5}"')}</div>${nav()}`,
    security: () => `<h1 style="font-size:44px">Secure your account</h1><p class="muted" style="margin:10px 0 26px">At least 8 characters, including a number.</p><div class="form-grid">${fld("pw", "Password", "password", 1, 'autocomplete="new-password"')}${fld("pw2", "Confirm password", "password", 1, 'autocomplete="new-password"')}<div class="field"><label class="checkline" style="font-family:var(--sans);text-transform:none;letter-spacing:0"><input type="checkbox" id="sms" ${st.sms ? 'checked' : ''}> <span>${esc(box.dataset.smsConsent)}</span></label></div><div class="field"><label class="checkline" style="font-family:var(--sans);text-transform:none;letter-spacing:0"><input type="checkbox" id="tos" required> <span>I agree to the <a class="copper" href="${box.dataset.terms}" target="_blank">Terms</a> and <a class="copper" href="${box.dataset.privacy}" target="_blank">Privacy Policy</a>.</span></label><span class="err-msg">Accept the terms to continue.</span></div></div>${nav("Create account")}`,
    verify: () => `<h1 style="font-size:44px">Verify your email</h1><p class="muted" style="margin:10px 0 26px">We sent a 6-digit code to <b style="color:var(--ink)">${esc(st.email)}</b>.</p><div class="otp">${Array(6).fill('<input inputmode="numeric" maxlength="1" aria-label="Digit">').join("")}</div><p class="small" id="otpErr" style="color:var(--red);display:none;margin-top:10px">Enter all 6 digits.</p><button class="btn-x" style="margin-top:16px" data-resend>Resend code</button>${nav("Verify email")}`,
    payment: () => { const p = PLANS[st.plan], amt = p.p * months(); return `<h1 style="font-size:44px">Activate your membership</h1><p class="muted" style="margin:10px 0 22px">Your ${p.n} membership starts after this payment.</p><span class="lbl mono">Billing frequency</span><div class="chips" style="margin:10px 0 24px">${Object.entries(FREQ).filter(([k]) => (REGISTER.freqs[st.plan] || []).includes(k)).map(([k, f]) => `<button type="button" class="chip ${st.freq === k ? 'on' : ''}" data-freq="${k}">${f.label}</button>`).join("")}</div>
      <div class="card" style="background:var(--bg-2)"><div class="row-between"><span>${p.n} membership · ${FREQ[st.freq].label}</span><b>${fmt$(amt)}</b></div><div class="row-between muted small" style="margin-top:8px"><span>Dues Tokens on payment</span><span>+${dtFor()} DT</span></div><div class="divider" style="margin:14px 0"></div><div class="row-between"><b>Due today</b><b style="font-family:var(--serif);font-size:28px;font-weight:400">${fmt$(amt)}</b></div></div>
      <div class="card" style="margin-top:14px"><div class="row-between" style="margin-bottom:16px"><span class="mono muted">Secure payment</span><span class="mono" style="color:var(--green)">🔒 PCI gateway</span></div><div class="form-grid">${fld("cname", "Name on card")}${CardEntry.fields()}</div><label class="checkline" style="margin-top:16px"><input type="checkbox" id="autorenew" checked> Use this card for recurring ${FREQ[st.freq].label.toLowerCase()} billing</label></div>${nav("Pay " + fmt$(amt))}`; },
    done: () => `<div class="confirm" style="text-align:left;padding:0"><div class="confirm-mark" style="margin:0 0 22px">✓</div><h1 style="font-size:44px">${PLANS[st.plan].p ? "Your membership is active." : "Your account is ready."}</h1><p class="muted" style="margin:14px 0 20px">Welcome to COSPARK, ${esc(st.first)}. A confirmation was sent to ${esc(st.email)}${st.sms ? " and by text" : ""}.</p>${PLANS[st.plan].p && st.payRef ? `<div class="ref-id">${esc(st.payRef)}</div><p class="small muted">+${st.dtCredited} Dues Tokens were added to your wallet.</p>` : ""}<a href="${box.dataset.dashboard}" class="btn btn-copper" style="margin-top:14px">Go to my dashboard →</a></div>`,
  };
  const render = () => { const s = steps(); if (s[i] === "payment") fixFreq(); bar.innerHTML = s.map((_, k) => `<i class="${k <= i ? 'on' : ''}"></i>`).join(""); root.innerHTML = V[s[i]](); side(); if (s[i] === "payment") CardEntry.restore(root); const o = root.querySelector(".otp"); if (o) { otpWire(o); o.querySelector("input").focus(); } else root.querySelector("input")?.focus(); };
  const save = () => root.querySelectorAll("input[name],select[name]").forEach(e => { if (!["pw", "pw2", "cnum", "ccvc"].includes(e.name)) st[e.name] = e.value; });
  const fieldErr = (id, msg) => { const el = root.querySelector("#" + id); if (!el) return; const f = el.closest(".field"); f.classList.add("err"); f.querySelector(".err-msg").textContent = msg; };

  // Server field → wizard step that owns it
  const OWNER = { plan: "plan", first: "personal", last: "personal", email: "personal", phone: "personal", street: "address", street2: "address", city: "address", stt: "address", zip: "address", pw: "security" };
  const createAccount = async (pw, pw2) => {
    const r = await send(box.dataset.store, { plan: st.plan, first: st.first, last: st.last, email: st.email, phone: st.phone, street: st.street, street2: st.street2, city: st.city, stt: st.stt, zip: st.zip, pw, pw_confirmation: pw2, sms: !!st.sms, tos: true });
    st.locked = true; // account exists: earlier steps can't be edited from here
    return r;
  };

  // Keep the chosen frequency valid for the plan
  const fixFreq = () => { if (!REGISTER.freqs[st.plan]?.includes(st.freq)) st.freq = REGISTER.freqs[st.plan]?.[0] || "monthly"; };

  root.addEventListener("click", async e => {
    const t = e.target.closest("button"); if (!t) return;
    if (t.dataset.plan) { st.plan = t.dataset.plan; render(); return; }
    if (t.dataset.freq) { save(); CardEntry.keep(root); st.freq = t.dataset.freq; render(); return; }
    if (t.hasAttribute("data-resend")) {
      try { await send(box.dataset.resend, {}); toast("A new code was sent to " + st.email); } catch (err) { toast(err.message); }
      return;
    }
    if (t.hasAttribute("data-b")) { save(); i--; render(); return; }
    if (!t.hasAttribute("data-n")) return;

    const cur = steps()[i];
    if (!validate(root)) return;
    if (cur === "address" && !/^\d{5}$/.test(root.querySelector("#zip").value)) { root.querySelector("#zip").closest(".field").classList.add("err"); return; }

    if (cur === "security") {
      const a = root.querySelector("#pw"), b = root.querySelector("#pw2");
      if (a.value.length < 8 || !/\d/.test(a.value)) return fieldErr("pw", "Use 8+ characters with a number.");
      if (a.value !== b.value) return fieldErr("pw2", "Passwords don't match.");
      st.sms = root.querySelector("#sms").checked;
      busy(t, true, "Creating account…");
      try { await createAccount(a.value, b.value); i++; render(); }
      catch (err) {
        busy(t, false);
        const key = Object.keys(err.errors || {})[0];
        if (key && OWNER[key] && OWNER[key] !== "security") { i = steps().indexOf(OWNER[key]); render(); showServerErrors(root, err.errors); }
        else if (key === "pw") fieldErr("pw", err.message);
        toast(err.message);
      }
      return;
    }

    if (cur === "verify") {
      const code = [...root.querySelectorAll(".otp input")].map(x => x.value).join("");
      if (code.length < 6) { root.querySelector("#otpErr").style.display = "block"; return; }
      busy(t, true, "Verifying…");
      try { await send(box.dataset.verify, { code }); i++; render(); }
      catch (err) { busy(t, false); const m = root.querySelector("#otpErr"); m.textContent = err.message; m.style.display = "block"; }
      return;
    }

    if (cur === "payment") {
      save();
      busy(t, true, "Processing…");
      let card;
      try { card = await CardEntry.tokenize(root, REGISTER.acceptJs); } catch (err) { busy(t, false); return fieldErr(err.field, err.message); }
      try {
        const r = await send(box.dataset.payment, { freq: st.freq, payment_method: card.token, payment_descriptor: card.descriptor, auto_renew: root.querySelector("#autorenew").checked });
        CardEntry.clear(); st.payRef = r.reference; st.dtCredited = r.dues_tokens; i++; render();
      } catch (err) { busy(t, false); fieldErr("cnum", err.message); toast(err.message); }
      return;
    }

    save(); i++; render();
  });
  render();
});
