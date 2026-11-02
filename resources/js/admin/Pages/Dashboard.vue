<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ kpis: Array, recentLeads: Array, upcoming: Array, system: Object });
const busy = ref(false);
const run = name => { busy.value = true; router.post(route(name), {}, { preserveScroll: true, onFinish: () => (busy.value = false) }); };
</script>

<template>
  <Head title="Dashboard" />
  <Teleport defer to="#pageTitle">Dashboard</Teleport>
  <div class="kpis" style="grid-template-columns:repeat(auto-fill,minmax(210px,1fr))">
    <component :is="k.href ? Link : 'div'" v-for="k in kpis" :key="k.label" :href="k.href" class="kpi">
      <span class="mono muted">{{ k.label }}</span>
      <div class="v" style="font-size:30px">{{ k.value }}</div>
    </component>
  </div>
  <div class="dash-grid">
    <section class="box">
      <div class="box-h"><h3>New leads & quotes</h3><Link :href="route('admin.resources.index', 'lead')" class="link-arrow">All</Link></div>
      <Link v-for="l in recentLeads" :key="l[0]" :href="l[4]" class="list-row"><div><div class="t">{{ l[1] }}</div><div class="s">{{ l[0] }} · {{ l[2] }}</div></div><div class="r s">{{ l[3] }}</div></Link>
      <p v-if="!recentLeads.length" class="empty">No leads yet.</p>
    </section>
    <section class="box">
      <div class="box-h"><h3>Upcoming appointments</h3><Link :href="route('admin.resources.index', 'appointment')" class="link-arrow">All</Link></div>
      <Link v-for="a in upcoming" :key="a[4]" :href="a[4]" class="list-row"><div><div class="t">{{ a[1] }}</div><div class="s">{{ a[0] }} · {{ a[2] }}</div></div><div class="r s">{{ a[3] }}</div></Link>
      <p v-if="!upcoming.length" class="empty">Nothing scheduled.</p>
    </section>
  </div>
  <!-- System status card hidden for now; the data is still sent as the "system" prop. Remove this comment to show it again.
  <section class="box" style="margin-top:18px">
    <div class="box-h"><h3>System status · emails &amp; texts</h3><span class="pill" :class="system.cronOk && !system.failed ? 'green' : 'amber'">{{ system.cronOk ? (system.failed ? 'Check failures' : 'Running') : 'Cron not running' }}</span></div>
    <dl class="admin-dl">
      <dt>Server cron (schedule:run)</dt><dd>Last run {{ system.cron }}</dd>
      <dt>Queue</dt><dd>{{ system.queue }} · {{ system.pending }} waiting<span v-if="system.oldest"> (oldest {{ system.oldest }})</span></dd>
      <dt>Failed</dt><dd>{{ system.failed }}<span v-if="system.lastFailure" class="muted small"> · {{ system.lastFailure }}</span></dd>
      <dt>Email delivery</dt><dd>{{ system.mail }}</dd>
      <dt>Klaviyo last error</dt><dd :class="{ muted: !system.klaviyoError }">{{ system.klaviyoError || 'None in the last 7 days' }}</dd>
    </dl>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px">
      <button class="btn btn-dark btn-sm" :disabled="busy || !system.pending" @click="run('admin.system.queue')">Send queued now</button>
      <button class="btn btn-line btn-sm" :disabled="busy || !system.failed" @click="run('admin.system.retry')">Retry failed</button>
    </div>
  </section>
  -->
</template>
