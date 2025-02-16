import { useState } from "react";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useToast } from "@/components/ui/use-toast";
import { ImagePlus } from "lucide-react";

interface AddBadgeDialogProps {
	open: boolean;
	onOpenChange: (open: boolean) => void;
}

export function AddBadgeDialog({ open, onOpenChange }: AddBadgeDialogProps) {
	const [title, setTitle] = useState("");
	const [imageUrl, setImageUrl] = useState("");
	const [linkUrl, setLinkUrl] = useState("");
	const { toast } = useToast();
	const [isUploading, setIsUploading] = useState(false);

	const handleImageUpload = () => {
		// Create media frame
		const mediaFrame = wp.media({
			title: "Select Badge Image",
			button: {
				text: "Use this image",
			},
			multiple: false,
		});

		// Handle selection
		mediaFrame.on("select", function () {
			const attachment = mediaFrame.state().get("selection").first().toJSON();
			setImageUrl(attachment.url);
		});

		// Open media frame
		mediaFrame.open();
	};

	const handleSubmit = async (e: React.FormEvent) => {
		e.preventDefault();

		if (!title || !imageUrl) {
			toast({
				title: "Error",
				description: "Please fill in all required fields",
				variant: "destructive",
			});
			return;
		}

		try {
			const response = await fetch("/wp-json/trust-badges/v1/badges", {
				method: "POST",
				headers: {
					"Content-Type": "application/json",
					"X-WP-Nonce": (window as any).wpApiSettings.nonce,
				},
				body: JSON.stringify({
					title,
					image_url: imageUrl,
					link_url: linkUrl,
				}),
			});

			if (!response.ok) throw new Error("Failed to create badge");

			toast({
				title: "Success",
				description: "Badge created successfully",
			});

			// Reset form
			setTitle("");
			setImageUrl("");
			setLinkUrl("");
			onOpenChange(false);
		} catch (error) {
			toast({
				title: "Error",
				description: "Failed to create badge",
				variant: "destructive",
			});
		}
	};

	return (
		<Dialog open={open} onOpenChange={onOpenChange}>
			<DialogContent className="sm:max-w-[425px]">
				<DialogHeader>
					<DialogTitle>Add New Badge</DialogTitle>
					<DialogDescription>Create a new trust badge to display on your store.</DialogDescription>
				</DialogHeader>
				<form onSubmit={handleSubmit} className="space-y-4">
					<div className="space-y-2">
						<Label htmlFor="title">Badge Title *</Label>
						<Input id="title" value={title} onChange={(e) => setTitle(e.target.value)} placeholder="Enter badge title" />
					</div>
					<div className="space-y-2">
						<Label>Badge Image *</Label>
						<div className="flex gap-2">
							<Input value={imageUrl} onChange={(e) => setImageUrl(e.target.value)} placeholder="Image URL" readOnly />
							<Button type="button" variant="outline" onClick={handleImageUpload} disabled={isUploading}>
								<ImagePlus className="h-4 w-4" />
							</Button>
						</div>
						{imageUrl && (
							<div className="mt-2">
								<img src={imageUrl} alt="Badge preview" className="max-h-20 rounded-md" />
							</div>
						)}
					</div>
					<div className="space-y-2">
						<Label htmlFor="link">Badge Link URL</Label>
						<Input id="link" value={linkUrl} onChange={(e) => setLinkUrl(e.target.value)} placeholder="https://" />
					</div>
					<div className="flex justify-end gap-2">
						<Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
							Cancel
						</Button>
						<Button type="submit">Create Badge</Button>
					</div>
				</form>
			</DialogContent>
		</Dialog>
	);
}
