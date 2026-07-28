@csrf
@if ($product->exists)
    @method('PUT')
@endif

<div class="admin-form-grid">
    <div class="form-field admin-field--wide">
        <label class="form-label" for="category_id">Категория</label>
        <select class="form-control" id="category_id" name="category_id" required>
            <option value="">Выберите категорию</option>
            @foreach ($categories as $category)
                <option value="{{ $category->getKey() }}" @selected((string) old('category_id', $product->category_id) === (string) $category->getKey())>
                    {{ $category->name }}{{ $category->is_active ? '' : ' — отключена' }}
                </option>
            @endforeach
        </select>
        @error('category_id')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-field admin-field--wide">
        <label class="form-label" for="name">Название</label>
        <input class="form-control" id="name" name="name" type="text" value="{{ old('name', $product->name) }}" maxlength="255" required>
        @error('name')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-field admin-field--wide">
        <label class="form-label" for="slug">Slug</label>
        <input class="form-control" id="slug" name="slug" type="text" value="{{ old('slug', $product->slug) }}" maxlength="255" placeholder="создастся из названия">
        <span class="form-help">Используется в адресе карточки товара.</span>
        @error('slug')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-field admin-field--wide">
        <label class="form-label" for="short_description">Краткое описание</label>
        <input class="form-control" id="short_description" name="short_description" type="text" value="{{ old('short_description', $product->short_description) }}" maxlength="255">
        @error('short_description')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-field admin-field--wide">
        <label class="form-label" for="description">Полное описание</label>
        <textarea class="form-control admin-textarea" id="description" name="description" rows="8">{{ old('description', $product->description) }}</textarea>
        @error('description')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-field">
        <label class="form-label" for="base_price">Базовая цена, ₽</label>
        <input class="form-control" id="base_price" name="base_price" type="text" inputmode="decimal" value="{{ old('base_price', $product->base_price) }}" placeholder="1000.00" required>
        @error('base_price')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-field">
        <label class="form-label" for="default_duration_days">Срок по умолчанию, дней</label>
        <input class="form-control" id="default_duration_days" name="default_duration_days" type="number" value="{{ old('default_duration_days', $product->default_duration_days ?? 30) }}" min="1" max="3650" required>
        @error('default_duration_days')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-field">
        <label class="form-label" for="sort_order">Порядок</label>
        <input class="form-control" id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', $product->sort_order ?? 0) }}" min="0" required>
        @error('sort_order')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <label class="admin-check">
        <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $product->exists ? $product->is_active : true))>
        <span><strong>Активный товар</strong><small>Показывать в публичном каталоге</small></span>
    </label>
</div>

<div class="admin-form-actions">
    <button class="button button-dark" type="submit">Сохранить</button>
    <a class="button button-secondary" href="{{ route('admin.products.index') }}">Отмена</a>
</div>
