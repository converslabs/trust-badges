import { Popover, PopoverContent, PopoverTrigger } from "./popover";
import { Button } from "./button";
import { Input } from "./input";
import { cn } from "@/lib/utils";
import { Pipette } from "lucide-react";
import { useState } from "react";

interface ColorPickerProps {
	value: string;
	onChange: (value: string) => void;
}

export function ColorPicker({ value, onChange }: ColorPickerProps) {
	const [isOpen, setIsOpen] = useState(false);

	return (
		<Popover open={isOpen} onOpenChange={setIsOpen}>
			<PopoverTrigger asChild>
				<Button variant="outline" className="w-[280px] justify-start text-left font-normal">
					<div className="w-full flex items-center gap-2">
						<div className="h-4 w-4 rounded ring-1 ring-inset ring-gray-200" style={{ backgroundColor: value }} />
						<div className="flex-1">{value}</div>
						<Pipette className="h-4 w-4 opacity-50" />
					</div>
				</Button>
			</PopoverTrigger>
			<PopoverContent className="w-[280px] p-3">
				<div className="flex flex-col gap-4">
					<div
						className="h-32 rounded-lg"
						style={{
							backgroundColor: value,
							backgroundImage: "linear-gradient(to top, #000000 0%, transparent 100%), linear-gradient(to right, #ffffff 0%, transparent 100%)",
						}}
					/>
					<div className="flex gap-2">
						<div className="flex-1">
							<Input type="color" value={value} onChange={(e) => onChange(e.target.value)} className="h-10 w-full cursor-pointer" />
						</div>
						<Input value={value.toUpperCase()} onChange={(e) => onChange(e.target.value)} className="w-[120px] uppercase" />
					</div>
					<div className="grid grid-cols-3 gap-2">
						<div className="space-y-1 text-center text-xs">
							<div>Hex</div>
							<Input value={value.replace("#", "")} onChange={(e) => onChange("#" + e.target.value)} className="h-6 text-xs text-center uppercase" />
						</div>
						<div className="space-y-1 text-center text-xs">
							<div>R</div>
							<Input
								value={parseInt(value.slice(1, 3), 16)}
								onChange={(e) => {
									const r = Math.max(0, Math.min(255, Number(e.target.value) || 0))
										.toString(16)
										.padStart(2, "0");
									onChange("#" + r + value.slice(3));
								}}
								className="h-6 text-xs text-center"
							/>
						</div>
						<div className="space-y-1 text-center text-xs">
							<div>G</div>
							<Input
								value={parseInt(value.slice(3, 5), 16)}
								onChange={(e) => {
									const g = Math.max(0, Math.min(255, Number(e.target.value) || 0))
										.toString(16)
										.padStart(2, "0");
									onChange(value.slice(0, 3) + g + value.slice(5));
								}}
								className="h-6 text-xs text-center"
							/>
						</div>
						<div className="space-y-1 text-center text-xs">
							<div>B</div>
							<Input
								value={parseInt(value.slice(5, 7), 16)}
								onChange={(e) => {
									const b = Math.max(0, Math.min(255, Number(e.target.value) || 0))
										.toString(16)
										.padStart(2, "0");
									onChange(value.slice(0, 5) + b);
								}}
								className="h-6 text-xs text-center"
							/>
						</div>
					</div>
				</div>
			</PopoverContent>
		</Popover>
	);
}
