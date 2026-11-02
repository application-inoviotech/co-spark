/* COSPARK — resources: category chips + search over the server-rendered cards */
document.addEventListener("DOMContentLoaded", () => {
  const cats = document.getElementById("rCats"), q = document.getElementById("rq");
  const cards = [...document.querySelectorAll("#rGrid [data-cat]")];
  let cat = cats.querySelector(".chip.on")?.dataset.c || "all";
  const draw = () => {
    const t = q.value.toLowerCase(); let shown = 0;
    cards.forEach(c => { const ok = (cat === "all" || c.dataset.cat === cat) && c.dataset.text.toLowerCase().includes(t); c.hidden = !ok; shown += ok; });
    document.getElementById("rEmpty").hidden = !!shown;
  };
  cats.onclick = e => { const b = e.target.closest("[data-c]"); if (!b) return; cat = b.dataset.c; cats.querySelectorAll(".chip").forEach(x => x.classList.toggle("on", x === b)); draw(); };
  q.oninput = draw; draw();
});
