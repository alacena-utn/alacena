<?php

declare(strict_types=1);

namespace Alacena\Tests\Integracion;

/**
 * Simula un navegador para probar las páginas: guarda la cookie de sesión, no sigue
 * las redirecciones (para poder comprobar a dónde mandan) y completa solo el token CSRF.
 *
 * Uso en una prueba:
 *   $web = new Navegador();
 *   $web->ingresar('maria@alacena.test', 'Usuario1234');
 *   [$estado, $html, $destino] = $web->get('app/despensa.php');
 */
final class Navegador
{
    private string $cookie = '';
    private string $ultimoHtml = '';
    /** Otras cookies que manda siempre (por ejemplo, el color elegido sin cuenta). */
    private string $otrasCookies = '';

    public function agregarCookie(string $nombre, string $valor): void
    {
        $this->otrasCookies .= '; ' . $nombre . '=' . rawurlencode($valor);
    }

    /** @return array{0: int, 1: string, 2: string} [código HTTP, HTML, Location] */
    public function get(string $ruta): array
    {
        return $this->pedir('GET', $ruta);
    }

    /**
     * Envía un formulario. Si no se pasa 'csrf', usa el token de la última página que se pidió.
     * @param array<string, mixed> $campos
     * @return array{0: int, 1: string, 2: string}
     */
    public function post(string $ruta, array $campos): array
    {
        if (!array_key_exists('csrf', $campos) && preg_match('/name="csrf" value="([^"]+)"/', $this->ultimoHtml, $m)) {
            $campos['csrf'] = $m[1];
        }
        return $this->pedir('POST', $ruta, http_build_query($campos));
    }

    public function ingresar(string $email, string $contrasena): string
    {
        $this->get('login.php');
        return $this->post('login.php', ['email' => $email, 'contrasena' => $contrasena])[2];
    }

    /** @return array{0: int, 1: string, 2: string} */
    private function pedir(string $metodo, string $ruta, string $cuerpo = ''): array
    {
        $encabezados = ['Content-Type: application/x-www-form-urlencoded'];
        $cookies = ltrim($this->cookie . $this->otrasCookies, '; ');
        if ($cookies !== '') {
            $encabezados[] = 'Cookie: ' . $cookies;
        }
        $contexto = stream_context_create(['http' => [
            'method' => $metodo,
            'header' => implode("\r\n", $encabezados),
            'content' => $cuerpo,
            'ignore_errors' => true,
            'follow_location' => 0,
        ]]);
        $html = (string) file_get_contents(ClienteApi::base() . '/' . ltrim($ruta, '/'), false, $contexto);
        $lineas = $http_response_header ?? [];
        preg_match('#HTTP/\S+ (\d{3})#', $lineas[0] ?? '', $codigo);
        $destino = '';
        foreach ($lineas as $linea) {
            if (preg_match('/^Set-Cookie:\s*(alacena=[^;]+)/i', $linea, $c)) {
                $this->cookie = $c[1];
            }
            if (preg_match('/^Location:\s*(.+)$/i', $linea, $l)) {
                $destino = trim($l[1]);
            }
        }
        $this->ultimoHtml = $html !== '' ? $html : $this->ultimoHtml;
        return [(int) ($codigo[1] ?? 0), $html, $destino];
    }
}
