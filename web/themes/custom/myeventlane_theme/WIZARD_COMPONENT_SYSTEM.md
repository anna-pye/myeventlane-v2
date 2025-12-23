# MyEventLane Wizard Component System

## Overview

The MyEventLane wizard component system provides a reusable, accessible, and mobile-first wizard layout for multi-step forms and flows across the platform.

## Architecture

### Generic Component (`_wizard.scss`)

The generic wizard component (`components/_wizard.scss`) provides base styles for:
- Two-column layout (sidebar navigation + content panel)
- Step navigation with states (default, active, completed)
- Step content panels with card styling
- Action buttons (Back/Next/Submit) with sticky mobile behavior
- Progress indicators
- Mobile navigation toggle

**Base Classes:**
- `.mel-wizard` - Root container
- `.mel-wizard__layout` - Grid layout container
- `.mel-wizard__nav` - Left sidebar navigation
- `.mel-wizard__content` - Right content area
- `.mel-wizard__step-panel` - Individual step content card
- `.mel-wizard__actions` - Action buttons container

### Event Creation Wizard (`_event-wizard.scss`)

The event creation wizard extends the generic component design patterns while maintaining backward compatibility with the existing `EventFormAlter.php` structure.

**Event-Specific Classes:**
- `.mel-event-form--wizard` - Root container (maps to generic wizard)
- `.mel-event-form__wizard-layout` - Three-column layout (nav + content + diagnostics)
- `.mel-event-form__wizard-nav` - Left navigation sidebar
- `.mel-event-form__step` - Step navigation buttons
- `.mel-event-form__panel` - Step content panels

## Usage

### Event Creation Wizard

The event creation wizard is automatically applied via `EventFormAlter.php` when creating/editing events in the vendor context. No template changes required - the PHP form alter builds the structure.

**Current Structure:**
```
.mel-event-form--wizard
  └── .mel-event-form__wizard-layout
      ├── .mel-event-form__wizard-nav (left)
      ├── .mel-event-form__wizard-content (center)
      └── .mel-event-form__wizard-diagnostics (right, desktop only)
```

### Commerce Checkout

To reuse the wizard component for checkout:

1. **Update checkout template** (`templates/commerce/commerce-checkout-form.html.twig`):
   ```twig
   {% include 'components/mel-wizard.html.twig' with {
     wizard_nav: checkout_steps_nav,
     wizard_content: checkout_panes,
     wizard_actions: form.actions,
     wizard_class: 'mel-wizard--checkout'
   } %}
   ```

2. **Create checkout-specific SCSS** (`components/_checkout-wizard.scss`):
   ```scss
   .mel-wizard--checkout {
     // Checkout-specific overrides
     .mel-wizard__layout {
       @include breakpoints.mel-break(lg) {
         grid-template-columns: 280px 1fr 400px; // Order summary sidebar
       }
     }
   }
   ```

3. **Map checkout step classes** to generic wizard classes in PHP form alter or theme preprocess.

### User Onboarding

To reuse for user onboarding flows:

1. **Create onboarding template**:
   ```twig
   {% include 'components/mel-wizard.html.twig' with {
     wizard_nav: onboarding_steps_nav,
     wizard_content: onboarding_step_content,
     wizard_actions: onboarding_actions,
     wizard_progress: onboarding_progress,
     wizard_class: 'mel-wizard--onboarding'
   } %}
   ```

2. **Create onboarding-specific SCSS** (`components/_onboarding-wizard.scss`):
   ```scss
   .mel-wizard--onboarding {
     // Onboarding-specific overrides
   }
   ```

## Design Principles

### MyEventLane Brand
- **Soft surfaces**: Rounded corners (`radii.$mel-radius-xl`), subtle shadows
- **Pastel colors**: Primary coral (`#ff6f61`), secondary lavender (`#8d79f6`)
- **Premium spacing**: 8px grid system (`spacing.mel-space()`)
- **Calm aesthetic**: Soft backgrounds, gentle transitions

### Mobile-First
- Sidebar hidden on mobile by default
- Stacked layout on small screens
- Sticky actions bar on mobile
- Touch-friendly button sizes (min 44px)

### Accessibility (WCAG AA)
- **Color contrast**: All text meets AA standards
- **Focus states**: Visible focus rings on all interactive elements
- **Keyboard navigation**: Full keyboard support
- **Screen readers**: Proper ARIA labels and semantic HTML
- **Logical DOM order**: Content flows naturally

## File Structure

```
web/themes/custom/myeventlane_theme/
├── src/scss/
│   ├── components/
│   │   ├── _wizard.scss          # Generic wizard component
│   │   ├── _event-wizard.scss    # Event creation wizard
│   │   ├── _checkout.scss        # Checkout (can be extended)
│   │   └── _onboarding-wizard.scss  # Future: onboarding
│   └── main.scss                 # Imports wizard component
└── templates/
    └── components/
        └── mel-wizard.html.twig  # Generic wizard template
```

## Reuse Strategy

### 1. Event Creation (Current)
- **Status**: ✅ Implemented
- **Method**: PHP form alter (`EventFormAlter.php`) builds structure
- **Classes**: `mel-event-form__*` (mapped to generic patterns)
- **No template changes required**

### 2. Commerce Checkout (Future)
- **Status**: 🔄 Ready for implementation
- **Method**: 
  - Option A: Use generic wizard template with checkout-specific classes
  - Option B: Extend checkout template to use wizard component
- **Classes**: `mel-wizard--checkout` + existing `mel-checkout-*` classes
- **Steps**: Login → Contact → Billing → Payment → Review

### 3. User Onboarding (Future)
- **Status**: 📋 Planned
- **Method**: New controller/template using generic wizard component
- **Classes**: `mel-wizard--onboarding`
- **Steps**: Welcome → Profile → Preferences → Complete

## Customization

### Adding a New Wizard Variant

1. **Create variant SCSS file**:
   ```scss
   // components/_my-wizard.scss
   .mel-wizard--my-variant {
     // Override generic styles
     .mel-wizard__layout {
       // Custom layout
     }
   }
   ```

2. **Import in main.scss**:
   ```scss
   @use 'components/my-wizard';
   ```

3. **Use in template**:
   ```twig
   {% include 'components/mel-wizard.html.twig' with {
     wizard_class: 'mel-wizard--my-variant',
     // ... other variables
   } %}
   ```

### Modifying Step States

Step states are controlled via classes:
- `.is-active` - Current step
- `.is-complete` - Completed step
- `.is-hidden` - Hidden step panel
- `[aria-disabled="true"]` - Disabled step button

## Testing Checklist

- [ ] Desktop layout: sidebar + content visible
- [ ] Mobile layout: sidebar hidden, content stacked
- [ ] Step navigation: click to change steps
- [ ] Active step: highlighted in navigation
- [ ] Completed steps: show checkmark
- [ ] Focus states: visible on keyboard navigation
- [ ] Form fields: proper spacing and styling
- [ ] Actions: Back/Next buttons work correctly
- [ ] Sticky actions: mobile actions stick to bottom
- [ ] Color contrast: meets WCAG AA standards

## Browser Support

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile Safari (iOS 12+)
- Chrome Mobile (Android 8+)

## Maintenance Notes

- **Do NOT modify** form element names, `#parents`, validation, or submit handlers
- **Do NOT move** form fields across steps
- **Do NOT introduce** JavaScript unless explicitly requested
- **Do NOT hardcode** paths or environment-specific values
- **Scope all styles** to MyEventLane wizard components only
- **Use BEM naming** convention consistently
- **Use CSS variables** from design tokens where possible

## Version History

- **v1.0** (2024): Initial generic wizard component system
  - Generic `.mel-wizard` component
  - Event creation wizard integration
  - Mobile-first responsive layout
  - WCAG AA compliant
