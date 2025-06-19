export function useToast() {
    const showToast = (message, options = {}) => {
        const event = new CustomEvent('show-toast', {
            detail: {
                message,
                type: options.type || 'success',
                detail: options.detail || '',
                duration: options.duration || 5000
            }
        });
        window.dispatchEvent(event);
    };

    const success = (message, detail = '') => {
        showToast(message, { type: 'success', detail });
    };

    const error = (message, detail = '') => {
        showToast(message, { type: 'error', detail });
    };

    const warning = (message, detail = '') => {
        showToast(message, { type: 'warning', detail });
    };

    const info = (message, detail = '') => {
        showToast(message, { type: 'info', detail });
    };

    return {
        showToast,
        success,
        error,
        warning,
        info
    };
}