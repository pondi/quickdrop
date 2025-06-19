<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Button from '@/Components/App/Button.vue';
import { 
    CheckCircleIcon, 
    ExclamationTriangleIcon, 
    EyeIcon, 
    EyeSlashIcon,
    ShieldCheckIcon
} from '@heroicons/vue/24/outline';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const passwordStrength = computed(() => {
    const password = form.password;
    if (!password) return { score: 0, label: '', color: '' };
    
    let score = 0;
    if (password.length >= 8) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/\d/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;
    
    const levels = [
        { score: 0, label: 'Very Weak', color: 'text-red-400 bg-red-400' },
        { score: 1, label: 'Weak', color: 'text-red-400 bg-red-400' },
        { score: 2, label: 'Fair', color: 'text-amber-400 bg-amber-400' },
        { score: 3, label: 'Good', color: 'text-amber-400 bg-amber-400' },
        { score: 4, label: 'Strong', color: 'text-emerald-400 bg-emerald-400' },
        { score: 5, label: 'Very Strong', color: 'text-emerald-400 bg-emerald-400' }
    ];
    
    return levels[score];
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <form @submit.prevent="updatePassword" class="space-y-6">
        <!-- Current Password -->
        <div class="space-y-2">
            <label for="current_password" class="block text-sm font-medium text-white">
                Current Password
            </label>
            <div class="relative">
                <input
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    :type="showCurrentPassword ? 'text' : 'password'"
                    autocomplete="current-password"
                    class="w-full px-4 py-3 pr-12 rounded-xl bg-white/5 border border-white/10 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500/50 transition-all duration-300"
                    placeholder="Enter your current password"
                />
                <button
                    type="button"
                    @click="showCurrentPassword = !showCurrentPassword"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-white/50 hover:text-white/80 transition-colors duration-200"
                >
                    <EyeIcon v-if="!showCurrentPassword" class="w-5 h-5" />
                    <EyeSlashIcon v-else class="w-5 h-5" />
                </button>
            </div>
            <p v-if="form.errors.current_password" class="text-red-400 text-sm flex items-center gap-2">
                <ExclamationTriangleIcon class="w-4 h-4" />
                {{ form.errors.current_password }}
            </p>
        </div>

        <!-- New Password -->
        <div class="space-y-2">
            <label for="password" class="block text-sm font-medium text-white">
                New Password
            </label>
            <div class="relative">
                <input
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    :type="showNewPassword ? 'text' : 'password'"
                    autocomplete="new-password"
                    class="w-full px-4 py-3 pr-12 rounded-xl bg-white/5 border border-white/10 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500/50 transition-all duration-300"
                    placeholder="Enter a strong password"
                />
                <button
                    type="button"
                    @click="showNewPassword = !showNewPassword"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-white/50 hover:text-white/80 transition-colors duration-200"
                >
                    <EyeIcon v-if="!showNewPassword" class="w-5 h-5" />
                    <EyeSlashIcon v-else class="w-5 h-5" />
                </button>
            </div>
            
            <!-- Password Strength Indicator -->
            <div v-if="form.password" class="space-y-2">
                <div class="flex items-center gap-2">
                    <div class="flex-1 h-2 bg-white/10 rounded-full overflow-hidden">
                        <div 
                            class="h-full transition-all duration-300 rounded-full"
                            :class="passwordStrength.color.split(' ')[1]"
                            :style="{ width: `${(passwordStrength.score / 5) * 100}%` }"
                        />
                    </div>
                    <span 
                        class="text-xs font-medium"
                        :class="passwordStrength.color.split(' ')[0]"
                    >
                        {{ passwordStrength.label }}
                    </span>
                </div>
                
                <!-- Password Requirements -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                    <div class="flex items-center gap-2" :class="form.password.length >= 8 ? 'text-emerald-400' : 'text-white/50'">
                        <div class="w-1 h-1 rounded-full" :class="form.password.length >= 8 ? 'bg-emerald-400' : 'bg-white/30'" />
                        8+ characters
                    </div>
                    <div class="flex items-center gap-2" :class="/[A-Z]/.test(form.password) ? 'text-emerald-400' : 'text-white/50'">
                        <div class="w-1 h-1 rounded-full" :class="/[A-Z]/.test(form.password) ? 'bg-emerald-400' : 'bg-white/30'" />
                        Uppercase letter
                    </div>
                    <div class="flex items-center gap-2" :class="/[a-z]/.test(form.password) ? 'text-emerald-400' : 'text-white/50'">
                        <div class="w-1 h-1 rounded-full" :class="/[a-z]/.test(form.password) ? 'bg-emerald-400' : 'bg-white/30'" />
                        Lowercase letter
                    </div>
                    <div class="flex items-center gap-2" :class="/\d/.test(form.password) ? 'text-emerald-400' : 'text-white/50'">
                        <div class="w-1 h-1 rounded-full" :class="/\d/.test(form.password) ? 'bg-emerald-400' : 'bg-white/30'" />
                        Number
                    </div>
                </div>
            </div>
            
            <p v-if="form.errors.password" class="text-red-400 text-sm flex items-center gap-2">
                <ExclamationTriangleIcon class="w-4 h-4" />
                {{ form.errors.password }}
            </p>
        </div>

        <!-- Confirm Password -->
        <div class="space-y-2">
            <label for="password_confirmation" class="block text-sm font-medium text-white">
                Confirm New Password
            </label>
            <div class="relative">
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    :type="showConfirmPassword ? 'text' : 'password'"
                    autocomplete="new-password"
                    class="w-full px-4 py-3 pr-12 rounded-xl bg-white/5 border border-white/10 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500/50 transition-all duration-300"
                    placeholder="Confirm your new password"
                />
                <button
                    type="button"
                    @click="showConfirmPassword = !showConfirmPassword"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-white/50 hover:text-white/80 transition-colors duration-200"
                >
                    <EyeIcon v-if="!showConfirmPassword" class="w-5 h-5" />
                    <EyeSlashIcon v-else class="w-5 h-5" />
                </button>
            </div>
            
            <!-- Password Match Indicator -->
            <div v-if="form.password_confirmation && form.password" class="flex items-center gap-2 text-xs">
                <CheckCircleIcon 
                    v-if="form.password === form.password_confirmation" 
                    class="w-4 h-4 text-emerald-400" 
                />
                <ExclamationTriangleIcon 
                    v-else 
                    class="w-4 h-4 text-red-400" 
                />
                <span :class="form.password === form.password_confirmation ? 'text-emerald-400' : 'text-red-400'">
                    {{ form.password === form.password_confirmation ? 'Passwords match' : 'Passwords do not match' }}
                </span>
            </div>
            
            <p v-if="form.errors.password_confirmation" class="text-red-400 text-sm flex items-center gap-2">
                <ExclamationTriangleIcon class="w-4 h-4" />
                {{ form.errors.password_confirmation }}
            </p>
        </div>

        <!-- Security Tips -->
        <div class="p-4 rounded-xl bg-purple-500/10 border border-purple-500/20">
            <div class="flex items-start gap-3">
                <ShieldCheckIcon class="w-5 h-5 text-purple-400 flex-shrink-0 mt-0.5" />
                <div>
                    <h4 class="text-purple-200 font-medium text-sm mb-1">Password Security Tips</h4>
                    <ul class="text-purple-200/70 text-xs space-y-1">
                        <li>• Use a unique password that you don't use elsewhere</li>
                        <li>• Consider using a password manager</li>
                        <li>• Avoid personal information like names or birthdays</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-between pt-4">
            <div class="flex items-center gap-4">
                <Button 
                    type="submit" 
                    :disabled="form.processing"
                    class="px-6 py-3"
                >
                    <span v-if="form.processing">Updating...</span>
                    <span v-else>Update Password</span>
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
                        <span class="text-emerald-200 text-sm font-medium">Password updated!</span>
                    </div>
                </Transition>
            </div>
        </div>
    </form>
</template>
