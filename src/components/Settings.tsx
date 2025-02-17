import { useState, useEffect } from "react";
import { Card } from "./ui/card";
import { Label } from "./ui/label";
import { Input } from "./ui/input";
import { Button } from "./ui/button";
import { Switch } from "./ui/switch";
import { Checkbox } from "./ui/checkbox";
import { BadgeSelector } from "./BadgeSelector";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "./ui/select";
import {
  PlayIcon,
  AlignCenter,
  AlignLeft,
  AlignRight,
  CheckCircle,
  Copy,
  HelpCircle,
  PlusCircle,
  Trash2,
  Lock,
  Unlock,
  Check,
  PenSquare,
  X,
  Monitor,
  Smartphone,
  ListRestart,
  AlertCircle,
} from "lucide-react";
import { paymentBadges } from "./pages/assets/PaymentBadges";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "./ui/tooltip";
import { motion, AnimatePresence } from "framer-motion";
import { useToast } from "./ui/use-toast";
import type { BadgeSize, TrustBadgesSettings } from "../types/badges";
import type { BadgeGroup } from "../types/badges";
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "./ui/accordion";
import { Separator } from "@/components/ui/separator";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "./ui/alert-dialog";
const free = `${window.txBadgesSettings.pluginUrl}assets/images/needed/free.png`;
const ccourierDelivey = `${window.txBadgesSettings.pluginUrl}assets/images/needed/courierDelivey.png`;
const trust = `${window.txBadgesSettings.pluginUrl}assets/images/needed/trust.png`;
import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

// Utility function for merging class names
function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

// Add these utility functions at the top of the file
const toCamelCase = (str: string) => {
  return str.replace(/([-_][a-z])/g, (group) =>
    group.toUpperCase().replace("-", "").replace("_", "")
  );
};

const toSnakeCase = (str: string) => {
  return str.replace(/[A-Z]/g, (letter) => `_${letter.toLowerCase()}`);
};

const convertKeysToSnakeCase = (obj: any): any => {
  if (typeof obj !== "object" || obj === null) return obj;

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
  if (typeof obj !== "object" || obj === null) return obj;

  if (Array.isArray(obj)) {
    return obj.map(convertKeysToCamelCase);
  }

  return Object.keys(obj).reduce((acc, key) => {
    const camelKey = toCamelCase(key);
    acc[camelKey] = convertKeysToCamelCase(obj[key]);
    return acc;
  }, {} as any);
};

// Base default settings without accordion-specific settings
const baseDefaultSettings: Omit<TrustBadgesSettings, 'checkoutBeforeOrderReview' | 'eddCheckoutBeforePurchaseForm' | 'showAfterAddToCart' | 'eddPurchaseLinkEnd'> = {
  showHeader: true,
  headerText: "Secure Checkout With",
  fontSize: "18",
  alignment: "center",
  badgeAlignment: "center",
  position: "center",
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
  selectedBadges: [
    "mastercardcolor",
    "visa1color",
    "paypal1color",
    "applepaycolor",
    "stripecolor",
    "amazonpay2color",
    "americanexpress1color"
  ],
  woocommerce: false,
  edd: false
};

const defaultBadgeGroups: BadgeGroup[] = [
  {
    id: "checkout",
    name: "Checkout",
    isDefault: true,
    isActive: true,
    settings: {
      ...baseDefaultSettings,
      checkoutBeforeOrderReview: true,
      eddCheckoutBeforePurchaseForm: true,
    },
    requiredPlugin: "woocommerce",
  },
  {
    id: "product_page",
    name: "Product Page",
    isDefault: true,
    isActive: false,
    settings: {
      ...baseDefaultSettings,
      headerText: "Secure Payment Methods",
      alignment: "left",
      showAfterAddToCart: true,
      eddPurchaseLinkEnd: true,
    },
    requiredPlugin: "woocommerce",
  },
  {
    id: "footer",
    name: "Footer",
    isDefault: true,
    isActive: false,
    settings: {
      ...baseDefaultSettings,
      headerText: "Payment Options",
      alignment: "center",
      position: "center",
    },
  },
];

/**
 * Enhanced API error handler with improved session handling
 * @param error - The error object from the API call
 * @param toast - Toast notification function
 */
const handleApiError = (error: any, toast: any) => {
  // Log the full error for debugging
  console.error("API Error:", {
    message: error.message,
    status: error.status,
    stack: error.stack,
  });

  // Check for session expiration (nonce error)
  if (error.message?.toLowerCase().includes("session expired")) {
    console.warn("Session expired - Showing warning to user");
    toast({
      title: "Session Expired",
      description:
        "Your session has expired. Please refresh the page to continue.",
      variant: "destructive",
    });
    return;
  }

  // Handle other API errors
  toast({
    title: "Error",
    description:
      error.message || "An unexpected error occurred. Please try again.",
    variant: "destructive",
  });
};

/**
 * Enhanced API fetch wrapper with improved error handling
 * @param path - API endpoint path
 * @param options - Fetch options
 */
const fetchApi = async (path: string, options: RequestInit = {}) => {
  // Setup default options with proper headers
  const defaultOptions: RequestInit = {
    headers: {
      "Content-Type": "application/json",
      "X-WP-Nonce": window.txBadgesSettings.restNonce,
    },
    credentials: "same-origin",
  };

  // Clean the path and build the full URL
  const cleanPath = path.startsWith("/") ? path : `/${path}`;
  const cleanRestUrl = window.txBadgesSettings.restUrl.replace(/\/+$/, "");
  const url = `${cleanRestUrl}${cleanPath}`;

  try {
    // Log request details in debug mode
    if (window.txBadgesSettings.debug) {
      console.log("API Request:", {
        url,
        options: {
          ...options,
          headers: {
            ...defaultOptions.headers,
            ...options.headers,
          },
        },
      });
    }

    // Make the API call
    const response = await fetch(url, {
      ...defaultOptions,
      ...options,
      headers: {
        ...defaultOptions.headers,
        ...options.headers,
      },
    });

    const data = await response.json();

    // Handle non-successful responses
    if (!response.ok) {
      console.error("API Error Response:", {
        status: response.status,
        statusText: response.statusText,
        data,
      });

      // Handle session expiration
      if (
        response.status === 403 &&
        data.message?.toLowerCase().includes("session expired")
      ) {
        throw new Error(
          "Session expired. Please refresh the page and try again."
        );
      }

      throw new Error(data.message || `HTTP error! status: ${response.status}`);
    }

    return data;
  } catch (error) {
    // Log the full error in debug mode
    if (window.txBadgesSettings.debug) {
      console.error("API Call Failed:", {
        url,
        error,
        options,
      });
    }
    throw error;
  }
};

// Badge Groups API
const badgeGroupsApi = {
  // Fetch a single group: This method is for fetching a SINGLE group by its ID
  fetchGroup: async (groupId: string) => {
    try {
      const data = await fetchApi(`settings/${groupId}`);
      return data;
    } catch (error) {
      console.error("Error fetching group:", error);
      throw error;
    }
  },

  // Save a single group: This method is for saving a SINGLE group by its ID
  saveGroup: async (group: BadgeGroup) => {
    try {
      const formattedGroup = {
        id: group.id,
        name: group.name,
        isDefault: group.isDefault,
        isActive: group.isActive,
        settings: group.settings,
        requiredPlugin: group.requiredPlugin,
      };

      const result = await fetchApi("settings/group", {
        method: "POST",
        body: JSON.stringify({ group: formattedGroup }),
      });

      return result;
    } catch (error) {
      console.error("Error saving group:", error);
      throw error;
    }
  },

  // Delete a single group: This method is for deleting a SINGLE group by its ID
  deleteGroup: async (groupId: string) => {
    try {
      const result = await fetchApi(`settings/group/${groupId}`, {
        method: "DELETE",
      });
      return result;
    } catch (error) {
      console.error("Error deleting group:", error);
      throw error;
    }
  },

  // Fetch all groups: This method is used to load ALL badge groups at once when the Settings page initially loads
  fetchAllGroups: async () => {
    try {
      const data = await fetchApi("settings");
      return data;
    } catch (error) {
      console.error("Error fetching all groups:", error);
      throw error;
    }
  },
};

// Add this new component near the top of the file, after the imports
const LoadingSwitch = ({
  checked,
  onCheckedChange,
  disabled,
  loading,
}: {
  checked: boolean;
  onCheckedChange: (checked: boolean) => void;
  disabled?: boolean;
  loading?: boolean;
}) => {
  return (
    <div className="relative">
      <Switch
        checked={checked}
        onCheckedChange={onCheckedChange}
        disabled={disabled || loading}
        className={cn(loading && "opacity-50 cursor-wait")}
      />
      {loading && (
        <div className="flex absolute inset-0 flex items-center justify-center">
          <div className="w-5 h-5 border-2 border-primary/50 border-t-primary rounded-full animate-spin mb-1" />
        </div>
      )}
    </div>
  );
};

export function Settings() {
  // States
  const [badgeGroups, setBadgeGroups] =
    useState<BadgeGroup[]>(defaultBadgeGroups);
  const [badgeSelectorOpen, setBadgeSelectorOpen] = useState(false);
  const [isPlaying, setIsPlaying] = useState(false);
  const [showCopied, setShowCopied] = useState(false);
  const [, setHasUnsavedChanges] = useState(false);
  const [, setGroupToDelete] = useState<string | null>(null);
  const [editingGroupId, setEditingGroupId] = useState<string | null>(null);
  const [originalName, setOriginalName] = useState<string>("");
  const [unsavedGroups, setUnsavedGroups] = useState<Record<string, boolean>>({});

  const [loadingGroups, setLoadingGroups] = useState<Record<string, boolean>>({});

  const { toast } = useToast();

  const [installedPlugins, setInstalledPlugins] = useState({
    woocommerce: false,
    edd: false
  });

  // Event handlers
  const handleChange = (badgeGroupId: string, key: keyof TrustBadgesSettings, value: any) => {
    setBadgeGroups((prev: BadgeGroup[]) =>
      prev.map((group) => {
        if (group.id === badgeGroupId) {
          const newSettings = { ...group.settings } as TrustBadgesSettings;

          // Handle plugin-specific settings
          if (key === 'woocommerce') {
            newSettings.woocommerce = value;
            // Update the relevant feature flag for this accordion
            if (group.id === 'checkout') {
              newSettings.checkoutBeforeOrderReview = value;
            } else if (group.id === 'product_page') {
              newSettings.showAfterAddToCart = value;
            }
          } else if (key === 'edd') {
            newSettings.edd = value;
            // Update the relevant feature flag for this accordion
            if (group.id === 'checkout') {
              newSettings.eddCheckoutBeforePurchaseForm = value;
            } else if (group.id === 'product_page') {
              newSettings.eddPurchaseLinkEnd = value;
            }
          } else {
            (newSettings as any)[key] = value;
          }

          return { ...group, settings: newSettings };
        }
        return group;
      })
    );
    
    setUnsavedGroups((prev) => ({
      ...prev,
      [badgeGroupId]: true,
    }));
  };

  const saveGroupSettings = async (group: BadgeGroup) => {
    try {
      setLoadingGroups((prev) => ({ ...prev, [group.id]: true }));
      console.log("Preparing to save group settings:", { group });

      // Prevent default form submission behavior
      event?.preventDefault?.();

      const result = await badgeGroupsApi.saveGroup(group);
      console.log("Save result:", result);

      // Update the local state to reflect the saved changes
      setBadgeGroups((prev) =>
        prev.map((g) => (g.id === group.id ? { ...g, ...result.group } : g))
      );

      // Clear the unsaved changes for this group
      setUnsavedGroups((prev) => {
        const newState = { ...prev };
        delete newState[group.id];
        return newState;
      });

      toast({
        title: "Success",
        description: `${group.name} settings saved successfully`,
      });
    } catch (error: any) {
      console.error("Save Group Settings Error:", error);
      handleApiError(error, toast);
    } finally {
      setLoadingGroups((prev) => ({ ...prev, [group.id]: false }));
    }
  };

  const loadSettings = async () => {
    try {
      const data = await badgeGroupsApi.fetchAllGroups();
      
      if (Array.isArray(data)) {
        // Set the badge groups directly from the database
        setBadgeGroups(data.map(group => ({
          ...group,
          settings: {
            ...baseDefaultSettings,
            ...group.settings
          }
        })));
      } else {
        throw new Error("Invalid settings data received");
      }
    } catch (error) {
      handleApiError(error, toast);
      // Set default settings if loading fails
      setBadgeGroups(defaultBadgeGroups);
    }
  };

  useEffect(() => {
    loadSettings();
  }, [toast]);

  // Add this useEffect after the loadSettings useEffect
  useEffect(() => {
    // Only set active accordion if explicitly toggled on
    if (badgeGroups.length > 0) {
    }
  }, []); // Empty dependency array so it only runs once on mount

  // Handle position change for Footer
  const handleSaveBadges = (badgeGroupId: string, selectedBadges: string[]) => {
    handleChange(badgeGroupId, "selectedBadges", selectedBadges);
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
        small: "h-12 w-12",
        medium: "h-16 w-16",
        large: "h-20 w-20",
      },
    } as const;

    const sizeSet = isMobile ? sizes.mobile : sizes.desktop;
    return sizeSet[size] ?? sizeSet["medium"];
  };

  const getNextSequentialId = (groups: BadgeGroup[]) => {
    const customGroups = groups.filter((g) => !g.isDefault);
    if (customGroups.length === 0) return "1";

    const ids = customGroups.map((g) => {
      const num = parseInt(g.id);
      return isNaN(num) ? 0 : num;
    });

    return String(Math.max(...ids) + 1);
  };

  const addNewBadgeGroup = () => {
    const newId = getNextSequentialId(badgeGroups);
    const newGroup: BadgeGroup = {
      id: newId,
      name: `New Badge Group ${newId}`,
      settings: { ...baseDefaultSettings },
      isDefault: false,
      isActive: true,
    };
    setBadgeGroups((prev) => [...prev, newGroup]);
    setHasUnsavedChanges(true);
  };

  const handleNameChange = (badgeGroupId: string, newName: string) => {
    setBadgeGroups((prev) =>
      prev.map((group) => {
        if (group.id === badgeGroupId) {
          return { ...group, name: newName };
        }
        return group;
      })
    );
    setHasUnsavedChanges(true);
  };

  const toggleBadgeGroupActive = async (groupId: string) => {
    try {
      setLoadingGroups((prev) => ({ ...prev, [groupId]: true }));

      const groupToUpdate = badgeGroups.find((g) => g.id === groupId);
      if (!groupToUpdate) return;

      const updatedGroup = {
        ...groupToUpdate,
        isActive: !groupToUpdate.isActive,
      };

      // Update local state
      setBadgeGroups((prev) =>
        prev.map((group) => {
          if (group.id === groupId) {
            return updatedGroup;
          }
          return group;
        })
      );

      // Save to database
      await badgeGroupsApi.saveGroup(updatedGroup);

      toast({
        title: "Success",
        description: `Badge group ${
          updatedGroup.isActive ? "activated" : "deactivated"
        } successfully`,
      });
    } catch (error: any) {
      // Revert the local state if save fails
      setBadgeGroups((prev) =>
        prev.map((group) => {
          if (group.id === groupId) {
            return { ...group, isActive: !group.isActive };
          }
          return group;
        })
      );

      console.error("Toggle Active State Error:", error);
      toast({
        title: "Error",
        description: error.message || "Failed to update badge group state",
        variant: "destructive",
      });
    } finally {
      setLoadingGroups((prev) => ({ ...prev, [groupId]: false }));
    }
  };

  const handleDeleteGroup = async (groupId: string) => {
    try {
      await badgeGroupsApi.deleteGroup(groupId);

      setBadgeGroups((prev) => prev.filter((b) => b.id !== groupId));
      setGroupToDelete(null);

      toast({
        title: "Success",
        description: "Badge group deleted successfully",
      });
    } catch (error: any) {
      console.error("Delete Group Error:", error);
      toast({
        title: "Error",
        description:
          error.message || "Failed to delete group. Please try again.",
        variant: "destructive",
      });
    }
  };

  const isPluginInstalled = (plugin?: "woocommerce" | "edd") => {
    if (!plugin) return true;
    return installedPlugins[plugin];
  };

  const getPluginWarning = (plugin?: "woocommerce" | "edd") => {
    if (!plugin || isPluginInstalled(plugin)) return null;

    const pluginInfo = {
      woocommerce: {
        name: "WooCommerce",
        link: "/wp-admin/plugin-install.php?s=woocommerce&tab=search&type=term",
      },
      edd: {
        name: "Easy Digital Downloads",
        link: "/wp-admin/plugin-install.php?s=easy-digital-downloads&tab=search&type=term",
      },
    };

    return (
      <div className="flex items-center text-red-500 text-sm">
        <span>{pluginInfo[plugin].name} is not installed. </span>
        <a
          href={pluginInfo[plugin].link}
          className="ml-1 underline hover:text-red-600"
          target="_blank"
          rel="noopener noreferrer"
          onClick={(e) => e.stopPropagation()}
        >
          Install now
        </a>
      </div>
    );
  };

  useEffect(() => {
    const fetchPluginStatus = async () => {
      try {
        const data = await fetchApi("installed-plugins");
        setInstalledPlugins(data);

        // Update badge groups based on plugin status
        setBadgeGroups(prev => prev.map(group => {
          if (group.id === 'checkout' || group.id === 'product_page') {
            return {
              ...group,
              settings: {
                ...group.settings,
                woocommerce: data.woocommerce,
                edd: data.edd,
                // Set display settings based on plugin status
                checkoutBeforeOrderReview: group.id === 'checkout' ? 
                  (data.woocommerce || data.edd) : group.settings.checkoutBeforeOrderReview,
                showAfterAddToCart: group.id === 'product_page' ? 
                  (data.woocommerce || data.edd) : group.settings.showAfterAddToCart
              }
            };
          }
          return group;
        }));
      } catch (error) {
        console.error('Failed to fetch plugin status:', error);
      }
    };

    fetchPluginStatus();
  }, []);

  // Add this function inside the Settings component
  const handleDiscardChanges = async (group: BadgeGroup) => {
    try {
      setLoadingGroups((prev) => ({ ...prev, [group.id]: true }));

      // Load all groups to get the last saved state
      const data = await badgeGroupsApi.fetchAllGroups();
      
      if (!Array.isArray(data)) {
        throw new Error("Invalid settings data received");
      }

      // Find the saved settings for this group
      const savedGroup = data.find(g => g.id === group.id);
      
      if (!savedGroup) {
        throw new Error("No saved settings found for this group");
      }

      // Update the group with saved settings
      setBadgeGroups((prev) =>
        prev.map((g) => {
          if (g.id === group.id) {
            return {
              ...g,
              settings: {
                ...baseDefaultSettings,
                ...savedGroup.settings
              }
            };
          }
          return g;
        })
      );

      // Remove this group from unsaved changes
      setUnsavedGroups((prev) => {
        const newState = { ...prev };
        delete newState[group.id];
        return newState;
      });

      toast({
        title: "Changes Reset",
        description: `Settings for ${group.name} have been reset to last saved state`,
      });
    } catch (error) {
      console.error("Error resetting changes:", error);
      toast({
        title: "Error",
        description: "Failed to reset changes. Please try again.",
        variant: "destructive"
      });
    } finally {
      setLoadingGroups((prev) => ({ ...prev, [group.id]: false }));
    }
  };

  // Add animation options
  const animationOptions = [
    { value: "fade", label: "Fade" },
    { value: "slide", label: "Slide" },
    { value: "scale", label: "Scale" },
    { value: "bounce", label: "Bounce" },
  ];

  return (
    <div className="space-y-4">
      <div className="mb-6 space-y-8">
        <div className="space-y-2">
          <h1 className="text-2xl font-bold">Trust Badges</h1>
          <p className="text-sm text-muted-foreground">
            Easily manage your trust badges and customize their display settings to boost credibility and trust with your customers within your WordPress site.
          </p>
        </div>

        <div className="flex justify-between items-center">
          <h1 className="text-lg font-bold">Your Badges</h1>
          <Button
            onClick={addNewBadgeGroup}
            className="flex items-center gap-2"
            variant="outline"
          >
            <PlusCircle className="w-4 h-4" />
            Add New Badge
          </Button>
        </div>
      </div>

      <Accordion
        type="single"
        collapsible
        className="space-y-4"
      >
        {badgeGroups.map((group) => (
          <AccordionItem
            key={group.id}
            value={group.id}
            disabled={!installedPlugins.woocommerce && !installedPlugins.edd && group.id !== 'footer'}
            className={cn(
              "border rounded-lg overflow-hidden",
              !group.isActive && "opacity-60"
            )}
          >
            <div className="flex items-center justify-between px-4">
              <div
                className={cn(
                  "flex-1 flex items-center py-4",
                  !group.isActive && "cursor-not-allowed"
                )}
              >
                {!group.isDefault ? (
                  <div className="flex items-center gap-2">
                    <div className="flex items-center gap-4">
                      {/* Lock/Unlock Icon */}
                      {editingGroupId === group.id ? (
                        <Unlock className="h-4 w-4 text-gray-300" />
                      ) : (
                        <Lock className="h-4 w-4 text-gray-300" />
                      )}

                      {/* Name/Input Field */}
                      {editingGroupId === group.id ? (
                        <Input
                          value={group.name}
                          onChange={(e) =>
                            handleNameChange(group.id, e.target.value)
                          }
                          onClick={(e) => e.stopPropagation()}
                          className={cn(
                            !group.isActive && "cursor-not-allowed bg-muted"
                          )}
                          disabled={!group.isActive}
                          autoFocus
                        />
                      ) : (
                        <span className="font-mono text-sm text-gray-700">
                          {group.name}
                        </span>
                      )}

                      {/* Edit/Save Button */}
                      {editingGroupId === group.id ? (
                        <div className="flex items-center gap-2">
                          <button
                            onClick={() => setEditingGroupId(null)}
                            className="p-0.5 hover:bg-gray-200 rounded-sm"
                          >
                            <Check className="h-4 w-4 text-gray-300" />
                          </button>
                          <button
                            onClick={() => {
                              handleNameChange(group.id, originalName); // Reset to original name
                              setEditingGroupId(null);
                              setOriginalName("");
                            }}
                            className="p-0.5 hover:bg-gray-200 rounded-sm"
                          >
                            <X className="h-4 w-4 text-gray-300" />
                          </button>
                        </div>
                      ) : (
                        <button
                          onClick={() => {
                            setOriginalName(group.name);
                            setEditingGroupId(group.id);
                          }}
                          className="p-0.5 hover:bg-gray-200 rounded-sm"
                        >
                          <PenSquare className="h-4 w-4 text-gray-300" />
                        </button>
                      )}
                    </div>
                  </div>
                ) : (
                  <span
                    className={cn(
                      "font-medium",
                      !group.isActive && "text-muted-foreground"
                    )}
                  >
                    {group.name}
                  </span>
                )}
              </div>

              <div className="flex items-center gap-8">
                {!group.isDefault && (
                  <AlertDialog>
                    <AlertDialogTrigger asChild>
                      <Button
                        variant="ghost"
                        size="icon"
                        className="h-8 w-8"
                        onClick={(e) => e.stopPropagation()}
                      >
                        <Trash2 className="h-4 w-4 text-red-500 hover:text-red-600" />
                      </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                      <AlertDialogHeader>
                        <AlertDialogTitle>Delete Badge Group</AlertDialogTitle>
                        <AlertDialogDescription>
                          Are you sure you want to delete "{group.name}"? This
                          action cannot be undone.
                        </AlertDialogDescription>
                      </AlertDialogHeader>
                      <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction
                          onClick={() => handleDeleteGroup(group.id)}
                          className="bg-red-500 hover:bg-red-600"
                        >
                          Delete
                        </AlertDialogAction>
                      </AlertDialogFooter>
                    </AlertDialogContent>
                  </AlertDialog>
                )}

                {getPluginWarning(group.requiredPlugin)}

                <LoadingSwitch
                  checked={group.isActive ?? false}
                  onCheckedChange={() => toggleBadgeGroupActive(group.id)}
                  disabled={!isPluginInstalled(group.requiredPlugin)}
                  loading={loadingGroups[group.id]}
                />

                <AccordionTrigger
                  className={cn(
                    "h-8 w-8 p-0",
                    "cursor-pointer"
                  )}
                />
              </div>
            </div>

            <AccordionContent
              className={cn(
                !group.isActive && "opacity-50 select-none"
              )}
            >
              <Separator className="my-4 bg-muted" />
              <div className="p-6 pt-4">
                {/* Main Settings */}
                <div className="flex gap-12">
                  {/* Left Section - Settings */}
                  <div className="flex-1">
                    {/* Header */}
                    <>
                      <div className="flex items-center justify-between border-b">
                        <h2 className="text-base font-semibold pb-2">
                          Header Settings
                        </h2>
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
                                  handleChange(
                                    group.id,
                                    "headerText",
                                    e.target.value
                                  )
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
                          <div className="flex items-center gap-8">
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
                                  handleChange(
                                    group.id,
                                    "fontSize",
                                    e.target.value
                                  )
                                }
                                className={`w-[150px] ${
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
                                    group.settings.alignment === "left"
                                      ? "secondary"
                                      : "ghost"
                                  }
                                  size="sm"
                                  onClick={() =>
                                    handleChange(group.id, "alignment", "left")
                                  }
                                  className="h-8 w-10"
                                  disabled={!group.settings.showHeader}
                                >
                                  <AlignLeft />
                                </Button>
                                <Button
                                  variant={
                                    group.settings.alignment === "center"
                                      ? "secondary"
                                      : "ghost"
                                  }
                                  size="sm"
                                  onClick={() =>
                                    handleChange(
                                      group.id,
                                      "alignment",
                                      "center"
                                    )
                                  }
                                  className="h-8 w-10"
                                  disabled={!group.settings.showHeader}
                                >
                                  <AlignCenter />
                                </Button>
                                <Button
                                  variant={
                                    group.settings.alignment === "right"
                                      ? "secondary"
                                      : "ghost"
                                  }
                                  size="sm"
                                  onClick={() =>
                                    handleChange(group.id, "alignment", "right")
                                  }
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
                                    handleChange(
                                      group.id,
                                      "textColor",
                                      e.target.value
                                    )
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

                    {/* Badge Placement */}
                    {(group.id === "checkout" || group.id === "product_page") && (
                      <div className="mt-8">
                        <h2 className="text-lg font-semibold border-b pb-2 mb-4">
                          Badge Placement
                        </h2>
                        <div className="space-y-6">
                          <div className="space-y-4">
                            {/* WooCommerce Option */}
                            {/*{group.isDefault && (*/}
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
                             {/*)}*/}

                            {/* EDD Option */}
                            {/*{group.isDefault && (*/}
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
                            {/*)}*/}

                            {/* Shortcode section for custom accordions */}
                            <div className="space-y-2 mt-6">
                              <p className="text-sm">
                                Use this shortcode to display the badges anywhere in the website:
                              </p>
                              <div className="relative">
                                <div className="rounded-md border bg-muted px-3 py-2 font-mono text-sm">
                                  {`[trust_badges_${group.id}]`}
                                </div>
                                <div className="absolute right-2 top-1.5 flex gap-1">
                                  <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-7 w-7"
                                    onClick={() =>
                                      copyToClipboard(`[trust_badges_${group.id}]`)
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
                    )}
                  </div>

                  <Separator
                    orientation="vertical"
                    className="bg-muted h-auto"
                  />

                  {/* Right Section - Bar Preview */}
                  <div className="w-[60%] top-6 self-start space-y-12">
                    {/* Badges */}
                    <div className="">
                      <h2 className="text-base font-semibold border-b pb-2 mb-4">
                        Badge Settings
                      </h2>
                      <div className="space-y-8">
                        <div className="flex flex-col gap-4">
                          {/* Badge Style */}
                          <div className="space-y-2">
                            <div className="grid grid-cols-4 gap-8">
                              {[
                                { id: "original", label: "Original" },
                                { id: "card", label: "Card" },
                                { id: "mono", label: "Mono" },
                                { id: "mono-card", label: "Mono Card" },
                              ].map((style) => (
                                <button
                                  key={style.id}
                                  onClick={() =>
                                    handleChange(
                                      group.id,
                                      "badgeStyle",
                                      style.id
                                    )
                                  }
                                  className={`border rounded-lg p-2 flex flex-col items-center transition-colors ${
                                    group.settings.badgeStyle === style.id
                                      ? "border-primary bg-primary/5"
                                      : "border-input hover:border-primary/50"
                                  }`}
                                >
                                  <div
                                    className={`w-10 h-8 rounded flex items-center justify-center ${
                                      style.id.includes("card")
                                        ? "bg-gray-400 shadow-sm py-1 px-2"
                                        : "p-1"
                                    }`}
                                  >
                                    <img
                                      src={`${window.txBadgesSettings.pluginUrl}assets/images/badges/mastercardcolor.svg`}
                                      alt="Badge Style Preview"
                                      className={`w-full h-full object-contain ${
                                        style.id.includes("mono")
                                          ? "grayscale"
                                          : ""
                                      }`}
                                    />
                                  </div>
                                  <span className="text-xs font-medium">
                                    {style.label}
                                  </span>
                                </button>
                              ))}
                            </div>
                          </div>

                          {/* Alignment, Size, Color */}
                          <div className="space-y-2 flex items-start gap-8">
                            {/* Badge Alignment */}
                            <div className="space-y-2">
                              <Label className="font-medium block">
                                Alignment
                              </Label>
                              <div className="flex gap-2 border rounded-md p-1 w-[150px]">
                                <Button
                                  variant={
                                    group.settings.badgeAlignment === "left"
                                      ? "secondary"
                                      : "ghost"
                                  }
                                  size="sm"
                                  onClick={() =>
                                    handleChange(
                                      group.id,
                                      "badgeAlignment",
                                      "left"
                                    )
                                  }
                                  className="h-8 w-10"
                                >
                                  <AlignLeft />
                                </Button>
                                <Button
                                  variant={
                                    group.settings.badgeAlignment === "center"
                                      ? "secondary"
                                      : "ghost"
                                  }
                                  size="sm"
                                  onClick={() =>
                                    handleChange(
                                      group.id,
                                      "badgeAlignment",
                                      "center"
                                    )
                                  }
                                  className="h-8 w-10"
                                >
                                  <AlignCenter />
                                </Button>
                                <Button
                                  variant={
                                    group.settings.badgeAlignment === "right"
                                      ? "secondary"
                                      : "ghost"
                                  }
                                  size="sm"
                                  onClick={() =>
                                    handleChange(
                                      group.id,
                                      "badgeAlignment",
                                      "right"
                                    )
                                  }
                                  className="h-8 w-10"
                                >
                                  <AlignRight />
                                </Button>
                              </div>
                            </div>

                            {/* Badge Size Dropdown */}
                            <div className="space-y-2" style={{ marginTop: 0 }}>
                              <div className="flex gap-4">
                                <div>
                                  <div className="flex items-center gap-2 mb-2">
                                    <Label className="font-medium block">
                                      Desktop Size
                                    </Label>
                                    <TooltipProvider delayDuration={0}>
                                      <Tooltip>
                                        <TooltipTrigger>
                                          <HelpCircle className="h-4 w-4 text-muted-foreground" />
                                        </TooltipTrigger>
                                        <TooltipContent
                                          sideOffset={5}
                                          className="w-48"
                                        >
                                          <p>
                                            Size of badges on desktop devices
                                            will apply after (768px) width.
                                          </p>
                                        </TooltipContent>
                                      </Tooltip>
                                    </TooltipProvider>
                                  </div>
                                  <Select
                                    value={group.settings.badgeSizeDesktop}
                                    onValueChange={(value) =>
                                      handleChange(
                                        group.id,
                                        "badgeSizeDesktop",
                                        value
                                      )
                                    }
                                  >
                                    <SelectTrigger className="w-[180px] flex items-center gap-2">
                                      <Monitor className="h-4 w-4" />
                                      <SelectValue placeholder="Desktop size" />
                                    </SelectTrigger>
                                    <SelectContent>
                                      <SelectItem value="extra-small">
                                        Extra Small
                                      </SelectItem>
                                      <SelectItem value="small">
                                        Small
                                      </SelectItem>
                                      <SelectItem value="medium">
                                        Medium
                                      </SelectItem>
                                      <SelectItem value="large">
                                        Large
                                      </SelectItem>
                                    </SelectContent>
                                  </Select>
                                </div>

                                <div>
                                  <div className="flex items-center gap-2 mb-2">
                                    <Label className="font-medium block">
                                      Mobile Size
                                    </Label>
                                    <TooltipProvider delayDuration={0}>
                                      <Tooltip>
                                        <TooltipTrigger>
                                          <HelpCircle className="h-4 w-4 text-muted-foreground" />
                                        </TooltipTrigger>
                                        <TooltipContent
                                          sideOffset={5}
                                          className="w-48"
                                        >
                                          <p>
                                            Size of badges on mobile devices
                                            will effect below of width (768px).
                                            And mobile sizes will not show on
                                            bar preview.
                                          </p>
                                        </TooltipContent>
                                      </Tooltip>
                                    </TooltipProvider>
                                  </div>
                                  <Select
                                    value={group.settings.badgeSizeMobile}
                                    onValueChange={(value) =>
                                      handleChange(
                                        group.id,
                                        "badgeSizeMobile",
                                        value
                                      )
                                    }
                                  >
                                    <SelectTrigger className="w-[180px] flex items-center gap-2">
                                      <Smartphone className="h-4 w-4" />
                                      <SelectValue placeholder="Mobile size" />
                                    </SelectTrigger>
                                    <SelectContent>
                                      <SelectItem value="extra-small">
                                        Extra Small
                                      </SelectItem>
                                      <SelectItem value="small">
                                        Small
                                      </SelectItem>
                                      <SelectItem value="medium">
                                        Medium
                                      </SelectItem>
                                      <SelectItem value="large">
                                        Large
                                      </SelectItem>
                                    </SelectContent>
                                  </Select>
                                </div>
                              </div>
                            </div>

                            {/* Badge Color */}
                            <div className="space-y-2" style={{ marginTop: 0 }}>
                              <Label
                                className={cn(
                                  "font-medium block",
                                  (group.settings.badgeStyle === "original" ||
                                    group.settings.badgeStyle === "card") &&
                                    "opacity-50"
                                )}
                              >
                                Color
                              </Label>
                              <div className="flex items-center p-2 border w-[50px] h-[42px] rounded-md bg-white">
                                <Input
                                  type="color"
                                  value={group.settings.badgeColor}
                                  onChange={(e) =>
                                    handleChange(
                                      group.id,
                                      "badgeColor",
                                      e.target.value
                                    )
                                  }
                                  className="w-11 h-8 p-0 border-0"
                                  disabled={
                                    group.settings.badgeStyle === "original" ||
                                    group.settings.badgeStyle === "card"
                                  }
                                />
                              </div>
                            </div>
                          </div>

                          {/* Custom Margin */}
                          <div className="space-y-4">
                            <div className="flex items-center justify-between">
                              <Label className="font-medium">
                                Custom Margin
                              </Label>
                              <Switch
                                checked={group.settings.customMargin}
                                onCheckedChange={(checked) =>
                                  handleChange(
                                    group.id,
                                    "customMargin",
                                    checked
                                  )
                                }
                              />
                            </div>

                            {group.settings.customMargin && (
                              <div className="" style={{ marginTop: 5 }}>
                                <div className="flex items-center gap-4">
                                  <div className="flex items-center gap-2">
                                    <Label className="font-medium min-w-[35px]">
                                      Top
                                    </Label>
                                    <Input
                                      type="number"
                                      value={group.settings.marginTop}
                                      onChange={(e) =>
                                        handleChange(
                                          group.id,
                                          "marginTop",
                                          e.target.value
                                        )
                                      }
                                      className="w-[60px] h-10"
                                    />
                                  </div>

                                  <div className="flex items-center gap-2">
                                    <Label className="font-medium min-w-[35px]">
                                      Right
                                    </Label>
                                    <Input
                                      type="number"
                                      value={group.settings.marginRight}
                                      onChange={(e) =>
                                        handleChange(
                                          group.id,
                                          "marginRight",
                                          e.target.value
                                        )
                                      }
                                      className="w-[60px] h-10"
                                    />
                                  </div>

                                  <div className="flex items-center gap-2">
                                    <Label className="font-medium min-w-[35px]">
                                      Bottom
                                    </Label>
                                    <Input
                                      type="number"
                                      value={group.settings.marginBottom}
                                      onChange={(e) =>
                                        handleChange(
                                          group.id,
                                          "marginBottom",
                                          e.target.value
                                        )
                                      }
                                      className="w-[60px] h-10"
                                    />
                                  </div>

                                  <div className="flex items-center gap-2">
                                    <Label className="font-medium min-w-[35px]">
                                      Left
                                    </Label>
                                    <Input
                                      type="number"
                                      value={group.settings.marginLeft}
                                      onChange={(e) =>
                                        handleChange(
                                          group.id,
                                          "marginLeft",
                                          e.target.value
                                        )
                                      }
                                      className="w-[60px] h-10"
                                    />
                                  </div>
                                </div>
                              </div>
                            )}
                          </div>
                        </div>
                      </div>
                    </div>

                    {/* Bar Preview Header with Animation Controls */}
                    <Card>
                      <div className="flex items-center justify-between pt-6 mb-4 px-6">
                        <h2 className="text-lg font-semibold">Bar Preview</h2>
                        <div className="flex items-center gap-4">
                          <div className="flex items-center gap-2">
                            <Label className="font-medium">Animation</Label>
                            <Select
                              value={group.settings.animation}
                              onValueChange={(value) =>
                                handleChange(group.id, "animation", value)
                              }
                            >
                              <SelectTrigger className="w-[130px]">
                                <SelectValue placeholder="Select animation" />
                              </SelectTrigger>
                              <SelectContent>
                                {animationOptions.map((option) => (
                                  <SelectItem
                                    key={option.value}
                                    value={option.value}
                                  >
                                    {option.label}
                                  </SelectItem>
                                ))}
                              </SelectContent>
                            </Select>
                          </div>
                          <Button
                            variant="outline"
                            onClick={toggleAnimation}
                            disabled={isPlaying}
                          >
                            <PlayIcon className="h-4 w-4 mr-2" />
                            {isPlaying ? "Playing..." : "Play"}
                          </Button>
                        </div>
                      </div>

                      <div
                        className="px-24 py-6 space-y-4 bg-gray-100 mt-6"
                        style={{
                          fontSize: `${group.settings.fontSize}px`,
                          textAlign: group.settings.alignment as any,
                          color: group.settings.textColor,
                        }}
                      >
                        {group.settings.showHeader && group.settings.headerText}
                        <AnimatePresence>
                          <motion.div
                            className="flex flex-wrap gap-4 mt-2"
                            initial={false}
                            animate={
                              isPlaying
                                ? {
                                    opacity:
                                      group.settings.animation === "fade"
                                        ? [0, 1]
                                        : 1,
                                    x:
                                      group.settings.animation === "slide"
                                        ? [-100, 0]
                                        : 0,
                                    scale:
                                      group.settings.animation === "scale"
                                        ? [0, 1]
                                        : 1,
                                    y:
                                      group.settings.animation === "bounce"
                                        ? [-20, 0]
                                        : 0,
                                  }
                                : {}
                            }
                            transition={{
                              duration: 0.4,
                              ease:
                                group.settings.animation === "bounce"
                                  ? "easeOut"
                                  : "easeInOut",
                              repeat: isPlaying ? 0 : 0,
                            }}
                            style={{
                              display: "flex",
                              flexWrap: "wrap",
                              gap: "1.2rem",
                              justifyContent:
                                group.settings.badgeAlignment === "left"
                                  ? "flex-start"
                                  : group.settings.badgeAlignment === "right"
                                  ? "flex-end"
                                  : "center",
                            }}
                          >
                            {group.settings.selectedBadges.map((badgeId) => {
                              const badge = paymentBadges.find(
                                (b) => b.id === badgeId
                              );
                              const isMonoStyle =
                                group.settings.badgeStyle === "mono" ||
                                group.settings.badgeStyle === "mono-card";
                              return badge ? (
                                <div
                                  key={badgeId}
                                  className={cn(
                                    "flex items-center justify-center",
                                    group.settings.badgeStyle === "card" ||
                                      group.settings.badgeStyle === "mono-card"
                                      ? "px-2 bg-gray-200 rounded"
                                      : ""
                                  )}
                                  style={
                                    group.settings.customMargin
                                      ? {
                                          marginTop: `${group.settings.marginTop}px`,
                                          marginBottom: `${group.settings.marginBottom}px`,
                                          marginLeft: `${group.settings.marginLeft}px`,
                                          marginRight: `${group.settings.marginRight}px`,
                                        }
                                      : undefined
                                  }
                                >
                                  <div
                                    className={`object-contain transition-all duration-300
																					${getBadgeSize(
                                            group.settings
                                              .badgeSizeDesktop as BadgeSize
                                          )} // Only desktop size for preview
																				`}
                                    style={{
                                      ...(isMonoStyle
                                        ? {
                                            WebkitMask: `url(${badge.image}) center/contain no-repeat`,
                                            mask: `url(${badge.image}) center/contain no-repeat`,
                                            backgroundColor:
                                              group.settings.badgeColor,
                                          }
                                        : {
                                            backgroundImage: `url(${badge.image})`,
                                            backgroundSize: "contain",
                                            backgroundPosition: "center",
                                            backgroundRepeat: "no-repeat",
                                          }),
                                    }}
                                  />
                                </div>
                              ) : null;
                            })}
                          </motion.div>
                        </AnimatePresence>
                      </div>

                      <div className="p-6 pt-0 text-center pt-4">
                        <Button
                          variant="outline"
                          onClick={() => setBadgeSelectorOpen(true)}
                        >
                          Select Badges
                        </Button>
                      </div>
                    </Card>
                  </div>
                </div>

                {/* Badge Selector Modal */}
                <BadgeSelector
                  open={badgeSelectorOpen}
                  onOpenChange={setBadgeSelectorOpen}
                  badges={paymentBadges}
                  initialSelected={group.settings.selectedBadges}
                  onSave={(selectedBadges) =>
                    handleSaveBadges(group.id, selectedBadges)
                  }
                />

                {/* Save and Cancel buttons */}
                <div className="mt-8 flex items-center justify-between gap-2">
                  <Button
                    onClick={() => handleDiscardChanges(group)}
                    disabled={
                      !unsavedGroups[group.id] || loadingGroups[group.id]
                    }
                    variant="secondary"
                    className="gap-2"
                  >
                    <ListRestart className="h-4 w-4" />
                    Reset
                  </Button>

                  <Button
                    onClick={() => saveGroupSettings(group)}
                    disabled={
                      !unsavedGroups[group.id] || loadingGroups[group.id]
                    }
                    className={`${
                      unsavedGroups[group.id]
                        ? "bg-primary hover:bg-primary/90"
                        : "bg-gray-200"
                    } gap-2`}
                  >
                    {loadingGroups[group.id] ? (
                      <div className="flex items-center gap-2">
                        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                        Saving...
                      </div>
                    ) : (
                      <>
                        <Check className="h-4 w-4" />
                        {unsavedGroups[group.id]
                          ? "Save Changes"
                          : "All Changes Saved"}
                      </>
                    )}
                  </Button>
                </div>
              </div>
            </AccordionContent>
          </AccordionItem>
        ))}
      </Accordion>
      
      <div className="pt-8 space-y-2">
        <div className="space-y-2">
            <h1 className="text-lg font-bold">Example Screenshots</h1>

            <div className="grid grid-cols-3 gap-4">
              <img
                src={ccourierDelivey}
                alt="American Express"
                className="w-full h-full rounded"
              />
              <img
                src={trust}
                alt="American Express"
                className="w-full h-full rounded"
              />
              <img
                src={free}
                alt="American Express"
                className="w-full h-full rounded"
              />
      
          </div>
        </div>
      </div>
    </div>
  );
}
