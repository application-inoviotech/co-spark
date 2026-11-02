/* COSPARK — card entry with Authorize.net Accept.js (registration + member dashboard).
   The card number, expiry and CVC go straight from the browser to Authorize.net, which returns a
   single-use token; only that token is sent to COSPARK. The inputs have no name attribute, so they
   are never collected into form state. */
window.CardEntry = (() => {
  const field = (id, label, half, attrs) => `<div class="field${half ? " half" : ""}"><label for="${id}">${label} <span class="req">*</span></label><input id="${id}" type="text" ${attrs}><span class="err-msg">Required.</span></div>`;
  const digits = el => (el?.value || "").replace(/\D/g, "");
  const FIELD_FOR = { E_WC_05: "cnum", E_WC_06: "cexp", E_WC_07: "cexp", E_WC_08: "cexp", E_WC_15: "ccvc" };
  let kept = {};

  // Format as the member types: "4111 1111 1111 1111" (16 digits max), "MM / YY", 3–4 digit CVC
  document.addEventListener("input", e => {
    const el = e.target;
    if (el.id === "cnum") el.value = digits(el).slice(0, 16).replace(/(\d{4})(?=\d)/g, "$1 ");
    if (el.id === "cexp") { const d = digits(el).slice(0, 4); el.value = d.length > 2 ? `${d.slice(0, 2)} / ${d.slice(2)}` : d; }
    if (el.id === "ccvc") el.value = digits(el).slice(0, 4);
  });

  return {
    fields: () => field("cnum", "Card number", false, 'inputmode="numeric" autocomplete="cc-number" maxlength="19" placeholder="1234 1234 1234 1234"')
      + field("cexp", "Expiry", true, 'inputmode="numeric" autocomplete="cc-exp" placeholder="MM / YY"')
      + field("ccvc", "CVC", true, 'inputmode="numeric" autocomplete="cc-csc" placeholder="123"'),

    // Keep typed values across a re-render of the step (in memory only)
    keep: scope => { kept = Object.fromEntries(["cnum", "cexp", "ccvc"].map(id => [id, scope.querySelector("#" + id)?.value || ""])); },
    restore: scope => { Object.entries(kept).forEach(([id, v]) => { const el = scope.querySelector("#" + id); if (el && v) el.value = v; }); },
    clear: () => { kept = {}; },

    // Resolves { descriptor, token }; rejects with { field, message } for the input to flag
    tokenize: (scope, config) => new Promise((resolve, reject) => {
      if (!window.Accept || !config?.clientKey) return reject({ field: "cnum", message: "Card payments are unavailable right now. Please try again shortly." });
      const exp = digits(scope.querySelector("#cexp"));
      Accept.dispatchData({
        authData: { clientKey: config.clientKey, apiLoginID: config.loginId },
        cardData: { cardNumber: digits(scope.querySelector("#cnum")), month: exp.slice(0, 2), year: exp.slice(2, 4), cardCode: digits(scope.querySelector("#ccvc")) },
      }, r => {
        if (r.messages.resultCode === "Ok") return resolve({ descriptor: r.opaqueData.dataDescriptor, token: r.opaqueData.dataValue });
        const m = r.messages.message[0];
        reject({ field: FIELD_FOR[m.code] || "cnum", message: m.code === "E_WC_02" ? "Card payments need a secure (https) connection." : m.text });
      });
    }),
  };
})();
