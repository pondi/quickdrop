<script setup>
import { ref, computed } from 'vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Button from '@/Components/App/Button.vue';
import Icon from '@/Components/App/Icon.vue';
import ProgressRing from '@/Components/App/ProgressRing.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const currentStep = ref(1);
const totalSteps = 3;

const stepProgress = computed(() => {
    return (currentStep.value / totalSteps) * 100;
});

const passwordStrength = computed(() => {
    const password = form.password;
    if (!password) return 0;
    
    let strength = 0;
    if (password.length >= 8) strength += 25;
    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 25;
    if (password.match(/[0-9]/)) strength += 25;
    if (password.match(/[^a-zA-Z0-9]/)) strength += 25;
    
    return strength;
});

const passwordStrengthText = computed(() => {
    const strength = passwordStrength.value;
    if (strength === 0) return '';
    if (strength <= 25) return 'Weak';
    if (strength <= 50) return 'Fair';
    if (strength <= 75) return 'Good';
    return 'Strong';
});

const passwordStrengthColor = computed(() => {
    const strength = passwordStrength.value;
    if (strength <= 25) return '#ef4444';
    if (strength <= 50) return '#f59e0b';
    if (strength <= 75) return '#3b82f6';
    return '#10b981';
});

const nextStep = () => {
    if (currentStep.value < totalSteps) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthLayout>
        <Head title="Register" />

        <template #footer>
            Already have an account? 
            <Link :href="route('login')" class="text-primary hover:text-primary-end transition-colors">
                Sign in
            </Link>
        </template>

        <div class="text-center mb-8">
            <h2 class="text-2xl font-display font-bold text-text-primary mb-2">
                Create your account
            </h2>
            <p class="text-text-secondary">
                Start sharing files securely in seconds
            </p>
        </div>

        <div class="mb-8">
            <div class="flex items-center justify-center mb-4">
                <ProgressRing 
                    :value="stepProgress" 
                    :size="80"
                    :stroke-width="6"
                    :show-percentage="false"
                />
                <div class="absolute">
                    <span class="text-lg font-bold text-text-primary">{{ currentStep }}/{{ totalSteps }}</span>
                </div>
            </div>
            <div class="flex justify-between text-xs text-text-secondary">
                <span :class="{ 'text-primary': currentStep >= 1 }">Account</span>
                <span :class="{ 'text-primary': currentStep >= 2 }">Security</span>
                <span :class="{ 'text-primary': currentStep >= 3 }">Confirm</span>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0 transform translate-x-4"
                enter-to-class="opacity-100 transform translate-x-0"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100 transform translate-x-0"
                leave-to-class="opacity-0 transform -translate-x-4"
                mode="out-in"
            >
                <div v-if="currentStep === 1" key="step1" class="space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-text-primary mb-2">
                            Full name
                        </label>
                        <input
                            id="name"
                            type="text"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="name"
                            class="w-full px-4 py-3 rounded-xl bg-surface border border-white/10 text-text-primary placeholder-text-muted focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors.name }"
                            placeholder="John Doe"
                        />
                        <p v-if="form.errors.name" class="mt-2 text-sm text-red-400">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-text-primary mb-2">
                            Email address
                        </label>
                        <input
                            id="email"
                            type="email"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            class="w-full px-4 py-3 rounded-xl bg-surface border border-white/10 text-text-primary placeholder-text-muted focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors.email }"
                            placeholder="john@example.com"
                        />
                        <p v-if="form.errors.email" class="mt-2 text-sm text-red-400">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <Button
                        type="button"
                        variant="primary"
                        size="lg"
                        class="w-full"
                        @click="nextStep"
                        :disabled="!form.name || !form.email"
                    >
                        Continue
                    </Button>
                </div>

                <div v-else-if="currentStep === 2" key="step2" class="space-y-6">
                    <div>
                        <label for="password" class="block text-sm font-medium text-text-primary mb-2">
                            Password
                        </label>
                        <input
                            id="password"
                            type="password"
                            v-model="form.password"
                            required
                            autocomplete="new-password"
                            class="w-full px-4 py-3 rounded-xl bg-surface border border-white/10 text-text-primary placeholder-text-muted focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors.password }"
                            placeholder="Create a strong password"
                        />
                        <div v-if="form.password" class="mt-2">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-text-secondary">Password strength</span>
                                <span class="text-xs font-medium" :style="{ color: passwordStrengthColor }">
                                    {{ passwordStrengthText }}
                                </span>
                            </div>
                            <div class="w-full bg-white/10 rounded-full h-1.5 overflow-hidden">
                                <div 
                                    class="h-full transition-all duration-300"
                                    :style="{ width: `${passwordStrength}%`, backgroundColor: passwordStrengthColor }"
                                />
                            </div>
                        </div>
                        <p v-if="form.errors.password" class="mt-2 text-sm text-red-400">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-text-primary mb-2">
                            Confirm password
                        </label>
                        <input
                            id="password_confirmation"
                            type="password"
                            v-model="form.password_confirmation"
                            required
                            autocomplete="new-password"
                            class="w-full px-4 py-3 rounded-xl bg-surface border border-white/10 text-text-primary placeholder-text-muted focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors.password_confirmation }"
                            placeholder="Confirm your password"
                        />
                        <p v-if="form.errors.password_confirmation" class="mt-2 text-sm text-red-400">
                            {{ form.errors.password_confirmation }}
                        </p>
                    </div>

                    <div class="flex space-x-3">
                        <Button
                            type="button"
                            variant="ghost"
                            size="lg"
                            class="flex-1"
                            @click="prevStep"
                        >
                            Back
                        </Button>
                        <Button
                            type="button"
                            variant="primary"
                            size="lg"
                            class="flex-1"
                            @click="nextStep"
                            :disabled="!form.password || !form.password_confirmation || form.password !== form.password_confirmation"
                        >
                            Continue
                        </Button>
                    </div>
                </div>

                <div v-else-if="currentStep === 3" key="step3" class="space-y-6">
                    <div class="text-center py-8">
                        <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gradient-primary flex items-center justify-center animate-pulse-glow">
                            <Icon name="check" :size="40" class="text-white" />
                        </div>
                        <h3 class="text-lg font-medium text-text-primary mb-2">
                            Almost done!
                        </h3>
                        <p class="text-sm text-text-secondary">
                            Review your information and create your account
                        </p>
                    </div>

                    <div class="space-y-3 p-4 rounded-xl bg-surface">
                        <div class="flex justify-between">
                            <span class="text-sm text-text-secondary">Name</span>
                            <span class="text-sm text-text-primary">{{ form.name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-text-secondary">Email</span>
                            <span class="text-sm text-text-primary">{{ form.email }}</span>
                        </div>
                    </div>

                    <div class="flex space-x-3">
                        <Button
                            type="button"
                            variant="ghost"
                            size="lg"
                            class="flex-1"
                            @click="prevStep"
                        >
                            Back
                        </Button>
                        <Button
                            type="submit"
                            variant="primary"
                            size="lg"
                            class="flex-1"
                            :loading="form.processing"
                            :disabled="form.processing"
                        >
                            Create account
                        </Button>
                    </div>
                </div>
            </Transition>
        </form>
    </AuthLayout>
</template>
