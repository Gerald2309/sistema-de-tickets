<?php

namespace App\Http\Errores;

use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;

// La documentación describe los errores como los entrega ProblemaHttp (RFC 9457), no con el formato por defecto
// de Laravel: el contrato publicado tiene que coincidir con lo que responde la API.
final class DocumentarProblemas
{
    public const TIPO = 'application/problem+json';

    public function __invoke(OpenApi $openApi): void
    {
        foreach ($openApi->components->responses as $respuesta) {
            if (! $respuesta instanceof Response) {
                continue;
            }

            $conCampos = isset($respuesta->content['application/json'])
                && $respuesta->code === 422;

            $respuesta->content = [self::TIPO => Schema::fromType(self::problema($conCampos))];
        }
    }

    private static function problema(bool $conCampos): ObjectType
    {
        $problema = (new ObjectType)
            ->addProperty('type', (new StringType)->setDescription('URN que identifica el tipo de problema.'))
            ->addProperty('title', new StringType)
            ->addProperty('status', new IntegerType)
            ->addProperty('detail', (new StringType)->setDescription('Explicación para la persona.'))
            ->setRequired(['type', 'title', 'status', 'detail']);

        if ($conCampos) {
            $problema->addProperty(
                'errors',
                (new ObjectType)
                    ->setDescription('Los mensajes de cada campo que no pasó la validación.')
                    ->additionalProperties((new ArrayType)->setItems(new StringType)),
            );
        }

        return $problema;
    }
}
