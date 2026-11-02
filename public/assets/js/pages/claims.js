/* COSPARK — claims hub: switch the claim-type guidance panel */
document.addEventListener("DOMContentLoaded", () => {
  const tabs = document.getElementById("ctTabs");
  tabs.addEventListener("click", e => {
    const k = e.target.dataset.k; if (!k) return;
    tabs.querySelectorAll("button").forEach(b => b.classList.toggle("on", b.dataset.k === k));
    document.querySelectorAll("[data-ct]").forEach(p => p.hidden = p.dataset.ct !== k);
  });
});
