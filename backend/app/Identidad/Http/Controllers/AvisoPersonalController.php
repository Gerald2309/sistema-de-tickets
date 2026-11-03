<?php

namespace App\Identidad\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Identidad\Http\Resources\AvisoPersonalResource;
use App\Identidad\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Date;

// Los avisos de la campanita de quien pregunta: solo los suyos, nunca los de otra persona.
class AvisoPersonalController extends Controller
{
    private const RECIENTES = 30;

    /**
     * Mis avisos
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $persona */
        $persona = $request->user();

        return AvisoPersonalResource::collection($persona->notifications()->limit(self::RECIENTES)->get())
            ->additional(['meta' => ['sin_leer' => $persona->unreadNotifications()->count()]]);
    }

    /**
     * Marcar un aviso como leído
     */
    public function marcarLeido(Request $request, string $aviso): Response
    {
        /** @var User $persona */
        $persona = $request->user();
        $persona->notifications()->findOrFail($aviso)->markAsRead();

        return response()->noContent();
    }

    /**
     * Marcar todos mis avisos como leídos
     */
    public function marcarTodos(Request $request): Response
    {
        /** @var User $persona */
        $persona = $request->user();
        $persona->unreadNotifications()->update(['read_at' => Date::now()]);

        return response()->noContent();
    }
}
