/* COSPARK — Producer Portal
   Data comes from PORTAL.data (ProducerDashboardPresenter); actions post to the server and reload.
   PORTAL (endpoint URLs) is provided by resources/views/dashboard/producer.blade.php. */
(() => {
const $ = s => document.querySelector(s);
const today = new Date(), d = s => new Date(s + "T12:00");
const iso = x => `${x.getFullYear()}-${String(x.getMonth() + 1).padStart(2, "0")}-${String(x.getDate()).padStart(2, "0")}`;
const fd = s => s ? d(s).toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" }) : "—";
const esc = s => String(s ?? "").replace(/[&<>"']/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
const pill = s => { const c = /active|approved|paid|credited|won|verified|eligible|confirmed|met|available/i.test(s) ? "green" : /expired|lost|rejected|declined/i.test(s) ? "red" : /pending|review|progress|new|requested|locked|contacted|quoted|needed|expiring|not credited|not met/i.test(s) ? "amber" : "grey"; return `<span class="pill ${c}">${esc(s)}</span>`; };
const box = (t, b, r = "") => `<section class="box"><div class="box-h"><h3>${t}</h3>${r}</div>${b}</section>`;
const dl = rows => `<dl style="display:grid;grid-template-columns:1fr 1.3fr;margin:0;font-size:14px">${rows.map(([a, b]) => `<dt class="muted" style="padding:9px 0;border-bottom:1px solid var(--bg-2)">${a}</dt><dd style="margin:0;padding:9px 0;border-bottom:1px solid var(--bg-2)">${b}</dd>`).join("")}</dl>`;
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

const url = (name, p) => PORTAL[name].replace(/__ID__|__REF__/, encodeURIComponent(p));
function modal(html, sm) { const b = $("#dBox"); b.className = "modal-box" + (sm ? " sm" : ""); b.innerHTML = `<button class="modal-x" data-close aria-label="Close">×</button>` + html; openModal("dModal"); }
const closeM = () => closeModal($("#dModal"));
const fieldErr = (el, msg) => { const f = el.closest(".field"); f.classList.add("err"); const m = f.querySelector(".err-msg"); if (m && msg) m.textContent = msg; };

/* ---------- Live data ---------- */
let P = null;
async function load() {
  const res = await fetch(PORTAL.data, { headers: { Accept: "application/json" }, credentials: "same-origin" });
  if (res.status === 401 || res.status === 419) { location.reload(); return; }
  P = await res.json();
}
const refresh = async () => { await load(); render(); };
const act = async (u, data, method) => { try { return await send(u, data, method); } catch (e) { toast(e.message); throw e; } };

const B = () => P.bonus;
const rate = () => Math.round(P.rate * 100);
const months = () => B().months;
const eligDate = () => B().eligibleOn;
const totPrem = () => P.comm.reduce((a, c) => a + c.prem, 0), totComm = () => P.comm.reduce((a, c) => a + c.amt, 0);
const BONUS_LABEL = { not_credited: "Not credited", locked: "Locked", available: "Available", redemption_requested: "Redemption requested", redeemed: "Redeemed" };
// SOW: the bonus is credited only once the producer is approved, licensed and keeps the $8.88 membership
const bonusMissing = () => { const e = B().eligibility, m = [!e.approved && "approval", !e.licensed && "an active license", !e.membership && "the $8.88 membership"].filter(Boolean); return m.length > 1 ? m.slice(0, -1).join(", ") + " and " + m.at(-1) : m[0] || "all requirements"; };
const bonusPending = () => `${B().tokens.toLocaleString()} tokens once you have ${bonusMissing()}`;
const bonusNote = () => ({ not_credited: bonusPending(), locked: `until ${fd(eligDate())}`, available: "Ready to redeem", redemption_requested: `Requested ${fd(B().requested)}`, redeemed: "Redeemed" })[B().status];

const V = {};
const reviewOnly = ["overview", "profile", "licensing", "documents", "notifications"];
V.overview = () => P.status === "review" ? `<div class="notice warn"><span>${ic("alert")}</span><div><b>Your application is under review.</b>${P.review ? ` Reference ${P.review.ref} · submitted ${fd(P.review.submitted)}.` : ""} Benefits that require an active license unlock once approval and licensing are complete.</div></div>
  <div class="dash-grid">${box("Application status", `<ul class="timeline"><li class="done">Application submitted${P.review ? `<small>${fd(P.review.submitted)}</small>` : ""}</li><li class="done">Documents received</li><li class="now">Licensing verification${P.docs.some(x => x.s === "Needed") ? `<small>Additional document requested: ${esc(P.docs.find(x => x.s === "Needed").n)}</small>` : ""}</li><li>Approval</li><li>Producer dashboard unlocked</li></ul>`)}${box("Next step", P.docs.some(x => x.s === "Needed") ? `<p class="muted">Upload the requested documents to continue.</p><a class="btn btn-copper" href="#documents">Upload document →</a>` : `<p class="muted">No action needed. We'll email you when your review is complete.</p>`)}</div>` :
  `${B().eligibility.membership ? "" : `<div class="notice warn" style="margin-bottom:16px"><span>${ic("alert")}</span><div style="flex:1"><b>Activate your $8.88 Associate membership.</b> It's required for producer benefits and the 2,000-token bonus.</div><a class="btn btn-copper btn-sm" href="${PORTAL.membership}">Activate membership →</a></div>`}<div class="kpis">
    <div class="kpi"><span class="mono muted">Producer status</span><div class="v" style="font-size:30px">Active</div><div class="s">${pill(B().eligibility.licensed ? "Licensed" : "License pending")} since ${fd(P.me.joined)}</div></div>
    <div class="kpi"><span class="mono muted">Token bonus</span><div class="v">${B().status === "not_credited" ? "0" : B().tokens.toLocaleString()}</div><div class="s">${pill(BONUS_LABEL[B().status])} ${bonusNote()}</div></div>
    <div class="kpi"><span class="mono muted">Commission YTD</span><div class="v">${fmt$(P.comm.filter(c => d(c.date).getFullYear() === today.getFullYear()).reduce((a, c) => a + c.amt, 0)).replace(/\.\d\d$/, "")}</div><div class="s">${rate()}% rate · ${P.comm.length} policies</div></div>
    <div class="kpi"><span class="mono muted">Premium written</span><div class="v">${fmt$(totPrem()).replace(/\.\d\d$/, "")}</div><div class="s">Since ${fd(P.me.joined)}</div></div>
  </div>
  <div class="dash-grid">
    <div style="display:grid;gap:14px;align-content:start">
      ${box("12-month bonus qualification", `<div class="row-between"><span class="mono muted">Months completed</span><span class="mono">${months()} / 12</span></div><div class="progress" style="margin:10px 0 16px"><i style="width:${Math.min(100, months() / 12 * 100)}%"></i></div><p class="small muted" style="margin:0">Keep writing insurance with COSPARK and maintain your $8.88 membership.${eligDate() ? ` Eligible to redeem on <b style="color:var(--ink)">${fd(eligDate())}</b>.` : ""}</p>`, `<a href="#qualification" class="link-arrow">Details</a>`)}
      ${box("Production by month", chart(), `<a href="#performance" class="link-arrow">Performance</a>`)}
      ${box("Recent commissions", P.comm.length ? commTable(P.comm.slice(0, 5)) : empty("Commissions appear here once your first policy is written."), `<a href="#commission" class="link-arrow">All</a>`)}
    </div>
    <div style="display:grid;gap:14px;align-content:start">
      ${box("Assigned leads", P.leads.slice(0, 4).map(l => `<div class="list-row"><span class="ic">${ic("lead")}</span><div><div class="t">${esc(l.n)}</div><div class="s">${esc(l.int)} · ${esc(l.src)}</div></div><div class="r">${pill(l.s)}</div></div>`).join("") || empty("No leads assigned yet."), `<a href="#leads" class="link-arrow">All leads</a>`)}
      ${box("Upcoming appointments", P.appts.filter(a => d(a.date) >= d(iso(today))).map(a => `<div class="list-row"><span class="ic">${ic("cal")}</span><div><div class="t">${esc(a.who)}</div><div class="s">${fd(a.date)} · ${a.time} · ${a.type}</div></div></div>`).join("") || empty("No upcoming appointments."))}
      ${box("Benefits", `<div class="list-row"><span class="ic">${ic("palm")}</span><div><div class="t">10% vacation discount</div><div class="s">You, family & friends</div></div></div><div class="list-row"><span class="ic">${ic("car")}</span><div><div class="t">Auto allowance</div><div class="s">${pill(P.allowance)} · threshold pending</div></div></div>`)}
    </div>
  </div>`;
const chart = () => { if (!P.comm.length) return empty("No production yet."); const by = {}; P.comm.forEach(c => { const k = c.date.slice(0, 7); by[k] = (by[k] || 0) + c.prem; }); const ks = Object.keys(by).sort().slice(-12), mx = Math.max(...ks.map(k => by[k]));
  return `<div class="chart-bars" role="img" aria-label="Premium written by month">${ks.map(k => `<div><span class="mono" style="font-size:9px">${Math.round(by[k] / 1000)}k</span><i style="height:${by[k] / mx * 80}%"></i><span>${new Date(k + "-15T12:00").toLocaleString("en-US", { month: "short" })}</span></div>`).join("")}</div>`; };
const commTable = rows => `<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Policy</th><th>Insured</th><th>Line</th><th>Written</th><th>Premium</th><th>Commission base</th><th>Rate</th><th>Amount</th><th>Status</th></tr></thead><tbody>${rows.map(c => `<tr><td class="tag" style="font-size:10px">${esc(c.ref)}</td><td>${esc(c.ins)}</td><td>${esc(c.line)}</td><td>${fd(c.date)}</td><td>${fmt$(c.prem)}</td><td>${fmt$(c.base)}</td><td>${Math.round(c.rate * 100)}%</td><td><b>${fmt$(c.amt)}</b></td><td>${pill(c.s)}${c.paid ? `<div class="tag" style="font-size:9px;margin-top:4px">${fd(c.paid)}</div>` : ""}</td></tr>`).join("")}</tbody></table></div>`;
V.profile = () => box("Producer profile", dl([["Name", esc(P.me.first + " " + P.me.last)], ["Producer ID", P.me.id], ["NPN", esc(P.me.npn || "—")], ["Email", esc(P.me.email)], ["Phone", esc(P.me.phone || "—")], ["Business", esc(P.me.biz || "—")], ["Address", esc(P.me.addr || "—")], ["Status", pill(P.status === "review" ? "Under review" : "Active")], ["Joined / onboarded", fd(P.me.joined)]]) + `<div class="form-nav"><span class="muted small">Need to change your legal name or ID? Contact the agency office.</span><button class="btn btn-dark" id="editProf">Edit contact details</button></div>`);
V.licensing = () => { const exp = P.lic.find(l => /expir/i.test(l.status));
  return `${exp ? `<div class="notice warn" style="margin-bottom:14px"><span>${ic("alert")}</span>Your ${esc(exp.type)} license ${exp.status === "Expired" ? "expired" : "expires"} ${fd(exp.exp)}. Upload the renewal to keep those lines active.</div>` : ""}${box("Licenses", P.lic.length ? `<div class="tbl-wrap"><table class="tbl"><thead><tr><th>License type</th><th>Number</th><th>State</th><th>Issued</th><th>Expires</th><th>Status</th><th></th></tr></thead><tbody>${P.lic.map(l => `<tr><td>${esc(l.type)}</td><td>${esc(l.no)}</td><td>${l.st}</td><td>${fd(l.issue)}</td><td>${fd(l.exp)}</td><td>${pill(l.status)}</td><td><button class="btn-x" data-upd="${l.id}">Update</button></td></tr>`).join("")}</tbody></table></div>` : empty("No licenses on file yet."), `<button class="btn btn-line btn-sm" data-upd="new">+ Add license</button>`)}
  <div style="margin-top:14px">${box("Onboarding checklist", `<ul class="timeline"><li class="done">Application submitted</li><li class="${P.docs.length ? "done" : ""}">Resume & license uploaded</li><li class="${B().eligibility.licensed ? "done" : "now"}">Licensing verified</li><li class="${P.docs.some(x => x.s === "Needed") ? "now" : "done"}">Requested documents${P.docs.some(x => x.s === "Needed") ? "<small>Required — upload in Documents</small>" : ""}</li><li class="${P.status === "active" ? "done" : ""}">Producer approved</li></ul>`)}</div>`; };
V.documents = () => box("Documents", P.docs.map(x => `<div class="list-row"><span class="ic">${ic("doc")}</span><div><div class="t">${esc(x.n)}</div><div class="s">${x.date ? "Uploaded " + fd(x.date) : "Requested by COSPARK"}</div></div><div class="r" style="display:flex;gap:12px;align-items:center">${pill(x.s)}${x.s === "Needed" ? `<label class="btn btn-copper btn-sm" style="cursor:pointer">Upload<input type="file" hidden data-up="${x.id}"></label>` : x.url ? `<a class="btn-x" href="${x.url}">Download</a>` : ""}</div></div>`).join("") || empty("No documents yet."), `<label class="btn btn-line btn-sm" style="cursor:pointer">+ Upload document<input type="file" hidden data-up="new"></label>`);
V.tokens = () => { const st = B().status, credited = st !== "not_credited", ok = ["available", "redemption_requested", "redeemed"].includes(st), e = B().eligibility;
  const met = (x, label) => pill(x ? "Met" : label || "Not met");
  return `<div class="tokens3"><div class="tok dt"><span class="mono">Bonus</span><div class="v">${credited ? B().tokens.toLocaleString() : "0"}</div><div class="s">${credited ? "Producer token bonus" : `Not credited — ${bonusPending()}`}</div></div><div class="tok it"><span class="mono">Credited · pending</span><div class="v">${credited && !ok ? B().tokens.toLocaleString() : 0}</div><div class="s">${!credited ? "Not credited yet" : ok ? "Released" : "Locked until 12 months complete"}</div></div><div class="tok vt"><span class="mono">Available to redeem</span><div class="v">${st === "available" ? B().tokens.toLocaleString() : "0"}</div><div class="s">Redeemed: ${st === "redeemed" ? B().tokens.toLocaleString() : 0}</div></div></div>
  <div class="dash-grid">${box("Eligibility", `<ul class="check-list" style="gap:14px"><li>Hired & approved producer — ${met(e.approved)}</li><li>Appropriately licensed — ${met(e.licensed)}</li><li>$8.88 membership maintained — ${met(e.membership)}${e.membership ? "" : ` <a class="link-arrow" href="${PORTAL.membership}">Manage membership</a>`}</li><li>12 months writing with COSPARK — ${met(e.months, `${months()} of 12 months`)}</li></ul><button class="btn btn-copper" style="margin-top:22px" id="redeem" ${st === "available" ? "" : "disabled"}>${st === "redemption_requested" ? "Redemption requested" : "Redeem bonus"}</button><p class="small muted" style="margin-top:10px">${st === "available" ? "" : st === "redemption_requested" ? "COSPARK is processing your redemption." : eligDate() ? `The bonus can't be redeemed until ${fd(eligDate())}.` : ""}</p>`)}
  ${box("Token transactions", credited ? `<div class="tbl-wrap"><table class="tbl" style="min-width:420px"><thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Status</th></tr></thead><tbody><tr><td>${fd(B().credited)}</td><td>Producer bonus credited</td><td>+${B().tokens.toLocaleString()}</td><td>${pill(BONUS_LABEL[st])}</td></tr>${B().requested ? `<tr><td>${fd(B().requested)}</td><td>Redemption requested</td><td>${B().tokens.toLocaleString()}</td><td>${pill("Requested")}</td></tr>` : ""}</tbody></table></div>` : empty("The bonus is credited once you're approved, licensed and your $8.88 membership is active."))}<p class="small muted" style="margin-top:12px">Redemption follows the program's redemption rules, which COSPARK will publish before launch.</p></div>`; };
V.qualification = () => { const m = months(); return box("12-month qualification", `<div class="kpis" style="margin-bottom:20px">${[["Start date", fd(B().start)], ["Current period", `Month ${Math.min(12, m + 1)}`], ["Months completed", m], ["Months remaining", Math.max(0, 12 - m)]].map(([a, b]) => `<div class="kpi"><span class="mono muted">${a}</span><div class="v" style="font-size:30px">${b}</div></div>`).join("")}</div>
  <div class="progress" style="height:10px"><i style="width:${Math.min(100, m / 12 * 100)}%"></i></div>
  <div style="display:grid;grid-template-columns:repeat(12,1fr);gap:4px;margin-top:14px">${Array.from({ length: 12 }, (_, i) => `<div style="text-align:center"><div style="height:28px;border-radius:3px;background:${i < m ? "var(--copper)" : i === m ? "var(--copper-soft)" : "var(--bg-2)"};border:1px solid ${i === m ? "var(--copper)" : "transparent"}"></div><span class="mono" style="font-size:8.5px;color:var(--muted)">M${i + 1}</span></div>`).join("")}</div>
  ${dl([["Status", pill(m >= 12 ? "Eligible" : "In progress")], ["Eligibility date", fd(eligDate())], ["Redemption status", BONUS_LABEL[B().status] === "Available" ? "Available" : B().status === "redemption_requested" ? "Requested" : "Not yet redeemable"]])}`); };
V.commission = () => `<div class="kpis" style="grid-template-columns:repeat(3,1fr)"><div class="kpi"><span class="mono muted">Commission rate</span><div class="v">${rate()}%</div><div class="s">On applicable insurance you sell</div></div><div class="kpi"><span class="mono muted">Paid to date</span><div class="v">${fmt$(P.comm.filter(c => c.s === "Paid").reduce((a, c) => a + c.amt, 0))}</div><div class="s">${esc(P.payout || "Payout details on file with the office")}</div></div><div class="kpi"><span class="mono muted">Pending</span><div class="v">${fmt$(P.comm.filter(c => c.s === "Pending").reduce((a, c) => a + c.amt, 0))}</div><div class="s">Pays on the 1st</div></div></div>
  <div style="margin-top:14px">${box("Commission history", P.comm.length ? paged("comm", P.comm, commTable) : empty("No commissions yet."), P.comm.length ? `<a class="btn btn-line btn-sm" href="${PORTAL.export}">Export statement</a>` : "")}</div><p class="small muted" style="margin-top:12px">Commission base shown is illustrative. Exact calculation rules will follow the producer agreement.</p>`;
V.performance = () => { const by = {}; P.comm.forEach(c => by[c.line] = (by[c.line] || 0) + c.prem); const mx = Math.max(1, ...Object.values(by));
  return `<div class="kpis"><div class="kpi"><span class="mono muted">Premium written</span><div class="v">${fmt$(totPrem()).replace(/\.\d\d$/, "")}</div></div><div class="kpi"><span class="mono muted">Policies</span><div class="v">${P.comm.length}</div></div><div class="kpi"><span class="mono muted">Avg. premium</span><div class="v">${P.comm.length ? fmt$(totPrem() / P.comm.length).replace(/\.\d\d$/, "") : "—"}</div></div><div class="kpi"><span class="mono muted">Lead conversion</span><div class="v">${P.leads.length ? Math.round(P.leads.filter(l => l.s === "Won").length / P.leads.length * 100) : 0}%</div></div></div>
  <div class="dash-grid">${box("Production by month", chart())}${box("By line of business", Object.entries(by).sort((a, b) => b[1] - a[1]).map(([k, v]) => `<div style="margin-bottom:12px"><div class="row-between small"><span>${esc(k)}</span><span>${fmt$(v).replace(/\.\d\d$/, "")}</span></div><div class="progress" style="margin-top:6px"><i style="width:${v / mx * 100}%"></i></div></div>`).join("") || empty("No production yet."))}</div>`; };
V.benefits = () => `<div class="grid-3">${[["coin", "2,000-token bonus", "Redeemable after 12 months of writing with COSPARK.", "#tokens"], ["chart", `${rate()}% commission`, "On applicable insurance sold within the company's book.", "#commission"], ["palm", "10% vacation discount", "For you, your family and friends.", "#vacations"], ["star", "Top-performer programs", "Recognition and rewards for leading producers.", ""], ["briefcase", "Additional income programs", "Opportunities across the COSPARK ecosystem.", ""], ["car", "Auto allowance", "Unlocks at qualifying production thresholds.", ""]].map(([i, t, s, h]) => `<div class="card"><span class="copper">${ic(i)}</span><h3 style="font-weight:500;margin:14px 0 6px">${t}</h3><p class="small muted" style="margin:0 0 12px">${s}</p>${h ? `<a class="link-arrow" href="${h}">View</a>` : `<span class="tag">Details from COSPARK</span>`}</div>`).join("")}</div>`;
V.vacations = () => `<div class="notice" style="margin-bottom:14px"><span>${ic("palm")}</span><div><b>10% producer vacation discount</b> — available to you, your family and friends. Producer discounts are separate from member token benefits.</div></div>
  <div class="dash-grid">${box("Participating locations", P.properties.map(p => `<div class="list-row"><span class="ic">${ic("palm")}</span><div class="t">${esc(p.n)}</div><div class="r">${pill("Eligible")}</div></div>`).join(""))}
  ${box("How to use it", `<ol class="timeline"><li class="done">Choose a participating property</li><li class="done">Request dates at least ${P.config.noticeDays} days ahead</li><li class="done">Mention your producer ID ${P.me.id}</li><li>10% is applied at confirmation</li></ol><p class="small muted">Eligible users: producer, immediate family and friends travelling with or referred by the producer.</p><button class="btn btn-copper" id="pVac">Request a discounted stay</button>`)}</div>`;
V.leads = () => { const f = V._lf || "All", rows = P.leads.filter(l => f === "All" || l.s === f);
  return box("Assigned leads", `<div class="filter-row">${["All", "New", "Contacted", "Quoted", "Won", "Lost"].map(x => `<button class="chip ${x === f ? "on" : ""}" data-lf="${x}">${x}</button>`).join("")}</div>${rows.length ? `<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Lead</th><th>Contact</th><th>Interest</th><th>Source</th><th>Date</th><th>Status</th><th>Notes</th><th></th></tr></thead><tbody>${page("leads:" + f, rows)[0].map(l => `<tr><td><b>${esc(l.n)}</b><div class="tag" style="font-size:9px">${l.id}</div></td><td class="small">${esc(l.c)}</td><td>${esc(l.int)}</td><td>${esc(l.src)}</td><td>${fd(l.date)}</td><td>${pill(l.s)}</td><td class="small muted" style="max-width:200px">${esc(l.note || "—")}${l.follow ? `<div class="tag" style="font-size:9px">Follow up ${fd(l.follow)}</div>` : ""}</td><td><button class="btn-x" data-lead="${l.id}">Update</button></td></tr>`).join("")}</tbody></table></div>${page("leads:" + f, rows)[1]}` : empty("No leads in this status.")}`); };
V.appointments = () => box("Assigned appointments", P.appts.length ? `<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Date</th><th>Time</th><th>Type</th><th>Customer / member</th><th>Notes</th><th>Status</th></tr></thead><tbody>${page("appts", P.appts)[0].map(a => `<tr><td>${fd(a.date)}</td><td>${a.time}</td><td>${a.type}</td><td>${esc(a.who)}</td><td class="small">${esc(a.note)}</td><td>${pill(a.s)}</td></tr>`).join("")}</tbody></table></div>${page("appts", P.appts)[1]}` : empty("No appointments assigned yet."));
V.notifications = () => box("Notifications", paged("notes", P.notes, rows => rows.map(n => `<a href="#${n.go}" class="list-row" data-pr="${n.id}" style="${n.read ? "" : "font-weight:600"}"><span class="ic">${ic("bell")}</span><div><div class="t">${esc(n.t)}</div><div class="s" style="font-weight:400">${esc(n.s)}</div></div><div class="r s" style="font-weight:400">${fd(n.date)}</div></a>`).join("")) || empty("You're all caught up."), `<button class="btn-x" id="pReadAll">Mark all as read</button>`);

const NAVD = [["Producer"], ["overview", "Overview", "grid"], ["profile", "Profile", "user"], ["licensing", "Licensing", "shield"], ["documents", "Documents", "doc"], ["Earnings"], ["tokens", "Token Bonus", "coin"], ["qualification", "12-Month Qualification", "cal"], ["commission", "Commission", "wallet"], ["performance", "Performance", "chart"], ["Benefits"], ["benefits", "Benefits", "gift"], ["vacations", "Vacation Discount", "palm"], ["Work"], ["leads", "Leads", "lead"], ["appointments", "Appointments", "users"], ["notifications", "Notifications", "bell"]];
// Drawn at once on page load from the URL (before the data arrives), then again with the data
function navMenu(v) {
  const review = P?.status === "review", unread = P ? P.notes.filter(n => !n.read).length : 0;
  $("#dNav").innerHTML = NAVD.map(([k, l, i]) => l ? (review && !reviewOnly.includes(k) ? `<a style="opacity:.35;pointer-events:none">${ic(i)}${l}<span class="mono" style="margin-left:auto;font-size:8px">Locked</span></a>` : `<a href="#${k}" class="${k === v ? "on" : ""}">${ic(i)}${l}${k === "notifications" && unread ? `<span class="cnt">${unread}</span>` : ""}</a>`) : `<h6>${k}</h6>`).join("");
  $("#dBell").innerHTML = ic("bell") + (unread ? '<span class="dot"></span>' : "");
  // Keep the current section visible in a long sidebar (scrolls the sidebar only, never the page)
  const on = $("#dNav .on"), side = $("#dSide");
  if (on?.getBoundingClientRect && side?.getBoundingClientRect) { const r = on.getBoundingClientRect(), sr = side.getBoundingClientRect(); if (r.top < sr.top || r.bottom > sr.bottom) side.scrollTop += r.top - sr.top - sr.height / 3; }
}
function render() {
  if (!P) return;
  let v = location.hash.slice(1) || "overview"; if (!V[v] || v[0] === "_") v = "overview";
  if (P.status === "review" && !reviewOnly.includes(v)) v = "overview";
  navMenu(v);
  $("#dTitle").textContent = v === "overview" ? `Welcome back, ${P.me.first}` : NAVD.find(n => n[0] === v)[1];
  $("#dCrumb").textContent = `Producer portal · ${P.me.id}`;
  $("#dTier").textContent = P.status === "review" ? "Application under review" : "Licensed producer";
  $("#dName").textContent = `${P.me.first} ${P.me.last}`;
  $("#view").innerHTML = `<div class="view on">${V[v]()}</div>`;
}
document.addEventListener("DOMContentLoaded", async () => {
  $("#dLogo").innerHTML = LOGO + '<span class="logo-t"><b>COSPARK</b><span>PRODUCER PORTAL</span></span>';
  $("#dOut").innerHTML = ic("out"); $("#dMenu").innerHTML = ic("menu");
  addEventListener("hashchange", () => { $("#dSide").classList.remove("open"); document.querySelectorAll(".modal.open").forEach(closeModal); render(); scrollTo(0, 0); });
  navMenu(location.hash.slice(1) || "overview");
  $("#view").innerHTML = `<p class="empty">Loading the producer portal…</p>`;
  try { await load(); render(); } catch { $("#view").innerHTML = `<p class="empty">We couldn't load the portal. <a href="" class="copper">Try again</a></p>`; }
  // Live notifications: redraw only views that list notifications, so open forms keep their input
  liveNotifications("producer", n => {
    if (!P) return;
    P.notes.unshift(n);
    const v = location.hash.slice(1) || "overview";
    if (["overview", "notifications"].includes(v)) render(); else $("#dBell").innerHTML = ic("bell") + '<span class="dot"></span>';
    toast(n.t);
  });

  document.addEventListener("change", async e => { const u = e.target.dataset.up; if (u === undefined || !e.target.files.length) return;
    const f = new FormData(); f.append("file", e.target.files[0]); if (u !== "new") f.append("document_id", u);
    try { await act(PORTAL.document, f); } catch { return; } toast("Document uploaded for review"); refresh(); });

  document.addEventListener("click", async e => {
    const t = e.target.closest("[data-lf],[data-lead],[data-upd],[data-pr],#pReadAll,#editProf,#pVac,#redeem"); if (!t || !P) return;
    if (t.dataset.lf) { V._lf = t.dataset.lf; render(); }
    if (t.dataset.pr) { const n = P.notes.find(x => x.id === t.dataset.pr); if (n && !n.read) { n.read = true; send(url("noteRead", n.id), {}).catch(() => {}); } }
    if (t.id === "pReadAll") { try { await act(PORTAL.notesReadAll, {}); } catch { return; } refresh(); }
    if (t.id === "redeem") { busy(t, true, "Requesting…"); try { await act(PORTAL.redeem, {}); } catch { return busy(t, false); } toast("Redemption requested — COSPARK will contact you"); refresh(); }
    if (t.dataset.lead) { const l = P.leads.find(x => x.id === t.dataset.lead);
      modal(`<span class="eyebrow">${l.id} · ${esc(l.src)}</span><h2 class="h3s" style="margin:12px 0 6px;font-size:32px">${esc(l.n)}</h2><p class="muted small">${esc(l.c)} · ${esc(l.int)}</p><div class="field" style="margin-top:18px"><span class="lbl">Status</span><div class="chips" id="ls">${["New", "Contacted", "Quoted", "Won", "Lost"].map(s => `<button class="chip ${s === l.s ? "on" : ""}" data-s="${s}">${s}</button>`).join("")}</div></div><div class="field" style="margin-top:16px"><label>Follow-up date</label><input type="date" id="lfd" value="${l.follow || ""}"></div><div class="field" style="margin-top:16px"><label>Notes</label><textarea id="ln">${esc(l.note)}</textarea></div><div class="form-nav"><button class="btn btn-line" data-close>Cancel</button><button class="btn btn-dark" id="lSave">Save</button></div>`, true);
      let s = l.s; $("#ls").onclick = e2 => { const b = e2.target.closest("[data-s]"); if (!b) return; s = b.dataset.s; $("#ls").querySelectorAll(".chip").forEach(x => x.classList.toggle("on", x === b)); };
      $("#lSave").onclick = async () => { const btn = $("#lSave"); busy(btn, true, "Saving…");
        try { await act(url("lead", l.id), { status: s.toLowerCase(), follow_up_on: $("#lfd").value || null, notes: $("#ln").value }); } catch { return busy(btn, false); }
        closeM(); toast("Lead updated"); refresh(); }; }
    if (t.dataset.upd) { const l = P.lic.find(x => x.id == t.dataset.upd);
      modal(`<h2 class="h3s" style="font-size:32px;margin-bottom:18px">${l ? "Update " + esc(l.type) : "Add a license"}</h2><div class="form-grid" id="licF"><div class="field half"><label>License type</label><select name="license_type">${["Property & Casualty", "Life & Health"].map(x => `<option ${l?.type === x ? "selected" : ""}>${x}</option>`).join("")}</select></div><div class="field half"><label>State</label><select name="state">${P.config.states.map(s => `<option ${s === (l?.st || "MI") ? "selected" : ""}>${s}</option>`).join("")}</select></div><div class="field half"><label>License number</label><input name="license_number" required value="${esc(l?.no || "")}"><span class="err-msg">Required.</span></div><div class="field half"><label>Expiration date</label><input type="date" name="expires_on" required><span class="err-msg">Required.</span></div><div class="field"><span class="lbl">License document</span><label class="file-drop"><input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.heic">Choose file</label></div></div><div class="form-nav"><button class="btn btn-line" data-close>Cancel</button><button class="btn btn-dark" id="licSave">Submit for verification</button></div>`, true);
      $("#licSave").onclick = async () => { if (!validate($("#dBox"))) return; const f = new FormData(); $("#licF").querySelectorAll("[name]").forEach(i => i.type === "file" ? (i.files[0] && f.append(i.name, i.files[0])) : f.append(i.name, i.value)); if (l) f.append("license_id", l.id);
        const btn = $("#licSave"); busy(btn, true, "Submitting…");
        try { await act(PORTAL.license, f); } catch (err) { busy(btn, false); showServerErrors($("#licF"), err.errors || {}); return; }
        closeM(); toast("License submitted for verification"); refresh(); }; }
    if (t.id === "editProf") { modal(`<h2 class="h3s" style="font-size:32px;margin-bottom:18px">Edit contact details</h2><div class="form-grid" id="peF"><div class="field"><label>Email</label><input id="pe" type="email" value="${esc(P.me.email)}" disabled><span class="hint">Contact COSPARK to change your sign-in email.</span></div><div class="field"><label>Phone</label><input id="pp" name="phone" required value="${esc(P.me.phone)}"><span class="err-msg">Required.</span></div><div class="field"><label>Business name</label><input id="pb" name="business_name" value="${esc(P.me.biz)}"></div><div class="field"><label>Address</label><input id="pa" name="business_address" value="${esc(P.me.addr)}"></div></div><div class="form-nav"><button class="btn btn-line" data-close>Cancel</button><button class="btn btn-dark" id="peSave">Save</button></div>`, true);
      $("#peSave").onclick = async () => { if (!validate($("#peF"))) return; const btn = $("#peSave"); busy(btn, true, "Saving…");
        try { await act(PORTAL.profile, { phone: $("#pp").value, business_name: $("#pb").value, business_address: $("#pa").value }); } catch (err) { busy(btn, false); showServerErrors($("#peF"), err.errors || {}); return; }
        closeM(); toast("Profile updated"); refresh(); }; }
    if (t.id === "pVac") { const min = new Date(today); min.setDate(min.getDate() + P.config.noticeDays);
      modal(`<h2 class="h3s" style="font-size:32px;margin-bottom:18px">Request a discounted stay</h2><div class="form-grid"><div class="field"><label>Property</label><select id="pvP">${P.properties.map(p => `<option value="${p.id}">${esc(p.n)}</option>`).join("")}</select></div><div class="field half"><label>Check-in</label><input type="date" id="pvIn" min="${iso(min)}" required><span class="err-msg">Required (${P.config.noticeDays}+ days ahead).</span></div><div class="field half"><label>Check-out</label><input type="date" id="pvOut" min="${iso(min)}" required><span class="err-msg">Required.</span></div><div class="field"><label>Who's travelling?</label><select id="pvW"><option>Me</option><option>Family</option><option>Friends</option></select></div></div><div class="card" id="pvEst" style="margin-top:14px;background:var(--bg-2)"><span class="small muted">Choose dates to see your discounted estimate.</span></div><div class="form-nav"><button class="btn btn-line" data-close>Cancel</button><button class="btn btn-copper" id="pvGo">Submit request</button></div>`, true);
      // Estimate with the producer discount, as applied when the request is saved
      const pvEst = () => { const a = $("#pvIn").value, b = $("#pvOut").value, p = P.properties.find(x => x.id == $("#pvP").value); if (!a || !b || d(b) <= d(a) || !p) return;
        const n = Math.round((d(b) - d(a)) / 864e5), full = n * p.rate, off = Math.round(full * P.config.discount * 100) / 100;
        $("#pvEst").innerHTML = `<div class="row-between"><span>${n} night${n > 1 ? "s" : ""} · est. rate</span><span>${fmt$(full)}</span></div><div class="row-between" style="margin-top:6px"><span class="copper">Producer discount (${Math.round(P.config.discount * 100)}%)</span><span class="copper">− ${fmt$(off)}</span></div><div class="row-between" style="margin-top:6px"><b>Estimated balance due</b><b>${fmt$(full - off)}</b></div>`; };
      ["#pvIn", "#pvOut", "#pvP"].forEach(s => $(s).onchange = pvEst);
      $("#pvGo").onclick = async () => { if (!validate($("#dBox"))) return; const a = $("#pvIn").value, b = $("#pvOut").value;
        if (d(a) < d(iso(min))) return fieldErr($("#pvIn")); if (d(b) <= d(a)) return fieldErr($("#pvOut"), "Check-out must be after check-in.");
        const btn = $("#pvGo"); busy(btn, true, "Submitting…");
        try { await act(PORTAL.vacation, { property_id: +$("#pvP").value, check_in: a, check_out: b, traveller: $("#pvW").value }); }
        catch (err) { busy(btn, false); const map = { check_in: "#pvIn", check_out: "#pvOut" }; Object.keys(err.errors || {}).forEach(k => map[k] && fieldErr($(map[k]), err.errors[k][0])); return; }
        closeM(); toast("Request sent — 10% producer discount will be applied"); refresh(); }; }
  });
});
})();
