<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import axios from 'axios';
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

const commonMimeTypes = computed(() => props.config.allowed_mime_types);
const expirationOptions = computed(() => props.config.expiration_options);
const fileSizeOptions = computed(() => props.config.file_size_options);
const maxFilesOptions = computed(() => props.config.max_files_options);

const isSubmitting = ref(false);
const createdUrl = ref(null);
const encryptionKey = ref(null);

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
                createdUrl.value = route('quickdrop.upload', uniqueRequestId);
                
                if (form.use_encryption && clientEncryptionKey) {
                    const storageKey = `quickdrop_key_${uniqueRequestId}`;
                    try {
                        securelyStoreKey(storageKey, clientEncryptionKey);
                    } catch (error) {
                        console.error('Failed to store encryption key:', error);
                    }
                    encryptionKey.value = clientEncryptionKey;
                }
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
        alert('Copied to clipboard!');
    } catch (err) {
        alert('Failed to copy to clipboard');
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Create QuickDrop Box" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h2 class="text-lg font-semibold mb-6">Create a New QuickDrop Box</h2>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <InputLabel for="title" value="Box Title" />
                                <TextInput
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Enter a title for your QuickDrop box"
                                    required
                                />
                                <InputError :message="form.errors.title" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="comment" value="Comment" />
                                <textarea
                                    id="comment"
                                    v-model="form.comment"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600"
                                    rows="3"
                                    placeholder="Add any additional information or instructions"
                                ></textarea>
                                <InputError :message="form.errors.comment" class="mt-2" />
                            </div>

                            <div v-if="props.config?.reference_number?.enabled">
                                <InputLabel 
                                    for="reference_number" 
                                    :value="props.config.reference_number?.label || 'Reference Number'" 
                                />
                                <TextInput
                                    id="reference_number"
                                    v-model="form.reference_number"
                                    type="text"
                                    class="mt-1 block w-full"
                                    :placeholder="props.config.reference_number?.help_text || 'Enter reference number'"
                                    :required="props.config.reference_number?.required"
                                />
                                <InputError :message="form.errors.reference_number" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="expires_in_minutes" value="Expiration Time" />
                                <select
                                    v-model="form.expires_in_minutes"
                                    id="expires_in_minutes"
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600"
                                >
                                    <option v-for="option in expirationOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.expires_in_minutes" class="mt-2" />
                            </div>

                            <div class="flex items-center">
                                <input
                                    v-model="form.use_encryption"
                                    id="use_encryption"
                                    type="checkbox"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                />
                                <label for="use_encryption" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                                    Enable End-to-End Encryption
                                </label>
                            </div>

                            <!-- Public User Permissions -->
                            <div class="space-y-4 border-t pt-4 mt-4">
                                <h3 class="text-md font-medium">Public User Permissions</h3>
                                
                                <div class="flex items-center">
                                    <input
                                        v-model="form.allow_public_upload"
                                        id="allow_public_upload"
                                        type="checkbox"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    />
                                    <label for="allow_public_upload" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                                        Allow Public Users to Upload Files
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input
                                        v-model="form.allow_public_download"
                                        id="allow_public_download"
                                        type="checkbox"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    />
                                    <label for="allow_public_download" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                                        Allow Public Users to Download Files
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input
                                        v-model="form.allow_public_delete"
                                        id="allow_public_delete"
                                        type="checkbox"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    />
                                    <label for="allow_public_delete" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                                        Allow Public Users to Delete Files
                                    </label>
                                </div>
                            </div>

                            <div class="flex items-center justify-end mt-6">
                                <PrimaryButton :class="{ 'opacity-25': isSubmitting }" :disabled="isSubmitting">
                                    Create QuickDrop Box
                                </PrimaryButton>
                            </div>
                        </form>

                        <!-- Success State -->
                        <div v-if="createdUrl" class="mt-8 p-4 bg-green-50 dark:bg-green-900 rounded-lg">
                            <h3 class="text-lg font-medium text-green-800 dark:text-green-100 mb-4">
                                QuickDrop Box Created Successfully!
                            </h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-green-800 dark:text-green-100">
                                        Upload URL
                                    </label>
                                    <div class="mt-1 flex rounded-md shadow-sm">
                                        <input
                                            type="text"
                                            :value="createdUrl"
                                            readonly
                                            class="flex-1 min-w-0 block w-full px-3 py-2 rounded-md text-sm border-green-300 bg-white dark:bg-gray-700"
                                        />
                                        <button
                                            type="button"
                                            @click="copyToClipboard(createdUrl)"
                                            class="ml-3 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 dark:text-green-100 dark:bg-green-800 dark:hover:bg-green-700"
                                        >
                                            Copy
                                        </button>
                                    </div>
                                </div>

                                <div v-if="encryptionKey" class="mt-4">
                                    <label class="block text-sm font-medium text-green-800 dark:text-green-100">
                                        Encryption Key (Save this securely!)
                                    </label>
                                    <div class="mt-1 flex rounded-md shadow-sm">
                                        <input
                                            type="text"
                                            :value="encryptionKey"
                                            readonly
                                            class="flex-1 min-w-0 block w-full px-3 py-2 rounded-md text-sm border-green-300 bg-white dark:bg-gray-700"
                                        />
                                        <button
                                            type="button"
                                            @click="copyToClipboard(encryptionKey)"
                                            class="ml-3 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 dark:text-green-100 dark:bg-green-800 dark:hover:bg-green-700"
                                        >
                                            Copy
                                        </button>
                                    </div>
                                    <p class="mt-2 text-sm text-green-700 dark:text-green-200">
                                        This key will be required to decrypt the files. Store it securely and share it only with intended recipients.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template> 