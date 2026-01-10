<?php

if (!function_exists('numeroALetras')) {
    /**
     * Convierte un número a su representación en letras
     * 
     * @param float $numero
     * @return string
     */
    function numeroALetras($numero)
    {
        $entero = floor($numero);
        $decimales = round(($numero - $entero) * 100);
        
        return strtoupper(convertirEnteroALetras($entero)) . ' CON ' . sprintf('%02d', $decimales) . '/100 SOLES';
    }
}

if (!function_exists('convertirEnteroALetras')) {
    /**
     * Convierte un número entero a letras
     * 
     * @param int $numero
     * @return string
     */
    function convertirEnteroALetras($numero)
    {
        if ($numero == 0) return 'cero';
        
        $unidades = [
            '', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve',
            'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve'
        ];
        
        $decenas = [
            '', '', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'
        ];
        
        $centenas = [
            '', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 
            'seiscientos', 'setecientos', 'ochocientos', 'novecientos'
        ];
        
        if ($numero < 20) {
            return $unidades[$numero];
        }
        
        if ($numero < 100) {
            $decena = floor($numero / 10);
            $unidad = $numero % 10;
            
            if ($numero == 20) return 'veinte';
            if ($numero < 30) return 'veinti' . $unidades[$unidad];
            
            return $decenas[$decena] . ($unidad > 0 ? ' y ' . $unidades[$unidad] : '');
        }
        
        if ($numero < 1000) {
            $centena = floor($numero / 100);
            $resto = $numero % 100;
            
            $resultado = '';
            if ($numero == 100) return 'cien';
            
            $resultado = $centenas[$centena];
            if ($resto > 0) {
                $resultado .= ' ' . convertirEnteroALetras($resto);
            }
            
            return $resultado;
        }
        
        if ($numero < 1000000) {
            $miles = floor($numero / 1000);
            $resto = $numero % 1000;
            
            $resultado = '';
            if ($miles == 1) {
                $resultado = 'mil';
            } else {
                $resultado = convertirEnteroALetras($miles) . ' mil';
            }
            
            if ($resto > 0) {
                $resultado .= ' ' . convertirEnteroALetras($resto);
            }
            
            return $resultado;
        }
        
        if ($numero < 1000000000) {
            $millones = floor($numero / 1000000);
            $resto = $numero % 1000000;
            
            $resultado = '';
            if ($millones == 1) {
                $resultado = 'un millón';
            } else {
                $resultado = convertirEnteroALetras($millones) . ' millones';
            }
            
            if ($resto > 0) {
                $resultado .= ' ' . convertirEnteroALetras($resto);
            }
            
            return $resultado;
        }
        
        // Para números muy grandes, devolver una representación básica
        return 'número muy grande: ' . number_format($numero, 0, '', ' ');
    }
}