(() => {
    'use strict';

    const BREAKPOINTS = ['desktop', 'tablet', 'mobile'];
    const PRESETS = {
        h1: {label: 'H1 główny', font_size: 52.4, font_weight: 300, line_height: 0.95, letter_spacing: -0.02, letter_spacing_unit: 'em'},
        h2: {label: 'H2', font_size: 38.4, font_weight: 300, line_height: 1.02, letter_spacing: -0.01, letter_spacing_unit: 'em'},
        h3: {label: 'H3', font_size: 27.2, font_weight: 400, line_height: 1.1, letter_spacing: 0, letter_spacing_unit: 'em'},
        lead: {label: 'Lead', font_size: 22.4, font_weight: 300, line_height: 1.35, letter_spacing: 0, letter_spacing_unit: 'em'},
        body: {label: 'Body', font_size: 18, font_weight: 400, line_height: 1.55, letter_spacing: 0, letter_spacing_unit: 'em'},
        small: {label: 'Small', font_size: 14.4, font_weight: 400, line_height: 1.45, letter_spacing: 0.01, letter_spacing_unit: 'em'},
        caption: {label: 'Caption', font_size: 12.8, font_weight: 400, line_height: 1.35, letter_spacing: 0.02, letter_spacing_unit: 'em'}
    };

    const number = (value, fallback = null) => {
        if (value === null || value === undefined || value === '') return fallback;
        const normalized = typeof value === 'string' ? value.replace(',', '.') : value;
        const parsed = Number(normalized);
        return Number.isFinite(parsed) ? parsed : fallback;
    };

    const compact = (object) => Object.fromEntries(
        Object.entries(object || {}).filter(([, value]) => value !== null && value !== undefined && value !== '')
    );

    window.SiteTypography = {
        create(catalog) {
            const textTypes = new Set(catalog.textTypes);
            const css = (id) => typeof id === 'string' && Object.hasOwn(catalog.families, id)
                ? catalog.families[id] : catalog.families.Arial;

            const base = (item) => {
                const style = item.style || {};
                return {
                    font_family: style.font_family || 'Arial',
                    font_size: number(style.font_size, item.type === 'heading' ? 42 : 18),
                    font_weight: number(style.font_weight, 400),
                    font_style: style.font_style || 'normal',
                    text_transform: style.text_transform || 'none',
                    text_align: style.text_align || 'left',
                    line_height: number(style.line_height, 1.4),
                    line_height_unit: style.line_height_unit || 'unitless',
                    letter_spacing: number(style.letter_spacing, 0),
                    letter_spacing_unit: style.letter_spacing_unit || 'px',
                    word_spacing: number(style.word_spacing, 0),
                    color: style.color || '#222222',
                    opacity: number(style.opacity, 100),
                    paragraph_spacing: number(style.paragraph_spacing, 0),
                    margin_top: number(style.margin_top, 0),
                    margin_bottom: number(style.margin_bottom, 0),
                    text_width: style.text_width ?? null,
                    text_width_unit: style.text_width_unit || '%',
                    max_width: style.max_width ?? null,
                    max_line_length: style.max_line_length ?? null,
                    offset_x: number(style.offset_x, 0),
                    offset_y: number(style.offset_y, 0)
                };
            };

            const override = (item, breakpoint) => compact(item.typography?.[breakpoint] || {});

            const resolved = (item, breakpoint = 'desktop') => {
                let values = {...base(item), ...override(item, 'desktop')};
                if (breakpoint === 'tablet' || breakpoint === 'mobile') values = {...values, ...override(item, 'tablet')};
                if (breakpoint === 'mobile') values = {...values, ...override(item, 'mobile')};
                return values;
            };

            const ensureBreakpoint = (item, breakpoint) => {
                item.typography ??= {};
                item.typography[breakpoint] ??= {};
                return item.typography[breakpoint];
            };

            const cleanup = (item, breakpoint) => {
                if (!item.typography?.[breakpoint]) return;
                item.typography[breakpoint] = compact(item.typography[breakpoint]);
                if (!Object.keys(item.typography[breakpoint]).length) delete item.typography[breakpoint];
                if (item.typography && !Object.keys(item.typography).length) delete item.typography;
            };

            const setValue = (item, breakpoint, key, value) => {
                if (value === '' || value === null || value === undefined || Number.isNaN(value)) {
                    if (item.typography?.[breakpoint]) delete item.typography[breakpoint][key];
                    cleanup(item, breakpoint);
                    return;
                }
                ensureBreakpoint(item, breakpoint)[key] = value;
            };

            const apply = (node, item, breakpoint = 'desktop') => {
                if (!textTypes.has(item.type)) return;
                const values = resolved(item, breakpoint);
                node.style.fontFamily = css(values.font_family);

                const fluid = item.typography?.mode === 'fluid' ? item.typography?.fluid : null;
                if (breakpoint === 'desktop' && fluid && number(fluid.min_size) && number(fluid.max_size) && number(fluid.vw)) {
                    node.style.fontSize = `clamp(${number(fluid.min_size)}px, ${number(fluid.vw)}vw, ${number(fluid.max_size)}px)`;
                } else {
                    node.style.fontSize = number(values.font_size, item.type === 'heading' ? 42 : 18) + 'px';
                }

                node.style.fontWeight = String(Math.max(100, Math.min(900, number(values.font_weight, 400))));
                node.style.fontStyle = values.font_style || 'normal';
                node.style.textTransform = values.text_transform || 'none';
                node.style.textAlign = values.text_align || 'left';
                node.style.lineHeight = String(number(values.line_height, 1.4)) + (values.line_height_unit === 'px' ? 'px' : '');
                node.style.letterSpacing = number(values.letter_spacing, 0) + (values.letter_spacing_unit === 'em' ? 'em' : 'px');
                node.style.wordSpacing = number(values.word_spacing, 0) + 'px';
                node.style.color = values.color || '#222222';
                node.style.opacity = String(Math.max(0, Math.min(100, number(values.opacity, 100))) / 100);
                node.style.marginTop = number(values.margin_top, 0) + 'px';
                node.style.marginBottom = number(values.margin_bottom, 0) + 'px';

                if (values.text_width !== null && values.text_width !== undefined && values.text_width !== '') {
                    node.style.width = number(values.text_width, 100) + (values.text_width_unit === 'px' ? 'px' : '%');
                } else {
                    node.style.width = '100%';
                }

                if (values.max_line_length !== null && values.max_line_length !== undefined && values.max_line_length !== '') {
                    node.style.maxWidth = number(values.max_line_length) + 'ch';
                } else if (values.max_width !== null && values.max_width !== undefined && values.max_width !== '') {
                    node.style.maxWidth = number(values.max_width) + 'px';
                } else {
                    node.style.maxWidth = '';
                }

                const x = number(values.offset_x, 0);
                const y = number(values.offset_y, 0);
                node.style.transform = x || y ? `translate(${x}px, ${y}px)` : '';
            };

            const makeField = (container, className, labelText, input, suffix = '') => {
                const wrapper = document.createElement('label');
                wrapper.className = className;
                wrapper.textContent = labelText;
                input.setAttribute('aria-label', labelText);
                wrapper.appendChild(input);
                if (suffix) {
                    const unit = document.createElement('span');
                    unit.textContent = suffix;
                    unit.style.marginLeft = '6px';
                    unit.style.fontSize = '12px';
                    unit.style.color = '#777';
                    wrapper.appendChild(unit);
                }
                container.appendChild(wrapper);
                return input;
            };

            const numeric = (container, className, label, value, options, onInput) => {
                const input = document.createElement('input');
                input.type = 'number';
                input.inputMode = 'decimal';
                input.step = options.step ?? 'any';
                if (options.min !== undefined) input.min = String(options.min);
                if (options.max !== undefined) input.max = String(options.max);
                input.value = value ?? '';
                input.placeholder = options.placeholder || '';
                input.addEventListener('input', () => onInput(input.value === '' ? '' : number(input.value, '')));
                return makeField(container, className, label, input, options.suffix || '');
            };

            const select = (container, className, label, value, choices, onChange) => {
                const input = document.createElement('select');
                for (const [id, title] of choices) {
                    const option = document.createElement('option');
                    option.value = id;
                    option.textContent = title;
                    input.appendChild(option);
                }
                input.value = value ?? '';
                input.addEventListener('change', () => onChange(input.value));
                return makeField(container, className, label, input);
            };

            const color = (container, className, label, value, onInput) => {
                const input = document.createElement('input');
                input.type = 'color';
                input.value = /^#[0-9a-f]{6}$/i.test(value || '') ? value : '#222222';
                input.addEventListener('input', () => onInput(input.value));
                return makeField(container, className, label, input);
            };

            const button = (container, text, onClick) => {
                const control = document.createElement('button');
                control.type = 'button';
                control.className = 'fve-button';
                control.textContent = text;
                control.style.margin = '4px 4px 8px 0';
                control.addEventListener('click', onClick);
                container.appendChild(control);
                return control;
            };

            const panel = (container, item, render, className) => {
                if (!textTypes.has(item.type)) return;

                const root = document.createElement('div');
                root.className = 'builder-typography-panel';
                root.style.borderTop = '1px solid #e5e5e5';
                root.style.paddingTop = '14px';
                root.style.marginTop = '14px';

                const title = document.createElement('div');
                title.textContent = 'Typografia';
                title.style.fontWeight = '700';
                title.style.marginBottom = '10px';
                root.appendChild(title);

                let active = root.dataset.breakpoint || 'desktop';
                const tabs = document.createElement('div');
                tabs.style.display = 'grid';
                tabs.style.gridTemplateColumns = 'repeat(3, 1fr)';
                tabs.style.gap = '6px';
                tabs.style.marginBottom = '12px';
                root.appendChild(tabs);

                const fields = document.createElement('div');
                root.appendChild(fields);

                const rerenderPanel = () => {
                    tabs.innerHTML = '';
                    for (const breakpoint of BREAKPOINTS) {
                        const tab = document.createElement('button');
                        tab.type = 'button';
                        tab.className = 'fve-button';
                        tab.textContent = breakpoint === 'desktop' ? 'Desktop' : breakpoint === 'tablet' ? 'Tablet' : 'Mobile';
                        if (breakpoint === active) tab.classList.add('dark');
                        tab.addEventListener('click', () => {
                            active = breakpoint;
                            drawFields();
                            rerenderPanel();
                        });
                        tabs.appendChild(tab);
                    }
                };

                const changed = () => {
                    render();
                };

                const drawFields = () => {
                    fields.innerHTML = '';
                    const own = item.typography?.[active] || {};
                    const current = resolved(item, active);
                    const inheritedLabel = active === 'desktop' ? 'Dotychczasowa / domyślna' : 'Dziedzicz';

                    select(fields, className, 'Preset stylu', '', [
                        ['', '— wybierz —'],
                        ...Object.entries(PRESETS).map(([id, preset]) => [id, preset.label])
                    ], presetId => {
                        if (!presetId) return;
                        Object.assign(ensureBreakpoint(item, active), PRESETS[presetId]);
                        delete item.typography[active].label;
                        changed();
                        drawFields();
                    });

                    const familyChoices = active === 'desktop'
                        ? [['', inheritedLabel], ...catalog.choices.map(choice => [choice.value, choice.label])]
                        : [['', inheritedLabel], ...catalog.choices.map(choice => [choice.value, choice.label])];
                    select(fields, className, 'Rodzaj czcionki', own.font_family ?? '', familyChoices, value => {
                        setValue(item, active, 'font_family', value);
                        changed();
                    });

                    numeric(fields, className, 'Rozmiar czcionki', own.font_size ?? '', {
                        step: 0.1, min: 1, max: 300, placeholder: String(current.font_size), suffix: 'px'
                    }, value => {
                        setValue(item, active, 'font_size', value);
                        changed();
                    });

                    select(fields, className, 'Grubość czcionki', own.font_weight ?? '', [
                        ['', inheritedLabel],
                        ...[100,200,300,400,500,600,700,800,900].map(value => [String(value), String(value)])
                    ], value => {
                        setValue(item, active, 'font_weight', value === '' ? '' : Number(value));
                        changed();
                    });

                    select(fields, className, 'Styl', own.font_style ?? '', [
                        ['', inheritedLabel], ['normal', 'Normal'], ['italic', 'Italic']
                    ], value => {
                        setValue(item, active, 'font_style', value);
                        changed();
                    });

                    select(fields, className, 'Wielkość liter', own.text_transform ?? '', [
                        ['', inheritedLabel], ['none', 'Bez zmian'], ['uppercase', 'UPPERCASE'],
                        ['lowercase', 'lowercase'], ['capitalize', 'Capitalize']
                    ], value => {
                        setValue(item, active, 'text_transform', value);
                        changed();
                    });

                    select(fields, className, 'Wyrównanie tekstu', own.text_align ?? '', [
                        ['', inheritedLabel], ['left', 'Do lewej'], ['center', 'Do środka'],
                        ['right', 'Do prawej'], ['justify', 'Justuj']
                    ], value => {
                        setValue(item, active, 'text_align', value);
                        changed();
                    });

                    numeric(fields, className, 'Wysokość linii', own.line_height ?? '', {
                        step: 0.05, min: 0.1, max: 10, placeholder: String(current.line_height)
                    }, value => {
                        setValue(item, active, 'line_height', value);
                        changed();
                    });
                    select(fields, className, 'Jednostka interlinii', own.line_height_unit ?? '', [
                        ['', inheritedLabel], ['unitless', 'Bez jednostki'], ['px', 'px']
                    ], value => {
                        setValue(item, active, 'line_height_unit', value);
                        changed();
                    });

                    numeric(fields, className, 'Odstęp między literami', own.letter_spacing ?? '', {
                        step: 0.01, min: -10, max: 20, placeholder: String(current.letter_spacing)
                    }, value => {
                        setValue(item, active, 'letter_spacing', value);
                        changed();
                    });
                    select(fields, className, 'Jednostka trackingu', own.letter_spacing_unit ?? '', [
                        ['', inheritedLabel], ['px', 'px'], ['em', 'em']
                    ], value => {
                        setValue(item, active, 'letter_spacing_unit', value);
                        changed();
                    });

                    numeric(fields, className, 'Odstęp między słowami (px)', own.word_spacing ?? '', {
                        step: 0.1, min: -50, max: 100, placeholder: String(current.word_spacing)
                    }, value => {
                        setValue(item, active, 'word_spacing', value);
                        changed();
                    });

                    color(fields, className, 'Kolor tekstu', own.color ?? current.color, value => {
                        setValue(item, active, 'color', value);
                        changed();
                    });

                    numeric(fields, className, 'Opacity tekstu (%)', own.opacity ?? '', {
                        step: 1, min: 0, max: 100, placeholder: String(current.opacity)
                    }, value => {
                        setValue(item, active, 'opacity', value);
                        changed();
                    });

                    const advanced = document.createElement('details');
                    advanced.style.marginTop = '10px';
                    const summary = document.createElement('summary');
                    summary.textContent = 'Zaawansowane';
                    summary.style.cursor = 'pointer';
                    summary.style.fontWeight = '600';
                    advanced.appendChild(summary);
                    fields.appendChild(advanced);

                    numeric(advanced, className, 'Odstęp między akapitami (px)', own.paragraph_spacing ?? '', {
                        step: 1, min: 0, max: 500, placeholder: String(current.paragraph_spacing)
                    }, value => { setValue(item, active, 'paragraph_spacing', value); changed(); });

                    numeric(advanced, className, 'Margines nad tekstem (px)', own.margin_top ?? '', {
                        step: 1, min: -500, max: 500, placeholder: String(current.margin_top)
                    }, value => { setValue(item, active, 'margin_top', value); changed(); });

                    numeric(advanced, className, 'Margines pod tekstem (px)', own.margin_bottom ?? '', {
                        step: 1, min: -500, max: 500, placeholder: String(current.margin_bottom)
                    }, value => { setValue(item, active, 'margin_bottom', value); changed(); });

                    numeric(advanced, className, 'Szerokość tekstu', own.text_width ?? '', {
                        step: 0.1, min: 0, max: 2000, placeholder: current.text_width ?? ''
                    }, value => { setValue(item, active, 'text_width', value); changed(); });

                    select(advanced, className, 'Jednostka szerokości', own.text_width_unit ?? '', [
                        ['', inheritedLabel], ['%', '%'], ['px', 'px']
                    ], value => { setValue(item, active, 'text_width_unit', value); changed(); });

                    numeric(advanced, className, 'Maksymalna szerokość (px)', own.max_width ?? '', {
                        step: 1, min: 0, max: 3000, placeholder: current.max_width ?? ''
                    }, value => { setValue(item, active, 'max_width', value); changed(); });

                    numeric(advanced, className, 'Maks. długość wiersza (ch)', own.max_line_length ?? '', {
                        step: 1, min: 10, max: 120, placeholder: current.max_line_length ?? ''
                    }, value => { setValue(item, active, 'max_line_length', value); changed(); });

                    numeric(advanced, className, 'Offset X (px)', own.offset_x ?? '', {
                        step: 1, min: -1000, max: 1000, placeholder: String(current.offset_x)
                    }, value => { setValue(item, active, 'offset_x', value); changed(); });

                    numeric(advanced, className, 'Offset Y (px)', own.offset_y ?? '', {
                        step: 1, min: -1000, max: 1000, placeholder: String(current.offset_y)
                    }, value => { setValue(item, active, 'offset_y', value); changed(); });

                    if (active === 'desktop') {
                        const fluidTitle = document.createElement('div');
                        fluidTitle.textContent = 'Responsywne skalowanie';
                        fluidTitle.style.fontWeight = '600';
                        fluidTitle.style.marginTop = '12px';
                        advanced.appendChild(fluidTitle);

                        select(advanced, className, 'Tryb rozmiaru', item.typography?.mode ?? 'manual', [
                            ['manual', 'Manual — breakpointy'], ['fluid', 'Fluid — clamp()']
                        ], value => {
                            item.typography ??= {};
                            item.typography.mode = value;
                            changed();
                            drawFields();
                        });

                        if (item.typography?.mode === 'fluid') {
                            item.typography.fluid ??= {};
                            numeric(advanced, className, 'Fluid — minimum (px)', item.typography.fluid.min_size ?? '', {
                                step: 0.1, min: 1, max: 300
                            }, value => { item.typography.fluid.min_size = value; changed(); });
                            numeric(advanced, className, 'Fluid — skala (vw)', item.typography.fluid.vw ?? '', {
                                step: 0.1, min: 0.1, max: 20
                            }, value => { item.typography.fluid.vw = value; changed(); });
                            numeric(advanced, className, 'Fluid — maksimum (px)', item.typography.fluid.max_size ?? '', {
                                step: 0.1, min: 1, max: 300
                            }, value => { item.typography.fluid.max_size = value; changed(); });
                        }
                    }

                    button(fields, active === 'desktop' ? 'Reset typografii Desktop' : `Reset ${active}`, () => {
                        if (item.typography) {
                            delete item.typography[active];
                            if (!Object.keys(item.typography).length) delete item.typography;
                        }
                        changed();
                        drawFields();
                    });

                    if (item.typography) {
                        button(fields, 'Reset całej typografii', () => {
                            delete item.typography;
                            changed();
                            drawFields();
                        });
                    }
                };

                rerenderPanel();
                drawFields();
                container.appendChild(root);
            };

            return {
                css,
                resolved,
                panel,
                captionFields(container, item, render, className) {
                    if (!['image', 'gallery'].includes(item.type)) return;
                    const add = (labelText, input) => makeField(container, className, labelText, input);
                    if (item.type === 'image') {
                        const text = document.createElement('textarea');
                        text.value = item.caption ?? '';
                        text.addEventListener('input', () => { item.caption = text.value; render(); });
                        add('Podpis / opis zdjęcia', text);
                    }
                    const prefix = item.type === 'gallery' ? 'Opis zdjęcia w podglądzie' : 'Podpis zdjęcia';
                    const family = document.createElement('select');
                    for (const choice of [{ value: '', label: 'Domyślna (bez zmiany)' }, ...catalog.choices]) {
                        const option = document.createElement('option');
                        option.value = choice.value;
                        option.textContent = choice.label;
                        family.appendChild(option);
                    }
                    family.value = item.caption_font_family ?? '';
                    family.addEventListener('change', () => {
                        if (family.value) item.caption_font_family = family.value;
                        else delete item.caption_font_family;
                        render();
                    });
                    add(`${prefix} — rodzaj czcionki`, family);

                    const size = document.createElement('input');
                    size.type = 'number';
                    size.inputMode = 'decimal';
                    size.step = '0.1';
                    size.min = '1';
                    size.max = '200';
                    size.placeholder = 'Domyślny (bez zmiany)';
                    size.value = item.caption_font_size ?? '';
                    size.addEventListener('input', () => {
                        if (size.value === '') delete item.caption_font_size;
                        else item.caption_font_size = Math.max(1, Math.min(200, number(size.value, 1)));
                        render();
                    });
                    add(`${prefix} — rozmiar czcionki (px)`, size);
                },
                imageCaption(container, item) {
                    if (item.type !== 'image' || !item.photo_url || !item.caption?.trim()) return;
                    const caption = document.createElement('div');
                    caption.textContent = item.caption;
                    caption.style.cssText = 'font:16px Arial,sans-serif;color:#222;letter-spacing:normal;text-align:left;white-space:pre-line;';
                    if (item.caption_font_family) caption.style.fontFamily = css(item.caption_font_family);
                    if (item.caption_font_size) caption.style.fontSize = `${item.caption_font_size}px`;
                    container.appendChild(caption);
                },
                isText: (item) => textTypes.has(item.type),
                initialize(item) {
                    if (!textTypes.has(item.type)) return;
                    item.style ??= {};
                    item.style.font_family ??= catalog.defaults[item.type === 'heading' ? 'site_heading_font_family' : 'site_body_font_family'];
                },
                apply,
                field(container, item, render, className) {
                    panel(container, item, render, className);
                }
            };
        }
    };
})();
