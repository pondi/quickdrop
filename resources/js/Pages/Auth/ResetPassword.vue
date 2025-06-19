<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Card from '@/Components/App/Card.vue';
import Button from '@/Components/App/Button.vue';
import Icon from '@/Components/App/Icon.vue';
import { ref } from 'vue';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const togglePassword = () => {
    showPassword.value = !showPassword.value;
};

const toggleConfirmPassword = () => {
    showConfirmPassword.value = !showConfirmPassword.value;
};
</script>

<template>
    <Head title="Reset Password" />

    <AuthLayout>
        <Card class="w-full max-w-md">
            <div class="text-center mb-8">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-primary flex items-center justify-center animate-pulse-glow">
                    <Icon name="key" :size="32" class="text-white" />
                </div>
                <h1 class="text-2xl font-display font-bold text-text-primary mb-2">
                    Reset Password
                </h1>
                <p class="text-text-secondary text-sm">
                    Enter your new password below to complete the reset.
                </p>
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
                        readonly
                        class="w-full px-4 py-3 rounded-xl bg-surface border border-white/10 text-text-secondary cursor-not-allowed"
                    />
                    <p v-if="form.errors.email" class="mt-2 text-sm text-red-400">
                        {{ form.errors.email }}
                    </p>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-text-primary mb-2">
                        New Password
                    </label>
                    <div class="relative">
                        <input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            autocomplete="new-password"
                            class="w-full px-4 py-3 pr-12 rounded-xl bg-surface border border-white/10 text-text-primary placeholder-text-muted focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors.password }"
                            placeholder="Enter new password"
                        />
                        <button
                            type="button"
                            @click="togglePassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-text-muted hover:text-text-primary transition-colors"
                        >
                            <Icon :name="showPassword ? 'eyeOff' : 'eye'" :size="20" />
                        </button>
                    </div>
                    <p v-if="form.errors.password" class="mt-2 text-sm text-red-400">
                        {{ form.errors.password }}
                    </p>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-text-primary mb-2">
                        Confirm Password
                    </label>
                    <div class="relative">
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :type="showConfirmPassword ? 'text' : 'password'"
                            required
                            autocomplete="new-password"
                            class="w-full px-4 py-3 pr-12 rounded-xl bg-surface border border-white/10 text-text-primary placeholder-text-muted focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors.password_confirmation }"
                            placeholder="Confirm new password"
                        />
                        <button
                            type="button"
                            @click="toggleConfirmPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-text-muted hover:text-text-primary transition-colors"
                        >
                            <Icon :name="showConfirmPassword ? 'eyeOff' : 'eye'" :size="20" />
                        </button>
                    </div>
                    <p v-if="form.errors.password_confirmation" class="mt-2 text-sm text-red-400">
                        {{ form.errors.password_confirmation }}
                    </p>
                </div>

                <Button
                    type="submit"
                    variant="primary"
                    size="lg"
                    :loading="form.processing"
                    :disabled="form.processing"
                    class="w-full animate-pulse-glow"
                >
                    <Icon name="check" :size="20" class="mr-2" />
                    Reset Password
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