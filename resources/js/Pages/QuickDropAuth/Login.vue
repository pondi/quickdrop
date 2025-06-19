<template>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
                    Welcome back
                </h2>
                <p class="text-gray-600 dark:text-gray-400">
                    Sign in to your QuickDrop account
                </p>
            </div>

            <Card class="p-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div v-if="$page.props.flash?.success" class="rounded-md bg-green-50 dark:bg-green-900/20 p-4">
                        <p class="text-sm text-green-800 dark:text-green-300">
                            {{ $page.props.flash.success }}
                        </p>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Email address
                        </label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            required
                            class="appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white bg-white dark:bg-gray-700 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 focus:z-10 sm:text-sm"
                            placeholder="you@example.com"
                        />
                        <p v-if="form.errors.email" class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <input type="hidden" v-model="form.purpose" />

                    <div>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full"
                        >
                            <span v-if="!form.processing">Send magic link</span>
                            <span v-else class="flex items-center justify-center">
                                <LoadingSpinner class="w-4 h-4 mr-2" />
                                Sending...
                            </span>
                        </Button>
                    </div>

                    <div class="text-center">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Don't have an account?
                            <Link :href="route('quickdrop.register')" class="font-medium text-primary-600 hover:text-primary-500">
                                Sign up
                            </Link>
                        </p>
                    </div>
                </form>
            </Card>

            <div class="text-center">
                <Link :href="route('login')" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                    Are you an administrator? Sign in here →
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import Card from '@/Components/App/Card.vue';
import Button from '@/Components/App/Button.vue';
import LoadingSpinner from '@/Components/App/LoadingSpinner.vue';

const form = useForm({
    email: '',
    purpose: 'login',
});

const submit = () => {
    form.post(route('quickdrop.magic-link'), {
        preserveScroll: true,
        onError: () => {
            form.reset('email');
        },
    });
};
</script>