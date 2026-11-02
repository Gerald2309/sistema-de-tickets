import { iconos } from '@/compartido/iconos.js'

/**
 * Nombre e ícono de cada acción auditada (sistema de diseño, Auditoría). Los valores son los de la API; una
 * prueba falla si el backend agrega una acción sin presentación.
 * @type {Readonly<Record<string, { nombre: string, icono: string, alerta?: boolean }>>}
 */
export const ACCIONES = Object.freeze({
  login_fallido: { nombre: 'Inicio de sesión fallido', icono: iconos.loginFallido, alerta: true },
  contrasena_cambiada: { nombre: 'Cambio de contraseña', icono: iconos.contrasenaCambiada },
  contrasena_restablecida: { nombre: 'Contraseña restablecida', icono: iconos.contrasenaRestablecida },
  usuario_creado: { nombre: 'Usuario creado', icono: iconos.usuarioCreado },
  usuario_modificado: { nombre: 'Usuario modificado', icono: iconos.usuarioModificado },
  usuario_desactivado: { nombre: 'Usuario desactivado', icono: iconos.usuarioDesactivado },
  usuario_reactivado: { nombre: 'Usuario reactivado', icono: iconos.usuarioReactivado },
  rol_asignado: { nombre: 'Rol asignado', icono: iconos.rolAsignado },
  rol_creado: { nombre: 'Rol creado', icono: iconos.rolCreado },
  rol_renombrado: { nombre: 'Rol renombrado', icono: iconos.rolRenombrado },
  rol_eliminado: { nombre: 'Rol eliminado', icono: iconos.rolEliminado },
  permiso_concedido: { nombre: 'Permiso concedido', icono: iconos.permisoConcedido },
  permiso_retirado: { nombre: 'Permiso retirado', icono: iconos.permisoRetirado },
  cliente_creado: { nombre: 'Empresa creada', icono: iconos.empresaCreada },
  cliente_modificado: { nombre: 'Empresa modificada', icono: iconos.empresaModificada },
  acuerdo_de_servicio_modificado: { nombre: 'SLA modificado', icono: iconos.acuerdoModificado },
  alerta_de_seguridad: { nombre: 'Alerta de seguridad', icono: iconos.alertaDeSeguridad, alerta: true },
})

/**
 * @param {string} accion
 * @returns {{ nombre: string, icono: string, alerta?: boolean }}
 */
export function presentarAccion(accion) {
  return ACCIONES[accion] ?? { nombre: accion, icono: iconos.info }
}
