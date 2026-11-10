<script setup>
import { Head } from '@inertiajs/vue3';
import AdminIcon from '@/Components/AdminIcon.vue';
import PanelHeading from '@/Components/PanelHeading.vue';

defineProps({ reports: Array });
</script>

<template>
  <Head title="Reports" />
  <Teleport defer to="#pageTitle">Reports</Teleport>
  <section class="box admin-panel">
    <PanelHeading icon="reports" title="CSV exports" description="Open downloads in Excel or Google Sheets." />
    <a v-for="r in reports" :key="r.href" :href="r.href" class="report-row">
      <span class="report-name">
        <AdminIcon name="page" class="panel-icon" />
        {{ r.label }}
      </span>
      <span class="btn btn-line btn-sm report-download">
        <AdminIcon name="download" />
        Download CSV
      </span>
    </a>
  </section>
</template>
