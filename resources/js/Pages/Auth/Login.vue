<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Button from '@/Components/App/Button.vue';
import Icon from '@/Components/App/Icon.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
        onSuccess: () => {
            console.log('Login successful');
        },
        onError: (errors) => {
            console.log('Login errors:', errors);
        }
    });
};
</script>

<template>
    <AuthLayout>
        <Head title="Log in" />

        <template #footer>
            Don't have an account? 
            <Link :href="route('register')" class="text-primary hover:text-primary-end transition-colors">
                Sign up
            </Link>
        </template>

        <div class="text-center mb-8">
            <h2 class="text-2xl font-display font-bold text-text-primary mb-2">
                Backstage Login
            </h2>
            <p class="text-text-secondary">
                Administrator access only
            </p>
            <p class="text-sm text-text-secondary mt-2">
                Regular users should <Link :href="route('quickdrop.login')" class="text-primary hover:text-primary-end">use QuickDrop login</Link>
            </p>
        </div>

        <div v-if="status" class="mb-6 p-4 rounded-lg bg-green-500/10 border border-green-500/20">
            <p class="text-sm text-green-400">{{ status }}</p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-text-primary mb-2">
                    Email address
                </label>
                <div class="relative">
                    <input
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full px-4 py-3 rounded-xl bg-surface border border-white/10 text-text-primary placeholder-text-muted focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                        :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors.email }"
                        placeholder="Enter your email"
                    />
                    <Icon 
                        v-if="form.errors.email" 
                        name="alertCircle" 
                        :size="20" 
                        class="absolute right-3 top-3.5 text-red-500"
                    />
                </div>
                <p v-if="form.errors.email" class="mt-2 text-sm text-red-400">
                    {{ form.errors.email }}
                </p>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-text-primary mb-2">
                    Password
                </label>
                <div class="relative">
                    <input
                        id="password"
                        type="password"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        class="w-full px-4 py-3 rounded-xl bg-surface border border-white/10 text-text-primary placeholder-text-muted focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                        :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors.password }"
                        placeholder="Enter your password"
                    />
                    <Icon 
                        v-if="form.errors.password" 
                        name="alertCircle" 
                        :size="20" 
                        class="absolute right-3 top-3.5 text-red-500"
                    />
                </div>
                <p v-if="form.errors.password" class="mt-2 text-sm text-red-400">
                    {{ form.errors.password }}
                </p>
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center cursor-pointer">
                    <input
                        type="checkbox"
                        v-model="form.remember"
                        class="w-4 h-4 rounded bg-surface border-white/20 text-primary focus:ring-2 focus:ring-primary/20 focus:ring-offset-0 focus:ring-offset-transparent"
                    />
                    <span class="ml-2 text-sm text-text-secondary">Remember me</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-primary hover:text-primary-end transition-colors"
                >
                    Forgot password?
                </Link>
            </div>

            <Button
                type="submit"
                variant="primary"
                size="lg"
                class="w-full"
                :loading="form.processing"
                :disabled="form.processing"
            >
                Sign in
            </Button>

            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-white/10"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-gradient-background text-text-muted">Or continue with</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <button
                    type="button"
                    class="flex items-center justify-center px-4 py-3 rounded-xl bg-surface border border-white/10 hover:bg-surface-hover transition-all"
                >
                    <Icon name="github" :size="20" class="text-text-secondary" />
                    <span class="ml-2 text-sm font-medium text-text-primary">GitHub</span>
                </button>
                <button
                    type="button"
                    class="flex items-center justify-center px-4 py-3 rounded-xl bg-surface border border-white/10 hover:bg-surface-hover transition-all"
                >
                    <Icon name="mail" :size="20" class="text-text-secondary" />
                    <span class="ml-2 text-sm font-medium text-text-primary">Google</span>
                </button>
            </div>
        </form>
    </AuthLayout>
</template>
