<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Card from '@/Components/App/Card.vue';
import Button from '@/Components/App/Button.vue';
import Icon from '@/Components/App/Icon.vue';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Forgot Password" />

    <AuthLayout>
        <Card class="w-full max-w-md">
            <div class="text-center mb-8">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-primary flex items-center justify-center">
                    <Icon name="mail" :size="32" class="text-white" />
                </div>
                <h1 class="text-2xl font-display font-bold text-text-primary mb-2">
                    Forgot Password?
                </h1>
                <p class="text-text-secondary text-sm">
                    No problem! Enter your email and we'll send you a reset link.
                </p>
            </div>

            <!-- Success Message -->
            <div v-if="status" class="mb-6 p-4 rounded-lg bg-green-500/10 border border-green-500/20">
                <div class="flex items-center space-x-3">
                    <Icon name="checkCircle" :size="20" class="text-green-400 flex-shrink-0" />
                    <p class="text-sm text-green-400">{{ status }}</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-text-primary mb-2">
                        Email Address
                    </label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full px-4 py-3 rounded-xl bg-surface border border-white/10 text-text-primary placeholder-text-muted focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                        :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors.email }"
                        placeholder="Enter your email address"
                    />
                    <p v-if="form.errors.email" class="mt-2 text-sm text-red-400">
                        {{ form.errors.email }}
                    </p>
                </div>

                <Button
                    type="submit"
                    variant="primary"
                    size="lg"
                    :loading="form.processing"
                    :disabled="form.processing"
                    class="w-full"
                >
                    <Icon name="mail" :size="20" class="mr-2" />
                    Send Reset Link
                </Button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-text-secondary">
                    Remember your password?
                    <a :href="route('login')" class="text-primary hover:text-primary-end transition-colors font-medium">
                        Sign in
                    </a>
                </p>
            </div>
        </Card>
    </AuthLayout>
</template>