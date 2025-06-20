<script setup>
import { useForm } from '@inertiajs/vue3';
import Card from '@/Components/App/Card.vue';
import Button from '@/Components/App/Button.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { ref } from 'vue';
import { EnvelopeIcon, BellIcon, BellSlashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    preferences: {
        type: Object,
        default: () => ({})
    }
});

const form = useForm({
    notify_on_upload_complete: props.preferences?.notify_on_upload_complete ?? true,
    notify_on_download: props.preferences?.notify_on_download ?? true,
    notify_on_expiration_warning: props.preferences?.notify_on_expiration_warning ?? true,
    notify_marketing: props.preferences?.notify_marketing ?? false,
});

const updatePreferences = () => {
    form.put(route('profile.email-preferences'), {
        preserveScroll: true,
        onSuccess: () => {
            // Success message handled by Inertia
        },
    });
};

const preferences = [
    {
        key: 'notify_on_upload_complete',
        title: 'Upload Completion',
        description: 'Get notified when files are uploaded to your QuickDrops',
        icon: BellIcon,
    },
    {
        key: 'notify_on_download',
        title: 'Download Alerts',
        description: 'Get notified when someone downloads files from your QuickDrops',
        icon: BellIcon,
    },
    {
        key: 'notify_on_expiration_warning',
        title: 'Expiration Warnings',
        description: 'Get reminded 24 hours before your QuickDrops expire',
        icon: BellIcon,
    },
    {
        key: 'notify_marketing',
        title: 'Product Updates',
        description: 'Receive updates about new features and improvements',
        icon: EnvelopeIcon,
    },
];
</script>

<template>
    <Card class="p-8">
        <header class="mb-6">
            <h2 class="text-2xl font-display font-bold text-white mb-2">
                Email Preferences
            </h2>
            <p class="text-white/60">
                Choose which email notifications you want to receive.
            </p>
        </header>

        <form @submit.prevent="updatePreferences" class="space-y-6">
            <div class="space-y-4">
                <div v-for="preference in preferences" :key="preference.key" class="group">
                    <label class="flex items-start gap-4 p-4 rounded-xl bg-white/5 hover:bg-white/10 transition-all duration-300 cursor-pointer">
                        <div class="relative flex items-center">
                            <input
                                type="checkbox"
                                v-model="form[preference.key]"
                                class="w-5 h-5 rounded border-white/20 bg-white/10 text-purple-500 focus:ring-2 focus:ring-purple-500 focus:ring-offset-0"
                            />
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <component :is="preference.icon" class="w-5 h-5 text-white/40" />
                                <span class="font-medium text-white">{{ preference.title }}</span>
                            </div>
                            <p class="text-sm text-white/60">{{ preference.description }}</p>
                        </div>
                    </label>
                    <InputError :message="form.errors[preference.key]" class="mt-2 ml-9" />
                </div>
            </div>

            <div class="pt-4 space-y-4">
                <div class="p-4 rounded-xl bg-purple-500/10 border border-purple-500/20">
                    <p class="text-sm text-white/80">
                        <strong>Note:</strong> You can unsubscribe from any email by clicking the unsubscribe link in the email footer.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="relative group"
                    >
                        <span class="relative z-10 flex items-center gap-2">
                            <BellIcon class="w-5 h-5" />
                            {{ form.processing ? 'Saving...' : 'Save Preferences' }}
                        </span>
                    </Button>

                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p v-if="form.recentlySuccessful" class="text-sm text-green-400">
                            Preferences saved successfully.
                        </p>
                    </Transition>
                </div>
            </div>
        </form>
    </Card>
</template>