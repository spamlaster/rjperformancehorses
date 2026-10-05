import { ref } from 'vue'
import type { Ref } from 'vue'

export interface Notification {
  id: string
  message: string
  type: 'success' | 'error'
  duration?: number // ms, null = no auto-dismiss
}

const notifications: Ref<Notification[]> = ref([])

export function useNotification() {
  const notify = (message: string, type: 'success' | 'error' = 'success', duration = 3500) => {
    const id = `notif-${Date.now()}-${Math.random().toString(36).slice(2)}`

    const notification: Notification = {
      id,
      message,
      type,
      duration,
    }

    notifications.value.push(notification)

    // Auto-dismiss if duration is set
    if (duration) {
      setTimeout(() => {
        dismiss(id)
      }, duration)
    }

    return id
  }

  const success = (message: string, duration = 3500) => {
    return notify(message, 'success', duration)
  }

  const error = (message: string, duration = 5000) => {
    return notify(message, 'error', duration)
  }

  const dismiss = (id: string) => {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index !== -1) {
      notifications.value.splice(index, 1)
    }
  }

  const dismissAll = () => {
    notifications.value = []
  }

  return {
    notifications,
    notify,
    success,
    error,
    dismiss,
    dismissAll,
  }
}
