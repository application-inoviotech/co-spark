<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({ reports: Array });
</script>

<template>
  <Head title="Reports" />
  <Teleport defer to="#pageTitle">Reports</Teleport>
  <section class="box" style="max-width:720px">
    <p class="muted small" style="margin-top:0">CSV exports open in Excel or Google Sheets.</p>
    <a v-for="r in reports" :key="r.href" :href="r.href" class="list-row"><div class="t">{{ r.label }}</div><div class="r link-arrow">Download CSV</div></a>
  </section>
</template>
