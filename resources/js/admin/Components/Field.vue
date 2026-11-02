<script setup>
/* One form control; field = { name, label, type, options, half, hint } */
defineProps({ field: Object, error: String });
const model = defineModel();
</script>

<template>
  <div class="field" :class="{ half: field.half, err: error }">
    <label v-if="field.type === 'checkbox'" class="checkline" style="font-family:var(--sans);text-transform:none;letter-spacing:0;font-size:14px">
      <input type="checkbox" v-model="model"> <span>{{ field.label }}</span>
    </label>
    <template v-else>
      <label>{{ field.label }}</label>
      <select v-if="field.type === 'select'" v-model="model">
        <option v-for="(text, value) in field.options" :key="value" :value="value">{{ text }}</option>
      </select>
      <textarea v-else-if="field.type === 'textarea' || field.type === 'lines'" v-model="model" :rows="field.type === 'lines' ? 5 : 6" />
      <input v-else-if="field.type === 'file'" type="file" @change="model = $event.target.files[0]">
      <input v-else :type="field.type === 'email' ? 'email' : field.type === 'number' ? 'number' : field.type === 'date' ? 'date' : 'text'" v-model="model" step="any">
    </template>
    <span v-if="field.hint && !error" class="hint">{{ field.hint }}</span>
    <span v-if="error" class="err-msg" style="display:block">{{ error }}</span>
  </div>
</template>
