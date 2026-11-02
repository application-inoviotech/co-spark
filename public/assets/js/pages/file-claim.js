/* COSPARK — File a Claim (SOW: Verify policy → Claim type → Incident → Documents → Review → Submit)
   CLAIM_PREFILL is the signed-in member's profile/policy (null for guests). */
document.addEventListener("DOMContentLoaded", () => {
  const root = document.getElementById("claimForm");
  const types = { Auto: ["Collision", "Theft", "Vandalism", "Weather / hail", "Animal strike", "Glass"], Home: ["Water damage", "Fire / smoke", "Wind / hail", "Theft", "Liability"], Renters: ["Theft", "Water damage", "Fire", "Liability"], Condo: ["Water damage", "Fire", "Theft", "Interior damage"], Commercial: ["Property damage", "Business interruption", "Liability", "Employee injury", "Commercial vehicle"], Life: ["Death benefit"], Other: ["Other loss"] };
  const map = { auto: "Auto", glass: "Auto", home: "Home", renters: "Renters", condo: "Condo", business: "Commercial", life: "Life", other: "Other" };
  const qType = new URLSearchParams(location.search).get("type");
  const pre = map[qType];
  const { policies = [], ...member } = CLAIM_PREFILL || {};
  const state = CLAIM_PREFILL ? { ...member, itype: member.itype || pre || "Auto" } : { itype: pre };
  // Signed-in members pick from their own policies instead of typing a number
  // A signed-in member with no policy on file can't pick one: say why, and how to get it linked
  const noPolicyNote = CLAIM_PREFILL && !policies.length
    ? `<b>There's no policy on your account yet.</b> A claim is filed against a COSPARK policy on your account. If you have a COSPARK policy that isn't showing, enter its number above or <a href="#" class="copper" data-appt>schedule a call</a> and we'll link it.`
    : "";
  const policyField = policies.length
    ? { name: "policy", label: "Policy number", type: "select", options: policies.map(p => p.no), required: true, half: true, hint: "Choose one of the policies on your account." }
    : { name: "policy", label: "Policy number", required: true, half: true, placeholder: "e.g. CSP-AUT-000000", hint: "You'll find it on your policy documents." };
  if (qType === "glass" && state.itype === "Auto") state.ctype = "Glass";
  const dyn = s => {
    const q = [];
    if (s.itype === "Auto") q.push({ name: "vehicle", label: "Vehicle involved", half: true, required: true, placeholder: "Year, make, model" }, { name: "police", label: "Police report filed?", type: "chips", options: ["Yes", "No"], required: true }, { name: "report", label: "Police report number", half: true, show: x => x.police === "Yes" }, { name: "other", label: "Was another vehicle involved?", type: "chips", options: ["Yes", "No"], required: true }, { name: "otherInfo", label: "Other driver's name, insurer & plate", show: x => x.other === "Yes" }, { name: "drivable", label: "Is the vehicle drivable?", type: "chips", options: ["Yes", "No", "Not sure"], required: true });
    if (["Home", "Renters", "Condo"].includes(s.itype)) q.push({ name: "rooms", label: "Areas affected", type: "multi", options: ["Kitchen", "Living area", "Bedroom", "Bathroom", "Basement", "Roof", "Exterior", "Garage"], required: true }, { name: "habitable", label: "Can you stay in the home?", type: "chips", options: ["Yes", "No"], required: true }, { name: "mitig", label: "Have you made temporary repairs?", type: "chips", options: ["Yes", "No"] });
    if (s.itype === "Commercial") q.push({ name: "closed", label: "Is the business closed due to the loss?", type: "chips", options: ["Yes", "Partially", "No"], required: true }, { name: "injured", label: "Was anyone injured?", type: "chips", options: ["Yes", "No"], required: true });
    if (s.itype === "Life") q.push({ name: "insured", label: "Name of insured", required: true }, { name: "relation", label: "Your relationship to the insured", half: true, required: true });
    q.push({ name: "injury", label: "Were there any injuries?", type: "chips", options: ["Yes", "No"], required: s.itype !== "Life", show: () => s.itype !== "Life" && s.itype !== "Commercial" });
    return q;
  };
  const today = new Date(); const maxDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}-${String(today.getDate()).padStart(2, "0")}`;
  new StepForm(root, {
    state, action: root.dataset.action, submitLabel: "Submit claim",
    terms: "I confirm the information provided is true and complete to the best of my knowledge.",
    getSteps: s => [
      { title: "Verify the policyholder.", short: "Customer & policy", note: noPolicyNote, sub: CLAIM_PREFILL ? "We've filled this in from your account. Check it's correct." : "Enter the details on the policy.", fields: [F.first, F.last, F.email, F.phone, policyField, { name: "itype", label: "Insurance type", type: "select", options: Object.keys(types), required: true, half: true }, F.pref] },
      { title: "What kind of claim is it?", short: "Claim type", fields: [{ name: "ctype", label: "Claim type", type: "chips", options: types[s.itype] || types.Other, required: true }] },
      { title: "Tell us what happened.", short: "Incident", fields: [{ name: "date", label: "Incident date", type: "date", required: true, half: true, max: maxDate }, { name: "time", label: "Approximate time", type: "time", half: true }, { name: "loc", label: "Incident location", required: true, placeholder: "Address or nearest intersection" }, { name: "desc", label: "Describe what happened", type: "textarea", required: true }, ...dyn(s)] },
      { title: "Add photos and documents.", short: "Documents", sub: "Upload what you have now — you can add more later from your dashboard.", fields: [{ name: "photos", label: "Photos & video", type: "file", accept: "image/jpeg,image/png,image/heic,.heic,video/mp4,video/quicktime,.mov", formats: "JPG, PNG, HEIC, MP4 or MOV" }, { name: "docs", label: "Documents (police report, estimates, receipts)", type: "file" }, { name: "comments", label: "Additional comments", type: "textarea" }] },
    ],
    summary: [["#c_pol", s => s.policy], ["#c_type", s => [s.itype, s.ctype].filter(Boolean).join(" · ")], ["#c_date", s => s.date], ["#c_loc", s => s.loc], ["#c_files", s => { const n = (s.photos || []).length + (s.docs || []).length; return n ? `${n} file${n > 1 ? "s" : ""}` : ""; }]],
    confirm: (s, id) => `<div class="confirm"><div class="confirm-mark">✓</div><h2 class="step-title">Your claim has been submitted.</h2><p class="muted">An adjuster will contact you by ${(s.pref || "email").toLowerCase()} within one business day. A confirmation was sent to ${s.email}.</p><div class="ref-id">${id}</div><ul class="check-list" style="text-align:left;max-width:420px;margin:6px auto 28px"><li>Save your claim number for reference.</li><li>Keep receipts for any emergency repairs.</li><li>Upload more documents from your dashboard anytime.</li></ul><div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap"><a href="${root.dataset.dashboardUrl}#claims" class="btn btn-copper">Track this claim →</a><a href="${root.dataset.claimsUrl}" class="btn btn-line">Back to claims</a></div></div>`,
  });
});
