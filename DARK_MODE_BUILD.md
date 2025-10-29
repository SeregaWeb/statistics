# Dark Mode Build Instructions

## Prerequisites
Make sure you have Node.js and Yarn installed.

## Installation
```bash
cd wp-content/themes/wp-rock
yarn install
```

## Development
```bash
# Start development build with watch mode
yarn dev
```

## Production
```bash
# Build for production
yarn build
```

## Files Structure

### Source Files
- `src/scss/dark-mode.scss` - SCSS styles with variables and mixins
- `src/js/components/dark-mode-toggle.ts` - TypeScript implementation
- `src/js/frontend.ts` - Main entry point (imports dark mode files)

### Compiled Files
- `assets/public/css/frontend.css` - Compiled CSS (includes dark mode styles)
- `assets/public/js/frontend.js` - Compiled JavaScript (includes dark mode functionality)

## Integration
The dark mode files are automatically imported into the main frontend bundle:

```typescript
// In src/js/frontend.ts
import '../scss/dark-mode.scss';  // SCSS styles
import './components/dark-mode-toggle';   // TypeScript functionality
```

## PHP Integration
The PHP class automatically enqueues the compiled files:

```php
// CSS (compiled from SCSS)
wp_enqueue_style('dark-mode-styles', 'assets/public/css/frontend.css');

// JS (compiled from TypeScript)  
wp_enqueue_script('dark-mode-toggle', 'assets/public/js/frontend.js');
```

## Notes
- Always run `yarn build` after making changes to SCSS or TypeScript files
- The compiled files are automatically included in the main frontend bundle
- No separate dark mode files are generated - everything is bundled together
