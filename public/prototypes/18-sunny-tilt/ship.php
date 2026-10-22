<?php

declare(strict_types=1);

/**
 * Own blueprint drawing of the Thousand Sunny as a section drawing (side elevation, bow on the left):
 * decks as levels D1-D5, frames, rooms with their furniture, dimension lines and station marks.
 * Drawn from scratch; no official artwork is traced.
 * Drawing space is 1170 x 606 units (see VIEW_PLAN); every coordinate below lives in that space.
 */

/** Sheet with callouts around the ship (desktop, tablet and phone in landscape). */
const VIEW_PLAN = '-10 -6 1170 606';
/** The ship alone, cropped to its hull, rigging and level marks (phone and tablet in portrait). */
const VIEW_SHIP = '-4 50 1000 452';

/** Deck levels: tag => y of the deck line in drawing space. Used by the level scale and the sheet. */
const LEVELS = ['K' => 466, 'WL' => 410, 'D1' => 396, 'D2' => 292, 'D3' => 238, 'D4' => 204, 'D5' => 108];

/**
 * Room id => hit area [x, y, width, height] and the badge position for the number in the ship.
 */
const ZONES = [
    'figurehead' => ['box' => [30, 206, 122, 134], 'badge' => [62, 232]],
    'helm' => ['box' => [205, 170, 150, 68], 'badge' => [327, 198]],
    'crows-nest' => ['box' => [424, 72, 92, 58], 'badge' => [446, 94]],
    'library' => ['box' => [706, 112, 140, 90], 'badge' => [728, 134]],
    'navigation-room' => ['box' => [852, 112, 78, 90], 'badge' => [872, 134]],
    'galley' => ['box' => [646, 208, 134, 80], 'badge' => [668, 230]],
    'sick-bay' => ['box' => [786, 208, 144, 80], 'badge' => [808, 230]],
    'aquarium-bar' => ['box' => [408, 304, 168, 88], 'badge' => [430, 326]],
    'soldier-dock' => ['box' => [330, 400, 240, 58], 'badge' => [352, 422]],
    'usopp-factory' => ['box' => [700, 316, 150, 118], 'badge' => [722, 338]],
];

/**
 * Callouts of the plan view: label box top-left [x, y] and the leader path from the room to the box.
 */
const CALLOUTS = [
    'figurehead' => ['at' => [4, 4], 'leader' => 'M93 206V56'],
    'helm' => ['at' => [190, 4], 'leader' => 'M279 170V56'],
    'crows-nest' => ['at' => [374, 4], 'leader' => 'M450 72V56'],
    'galley' => ['at' => [558, 4], 'leader' => 'M700 208V130H640V56'],
    'library' => ['at' => [742, 4], 'leader' => 'M800 112V56'],
    'navigation-room' => ['at' => [976, 108], 'leader' => 'M930 134H976'],
    'sick-bay' => ['at' => [976, 226], 'leader' => 'M930 252H976'],
    'aquarium-bar' => ['at' => [515, 536], 'leader' => 'M576 340H594V536'],
    'soldier-dock' => ['at' => [305, 536], 'leader' => 'M400 458V536'],
    'usopp-factory' => ['at' => [700, 536], 'leader' => 'M775 434V536'],
];

const LABEL_W = 178;
const LABEL_H = 52;

/** Rectangle with cut corners, as a polygon point list. */
function chamfer(float $x, float $y, float $w, float $h, float $c): string
{
    $pts = [
        [$x + $c, $y], [$x + $w - $c, $y], [$x + $w, $y + $c], [$x + $w, $y + $h - $c],
        [$x + $w - $c, $y + $h], [$x + $c, $y + $h], [$x, $y + $h - $c], [$x, $y + $c],
    ];

    return implode(' ', array_map(static fn (array $p): string => $p[0] . ',' . $p[1], $pts));
}

/** Shared defs: hatch for the highlight, grass, arrow heads and dots for dimension and leader lines. */
function ship_defs(): string
{
    return '<svg class="defs" width="0" height="0" aria-hidden="true" focusable="false"><defs>'
        . '<pattern id="hatch" width="8" height="8" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><path d="M0 0V8" stroke="currentColor" stroke-width="1.2"/></pattern>'
        . '<pattern id="grass" width="6" height="6" patternUnits="userSpaceOnUse"><path d="M1 6l1-4m2 4l1-3" stroke="currentColor" stroke-width="1" fill="none"/></pattern>'
        . '<pattern id="plank" width="10" height="6" patternUnits="userSpaceOnUse"><path d="M0 5.5H10M5 0V5.5" stroke="currentColor" stroke-width=".7" fill="none"/></pattern>'
        . '<marker id="dot" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="5" markerHeight="5"><circle cx="5" cy="5" r="4.5" fill="currentColor"/></marker>'
        . '<marker id="arr" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="8" markerHeight="8" orient="auto-start-reverse"><path d="M0 1L10 5L0 9Z" fill="currentColor"/></marker>'
        . '</defs></svg>';
}

/** Round gear as one path: teeth on a circle. */
function gear(float $cx, float $cy, float $r, int $teeth): string
{
    $d = '';
    for ($i = 0; $i < $teeth; $i++) {
        $a0 = ($i + 0.0) / $teeth * 2 * M_PI;
        $a1 = ($i + 0.3) / $teeth * 2 * M_PI;
        $a2 = ($i + 0.5) / $teeth * 2 * M_PI;
        $a3 = ($i + 0.8) / $teeth * 2 * M_PI;
        $pt = static fn (float $a, float $k): string => sprintf('%.1f %.1f', $cx + cos($a) * $r * $k, $cy + sin($a) * $r * $k);
        $d .= ($i === 0 ? 'M' : 'L') . $pt($a0, 0.8) . 'L' . $pt($a1, 1) . 'L' . $pt($a2, 1) . 'L' . $pt($a3, 0.8);
    }

    return $d . 'Z';
}

/** Level scale at the left edge and the station scale below the ship: the dimension lines of the sheet. */
function layer_base(): string
{
    $s = '<g class="g-base">';
    $s .= '<path class="centre" d="M-10 468H1150"/>';
    $s .= '<path class="water" d="M-10 410H1150"/>';
    $s .= '<path class="wave" d="M-6 424q12-8 24 0t24 0 24 0 24 0 24 0 24 0 24 0M-6 440q12-8 24 0t24 0 24 0 24 0 24 0M1006 424q12-8 24 0t24 0 24 0 24 0 24 0 24 0M1030 440q12-8 24 0t24 0 24 0 24 0 24 0"/>';

    // Level scale: one tick and one tag per deck.
    $s .= '<path class="dim" d="M4 66V466" marker-start="url(#arr)" marker-end="url(#arr)"/>';
    foreach (LEVELS as $tag => $y) {
        $s .= '<path class="dim" d="M0 ' . $y . 'H10"/>';
        $s .= '<text class="lvl" x="13" y="' . ($y - 3) . '">' . $tag . '</text>';
    }

    // Length dimension with station marks 0..10 (every tenth of the length overall).
    $s .= '<path class="dim" d="M38 492H990" marker-start="url(#arr)" marker-end="url(#arr)"/>';
    $s .= '<path class="dim" d="M38 482V502M990 482V502"/>';
    $s .= '<text class="dimt" x="514" y="488" text-anchor="middle">LENGTH OVERALL</text>';
    for ($i = 0; $i <= 10; $i++) {
        $x = 38 + $i * 95.2;
        $s .= sprintf('<path class="dim" d="M%.1f 497V507"/>', $x);
        $s .= sprintf('<text class="stn" x="%.1f" y="518" text-anchor="middle">%d</text>', $x, $i);
    }

    return $s . '</g>';
}

/** Hull: paddle wheel, shell, inner shell, decks D1 and D2 with planking, frames, ports. */
function layer_hull(): string
{
    $s = '<g class="g-hull">';

    // Paddle wheel with housing and 12 blades behind the stern.
    $s .= '<g class="wheel"><circle cx="948" cy="414" r="42"/><circle cx="948" cy="414" r="30"/><circle cx="948" cy="414" r="8"/><path d="M906 414A42 42 0 0 1 948 372" />';
    for ($i = 0; $i < 12; $i++) {
        $a = deg2rad($i * 30);
        $s .= sprintf('<path d="M%.1f %.1fL%.1f %.1f"/><path d="M%.1f %.1fL%.1f %.1f"/>', 948 + cos($a) * 8, 414 + sin($a) * 8, 948 + cos($a) * 30, 414 + sin($a) * 30, 948 + cos($a) * 30, 414 + sin($a) * 30, 948 + cos($a) * 44, 414 + sin($a) * 44);
    }
    $s .= '</g>';
    // Stern cannon.
    $s .= '<path class="part" d="M944 344h28v12h-28zM972 346h6v8h-6z"/>';

    // Outer shell and inner shell (keel line, bilge and the two decks below the weather deck).
    $s .= '<path class="hull" d="M128 292H946C950 362 930 430 876 458C860 466 842 466 824 466H340C230 456 150 384 128 292Z"/>';
    $s .= '<path class="thin" d="M146 304C164 380 236 438 342 456H824C842 456 856 452 868 446C920 420 936 360 934 304"/>';
    $s .= '<path class="deck" d="M128 292H946"/><path class="thin" d="M134 300H942"/><rect class="plank" x="134" y="292" width="808" height="8"/>';
    $s .= '<path class="deck" d="M164 396H926"/><path class="thin" d="M164 402H926"/>';
    $s .= '<path class="thin" d="M300 466C220 440 168 392 150 340"/>';

    // Frames (bulkheads), shorter towards the bow where the hull narrows.
    foreach ([[190, 392], [250, 428], [330, 456], [420, 456], [580, 456], [700, 456], [860, 446]] as [$x, $y]) {
        $s .= '<path class="rib" d="M' . $x . ' 300V' . ($y - 6) . '"/>';
    }

    // Gun ports along the weather deck and portholes in the hold.
    foreach ([212, 262, 312, 362] as $x) {
        $s .= '<circle class="thin" cx="' . $x . '" cy="350" r="6"/>';
    }
    foreach ([596, 640, 686] as $x) {
        $s .= '<circle class="thin" cx="' . $x . '" cy="438" r="5"/>';
    }

    return $s . '</g>';
}

/** Cabins and decks above the weather deck, lion head, rooms with their furniture. */
function layer_cabins(): string
{
    $s = '<g class="g-cabins">';

    // Lion head: ring of mane points, inner mane ring, face with snout, ear, eye, teeth.
    $mane = '';
    for ($i = 0; $i < 28; $i++) {
        $a = deg2rad($i * 360 / 28 - 90);
        $r = $i % 2 === 0 ? 46 : 34;
        $mane .= sprintf('%.1f,%.1f ', 90 + cos($a) * $r, 256 + sin($a) * $r);
    }
    $s .= '<polygon class="part mane" points="' . trim($mane) . '"/><circle class="thin" cx="90" cy="256" r="28"/>';
    $s .= '<circle class="part" cx="86" cy="260" r="23"/>';
    $s .= '<path class="part" d="M70 262H52a4 4 0 0 0-4 4v8a4 4 0 0 0 4 4h20"/><path class="thin" d="M50 270H72M56 270v4M62 270v4M68 270v4"/>';
    $s .= '<circle class="part" cx="78" cy="252" r="3"/><path class="thin" d="M72 246l10-3"/><path class="part" d="M96 238l8-10 4 14"/>';
    $s .= '<path class="part" d="M108 288C116 296 124 296 132 292"/>';

    // Fore cabin (D2 to D3) with doors and windows, helm platform with railing and ship's wheel.
    $s .= '<path class="part" d="M175 238H425V292H175Z"/><path class="thin" d="M175 248H425"/>';
    for ($i = 0; $i < 5; $i++) {
        $s .= '<path class="thin" d="M' . (196 + $i * 44) . ' 258h26v20h-26z"/>';
    }
    $s .= '<path class="thin" d="M404 292V262h16v30"/>';
    $s .= '<path class="part" d="M200 238V224H360V238"/>';
    $s .= '<path class="thin" d="M200 224V210M230 224V210M260 224V210M290 224V210M320 224V210M360 224V210M200 210H360"/>';
    $s .= '<g class="part"><circle cx="236" cy="198" r="15" class="wh"/><circle cx="236" cy="198" r="4"/><path d="M236 183V213M221 198H251M225.4 187.4L246.6 208.6M246.6 187.4L225.4 208.6" class="thin"/></g>';
    $s .= '<path class="thin" d="M364 238l12 54M376 238l12 54"/>';

    // Lawn deck: railing, planking of grass, tree with trunk and branches.
    $s .= '<path class="part" d="M400 292V268M440 292V268M500 292V268M540 292V268M580 292V268M620 292V268M400 268H640M400 280H640"/>';
    $s .= '<rect class="grass" x="400" y="286" width="240" height="6"/>';
    $s .= '<path class="part" d="M606 292V250"/><circle class="part" cx="606" cy="228" r="24"/><circle class="thin" cx="606" cy="228" r="15"/>';
    $s .= '<path class="thin" d="M606 270l14-12M606 262l-12-10"/>';
    $s .= '<path class="part" d="M452 292v-14h14v14M520 292v-10h16v10"/>';

    // Stern: two storeys (D3 to D4, D4 to D5), roof deck with railing, hut roof, chimney.
    $s .= '<path class="part" d="M640 292V204H936V292"/><path class="thin" d="M640 204H700"/>';
    $s .= '<path class="part" d="M640 204V184M662 204V184M684 204V184M706 204V184M640 184H706M640 194H706"/>';
    $s .= '<path class="part" d="M700 204V108H936V204"/><path class="part" d="M692 108H944M692 114H944"/>';
    $s .= '<path class="part" d="M722 108V86h18v22M718 86h26"/><path class="thin" d="M726 76q4-8 0-14M734 78q4-8 0-14"/>';
    $s .= '<path class="thin" d="M846 204V112M786 288V204"/>';

    $s .= interiors();

    return $s . '</g>';
}

/** Furniture inside the rooms: bookshelves, chart table, stove, bed, tank, boats, workbench. */
function interiors(): string
{
    $s = '<g class="furn">';
    // Library: two rows of books on a shelf with a ladder.
    $s .= '<path d="M722 152H836M722 176H836"/>';
    for ($i = 0; $i < 11; $i++) {
        $x = 726 + $i * 10;
        $s .= '<path d="M' . $x . ' 152v-' . (10 + $i % 3 * 3) . 'M' . $x . ' 176v-' . (12 - $i % 4 * 2) . '"/>';
    }
    $s .= '<path d="M826 152V198M834 152V198M826 164h8M826 188h8"/>';
    // Navigation room: chart table with a compass and a lamp.
    $s .= '<path d="M866 178H922M872 178v20M916 178v20"/><circle cx="894" cy="170" r="8"/><path d="M894 164v12M888 170h12"/><path d="M906 132v14M900 146h12"/>';
    // Galley: counter, stove with pot, hanging pans.
    $s .= '<path d="M656 270H770M656 270v16M770 270v16"/><path d="M690 270v-10h24v10M694 260h16"/><path d="M726 270v-12h30v12"/>';
    $s .= '<path d="M670 214v10M690 214v14M710 214v8"/><circle cx="670" cy="228" r="4"/><circle cx="690" cy="232" r="4"/>';
    // Sick bay: bed with pillow, cabinet with a cross.
    $s .= '<path d="M806 268H866M806 268v-12h50v12M806 258h14"/><rect x="884" y="232" width="34" height="40"/><path d="M901 238v14M894 245h14"/>';
    // Aquarium bar: tank with fish, bar counter with stools.
    $s .= '<path d="M446 374H560M452 374v14M476 374v14M500 374v14"/><path d="M530 340c10-8 22-8 30 0c-8 8-20 8-30 0zM530 340l-8-5v10z"/><path d="M542 356c6-5 14-5 20 0c-6 5-14 5-20 0zM542 356l-6-4v8z"/>';
    // Soldier dock: two launch hatches and a small boat on rails.
    $s .= '<path d="M350 442H550"/><path d="M380 442l10-12h60l10 12M392 430v12M438 430v12"/><path d="M468 442l8-10h50l8 10"/>';
    // Usopp factory: workbench with tools, gear and a hanging lamp.
    $s .= '<path d="M714 420H838M722 420v14M830 420v14"/><path d="M730 420v-16h22v16"/><path d="' . gear(806, 388, 20, 10) . '"/><circle cx="806" cy="388" r="6"/><path d="M768 340v16"/><path d="M760 356h16"/>';
    // Crow's nest: a barbell on the floor.
    $s .= '<path d="M452 118H492M450 112v12M456 114v8M488 114v8M494 112v12"/>';

    return $s . '</g>';
}

/** Mast, sail with the crew's mark, crow's nest, flag and rigging. */
function layer_rig(): string
{
    $s = '<g class="g-rig">';
    // Forestay from the mast head to the lion head, backstay to the stern, shrouds to the rails.
    $s .= '<path class="rig" d="M470 62L100 212M470 62L706 108"/><path class="rig" d="M464 124L402 268M476 124L538 268M464 124L418 268M476 124L522 268"/>';
    $s .= '<path class="mast" d="M464 292V86M476 292V86M464 392V292M476 392V292"/>';
    $s .= '<path class="mast" d="M470 86V62"/><path class="part" d="M470 62h34l-8 6 8 6h-34z"/><circle class="thin" cx="486" cy="68" r="2.4"/>';
    // Crow's nest.
    $s .= '<path class="part" d="M432 78H508L502 124H438Z"/><path class="thin" d="M438 90H502M438 108H502M450 78v46M470 78v46M490 78v46"/>';
    // Yard, sail with reef lines, battens and the mark (round head with hat and crossbones).
    $s .= '<path class="part" d="M374 146H566"/><path class="thin" d="M374 146v-6M566 146v-6"/>';
    $s .= '<path class="part sail" d="M380 148Q470 170 560 148L552 266Q470 284 388 266Z"/>';
    $s .= '<path class="thin" d="M384 188Q470 208 556 188M386 228Q470 246 554 228"/>';
    $s .= '<circle class="thin" cx="470" cy="214" r="15"/><path class="thin" d="M456 207h28M460 207q10-14 20 0"/><path class="thin" d="M456 232l28-12M484 232l-28-12"/><circle class="thin" cx="465" cy="215" r="2"/><circle class="thin" cx="475" cy="215" r="2"/>';
    $s .= '<path class="thin" d="M392 266v8M432 270v8M470 274v8M508 270v8M548 266v8"/>';

    return $s . '</g>';
}

/** Inline-SVG groups of the ship, from the water up. Named so that prototype 18 can stack them in 3D. */
function ship_layers(): array
{
    return ['base' => layer_base(), 'hull' => layer_hull(), 'cabins' => layer_cabins(), 'rig' => layer_rig()];
}

/** Mark every outline with pathLength="1" so that the stroke animation can draw it with a unit dash. */
function draw_ready(string $svg): string
{
    return (string) preg_replace('/<(path|circle|polygon|rect) class="((?:part|thin|hull|deck|mast|rig)\b[^"]*)"/', '<$1 pathLength="1" class="$2"', $svg);
}
