<template>
    <BackstageLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Create QuickDrop User</h1>
                <Link
                    :href="route('backstage.quickdrop-users.index')"
                    class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100"
                >
                    ← Back to list
                </Link>
            </div>

            <Card class="p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Name
                        </label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            required
                        />
                        <p v-if="form.errors.name" class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Email
                        </label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            required
                        />
                        <p v-if="form.errors.email" class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div>
                        <label for="storage_limit" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Storage Limit
                        </label>
                        <select
                            id="storage_limit"
                            v-model="form.storage_limit"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        >
                            <option :value="1073741824">1 GB</option>
                            <option :value="5368709120">5 GB (Default)</option>
                            <option :value="10737418240">10 GB</option>
                            <option :value="26843545600">25 GB</option>
                            <option :value="53687091200">50 GB</option>
                            <option :value="107374182400">100 GB</option>
                        </select>
                        <p v-if="form.errors.storage_limit" class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.storage_limit }}
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input
                                id="is_active"
                                v-model="form.is_active"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            />
                            <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                                Account is active
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input
                                id="email_verified"
                                v-model="form.email_verified"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            />
                            <label for="email_verified" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                                Email is verified
                            </label>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <Button type="submit" :disabled="form.processing">
                            Create User
                        </Button>
                        <Link
                            :href="route('backstage.quickdrop-users.index')"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100"
                        >
                            Cancel
                        </Link>
                    </div>
                </form>
            </Card>
        </div>
    </BackstageLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import BackstageLayout from '@/Layouts/BackstageLayout.vue';
import Card from '@/Components/App/Card.vue';
import Button from '@/Components/App/Button.vue';

const form = useForm({
    name: '',
    email: '',
    storage_limit: 5368709120, // 5GB default
    is_active: true,
    email_verified: false,
});

const submit = () => {
    form.post(route('backstage.quickdrop-users.store'));
};
</script>