import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import App from "./App";
import "./index.css";

document.addEventListener("DOMContentLoaded", () => {
  const appElement = document.getElementById("tx-badges-app");
  if (!appElement) return;

  const root = createRoot(appElement);
  root.render(
    <StrictMode>
      <App />
    </StrictMode>
  );
});
