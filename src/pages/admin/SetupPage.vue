<template>
  <AdminLayout>
    <div class="max-w-4xl space-y-6">
      <h1 class="text-3xl font-serif font-bold">Setup</h1>
      <p class="text-light-brown">Your six standard lesson types are ready to edit. Add your horses, weekly hours, and waiver to finish setup.</p>
      <p v-if="loading" role="status">Loading setup…</p>
      <p v-if="error" role="alert" class="text-red-700">{{ error }} <button class="underline" @click="load">Try again</button></p>
      <p v-if="notice" role="status">{{ notice }}</p>
      <template v-if="status">
        <SetupChecklist :status="status" />
        <form class="card card-padding space-y-4" @submit.prevent="saveTimezone">
          <h2 class="text-xl font-bold">Ranch timezone</h2>
          <p class="text-sm text-light-brown">Schedules and bookings use this timezone.</p>
          <div><label for="setup-timezone" class="form-label">Timezone</label><select id="setup-timezone" v-model="timezone" required class="form-select"><option v-for="zone in status.timezones" :key="zone" :value="zone">{{ zone }}</option></select></div>
          <button class="btn-primary" :disabled="saving" type="submit">Save timezone</button>
        </form>
        <form id="waiver" class="card card-padding space-y-4" @submit.prevent="publishWaiver">
          <h2 class="text-xl font-bold">Booking waiver</h2>
          <p class="text-sm text-light-brown">Add your waiver text for riders to read and sign. Publishing makes a new version available immediately. Previously signed versions are kept.</p>
          <p v-if="status.waiver" class="text-sm text-light-brown">Current version: {{ status.waiver.version }}</p>
          <div><label for="waiver-title" class="form-label">Waiver title</label><input id="waiver-title" v-model="waiver.title" maxlength="255" required class="form-input" /></div>
          <div><label for="waiver-content" class="form-label">Waiver text</label><textarea id="waiver-content" v-model="waiver.content" rows="12" maxlength="60000" required class="form-input"></textarea></div>
          <button class="btn-primary" :disabled="saving" type="submit">{{ saving ? 'Saving…' : 'Publish waiver' }}</button>
        </form>
      </template>
    </div>
  </AdminLayout>
</template>
<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import AdminLayout from '@/components/layouts/AdminLayout.vue'
import SetupChecklist from '@/components/admin/SetupChecklist.vue'
import { api } from '@/lib/api'
import { fetchSetupStatus, type SetupStatus } from '@/services/setupService'
const status = ref<SetupStatus | null>(null)
const loading = ref(true)
const saving = ref(false)
const error = ref('')
const notice = ref('')
const timezone = ref('')
const waiver = reactive({ title: '', content: '' })
async function load() {
  loading.value = true
  error.value = ''
  try {
    status.value = await fetchSetupStatus()
    timezone.value = status.value.business_timezone
    waiver.title = status.value.waiver?.title || ''
    waiver.content = status.value.waiver?.content || ''
  } catch (err) { error.value = err instanceof Error ? err.message : 'Unable to load setup.' }
  finally { loading.value = false }
}
async function saveTimezone() {
  await save('PATCH', '/admin/config', { business_timezone: timezone.value }, 'Timezone saved.')
}
async function publishWaiver() {
  await save('POST', '/admin/waivers', { ...waiver }, 'Waiver published.')
}
async function save(method: string, path: string, body: unknown, message: string) {
  if (saving.value) return
  saving.value = true
  error.value = ''
  notice.value = ''
  try {
    const result = await api(method, path, body)
    if (!result.ok) throw new Error(result.error || 'Unable to save setup.')
    status.value = await fetchSetupStatus()
    notice.value = message
  } catch (err) { error.value = err instanceof Error ? err.message : 'Unable to save setup.' }
  finally { saving.value = false }
}
onMounted(load)
</script>
