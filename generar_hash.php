<?php
// --- SCRIPT PARA GENERAR UN HASH SEGURO ---

// 1. Escribe la contraseña que quieres usar aquí:
$password_que_quieres = 'secreto123';

// 2. Este código la convierte en un hash seguro
$hash_seguro = password_hash($password_que_quieres, PASSWORD_DEFAULT);

// 3. Muestra el resultado en pantalla
echo "<h1>¡Hash Generado!</h1>";
echo "<p>Para la contraseña: <strong>" . htmlspecialchars($password_que_quieres) . "</strong></p>";
echo "<p>El hash seguro es:</p>";
echo "<textarea rows='3' style='width:100%; font-family: monospace;'>" . htmlspecialchars($hash_seguro) . "</textarea>";
echo "<p><strong>Copia este código largo y pégalo en el SQL del Paso 3.</strong></p>";
?>
