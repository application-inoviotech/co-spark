<script setup>
import { reactive, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Cell from '@/Components/Cell.vue';
import AdminIcon from '@/Components/AdminIcon.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

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
  <section class="box admin-panel">
    <div class="record-toolbar">
      <div class="record-filters">
        <div class="field record-search">
          <AdminIcon name="search" />
          <input v-model="state.q" type="search" placeholder="Search…" aria-label="Search">
        </div>
        <SearchableSelect
          v-for="(filter, key) in filters" :key="key" v-model="state[key]"
          class="record-filter" :label="filter.label" :options="filter.options" filter
        />
      </div>
      <Link v-if="resource.creatable" :href="route('admin.resources.create', resource.key)" class="btn btn-dark">
        <AdminIcon name="plus" />New {{ resource.singular.toLowerCase() }}
      </Link>
    </div>
    <div class="tbl-wrap record-table">
      <table class="tbl">
        <thead><tr><th v-for="c in columns" :key="c.key">{{ c.label }}</th></tr></thead>
        <tbody>
          <tr v-for="row in rows.data" :key="row.id" class="record-row" @click="router.visit(route('admin.resources.show', [resource.key, row.id]))">
            <td v-for="c in columns" :key="c.key"><Cell :value="row[c.key]" :type="c.type" /></td>
          </tr>
          <tr v-if="!rows.data.length">
            <td :colspan="columns.length" class="empty"><AdminIcon name="search" class="panel-icon empty-icon" />Nothing found.</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="panel-footer">
      <span class="mono muted">{{ rows.total }} records · page {{ rows.current_page }} of {{ rows.last_page }}</span>
      <div class="panel-actions">
        <Link v-if="rows.prev_page_url" :href="rows.prev_page_url" class="btn btn-line btn-sm" preserve-scroll>
          <AdminIcon name="back" />Previous
        </Link>
        <Link v-if="rows.next_page_url" :href="rows.next_page_url" class="btn btn-line btn-sm" preserve-scroll>
          Next<AdminIcon name="forward" />
        </Link>
      </div>
    </div>
  </section>
</template>
