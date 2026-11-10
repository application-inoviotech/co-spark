<script setup>
import AdminIcon from './AdminIcon.vue';

defineProps({ icon: String, title: String, description: String });
</script>

<template>
  <header class="panel-heading">
    <AdminIcon :name="icon" class="panel-icon" />
    <div>
      <h2>{{ title }}</h2>
      <p v-if="description">{{ description }}</p>
    </div>
  </header>
</template>
