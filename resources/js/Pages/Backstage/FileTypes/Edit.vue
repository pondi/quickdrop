<template>
  <BackstageLayout>
    <Head title="Edit File Type" />
    
    <div class="max-w-2xl mx-auto">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit File Type</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Update file type configuration for .{{ fileType.extension }}</p>
      </div>

      <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            File Extension
          </label>
          <p class="mt-1 text-lg font-medium text-gray-900 dark:text-white">.{{ fileType.extension }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">Extension cannot be changed</p>
        </div>

        <div>
          <label for="mime_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            MIME Type <span class="text-red-500">*</span>
          </label>
          <input
            id="mime_type"
            v-model="form.mime_type"
            type="text"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
          />
          <p v-if="form.errors.mime_type" class="mt-1 text-sm text-red-600 dark:text-red-400">
            {{ form.errors.mime_type }}
          </p>
        </div>

        <div>
          <label for="display_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Display Name <span class="text-red-500">*</span>
          </label>
          <input
            id="display_name"
            v-model="form.display_name"
            type="text"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
          />
          <p v-if="form.errors.display_name" class="mt-1 text-sm text-red-600 dark:text-red-400">
            {{ form.errors.display_name }}
          </p>
        </div>

        <div>
          <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Category <span class="text-red-500">*</span>
          </label>
          <select
            id="category"
            v-model="form.category"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option v-for="(name, key) in categories" :key="key" :value="key">{{ name }}</option>
          </select>
          <p v-if="form.errors.category" class="mt-1 text-sm text-red-600 dark:text-red-400">
            {{ form.errors.category }}
          </p>
        </div>

        <div class="grid grid-cols-2 gap-6">
          <div>
            <label for="max_size" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Max File Size (bytes)
            </label>
            <input
              id="max_size"
              v-model="form.max_size"
              type="number"
              min="0"
              placeholder="Leave empty for no limit"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
            <p v-if="form.errors.max_size" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ form.errors.max_size }}
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              {{ maxSizeInMB }} MB
            </p>
          </div>

          <div>
            <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Priority
            </label>
            <input
              id="priority"
              v-model="form.priority"
              type="number"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
            <p v-if="form.errors.priority" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ form.errors.priority }}
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Higher priority types appear first</p>
          </div>
        </div>

        <div>
          <label for="icon_class" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Icon Class
          </label>
          <input
            id="icon_class"
            v-model="form.icon_class"
            type="text"
            placeholder="DocumentTextIcon"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
          />
          <p v-if="form.errors.icon_class" class="mt-1 text-sm text-red-600 dark:text-red-400">
            {{ form.errors.icon_class }}
          </p>
        </div>

        <div>
          <label class="flex items-center">
            <input
              v-model="form.is_allowed"
              type="checkbox"
              class="rounded border-gray-300 dark:border-gray-600 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
            />
            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
              Allow this file type for uploads
            </span>
          </label>
        </div>

        <div class="flex items-center justify-between pt-4">
          <Link
            :href="route('backstage.file-types.index')"
            class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200"
          >
            Cancel
          </Link>
          
          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 transition-colors duration-fast"
          >
            <span v-if="form.processing">Updating...</span>
            <span v-else>Update File Type</span>
          </button>
        </div>
      </form>
    </div>
  </BackstageLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import BackstageLayout from '@/Layouts/BackstageLayout.vue'

const props = defineProps({
  fileType: Object,
  categories: Object,
})

const form = useForm({
  mime_type: props.fileType.mime_type,
  display_name: props.fileType.display_name,
  category: props.fileType.category,
  is_allowed: props.fileType.is_allowed,
  max_size: props.fileType.max_size,
  icon_class: props.fileType.icon_class || '',
  priority: props.fileType.priority,
})

const maxSizeInMB = computed(() => {
  if (!form.max_size) return 'No limit'
  return (form.max_size / (1024 * 1024)).toFixed(2)
})

const submit = () => {
  form.put(route('backstage.file-types.update', props.fileType.id))
}
</script>