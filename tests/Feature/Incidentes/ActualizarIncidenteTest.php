<?php

namespace Tests\Feature\Incidentes;

use App\Incidentes\Dominio\CampoHistorial;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\EventoHistorial;
use App\Incidentes\Models\HistorialIncidente;
use App\Incidentes\Models\Incidente;
use Tests\TestCase;

class ActualizarIncidenteTest extends TestCase
{
    private function ruta(Incidente $incidente): string
    {
        return "/api/v1/incidentes/{$incidente->id}";
    }

    public function test_actualiza_varios_campos_y_registra_cada_cambio_en_el_historial(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteDe($this->crearUsuario());

        $this->conToken($this->iniciarSesion($soporte))->patchJson($this->ruta($incidente), [
            'titulo' => 'VPN caída para todo el equipo',
            'prioridad' => 'alta',
            'categoria' => 'red',
            'fecha_vencimiento' => '2031-02-01T09:00:00-05:00',
        ])
            ->assertOk()
            ->assertJsonPath('titulo', 'VPN caída para todo el equipo')
            ->assertJsonPath('prioridad', 'alta')
            ->assertJsonPath('categoria', 'red')
            ->assertJsonPath('fecha_vencimiento', '2031-02-01T14:00:00Z');

        $entradas = HistorialIncidente::where('incidente_id', $incidente->id)->orderBy('id')->get();

        $this->assertSame(
            [CampoHistorial::Titulo, CampoHistorial::Prioridad, CampoHistorial::FechaVencimiento, CampoHistorial::Categoria],
            $entradas->pluck('campo')->all(),
        );
        $this->assertTrue($entradas->every(fn ($entrada) => $entrada->evento === EventoHistorial::Modificacion));
        $this->assertTrue($entradas->every(fn ($entrada) => $entrada->usuario_id === $soporte->id));
        $this->assertSame(['media', 'alta'], [$entradas[1]->valor_anterior, $entradas[1]->valor_nuevo]);
        $this->assertSame('2031-02-01 14:00:00', $entradas[2]->valor_nuevo);
    }

    public function test_cambiar_el_estado_registra_el_paso_y_actualiza_los_siguientes(): void
    {
        $incidente = $this->incidenteDe($this->crearUsuario());
        $token = $this->iniciarSesion($this->crearUsuario('Soporte N1'));

        $this->conToken($token)->patchJson($this->ruta($incidente), ['estado' => 'en_progreso'])
            ->assertOk()
            ->assertJsonPath('estado', 'en_progreso')
            ->assertJsonPath('estados_siguientes', ['resuelto']);

        $entrada = HistorialIncidente::where('incidente_id', $incidente->id)->sole();

        $this->assertSame(CampoHistorial::Estado, $entrada->campo);
        $this->assertSame(['abierto', 'en_progreso'], [$entrada->valor_anterior, $entrada->valor_nuevo]);
    }

    public function test_una_transicion_no_permitida_responde_422_e_indica_los_estados_permitidos(): void
    {
        $incidente = $this->incidenteDe($this->crearUsuario());
        $token = $this->iniciarSesion($this->crearUsuario('Soporte N1'));

        $this->conToken($token)->patchJson($this->ruta($incidente), ['estado' => 'resuelto'])
            ->assertUnprocessable()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'urn:incidentes:problema:transicion-invalida')
            ->assertJsonPath('estados_permitidos', ['en_progreso']);

        $this->assertSame(Estado::Abierto, $incidente->fresh()->estado);
        $this->assertSame(0, HistorialIncidente::count());
    }

    public function test_un_incidente_cerrado_no_cambia_de_estado(): void
    {
        $incidente = $this->incidenteDe($this->crearUsuario(), ['estado' => Estado::Cerrado, 'solucion' => 'Resuelto hace tiempo.']);
        $token = $this->iniciarSesion($this->crearUsuario('Soporte N1'));

        $this->conToken($token)->patchJson($this->ruta($incidente), ['estado' => 'abierto'])
            ->assertUnprocessable()
            ->assertJsonPath('estados_permitidos', [])
            ->assertJsonPath('detail', 'No se puede pasar de «cerrado» a «abierto». Estados permitidos: ninguno (estado final).');
    }

    public function test_resolver_exige_la_solucion(): void
    {
        $incidente = $this->incidenteDe($this->crearUsuario(), ['estado' => Estado::EnProgreso]);
        $token = $this->iniciarSesion($this->crearUsuario('Soporte N1'));

        $this->conToken($token)->patchJson($this->ruta($incidente), ['estado' => 'resuelto'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('solucion');

        $this->assertSame(Estado::EnProgreso, $incidente->fresh()->estado);

        $this->conToken($token)->patchJson($this->ruta($incidente), ['estado' => 'resuelto', 'solucion' => 'Se reinició el servicio.'])
            ->assertOk()
            ->assertJsonPath('estado', 'resuelto')
            ->assertJsonPath('solucion', 'Se reinició el servicio.');

        $entrada = HistorialIncidente::where('incidente_id', $incidente->id)->sole();
        $this->assertSame(CampoHistorial::Estado, $entrada->campo, 'La solución no se registra en el historial.');
    }

    public function test_al_reabrir_se_conserva_la_solucion_y_al_resolver_de_nuevo_no_hace_falta_otra(): void
    {
        $incidente = $this->incidenteDe($this->crearUsuario(), ['estado' => Estado::Resuelto, 'solucion' => 'Primera solución.']);
        $token = $this->iniciarSesion($this->crearUsuario('Soporte N1'));

        $this->conToken($token)->patchJson($this->ruta($incidente), ['estado' => 'en_progreso'])
            ->assertOk()
            ->assertJsonPath('solucion', 'Primera solución.');

        $this->conToken($token)->patchJson($this->ruta($incidente), ['estado' => 'resuelto'])
            ->assertOk()
            ->assertJsonPath('solucion', 'Primera solución.');
    }

    public function test_pedir_el_estado_actual_no_cambia_nada_ni_deja_historial(): void
    {
        $incidente = $this->incidenteDe($this->crearUsuario());
        $token = $this->iniciarSesion($this->crearUsuario('Soporte N1'));

        $this->conToken($token)->patchJson($this->ruta($incidente), ['estado' => 'abierto'])->assertOk();

        $this->assertSame(0, HistorialIncidente::count());
    }

    public function test_repetir_un_valor_igual_no_deja_historial(): void
    {
        $incidente = $this->incidenteDe($this->crearUsuario(), ['titulo' => 'Mismo título']);
        $token = $this->iniciarSesion($this->crearUsuario('Soporte N1'));

        $this->conToken($token)->patchJson($this->ruta($incidente), ['titulo' => 'Mismo título', 'prioridad' => 'media'])->assertOk();

        $this->assertSame(0, HistorialIncidente::count());
    }

    public function test_cambiar_la_descripcion_no_se_registra_en_el_historial(): void
    {
        $incidente = $this->incidenteDe($this->crearUsuario());
        $token = $this->iniciarSesion($this->crearUsuario('Soporte N1'));

        $this->conToken($token)->patchJson($this->ruta($incidente), ['descripcion' => 'Descripción ampliada con más detalle.'])
            ->assertOk()
            ->assertJsonPath('descripcion', 'Descripción ampliada con más detalle.');

        $this->assertSame(0, HistorialIncidente::count());
    }

    public function test_reasignar_y_desasignar_se_registran_con_el_id_como_texto(): void
    {
        $agente = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteDe($this->crearUsuario());
        $token = $this->iniciarSesion($this->crearUsuario('Soporte N2'));

        $this->conToken($token)->patchJson($this->ruta($incidente), ['usuario_asignado_id' => $agente->id])
            ->assertOk()
            ->assertJsonPath('asignado.id', $agente->id);

        $this->conToken($token)->patchJson($this->ruta($incidente), ['usuario_asignado_id' => null])
            ->assertOk()
            ->assertJsonPath('asignado', null);

        $entradas = HistorialIncidente::where('incidente_id', $incidente->id)->orderBy('id')->get();

        $this->assertSame([null, (string) $agente->id], [$entradas[0]->valor_anterior, $entradas[0]->valor_nuevo]);
        $this->assertSame([(string) $agente->id, null], [$entradas[1]->valor_anterior, $entradas[1]->valor_nuevo]);
        $this->assertSame(CampoHistorial::UsuarioAsignado, $entradas[1]->campo);
    }

    public function test_no_se_asigna_a_un_usuario_inactivo(): void
    {
        $inactivo = $this->crearUsuario('Soporte N1', ['activo' => false]);
        $incidente = $this->incidenteDe($this->crearUsuario());
        $token = $this->iniciarSesion($this->crearUsuario('Soporte N2'));

        $this->conToken($token)->patchJson($this->ruta($incidente), ['usuario_asignado_id' => $inactivo->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('usuario_asignado_id');
    }

    public function test_el_creador_edita_su_incidente_pero_no_el_estado_ni_el_asignado(): void
    {
        $creador = $this->crearUsuario();
        $incidente = $this->incidenteDe($creador, ['titulo' => 'Título original']);
        $token = $this->iniciarSesion($creador);

        $this->conToken($token)->patchJson($this->ruta($incidente), ['titulo' => 'Título corregido'])
            ->assertOk()
            ->assertJsonPath('titulo', 'Título corregido');

        $this->conToken($token)->patchJson($this->ruta($incidente), ['estado' => 'en_progreso'])->assertForbidden();
        $this->conToken($token)->patchJson($this->ruta($incidente), ['usuario_asignado_id' => $this->crearUsuario('Soporte N1')->id])->assertForbidden();
    }

    public function test_un_solo_campo_sin_permiso_rechaza_toda_la_peticion(): void
    {
        $creador = $this->crearUsuario();
        $incidente = $this->incidenteDe($creador, ['titulo' => 'Título original']);
        $token = $this->iniciarSesion($creador);

        $this->conToken($token)->patchJson($this->ruta($incidente), ['titulo' => 'Título nuevo', 'estado' => 'en_progreso'])
            ->assertForbidden();

        $this->assertSame('Título original', $incidente->fresh()->titulo);
    }

    public function test_sobre_un_incidente_ajeno_responde_404_aunque_el_cuerpo_sea_invalido(): void
    {
        $incidente = $this->incidenteDe($this->crearUsuario());
        $token = $this->iniciarSesion($this->crearUsuario());

        $this->conToken($token)->patchJson($this->ruta($incidente), ['prioridad' => 'inexistente'])->assertNotFound();
    }

    public function test_el_cuerpo_invalido_responde_422_cuando_si_tiene_permiso(): void
    {
        $incidente = $this->incidenteDe($this->crearUsuario());
        $token = $this->iniciarSesion($this->crearUsuario('Soporte N1'));

        $this->conToken($token)->patchJson($this->ruta($incidente), ['prioridad' => 'inexistente', 'estado' => 'volando'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['prioridad', 'estado']);
    }

    public function test_el_creador_y_el_id_no_se_pueden_modificar(): void
    {
        $creador = $this->crearUsuario();
        $incidente = $this->incidenteDe($creador);
        $token = $this->iniciarSesion($this->crearUsuario('Soporte N1'));

        $this->conToken($token)->patchJson($this->ruta($incidente), ['usuario_creador_id' => $this->crearUsuario()->id, 'id' => 9999, 'titulo' => 'Otro título'])
            ->assertOk()
            ->assertJsonPath('id', $incidente->id)
            ->assertJsonPath('creador.id', $creador->id);
    }

    public function test_un_incidente_borrado_responde_404(): void
    {
        $incidente = $this->incidenteDe($this->crearUsuario());
        $incidente->delete();
        $token = $this->iniciarSesion($this->crearUsuario('Administrador'));

        $this->conToken($token)->patchJson($this->ruta($incidente), ['titulo' => 'Nuevo'])->assertNotFound();
    }
}
