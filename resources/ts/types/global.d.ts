import type OminityFormsType from '../forms';

declare global {
    interface Window {
        $?: JQueryStatic|undefined;
        OminityForms: typeof OminityFormsType;
    }
}

export {};
