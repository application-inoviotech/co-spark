<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import Cell from '@/Components/Cell.vue';
import Field from '@/Components/Field.vue';

const props = defineProps({ resource: Object, record: Object, sections: Array, actions: Array });
const active = ref(null);
const form = ref(null);

/* Actions without inputs run straight away (after confirmation); others open a small form */
const start = action => {
  if (!action.fields?.length) {
    if (action.confirm && !confirm(action.confirm)) return;
    return router.post(route('admin.resources.action', [props.resource.key, props.record.id, action.key]), {}, { preserveScroll: true });
  }
  active.value = action;
  form.value = useForm(Object.fromEntries(action.fields.map(f => [f.name, f.default ?? ''])));
};
const run = () => {
  if (active.value.confirm && !confirm(active.value.confirm)) return;
  form.value.post(route('admin.resources.action', [props.resource.key, props.record.id, active.value.key]), {
    preserveScroll: true,
    forceFormData: active.value.fields.some(f => f.type === 'file'),
    onSuccess: () => (active.value = null),
  });
};
const destroy = () => confirm(`Delete this ${props.resource.singular.toLowerCase()}?`) && router.delete(route('admin.resources.destroy', [props.resource.key, props.record.id]));
const btn = style => ({ copper: 'btn-copper', dark: 'btn-dark', danger: 'btn-line danger', line: 'btn-line' })[style] ?? 'btn-line';
</script>

<template>
  <Head :title="record.title" />
  <Teleport defer to="#pageTitle">{{ record.title }}</Teleport>
  <div class="row-between" style="margin-bottom:14px;flex-wrap:wrap;gap:10px">
    <Link :href="route('admin.resources.index', resource.key)" class="link-arrow">← {{ resource.label }}</Link>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <button v-for="a in actions" :key="a.key" class="btn btn-sm" :class="btn(a.style)" @click="start(a)">{{ a.label }}</button>
      <Link v-if="resource.editable" :href="route('admin.resources.edit', [resource.key, record.id])" class="btn btn-dark btn-sm">Edit</Link>
      <button v-if="resource.deletable" class="btn btn-line btn-sm danger" @click="destroy">Delete</button>
    </div>
  </div>

  <div style="display:grid;gap:14px">
    <section v-for="(s, i) in sections" :key="i" class="box">
      <div class="box-h"><h3>{{ s.title }}</h3></div>
      <div v-if="s.svg" class="qr-svg" style="max-width:220px;margin:0 0 18px" v-html="s.svg"></div>
      <dl v-if="s.rows" class="admin-dl">
        <template v-for="(r, j) in s.rows" :key="j"><dt>{{ r[0] }}</dt><dd><Cell :value="r[1]" /></dd></template>
      </dl>
      <div v-if="s.table" class="tbl-wrap">
        <table class="tbl">
          <thead><tr><th v-for="h in s.table.headings" :key="h">{{ h }}</th></tr></thead>
          <tbody>
            <tr v-for="(row, k) in s.table.rows" :key="k"><td v-for="(cell, c) in row" :key="c"><Cell :value="cell" /></td></tr>
            <tr v-if="!s.table.rows.length"><td :colspan="s.table.headings.length" class="empty">None yet.</td></tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>

  <div v-if="active" class="modal open" role="dialog" aria-modal="true">
    <div class="modal-bg" @click="active = null"></div>
    <form class="modal-box sm" @submit.prevent="run">
      <button type="button" class="modal-x" aria-label="Close" @click="active = null">×</button>
      <h2 class="h3s" style="font-size:30px;margin-bottom:18px">{{ active.label }}</h2>
      <div class="form-grid"><Field v-for="f in active.fields" :key="f.name" v-model="form[f.name]" :field="f" :error="form.errors[f.name]" /></div>
      <div class="form-nav">
        <button type="button" class="btn btn-line" @click="active = null">Cancel</button>
        <button class="btn btn-dark" :disabled="form.processing">{{ form.processing ? 'Working…' : active.label }}</button>
      </div>
    </form>
  </div>
</template>
