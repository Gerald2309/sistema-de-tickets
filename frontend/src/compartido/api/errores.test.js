import { describe, expect, it } from 'vitest'
import { aErrorDeApi, ErrorDeApi } from './errores.js'

/** Respuesta de Axios con un problema RFC 9457 como el que devuelve el backend. */
const conRespuesta = (status, data, headers = {}) => ({ response: { status, data, headers } })

describe('aErrorDeApi', () => {
  it('toma los campos del problema RFC 9457', () => {
    const error = aErrorDeApi(
      conRespuesta(422, {
        type: 'urn:incidentes:problema:validacion',
        title: 'Error de validación',
        status: 422,
        detail: 'Los datos enviados no son válidos.',
        errors: { titulo: ['El título es obligatorio.'] },
      }),
    )

    expect(error).toBeInstanceOf(ErrorDeApi)
    expect(error.estado).toBe(422)
    expect(error.tipo).toBe('urn:incidentes:problema:validacion')
    expect(error.esValidacion).toBe(true)
    expect(error.erroresDe('titulo')).toEqual(['El título es obligatorio.'])
    expect(error.erroresDe('descripcion')).toEqual([])
  })

  it('conserva los estados permitidos de una transición rechazada', () => {
    const error = aErrorDeApi(
      conRespuesta(422, { detail: 'No se puede pasar de abierto a cerrado.', estados_permitidos: ['en_progreso'] }),
    )

    expect(error.estadosPermitidos).toEqual(['en_progreso'])
    expect(error.esValidacion).toBe(false)
  })

  it('lee la espera de Retry-After en un 429', () => {
    expect(
      aErrorDeApi(conRespuesta(429, { detail: 'Demasiadas peticiones.' }, { 'retry-after': '42' })).reintentarEn,
    ).toBe(42)
    expect(
      aErrorDeApi(conRespuesta(429, { detail: 'Demasiadas peticiones.' }, { 'retry-after': 'mañana' })).reintentarEn,
    ).toBeNull()
  })

  it('sin respuesta del servidor es un error de conexión', () => {
    const sinRed = aErrorDeApi({ code: 'ERR_NETWORK' })
    const agotado = aErrorDeApi({ code: 'ECONNABORTED' })

    expect(sinRed.sinConexion).toBe(true)
    expect(sinRed.titulo).toBe('Sin conexión')
    expect(agotado.estado).toBe(0)
    expect(agotado.titulo).toBe('Tiempo agotado')
  })

  it('un error sin cuerpo propio (por ejemplo, del proxy) nunca muestra detalles técnicos', () => {
    const error = aErrorDeApi(conRespuesta(502, '<html>Bad Gateway nginx/1.27</html>'))

    expect(error.detalle).toBe('Ocurrió un error inesperado.')
    expect(error.detalle).not.toContain('nginx')
  })

  it('no vuelve a convertir un ErrorDeApi', () => {
    const original = new ErrorDeApi({ estado: 403, titulo: 'Prohibido', detalle: 'No tiene permiso.' })

    expect(aErrorDeApi(original)).toBe(original)
  })
})
