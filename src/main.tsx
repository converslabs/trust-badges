import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import App from "./App";
import "./index.css";

declare global {
	interface Window {
		txBadgesSettings: {
			restUrl: string;
			nonce: string;
			pluginUrl: string;
		};
	}
}

createRoot(document.getElementById("tx-badges-app")!).render(
	<StrictMode>
		<App />
	</StrictMode>
);
