import { createVuetify } from 'vuetify'
import { aliases, mdi } from 'vuetify/iconsets/mdi-svg'
import { es } from 'vuetify/locale'
import 'vuetify/styles'

/**
 * Tema `psicol` del sistema de diseño (F-04). Los valores coinciden con compartido/estilos/tokens.css.
 * Íconos SVG individuales (@mdi/js): solo los usados entran al paquete (RNF-F02).
 * @returns {ReturnType<typeof createVuetify>}
 */
export function crearVuetify() {
  return createVuetify({
    locale: { locale: 'es', messages: { es } },
    icons: { defaultSet: 'mdi', aliases, sets: { mdi } },
    theme: {
      defaultTheme: 'psicol',
      themes: {
        psicol: {
          dark: false,
          colors: {
            primary: '#007DA3',
            secondary: '#FF9429',
            info: '#00A4D6',
            success: '#5E8C10',
            warning: '#B45309',
            error: '#B42318',
            background: '#F3F5F7',
            surface: '#FFFFFF',
          },
        },
      },
    },
    defaults: {
      VBtn: { elevation: 0, class: 'text-none' },
      VTextField: { variant: 'outlined', density: 'comfortable', color: 'primary' },
      VTextarea: { variant: 'outlined', density: 'comfortable', color: 'primary' },
      VSelect: { variant: 'outlined', density: 'comfortable', color: 'primary' },
      VAutocomplete: { variant: 'outlined', density: 'comfortable', color: 'primary' },
      VCard: { elevation: 0 },
      VTable: { density: 'compact' },
      VList: { density: 'compact' },
    },
  })
}
