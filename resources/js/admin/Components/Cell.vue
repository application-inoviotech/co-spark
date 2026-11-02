<script setup>
/* Table / detail value: plain text, {href, label} link, or a status pill */
const props = defineProps({ value: [String, Number, Object, Boolean, null], type: String });
const pillColor = s => /active|succeeded|success|paid|confirmed|completed|approved|verified|handled|yes|credited|posted|won/i.test(s) ? 'green'
  : /fail|declin|reject|cancel|expired|forfeit|suspend|denied|lost|inactive|refund/i.test(s) ? 'red'
  : /pending|review|new|requested|due|progress|locked|info|awaiting|contacted|quoted/i.test(s) ? 'amber' : 'grey';
</script>

<template>
  <a v-if="value && typeof value === 'object'" :href="value.href" class="link-arrow" target="_blank" rel="noopener">{{ value.label }}</a>
  <span v-else-if="type === 'pill' && value" class="pill" :class="pillColor(value)">{{ value }}</span>
  <span v-else-if="value === null || value === ''" class="muted">—</span>
  <span v-else style="white-space:pre-line">{{ value }}</span>
</template>
