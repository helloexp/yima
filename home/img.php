<?php

/* Create a new imagick object */
$im = new Imagick();

$width = 48;
$height = 22;
/* Create new image. This will be used as fill pattern */
$r   = [225, 255, 255, 223];
$g   = [225, 236, 237, 255];
$b   = [225, 236, 166, 125];
$key = mt_rand(0, 3);

$currentRed   = $r[$key];
$currentGreen = $g[$key];
$currentBlue  = $b[$key];

//$im->newPseudoImage($width, $height, "gradient:red-black");
$im->newPseudoImage($width, $height, "gradient:red-rgba({$currentRed}, {$currentGreen}, {$currentBlue}, 0.5)");
/* Create imagickdraw object */
$draw = new ImagickDraw();

/* Start a new pattern called "gradient" */
$draw->pushPattern('gradient', 0, 0, $width, $height);

/* Composite the gradient on the pattern */
$draw->composite(Imagick::COMPOSITE_OVER, 0, 0, $width, $height, $im);

/* Close the pattern */
$draw->popPattern();

/* Use the pattern called "gradient" as the fill */
$draw->setFillPatternURL('#gradient');

/* Set font size to 52 */
$draw->setFontSize(20);

/* Annotate some text */
//$draw->annotation(20, 50, "Hello World!");
$draw->annotation(1, 20, '4589');

/* Create a few random lines */
$draw->line( rand( 0, 70 ), rand( 0, 30 ), rand( 0, 70 ), rand( 0, 30 ) );
$draw->line( rand( 0, 70 ), rand( 0, 30 ), rand( 0, 70 ), rand( 0, 30 ) );
$draw->line( rand( 0, 70 ), rand( 0, 30 ), rand( 0, 70 ), rand( 0, 30 ) );
$draw->line( rand( 0, 70 ), rand( 0, 30 ), rand( 0, 70 ), rand( 0, 30 ) );
$draw->line( rand( 0, 70 ), rand( 0, 30 ), rand( 0, 70 ), rand( 0, 30 ) );

/* Create a new canvas object and a white image */
$canvas = new Imagick();
$canvas->newImage($width, $height, "white");

/* Draw the ImagickDraw on to the canvas */
$canvas->drawImage($draw);

/* 1px black border around the image */
$canvas->borderImage('black', 1, 1);

/* Set the format to PNG */
$canvas->setImageFormat('png');

/* Output the image */
header("Content-Type: image/png");
echo $canvas;