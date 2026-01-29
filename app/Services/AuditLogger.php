<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Registra una acción en el log con formato detallado.
     */
    public static function log($tipo, $nivel, $descripcion, $exception = null)
    {
        try {
            $context = [
                'tipo' => strtoupper($tipo),
                'nivel' => $nivel,
                'ubicacion' => Request::fullUrl(),
                'ip' => Request::ip(),
            ];

            if ($exception) {
                $context['error_msg'] = $exception->getMessage();
                $context['archivo'] = $exception->getFile().':'.$exception->getLine();
            }

            // El LogContextMiddleware ya inyecta 'env', 'release' y 'user_id' al contexto global
            Log::info("AUDIT: $descripcion", $context);

        } catch (\Exception $e) {
            Log::error('Error crítico al intentar registrar log de auditoría: '.$e->getMessage());
        }
    }

    // Helpers para acciones comunes
    public static function consulta($descripcion)
    {
        self::log('CONSULTA', 'info', $descripcion);
    }

    public static function insercion($descripcion, $datos = null)
    {
        $desc = $descripcion;
        if ($datos) {
            $desc .= ' | Datos: '.json_encode($datos);
        }
        self::log('INSERCIÓN', 'success', $desc);
    }

    public static function actualizacion($descripcion, $cambios = null)
    {
        $desc = $descripcion;
        if ($cambios) {
            $desc .= ' | Cambios: '.json_encode($cambios);
        }
        self::log('ACTUALIZACIÓN', 'info', $desc);
    }

    public static function eliminacion($descripcion, $id = null)
    {
        $desc = $descripcion;
        if ($id) {
            $desc .= ' | ID Eliminado: '.$id;
        }
        self::log('ELIMINACIÓN', 'warning', $desc);
    }

    public static function error($descripcion, $exception = null)
    {
        self::log('ERROR', 'error', $descripcion, $exception);
    }

    public static function login($descripcion)
    {
        self::log('LOGIN', 'info', $descripcion);
    }
}
