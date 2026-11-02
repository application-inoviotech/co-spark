/* COSPARK — home page: counters, model orbit, mini quote, appointment picker, ecosystem map */
document.addEventListener("DOMContentLoaded", () => {
  // count-up
  document.querySelectorAll("[data-count]").forEach(el => {
    const end = +el.dataset.count; let s = null;
    const io = new IntersectionObserver(([e]) => { if (!e.isIntersecting) return; io.disconnect();
      const step = t => { s ??= t; const p = Math.min((t - s) / 1400, 1); el.textContent = Math.round(end * (1 - Math.pow(1 - p, 3))); if (p < 1) requestAnimationFrame(step); };
      matchMedia("(prefers-reduced-motion: reduce)").matches ? el.textContent = end : requestAnimationFrame(step); });
    io.observe(el);
  });

  // connected model orbit
  const nodes = [["Benefits", 300, "Access participating COSPARK partner benefits — dining, vacations and member pricing — with your QR code."], ["Insurance", 40, "Your policies are the foundation. Eligible annual premiums earn Insurance Tokens (5% Associate, 7% Premium)."], ["Membership", 125, "Associate and Premium dues earn 4 or 12 Dues Tokens each month after a successful payment."], ["Payments", 225, "Dues and insurance premiums are paid separately, with history, receipts and autopay in one place."]];
  const orbit = document.getElementById("modelOrbit"), copy = document.getElementById("modelCopy");
  nodes.forEach(([n, deg, txt], k) => {
    const r = (deg - 90) * Math.PI / 180, b = document.createElement("button");
    b.className = "model-node"; b.style.left = 50 + 50 * Math.cos(r) + "%"; b.style.top = 50 + 50 * Math.sin(r) + "%";
    b.innerHTML = `<span>${n}</span><i></i>`;
    b.onclick = () => { orbit.querySelectorAll(".model-node").forEach(x => x.classList.remove("on")); b.classList.add("on"); copy.textContent = txt; };
    orbit.appendChild(b); if (k === 1) b.click();
  });

  // mini quote (shared StepForm engine) → saved as a lead, then offers the detailed quote
  const mq = document.getElementById("miniQuote");
  const cov = ["Home", "Auto", "Health", "Commercial", "Multiple Policies"];
  const toType = { Home: "home", Auto: "auto", Health: "life", Commercial: "business", "Multiple Policies": "auto-home" };
  new StepForm(mq, {
    getSteps: () => [
      { title: "What are you looking to insure?", short: "Coverage", fields: [{ name: "cov", label: "Coverage", type: "chips", options: cov, required: true, err: "Choose what you'd like to insure." }] },
      { title: "How can we reach you?", short: "Contact", fields: [F.first, F.last, F.email, F.phone] },
      { title: "Where is the coverage needed?", short: "Address", fields: [F.street, F.city, F.state, F.zip] },
    ],
    action: mq.dataset.action, submitLabel: "Request my quote",
    summary: [["#sq_cov", s => s.cov], ["#sq_name", s => [s.first, s.last].filter(Boolean).join(" ")], ["#sq_email", s => s.email], ["#sq_phone", s => s.phone], ["#sq_addr", s => [s.street, s.city, s.st, s.zip].filter(Boolean).join(", ")]],
    confirm: (s, id) => {
      const q = new URLSearchParams({ type: toType[s.cov], zip: s.zip, first: s.first, last: s.last, email: s.email, phone: s.phone });
      return `<div class="confirm"><div class="confirm-mark">✓</div><h2 class="step-title">Thanks, ${s.first}. Your request is in.</h2><p class="muted">A COSPARK agent will contact you by phone or email within one business day.</p><div class="ref-id">${id}</div><p class="small muted">Want a faster, more accurate quote? Add your details now.</p><a class="btn btn-copper" href="${mq.dataset.quoteUrl}?${q}">Complete detailed quote →</a></div>`;
    },
  });

  // inline appointment widget → prefills the appointment popup
  const cal = document.getElementById("cal"), times = document.getElementById("times"), hs = document.getElementById("hSum");
  let hType = "Phone", hDate = null, hTime = null;
  const start = new Date(); start.setDate(start.getDate() + 1); while (start.getDay() !== 1) start.setDate(start.getDate() + 1);
  cal.innerHTML = ["M", "T", "W", "T", "F"].map(d => `<span class="dh">${d}</span>`).join("");
  for (let w = 0; w < 3; w++) for (let d = 0; d < 5; d++) {
    const dt = new Date(start); dt.setDate(start.getDate() + w * 7 + d);
    const b = document.createElement("button"); b.textContent = dt.getDate(); b.type = "button";
    b.setAttribute("aria-label", dt.toDateString());
    if (d === 4) { b.disabled = true; b.title = "Office closed Fridays — request after-hours in the form"; }
    b.onclick = () => { cal.querySelectorAll("button").forEach(x => x.classList.remove("on")); b.classList.add("on"); hDate = dt; upd(); };
    cal.appendChild(b);
  }
  JSON.parse(document.getElementById("homeAppt").dataset.times).forEach(t => { const b = document.createElement("button"); b.type = "button"; b.textContent = t; b.onclick = () => { times.querySelectorAll("button").forEach(x => x.classList.remove("on")); b.classList.add("on"); hTime = t; upd(); }; times.appendChild(b); });
  document.querySelectorAll("[data-ht]").forEach(b => b.onclick = () => { document.querySelectorAll("[data-ht]").forEach(x => x.classList.remove("on")); b.classList.add("on"); hType = b.dataset.ht; upd(); });
  function upd() { hs.textContent = `${hType} · ${hDate ? hDate.toLocaleDateString("en-US", { month: "long", day: "numeric" }) : "choose a date"}${hTime ? " · " + hTime : ""}`; }
  const isoDate = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
  document.getElementById("hConfirm").onclick = () => {
    if (!hDate || !hTime) return toast("Choose a date and a time first.");
    const m = document.getElementById("apptModal");
    m.querySelector("#apptForm").hidden = false; m.querySelector("#apptDone").hidden = true;
    m.querySelectorAll(".appt-type")[hType === "Phone" ? 0 : 1].click();
    m.querySelector("[name=a_date]").value = isoDate(hDate);
    m.querySelector("[name=a_time]").value = hTime; m.querySelector("[name=a_time]").dispatchEvent(new Event("change", { bubbles: true }));
    openModal("apptModal"); setTimeout(() => m.querySelector("[name=a_name]").focus(), 60);
  };

  // ecosystem map
  const eco = document.getElementById("ecoMap"), ecoHref = eco.dataset.href;
  const pts = [["Stay for Free Vacations", -20, .36], ["Big Circle Auctions", 25, .36], ["COSPARK Eateries", 70, .36], ["COSPARK Centers", 115, .36], ["Keys Cove Luxury Vacations", 160, .36], ["Fish Camp", 205, .36], ["Sarasota Properties", 250, .36], ["Grassy Key", 295, .36]];
  const cats = [["Travel", -25], ["Member benefits", 72], ["Auctions", 120], ["Dining", 180], ["Centers", 226], ["Vacations", 280]];
  const place = (deg, rad) => { const r = (deg - 90) * Math.PI / 180; return [50 + rad * 100 * Math.cos(r), 50 + rad * 100 * Math.sin(r)]; };
  pts.forEach(([n, d, r]) => { const [x, y] = place(d, r); eco.insertAdjacentHTML("beforeend", `<a class="eco-node" href="${ecoHref}" style="left:${x}%;top:${y}%"><i></i>${n}</a>`); });
  cats.forEach(([n, d]) => { const [x, y] = place(d, .49); eco.insertAdjacentHTML("beforeend", `<span class="eco-cat" style="left:${x}%;top:${y}%">${n}</span>`); });

  document.getElementById("qrHome").innerHTML = makeQR("COSPARK-MBR-JAMES-DINING");
});
