/**
 * API pública del módulo de automatizaciones: lo único que otros módulos y el núcleo pueden importar de él
 * (arquitectura §3.3; lo vigila pruebas/unitarias/arquitectura.test.js).
 */
import { iconos } from '@/compartido/iconos.js'

export { rutas } from './rutas.js'

/** @type {import('@/modulos/identidad/index.js').EntradaDeNavegacion[]} */
export const navegacion = [
  {
    titulo: 'Automatizaciones',
    icono: iconos.automatizaciones,
    destino: { name: 'automatizaciones' },
    permisos: ['administrar_automatizaciones'],
    grupo: 'administracion',
  },
]
