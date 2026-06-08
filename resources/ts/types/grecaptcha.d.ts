interface GrecaptchaExecutor {
    execute(siteKey: string, options: { action: string }): Promise<string>;
    ready(callback: () => void): void;
}

declare const grecaptcha: GrecaptchaExecutor & {
    enterprise?: GrecaptchaExecutor;
};
