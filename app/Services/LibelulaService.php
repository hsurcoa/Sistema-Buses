<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente de la API de Libélula (pasarela de pagos boliviana), segun su
 * "Guía de Integración para Empresas" v2.145. Solo se implementan los
 * servicios que este sistema realmente usa: registrar una deuda (para
 * mostrar el QR de cobro al pasajero) y consultar pagos (conciliacion
 * manual, ya que el callback de confirmacion necesita una URL publica que
 * un XAMPP local no tiene).
 *
 * IMPORTANTE (segun el manual): no existe una URL de "sandbox" separada.
 * Las pruebas se hacen contra la MISMA url de produccion, usando el
 * appkey de pruebas que asigna Libelula. Por eso $baseUrl es fijo.
 */
class LibelulaService
{
    private const BASE_URL = 'https://api.libelula.bo';

    public function __construct(private ConfiguracionService $config) {}

    public function estaActivo(): bool
    {
        $cfg = $this->config->obtenerConfiguracion();

        return ! empty($cfg['libelula_activo']) && ! empty($cfg['libelula_appkey']);
    }

    private function appkey(): ?string
    {
        return $this->config->obtenerConfiguracion()['libelula_appkey'] ?? null;
    }

    /**
     * Registra la "deuda" de un boleto en Libélula. Devuelve un array
     * normalizado; nunca lanza excepcion (los llamadores no deben caerse
     * porque la pasarela externa tenga un mal dia).
     *
     * @return array{ok: bool, mensaje: string, id_transaccion: ?string, url_pasarela_pagos: ?string, qr_simple_url: ?string, crudo: array}
     */
    public function registrarDeuda(array $datos): array
    {
        $appkey = $this->appkey();
        if (! $appkey) {
            return ['ok' => false, 'mensaje' => 'Libélula no tiene AppKey configurado.', 'id_transaccion' => null, 'url_pasarela_pagos' => null, 'qr_simple_url' => null, 'crudo' => []];
        }

        $payload = array_filter([
            'appkey' => $appkey,
            'email_cliente' => $datos['email_cliente'] ?: 'pasajero@sinemail.local',
            'identificador' => $datos['identificador'],
            'callback_url' => $datos['callback_url'],
            'descripcion' => $datos['descripcion'] ?? null,
            'nombre_cliente' => $datos['nombre_cliente'] ?? null,
            'apellido_cliente' => $datos['apellido_cliente'] ?? null,
            'moneda' => 'BOB',
            'lineas_detalle_deuda' => $datos['lineas_detalle_deuda'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        try {
            $resp = Http::timeout(15)->post(self::BASE_URL.'/rest/deuda/registrar', $payload);
            $body = $resp->json() ?? [];

            $huboError = ! $resp->successful() || ! empty($body['error']);

            return [
                'ok' => ! $huboError,
                'mensaje' => $body['mensaje'] ?? ($huboError ? 'Libélula rechazó el registro de la deuda.' : 'OK'),
                'id_transaccion' => $body['id_transaccion'] ?? null,
                'url_pasarela_pagos' => $body['url_pasarela_pagos'] ?? null,
                'qr_simple_url' => $body['qr_simple_url'] ?? null,
                'crudo' => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('LibelulaService::registrarDeuda: '.$e->getMessage());

            return ['ok' => false, 'mensaje' => 'No se pudo conectar con Libélula: '.$e->getMessage(), 'id_transaccion' => null, 'url_pasarela_pagos' => null, 'qr_simple_url' => null, 'crudo' => []];
        }
    }

    /** Lista de deudas pagadas entre dos fechas (formato 'Y-m-d H:i:s'). Para conciliacion manual. */
    public function consultarPagos(string $fechaInicial, string $fechaFinal): array
    {
        $appkey = $this->appkey();
        if (! $appkey) {
            return ['ok' => false, 'mensaje' => 'Libélula no tiene AppKey configurado.', 'pagos' => []];
        }

        try {
            $resp = Http::timeout(15)->post(self::BASE_URL.'/rest/deuda/consultar_pagos', [
                'appkey' => $appkey,
                'fecha_inicial' => $fechaInicial,
                'fecha_final' => $fechaFinal,
            ]);
            $body = $resp->json() ?? [];

            return [
                'ok' => $resp->successful() && empty($body['error']),
                'mensaje' => $body['mensaje'] ?? '',
                'pagos' => $body['datos'] ?? [],
            ];
        } catch (\Throwable $e) {
            Log::error('LibelulaService::consultarPagos: '.$e->getMessage());

            return ['ok' => false, 'mensaje' => 'No se pudo conectar con Libélula: '.$e->getMessage(), 'pagos' => []];
        }
    }

    /** Anula (en caja) un pago ya registrado. Usado si se cancela un boleto que se pagó vía Libélula. */
    public function anularPago(string $identificador, float $montoPagado, ?string $motivo = null): array
    {
        $appkey = $this->appkey();
        if (! $appkey) {
            return ['ok' => false, 'mensaje' => 'Libélula no tiene AppKey configurado.'];
        }

        try {
            $resp = Http::timeout(15)->post(self::BASE_URL.'/rest/deuda/anular_pagos', array_filter([
                'appkey' => $appkey,
                'identificador' => $identificador,
                'monto_pagado' => $montoPagado,
                'motivo' => $motivo,
            ]));
            $body = $resp->json() ?? [];

            return [
                'ok' => $resp->successful() && ! empty($body['proceso_exitoso']),
                'mensaje' => $body['mensaje'] ?? '',
            ];
        } catch (\Throwable $e) {
            Log::error('LibelulaService::anularPago: '.$e->getMessage());

            return ['ok' => false, 'mensaje' => 'No se pudo conectar con Libélula: '.$e->getMessage()];
        }
    }
}
