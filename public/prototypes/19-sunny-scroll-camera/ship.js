/*
 * Own drawing of the Thousand Sunny as an isometric cutaway, projected in the browser to plain SVG.
 * Model units: x runs bow (0) to stern (100), y across the beam (0 far side .. 30 near side), z up.
 * The near side of the hull is cut away. Nothing here is traced from official artwork.
 * The same file ships in 17 and 19 so that both prototypes stay self-contained.
 */
window.SunnyShip = (() => {
    const D = Math.PI / 180;
    const DECK = 32;
    const MID = 15;
    const KEEL = 0.45;
    const HULL = [[0, 1.6], [3, 3.8], [8, 6.8], [15, 9.8], [25, 12.4], [38, 13.7], [60, 13.9], [76, 13.5], [88, 12.7], [96, 11.8], [100, 11.2]]; // x, half beam at deck level

    // Where the rooms lie: box [x, y, z, dx, dy, dz]; `look` picks the decoration. Area = bow / mid / stern, used by the scroll story.
    const ROOMS = {
        figurehead: { box: [-4, 11, 32, 8, 8, 10], look: 'ghost', area: 'bow' },
        helm: { box: [17, 9, 40, 11, 12, 8], look: 'helm', area: 'bow' },
        'aquarium-bar': { box: [34, 6, 16, 32, 18, 11], look: 'aquarium', area: 'mid' },
        'soldier-dock': { box: [34, 8, 2, 32, 14, 9], look: 'dock', area: 'mid' },
        'crows-nest': { box: [47, 11, 63, 10, 8, 6], look: 'ghost', area: 'mid' },
        'usopp-factory': { box: [72, 6, 16, 20, 18, 11], look: 'factory', area: 'stern' },
        galley: { box: [74, 5, 40, 13, 20, 8], look: 'cabin', area: 'stern' },
        library: { box: [76, 6, 48, 15, 18, 8], look: 'cabin', area: 'stern' },
        'sick-bay': { box: [87, 6, 40, 12, 18, 8], look: 'cabin', area: 'stern' },
        'navigation-room': { box: [91, 8, 48, 9, 14, 8], look: 'cabin', area: 'stern' },
    };
    const AREA = { bow: 'Bow', mid: 'Midship', stern: 'Stern' };
    const BLOCKS = [[14, 7, 32, 16, 16, 8], [74, 4, 32, 26, 22, 8]]; // fore cabins, stern deckhouse

    const halfBeam = (x, z) => {
        let i = 0;
        while (i < HULL.length - 2 && HULL[i + 1][0] < x) i++;
        const [x0, w0] = HULL[i];
        const [x1, w1] = HULL[i + 1];
        const w = w0 + ((w1 - w0) * Math.min(1, Math.max(0, (x - x0) / (x1 - x0))));
        return w * (KEEL + (1 - KEEL) * Math.pow(Math.max(0, z) / DECK, 0.72));
    };

    const SHIPPTS = [[-9, 15, 32], [-9, 15, 46], [104, 15, 62], [52, 15, 76], [0, 15, 0], [100, 4, 58], [104, 26, 0]];
    const DIMPTS = [[-2, 41, -6], [104, 41, -6], [110, 15, 0], [108, 34, 78], [30, 38, 0]];
    const hullPts = () => { const pts = []; for (let i = 0; i < HULL.length; i++) pts.push([HULL[i][0], MID - HULL[i][1], DECK], [HULL[i][0], MID + HULL[i][1], DECK], [HULL[i][0], MID, 0]); return pts.concat(SHIPPTS); };

    const rot = (v, x, y) => [x * Math.cos(v.yaw * D) - y * Math.sin(v.yaw * D), x * Math.sin(v.yaw * D) + y * Math.cos(v.yaw * D)];
    const screenXY = (v, x, y, z) => {
        const [rx, ry] = rot(v, x, y);
        const p = v.pitch * D;
        return [rx, ry * Math.sin(p) - z * Math.cos(p)];
    };

    /** Scale and centre so that the ship fills the stage at the rest view and never leaves it inside the turning range. */
    const fit = (W, H, { rest, limits, padX = 0.08, fillY = 0.8, maxScale = 9, dims = true }) => {
        const pts = dims ? hullPts().concat(DIMPTS) : hullPts();
        const views = [rest, { yaw: limits.yaw[0], pitch: rest.pitch }, { yaw: limits.yaw[1], pitch: rest.pitch }, { yaw: rest.yaw, pitch: limits.pitch[0] }, { yaw: rest.yaw, pitch: limits.pitch[1] }];
        let S = Infinity;
        let C = [0, 0];
        views.forEach((v, n) => {
            let minx = 1e9, maxx = -1e9, miny = 1e9, maxy = -1e9;
            for (const [x, y, z] of pts) {
                const [sx, sy] = screenXY(v, x, y, z);
                minx = Math.min(minx, sx); maxx = Math.max(maxx, sx); miny = Math.min(miny, sy); maxy = Math.max(maxy, sy);
            }
            S = Math.min(S, (W * (1 - 2 * padX)) / (maxx - minx), (H * fillY) / (maxy - miny));
            if (n === 0) C = [(minx + maxx) / 2, (miny + maxy) / 2];
        });
        return { S: Math.min(S, maxScale), C, cx: W / 2, cy: H / 2 };
    };

    /** Screen box [minx, miny, maxx, maxy] of the ship itself (without the dimension lines) in a view. */
    const bounds = (cam, v) => {
        let b = [1e9, 1e9, -1e9, -1e9];
        for (const [x, y, z] of hullPts()) {
            const [sx, sy] = screenXY(v, x, y, z);
            const px = cam.cx + cam.S * (sx - cam.C[0]);
            const py = cam.cy + cam.S * (sy - cam.C[1]);
            b = [Math.min(b[0], px), Math.min(b[1], py), Math.max(b[2], px), Math.max(b[3], py)];
        }
        return b;
    };

    const make = (cam, v) => {
        const P = (x, y, z) => {
            const [sx, sy] = screenXY(v, x, y, z);
            return [cam.cx + cam.S * (sx - cam.C[0]), cam.cy + cam.S * (sy - cam.C[1])];
        };
        const depth = (x, y, z) => {
            const a = v.yaw * D;
            const p = v.pitch * D;
            return (x * Math.sin(a) + y * Math.cos(a)) * Math.sin(p) + z * Math.cos(p);
        };
        const facing = (n) => {
            const a = v.yaw * D;
            const p = v.pitch * D;
            return (n[0] * Math.sin(a) + n[1] * Math.cos(a)) * Math.sin(p) + n[2] * Math.cos(p) > 0.001;
        };
        return { P, depth, facing };
    };

    const f1 = (n) => n.toFixed(1);

    const anchor = (cam, v, id) => {
        const [x, y, z, dx, dy, dz] = ROOMS[id].box;
        return make(cam, v).P(x + dx / 2, y + dy / 2, z + dz / 2);
    };

    const FACES = [ // outward normal, box corners by bit (x=1, y=2, z=4), name
        { n: [0, 0, 1], c: [4, 5, 7, 6], k: 'top' },
        { n: [0, 1, 0], c: [2, 3, 7, 6], k: 'near' },
        { n: [0, -1, 0], c: [0, 1, 5, 4], k: 'far' },
        { n: [1, 0, 0], c: [1, 3, 7, 5], k: 'aft' },
        { n: [-1, 0, 0], c: [0, 2, 6, 4], k: 'fore' },
    ];

    const draw = (cam, v, { dims = true } = {}) => {
        const { P, depth, facing } = make(cam, v);
        const pts = (list) => list.map(([x, y, z]) => P(x, y, z).map(f1).join(',')).join(' ');
        const poly = (list, cls, extra = '') => `<polygon class="${cls}" points="${pts(list)}"${extra}/>`;
        const line = (list, cls) => `<polyline class="${cls}" points="${pts(list)}"/>`;
        const ring = (plane, c, r, n, rotA = 0, ry = r) => {
            const out = [];
            for (let i = 0; i < n; i++) {
                const t = rotA + (i * 2 * Math.PI) / n;
                const u = r * Math.cos(t);
                const w = ry * Math.sin(t);
                out.push(plane === 'x' ? [c[0], c[1] + u, c[2] + w] : plane === 'y' ? [c[0] + u, c[1], c[2] + w] : [c[0] + u, c[1] + w, c[2]]);
            }
            return out;
        };
        const dot = (p, r, cls) => { const q = P(...p); return `<circle class="${cls}" cx="${f1(q[0])}" cy="${f1(q[1])}" r="${r}"/>`; };
        const box = (x, y, z, dx, dy, dz, cls) => {
            const c = [0, 1, 2, 3, 4, 5, 6, 7].map((i) => [x + (i & 1 ? dx : 0), y + (i & 2 ? dy : 0), z + (i & 4 ? dz : 0)]);
            let s = '';
            for (const f of FACES) if (facing(f.n)) s += poly(f.c.map((i) => c[i]), `${cls} f-${f.k}`);
            return s;
        };
        /** Row of windows on the near and aft faces of a box. */
        const windows = (x, y, z, dx, dy, dz) => {
            let s = '';
            if (facing([0, 1, 0])) {
                const n = Math.max(1, Math.floor(dx / 5));
                const step = dx / n;
                for (let i = 0; i < n; i++) {
                    const x0 = x + i * step + step * 0.25;
                    s += poly([[x0, y + dy, z + dz * 0.38], [x0 + step * 0.5, y + dy, z + dz * 0.38], [x0 + step * 0.5, y + dy, z + dz * 0.74], [x0, y + dy, z + dz * 0.74]], 'win');
                }
            }
            if (facing([1, 0, 0])) {
                const n = Math.max(1, Math.floor(dy / 6));
                const step = dy / n;
                for (let i = 0; i < n; i++) {
                    const y0 = y + i * step + step * 0.25;
                    s += poly([[x + dx, y0, z + dz * 0.38], [x + dx, y0 + step * 0.5, z + dz * 0.38], [x + dx, y0 + step * 0.5, z + dz * 0.74], [x + dx, y0, z + dz * 0.74]], 'win');
                }
            }
            return s;
        };
        const gear = (c, r, teeth) => {
            const out = [];
            for (let i = 0; i < teeth * 2; i++) {
                const t = (i * Math.PI) / teeth;
                const rr = i % 2 ? r * 0.78 : r;
                out.push([c[0] + rr * Math.cos(t), c[1], c[2] + rr * Math.sin(t)]);
            }
            return out;
        };

        let s = '';
        // ---- drafting grid ----
        for (let x = -20; x <= 120; x += 10) s += line([[x, -12, -6], [x, 44, -6]], 'gridl');
        for (let y = -10; y <= 44; y += 10) s += line([[-20, y, -6], [120, y, -6]], 'gridl');

        // ---- hull interior: far wall with planking and frames, stern wall, stem ----
        const xs = HULL.map((h) => h[0]);
        const zsPlank = [5, 11, 17, 23, 29];
        for (let i = 0; i < HULL.length - 1; i++) {
            const [xa, xb] = [HULL[i][0], HULL[i + 1][0]];
            s += poly([[xa, MID - halfBeam(xa, DECK), DECK], [xb, MID - halfBeam(xb, DECK), DECK], [xb, MID - halfBeam(xb, 0), 0], [xa, MID - halfBeam(xa, 0), 0]], 'wall');
        }
        for (const z of zsPlank) s += line(xs.map((x) => [x, MID - halfBeam(x, z), z]), 'plank');
        for (const x of xs.slice(1)) s += line([[x, MID - halfBeam(x, DECK), DECK], [x, MID - halfBeam(x, 0), 0]], 'frame');
        const n = HULL.length - 1;
        s += poly([[100, MID - halfBeam(100, DECK), DECK], [100, MID + halfBeam(100, DECK), DECK], [100, MID + halfBeam(100, 0), 0], [100, MID - halfBeam(100, 0), 0]], 'wall back');
        for (const z of [6, 14, 22, 30]) s += line([[100, MID - halfBeam(100, z), z], [100, MID + halfBeam(100, z), z]], 'plank');
        // ---- floors: hold, tween deck; main deck as a ghost so the levels stay visible ----
        const plan = (z) => HULL.map((h) => [h[0], MID - halfBeam(h[0], z), z]).concat(HULL.map((h) => [h[0], MID + halfBeam(h[0], z), z]).reverse());
        for (const z of [2, 16]) {
            s += poly(plan(z), 'floor');
            for (let x = 8; x < 100; x += 6) s += line([[x, MID - halfBeam(x, z), z], [x, MID + halfBeam(x, z), z]], 'plank');
        }
        s += poly(plan(12), 'water');
        s += poly(plan(DECK), 'deckghost');
        s += poly(HULL.map((h) => [h[0], MID - halfBeam(h[0], DECK) + 1.6, DECK + 0.1]).concat(HULL.map((h) => [h[0], MID + halfBeam(h[0], DECK) - 1.6, DECK + 0.1]).reverse()), 'deckin');
        // ---- near side cut away: cut edge, keel and ribs ----
        const near = HULL.map((h) => [h[0], MID + halfBeam(h[0], DECK), DECK]);
        const keelN = HULL.map((h) => [h[0], MID + halfBeam(h[0], 0), 0]);
        s += line(near, 'cut') + line(keelN, 'keel');
        for (let i = 1; i < HULL.length; i++) s += line([near[i], [HULL[i][0], MID + halfBeam(HULL[i][0], 16), 16], keelN[i]], 'rib');
        s += line([[0, MID, DECK + 3], [0, MID, 0]], 'rib');
        // far rail: posts and top rail, stern rail, bow rail
        let rail = [];
        for (let x = 4; x <= 100; x += 6) {
            const y = MID - halfBeam(x, DECK) + 0.4;
            rail.push([x, y, DECK + 3.6]);
            s += line([[x, y, DECK], [x, y, DECK + 3.6]], 'post');
        }
        s += line(rail, 'rail');
        for (let y = MID - 9; y <= MID + 9; y += 3) s += line([[99.6, y, DECK], [99.6, y, DECK + 3.6]], 'post');
        s += line([[99.6, MID - 10.6, DECK + 3.6], [99.6, MID + 10.6, DECK + 3.6]], 'rail');
        // gun ports on the far wall
        for (let x = 30; x <= 70; x += 8) {
            const y0 = MID - halfBeam(x, 22);
            s += poly([[x, y0, 20], [x + 3, y0, 20], [x + 3, y0, 24], [x, y0, 24]], 'port');
        }
        // rudder behind the stern
        s += poly([[100, MID - 1, 4], [106, MID - 0.6, 3], [106, MID - 0.6, -3], [100, MID - 1, 12]], 'rudder');

        // ---- objects, far to near ----
        const items = [];
        const add = (d, str) => items.push({ d, str });
        for (const [x, y, z, dx, dy, dz] of BLOCKS) add(depth(x + dx / 2, y + dy / 2, z + dz / 2), box(x, y, z, dx, dy, dz, 'blk') + windows(x, y, z, dx, dy, dz));
        // stern roof slab and fore roof
        add(depth(88, 15, 57), box(75.5, 4.5, 56, 25, 21, 1, 'roof') + line([[75, 3, 57.2], [100, 3, 57.2]], 'thin'));

        for (const [id, r] of Object.entries(ROOMS)) {
            const [x, y, z, dx, dy, dz] = r.box;
            let art = '';
            if (r.look === 'ghost') art = '';
            else art += windows(x, y, z, dx, dy, dz);
            const cx = x + dx / 2;
            if (r.look === 'aquarium' && facing([0, 1, 0])) {
                art = poly([[x + 3, y + dy, z + 3], [x + dx - 3, y + dy, z + 3], [x + dx - 3, y + dy, z + dz - 2.5], [x + 3, y + dy, z + dz - 2.5]], 'win glass')
                    + line([[x + 3, y + dy, z + 6], [x + 9, y + dy, z + 7], [x + 15, y + dy, z + 6], [x + 21, y + dy, z + 7], [x + dx - 3, y + dy, z + 6]], 'thin')
                    + poly([[x + 10, y + dy, z + 8.6], [x + 13, y + dy, z + 7.6], [x + 14, y + dy, z + 8.6], [x + 13, y + dy, z + 9.6]], 'fish')
                    + poly([[x + 19, y + dy, z + 5.4], [x + 21.6, y + dy, z + 4.6], [x + 22.6, y + dy, z + 5.4], [x + 21.6, y + dy, z + 6.2]], 'fish');
            }
            if (r.look === 'dock' && facing([0, 1, 0])) {
                art = poly([[x + 4, y + dy, z], [x + dx - 4, y + dy, z], [x + dx - 4, y + dy, z + dz - 2], [x + 4, y + dy, z + dz - 2]], 'win')
                    + line([[x + dx / 2, y + dy, z], [x + dx / 2, y + dy, z + dz - 2]], 'thin');
            }
            if (r.look === 'factory' && facing([0, 1, 0])) {
                art = windows(x, y, z, dx, dy, dz).split('<polygon').slice(0, 1).join('<polygon')
                    + poly(gear([cx, y + dy, z + dz * 0.55], 3.6, 8), 'gearp') + poly(ring('y', [cx, y + dy, z + dz * 0.55], 1.1, 10), 'gearp');
            }
            if (r.look === 'helm' && facing([-1, 0, 0])) {
                const c = [x, y + dy / 2, z + dz * 0.55];
                art += poly(ring('x', [x, c[1], c[2]], 2.7, 14), 'wheelh') + poly(ring('x', [x, c[1], c[2]], 0.7, 8), 'wheelh');
                for (let i = 0; i < 6; i++) { const t = (i * Math.PI) / 3; art += line([[x, c[1], c[2]], [x, c[1] + 3.8 * Math.cos(t), c[2] + 3.8 * Math.sin(t)]], 'thin'); }
            }
            const faceCls = r.look === 'ghost' ? 'face ghostf' : 'face';
            if (id === 'crows-nest') {
                const c = [52, 15, 63];
                art += poly(ring('z', c, 5.2, 18), 'barrel') + poly(ring('z', [52, 15, 66.4], 5.2, 18), 'barrel')
                    + [0, 3, 6, 9, 12, 15].map((i) => { const t = (i * 2 * Math.PI) / 18; return line([[52 + 5.2 * Math.cos(t), 15 + 5.2 * Math.sin(t), 63], [52 + 5.2 * Math.cos(t), 15 + 5.2 * Math.sin(t), 66.4]], 'post'); }).join('');
            }
            const g = `<g class="rm" data-room="${id}">${box(x, y, z, dx, dy, dz, faceCls)}${art}</g>`;
            add(depth(x + dx / 2, y + dy / 2, z + dz / 2) + 0.01, g);
        }
        // lawn deck: grass ticks, two mikan trees
        let lawn = poly([[34, 4, DECK + 0.6], [66, 4, DECK + 0.6], [66, 26, DECK + 0.6], [34, 26, DECK + 0.6]], 'lawn');
        for (let x = 36; x <= 64; x += 4) for (let y = 7; y <= 24; y += 4.5) lawn += line([[x, y, DECK + 0.6], [x + 0.4, y + 0.3, DECK + 1.6]], 'grass');
        add(depth(50, 15, DECK) - 4, lawn);
        for (const [tx, ty] of [[40, 21], [60, 9]]) {
            let tr = line([[tx, ty, DECK + 0.6], [tx, ty, DECK + 5.5]], 'trunk') + poly(ring('x', [tx, ty, DECK + 8], 3.8, 14), 'canopy') + poly(ring('y', [tx, ty, DECK + 8], 3.8, 14), 'canopy') + poly(ring('z', [tx, ty, DECK + 8], 3.8, 14), 'canopy');
            for (const [fx, fy, fz] of [[1.8, 1, 9.4], [-2, 0.6, 7.2], [0.6, -2.4, 8.6], [-0.4, 2.6, 7.4]]) tr += dot([tx + fx, ty + fy, DECK + fz], 1.7, 'fruit');
            add(depth(tx, ty, DECK + 6), tr);
        }
        // mast, yards, sails, shrouds, stays
        const sailQuad = (x, zt, zb, y0, y1) => poly([[x, y0, zt], [x, y1, zt], [x, y1 + 1.4, (zt + zb) / 2], [x, y1 - 1, zb], [x, (y0 + y1) / 2, zb - 1.2], [x, y0 + 1, zb], [x, y0 - 1.4, (zt + zb) / 2]], 'sail');
        let rig = line([[52, MID, DECK], [52, MID, 72]], 'mast');
        rig += sailQuad(52, 58, 44, 3, 27) + sailQuad(52, 43.4, 36, 5, 25) + line([[52, 2, 58.2], [52, 28, 58.2]], 'yard') + line([[52, 4, 43.6], [52, 26, 43.6]], 'yard');
        for (const z of [51, 54.5, 47.5]) rig += line([[52, 5, z], [52, 25, z + 0.4]], 'thin');
        rig += poly(ring('x', [52, MID, 51], 3.4, 16), 'emblem') + line([[52, MID - 5.4, 49.6], [52, MID + 5.4, 49.6]], 'emblem') + poly(ring('x', [52, MID, 49.6], 5.4, 16, 0, 1.2), 'emblem');
        for (const side of [-1, 1]) {
            const yd = MID + side * (halfBeam(52, DECK) - 0.6);
            rig += line([[52, MID, 58], [49, yd, DECK + 1]], 'shroud') + line([[52, MID, 58], [55, yd, DECK + 1]], 'shroud');
            for (let k = 1; k <= 5; k++) {
                const f = k / 6;
                const zz = 58 - f * (58 - DECK - 1);
                rig += line([[52 - 3 * f, MID + (yd - MID) * f, zz], [52 + 3 * f, MID + (yd - MID) * f, zz]], 'ratline');
            }
        }
        rig += line([[52, MID, 72], [-3, MID, 44]], 'rigl') + line([[52, MID, 72], [-3, MID, 40]], 'rigl') + line([[52, MID, 72], [98, MID, 58]], 'rigl');
        rig += line([[52, MID, 72], [52, MID, 76]], 'mast') + poly([[52, MID, 76], [52, MID, 73.4], [57.6, MID - 0.4, 74.6]], 'sail');
        add(depth(52, MID, 52) + 0.4, rig);
        // stern flag
        add(depth(98, MID, 62), line([[98, MID, 57.4], [98, MID, 68]], 'mast') + poly([[98, MID, 68], [98, MID, 64.2], [92.4, MID, 66]], 'sail'));
        // lion head
        const L = [-5.5, MID, 39];
        let lion = line([[-0.6, MID, DECK + 5], [L[0], MID, L[2] - 5], [L[0], MID, L[2] - 8]], 'thin');
        const mane = [];
        for (let i = 0; i < 40; i++) { const t = (i * 2 * Math.PI) / 40; const r = i % 2 ? 6.2 : 8.4; mane.push([L[0], L[1] + r * Math.cos(t), L[2] + r * Math.sin(t)]); }
        const mane2 = [];
        for (let i = 0; i < 24; i++) { const t = (i * 2 * Math.PI) / 24 + 0.12; const r = i % 2 ? 4.4 : 6; mane2.push([L[0] - 0.4, L[1] + r * Math.cos(t), L[2] + r * Math.sin(t)]); }
        lion += poly(mane, 'mane') + poly(mane2, 'mane2') + poly(ring('x', [L[0] - 1, L[1], L[2] - 0.5], 4, 18), 'lface')
            + poly(ring('x', [L[0] - 1.2, L[1] - 3.6, L[2] + 3.4], 1.5, 8), 'lear') + poly(ring('x', [L[0] - 1.2, L[1] + 3.6, L[2] + 3.4], 1.5, 8), 'lear')
            + poly(ring('x', [L[0] - 1.6, L[1] - 1.8, L[2] + 0.6], 0.7, 8), 'leye') + poly(ring('x', [L[0] - 1.6, L[1] + 1.8, L[2] + 0.6], 0.7, 8), 'leye')
            + poly([[L[0] - 2.4, L[1], L[2] - 0.2], [L[0] - 2.2, L[1] - 1.3, L[2] - 1.8], [L[0] - 2.2, L[1] + 1.3, L[2] - 1.8]], 'lnose')
            + line([[L[0] - 2.2, L[1], L[2] - 1.8], [L[0] - 2.2, L[1], L[2] - 3], [L[0] - 2.2, L[1] - 1.6, L[2] - 3.5]], 'thin') + line([[L[0] - 2.2, L[1], L[2] - 3], [L[0] - 2.2, L[1] + 1.6, L[2] - 3.5]], 'thin');
        add(depth(...L) + 0.02, lion);
        s += items.sort((a, b) => a.d - b.d).map((i) => i.str).join('');

        // ---- paddle wheel on the near side (outside the cut wall), with paddles and a housing arc ----
        const wc = [80, MID + halfBeam(80, 12) + 3, 12];
        let wheel = poly(ring('y', wc, 8.4, 24), 'wheel') + poly(ring('y', wc, 5.8, 24), 'wheel') + poly(ring('y', wc, 1.5, 10), 'wheel');
        for (let i = 0; i < 8; i++) {
            const t = (i * Math.PI) / 4;
            const c = Math.cos(t);
            const sn = Math.sin(t);
            wheel += line([[wc[0] + 1.5 * c, wc[1], wc[2] + 1.5 * sn], [wc[0] + 8.4 * c, wc[1], wc[2] + 8.4 * sn]], 'spoke');
            const tx = -sn;
            const tz = c;
            wheel += poly([[wc[0] + 8.4 * c - 1.6 * tx, wc[1], wc[2] + 8.4 * sn - 1.6 * tz], [wc[0] + 8.4 * c + 1.6 * tx, wc[1], wc[2] + 8.4 * sn + 1.6 * tz], [wc[0] + 10.4 * c + 1.6 * tx, wc[1], wc[2] + 10.4 * sn + 1.6 * tz], [wc[0] + 10.4 * c - 1.6 * tx, wc[1], wc[2] + 10.4 * sn - 1.6 * tz]], 'paddle');
        }
        wheel += line([[wc[0], wc[1], wc[2]], [wc[0], MID + halfBeam(80, 12), wc[2]]], 'thin');
        s += wheel;

        // ---- dimension lines, drafting style ----
        if (dims) {
            const arrow = (a, b) => { // arrow head at a, pointing away from b, in screen space
                const pa = P(...a);
                const pb = P(...b);
                const d = Math.hypot(pb[0] - pa[0], pb[1] - pa[1]) || 1;
                const ux = (pb[0] - pa[0]) / d;
                const uy = (pb[1] - pa[1]) / d;
                return `<polygon class="dimh" points="${f1(pa[0])},${f1(pa[1])} ${f1(pa[0] + ux * 9 - uy * 3.2)},${f1(pa[1] + uy * 9 + ux * 3.2)} ${f1(pa[0] + ux * 9 + uy * 3.2)},${f1(pa[1] + uy * 9 - ux * 3.2)}"/>`;
            };
            const dim = (a, b, ea, eb, label) => {
                const m = P((a[0] + b[0]) / 2, (a[1] + b[1]) / 2, (a[2] + b[2]) / 2);
                return line([ea[0], a], 'dimx') + line([eb[0], b], 'dimx') + line([a, b], 'dimline') + arrow(a, b) + arrow(b, a)
                    + `<text class="dimt" x="${f1(m[0])}" y="${f1(m[1] - 7)}" text-anchor="middle">${label}</text>`;
            };
            s += dim([0, 40, -6], [100, 40, -6], [[0, 31, -6]], [[100, 31, -6]], 'LENGTH 100');
            s += dim([106, 4, -6], [106, 26, -6], [[101, 4, -6]], [[101, 26, -6]], 'TRANSOM 22');
            s += dim([108, 34, 0], [108, 34, 76], [[102, 34, 0]], [[54, 34, 76]], 'MAST 76');
        }
        return s;
    };

    return { ROOMS, AREA, fit, draw, anchor, bounds, project: (cam, v, x, y, z) => make(cam, v).P(x, y, z), DECK, MID };
})();
