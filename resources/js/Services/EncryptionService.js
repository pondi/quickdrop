// Utility functions for encryption/decryption using Web Crypto API

/**
 * Convert hex string to Uint8Array
 */
function hexToUint8Array(hexString) {
    const pairs = hexString.match(/[\dA-F]{2}/gi);
    if (!pairs) {
        throw new Error('Invalid hex string');
    }
    return new Uint8Array(
        pairs.map(s => parseInt(s, 16))
    );
}

/**
 * Convert Uint8Array to hex string
 */
function uint8ArrayToHex(uint8Array) {
    return Array.from(uint8Array)
        .map(b => b.toString(16).padStart(2, '0'))
        .join('');
}

/**
 * Derive a key using PBKDF2
 */
async function deriveKey(password, salt, iterations = 600000) {
    const enc = new TextEncoder();
    const passwordBuffer = enc.encode(password);
    
    // Import the password as a key
    const baseKey = await crypto.subtle.importKey(
        'raw',
        passwordBuffer,
        'PBKDF2',
        false,
        ['deriveBits']
    );
    
    // Derive bits using PBKDF2
    const derivedBits = await crypto.subtle.deriveBits(
        {
            name: 'PBKDF2',
            salt: salt,
            iterations: iterations,
            hash: 'SHA-256'
        },
        baseKey,
        256 // 32 bytes for AES-256
    );
    
    return new Uint8Array(derivedBits);
}

/**
 * Generate a random encryption key
 */
export async function generateEncryptionKey() {
    // Generate a random key using Web Crypto API
    const key = await crypto.subtle.generateKey(
        {
            name: 'AES-GCM',
            length: 256
        },
        true, // extractable
        ['encrypt', 'decrypt']
    );
    
    // Export the key as raw bytes
    const exportedKey = await crypto.subtle.exportKey('raw', key);
    return uint8ArrayToHex(new Uint8Array(exportedKey));
}

/**
 * Generate a verification hash for the encryption key
 */
export async function generateKeyHash(key) {
    try {
        const keyBytes = hexToUint8Array(key);
        // Add a constant salt to prevent timing attacks
        const salt = new Uint8Array(16);
        const derivedKey = await deriveKey(uint8ArrayToHex(keyBytes), salt);
        const hashBuffer = await crypto.subtle.digest('SHA-256', derivedKey);
        return uint8ArrayToHex(new Uint8Array(hashBuffer));
    } catch (error) {
        throw new Error('Failed to generate key hash');
    }
}

/**
 * Verify if the provided key matches the verification hash
 */
export async function verifyEncryptionKey(key, verificationHash) {
    if (!key || !verificationHash) return false;
    try {
        const hash = await generateKeyHash(key);
        // Use timing-safe comparison
        return timingSafeEqual(hash, verificationHash);
    } catch {
        return false;
    }
}

/**
 * Timing-safe string comparison
 */
function timingSafeEqual(a, b) {
    if (a.length !== b.length) {
        return false;
    }
    
    let result = 0;
    for (let i = 0; i < a.length; i++) {
        result |= a.charCodeAt(i) ^ b.charCodeAt(i);
    }
    return result === 0;
}

/**
 * Encrypt a file using AES-GCM
 */
export async function encryptFile(file, keyHex) {
    try {
        // Convert hex key to Uint8Array
        const keyBytes = hexToUint8Array(keyHex);
        
        // Generate a random IV
        const iv = crypto.getRandomValues(new Uint8Array(12));
        
        // Import the key
        const key = await crypto.subtle.importKey(
            'raw',
            keyBytes,
            { name: 'AES-GCM', length: 256 },
            false,
            ['encrypt']
        );

        // Read file as ArrayBuffer
        const fileData = await file.arrayBuffer();
        
        // Add file metadata to AAD (Additional Authenticated Data)
        const aad = new TextEncoder().encode(JSON.stringify({
            name: file.name,
            type: file.type,
            size: file.size,
            lastModified: file.lastModified
        }));
        
        // Encrypt the file content with AAD
        const encryptedContent = await crypto.subtle.encrypt(
            {
                name: 'AES-GCM',
                iv: iv,
                additionalData: aad
            },
            key,
            fileData
        );

        // Combine IV, AAD length (4 bytes), AAD, and encrypted content
        const aadLengthBytes = new Uint8Array(4);
        new DataView(aadLengthBytes.buffer).setUint32(0, aad.length, true);
        
        const combinedContent = new Uint8Array(
            iv.length + aadLengthBytes.length + aad.length + encryptedContent.byteLength
        );
        let offset = 0;
        combinedContent.set(iv, offset);
        offset += iv.length;
        combinedContent.set(aadLengthBytes, offset);
        offset += aadLengthBytes.length;
        combinedContent.set(aad, offset);
        offset += aad.length;
        combinedContent.set(new Uint8Array(encryptedContent), offset);
        
        // Create a new file with encrypted content
        return new File([combinedContent], file.name, {
            type: 'application/octet-stream',
            lastModified: file.lastModified
        });
    } catch (error) {
        console.error('Encryption error:', error);
        throw new Error('Failed to encrypt file');
    }
}

/**
 * Decrypt a file using AES-GCM
 */
export async function decryptFile(encryptedBlob, keyHex) {
    try {
        // Convert hex key to Uint8Array
        const keyBytes = hexToUint8Array(keyHex);
        
        // Import the key
        const key = await crypto.subtle.importKey(
            'raw',
            keyBytes,
            { name: 'AES-GCM', length: 256 },
            false,
            ['decrypt']
        );

        // Read the encrypted data
        const encryptedData = await encryptedBlob.arrayBuffer();
        const encryptedArray = new Uint8Array(encryptedData);
        
        // Extract IV (first 12 bytes)
        const iv = encryptedArray.slice(0, 12);
        let offset = 12;
        
        // Extract AAD length and AAD
        const aadLength = new DataView(encryptedArray.buffer).getUint32(offset, true);
        offset += 4;
        const aad = encryptedArray.slice(offset, offset + aadLength);
        offset += aadLength;
        
        // Extract encrypted content
        const content = encryptedArray.slice(offset);
        
        // Decrypt the content with AAD
        const decryptedContent = await crypto.subtle.decrypt(
            {
                name: 'AES-GCM',
                iv: iv,
                additionalData: aad
            },
            key,
            content
        );
        
        // Parse the AAD to get original file metadata
        const metadata = JSON.parse(new TextDecoder().decode(aad));
        
        return new Blob([decryptedContent], { type: metadata.type });
    } catch (error) {
        console.error('Decryption error:', error);
        throw new Error('Failed to decrypt file');
    }
}

/**
 * Securely store an encryption key in localStorage
 * This uses the user's session key to encrypt the file encryption key
 */
export async function securelyStoreKey(storageKey, encryptionKey) {
    try {
        // Use session storage key or generate one
        let sessionKey = sessionStorage.getItem('session_key');
        if (!sessionKey) {
            sessionKey = await generateEncryptionKey();
            sessionStorage.setItem('session_key', sessionKey);
        }
        
        // Encrypt the encryption key with the session key
        const keyBytes = hexToUint8Array(encryptionKey);
        const sessionKeyBytes = hexToUint8Array(sessionKey);
        
        const key = await crypto.subtle.importKey(
            'raw',
            sessionKeyBytes,
            { name: 'AES-GCM', length: 256 },
            false,
            ['encrypt']
        );
        
        const iv = crypto.getRandomValues(new Uint8Array(12));
        const encryptedKey = await crypto.subtle.encrypt(
            {
                name: 'AES-GCM',
                iv: iv
            },
            key,
            keyBytes
        );
        
        // Store the encrypted key with its IV
        const combined = new Uint8Array(iv.length + encryptedKey.byteLength);
        combined.set(iv);
        combined.set(new Uint8Array(encryptedKey), iv.length);
        
        localStorage.setItem(storageKey, uint8ArrayToHex(combined));
    } catch (error) {
        console.error('Failed to securely store key:', error);
        // Fallback to direct storage if encryption fails
        localStorage.setItem(storageKey, encryptionKey);
    }
}

/**
 * Retrieve a securely stored encryption key from localStorage
 */
export async function retrieveSecureKey(storageKey) {
    try {
        const sessionKey = sessionStorage.getItem('session_key');
        if (!sessionKey) {
            throw new Error('Session key not found');
        }
        
        const stored = localStorage.getItem(storageKey);
        if (!stored) {
            return null;
        }
        
        const combined = hexToUint8Array(stored);
        const iv = combined.slice(0, 12);
        const encryptedKey = combined.slice(12);
        
        const sessionKeyBytes = hexToUint8Array(sessionKey);
        const key = await crypto.subtle.importKey(
            'raw',
            sessionKeyBytes,
            { name: 'AES-GCM', length: 256 },
            false,
            ['decrypt']
        );
        
        const decryptedKey = await crypto.subtle.decrypt(
            {
                name: 'AES-GCM',
                iv: iv
            },
            key,
            encryptedKey
        );
        
        return uint8ArrayToHex(new Uint8Array(decryptedKey));
    } catch (error) {
        console.error('Failed to retrieve secure key:', error);
        // Fallback to direct retrieval if decryption fails
        return localStorage.getItem(storageKey);
    }
} 