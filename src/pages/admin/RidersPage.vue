<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex justify-between items-center">
        <h1 class="text-3xl font-serif font-bold text-text-dark">Riders</h1>
        <button
          @click="showAddForm = true"
          class="btn-primary"
        >
          + Add Rider
        </button>
      </div>

      <!-- Add/Edit Form Modal -->
      <div v-if="showAddForm" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
          <h2 class="text-xl font-bold mb-4">{{ editingRider ? 'Edit Rider' : 'Add New Rider' }}</h2>

          <form @submit.prevent="saveRider" class="space-y-3">
            <div>
              <label class="form-label">First Name</label>
              <input v-model="formData.firstName" type="text" class="form-input" required />
            </div>
            <div>
              <label class="form-label">Last Name</label>
              <input v-model="formData.lastName" type="text" class="form-input" required />
            </div>
            <div>
              <label class="form-label">Email</label>
              <input v-model="formData.email" type="email" class="form-input" required />
            </div>
            <div>
              <label class="form-label">Phone</label>
              <input v-model="formData.phone" type="tel" class="form-input" />
            </div>
            <div>
              <label class="form-label">Experience Level</label>
              <select v-model="formData.experienceLevel" class="form-select">
                <option value="beginner">Beginner</option>
                <option value="intermediate">Intermediate</option>
                <option value="advanced">Advanced</option>
              </select>
            </div>

            <div class="flex gap-3 pt-4">
              <button type="submit" class="btn-primary flex-1">Save</button>
              <button type="button" @click="closeForm" class="btn-secondary flex-1">Cancel</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Riders Table -->
      <div class="card">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="border-b border-border-light">
              <tr class="text-left">
                <th class="p-4 font-bold text-text-dark">Name</th>
                <th class="p-4 font-bold text-text-dark">Email</th>
                <th class="p-4 font-bold text-text-dark">Phone</th>
                <th class="p-4 font-bold text-text-dark">Experience</th>
                <th class="p-4 font-bold text-text-dark">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="rider in adminStore.riders" :key="rider.id" class="border-b border-border-light hover:bg-gray-50">
                <td class="p-4">{{ rider.firstName }} {{ rider.lastName }}</td>
                <td class="p-4 text-sm">{{ rider.email }}</td>
                <td class="p-4 text-sm">{{ rider.phone || '—' }}</td>
                <td class="p-4"><span class="badge badge-info">{{ rider.experienceLevel }}</span></td>
                <td class="p-4 flex gap-2">
                  <router-link :to="{ path: '/admin/bookings', query: { rider: rider.id } }" class="text-xs px-2 py-1 bg-dark-brown text-white rounded">Add Session</router-link>
                  <button
                    @click="editRider(rider)"
                    class="text-xs px-2 py-1 bg-accent-gold text-white rounded hover:bg-warm-gold"
                  >
                    Edit
                  </button>
                  <button
                    @click="deleteRiderConfirm(rider.id)"
                    class="text-xs px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600"
                  >
                    Delete
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import AdminLayout from '@/components/layouts/AdminLayout.vue'
import { useAdminStore } from '@/stores/adminStore'
import { useNotification } from '@/composables/useNotification'
import { useConfirmDialog } from '@/composables/useConfirmDialog'
import type { Rider } from '@/types/models'

const adminStore = useAdminStore()
const { success, error } = useNotification()
const { confirm } = useConfirmDialog()
const showAddForm = ref(false)
const editingRider = ref<Rider | null>(null)
const formData = ref<Partial<Rider>>({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  experienceLevel: 'beginner',
})

const editRider = (rider: Rider) => {
  editingRider.value = rider
  formData.value = { ...rider }
  showAddForm.value = true
}

const closeForm = () => {
  showAddForm.value = false
  editingRider.value = null
  formData.value = {
    firstName: '',
    lastName: '',
    email: '',
    phone: '',
    experienceLevel: 'beginner',
  }
}

const saveRider = async () => {
  if (editingRider.value) {
    const result = await adminStore.updateRiderLocal(editingRider.value.id, formData.value)
    if (!result) {
      error('Unable to update rider')
      return
    }
    success('Rider updated')
  } else {
    const newRider = await adminStore.addRider(formData.value as Omit<Rider, 'id' | 'createdAt' | 'updatedAt' | 'deletedAt'>)
    if (!newRider) {
      error('Unable to create rider')
      return
    }
    success('Rider created')
  }
  closeForm()
}

const deleteRiderConfirm = (id: string) => {
  const rider = adminStore.getRider(id)
  confirm({
    title: 'Delete Rider',
    message: `Are you sure? This will permanently delete ${rider?.firstName} ${rider?.lastName} from the system.`,
    confirmText: 'Delete',
    cancelText: 'Cancel',
    isDangerous: true,
    onConfirm: async () => {
      const result = await adminStore.deleteRider(id)
      if (result) {
        success('Rider deleted')
      } else {
        error('Unable to delete rider')
      }
    },
  })
}
</script>
