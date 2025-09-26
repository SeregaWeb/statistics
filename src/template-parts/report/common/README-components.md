# Универсальные компоненты для загрузки файлов

Коллекция переиспользуемых компонентов для различных типов загрузки файлов.

## 📁 Доступные компоненты

### 1. File Upload Block
**Файл:** `file-upload-block.php`  
**Описание:** Компонент с переключателем (checkbox) для условной загрузки файлов  
**Использование:** Когда файл нужен только при включенном переключателе

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
echo esc_html( get_template_part( TEMPLATE_PATH . 'common/file', 'upload-block', $file_upload_args ) );
```

### 2. Simple File Upload
**Файл:** `simple-file-upload.php`  
**Описание:** Простой компонент без переключателя для обязательных файлов  
**Использование:** Когда файл всегда нужен (обязательные документы)

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

## 🔄 Сравнение компонентов

| Характеристика | File Upload Block | Simple File Upload |
|----------------|-------------------|-------------------|
| **Переключатель** | ✅ Есть | ❌ Нет |
| **Сложность** | Высокая | Низкая |
| **HTML размер** | Большой | Маленький |
| **Параметры** | 12+ | 8+ |
| **Использование** | Условные файлы | Обязательные файлы |
| **Примеры** | COI, IC agreements | Driving record, License |

## 🎯 Когда использовать какой компонент

### File Upload Block используйте когда:
- ✅ Нужен переключатель (checkbox)
- ✅ Файл загружается только при определенных условиях
- ✅ Пользователь может включить/выключить загрузку
- ✅ Примеры: COI файлы, IC agreements, сертификаты

### Simple File Upload используйте когда:
- ✅ Файл всегда обязателен
- ✅ Нет условий для загрузки
- ✅ Простая форма без переключателей
- ✅ Примеры: Driving record, Driver license, ID документы

## 📋 Общие параметры

### Базовые параметры (есть в обоих компонентах):
- `field_name` - Имя поля
- `label` - Текст метки
- `file_value` - Текущее значение файла
- `popup_id` - ID popup окна
- `button_text` - Текст кнопки
- `uploaded_text` - Текст при загрузке
- `col_class` - Bootstrap классы
- `button_class` - CSS класс кнопки
- `show_icon` - Показывать иконку
- `wrapper_class` - Дополнительные классы

### Уникальные для File Upload Block:
- `toggle_block` - CSS класс блока переключения
- `checkbox_name` - Name атрибут checkbox
- `checkbox_id` - ID атрибут checkbox
- `is_checked` - Состояние checkbox

## 🚀 Преимущества использования

1. **DRY принцип** - Не повторяем код
2. **Консистентность** - Единообразный дизайн
3. **Легкость изменений** - Изменения в одном месте
4. **Читаемость** - Понятная структура
5. **Безопасность** - Автоматическое экранирование
6. **Гибкость** - Много опций кастомизации

## 📖 Дополнительная документация

- [File Upload Block - подробная документация](README-file-upload-block.md)
- [Simple File Upload - подробная документация](README-simple-file-upload.md)

## 🔧 Примеры рефакторинга

### Было (дублированный код):
```html
<div class="col-12 col-md-6 js-add-new-report">
    <div class="row">
        <div class="col-12 mb-3">
            <label class="form-label d-flex align-items-center gap-1">
                Driving Record
                <?php echo $driving_record ? $reports->get_icon_uploaded_file() : ''; ?>
            </label>
            <?php if ( ! $driving_record ): ?>
                <button data-href="#popup_upload_driving_record"
                        class="btn btn-success js-open-popup-activator mt-1">
                    Upload file
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>
```

### Стало (универсальный компонент):
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

**Результат:** 15+ строк HTML → 6 строк PHP! 🎉

## 📈 Статистика рефакторинга

### 🔧 Уже заменено в коде:

#### Simple File Upload (14 блоков):
- ✅ **Driving Record** - заменен на Simple File Upload
- ✅ **Driving Record (Team driver)** - заменен на Simple File Upload  
- ✅ **Driver Licence** - заменен на Simple File Upload
- ✅ **Odysseia IC agreement** - заменен на Simple File Upload
- ✅ **Hazmat Certificate** - заменен на Simple File Upload
- ✅ **TWIC File** - заменен на Simple File Upload
- ✅ **TSA File** - заменен на Simple File Upload
- ✅ **Legal document** - заменен на Simple File Upload
- ✅ **Expiration File (Immigration)** - заменен на Simple File Upload
- ✅ **Background File** - заменен на Simple File Upload
- ✅ **Canada transition File** - заменен на Simple File Upload
- ✅ **Change 9 File** - заменен на Simple File Upload
- ✅ **Odysseia COI** - заменен на Simple File Upload
- ✅ **Motor Cargo COI** - заменен на Simple File Upload

#### File Upload Block (4 блока):
- ✅ **Martlet Express IC agreement** - заменен на File Upload Block
- ✅ **Endurance Transport IC agreement** - заменен на File Upload Block
- ✅ **Martlet Express COI** - заменен на File Upload Block
- ✅ **Endurance Transport COI** - заменен на File Upload Block

### 📊 Итоговый результат:

- **Было:** 18 блоков × 15+ строк HTML = 270+ строк дублированного кода
- **Стало:** 18 вызовов × 6 строк PHP = 108 строк чистого кода
- **Экономия:** ~60% кода! 🚀
- **Улучшение читаемости:** Значительно лучше
- **Упрощение поддержки:** Изменения в одном месте
