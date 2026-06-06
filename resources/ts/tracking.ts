interface TrackingEventMetadata {
    [key: string]: unknown;
}

interface TrackingEventUtm {
    [key: string]: string;
}

interface TrackEventRequest {
    event: string;
    timestamp?: string;
    title?: string;
    url?: string;
    metadata?: TrackingEventMetadata;
    visitorId?: string;
    userId?: number;
    referrer?: string;
    utm?: TrackingEventUtm;
}

interface VisitorCookieOptions {
    name?: string;
    path?: string;
    maxAgeSeconds?: number;
    secure?: boolean;
    sameSite?: 'lax' | 'strict' | 'none';
}

interface TrackingEventNames {
    pageView?: string;
    sessionStart?: string;
    scrollDepth?: string;
    outboundClick?: string;
    fileDownload?: string;
    formSubmit?: string;
}

export interface TrackingBootstrapConfig {
    enabled?: boolean;
    endpoint?: string;
    userId?: number;
    visitorId?: string;
    visitorCookie?: VisitorCookieOptions;
    sampleRate?: number;
    trackPageViews?: boolean;
    trackSessions?: boolean;
    trackScrollDepth?: boolean;
    scrollDepthThresholds?: number[];
    trackOutboundClicks?: boolean;
    trackFileDownloads?: boolean;
    trackFormSubmissions?: boolean;
    trackCustomClicks?: boolean;
    flushQueueOnMount?: boolean;
    queueKey?: string;
    sessionKey?: string;
    maxQueueSize?: number;
    pageMetadata?: TrackingEventMetadata;
    extraMetadata?: TrackingEventMetadata;
    eventNames?: TrackingEventNames;
}

interface TrackEventInput {
    event: string;
    title?: string;
    url?: string;
    metadata?: TrackingEventMetadata;
    referrer?: string;
    utm?: TrackingEventUtm;
}

interface QueueOptions {
    endpoint: string;
    queueKey: string;
    maxQueueSize: number;
}

export interface OminityTrackingApi {
    config: TrackingBootstrapConfig | null;
    start(config?: TrackingBootstrapConfig): OminityTrackingApi;
    track(input: TrackEventInput, options?: { preferBeacon?: boolean; queueOnFailure?: boolean }): Promise<boolean>;
    trackPageView(): Promise<boolean>;
    setPageMetadata(metadata: TrackingEventMetadata | null | undefined): OminityTrackingApi;
    mergePageMetadata(metadata: TrackingEventMetadata | null | undefined): OminityTrackingApi;
    flushQueue(): Promise<void>;
    ensureVisitorId(): string | null;
    clearVisitorId(): void;
}

const DEFAULT_TRACKING_ENDPOINT = '/ominity/tracking/events';
const DEFAULT_QUEUE_KEY = '__ominity_tracking_queue_v1';
const DEFAULT_SESSION_KEY = '__ominity_tracking_session_v1';
const DEFAULT_MAX_QUEUE_SIZE = 50;
const DEFAULT_SCROLL_DEPTH_THRESHOLDS = [25, 50, 75, 100];
const DEFAULT_VISITOR_COOKIE_NAME = '_omtvid';
const DEFAULT_VISITOR_COOKIE_MAX_AGE_SECONDS = 60 * 60 * 24 * 365;
const DOWNLOAD_FILE_PATTERN = /\.(csv|doc|docx|ics|jpg|jpeg|json|mp3|mp4|pdf|png|ppt|pptx|svg|txt|webp|xls|xlsx|zip)(\?.*)?$/i;
const UUID_PATTERN = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

const state = {
    started: false,
    listenersInstalled: false,
    navigationInstalled: false,
    config: null as TrackingBootstrapConfig | null,
    visitorId: null as string | null,
    sampledIn: null as boolean | null,
    lastTrackedUrl: null as string | null,
    lastPageKey: null as string | null,
    trackedScrollDepths: new Set<number>(),
    pageMetadata: {} as TrackingEventMetadata,
};

function asNonEmptyString(value: unknown): string | undefined {
    if (typeof value !== 'string') {
        return;
    }

    const trimmed = value.trim();
    return trimmed.length > 0 ? trimmed : undefined;
}

function asRecord(value: unknown): Record<string, unknown> {
    if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
        return value as Record<string, unknown>;
    }

    return {};
}

function isVisitorId(value: unknown): value is string {
    return typeof value === 'string' && UUID_PATTERN.test(value.trim());
}

function fillRandomBytes(bytes: Uint8Array): void {
    const randomValues = globalThis.crypto?.getRandomValues;
    if (typeof randomValues === 'function') {
        randomValues.call(globalThis.crypto, bytes);
        return;
    }

    for (let index = 0; index < bytes.length; index += 1) {
        bytes[index] = Math.floor(Math.random() * 256);
    }
}

function generateVisitorId(): string {
    const randomUUID = globalThis.crypto?.randomUUID;
    if (typeof randomUUID === 'function') {
        const candidate = randomUUID.call(globalThis.crypto);
        if (isVisitorId(candidate)) {
            return candidate;
        }
    }

    const bytes = new Uint8Array(16);
    fillRandomBytes(bytes);
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;

    const hex = Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('');
    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}

function getRuntimeConfig(): TrackingBootstrapConfig {
    return state.config ?? {};
}

function normalizeSampleRate(value: unknown): number {
    if (typeof value !== 'number' || !Number.isFinite(value)) {
        return 1;
    }

    return Math.min(1, Math.max(0, value));
}

function normalizeThresholds(values: unknown): number[] {
    const input = Array.isArray(values) && values.length > 0 ? values : DEFAULT_SCROLL_DEPTH_THRESHOLDS;
    const normalized = Array.from(new Set(
        input
            .map((value) => Number(value))
            .filter((value) => Number.isFinite(value))
            .map((value) => Math.min(100, Math.max(1, Math.floor(value)))),
    ));

    normalized.sort((left, right) => left - right);
    return normalized;
}

function resolveVisitorCookieOptions(): Required<VisitorCookieOptions> {
    const config = getRuntimeConfig().visitorCookie ?? {};

    return {
        name: asNonEmptyString(config.name) ?? DEFAULT_VISITOR_COOKIE_NAME,
        path: asNonEmptyString(config.path) ?? '/',
        maxAgeSeconds:
            typeof config.maxAgeSeconds === 'number' && Number.isFinite(config.maxAgeSeconds)
                ? Math.max(0, Math.floor(config.maxAgeSeconds))
                : DEFAULT_VISITOR_COOKIE_MAX_AGE_SECONDS,
        secure: config.secure !== false,
        sameSite: config.sameSite ?? 'lax',
    };
}

function readVisitorCookie(): string | null {
    const { name } = resolveVisitorCookieOptions();
    const entries = document.cookie.split(';').map((entry) => entry.trim());

    for (const entry of entries) {
        if (!entry) {
            continue;
        }

        const separatorIndex = entry.indexOf('=');
        const key = separatorIndex >= 0 ? entry.slice(0, separatorIndex) : entry;
        if (key !== name) {
            continue;
        }

        const rawValue = separatorIndex >= 0 ? entry.slice(separatorIndex + 1) : '';
        const value = decodeURIComponent(rawValue).trim();
        return isVisitorId(value) ? value : null;
    }

    return null;
}

function writeVisitorCookie(visitorId: string): void {
    if (!isVisitorId(visitorId)) {
        return;
    }

    const options = resolveVisitorCookieOptions();
    const parts = [`${options.name}=${encodeURIComponent(visitorId)}`, `Path=${options.path}`, `Max-Age=${options.maxAgeSeconds}`];

    if (options.secure) {
        parts.push('Secure');
    }

    parts.push(`SameSite=${options.sameSite.charAt(0).toUpperCase()}${options.sameSite.slice(1)}`);
    document.cookie = parts.join('; ');
}

function clearVisitorCookie(): void {
    const options = resolveVisitorCookieOptions();
    document.cookie = `${options.name}=; Path=${options.path}; Max-Age=0; SameSite=${options.sameSite.charAt(0).toUpperCase()}${options.sameSite.slice(1)}${options.secure ? '; Secure' : ''}`;
}

function resolveCurrentUrl(): string {
    return window.location.href;
}

function resolveCurrentPageKey(): string {
    return `${window.location.pathname}${window.location.search}`;
}

function buildUtmFromLocation(location: Location): TrackingEventUtm | undefined {
    const params = new URLSearchParams(location.search);
    const knownKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'utm_id'];
    const utm: TrackingEventUtm = {};

    for (const key of knownKeys) {
        const value = asNonEmptyString(params.get(key));
        if (value) {
            utm[key] = value;
        }
    }

    return Object.keys(utm).length > 0 ? utm : undefined;
}

function buildBaseMetadata(pageKey: string, previousUrl: string | null): TrackingEventMetadata {
    const locale = asNonEmptyString(document.documentElement.lang) ?? asNonEmptyString(navigator.language);
    const timezone = asNonEmptyString(Intl.DateTimeFormat().resolvedOptions().timeZone);

    return {
        page_key: pageKey,
        pathname: window.location.pathname,
        search: window.location.search || undefined,
        hash: window.location.hash || undefined,
        previous_url: previousUrl ?? undefined,
        locale,
        timezone,
        viewport_width: window.innerWidth,
        viewport_height: window.innerHeight,
        screen_width: window.screen.width,
        screen_height: window.screen.height,
    };
}

function parseDatasetMetadata(element: HTMLElement): TrackingEventMetadata | undefined {
    const raw = asNonEmptyString(element.dataset.ominityMetadata);
    if (!raw) {
        return;
    }

    try {
        const parsed = JSON.parse(raw);
        if (typeof parsed === 'object' && parsed !== null && !Array.isArray(parsed)) {
            return parsed as TrackingEventMetadata;
        }
    } catch {
        // Ignore invalid JSON.
    }

    return;
}

function isExternalUrl(candidate: URL): boolean {
    return candidate.origin !== window.location.origin;
}

function isDownloadUrl(element: HTMLAnchorElement, candidate: URL): boolean {
    if (element.hasAttribute('download')) {
        return true;
    }

    return DOWNLOAD_FILE_PATTERN.test(candidate.href);
}

function getElementText(element: Element): string | undefined {
    const ariaLabel = asNonEmptyString(element.getAttribute('aria-label'));
    if (ariaLabel) {
        return ariaLabel;
    }

    return asNonEmptyString(element.textContent);
}

function readClosestTrackableElement(target: EventTarget | null): HTMLElement | null {
    if (!(target instanceof Element)) {
        return null;
    }

    return target.closest('[data-ominity-event], a[href], form, button');
}

function readQueue(queueKey: string): TrackEventRequest[] {
    try {
        const raw = window.localStorage.getItem(queueKey);
        if (!raw) {
            return [];
        }

        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed as TrackEventRequest[] : [];
    } catch {
        return [];
    }
}

function writeQueue(queueKey: string, entries: TrackEventRequest[]): void {
    try {
        if (entries.length === 0) {
            window.localStorage.removeItem(queueKey);
            return;
        }

        window.localStorage.setItem(queueKey, JSON.stringify(entries));
    } catch {
        // Ignore storage failures.
    }
}

function enqueueEvent(queueKey: string, maxQueueSize: number, payload: TrackEventRequest): void {
    const queue = readQueue(queueKey);
    queue.push(payload);
    writeQueue(queueKey, queue.slice(-Math.max(1, maxQueueSize)));
}

function resolveQueueOptions(): QueueOptions {
    const config = getRuntimeConfig();

    return {
        endpoint: asNonEmptyString(config.endpoint) ?? DEFAULT_TRACKING_ENDPOINT,
        queueKey: asNonEmptyString(config.queueKey) ?? DEFAULT_QUEUE_KEY,
        maxQueueSize:
            typeof config.maxQueueSize === 'number' && Number.isFinite(config.maxQueueSize)
                ? Math.max(1, Math.floor(config.maxQueueSize))
                : DEFAULT_MAX_QUEUE_SIZE,
    };
}

async function sendTrackingRequest(payload: TrackEventRequest, options: QueueOptions & { preferBeacon?: boolean }): Promise<boolean> {
    const body = JSON.stringify(payload);

    if (options.preferBeacon && typeof navigator.sendBeacon === 'function') {
        try {
            const blob = new Blob([body], { type: 'application/json' });
            if (navigator.sendBeacon(options.endpoint, blob)) {
                return true;
            }
        } catch {
            // Fall through to fetch.
        }
    }

    try {
        const csrfToken = asNonEmptyString(document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? undefined);
        const response = await fetch(options.endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            },
            body,
            credentials: 'same-origin',
            cache: 'no-store',
            keepalive: true,
        });

        return response.ok;
    } catch {
        return false;
    }
}

async function dispatchTrackingEvent(
    payload: TrackEventRequest,
    options: QueueOptions & { preferBeacon?: boolean; queueOnFailure?: boolean },
): Promise<boolean> {
    const success = await sendTrackingRequest(payload, options);
    if (!success && options.queueOnFailure !== false) {
        enqueueEvent(options.queueKey, options.maxQueueSize, payload);
    }

    return success;
}

async function flushTrackingQueue(): Promise<void> {
    const options = resolveQueueOptions();
    const queue = readQueue(options.queueKey);
    if (queue.length === 0) {
        return;
    }

    const pending: TrackEventRequest[] = [];
    for (const payload of queue) {
        const success = await sendTrackingRequest(payload, options);
        if (!success) {
            pending.push(payload);
        }
    }

    writeQueue(options.queueKey, pending);
}

function ensureSampledIn(): boolean {
    if (state.sampledIn !== null) {
        return state.sampledIn;
    }

    state.sampledIn = Math.random() <= normalizeSampleRate(getRuntimeConfig().sampleRate);
    return state.sampledIn;
}

function ensureVisitorId(): string | null {
    if (state.visitorId && isVisitorId(state.visitorId)) {
        return state.visitorId;
    }

    const configuredVisitorId = getRuntimeConfig().visitorId;
    if (isVisitorId(configuredVisitorId)) {
        state.visitorId = configuredVisitorId;
        writeVisitorCookie(configuredVisitorId);
        return state.visitorId;
    }

    const cookieVisitorId = readVisitorCookie();
    if (cookieVisitorId) {
        state.visitorId = cookieVisitorId;
        return state.visitorId;
    }

    state.visitorId = generateVisitorId();
    writeVisitorCookie(state.visitorId);
    return state.visitorId;
}

function mergeMetadata(...parts: Array<TrackingEventMetadata | undefined>): TrackingEventMetadata | undefined {
    const merged: TrackingEventMetadata = {};

    for (const part of parts) {
        if (!part || typeof part !== 'object' || Array.isArray(part)) {
            continue;
        }

        Object.assign(merged, part);
    }

    return Object.keys(merged).length > 0 ? merged : undefined;
}

function normalizeConfig(config: TrackingBootstrapConfig | undefined): TrackingBootstrapConfig {
    const existing = getRuntimeConfig();
    const incoming = config ?? {};

    return {
        ...existing,
        ...incoming,
        visitorCookie: {
            ...(existing.visitorCookie ?? {}),
            ...(incoming.visitorCookie ?? {}),
        },
        eventNames: {
            ...(existing.eventNames ?? {}),
            ...(incoming.eventNames ?? {}),
        },
        pageMetadata: {
            ...(existing.pageMetadata ?? {}),
            ...(incoming.pageMetadata ?? {}),
        },
        extraMetadata: {
            ...(existing.extraMetadata ?? {}),
            ...(incoming.extraMetadata ?? {}),
        },
    };
}

function isEnabled(): boolean {
    return getRuntimeConfig().enabled !== false;
}

async function track(input: TrackEventInput, options?: { preferBeacon?: boolean; queueOnFailure?: boolean }): Promise<boolean> {
    if (!isEnabled() || !ensureSampledIn()) {
        return false;
    }

    const visitorId = ensureVisitorId();
    if (!visitorId) {
        return false;
    }

    const pageKey = resolveCurrentPageKey();
    const metadata = mergeMetadata(
        buildBaseMetadata(pageKey, state.lastTrackedUrl),
        asRecord(getRuntimeConfig().extraMetadata),
        asRecord(state.pageMetadata),
        asRecord(input.metadata),
    );

    const payload: TrackEventRequest = {
        event: input.event,
        visitorId,
        timestamp: new Date().toISOString(),
        title: input.title ?? document.title,
        url: input.url ?? resolveCurrentUrl(),
        ...(metadata ? { metadata } : {}),
        ...(typeof getRuntimeConfig().userId === 'number' ? { userId: Math.trunc(getRuntimeConfig().userId as number) } : {}),
        ...(input.referrer || document.referrer ? { referrer: input.referrer ?? document.referrer } : {}),
        ...(input.utm ?? buildUtmFromLocation(window.location) ? { utm: input.utm ?? buildUtmFromLocation(window.location) } : {}),
    };

    return dispatchTrackingEvent(payload, {
        ...resolveQueueOptions(),
        ...(options?.preferBeacon ? { preferBeacon: true } : {}),
        ...(options?.queueOnFailure === false ? { queueOnFailure: false } : {}),
    });
}

async function trackPageView(): Promise<boolean> {
    if (!isEnabled() || !ensureSampledIn() || getRuntimeConfig().trackPageViews === false) {
        return false;
    }

    const pageKey = resolveCurrentPageKey();
    if (state.lastPageKey === pageKey) {
        return false;
    }

    const previousUrl = state.lastTrackedUrl;
    state.lastPageKey = pageKey;
    state.trackedScrollDepths.clear();

    const success = await track({
        event: getRuntimeConfig().eventNames?.pageView ?? 'page_view',
        metadata: {
            previous_url: previousUrl ?? document.referrer ?? undefined,
        },
    });

    state.lastTrackedUrl = resolveCurrentUrl();
    return success;
}

function installNavigationTracking(): void {
    if (state.navigationInstalled) {
        return;
    }

    state.navigationInstalled = true;

    const dispatchNavigation = () => {
        window.setTimeout(() => {
            void trackPageView();
        }, 0);
    };

    const originalPushState = window.history.pushState.bind(window.history);
    const originalReplaceState = window.history.replaceState.bind(window.history);

    window.history.pushState = function pushState(...args: Parameters<History['pushState']>) {
        const result = originalPushState(...args);
        dispatchNavigation();
        return result;
    };

    window.history.replaceState = function replaceState(...args: Parameters<History['replaceState']>) {
        const result = originalReplaceState(...args);
        dispatchNavigation();
        return result;
    };

    window.addEventListener('popstate', dispatchNavigation);
}

function installListeners(): void {
    if (state.listenersInstalled) {
        return;
    }

    state.listenersInstalled = true;

    window.addEventListener('online', () => {
        void flushTrackingQueue();
    });

    document.addEventListener('click', (event: Event) => {
        if (!isEnabled() || !ensureSampledIn()) {
            return;
        }

        if (
            getRuntimeConfig().trackCustomClicks === false
            && getRuntimeConfig().trackOutboundClicks === false
            && getRuntimeConfig().trackFileDownloads === false
        ) {
            return;
        }

        const element = readClosestTrackableElement(event.target);
        if (!element) {
            return;
        }

        const customTarget = element.closest('[data-ominity-event]');
        if (customTarget instanceof HTMLElement && getRuntimeConfig().trackCustomClicks !== false) {
            const eventName = asNonEmptyString(customTarget.dataset.ominityEvent);
            if (eventName) {
                void track({
                    event: eventName,
                    title: asNonEmptyString(customTarget.dataset.ominityTitle) ?? getElementText(customTarget),
                    metadata: mergeMetadata({
                        element_id: customTarget.id || undefined,
                        element_name: customTarget.getAttribute('name') || undefined,
                        element_text: getElementText(customTarget),
                    }, parseDatasetMetadata(customTarget)),
                });
                return;
            }
        }

        const anchor = element.closest('a[href]');
        if (!(anchor instanceof HTMLAnchorElement)) {
            return;
        }

        let candidate: URL;
        try {
            candidate = new URL(anchor.href, window.location.href);
        } catch {
            return;
        }

        if (getRuntimeConfig().trackFileDownloads !== false && isDownloadUrl(anchor, candidate)) {
            void track({
                event: getRuntimeConfig().eventNames?.fileDownload ?? 'file_download',
                title: getElementText(anchor),
                url: candidate.toString(),
                metadata: {
                    href: candidate.toString(),
                    target: anchor.target || undefined,
                },
            }, { preferBeacon: true });
            return;
        }

        if (getRuntimeConfig().trackOutboundClicks !== false && isExternalUrl(candidate)) {
            void track({
                event: getRuntimeConfig().eventNames?.outboundClick ?? 'outbound_click',
                title: getElementText(anchor),
                url: candidate.toString(),
                metadata: {
                    href: candidate.toString(),
                    target: anchor.target || undefined,
                },
            }, { preferBeacon: true });
        }
    }, true);

    document.addEventListener('submit', (event: Event) => {
        if (!isEnabled() || !ensureSampledIn() || getRuntimeConfig().trackFormSubmissions === false) {
            return;
        }

        const form = event.target instanceof HTMLFormElement ? event.target : null;
        if (!form) {
            return;
        }

        void track({
            event: getRuntimeConfig().eventNames?.formSubmit ?? 'form_submit',
            title: asNonEmptyString(form.getAttribute('name')) ?? asNonEmptyString(form.id) ?? document.title,
            metadata: {
                form_id: form.id || undefined,
                form_name: form.getAttribute('name') || undefined,
                form_action: form.action || window.location.href,
                form_method: (form.method || 'get').toUpperCase(),
            },
        }, { preferBeacon: true });
    }, true);

    const handleScroll = () => {
        if (!isEnabled() || !ensureSampledIn() || getRuntimeConfig().trackScrollDepth === false) {
            return;
        }

        const thresholds = normalizeThresholds(getRuntimeConfig().scrollDepthThresholds);
        const documentHeight = document.documentElement.scrollHeight - window.innerHeight;
        const currentPercent = documentHeight <= 0
            ? 100
            : Math.min(100, Math.max(0, Math.round((window.scrollY / documentHeight) * 100)));

        for (const threshold of thresholds) {
            if (currentPercent < threshold || state.trackedScrollDepths.has(threshold)) {
                continue;
            }

            state.trackedScrollDepths.add(threshold);
            void track({
                event: getRuntimeConfig().eventNames?.scrollDepth ?? 'scroll_depth',
                metadata: {
                    depth_percent: threshold,
                },
            });
        }
    };

    window.addEventListener('scroll', handleScroll, { passive: true });
    window.addEventListener('resize', handleScroll);

    if (getRuntimeConfig().trackSessions !== false) {
        try {
            const sessionKey = asNonEmptyString(getRuntimeConfig().sessionKey) ?? DEFAULT_SESSION_KEY;
            if (window.sessionStorage.getItem(sessionKey) !== '1') {
                window.sessionStorage.setItem(sessionKey, '1');
                void track({
                    event: getRuntimeConfig().eventNames?.sessionStart ?? 'session_start',
                    metadata: {
                        session_key: sessionKey,
                    },
                });
            }
        } catch {
            void track({
                event: getRuntimeConfig().eventNames?.sessionStart ?? 'session_start',
            });
        }
    }
}

function runWhenReady(callback: () => void): void {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, { once: true });
        return;
    }

    callback();
}

const OminityTracking: OminityTrackingApi = {
    config: null,

    start(config?: TrackingBootstrapConfig): OminityTrackingApi {
        state.config = normalizeConfig(config);
        OminityTracking.config = state.config;

        if (state.config.pageMetadata) {
            state.pageMetadata = asRecord(state.config.pageMetadata) as TrackingEventMetadata;
        }

        if (!isEnabled()) {
            return OminityTracking;
        }

        runWhenReady(() => {
            ensureVisitorId();
            installNavigationTracking();
            installListeners();

            if (getRuntimeConfig().flushQueueOnMount !== false) {
                void flushTrackingQueue();
            }

            void trackPageView();
        });

        state.started = true;
        return OminityTracking;
    },

    async track(input: TrackEventInput, options?: { preferBeacon?: boolean; queueOnFailure?: boolean }): Promise<boolean> {
        return track(input, options);
    },

    async trackPageView(): Promise<boolean> {
        return trackPageView();
    },

    setPageMetadata(metadata: TrackingEventMetadata | null | undefined): OminityTrackingApi {
        state.pageMetadata = metadata ? asRecord(metadata) as TrackingEventMetadata : {};
        return OminityTracking;
    },

    mergePageMetadata(metadata: TrackingEventMetadata | null | undefined): OminityTrackingApi {
        if (!metadata) {
            return OminityTracking;
        }

        state.pageMetadata = {
            ...state.pageMetadata,
            ...asRecord(metadata),
        };

        return OminityTracking;
    },

    async flushQueue(): Promise<void> {
        await flushTrackingQueue();
    },

    ensureVisitorId(): string | null {
        return ensureVisitorId();
    },

    clearVisitorId(): void {
        state.visitorId = null;
        clearVisitorCookie();
    },
};

window.OminityTracking = OminityTracking;

if (window.__ominityTrackingConfig) {
    OminityTracking.start(window.__ominityTrackingConfig);
}

export default OminityTracking;
