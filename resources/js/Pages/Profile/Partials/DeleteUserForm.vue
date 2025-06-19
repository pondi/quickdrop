<script setup>
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import Button from '@/Components/App/Button.vue';
import Modal from '@/Components/App/Modal.vue';
import { 
    TrashIcon, 
    ExclamationTriangleIcon,
    ShieldExclamationIcon 
} from '@heroicons/vue/24/outline';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.reset();
};
</script>

<template>
    <div class="space-y-6">
        <!-- Warning Notice -->
        <div class="p-6 rounded-xl bg-red-500/10 border border-red-500/20">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <ShieldExclamationIcon class="w-8 h-8 text-red-400" />
                </div>
                <div class="space-y-3">
                    <div>
                        <h3 class="text-lg font-semibold text-red-200 mb-2">Permanent Account Deletion</h3>
                        <p class="text-red-200/80 text-sm leading-relaxed">
                            Once your account is deleted, all of its resources and data will be permanently deleted. 
                            This includes all your file drops, shared links, and account information.
                        </p>
                    </div>
                    
                    <!-- What gets deleted -->
                    <div class="border-t border-red-500/20 pt-4">
                        <h4 class="text-red-200 font-medium text-sm mb-2">The following will be permanently deleted:</h4>
                        <ul class="text-red-200/70 text-sm space-y-1">
                            <li>• All uploaded files and file drops</li>
                            <li>• Shared links and access history</li>
                            <li>• Account settings and preferences</li>
                            <li>• Usage statistics and analytics</li>
                        </ul>
                    </div>
                    
                    <!-- Recommendation -->
                    <div class="p-3 rounded-lg bg-amber-500/10 border border-amber-500/20">
                        <p class="text-amber-200 text-sm">
                            <strong>Recommendation:</strong> Before deleting your account, please download any data 
                            or information that you wish to retain.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Button -->
        <div class="flex justify-start">
            <button
                @click="confirmUserDeletion"
                class="group inline-flex items-center gap-3 px-6 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 hover:bg-red-500/20 hover:border-red-500/30 transition-all duration-300 font-medium"
            >
                <TrashIcon class="w-5 h-5 transition-transform duration-300 group-hover:scale-110" />
                Delete My Account
            </button>
        </div>

        <!-- Confirmation Modal -->
        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-8">
                <!-- Modal Header -->
                <div class="text-center mb-8">
                    <div class="w-16 h-16 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center mx-auto mb-4">
                        <ExclamationTriangleIcon class="w-8 h-8 text-red-400" />
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-2">
                        Delete Account Forever?
                    </h2>
                    <p class="text-white/60 text-sm">
                        This action cannot be undone. All your data will be permanently lost.
                    </p>
                </div>

                <!-- Password Confirmation -->
                <div class="space-y-4 mb-8">
                    <div>
                        <label for="password" class="block text-sm font-medium text-white mb-2">
                            Enter your password to confirm
                        </label>
                        <input
                            id="password"
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            placeholder="Your current password"
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-red-500/50 focus:border-red-500/50 transition-all duration-300"
                            @keyup.enter="deleteUser"
                        />
                        <p v-if="form.errors.password" class="mt-2 text-red-400 text-sm flex items-center gap-2">
                            <ExclamationTriangleIcon class="w-4 h-4" />
                            {{ form.errors.password }}
                        </p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between gap-4">
                    <Button
                        variant="ghost"
                        @click="closeModal"
                        class="flex-1 py-3"
                    >
                        Cancel
                    </Button>

                    <button
                        @click="deleteUser"
                        :disabled="form.processing || !form.password"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-red-500 hover:bg-red-600 disabled:bg-red-500/50 text-white font-medium transition-all duration-300 disabled:cursor-not-allowed"
                        :class="{ 'opacity-50': form.processing }"
                    >
                        <TrashIcon class="w-4 h-4" />
                        <span v-if="form.processing">Deleting Account...</span>
                        <span v-else>Yes, Delete Forever</span>
                    </button>
                </div>

                <!-- Final Warning -->
                <div class="mt-6 p-4 rounded-lg bg-red-500/5 border border-red-500/10">
                    <p class="text-red-300 text-xs text-center">
                        This will immediately delete your account and cannot be recovered.
                    </p>
                </div>
            </div>
        </Modal>
    </div>
</template>
