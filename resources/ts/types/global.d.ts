import type OminityFormsType from '../forms';

declare global {
    interface Window {
        $?: JQueryStatic|undefined;
        OminityForms: typeof OminityFormsType;
        gtag?: (command: string, eventName: string, eventParams?: Record<string, any>) => void;
    }
}

export {};
