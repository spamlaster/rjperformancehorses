import { ref } from 'vue'
import type { Ref } from 'vue'

export interface ConfirmDialog {
  isOpen: Ref<boolean>
  title: string
  message: string
  confirmText: string
  cancelText: string
  isDangerous: boolean
  onConfirm: (() => void | Promise<void>) | null
  onCancel: (() => void) | null
}

  const isOpen = ref(false)
  const title = ref('')
  const message = ref('')
  const confirmText = ref('Confirm')
  const cancelText = ref('Cancel')
  const isDangerous = ref(false)
  let onConfirm: (() => void | Promise<void>) | null = null
  let onCancel: (() => void) | null = null

export function useConfirmDialog() {
  const confirm = async (options: {
    title: string
    message: string
    confirmText?: string
    cancelText?: string
    isDangerous?: boolean
    onConfirm: () => void | Promise<void>
    onCancel?: () => void
  }) => {
    title.value = options.title
    message.value = options.message
    confirmText.value = options.confirmText || 'Confirm'
    cancelText.value = options.cancelText || 'Cancel'
    isDangerous.value = options.isDangerous || false
    onConfirm = options.onConfirm
    onCancel = options.onCancel || null
    isOpen.value = true
  }

  const handleConfirm = async () => {
    if (onConfirm) {
      await onConfirm()
    }
    isOpen.value = false
  }

  const handleCancel = () => {
    if (onCancel) {
      onCancel()
    }
    isOpen.value = false
  }

  return {
    isOpen,
    title,
    message,
    confirmText,
    cancelText,
    isDangerous,
    confirm,
    handleConfirm,
    handleCancel,
  }
}
