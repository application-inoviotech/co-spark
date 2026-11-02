<script setup>
import { useForm } from '@inertiajs/vue3';

defineOptions({ layout: false });

const form = useForm({ email: '', password: '', remember: true });
const submit = () => form.post(route('admin.login.store'), { onFinish: () => form.reset('password') });
</script>

<template>
  <div class="auth">
    <aside class="auth-side">
      <a class="logo" :href="route('home')" style="color:#fff">
        <svg viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M16 2.5 4.5 7v8.2c0 7 4.9 12.4 11.5 14.3 6.6-1.9 11.5-7.3 11.5-14.3V7L16 2.5Z" stroke="currentColor" stroke-width="1.4"/><path d="m16 10 2 4 4 2-4 2-2 4-2-4-4-2 4-2 2-4Z" fill="#C8753D"/></svg>
        <span class="logo-t"><b>COSPARK</b><span>INSURANCE GROUP</span></span>
      </a>
      <div>
        <span class="eyebrow" style="color:#97A1AF">Admin portal</span>
        <h2 style="margin-top:18px">Protect today. Build tomorrow.</h2>
        <p style="color:#97A1AF;max-width:36ch;margin-top:18px">Members, producers, policies, claims, payments and content — managed in one place.</p>
      </div>
      <span class="mono" style="color:#5E6878">COSPARK Insurance Group · Rochester, MI</span>
    </aside>
    <main class="auth-main">
      <div class="auth-form">
        <a :href="route('home')" class="crumbs" style="margin-bottom:30px;display:inline-flex">← Back to site</a>
        <h1 style="font-size:46px">Sign in</h1>
        <p class="muted" style="margin:10px 0 28px">Authorised COSPARK team members only.</p>
        <form novalidate @submit.prevent="submit">
          <div class="form-grid">
            <div class="field" :class="{ err: form.errors.email }">
              <label for="ae">Email</label>
              <input id="ae" v-model="form.email" type="email" autocomplete="username" required>
              <span class="err-msg">{{ form.errors.email }}</span>
            </div>
            <div class="field" :class="{ err: form.errors.password }">
              <label for="ap">Password</label>
              <input id="ap" v-model="form.password" type="password" autocomplete="current-password" required>
              <span class="err-msg">{{ form.errors.password }}</span>
            </div>
          </div>
          <div class="row-between" style="margin:18px 0 26px">
            <label class="checkline"><input v-model="form.remember" type="checkbox"> Keep me signed in</label>
          </div>
          <button class="btn btn-dark btn-block" type="submit" :disabled="form.processing">{{ form.processing ? 'Signing in…' : 'Sign in →' }}</button>
        </form>
      </div>
    </main>
  </div>
</template>
