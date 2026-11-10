<script setup>
import { useId } from 'vue';
import SearchableSelect from './SearchableSelect.vue';

/* One form control; field = { name, label, type, options, half, hint } */
defineProps({ field: Object, error: String });
const model = defineModel();
const id = useId();
</script>

<template>
  <div class="field" :class="{ half: field.half, err: error }">
    <label v-if="field.type === 'checkbox'" class="checkline">
      <input type="checkbox" v-model="model"> <span>{{ field.label }}</span>
    </label>
    <template v-else>
      <label :for="id">{{ field.label }}</label>
      <SearchableSelect
        v-if="field.type === 'select'" v-model="model"
        :control-id="id" :label="field.label" :options="field.options" :invalid="Boolean(error)"
      />
      <textarea v-else-if="field.type === 'textarea' || field.type === 'lines'" :id="id" v-model="model" :rows="field.type === 'lines' ? 5 : 6" />
      <input v-else-if="field.type === 'file'" :id="id" type="file" @change="model = $event.target.files[0]">
      <input v-else :id="id" :type="field.type === 'email' ? 'email' : field.type === 'number' ? 'number' : field.type === 'date' ? 'date' : 'text'" v-model="model" step="any">
    </template>
    <span v-if="field.hint && !error" class="hint">{{ field.hint }}</span>
    <span v-if="error" class="err-msg" style="display:block">{{ error }}</span>
  </div>
</template>
