<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Field from '@/Components/Field.vue';
import AdminIcon from '@/Components/AdminIcon.vue';
import PanelHeading from '@/Components/PanelHeading.vue';

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
  <form class="box admin-panel" @submit.prevent="submit">
    <PanelHeading :icon="resource.key" title="Record details" />
    <div class="form-grid panel-body">
      <Field v-for="f in fields" :key="f.name" v-model="form[f.name]" :field="f" :error="form.errors[f.name]" />
    </div>
    <div class="panel-footer sticky-actions">
      <Link :href="recordId ? route('admin.resources.show', [resource.key, recordId]) : route('admin.resources.index', resource.key)" class="btn btn-line">
        <AdminIcon name="close" />Cancel
      </Link>
      <button class="btn btn-dark" :disabled="form.processing"><AdminIcon name="save" />{{ form.processing ? 'Saving…' : 'Save' }}</button>
    </div>
  </form>
</template>
