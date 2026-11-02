/* COSPARK — dynamic quote engine config (SOW §7–14)
   Flow: Insurance type → ZIP → dynamic product steps → contact → review → submit
   QUOTE_TYPES ({ slug: label }) is provided by the page from config('cospark.quote_types'). */
const yes = k => s => s[k] === "Yes";
const birthDateLimit = (() => { const day = new Date(); day.setDate(day.getDate() - 1); return `${day.getFullYear()}-${String(day.getMonth() + 1).padStart(2, '0')}-${String(day.getDate()).padStart(2, '0')}`; })();
const prior = (withLimits) => ({
  key: "prior", title: "Tell us about your current coverage.", short: "Prior coverage", sub: "Sharing your current declarations page helps us compare like for like.",
  fields: [
    { name: "insured", label: "Are you currently insured?", type: "chips", options: ["Yes", "No"], required: true },
    { name: "carrier", label: "Current carrier", half: true, required: true, show: yes("insured") },
    { name: "expires", label: "Policy expiration date", type: "date", half: true, required: true, show: yes("insured") },
    ...(withLimits ? [{ name: "limits", label: "Current coverage limits", type: "select", options: ["State minimum", "50/100/50", "100/300/100", "250/500/250", "Not sure"], show: yes("insured") }] : []),
    { name: "decs", label: "Declarations (deck) sheet", type: "file", show: yes("insured"), hint: "Optional, but it speeds things up." },
  ]
});
const STEPS = {
  driver: { key: "drivers", title: "Who will be driving?", short: "Drivers", sub: "Add every licensed driver in your household.", fields: [
    { name: "drivers", label: "Driver", type: "group", repeat: true, fields: [
      { name: "name", label: "Full legal name", required: true },
      { name: "dob", label: "Date of birth", type: "date", required: true, half: true, max: birthDateLimit, err: "Date of birth must be before today." },
      { name: "dl", label: "Driver license number", required: true, half: true },
      { name: "dlst", label: "Issuing state", type: "select", options: US_STATES, required: true, half: true },
      { name: "acc", label: "Accidents (last 5 yrs)", type: "select", options: ["0", "1", "2", "3+"], required: true, half: true },
      { name: "viol", label: "Moving violations (last 5 yrs)", type: "select", options: ["0", "1", "2", "3+"], required: true, half: true },
      { name: "claims", label: "Claims (last 3–5 yrs)", type: "select", options: ["0", "1", "2", "3+"], required: true, half: true },
    ] }] },
  vehicle: { key: "vehicles", title: "Which vehicles should we cover?", short: "Vehicles", sub: "Your VIN is on your registration or the driver-side dashboard.", fields: [
    { name: "vehicles", label: "Vehicle", type: "group", repeat: true, fields: [
      { name: "year", label: "Year", type: "number", required: true, half: true, min: 1950, max: 2027 },
      { name: "make", label: "Make", required: true, half: true, placeholder: "e.g. Toyota" },
      { name: "model", label: "Model", required: true, half: true, placeholder: "e.g. RAV4" },
      { name: "vin", label: "VIN", half: true, placeholder: "17 characters", hint: "Optional; pre-1981 vehicles may have a shorter VIN", validate: (v, state, path) => { const year = Number(state.vehicles?.[Number(path.split('.')[1])]?.year); return year > 0 && year < 1981 ? /^[a-z0-9]{1,17}$/i.test(v) : /^[a-hj-npr-z0-9]{17}$/i.test(v); }, err: "Enter a valid VIN (17 characters for vehicles from 1981 onward; no I, O or Q)." },
      { name: "own", label: "Ownership", type: "chips", options: ["Owned", "Financed", "Leased"], required: true },
      { name: "use", label: "Primary use", type: "chips", options: ["Commute", "Business", "Pleasure"], required: true },
      { name: "miles", label: "Estimated annual mileage", type: "select", options: ["Under 5,000", "5,000–10,000", "10,000–15,000", "15,000–20,000", "20,000+"], required: true },
    ] }] },
  home: { key: "property", title: "Tell us about the home.", short: "Property", fields: [
    { name: "paddr", label: "Property address", required: true, placeholder: "Street, city, state" },
    { name: "occ", label: "Occupancy", type: "chips", options: ["Owner occupied", "Rental", "Secondary home"], required: true },
    { name: "built", label: "Year built", type: "number", required: true, half: true, min: 1800, max: 2027 },
    { name: "sqft", label: "Square footage", type: "number", min: 100, max: 100000, required: true, half: true },
    { name: "beds", label: "Bedrooms", type: "select", options: ["1", "2", "3", "4", "5", "6+"], required: true, half: true },
    { name: "baths", label: "Bathrooms", type: "select", options: ["1", "1.5", "2", "2.5", "3", "3.5", "4+"], required: true, half: true },
    { name: "constr", label: "Construction type", type: "select", options: ["Frame", "Masonry", "Brick veneer", "Stucco", "Log", "Other"], required: true, half: true },
    { name: "roof", label: "Roof type", type: "select", options: ["Asphalt shingle", "Metal", "Tile", "Slate", "Flat / membrane", "Other"], required: true, half: true },
    { name: "roofage", label: "Roof age (years)", type: "number", min: 0, max: 200, required: true, half: true },
    { name: "garage", label: "Garage", type: "select", options: ["Attached", "Detached", "Built-in", "Carport", "None"], half: true },
    { name: "value", label: "Estimated replacement value", type: "select", options: ["Under $150k", "$150k–$300k", "$300k–$500k", "$500k–$750k", "$750k–$1M", "Over $1M"], required: true },
  ] },
  rental: { key: "rental", title: "Tell us about your rental.", short: "Rental", fields: [
    { name: "raddr", label: "Rental address", required: true },
    { name: "dwell", label: "Dwelling type", type: "chips", options: ["Apartment", "Multi-family", "Single-family"], required: true },
    { name: "occupants", label: "Number of occupants", type: "select", options: ["1", "2", "3", "4", "5+"], required: true, half: true },
    { name: "length", label: "Length of residency", type: "select", options: ["New move-in", "Under 1 year", "1–3 years", "3+ years"], required: true, half: true },
    { name: "ppv", label: "Personal property value", type: "select", options: ["$10k", "$20k", "$30k", "$50k", "$75k+"], required: true, half: true },
    { name: "liab", label: "Desired liability limit", type: "select", options: ["$100k", "$300k", "$500k"], required: true, half: true },
  ] },
  condo: { key: "unit", title: "Tell us about your unit.", short: "Unit", fields: [
    { name: "caddr", label: "Condo address", required: true },
    { name: "unit", label: "Unit / floor", required: true, half: true },
    { name: "built", label: "Year built", type: "number", min: 1800, max: new Date().getFullYear() + 1, required: true, half: true },
    { name: "sqft", label: "Square footage", type: "number", min: 100, max: 100000, required: true, half: true },
    { name: "occ", label: "Occupancy", type: "select", options: ["Owner occupied", "Rented out", "Secondary home"], required: true, half: true },
    { name: "beds", label: "Bedrooms", type: "select", options: ["Studio", "1", "2", "3", "4+"], required: true, half: true },
    { name: "baths", label: "Bathrooms", type: "select", options: ["1", "1.5", "2", "2.5", "3+"], required: true, half: true },
  ] },
  condoCov: { key: "ccov", title: "How much coverage do you want?", short: "Coverage", fields: [
    { name: "ppv", label: "Personal property value", type: "select", options: ["$25k", "$50k", "$75k", "$100k", "$150k+"], required: true, half: true },
    { name: "addv", label: "Additions & alterations value", type: "select", options: ["$10k", "$25k", "$50k", "$100k+"], required: true, half: true },
    { name: "liab", label: "Desired liability limit", type: "select", options: ["$100k", "$300k", "$500k"], required: true },
  ] },
  lifeApp: { key: "applicant", title: "Tell us about the applicant.", short: "Applicant", fields: [
    { name: "aname", label: "Applicant full name", required: true },
    { name: "gender", label: "Gender", type: "chips", options: ["Female", "Male", "Prefer not to say"], required: true },
    { name: "adob", label: "Date of birth", type: "date", required: true, half: true, max: birthDateLimit, err: "Date of birth must be before today." },
  ] },
  lifeCov: { key: "lcov", title: "What coverage are you considering?", short: "Coverage", fields: [
    { name: "amount", label: "Coverage amount", type: "select", options: ["$100,000", "$250,000", "$500,000", "$750,000", "$1,000,000", "$2,000,000+"], required: true, half: true },
    { name: "ptype", label: "Policy type", type: "chips", options: ["Term", "Whole Life", "Universal", "Not sure"], required: true },
    { name: "dur", label: "Desired duration", type: "select", options: ["10 years", "15 years", "20 years", "30 years", "Lifetime"], required: true, half: true, show: s => s.ptype !== "Whole Life" },
  ] },
  lifeUw: { key: "uw", title: "A few lifestyle questions.", short: "Lifestyle", sub: "Basic answers only — an agent will follow up privately if more detail is needed.", fields: [
    { name: "occupation", label: "Occupation", required: true, half: true },
    { name: "hazard", label: "Hazardous duties at work?", type: "chips", options: ["Yes", "No"], required: true },
    { name: "tobacco", label: "Tobacco or nicotine use in the last 12 months?", type: "chips", options: ["Yes", "No"], required: true },
    { name: "health", label: "Any major pre-existing health conditions?", type: "chips", options: ["Yes", "No", "Prefer to discuss"], required: true },
    { name: "existing", label: "Existing life insurance?", type: "chips", options: ["Yes", "No"], required: true },
    { name: "existingAmt", label: "Existing coverage amount", half: true, show: yes("existing") },
  ] },
  biz: { key: "biz", title: "Tell us about the business.", short: "Business", fields: [
    { name: "legal", label: "Legal business name", required: true },
    { name: "entity", label: "Entity type", type: "select", options: ["LLC", "Corporation", "S-Corporation", "Sole Proprietorship", "Partnership", "Non-profit", "Other"], required: true, half: true },
    { name: "industry", label: "Industry / NAICS / SIC", required: true, half: true, placeholder: "e.g. Restaurant, 722511" },
    { name: "baddr", label: "Business address", required: true },
    { name: "years", label: "Years in business", type: "select", options: ["New / startup", "1–2", "3–5", "6–10", "10+"], required: true, half: true },
    { name: "revenue", label: "Annual revenue", type: "select", options: ["Under $250k", "$250k–$1M", "$1M–$5M", "$5M–$20M", "$20M+"], required: true, half: true },
    { name: "emp", label: "Employee count", type: "select", options: ["Just me", "2–10", "11–50", "51–200", "200+"], required: true, half: true },
  ] },
  ops: { key: "ops", title: "How does the business operate?", short: "Operations", fields: [
    { name: "activities", label: "Describe your business activities", type: "textarea", required: true },
    { name: "locations", label: "Number of locations", type: "select", options: ["1", "2–3", "4–10", "10+"], required: true, half: true },
    { name: "property", label: "Own or lease your space?", type: "select", options: ["Own", "Lease", "Home-based", "No fixed location"], required: true, half: true },
    { name: "equip", label: "Equipment value", type: "select", options: ["Under $25k", "$25k–$100k", "$100k–$500k", "$500k+"], half: true },
    { name: "vehicles", label: "Commercial vehicles", type: "select", options: ["None", "1", "2–5", "6–20", "20+"], half: true },
  ] },
  bizCov: { key: "bcov", title: "Which coverage do you need?", short: "Coverage", sub: "Select all that apply.", fields: [
    { name: "cov", label: "Coverage", type: "multi", options: ["General Liability", "Commercial Property", "Commercial Auto", "Workers' Compensation", "Cyber / Professional Liability", "Not sure — advise me"], required: true },
  ] },
};
const contactStep = (title) => ({ key: "contact", title: title || "How can we reach you?", short: "Contact details", fields: [
  F.first, F.last, F.email, F.phone, F.pref, F.street, F.city, F.state, { ...F.zip, name: "zip" },
  { name: "notes", label: "Notes / special requirements", type: "textarea", placeholder: "Anything else we should know?" },
] });
const FLOWS = {
  auto: () => [STEPS.driver, STEPS.vehicle, prior(true), contactStep()],
  home: () => [STEPS.home, prior(), contactStep()],
  renters: () => [STEPS.rental, prior(), contactStep()],
  "auto-home": () => [contactStep("Let's start with you."), STEPS.home, STEPS.driver, STEPS.vehicle, prior(true)],
  "auto-renters": () => [contactStep("Let's start with you."), STEPS.rental, STEPS.driver, STEPS.vehicle, prior(true)],
  life: () => [STEPS.lifeApp, STEPS.lifeCov, STEPS.lifeUw, contactStep()],
  business: () => [STEPS.biz, STEPS.ops, STEPS.bizCov, contactStep("Who should we contact?")],
  condo: () => [STEPS.condo, STEPS.condoCov, prior(), contactStep()],
};
const TYPE_BY_LABEL = Object.fromEntries(Object.entries(QUOTE_TYPES).map(([k, v]) => [v, k]));

document.addEventListener("DOMContentLoaded", () => {
  const q = new URLSearchParams(location.search), pre = q.get("type");
  const root = document.getElementById("quoteForm");
  const state = QUOTE_DRAFT ? { ...QUOTE_DRAFT.state } : {};
  if (!QUOTE_DRAFT) {
    if (pre && QUOTE_TYPES[pre]) state.type = QUOTE_TYPES[pre];
    ["zip", "first", "last", "email", "phone"].forEach(k => q.get(k) && (state[k] = q.get(k)));
  }
  const typeKey = s => TYPE_BY_LABEL[s.type];
  const sf = new StepForm(root, {
    state, start: QUOTE_DRAFT ? QUOTE_DRAFT.step : state.type ? 1 : 0,
    getSteps: s => [
      { title: "What are you looking to insure?", short: "Insurance type", sub: "One form handles every quote — the questions adapt to what you choose.", fields: [{ name: "type", label: "Insurance type", type: "chips", options: Object.values(QUOTE_TYPES), required: true, err: "Choose an insurance type." }] },
      { title: "Where will the coverage be?", short: "Location", sub: "Coverage and pricing vary by state.", fields: [{ ...F.zip, name: "zip", half: false }] },
      ...(s.type ? FLOWS[typeKey(s)]() : []),
    ],
    action: document.getElementById("quoteForm").dataset.action, submitLabel: "Submit quote request",
    terms: "I confirm the information is accurate and agree that COSPARK Insurance Group may contact me about this quote by phone, email or text. Consent is not a condition of purchase.",
    summary: [
      ["#s_type", s => s.type], ["#s_zip", s => s.zip], ["#s_name", s => [s.first, s.last].filter(Boolean).join(" ")],
      ["#s_email", s => s.email], ["#s_phone", s => s.phone],
      ["#s_detail", s => { const d = []; if (s.drivers?.length) d.push(`${s.drivers.length} driver${s.drivers.length > 1 ? "s" : ""}`); if (s.vehicles?.length && Array.isArray(s.vehicles)) d.push(`${s.vehicles.length} vehicle${s.vehicles.length > 1 ? "s" : ""}`); if (s.amount) d.push(s.amount); if (s.legal) d.push(s.legal); if (s.value) d.push(s.value); return d.join(" · "); }],
    ],
    confirm: (s, id) => `<div class="confirm"><div class="confirm-mark">✓</div>
      <h2 class="step-title">Quote request received.</h2>
      <p class="muted">Thanks, ${s.first}. We sent a confirmation to ${s.email}${s.phone ? " and a text to " + s.phone : ""}.</p>
      <div class="ref-id">${id}</div>
      <ul class="check-list" style="text-align:left;max-width:440px;margin:10px auto 28px"><li>An agent reviews your ${s.type.toLowerCase()} details and compares carriers.</li><li>We contact you by ${(s.pref || "email").toLowerCase()} within one business day.</li><li>Track this quote and earn Insurance Tokens once your policy is active.</li></ul>
      <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">${root.dataset.dashboard ? `<a href="${root.dataset.dashboard}#policies" class="btn btn-copper">View in my dashboard →</a>` : `<a href="/register?email=${encodeURIComponent(s.email)}" class="btn btn-copper">Create account to track →</a>`}<button class="btn btn-line" data-appt>Schedule a call</button></div></div>`,
  });
});
