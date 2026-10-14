/* FoodFoto private editorial preview editor — no public page interaction. */
(function () {
    'use strict';
    const root = document.querySelector('[data-foodfoto-layout]');
    if (!root) return;
    const nodes = new Map(Array.from(root.querySelectorAll('[data-move-id]')).map(el => [el.dataset.moveId, el]));
    const initial = JSON.parse(root.dataset.positions || '{}');
    let positions = {};
    for (const key of nodes.keys()) {
        const pos = initial[key] || {};
        positions[key] = {x: Number(pos.x) || 0, y: Number(pos.y) || 0};
    }
    const saved = JSON.parse(JSON.stringify(positions));
    const selected = new Set();
    const history = [];
    let editing = false;
    let drag = null;
    const toggle = document.getElementById('ff-layout-toggle');
    const controls = document.getElementById('ff-layout-controls');
    const saveForm = document.getElementById('ff-layout-form');
    const saveValue = document.getElementById('ff-layout-json');
    const guideX = document.getElementById('ff-guide-x');
    const guideY = document.getElementById('ff-guide-y');
    const status = document.getElementById('ff-layout-status');
    const snapToggle = document.getElementById('ff-layout-snap');
    const clone = p => JSON.parse(JSON.stringify(p));
    const clamp = n => Math.max(-350, Math.min(350, Math.round(n)));
    function apply() {
        for (const [id, el] of nodes) {
            const p = positions[id] || {x:0,y:0};
            el.style.translate = p.x + 'px ' + p.y + 'px';
            el.classList.toggle('ff-selected', editing && selected.has(id));
            el.classList.toggle('ff-movable', editing);
        }
    }
    function note(message) { status.textContent = message; }
    function guides(x, y) {
        guideX.hidden = x == null; guideY.hidden = y == null;
        if (x != null) guideX.style.left = x + 'px';
        if (y != null) guideY.style.top = y + 'px';
    }
    function clearSelection() { selected.clear(); apply(); }
    function snapshot() { history.push(clone(positions)); if (history.length > 50) history.shift(); }
    function pick(el, additive) {
        const id = el.dataset.moveId;
        if (additive) {
            if (selected.has(id)) selected.delete(id); else selected.add(id);
        } else if (!selected.has(id)) {
            selected.clear(); selected.add(id);
        }
        apply();
    }
    function setEditing(active) {
        editing = active;
        root.classList.toggle('ff-layout-editing', active);
        controls.hidden = !active;
        toggle.setAttribute('aria-pressed', String(active));
        toggle.textContent = active ? 'Zakończ układanie' : 'Przesuwaj elementy ↔';
        if (!active) { selected.clear(); guides(null, null); }
        apply();
        note(active ? 'Kliknij element. Shift + klik zaznacza kilka. Przeciągnij, aby przesunąć.' : '');
    }
    toggle.addEventListener('click', () => setEditing(!editing));
    root.addEventListener('pointerdown', e => {
        if (!editing || e.button !== 0) return;
        const el = e.target.closest('[data-move-id]');
        if (!el || !root.contains(el)) { clearSelection(); return; }
        if (e.shiftKey) { pick(el, true); e.preventDefault(); return; }
        pick(el, false);
        if (!selected.size) return;
        e.preventDefault();
        snapshot();
        const initialPositions = clone(positions);
        const rect = root.getBoundingClientRect();
        drag = { pointerId:e.pointerId, startX:e.clientX, startY:e.clientY, initialPositions, rect };
        el.setPointerCapture(e.pointerId);
        document.documentElement.classList.add('ff-dragging');
    });
    root.addEventListener('pointermove', e => {
        if (!drag || e.pointerId !== drag.pointerId) return;
        const dx = e.clientX - drag.startX, dy = e.clientY - drag.startY;
        for (const id of selected) {
            positions[id] = {x:clamp(drag.initialPositions[id].x+dx), y:clamp(drag.initialPositions[id].y+dy)};
        }
        apply();
        let bestX = null, bestY = null;
        if (snapToggle.checked && selected.size === 1) {
            const moving = nodes.get(Array.from(selected)[0]);
            const r = moving.getBoundingClientRect();
            let distX = 7, distY = 7;
            for (const [id, other] of nodes) {
                if (selected.has(id)) continue;
                const o = other.getBoundingClientRect();
                for (const [a,b] of [[r.left,o.left],[r.right,o.right],[r.left,o.right],[r.right,o.left],[r.left+r.width/2,o.left+o.width/2]]) {
                    if (Math.abs(a-b)<distX) {distX=Math.abs(a-b); bestX={adjust:b-a,screen:b};}
                }
                for (const [a,b] of [[r.top,o.top],[r.bottom,o.bottom],[r.top+r.height/2,o.top+o.height/2]]) {
                    if (Math.abs(a-b)<distY) {distY=Math.abs(a-b); bestY={adjust:b-a,screen:b};}
                }
            }
            const id = moving.dataset.moveId;
            if (bestX) positions[id].x=clamp(positions[id].x+bestX.adjust);
            if (bestY) positions[id].y=clamp(positions[id].y+bestY.adjust);
            apply();
        }
        const bounds = root.getBoundingClientRect();
        guides(bestX ? bestX.screen-bounds.left : null,bestY ? bestY.screen-bounds.top : null);
        note('Zaznaczono: '+selected.size+' · Przesunięcie: '+Math.round(dx)+' / '+Math.round(dy)+' px');
    });
    const stop = e => {
        if (!drag || e.pointerId !== drag.pointerId) return;
        drag = null; guides(null,null);
        document.documentElement.classList.remove('ff-dragging');
    };
    root.addEventListener('pointerup', stop);
    root.addEventListener('pointercancel', stop);
    document.getElementById('ff-layout-undo').addEventListener('click', () => {
        if (history.length) { positions=history.pop(); apply(); note('Cofnięto ostatnią zmianę.'); }
    });
    document.getElementById('ff-layout-reset').addEventListener('click', () => {
        if (!window.confirm('Przywrócić pozycje wyjściowe wszystkich elementów? Zmiany zapiszą się dopiero po kliknięciu Zapisz układ.')) return;
        snapshot();
        positions = Object.fromEntries(Array.from(nodes.keys()).map(id=>[id,{x:0,y:0}]));
        apply(); note('Przywrócono pozycje wyjściowe.');
    });
    document.getElementById('ff-layout-discard').addEventListener('click', () => {
        positions=clone(saved); history.length=0; apply(); note('Odrzucono niezapisane przesunięcia.');
    });
    document.addEventListener('keydown', e => {
        if (!editing || e.target.matches('input,textarea,select') || e.target.isContentEditable) return;
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase()==='z') {
            e.preventDefault(); document.getElementById('ff-layout-undo').click(); return;
        }
        const keys={ArrowLeft:[-1,0],ArrowRight:[1,0],ArrowUp:[0,-1],ArrowDown:[0,1]};
        if (!keys[e.key] || !selected.size) return;
        e.preventDefault(); snapshot();
        const step=e.shiftKey?10:1;
        for(const id of selected) {
            positions[id].x=clamp(positions[id].x+keys[e.key][0]*step);
            positions[id].y=clamp(positions[id].y+keys[e.key][1]*step);
        }
        apply();
    });
    saveForm.addEventListener('submit', () => {
        saveValue.value=JSON.stringify(positions);
    });
    apply();
})();
