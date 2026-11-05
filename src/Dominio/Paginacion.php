<?php

declare(strict_types=1);

namespace Cooki\Dominio;

/**
 * Paginación de listados: qué página se muestra, qué elementos le tocan y qué números van en la barra.
 * Si piden una página que no existe (0, -3, 999), se muestra la más cercana que sí existe.
 *
 *   $paginas = new Paginacion(count($recetas), 12, (int) ($_GET['pagina'] ?? 1));
 *   $visibles = $paginas->cortar($recetas);
 */
final class Paginacion
{
    public readonly int $pagina;
    public readonly int $paginas;

    public function __construct(public readonly int $total, public readonly int $porPagina, int $pedida)
    {
        $this->paginas = max(1, (int) ceil($total / max(1, $porPagina)));
        $this->pagina = max(1, min($this->paginas, $pedida));
    }

    /** Cuántos elementos se saltean (el OFFSET de SQL). */
    public function desde(): int
    {
        return ($this->pagina - 1) * $this->porPagina;
    }

    /**
     * @template T
     * @param list<T> $elementos todos los elementos
     * @return list<T> los de esta página
     */
    public function cortar(array $elementos): array
    {
        return array_slice($elementos, $this->desde(), $this->porPagina);
    }

    /**
     * Números para la barra: la primera, la última y las vecinas de la actual; null donde va "…".
     * Ejemplo con 10 páginas, estando en la 5: [1, null, 4, 5, 6, null, 10].
     * @return list<int|null>
     */
    public function numeros(int $vecinas = 1): array
    {
        $resultado = [];
        $anterior = 0;
        for ($n = 1; $n <= $this->paginas; $n++) {
            if ($n !== 1 && $n !== $this->paginas && abs($n - $this->pagina) > $vecinas) {
                continue;
            }
            if ($n - $anterior > 1) {
                $resultado[] = null;
            }
            $resultado[] = $n;
            $anterior = $n;
        }
        return $resultado;
    }

    /** Texto para lectores y para el pie del listado: "Mostrando 13 a 24 de 62". */
    public function texto(): string
    {
        if ($this->total === 0) {
            return 'Sin resultados';
        }
        $hasta = min($this->total, $this->desde() + $this->porPagina);
        return 'Mostrando ' . ($this->desde() + 1) . ' a ' . $hasta . ' de ' . $this->total;
    }
}
