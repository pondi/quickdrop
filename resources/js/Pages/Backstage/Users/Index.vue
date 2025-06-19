<template>
  <BackstageLayout>
    <Head title="User Management" />
    
    <div class="px-4 sm:px-6 lg:px-8">
      <!-- Page Header -->
      <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
          <h1 class="text-base font-semibold text-gray-900 dark:text-white">User Management</h1>
          <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">Manage all registered users and their accounts</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none flex items-center space-x-4">
          <span class="text-sm text-gray-500 dark:text-gray-400">{{ users.total || users.length }} users</span>
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
              placeholder="Search by name or email..."
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
              <option value="">All Users</option>
              <option value="verified">Verified</option>
              <option value="unverified">Unverified</option>
            </select>
          </label>
        </div>
      </div>

      <!-- Users Table -->
      <div class="-mx-4 mt-10 ring-1 ring-gray-300 dark:ring-gray-600 sm:mx-0 sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
          <thead>
            <tr>
              <th scope="col" class="py-3.5 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 dark:text-white sm:pl-6">
                User
              </th>
              <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white lg:table-cell">
                Storage Used
              </th>
              <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white lg:table-cell">
                QuickDrops
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">
                Status
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">
                Joined
              </th>
              <th scope="col" class="relative py-3.5 pr-4 pl-3 sm:pr-6">
                <span class="sr-only">Actions</span>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(user, userIdx) in users.data || users" :key="user.id">
              <!-- User Info -->
              <td :class="[userIdx === 0 ? '' : 'border-t border-transparent', 'relative py-4 pr-3 pl-4 text-sm sm:pl-6']">
                <div class="flex items-center">
                  <div class="h-11 w-11 flex-shrink-0">
                    <div class="h-11 w-11 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                      <span class="text-sm font-medium text-white">
                        {{ getInitials(user.name) }}
                      </span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="font-medium text-gray-900 dark:text-white">{{ user.name }}</div>
                    <div class="mt-1 text-gray-500 dark:text-gray-400">{{ user.email }}</div>
                    <div class="mt-1 flex flex-col text-gray-500 dark:text-gray-400 sm:block lg:hidden">
                      <span>{{ formatBytes(user.storage_used || 0) }}</span>
                      <span class="hidden sm:inline">·</span>
                      <span>{{ user.quickdrops_count || 0 }} QuickDrops</span>
                    </div>
                  </div>
                </div>
                <div v-if="userIdx !== 0" class="absolute -top-px right-0 left-6 h-px bg-gray-200 dark:bg-gray-700" />
              </td>

              <!-- Storage -->
              <td :class="[userIdx === 0 ? '' : 'border-t border-gray-200 dark:border-gray-700', 'hidden px-3 py-3.5 text-sm text-gray-500 dark:text-gray-400 lg:table-cell']">
                <div class="text-gray-900 dark:text-white font-mono">
                  {{ formatBytes(user.storage_used || 0) }}
                </div>
                <div v-if="user.storage_limit" class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                  of {{ formatBytes(user.storage_limit) }}
                </div>
              </td>

              <!-- QuickDrops -->
              <td :class="[userIdx === 0 ? '' : 'border-t border-gray-200 dark:border-gray-700', 'hidden px-3 py-3.5 text-sm text-gray-500 dark:text-gray-400 lg:table-cell']">
                <div class="text-gray-900 dark:text-white">
                  {{ user.quickdrops_count || 0 }}
                </div>
                <div v-if="user.active_quickdrops_count > 0" class="mt-1 text-xs text-green-600 dark:text-green-400">
                  {{ user.active_quickdrops_count }} active
                </div>
              </td>

              <!-- Status -->
              <td :class="[userIdx === 0 ? '' : 'border-t border-gray-200 dark:border-gray-700', 'px-3 py-3.5 text-sm text-gray-500 dark:text-gray-400']">
                <span 
                  :class="[
                    'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
                    user.email_verified_at
                      ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400 dark:ring-green-500/30'
                      : 'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-900/30 dark:text-yellow-400 dark:ring-yellow-500/30'
                  ]"
                >
                  {{ user.email_verified_at ? 'Verified' : 'Unverified' }}
                </span>
              </td>

              <!-- Joined Date -->
              <td :class="[userIdx === 0 ? '' : 'border-t border-gray-200 dark:border-gray-700', 'px-3 py-3.5 text-sm text-gray-500 dark:text-gray-400']">
                <div class="text-gray-900 dark:text-white">{{ formatDate(user.created_at) }}</div>
                <div class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                  {{ formatRelativeTime(user.created_at) }}
                </div>
              </td>

              <!-- Actions -->
              <td :class="[userIdx === 0 ? '' : 'border-t border-transparent', 'relative py-3.5 pr-4 pl-3 text-right text-sm font-medium sm:pr-6']">
                <div class="flex items-center justify-end space-x-2">
                  <!-- View Button -->
                  <Link
                    :href="route('backstage.users.show', user.id)"
                    class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-2.5 py-1.5 text-sm font-semibold text-gray-900 dark:text-gray-100 ring-1 ring-gray-300 dark:ring-gray-600 ring-inset hover:bg-gray-50 dark:hover:bg-gray-600"
                  >
                    <EyeIcon class="h-4 w-4 mr-1" />
                    View<span class="sr-only">, {{ user.name }}</span>
                  </Link>

                  <!-- Dropdown Menu -->
                  <Menu as="div" class="relative">
                    <MenuButton class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-2.5 py-1.5 text-sm font-semibold text-gray-900 dark:text-gray-100 ring-1 ring-gray-300 dark:ring-gray-600 ring-inset hover:bg-gray-50 dark:hover:bg-gray-600">
                      <EllipsisVerticalIcon class="h-4 w-4" />
                      <span class="sr-only">Open options for {{ user.name }}</span>
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
                        <MenuItem v-slot="{ active }">
                          <button
                            @click="handleAction('edit', user)"
                            :class="[
                              active ? 'bg-gray-100 dark:bg-gray-700' : '',
                              'block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300'
                            ]"
                          >
                            Edit User
                          </button>
                        </MenuItem>
                        <MenuItem v-if="!user.email_verified_at" v-slot="{ active }">
                          <button
                            @click="handleAction('verify', user)"
                            :class="[
                              active ? 'bg-gray-100 dark:bg-gray-700' : '',
                              'block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300'
                            ]"
                          >
                            Verify Email
                          </button>
                        </MenuItem>
                        <MenuItem v-slot="{ active }">
                          <button
                            @click="handleAction('suspend', user)"
                            :class="[
                              active ? 'bg-gray-100 dark:bg-gray-700' : '',
                              'block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300'
                            ]"
                          >
                            Suspend User
                          </button>
                        </MenuItem>
                        <MenuItem v-slot="{ active }">
                          <button
                            @click="handleAction('delete', user)"
                            :class="[
                              active ? 'bg-gray-100 dark:bg-gray-700' : '',
                              'block w-full text-left px-4 py-2 text-sm text-red-700 dark:text-red-400'
                            ]"
                          >
                            Delete User
                          </button>
                        </MenuItem>
                      </MenuItems>
                    </transition>
                  </Menu>
                </div>
                <div v-if="userIdx !== 0" class="absolute -top-px right-6 left-0 h-px bg-gray-200 dark:bg-gray-700" />
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-if="(users.data || users).length === 0" class="text-center py-12 px-6">
          <UsersIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No users found</h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ searchQuery ? 'Try adjusting your search criteria.' : 'No users have registered yet.' }}
          </p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="users.links" class="mt-6 space-y-4">
        <Pagination 
          :links="users.links"
          :from="users.from"
          :to="users.to"
          :total="users.total"
          :prev-page-url="users.prev_page_url"
          :next-page-url="users.next_page_url"
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
      title="Delete User"
      :message="`Are you sure you want to delete ${selectedUser?.name}? This action cannot be undone and will remove all their QuickDrops.`"
      confirm-text="Delete User"
      type="danger"
      :loading="dialogLoading"
      @confirm="confirmDelete"
    />

    <ConfirmationDialog
      v-model:open="showSuspendDialog"
      title="Suspend User"
      :message="`Are you sure you want to suspend ${selectedUser?.name}? They will not be able to access their account.`"
      confirm-text="Suspend User"
      type="warning"
      :loading="dialogLoading"
      @confirm="confirmSuspend"
    />
  </BackstageLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import { debounce } from 'lodash'
import {
  EyeIcon,
  UsersIcon,
  MagnifyingGlassIcon,
  EllipsisVerticalIcon,
} from '@heroicons/vue/24/outline'

import BackstageLayout from '@/Layouts/BackstageLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import ConfirmationDialog from '@/Components/ConfirmationDialog.vue'

const props = defineProps({
  users: {
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
const showSuspendDialog = ref(false)
const selectedUser = ref(null)
const dialogLoading = ref(false)

// Debounced search function
const debouncedSearch = debounce(() => {
  router.get(route('backstage.users.index'), { 
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
  router.get(route('backstage.users.index'), { 
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
function getInitials(name) {
  if (!name) return '?'
  const words = name.split(' ')
  if (words.length >= 2) {
    return (words[0][0] + words[1][0]).toUpperCase()
  }
  return name.substring(0, 2).toUpperCase()
}

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
    return 'Today'
  } else if (diffDays === 1) {
    return 'Yesterday'
  } else if (diffDays < 7) {
    return `${diffDays} days ago`
  } else if (diffDays < 30) {
    const weeks = Math.floor(diffDays / 7)
    return `${weeks} week${weeks > 1 ? 's' : ''} ago`
  } else if (diffDays < 365) {
    const months = Math.floor(diffDays / 30)
    return `${months} month${months > 1 ? 's' : ''} ago`
  } else {
    const years = Math.floor(diffDays / 365)
    return `${years} year${years > 1 ? 's' : ''} ago`
  }
}

function handleAction(action, user) {
  selectedUser.value = user
  
  switch (action) {
    case 'edit':
      router.visit(route('backstage.users.edit', user.id))
      break
    case 'verify':
      router.post(route('backstage.users.verify', user.id))
      break
    case 'suspend':
      showSuspendDialog.value = true
      break
    case 'delete':
      showDeleteDialog.value = true
      break
  }
}

// Dialog confirmation handlers
function confirmDelete() {
  dialogLoading.value = true
  router.delete(route('backstage.users.destroy', selectedUser.value.id), {
    onFinish: () => {
      dialogLoading.value = false
      showDeleteDialog.value = false
    }
  })
}

function confirmSuspend() {
  dialogLoading.value = true
  router.post(route('backstage.users.suspend', selectedUser.value.id), {}, {
    onFinish: () => {
      dialogLoading.value = false
      showSuspendDialog.value = false
    }
  })
}
</script>