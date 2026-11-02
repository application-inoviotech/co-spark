<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const page = usePage();
const open = ref(false);
const toast = ref('');
const toastUrl = ref(null);
let toastTimer;
const showToast = (msg, url = null, ms = 3200) => { toast.value = msg; toastUrl.value = url; clearTimeout(toastTimer); toastTimer = setTimeout(() => { toast.value = ''; toastUrl.value = null; }, ms); };
const flash = computed(() => page.props.flash?.success || page.props.flash?.error);
watch(flash, msg => { if (msg) showToast(msg); }, { immediate: true });

// Live alerts pushed by the admin root view (new quotes, claims, applications…); click to open the section
const onAlert = e => showToast(`${e.detail.title} · ${e.detail.summary}`, e.detail.url, 8000);
onMounted(() => {
  addEventListener('cospark:alert', onAlert);
  // Subscribe once per page load (the layout remounts on every Inertia visit)
  if (window.liveChannel && !window.adminAlertsBound) {
    window.adminAlertsBound = true;
    window.liveChannel('alert', alert => { window.chime(); dispatchEvent(new CustomEvent('cospark:alert', { detail: alert })); });
  }
});
onBeforeUnmount(() => removeEventListener('cospark:alert', onAlert));
const openToast = () => { if (toastUrl.value) { router.visit(toastUrl.value); toast.value = ''; toastUrl.value = null; } };
router.on('navigate', () => (open.value = false));

// Page loader: fade the content and show a spinner while an Inertia visit is loading (quick visits don't flash)
const loading = ref(false);
let loadTimer, offStart, offFinish;
onMounted(() => {
  offStart = router.on('start', () => { clearTimeout(loadTimer); loadTimer = setTimeout(() => (loading.value = true), 120); });
  offFinish = router.on('finish', () => { clearTimeout(loadTimer); loading.value = false; });
});
onBeforeUnmount(() => { offStart?.(); offFinish?.(); clearTimeout(loadTimer); });
const isActive = href => location.href.split('?')[0].startsWith(href);
const logout = () => router.post(page.props.site.logout);
</script>

<template>
  <div class="dash">
    <aside class="dash-side" :class="{ open }">
      <button type="button" class="dash-close" aria-label="Close menu" @click="open = false">&times;</button>
      <Link :href="route('admin.dashboard')" class="logo" style="color:#fff">
        <svg viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M16 2.5 4.5 7v8.2c0 7 4.9 12.4 11.5 14.3 6.6-1.9 11.5-7.3 11.5-14.3V7L16 2.5Z" stroke="currentColor" stroke-width="1.4"/><path d="m16 10 2 4 4 2-4 2-2 4-2-4-4-2 4-2 2-4Z" fill="#C8753D"/></svg>
        <span class="logo-t"><b>COSPARK</b><span>ADMIN PORTAL</span></span>
      </Link>
      <nav class="dash-nav" aria-label="Admin">
        <Link :href="route('admin.dashboard')" :class="{ on: route().current('admin.dashboard') }">Dashboard</Link>
        <Link :href="route('admin.qr.scan')" :class="{ on: route().current('admin.qr.scan') }">QR scan & redeem</Link>
        <template v-for="(items, group) in page.props.nav" :key="group">
          <h6>{{ group }}</h6>
          <Link v-for="item in items" :key="item.key" :href="item.href" :class="{ on: isActive(item.href) }">{{ item.label }}</Link>
        </template>
        <h6>System</h6>
        <Link :href="route('admin.reports')" :class="{ on: route().current('admin.reports') }">Reports</Link>
        <Link :href="route('admin.settings')" :class="{ on: route().current('admin.settings') }">Settings</Link>
      </nav>
      <div class="dash-user">
        <span class="avatar">{{ page.props.auth.name?.split(' ').map(p => p[0]).join('').slice(0, 2) }}</span>
        <div><div style="color:#fff">{{ page.props.auth.name }}</div><a :href="page.props.site.home" class="mono" style="font-size:9px;color:var(--copper-2)">View website</a></div>
        <button type="button" class="out" aria-label="Sign out" title="Sign out" @click="logout"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H4V3h5M16 17l5-5-5-5M21 12H9"/></svg></button>
      </div>
    </aside>
    <div class="dash-backdrop" :class="{ on: open }" @click="open = false"></div>
    <div class="dash-main">
      <header class="dash-top">
        <button class="icon-btn mob-dash-toggle" aria-label="Open menu" @click="open = !open">☰</button>
        <div><span class="mono muted" style="font-size:9.5px">Admin portal</span><h1 id="pageTitle"></h1></div>
        <div class="right"><button type="button" class="btn btn-line btn-sm top-out" aria-label="Sign out" @click="logout"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H4V3h5M16 17l5-5-5-5M21 12H9"/></svg><span>Sign out</span></button></div>
      </header>
      <div class="dash-body" :class="{ 'is-loading': loading }" :aria-busy="loading"><slot /></div>
      <div v-if="loading" class="admin-loader" role="status" aria-label="Loading"><span></span></div>
    </div>
    <div class="toast" :class="{ show: toast }" role="status" :style="toastUrl ? 'cursor:pointer;pointer-events:auto' : ''" @click="openToast">{{ toast }}</div>
  </div>
</template>

<style>
/* Page loader (see `loading` above): the current page fades and a spinner shows until the next one arrives */
.dash-body { transition: opacity .2s ease; }
.dash-body.is-loading { opacity: .35; pointer-events: none; }
.admin-loader { position: fixed; inset: 0; display: grid; place-items: center; pointer-events: none; z-index: 40; }
.admin-loader span { width: 38px; height: 38px; border-radius: 50%; border: 3px solid rgba(200, 117, 61, .25); border-top-color: #C8753D; animation: admin-spin .7s linear infinite; }
@keyframes admin-spin { to { transform: rotate(360deg); } }
/* Inertia's top progress bar, thicker so it's noticeable */
#nprogress .bar { height: 4px !important; }
</style>
