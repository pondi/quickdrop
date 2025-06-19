<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdateEmailPreferencesForm from './Partials/UpdateEmailPreferencesForm.vue';
import Card from '@/Components/App/Card.vue';
import { Head } from '@inertiajs/vue3';
import { 
    UserIcon, 
    KeyIcon, 
    TrashIcon,
    CogIcon
} from '@heroicons/vue/24/outline';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    preferences: {
        type: Object,
        default: () => ({})
    },
});

const activeSection = ref('profile');

const sections = [
    {
        id: 'profile',
        name: 'Profile Information',
        description: 'Update your account information and email address',
        icon: UserIcon
    },
    {
        id: 'password',
        name: 'Change Password',
        description: 'Update your password to keep your account secure',
        icon: KeyIcon
    },
    {
        id: 'preferences',
        name: 'Preferences',
        description: 'Customize your QuickDrop experience',
        icon: CogIcon
    },
    {
        id: 'danger',
        name: 'Danger Zone',
        description: 'Permanently delete your account and all data',
        icon: TrashIcon
    }
];
</script>

<template>
    <Head title="Profile Settings" />

    <AppLayout>
        <div class="max-w-6xl mx-auto space-y-8">
            <!-- Header -->
            <div class="text-center space-y-4">
                <div class="relative inline-block">
                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center shadow-2xl shadow-purple-500/25">
                        <UserIcon class="w-10 h-10 text-white" />
                    </div>
                    <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 opacity-30 blur-xl" />
                </div>
                <div>
                    <h1 class="text-3xl font-display font-bold text-white mb-2">Profile Settings</h1>
                    <p class="text-white/60">Manage your account settings and preferences</p>
                </div>
            </div>

            <!-- Desktop Layout -->
            <div class="hidden lg:flex gap-8">
                <!-- Sidebar Navigation -->
                <div class="w-80 space-y-2">
                    <Card class="p-6">
                        <nav class="space-y-2">
                            <button
                                v-for="section in sections"
                                :key="section.id"
                                @click="activeSection = section.id"
                                class="w-full group flex items-start gap-4 p-4 rounded-xl transition-all duration-300"
                                :class="activeSection === section.id 
                                    ? 'bg-gradient-to-r from-purple-500/20 to-pink-500/20 border border-purple-500/30 text-white' 
                                    : 'text-white/70 hover:text-white hover:bg-white/5'"
                            >
                                <div class="flex-shrink-0 mt-0.5">
                                    <component 
                                        :is="section.icon" 
                                        class="w-5 h-5 transition-transform duration-300 group-hover:scale-110"
                                        :class="activeSection === section.id ? 'text-purple-400' : 'text-white/60'"
                                    />
                                </div>
                                <div class="text-left">
                                    <div class="font-medium">{{ section.name }}</div>
                                    <div class="text-xs mt-1 opacity-70">{{ section.description }}</div>
                                </div>
                            </button>
                        </nav>
                    </Card>
                </div>

                <!-- Main Content -->
                <div class="flex-1">
                    <Card class="p-8">
                        <!-- Profile Information Section -->
                        <div v-if="activeSection === 'profile'">
                            <div class="mb-8">
                                <div class="flex items-center gap-3 mb-2">
                                    <UserIcon class="w-6 h-6 text-purple-400" />
                                    <h2 class="text-xl font-semibold text-white">Profile Information</h2>
                                </div>
                                <p class="text-white/60 text-sm">Update your account's profile information and email address.</p>
                            </div>
                            <UpdateProfileInformationForm
                                :must-verify-email="mustVerifyEmail"
                                :status="status"
                            />
                        </div>

                        <!-- Password Section -->
                        <div v-else-if="activeSection === 'password'">
                            <div class="mb-8">
                                <div class="flex items-center gap-3 mb-2">
                                    <KeyIcon class="w-6 h-6 text-purple-400" />
                                    <h2 class="text-xl font-semibold text-white">Change Password</h2>
                                </div>
                                <p class="text-white/60 text-sm">Ensure your account is using a long, random password to stay secure.</p>
                            </div>
                            <UpdatePasswordForm />
                        </div>

                        <!-- Preferences Section -->
                        <div v-else-if="activeSection === 'preferences'">
                            <UpdateEmailPreferencesForm :preferences="preferences" />
                        </div>

                        <!-- Danger Zone Section -->
                        <div v-else-if="activeSection === 'danger'">
                            <div class="mb-8">
                                <div class="flex items-center gap-3 mb-2">
                                    <TrashIcon class="w-6 h-6 text-red-400" />
                                    <h2 class="text-xl font-semibold text-white">Danger Zone</h2>
                                </div>
                                <p class="text-white/60 text-sm">Once you delete your account, all of your resources and data will be permanently deleted.</p>
                            </div>
                            <DeleteUserForm />
                        </div>
                    </Card>
                </div>
            </div>

            <!-- Mobile Layout -->
            <div class="lg:hidden space-y-6">
                <!-- Section Tabs -->
                <Card class="p-4">
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            v-for="section in sections"
                            :key="section.id"
                            @click="activeSection = section.id"
                            class="flex items-center gap-2 p-3 rounded-xl text-sm font-medium transition-all duration-300"
                            :class="activeSection === section.id 
                                ? 'bg-gradient-to-r from-purple-500/20 to-pink-500/20 border border-purple-500/30 text-white' 
                                : 'text-white/70 hover:text-white hover:bg-white/5'"
                        >
                            <component :is="section.icon" class="w-4 h-4" />
                            <span class="truncate">{{ section.name }}</span>
                        </button>
                    </div>
                </Card>

                <!-- Mobile Content -->
                <Card class="p-6">
                    <!-- Same content sections as desktop but in mobile layout -->
                    <div v-if="activeSection === 'profile'">
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                        />
                    </div>
                    <div v-else-if="activeSection === 'password'">
                        <UpdatePasswordForm />
                    </div>
                    <div v-else-if="activeSection === 'preferences'">
                        <!-- Same preferences content -->
                        <div class="space-y-6">
                            <div class="flex items-center justify-between py-4 border-b border-white/10">
                                <div>
                                    <div class="text-white font-medium">Email Notifications</div>
                                    <div class="text-white/60 text-sm mt-1">Receive email notifications</div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" value="" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-white/20 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-purple-500 peer-checked:to-pink-500"></div>
                                </label>
                            </div>
                            <!-- Add other mobile preference toggles here -->
                        </div>
                    </div>
                    <div v-else-if="activeSection === 'danger'">
                        <DeleteUserForm />
                    </div>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
