/* COSPARK — live updates over Pusher, with a short chime.
   window.COSPARK_RT (key, cluster, auth, channel) is set by partials/realtime only when Pusher is configured. */
(() => {
  let pusher, audio;

  // Browsers only allow sound after the user has interacted with the page. Keep trying on every gesture
  // (capture phase, so no UI handler can swallow it) until the audio context is running.
  const GESTURES = ["pointerdown", "keydown", "touchstart"];
  const context = () => (audio = audio || new (window.AudioContext || window.webkitAudioContext)());
  let hint;
  const unlock = () => context().resume().then(() => {
    if (audio.state !== "running") return;
    GESTURES.forEach(e => removeEventListener(e, unlock, true));
    if (hint) { hint.remove(); hint = null; }
  }).catch(() => {});
  GESTURES.forEach(e => addEventListener(e, unlock, true));

  // Shown once when an alert arrives before the page has been clicked, since the browser blocks the sound until then
  const soundHint = () => {
    if (hint) return;
    hint = document.createElement("button");
    hint.type = "button";
    hint.textContent = "🔔 Click anywhere to turn on notification sound";
    hint.style.cssText = "position:fixed;top:16px;left:50%;transform:translateX(-50%);max-width:calc(100% - 32px);z-index:200;padding:10px 14px;border:1px solid var(--line);border-radius:4px;background:var(--bg);color:var(--ink);font:500 13px var(--sans);box-shadow:0 6px 24px rgba(10,17,28,.12);cursor:pointer";
    document.body.appendChild(hint);
  };

  // Two-note chime generated in the browser (no audio file to load)
  window.chime = async () => {
    // Any earlier interaction with the page ("sticky activation") is enough to start audio
    if (!audio && !(navigator.userActivation && navigator.userActivation.hasBeenActive)) return soundHint();
    try { await context().resume(); } catch { return soundHint(); }
    if (audio.state !== "running") return soundHint();
    const t = audio.currentTime;
    [880, 1320].forEach((freq, i) => {
      const osc = audio.createOscillator(), gain = audio.createGain(), at = t + i * 0.14;
      osc.type = "sine"; osc.frequency.value = freq;
      gain.gain.setValueAtTime(0.0001, at);
      gain.gain.exponentialRampToValueAtTime(0.25, at + 0.02);
      gain.gain.exponentialRampToValueAtTime(0.0001, at + 0.35);
      osc.connect(gain).connect(audio.destination);
      osc.start(at); osc.stop(at + 0.4);
    });
  };

  // Listen for an event on the page's private channel; every handler shares one connection
  window.liveChannel = (event, onMessage) => {
    const rt = window.COSPARK_RT;
    if (!rt || !window.Pusher) return;
    pusher = pusher || new Pusher(rt.key, { cluster: rt.cluster, channelAuthorization: { endpoint: rt.auth, transport: "ajax", headers: { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content } } });
    (pusher.channel(rt.channel) || pusher.subscribe(rt.channel)).bind(event, onMessage);
  };

  // Member dashboard / producer portal notifications for this portal only
  window.liveNotifications = (portal, onNote) => liveChannel("Illuminate\\Notifications\\Events\\BroadcastNotificationCreated", n => {
    if ((n.portal || "member") !== portal) return;
    onNote(n);
    chime();
  });
})();
