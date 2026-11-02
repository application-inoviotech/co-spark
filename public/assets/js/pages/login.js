/* COSPARK — sign in: account type chips + client-side check before posting */
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("loginForm"), role = form.querySelector("[name=role]");
  document.querySelectorAll("#role .chip").forEach(b => b.onclick = () => {
    document.querySelectorAll("#role .chip").forEach(x => x.classList.toggle("on", x === b));
    role.value = b.dataset.r;
    // Producers do not self-register (SOW): they apply and are onboarded by COSPARK
    document.querySelectorAll("[data-for]").forEach(s => s.hidden = s.dataset.for !== b.dataset.r);
  });
  form.addEventListener("submit", e => {
    if (!validate(form)) return e.preventDefault();
    const btn = form.querySelector("button[type=submit]"); btn.textContent = "Signing in…"; btn.disabled = true;
  });
});
