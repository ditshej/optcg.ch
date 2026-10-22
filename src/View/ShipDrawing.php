<?php

declare(strict_types=1);

namespace Optcg\View;

/**
 * Own blueprint drawing of the Thousand Sunny, side view, bow on the left.
 * Built after the plans in the author's volume 46: two masts (foremast with the crow's nest,
 * main mast through the kitchen and the aquarium bar), four levels (below deck, 1st floor at
 * lawn-deck height, 2nd floor, 3rd floor/upper deck), no paddle wheels.
 * Drawn from scratch; no official artwork is traced.
 *
 * Deck lines in drawing space: 1st floor 330 (lawn deck), 2nd floor 248, 3rd floor 166, hut roof 88.
 * Where the rooms sit is defined in ShipLayout.
 */
final class ShipDrawing
{
    /** Level tag => y of its floor line. Used by the level scale. */
    public const LEVELS = ['3F' => 166, '2F' => 248, '1F' => 330, 'B' => 456];

    /** Shared defs: hatch for the highlight, grass and planks, dot for leader lines. */
    public static function defs(): string
    {
        return '<svg class="defs" width="0" height="0" aria-hidden="true" focusable="false"><defs>'
            . '<pattern id="hatch" width="8" height="8" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><path d="M0 0V8" stroke="currentColor" stroke-width="1.2"/></pattern>'
            . '<pattern id="grass" width="6" height="6" patternUnits="userSpaceOnUse"><path d="M1 6l1-4m2 4l1-3" stroke="currentColor" stroke-width="1" fill="none"/></pattern>'
            . '<pattern id="plank" width="10" height="6" patternUnits="userSpaceOnUse"><path d="M0 5.5H10M5 0V5.5" stroke="currentColor" stroke-width=".7" fill="none"/></pattern>'
            . '<marker id="dot" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="5" markerHeight="5"><circle cx="5" cy="5" r="4.5" fill="currentColor"/></marker>'
            . '<marker id="arr" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="8" markerHeight="8" orient="auto-start-reverse"><path d="M0 1L10 5L0 9Z" fill="currentColor"/></marker>'
            . '</defs></svg>';
    }

    /** The ship itself: no rooms, no callouts, no text except the dimension marks. */
    public static function svg(): string
    {
        return '<g class="draw" aria-hidden="true">'
            . self::drawable(self::base() . self::hull() . self::structures() . self::interiors() . self::rig())
            . '</g>';
    }

    /** Mark every outline with pathLength="1" so that the first-visit animation can draw it with a unit dash. */
    private static function drawable(string $svg): string
    {
        return (string) preg_replace('/<(path|circle|polygon|rect) class="((?:part|thin|hull|deck|mast|rig)\b[^"]*)"/', '<$1 pathLength="1" class="$2"', $svg);
    }

    /** Level scale at the left edge and the length dimension below the ship. */
    private static function base(): string
    {
        $s = '<g class="g-base">';
        $s .= '<path class="water" d="M-10 428H1150"/>';
        $s .= '<path class="wave" d="M-6 440q12-8 24 0t24 0 24 0 24 0 24 0 24 0 24 0M1006 440q12-8 24 0t24 0 24 0 24 0 24 0 24 0"/>';

        $s .= '<path class="dim" d="M4 40V466" marker-start="url(#arr)" marker-end="url(#arr)"/>';
        foreach (self::LEVELS as $tag => $y) {
            $s .= '<path class="dim" d="M0 ' . $y . 'H10"/>';
            $s .= '<text class="lvl" x="13" y="' . ($y - 3) . '">' . $tag . '</text>';
        }

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

    /** Hull: shell, inner shell, lawn-deck planking, frames, ports, stern cannon. */
    private static function hull(): string
    {
        $s = '<g class="g-hull">';
        $s .= '<path class="part" d="M944 318h30v12h-30zM974 320h6v8h-6z"/>';
        $s .= '<path class="hull" d="M128 330H946C950 392 930 436 876 458C860 466 842 466 824 466H340C230 458 154 410 128 330Z"/>';
        $s .= '<path class="thin" d="M146 340C164 404 236 446 342 456H824C842 456 856 452 868 446C920 424 936 384 934 340"/>';
        $s .= '<path class="deck" d="M128 330H946"/><path class="thin" d="M134 338H942"/><rect class="plank" x="134" y="330" width="808" height="8"/>';
        $s .= '<path class="thin" d="M300 466C230 450 172 420 150 380"/>';

        foreach ([[190, 430], [250, 446], [330, 456], [420, 456], [580, 456], [700, 456], [860, 446]] as [$x, $y]) {
            $s .= '<path class="rib" d="M' . $x . ' 338V' . ($y - 4) . '"/>';
        }
        foreach ([212, 262, 312] as $x) {
            $s .= '<circle class="thin" cx="' . $x . '" cy="362" r="6"/>';
        }

        return $s . '</g>';
    }

    /** Lion figurehead, fore cabin with the helm, lawn deck, the three-storey stern block. */
    private static function structures(): string
    {
        $s = '<g class="g-cabins">';

        // Lion head: ring of mane points, inner mane ring, face with snout, ear, eye, teeth.
        $mane = '';
        for ($i = 0; $i < 28; $i++) {
            $a = deg2rad($i * 360 / 28 - 90);
            $r = $i % 2 === 0 ? 46 : 34;
            $mane .= sprintf('%.1f,%.1f ', 90 + cos($a) * $r, 286 + sin($a) * $r);
        }
        $s .= '<polygon class="part mane" points="' . trim($mane) . '"/><circle class="thin" cx="90" cy="286" r="28"/>';
        $s .= '<circle class="part" cx="86" cy="290" r="23"/>';
        $s .= '<path class="part" d="M70 292H52a4 4 0 0 0-4 4v8a4 4 0 0 0 4 4h20"/><path class="thin" d="M50 300H72M56 300v4M62 300v4M68 300v4"/>';
        $s .= '<circle class="part" cx="78" cy="282" r="3"/><path class="thin" d="M72 276l10-3"/><path class="part" d="M96 268l8-10 4 14"/>';
        $s .= '<path class="part" d="M108 322C116 330 124 330 132 326"/>';

        // Fore cabin on the lawn-deck level, roof deck with railing and the ship's wheel.
        $s .= '<path class="part" d="M176 276H336V330H176Z"/><path class="thin" d="M176 286H336"/>';
        foreach ([0, 1, 2] as $i) {
            $s .= '<path class="thin" d="M' . (192 + $i * 46) . ' 296h28v22h-28z"/>';
        }
        $s .= '<path class="thin" d="M318 330V300h12v30"/>';
        $s .= '<path class="part" d="M190 276V252M250 276V252M310 276V252M190 252H310M190 264H310"/>';
        $s .= '<g class="part"><circle cx="236" cy="236" r="15" class="wh"/><circle cx="236" cy="236" r="4"/><path d="M236 221V251M221 236H251M225.4 225.4L246.6 246.6M246.6 225.4L225.4 246.6" class="thin"/></g>';

        // Lawn deck: railing, planking of grass, tree, hatch down to the soldier dock.
        $s .= '<path class="part" d="M346 330V308M600 330V308M630 330V308M346 308H636M346 319H636"/>';
        $s .= '<rect class="grass" x="346" y="324" width="290" height="6"/>';
        $s .= '<path class="part" d="M570 330V290"/><circle class="part" cx="570" cy="268" r="22"/><circle class="thin" cx="570" cy="268" r="14"/>';
        $s .= '<path class="thin" d="M570 308l13-11M570 300l-11-10"/>';
        $s .= '<path class="part" d="M376 330v-12h30v12M404 330v-8h14v8"/>';

        // Stern block: 1st floor (aquarium bar), 2nd floor (kitchen and sick bay), 3rd floor (library hut).
        $s .= '<path class="part" d="M640 330V248H940V330"/>';
        $s .= '<path class="part" d="M640 248V166H946V248"/><path class="part" d="M790 248V166"/>';
        $s .= '<path class="thin" d="M640 258H940M660 190h24v24h-24zM696 190h24v24h-24zM810 190h24v24h-24zM850 190h24v24h-24zM896 190h24v24h-24z"/>';
        $s .= '<path class="part" d="M790 166V88H946V166"/><path class="part" d="M780 88H956M780 94H956"/>';
        $s .= '<path class="thin" d="M806 116h26v30h-26zM848 116h26v30h-26zM890 116h26v30h-26z"/>';
        $s .= '<path class="part" d="M830 88V66h18v22M826 66h26"/><path class="thin" d="M834 56q4-8 0-14M842 58q4-8 0-14"/>';
        // Railing of the kitchen's roof deck (the lookout in front of the library).
        $s .= '<path class="part" d="M644 166V146M690 166V146M770 166V146M644 146H770M644 156H770"/>';

        return $s . '</g>';
    }

    /** Furniture inside the rooms: books, stove, bed, tank, boats, workbench, barbell, chart table. */
    private static function interiors(): string
    {
        $s = '<g class="furn">';
        // Library (upper level of the hut): shelf with books.
        $s .= '<path d="M806 150H934"/>';
        for ($i = 0; $i < 12; $i++) {
            $x = 810 + $i * 10;
            $s .= '<path d="M' . $x . ' 150v-' . (10 + $i % 3 * 3) . '"/>';
        }
        // Nami's chart table in the library, not a room of its own.
        $s .= '<path d="M880 150H926M884 150v-8M922 150v-8"/>';
        // Galley: counter, stove with pot, hanging pans.
        $s .= '<path d="M652 244H780M652 244v-12M780 244v-12"/><path d="M690 232v-8h24v8M694 224h16"/><path d="M740 232v-10h28v10"/>';
        $s .= '<path d="M664 176v8M684 176v12"/><circle cx="664" cy="188" r="3.5"/><circle cx="684" cy="192" r="3.5"/>';
        // Sick bay: bed with pillow, cabinet with a cross.
        $s .= '<path d="M806 244H866M806 244v-14h50v14M806 234h14"/><rect x="888" y="200" width="36" height="44"/><path d="M906 208v14M899 215h14"/>';
        // Aquarium bar: tank with fish around the main mast, bar counter with stools.
        $s .= '<path d="M752 326H930M760 326v-10M790 326v-10M820 326v-10"/><path d="M848 280c10-8 24-8 32 0c-8 8-22 8-32 0zM848 280l-8-5v10z"/><path d="M880 302c6-5 14-5 20 0c-6 5-14 5-20 0zM880 302l-6-4v8z"/>';
        // Soldier dock: two launch hatches and a small boat on rails.
        $s .= '<path d="M326 444H548"/><path d="M356 444l10-14h66l10 14M368 430v14M424 430v14"/><path d="M458 444l8-10h52l8 10"/>';
        // Usopp factory: workbench with tools, gear and a hanging lamp.
        $s .= '<path d="M606 444H850M614 444v10M842 444v-4"/><path d="M630 444v-18h24v18"/><path d="' . self::gear(780, 410, 20, 10) . '"/><circle cx="780" cy="410" r="6"/><path d="M700 384v16M692 400h16"/>';
        // Crow's nest: a barbell on the floor.
        $s .= '<path d="M452 118H492M450 112v12M456 114v8M488 114v8M494 112v12"/>';

        return $s . '</g>';
    }

    /** Two masts with sails, crow's nest on the foremast, flags and rigging. */
    private static function rig(): string
    {
        $s = '<g class="g-rig">';
        // Stays: forestay to the bow, between the masts, main mast to the stern roof; shrouds to the rails.
        $s .= '<path class="rig" d="M470 60L100 246M470 60L730 50M730 50L950 90"/>';
        $s .= '<path class="rig" d="M464 130L404 330M476 130L536 330M464 130L420 330M476 130L520 330"/>';
        $s .= '<path class="rig" d="M724 90L660 166M736 90L790 166"/>';

        // Foremast: from the lawn deck up through the crow's nest.
        $s .= '<path class="mast" d="M464 330V70M476 330V70M470 70V36"/><path class="part" d="M470 36h34l-8 6 8 6h-34z"/>';
        $s .= '<path class="part" d="M432 76H508L502 124H438Z"/><path class="thin" d="M438 90H502M438 108H502M450 76v48M470 76v48M490 76v48"/>';
        $s .= '<path class="part" d="M384 148H556"/><path class="thin" d="M384 148v-6M556 148v-6"/>';
        $s .= '<path class="part sail" d="M390 150Q470 170 550 150L544 252Q470 268 396 252Z"/>';
        $s .= '<path class="thin" d="M394 188Q470 206 546 188M395 222Q470 238 545 222"/>';
        $s .= '<circle class="thin" cx="470" cy="206" r="14"/><path class="thin" d="M457 200h26M461 200q9-13 18 0"/><path class="thin" d="M457 224l26-11M483 224l-26-11"/><circle class="thin" cx="465" cy="208" r="2"/><circle class="thin" cx="475" cy="208" r="2"/>';

        // Main mast: from the lawn-deck level through aquarium bar and kitchen, with a smaller sail.
        $s .= '<path class="mast" d="M724 330V56M736 330V56M730 56V44"/><path class="part" d="M730 44h28l-7 5 7 5h-28z"/>';
        $s .= '<path class="part" d="M672 84H788"/><path class="thin" d="M672 84v-5M788 84v-5"/>';
        $s .= '<path class="part sail" d="M676 86Q730 100 784 86L780 152Q730 164 680 152Z"/>';
        $s .= '<path class="thin" d="M678 118Q730 130 782 118"/>';

        return $s . '</g>';
    }

    /** Round gear as one path: teeth on a circle. */
    private static function gear(float $cx, float $cy, float $r, int $teeth): string
    {
        $d = '';
        for ($i = 0; $i < $teeth; $i++) {
            $pt = static fn (float $a, float $k): string => sprintf('%.1f %.1f', $cx + cos($a) * $r * $k, $cy + sin($a) * $r * $k);
            $a = static fn (float $f): float => ($i + $f) / $teeth * 2 * M_PI;
            $d .= ($i === 0 ? 'M' : 'L') . $pt($a(0.0), 0.8) . 'L' . $pt($a(0.3), 1) . 'L' . $pt($a(0.5), 1) . 'L' . $pt($a(0.8), 0.8);
        }

        return $d . 'Z';
    }
}
