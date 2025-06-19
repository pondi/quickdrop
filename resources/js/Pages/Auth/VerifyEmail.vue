<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Card from '@/Components/App/Card.vue';
import Button from '@/Components/App/Button.vue';
import Icon from '@/Components/App/Icon.vue';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <Head title="Email Verification" />

    <AuthLayout>
        <Card class="w-full max-w-md">
            <div class="text-center mb-8">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-primary flex items-center justify-center animate-pulse-glow">
                    <Icon name="mail" :size="32" class="text-white" />
                </div>
                <h1 class="text-2xl font-display font-bold text-text-primary mb-2">
                    Verify Your Email
                </h1>
                <p class="text-text-secondary text-sm">
                    We've sent a verification link to your email address. Please check your inbox and click the link to continue.
                </p>
            </div>

            <!-- Success Message -->
            <div v-if="verificationLinkSent" class="mb-6 p-4 rounded-lg bg-green-500/10 border border-green-500/20">
                <div class="flex items-center space-x-3">
                    <Icon name="checkCircle" :size="20" class="text-green-400 flex-shrink-0" />
                    <p class="text-sm text-green-400">
                        A new verification link has been sent to your email address.
                    </p>
                </div>
            </div>

            <!-- Email Illustration -->
            <div class="mb-8 text-center">
                <div class="w-32 h-24 mx-auto mb-4 rounded-xl bg-gradient-secondary/10 flex items-center justify-center">
                    <div class="relative">
                        <Icon name="inbox" :size="48" class="text-text-muted" />
                        <div class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-primary flex items-center justify-center">
                            <Icon name="mail" :size="16" class="text-white" />
                        </div>
                    </div>
                </div>
                <p class="text-sm text-text-secondary">
                    Didn't receive the email? Check your spam folder or request a new one below.
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <Button
                    type="submit"
                    variant="primary"
                    size="lg"
                    :loading="form.processing"
                    :disabled="form.processing"
                    class="w-full"
                >
                    <Icon name="refreshCw" :size="20" class="mr-2" />
                    Resend Verification Email
                </Button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-text-secondary mb-4">
                    Want to use a different email address?
                </p>
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-sm text-red-400 hover:text-red-300 transition-colors font-medium"
                >
                    <Icon name="logOut" :size="16" class="inline mr-1" />
                    Sign Out
                </Link>
            </div>
        </Card>
    </AuthLayout>
</template>