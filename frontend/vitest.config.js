import { defineConfig, mergeConfig } from 'vitest/config'
import configuracionVite from './vite.config.js'

export default mergeConfig(
  configuracionVite,
  defineConfig({
    test: {
      environment: 'happy-dom',
      include: ['src/**/*.test.js', 'pruebas/unitarias/**/*.test.js'],
      setupFiles: ['pruebas/configuracion.js'],
      // Vuetify se compila con la aplicación en lugar de cargarse como dependencia externa.
      server: { deps: { inline: ['vuetify'] } },
      restoreMocks: true,
      coverage: {
        provider: 'v8',
        include: ['src/compartido/**', 'src/**/composables/**', 'src/**/api/**', 'src/**/tienda/**'],
        exclude: ['**/*.test.js'],
        // RNF-F30: al menos el 80 % de las líneas de lo compartido, los composables, la API y los stores.
        thresholds: { lines: 80 },
      },
    },
  }),
)
