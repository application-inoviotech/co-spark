<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminIcon from '../Components/AdminIcon.vue';

defineProps({ kpis: Array, recentLeads: Array, upcoming: Array, system: Object });
const busy = ref(false);
const run = name => { busy.value = true; router.post(route(name), {}, { preserveScroll: true, onFinish: () => (busy.value = false) }); };

const metricIcons = {
  'Total members': 'user',
  'Active members': 'user',
  'Free · Associate · Premium': 'membership-plan',
  'Active policies': 'policy',
  'Policies expiring (30 days)': 'clock',
  'Policies pending renewal (90 days)': 'clock',
  'Token balances (DT · IT · VT)': 'token-transaction',
  'Pending token transfers': 'token-transfer',
  'Pending token redemptions': 'token-transaction',
  'Dues collected (this month)': 'payment',
  'Failed payments': 'payment',
  'Refunds (30 days)': 'refund',
  'Pending cancellations': 'membership',
  'Producer applications': 'producer-application',
  'Active producers': 'producer',
  'Pending appointments': 'appointment',
  'New leads / quotes': 'lead',
  'Vacation requests': 'vacation-request',
  'QR redemptions (30 days)': 'qr-code',
  'Partner programs': 'partner',
};
</script>

<template>
  <Head title="Dashboard" />
  <Teleport defer to="#pageTitle">Dashboard</Teleport>
  <section class="dashboard-overview" aria-labelledby="overview-title">
    <header class="overview-header">
      <h2 id="overview-title">Operations overview</h2>
      <p>Membership, policies, payments and service activity at a glance.</p>
      <svg class="overview-mark" viewBox="0 0 32 32" fill="none" aria-hidden="true">
        <path d="M16 2.5 4.5 7v8.2c0 7 4.9 12.4 11.5 14.3 6.6-1.9 11.5-7.3 11.5-14.3V7L16 2.5Z" stroke="currentColor" stroke-width=".45"/>
        <path d="m16 10 2 4 4 2-4 2-2 4-2-4-4-2 4-2 2-4Z" fill="currentColor"/>
      </svg>
    </header>
    <div class="kpis">
      <component :is="k.href ? Link : 'div'" v-for="k in kpis" :key="k.label" :href="k.href" class="kpi">
        <div class="kpi-heading">
          <span class="kpi-label">{{ k.label }}</span>
          <AdminIcon class="dashboard-icon" :name="metricIcons[k.label] ?? 'reports'" />
        </div>
        <div class="v" :class="{ 'kpi-breakdown': typeof k.value === 'string' && k.value.includes(' · ') }">{{ k.value }}</div>
      </component>
    </div>
  </section>
  <div class="dash-grid dashboard-activity">
    <section class="box">
      <div class="box-h">
        <h3><AdminIcon name="lead" class="dashboard-icon" />New leads & quotes</h3>
        <Link :href="route('admin.resources.index', 'lead')" class="btn btn-line btn-sm">
          View all<AdminIcon name="forward" />
        </Link>
      </div>
      <Link v-for="l in recentLeads" :key="l[0]" :href="l[4]" class="list-row"><div><div class="t">{{ l[1] }}</div><div class="s">{{ l[0] }} · {{ l[2] }}</div></div><div class="r s">{{ l[3] }}</div></Link>
      <p v-if="!recentLeads.length" class="empty">No leads yet.</p>
    </section>
    <section class="box">
      <div class="box-h">
        <h3><AdminIcon name="appointment" class="dashboard-icon" />Upcoming appointments</h3>
        <Link :href="route('admin.resources.index', 'appointment')" class="btn btn-line btn-sm">
          View all<AdminIcon name="forward" />
        </Link>
      </div>
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
