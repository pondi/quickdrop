import { ref, watch } from 'vue';
import { verifyEncryptionKey, securelyStoreKey, retrieveSecureKey } from '@/Services/EncryptionService';

export function useEncryptionKey(uploadRequest) {
    const encryptionKey = ref(null);
    const formKey = ref(null);

    const getStorageKey = (uniqueRequestId) => `quickdrop_key_${uniqueRequestId}`;

    const initializeKey = async () => {
        if (!uploadRequest.value?.is_encrypted) return;

        const key = await retrieveSecureKey(getStorageKey(uploadRequest.value.unique_request_id));
        if (key) {
            encryptionKey.value = key;
            formKey.value = key;
        }
    };

    const verifyAndStoreKey = async (key) => {
        if (!key) return null;
        
        const isValid = await verifyEncryptionKey(key, uploadRequest.value.key_verification_hash);
        if (isValid) {
            await securelyStoreKey(
                getStorageKey(uploadRequest.value.unique_request_id), 
                key
            );
            encryptionKey.value = key;
            return key;
        } else {
            alert('Invalid encryption key');
            formKey.value = null;
            encryptionKey.value = null;
            localStorage.removeItem(getStorageKey(uploadRequest.value.unique_request_id));
            return null;
        }
    };

    const getCurrentKey = async () => {
        return formKey.value || encryptionKey.value;
    };

    // Watch for changes in the form's encryption key
    watch(formKey, (newKey) => {
        if (uploadRequest.value?.is_encrypted && newKey) {
            verifyAndStoreKey(newKey);
        }
    });

    return {
        formKey,
        encryptionKey,
        initializeKey,
        verifyAndStoreKey,
        getCurrentKey,
    };
} 