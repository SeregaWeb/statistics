# File Upload Block Component

Универсальный компонент для блоков загрузки файлов с переключателем (checkbox).

## Использование

```php
$file_upload_args = [
    'field_name'     => 'martlet_ic_agreement',
    'label'          => 'Martlet Express IC agreement',
    'toggle_block'   => 'js-martlet-ic-agreement-files',
    'checkbox_name'  => 'martlet_ic_agreement_on',
    'checkbox_id'    => 'martlet-ic-agreement',
    'is_checked'     => $martlet_ic_agreement_on,
    'file_value'     => $martlet_ic_agreement,
    'popup_id'       => 'popup_upload_martlet_ic_agreement'
];
get_template_part( 'template-parts/report/common/file-upload-block', null, $file_upload_args );
```

## Параметры

### Обязательные параметры:
- `field_name` - Имя поля для загрузки
- `label` - Отображаемый текст для поля
- `toggle_block` - CSS класс для блока переключения
- `checkbox_name` - Атрибут name для checkbox
- `checkbox_id` - Атрибут id для checkbox
- `is_checked` - Состояние checkbox (true/false)
- `file_value` - Текущее значение файла (для проверки загружен ли файл)
- `popup_id` - ID для popup модального окна

### Опциональные параметры:
- `button_text` - Текст кнопки загрузки (по умолчанию: "Upload file")
- `uploaded_text` - Текст когда файл загружен (по умолчанию: "File uploaded")
- `col_class` - Bootstrap класс колонки (по умолчанию: "col-12 col-md-6")
- `button_class` - CSS класс для кнопки (по умолчанию: "btn btn-success")
- `show_icon` - Показывать ли иконку загруженного файла (по умолчанию: true)
- `wrapper_class` - Дополнительный CSS класс для обертки

## Примеры использования

### Базовое использование:
```php
$file_upload_args = [
    'field_name'     => 'document_file',
    'label'          => 'Document File',
    'toggle_block'   => 'js-document-files',
    'checkbox_name'  => 'document_on',
    'checkbox_id'    => 'document-checkbox',
    'is_checked'     => $document_on,
    'file_value'     => $document_file,
    'popup_id'       => 'popup_upload_document'
];
get_template_part( 'template-parts/report/common/file-upload-block', null, $file_upload_args );
```

### Расширенное использование:
```php
$file_upload_args = [
    'field_name'     => 'special_document',
    'label'          => 'Special Document',
    'toggle_block'   => 'js-special-document-files',
    'checkbox_name'  => 'special_document_on',
    'checkbox_id'    => 'special-document-checkbox',
    'is_checked'     => $special_document_on,
    'file_value'     => $special_document,
    'popup_id'       => 'popup_upload_special_document',
    'button_text'    => 'Choose File',
    'uploaded_text'  => 'Document uploaded successfully',
    'col_class'      => 'col-12 col-md-4',
    'button_class'   => 'btn btn-primary',
    'show_icon'      => false,
    'wrapper_class'  => 'custom-wrapper'
];
get_template_part( 'template-parts/report/common/file-upload-block', null, $file_upload_args );
```

## Преимущества

1. **DRY принцип** - Не повторяем код
2. **Консистентность** - Все блоки выглядят одинаково
3. **Легкость изменений** - Изменения в одном месте
4. **Гибкость** - Много опций для кастомизации
5. **Безопасность** - Все данные экранируются
6. **Читаемость** - Четкая структура параметров

## Структура HTML

Компонент генерирует следующую структуру:

```html
<div class="col-12 col-md-6 js-add-new-report">
    <div class="row">
        <div class="col-12 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input js-toggle" type="checkbox" ...>
                <label class="form-check-label">...</label>
            </div>
            <div class="toggle-block">
                <label class="form-label">...</label>
                <button class="btn btn-success js-open-popup-activator">...</button>
            </div>
        </div>
    </div>
</div>
```
