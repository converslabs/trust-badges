import { useState } from "react";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "./ui/dialog";
import { Input } from "./ui/input";
import { Button } from "./ui/button";

interface BadgeSelectorProps {
	open: boolean;
	onOpenChange: (open: boolean) => void;
	onSave: (selectedBadges: string[]) => void;
}

const badges = [
	{ id: "1", name: "Mastercard", src: "/mastercard.svg" },
	{ id: "2", name: "Visa", src: "/visa.svg" },
	{ id: "4", name: "Apple Pay", src: "/apple-pay.svg" },
	{ id: "3", name: "American Express", src: "/amex.svg" },
	{ id: "5", name: "Shop Pay", src: "/shop-pay.svg" },
	{ id: "6", name: "Amazon Pay", src: "/amazon-pay.svg" },
	{ id: "7", name: "Google Pay", src: "/google-pay.svg" },
	{ id: "8", name: "PayPal", src: "/paypal.svg" },
	{ id: "9", name: "Klarna", src: "/klarna.svg" },
	{ id: "10", name: "Afterpay", src: "/afterpay.svg" },
];

export function BadgeSelector({ open, onOpenChange, onSave }: BadgeSelectorProps) {
	const [search, setSearch] = useState("");
	const [selectedBadges, setSelectedBadges] = useState<string[]>([]);

	const filteredBadges = badges.filter((badge) => badge.name.toLowerCase().includes(search.toLowerCase()));

	const toggleBadge = (id: string) => {
		setSelectedBadges((prev) => (prev.includes(id) ? prev.filter((b) => b !== id) : [...prev, id]));
	};

	return (
		<Dialog open={open} onOpenChange={onOpenChange}>
			<DialogContent>
				<DialogHeader>
					<DialogTitle>Select Badges</DialogTitle>
				</DialogHeader>

				<div className="relative">
					<Input placeholder="Search" value={search} onChange={(e) => setSearch(e.target.value)} className="pl-10" />
					<svg className="absolute left-3 top-2.5 h-5 w-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
					</svg>
				</div>

				<div className="grid grid-cols-5 gap-4 overflow-y-auto max-h-[400px] p-1">
					{filteredBadges.map((badge) => (
						<div
							key={badge.id}
							className={`relative border rounded-lg p-4 cursor-pointer hover:border-primary transition-colors ${selectedBadges.includes(badge.id) ? "border-primary" : "border-input"}`}
							onClick={() => toggleBadge(badge.id)}>
							{selectedBadges.includes(badge.id) && <div className="absolute -top-2 -right-2 bg-primary text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">{selectedBadges.indexOf(badge.id) + 1}</div>}
							<div className="h-12 flex items-center justify-center">
								<div className="w-12 h-8 bg-gray-200 rounded" />
							</div>
						</div>
					))}
				</div>

				<DialogFooter>
					<Button variant="outline" onClick={() => onOpenChange(false)}>
						Cancel
					</Button>
					<Button onClick={() => onSave(selectedBadges)}>Save</Button>
				</DialogFooter>
			</DialogContent>
		</Dialog>
	);
}
