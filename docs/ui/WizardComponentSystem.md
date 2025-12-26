# MyEventLane Wizard Component System (v2)

## Purpose
A shared, calm, mobile-first wizard layout used across:
- Event creation
- Checkout flows
- Vendor onboarding

## Design Principles
- Supportive, not instructional
- No progress pressure
- Familiar between flows
- Zero JavaScript dependency

## Core Structure

.mel-vendor-wizard  
├── __rail (desktop steps)  
├── __mobile-steps (mobile chips)  
└── __card (content)

## Visual Parity Rules
- Wizard, Checkout, and Onboarding MUST look the same
- Same card radius, spacing, and CTA placement
- Users should never feel "moved to a new system"

## Accessibility
- Buttons ≥ 44px
- Visible focus states
- No colour-only meaning

## Do Not
- Add JS step logic
- Hide steps dynamically
- Reorder DOM

## Status
This is a canonical UI system for MyEventLane v2.

## Wizard QA Checklist

- [ ] Desktop rail visible ≥ 900px
- [ ] Mobile chips visible < 900px
- [ ] Active step always highlighted
- [ ] Card animates on step change
- [ ] Primary CTA always visible
- [ ] Back button never submits data
- [ ] Draft save never advances step
- [ ] No layout jump on AJAX refresh
- [ ] Checkout uses same card layout
