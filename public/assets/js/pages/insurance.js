/* COSPARK — insurance hub: sticky category tabs + state availability */
document.addEventListener("DOMContentLoaded", () => {
  const tabs = document.querySelectorAll("#catTabs a");
  const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) tabs.forEach(t => t.classList.toggle("on", t.getAttribute("href") === "#" + e.target.id)); }), { rootMargin: "-40% 0px -55% 0px" });
  document.querySelectorAll("#cats section, #states").forEach(s => io.observe(s));

  const sel = document.getElementById("stSel"), out = document.getElementById("stOut");
  sel.onchange = () => {
    const v = sel.value;
    out.innerHTML = v ? `<p style="color:var(--ink);margin:0 0 10px"><b>${v}</b>: auto, home, renters, condo, life and business coverage are available through COSPARK. Flood and earthquake availability depends on your county.</p><a href="${sel.dataset.quoteUrl}" class="link-arrow">Start a ${v} quote</a>` : "Select a state to see product availability.";
  };
});
