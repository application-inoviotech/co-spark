/* COSPARK — Member Dashboard
   Data comes from DASH.data (see MemberDashboardPresenter); every action posts to the server and reloads.
   DASH (endpoint URLs) is provided by resources/views/dashboard/member.blade.php. */
(() => {
const $ = s => document.querySelector(s);
const today = new Date();
const d = s => new Date(s + "T12:00");
const fd = s => s ? d(s).toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" }) : "—";
const iso = dt => `${dt.getFullYear()}-${String(dt.getMonth() + 1).padStart(2, "0")}-${String(dt.getDate()).padStart(2, "0")}`;
const esc = s => String(s ?? "").replace(/[&<>"']/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
const url = (name, ...params) => params.reduce((u, p) => u.replace(/__ID__|__REF__/, encodeURIComponent(p)), DASH[name]);

/* ---------- Live data ---------- */
let DB = null, PLAN = {};
async function load() {
  const res = await fetch(DASH.data, { headers: { Accept: "application/json" }, credentials: "same-origin" });
  if (res.status === 401 || res.status === 419) { location.reload(); return; }
  DB = await res.json(); PLAN = DB.plans;
}
const refresh = async () => { await load(); render(); };
document.addEventListener("cospark:appointment-created", () => { refresh().catch(() => toast("Appointment saved. Reload to refresh your dashboard.")); });
const act = async (u, data, method) => { try { return await send(u, data, method); } catch (e) { toast(e.message); throw e; } };

const bal = t => DB.ledger.filter(l => l.tok === t).reduce((a, l) => a + l.amt, 0);
// DT quoted on pending stays stay with the member (deducted only on confirmation) but are kept out of transfers and partner redemptions
const spendDT = () => bal("DT") - DB.vacations.filter(v => v.status === "Pending").reduce((a, v) => a + v.tokens, 0);
const sumType = (t, ty) => Math.abs(DB.ledger.filter(l => l.tok === t && l.type === ty).reduce((a, l) => a + l.amt, 0));
const monthsSince = s => Math.max(0, Math.floor((today - d(s)) / 2.63e9));
const vtAccrued = () => DB.vt.reduce((a, l) => a + l.accrued, 0);
const journey = () => Math.min(60, monthsSince(DB.member.start) + 1);
const P = () => PLAN[DB.plan];
const paid = () => ["premium", "associate"].includes(DB.plan);
const nextDuesAmount = () => DB.member.nextChargeAmount ?? (DB.member.cancelled || DB.member.pendingPlan === "free" ? 0 : DB.member.periodAmount);
const nextDuesText = () => DB.member.cancelled || DB.member.pendingPlan === "free" ? `No further dues · cancels ${fd(DB.member.next)}` : DB.member.nextChargeDate === null ? "No automatic renewal" : fd(DB.member.nextChargeDate ?? DB.member.next);
const empty = msg => `<p class="empty">${msg}</p>`;
/* ---------- Pagination: 10 per page, same pager as the admin tables; each list/filter keeps its own page ---------- */
const PG = {};
const page = (key, items, size = 10) => {
  const pages = Math.max(1, Math.ceil(items.length / size)), p = Math.min(PG[key] || 1, pages);
  const pager = pages < 2 ? "" : `<div class="row-between" style="margin-top:14px;flex-wrap:wrap;gap:10px"><span class="mono muted" style="font-size:9.5px">${items.length} records · page ${p} of ${pages}</span><div style="display:flex;gap:8px">${p > 1 ? `<button class="btn btn-line btn-sm" data-pg="${key}" data-to="${p - 1}">← Previous</button>` : ""}${p < pages ? `<button class="btn btn-line btn-sm" data-pg="${key}" data-to="${p + 1}">Next →</button>` : ""}</div></div>`;
  return [items.slice((p - 1) * size, p * size), pager];
};
const paged = (key, items, renderRows) => { const [rows, pager] = page(key, items); return rows.length ? renderRows(rows) + pager : ""; };
document.addEventListener("click", e => { const b = e.target.closest("[data-pg]"); if (b) { PG[b.dataset.pg] = +b.dataset.to; render(); } });

/* Expired / cancelled paid membership: benefits are paused until the member renews */
const lapsedNotice = () => DB.member.lapsedPlan ? `<div class="notice warn" style="margin-bottom:14px"><span>${ic("alert")}</span><div style="flex:1"><b>Your ${PLAN[DB.member.lapsedPlan].n} membership ${DB.member.status.toLowerCase() === "cancelled" ? "ended" : "expired"} ${fd(DB.member.expiredAt)}.</b> Token earning, QR benefits and vacation planning are paused.</div><a href="#membership" class="link-arrow">Renew</a></div>` : "";

/* ---------- Helpers ---------- */
const pill = s => { const c = /active|success|confirmed|paid|completed|credited|approved|current/i.test(s) ? "green" : /fail|declin|forfeit|expired|cancel|deactivated/i.test(s) ? "red" : /pending|review|requested|due|action|progress|invited/i.test(s) ? "amber" : "grey"; return `<span class="pill ${c}">${esc(s)}</span>`; };
const box = (title, body, right = "") => `<section class="box"><div class="box-h"><h3>${title}</h3>${right}</div>${body}</section>`;
const lock = what => `<div class="box" style="text-align:center;padding:60px 30px"><span class="copper">${ic("star")}</span><h3 class="h3s" style="margin:16px 0 10px">${what} is part of paid membership.</h3><p class="muted" style="max-width:46ch;margin:0 auto 24px">Upgrade to Associate ($8.88/mo) or Premium ($25/mo) to earn tokens, use partner benefits and plan vacations.</p><a href="#membership" class="btn btn-copper">Compare upgrades →</a></div>`;
function modal(html, sm) { const b = $("#dBox"); b.className = "modal-box" + (sm ? " sm" : ""); b.innerHTML = `<button class="modal-x" data-close aria-label="Close">×</button>` + html; openModal("dModal"); }
const closeM = () => closeModal($("#dModal"));
const methodOpts = (sel) => DB.methods.map(m => `<label class="appt-type ${m.id === sel ? "on" : ""}" style="display:flex;gap:14px;align-items:center;padding:14px 16px;cursor:pointer"><input type="radio" name="pm" value="${m.id}" ${m.id === sel ? "checked" : ""} style="accent-color:var(--copper)"><span>${ic(m.bank ? "building" : "card")}</span><span style="flex:1"><b style="font-size:14px">${esc(m.bank || m.brand)} •${m.last4}</b><span style="display:block">${m.expired ? '<span style="color:var(--red)">Expired ' + m.exp + "</span>" : m.bank ? "ACH" : "Expires " + m.exp}</span></span>${m.def ? '<span class="tag">Default</span>' : ""}</label>`).join("");
const avatar = (size = "") => DB.user.avatar ? `<img src="${esc(DB.user.avatar)}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%">` : esc((DB.user.first[0] || "") + (DB.user.last[0] || ""));

/* ---------- Generic payment flow (SOW §30, §32, §36) ----------
   endpoint + data are posted with the chosen method_id; the server charges and records the payment. */
function payFlow({ title, lines, total, kind, desc, tokens, endpoint, data = {} }) {
  if (!DB.methods.length) return addMethod(() => payFlow({ title, lines, total, kind, desc, tokens, endpoint, data }), "Add a payment method to continue.");
  let pm = (DB.methods.find(m => m.def) || DB.methods[0]).id;
  const step1 = () => modal(`<span class="eyebrow">${kind}</span><h2 class="h3s" style="margin:12px 0 22px;font-size:34px">${title}</h2>
    <div class="card" style="background:var(--bg-2)">${lines.map(([a, b]) => `<div class="row-between" style="padding:6px 0"><span class="muted">${a}</span><span>${esc(b)}</span></div>`).join("")}<div class="divider" style="margin:12px 0"></div><div class="row-between"><b>Total</b><b style="font-family:var(--serif);font-weight:400;font-size:30px">${fmt$(total)}</b></div>${tokens ? `<p class="small copper" style="margin:10px 0 0">${tokens}</p>` : ""}</div>
    <p class="mono muted" style="margin:24px 0 10px">Payment method</p><div style="display:grid;gap:8px" id="pmList">${methodOpts(pm)}</div>
    <button class="btn-x" style="margin-top:12px" id="pmAdd">+ Add a new payment method</button>
    <div class="form-nav"><button class="btn btn-line" data-close>Cancel</button><button class="btn btn-copper" id="payGo">Pay ${fmt$(total)} →</button></div>`);
  const wire = () => {
    $("#pmList").onchange = e => { pm = +e.target.value; $("#pmList").querySelectorAll(".appt-type").forEach(l => l.classList.toggle("on", l.querySelector("input").checked)); };
    $("#pmAdd").onclick = () => addMethod(() => { pm = (DB.methods.find(m => m.def) || DB.methods[0]).id; step1(); wire(); });
    $("#payGo").onclick = async () => {
      const m = DB.methods.find(x => x.id === pm), btn = $("#payGo"); busy(btn, true, "Processing…");
      let r; try { r = await act(endpoint, { ...data, method_id: pm }); } catch { busy(btn, false); return; }
      await load(); render();
      const ok = r.status === "succeeded";
      modal(ok ? `<div class="confirm"><div class="confirm-mark">✓</div><h2 class="h3s">Payment successful.</h2><p class="muted">${esc(desc)}</p><div class="ref-id">${r.reference}</div>${tokens && r.tokens ? `<p class="copper">${tokens.replace("will be", "were")}</p>` : ""}<p class="small muted">A receipt was emailed${DB.user.sms ? " and texted" : ""} to you.</p><div style="display:flex;gap:10px;justify-content:center"><a class="btn btn-line" href="${url("receipt", r.reference)}" target="_blank" rel="noopener">Download receipt</a><button class="btn btn-dark" data-close>Done</button></div></div>`
        : `<div class="confirm"><div class="confirm-mark" style="background:#FBE9E5;color:var(--red)">!</div><h2 class="h3s">Payment failed.</h2><p class="muted">${esc(r.failure || `Your payment with ${m.bank || m.brand} ending ${m.last4} was declined.`)} No tokens were issued.</p><div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:20px"><button class="btn btn-line" id="retry">Retry payment</button><button class="btn btn-copper" id="chg">Change payment method</button></div></div>`);
      if (!ok) { $("#retry").onclick = () => { step1(); wire(); $("#payGo").click(); }; $("#chg").onclick = () => { step1(); wire(); }; }
    };
  };
  step1(); wire();
}
const fieldErr = (el, msg) => { const f = el.closest(".field"); f.classList.add("err"); const m = f.querySelector(".err-msg"); if (m && msg) m.textContent = msg; };
/* Cards are tokenized by Authorize.net Accept.js (card.js); only the token is sent to COSPARK. */
function addMethod(after, note) {
  modal(`<span class="eyebrow">Payment methods</span><h2 class="h3s" style="margin:12px 0 8px;font-size:32px">Add a payment method</h2><p class="muted small">${note ? note + " " : ""}Card details are entered in the secure payment provider's form and never stored by COSPARK.</p>
  <div class="chips" style="margin:18px 0" id="mt"><button class="chip on" data-t="card">Card</button></div>
  <div class="card" style="border-style:dashed"><div class="row-between" style="margin-bottom:14px"><span class="mono muted">Secure gateway</span><span class="mono" style="color:var(--green)">🔒 Encrypted</span></div><div class="form-grid" id="mf">${CardEntry.fields()}</div></div>
  <label class="checkline" style="margin-top:16px"><input type="checkbox" id="mDef"> Make this my default and use it for recurring billing</label>
  <div class="form-nav"><button class="btn btn-line" data-close>Cancel</button><button class="btn btn-dark" id="mSave">Save method</button></div>`, true);
  $("#mSave").onclick = async () => {
    const btn = $("#mSave"); busy(btn, true, "Saving…");
    let card;
    try { card = await CardEntry.tokenize($("#mf"), DASH.acceptJs); } catch (err) { busy(btn, false); return fieldErr($("#" + err.field), err.message); }
    try { await act(DASH.methodStore, { payment_method: card.token, payment_descriptor: card.descriptor, default: $("#mDef").checked }); } catch { return busy(btn, false); }
    await load(); toast("Payment method saved"); after ? after() : (closeM(), render());
  };
}

/* ---------- Views ---------- */
const V = {};
V.overview = () => {
  const p = P(), pol = DB.policies.filter(x => x.status === "Active"), unread = DB.notes.filter(n => !n.read).length, nextPol = [...pol].sort((a, b) => d(a.exp) - d(b.exp))[0];
  const upcoming = DB.appts.filter(a => ["Pending", "Confirmed"].includes(a.status) && d(a.date) >= d(iso(today))).map(a => `<div class="list-row"><span class="ic">${ic("cal")}</span><div><div class="t">${esc(a.reason)}</div><div class="s">${fd(a.date)} · ${a.time} · ${a.type}</div></div></div>`).join("") + (paid() ? DB.vacations.filter(v => ["Pending", "Confirmed"].includes(v.status)).map(v => `<div class="list-row"><span class="ic">${ic("palm")}</span><div><div class="t">${esc(v.prop)}</div><div class="s">${fd(v.in)} – ${fd(v.out)}</div></div><div class="r">${pill(v.status)}</div></div>`).join("") : "");
  return `${lapsedNotice() || (DB.plan === "free" ? `<div class="notice" style="margin-bottom:14px"><span>${ic("star")}</span><div>You're on the Free plan. <a href="#membership" class="copper" style="text-decoration:underline">Upgrade</a> to start earning Dues and Insurance Tokens.</div></div>` : "")}
  <div class="kpis">
    <div class="kpi"><span class="mono muted">Membership</span><div class="v">${p.n}</div><div class="s">${pill(DB.member.status)} since ${fd(DB.member.start)}</div></div>
    <div class="kpi"><span class="mono muted">Token balance</span><div class="v">${paid() ? (bal("DT") + bal("IT")).toLocaleString() : "—"}</div><div class="s">${paid() ? `DT ${bal("DT")} · IT ${bal("IT")} · VT ${fmt$(bal("VT"))}` : "Upgrade to earn tokens"}</div></div>
    <div class="kpi"><span class="mono muted">Active policies</span><div class="v">${pol.length}</div><div class="s">${nextPol ? "Next renewal " + fd(nextPol.exp) : "No policies yet"}</div></div>
    <div class="kpi"><span class="mono muted">Next membership payment</span><div class="v">${DB.plan === "sub" ? fmt$(p.fee) : p.fee ? fmt$(nextDuesAmount()) : "$0.00"}</div><div class="s">${DB.plan === "sub" ? `Billed to ${esc(DB.member.parentName)}` : p.fee ? nextDuesText() + (DB.member.autopay ? " · Autopay on" : "") : "No dues on Free"}</div></div>
  </div>
  ${nextPol ? `<div class="notice warn" style="margin-top:14px"><span>${ic("alert")}</span><div style="flex:1"><b>${esc(nextPol.type)} policy renews ${fd(nextPol.exp)}.</b> Review coverage and pay your renewal premium.</div><a href="#policies" class="link-arrow">Review policy</a></div>` : ""}
  <div class="dash-grid">
    <div style="display:grid;gap:14px;align-content:start">
      ${paid() ? box("Token summary", `<div class="tokens3">${tokCard("dt")}${tokCard("it")}${tokCard("vt")}</div><div style="margin-top:18px"><div class="row-between"><span class="mono muted">60-month journey</span><span class="mono">${journey()} / 60</span></div><div class="progress" style="margin-top:10px"><i style="width:${journey() / 60 * 100}%"></i></div></div>`, `<a href="#wallet" class="link-arrow">Open wallet</a>`) : ""}
      ${!allowed("policies") ? "" : box("My policies", pol.length ? `<div class="grid-2">${pol.map(x => `<a href="#policies" class="card" style="padding:18px"><div class="row-between"><span class="copper">${ic(x.icon)}</span>${pill(x.status)}</div><h3 style="font-weight:500;margin:14px 0 4px">${esc(x.type)}</h3><span class="mono muted" style="font-size:9.5px">Expires ${fd(x.exp)}</span></a>`).join("")}</div>` : empty("Your policies will appear here once COSPARK adds them to your account."), `<a href="#policies" class="link-arrow">All policies</a>`)}
      ${DB.plan === "sub" ? "" : box("Recent payments", DB.payments.length ? payTable(DB.payments.slice(0, 4)) : empty("No payments yet."), `<a href="#payments" class="link-arrow">History</a>`)}
    </div>
    <div style="display:grid;gap:14px;align-content:start">
      ${box(`Notifications ${unread ? `<span class="pill copper" style="vertical-align:middle">${unread} new</span>` : ""}`, DB.notes.slice(0, 4).map(noteRow).join("") || empty("You're all caught up."), `<a href="#notifications" class="link-arrow">All</a>`)}
      ${paid() ? box("Recent token activity", DB.ledger.slice(0, 4).map(l => `<div class="list-row"><span class="ic">${ic("coin")}</span><div><div class="t">${esc(l.desc)}</div><div class="s">${fd(l.date)} · ${l.tok}</div></div><div class="r" style="font-family:var(--serif);font-size:22px;color:${l.amt > 0 ? "var(--copper)" : "var(--muted)"}">${l.amt > 0 ? "+" : ""}${l.tok === "VT" ? fmt$(l.amt) : l.amt}</div></div>`).join("") || empty("No token activity yet.")) : ""}
      ${DB.plan === "sub" ? "" : box("Claims", DB.claims.slice(0, 2).map(c => `<div class="list-row"><span class="ic">${ic("doc")}</span><div><div class="t">${c.no}</div><div class="s">${esc(c.type)} · ${esc(c.action)}</div></div><div class="r">${pill(c.status)}</div></div>`).join("") || empty("No open claims."), `<a href="#claims" class="link-arrow">View</a>`)}
      ${box("Upcoming", upcoming || empty("Nothing scheduled."))}
    </div>
  </div>`;
};
const tokCard = k => {
  if (k === "dt") return `<div class="tok dt"><span class="mono">DT · Dues tokens</span><div class="v">${bal("DT")}</div><div class="s">+${P().dt} per paid month</div></div>`;
  if (k === "it") return `<div class="tok it"><span class="mono">IT · Insurance tokens</span><div class="v">${bal("IT")}</div><div class="s">${Math.round(P().it * 100)}% of eligible premium</div></div>`;
  return `<div class="tok vt"><span class="mono">VT · Vacation tokens</span><div class="v">$${Math.round(bal("VT") + vtAccrued()).toLocaleString()}</div><div class="s">${bal("VT")} VT + accrued value*</div></div>`;
};
const noteRow = n => `<a href="#${n.go}" class="list-row" data-read="${n.id}" style="${n.read ? "" : "font-weight:600"}"><span class="ic" style="${n.read ? "background:var(--bg-2);color:var(--muted)" : ""}">${ic({ Policy: "shield", Claims: "doc", Tokens: "coin", Payments: "card", Vacations: "palm", Appointments: "cal", Membership: "star" }[n.cat] || "bell")}</span><div><div class="t">${esc(n.t)}</div><div class="s" style="font-weight:400">${esc(n.s)}</div></div><div class="r s" style="font-weight:400;white-space:nowrap">${fd(n.date)}</div></a>`;
const payTable = rows => `<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Date</th><th>Description</th><th>Method</th><th>Amount</th><th>Status</th><th></th></tr></thead><tbody>${rows.map(p => `<tr><td>${fd(p.date)}</td><td>${esc(p.desc)}<div class="tag" style="font-size:9px">${p.kind} · ${p.ref}</div></td><td>${esc(p.method)}</td><td>${fmt$(p.amt)}</td><td>${pill(p.status)}</td><td>${p.status === "Successful" ? `<a class="btn-x" href="${url("receipt", p.ref)}" target="_blank" rel="noopener">Receipt</a>` : p.retryable ? `<button class="btn-x" data-retry="${p.ref}">Retry</button>` : ""}</td></tr>`).join("")}</tbody></table></div>`;

// SOW: a producer must keep the $8.88 (Associate) membership; until it's paid their plan shows as Free here
const producerNotice = () => DB.config.isProducer && !["associate", "premium"].includes(DB.plan) ? `<div class="notice warn" style="margin-bottom:16px"><span>${ic("alert")}</span><div><b>Your producer account needs the $8.88 Associate membership.</b> Choose <b>Associate</b> under Change plan below and pay to activate it. It's required for producer benefits and the 2,000-token bonus.</div></div>` : "";
V.membership = () => {
  const p = P();
  if (DB.plan === "sub") return box("Membership", `<p class="muted">Your access is managed by the main Premium member, <b>${esc(DB.member.parentName)}</b>. Contact them to change your permissions or billing.</p><ul class="check-list" style="margin-top:14px"><li>Partner benefits</li><li>QR code</li><li>Vacation browsing</li></ul>`);
  const lastDues = DB.payments.find(x => x.kind === "Membership dues");
  const rows = [["Current plan", p.n], ["Member ID", DB.member.id], ["Status", pill(DB.member.status)], ["Start date", fd(DB.member.start)], ["Billing frequency", p.fee ? DB.member.freq : "—"], ["Fee", p.fee ? fmt$(p.fee) + " / month" : "Free"], ["Next payment", p.fee ? nextDuesText() : "—"], ["Payment status", p.fee && lastDues ? pill(lastDues.status) + " last payment" : "—"], ["Recurring billing", p.fee ? (DB.member.autopay ? `On · ${esc(DB.member.methodLabel)}` : "Off") : "—"]];
  const pending = DB.member.pendingPlan && !DB.member.cancelled ? `<div class="notice info" style="margin-top:18px">Your plan changes to <b>&nbsp;${PLAN[DB.member.pendingPlan].n}&nbsp;</b> on ${fd(DB.member.next)}.</div>` : "";
  return `${lapsedNotice()}${producerNotice()}<div class="dash-grid" style="margin-top:0">
    <div style="display:grid;gap:14px;align-content:start">
      ${box("My membership", `<dl style="display:grid;grid-template-columns:1fr 1.4fr;margin:0">${rows.map(([a, b]) => `<dt class="muted" style="padding:11px 0;border-bottom:1px solid var(--bg-2)">${a}</dt><dd style="margin:0;padding:11px 0;border-bottom:1px solid var(--bg-2)">${b}</dd>`).join("")}</dl>${pending}
        ${p.fee ? `<div style="display:flex;gap:10px;margin-top:22px;flex-wrap:wrap">${DB.member.due ? `<button class="btn btn-copper" id="payDues">Pay dues now →</button>` : ""}<button class="btn btn-line" id="chgFreq">Change frequency</button>${DB.member.cancelled ? "" : `<button class="btn-x" id="cancelM" style="margin-left:auto">Cancel membership</button>`}</div>` : ""}`)}
      ${box("Change plan", `<div class="grid-3">${["free", "associate", "premium"].map(k => `<div class="card" style="${k === DB.plan ? "border-color:var(--copper);background:var(--copper-soft)" : ""}"><span class="mono muted">${PLAN[k].n}</span><div style="font-family:var(--serif);font-size:34px;margin:8px 0">${PLAN[k].fee ? "$" + PLAN[k].fee : "$0"}</div><p class="small muted" style="margin:0 0 14px">${k === "free" ? "Digital access, no tokens" : `${PLAN[k].dt} DT/mo · ${Math.round(PLAN[k].it * 100)}% IT`}</p>${k === DB.plan ? '<span class="pill green">Current</span>' : `<button class="btn btn-sm ${k === "premium" ? "btn-copper" : "btn-line"}" data-plan="${k}">${PLAN[k].fee > p.fee ? "Upgrade" : "Switch"}</button>`}</div>`).join("")}</div>`)}
    </div>
    <div style="display:grid;gap:14px;align-content:start">
      ${box("Benefits", `<ul class="check-list">${(DB.plan === "free" ? ["Digital member account", "Policy management", "Upgrade anytime"] : [`${p.dt} Dues Tokens each paid month`, `Insurance Tokens at ${Math.round(p.it * 100)}% of eligible premium`, "QR partner benefits", "Vacation planning", "Token transfers", ...(DB.plan === "premium" ? ["Sub-accounts at $8.88/mo", "Priority partner benefits"] : [])]).map(x => `<li>${x}</li>`).join("")}</ul>`)}
      ${box("Dues history", DB.payments.some(x => x.kind === "Membership dues") ? payTable(DB.payments.filter(x => x.kind === "Membership dues").slice(0, 5)) : empty("No dues payments yet."))}
    </div></div>`;
};
V.policies = () => `<div class="row-between" style="margin-bottom:16px;flex-wrap:wrap"><p class="muted" style="margin:0">${DB.policies.length} policies on your account. Insurance payments are separate from membership dues.</p><a class="btn btn-dark btn-sm" href="${DASH.quote}">Get a Quote</a></div>
  ${DB.quotes.length ? `<div style="margin-bottom:16px">${box("Quote requests", `<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Reference</th><th>Insurance</th><th>Requested</th><th>Status</th></tr></thead><tbody>${DB.quotes.map(q => `<tr><td><b>${esc(q.ref)}</b></td><td>${esc(q.type)}</td><td>${fd(q.date)}</td><td>${pill(q.status)}</td></tr>`).join("")}</tbody></table></div><p class="small muted" style="margin:12px 0 0">An agent compares carriers and contacts you. The policy appears here once it's issued.</p>`)}</div>` : ""}
  ${DB.policies.length ? "" : box("My policies", empty(DB.quotes.length ? "No active policies yet. Your policy appears here once COSPARK issues it." : "No policies on your account yet. Policies appear here once COSPARK issues them. Request a quote to get started."))}
  <div class="grid-2">${DB.policies.map(x => `<div class="box"><div class="row-between"><div style="display:flex;gap:14px;align-items:center"><span class="ic" style="width:44px;height:44px;border-radius:50%;background:var(--copper-soft);color:var(--copper);display:grid;place-items:center">${ic(x.icon)}</span><div><h3 style="font-weight:500">${esc(x.type)}</h3><span class="tag">${esc(x.no)}</span></div></div>${pill(x.status)}</div>
    <dl style="display:grid;grid-template-columns:1fr 1.2fr;margin:18px 0 0;font-size:13.5px">${[["Policy number", x.no], ["Carrier", x.carrier], ["Insured", x.insured], ["Covers", x.addr], ["Term", `${fd(x.start)} – ${fd(x.exp)}`], ["Annual premium", fmt$(x.premium)], ["Renewal", x.renewal], ["Next due", x.paid ? "Paid in full" : `${fmt$(x.amountDue)} · ${fd(x.due)}`]].map(([a, b]) => `<dt class="muted" style="padding:7px 0;border-bottom:1px solid var(--bg-2)">${a}</dt><dd style="margin:0;padding:7px 0;border-bottom:1px solid var(--bg-2)">${esc(b)}</dd>`).join("")}</dl>
    <div style="display:flex;gap:8px;margin-top:18px;flex-wrap:wrap">${x.paid || x.status !== "Active" ? "" : `<button class="btn btn-copper btn-sm" data-paypol="${x.id}">Make payment</button>`}<button class="btn btn-line btn-sm" data-docs="${x.id}">Documents</button>${x.status === "Cancelled" ? "" : `<a class="btn btn-line btn-sm" href="${DASH.fileClaim}?type=${encodeURIComponent(x.type.toLowerCase())}&policy=${encodeURIComponent(x.id)}">File a claim</a>`}</div></div>`).join("")}</div>`;
V.claims = () => `<div class="row-between" style="margin-bottom:16px;flex-wrap:wrap"><p class="muted" style="margin:0">Track progress, upload documents and message your adjuster.</p><a class="btn btn-copper btn-sm" href="${DASH.fileClaim}">Report a claim →</a></div>
  ${box("My claims", DB.claims.length ? `<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Claim</th><th>Type</th><th>Policy</th><th>Submitted</th><th>Incident</th><th>Status</th><th>Required action</th><th>Updated</th></tr></thead><tbody>${page("claims", DB.claims)[0].map(c => `<tr style="cursor:pointer" data-claim="${c.no}"><td><b>${c.no}</b></td><td>${esc(c.type)}</td><td>${esc(c.policy)}</td><td>${fd(c.sub)}</td><td>${fd(c.inc)}</td><td>${pill(c.status)}</td><td>${c.action !== "None" ? `<span class="copper">${esc(c.action)}</span>` : '<span class="muted">—</span>'}</td><td>${fd(c.upd)}</td></tr>`).join("")}</tbody></table></div>${page("claims", DB.claims)[1]}` : empty("You haven't filed any claims."))}`;
function claimDetail(no) {
  const c = DB.claims.find(x => x.no === no), steps = ["Submitted", "In review", "Adjuster assigned", "Decision", "Closed"];
  modal(`<span class="eyebrow">${esc(c.type)} claim</span><div class="row-between" style="margin:12px 0 22px;flex-wrap:wrap"><h2 class="h3s" style="font-size:34px">${c.no}</h2>${pill(c.status)}</div>
  <div class="grid-2" style="gap:20px"><div><p class="mono muted" style="margin:0 0 14px">Progress</p><ul class="timeline">${steps.map((s, i) => `<li class="${i < c.step ? "done" : i === c.step ? "now" : ""}">${s}${i === 0 ? `<small>${fd(c.sub)}</small>` : ""}</li>`).join("")}</ul></div>
  <div><p class="mono muted" style="margin:0 0 10px">Details</p><p class="small" style="margin:0 0 4px">Policy ${esc(c.policy)}</p><p class="small muted" style="margin:0 0 18px">Incident ${fd(c.inc)} · Last updated ${fd(c.upd)}</p>
  ${c.docs.length ? `<div class="notice warn"><div><b>Outstanding:</b> ${esc(c.docs.join(", "))}<label class="file-drop" style="margin-top:10px;display:block;padding:12px"><input type="file" id="cUp" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.heic">Upload document</label></div></div>` : `<div class="notice info">No outstanding documents.</div>`}</div></div>
  <p class="mono muted" style="margin:26px 0 10px">Messages</p><div id="cMsgs">${c.msgs.map(([w, t, dt]) => `<div class="card" style="padding:14px 16px;margin-bottom:8px;${w === "You" ? "background:var(--bg-2)" : ""}"><div class="row-between"><b class="small">${esc(w)}</b><span class="tag">${fd(dt)}</span></div><p class="small" style="margin:6px 0 0">${esc(t)}</p></div>`).join("") || empty("No messages yet.")}</div>
  <div class="field" style="margin-top:12px"><textarea id="cMsg" placeholder="Write a message to your adjuster"></textarea></div><div class="form-nav" style="margin-top:14px;padding-top:14px"><button class="btn btn-line" data-close>Close</button><button class="btn btn-dark" id="cSend">Send message</button></div>`);
  $("#cUp") && ($("#cUp").onchange = async () => { const fd2 = new FormData(); fd2.append("file", $("#cUp").files[0]);
    try { await act(url("claimUpload", no), fd2); } catch { return; } await refresh(); toast("Document uploaded"); claimDetail(no); });
  $("#cSend").onclick = async () => { const v = $("#cMsg").value.trim(); if (!v) return; const btn = $("#cSend"); busy(btn, true);
    try { await act(url("claimMessage", no), { body: v }); } catch { return busy(btn, false); } await refresh(); toast("Message sent"); claimDetail(no); };
}
V.payments = () => { const f = V._pf || "All"; const rows = DB.payments.filter(p => f === "All" || p.kind === f); const unpaid = DB.policies.filter(x => !x.paid && x.status === "Active");
  return `<div class="kpis" style="grid-template-columns:repeat(3,1fr)"><div class="kpi"><span class="mono muted">Next dues</span><div class="v">${P().fee ? fmt$(nextDuesAmount()) : "$0.00"}</div><div class="s">${P().fee ? nextDuesText() : "—"}</div></div><div class="kpi"><span class="mono muted">Insurance due</span><div class="v">${fmt$(unpaid.reduce((a, x) => a + x.amountDue, 0))}</div><div class="s">${unpaid.length} policies with balances</div></div><div class="kpi"><span class="mono muted">Paid this year</span><div class="v">${fmt$(DB.payments.filter(p => p.status === "Successful" && d(p.date).getFullYear() === today.getFullYear()).reduce((a, p) => a + p.amt, 0))}</div><div class="s">Dues, premiums & VT</div></div></div>
  <div style="display:flex;gap:10px;margin:14px 0;flex-wrap:wrap">${DB.member.due ? `<button class="btn btn-copper btn-sm" id="payDues">Pay membership dues</button>` : ""}<a href="#policies" class="btn btn-line btn-sm">Pay insurance</a>${paid() ? `<button class="btn btn-line btn-sm" id="buyVT">Buy Vacation Tokens</button>` : ""}<a href="#methods" class="btn btn-line btn-sm">Payment methods</a></div>
  ${box("Payment history & invoices", `<div class="filter-row">${["All", "Membership dues", "Insurance premium", "Vacation Tokens"].map(x => `<button class="chip ${x === f ? "on" : ""}" data-pf="${x}">${x}</button>`).join("")}</div>${rows.length ? paged("pay:" + f, rows, payTable) : empty("No payments in this category.")}`)}`; };
V.methods = () => `${DB.methods.some(m => m.expired) ? `<div class="notice warn" style="margin-bottom:14px"><span>${ic("alert")}</span>A saved card has expired. Update or remove it to avoid failed payments.</div>` : ""}
  ${box("Payment methods", DB.methods.map(m => `<div class="list-row"><span class="ic">${ic(m.bank ? "building" : "card")}</span><div><div class="t">${esc(m.bank || m.brand)} ending ${m.last4} ${m.def ? '<span class="pill green" style="margin-left:6px">Default · recurring</span>' : ""}</div><div class="s">${m.expired ? '<span style="color:var(--red)">Expired ' + m.exp + "</span>" : m.bank ? "ACH bank transfer" : "Expires " + m.exp}</div></div><div class="r" style="display:flex;gap:12px">${m.def || m.expired ? "" : `<button class="btn-x" data-def="${m.id}">Make default</button>`}${m.def ? "" : `<button class="btn-x" data-rm="${m.id}" style="color:var(--red)">Remove</button>`}</div></div>`).join("") || empty("No saved payment methods yet."), `<button class="btn btn-dark btn-sm" id="addPM">+ Add method</button>`)}
  <p class="small muted" style="margin-top:12px">COSPARK never stores full card numbers. Methods are tokenized by the payment provider.</p>`;
V.wallet = () => {
  if (!paid()) return lock("The token wallet");
  const f = V._wf || "All";
  let run = { DT: 0, IT: 0, VT: 0 }; const withBal = [...DB.ledger].reverse().map(l => (run[l.tok] += l.amt, { ...l, bal: run[l.tok] })).reverse();
  const brk = t => [["Earned", sumType(t, "Earned")], ["Purchased", sumType(t, "Purchased")], ["Used", sumType(t, "Used")], ["Transferred out", sumType(t, "Transferred")], ["Received", sumType(t, "Received")], ["Forfeited", sumType(t, "Forfeited")], ["Adjusted", adjusted(t)]];
// Admin adjustments (and returned transfers) can add or deduct, so the net is shown with its sign
const adjusted = t => { const n = DB.ledger.filter(l => l.tok === t && l.type === "Adjusted").reduce((a, l) => a + l.amt, 0); return (n > 0 ? "+" : n < 0 ? "−" : "") + (t === "VT" ? fmt$(Math.abs(n)) : Math.abs(n)); };
  const itRows = DB.ledger.filter(l => l.tok === "IT" && l.type === "Earned");
  return `<div class="tokens3">${tokCard("dt")}${tokCard("it")}${tokCard("vt")}</div>
  <div style="display:flex;gap:10px;margin:14px 0;flex-wrap:wrap"><button class="btn btn-copper btn-sm" id="buyVT">Buy Vacation Tokens</button><button class="btn btn-line btn-sm" id="xfer">${ic("transfer")} Transfer tokens</button><a class="btn btn-line btn-sm" href="#vacations">Use on a vacation</a></div>
  <div class="grid-3">${["DT", "IT", "VT"].map(t => box(t + " breakdown", brk(t).map(([a, b]) => `<div class="row-between" style="padding:7px 0;border-bottom:1px solid var(--bg-2);font-size:14px"><span class="muted">${a}</span><span>${typeof b === "string" ? b : t === "VT" ? fmt$(b) : b}</span></div>`).join("") + `<div class="row-between" style="padding:10px 0 0"><b>Available</b><b>${t === "VT" ? fmt$(bal(t)) : bal(t)}</b></div>`)).join("")}</div>
  <div class="dash-grid">
    ${box("Insurance Tokens by policy & year", (itRows.length ? `<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Policy</th><th>Policy year</th><th>Premium period</th><th>IT</th><th>Status</th></tr></thead><tbody>${page("it", itRows)[0].map(l => `<tr><td>${esc(l.policy || "—")}<div class="tag" style="font-size:9px">${esc(l.rel)}</div></td><td>${esc(l.py || "—")}</td><td>${fd(l.date)}</td><td>${l.amt}</td><td>${pill(l.state === "Forfeited" ? "Forfeited" : "Credited")}</td></tr>`).join("")}</tbody></table></div>${page("it", itRows)[1]}` : empty("Insurance Tokens appear after an eligible premium payment.")) + `<p class="small muted" style="margin:12px 0 0">If a policy is cancelled mid-year, that year's IT is forfeited. Completed years remain.</p>`)}
    ${box("Vacation Token lots", (DB.vt.map(v => `<dl style="display:grid;grid-template-columns:1fr 1fr;margin:0;font-size:14px">${[["Lot", v.lot], ["Purchased", fd(v.date)], ["Original value", fmt$(v.orig)], ["Accrued value*", fmt$(v.accrued)], ["Used", fmt$(v.used)], ["Available", fmt$(v.orig - v.used + v.accrued)]].map(([a, b]) => `<dt class="muted" style="padding:7px 0;border-bottom:1px solid var(--bg-2)">${a}</dt><dd style="margin:0;padding:7px 0;border-bottom:1px solid var(--bg-2)">${b}</dd>`).join("")}</dl>`).join("") || empty("No Vacation Tokens purchased yet.")) + `<p class="small muted" style="margin:12px 0 0">*Illustrative at 0.75%/month. Final VT accrual rate pending COSPARK confirmation.</p>`)}
  </div>
  <div style="margin-top:14px">${box("Transaction history", `<div class="filter-row">${["All", "DT", "IT", "VT"].map(x => `<button class="chip ${x === f ? "on" : ""}" data-wf="${x}">${x}</button>`).join("")}</div><div class="tbl-wrap"><table class="tbl"><thead><tr><th>Date</th><th>Token</th><th>Type</th><th>Description</th><th>Related</th><th>Amount</th><th>Balance</th></tr></thead><tbody>${page("tx:" + f, withBal.filter(l => f === "All" || l.tok === f))[0].map(l => `<tr><td>${fd(l.date)}</td><td><span class="tag">${l.tok}</span></td><td>${l.type}</td><td>${esc(l.desc)}</td><td class="tag" style="font-size:9.5px">${esc(l.rel)}</td><td style="color:${l.amt > 0 ? "#1F9E66" : "var(--ink)"}">${l.amt > 0 ? "+" : ""}${l.tok === "VT" ? fmt$(l.amt) : l.amt}</td><td>${l.tok === "VT" ? fmt$(l.bal) : l.bal}</td></tr>`).join("")}</tbody></table></div>${page("tx:" + f, withBal.filter(l => f === "All" || l.tok === f))[1]}`)}</div>`;
};
V.vacations = () => {
  if (!paid() && DB.plan !== "sub") return lock("Vacation planning");
  return `<div class="notice info" style="margin-bottom:14px"><span>${ic("palm")}</span><div>Available toward stays: <b>${bal("DT")} DT</b> · <b>${bal("IT")} IT</b> · <b>${fmt$(bal("VT") + vtAccrued())} VT</b>. Requests need ${DB.config.noticeDays}+ days' notice and are subject to availability.</div></div>
  ${box("My requests", DB.vacations.length ? `<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Request</th><th>Property</th><th>Dates</th><th>Guests</th><th>Tokens</th><th>Payment</th><th>Status</th></tr></thead><tbody>${page("vac", DB.vacations)[0].map(v => `<tr style="cursor:pointer" data-vac="${v.id}"><td><b>${v.id}</b></td><td>${esc(v.prop)}</td><td>${fd(v.in)} – ${fd(v.out)}</td><td>${v.guests}</td><td>${v.tokens}</td><td>${v.pay ? fmt$(v.pay) + " · " + v.payStatus : "—"}</td><td>${pill(v.status)}</td></tr>`).join("")}</tbody></table></div>${page("vac", DB.vacations)[1]}` : empty("No vacation requests yet."))}
  <h3 class="h3s" style="margin:30px 0 16px">Browse properties</h3>
  <div class="grid-3">${DB.properties.map(e => `<div class="prop-card"><div class="prop-img" style="background-image:url(${e.img.replace("w=1600", "w=700")})"></div><div class="in"><span class="tag">${esc(e.loc)}</span><h3 style="font-weight:500">${esc(e.n)}</h3><p class="small muted" style="margin:0">${esc(e.d)}</p><div class="amen">${e.amen.map(a => `<span>${esc(a)}</span>`).join("")}</div><button class="btn btn-dark btn-sm" style="align-self:flex-start;margin-top:auto" data-reqvac="${e.id}">Request a stay</button></div></div>`).join("")}</div>`;
};
function vacRequest(propId) {
  const min = new Date(today); min.setDate(min.getDate() + DB.config.noticeDays); const mn = iso(min);
  modal(`<span class="eyebrow">Vacation request</span><h2 class="h3s" style="margin:12px 0 22px;font-size:34px">Request a stay</h2><div class="form-grid">
  <div class="field"><label>Property</label><select id="vp">${DB.properties.map(e => `<option value="${e.id}" ${e.id == propId ? "selected" : ""}>${esc(e.n)}</option>`).join("")}</select></div>
  <div class="field half"><label>Check-in <span class="req">*</span></label><input type="date" id="vin" min="${mn}" required><span class="err-msg">Choose a date at least ${DB.config.noticeDays} days from today (${fd(mn)}).</span></div>
  <div class="field half"><label>Check-out <span class="req">*</span></label><input type="date" id="vout" min="${mn}" required><span class="err-msg">Check-out must be after check-in.</span></div>
  <div class="field half"><label>Guests <span class="req">*</span></label><select id="vg" required>${Array.from({ length: DB.config.maxGuests }, (_, k) => `<option>${k + 1}</option>`).join("")}</select></div>
  <div class="field"><label>Notes</label><textarea id="vn" placeholder="Accessibility, arrival time, special occasions…"></textarea></div></div>
  <div class="card" style="background:var(--bg-2);margin-top:18px" id="vEst"><span class="muted small">Choose dates to see the token credits that can apply.</span></div>
  <p class="small muted" style="margin-top:12px">Tokens are only deducted after the stay is confirmed. All bookings are subject to availability.</p>
  <div class="form-nav"><button class="btn btn-line" data-close>Cancel</button><button class="btn btn-copper" id="vGo">Submit request →</button></div>`);
  const est = () => { const a = $("#vin").value, b = $("#vout").value, p = DB.properties.find(x => x.id == $("#vp").value); if (!a || !b || d(b) <= d(a)) return; const n = Math.round((d(b) - d(a)) / 864e5), disc = DB.config.producerDiscount || 0, off = Math.round(n * p.rate * disc * 100) / 100, cost = n * p.rate - off, tok = Math.max(0, Math.min(bal("DT"), n * p.tpn)), others = DB.vacations.filter(v => v.status === "Pending" && v.tokens > 0).length;
    $("#vEst").innerHTML = `<div class="row-between"><span>${n} night${n > 1 ? "s" : ""} · est. rate</span><span>${fmt$(n * p.rate)}</span></div>${off ? `<div class="row-between" style="margin-top:6px"><span class="copper">Producer discount (${Math.round(disc * 100)}%)</span><span class="copper">− ${fmt$(off)}</span></div>` : ""}<div class="row-between" style="margin-top:6px"><span class="copper">Token credits applicable (DT)</span><span class="copper">− ${tok} tokens</span></div><div class="row-between" style="margin-top:6px"><b>Estimated balance due</b><b>${fmt$(Math.max(0, cost - tok))}</b></div><p class="small muted" style="margin-top:10px">The rate is per night for the whole property, so it doesn't change with the number of guests.</p>${others ? `<p class="small muted" style="margin-top:10px">Your ${others} pending stay request${others > 1 ? "s" : ""} also quote${others > 1 ? "" : "s"} DT. Tokens are deducted only from the stay confirmed first.</p>` : ""}`; };
  ["#vin", "#vout", "#vp"].forEach(s => $(s).onchange = est);
  $("#vGo").onclick = async () => { if (!validate($("#dBox"))) return; const a = $("#vin").value, b = $("#vout").value;
    if (d(a) < d(mn)) { $("#vin").closest(".field").classList.add("err"); return; } if (d(b) <= d(a)) { $("#vout").closest(".field").classList.add("err"); return; }
    const btn = $("#vGo"); busy(btn, true, "Submitting…");
    let r; try { r = await act(DASH.vacationStore, { property_id: +$("#vp").value, check_in: a, check_out: b, guests: +$("#vg").value, notes: $("#vn").value }); }
    catch (e) { busy(btn, false); const map = { check_in: "#vin", check_out: "#vout" }; Object.keys(e.errors || {}).forEach(k => map[k] && fieldErr($(map[k]), e.errors[k][0])); return; }
    const e = DB.properties.find(x => x.id == $("#vp").value); await refresh();
    modal(`<div class="confirm"><div class="confirm-mark">✓</div><h2 class="h3s">Request submitted.</h2><p class="muted">${esc(e.n)} · ${fd(a)} – ${fd(b)}. We'll confirm availability by email and text.</p><div class="ref-id">${r.reference}</div><button class="btn btn-dark" data-close>Done</button></div>`); };
}
function vacDetail(id) { const v = DB.vacations.find(x => x.id === id);
  modal(`<span class="eyebrow">${esc(v.loc)}</span><div class="row-between" style="margin:12px 0 20px;flex-wrap:wrap"><h2 class="h3s" style="font-size:32px">${esc(v.prop)}</h2>${pill(v.status)}</div><dl style="display:grid;grid-template-columns:1fr 1.3fr;margin:0">${[["Request ID", v.id], ["Dates", `${fd(v.in)} – ${fd(v.out)}`], ["Guests", v.guests], ["Requested", fd(v.req)], ["Tokens applied", v.tokens + " DT"], ["Remaining DT", bal("DT")], ["Payment amount", v.pay ? fmt$(v.pay) : "—"], ["Payment status", v.payStatus], ["Notes", v.notes || "—"], ["Instructions", v.instructions || (v.status === "Confirmed" ? "Instructions will be sent before arrival." : "Available once confirmed.")]].map(([a, b]) => `<dt class="muted" style="padding:9px 0;border-bottom:1px solid var(--line)">${a}</dt><dd style="margin:0;padding:9px 0;border-bottom:1px solid var(--line)">${esc(b)}</dd>`).join("")}</dl>
  <div class="form-nav">${v.cancellable ? `<button class="btn-x" style="color:var(--red)" id="vCancel">Cancel request</button>` : "<span></span>"}<button class="btn btn-dark" data-close>Close</button></div>`);
  $("#vCancel") && ($("#vCancel").onclick = async () => { try { await act(url("vacationCancel", id), {}); } catch { return; } toast("Request cancelled"); closeM(); refresh(); }); }
V.appointments = () => `<div class="row-between" style="margin-bottom:16px"><p class="muted" style="margin:0">Phone or in-person, Monday–Thursday 9 AM–4 PM. After-hours on request.</p><button class="btn btn-copper btn-sm" data-appt>Schedule appointment</button></div>
  ${box("My appointments", paged("appts", DB.appts, rows => rows.map(a => `<div class="list-row"><span class="ic">${ic(a.type === "Phone call" ? "phone" : "users")}</span><div><div class="t">${esc(a.reason)}</div><div class="s">${fd(a.date)} · ${a.time} · ${a.type} · ${esc(a.with)}</div></div><div class="r">${pill(a.status)}</div></div>`).join("")) || empty("No appointments yet."))}`;
V.qr = () => {
  if (DB.plan === "free") return lock("Your member QR code");
  const credits = [["Monthly Dining Credit", "COSPARK Eateries", "$15 / month"], ["Partner discounts", "COSPARK Business Partners", "Varies"], ["Vacation credits", "Keys Cove, Fish Camp & more", `${bal("DT")} DT`]];
  return `<div class="dash-grid" style="margin-top:0;grid-template-columns:.8fr 1.2fr">
  <div class="box" style="text-align:center"><span class="mono muted">My member QR</span>${DB.qr ? `<div style="max-width:260px;margin:18px auto;border:1px solid var(--line);padding:16px;border-radius:4px" class="qr-svg">${DB.qr.svg}</div>${pill(DB.qr.status)}<p class="small muted" style="margin:12px 0 18px">${DB.member.id} · ${P().n}<br>Only valid for ${esc(DB.user.first)} ${esc(DB.user.last)}.</p><div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap"><button class="btn btn-line btn-sm" id="qrSend">Send to my phone</button></div>` : `<p class="muted" style="margin:24px 0">Your QR code will appear here once COSPARK issues it for your membership.</p>`}</div>
  <div style="display:grid;gap:14px;align-content:start">
    ${box("Eligible credits", credits.map(([a, b, c]) => `<div class="list-row"><span class="ic">${ic("gift")}</span><div><div class="t">${a}</div><div class="s">${b}</div></div><div class="r">${c}</div></div>`).join(""))}
    ${box("Redemption history", paged("qr", DB.redemptions, rows => rows.map(r => `<div class="list-row"><span class="ic">${ic("qr")}</span><div><div class="t">${esc(r.partner)}</div><div class="s">${fd(r.date)} · ${r.ref}</div></div><div class="r">−${r.amt} DT</div></div>`).join("")) || empty("No redemptions yet."))}
  </div></div>`;
};
V.benefits = () => DB.plan === "free" ? lock("Partner benefits") : `<div class="grid-3">${DB.partners.map(e => `<div class="prop-card"><div class="prop-img" style="height:140px;background-image:url(${e.img.replace("w=1600", "w=600")})"></div><div class="in"><span class="tag">${esc(e.c)}</span><h3 style="font-weight:500">${esc(e.n)}</h3><p class="small muted" style="margin:0">${esc(e.d)}</p><a href="#qr" class="link-arrow" style="margin-top:10px;align-self:flex-start">Use my QR</a></div></div>`).join("")}</div>`;
V.notifications = () => { const f = V._nf || "All", cats = ["All", ...new Set(DB.notes.map(n => n.cat))];
  return box("Notifications", `<div class="row-between" style="flex-wrap:wrap;margin-bottom:6px"><div class="filter-row" style="margin:0">${cats.map(c => `<button class="chip ${c === f ? "on" : ""}" data-nf="${c}">${c}</button>`).join("")}</div><button class="btn-x" id="readAll">Mark all as read</button></div>${paged("notes:" + f, DB.notes.filter(n => f === "All" || n.cat === f), rows => rows.map(noteRow).join("")) || '<p class="empty">No notifications in this category.</p>'}`); };
V.subaccounts = () => { const active = DB.subs.filter(s => s.status !== "Deactivated").length;
  return DB.plan !== "premium" ? `<div class="box" style="text-align:center;padding:60px 30px"><h3 class="h3s">Sub-accounts are a Premium feature.</h3><p class="muted">Add family members, employees or group members for $8.88/month each.</p><a href="#membership" class="btn btn-copper">Upgrade to Premium →</a></div>` :
  `<div class="row-between" style="margin-bottom:16px;flex-wrap:wrap"><p class="muted" style="margin:0">${active} active sub-accounts · ${fmt$(active * DB.config.subFee)}/month added to your dues.</p><button class="btn btn-dark btn-sm" id="addSub">+ Add sub-account</button></div>
  ${box("Sub-accounts", DB.subs.length ? `<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Name</th><th>Relationship</th><th>Since</th><th>Permissions</th><th>Billing</th><th>Status</th><th></th></tr></thead><tbody>${DB.subs.map(s => `<tr><td><b>${esc(s.name)}</b><div class="tag" style="font-size:9.5px;text-transform:none;letter-spacing:0">${esc(s.email)}</div></td><td>${esc(s.rel)}</td><td>${fd(s.since)}</td><td class="small">${esc(s.perms.join(", "))}</td><td>${fmt$(DB.config.subFee)}/mo</td><td>${pill(s.status)}</td><td>${s.status !== "Deactivated" ? `<button class="btn-x" data-deact="${s.id}">Deactivate</button>` : `<button class="btn-x" data-react="${s.id}">Reactivate</button>`}</td></tr>`).join("")}</tbody></table></div>` : empty("No sub-accounts yet."))}`; };
V.profile = () => { const u = DB.user; const f = (id, l, t = "text", h = 1) => id === "email"
    // The sign-in email can't be changed here (contact COSPARK); it isn't sent with the form
    ? `<div class="field half"><label for="u_email">${l}</label><input id="u_email" type="email" value="${esc(u.email)}" disabled><span class="hint">Contact COSPARK to change your sign-in email.</span></div>`
    : `<div class="field ${h ? "half" : ""}"><label for="u_${id}">${l}</label><input id="u_${id}" name="${id}" data-u="${id}" type="${t}" value="${esc(u[id])}" ${["first", "last"].includes(id) ? "required" : ""}><span class="err-msg">Required.</span></div>`;
  return `<div class="dash-grid" style="margin-top:0">${box("Profile & contact", `<div style="display:flex;gap:16px;align-items:center;margin-bottom:22px"><span class="avatar" style="width:64px;height:64px;font-size:22px;overflow:hidden">${avatar()}</span><label class="btn btn-line btn-sm" style="cursor:pointer">Change photo<input type="file" accept="image/*" hidden id="u_avatar"></label></div><div class="form-grid" id="profF">${f("first", "First name")}${f("last", "Last name")}${f("email", "Email", "email")}${f("phone", "Phone", "tel")}${f("street", "Street address", "text", 0)}${f("street2", "Address line 2", "text", 0)}${f("city", "City")}${f("st", "State")}${f("zip", "ZIP")}</div><label class="checkline" style="margin-top:18px"><input type="checkbox" id="u_sms" ${u.sms ? "checked" : ""}> Receive text messages from COSPARK and related companies</label><div class="form-nav"><span></span><button class="btn btn-dark" id="saveProf">Save changes</button></div>`)}
  ${box("Password", `<div class="form-grid" id="pwF"><div class="field"><label>Current password</label><input type="password" name="current_password" id="pw0" required autocomplete="current-password"><span class="err-msg">Required.</span></div><div class="field"><label>New password</label><input type="password" name="password" id="pw1" required minlength="8" autocomplete="new-password"><span class="err-msg">Required.</span></div><div class="field"><label>Confirm new password</label><input type="password" name="password_confirmation" id="pw2" required autocomplete="new-password"><span class="err-msg">Required.</span></div></div><div class="form-nav"><span></span><button class="btn btn-line" id="savePw">Update password</button></div>`)}</div>`; };

/* ---------- Navigation ---------- */
const NAVD = [
  ["Account"], ["overview", "Overview", "grid"], ["membership", "Membership", "star"], ["policies", "My Policies", "shield"], ["claims", "Claims", "doc"],
  ["Money & tokens"], ["payments", "Payments", "card"], ["methods", "Payment Methods", "wallet"], ["wallet", "Token Wallet", "coin"],
  ["Benefits"], ["vacations", "Vacations", "palm"], ["qr", "My QR Code", "qr"], ["benefits", "Partner Benefits", "gift"], ["appointments", "Appointments", "cal"],
  ["Settings"], ["notifications", "Notifications", "bell"], ["subaccounts", "Sub-accounts", "users"], ["profile", "Profile", "settings"], ["resources", "Resources", "book"],
];
/* Sub-accounts see the base sections plus whatever the main member granted */
const SUB_BASE = ["overview", "notifications", "profile", "resources"];
const SUB_PERMS = { "View policies": "policies", "Vacations": "vacations", "QR code": "qr", "Partner benefits": "benefits", "Appointments": "appointments" };
const allowed = k => DB.plan !== "sub" || !NAVD.some(n => n[0] === k && n[1]) || SUB_BASE.includes(k) || (DB.member.perms || []).some(p => SUB_PERMS[p] === k);
function nav(cur) {
  // Before the data arrives (page reload) the menu is drawn at once from the URL, without the plan filters
  const unread = DB ? DB.notes.filter(n => !n.read).length : 0;
  $("#dNav").innerHTML = NAVD.filter(([k, l]) => !l || !DB || allowed(k)).filter(([k]) => k !== "subaccounts" || DB?.plan === "premium").map(([k, l, i]) => l ? `<a href="${k === "resources" ? DASH.resources : "#" + k}" class="${k === cur ? "on" : ""}">${ic(i)}${l}${k === "notifications" && unread ? `<span class="cnt">${unread}</span>` : ""}</a>` : `<h6>${k}</h6>`).join("");
  $("#dBell").innerHTML = ic("bell") + (unread ? '<span class="dot"></span>' : "");
  // Keep the current section visible in a long sidebar (scrolls the sidebar only, never the page)
  const on = $("#dNav .on"), side = $("#dSide");
  if (on?.getBoundingClientRect && side?.getBoundingClientRect) { const r = on.getBoundingClientRect(), sr = side.getBoundingClientRect(); if (r.top < sr.top || r.bottom > sr.bottom) side.scrollTop += r.top - sr.top - sr.height / 3; }
}
function render() {
  if (!DB) return;
  let v = location.hash.slice(1) || "overview"; if (!V[v] || v.startsWith("_")) v = "overview";
  if (!allowed(v)) v = "overview";
  nav(v);
  const t = NAVD.find(n => n[0] === v); $("#dTitle").textContent = v === "overview" ? `Good ${today.getHours() < 12 ? "morning" : today.getHours() < 18 ? "afternoon" : "evening"}, ${DB.user.first}` : t[1];
  $("#dCrumb").textContent = v === "overview" ? `${P().n} member · ${DB.member.id}` : "Member dashboard";
  $("#dTier").textContent = P().n + (DB.plan === "sub" ? ` · linked to ${DB.member.parentName}` : " member");
  $("#dName").textContent = `${DB.user.first} ${DB.user.last}`; $("#dAv").innerHTML = avatar();
  $("#view").innerHTML = `<div class="view on">${V[v]()}</div>`;
}

/* ---------- Events ---------- */
document.addEventListener("DOMContentLoaded", async () => {
  $("#dLogo").innerHTML = LOGO + '<span class="logo-t"><b>COSPARK</b><span>INSURANCE GROUP</span></span>';
  $("#dOut").innerHTML = ic("out"); $("#dMenu").innerHTML = ic("menu");
  addEventListener("hashchange", () => { $("#dSide").classList.remove("open"); document.querySelectorAll(".modal.open").forEach(closeModal); render(); scrollTo(0, 0); });
  nav(location.hash.slice(1) || "overview");
  $("#view").innerHTML = `<p class="empty">Loading your dashboard…</p>`;
  try { await load(); render(); } catch { $("#view").innerHTML = `<p class="empty">We couldn't load your dashboard. <a href="" class="copper">Try again</a></p>`; }
  // Live notifications: always refresh the bell and menu badge; redraw only views that list notifications, so open forms keep their input
  liveNotifications("member", n => {
    if (!DB) return;
    DB.notes.unshift(n);
    const v = location.hash.slice(1) || "overview";
    ["overview", "notifications"].includes(v) ? render() : nav(v);
    toast(n.t);
  });

  document.addEventListener("change", async e => {
    if (e.target.id !== "u_avatar" || !e.target.files[0]) return;
    const f = new FormData(); f.append("avatar", e.target.files[0]);
    try { await act(DASH.avatar, f); } catch { return; } await refresh(); toast("Profile photo updated");
  });

  document.addEventListener("click", async e => {
    const t = e.target.closest("[data-paypol],[data-docs],[data-claim],[data-pf],[data-wf],[data-nf],[data-read],[data-def],[data-rm],[data-reqvac],[data-vac],[data-plan],[data-deact],[data-react],[data-retry],#payDues,#buyVT,#xfer,#addPM,#qrSend,#readAll,#addSub,#saveProf,#savePw,#chgFreq,#cancelM");
    if (!t || !DB) return; const ds = t.dataset;
    if (ds.paypol) { const p = DB.policies.find(x => x.id == ds.paypol), it = Math.round(p.premium * P().it);
      payFlow({ title: `${p.type} insurance payment`, kind: "Insurance premium", desc: `${p.type} · ${p.no} · annual`, total: p.amountDue, lines: [["Policy", p.no], ["Carrier", p.carrier], ["Period", `${fd(p.start)} – ${fd(p.exp)}`], ["Due date", fd(p.due)]], tokens: it ? `+${it} Insurance Tokens will be credited (${Math.round(P().it * 100)}% of eligible annual premium).` : "",
        endpoint: DASH.payPremium, data: { policy_id: p.id } }); }
    if (ds.docs) { const p = DB.policies.find(x => x.id == ds.docs); modal(`<span class="eyebrow">${esc(p.no)}</span><h2 class="h3s" style="margin:12px 0 20px;font-size:32px">${esc(p.type)} documents</h2>${p.docs.map(doc => `<div class="list-row"><span class="ic">${ic("doc")}</span><div><div class="t">${esc(doc.name)}</div><div class="s">Updated ${fd(doc.date)}</div></div><div class="r" style="display:flex;gap:12px"><a class="btn-x" href="${doc.url}" target="_blank" rel="noopener">View</a><a class="btn-x" href="${doc.url}?download=1">Download</a></div></div>`).join("") || empty("No documents have been added to this policy yet. Ask your agent if you need a copy.")}`); }
    if (ds.claim) claimDetail(ds.claim);
    if (ds.pf) { V._pf = ds.pf; render(); }
    if (ds.wf) { V._wf = ds.wf; render(); }
    if (ds.nf) { V._nf = ds.nf; render(); }
    if (ds.read) { const n = DB.notes.find(x => x.id === ds.read); if (n && !n.read) { n.read = true; send(url("noteRead", n.id), {}).catch(() => {}); } }
    if (t.id === "readAll") { try { await act(DASH.notesReadAll, {}); } catch { return; } toast("All notifications marked as read"); refresh(); }
    if (ds.def) { try { await act(url("methodDefault", ds.def), {}); } catch { return; } toast("Default payment method updated"); refresh(); }
    if (ds.rm) { try { await act(url("methodDestroy", ds.rm), {}, "DELETE"); } catch { return; } toast("Payment method removed"); refresh(); }
    if (t.id === "addPM") addMethod();
    if (ds.reqvac) vacRequest(ds.reqvac);
    if (ds.vac) vacDetail(ds.vac);
    if (ds.plan) { const k = ds.plan; if (PLAN[k].fee > P().fee) payFlow({ title: `Upgrade to ${PLAN[k].n}`, kind: "Membership dues", desc: `${PLAN[k].n} membership · first month`, total: PLAN[k].fee, lines: [["New plan", PLAN[k].n], ["Billing", "Monthly, recurring"], ["Starts", fd(iso(today))]], tokens: `+${PLAN[k].dt} Dues Tokens will be credited after payment.`, endpoint: DASH.upgrade, data: { plan: k } });
      else { modal(`<h2 class="h3s" style="font-size:32px">Switch to ${PLAN[k].n}?</h2><p class="muted">Your plan changes at the end of the current billing period (${fd(DB.member.next)}). ${k === "free" ? "You'll stop earning Dues and Insurance Tokens; tokens already earned stay in your wallet under program rules." : ""}</p><div class="form-nav"><button class="btn btn-line" data-close>Keep current plan</button><button class="btn btn-dark" id="swGo">Confirm switch</button></div>`, true);
        $("#swGo").onclick = async () => { try { await act(DASH.switchPlan, { plan: k }); } catch { return; } closeM(); toast(`Plan change to ${PLAN[k].n} scheduled`); refresh(); }; } }
    if (t.id === "payDues") { const p = P(), start = DB.member.next && d(DB.member.next) > today ? DB.member.next : iso(today), months = { Monthly: 1, Quarterly: 3, "Bi-annual": 6, Annual: 12 }[DB.member.freq] || 1, end = new Date(d(start)); end.setMonth(end.getMonth() + months);
      payFlow({ title: "Pay membership dues", kind: "Membership dues", desc: `${p.n} membership · ${DB.member.freq}`, total: DB.member.periodAmount, lines: [["Plan", p.n], ["Period", `${fd(start)} – ${fd(iso(end))}`], ...(DB.plan === "premium" && DB.subs.length ? [["Sub-accounts", "Billed separately"]] : [])], tokens: `+${p.dt * months} Dues Tokens will be credited after payment.`, endpoint: DASH.payDues }); }
    if (ds.retry) { const pay = DB.payments.find(x => x.ref === ds.retry); payFlow({ title: "Retry payment", kind: pay.kind, desc: pay.desc, total: pay.amt, lines: [["Original payment", pay.ref], ["Description", pay.desc]], endpoint: url("retry", pay.ref) }); }
    if (t.id === "buyVT") { const minVT = DB.config.vtMin; modal(`<span class="eyebrow">Vacation Tokens</span><h2 class="h3s" style="margin:12px 0 10px;font-size:34px">Buy Vacation Tokens</h2><p class="muted small">1 VT = $1 face value. VT is stored separately and used toward eligible vacation stays.</p><div class="chips" id="vtAmt" style="margin:18px 0">${[100, 250, 500, 1000, 2500].map((a, i) => `<button class="chip ${i === 2 ? "on" : ""}" data-a="${a}">${a} VT</button>`).join("")}</div><div class="field"><label>Or enter an amount</label><input type="number" id="vtCustom" min="${minVT}" step="1" placeholder="e.g. 750"><span class="err-msg">Enter at least ${minVT} VT.</span></div><div class="form-nav"><button class="btn btn-line" data-close>Cancel</button><button class="btn btn-copper" id="vtNext">Continue →</button></div>`, true);
      let a = 500; $("#vtAmt").onclick = e2 => { const b = e2.target.closest("[data-a]"); if (!b) return; a = +b.dataset.a; $("#vtCustom").value = ""; $("#vtAmt").querySelectorAll(".chip").forEach(x => x.classList.toggle("on", x === b)); };
      $("#vtNext").onclick = () => { const c = Math.floor(+$("#vtCustom").value); if ($("#vtCustom").value && c < minVT) return fieldErr($("#vtCustom")); if (c) a = c; payFlow({ title: `Buy ${a} Vacation Tokens`, kind: "Vacation Tokens", desc: `${a} VT purchase`, total: a, lines: [["Tokens", a + " VT"], ["Face value", fmt$(a)]], tokens: `+${a} VT will be added to your wallet.`, endpoint: DASH.payVT, data: { quantity: a } }); }; }
    if (t.id === "xfer") { modal(`<span class="eyebrow">Token transfer</span><h2 class="h3s" style="margin:12px 0 18px;font-size:34px">Transfer tokens</h2><div class="form-grid"><div class="field"><label>Recipient's registered email <span class="req">*</span></label><input type="email" id="xE" required><span class="err-msg">Enter a registered member's email.</span></div><div class="field half"><label>Token type</label><select id="xT"><option value="DT">DT · ${spendDT()} available</option><option value="IT">IT · ${bal("IT")} available</option></select></div><div class="field half"><label>Quantity <span class="req">*</span></label><input type="number" id="xQ" min="1" required><span class="err-msg">Enter a quantity within your balance.</span></div><div class="field"><label>Note</label><input id="xN"></div><div class="field"><label class="checkline" style="font-family:var(--sans);text-transform:none;letter-spacing:0;font-size:13px"><input type="checkbox" required> <span>I understand this is a member-to-member arrangement and agree to hold COSPARK Insurance Group harmless and indemnify it against any related claims.</span></label><span class="err-msg">Accept to continue.</span></div></div><p class="small muted">Transfers are reviewed by the COSPARK team (${esc(DB.config.transferEmail)}) before completion.</p><div class="form-nav"><button class="btn btn-line" data-close>Cancel</button><button class="btn btn-dark" id="xGo">Request transfer</button></div>`, true);
      $("#xGo").onclick = async () => { if (!validate($("#dBox"))) return; const q = +$("#xQ").value, tk = $("#xT").value, em = $("#xE").value; if (q > bal(tk)) { $("#xQ").closest(".field").classList.add("err"); return; }
        const btn = $("#xGo"); busy(btn, true);
        let r; try { r = await act(DASH.transfer, { email: em, token_type: tk, quantity: q, note: $("#xN").value, indemnity: true }); }
        catch (err) { busy(btn, false); const map = { email: "#xE", quantity: "#xQ" }; Object.keys(err.errors || {}).forEach(k => map[k] && fieldErr($(map[k]), err.errors[k][0])); return; }
        await refresh();
        modal(`<div class="confirm"><div class="confirm-mark">✓</div><h2 class="h3s">Transfer requested.</h2><p class="muted">${q} ${tk} to ${esc(em)}. Status: <span class="pill amber">Pending review</span></p><p class="small muted">The recipient receives the tokens once the COSPARK team approves the transfer.</p><div class="ref-id">${r.reference}</div><button class="btn btn-dark" data-close>Done</button></div>`); }; }
    if (t.id === "qrSend") { let r; try { r = await act(DASH.qrSend, {}); } catch { return; } toast(r.message); }
    if (t.id === "addSub") { modal(`<span class="eyebrow">Premium sub-account</span><h2 class="h3s" style="margin:12px 0 18px;font-size:34px">Add a sub-account</h2><div class="form-grid"><div class="field half"><label>Full name <span class="req">*</span></label><input id="sN" required><span class="err-msg">Required.</span></div><div class="field half"><label>Relationship <span class="req">*</span></label><select id="sR" required><option value="">Select…</option><option>Spouse / partner</option><option>Child (18+)</option><option>Family member</option><option>Employee</option><option>Group member</option></select><span class="err-msg">Required.</span></div><div class="field"><label>Email for their login <span class="req">*</span></label><input type="email" id="sE" required><span class="err-msg">Enter a valid email.</span></div><div class="field"><span class="lbl">Permissions</span>${DB.config.subPermissions.map((p, i) => `<label class="checkline"><input type="checkbox" value="${p}" ${i < 2 ? "checked" : ""} class="sP"> ${p}</label>`).join("")}</div></div><div class="notice" style="margin-top:16px">Adds <b>&nbsp;${fmt$(DB.config.subFee)}/month&nbsp;</b> to your membership billing. They'll receive an email to set a password.</div><div class="form-nav"><button class="btn btn-line" data-close>Cancel</button><button class="btn btn-copper" id="sGo">Add & invite →</button></div>`, true);
      $("#sGo").onclick = async () => { if (!validate($("#dBox"))) return; const btn = $("#sGo"); busy(btn, true);
        try { await act(DASH.subStore, { name: $("#sN").value, relationship: $("#sR").value, email: $("#sE").value, permissions: [...document.querySelectorAll(".sP:checked")].map(x => x.value) }); }
        catch (err) { busy(btn, false); err.errors?.email && fieldErr($("#sE"), err.errors.email[0]); return; }
        closeM(); toast("Invitation sent"); refresh(); }; }
    if (ds.deact || ds.react) { try { await act(url("subStatus", ds.deact || ds.react), { active: !!ds.react }); } catch { return; } toast(ds.react ? "Sub-account reactivated" : "Sub-account deactivated"); refresh(); }
    if (t.id === "saveProf") { if (!validate($("#profF"))) return; const data = { sms: $("#u_sms").checked }; document.querySelectorAll("[data-u]").forEach(i => data[i.dataset.u] = i.value);
      busy(t, true, "Saving…"); try { await act(DASH.profile, data); } catch (err) { busy(t, false); showServerErrors($("#profF"), err.errors || {}); return; } await refresh(); toast("Profile saved"); }
    if (t.id === "savePw") { const box2 = $("#pwF"); if (!validate(box2)) return; if ($("#pw1").value !== $("#pw2").value) return fieldErr($("#pw2"), "Passwords don't match.");
      busy(t, true, "Updating…"); try { await act(DASH.password, { current_password: $("#pw0").value, password: $("#pw1").value, password_confirmation: $("#pw2").value }); } catch (err) { busy(t, false); showServerErrors(box2, err.errors || {}); return; } toast("Password updated"); render(); }
    if (t.id === "chgFreq") { const F2 = { monthly: "Monthly", quarterly: "Quarterly", biannual: "Bi-annual", annual: "Annual" }; modal(`<h2 class="h3s" style="font-size:32px;margin-bottom:18px">Billing frequency</h2><div class="chips" id="fq">${Object.entries(F2).filter(([k]) => (DB.config.freqs[DB.plan] || []).includes(k)).map(([k, f]) => `<button class="chip ${k === DB.member.freqKey ? "on" : ""}" data-f="${k}">${f}</button>`).join("")}</div><p class="small muted" style="margin-top:16px">The new frequency starts at your next billing date. Dues Tokens are credited for each month covered by a successful payment.</p><div class="form-nav"><button class="btn btn-line" data-close>Cancel</button><button class="btn btn-dark" id="fqGo">Save</button></div>`, true);
      let f = DB.member.freqKey; $("#fq").onclick = e2 => { const b = e2.target.closest("[data-f]"); if (!b) return; f = b.dataset.f; $("#fq").querySelectorAll(".chip").forEach(x => x.classList.toggle("on", x === b)); };
      $("#fqGo").onclick = async () => { try { await act(DASH.frequency, { frequency: f }); } catch { return; } closeM(); toast("Billing frequency updated"); refresh(); }; }
    if (t.id === "cancelM") { modal(`<h2 class="h3s" style="font-size:32px">Cancel membership?</h2><p class="muted">Your ${P().n} membership stays active until ${fd(DB.member.next)}. After that you'll move to the Free plan and stop earning Dues and Insurance Tokens. Earned tokens remain subject to program rules.</p><div class="form-nav"><button class="btn btn-dark" data-close>Keep membership</button><button class="btn-x" style="color:var(--red)" id="cmGo">Cancel membership</button></div>`, true);
      $("#cmGo").onclick = async () => { try { await act(DASH.cancel, {}); } catch { return; } closeM(); toast("Membership will cancel at period end"); refresh(); }; }
  });
});
})();
