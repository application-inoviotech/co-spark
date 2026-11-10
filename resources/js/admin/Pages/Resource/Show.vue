<script setup>
import { onBeforeUnmount, ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2/dist/sweetalert2.esm.js';
import Cell from '@/Components/Cell.vue';
import Field from '@/Components/Field.vue';
import AdminIcon from '@/Components/AdminIcon.vue';
import PanelHeading from '@/Components/PanelHeading.vue';

const props = defineProps({ resource: Object, record: Object, sections: Array, actions: Array });
const active = ref(null);
const form = ref(null);
const confirming = ref(false);

async function confirmAction({ label, confirm, style }, confirmButtonText = 'Confirm') {
  if (confirming.value) return false;
  confirming.value = true;
  try {
    const result = await Swal.fire({
      titleText: label,
      text: confirm,
      icon: 'warning',
      iconColor: style === 'danger' ? '#b63d32' : '#c8753d',
      showCancelButton: true,
      confirmButtonText,
      focusCancel: true,
      reverseButtons: true,
      allowOutsideClick: false,
      heightAuto: false,
      buttonsStyling: false,
      customClass: {
        popup: 'admin-confirmation',
        confirmButton: style === 'danger' ? 'btn btn-line danger' : 'btn btn-dark',
        cancelButton: 'btn btn-line',
      },
    });
    return result.isConfirmed;
  } finally {
    confirming.value = false;
  }
}

onBeforeUnmount(() => {
  if (confirming.value) Swal.close();
});

/* Actions without inputs run straight away (after confirmation); others open a small form */
const start = async action => {
  if (confirming.value) return;
  if (!action.fields?.length) {
    if (action.confirm && !(await confirmAction(action))) return;
    return router.post(route('admin.resources.action', [props.resource.key, props.record.id, action.key]), {}, { preserveScroll: true });
  }
  active.value = action;
  form.value = useForm(Object.fromEntries(action.fields.map(f => [f.name, f.default ?? ''])));
};
const run = async () => {
  const action = active.value;
  if (!action || form.value.processing || confirming.value) return;
  if (action.confirm && !(await confirmAction(action))) return;
  form.value.post(route('admin.resources.action', [props.resource.key, props.record.id, action.key]), {
    preserveScroll: true,
    forceFormData: action.fields.some(f => f.type === 'file'),
    onSuccess: () => (active.value = null),
  });
};
const destroy = async () => {
  const label = `Delete this ${props.resource.singular.toLowerCase()}?`;
  if (await confirmAction({ label, style: 'danger' }, 'Delete')) {
    router.delete(route('admin.resources.destroy', [props.resource.key, props.record.id]));
  }
};
const btn = style => ({ copper: 'btn-copper', dark: 'btn-dark', danger: 'btn-line danger', line: 'btn-line' })[style] ?? 'btn-line';
const actionIcons = {
  activate: 'check', active: 'check', approve: 'check', completed: 'check',
  confirm: 'check', confirmed: 'check', handled: 'check', verify_license: 'policy',
  adjust: 'token-transaction', commission: 'commission', bonus_redeemed: 'token-transaction',
  premium: 'payment', paid: 'payment', refund: 'refund',
  assign: 'user', reschedule: 'appointment', cancel: 'clock',
  document: 'page', review_document: 'page', request_document: 'page',
  message: 'contact-message', request_info: 'contact-message', send: 'contact-message',
  qr: 'qr-code', reissue: 'qr-code',
};
const actionIcon = action => actionIcons[action.key] ?? (action.style === 'danger' ? 'blocked' : 'settings');
</script>

<template>
  <Head :title="record.title" />
  <Teleport defer to="#pageTitle">{{ record.title }}</Teleport>
  <div class="box record-actions">
    <Link :href="route('admin.resources.index', resource.key)" class="record-back">
      <AdminIcon name="back" />Back to {{ resource.label }}
    </Link>
    <div class="panel-actions">
      <button v-for="a in actions" :key="a.key" type="button" class="btn btn-sm" :class="btn(a.style)" :disabled="confirming" @click="start(a)">
        <AdminIcon :name="actionIcon(a)" />{{ a.label }}
      </button>
      <Link v-if="resource.editable" :href="route('admin.resources.edit', [resource.key, record.id])" class="btn btn-dark btn-sm">
        <AdminIcon name="edit" />Edit
      </Link>
      <button v-if="resource.deletable" type="button" class="btn btn-line btn-sm danger" :disabled="confirming" @click="destroy">
        <AdminIcon name="trash" />Delete
      </button>
    </div>
  </div>

  <div class="record-sections">
    <section v-for="(s, i) in sections" :key="i" class="box admin-panel">
      <PanelHeading :title="s.title" :icon="s.svg ? 'qr-code' : s.table ? 'reports' : i === 0 ? resource.key : 'page'" />
      <div v-if="s.svg" class="qr-svg record-qr" v-html="s.svg"></div>
      <dl v-if="s.rows" class="admin-dl">
        <template v-for="(r, j) in s.rows" :key="j"><dt>{{ r[0] }}</dt><dd><Cell :value="r[1]" /></dd></template>
      </dl>
      <div v-if="s.table" class="tbl-wrap record-table">
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
    <form class="modal-box sm admin-panel action-panel" @submit.prevent="run">
      <button type="button" class="modal-x" aria-label="Close" @click="active = null"><AdminIcon name="close" /></button>
      <PanelHeading :title="active.label" :icon="actionIcon(active)" />
      <div class="form-grid panel-body"><Field v-for="f in active.fields" :key="f.name" v-model="form[f.name]" :field="f" :error="form.errors[f.name]" /></div>
      <div class="panel-footer">
        <button type="button" class="btn btn-line" @click="active = null"><AdminIcon name="close" />Cancel</button>
        <button class="btn btn-dark" :disabled="form.processing || confirming"><AdminIcon :name="actionIcon(active)" />{{ form.processing ? 'Working…' : active.label }}</button>
      </div>
    </form>
  </div>
</template>
