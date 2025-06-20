<template>
  <TransitionRoot as="template" :show="show">
    <Dialog as="div" class="relative z-50" @close="close">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
              <div>
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                  <ShareIcon class="h-6 w-6 text-green-600 dark:text-green-400" aria-hidden="true" />
                </div>
                <div class="mt-3 text-center sm:mt-5">
                  <DialogTitle as="h3" class="text-base font-semibold leading-6 text-gray-900 dark:text-gray-100">
                    Share QuickDrop
                  </DialogTitle>
                  <div class="mt-2">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                      {{ shareData?.title || 'Your QuickDrop' }}
                    </p>
                    <p v-if="shareData?.expires_at" class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                      Expires {{ formatDate(shareData.expires_at) }}
                    </p>
                    <p v-if="shareData?.is_encrypted" class="mt-1 text-xs text-orange-600 dark:text-orange-400">
                      <LockClosedIcon class="inline h-3 w-3 mr-1" />
                      This QuickDrop is encrypted
                    </p>
                  </div>
                  
                  <div class="mt-4">
                    <div class="relative">
                      <input
                        type="text"
                        readonly
                        :value="shareUrl"
                        class="block w-full rounded-md border-0 py-2 pr-20 text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:focus:ring-indigo-500 sm:text-sm sm:leading-6"
                        @click="selectText"
                      />
                      <div class="absolute inset-y-0 right-0 flex items-center pr-1">
                        <button
                          type="button"
                          @click="copyToClipboard"
                          class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                        >
                          <ClipboardIcon v-if="!copied" class="h-4 w-4" />
                          <CheckIcon v-else class="h-4 w-4" />
                          {{ copied ? 'Copied!' : 'Copy' }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="mt-5 sm:mt-6">
                <button
                  type="button"
                  class="inline-flex w-full justify-center rounded-md bg-gray-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-600"
                  @click="close"
                >
                  Close
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { ShareIcon, ClipboardIcon, CheckIcon, LockClosedIcon } from '@heroicons/vue/24/outline'
import { format } from 'date-fns'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  shareUrl: {
    type: String,
    required: true
  },
  shareData: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close'])

const copied = ref(false)

const close = () => {
  emit('close')
}

const copyToClipboard = async () => {
  try {
    await navigator.clipboard.writeText(props.shareUrl)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch (err) {
  }
}

const selectText = (event) => {
  event.target.select()
}

const formatDate = (dateString) => {
  return format(new Date(dateString), 'MMM d, yyyy \'at\' h:mm a')
}

watch(() => props.show, (newVal) => {
  if (!newVal) {
    copied.value = false
  }
})
</script>