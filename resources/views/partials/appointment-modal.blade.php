{{-- Appointment popup (SOW: opens as a modal; phone and in-person in one form) --}}
<div class="modal" id="apptModal" role="dialog" aria-modal="true" aria-labelledby="apptTitle" data-action="{{ route('appointments.store') }}" data-office-days="{{ implode(',', config('cospark.appointment_office_days')) }}">
    <div class="modal-bg" data-close></div>
    <div class="modal-box">
      <button class="modal-x" data-close aria-label="Close">×</button>
      <div id="apptForm">
        <span class="eyebrow">Schedule an appointment</span>
        <h2 class="h3s" id="apptTitle" style="margin:14px 0 24px;font-size:36px">Talk to the COSPARK team.</h2>
        <div class="appt-types" style="margin-bottom:22px">
          <button type="button" class="appt-type on" data-atype="Phone call" data-type="phone"><x-icon name="phone" /><b>Call</b><span>We call you at your chosen time.</span></button>
          <button type="button" class="appt-type" data-atype="In person" data-type="in_person"><x-icon name="users" /><b>In person</b><span>Meet at our Rochester, MI office.</span></button>
        </div>
        <div class="form-grid">
          <div class="field half"><label>Preferred date <span class="req">*</span></label><input type="date" name="a_date" required><span class="err-msg">Choose a date.</span></div>
          <div class="field half"><label>Preferred time <span class="req">*</span></label><select name="a_time" required><option value="">Select a time</option>@foreach ($times as $time)<option>{{ $time }}</option>@endforeach</select><span class="hint" id="apptClosed" hidden></span><span class="err-msg">Choose a time.</span></div>
          <div class="field"><label>Full name <span class="req">*</span></label><input name="a_name" required autocomplete="name" value="{{ auth()->user()?->name }}"><span class="err-msg">Enter your name.</span></div>
          <div class="field half"><label>Email <span class="req">*</span></label><input type="email" name="a_email" required autocomplete="email" value="{{ auth()->user()?->email }}"><span class="err-msg">Enter a valid email.</span></div>
          <div class="field half"><label>Phone <span class="req">*</span></label><input type="tel" name="a_phone" required autocomplete="tel" value="{{ auth()->user()?->phone }}"><span class="err-msg">Enter a phone number.</span></div>
          <div class="field"><label>Reason</label><select name="a_reason">@foreach ($reasons as $reason)<option>{{ $reason }}</option>@endforeach</select></div>
          <div class="field"><label>Message</label><textarea name="a_msg" placeholder="Anything we should know before we talk?"></textarea></div>
        </div>
        <div class="appt-foot"><span class="mono muted" id="apptSum">Call · choose a date</span><button class="btn btn-dark" id="apptSubmit">Confirm appointment →</button></div>
      </div>
      <div id="apptDone" class="confirm" hidden>
        <div class="confirm-mark">✓</div>
        <h2 class="h3s">Appointment requested.</h2>
        <p class="muted" id="apptDoneTxt"></p>
        <div class="ref-id" id="apptRef"></div>
        <p class="small muted">A confirmation has been sent by email and text.</p>
        <button class="btn btn-line" data-close>Done</button>
      </div>
    </div></div>
  <div class="toast" id="toast" role="status"></div>
