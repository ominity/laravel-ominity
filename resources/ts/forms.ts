interface OminityFormsConfig {
    toastHandler?: (options: { type: string; message: string }) => void;
}

interface AjaxResponse {
    success: boolean;
    data?: any;
    errors?: Record<string, string[]>;
}

const OminityForms = {
    config: {} as OminityFormsConfig,

    init(): void {
        document.querySelectorAll<HTMLFormElement>('form.ominity-form[data-form]').forEach(form => {
            const formId = form.getAttribute('data-form') || '';
            const recaptchaVersion = form.getAttribute('data-recaptcha'); // 'v2', 'v3', or null
            const siteKey = document.querySelector('meta[name="recaptcha-site-key"]')?.getAttribute('content');

            form.addEventListener('submit', (e) => {
                form.dispatchEvent(new CustomEvent('form:submit', { detail: { formId, recaptchaVersion } }));

                if (recaptchaVersion === 'v3') {
                    if (typeof grecaptcha === 'undefined') {
                        console.warn('reCAPTCHA v3 is not loaded.');
                        return;
                    }

                    let recaptchaInput = form.querySelector<HTMLInputElement>('input[name="g-recaptcha-response"]');
                    if (!recaptchaInput) {
                        recaptchaInput = document.createElement('input');
                        recaptchaInput.type = 'hidden';
                        recaptchaInput.name = 'g-recaptcha-response';
                        form.appendChild(recaptchaInput);
                    }

                    if (recaptchaInput.value === '') {
                        e.preventDefault();

                        grecaptcha.execute(siteKey!, { action: 'submit' }).then((token: string) => {
                            recaptchaInput!.value = token;
                            OminityForms.submitForm(form, formId);
                        });
                    }
                }

                if (form.getAttribute('data-role') === 'ajax') {
                    e.preventDefault();
                    OminityForms.submitForm(form, formId);
                }
            });
        });
    },

    submitForm(form: HTMLFormElement, formId: string): void {
        if (form.getAttribute('data-role') === 'ajax') {
            this.handleFormAjaxSubmit(form, formId);
        } else {
            form.submit();
        }
    },

    handleFormAjaxSubmit(form: HTMLFormElement, formId: string): void {
        const formData = new FormData(form);

        fetch(form.action, {
            method: form.method || 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            }
        })
        .then(response => response.json())
        .then((data: AjaxResponse) => {
            const recaptchaVersion = form.getAttribute('data-recaptcha');
            if (recaptchaVersion === 'v3') {
                const recaptchaInput = form.querySelector<HTMLInputElement>('input[name="g-recaptcha-response"]');
                if (recaptchaInput) {
                    recaptchaInput.value = ''; // reset token after use
                }
            }

            form.dispatchEvent(new CustomEvent('form:submitted', { detail: { formId, data } }));

            if (data.success) {
                form.reset();
                form.dispatchEvent(new CustomEvent('form:success', { detail: { formId, data } }));
            } else if (data.errors) {
                OminityForms.handleFormErrors(form, data.errors);
                form.dispatchEvent(new CustomEvent('form:error', { detail: { formId, data } }));
            } else {
                form.dispatchEvent(new CustomEvent('form:unknown', { detail: { formId, data } }));
            }
        })
        .catch(error => {
            console.error('Form submit error:', error);
            form.dispatchEvent(new CustomEvent('form:fail', { detail: { formId, error } }));
        });
    },

    handleFormErrors(form: HTMLFormElement, errors: Record<string, string[]>): void {
        form.querySelectorAll('.has-validation').forEach(el => el.classList.remove('has-validation'));
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

        Object.entries(errors).forEach(([field, messageArray]) => {
            const message = messageArray[0];

            let selector = `[name="${field}"], [name="${field}[]"]`;
            if (field.includes('.')) {
                const flatField = field.replace(/\./g, '][');
                selector += `, [name="${flatField}"]`;
            }

            const inputElements = form.querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>(selector);

            if (inputElements.length > 0) {
                let handled = false;

                inputElements.forEach(input => {
                    const isHidden = input.type === 'hidden' || input.offsetParent === null;

                    if (isHidden) {
                        OminityForms.showToast(message, 'danger');
                        handled = true;
                    } else {
                        input.classList.add('is-invalid');

                        const inputGroup = input.closest('.input-group');
                        if (inputGroup) {
                            inputGroup.classList.add('has-validation');
                        }
                    }
                });

                if (!handled) {
                    const firstInput = inputElements[0];
                    const isCheckboxOrRadio = firstInput.type === 'checkbox' || firstInput.type === 'radio';

                    if (isCheckboxOrRadio) {
                        const lastInput = inputElements[inputElements.length - 1];
                        lastInput.insertAdjacentHTML('afterend', `<div class="invalid-feedback">${message}</div>`);
                    } else {
                        firstInput.insertAdjacentHTML('afterend', `<div class="invalid-feedback">${message}</div>`);
                    }
                }
            } else {
                OminityForms.showToast(message, 'danger');
            }
        });
    },

    showToast(message: string, type = 'danger'): void {
        if (typeof OminityForms.config.toastHandler === 'function') {
            OminityForms.config.toastHandler({ type, message });
        } else if (typeof window.$ !== 'undefined' && typeof (window.$ as any).fn.showToast === 'function') {
            (window.$ as any).fn.showToast({ type, title: message });
        } else {
            console.warn(`[${type.toUpperCase()}] ${message}`);
        }
    }
};

export default OminityForms;
