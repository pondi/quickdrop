<template>
  <div class="flex items-center justify-between px-4 py-3 sm:px-6">
    <div class="flex flex-1 justify-between sm:hidden">
      <Link
        v-if="prevPageUrl"
        :href="prevPageUrl"
        class="relative inline-flex items-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600"
      >
        Previous
      </Link>
      <span v-else class="relative inline-flex items-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-300 dark:text-gray-500 cursor-not-allowed">
        Previous
      </span>
      <Link
        v-if="nextPageUrl"
        :href="nextPageUrl"
        class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600"
      >
        Next
      </Link>
      <span v-else class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-300 dark:text-gray-500 cursor-not-allowed">
        Next
      </span>
    </div>
    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
      <div>
        <p class="text-sm text-gray-700 dark:text-gray-300">
          Showing
          <span class="font-medium">{{ from }}</span>
          to
          <span class="font-medium">{{ to }}</span>
          of
          <span class="font-medium">{{ total }}</span>
          results
        </p>
      </div>
      <div>
        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
          <template v-for="(link, index) in links" :key="index">
            <Link
              v-if="link.url"
              :href="link.url"
              :class="[
                link.active
                  ? 'z-10 bg-indigo-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600'
                  : 'text-gray-900 dark:text-gray-100 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-offset-0',
                index === 0 ? 'rounded-l-md' : '',
                index === links.length - 1 ? 'rounded-r-md' : '',
                'relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20'
              ]"
              v-html="link.label"
            />
            <span
              v-else
              :class="[
                'text-gray-300 dark:text-gray-500 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 cursor-not-allowed',
                index === 0 ? 'rounded-l-md' : '',
                index === links.length - 1 ? 'rounded-r-md' : '',
                'relative inline-flex items-center px-4 py-2 text-sm font-semibold'
              ]"
              v-html="link.label"
            />
          </template>
        </nav>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  links: {
    type: Array,
    required: true
  },
  from: {
    type: Number,
    default: 0
  },
  to: {
    type: Number,
    default: 0
  },
  total: {
    type: Number,
    default: 0
  },
  prevPageUrl: {
    type: String,
    default: null
  },
  nextPageUrl: {
    type: String,
    default: null
  }
})
</script>