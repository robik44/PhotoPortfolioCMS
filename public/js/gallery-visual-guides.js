/* Alignment calculation for FoodFoto visual gallery builder.
 * Framework-independent: browser global plus CommonJS for node:test.
 * Inputs/outputs use native, unscaled canvas pixels.
 */
(function (root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    if (root) root.FoodFotoGalleryGuides = api;
})(typeof window !== 'undefined' ? window : null, function () {
    'use strict';

    function anchors(rect, axis) {
        const a = axis === 'x' ? rect.x : rect.y;
        const size = axis === 'x' ? rect.w : rect.h;
        return [a, a + size / 2, a + size];
    }

    function nearestMatch(rect, peers, axis, distance) {
        const own = anchors(rect, axis);
        let best = null;
        for (const other of peers) {
            const their = anchors(other, axis);
            for (const candidate of own) {
                for (const target of their) {
                    const delta = target - candidate;
                    const error = Math.abs(delta);
                    if (error <= distance && (!best || error < best.error)) {
                        best = { delta, error, guide: target };
                    }
                }
            }
        }
        return best;
    }

    /**
     * Return snapped position AND optional alignment guides.
     * Guides can be shown independently of snapping.
     */
    function align(rect, peers, {threshold = 7, snap = true, guides = true} = {}) {
        const xMatch = nearestMatch(rect, peers, 'x', threshold);
        const yMatch = nearestMatch(rect, peers, 'y', threshold);
        return {
            x: rect.x + (snap && xMatch ? xMatch.delta : 0),
            y: rect.y + (snap && yMatch ? yMatch.delta : 0),
            vertical: guides && xMatch ? xMatch.guide : null,
            horizontal: guides && yMatch ? yMatch.guide : null,
        };
    }

    return { align, nearestMatch, anchors };
});
