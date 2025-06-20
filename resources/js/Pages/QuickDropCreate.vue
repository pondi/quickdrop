<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/App/Card.vue';
import Button from '@/Components/App/Button.vue';
import Icon from '@/Components/App/Icon.vue';
import ProgressRing from '@/Components/App/ProgressRing.vue';
import Modal from '@/Components/App/Modal.vue';
import { generateEncryptionKey, generateKeyHash, securelyStoreKey } from '@/Services/EncryptionService';

const props = defineProps({
    auth: {
        type: Object,
        required: true
    },
    config: {
        type: Object,
        required: true
    }
});

const form = useForm({
    title: '',
    comment: '',
    reference_number: '',
    expires_in_minutes: props.config.defaults.expires_in_minutes,
    use_encryption: false,
    key_verification_hash: null,
    allow_public_download: false,
    allow_public_delete: false,
    allow_public_upload: true,
});

const currentStep = ref(1);
const totalSteps = 4;
const isSubmitting = ref(false);
const createdUrl = ref(null);
const encryptionKey = ref(null);
const showSuccessModal = ref(false);
const copySuccess = ref(false);
const isAdvancedMode = ref(false);

const stepProgress = computed(() => {
    return (currentStep.value / totalSteps) * 100;
});

const canProceedToStep2 = computed(() => {
    return form.title.trim().length > 0;
});

const canProceedToStep3 = computed(() => {
    if (props.config?.reference_number?.required) {
        return form.reference_number.trim().length > 0;
    }
    return true;
});

const expirationOptions = computed(() => props.config.expiration_options);

const stepTitles = ['Basic Info', 'Reference & Settings', 'Security', 'Review & Create'];

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

const formatExpirationTime = (minutes) => {
    const option = expirationOptions.value.find(opt => opt.value === minutes);
    return option ? option.label : `${minutes} minutes`;
};

const submit = async () => {
    if (isSubmitting.value) return;
    isSubmitting.value = true;
    
    let clientEncryptionKey = null;
    if (form.use_encryption) {
        clientEncryptionKey = await generateEncryptionKey();
        form.key_verification_hash = await generateKeyHash(clientEncryptionKey);
        encryptionKey.value = clientEncryptionKey;
    }
    
    form.post(route('quickdrop.store'), {
        preserveScroll: true,
        onSuccess: (response) => {
            const urlParts = response.url.split('/');
            const uniqueRequestId = urlParts[urlParts.length - 1];
            
            if (uniqueRequestId) {
                createdUrl.value = route('quickdrop.show', uniqueRequestId);
                
                if (form.use_encryption && clientEncryptionKey) {
                    const storageKey = `quickdrop_key_${uniqueRequestId}`;
                    try {
                        securelyStoreKey(storageKey, clientEncryptionKey);
                    } catch (error) {
                    }
                    encryptionKey.value = clientEncryptionKey;
                }
                
                showSuccessModal.value = true;
            }
        },
        onError: () => {
            encryptionKey.value = null;
            createdUrl.value = null;
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        copySuccess.value = true;
        setTimeout(() => copySuccess.value = false, 2000);
    } catch (err) {
    }
};

const redirectToQuickDrop = () => {
    if (createdUrl.value) {
        router.visit(createdUrl.value);
    }
};
</script>

<!-- FEAT-001: QuickDrop Creation - Multi-step wizard interface -->
<!-- FEAT-006: Client-Side Encryption - Encryption options -->
<!-- FEAT-007: Reference Number Validation - Reference input -->
<!-- FEAT-008: Expiration Management - Time selection -->
<template>
    <Head title="Create New QuickDrop" />

    <AppLayout>
        <div class="max-w-4xl mx-auto space-y-8">
            <!-- Header -->
            <div class="text-center">
                <h1 class="text-3xl font-display font-bold gradient-text mb-2">
                    Create New QuickDrop
                </h1>
                <p class="text-text-secondary">
                    Set up a secure space for file sharing
                </p>
            </div>

            <!-- Progress Indicator - Only show in advanced mode -->
            <Card v-if="isAdvancedMode">
                <div class="flex items-center justify-center mb-6 relative">
                    <ProgressRing 
                        :value="stepProgress" 
                        :size="100"
                        :stroke-width="6"
                        :show-percentage="false"
                    />
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-lg font-bold text-text-primary">{{ currentStep }}/{{ totalSteps }}</span>
                    </div>
                </div>
                
                <div class="flex justify-between text-xs">
                    <div 
                        v-for="(title, index) in stepTitles"
                        :key="index"
                        class="flex flex-col items-center flex-1"
                        :class="{ 'text-primary': currentStep > index, 'text-text-secondary': currentStep <= index }"
                    >
                        <div 
                            class="w-8 h-8 rounded-full flex items-center justify-center mb-2 transition-all"
                            :class="currentStep > index + 1 
                                ? 'bg-gradient-primary text-white' 
                                : currentStep === index + 1 
                                    ? 'bg-primary text-white' 
                                    : 'bg-surface text-text-muted'"
                        >
                            <Icon 
                                v-if="currentStep > index + 1" 
                                name="check" 
                                :size="16" 
                            />
                            <span v-else>{{ index + 1 }}</span>
                        </div>
                        <span class="text-center max-w-20">{{ title }}</span>
                    </div>
                </div>
            </Card>

            <!-- Form -->
            <Card class="min-h-96">
                <!-- Simple Mode -->
                <div v-if="!isAdvancedMode" class="space-y-6">
                    <div class="text-center mb-8">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-primary flex items-center justify-center">
                            <Icon name="zap" :size="32" class="text-white" />
                        </div>
                        <h2 class="text-2xl font-semibold text-text-primary mb-2">Quick Setup</h2>
                        <p class="text-text-secondary">Create a QuickDrop in seconds</p>
                    </div>

                    <div>
                        <label for="title" class="block text-sm font-medium text-text-primary mb-2">
                            QuickDrop Title <span class="text-red-400">*</span>
                        </label>
                        <input
                            id="title"
                            v-model="form.title"
                            type="text"
                            required
                            class="w-full px-4 py-3 rounded-xl bg-surface border border-white/10 text-text-primary placeholder-text-muted focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors.title }"
                            placeholder="My Project Files"
                        />
                        <p v-if="form.errors.title" class="mt-2 text-sm text-red-400">
                            {{ form.errors.title }}
                        </p>
                    </div>

                    <div>
                        <label for="comment" class="block text-sm font-medium text-text-primary mb-2">
                            Description
                        </label>
                        <textarea
                            id="comment"
                            v-model="form.comment"
                            rows="3"
                            class="w-full px-4 py-3 rounded-xl bg-surface border border-white/10 text-text-primary placeholder-text-muted focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all resize-none"
                            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors.comment }"
                            placeholder="Add any additional information..."
                        />
                        <p v-if="form.errors.comment" class="mt-2 text-sm text-red-400">
                            {{ form.errors.comment }}
                        </p>
                    </div>

                    <div v-if="props.config?.reference_number?.enabled">
                        <label for="reference_number" class="block text-sm font-medium text-text-primary mb-2">
                            {{ props.config.reference_number?.label || 'Reference Number' }}
                            <span v-if="props.config.reference_number?.required" class="text-red-400">*</span>
                        </label>
                        <input
                            id="reference_number"
                            v-model="form.reference_number"
                            type="text"
                            :required="props.config.reference_number?.required"
                            class="w-full px-4 py-3 rounded-xl bg-surface border border-white/10 text-text-primary placeholder-text-muted focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors.reference_number }"
                            :placeholder="props.config.reference_number?.help_text || 'Enter reference number'"
                        />
                        <p v-if="form.errors.reference_number" class="mt-2 text-sm text-red-400">
                            {{ form.errors.reference_number }}
                        </p>
                    </div>

                    <div>
                        <label for="expires_in_minutes" class="block text-sm font-medium text-text-primary mb-2">
                            Expiration Time
                        </label>
                        <select
                            v-model.number="form.expires_in_minutes"
                            id="expires_in_minutes"
                            class="w-full px-4 py-3 rounded-xl bg-surface border border-white/10 text-text-primary focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors.expires_in_minutes }"
                        >
                            <option v-for="option in expirationOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <p v-if="form.errors.expires_in_minutes" class="mt-2 text-sm text-red-400">
                            {{ form.errors.expires_in_minutes }}
                        </p>
                        <p class="mt-2 text-xs text-text-muted">
                            <Icon name="info" :size="12" class="inline mr-1" />
                            Files will be automatically deleted after this time
                        </p>
                    </div>

                    <div class="pt-4 border-t border-white/10">
                        <button
                            type="button"
                            @click="isAdvancedMode = true"
                            class="text-primary hover:text-primary-light transition-colors text-sm font-medium flex items-center gap-2 mx-auto"
                        >
                            <Icon name="settings" :size="16" />
                            Advanced Settings
                        </button>
                    </div>

                    <div class="flex gap-3">
                        <Button
                            variant="primary"
                            size="lg"
                            @click="submit"
                            :loading="isSubmitting"
                            :disabled="!form.title.trim() || (props.config?.reference_number?.required && !form.reference_number.trim())"
                            class="flex-1"
                        >
                            Create QuickDrop
                        </Button>
                    </div>
                </div>

                <!-- Advanced Mode - Multi-step wizard -->
                <div v-if="isAdvancedMode">
                    <!-- Exit Advanced Mode Button -->
                    <div class="mb-6 flex justify-end">
                        <button
                            type="button"
                            @click="isAdvancedMode = false; currentStep = 1"
                            class="text-text-secondary hover:text-text-primary transition-colors text-sm font-medium flex items-center gap-2"
                        >
                            <Icon name="x" :size="16" />
                            Exit Advanced Mode
                        </button>
                    </div>

                    <Transition
                        enter-active-class="transition ease-out duration-300"
                        enter-from-class="opacity-0 transform translate-x-4"
                        enter-to-class="opacity-100 transform translate-x-0"
                        leave-active-class="transition ease-in duration-200"
                        leave-from-class="opacity-100 transform translate-x-0"
                        leave-to-class="opacity-0 transform -translate-x-4"
                        mode="out-in"
                    >
                        <!-- Step 1: Basic Info -->
                        <div v-if="currentStep === 1" key="step1" class="space-y-6">
                        <div class="text-center mb-8">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-primary flex items-center justify-center">
                                <Icon name="edit3" :size="32" class="text-white" />
                            </div>
                            <h2 class="text-2xl font-semibold text-text-primary mb-2">Basic Information</h2>
                            <p class="text-text-secondary">Give your QuickDrop a name and description</p>
                        </div>

                        <div>
                            <label for="title" class="block text-sm font-medium text-text-primary mb-2">
                                QuickDrop Title <span class="text-red-400">*</span>
                            </label>
                            <input
                                id="title"
                                v-model="form.title"
                                type="text"
                                required
                                class="w-full px-4 py-3 rounded-xl bg-surface border border-white/10 text-text-primary placeholder-text-muted focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors.title }"
                                placeholder="My Project Files"
                            />
                            <p v-if="form.errors.title" class="mt-2 text-sm text-red-400">
                                {{ form.errors.title }}
                            </p>
                        </div>

                        <div>
                            <label for="comment" class="block text-sm font-medium text-text-primary mb-2">
                                Description
                            </label>
                            <textarea
                                id="comment"
                                v-model="form.comment"
                                rows="4"
                                class="w-full px-4 py-3 rounded-xl bg-surface border border-white/10 text-text-primary placeholder-text-muted focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all resize-none"
                                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors.comment }"
                                placeholder="Add any additional information or instructions for recipients..."
                            />
                            <p v-if="form.errors.comment" class="mt-2 text-sm text-red-400">
                                {{ form.errors.comment }}
                            </p>
                        </div>

                        <div class="flex justify-end">
                            <Button
                                variant="primary"
                                size="lg"
                                @click="nextStep"
                                :disabled="!canProceedToStep2"
                            >
                                Continue
                            </Button>
                        </div>
                    </div>

                    <!-- Step 2: Reference & Settings -->
                    <div v-else-if="currentStep === 2" key="step2" class="space-y-6">
                        <div class="text-center mb-8">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-primary flex items-center justify-center">
                                <Icon name="settings" :size="32" class="text-white" />
                            </div>
                            <h2 class="text-2xl font-semibold text-text-primary mb-2">Settings & Reference</h2>
                            <p class="text-text-secondary">Configure expiration and reference settings</p>
                        </div>

                        <div v-if="props.config?.reference_number?.enabled">
                            <label for="reference_number" class="block text-sm font-medium text-text-primary mb-2">
                                {{ props.config.reference_number?.label || 'Reference Number' }}
                                <span v-if="props.config.reference_number?.required" class="text-red-400">*</span>
                            </label>
                            <input
                                id="reference_number"
                                v-model="form.reference_number"
                                type="text"
                                :required="props.config.reference_number?.required"
                                class="w-full px-4 py-3 rounded-xl bg-surface border border-white/10 text-text-primary placeholder-text-muted focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors.reference_number }"
                                :placeholder="props.config.reference_number?.help_text || 'Enter reference number'"
                            />
                            <p v-if="form.errors.reference_number" class="mt-2 text-sm text-red-400">
                                {{ form.errors.reference_number }}
                            </p>
                            <p v-if="props.config.reference_number?.help_text" class="mt-1 text-xs text-text-muted">
                                {{ props.config.reference_number.help_text }}
                            </p>
                        </div>

                        <div>
                            <label for="expires_in_minutes" class="block text-sm font-medium text-text-primary mb-2">
                                Expiration Time
                            </label>
                            <select
                                v-model.number="form.expires_in_minutes"
                                id="expires_in_minutes"
                                class="w-full px-4 py-3 rounded-xl bg-surface border border-white/10 text-text-primary focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors.expires_in_minutes }"
                            >
                                <option v-for="option in expirationOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <p v-if="form.errors.expires_in_minutes" class="mt-2 text-sm text-red-400">
                                {{ form.errors.expires_in_minutes }}
                            </p>
                        </div>

                        <div class="flex space-x-3">
                            <Button
                                variant="ghost"
                                size="lg"
                                @click="prevStep"
                                class="flex-1"
                            >
                                Back
                            </Button>
                            <Button
                                variant="primary"
                                size="lg"
                                @click="nextStep"
                                :disabled="!canProceedToStep3"
                                class="flex-1"
                            >
                                Continue
                            </Button>
                        </div>
                    </div>

                    <!-- Step 3: Security -->
                    <div v-else-if="currentStep === 3" key="step3" class="space-y-6">
                        <div class="text-center mb-8">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-primary flex items-center justify-center">
                                <Icon name="shield" :size="32" class="text-white" />
                            </div>
                            <h2 class="text-2xl font-semibold text-text-primary mb-2">Security & Permissions</h2>
                            <p class="text-text-secondary">Configure encryption and access permissions</p>
                        </div>

                        <div class="space-y-4">
                            <div class="p-4 rounded-xl bg-surface border border-white/10">
                                <label class="flex items-start space-x-3 cursor-pointer">
                                    <input
                                        v-model="form.use_encryption"
                                        type="checkbox"
                                        class="w-5 h-5 mt-0.5 rounded bg-surface border-white/20 text-primary focus:ring-2 focus:ring-primary/20 focus:ring-offset-0"
                                    />
                                    <div>
                                        <span class="font-medium text-text-primary">Enable End-to-End Encryption</span>
                                        <p class="text-sm text-text-secondary mt-1">
                                            Files will be encrypted before upload and can only be decrypted with the encryption key
                                        </p>
                                    </div>
                                </label>
                            </div>

                            <div class="space-y-3">
                                <h3 class="font-medium text-text-primary">Public User Permissions</h3>
                                
                                <div class="p-4 rounded-xl bg-surface border border-white/10">
                                    <label class="flex items-start space-x-3 cursor-pointer">
                                        <input
                                            v-model="form.allow_public_upload"
                                            type="checkbox"
                                            class="w-5 h-5 mt-0.5 rounded bg-surface border-white/20 text-primary focus:ring-2 focus:ring-primary/20 focus:ring-offset-0"
                                        />
                                        <div>
                                            <span class="font-medium text-text-primary">Allow Public Upload</span>
                                            <p class="text-sm text-text-secondary mt-1">
                                                Anyone with the link can upload files to this QuickDrop
                                            </p>
                                        </div>
                                    </label>
                                </div>

                                <div class="p-4 rounded-xl bg-surface border border-white/10">
                                    <label class="flex items-start space-x-3 cursor-pointer">
                                        <input
                                            v-model="form.allow_public_download"
                                            type="checkbox"
                                            class="w-5 h-5 mt-0.5 rounded bg-surface border-white/20 text-primary focus:ring-2 focus:ring-primary/20 focus:ring-offset-0"
                                        />
                                        <div>
                                            <span class="font-medium text-text-primary">Allow Public Download</span>
                                            <p class="text-sm text-text-secondary mt-1">
                                                Anyone with the link can download files from this QuickDrop
                                            </p>
                                        </div>
                                    </label>
                                </div>

                                <div class="p-4 rounded-xl bg-surface border border-white/10">
                                    <label class="flex items-start space-x-3 cursor-pointer">
                                        <input
                                            v-model="form.allow_public_delete"
                                            type="checkbox"
                                            class="w-5 h-5 mt-0.5 rounded bg-surface border-white/20 text-primary focus:ring-2 focus:ring-primary/20 focus:ring-offset-0"
                                        />
                                        <div>
                                            <span class="font-medium text-text-primary">Allow Public Delete</span>
                                            <p class="text-sm text-text-secondary mt-1">
                                                Anyone with the link can delete files from this QuickDrop
                                            </p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="flex space-x-3">
                            <Button
                                variant="ghost"
                                size="lg"
                                @click="prevStep"
                                class="flex-1"
                            >
                                Back
                            </Button>
                            <Button
                                variant="primary"
                                size="lg"
                                @click="nextStep"
                                class="flex-1"
                            >
                                Review
                            </Button>
                        </div>
                    </div>

                    <!-- Step 4: Review & Create -->
                    <div v-else-if="currentStep === 4" key="step4" class="space-y-6">
                        <div class="text-center mb-8">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-primary flex items-center justify-center animate-pulse-glow">
                                <Icon name="checkCircle" :size="32" class="text-white" />
                            </div>
                            <h2 class="text-2xl font-semibold text-text-primary mb-2">Review & Create</h2>
                            <p class="text-text-secondary">Review your settings and create the QuickDrop</p>
                        </div>

                        <div class="space-y-4">
                            <div class="p-4 rounded-xl bg-surface">
                                <h3 class="font-medium text-text-primary mb-3">QuickDrop Details</h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-text-secondary">Title:</span>
                                        <span class="text-text-primary font-medium">{{ form.title }}</span>
                                    </div>
                                    <div v-if="form.comment" class="flex justify-between">
                                        <span class="text-text-secondary">Description:</span>
                                        <span class="text-text-primary">{{ form.comment.substring(0, 50) }}{{ form.comment.length > 50 ? '...' : '' }}</span>
                                    </div>
                                    <div v-if="form.reference_number" class="flex justify-between">
                                        <span class="text-text-secondary">Reference:</span>
                                        <span class="text-text-primary font-mono">{{ form.reference_number }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-text-secondary">Expires:</span>
                                        <span class="text-text-primary">{{ formatExpirationTime(form.expires_in_minutes) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 rounded-xl bg-surface">
                                <h3 class="font-medium text-text-primary mb-3">Security Settings</h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex items-center space-x-2">
                                        <Icon :name="form.use_encryption ? 'check' : 'x'" :size="16" :class="form.use_encryption ? 'text-green-400' : 'text-red-400'" />
                                        <span class="text-text-secondary">End-to-End Encryption</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <Icon :name="form.allow_public_upload ? 'check' : 'x'" :size="16" :class="form.allow_public_upload ? 'text-green-400' : 'text-red-400'" />
                                        <span class="text-text-secondary">Public Upload</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <Icon :name="form.allow_public_download ? 'check' : 'x'" :size="16" :class="form.allow_public_download ? 'text-green-400' : 'text-red-400'" />
                                        <span class="text-text-secondary">Public Download</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <Icon :name="form.allow_public_delete ? 'check' : 'x'" :size="16" :class="form.allow_public_delete ? 'text-green-400' : 'text-red-400'" />
                                        <span class="text-text-secondary">Public Delete</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex space-x-3">
                            <Button
                                variant="ghost"
                                size="lg"
                                @click="prevStep"
                                class="flex-1"
                            >
                                Back
                            </Button>
                            <Button
                                variant="primary"
                                size="lg"
                                @click="submit"
                                :loading="isSubmitting"
                                :disabled="isSubmitting"
                                class="flex-1"
                            >
                                Create QuickDrop
                            </Button>
                        </div>
                    </div>
                    </Transition>
                </div>
            </Card>
        </div>

        <!-- Success Modal -->
        <Modal
            :show="showSuccessModal"
            @close="showSuccessModal = false"
            title="QuickDrop Created Successfully!"
            size="lg"
        >
            <div class="text-center mb-6">
                <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gradient-primary flex items-center justify-center animate-pulse-glow">
                    <Icon name="check" :size="40" class="text-white" />
                </div>
                <p class="text-text-secondary">
                    Your QuickDrop is ready! Share the link below to start collecting files.
                </p>
            </div>

            <div v-if="createdUrl" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">
                        Share Link
                    </label>
                    <div class="flex space-x-2">
                        <input
                            :value="createdUrl"
                            readonly
                            class="flex-1 px-4 py-2 rounded-lg bg-surface border border-white/10 text-text-primary text-sm"
                        />
                        <Button
                            variant="primary"
                            size="sm"
                            icon="copy"
                            @click="copyToClipboard(createdUrl)"
                        >
                            {{ copySuccess ? 'Copied!' : 'Copy' }}
                        </Button>
                    </div>
                </div>
                
                <div v-if="encryptionKey" class="p-4 rounded-lg bg-amber-500/10 border border-amber-500/20">
                    <h3 class="font-medium text-amber-400 mb-2">
                        <Icon name="alertTriangle" :size="16" class="inline mr-1" />
                        Important: Save Your Encryption Key
                    </h3>
                    <div class="flex space-x-2 mb-2">
                        <input
                            :value="encryptionKey"
                            readonly
                            class="flex-1 px-3 py-2 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-400 text-sm font-mono"
                        />
                        <Button
                            variant="outline"
                            size="sm"
                            icon="copy"
                            @click="copyToClipboard(encryptionKey)"
                        >
                            Copy Key
                        </Button>
                    </div>
                    <p class="text-xs text-amber-400">
                        This key is required to decrypt uploaded files. Store it securely and share only with intended recipients.
                    </p>
                </div>
            </div>
            
            <template #footer>
                <div class="flex space-x-3">
                    <Button variant="ghost" @click="showSuccessModal = false">
                        Close
                    </Button>
                    <Button variant="primary" @click="redirectToQuickDrop">
                        Go to QuickDrop
                    </Button>
                </div>
            </template>
        </Modal>
    </AppLayout>
</template>