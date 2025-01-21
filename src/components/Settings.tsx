import { useState } from "react";
import { Card } from "./ui/card";
import { Label } from "./ui/label";
import { Input } from "./ui/input";
import { Button } from "./ui/button";
import { Switch } from "./ui/switch";
import { Checkbox } from "./ui/checkbox";
import { BadgeSelector } from "./BadgeSelector";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "./ui/select";
import { PlayIcon, AlignCenter, AlignLeft, AlignRight, Copy, Check } from "lucide-react";
import { ColorPicker } from "./ui/color-picker";

declare global {
	interface Window {
		txBadgesSettings: {
			pluginUrl: string;
			ajaxUrl: string;
			nonce: string;
			restUrl: string;
			mediaTitle: string;
			mediaButton: string;
		};
	}
}

// Define payment badges data
const paymentBadges = [
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

export function Settings() {
	const [settings, setSettings] = useState({
		enabled: false,
		showHeader: true,
		headerText: "Secure Checkout With",
		font: "Asap",
		fontSize: "18",
		alignment: "center",
		textColor: "#000000",
		badgeStyle: "original",
		badgeSizeDesktop: "medium",
		badgeSizeMobile: "small",
		badgeColor: "#0066FF",
		customMargin: false,
		marginTop: "0",
		marginBottom: "0",
		animation: "fade",
		showOnProductPage: true,
		selectedBadges: ["stripe", "shopify", "paypal", "apple-pay"],
	});

	const [isPlaying, setIsPlaying] = useState(false);
	const [badgeSelectorOpen, setBadgeSelectorOpen] = useState(false);
	const [showCopied, setShowCopied] = useState(false);

	const handleChange = (key: string, value: any) => {
		setSettings((prev) => ({ ...prev, [key]: value }));
	};

	const handleSaveBadges = (selectedBadges: string[]) => {
		handleChange("selectedBadges", selectedBadges);
		setBadgeSelectorOpen(false);
	};

	const toggleAnimation = () => {
		setIsPlaying(!isPlaying);
		setTimeout(() => setIsPlaying(false), 2000);
	};

	const handleCopyColor = () => {
		navigator.clipboard.writeText(settings.textColor);
		setShowCopied(true);
		setTimeout(() => setShowCopied(false), 3000); // Hide after 3 seconds
	};

	return (
		<div className="max-w-[1200px] mx-auto p-6">
			{/* Enable/Disable */}
			<div className="bg-background border rounded-lg p-4 mb-6 flex items-center gap-2">
				<div className="flex-1">
					<div className="flex items-center gap-2">
						<div className="h-2 w-2 rounded-full bg-destructive"></div>
						<span className="font-medium">Ultimate Trust Badges is disabled</span>
					</div>
					<p className="text-sm text-muted-foreground">Click Enable to add Ultimate Trust Badges to your store.</p>
				</div>
				<Button onClick={() => handleChange("enabled", !settings.enabled)}>Enable</Button>
			</div>

			{/* Main Settings */}
			<div className="flex gap-8">
				<div className="flex-1 space-y-6">
					{/* Header */}
					<Card className="p-6 shadow-sm">
						<h2 className="text-lg font-semibold border-b pb-2">Header Settings</h2>
						<div className="space-y-5">
							<div className="flex flex-col gap-4 p-6">
								{/* Show header */}
								<div className="flex items-center justify-between">
									<Label className="font-medium">Show header</Label>
									<Switch checked={settings.showHeader} onCheckedChange={(checked) => handleChange("showHeader", checked)} />
								</div>

								{/* Header text input */}
								<div className="space-y-2">
									<Label className="font-medium">Header text</Label>
									<Input value={settings.headerText} onChange={(e) => handleChange("headerText", e.target.value)} />
								</div>

								{/* Fonts */}
								<div className="space-y-2">
									<Label className="font-medium">Fonts</Label>
									<Select value={settings.font} onValueChange={(value) => handleChange("font", value)}>
										<SelectTrigger className="w-full">
											<SelectValue placeholder="Select Font" />
										</SelectTrigger>
										<SelectContent>
											<SelectItem value="Asap">Asap</SelectItem>
											<SelectItem value="Arial">Arial</SelectItem>
											<SelectItem value="Helvetica">Helvetica</SelectItem>
										</SelectContent>
									</Select>
								</div>

								{/* Font Size */}
								<div className="space-y-2">
									<Label className="font-medium">Font Size (px)</Label>
									<div>
										<Input type="number" value={settings.fontSize} onChange={(e) => handleChange("fontSize", e.target.value)} className="w-[150px]" />
									</div>
								</div>

								{/* Alignment */}
								<div className="space-y-2">
									<Label className="font-medium">Alignment</Label>
									<div className="flex gap-2 border rounded-md p-1 w-[150px]">
										<Button variant={settings.alignment === "left" ? "default" : "ghost"} size="sm" onClick={() => handleChange("alignment", "left")} className="h-8 w-10">
											<AlignLeft />
										</Button>
										<Button variant={settings.alignment === "center" ? "default" : "ghost"} size="sm" onClick={() => handleChange("alignment", "center")} className="h-8 w-10">
											<AlignCenter />
										</Button>
										<Button variant={settings.alignment === "right" ? "default" : "ghost"} size="sm" onClick={() => handleChange("alignment", "right")} className="h-8 w-10">
											<AlignRight />
										</Button>
									</div>
								</div>

								{/* Text Color */}
								<div className="space-y-2">
									<Label className="font-medium">Text Color</Label>
									<div className="flex items-center gap-2 p-2 border w-[250px] rounded-md bg-white">
										<div className="flex items-center gap-2 flex-1">
											<Input type="color" value={settings.textColor} onChange={(e) => handleChange("textColor", e.target.value)} className="w-6 h-6 p-0 border-0 mr-2" />
											<span className="text-sm">{settings.textColor}</span>
										</div>
										<div className="flex items-center gap-2">
											{showCopied && <span className="text-xs text-green-600">Color copied</span>}
											<Button variant="default" size="icon" className="h-6 w-6" onClick={handleCopyColor}>
												{showCopied ? <Check className="h-4 w-4 text-white" /> : <Copy className="h-4 w-4" />}
											</Button>
										</div>
									</div>
								</div>
							</div>
						</div>
					</Card>

					{/* Badges */}
					<Card className="p-6 shadow-sm">
						<h2 className="text-lg font-semibold mb-6 border-b pb-2">Badge Settings</h2>
						<div className="space-y-6">
							{/* Badge Style */}
							<div className="space-y-2">
								<Label className="font-medium">Badge style</Label>
								<div className="grid grid-cols-2 gap-4">
									{[
										{ id: "mono", label: "Mono" },
										{ id: "original", label: "Original" },
										{ id: "mono-card", label: "Mono Card" },
										{ id: "card", label: "Card" },
									].map((style) => (
										<button
											key={style.id}
											onClick={() => handleChange("badgeStyle", style.id)}
											className={`border rounded-lg p-4 flex flex-col items-center gap-2 transition-colors ${settings.badgeStyle === style.id ? "border-primary bg-primary/5" : "border-input hover:border-primary/50"}`}>
											<div className={`w-12 h-8 rounded ${style.id.includes("mono") ? "bg-gray-400" : "bg-blue-500"} ${style.id.includes("card") ? "shadow-sm" : ""}`} />
											<span className="text-sm font-medium">{style.label}</span>
										</button>
									))}
								</div>
							</div>

							<div className="space-y-2">
								<Label className="font-medium">Badge size mobile</Label>
								<div className="space-y-1">
									<select value={settings.badgeSizeMobile} onChange={(e) => handleChange("badgeSizeMobile", e.target.value)} className="w-full rounded-md border border-input bg-background px-3 py-2">
										<option value="small">Small</option>
										<option value="medium">Medium</option>
										<option value="large">Large</option>
									</select>
									<p className="text-sm text-muted-foreground">This setting will only appear in your live store</p>
								</div>
							</div>

							<div className="space-y-2">
								<Label className="font-medium">Badge color</Label>
								<div className="flex items-center gap-2">
									<ColorPicker value={settings.badgeColor} onChange={(value) => handleChange("badgeColor", value)} />
								</div>
							</div>

							<div className="space-y-4">
								<div className="flex items-center gap-2">
									<Checkbox id="custom-margin" checked={settings.customMargin} onCheckedChange={(checked) => handleChange("customMargin", checked)} />
									<Label htmlFor="custom-margin" className="font-medium">
										Custom Margin
									</Label>
								</div>

								{settings.customMargin && (
									<div className="space-y-4 pl-6">
										<div className="space-y-2">
											<Label>Top</Label>
											<div className="flex items-center gap-2">
												<Input type="number" value={settings.marginTop} onChange={(e) => handleChange("marginTop", e.target.value)} className="w-20" />
												<span className="text-muted-foreground">px</span>
											</div>
										</div>
										<div className="space-y-2">
											<Label>Bottom</Label>
											<div className="flex items-center gap-2">
												<Input type="number" value={settings.marginBottom} onChange={(e) => handleChange("marginBottom", e.target.value)} className="w-20" />
												<span className="text-muted-foreground">px</span>
											</div>
										</div>
										<p className="text-sm text-muted-foreground">This setting will only appear in your live store</p>
									</div>
								)}
							</div>
						</div>
					</Card>

					<Card className="p-6 shadow-sm">
						<h2 className="text-lg font-semibold mb-6 border-b pb-2">Animation</h2>
						<div className="space-y-6">
							<div className="space-y-2">
								<div className="flex items-center justify-between">
									<Label className="font-medium">Animation</Label>
									<Button variant="outline" size="sm" onClick={toggleAnimation} disabled={isPlaying}>
										<PlayIcon className="h-4 w-4 mr-2" />
										{isPlaying ? "Playing..." : "Play animation"}
									</Button>
								</div>
								<select value={settings.animation} onChange={(e) => handleChange("animation", e.target.value)} className="w-full rounded-md border border-input bg-background px-3 py-2">
									<option value="groove">Groove</option>
									<option value="fade">Fade</option>
									<option value="slide">Slide</option>
									<option value="bounce">Bounce</option>
								</select>
							</div>
						</div>
					</Card>

					<Card className="p-6 shadow-sm">
						<h2 className="text-lg font-semibold mb-6 border-b pb-2">Bar Placement</h2>
						<div className="space-y-6">
							<div className="flex items-center gap-2">
								<Checkbox id="show-product-page" checked={settings.showOnProductPage} onCheckedChange={(checked) => handleChange("showOnProductPage", checked)} />
								<Label htmlFor="show-product-page" className="font-medium">
									Product page
								</Label>
							</div>

							<div className="space-y-2">
								<Label className="font-medium">To display the bar in a custom location place the following code inside the template file.</Label>
								<div className="relative">
									<div className="rounded-md border bg-muted px-3 py-2 font-mono text-sm">{'<div class="ultimate-badges"></div>'}</div>
									<Button
										variant="secondary"
										size="sm"
										className="absolute right-2 top-1.5"
										onClick={() => {
											navigator.clipboard.writeText('<div class="ultimate-badges"></div>');
										}}>
										Copy
									</Button>
								</div>
							</div>

							<div className="flex items-center gap-2">
								<Button variant="link" className="h-auto p-0 text-sm text-blue-600" asChild>
									<a href="#">Step by Step Guide</a>
								</Button>
							</div>
						</div>
					</Card>
				</div>

				<Card className="w-[400px] sticky top-6 self-start">
					<div className="p-6 border-b">
						<h2 className="text-lg font-semibold">Bar Preview</h2>
					</div>
					<div
						className="p-6 border rounded-lg space-y-4"
						style={{
							fontFamily: settings.font,
							fontSize: `${settings.fontSize}px`,
							textAlign: settings.alignment as any,
							color: settings.textColor,
							marginTop: settings.customMargin ? `${settings.marginTop}px` : undefined,
							marginBottom: settings.customMargin ? `${settings.marginBottom}px` : undefined,
						}}>
						{settings.showHeader && settings.headerText}
						<div className={`grid grid-cols-4 gap-4 mt-2 ${isPlaying ? "animate-" + settings.animation : ""}`}>
							{settings.selectedBadges.map((badgeId) => {
								const badge = paymentBadges.find((b) => b.id === badgeId);
								return badge ? (
									<img
										key={badgeId}
										src={badge.image}
										alt={badge.name}
										className={`h-8 w-auto object-contain ${settings.badgeStyle === "card" || settings.badgeStyle === "mono-card" ? "shadow-sm" : ""}`}
										style={{
											filter: settings.badgeStyle === "mono" || settings.badgeStyle === "mono-card" ? "grayscale(100%)" : "none",
										}}
									/>
								) : null;
							})}
						</div>
					</div>
					<div className="p-6 pt-0">
						<Button className="w-full" onClick={() => setBadgeSelectorOpen(true)}>
							Select Badges
						</Button>
					</div>
				</Card>
			</div>

			<BadgeSelector
				open={badgeSelectorOpen}
				onOpenChange={setBadgeSelectorOpen}
				badges={paymentBadges}
				initialSelected={settings.selectedBadges}
				onSave={(selectedBadges) => {
					setSettings((prev) => ({
						...prev,
						selectedBadges,
					}));
				}}
			/>
		</div>
	);
}
