import { createRoot } from "react-dom/client";
import App from "./App";
import "./index.css";

document.addEventListener("DOMContentLoaded", () => {
  const appElement = document.getElementById("trust-badges-app");
  if (!appElement) {
    alert("Trust Badges: app element(#trust-badges-app) not found");
    return;
  }
  const root = createRoot(appElement);
  root.render(
    <App />
  );
});
