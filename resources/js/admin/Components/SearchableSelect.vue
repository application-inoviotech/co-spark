<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, useId, watch } from 'vue';
import AdminIcon from './AdminIcon.vue';

const props = defineProps({
  label: String,
  options: [Object, Array],
  filter: Boolean,
  controlId: String,
  invalid: Boolean,
});
const model = defineModel();
const id = useId();
const root = ref(null);
const trigger = ref(null);
const search = ref(null);
const list = ref(null);
const open = ref(false);
const opensUp = ref(false);
const term = ref('');
const active = ref(0);

const options = computed(() => {
  const entries = Array.isArray(props.options)
    ? props.options.map((label, index) => [index, label])
    : Object.entries(props.options ?? {});
  return props.filter ? [['', 'All'], ...entries] : entries;
});
const selected = computed(() => options.value.find(([value]) => String(value) === String(model.value ?? '')));
const selectedLabel = computed(() => selected.value?.[1] ?? (props.filter ? 'All' : 'Select an option'));
const matches = computed(() => {
  const query = term.value.trim().toLowerCase();
  return options.value.filter(([, text]) => String(text).toLowerCase().includes(query));
});

watch(term, () => {
  active.value = 0;
  if (list.value) list.value.scrollTop = 0;
}, { flush: 'sync' });

async function show() {
  term.value = '';
  active.value = Math.max(0, options.value.indexOf(selected.value));
  const bounds = trigger.value.getBoundingClientRect();
  const spaceBelow = window.innerHeight - bounds.bottom;
  opensUp.value = spaceBelow < 250 && bounds.top > spaceBelow;
  open.value = true;
  await nextTick();
  search.value?.focus();
  list.value?.children[active.value]?.scrollIntoView({ block: 'nearest' });
}

function close(restoreFocus = false) {
  open.value = false;
  if (restoreFocus) trigger.value?.focus();
}

function choose(option) {
  if (!option) return;
  model.value = option[0];
  close(true);
}

function move(step) {
  if (!matches.value.length) return;
  active.value = Math.max(0, Math.min(active.value + step, matches.value.length - 1));
  list.value?.children[active.value]?.scrollIntoView({ block: 'nearest' });
}

function closeOutside(event) {
  if (open.value && !root.value?.contains(event.target)) close();
}

function closeOnBlur(event) {
  if (open.value && !root.value?.contains(event.relatedTarget)) close();
}

onMounted(() => document.addEventListener('pointerdown', closeOutside));
onUnmounted(() => document.removeEventListener('pointerdown', closeOutside));
</script>

<template>
  <div ref="root" class="searchable-select" @focusout="closeOnBlur">
    <button
      :id="controlId" ref="trigger" class="filter-trigger" type="button" aria-haspopup="dialog"
      :aria-expanded="open" :aria-controls="open ? id : undefined"
      :aria-invalid="invalid || undefined" :aria-label="label + ': ' + selectedLabel"
      @click="open ? close(true) : show()"
      @keydown.down.prevent="show()"
    >
      <span v-if="filter">{{ label }}: <strong>{{ selectedLabel }}</strong></span>
      <span v-else :class="{ 'select-placeholder': !selected }">{{ selectedLabel }}</span>
      <AdminIcon name="chevron" />
    </button>

    <div v-if="open" :id="id" class="filter-menu" :class="{ 'opens-up': opensUp }" role="dialog" :aria-label="label + ' options'" @keydown.esc.stop.prevent="close(true)">
      <input
        ref="search" v-model="term" class="filter-search" type="text" role="combobox" autocomplete="off"
        :aria-label="'Find ' + label.toLowerCase() + ' options'" placeholder="Find option…"
        aria-expanded="true" aria-autocomplete="list" :aria-controls="id + '-list'"
        :aria-activedescendant="matches[active] ? id + '-option-' + active : undefined"
        @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)"
        @keydown.enter.prevent="choose(matches[active])"
      >
      <ul :id="id + '-list'" ref="list" class="filter-options" role="listbox" :aria-label="label">
        <li
          v-for="(option, index) in matches" :id="id + '-option-' + index" :key="option[0]"
          role="option" :aria-selected="option === selected" :class="{ highlighted: index === active }"
          @mousemove="active = index" @mousedown.prevent @click="choose(option)"
        >
          {{ option[1] }}<AdminIcon v-if="option === selected" name="check" />
        </li>
      </ul>
      <p v-if="!matches.length" class="filter-empty" role="status">No options found.</p>
    </div>
  </div>
</template>
