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
      const conflicts = rows.filter(other =>
        other.id !== source.id &&
        overlapsX(source, other) &&
        // Detect real vertical intersection, even when an image begins
        // far above the new text. The old 60px cutoff made a tiny drag
        // suddenly turn reflow off and let the text overlap the image.
        other.y < bottom + gap - .5 &&
        other.y + other.height > source.y + .5 &&
        (other.mode !== 'auto' || source.index > other.index)
      );
      if (conflicts.length) {
        const firstY = Math.min(...conflicts.map(row => row.y));
        const rowStart = Math.min(firstY, ...rows.filter(other =>
          other.id !== source.id &&
          other.y >= firstY - 24 && other.y <= firstY + 24
        ).map(row => row.y));
        events.push({ at: rowStart, delta: 0, sourceId: source.id,
          kind: 'collision', targets: conflicts.map(row => row.id) });
      } else {
        const delta = source.height - baseline;
        if (Math.abs(delta) > .5) events.push({
          at: source.y + baseline, delta, sourceId: source.id,
          kind: 'growth', targets: []
        });
      }
    }

    events.sort((a, b) => a.at - b.at);
    const grouped = [];
    for (const event of events) {
      const last = grouped[grouped.length - 1];
      // Multiple columns of one row share a single displacement.
      if (last && last.kind === 'collision' && event.kind === 'collision'
        && Math.abs(event.at - last.at) <= 12) {
        last.at = Math.min(last.at, event.at);
        last.sources.push({id: event.sourceId, targets: event.targets});
      } else {
        grouped.push({...event,
          sources: [{id: event.sourceId, targets: event.targets}]});
      }
    }
    const rowById = new Map(rows.map(row => [row.id, row]));
    const layout = () => rows.map(row => {
      const contributions = {};
      for (const event of grouped) {
        if (row.y < event.at - .1 ||
          (event.kind === 'collision' &&
            event.sources.some(source => source.id === row.id)) ||
          (event.kind !== 'collision' && row.id === event.sourceId)) continue;
        contributions[event.sourceId] = (contributions[event.sourceId] || 0) + event.delta;
      }
      const shift = Object.values(contributions).reduce((sum, x) => sum + x, 0);
      const anchor = row.anchorContributions
        ? Object.values(row.anchorContributions).reduce((sum, x) => sum + n(x), 0)
        : row.anchor;
      return {id: row.id, top: row.y + shift - anchor, shift, contributions};
    });

    // Resolve against the visible rectangles, not just the saved Y values:
    // a previously dragged card can have a non-zero anchor.
    // This prevents a new block from sitting over those anchored cards.
    for (let iteration = 0; iteration < 12; iteration++) {
      const positions = new Map(layout().map(row => [row.id, row.top]));
      let changed = false;
      for (const event of grouped) {
        if (event.kind !== 'collision') continue;
        let required = 0;
        let owner = event.sourceId;
        for (const sourceEvent of event.sources) {
          const source = rowById.get(sourceEvent.id);
          if (!source) continue;
          const bottom = positions.get(source.id) + source.height + gap;
          for (const targetId of sourceEvent.targets) {
            if (!positions.has(targetId) || event.sources.some(x => x.id === targetId)) continue;
            const deficit = bottom - positions.get(targetId);
            if (deficit > required) {
              required = deficit;
              owner = source.id;
            }
          }
        }
        if (required > .5) {
          event.delta += required;
          event.sourceId = owner;
          changed = true;
        }
      }
      if (!changed) break;
    }
    const placements = layout();

    return { placements, shifts: grouped };
  }

  window.BuilderAutoFlow = { calculate: calculateAutoFlow };
})();
