<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Field from '@/Components/Field.vue';

const props = defineProps({ resource: Object, fields: Array, values: Object, recordId: [Number, String, null] });
const form = useForm(Object.fromEntries(props.fields.map(f => [f.name, props.values[f.name] ?? (f.type === 'checkbox' ? false : '')])));
const submit = () => props.recordId
  ? form.put(route('admin.resources.update', [props.resource.key, props.recordId]))
  : form.post(route('admin.resources.store', props.resource.key));
const title = `${props.recordId ? 'Edit' : 'New'} ${props.resource.singular.toLowerCase()}`;
</script>

<template>
  <Head :title="title" />
  <Teleport defer to="#pageTitle">{{ title }}</Teleport>
  <form class="box" style="max-width:860px" @submit.prevent="submit">
    <div class="form-grid">
      <Field v-for="f in fields" :key="f.name" v-model="form[f.name]" :field="f" :error="form.errors[f.name]" />
    </div>
    <div class="form-nav form-actions">
      <Link :href="recordId ? route('admin.resources.show', [resource.key, recordId]) : route('admin.resources.index', resource.key)" class="btn btn-line">Cancel</Link>
      <button class="btn btn-dark" :disabled="form.processing">{{ form.processing ? 'Saving…' : 'Save' }}</button>
    </div>
  </form>
</template>

<style scoped>
/* Save/Cancel stay pinned to the bottom of the screen while a long form scrolls (bleeds over the box padding) */
.form-actions { position: sticky; bottom: 0; z-index: 5; background: var(--paper); margin: 36px -24px -24px; padding: 16px 24px; border-radius: 0 0 var(--r) var(--r); }
</style>
