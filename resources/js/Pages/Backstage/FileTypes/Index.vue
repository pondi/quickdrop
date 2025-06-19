<template>
  <BackstageLayout>
    <Head title="File Type Management" />
    
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">File Type Management</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">Configure allowed file types and upload restrictions</p>
        </div>
        <Link 
          :href="route('backstage.file-types.create')"
          class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 transition-colors duration-fast"
        >
          <PlusIcon class="h-5 w-5 mr-2" />
          Add File Type
        </Link>
      </div>

      <!-- Category Stats -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div v-for="(stat, category) in stats" :key="category" class="bg-white dark:bg-gray-800 rounded-lg p-4">
          <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ stat.name }}</div>
          <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ stat.allowed }}/{{ stat.total }}</div>
          <div class="mt-1">
            <button
              @click="toggleCategory(category, true)"
              class="text-xs text-green-600 hover:text-green-700 dark:text-green-400 mr-2"
            >
              Enable All
            </button>
            <button
              @click="toggleCategory(category, false)"
              class="text-xs text-red-600 hover:text-red-700 dark:text-red-400"
            >
              Disable All
            </button>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Search
            </label>
            <input
              id="search"
              v-model="form.search"
              type="text"
              placeholder="Search extensions, mime types..."
              class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
              @input="debounceSearch"
            />
          </div>
          
          <div>
            <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Category
            </label>
            <select
              id="category"
              v-model="form.category"
              @change="updateFilters"
              class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
              <option value="">All Categories</option>
              <option v-for="(name, key) in categories" :key="key" :value="key">{{ name }}</option>
            </select>
          </div>
          
          <div>
            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Status
            </label>
            <select
              id="status"
              v-model="form.is_allowed"
              @change="updateFilters"
              class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
              <option value="">All Status</option>
              <option value="1">Allowed</option>
              <option value="0">Disabled</option>
            </select>
          </div>
        </div>
      </div>

      <!-- File Types Table -->
      <div class="bg-white dark:bg-gray-800 shadow overflow-hidden rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Extension
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                MIME Type
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Category
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Max Size
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Status
              </th>
              <th class="relative px-6 py-3">
                <span class="sr-only">Actions</span>
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="fileType in fileTypes.data" :key="fileType.id">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <span class="text-sm font-medium text-gray-900 dark:text-white">.{{ fileType.extension }}</span>
                  <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">({{ fileType.display_name }})</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                {{ fileType.mime_type }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="getCategoryClass(fileType.category)">
                  {{ categories[fileType.category] || fileType.category }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                {{ fileType.formatted_max_size || 'Unlimited' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <button
                  @click="toggleFileType(fileType)"
                  :class="fileType.is_allowed ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ fileType.is_allowed ? 'Allowed' : 'Disabled' }}
                </button>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <Link
                  :href="route('backstage.file-types.edit', fileType.id)"
                  class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300 mr-3"
                >
                  Edit
                </Link>
                <button
                  @click="deleteFileType(fileType)"
                  class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        
        <!-- Pagination -->
        <div v-if="fileTypes.last_page > 1" class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700">
          <Pagination :links="fileTypes.links" />
        </div>
      </div>
    </div>
  </BackstageLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, reactive } from 'vue'
import { debounce } from 'lodash'
import BackstageLayout from '@/Layouts/BackstageLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { PlusIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  fileTypes: Object,
  filters: Object,
  categories: Object,
  stats: Object,
})

const form = reactive({
  search: props.filters.search || '',
  category: props.filters.category || '',
  is_allowed: props.filters.is_allowed || '',
})

const updateFilters = () => {
  router.get(route('backstage.file-types.index'), form, {
    preserveState: true,
    preserveScroll: true,
  })
}

const debounceSearch = debounce(updateFilters, 300)

const getCategoryClass = (category) => {
  const classes = {
    image: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    video: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
    audio: 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200',
    document: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    archive: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    other: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
  }
  return classes[category] || classes.other
}

const toggleFileType = (fileType) => {
  router.post(route('backstage.file-types.toggle', fileType.id), {}, {
    preserveState: true,
    preserveScroll: true,
  })
}

const toggleCategory = (category, isAllowed) => {
  if (confirm(`Are you sure you want to ${isAllowed ? 'enable' : 'disable'} all ${props.stats[category].name}?`)) {
    router.post(route('backstage.file-types.bulk-toggle'), {
      category: category,
      is_allowed: isAllowed,
    }, {
      preserveState: true,
      preserveScroll: true,
    })
  }
}

const deleteFileType = (fileType) => {
  if (confirm(`Are you sure you want to delete the .${fileType.extension} file type?`)) {
    router.delete(route('backstage.file-types.destroy', fileType.id))
  }
}
</script>