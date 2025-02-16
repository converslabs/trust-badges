export type TrustBadgesSettings = {
    showHeader: boolean;
    headerText: string;
    fontSize: string;
    alignment: string;
    badgeAlignment: string;
    position: string;
    textColor: string;
    badgeStyle: string;
    badgeSizeDesktop: string;
    badgeSizeMobile: string;
    badgeColor: string;
    customMargin: boolean;
    marginTop: string;
    marginBottom: string;
    marginLeft: string;
    marginRight: string;
    animation: string;
    selectedBadges: string[];
    woocommerce?: boolean;
    edd?: boolean;
    // Checkout specific settings
    checkoutBeforeOrderReview?: boolean;
    eddCheckoutBeforePurchaseForm?: boolean;
    // Product page specific settings
    showAfterAddToCart?: boolean;
    eddPurchaseLinkEnd?: boolean;
};

export interface BadgeGroup {
    id: string;
    name: string;
    settings: TrustBadgesSettings;
    isDefault: boolean;
    isActive: boolean;
    requiredPlugin?: 'woocommerce' | 'edd';
}

export interface ApiResponse<T> {
    success: boolean;
    data?: T;
    error?: {
        code: string;
        message: string;
        status: number;
    };
}

export interface ApiError {
    code: string;
    message: string;
    status: number;
}

export type BadgeSize = "extra-small" | "small" | "medium" | "large"; 