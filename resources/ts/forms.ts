interface OminityFormsConfig {
    toastHandler?: (options: { type: string; message: string }) => void;
    disableSubmitDuringRequest?: boolean;
    enableTracking?: boolean;
    gtagEvents?: {
        successEvent?: string;
        errorEvent?: string;
        unknownEvent?: string;
        submitEvent?: string;
        defaultParams?: Record<string, any>;
    }
}

interface AjaxResponse {
    success: boolean;
    data?: any;
    errors?: Record<string, string[]>;
    message?: string;
}

const OminityForms = {
    config: {} as OminityFormsConfig,

     init(): void {
        document.addEventListener('submit', (e: Event) => {
            const form = e.target as HTMLFormElement;
            if (!(form instanceof HTMLFormElement)) return;

            // Only handle forms managed by Ominity
            if (!form.matches('form.ominity-form[data-form]')) return;

            const formId = form.getAttribute('data-form') || '';
            const recaptchaVersion = form.getAttribute('data-recaptcha');
            const siteKey = document.querySelector('meta[name="recaptcha-site-key"]')?.getAttribute('content');

            const event = new CustomEvent('form:submit', { detail: { formId, recaptchaVersion }, cancelable: true });
            const wasPrevented = !form.dispatchEvent(event);
            if (wasPrevented) {
                e.preventDefault();
                return;
            }

            this.disableSubmitButtons(form);

            if (recaptchaVersion === 'v3') {
                e.preventDefault();

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

                grecaptcha.ready(() => {
                    grecaptcha.execute(siteKey!, { action: 'submit' }).then((token: string) => {
                        recaptchaInput!.value = token;
                        this.submitForm(form, formId);
                    });
                });
            }
            else if (form.getAttribute('data-role') === 'ajax') {
                e.preventDefault();
                this.submitForm(form, formId);
            }
        });
    },

    submitForm(form: HTMLFormElement, formId: string): void {
        this.fireFormGtag(form, 'submit');
        
        if (form.getAttribute('data-role') === 'ajax') {
            this.handleFormAjaxSubmit(form, formId);
        } else {
            form.submit();
        }
    },

    handleFormAjaxSubmit(form: HTMLFormElement, formId: string): void {
        const formData = new FormData(form);

        // Clear success messages
        form.querySelectorAll('.alert.alert-success').forEach(el => el.remove());

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
            // Handle reCAPTCHA v3 token reset
            const recaptchaVersion = form.getAttribute('data-recaptcha');
            if (recaptchaVersion === 'v3') {
                const recaptchaInput = form.querySelector<HTMLInputElement>('input[name="g-recaptcha-response"]');
                if (recaptchaInput) {
                    recaptchaInput.value = ''; // reset token after use
                }
            }

            OminityForms.enableSubmitButtons(form);

            // Clear validation states
            form.querySelectorAll('.has-validation').forEach(el => el.classList.remove('has-validation'));
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

            form.dispatchEvent(new CustomEvent('form:submitted', { detail: { formId, data } }));

            if (data.success) {
                form.reset();

                const event = new CustomEvent('form:success', { detail: { formId, data }, cancelable: true });
                const wasPrevented = !form.dispatchEvent(event);

                if (!wasPrevented) {
                    this.fireFormGtag(form, 'success');
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success';
                    alert.textContent = data?.message || 'Your form was successfully submitted.';
                    form.prepend(alert);
                }
            } else if (data.errors) {
                this.fireFormGtag(form, 'error');
                const event = new CustomEvent('form:errors', { detail: { formId, data }, cancelable: true });
                const wasPrevented = !form.dispatchEvent(event);

                if (!wasPrevented) {
                    OminityForms.handleFormErrors(form, data.errors);
                }
            } else {
                this.fireFormGtag(form, 'unknown');
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
    },

    enableSubmitButtons(form: HTMLFormElement): void {
        const disableSubmit = OminityForms.config.disableSubmitDuringRequest !== false;
        if (disableSubmit) {
            const formId = form.getAttribute('data-form') || '';
            const event = new CustomEvent('form:submit-enabled', { detail: { formId }, cancelable: true });
            const wasPrevented = !form.dispatchEvent(event);
            if (!wasPrevented) {
                const submitButtons = form.querySelectorAll<HTMLButtonElement>('button[type="submit"]');
                submitButtons.forEach(btn => {
                    btn.disabled = false;
                    btn.classList.remove('disabled');
                });
            }
        }
    },

    disableSubmitButtons(form: HTMLFormElement): void {
        const disableSubmit = OminityForms.config.disableSubmitDuringRequest !== false;
        if (disableSubmit) {
            const formId = form.getAttribute('data-form') || '';
            const event = new CustomEvent('form:submit-disabled', { detail: { formId }, cancelable: true });
            const wasPrevented = !form.dispatchEvent(event);
            if (!wasPrevented) {
                const submitButtons = form.querySelectorAll<HTMLButtonElement>('button[type="submit"]');
                submitButtons.forEach(btn => {
                    btn.disabled = true;
                    btn.classList.add('disabled');
                });
            }
        }
    },

    fireFormGtag(form: HTMLFormElement, type: 'success' | 'error' | 'unknown' | 'submit', extraParams?: Record<string, any>) {
        if (this.config.enableTracking === false) {
            return;
        }

        const formId = form.getAttribute('data-form') || '';

        const typeMap: Record<string, string> = {
            success: this.config.gtagEvents?.successEvent || 'form_submission',
            error: this.config.gtagEvents?.errorEvent || 'form_submission_error',
            unknown: this.config.gtagEvents?.unknownEvent || 'form_submission_unknown',
            submit: this.config.gtagEvents?.submitEvent || 'form_submission_attempt'
        };

        const attrOverride = form.getAttribute(`data-gtag-${type}-event`)?.trim() || undefined;
        const eventName = attrOverride || typeMap[type];

        if (!eventName) return;

        const eventParams = {
            form_id: formId,
            ...(this.config.gtagEvents?.defaultParams || {}),
            ...(extraParams || {})
        };

        if (typeof window.gtag === 'function') {
            window.gtag('event', eventName, eventParams);
        }
    }
};

export default OminityForms;
