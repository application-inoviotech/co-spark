/* COSPARK — membership: billing-frequency pricing toggle + Insurance Token estimator */
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("#freq .chip").forEach(b => b.onclick = () => {
    document.querySelectorAll("#freq .chip").forEach(x => x.classList.toggle("on", x === b));
    document.querySelectorAll("[data-p]").forEach(p => p.textContent = "$" + (+p.dataset.p * +b.dataset.m).toFixed(2).replace(/\.00$/, ""));
    document.querySelectorAll(".per").forEach(p => p.textContent = "/ " + b.dataset.l.toUpperCase());
  });
  const calc = () => { const entered = Number(document.getElementById("prem").value), pr = Number.isFinite(entered) ? Math.max(0, entered) : 0, r = +document.getElementById("tier").value; const it = Math.round(pr * r), dt = r > .06 ? 144 : 48;
    document.getElementById("itOut").textContent = it.toLocaleString(); document.getElementById("dtOut").textContent = dt; document.getElementById("totOut").textContent = (it + dt).toLocaleString(); };
  ["prem", "tier"].forEach(id => document.getElementById(id).addEventListener("input", calc)); calc();
});
