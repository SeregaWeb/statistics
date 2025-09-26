# Simple File Upload Component

Универсальный компонент для простых блоков загрузки файлов без переключателей (checkbox).

## Использование

```php
$simple_upload_args = [
    'field_name'    => 'driving_record',
    'label'         => 'Driving Record',
    'file_value'    => $driving_record,
    'popup_id'      => 'popup_upload_driving_record',
    'col_class'     => 'col-12 col-md-6'
];
echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
```

## Параметры

### Обязательные параметры:
- `field_name` - Имя поля для загрузки
- `label` - Отображаемый текст для поля
- `file_value` - Текущее значение файла (для проверки загружен ли файл)
- `popup_id` - ID для popup модального окна

### Опциональные параметры:
- `button_text` - Текст кнопки загрузки (по умолчанию: "Upload file")
- `uploaded_text` - Текст когда файл загружен (по умолчанию: "File uploaded")
- `col_class` - Bootstrap класс колонки (по умолчанию: "col-12")
- `button_class` - CSS класс для кнопки (по умолчанию: "btn btn-success")
- `show_icon` - Показывать ли иконку загруженного файла (по умолчанию: true)
- `wrapper_class` - Дополнительный CSS класс для обертки

## Примеры использования

### Базовое использование:
```php
$simple_upload_args = [
    'field_name'    => 'document_file',
    'label'         => 'Document File',
    'file_value'    => $document_file,
    'popup_id'      => 'popup_upload_document'
];
echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
```

### Расширенное использование:
```php
$simple_upload_args = [
    'field_name'    => 'special_document',
    'label'         => 'Special Document',
    'file_value'    => $special_document,
    'popup_id'      => 'popup_upload_special_document',
    'button_text'   => 'Choose File',
    'uploaded_text' => 'Document uploaded successfully',
    'col_class'     => 'col-12 col-md-4',
    'button_class'  => 'btn btn-primary',
    'show_icon'     => false,
    'wrapper_class' => 'custom-wrapper'
];
echo esc_html( get_template_part( TEMPLATE_PATH . 'common/simple', 'file-upload', $simple_upload_args ) );
```

## Отличия от File Upload Block

| Параметр | Simple File Upload | File Upload Block |
|----------|-------------------|-------------------|
| Переключатель | ❌ Нет | ✅ Есть |
| Сложность | Простой | Сложный |
| Использование | Простые загрузки | Условные загрузки |
| HTML размер | Меньше | Больше |

## Структура HTML

Компонент генерирует следующую структуру:

```html
<div class="col-12 js-add-new-report">
    <div class="row">
        <div class="col-12 mb-3">
            <label class="form-label d-flex align-items-center gap-1">
                Label Text
                <!-- Icon if file uploaded -->
            </label>
            <button class="btn btn-success js-open-popup-activator mt-1">
                Upload file
            </button>
        </div>
    </div>
</div>
```

## Когда использовать

- ✅ **Простые загрузки файлов** без условий
- ✅ **Обязательные документы** (всегда видимы)
- ✅ **Быстрая разработка** простых форм
- ❌ **Не использовать** когда нужны переключатели
- ❌ **Не использовать** для условной логики
