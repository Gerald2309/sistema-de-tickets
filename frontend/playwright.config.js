import { defineConfig, devices } from '@playwright/test'

// Recorridos completos contra la aplicación real en Docker (RNF-F31): `docker compose up -d` y luego
// `npm run prueba:recorridos`. Sin simulaciones: la base es la sembrada; cada recorrido crea y limpia sus
// propios datos. Un solo trabajador, porque todos comparten la misma base.
// CANAL_CHROMIUM=msedge usa el Edge instalado en lugar del Chromium de Playwright.
export default defineConfig({
  testDir: 'pruebas/recorridos',
  globalSetup: './pruebas/recorridos/preparacion.js',
  fullyParallel: false,
  workers: 1,
  retries: 0,
  timeout: 45_000,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: process.env.URL_APLICACION ?? 'http://localhost:8000',
    locale: 'es-CO',
    timezoneId: 'America/Bogota',
    trace: 'retain-on-failure',
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'], channel: process.env.CANAL_CHROMIUM } },
    { name: 'firefox', use: { ...devices['Desktop Firefox'] } },
    { name: 'webkit', use: { ...devices['Desktop Safari'] } },
  ],
})
