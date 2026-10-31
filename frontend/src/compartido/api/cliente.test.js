import { http as manejador, HttpResponse } from 'msw'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { api, servidor } from '../../../pruebas/servidor.js'
import { conectarSesion, http } from './cliente.js'
import { ErrorDeApi } from './errores.js'

describe('cliente HTTP', () => {
  afterEach(() => conectarSesion({ obtenerToken: () => null, alExpirar: () => {} }))

  it('añade el token de la sesión a las peticiones del mismo origen', async () => {
    let autorizacion = null
    servidor.use(
      manejador.get(api('me'), ({ request }) => {
        autorizacion = request.headers.get('Authorization')
        return HttpResponse.json({ data: {} })
      }),
    )
    conectarSesion({ obtenerToken: () => 'token-secreto', alExpirar: () => {} })

    await http.get('me')

    expect(autorizacion).toBe('Bearer token-secreto')
  })

  it('nunca envía el token a otro origen', async () => {
    conectarSesion({ obtenerToken: () => 'token-secreto', alExpirar: () => {} })
    // Se captura la petición ya preparada por los interceptores, sin salir a la red.
    let enviada
    const capturar = async (configuracion) => {
      enviada = configuracion
      return { data: {}, status: 200, statusText: 'OK', headers: {}, config: configuracion }
    }

    await http.get('https://otro-sitio.example/datos', { adapter: capturar })
    expect(enviada.headers.get('Authorization')).toBeUndefined()

    await http.get('me', { adapter: capturar })
    expect(enviada.headers.get('Authorization')).toBe('Bearer token-secreto')
  })

  it('convierte las respuestas de error en ErrorDeApi', async () => {
    servidor.use(
      manejador.get(api('incidentes/9'), () =>
        HttpResponse.json(
          { status: 404, title: 'Not Found', detail: 'El recurso no existe o no está disponible.' },
          { status: 404 },
        ),
      ),
    )

    const error = await http.get('incidentes/9').catch((causa) => causa)

    expect(error).toBeInstanceOf(ErrorDeApi)
    expect(error.estado).toBe(404)
    expect(error.detalle).toBe('El recurso no existe o no está disponible.')
  })

  it('un 401 con sesión abierta avisa a la sesión; sin sesión no', async () => {
    servidor.use(manejador.get(api('me'), () => HttpResponse.json({ detail: 'Falta la credencial.' }, { status: 401 })))
    const alExpirar = vi.fn()

    conectarSesion({ obtenerToken: () => null, alExpirar })
    await http.get('me').catch(() => {})
    expect(alExpirar).not.toHaveBeenCalled()

    conectarSesion({ obtenerToken: () => 'vencido', alExpirar })
    await http.get('me').catch(() => {})
    expect(alExpirar).toHaveBeenCalledTimes(1)
  })

  it('sin conexión responde un ErrorDeApi con estado 0', async () => {
    servidor.use(manejador.get(api('catalogos'), () => HttpResponse.error()))

    const error = await http.get('catalogos').catch((causa) => causa)

    expect(error).toBeInstanceOf(ErrorDeApi)
    expect(error.sinConexion).toBe(true)
  })
})
