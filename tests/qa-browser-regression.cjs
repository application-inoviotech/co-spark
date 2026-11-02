const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const read = path => fs.readFileSync(`public/assets/js/${path}`, 'utf8');
function context() {
  const ctx = vm.createContext({document: {addEventListener() {}}, window: {}, Date, URLSearchParams, console, US_STATES: ['MI'], QUOTE_TYPES: {auto:'Auto'}});
  vm.runInContext(read('main.js') + '\nthis.phone = validPhone; this.simpleValidate = validate;', ctx);
  vm.runInContext(read('forms.js') + '\nthis.Form = StepForm; this.fields = F;', ctx);
  vm.runInContext(read('quote.js') + '\nthis.quoteSteps = STEPS; this.quoteFlows = FLOWS;', ctx);
  return ctx;
}
function validate(ctx, fields, state) {
  const form = Object.create(ctx.Form.prototype);
  form.o = {getSteps: () => [{fields}]}; form.state = state; form.i = 0;
  form.root = {querySelector: () => null};
  return form.validateStep();
}
test('shared telephone rule rejects short, zero, malformed and oversized numbers', () => {
  const ctx = context();
  for (const v of ['000', '0000000000', 'abc2025550100', '1'.repeat(16)]) assert.equal(ctx.phone(v), false, v);
  for (const v of ['2025550100', '+1 (202) 555-0100', '+44 20 7946 0958']) assert.equal(ctx.phone(v), true, v);
  assert.equal(validate(ctx, [ctx.fields.phone], {phone: '000'}), false);
});
test('step engine enforces numeric/date bounds, finite input and trimmed required strings', () => {
  const ctx = context();
  for (const value of ['-1', '101', 'NaN', 'Infinity']) assert.equal(validate(ctx, [{name:'n',type:'number',min:0,max:100,required:true}], {n:value}), false, value);
  assert.equal(validate(ctx, [{name:'n',type:'number',min:0,max:100}], {n:'0'}), true);
  assert.equal(validate(ctx, [{name:'date',type:'date',max:'2026-10-20'}], {date:'2030-01-01'}), false);
  assert.equal(validate(ctx, [{name:'date',type:'date',max:'2026-10-20'}], {date:'2026-10-20'}), true);
  assert.equal(validate(ctx, [{name:'name',required:true}], {name:'  '}), false);
});
test('actual quote DOB and VIN configurations block bad values in repeated groups', () => {
  const ctx = context();
  const dob = ctx.quoteSteps.driver.fields[0].fields.find(f=>f.name==='dob');
  const vin = ctx.quoteSteps.vehicle.fields[0].fields.find(f=>f.name==='vin');
  assert.equal(validate(ctx, [dob], {dob:'2999-01-01'}), false);
  assert.equal(validate(ctx, [dob], {dob:'1990-01-01'}), true);
  const fields = [{name:'vehicles',type:'group',fields:[vin]}];
  assert.equal(validate(ctx, fields, {vehicles:[{year:2020,vin:'1HGCM82633A004352'},{year:2020,vin:'INVALID'}]}), false);
  assert.equal(validate(ctx, fields, {vehicles:[{year:2020,vin:'1HGCM82633A004352'},{year:1970,vin:'CLASSIC123'}]}), true);
  assert.equal(validate(ctx, fields, {vehicles:[{year:2020,vin:''}]}), true);
});
test('registration ZIP gets a meaningful pattern error and optional contact phone is validated', () => {
  const ctx = context();
  let error = ''; let invalid = false;
  const field = {classList:{toggle:(k,v)=>invalid=v},querySelector:()=>({dataset:{},set textContent(v){error=v;}})};
  const zip = {value:'abc', required:true,type:'text',pattern:'\\d{5}',dataset:{validationMessage:'Enter a 5-digit ZIP code.'},closest:()=>field};
  assert.equal(ctx.simpleValidate({querySelectorAll:()=>[zip],querySelector:()=>null}), false);
  assert.equal(error,'Enter a 5-digit ZIP code.'); assert.equal(invalid,true);
  const phone = {...zip,value:'000',required:false,type:'tel',pattern:''};
  assert.equal(ctx.simpleValidate({querySelectorAll:()=>[phone],querySelector:()=>null}), false);
  phone.value=''; assert.equal(ctx.simpleValidate({querySelectorAll:()=>[phone],querySelector:()=>null}), true);
});
test('actual producer licensing configuration checks chronology', () => {
  const ctx = context(); let options;
  ctx.document = {getElementById:()=>({dataset:{}}),addEventListener:(event,fn)=>fn()};
  ctx.captureOptions = opts => options=opts;
  vm.runInContext('StepForm = class {constructor(root, opts){captureOptions(opts);}};',ctx);
  vm.runInContext(read('pages/producer-apply.js'),ctx);
  const state = {lic:'Licensed',issue:'2030-01-01',exp_d:'2020-01-01'};
  const fields = options.getSteps(state).flatMap(step=>step.fields).filter(f=>['issue','exp_d'].includes(f.name));
  assert.equal(validate(ctx,fields,state),false);
  assert.equal(validate(ctx,fields,{lic:'Licensed',issue:'2020-01-01',exp_d:'2030-01-01'}),true);
});
test('membership calculator clamps negative and nonfinite premiums', () => {
  let ready; const elements = {};
  const ctx = vm.createContext({document:{querySelectorAll:()=>[],addEventListener:(event,fn)=>ready=fn,getElementById:id=>elements[id] ||= {value:id==='tier'?'0.05':'-100',addEventListener(){}}},Number,Math});
  vm.runInContext(read('pages/membership.js'),ctx); ready();
  assert.equal(elements.itOut.textContent,'0');
  elements.prem.value = '1000'; ready(); assert.equal(elements.itOut.textContent,'50');
  elements.prem.value = 'Infinity'; ready(); assert.equal(elements.itOut.textContent,'0');
});

test('all eight real quote configurations accept complete fixtures through review', () => {
  const ctx = context();
  for (const [type, build] of Object.entries(ctx.quoteFlows)) {
    const state = {type,zip:'48307',phone:'2025550100',email:'qa@example.invalid',dob:'1990-01-01',adob:'1990-01-01',insured:'No',built:'2000',sqft:'1500',roofage:'5'};
    const steps = build();
    function fill(fields, object) {
      for (const field of fields) {
        if (field.show && !field.show(state)) continue;
        if (field.type === 'group') { object[field.name] = [{}]; fill(field.fields,object[field.name][0]); continue; }
        if (object[field.name] != null || !field.required) continue;
        object[field.name] = field.name === 'dob' || field.name === 'adob' ? '1990-01-01' : field.type === 'email' ? 'qa@example.invalid' : field.type === 'tel' ? '2025550100' : field.type === 'number' ? String(field.min || 100) : field.type === 'date' ? '2026-01-01' : field.type === 'multi' ? [field.options[0]] : field.options ? field.options[0] : 'QA only';
      }
    }
    for (const step of steps) { fill(step.fields,state); assert.equal(validate(ctx,step.fields,state),true,`${type}: ${step.short}`); }
    const form = Object.create(ctx.Form.prototype); form.o = {getSteps:()=>steps}; form.state=state; form.i=steps.length; form.root={innerHTML:'',querySelector:()=>null}; form.updateSummary=()=>{};
    form.renderReview(); assert.match(form.root.innerHTML,/Review &amp; submit|Review & submit/);
  }
});

test('repeatable drivers retain values when adding, removing and navigating back', () => {
  const ctx=context(), form=Object.create(ctx.Form.prototype);
  form.state={drivers:[{name:'QA first',dob:'1990-01-01'}]}; form.i=1; form.o={getSteps:()=>[{fields:[]},{fields:[]}]}; form.render=()=>{}; form.scrollTop=()=>{};
  const click=(dataset,attr)=>form.onClick({target:{closest:()=>({dataset,hasAttribute:key=>key===attr})}});
  click({add:'drivers'}); assert.equal(form.state.drivers.length,2); assert.equal(form.state.drivers[0].name,'QA first');
  form.set('drivers.1.name','QA second'); click({remove:'drivers',n:'0'}); assert.equal(form.state.drivers.length,1); assert.equal(form.state.drivers[0].name,'QA second');
  click({},'data-back'); assert.equal(form.i,0); assert.equal(form.state.drivers[0].name,'QA second');
  click({},'data-next'); assert.equal(form.i,1); assert.equal(form.state.drivers[0].name,'QA second');
});

test('appointment event refreshes real dashboard rendering and excludes declined entries', async () => {
  const events = {}, nodes = {}, warnings = []; let fetches = 0;
  const data = {plan:'free',plans:{free:{n:'Free',fee:0}},user:{first:'QA',last:'Test'},member:{status:'Active',start:'2026-01-01',id:'QA'},policies:[],quotes:[],notes:[],payments:[],claims:[],vacations:[],ledger:[],appts:[
    {date:'2999-01-01',status:'Pending',reason:'QA pending',time:'3:30 PM',type:'Phone'},
    {date:'2999-01-01',status:'Confirmed',reason:'QA confirmed',time:'3:30 PM',type:'Phone'},
    {date:'2999-01-01',status:'Declined',reason:'QA declined',time:'3:30 PM',type:'Phone'},
    {date:'2000-01-01',status:'Confirmed',reason:'QA past',time:'3:30 PM',type:'Phone'},
  ]};
  const ctx = vm.createContext({document:{addEventListener:(event,fn)=>events[event]=fn,querySelector:selector=>nodes[selector] ||= {}},DASH:{data:'/fixture',resources:'/resources',fileClaim:'/claims/file'},fetch:async()=>{fetches++;return {status:200,json:async()=>data};},location:{hash:''},Date,URLSearchParams,ic:()=>'',fmt$:String,toast:msg=>warnings.push(msg)});
  vm.runInContext(read('dashboard.js'),ctx);
  events['cospark:appointment-created'](); await new Promise(setImmediate);
  assert.equal(fetches,1); assert.deepEqual(warnings,[]);
  assert.match(nodes['#view'].innerHTML,/QA pending/); assert.match(nodes['#view'].innerHTML,/QA confirmed/);
  assert.doesNotMatch(nodes['#view'].innerHTML,/QA declined|QA past/);
  data.appts.push({date:'2999-01-02',status:'Pending',reason:'QA new appointment',time:'3:30 PM',type:'Phone'});
  events['cospark:appointment-created'](); await new Promise(setImmediate);
  assert.equal(fetches,2); assert.match(nodes['#view'].innerHTML,/QA new appointment/);
  ctx.location.hash='#policies';
  data.policies.push({id:42,type:'Auto',status:'Active',no:'QA-42',exp:'2999-01-01',paid:true,premium:1000,carrier:'QA'});
  events['cospark:appointment-created'](); await new Promise(setImmediate);
  assert.deepEqual(warnings,[]); assert.match(nodes['#view'].innerHTML,/\/claims\/file\?type=auto&policy=42/);
});

test('payments show no canceled renewal and preserve cents in annual totals', async () => {
  const events = {}, nodes = {};
  const data = {plan:'associate',plans:{associate:{n:'Associate',fee:8.88}},user:{first:'QA',last:'Payment'},member:{status:'Cancels Nov 21',start:'2026-01-01',id:'QA',next:'2026-11-21',cancelled:true,pendingPlan:'free',nextChargeAmount:0,nextChargeDate:null,periodAmount:8.88},policies:[],methods:[],payments:[{ref:'QA-1',date:`${new Date().getFullYear()}-01-01`,status:'Successful',amt:8.88,kind:'Membership dues',desc:'Associate'}],notes:[],claims:[],vacations:[],ledger:[],appts:[]};
  const ctx=vm.createContext({document:{addEventListener:(event,fn)=>events[event]=fn,querySelector:selector=>nodes[selector] ||= {}},DASH:{data:'/fixture',receipt:'/receipt/__REF__'},fetch:async()=>({status:200,json:async()=>data}),location:{hash:'#payments'},Date,URLSearchParams,ic:()=>'',fmt$:n=>`$${Number(n).toFixed(2)}`,toast:()=>{}});
  vm.runInContext(read('dashboard.js'),ctx);
  events['cospark:appointment-created'](); await new Promise(setImmediate);
  assert.match(nodes['#view'].innerHTML,/Next dues<\/span><div class="v">\$0\.00/);
  assert.match(nodes['#view'].innerHTML,/No further dues/);
  assert.match(nodes['#view'].innerHTML,/Paid this year<\/span><div class="v">\$8\.88/);
  data.payments.push({...data.payments[0],ref:'QA-2',amt:25});
  events['cospark:appointment-created'](); await new Promise(setImmediate);
  assert.match(nodes['#view'].innerHTML,/Paid this year<\/span><div class="v">\$33\.88/);
  data.member.cancelled=false; data.member.pendingPlan=null; data.member.nextChargeAmount=8.88; data.member.nextChargeDate='2026-11-21';
  events['cospark:appointment-created'](); await new Promise(setImmediate);
  assert.match(nodes['#view'].innerHTML,/Next dues<\/span><div class="v">\$8\.88/);
  assert.doesNotMatch(nodes['#view'].innerHTML,/No further dues/);
});
