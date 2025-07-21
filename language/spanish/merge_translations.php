<?php
// Script para fusionar traducciones adicionales con el archivo principal
// Ejecutar desde la raíz del proyecto

$main_lang_file = __DIR__ . '/warehouse_lang.php';
$add_lang_file = __DIR__ . '/add_missing_translations.php';

// Cargar las traducciones originales
include($main_lang_file);
$original_lang = $lang;

// Cargar las traducciones adicionales
include($add_lang_file);
$additional_lang = $lang;

// Combinar las traducciones
$merged_lang = array_merge($original_lang, $additional_lang);

// Crear el contenido del nuevo archivo
$content = "<?php\n";
foreach ($merged_lang as $key => $value) {
    $value_escaped = str_replace("'", "\\'", $value);
    $content .= "\$lang['$key']  = '$value_escaped';\n";
}

// Escribir el nuevo archivo
file_put_contents($main_lang_file . '.new', $content);

echo "Archivo de traducciones actualizado creado en: {$main_lang_file}.new\n";
echo "Por favor renómbrelo a warehouse_lang.php después de verificarlo.\n";