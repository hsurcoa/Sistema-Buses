<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Puente hacia el sistema legacy (patron strangler fig).
 *
 * Unico componente que "sabe" del sistema viejo: toma cualquier request que
 * no coincida con una ruta ya migrada a Laravel y la reenvia al legacy,
 * dejando que legacy/app/core/App.php la despache tal cual lo hace hoy
 * (mismo controlador/modelo/vista legacy, sin cambios).
 *
 * Ver docs/superpowers/specs/2026-09-13-migracion-laravel-design.md (3.2)
 * y docs/superpowers/plans/2026-09-13-migracion-laravel-fase1-plan.md (Tarea 7).
 *
 * DECISION DE IMPLEMENTACION (importante, ver seccion 9 del spec):
 * El diseño original preveia un `require` directo de legacy/public/index.php
 * DENTRO del mismo proceso PHP que ya bootstrapeo Laravel. Verificado
 * empiricamente que eso provoca una falla nativa silenciosa bajo
 * Apache/mod_php en Windows (la respuesta llega con body vacio, sin
 * excepcion ni warning de PHP capturable). La via `php artisan serve` (CLI)
 * SI funciona con el mismo codigo.
 *
 * Por eso este bridge hace una peticion HTTP interna (loopback, mismo
 * Apache) a `/__legacy` en vez de un require en proceso: el legacy se
 * ejecuta en un hilo de Apache aparte, sin nada de Laravel cargado. La ruta
 * `/__legacy` (ver .htaccess raiz) solo se acepta desde 127.0.0.1/::1 y
 * apunta directo a legacy/, sin pasar por Laravel.
 *
 * La ruta que usa este controller (routes/legacy.php) se registra FUERA del
 * grupo de middleware `web`: sin sesion/CSRF/cifrado de cookies de Laravel,
 * porque el legacy tiene su propia sesion nativa y su propio CSRF, y las
 * cookies deben viajar intactas en ambos sentidos.
 */
class LegacyBridgeController extends Controller
{
    /**
     * Scripts sueltos que Apache servia como archivos reales antes de la
     * migracion (sin pasar por App.php). Se piden por su propio nombre de
     * archivo dentro de legacy/. login.php NO esta aqui a proposito: ese
     * endpoint ya lo maneja Laravel (routes/auth.php, Tarea 8).
     */
    private const LOOSE_SCRIPTS = [
        'ajax_info_viaje.php',
        'ajax_mapa.php',
        'logout.php',
        'logout_force.php',
        'public/print_ticket.php',
        'public/clear_cache.php',
        'public/diagnostico_caja.php',
        'public/diagnostico_rutas.php',
    ];

    /** Headers del request original que NO se reenvian tal cual. */
    private const SKIP_REQUEST_HEADERS = [
        'host', 'content-length', 'content-type', 'connection', 'accept-encoding',
        'cookie', 'expect', 'transfer-encoding', 'x-forwarded-for',
        'x-forwarded-host', 'x-forwarded-proto',
    ];

    /** Headers de la respuesta legacy que no deben copiarse de vuelta. */
    private const SKIP_RESPONSE_HEADERS = [
        'transfer-encoding', 'connection', 'content-length', 'content-encoding', 'keep-alive',
    ];

    public function handle(Request $request): Response
    {
        // Un reporte/backup legacy lento no debe cortarse aqui antes que alla.
        $timeout = (int) config('legacy.timeout', 300);
        @set_time_limit($timeout + 10);

        $path = trim($request->path(), '/');

        $query = $request->query();
        if (in_array($path, self::LOOSE_SCRIPTS, true)) {
            $target = $path;
        } else {
            // Reproduce legacy/public/.htaccess: index.php?url=$1 [QSA]
            $target = 'public/index.php';
            if ($path !== '') {
                $query['url'] = $path;
            } else {
                unset($query['url']);
            }
        }

        $internalUrl = $this->internalBase($request).'/__legacy/'.$target;
        if ($query) {
            $internalUrl .= '?'.http_build_query($query);
        }

        $pending = Http::withHeaders($this->forwardHeaders($request))
            ->withOptions([
                // Los redirects del legacy (p. ej. a login.php) deben llegar tal
                // cual al navegador, no seguirse aqui.
                'allow_redirects' => false,
                // Se reenvia el cuerpo tal cual lo manda el legacy (sin
                // descomprimir/recomprimir).
                'decode_content' => false,
                'http_errors' => false,
            ])
            ->timeout($timeout)
            ->connectTimeout(5);

        $method = strtoupper($request->method());

        try {
            if (in_array($method, ['GET', 'HEAD'], true)) {
                $legacyResponse = $pending->send($method, $internalUrl);
            } elseif ($this->isMultipart($request)) {
                $legacyResponse = $pending->asMultipart()
                    ->send($method, $internalUrl, ['multipart' => $this->multipartParts($request)]);
            } else {
                // Cuerpo crudo: el legacy lee JSON con file_get_contents('php://input')
                // (Ventas, Boletos, Admin...) ademas de $_POST para formularios.
                $legacyResponse = $pending
                    ->withBody($request->getContent(), $request->header('Content-Type', 'application/x-www-form-urlencoded'))
                    ->send($method, $internalUrl);
            }
        } catch (ConnectionException $e) {
            Log::error('LegacyBridge: no se pudo contactar al legacy', [
                'url' => $internalUrl,
                'error' => $e->getMessage(),
            ]);

            return response('El sistema no respondio a tiempo. Intente nuevamente.', 504);
        }

        return $this->relay($legacyResponse);
    }

    /**
     * Base interna siempre por loopback (el .htaccess raiz solo acepta
     * /__legacy desde 127.0.0.1/::1), aunque el usuario haya entrado por IP
     * de red o nombre de host.
     */
    private function internalBase(Request $request): string
    {
        $configured = config('legacy.internal_url');
        if ($configured) {
            return rtrim($configured, '/');
        }

        $port = $request->getPort();
        $portPart = in_array($port, [80, null], true) ? '' : ':'.$port;

        return 'http://127.0.0.1'.$portPart.$request->getBasePath();
    }

    private function forwardHeaders(Request $request): array
    {
        $headers = [];
        foreach ($request->headers->all() as $name => $values) {
            if (in_array(strtolower($name), self::SKIP_REQUEST_HEADERS, true)) {
                continue;
            }
            // Symfony guarda los nombres en minusculas; se reenvian en su forma canonica.
            $headers[str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)))] = implode(', ', $values);
        }

        // Cookie cruda del navegador (incluye SISTEMA_TRANSPORTES_SESSION), sin
        // pasar por el parseo/descifrado de Laravel.
        $cookie = $request->server('HTTP_COOKIE');
        if ($cookie) {
            $headers['Cookie'] = $cookie;
        }

        // Mismo Host que vio el navegador, por si el legacy lo usa ($_SERVER['HTTP_HOST']).
        $headers['Host'] = $request->getHttpHost();
        $headers['X-Forwarded-For'] = $request->ip();
        $headers['X-Forwarded-Proto'] = $request->getScheme();

        return $headers;
    }

    private function isMultipart(Request $request): bool
    {
        return str_starts_with(strtolower((string) $request->header('Content-Type')), 'multipart/form-data');
    }

    /**
     * PHP no deja el cuerpo crudo multipart en php://input, asi que se
     * reconstruye: campos desde $_POST (sin los TrimStrings/ConvertEmptyStrings
     * de Laravel) y archivos desde allFiles(), respetando nombres anidados
     * (p. ej. "fotos[]" o "datos[logo]"). Se arma el arreglo de partes a mano
     * en vez de usar attach(), que descarta los campos con valor vacio.
     */
    private function multipartParts(Request $request): array
    {
        $parts = [];
        foreach ($this->flatten($_POST) as [$name, $value]) {
            $parts[] = ['name' => $name, 'contents' => (string) $value];
        }

        foreach ($this->flattenFiles($request->allFiles()) as [$name, $file]) {
            if (! $file->isValid()) {
                continue;
            }
            $parts[] = [
                'name' => $name,
                'contents' => fopen($file->getRealPath(), 'r'),
                'filename' => $file->getClientOriginalName(),
                'headers' => ['Content-Type' => $file->getClientMimeType()],
            ];
        }

        return $parts;
    }

    /** @return list<array{0:string,1:mixed}> */
    private function flatten(array $data, string $prefix = ''): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $name = $prefix === '' ? (string) $key : $prefix.'['.$key.']';
            if (is_array($value)) {
                array_push($out, ...$this->flatten($value, $name));
            } else {
                $out[] = [$name, $value];
            }
        }

        return $out;
    }

    /** @return list<array{0:string,1:\Illuminate\Http\UploadedFile}> */
    private function flattenFiles(array $files, string $prefix = ''): array
    {
        $out = [];
        foreach ($files as $key => $value) {
            $name = $prefix === '' ? (string) $key : $prefix.'['.$key.']';
            if (is_array($value)) {
                array_push($out, ...$this->flattenFiles($value, $name));
            } elseif ($value) {
                $out[] = [$name, $value];
            }
        }

        return $out;
    }

    private function relay(ClientResponse $legacyResponse): Response
    {
        $response = response($legacyResponse->body(), $legacyResponse->status());

        foreach ($legacyResponse->headers() as $name => $values) {
            $lower = strtolower($name);
            if (in_array($lower, self::SKIP_RESPONSE_HEADERS, true)) {
                continue;
            }
            $response->headers->remove($name);
            foreach ((array) $values as $value) {
                $response->headers->set($name, $value, false);
            }
        }

        return $response;
    }
}
