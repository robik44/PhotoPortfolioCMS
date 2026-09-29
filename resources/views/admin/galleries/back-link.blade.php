<fieldset style="margin:24px 0;padding:16px;border:1px solid #ddd;">
    <legend>Przycisk / link Powrót do galerii</legend>
    <label for="back_text">Tekst</label>
    <input id="back_text" name="back_text" type="text" maxlength="255" value="{{ old('back_text', $backLink['back_text'] ?? '') }}"
           placeholder="Powrót do galerii" style="display:block;width:100%;margin:8px 0;">
    @error('back_text')<p role="alert">{{ $message }}</p>@enderror
    @include('components.typography-fields', ['field' => 'back', 'label' => 'Powrót do galerii', 'typography' => $backLink, 'withSize' => true])
    <label for="back_color">Kolor tekstu (HEX)</label>
    <input id="back_color" name="back_color" type="text" value="{{ old('back_color', $backLink['back_color'] ?? '') }}"
           placeholder="#222222" pattern="#[0-9a-fA-F]{6}" style="display:block;width:100%;margin:8px 0;">
    @error('back_color')<p role="alert">{{ $message }}</p>@enderror
    <p>Puste pola zachowują domyślny wygląd. Adres powrotu jest ustawiany automatycznie.</p>
</fieldset>
