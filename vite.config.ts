import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import react from "@vitejs/plugin-react";
import path from "path";

export default defineConfig({
  plugins: [
    laravel({
      input: [
        "resources/css/app.css",
        "resources/js/app.tsx",
        "resources/js/vendor/dpo-payments/index.ts",
      ],
      refresh: true,
    }),
    react(),
  ],
  build: {
    lib: {
      entry: path.resolve(__dirname, "resources/js/index.ts"),
      name: "DpoPayments",
      formats: ["es", "umd"],
      fileName: (format) => `dpo-payments.${format}.ts`,
    },
    rollupOptions: {
      external: ["react", "react-dom"],
      output: {
        globals: {
          react: "React",
          "react-dom": "ReactDOM",
        },
      },
    },
    outDir: "dist",
    sourcemap: true,
  },
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./resources/js"),
    },
  },
});
