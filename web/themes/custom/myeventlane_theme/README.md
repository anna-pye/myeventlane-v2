# MyEventLane Theme

A modern, accessible Drupal 11 theme for the MyEventLane event platform.

## Features

- **Nunito typography** - Friendly, readable font system
- **Pastel color palette** - Soft, inviting colors with high-contrast CTAs
- **Mobile-first design** - Responsive from 320px to 1440px+
- **Design tokens** - Centralized variables for colors, spacing, typography
- **Accessible focus states** - WCAG 2.1 AA compliant
- **Vite build system** - Fast builds, HMR support, modern tooling

## Quick Start

```bash
# Navigate to theme directory
cd web/themes/custom/myeventlane_theme

# Install dependencies
ddev npm install

# Build for production
ddev npm run build

# Clear Drupal cache
ddev drush cr
```

## Development

### Dev Server with HMR

```bash
cd web/themes/custom/myeventlane_theme
ddev npm run dev
```

Then visit your site at `https://myeventlane.ddev.site`. CSS changes will hot-reload.

### Production Build

```bash
cd web/themes/custom/myeventlane_theme
ddev npm run build
ddev drush cr
```

## File Structure

```
myeventlane_theme/
├── dist/                    # Built assets (git-ignored)
├── src/
│   ├── js/
│   │   ├── main.js          # JS entry point
│   │   └── header.js        # Mobile nav component
│   └── scss/
│       ├── main.scss        # SCSS entry point
│       ├── tokens/          # Design tokens
│       │   ├── _colors.scss
│       │   ├── _typography.scss
│       │   ├── _spacing.scss
│       │   ├── _radii.scss
│       │   ├── _shadows.scss
│       │   ├── _breakpoints.scss
│       │   └── _zindex.scss
│       ├── abstracts/       # Mixins & functions
│       ├── base/            # Reset, base styles
│       ├── components/      # UI components
│       ├── layout/          # Grid, containers
│       ├── pages/           # Page-specific styles
│       └── utilities/       # Helper classes
├── templates/               # Twig templates
├── package.json
├── vite.config.js
└── myeventlane_theme.libraries.yml
```

## Design Tokens

All design decisions are tokenized in `src/scss/tokens/`:

| File | Purpose |
|------|---------|
| `_colors.scss` | Color palette, state colors, focus ring |
| `_typography.scss` | Nunito font, weights, type scale |
| `_spacing.scss` | Spacing scale (0-8) |
| `_radii.scss` | Border radius values |
| `_shadows.scss` | Box shadow presets |
| `_breakpoints.scss` | Responsive breakpoints |
| `_zindex.scss` | Z-index layers |

### Using Tokens

```scss
@use '../tokens/colors';
@use '../tokens/spacing';
@use '../tokens/typography';

.my-component {
  background: colors.$mel-color-surface;
  padding: spacing.mel-space(4);
  font-size: typography.mel-font-size(lg);
}
```

## CSS Class Naming

All classes use the `mel-` prefix (MyEventLane):

- `.mel-btn` - Buttons
- `.mel-card` - Cards
- `.mel-event-card` - Event-specific card
- `.mel-header` - Header component
- `.mel-hero` - Hero section
- `.mel-container` - Content container

### BEM-like Pattern

```
.mel-component
.mel-component-element
.mel-component--modifier
```

## Breakpoints

| Name | Width | Usage |
|------|-------|-------|
| xs | 0 | Mobile (default) |
| sm | 480px | Large mobile |
| md | 768px | Tablet |
| lg | 1024px | Desktop |
| xl | 1280px | Large desktop |

```scss
@use '../tokens/breakpoints';

.my-element {
  // Mobile first
  padding: 1rem;

  @include breakpoints.mel-break(md) {
    padding: 2rem;
  }
}
```

## Accessibility

- All interactive elements have visible focus states
- Color contrast meets WCAG 2.1 AA (4.5:1 for text)
- Skip link provided for keyboard users
- Mobile nav is keyboard accessible

## Browser Support

- Chrome/Edge (last 2 versions)
- Firefox (last 2 versions)
- Safari (last 2 versions)
- iOS Safari (last 2 versions)






