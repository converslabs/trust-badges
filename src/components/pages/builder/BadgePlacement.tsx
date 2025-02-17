import { Button } from "../../ui/button";
import { Label } from "../../ui/label";
import { Checkbox } from "../../ui/checkbox";
import { AlertCircle, CheckCircle, Copy } from "lucide-react";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "../../ui/tooltip";
import { motion, AnimatePresence } from "framer-motion";
import type { BadgeGroup, TrustBadgesSettings } from "../../../types/badges";

interface BadgePlacementProps {
  group: BadgeGroup;
  installedPlugins: {
    woocommerce: boolean;
    edd: boolean;
  };
  handleChange: (
    groupId: string,
    key: keyof TrustBadgesSettings,
    value: any
  ) => void;
  copyToClipboard: (text: string) => void;
  showCopied: boolean;
}

export function BadgePlacement({
  group,
  installedPlugins,
  handleChange,
  copyToClipboard,
  showCopied,
}: BadgePlacementProps) {
  return (
    <div className="mt-8">
      <h2 className="text-lg font-semibold border-b pb-2 mb-4">
        Badge Placement
      </h2>
      <div className="space-y-6">
        <div className="space-y-4">
          {/* WooCommerce Option */}
          {(group.id === "checkout" || group.id === "product_page") && (
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <Checkbox
                  id={`show-woo-${group.id}`}
                  checked={
                    group.id === "checkout"
                      ? group.settings.checkoutBeforeOrderReview
                      : group.settings.showAfterAddToCart
                  }
                  onCheckedChange={(checked) => {
                    handleChange(group.id, "woocommerce", checked);
                  }}
                  disabled={!installedPlugins.woocommerce}
                />
                <Label htmlFor={`show-woo-${group.id}`} className="text-sm">
                  WooCommerce
                </Label>
              </div>
              {!installedPlugins.woocommerce && (
                <TooltipProvider>
                  <Tooltip>
                    <TooltipTrigger>
                      <div className="flex items-center text-amber-500">
                        <AlertCircle className="h-4 mr-1" />
                        <span className="text-xs">Required Plugin</span>
                      </div>
                    </TooltipTrigger>
                    <TooltipContent>
                      <p>WooCommerce plugin is required</p>
                    </TooltipContent>
                  </Tooltip>
                </TooltipProvider>
              )}
            </div>
          )}

          {/* Easy Digital Downloads Option */}
          {(group.id === "checkout" || group.id === "product_page") && (
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <Checkbox
                  id={`show-edd-${group.id}`}
                  checked={
                    group.id === "checkout"
                      ? group.settings.eddCheckoutBeforePurchaseForm
                      : group.settings.eddPurchaseLinkEnd
                  }
                  onCheckedChange={(checked) => {
                    handleChange(group.id, "edd", checked);
                  }}
                  disabled={!installedPlugins.edd}
                />
                <Label htmlFor={`show-edd-${group.id}`} className="text-sm">
                  Easy Digital Downloads
                </Label>
              </div>
              {!installedPlugins.edd && (
                <TooltipProvider>
                  <Tooltip>
                    <TooltipTrigger>
                      <div className="flex items-center text-amber-500">
                        <AlertCircle className="h-4 mr-1" />
                        <span className="text-xs">Required Plugin</span>
                      </div>
                    </TooltipTrigger>
                    <TooltipContent>
                      <p>Easy Digital Downloads plugin is required</p>
                    </TooltipContent>
                  </Tooltip>
                </TooltipProvider>
              )}
            </div>
          )}

          {/* Shortcode section */}
          <div className="space-y-2 mt-6">
            <p className="text-sm">
              Use this shortcode to display the badges anywhere in the website:
            </p>
            <div className="relative">
              <div className="rounded-md border bg-muted px-3 py-2 font-mono text-sm">
                {`[trust_badges id="${group.id}"]`}
              </div>

              <div className="absolute right-2 top-1.5 flex gap-1">
                <Button
                  variant="ghost"
                  size="icon"
                  className="h-7 w-7"
                  onClick={() =>
                    copyToClipboard(`[trust_badges id="${group.id}"]`)
                  }
                >
                  {showCopied ? (
                    <CheckCircle className="h-4 mr-1 text-green-500" />
                  ) : (
                    <Copy className="h-4 w-4 text-primary hover:text-primary/80" />
                  )}
                </Button>
              </div>

              <AnimatePresence>
                {showCopied && (
                  <motion.div
                    initial={{ opacity: 0, y: 5 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0 }}
                    className="absolute left-0 right-0 top-full mt-2 text-center z-10"
                  >
                    <span className="inline-flex items-center gap-1 rounded-md bg-black/80 px-4 py-3 text-sm text-white">
                      <CheckCircle className="h-4 mr-1 text-green-500" />{" "}
                      Shortcode copied to clipboard
                    </span>
                  </motion.div>
                )}
              </AnimatePresence>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
