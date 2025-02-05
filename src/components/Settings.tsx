import { useState, useEffect } from "react";
import { Card } from "./ui/card";
import { Label } from "./ui/label";
import { Input } from "./ui/input";
import { Button } from "./ui/button";
import { Switch } from "./ui/switch";
import { Checkbox } from "./ui/checkbox";
import { BadgeSelector } from "./BadgeSelector";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "./ui/select";
import { PlayIcon, AlignCenter, AlignLeft, AlignRight, CheckCircle, Copy, HelpCircle, PlayCircle } from "lucide-react";
import { paymentBadges } from "./pages/assets/PaymentBadges";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "./ui/tooltip";
import { motion, AnimatePresence } from "framer-motion";
import { useToast } from "./ui/use-toast";
import { TrustBadgesSettings, BadgeSize } from "../types/settings";

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

// Add these utility functions at the top of the file
const toCamelCase = (str: string) => {
	return str.replace(/([-_][a-z])/g, (group) =>
		group.toUpperCase().replace('-', '').replace('_', '')
	);
};

const toSnakeCase = (str: string) => {
	return str.replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`);
};

const convertKeysToSnakeCase = (obj: any): any => {
	if (typeof obj !== 'object' || obj === null) return obj;
	
	if (Array.isArray(obj)) {
		return obj.map(convertKeysToSnakeCase);
	}

	return Object.keys(obj).reduce((acc, key) => {
		const snakeKey = toSnakeCase(key);
		acc[snakeKey] = convertKeysToSnakeCase(obj[key]);
		return acc;
	}, {} as any);
};

const convertKeysToCamelCase = (obj: any): any => {
	if (typeof obj !== 'object' || obj === null) return obj;
	
	if (Array.isArray(obj)) {
		return obj.map(convertKeysToCamelCase);
	}

	return Object.keys(obj).reduce((acc, key) => {
		const camelKey = toCamelCase(key);
		acc[camelKey] = convertKeysToCamelCase(obj[key]);
		return acc;
	}, {} as any);
};

export function Settings() {
	const [settings, setSettings] = useState<TrustBadgesSettings>({
		showHeader: true,
		headerText: "Secure Checkout With",
		fontSize: "18",
		alignment: "center",
		badgeAlignment: "center",
		textColor: "#000000",
		badgeStyle: "original",
		badgeSizeDesktop: "medium",
		badgeSizeMobile: "small",
		badgeColor: "#0066FF",
		customMargin: false,
		marginTop: "0",
		marginBottom: "0",
		marginLeft: "0",
		marginRight: "0",
		animation: "fade",
		showOnProductPage: true,
		selectedBadges: ["mastercard", "visa-1", "paypal-1", "apple-pay", "stripe", "american-express-1"],
	});

	const [isPlaying, setIsPlaying] = useState(false);
	const [badgeSelectorOpen, setBadgeSelectorOpen] = useState(false);
	const [hasUnsavedChanges, setHasUnsavedChanges] = useState(false);
	const [showCopied, setShowCopied] = useState(false);
	const [isLoading, setIsLoading] = useState(true);

	const { toast } = useToast();

	useEffect(() => {
		const loadSettings = async () => {
			try {
				if (!window.txBadgesSettings?.ajaxUrl) {
					console.warn('txBadgesSettings not initialized');
					setIsLoading(false);
					return;
				}

				const formData = new FormData();
				formData.append("action", "tx_badges_get_settings");
				formData.append("nonce", window.txBadgesSettings.nonce || '');

				const response = await fetch(window.txBadgesSettings.ajaxUrl, {
					method: "POST",
					credentials: "same-origin",
					body: formData,
				});

				if (!response.ok) {
					throw new Error(`HTTP error! status: ${response.status}`);
				}

				const result = await response.json();

				if (!result.success) {
					throw new Error(result.data?.message || "Failed to load settings");
				}

				if (!result.data || typeof result.data !== "object") {
					throw new Error("Invalid settings data received");
				}

				// Convert snake_case to camelCase
				const camelCaseSettings = convertKeysToCamelCase(result.data);

				setSettings((prev) => ({
					...prev,
					...camelCaseSettings,
					selectedBadges: Array.isArray(camelCaseSettings.selectedBadges) 
						? camelCaseSettings.selectedBadges 
						: prev.selectedBadges,
				}));
			} catch (error) {
				console.error("Error loading settings:", error);
				toast({
					title: "Error",
					description: error instanceof Error ? error.message : "Failed to load settings",
					variant: "destructive",
					duration: 5000,
				});
			} finally {
				setIsLoading(false);
			}
		};

		loadSettings();
	}, [toast]);

	const handleChange = (key: string, value: any) => {
		setSettings((prev) => ({ ...prev, [key]: value }));
		setHasUnsavedChanges(true);
	};

	const saveSettings = async () => {
		try {
			setIsLoading(true);

			if (!window.txBadgesSettings?.ajaxUrl) {
				throw new Error('txBadgesSettings not initialized');
			}

			const formData = new FormData();
			formData.append("action", "tx_badges_save_settings");
			formData.append("nonce", window.txBadgesSettings.nonce || '');

			// Convert settings to snake_case before saving
			const snakeCaseSettings = convertKeysToSnakeCase(settings);
			formData.append("settings", JSON.stringify(snakeCaseSettings));

			const response = await fetch(window.txBadgesSettings.ajaxUrl, {
				method: "POST",
				credentials: "same-origin",
				body: formData,
			});

			if (!response.ok) {
				throw new Error(`HTTP error! status: ${response.status}`);
			}

			const result = await response.json();

			if (!result.success) {
				throw new Error(result.data?.message || "Failed to save settings");
			}

			setHasUnsavedChanges(false);
			toast({
				title: "Success",
				description: "Settings saved successfully",
				duration: 2000,
			});
		} catch (error) {
			console.error("Error saving settings:", error);
			toast({
				title: "Error",
				description: error instanceof Error ? error.message : "Failed to save settings",
				variant: "destructive",
				duration: 3000,
			});
		} finally {
			setIsLoading(false);
		}
	};

	const handleSaveBadges = (selectedBadges: string[]) => {
		handleChange("selectedBadges", selectedBadges);
		setBadgeSelectorOpen(false);
	};

	const toggleAnimation = () => {
		setIsPlaying(true);
		setTimeout(() => setIsPlaying(false), 400); // 400ms = 0.4 seconds
	};

	const copyToClipboard = async (text: string) => {
		try {
			if (navigator.clipboard && window.isSecureContext) {
				await navigator.clipboard.writeText(text);
			} else {
				// Fallback for older browsers
				const textArea = document.createElement("textarea");
				textArea.value = text;
				textArea.style.position = "fixed";
				textArea.style.left = "-999999px";
				textArea.style.top = "-999999px";
				document.body.appendChild(textArea);
				textArea.focus();
				textArea.select();
				try {
					document.execCommand("copy");
					textArea.remove();
				} catch (err) {
					console.error("Failed to copy text:", err);
				}
			}
			setShowCopied(true);
			setTimeout(() => setShowCopied(false), 2000);
		} catch (err) {
			console.error("Failed to copy text:", err);
		}
	};

	// Helper function to get size classes
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
		<div>
			{/* Main Settings */}
			<div className="flex gap-8">
				<div className="flex-1 space-y-6">
					{/* Header */}
					<Card className="p-6 shadow-sm">
						<div className="flex items-center justify-between border-b">
							<h2 className="text-lg font-semibold pb-2">Header Settings</h2>
							<Switch checked={settings.showHeader} onCheckedChange={(checked) => handleChange("showHeader", checked)} />
						</div>
						<div className="space-y-5">
							<div className="flex flex-col gap-4 p-6">
								{/* Show header */}
								{/* <div className="flex items-center justify-between">
                                    <Label className="font-medium">Show header</Label>
                                    <Switch checked={settings.showHeader} onCheckedChange={(checked) => handleChange("showHeader", checked)} />
                                </div> */}

								{/* Header text input */}
								<div className="space-y-2">
									<Label className={`font-medium ${!settings.showHeader ? "opacity-50" : ""}`}>Header text</Label>
									<Input value={settings.headerText} onChange={(e) => handleChange("headerText", e.target.value)} disabled={!settings.showHeader} className={!settings.showHeader ? "opacity-50 cursor-not-allowed" : ""} />
								</div>

								{/* Font Size */}
								<div className="space-y-2">
									<Label className={`font-medium ${!settings.showHeader ? "opacity-50" : ""}`}>Font Size (px)</Label>
									<div>
										<Input
											type="number"
											value={settings.fontSize}
											onChange={(e) => handleChange("fontSize", e.target.value)}
											className={`w-[150px] ${!settings.showHeader ? "opacity-50 cursor-not-allowed" : ""}`}
											disabled={!settings.showHeader}
										/>
									</div>
								</div>

								{/* Alignment */}
								<div className="space-y-2">
									<Label className={`font-medium ${!settings.showHeader ? "opacity-50" : ""}`}>Alignment</Label>
									<div className={`flex gap-2 border rounded-md p-1 w-[150px] ${!settings.showHeader ? "opacity-50" : ""}`}>
										<Button variant={settings.alignment === "left" ? "default" : "ghost"} size="sm" onClick={() => handleChange("alignment", "left")} className="h-8 w-10" disabled={!settings.showHeader}>
											<AlignLeft />
										</Button>
										<Button variant={settings.alignment === "center" ? "default" : "ghost"} size="sm" onClick={() => handleChange("alignment", "center")} className="h-8 w-10" disabled={!settings.showHeader}>
											<AlignCenter />
										</Button>
										<Button variant={settings.alignment === "right" ? "default" : "ghost"} size="sm" onClick={() => handleChange("alignment", "right")} className="h-8 w-10" disabled={!settings.showHeader}>
											<AlignRight />
										</Button>
									</div>
								</div>

								{/* Text Color */}
								<div className="space-y-2">
									<Label className={`font-medium ${!settings.showHeader ? "opacity-50" : ""}`}>Text Color</Label>
									<div className={`flex items-center p-2 border w-[50px] h-[50px] rounded-md bg-white ${!settings.showHeader ? "opacity-50" : ""}`}>
										<Input type="color" value={settings.textColor} onChange={(e) => handleChange("textColor", e.target.value)} className="w-11 h-8 p-0 border-0" disabled={!settings.showHeader} />
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
													<img
														src={`${window.txBadgesSettings.pluginUrl}assets/images/badges/mastercard_color.svg`}
														alt="Badge Style Preview"
														className={`w-full h-full object-contain ${style.id.includes("mono") ? "grayscale" : ""}`}
													/>
												</div>
												<span className="text-sm font-medium">{style.label}</span>
											</button>
										))}
									</div>
								</div>

								{/* Badge Alignment */}
								<div className="space-y-2">
									<Label className="font-medium">Badges Alignment</Label>
									<div className="flex gap-2 border rounded-md p-1 w-[150px]">
										<Button variant={settings.badgeAlignment === "left" ? "default" : "ghost"} size="sm" onClick={() => handleChange("badgeAlignment", "left")} className="h-8 w-10">
											<AlignLeft />
										</Button>
										<Button variant={settings.badgeAlignment === "center" ? "default" : "ghost"} size="sm" onClick={() => handleChange("badgeAlignment", "center")} className="h-8 w-10">
											<AlignCenter />
										</Button>
										<Button variant={settings.badgeAlignment === "right" ? "default" : "ghost"} size="sm" onClick={() => handleChange("badgeAlignment", "right")} className="h-8 w-10">
											<AlignRight />
										</Button>
									</div>
								</div>

								{/* Badge Size - Desktop  --  NEED TO FIX ISSUE */}
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

								{/* Badge Size - Mobile  --  NEED TO FIX ISSUE */}
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

								{/* Badge Color  --  NOT WORKING */}
								<div className="space-y-2">
									<Label className="font-medium">Badge color</Label>
									<div className="flex items-center p-2 border w-[50px] h-[50px] rounded-md bg-white">
										<Input type="color" value={settings.badgeColor} onChange={(e) => handleChange("badgeColor", e.target.value)} className="w-11 h-8 p-0 border-0" />
									</div>
								</div>

								{/* Custom Margin */}
								<div className="space-y-4">
									<div>
										<div className="flex items-center justify-between">
											<Label className="font-medium">Custom Margin</Label>
											<Switch checked={settings.customMargin} onCheckedChange={(checked) => handleChange("customMargin", checked)} />
										</div>
										{/* <p className="mt-2 text-sm text-muted-foreground">This setting will only appear in your live store</p> */}
									</div>

									{settings.customMargin && (
										<div className="space-y-4">
											<div className="flex gap-6">
												<div>
													<Label className="text-sm">Top</Label>
													<div className="flex items-center gap-3 mt-1">
														<Input type="number" value={settings.marginTop} onChange={(e) => handleChange("marginTop", e.target.value)} className="w-25" />
														<span className="text-muted-foreground">px</span>
													</div>
												</div>
												<div>
													<Label className="text-sm">Bottom</Label>
													<div className="flex items-center gap-3 mt-1">
														<Input type="number" value={settings.marginBottom} onChange={(e) => handleChange("marginBottom", e.target.value)} className="w-25" />
														<span className="text-muted-foreground">px</span>
													</div>
												</div>
											</div>
											<div className="flex gap-6">
												<div>
													<Label className="text-sm">Left</Label>
													<div className="flex items-center gap-3 mt-1">
														<Input type="number" value={settings.marginLeft} onChange={(e) => handleChange("marginLeft", e.target.value)} className="w-25" />
														<span className="text-muted-foreground">px</span>
													</div>
												</div>
												<div>
													<Label className="text-sm">Right</Label>
													<div className="flex items-center gap-3 mt-1">
														<Input type="number" value={settings.marginRight} onChange={(e) => handleChange("marginRight", e.target.value)} className="w-25" />
														<span className="text-muted-foreground">px</span>
													</div>
												</div>
											</div>
										</div>
									)}
								</div>
							</div>
						</div>
					</Card>

					{/* Animation */}
					<Card className="p-6 shadow-sm">
						<h2 className="text-lg font-semibold mb-6 border-b pb-2">Animation</h2>
						<div className="space-y-6">
							<div className="space-y-2 px-6">
								<div className="flex items-center justify-between">
									<Label className="font-medium">Animation</Label>
									<Button variant="outline" size="sm" onClick={toggleAnimation} disabled={isPlaying}>
										<PlayIcon className="h-4 w-4 mr-2" />
										{isPlaying ? "Playing..." : "Play animation"}
									</Button>
								</div>
								<Select value={settings.animation} onValueChange={(value) => handleChange("animation", value)}>
									<SelectTrigger className="w-full">
										<SelectValue placeholder="Select animation" />
									</SelectTrigger>
									<SelectContent>
										<SelectItem value="fade">Fade</SelectItem>
										<SelectItem value="slide">Slide</SelectItem>
										<SelectItem value="scale">Scale</SelectItem>
										<SelectItem value="bounce">Bounce</SelectItem>
									</SelectContent>
								</Select>
							</div>
						</div>
					</Card>

					{/* Bar Placement */}
					<Card className="p-6 shadow-sm mb-6">
						<div className="px-6">
							<h2 className="text-lg font-semibold mb-6 border-b pb-2">Bar Placement</h2>
							<div className="space-y-6">
								<div className="space-y-4">
									<div>
										<h4 className="text-sm font-medium mb-2">Show bar on</h4>
										<div className="flex items-center gap-2">
											<Checkbox id="show-product-page" checked={settings.showOnProductPage} onCheckedChange={(checked) => handleChange("showOnProductPage", checked)} />
											<Label htmlFor="show-product-page" className="text-sm">
												Product page
											</Label>
											<div className="flex-1 flex justify-end">
												<TooltipProvider>
													<Tooltip>
														<TooltipTrigger asChild>
															<Button variant="ghost" size="icon" className="h-5 w-5">
																<HelpCircle className="h-5 w-5 text-black" />
															</Button>
														</TooltipTrigger>
														<TooltipContent>
															<p className="w-[150px]">The bar would show below the "Add to Cart" button.</p>
														</TooltipContent>
													</Tooltip>
												</TooltipProvider>
											</div>
										</div>
									</div>

									<div className="space-y-2">
										<p className="text-sm">
											To display the bar in a custom location place the following code inside the{" "}
											<a href="#" className="text-blue-600 hover:underline">
												template file
											</a>
											.
										</p>
										<div className="relative">
											<div className="rounded-md border bg-muted px-3 py-2 font-mono text-sm">{'<div class="ultimate-badges"></div>'}</div>
											<div className="absolute right-2 top-1.5 flex gap-1">
												<Button variant="ghost" size="icon" className="h-7 w-7" onClick={() => copyToClipboard('<div class="ultimate-badges"></div>')}>
													{showCopied ? <CheckCircle className="h-4 w-4 text-green-500" /> : <Copy className="h-4 w-4 text-primary hover:text-primary/80" />}
												</Button>
											</div>
											<AnimatePresence>
												{showCopied && (
													<motion.div initial={{ opacity: 0, y: 5 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0 }} className="absolute left-0 right-0 top-full mt-2 text-center z-10">
														<span className="inline-flex items-center gap-1 rounded-md bg-black/80 px-4 py-3 text-sm text-white">
															<CheckCircle className="h-4 w-4 mr-1 text-green-500" /> Copied to clipboard
														</span>
													</motion.div>
												)}
											</AnimatePresence>
										</div>
									</div>

									<div className="space-y-2">
										<p className="text-sm font-medium">Need help?</p>
										<div className="flex items-center gap-4">
											<Button variant="link" className="h-auto p-0 text-sm text-blue-600 hover:text-blue-700" asChild>
												<a href="#" className="flex items-center gap-2">
													<PlayCircle />
													Step by Step Guide
												</a>
											</Button>
										</div>
									</div>
								</div>
							</div>
						</div>
					</Card>
				</div>

				{/* Bar Preview */}
				<div className="w-[1000px] h-screen sticky top-6 self-start">
					<div className="text-center pt-6">
						<h2 className="text-lg font-semibold">Bar Preview</h2>
					</div>
					<div
						className="px-48 py-16 space-y-4 bg-gray-100 mt-6"
						style={{
							fontSize: `${settings.fontSize}px`,
							textAlign: settings.alignment as any,
							color: settings.textColor,
							marginTop: settings.customMargin ? `${settings.marginTop}px` : undefined,
							marginBottom: settings.customMargin ? `${settings.marginBottom}px` : undefined,
							marginLeft: settings.customMargin ? `${settings.marginLeft}px` : undefined,
							marginRight: settings.customMargin ? `${settings.marginRight}px` : undefined,
						}}>
						{settings.showHeader && settings.headerText}
						<AnimatePresence>
							<motion.div
								className="flex flex-wrap gap-4 mt-2"
								initial={false}
								animate={
									isPlaying
										? {
												opacity: settings.animation === "fade" ? [0, 1] : 1,
												x: settings.animation === "slide" ? [-100, 0] : 0,
												scale: settings.animation === "scale" ? [0, 1] : 1,
												y: settings.animation === "bounce" ? [-20, 0] : 0,
										  }
										: {}
								}
								transition={{
									duration: 0.4,
									ease: settings.animation === "bounce" ? "easeOut" : "easeInOut",
									repeat: isPlaying ? 0 : 0,
								}}
								style={{
									display: "flex",
									flexWrap: "wrap",
									gap: "1.2rem",
									justifyContent: settings.badgeAlignment === "left" ? "flex-start" : settings.badgeAlignment === "right" ? "flex-end" : "center",
								}}>
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
							</motion.div>
						</AnimatePresence>
					</div>
					<div className="p-6 pt-0 text-center pt-4">
						<Button onClick={() => setBadgeSelectorOpen(true)}>Select Badges</Button>
					</div>
				</div>
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
