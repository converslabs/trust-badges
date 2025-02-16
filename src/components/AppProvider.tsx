import { ReactNode, useEffect, useState } from "react";

interface AppProviderProps {
  children: ReactNode;
}

export function AppProvider({ children }: AppProviderProps) {
  const [isReady, setIsReady] = useState(false);

  useEffect(() => {
    // Check if settings are already available
    if (window.txBadgesSettings) {
      setIsReady(true);
      return;
    }

    // If not, wait for them to be available
    const checkSettings = () => {
      if (window.txBadgesSettings) {
        setIsReady(true);
      } else {
        setTimeout(checkSettings, 100);
      }
    };

    checkSettings();

    // Cleanup
    return () => {
      setIsReady(false);
    };
  }, []);

  if (!isReady) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-lg">Loading...</div>
      </div>
    );
  }

  return <>{children}</>;
}
