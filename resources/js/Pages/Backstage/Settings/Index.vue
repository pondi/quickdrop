<template>
  <BackstageLayout>
    <Head title="System Settings" />
    
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">System Settings</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">Configure QuickDrop system parameters</p>
        </div>
        <div class="flex items-center space-x-2">
          <Badge variant="secondary">
            {{ Object.keys(settings).length }} settings
          </Badge>
        </div>
      </div>

      <!-- Flash Messages -->
      <div v-if="$page.props.flash.success" class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 border border-green-200 dark:border-green-700">
        <div class="flex">
          <CheckCircleIcon class="h-5 w-5 text-green-400" />
          <div class="ml-3">
            <p class="text-sm font-medium text-green-800 dark:text-green-200">
              {{ $page.props.flash.success }}
            </p>
          </div>
        </div>
      </div>

      <div v-if="$page.props.flash.error" class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 border border-red-200 dark:border-red-700">
        <div class="flex">
          <XCircleIcon class="h-5 w-5 text-red-400" />
          <div class="ml-3">
            <p class="text-sm font-medium text-red-800 dark:text-red-200">
              {{ $page.props.flash.error }}
            </p>
          </div>
        </div>
      </div>

      <!-- Settings Form -->
      <form @submit.prevent="submit">
        <div class="space-y-6">
          <!-- General Settings -->
          <Card>
            <CardHeader>
              <CardTitle>General Settings</CardTitle>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Configure basic QuickDrop functionality
              </p>
            </CardHeader>
            <CardContent class="pt-2 space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                <Label for="app_name" class="text-sm font-medium">
                  Application Name
                </Label>
                <div class="md:col-span-2 space-y-1">
                  <Input
                    id="app_name"
                    v-model="form.app_name"
                    type="text"
                    placeholder="QuickDrop"
                    :class="[
                      'w-full',
                      form.errors.app_name ? 'border-red-500 focus:ring-red-500' : ''
                    ]"
                  />
                  <p v-if="form.errors.app_name" class="text-sm text-red-600">
                    {{ form.errors.app_name }}
                  </p>
                  <p class="text-xs text-gray-500">
                    The name of your application
                  </p>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                <Label for="require_reference_number" class="text-sm font-medium">
                  Require Reference Number
                </Label>
                <div class="md:col-span-2 space-y-1">
                  <select
                    id="require_reference_number"
                    v-model="form.require_reference_number"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  >
                    <option value="true">Yes</option>
                    <option value="false">No</option>
                  </select>
                  <p class="text-xs text-gray-500">
                    Whether users must provide a reference number when creating QuickDrops
                  </p>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                <Label for="default_expiry_hours" class="text-sm font-medium">
                  Default Expiry (Hours)
                </Label>
                <div class="md:col-span-2 space-y-1">
                  <Input
                    id="default_expiry_hours"
                    v-model="form.default_expiry_hours"
                    type="number"
                    min="1"
                    max="720"
                    placeholder="72"
                    :class="[
                      'w-full',
                      form.errors.default_expiry_hours ? 'border-red-500 focus:ring-red-500' : ''
                    ]"
                  />
                  <p v-if="form.errors.default_expiry_hours" class="text-sm text-red-600">
                    {{ form.errors.default_expiry_hours }}
                  </p>
                  <p class="text-xs text-gray-500">
                    Default number of hours before QuickDrops expire (1-720)
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Storage Settings -->
          <Card>
            <CardHeader>
              <CardTitle>Storage Settings</CardTitle>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Configure file storage limits and options
              </p>
            </CardHeader>
            <CardContent class="pt-2 space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                <Label for="max_file_size_mb" class="text-sm font-medium">
                  Max File Size (MB)
                </Label>
                <div class="md:col-span-2 space-y-1">
                  <Input
                    id="max_file_size_mb"
                    v-model="form.max_file_size_mb"
                    type="number"
                    min="1"
                    max="5120"
                    placeholder="100"
                    :class="[
                      'w-full',
                      form.errors.max_file_size_mb ? 'border-red-500 focus:ring-red-500' : ''
                    ]"
                  />
                  <p v-if="form.errors.max_file_size_mb" class="text-sm text-red-600">
                    {{ form.errors.max_file_size_mb }}
                  </p>
                  <p class="text-xs text-gray-500">
                    Maximum file size allowed per upload in megabytes
                  </p>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                <Label for="max_files_per_quickdrop" class="text-sm font-medium">
                  Max Files per QuickDrop
                </Label>
                <div class="md:col-span-2 space-y-1">
                  <Input
                    id="max_files_per_quickdrop"
                    v-model="form.max_files_per_quickdrop"
                    type="number"
                    min="1"
                    max="100"
                    placeholder="10"
                    :class="[
                      'w-full',
                      form.errors.max_files_per_quickdrop ? 'border-red-500 focus:ring-red-500' : ''
                    ]"
                  />
                  <p v-if="form.errors.max_files_per_quickdrop" class="text-sm text-red-600">
                    {{ form.errors.max_files_per_quickdrop }}
                  </p>
                  <p class="text-xs text-gray-500">
                    Maximum number of files allowed in a single QuickDrop
                  </p>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                <Label for="max_user_storage_gb" class="text-sm font-medium">
                  User Storage Limit (GB)
                </Label>
                <div class="md:col-span-2 space-y-1">
                  <Input
                    id="max_user_storage_gb"
                    v-model="form.max_user_storage_gb"
                    type="number"
                    min="1"
                    max="1000"
                    placeholder="10"
                    :class="[
                      'w-full',
                      form.errors.max_user_storage_gb ? 'border-red-500 focus:ring-red-500' : ''
                    ]"
                  />
                  <p v-if="form.errors.max_user_storage_gb" class="text-sm text-red-600">
                    {{ form.errors.max_user_storage_gb }}
                  </p>
                  <p class="text-xs text-gray-500">
                    Default storage limit per user in gigabytes
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Email Settings -->
          <Card>
            <CardHeader>
              <CardTitle>Email Settings</CardTitle>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Configure email notifications and templates
              </p>
            </CardHeader>
            <CardContent class="pt-2 space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                <Label for="mail_from_address" class="text-sm font-medium">
                  From Email Address
                </Label>
                <div class="md:col-span-2 space-y-1">
                  <Input
                    id="mail_from_address"
                    v-model="form.mail_from_address"
                    type="email"
                    placeholder="noreply@example.com"
                    :class="[
                      'w-full',
                      form.errors.mail_from_address ? 'border-red-500 focus:ring-red-500' : ''
                    ]"
                  />
                  <p v-if="form.errors.mail_from_address" class="text-sm text-red-600">
                    {{ form.errors.mail_from_address }}
                  </p>
                  <p class="text-xs text-gray-500">
                    Email address used as sender for system emails
                  </p>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                <Label for="mail_from_name" class="text-sm font-medium">
                  From Name
                </Label>
                <div class="md:col-span-2 space-y-1">
                  <Input
                    id="mail_from_name"
                    v-model="form.mail_from_name"
                    type="text"
                    placeholder="QuickDrop"
                    :class="[
                      'w-full',
                      form.errors.mail_from_name ? 'border-red-500 focus:ring-red-500' : ''
                    ]"
                  />
                  <p v-if="form.errors.mail_from_name" class="text-sm text-red-600">
                    {{ form.errors.mail_from_name }}
                  </p>
                  <p class="text-xs text-gray-500">
                    Name displayed as sender for system emails
                  </p>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                <Label for="enable_email_notifications" class="text-sm font-medium">
                  Enable Email Notifications
                </Label>
                <div class="md:col-span-2 space-y-1">
                  <select
                    id="enable_email_notifications"
                    v-model="form.enable_email_notifications"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  >
                    <option value="true">Yes</option>
                    <option value="false">No</option>
                  </select>
                  <p class="text-xs text-gray-500">
                    Send email notifications for QuickDrop activities
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Security Settings -->
          <Card>
            <CardHeader>
              <CardTitle>Security Settings</CardTitle>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Configure security and access control
              </p>
            </CardHeader>
            <CardContent class="pt-2 space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                <Label for="allow_public_upload" class="text-sm font-medium">
                  Allow Public Uploads
                </Label>
                <div class="md:col-span-2 space-y-1">
                  <select
                    id="allow_public_upload"
                    v-model="form.allow_public_upload"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  >
                    <option value="true">Yes</option>
                    <option value="false">No</option>
                  </select>
                  <p class="text-xs text-gray-500">
                    Allow anonymous users to upload files to QuickDrops
                  </p>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                <Label for="require_auth_for_download" class="text-sm font-medium">
                  Require Auth for Downloads
                </Label>
                <div class="md:col-span-2 space-y-1">
                  <select
                    id="require_auth_for_download"
                    v-model="form.require_auth_for_download"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  >
                    <option value="true">Yes</option>
                    <option value="false">No</option>
                  </select>
                  <p class="text-xs text-gray-500">
                    Require users to be logged in to download files
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Submit Button -->
          <div class="flex items-center justify-end space-x-4">
            <Button
              type="button"
              variant="outline"
              @click="resetForm"
              :disabled="form.processing"
            >
              Reset Changes
            </Button>
            <Button
              type="submit"
              :disabled="form.processing || !hasChanges"
              class="min-w-[120px]"
            >
              <span v-if="form.processing" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Saving...
              </span>
              <span v-else>Save Settings</span>
            </Button>
          </div>
        </div>
      </form>
    </div>
  </BackstageLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline'

import BackstageLayout from '@/Layouts/BackstageLayout.vue'
import Card from '@/Components/ui/Card.vue'
import CardHeader from '@/Components/ui/CardHeader.vue'
import CardTitle from '@/Components/ui/CardTitle.vue'
import CardContent from '@/Components/ui/CardContent.vue'
import Badge from '@/Components/ui/Badge.vue'
import Button from '@/Components/App/Button.vue'
import Input from '@/Components/TextInput.vue'
import Label from '@/Components/InputLabel.vue'

const props = defineProps({
  settings: {
    type: Object,
    default: () => ({})
  }
})

// Initialize form with current settings
const form = useForm({
  app_name: props.settings.app_name || 'QuickDrop',
  require_reference_number: String(props.settings.require_reference_number ?? true),
  default_expiry_hours: props.settings.default_expiry_hours || 24,
  max_file_size_mb: props.settings.max_file_size_mb || 100,
  max_files_per_quickdrop: props.settings.max_files_per_quickdrop || 10,
  max_user_storage_gb: props.settings.max_user_storage_gb || 10,
  mail_from_address: props.settings.mail_from_address || '',
  mail_from_name: props.settings.mail_from_name || 'QuickDrop',
  enable_email_notifications: String(props.settings.enable_email_notifications ?? true),
  allow_public_upload: String(props.settings.allow_public_upload ?? true),
  require_auth_for_download: String(props.settings.require_auth_for_download ?? false)
})

// Store original values
const originalValues = ref({ ...form.data() })

// Check if form has changes
const hasChanges = computed(() => {
  return Object.keys(form.data()).some(key => {
    return form[key] !== originalValues.value[key]
  })
})

// Reset form to original values
function resetForm() {
  Object.keys(originalValues.value).forEach(key => {
    form[key] = originalValues.value[key]
  })
  form.clearErrors()
}

// Submit form
function submit() {
  form.put(route('backstage.settings.update'), {
    preserveScroll: true,
    onSuccess: () => {
      // Update original values on success
      originalValues.value = { ...form.data() }
    }
  })
}
</script>