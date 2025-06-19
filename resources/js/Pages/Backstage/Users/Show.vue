<template>
  <BackstageLayout>
    <Head :title="`User Details - ${user.name}`" />
    
    <div class="px-4 sm:px-6 lg:px-8">
      <!-- Page Header -->
      <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
          <!-- Breadcrumb -->
          <nav class="flex items-center space-x-2 text-sm/6 text-gray-500 dark:text-gray-400 mb-4">
            <Link :href="route('backstage.users.index')" class="hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
              Users
            </Link>
            <ChevronRightIcon class="w-4 h-4" />
            <span class="text-gray-900 dark:text-white font-medium">User Details</span>
          </nav>
          
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">User Account Details</h1>
          <p class="mt-2 text-base/7 text-gray-600 dark:text-gray-400">
            Complete account information for {{ user.name }}
          </p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
          <Link
            :href="route('backstage.users.edit', user.id)" 
            class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 ring-1 ring-gray-300 dark:ring-gray-600 ring-inset hover:bg-gray-50 dark:hover:bg-gray-600"
          >
            <PencilIcon class="h-4 w-4 mr-1" />
            Edit User
          </Link>
        </div>
      </div>

      <!-- User Profile Section -->
      <div class="mt-8 space-y-8">
        <div class="overflow-hidden bg-white dark:bg-gray-800 rounded-lg ring-1 ring-gray-300 dark:ring-gray-600">
          <div class="px-4 py-6 sm:px-6">
            <div class="flex items-center">
              <!-- Avatar -->
              <div class="h-20 w-20 flex-shrink-0">
                <div class="h-20 w-20 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                  <span class="text-2xl font-bold text-white">
                    {{ getInitials(user.name) }}
                  </span>
                </div>
              </div>
              
              <!-- User Info -->
              <div class="ml-6 flex-1">
                <div class="flex items-center">
                  <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ user.name }}
                  </h2>
                </div>
                <p class="text-base/6 text-gray-600 dark:text-gray-400">{{ user.email }}</p>
                <p class="text-sm/6 text-gray-500 dark:text-gray-500 mt-1">User ID: {{ user.id }}</p>
              </div>
              
              <!-- Status Badge -->
              <div class="flex items-center space-x-3">
                <span 
                  :class="[
                    'inline-flex items-center rounded-md px-3 py-2 text-sm font-medium ring-1 ring-inset',
                    user.email_verified_at
                      ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400 dark:ring-green-500/30'
                      : 'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-900/30 dark:text-yellow-400 dark:ring-yellow-500/30'
                  ]"
                >
                  {{ user.email_verified_at ? 'Verified' : 'Unverified' }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Account Information Grid -->
        <div class="space-y-8">
          <!-- Account Details Table -->
          <div class="-mx-4 mt-10 ring-1 ring-gray-300 dark:ring-gray-600 sm:mx-0 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
              <thead>
                <tr>
                  <th scope="col" class="py-3.5 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 dark:text-white sm:pl-6">Account Information</th>
                  <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">Details</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="relative py-4 pr-3 pl-4 text-sm sm:pl-6">
                    <div class="font-medium text-gray-900 dark:text-white">Storage Used</div>
                  </td>
                  <td class="px-3 py-3.5 text-sm text-gray-500 dark:text-gray-400">
                    <div class="text-gray-900 dark:text-white font-mono">{{ formatBytes(user.storage_used || 0) }}</div>
                    <div v-if="user.storage_limit" class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                      of {{ formatBytes(user.storage_limit) }} ({{ Math.round((user.storage_used / user.storage_limit) * 100) }}%)
                    </div>
                  </td>
                </tr>
                <tr>
                  <td class="border-t border-transparent relative py-4 pr-3 pl-4 text-sm sm:pl-6">
                    <div class="font-medium text-gray-900 dark:text-white">Total QuickDrops</div>
                    <div class="absolute -top-px right-0 left-6 h-px bg-gray-200 dark:bg-gray-700" />
                  </td>
                  <td class="border-t border-gray-200 dark:border-gray-700 px-3 py-3.5 text-sm text-gray-500 dark:text-gray-400">
                    <div class="text-gray-900 dark:text-white">{{ user.quickdrops_count || 0 }}</div>
                    <div v-if="user.active_quickdrops_count > 0" class="mt-1 text-xs text-green-600 dark:text-green-400">
                      {{ user.active_quickdrops_count }} active
                    </div>
                  </td>
                </tr>
                <tr>
                  <td class="border-t border-transparent relative py-4 pr-3 pl-4 text-sm sm:pl-6">
                    <div class="font-medium text-gray-900 dark:text-white">Total Downloads</div>
                    <div class="absolute -top-px right-0 left-6 h-px bg-gray-200 dark:bg-gray-700" />
                  </td>
                  <td class="border-t border-gray-200 dark:border-gray-700 px-3 py-3.5 text-sm text-gray-500 dark:text-gray-400">
                    <div class="text-gray-900 dark:text-white">{{ user.total_downloads || 0 }}</div>
                  </td>
                </tr>
                <tr>
                  <td class="border-t border-transparent relative py-4 pr-3 pl-4 text-sm sm:pl-6">
                    <div class="font-medium text-gray-900 dark:text-white">Account Status</div>
                    <div class="absolute -top-px right-0 left-6 h-px bg-gray-200 dark:bg-gray-700" />
                  </td>
                  <td class="border-t border-gray-200 dark:border-gray-700 px-3 py-3.5 text-sm text-gray-500 dark:text-gray-400">
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
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Contact & Timing Information -->
          <div class="-mx-4 mt-10 ring-1 ring-gray-300 dark:ring-gray-600 sm:mx-0 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
              <thead>
                <tr>
                  <th scope="col" class="py-3.5 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 dark:text-white sm:pl-6">Contact & Timing</th>
                  <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">Details</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="relative py-4 pr-3 pl-4 text-sm sm:pl-6">
                    <div class="font-medium text-gray-900 dark:text-white">Full Name</div>
                  </td>
                  <td class="px-3 py-3.5 text-sm text-gray-500 dark:text-gray-400">
                    <div class="text-gray-900 dark:text-white">{{ user.name }}</div>
                  </td>
                </tr>
                <tr>
                  <td class="border-t border-transparent relative py-4 pr-3 pl-4 text-sm sm:pl-6">
                    <div class="font-medium text-gray-900 dark:text-white">Email Address</div>
                    <div class="absolute -top-px right-0 left-6 h-px bg-gray-200 dark:bg-gray-700" />
                  </td>
                  <td class="border-t border-gray-200 dark:border-gray-700 px-3 py-3.5 text-sm text-gray-500 dark:text-gray-400">
                    <a :href="`mailto:${user.email}`" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300">
                      {{ user.email }}
                    </a>
                  </td>
                </tr>
                <tr>
                  <td class="border-t border-transparent relative py-4 pr-3 pl-4 text-sm sm:pl-6">
                    <div class="font-medium text-gray-900 dark:text-white">Joined Date</div>
                    <div class="absolute -top-px right-0 left-6 h-px bg-gray-200 dark:bg-gray-700" />
                  </td>
                  <td class="border-t border-gray-200 dark:border-gray-700 px-3 py-3.5 text-sm text-gray-500 dark:text-gray-400">
                    <div class="text-gray-900 dark:text-white">{{ formatDateTime(user.created_at) }}</div>
                    <div class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                      {{ formatRelativeTime(user.created_at) }}
                    </div>
                  </td>
                </tr>
                <tr>
                  <td class="border-t border-transparent relative py-4 pr-3 pl-4 text-sm sm:pl-6">
                    <div class="font-medium text-gray-900 dark:text-white">Last Login</div>
                    <div class="absolute -top-px right-0 left-6 h-px bg-gray-200 dark:bg-gray-700" />
                  </td>
                  <td class="border-t border-gray-200 dark:border-gray-700 px-3 py-3.5 text-sm text-gray-500 dark:text-gray-400">
                    <div v-if="user.last_login_at" class="text-gray-900 dark:text-white">
                      {{ formatDateTime(user.last_login_at) }}
                      <div class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                        {{ formatRelativeTime(user.last_login_at) }}
                      </div>
                    </div>
                    <div v-else class="text-gray-400 dark:text-gray-500">Never</div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Recent QuickDrops -->
        <div v-if="recentQuickDrops.length > 0" class="-mx-4 mt-10 ring-1 ring-gray-300 dark:ring-gray-600 sm:mx-0 sm:rounded-lg">
          <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white">Recent QuickDrops</h3>
          </div>
          <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
            <li v-for="quickdrop in recentQuickDrops" :key="quickdrop.id" class="px-4 py-4 sm:px-6">
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <FolderIcon class="h-8 w-8 text-gray-400" />
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ quickdrop.files_count }} file{{ quickdrop.files_count !== 1 ? 's' : '' }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                      Created {{ formatRelativeTime(quickdrop.created_at) }}
                    </p>
                  </div>
                </div>
                <div class="flex items-center space-x-2">
                  <span v-if="quickdrop.is_active" class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-400">
                    Active
                  </span>
                  <span v-else class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                    Expired
                  </span>
                  <Link
                    :href="route('backstage.quickdrops.show', quickdrop.id)"
                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300"
                  >
                    View
                  </Link>
                </div>
              </div>
            </li>
          </ul>
        </div>

        <!-- Action Buttons Section -->
        <div class="-mx-4 mt-10 ring-1 ring-gray-300 dark:ring-gray-600 sm:mx-0 sm:rounded-lg">
          <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
            <thead>
              <tr>
                <th scope="col" class="py-3.5 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 dark:text-white sm:pl-6">Account Actions</th>
                <th scope="col" class="relative py-3.5 pr-4 pl-3 sm:pr-6">
                  <span class="sr-only">Actions</span>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!user.email_verified_at">
                <td class="relative py-4 pr-3 pl-4 text-sm sm:pl-6">
                  <div class="font-medium text-gray-900 dark:text-white">Verify Email</div>
                  <div class="mt-1 text-gray-500 dark:text-gray-400">Manually verify this user's email address</div>
                </td>
                <td class="relative py-3.5 pr-4 pl-3 text-right text-sm font-medium sm:pr-6">
                  <button
                    @click="handleAction('verify')"
                    class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-2.5 py-1.5 text-sm font-semibold text-gray-900 dark:text-gray-100 ring-1 ring-gray-300 dark:ring-gray-600 ring-inset hover:bg-gray-50 dark:hover:bg-gray-600"
                  >
                    Verify<span class="sr-only">, email</span>
                  </button>
                </td>
              </tr>
              <tr>
                <td class="border-t border-transparent relative py-4 pr-3 pl-4 text-sm sm:pl-6">
                  <div class="font-medium text-gray-900 dark:text-white">Reset Password</div>
                  <div class="mt-1 text-gray-500 dark:text-gray-400">Send a password reset link to the user</div>
                  <div v-if="!user.email_verified_at" class="absolute -top-px right-0 left-6 h-px bg-gray-200 dark:bg-gray-700" />
                </td>
                <td class="border-t border-transparent relative py-3.5 pr-4 pl-3 text-right text-sm font-medium sm:pr-6">
                  <button
                    @click="handleAction('reset-password')"
                    class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-2.5 py-1.5 text-sm font-semibold text-gray-900 dark:text-gray-100 ring-1 ring-gray-300 dark:ring-gray-600 ring-inset hover:bg-gray-50 dark:hover:bg-gray-600"
                  >
                    Reset<span class="sr-only">, password</span>
                  </button>
                  <div v-if="!user.email_verified_at" class="absolute -top-px right-6 left-0 h-px bg-gray-200 dark:bg-gray-700" />
                </td>
              </tr>
              <tr>
                <td class="border-t border-transparent relative py-4 pr-3 pl-4 text-sm sm:pl-6">
                  <div class="font-medium text-gray-900 dark:text-white">Suspend User</div>
                  <div class="mt-1 text-gray-500 dark:text-gray-400">Temporarily suspend this user's account</div>
                  <div class="absolute -top-px right-0 left-6 h-px bg-gray-200 dark:bg-gray-700" />
                </td>
                <td class="border-t border-transparent relative py-3.5 pr-4 pl-3 text-right text-sm font-medium sm:pr-6">
                  <button
                    @click="handleAction('suspend')"
                    class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-2.5 py-1.5 text-sm font-semibold text-gray-900 dark:text-gray-100 ring-1 ring-gray-300 dark:ring-gray-600 ring-inset hover:bg-gray-50 dark:hover:bg-gray-600"
                  >
                    Suspend<span class="sr-only">, user</span>
                  </button>
                  <div class="absolute -top-px right-6 left-0 h-px bg-gray-200 dark:bg-gray-700" />
                </td>
              </tr>
              <tr>
                <td class="border-t border-transparent relative py-4 pr-3 pl-4 text-sm sm:pl-6">
                  <div class="font-medium text-gray-900 dark:text-white">Delete User</div>
                  <div class="mt-1 text-gray-500 dark:text-gray-400">Permanently delete this user and all their data</div>
                  <div class="absolute -top-px right-0 left-6 h-px bg-gray-200 dark:bg-gray-700" />
                </td>
                <td class="border-t border-transparent relative py-3.5 pr-4 pl-3 text-right text-sm font-medium sm:pr-6">
                  <button
                    @click="handleAction('delete')"
                    class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-2.5 py-1.5 text-sm font-semibold text-red-700 dark:text-red-400 ring-1 ring-gray-300 dark:ring-gray-600 ring-inset hover:bg-gray-50 dark:hover:bg-gray-600"
                  >
                    Delete<span class="sr-only">, user</span>
                  </button>
                  <div class="absolute -top-px right-6 left-0 h-px bg-gray-200 dark:bg-gray-700" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Confirmation Dialogs -->
    <ConfirmationDialog
      v-model:open="showVerifyDialog"
      title="Verify Email"
      :message="`Are you sure you want to manually verify the email address for ${user.name}?`"
      confirm-text="Verify Email"
      type="info"
      :loading="dialogLoading"
      @confirm="confirmVerify"
    />

    <ConfirmationDialog
      v-model:open="showResetDialog"
      title="Reset Password"
      :message="`Are you sure you want to send a password reset link to ${user.name}? They will receive an email at ${user.email}.`"
      confirm-text="Send Reset Link"
      type="warning"
      :loading="dialogLoading"
      @confirm="confirmReset"
    />

    <ConfirmationDialog
      v-model:open="showSuspendDialog"
      title="Suspend User"
      :message="`Are you sure you want to suspend ${user.name}? They will not be able to access their account.`"
      confirm-text="Suspend User"
      type="warning"
      :loading="dialogLoading"
      @confirm="confirmSuspend"
    />

    <ConfirmationDialog
      v-model:open="showDeleteDialog"
      title="Delete User"
      :message="`Are you sure you want to delete ${user.name}? This action cannot be undone and will remove all their QuickDrops.`"
      confirm-text="Delete User"
      type="danger"
      :loading="dialogLoading"
      @confirm="confirmDelete"
    />
  </BackstageLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import {
  ChevronRightIcon,
  PencilIcon,
  FolderIcon,
} from '@heroicons/vue/24/outline'

import BackstageLayout from '@/Layouts/BackstageLayout.vue'
import ConfirmationDialog from '@/Components/ConfirmationDialog.vue'

const props = defineProps({
  user: {
    type: Object,
    required: true
  },
  recentQuickDrops: {
    type: Array,
    default: () => []
  }
})

// Dialog states
const showVerifyDialog = ref(false)
const showResetDialog = ref(false)
const showSuspendDialog = ref(false)
const showDeleteDialog = ref(false)
const dialogLoading = ref(false)

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

function formatDateTime(dateString) {
  if (!dateString) return 'Not available'
  
  try {
    const date = new Date(dateString)
    if (isNaN(date.getTime())) return 'Invalid date'
    
    return date.toLocaleDateString('en-US', {
      month: 'long',
      day: 'numeric',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch (error) {
    return 'Invalid date'
  }
}

function formatRelativeTime(dateString) {
  if (!dateString) return ''
  
  try {
    const date = new Date(dateString)
    if (isNaN(date.getTime())) return 'Invalid date'
    
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
    } else if (diffDays < 365) {
      const months = Math.floor(diffDays / 30)
      return `${months} month${months > 1 ? 's' : ''} ago`
    } else {
      const years = Math.floor(diffDays / 365)
      return `${years} year${years > 1 ? 's' : ''} ago`
    }
  } catch (error) {
    return 'Invalid date'
  }
}

// Methods
function handleAction(action) {
  switch (action) {
    case 'verify':
      showVerifyDialog.value = true
      break
    case 'reset-password':
      showResetDialog.value = true
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
function confirmVerify() {
  dialogLoading.value = true
  router.post(route('backstage.users.verify', props.user.id), {}, {
    onFinish: () => {
      dialogLoading.value = false
      showVerifyDialog.value = false
    }
  })
}

function confirmReset() {
  dialogLoading.value = true
  router.post(route('backstage.users.reset-password', props.user.id), {}, {
    onFinish: () => {
      dialogLoading.value = false
      showResetDialog.value = false
    }
  })
}

function confirmSuspend() {
  dialogLoading.value = true
  router.post(route('backstage.users.suspend', props.user.id), {}, {
    onFinish: () => {
      dialogLoading.value = false
      showSuspendDialog.value = false
    }
  })
}

function confirmDelete() {
  dialogLoading.value = true
  router.delete(route('backstage.users.destroy', props.user.id), {
    onFinish: () => {
      dialogLoading.value = false
      showDeleteDialog.value = false
    }
  })
}
</script>