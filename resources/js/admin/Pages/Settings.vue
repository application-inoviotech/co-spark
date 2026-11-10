<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import Field from '@/Components/Field.vue';
import AdminIcon from '@/Components/AdminIcon.vue';
import PanelHeading from '@/Components/PanelHeading.vue';

const props = defineProps({ fields: Array, values: Object });
const form = useForm({ ...props.values });
</script>

<template>
  <Head title="Settings" />
  <Teleport defer to="#pageTitle">Settings</Teleport>
  <form class="box admin-panel" @submit.prevent="form.post(route('admin.settings.save'), { preserveScroll: true })">
    <PanelHeading icon="settings" title="Website settings" description="Business information, contact details, social links and legal texts shown across the website." />
    <div class="form-grid panel-body">
      <Field v-for="f in fields" :key="f.name" v-model="form[f.name]" :field="{ ...f, half: f.type !== 'textarea' }" :error="form.errors[f.name]" />
    </div>
    <div class="panel-footer panel-footer-end">
      <button class="btn btn-dark" :disabled="form.processing"><AdminIcon name="save" />Save settings</button>
    </div>
  </form>
</template>
