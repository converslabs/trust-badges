import { useState, useEffect } from "react";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "./ui/dialog";
import { Input } from "./ui/input";
import { Button } from "./ui/button";
import { Search } from "lucide-react";

interface BadgeSelectorProps {
	open: boolean;
	onOpenChange: (open: boolean) => void;
	onSave: (selectedBadges: string[]) => void;
	badges: Array<{
		id: string;
		name: string;
		image: string;
	}>;
	initialSelected?: string[];
}

export function BadgeSelector({ open, onOpenChange, onSave, badges, initialSelected = [] }: BadgeSelectorProps) {
	const [search, setSearch] = useState("");
	const [selectedBadges, setSelectedBadges] = useState<string[]>(initialSelected);

	// Update selected badges when initialSelected changes
	useEffect(() => {
		setSelectedBadges(initialSelected);
	}, [initialSelected]);

	const filteredBadges = badges.filter((badge) => badge.name.toLowerCase().includes(search.toLowerCase()));

	const toggleBadge = (id: string) => {
		setSelectedBadges((prev) => (prev.includes(id) ? prev.filter((b) => b !== id) : [...prev, id]));
	};

	return (
		<Dialog open={open} onOpenChange={onOpenChange}>
			<DialogContent className="max-w-[1000px]">
				<DialogHeader>
					<DialogTitle>Select Badges</DialogTitle>
				</DialogHeader>

				<div className="relative mb-4">
					<Search className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
					<Input placeholder="Search" value={search} onChange={(e) => setSearch(e.target.value)} className="pl-9" />
				</div>

				<div className="grid grid-cols-6 gap-4 overflow-y-auto max-h-[400px] p-[0.7rem]">
					{filteredBadges.map((badge) => (
						<div
							key={badge.id}
							onClick={() => toggleBadge(badge.id)}
							className={`relative border rounded-lg p-4 cursor-pointer hover:border-primary transition-colors ${selectedBadges.includes(badge.id) ? "border-primary bg-primary/5" : "border-input"}`}>
							{selectedBadges.includes(badge.id) && <div className="absolute -top-2 -right-2 bg-primary text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">{selectedBadges.indexOf(badge.id) + 1}</div>}
							<div className="h-12 flex items-center justify-center">
								<img src={badge.image} alt={badge.name} className="h-8 w-auto object-contain" />
							</div>
						</div>
					))}
				</div>

				<div className="flex justify-end gap-2 mt-4">
					<Button variant="outline" onClick={() => onOpenChange(false)}>
						Cancel
					</Button>
					<Button
						className="px-6"
						onClick={() => {
							onSave(selectedBadges);
							onOpenChange(false);
						}}>
						Save
					</Button>
				</div>
			</DialogContent>
		</Dialog>
	);
}
