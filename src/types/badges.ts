export interface TrustBadgesSettings {
    showHeader: boolean;
    headerText: string;
    fontSize: string;
    alignment: "left" | "center" | "right";
    badgeAlignment: "left" | "center" | "right";
    position: "left" | "center" | "right";
    textColor: string;
    badgeStyle: "mono" | "original" | "mono-card" | "card";
    badgeSizeDesktop: BadgeSize;
    badgeSizeMobile: BadgeSize;
    badgeColor: string;
    customMargin: boolean;
    marginTop: string;
    marginBottom: string;
    marginLeft: string;
    marginRight: string;
    animation: "fade" | "slide" | "scale" | "bounce";
    showAfterAddToCart: boolean;
    showBeforeAddToCart: boolean;
    showOnCheckout: boolean;
    selectedBadges: string[];
}

export interface BadgeGroup {
    id: string;
    name: string;
    settings: TrustBadgesSettings;
    isDefault?: boolean;
    isActive?: boolean;
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