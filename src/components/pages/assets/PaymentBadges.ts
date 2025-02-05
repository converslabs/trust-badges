interface PaymentBadge {
    id: string;
    name: string;
    image: string;
}

export const paymentBadges: PaymentBadge[] = [
    {
        id: "mastercard",
        name: "Mastercard",
        image: `${window.txBadgesSettings?.pluginUrl || ''}assets/images/badges/mastercard_color.svg`
    },
    {
        id: "visa-1",
        name: "Visa",
        image: `${window.txBadgesSettings?.pluginUrl || ''}assets/images/badges/visa_color.svg`
    },
    {
        id: "paypal-1",
        name: "PayPal",
        image: `${window.txBadgesSettings?.pluginUrl || ''}assets/images/badges/paypal_color.svg`
    },
    {
        id: "apple-pay",
        name: "Apple Pay",
        image: `${window.txBadgesSettings?.pluginUrl || ''}assets/images/badges/apple_pay_color.svg`
    },
    {
        id: "stripe",
        name: "Stripe",
        image: `${window.txBadgesSettings?.pluginUrl || ''}assets/images/badges/stripe_color.svg`
    },
    {
        id: "american-express-1",
        name: "American Express",
        image: `${window.txBadgesSettings?.pluginUrl || ''}assets/images/badges/amex_color.svg`
    }
]; 