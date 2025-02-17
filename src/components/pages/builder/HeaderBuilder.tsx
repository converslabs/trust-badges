import { Button } from "../../ui/button";
import { Label } from "../../ui/label";
import { Input } from "../../ui/input";
import { Switch } from "../../ui/switch";
import { AlignLeft, AlignCenter, AlignRight } from "lucide-react";
import type { BadgeGroup } from "../../../types/badges";
import type { TrustBadgesSettings } from "../../../types/badges";

interface HeaderBuilderProps {
  group: BadgeGroup;
  handleChange: (groupId: string, key: keyof TrustBadgesSettings, value: any) => void;
}

export function HeaderBuilder({ group, handleChange }: HeaderBuilderProps) {
  return (
    <>
      <div className="flex items-center justify-between border-b">
        <h2 className="text-base font-semibold pb-2">Header Settings</h2>
        <Switch
          checked={group.settings.showHeader}
          onCheckedChange={(checked) =>
            handleChange(group.id, "showHeader", checked)
          }
        />
      </div>
      <div className="space-y-2">
        <div className="flex flex-col gap-4">
          {/* Header text and font size */}
          <div className="space-y-4 mt-4">
            <div className="space-y-2">
              <Label
                className={`font-medium block ${
                  !group.settings.showHeader ? "opacity-50" : ""
                }`}
              >
                Header text
              </Label>
              <Input
                value={group.settings.headerText}
                onChange={(e) =>
                  handleChange(group.id, "headerText", e.target.value)
                }
                disabled={!group.settings.showHeader}
                className={
                  !group.settings.showHeader
                    ? "opacity-50 cursor-not-allowed"
                    : ""
                }
              />
            </div>
          </div>

          {/* Style Controls - Inline */}
          <div className="flex items-center gap-4">
            {/* Font Size */}
            <div className="space-y-2">
              <Label
                className={`font-medium block ${
                  !group.settings.showHeader ? "opacity-50" : ""
                }`}
              >
                Font Size (px)
              </Label>
              <Input
                type="number"
                value={group.settings.fontSize}
                onChange={(e) =>
                  handleChange(group.id, "fontSize", e.target.value)
                }
                className={`w-[100px] ${
                  !group.settings.showHeader
                    ? "opacity-50 cursor-not-allowed"
                    : ""
                }`}
                disabled={!group.settings.showHeader}
              />
            </div>

            {/* Alignment */}
            <div className="space-y-2">
              <Label
                className={`font-medium block ${
                  !group.settings.showHeader ? "opacity-50" : ""
                }`}
              >
                Alignment
              </Label>
              <div
                className={`flex gap-2 border rounded-md p-1 w-[150px] ${
                  !group.settings.showHeader ? "opacity-50" : ""
                }`}
              >
                <Button
                  variant={
                    group.settings.alignment === "left" ? "secondary" : "ghost"
                  }
                  size="sm"
                  onClick={() => handleChange(group.id, "alignment", "left")}
                  className="h-8 w-10"
                  disabled={!group.settings.showHeader}
                >
                  <AlignLeft />
                </Button>
                <Button
                  variant={
                    group.settings.alignment === "center" ? "secondary" : "ghost"
                  }
                  size="sm"
                  onClick={() => handleChange(group.id, "alignment", "center")}
                  className="h-8 w-10"
                  disabled={!group.settings.showHeader}
                >
                  <AlignCenter />
                </Button>
                <Button
                  variant={
                    group.settings.alignment === "right" ? "secondary" : "ghost"
                  }
                  size="sm"
                  onClick={() => handleChange(group.id, "alignment", "right")}
                  className="h-8 w-10"
                  disabled={!group.settings.showHeader}
                >
                  <AlignRight />
                </Button>
              </div>
            </div>

            {/* Text Color */}
            <div className="space-y-2">
              <Label
                className={`font-medium block ${
                  !group.settings.showHeader ? "opacity-50" : ""
                }`}
              >
                Color
              </Label>
              <div
                className={`flex items-center p-2 border w-[50px] h-[42px] rounded-md bg-white ${
                  !group.settings.showHeader ? "opacity-50" : ""
                }`}
              >
                <Input
                  type="color"
                  value={group.settings.textColor}
                  onChange={(e) =>
                    handleChange(group.id, "textColor", e.target.value)
                  }
                  className="w-11 h-8 p-0 border-0"
                  disabled={!group.settings.showHeader}
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
