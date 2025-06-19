<template>
  <BackstageLayout>
    <Head title="QuickDrops Management" />
    
    <div class="px-4 sm:px-6 lg:px-8">
      <!-- Page Header -->
      <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
          <h1 class="text-base font-semibold text-gray-900 dark:text-white">QuickDrops Management</h1>
          <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">Manage all QuickDrops and file shares</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none flex items-center space-x-4">
          <span 
            :class="[
              'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
              statusFilter === 'active' 
                ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400 dark:ring-green-500/30'
                : statusFilter === 'expired'
                ? 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-900/30 dark:text-red-400 dark:ring-red-500/30'
                : 'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-gray-900/30 dark:text-gray-400 dark:ring-gray-500/30'
            ]"
          >
            {{ statusFilter === 'active' ? 'Active' : statusFilter === 'expired' ? 'Expired' : 'All' }}
          </span>
          <span class="text-sm text-gray-500 dark:text-gray-400">{{ quickdrops.total || quickdrops.length }} QuickDrops</span>
        </div>
      </div>

      <!-- Search and Filters -->
      <div class="mt-6 space-y-4">
        <!-- Search Bar -->
        <div class="max-w-md">
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
            </div>
            <input
              v-model="searchQuery"
              @input="debouncedSearch"
              type="search"
              placeholder="Search by reference number, user, or file..."
              class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            />
          </div>
        </div>

        <!-- Filters -->
        <div class="flex items-center space-x-4">
          <label class="flex items-center space-x-2 text-sm">
            <span class="text-gray-700 dark:text-gray-300">Status:</span>
            <select 
              v-model="statusFilter"
              @change="applyFilters"
              class="rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="">All QuickDrops</option>
              <option value="active">Active</option>
              <option value="expired">Expired</option>
            </select>
          </label>
        </div>
      </div>

      <!-- QuickDrops Table -->
      <div class="-mx-4 mt-10 ring-1 ring-gray-300 dark:ring-gray-600 sm:mx-0 sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
          <thead>
            <tr>
              <th scope="col" class="py-3.5 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 dark:text-white sm:pl-6">
                QuickDrop
              </th>
              <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white lg:table-cell">
                User
              </th>
              <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white lg:table-cell">
                Files
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">
                Status
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">
                Expires
              </th>
              <th scope="col" class="relative py-3.5 pr-4 pl-3 sm:pr-6">
                <span class="sr-only">Actions</span>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(quickdrop, idx) in quickdrops.data || quickdrops" :key="quickdrop.id">
              <!-- QuickDrop Info -->
              <td :class="[idx === 0 ? '' : 'border-t border-transparent', 'relative py-4 pr-3 pl-4 text-sm sm:pl-6']">
                <div class="flex items-center">
                  <div class="h-11 w-11 flex-shrink-0">
                    <div class="h-11 w-11 rounded-full bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center">
                      <FolderIcon class="h-6 w-6 text-white" />
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="font-medium text-gray-900 dark:text-white">
                      {{ quickdrop.reference_number || 'No Reference' }}
                    </div>
                    <div class="mt-1 text-gray-500 dark:text-gray-400">
                      Created {{ formatRelativeTime(quickdrop.created_at) }}
                    </div>
                    <div class="mt-1 flex flex-col text-gray-500 dark:text-gray-400 sm:block lg:hidden">
                      <span>{{ quickdrop.user?.name || 'Anonymous' }}</span>
                      <span class="hidden sm:inline">·</span>
                      <span>{{ quickdrop.files_count }} file{{ quickdrop.files_count !== 1 ? 's' : '' }}</span>
                    </div>
                  </div>
                </div>
                <div v-if="idx !== 0" class="absolute -top-px right-0 left-6 h-px bg-gray-200 dark:bg-gray-700" />
              </td>

              <!-- User -->
              <td :class="[idx === 0 ? '' : 'border-t border-gray-200 dark:border-gray-700', 'hidden px-3 py-3.5 text-sm text-gray-500 dark:text-gray-400 lg:table-cell']">
                <div v-if="quickdrop.user">
                  <div class="text-gray-900 dark:text-white font-medium">{{ quickdrop.user.name }}</div>
                  <div class="mt-1 text-gray-500 dark:text-gray-400">{{ quickdrop.user.email }}</div>
                </div>
                <div v-else class="text-gray-400 dark:text-gray-500">
                  Anonymous
                </div>
              </td>

              <!-- Files -->
              <td :class="[idx === 0 ? '' : 'border-t border-gray-200 dark:border-gray-700', 'hidden px-3 py-3.5 text-sm text-gray-500 dark:text-gray-400 lg:table-cell']">
                <div class="text-gray-900 dark:text-white">
                  {{ quickdrop.files_count }} file{{ quickdrop.files_count !== 1 ? 's' : '' }}
                </div>
                <div class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                  {{ formatBytes(quickdrop.total_size || 0) }}
                </div>
              </td>

              <!-- Status -->
              <td :class="[idx === 0 ? '' : 'border-t border-gray-200 dark:border-gray-700', 'px-3 py-3.5 text-sm text-gray-500 dark:text-gray-400']">
                <span 
                  :class="[
                    'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
                    quickdrop.is_active
                      ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400 dark:ring-green-500/30'
                      : 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-900/30 dark:text-red-400 dark:ring-red-500/30'
                  ]"
                >
                  {{ quickdrop.is_active ? 'Active' : 'Expired' }}
                </span>
                <div v-if="quickdrop.download_count > 0" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  {{ quickdrop.download_count }} download{{ quickdrop.download_count !== 1 ? 's' : '' }}
                </div>
              </td>

              <!-- Expiry -->
              <td :class="[idx === 0 ? '' : 'border-t border-gray-200 dark:border-gray-700', 'px-3 py-3.5 text-sm text-gray-500 dark:text-gray-400']">
                <div class="text-gray-900 dark:text-white">{{ formatDate(quickdrop.expires_at) }}</div>
                <div class="mt-1 text-xs" :class="getExpiryColorClass(quickdrop.expires_at)">
                  {{ formatExpiryTime(quickdrop.expires_at) }}
                </div>
              </td>

              <!-- Actions -->
              <td :class="[idx === 0 ? '' : 'border-t border-transparent', 'relative py-3.5 pr-4 pl-3 text-right text-sm font-medium sm:pr-6']">
                <div class="flex items-center justify-end space-x-2">
                  <!-- View Button -->
                  <Link
                    :href="route('backstage.quickdrops.show', quickdrop.id)"
                    class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-2.5 py-1.5 text-sm font-semibold text-gray-900 dark:text-gray-100 ring-1 ring-gray-300 dark:ring-gray-600 ring-inset hover:bg-gray-50 dark:hover:bg-gray-600"
                  >
                    <EyeIcon class="h-4 w-4 mr-1" />
                    View<span class="sr-only">, {{ quickdrop.reference_number }}</span>
                  </Link>

                  <!-- Dropdown Menu -->
                  <Menu as="div" class="relative">
                    <MenuButton class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-2.5 py-1.5 text-sm font-semibold text-gray-900 dark:text-gray-100 ring-1 ring-gray-300 dark:ring-gray-600 ring-inset hover:bg-gray-50 dark:hover:bg-gray-600">
                      <EllipsisVerticalIcon class="h-4 w-4" />
                      <span class="sr-only">Open options for {{ quickdrop.reference_number }}</span>
                    </MenuButton>
                    <transition
                      enter-active-class="transition ease-out duration-100"
                      enter-from-class="transform opacity-0 scale-95"
                      enter-to-class="transform opacity-100 scale-100"
                      leave-active-class="transition ease-in duration-75"
                      leave-from-class="transform opacity-100 scale-100"
                      leave-to-class="transform opacity-0 scale-95"
                    >
                      <MenuItems class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white dark:bg-gray-800 py-1 ring-1 ring-black ring-opacity-5 focus:outline-none">
                        <MenuItem v-if="quickdrop.is_active" v-slot="{ active }">
                          <button
                            @click="handleAction('extend', quickdrop)"
                            :class="[
                              active ? 'bg-gray-100 dark:bg-gray-700' : '',
                              'block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300'
                            ]"
                          >
                            Extend Expiry
                          </button>
                        </MenuItem>
                        <MenuItem v-slot="{ active }">
                          <a
                            :href="quickdrop.public_url"
                            target="_blank"
                            :class="[
                              active ? 'bg-gray-100 dark:bg-gray-700' : '',
                              'block px-4 py-2 text-sm text-gray-700 dark:text-gray-300'
                            ]"
                          >
                            View Public Page
                          </a>
                        </MenuItem>
                        <MenuItem v-slot="{ active }">
                          <button
                            @click="handleAction('delete', quickdrop)"
                            :class="[
                              active ? 'bg-gray-100 dark:bg-gray-700' : '',
                              'block w-full text-left px-4 py-2 text-sm text-red-700 dark:text-red-400'
                            ]"
                          >
                            Delete QuickDrop
                          </button>
                        </MenuItem>
                      </MenuItems>
                    </transition>
                  </Menu>
                </div>
                <div v-if="idx !== 0" class="absolute -top-px right-6 left-0 h-px bg-gray-200 dark:bg-gray-700" />
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-if="(quickdrops.data || quickdrops).length === 0" class="text-center py-12 px-6">
          <FolderIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No QuickDrops found</h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ searchQuery ? 'Try adjusting your search criteria.' : 'No QuickDrops have been created yet.' }}
          </p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="quickdrops.links" class="mt-6 space-y-4">
        <Pagination 
          :links="quickdrops.links"
          :from="quickdrops.from"
          :to="quickdrops.to"
          :total="quickdrops.total"
          :prev-page-url="quickdrops.prev_page_url"
          :next-page-url="quickdrops.next_page_url"
        />
        
        <!-- Per page selector -->
        <div class="flex justify-end">
          <label class="flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-300">
            <span>Show</span>
            <select 
              v-model="perPage"
              @change="changePerPage"
              class="rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
            <span>per page</span>
          </label>
        </div>
      </div>
    </div>

    <!-- Confirmation Dialogs -->
    <ConfirmationDialog
      v-model:open="showDeleteDialog"
      title="Delete QuickDrop"
      :message="`Are you sure you want to delete this QuickDrop? This action cannot be undone and will remove all associated files.`"
      confirm-text="Delete QuickDrop"
      type="danger"
      :loading="dialogLoading"
      @confirm="confirmDelete"
    />

    <ConfirmationDialog
      v-model:open="showExtendDialog"
      title="Extend QuickDrop"
      :message="`Are you sure you want to extend the expiry time for this QuickDrop?`"
      confirm-text="Extend Expiry"
      type="info"
      :loading="dialogLoading"
      @confirm="confirmExtend"
    />
  </BackstageLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import { debounce } from 'lodash'
import {
  EyeIcon,
  FolderIcon,
  MagnifyingGlassIcon,
  EllipsisVerticalIcon,
} from '@heroicons/vue/24/outline'

import BackstageLayout from '@/Layouts/BackstageLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import ConfirmationDialog from '@/Components/ConfirmationDialog.vue'

const props = defineProps({
  quickdrops: {
    type: [Array, Object],
    required: true
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

// Reactive state
const searchQuery = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || '')
const perPage = ref(props.filters?.per_page || '10')

// Dialog states
const showDeleteDialog = ref(false)
const showExtendDialog = ref(false)
const selectedQuickDrop = ref(null)
const dialogLoading = ref(false)

// Debounced search function
const debouncedSearch = debounce(() => {
  router.get(route('backstage.quickdrops.index'), { 
    search: searchQuery.value,
    status: statusFilter.value,
    per_page: perPage.value 
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}, 300)

// Apply filters
function applyFilters() {
  router.get(route('backstage.quickdrops.index'), { 
    search: searchQuery.value,
    status: statusFilter.value,
    per_page: perPage.value 
  }, {
    preserveState: true,
    preserveScroll: false,
  })
}

// Change per page
function changePerPage() {
  applyFilters()
}

// Utility functions
function formatBytes(bytes) {
  if (!bytes || bytes === 0) return '0 B'
  
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  
  return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
}

function formatDate(dateString) {
  if (!dateString) return 'N/A'
  
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

function formatRelativeTime(dateString) {
  if (!dateString) return ''
  
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = now - date
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) {
    const diffHours = Math.floor(diffTime / (1000 * 60 * 60))
    if (diffHours === 0) {
      const diffMinutes = Math.floor(diffTime / (1000 * 60))
      if (diffMinutes === 0) {
        return 'Just now'
      }
      return `${diffMinutes} minute${diffMinutes > 1 ? 's' : ''} ago`
    }
    return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`
  } else if (diffDays === 1) {
    return 'Yesterday'
  } else if (diffDays < 7) {
    return `${diffDays} days ago`
  } else if (diffDays < 30) {
    const weeks = Math.floor(diffDays / 7)
    return `${weeks} week${weeks > 1 ? 's' : ''} ago`
  } else {
    const months = Math.floor(diffDays / 30)
    return `${months} month${months > 1 ? 's' : ''} ago`
  }
}

function formatExpiryTime(dateString) {
  if (!dateString) return ''
  
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = date - now
  
  if (diffTime < 0) {
    return 'Expired'
  }
  
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) {
    const diffHours = Math.floor(diffTime / (1000 * 60 * 60))
    if (diffHours === 0) {
      const diffMinutes = Math.floor(diffTime / (1000 * 60))
      return `Expires in ${diffMinutes} minute${diffMinutes !== 1 ? 's' : ''}`
    }
    return `Expires in ${diffHours} hour${diffHours !== 1 ? 's' : ''}`
  } else if (diffDays === 1) {
    return 'Expires tomorrow'
  } else {
    return `Expires in ${diffDays} days`
  }
}

function getExpiryColorClass(dateString) {
  if (!dateString) return 'text-gray-400 dark:text-gray-500'
  
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = date - now
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays < 0) {
    return 'text-red-600 dark:text-red-400'
  } else if (diffDays <= 1) {
    return 'text-yellow-600 dark:text-yellow-400'
  } else {
    return 'text-green-600 dark:text-green-400'
  }
}

function handleAction(action, quickdrop) {
  selectedQuickDrop.value = quickdrop
  
  switch (action) {
    case 'extend':
      showExtendDialog.value = true
      break
    case 'delete':
      showDeleteDialog.value = true
      break
  }
}

// Dialog confirmation handlers
function confirmDelete() {
  dialogLoading.value = true
  router.delete(route('backstage.quickdrops.destroy', selectedQuickDrop.value.id), {
    onFinish: () => {
      dialogLoading.value = false
      showDeleteDialog.value = false
    }
  })
}

function confirmExtend() {
  dialogLoading.value = true
  router.post(route('backstage.quickdrops.extend', selectedQuickDrop.value.id), {}, {
    onFinish: () => {
      dialogLoading.value = false
      showExtendDialog.value = false
    }
  })
}
</script>