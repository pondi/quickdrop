<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import Button from '@/Components/App/Button.vue';
import { CheckCircleIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <form @submit.prevent="form.patch(route('profile.update'))" class="space-y-6">
        <!-- Name Field -->
        <div class="space-y-2">
            <label for="name" class="block text-sm font-medium text-white">
                Full Name
            </label>
            <input
                id="name"
                v-model="form.name"
                type="text"
                required
                autofocus
                autocomplete="name"
                class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500/50 transition-all duration-300"
                placeholder="Enter your full name"
            />
            <p v-if="form.errors.name" class="text-red-400 text-sm flex items-center gap-2">
                <ExclamationTriangleIcon class="w-4 h-4" />
                {{ form.errors.name }}
            </p>
        </div>

        <!-- Email Field -->
        <div class="space-y-2">
            <label for="email" class="block text-sm font-medium text-white">
                Email Address
            </label>
            <input
                id="email"
                v-model="form.email"
                type="email"
                required
                autocomplete="username"
                class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500/50 transition-all duration-300"
                placeholder="Enter your email address"
            />
            <p v-if="form.errors.email" class="text-red-400 text-sm flex items-center gap-2">
                <ExclamationTriangleIcon class="w-4 h-4" />
                {{ form.errors.email }}
            </p>
        </div>

        <!-- Email Verification Notice -->
        <div v-if="mustVerifyEmail && user.email_verified_at === null" class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/20">
            <div class="flex items-start gap-3">
                <ExclamationTriangleIcon class="w-5 h-5 text-amber-400 flex-shrink-0 mt-0.5" />
                <div class="space-y-2">
                    <p class="text-amber-200 text-sm">
                        Your email address is not verified.
                    </p>
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-amber-500/20 text-amber-200 hover:bg-amber-500/30 text-sm font-medium transition-colors duration-200"
                    >
                        Send Verification Email
                    </Link>
                </div>
            </div>

            <Transition
                enter-active-class="transition-all duration-300"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition-all duration-200"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="status === 'verification-link-sent'"
                    class="mt-3 p-3 rounded-lg bg-emerald-500/10 border border-emerald-500/20"
                >
                    <div class="flex items-center gap-2">
                        <CheckCircleIcon class="w-4 h-4 text-emerald-400" />
                        <p class="text-emerald-200 text-sm">
                            A new verification link has been sent to your email address.
                        </p>
                    </div>
                </div>
            </Transition>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-between pt-4">
            <div class="flex items-center gap-4">
                <Button 
                    type="submit" 
                    :disabled="form.processing"
                    class="px-6 py-3"
                >
                    <span v-if="form.processing">Saving...</span>
                    <span v-else>Save Changes</span>
                </Button>

                <Transition
                    enter-active-class="transition-all duration-300"
                    enter-from-class="opacity-0 translate-x-2"
                    enter-to-class="opacity-100 translate-x-0"
                    leave-active-class="transition-all duration-200"
                    leave-from-class="opacity-100 translate-x-0"
                    leave-to-class="opacity-0 translate-x-2"
                >
                    <div 
                        v-if="form.recentlySuccessful" 
                        class="flex items-center gap-2 px-3 py-2 rounded-lg bg-emerald-500/10 border border-emerald-500/20"
                    >
                        <CheckCircleIcon class="w-4 h-4 text-emerald-400" />
                        <span class="text-emerald-200 text-sm font-medium">Saved successfully!</span>
                    </div>
                </Transition>
            </div>
        </div>
    </form>
</template>
