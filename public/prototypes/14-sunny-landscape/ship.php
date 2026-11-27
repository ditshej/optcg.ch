<?php

declare(strict_types=1);

/**
 * Own blueprint drawing of the Thousand Sunny in side elevation, bow on the left.
 * Simplified and drawn from scratch; no official artwork is traced.
 * Every coordinate below lives in one drawing space of about 1000 x 500 units.
 */

/** The ship fills the screen: hull, rigging and the water line, with a little margin. */
const VIEW_LAND = '16 40 984 470';

/**
 * Room id => hit area [x, y, width, height].
 */
const ZONES = [
    'figurehead' => ['box' => [30, 206, 122, 134]],
    'helm' => ['box' => [205, 170, 150, 68]],
    'crows-nest' => ['box' => [424, 72, 92, 58]],
    'library' => ['box' => [706, 112, 140, 90]],
    'navigation-room' => ['box' => [852, 112, 78, 90]],
    'galley' => ['box' => [646, 208, 134, 80]],
    'sick-bay' => ['box' => [786, 208, 144, 80]],
    'aquarium-bar' => ['box' => [408, 304, 168, 88]],
    'soldier-dock' => ['box' => [330, 400, 240, 58]],
    'usopp-factory' => ['box' => [700, 316, 150, 118]],
];

/**
 * Where the label sits inside the ship: [x, y of the topic baseline, text-anchor, font size].
 * The link count goes 17 units below.
 */
const LABELS = [
    'figurehead' => [82, 316, 'middle', 22],
    'helm' => [262, 205, 'start', 24],
    'crows-nest' => [470, 106, 'middle', 21],
    'library' => [776, 152, 'middle', 24],
    'navigation-room' => [891, 152, 'middle', 24],
    'galley' => [713, 252, 'middle', 24],
    'sick-bay' => [858, 252, 'middle', 24],
    'aquarium-bar' => [492, 350, 'middle', 24],
    'soldier-dock' => [450, 426, 'middle', 24],
    'usopp-factory' => [775, 372, 'middle', 24],
];

/** Rectangle with cut corners, as a polygon point list. */
function chamfer(float $x, float $y, float $w, float $h, float $c): string
{
    $pts = [
        [$x + $c, $y], [$x + $w - $c, $y], [$x + $w, $y + $c], [$x + $w, $y + $h - $c],
        [$x + $w - $c, $y + $h], [$x + $c, $y + $h], [$x, $y + $h - $c], [$x, $y + $c],
    ];

    return implode(' ', array_map(static fn (array $p): string => $p[0] . ',' . $p[1], $pts));
}

/** Shared defs: hatch for the highlight, arrow heads for dimension lines. */
function ship_defs(): string
{
    return '<svg class="defs" width="0" height="0" aria-hidden="true" focusable="false"><defs>'
        . '<pattern id="hatch" width="8" height="8" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><path d="M0 0V8" stroke="currentColor" stroke-width="1.2"/></pattern>'
        . '<pattern id="grass" width="6" height="6" patternUnits="userSpaceOnUse"><path d="M1 6l1-4m2 4l1-3" stroke="currentColor" stroke-width="1" fill="none"/></pattern>'
 . '<marker id="dot" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="5" markerHeight="5"><circle cx="5" cy="5" r="4.5" fill="currentColor"/></marker>'
        . '<marker id="arr" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="8" markerHeight="8" orient="auto-start-reverse"><path d="M0 1L10 5L0 9Z" fill="currentColor"/></marker>'
        . '</defs></svg>';
}

/** The ship itself: hull, cabins, mast, sail, lion head, paddle wheel. No rooms, no text. $detail adds the fittings that would sit under a label. */
function ship_drawing(bool $detail = true): string
{
    $s = '<g class="draw" aria-hidden="true">';

    // Centre line of the sheet and the waterline.
    $s .= '<path class="centre" d="M-10 468H1150"/>';
    $s .= '<path class="water" d="M-10 410H1150"/>';
    $s .= '<text class="wl" x="0" y="404">WL</text>';
    // Waves in the open water at bow and stern.
    $s .= '<path class="wave" d="M-6 424q12-8 24 0t24 0 24 0 24 0 24 0 24 0 24 0M-6 440q12-8 24 0t24 0 24 0 24 0 24 0M1006 424q12-8 24 0t24 0 24 0 24 0 24 0 24 0M1030 440q12-8 24 0t24 0 24 0 24 0 24 0"/>';

    // Paddle wheel behind the stern.
    $s .= '<g class="wheel"><circle cx="948" cy="414" r="40"/><circle cx="948" cy="414" r="8"/>';
    for ($i = 0; $i < 8; $i++) {
        $a = deg2rad($i * 45);
        $s .= sprintf('<path d="M%.1f %.1fL%.1f %.1f"/>', 948 + cos($a) * 8, 414 + sin($a) * 8, 948 + cos($a) * 40, 414 + sin($a) * 40);
    }
    $s .= '</g>';
    // Stern cannon.
    $s .= '<path class="part" d="M944 346h26v10h-26z"/>';

    // Forestay from the mast head to the lion head.
    $s .= '<path class="rig" d="M470 62L100 212"/>';

    // Hull, deck line, gunwale and strakes.
    $s .= '<path class="hull" d="M128 292H946C950 362 930 430 876 458C860 466 842 466 824 466H340C230 456 150 384 128 292Z"/>';
    $s .= '<path class="thin" d="M128 292H946M134 308H944M170 360H936"/>';
    $s .= '<path class="thin" d="M300 466C220 440 168 392 150 340"/>';

    // Fore cabins with the helm platform on the roof.
    $s .= '<path class="part" d="M175 238H425V292H175Z"/>';
    $s .= '<path class="thin" d="M175 248H425"/>';
    for ($i = 0; $i < 5; $i++) {
        $s .= '<path class="thin" d="M' . (196 + $i * 44) . ' 258h26v20h-26z"/>';
    }
    $s .= '<path class="part" d="M200 238V224H360V238"/>';
    $s .= '<path class="thin" d="M200 224V214M230 224V214M260 224V214M290 224V214M320 224V214M360 224V214M200 214H360"/>';
    $s .= '<g class="part"><circle cx="236" cy="200" r="15" class="wh"/><circle cx="236" cy="200" r="4"/><path d="M236 185V215M221 200H251M225.4 189.4L246.6 210.6M246.6 189.4L225.4 210.6" class="thin"/></g>';

    // Lion head: mane as a ring of points, face with snout, ear and eye.
    $mane = '';
    for ($i = 0; $i < 28; $i++) {
        $a = deg2rad($i * 360 / 28 - 90);
        $r = $i % 2 === 0 ? 44 : 33;
        $mane .= sprintf('%.1f,%.1f ', 90 + cos($a) * $r, 256 + sin($a) * $r);
    }
    $s .= '<polygon class="part mane" points="' . trim($mane) . '"/>';
    $s .= '<circle class="part" cx="86" cy="260" r="23"/>';
    $s .= '<path class="part" d="M70 262H52a4 4 0 0 0-4 4v8a4 4 0 0 0 4 4h20"/><path class="thin" d="M50 270H72"/>';
    $s .= '<circle class="part" cx="78" cy="252" r="3"/><path class="part" d="M96 238l8-10 4 14"/>';
    // Neck joining head and hull.
    $s .= '<path class="part" d="M108 288C116 296 124 296 132 292"/>';

    // Main mast with crow's nest, yard and sail.
    $s .= '<path class="mast" d="M464 292V86M476 292V86M464 392V292M476 392V292"/>';
    $s .= '<path class="mast" d="M470 86V62"/><path class="part" d="M470 62h34l-8 6 8 6h-34z"/>';
    $s .= '<path class="part" d="M432 78H508L502 124H438Z"/><path class="thin" d="M438 90H502M438 108H502"/>';
    $s .= '<path class="part" d="M374 146H566"/><path class="thin" d="M374 146v-6M566 146v-6"/>';
    $s .= '<path class="part sail" d="M380 148Q470 170 560 148L552 266Q470 284 388 266Z"/>';
    $s .= '<path class="thin" d="M384 188Q470 208 556 188M386 228Q470 246 554 228"/>';

    // Lawn deck: railing, grass, tree.
    $s .= '<path class="part" d="M400 292V268M440 292V268M500 292V268M540 292V268M580 292V268M620 292V268M400 268H640"/>';
    $s .= '<rect class="grass" x="400" y="286" width="240" height="6"/>';
    $s .= '<path class="part" d="M606 292V248"/><circle class="part" cx="606" cy="228" r="24"/>';
    $s .= '<path class="thin" d="M606 270l14-12M606 262l-12-10"/>';

    // Stern: two storeys, roof deck with railing, hut on top, chimney.
    $s .= '<path class="part" d="M640 292V204H936V292"/><path class="thin" d="M640 204H700"/>';
    $s .= '<path class="part" d="M640 204V184M662 204V184M684 204V184M706 204V184M640 184H706"/>';
    $s .= '<path class="part" d="M700 204V108H936V204"/><path class="part" d="M692 108H944"/>';
    $s .= '<path class="part" d="M726 108V90h14v18"/>';
    $s .= '<path class="thin" d="M846 204V112M786 288V204M700 120H936"/>';
    foreach ([714, 810, 868] as $x) {
        $s .= '<path class="thin" d="M' . $x . ' 130h18v20h-18z"/>';
    }
    foreach ([660, 740, 800, 880] as $x) {
        $s .= '<path class="thin" d="M' . $x . ' 226h24v22h-24z"/>';
    }

    // Below deck: unlabelled compartments as dashed outlines, aquarium, dock hatches.
    $s .= '<path class="ghost" d="M180 304H400V392H180ZM596 316H694V440H596ZM856 316H930V424H856Z"/>';
    if ($detail) {
        $s .= '<circle class="part" cx="492" cy="348" r="36"/><circle class="thin" cx="492" cy="348" r="28"/>';
        for ($i = 0; $i < 7; $i++) {
            $s .= '<path class="thin" d="M' . (346 + $i * 34) . ' 412v10h20v-10"/>';
        }
    }

    // Dimension line under the ship and a scale bar.
    $s .= '<path class="dim" d="M38 492H990" marker-start="url(#arr)" marker-end="url(#arr)"/>';
    $s .= '<text class="dimt" x="514" y="488" text-anchor="middle">LENGTH OVERALL</text>';

    return $s . '</g>';
}
