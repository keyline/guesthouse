---
name: guesthouse_admin_design
description: Professional hotel admin design system - components, themes, and implementation guide
---

# Guesthouse Admin Design System

Quick access to the professional enterprise hotel management design system.

## What This Skill Provides

- **Design System Documentation** — Color palettes, typography, spacing, components
- **Component Library** — Copy-paste ready HTML/CSS for all UI elements
- **CSS Variables** — Dynamic theming system for instant color changes
- **Implementation Guide** — Step-by-step setup and usage instructions
- **Theme Switching** — 5 pre-built color palettes (Sky Blue, Emerald, Indigo, Purple, Amber)

## Quick Commands

### View Design System
```
/guesthouse_admin_design design
```
Shows complete design specifications (colors, typography, spacing, components)

### View Component Library
```
/guesthouse_admin_design components
```
Shows copy-paste ready HTML/CSS examples for:
- Buttons (primary, secondary, danger, compact)
- Forms & inputs
- Tables & data display
- Cards & containers
- Navigation & tabs
- Alerts & status badges
- Modals & overlays

### View Usage Guide
```
/guesthouse_admin_design guide
```
Step-by-step implementation instructions:
1. Import theme variables
2. Use color variables in code
3. Copy components from library
4. Change theme colors

### View CSS Variables
```
/guesthouse_admin_design variables
```
Complete CSS variables reference:
- Primary colors (9 shades)
- Neutral colors (slate palette)
- Semantic colors (success, warning, danger, info)
- Typography, spacing, sizing variables
- Transitions, shadows, z-index scale

### List Available Themes
```
/guesthouse_admin_design themes
```
Shows all 5 available color palettes and how to switch between them

### Get Implementation Help
```
/guesthouse_admin_design help
```
Common implementation questions and solutions

## File Locations

- **Design Documentation**: `DESIGN_SYSTEM.md`
- **Component Examples**: `COMPONENT_GUIDE.md`
- **Implementation Guide**: `HOW_TO_USE_DESIGN_SYSTEM.md`
- **CSS Variables**: `resources/css/theme-variables.css`

## Design System Features

✨ **Professional Enterprise ERP Design**
- Compact, clean, minimalist styling
- Google-inspired design principles
- WCAG AA accessibility compliance
- Dynamic theming via CSS variables
- Fully responsive (mobile, tablet, desktop)

🎨 **Color System**
- 5 pre-built color palettes
- 9 shades per primary color
- Semantic colors (success, warning, danger, info)
- Neutral slate palette for grays

📐 **Components**
- Primary, secondary, tertiary, danger buttons
- Compact buttons for inline actions
- Form fields with focus states
- Professional tables with status badges
- Cards with headers and sections
- Alerts, modals, navigation

🔄 **Dynamic Theming**
- Change entire theme by editing one CSS file
- All components automatically update
- No need to change individual color codes

## Examples

### Change Theme to Emerald (Green)
Edit `resources/css/theme-variables.css` and uncomment:
```css
/* EMERALD (Hospitality, Green) */
:root {
  --primary-500: #22c55e;
  --primary-600: #16a34a;
  --primary-700: #15803d;
  /* ... etc */
}
```
All buttons, links, and highlights instantly turn green! ✨

### Use a Button Component
Copy from COMPONENT_GUIDE.md:
```html
<button class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold 
  text-white hover:bg-sky-700 transition">
  Save
</button>
```

When you change primary colors, this button changes automatically.

## Quick Reference

### Most Used Variables
```css
--primary-600      /* Main button/link color */
--primary-700      /* Hover state */
--slate-600        /* Sidebar/dark backgrounds */
--slate-900        /* Text/headings */
--space-3          /* Standard padding (12px) */
--radius-md        /* Border radius for buttons (6px) */
```

### Most Used Components
- **Primary Button**: sky-600 background, white text
- **Text Input**: border-slate-300, focus:border-sky-600
- **Table**: slate-200 borders, slate-50 header
- **Card**: white background, slate-200 border
- **Status Badge**: colored background with semantic colors

## Customization

### Add New Color Palette
1. Edit `theme-variables.css`
2. Uncomment one of the alternative palettes
3. All components automatically update

### Adjust Spacing
Change `--space-*` variables (multiples of 4px)

### Modify Typography
Change `--text-*` and `--font-*` variables

### Update Border Radius
Change `--radius-*` variables

## When to Use This Skill

- 🎨 Need a button/form/card component — run `/guesthouse_admin_design components`
- 🔄 Want to change the theme color — run `/guesthouse_admin_design guide`
- 📐 Need design specifications — run `/guesthouse_admin_design design`
- ❓ Have questions — run `/guesthouse_admin_design help`
- 🎯 Starting a new page — run `/guesthouse_admin_design guide`

---

**Your professional hotel management admin panel is ready to build!** 🚀

Built for international hotel chains with enterprise-grade design standards.
