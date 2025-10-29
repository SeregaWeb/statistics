# Dark Mode Implementation

## Overview
Система управления темной темой для WordPress темы WP-Rock. Позволяет пользователям переключаться между светлой и темной темой с сохранением настроек в куки.

## Files Created

### 1. PHP Class
- **File**: `wp-content/themes/wp-rock/src/inc/core/class-dark-mode.php`
- **Purpose**: Обработка AJAX запросов, управление куки, генерация CSS классов

### 2. TypeScript
- **File**: `wp-content/themes/wp-rock/src/js/components/dark-mode-toggle.ts`
- **Purpose**: Обработка кликов по переключателю, AJAX запросы, обновление UI
- **Compiled to**: `assets/public/js/frontend.js`

### 3. SCSS Styles
- **File**: `wp-content/themes/wp-rock/src/scss/dark-mode.scss`
- **Purpose**: Стили для темной темы (Bootstrap компоненты) с SCSS переменными и миксинами
- **Compiled to**: `assets/public/css/frontend.css`

## Build Process

### Compilation
The project uses Webpack to compile SCSS and TypeScript files:

1. **SCSS Compilation**: `src/scss/dark-mode.scss` → `assets/public/css/frontend.css`
2. **TypeScript Compilation**: `src/js/components/dark-mode-toggle.ts` → `assets/public/js/frontend.js`

### Build Commands
```bash
# Development build
yarn dev

# Production build  
yarn build
```

## How It Works

### 1. Initialization
```php
// В functions.php
require THEME_DIR . '/src/inc/core/class-dark-mode.php';

// В initial-setup.php
$dark_mode = new DarkMode();
```

### 2. Body Class Generation
```php
// В header.php
<body <?php 
$dark_mode_class = '';
if (class_exists('DarkMode')) {
    $dark_mode = new DarkMode();
    $dark_mode_class = $dark_mode->get_body_class();
}
$body_classes = array_filter([$page_class, $dark_mode_class]);
body_class($body_classes); 
?>>
```

### 3. Toggle Switch HTML
```html
<div class="form-check form-switch align-items-center m-0 d-flex gap-1">
    <input class="form-check-input" type="checkbox" id="night_mode" name="night_mode" value="1">
</div>
```

## AJAX Endpoints

### Toggle Dark Mode
- **Action**: `toggle_dark_mode`
- **Method**: POST
- **Parameters**:
  - `enabled`: boolean (true/false)
  - `nonce`: security nonce
- **Response**: JSON success/error

## Cookie Management

- **Cookie Name**: `dark_mode_enabled`
- **Values**: `1` (enabled) / `0` (disabled)
- **Expiry**: 30 days
- **Path**: Site root

## CSS Classes

### Body Classes
- `dark-mode`: Applied when dark mode is enabled
- No class: Light mode (default)

### CSS Selectors
All dark mode styles use `body.dark-mode` prefix:
```css
body.dark-mode {
    background-color: #1a1a1a;
    color: #e0e0e0;
}

body.dark-mode .card {
    background-color: #2d2d2d;
    border-color: #404040;
}
```

## Usage Examples

### Check Dark Mode Status (PHP)
```php
$dark_mode = new DarkMode();
if ($dark_mode->is_dark_mode_enabled()) {
    echo 'Dark mode is enabled';
}
```

### Get Body Class (PHP)
```php
$dark_mode = new DarkMode();
$body_class = $dark_mode->get_body_class(); // Returns 'dark-mode' or ''
```

### JavaScript Access
```javascript
// Check if dark mode is enabled
if (darkModeAjax.is_enabled) {
    console.log('Dark mode is enabled');
}
```

## Features

✅ **AJAX Toggle**: Instant switching without page reload  
✅ **Cookie Persistence**: Settings saved for 30 days  
✅ **Security**: Nonce verification for AJAX requests  
✅ **Bootstrap Compatible**: Works with Bootstrap components  
✅ **Error Handling**: Reverts UI state on AJAX errors  
✅ **Performance**: Minimal CSS/JS footprint  

## Browser Support

- Modern browsers with JavaScript enabled
- Cookie support required
- CSS3 support for styling

## Customization

### Adding Custom Dark Mode Styles
Add to `dark-mode.scss`:

**Using SCSS variables:**
```scss
body.dark-mode .your-custom-element {
  background-color: $dark-bg-secondary;
  color: $dark-text-primary;
  border-color: $dark-border;
}
```

**Available SCSS Variables:**
- `$dark-bg-primary: #1a1a1a` - Main background
- `$dark-bg-secondary: #2d2d2d` - Secondary background  
- `$dark-bg-tertiary: #333333` - Tertiary background
- `$dark-border: #404040` - Border color
- `$dark-text-primary: #e0e0e0` - Primary text
- `$dark-text-secondary: #adb5bd` - Secondary text

### Modifying Cookie Settings
In `class-dark-mode.php`:
```php
private $cookie_name = 'dark_mode_enabled';
private $cookie_expiry = 30 * DAY_IN_SECONDS; // Change this
```
