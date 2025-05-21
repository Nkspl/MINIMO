<?php
// includes/rut_validator.php

/**
 * Valida RUT chileno (sin puntos, con guión y dígito verificador).
 * Ej: 12345678-5
 */
function validar_rut(string $rut): bool {
    $rut = preg_replace('/[^\\dkK]/', '', $rut);
    if (strlen($rut) < 2) return false;
    $dv = strtoupper(substr($rut, -1));
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
    return $dv_calc === $dv;
}
?>
