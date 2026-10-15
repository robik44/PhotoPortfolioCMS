// Shared layout algorithm for the builder and published page.
// Only an element explicitly marked "auto" can create displacement.
// Other elements retain their stored geometry and move together by row.
(function () {
  'use strict';

  function calculateAutoFlow(items, gap = 14) {
    const n = (value, fallback = 0) => value !== null && value !== '' && Number.isFinite(Number(value))
      ? Number(value) : fallback;
    const rows = items.map((v, index) => ({
      id: String(v.id), index,
      type: String(v.type || ''),
      x: n(v.x), y: n(v.y),
      width: Math.max(0, n(v.width)), height: Math.max(0, n(v.height)),
      baseline: Math.max(0, n(v.baseline)),
      anchor: n(v.anchor), anchorContributions: v.anchorContributions &&
        typeof v.anchorContributions === 'object' && !Array.isArray(v.anchorContributions)
          ? v.anchorContributions : null,
      mode: v.mode
    })).sort((a, b) => a.y - b.y || a.index - b.index);
    const overlapsX = (a, b) =>
      Math.min(a.x + a.width, b.x + b.width) - Math.max(a.x, b.x) > 1;
    const events = [];

    for (const source of rows) {
      if (source.mode !== 'auto' || source.height <= 0) continue;
      const baseline = source.baseline > 0 ? source.baseline : source.height;
      const bottom = source.y + source.height;
      // A text block dropped across a row of photos may begin slightly AFTER
      // the photo row. Such a row still has to be pushed down.
      const conflicts = rows.filter(other =>
        other.id !== source.id &&
        overlapsX(source, other) &&
        other.y >= source.y - 60 &&
        other.y < bottom + gap - .5 &&
        other.y + other.height > source.y + .5 &&
        // When two auto blocks overlap, the newer block is the insertion.
        (other.mode !== 'auto' || source.index > other.index)
      );

      if (conflicts.length) {
        const firstY = Math.min(...conflicts.map(row => row.y));
        // Keep entire gallery/photo rows together even when positions vary.
        const rowStart = Math.min(firstY, ...rows.filter(other =>
          other.id !== source.id &&
          other.y >= firstY - 24 && other.y <= firstY + 24
        ).map(row => row.y));
        const delta = Math.max(0, bottom + gap - rowStart);
        if (delta > .5) events.push({ at: rowStart, delta, sourceId: source.id });
      } else {
        const delta = source.height - baseline;
        if (Math.abs(delta) > .5) {
          events.push({ at: source.y + baseline, delta, sourceId: source.id });
        }
      }
    }

    // Two auto texts pushing the same row must not double its displacement.
    events.sort((a, b) => a.at - b.at);
    const grouped = [];
    for (const event of events) {
      const last = grouped[grouped.length - 1];
      if (last && Math.abs(event.at - last.at) <= 12) {
        if (event.delta > last.delta) {
          grouped[grouped.length - 1] = { ...event };
        }
      } else {
        grouped.push({ ...event });
      }
    }

    const placements = rows.map(row => {
      const contributions = {};
      for (const event of grouped) {
        if (row.id === event.sourceId || row.y < event.at - .1) continue;
        contributions[event.sourceId] = (contributions[event.sourceId] || 0) + event.delta;
      }
      const shift = Object.values(contributions).reduce((sum, x) => sum + x, 0);
      const anchor = row.anchorContributions
        ? Object.values(row.anchorContributions).reduce((sum, x) => sum + n(x), 0)
        : row.anchor;
      return { id: row.id, top: row.y + shift - anchor, shift, contributions };
    });

    return { placements, shifts: grouped };
  }

  window.BuilderAutoFlow = { calculate: calculateAutoFlow };
})();
