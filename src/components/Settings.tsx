import { useState } from "react";
import { Card } from "./ui/card";
import { Label } from "./ui/label";
import { Input } from "./ui/input";
import { Button } from "./ui/button";
import { RadioGroup, RadioGroupItem } from "./ui/radio-group";
import { Switch } from "./ui/switch";
import { BadgeSelector } from "./BadgeSelector";
import { Checkbox } from "./ui/checkbox";
import { PlayIcon } from "lucide-react";

export function Settings() {
	const [settings, setSettings] = useState({
		showHeader: true,
		headerText: "Secure Checkout With",
		font: "Asap",
		alignment: "center",
		fontSize: "17",
		textColor: "#3A3A3A",
		selectedBadges: [] as string[],
		badgeStyle: "original",
		badgeSizeDesktop: "small",
		badgeSizeMobile: "small",
		badgeColor: "#0000FF",
		customMargin: false,
		marginTop: "0",
		marginBottom: "0",
		animation: "groove",
		showOnProductPage: false,
	});

	const [isPlaying, setIsPlaying] = useState(false);
	const [badgeSelectorOpen, setBadgeSelectorOpen] = useState(false);

	const handleChange = (key: string, value: any) => {
		setSettings((prev) => ({ ...prev, [key]: value }));
	};

	const handleSaveBadges = (selectedBadges: string[]) => {
		handleChange("selectedBadges", selectedBadges);
		setBadgeSelectorOpen(false);
	};

	const toggleAnimation = () => {
		setIsPlaying(!isPlaying);
		// Add animation logic here
		setTimeout(() => setIsPlaying(false), 2000); // Reset after 2 seconds
	};

	return (
		<div className="space-y-8">
			<h1 className="text-4xl font-bold">Trust Badges</h1>

			<div className="flex gap-8">
				<div className="flex-1 space-y-8">
					<Card className="p-6">
						<h2 className="text-lg font-semibold mb-4">Header Settings</h2>

						<div className="space-y-4">
							<div className="flex items-center gap-2">
								<Switch id="show-header" checked={settings.showHeader} onCheckedChange={(checked) => handleChange("showHeader", checked)} />
								<Label htmlFor="show-header">Show header</Label>
							</div>

							<div className="space-y-2">
								<Label>Header text</Label>
								<Input value={settings.headerText} onChange={(e) => handleChange("headerText", e.target.value)} />
							</div>

							<div className="space-y-2">
								<Label>Font</Label>
								<select value={settings.font} onChange={(e) => handleChange("font", e.target.value)} className="w-full rounded-md border border-input bg-background px-3 py-2">
									<option value="Asap">Asap</option>
									<option value="Arial">Arial</option>
									<option value="Helvetica">Helvetica</option>
								</select>
							</div>

							<div className="space-y-2">
								<Label>Alignment</Label>
								<RadioGroup value={settings.alignment} onValueChange={(value) => handleChange("alignment", value)} className="flex gap-1 border rounded-md p-1 w-fit">
									<div className="flex items-center">
										<RadioGroupItem value="left" id="left" className="sr-only" />
										<Label htmlFor="left">
											<Button variant={settings.alignment === "left" ? "default" : "ghost"} size="sm" type="button" className="h-8 w-8">
												<svg className="w-4 h-4" viewBox="0 0 24 24">
													<path fill="currentColor" d="M3 5h18v2H3V5zm0 4h12v2H3V9zm0 4h18v2H3v-2zm0 4h12v2H3v-2z" />
												</svg>
											</Button>
										</Label>
									</div>

									<div className="flex items-center">
										<RadioGroupItem value="center" id="center" className="sr-only" />
										<Label htmlFor="center">
											<Button variant={settings.alignment === "center" ? "default" : "ghost"} size="sm" type="button" className="h-8 w-8">
												<svg className="w-4 h-4" viewBox="0 0 24 24">
													<path fill="currentColor" d="M3 5h18v2H3V5zm4 4h10v2H7V9zm-4 4h18v2H3v-2zm4 4h10v2H7v-2z" />
												</svg>
											</Button>
										</Label>
									</div>

									<div className="flex items-center">
										<RadioGroupItem value="right" id="right" className="sr-only" />
										<Label htmlFor="right">
											<Button variant={settings.alignment === "right" ? "default" : "ghost"} size="sm" type="button" className="h-8 w-8">
												<svg className="w-4 h-4" viewBox="0 0 24 24">
													<path fill="currentColor" d="M3 5h18v2H3V5zm6 4h12v2H9V9zm-6 4h18v2H3v-2zm6 4h12v2H9v-2z" />
												</svg>
											</Button>
										</Label>
									</div>
								</RadioGroup>
							</div>

							<div className="space-y-2">
								<Label>Font size</Label>
								<div className="flex items-center gap-2">
									<Input type="number" value={settings.fontSize} onChange={(e) => handleChange("fontSize", e.target.value)} className="w-20" />
									<Button variant="outline" size="sm" onClick={() => handleChange("fontSize", String(Number(settings.fontSize) - 1))} className="h-8 w-8 p-0">
										-
									</Button>
									<Button variant="outline" size="sm" onClick={() => handleChange("fontSize", String(Number(settings.fontSize) + 1))} className="h-8 w-8 p-0">
										+
									</Button>
								</div>
							</div>

							<div className="space-y-2">
								<Label>Text color</Label>
								<Input type="color" value={settings.textColor} onChange={(e) => handleChange("textColor", e.target.value)} className="w-20 h-10" />
							</div>
						</div>
					</Card>

					<Card className="p-6">
						<h2 className="text-lg font-semibold mb-4">Badge Settings</h2>

						<div className="space-y-6">
							<div className="space-y-2">
								<Label>Badge style</Label>
								<div className="grid grid-cols-2 gap-2">
									<button className={`border rounded-lg p-4 flex flex-col items-center gap-2 ${settings.badgeStyle === "mono" ? "border-primary" : "border-input"}`} onClick={() => handleChange("badgeStyle", "mono")}>
										<div className="w-12 h-8 bg-gray-400 rounded" />
										<span>Mono</span>
									</button>
									<button className={`border rounded-lg p-4 flex flex-col items-center gap-2 ${settings.badgeStyle === "original" ? "border-primary" : "border-input"}`} onClick={() => handleChange("badgeStyle", "original")}>
										<div className="w-12 h-8 bg-blue-500 rounded" />
										<span>Original</span>
									</button>
									<button className={`border rounded-lg p-4 flex flex-col items-center gap-2 ${settings.badgeStyle === "mono-card" ? "border-primary" : "border-input"}`} onClick={() => handleChange("badgeStyle", "mono-card")}>
										<div className="w-12 h-8 bg-gray-400 rounded shadow-sm" />
										<span>Mono Card</span>
									</button>
									<button className={`border rounded-lg p-4 flex flex-col items-center gap-2 ${settings.badgeStyle === "card" ? "border-primary" : "border-input"}`} onClick={() => handleChange("badgeStyle", "card")}>
										<div className="w-12 h-8 bg-blue-500 rounded shadow-sm" />
										<span>Card</span>
									</button>
								</div>
							</div>

							<div className="space-y-2">
								<Label>Badge size desktop</Label>
								<select value={settings.badgeSizeDesktop} onChange={(e) => handleChange("badgeSizeDesktop", e.target.value)} className="w-full rounded-md border border-input bg-background px-3 py-2">
									<option value="small">Small</option>
									<option value="medium">Medium</option>
									<option value="large">Large</option>
								</select>
							</div>

							<div className="space-y-2">
								<Label>Badge size mobile</Label>
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
								<Label>Badge color</Label>
								<Input type="color" value={settings.badgeColor} onChange={(e) => handleChange("badgeColor", e.target.value)} className="w-20 h-10" />
							</div>

							<div className="space-y-4">
								<div className="flex items-center gap-2">
									<Checkbox id="custom-margin" checked={settings.customMargin} onCheckedChange={(checked) => handleChange("customMargin", checked)} />
									<Label htmlFor="custom-margin">Custom Margin</Label>
								</div>

								{settings.customMargin && (
									<div className="space-y-4 pl-6">
										<div className="space-y-2">
											<Label>Top</Label>
											<div className="flex items-center gap-2">
												<Input type="number" value={settings.marginTop} onChange={(e) => handleChange("marginTop", e.target.value)} className="w-20" />
												<span>px</span>
											</div>
										</div>
										<div className="space-y-2">
											<Label>Bottom</Label>
											<div className="flex items-center gap-2">
												<Input type="number" value={settings.marginBottom} onChange={(e) => handleChange("marginBottom", e.target.value)} className="w-20" />
												<span>px</span>
											</div>
										</div>
										<p className="text-sm text-muted-foreground">This setting will only appear in your live store</p>
									</div>
								)}
							</div>
						</div>
					</Card>

					<Card className="p-6">
						<h2 className="text-lg font-semibold mb-4">Animation</h2>

						<div className="space-y-6">
							<div className="space-y-2">
								<div className="flex items-center justify-between">
									<Label>Animation</Label>
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

					<Card className="p-6">
						<h2 className="text-lg font-semibold mb-4">Bar Placement</h2>

						<div className="space-y-6">
							<div className="flex items-center gap-2">
								<Checkbox id="show-product-page" checked={settings.showOnProductPage} onCheckedChange={(checked) => handleChange("showOnProductPage", checked)} />
								<Label htmlFor="show-product-page">Product page</Label>
								<Button variant="ghost" size="icon" className="ml-auto rounded-full" asChild>
									<a href="#" className="text-muted-foreground hover:text-foreground">
										<svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg" className="h-4 w-4">
											<path
												d="M7.5 1.5C4.18629 1.5 1.5 4.18629 1.5 7.5C1.5 10.8137 4.18629 13.5 7.5 13.5C10.8137 13.5 13.5 10.8137 13.5 7.5C13.5 4.18629 10.8137 1.5 7.5 1.5ZM7.5 2.5C10.2614 2.5 12.5 4.73858 12.5 7.5C12.5 10.2614 10.2614 12.5 7.5 12.5C4.73858 12.5 2.5 10.2614 2.5 7.5C2.5 4.73858 4.73858 2.5 7.5 2.5ZM7 4.5V5.5H8V4.5H7ZM7 6.5V10.5H8V6.5H7Z"
												fill="currentColor"
												fillRule="evenodd"
												clipRule="evenodd"></path>
										</svg>
									</a>
								</Button>
							</div>

							<div className="space-y-2">
								<Label>To display the bar in a custom location place the following code inside the template file.</Label>
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

				<Card className="flex-1 p-6">
					<h2 className="text-lg font-semibold mb-4">Bar Preview</h2>
					<div
						className="border rounded-lg p-4 mb-4"
						style={{
							textAlign: settings.alignment as any,
							fontFamily: settings.font,
							fontSize: `${settings.fontSize}px`,
							color: settings.textColor,
							marginTop: settings.customMargin ? `${settings.marginTop}px` : undefined,
							marginBottom: settings.customMargin ? `${settings.marginBottom}px` : undefined,
						}}>
						{settings.showHeader && settings.headerText}
						<div className={`flex justify-center gap-4 mt-2 ${isPlaying ? "animate-" + settings.animation : ""}`}>
							{settings.selectedBadges.length > 0 ? (
								settings.selectedBadges.map((badgeId) => (
									<div
										key={badgeId}
										className={`h-8 w-12 rounded ${settings.badgeStyle === "mono" || settings.badgeStyle === "mono-card" ? "bg-gray-400" : ""} ${
											settings.badgeStyle === "card" || settings.badgeStyle === "mono-card" ? "shadow-sm" : ""
										}`}
										style={{
											backgroundColor: settings.badgeStyle === "original" || settings.badgeStyle === "card" ? settings.badgeColor : undefined,
										}}
									/>
								))
							) : (
								<>
									<div className="h-8 w-12 bg-gray-200 rounded" />
									<div className="h-8 w-12 bg-gray-200 rounded" />
									<div className="h-8 w-12 bg-gray-200 rounded" />
									<div className="h-8 w-12 bg-gray-200 rounded" />
									<div className="h-8 w-12 bg-gray-200 rounded" />
								</>
							)}
						</div>
					</div>
					<Button className="w-full" onClick={() => setBadgeSelectorOpen(true)}>
						Select Badges
					</Button>
				</Card>
			</div>

			<BadgeSelector open={badgeSelectorOpen} onOpenChange={setBadgeSelectorOpen} onSave={handleSaveBadges} />
		</div>
	);
}
