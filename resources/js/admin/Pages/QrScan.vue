<script setup>
import { onBeforeUnmount, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import Field from '@/Components/Field.vue';
import AdminIcon from '@/Components/AdminIcon.vue';
import PanelHeading from '@/Components/PanelHeading.vue';

/* Partner terminal: scan (camera, where the browser supports BarcodeDetector) or type the member QR code. */
const props = defineProps({ partners: Object });
const form = useForm({ code: '', partner_id: Object.keys(props.partners)[0] ?? '', amount: 15, location: '' });
const video = ref(null);
const scanning = ref(false);
const canScan = 'BarcodeDetector' in window;
let stream;

const scan = async () => {
  stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
  video.value.srcObject = stream;
  await video.value.play();
  scanning.value = true;
  const detector = new window.BarcodeDetector({ formats: ['qr_code'] });
  const tick = async () => {
    if (!scanning.value) return;
    const [code] = await detector.detect(video.value).catch(() => []);
    if (code) { form.code = code.rawValue; stop(); } else requestAnimationFrame(tick);
  };
  tick();
};
const stop = () => { scanning.value = false; stream?.getTracks().forEach(t => t.stop()); };
onBeforeUnmount(stop);
const redeem = () => form.post(route('admin.qr.redeem'), { preserveScroll: true, onSuccess: () => form.reset('code') });
</script>

<template>
  <Head title="QR scan & redeem" />
  <Teleport defer to="#pageTitle">QR scan & redeem</Teleport>
  <form class="box admin-panel" @submit.prevent="redeem">
    <PanelHeading icon="qr-code" title="Member redemption" description="The system validates the QR code, the member, their membership and permissions and the available balance, then records the redemption and notifies the member." />
    <div class="panel-body">
      <div v-if="canScan" class="qr-tools">
        <video v-show="scanning" ref="video" playsinline class="qr-preview"></video>
        <button type="button" class="btn btn-line btn-sm" @click="scanning ? stop() : scan()">
          <AdminIcon :name="scanning ? 'stop' : 'scan'" />{{ scanning ? 'Stop camera' : 'Scan with camera' }}
        </button>
      </div>
      <div class="form-grid">
        <Field v-model="form.code" :field="{ label: 'Member QR code', hint: 'Scanned or typed code' }" :error="form.errors.code" />
        <Field v-model="form.partner_id" :field="{ label: 'Partner', type: 'select', options: partners, half: true }" :error="form.errors.partner_id" />
        <Field v-model="form.amount" :field="{ label: 'Credit (DT)', type: 'number', half: true }" :error="form.errors.amount" />
        <Field v-model="form.location" :field="{ label: 'Location (optional)' }" :error="form.errors.location" />
      </div>
    </div>
    <div class="panel-footer panel-footer-end">
      <button class="btn btn-copper" :disabled="form.processing">
        <AdminIcon name="check" />{{ form.processing ? 'Validating…' : 'Validate & redeem' }}
      </button>
    </div>
  </form>
</template>
