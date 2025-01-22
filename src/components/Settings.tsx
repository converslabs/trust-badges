import { useState } from "react";
import { Card } from "./ui/card";
import { Label } from "./ui/label";
import { Input } from "./ui/input";
import { Button } from "./ui/button";
import { Switch } from "./ui/switch";
import { Checkbox } from "./ui/checkbox";
import { BadgeSelector } from "./BadgeSelector";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "./ui/select";
import { PlayIcon, AlignCenter, AlignLeft, AlignRight, CheckCircle, AlertCircle } from "lucide-react";
import { paymentBadges } from "./pages/assist/PaymentBadges";

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
	const [hasUnsavedChanges, setHasUnsavedChanges] = useState(false);

	const handleChange = (key: string, value: any) => {
		setSettings((prev) => ({ ...prev, [key]: value }));
		setHasUnsavedChanges(true);
	};

	const saveSettings = () => {
		const formData = new FormData();
		formData.append("action", "save_tx_badges_settings");
		formData.append("nonce", window.txBadgesSettings.nonce);
		formData.append("settings", JSON.stringify(settings));

		fetch(window.txBadgesSettings.ajaxUrl, {
			method: "POST",
			body: formData,
		})
			.then((response) => response.json())
			.then((data) => {
				if (data.success) {
					setHasUnsavedChanges(false);
				} else {
					console.error("Failed to save settings:", data.message);
				}
			})
			.catch((error) => {
				console.error("Error saving settings:", error);
			});
	};

	const handleSaveBadges = (selectedBadges: string[]) => {
		handleChange("selectedBadges", selectedBadges);
		setBadgeSelectorOpen(false);
	};

	const toggleAnimation = () => {
		setIsPlaying(!isPlaying);
		setTimeout(() => setIsPlaying(false), 2000);
	};

	// Helper function to get size classes
	type BadgeSize = "extra-small" | "small" | "medium" | "large";

	const getBadgeSize = (size: BadgeSize, isMobile = false) => {
		const sizes = {
			mobile: {
				"extra-small": "h-6 w-6",
				small: "h-8 w-8",
				medium: "h-10 w-10",
				large: "h-12 w-12",
			},
			desktop: {
				"extra-small": "h-8 w-8",
				small: "h-10 w-10",
				medium: "h-12 w-12",
				large: "h-16 w-16",
			},
		} as const;

		const sizeSet = isMobile ? sizes.mobile : sizes.desktop;
		return sizeSet[size] ?? sizeSet["medium"];
	};

	return (
		<div className="max-w-[1200px] mx-auto p-6">
			{/* Enable/Disable */}
			<div className="bg-background border rounded-lg p-4 mb-6 flex items-center gap-2">
				<div className="flex-1">
					<div className="flex items-center gap-2 text-lg">
						{settings.enabled ? <CheckCircle className="h-4 w-4 text-green-600" /> : <AlertCircle className="h-4 w-4 text-destructive" />}
						<span className="font-semibold">
							Ultimate Trust Badges is <span className={settings.enabled ? "text-green-600" : "text-destructive"}>{settings.enabled ? "enabled" : "disabled"}</span>
						</span>
					</div>
					<p className="ml-6 text-sm text-muted-foreground">Click Enable to add Ultimate Trust Badges to your store.</p>
				</div>
				<Button className="px-6" onClick={() => handleChange("enabled", !settings.enabled)}>
					{settings.enabled ? <span className="opacity-70">Disable</span> : "Enable"}
				</Button>
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
										<Input type="color" value={settings.textColor} onChange={(e) => handleChange("textColor", e.target.value)} className="w-6 h-6 p-0 border-0" />
										<span className="text-sm">{settings.textColor}</span>
									</div>
								</div>
							</div>
						</div>
					</Card>

					{/* Badges */}
					<Card className="p-6 shadow-sm">
						<h2 className="text-lg font-semibold border-b pb-2">Badge Settings</h2>
						<div className="space-y-5">
							<div className="flex flex-col gap-4 p-6">
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
												<div className={`w-20 h-12 rounded flex items-center justify-center ${style.id.includes("card") ? "bg-gray-400 shadow-sm py-1 px-2" : "p-1"}`}>
													<img src={`${window.txBadgesSettings.pluginUrl}assets/images/mastercard.svg`} alt="Badge Style Preview" className={`w-full h-full object-contain ${style.id.includes("mono") ? "grayscale" : ""}`} />
												</div>
												<span className="text-sm font-medium">{style.label}</span>
											</button>
										))}
									</div>
								</div>

								{/* Badge Size - Desktop */}
								<div className="space-y-2">
									<Label className="font-medium">Badge size desktop</Label>
									<Select value={settings.badgeSizeDesktop} onValueChange={(value) => handleChange("badgeSizeDesktop", value)}>
										<SelectTrigger className="w-full">
											<SelectValue placeholder="Select desktop size" />
										</SelectTrigger>
										<SelectContent>
											<SelectItem value="extra-small">Extra Small</SelectItem>
											<SelectItem value="small">Small</SelectItem>
											<SelectItem value="medium">Medium</SelectItem>
											<SelectItem value="large">Large</SelectItem>
										</SelectContent>
									</Select>
								</div>

								{/* Badge Size - Mobile */}
								<div className="space-y-2">
									<Label className="font-medium">Badge size mobile</Label>
									<Select value={settings.badgeSizeMobile} onValueChange={(value) => handleChange("badgeSizeMobile", value)}>
										<SelectTrigger className="w-full">
											<SelectValue placeholder="Select mobile size" />
										</SelectTrigger>
										<SelectContent>
											<SelectItem value="extra-small">Extra Small</SelectItem>
											<SelectItem value="small">Small</SelectItem>
											<SelectItem value="medium">Medium</SelectItem>
											<SelectItem value="large">Large</SelectItem>
										</SelectContent>
									</Select>
								</div>

								{/* Badge Color */}
								<div className="space-y-2">
									<Label className="font-medium">Badge color</Label>
									<div className="flex items-center gap-2 p-2 border w-[250px] rounded-md bg-white">
										<Input type="color" value={settings.badgeColor} onChange={(e) => handleChange("badgeColor", e.target.value)} className="w-6 h-6 p-0 border-0" />
										<span className="text-sm">{settings.badgeColor}</span>
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

				{/* Bar Preview */}
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
										className={`object-contain 
											${getBadgeSize(settings.badgeSizeDesktop as BadgeSize)} 
											md:${getBadgeSize(settings.badgeSizeDesktop as BadgeSize)} 
											${getBadgeSize(settings.badgeSizeMobile as BadgeSize, true)}
											${settings.badgeStyle === "card" || settings.badgeStyle === "mono-card" ? "px-2 bg-gray-400 rounded text-white" : ""}`}
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

			{/* Badge Selector Modal */}
			<BadgeSelector open={badgeSelectorOpen} onOpenChange={setBadgeSelectorOpen} badges={paymentBadges} initialSelected={settings.selectedBadges} onSave={handleSaveBadges} />

			{/* Sticky Save Button */}
			<div className="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 flex justify-end">
				<Button onClick={saveSettings} disabled={!hasUnsavedChanges} className={`${hasUnsavedChanges ? "bg-primary hover:bg-primary/90" : "bg-gray-200"}`}>
					{hasUnsavedChanges ? "Save Changes" : "All Changes Saved"}
				</Button>
			</div>
		</div>
	);
}
