<?php
// includes/rut_validator.php

/**
 * Elimina puntos, guiones u otros caracteres y devuelve el RUT en
 * mayúsculas. Útil para almacenar el valor siempre con el mismo
 * formato. Ej: "12.345.678-k" → "12345678K".
 */
function limpiar_rut(string $rut): string {
    return strtoupper(preg_replace('/[^0-9kK]/', '', $rut));
}

/**
 * Devuelve el RUT formateado con puntos y guión.
 */
function formatear_rut(string $rut): string {
    $rut = limpiar_rut($rut);
    if (strlen($rut) < 2) return $rut;
    $num = substr($rut, 0, -1);
    $dv  = substr($rut, -1);
    $num = number_format(intval($num), 0, '', '.');
    return $num . '-' . $dv;
}

/**
 * Valida un RUT chileno (con o sin puntos o guión).
 */
function validar_rut(string $rut): bool {
    $rut = limpiar_rut($rut);
    if (strlen($rut) < 2) return false;
    $dv = substr($rut, -1);
    $num = substr($rut, 0, -1);
    $reversed = strrev($num);
    $factor = 2;
    $sum = 0;
    for ($i = 0; $i < strlen($reversed); $i++) {
        $sum += intval($reversed[$i]) * $factor;
        $factor = ($factor == 7) ? 2 : $factor + 1;
    }
    $rest = 11 - ($sum % 11);
    $dv_calc = $rest == 11 ? '0' : ($rest == 10 ? 'K' : (string)$rest);
    return strtoupper($dv_calc) === $dv;
}
?>
