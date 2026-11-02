<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import Field from '@/Components/Field.vue';

const props = defineProps({ fields: Array, values: Object });
const form = useForm({ ...props.values });
</script>

<template>
  <Head title="Settings" />
  <Teleport defer to="#pageTitle">Settings</Teleport>
  <form class="box" style="max-width:860px" @submit.prevent="form.post(route('admin.settings.save'), { preserveScroll: true })">
    <p class="muted small" style="margin-top:0">Business information, contact details, social links and legal texts shown across the website.</p>
    <div class="form-grid">
      <Field v-for="f in fields" :key="f.name" v-model="form[f.name]" :field="{ ...f, half: f.type !== 'textarea' }" :error="form.errors[f.name]" />
    </div>
    <div class="form-nav"><span></span><button class="btn btn-dark" :disabled="form.processing">Save settings</button></div>
  </form>
</template>
