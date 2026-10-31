import { defineConfig, devices } from '@playwright/test'

// Recorridos completos contra la aplicación real en Docker (RNF-F31): `docker compose up -d` y luego
// `npm run prueba:recorridos`. Sin simulaciones: la base es la sembrada.
export default defineConfig({
  testDir: 'pruebas/recorridos',
  fullyParallel: false,
  retries: 0,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: process.env.URL_APLICACION ?? 'http://localhost:8000',
    locale: 'es-CO',
    timezoneId: 'America/Bogota',
    trace: 'retain-on-failure',
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    { name: 'firefox', use: { ...devices['Desktop Firefox'] } },
    { name: 'webkit', use: { ...devices['Desktop Safari'] } },
  ],
})
