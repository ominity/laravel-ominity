import type OminityFormsType from '../forms';
import type OminityTrackingType, { TrackingBootstrapConfig } from '../tracking';

declare global {
    interface Window {
        $?: JQueryStatic|undefined;
        OminityForms: typeof OminityFormsType;
        OminityTracking: typeof OminityTrackingType;
        __ominityTrackingConfig?: TrackingBootstrapConfig;
        gtag?: (command: string, eventName: string, eventParams?: Record<string, any>) => void;
    }
}

export {};
