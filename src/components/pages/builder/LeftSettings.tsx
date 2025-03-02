import type { BadgeGroup } from "../../../types/badges";
import type { TrustBadgesSettings } from "../../../types/badges";
import { HeaderBuilder } from "./HeaderBuilder";
import { BadgePlacement } from "./BadgePlacement";

interface LeftSettingsProps {
  group: BadgeGroup;
  installedPlugins: {
    woocommerce: boolean;
    edd: boolean;
  };
  handleChange: (groupId: string, key: keyof TrustBadgesSettings, value: any) => void;
  copyToClipboard: (text: string) => void;
  showCopied: boolean;
}

export function LeftSettings({
  group,
  installedPlugins,
  handleChange,
  copyToClipboard,
  showCopied,
}: LeftSettingsProps) {
  return (
    <div className="w-[40%]">
      {/* Header Builder */}
      <HeaderBuilder group={group} handleChange={handleChange} />

      {/* Badge Placement */}
      <BadgePlacement
        group={group}
        installedPlugins={installedPlugins}
        handleChange={handleChange}
        copyToClipboard={copyToClipboard}
        showCopied={showCopied}
      />
    </div>
  );
}
