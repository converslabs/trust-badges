interface TxBadgesSettings {
    pluginUrl: string;
    ajaxUrl: string;
    nonce: string;
    restNonce: string;
    restUrl: string;
    mediaTitle: string;
    mediaButton: string;
    debug: boolean;
}

declare global {
    interface Window {
        txBadgesSettings: TxBadgesSettings;
    }
}

export {};
