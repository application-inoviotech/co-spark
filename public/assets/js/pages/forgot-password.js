/* COSPARK — Forgot password (SOW): email → 6-digit code → new password */
document.addEventListener("DOMContentLoaded", () => {
  const fp = document.getElementById("fp"), otp = fp.querySelector(".otp"), email = fp.querySelector("#fe");
  otpWire(otp);
  const show = n => { fp.querySelectorAll("[data-s]").forEach(s => s.hidden = s.dataset.s !== String(n)); fp.querySelector(`[data-s="${n}"] input`)?.focus(); };
  const otpErr = msg => { const e = document.getElementById("otpErr"); e.textContent = msg; e.style.display = "block"; };

  fp.querySelector("[data-resend]").onclick = async () => {
    try { await send(fp.dataset.send, { email: email.value }); toast("A new code was sent."); } catch (err) { toast(err.message); }
  };

  fp.querySelectorAll("[data-go]").forEach(b => b.onclick = async () => {
    const cur = b.closest("[data-s]"), n = b.dataset.go;
    if (n === "3" && [...otp.querySelectorAll("input")].some(i => !i.value)) return otpErr("Enter all 6 digits.");
    if (!validate(cur)) return;
    const np1 = fp.querySelector("#np1"), np2 = fp.querySelector("#np2");
    if (n === "4") {
      if (np1.value.length < 8 || !/\d/.test(np1.value)) return np1.closest(".field").classList.add("err");
      if (np1.value !== np2.value) return np2.closest(".field").classList.add("err");
    }
    busy(b, true, "Please wait…");
    try {
      if (n === "2") await send(fp.dataset.send, { email: email.value });
      if (n === "3") await send(fp.dataset.verify, { email: email.value, code: [...otp.querySelectorAll("input")].map(i => i.value).join("") });
      if (n === "4") await send(fp.dataset.update, { password: np1.value, password_confirmation: np2.value });
      show(n);
    } catch (err) {
      if (n === "3") otpErr(err.message);
      else { showServerErrors(cur, err.errors); toast(err.message); }
    } finally { busy(b, false); }
  });
});
