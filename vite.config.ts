import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
	plugins: [react()],
	build: {
		outDir: "assets",
		emptyOutDir: false,
		manifest: false,
		rollupOptions: {
			input: "src/main.tsx",
			output: {
				entryFileNames: "js/main.js",
				chunkFileNames: "[name].js",
				assetFileNames: (assetInfo) => {
					if (assetInfo.name === "main.css") return "css/main.css";
					return "[name][extname]";
				},
			},
		},
	},
	resolve: {
		alias: {
			"@": path.resolve(__dirname, "./src"),
		},
	},
	server: {
		port: 3000,
		strictPort: true,
		cors: true,
	},
});