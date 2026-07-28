@csrf
@if ($category->exists)
    @method('PUT')
@endif

<div class="admin-form-grid">
    <div class="form-field admin-field--wide">
        <label class="form-label" for="name">Название</label>
        <input class="form-control" id="name" name="name" type="text" value="{{ old('name', $category->name) }}" maxlength="255" required>
        @error('name')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-field admin-field--wide">
        <label class="form-label" for="slug">Slug</label>
        <input class="form-control" id="slug" name="slug" type="text" value="{{ old('slug', $category->slug) }}" maxlength="255" placeholder="создастся из названия">
        <span class="form-help">Латинские буквы, цифры и дефисы. Можно оставить пустым.</span>
        @error('slug')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-field admin-field--wide">
        <label class="form-label" for="description">Описание</label>
        <textarea class="form-control admin-textarea" id="description" name="description" rows="6">{{ old('description', $category->description) }}</textarea>
        @error('description')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-field">
        <label class="form-label" for="sort_order">Порядок</label>
        <input class="form-control" id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', $category->sort_order ?? 0) }}" min="0" required>
        @error('sort_order')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <label class="admin-check">
        <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $category->exists ? $category->is_active : true))>
        <span><strong>Активная категория</strong><small>Показывать в публичном каталоге</small></span>
    </label>
</div>

<div class="admin-form-actions">
    <button class="button button-dark" type="submit">Сохранить</button>
    <a class="button button-secondary" href="{{ route('admin.categories.index') }}">Отмена</a>
</div>
