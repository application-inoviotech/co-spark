<script setup>
import { reactive, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Cell from '@/Components/Cell.vue';

const props = defineProps({ resource: Object, columns: Array, filters: Object, query: Object, rows: Object });
const state = reactive({ q: props.query.q ?? '', ...Object.fromEntries(Object.keys(props.filters).map(k => [k, props.query[k] ?? ''])) });
let timer;
watch(state, () => {
  clearTimeout(timer);
  timer = setTimeout(() => router.get(route('admin.resources.index', props.resource.key), state, { preserveState: true, replace: true }), 300);
});
</script>

<template>
  <Head :title="resource.label" />
  <Teleport defer to="#pageTitle">{{ resource.label }}</Teleport>
  <section class="box">
    <div class="box-h" style="flex-wrap:wrap;gap:10px">
      <div style="display:flex;gap:10px;flex-wrap:wrap;flex:1">
        <div class="field" style="max-width:280px;flex:1"><input v-model="state.q" type="search" placeholder="Search…" aria-label="Search"></div>
        <div v-for="(filter, key) in filters" :key="key" class="field" style="max-width:220px">
          <select v-model="state[key]" :aria-label="filter.label">
            <option value="">{{ filter.label }}: All</option>
            <option v-for="(text, value) in filter.options" :key="value" :value="value">{{ text }}</option>
          </select>
        </div>
      </div>
      <Link v-if="resource.creatable" :href="route('admin.resources.create', resource.key)" class="btn btn-dark btn-sm">+ New {{ resource.singular.toLowerCase() }}</Link>
    </div>
    <div class="tbl-wrap">
      <table class="tbl">
        <thead><tr><th v-for="c in columns" :key="c.key">{{ c.label }}</th></tr></thead>
        <tbody>
          <tr v-for="row in rows.data" :key="row.id" style="cursor:pointer" @click="router.visit(route('admin.resources.show', [resource.key, row.id]))">
            <td v-for="c in columns" :key="c.key"><Cell :value="row[c.key]" :type="c.type" /></td>
          </tr>
          <tr v-if="!rows.data.length"><td :colspan="columns.length" class="empty">Nothing found.</td></tr>
        </tbody>
      </table>
    </div>
    <div class="row-between" style="margin-top:16px;flex-wrap:wrap;gap:10px">
      <span class="mono muted" style="font-size:10px">{{ rows.total }} records · page {{ rows.current_page }} of {{ rows.last_page }}</span>
      <div style="display:flex;gap:6px">
        <Link v-if="rows.prev_page_url" :href="rows.prev_page_url" class="btn btn-line btn-sm" preserve-scroll>← Previous</Link>
        <Link v-if="rows.next_page_url" :href="rows.next_page_url" class="btn btn-line btn-sm" preserve-scroll>Next →</Link>
      </div>
    </div>
  </section>
</template>
