<?php
/**
 * This file is part of escpos-php: PHP receipt printer library for use with
 * ESC/POS-compatible thermal and impact printers.
 *
 * Copyright (c) 2014-16 Michael Billington < michael.billington@gmail.com >,
 * incorporating modifications by others. See CONTRIBUTORS.md for a full list.
 *
 * This software is distributed under the terms of the MIT license. See LICENSE.md
 * for details.
 */

namespace Mike42\Escpos;

use Mike42\Escpos\EscposImage;
use Exception;

/**
 * Implementation of EscposImage using the GD PHP plugin.
 */
class GdEscposImage extends EscposImage
{
    /**
     * Load an image from disk, into memory, using GD.
     *
     * @param string $filename The filename to load from
     * @throws Exception if the image format is not supported,
     *  or the file cannot be opened.
     */
    protected function loadImageData($filename = null)
    {
        if ($filename === null) {
            /* Set to blank image */
            return parent::loadImageData($filename);
        }
        
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        switch ($ext) {
            case "png":
                $im = @imagecreatefrompng($filename);
                break;
            case "jpg":
                $im = @imagecreatefromjpeg($filename);
                break;
            case "gif":
                $im = @imagecreatefromgif($filename);
                break;
            default:
                throw new Exception("Image format not supported in GD");
        }
        $this -> readImageFromGdResource($im);
    }

    /**
     * Load actual image pixels from GD resource.
     *
     * @param resource $im GD resource to use
     * @throws Exception Where the image can't be read.
     */
    public function readImageFromGdResource($im)
    {
        if (!is_resource($im)) {
            throw new Exception("Failed to load image.");
        } elseif (!EscposImage::isGdLoaded()) {
            throw new Exception(__FUNCTION__ . " requires 'gd' extension.");
        }

        // =================================================================
        // SOLUCIÓN DEFINITIVA: Forzar la reconstrucción y binarización de la imagen
        // Esto soluciona problemas de 0x0 y fallos de procesamiento de píxeles.
        // =================================================================

        // 1. Obtener dimensiones y verificar
        $width = imagesx($im);
        $height = imagesy($im);

        if ($width == 0 || $height == 0) {
            throw new Exception("Failed to load image, GD resource is invalid (0x0).");
        }

        // 2. Crear un lienzo limpio y copiar la imagen para eliminar formatos extraños (CMYK, etc.)
        $im_clean = imagecreatetruecolor($width, $height);
        imagecopy($im_clean, $im, 0, 0, 0, 0, $width, $height);
        imagedestroy($im);

        // 3. Crear la imagen final en blanco y negro puro (binarización)
        $im_bw = imagecreatetruecolor($width, $height);
        $black = imagecolorallocate($im_bw, 0, 0, 0);
        $white = imagecolorallocate($im_bw, 255, 255, 255);
        $threshold = 127; // Umbral de gris para decidir si es blanco o negro

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($im_clean, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $grayscale = (int)(0.299 * $r + 0.587 * $g + 0.114 * $b);
                $color = ($grayscale < $threshold) ? $black : $white;
                imagesetpixel($im_bw, $x, $y, $color);
            }
        }
        imagedestroy($im_clean);

        // 4. Procesar la imagen binarizada para la impresora
        $imgData = str_repeat("\0", $height * $width);
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($im_bw, $x, $y);
                $isBlack = ($rgb === $black) ? 1 : 0;
                $imgData[$y * $width + $x] = $isBlack;
            }
        }
        imagedestroy($im_bw);

        $this -> setImgWidth($width);
        $this -> setImgHeight($height);
        $this -> setImgData($imgData);
    }
}
