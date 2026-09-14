<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * El puente al legacy es por donde pasa hoy TODO el trafico de negocio
 * (ventas, caja, admin...). Estos tests no tocan la BD ni el legacy real:
 * verifican con Http::fake() que el request interno se arma igual que lo
 * esperaba legacy/public/.htaccess y que la respuesta vuelve intacta.
 */
class LegacyBridgeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_get_se_traduce_a_index_php_con_url_y_query_string(): void
    {
        Http::fake(['*' => Http::response('<html>ventas</html>', 200)]);

        $this->call('GET', '/ventas/seleccionar/5', ['fecha' => '2026-09-13'], [], [], [
            'HTTP_COOKIE' => 'SISTEMA_TRANSPORTES_SESSION=abc123',
        ])->assertOk()->assertSee('ventas', false);

        Http::assertSent(function (ClientRequest $request) {
            return $request->method() === 'GET'
                && str_starts_with($request->url(), 'http://127.0.0.1/__legacy/public/index.php?')
                && $request['url'] === 'ventas/seleccionar/5'
                && $request['fecha'] === '2026-09-13'
                && $request->header('Cookie') === ['SISTEMA_TRANSPORTES_SESSION=abc123'];
        });
    }

    public function test_raiz_va_al_dashboard_legacy_sin_parametro_url(): void
    {
        Http::fake(['*' => Http::response('dashboard', 200)]);

        $this->get('/')->assertOk();

        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'http://127.0.0.1/__legacy/public/index.php');
    }

    public function test_scripts_sueltos_se_piden_por_su_nombre_de_archivo(): void
    {
        Http::fake(['*' => Http::response('{}', 200, ['Content-Type' => 'application/json'])]);

        $this->get('/ajax_mapa.php?viaje=3')->assertOk();
        $this->get('/public/print_ticket.php?id=9')->assertOk();

        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'http://127.0.0.1/__legacy/ajax_mapa.php?viaje=3');
        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'http://127.0.0.1/__legacy/public/print_ticket.php?id=9');
    }

    public function test_post_json_llega_crudo_para_php_input_y_sin_csrf_de_laravel(): void
    {
        Http::fake(['*' => Http::response('{"success":true}', 200)]);

        $this->call('POST', '/ventas/procesar_venta', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ], '{"viaje_id":1,"asientos":[3,4]}')->assertOk();

        Http::assertSent(function (ClientRequest $request) {
            return $request->method() === 'POST'
                && $request->body() === '{"viaje_id":1,"asientos":[3,4]}'
                && $request->header('Content-Type') === ['application/json']
                && $request->header('X-Requested-With') === ['XMLHttpRequest'];
        });
    }

    public function test_post_de_formulario_conserva_valores_vacios_y_espacios(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);

        $this->call('POST', '/caja/abrir', [], [], [], [
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        ], 'monto_inicial=100&observacion=&nota=+hola+')->assertOk();

        Http::assertSent(fn (ClientRequest $request) => $request->body() === 'monto_inicial=100&observacion=&nota=+hola+');
    }

    public function test_multipart_reenvia_campos_y_archivos(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);

        $_POST = ['nombre' => 'Bus 1', 'extra' => ['a' => '1'], 'vacio' => ''];
        try {
            $this->call('POST', '/admin/guardar_bus', $_POST, [], [
                'foto' => UploadedFile::fake()->image('bus.jpg'),
            ], ['CONTENT_TYPE' => 'multipart/form-data; boundary=x'])->assertOk();
        } finally {
            $_POST = [];
        }

        Http::assertSent(function (ClientRequest $request) {
            $parts = collect($request->data())->keyBy('name');

            return $request->isMultipart()
                && $parts['nombre']['contents'] === 'Bus 1'
                && $parts['extra[a]']['contents'] === '1'
                && $parts['vacio']['contents'] === ''
                && $parts['foto']['filename'] === 'bus.jpg';
        });
    }

    public function test_respuesta_legacy_se_devuelve_con_redirect_y_cookies(): void
    {
        Http::fake(['*' => Http::response('', 302, [
            'Location' => 'http://localhost/venta-pasajes/login.php',
            'Set-Cookie' => [
                'SISTEMA_TRANSPORTES_SESSION=nuevo; path=/; HttpOnly',
                'otra=1; path=/',
            ],
            'Content-Encoding' => 'gzip',
        ])]);

        $response = $this->get('/caja');

        $response->assertStatus(302)
            ->assertRedirect('http://localhost/venta-pasajes/login.php')
            ->assertCookie('SISTEMA_TRANSPORTES_SESSION', 'nuevo', false)
            ->assertCookie('otra', '1', false)
            ->assertHeaderMissing('Content-Encoding');
    }

    public function test_si_el_legacy_no_responde_devuelve_504(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout'));

        $this->get('/reportes')->assertStatus(504);
    }
}
