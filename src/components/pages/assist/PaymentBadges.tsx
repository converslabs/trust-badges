interface PaymentBadge {
    id: string;
    name: string;
    image: string;
}

// Define payment badges data
export const paymentBadges: PaymentBadge[] = [
    { id: "mastercard", name: "Mastercard", image: `${window.txBadgesSettings.pluginUrl}assets/images/mastercard.svg` },
    { id: "visa", name: "Visa", image: `${window.txBadgesSettings.pluginUrl}assets/images/visa.svg` },
    { id: "amex", name: "American Express", image: `${window.txBadgesSettings.pluginUrl}assets/images/american-express.svg` },
    { id: "apple-pay", name: "Apple Pay", image: `${window.txBadgesSettings.pluginUrl}assets/images/apple-pay.svg` },
    { id: "paypal", name: "PayPal", image: `${window.txBadgesSettings.pluginUrl}assets/images/paypal.svg` },
    { id: "google-pay", name: "Google Pay", image: `${window.txBadgesSettings.pluginUrl}assets/images/google-pay.svg` },
    { id: "stripe", name: "Stripe", image: `${window.txBadgesSettings.pluginUrl}assets/images/stripe.svg` },
    { id: "klarna", name: "Klarna", image: `${window.txBadgesSettings.pluginUrl}assets/images/klarna-logo-black.svg` },
    { id: "bitcoin", name: "Bitcoin", image: `${window.txBadgesSettings.pluginUrl}assets/images/bitcoin-logo.svg` },
    { id: "ethereum", name: "Ethereum", image: `${window.txBadgesSettings.pluginUrl}assets/images/ethereum.svg` },
    { id: "shopify", name: "Shopify Pay", image: `${window.txBadgesSettings.pluginUrl}assets/images/shopify.svg` },
    { id: "alipay", name: "Alipay", image: `${window.txBadgesSettings.pluginUrl}assets/images/alipay-logo.svg` },
];
