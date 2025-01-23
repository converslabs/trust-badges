interface PaymentBadge {
	id: string;
	name: string;
	image: string;
}

// Define payment badges data
export const paymentBadges: PaymentBadge[] = [
	{ id: "mastercard", name: "Mastercard", image: `${window.txBadgesSettings.pluginUrl}assets/images/mastercard_color.svg` },
	{ id: "visa", name: "Visa", image: `${window.txBadgesSettings.pluginUrl}assets/images/visa_1_color.svg` },
	{ id: "amex-1", name: "American Express", image: `${window.txBadgesSettings.pluginUrl}assets/images/americanexpress_1_color.svg` },
	{ id: "amex-2", name: "American Express (Alt)", image: `${window.txBadgesSettings.pluginUrl}assets/images/americanexpress_2_color.svg` },
	{ id: "amex-3", name: "American Express (Simple)", image: `${window.txBadgesSettings.pluginUrl}assets/images/amex_color.svg` },
	{ id: "apple-pay-1", name: "Apple Pay", image: `${window.txBadgesSettings.pluginUrl}assets/images/applepay_color.svg` },
	{ id: "apple-pay-2", name: "Apple Pay (Alt)", image: `${window.txBadgesSettings.pluginUrl}assets/images/applepay2_color.svg` },
	{ id: "shop-pay-1", name: "Shop Pay", image: `${window.txBadgesSettings.pluginUrl}assets/images/shoppay_color.svg` },
	{ id: "shop-pay-2", name: "Shop Pay (Alt 1)", image: `${window.txBadgesSettings.pluginUrl}assets/images/shoppay2_color.svg` },
	{ id: "shop-pay-3", name: "Shop Pay (Alt 2)", image: `${window.txBadgesSettings.pluginUrl}assets/images/shoppay3_color.svg` },
	{ id: "amazon-pay-1", name: "Amazon Pay", image: `${window.txBadgesSettings.pluginUrl}assets/images/amazonpay_1_color.svg` },
	{ id: "amazon-pay-2", name: "Amazon Pay (Alt)", image: `${window.txBadgesSettings.pluginUrl}assets/images/amazonpay_2_color.svg` },
	{ id: "afterpay", name: "Afterpay", image: `${window.txBadgesSettings.pluginUrl}assets/images/afterpay_color.svg` },
	{ id: "alipay", name: "Alipay", image: `${window.txBadgesSettings.pluginUrl}assets/images/alipay_color.svg` },
	{ id: "aramex", name: "Aramex", image: `${window.txBadgesSettings.pluginUrl}assets/images/aramex_color.svg` },
	{ id: "bancontact-1", name: "Bancontact", image: `${window.txBadgesSettings.pluginUrl}assets/images/bancontact_color.svg` },
	{ id: "bancontact-2", name: "Bancontact (Alt)", image: `${window.txBadgesSettings.pluginUrl}assets/images/bancontact2_color.svg` },
	{ id: "bance-intesa", name: "Banca Intesa", image: `${window.txBadgesSettings.pluginUrl}assets/images/banceintesa_color.svg` },
	{ id: "banco-bradesco", name: "Banco Bradesco", image: `${window.txBadgesSettings.pluginUrl}assets/images/bancobradesco_color.svg` },
	{ id: "banco-colombia", name: "Banco Colombia", image: `${window.txBadgesSettings.pluginUrl}assets/images/bancocolombia_color.svg` },
	{ id: "banco-bogota", name: "Banco de Bogota", image: `${window.txBadgesSettings.pluginUrl}assets/images/bancodebogota_color.svg` },
	{ id: "banco-brasil", name: "Banco do Brasil", image: `${window.txBadgesSettings.pluginUrl}assets/images/bancodobrasil_color.svg` },
	{ id: "banco-estado", name: "Banco Estado", image: `${window.txBadgesSettings.pluginUrl}assets/images/bancoestado_color.svg` },
	{ id: "banco-general", name: "Banco General", image: `${window.txBadgesSettings.pluginUrl}assets/images/bancogeneral_color.svg` },
];
