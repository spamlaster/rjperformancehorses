<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex justify-between items-center">
        <h1 class="text-3xl font-serif font-bold text-text-dark">Horses</h1>
        <button
          @click="showAddForm = true"
          class="btn-primary"
        >
          + Add Horse
        </button>
      </div>

      <!-- Add/Edit Form Modal -->
      <div v-if="showAddForm" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 max-h-96 overflow-y-auto">
          <h2 class="text-xl font-bold mb-4">{{ editingHorse ? 'Edit Horse' : 'Add New Horse' }}</h2>

          <form @submit.prevent="saveHorse" class="space-y-3">
            <div>
              <label class="form-label">Name</label>
              <input v-model="formData.name" type="text" class="form-input" required />
            </div>
            <div>
              <label class="form-label">Breed</label>
              <input v-model="formData.breed" type="text" class="form-input" />
            </div>
            <div>
              <label class="form-label">Age</label>
              <input v-model.number="formData.age" type="number" class="form-input" />
            </div>
            <div>
              <label class="form-label">Experience Level</label>
              <select v-model="formData.experienceLevel" class="form-select">
                <option value="beginner_friendly">Beginner Friendly</option>
                <option value="intermediate">Intermediate</option>
                <option value="advanced">Advanced</option>
                <option value="competition">Competition</option>
              </select>
            </div>
            <div>
              <label class="flex items-center gap-2">
                <input v-model="formData.active" type="checkbox" />
                <span>Active</span>
              </label>
            </div>

            <div class="flex gap-3 pt-4">
              <button type="submit" class="btn-primary flex-1">Save</button>
              <button type="button" @click="closeForm" class="btn-secondary flex-1">Cancel</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Horses Table -->
      <div class="card">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="border-b border-border-light">
              <tr class="text-left">
                <th class="p-4 font-bold text-text-dark">Name</th>
                <th class="p-4 font-bold text-text-dark">Breed</th>
                <th class="p-4 font-bold text-text-dark">Age</th>
                <th class="p-4 font-bold text-text-dark">Level</th>
                <th class="p-4 font-bold text-text-dark">Available</th>
                <th class="p-4 font-bold text-text-dark">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="horse in adminStore.horses" :key="horse.id" class="border-b border-border-light hover:bg-gray-50">
                <td class="p-4">{{ horse.name }}</td>
                <td class="p-4 text-sm">{{ horse.breed || '—' }}</td>
                <td class="p-4 text-sm">{{ horse.age || '—' }}</td>
                <td class="p-4"><span class="badge badge-info">{{ horse.experienceLevel }}</span></td>
                <td class="p-4">
                  <span v-if="horse.active" class="badge badge-success">Yes</span>
                  <span v-else class="badge badge-error">No</span>
                </td>
                <td class="p-4 flex gap-2">
                  <button
                    @click="editHorse(horse)"
                    class="text-xs px-2 py-1 bg-accent-gold text-white rounded hover:bg-warm-gold"
                  >
                    Edit
                  </button>
                  <button
                    @click="deleteHorseConfirm(horse.id)"
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
import type { Horse } from '@/types/models'

const adminStore = useAdminStore()
const { success, error } = useNotification()
const { confirm } = useConfirmDialog()
const showAddForm = ref(false)
const editingHorse = ref<Horse | null>(null)
const formData = ref<Partial<Horse>>({
  name: '',
  breed: '',
  age: undefined,
  experienceLevel: 'beginner_friendly',
  active: true,
})

const editHorse = (horse: Horse) => {
  editingHorse.value = horse
  formData.value = { ...horse }
  showAddForm.value = true
}

const closeForm = () => {
  showAddForm.value = false
  editingHorse.value = null
  formData.value = {
    name: '',
    breed: '',
    age: undefined,
    experienceLevel: 'beginner_friendly',
    active: true,
  }
}

const saveHorse = async () => {
  if (editingHorse.value) {
    const result = await adminStore.updateHorseLocal(editingHorse.value.id, formData.value)
    if (!result) {
      error('Unable to update horse')
      return
    }
    success('Horse updated')
  } else {
    const newHorse = await adminStore.addHorse(formData.value as Omit<Horse, 'id' | 'createdAt' | 'updatedAt' | 'deletedAt'>)
    if (!newHorse) {
      error('Unable to create horse')
      return
    }
    success('Horse created')
  }
  closeForm()
}

const deleteHorseConfirm = (id: string) => {
  const horse = adminStore.getHorse(id)
  confirm({
    title: 'Delete Horse',
    message: `Are you sure? This will permanently delete ${horse?.name || 'this horse'} from the system.`,
    confirmText: 'Delete',
    cancelText: 'Cancel',
    isDangerous: true,
    onConfirm: async () => {
      const result = await adminStore.deleteHorse(id)
      if (result) {
        success('Horse deleted')
      } else {
        error('Unable to delete horse')
      }
    },
  })
}
</script>
