# EENNRA Hotel Management System - Design System

**Version**: 1.0  
**Status**: Enterprise Production Grade  
**Design Philosophy**: Google-inspired minimalism + Enterprise ERP efficiency

---

## 🎨 Color System (Dynamic Theme Variables)

### Primary Colors
```css
--primary-50: #f0f9ff    /* Lightest */
--primary-100: #e0f2fe
--primary-200: #bae6fd
--primary-300: #7dd3fc
--primary-400: #38bdf8
--primary-500: #0ea5e9  /* Primary Brand Color (Sky Blue) */
--primary-600: #0284c7
--primary-700: #0369a1
--primary-800: #075985
--primary-900: #0c3d66  /* Darkest */
```

### Neutral Colors
```css
--slate-50: #f8fafc
--slate-100: #f1f5f9
--slate-200: #e2e8f0
--slate-300: #cbd5e1
--slate-400: #94a3b8
--slate-500: #64748b
--slate-600: #475569
--slate-700: #334155
--slate-800: #1e293b
--slate-900: #0f172a
```

### Semantic Colors
```css
--success-500: #10b981   /* Green - Checkmarks, Active */
--warning-500: #f59e0b   /* Amber - Warnings, Pending */
--danger-500: #ef4444    /* Red - Errors, Delete */
--info-500: #3b82f6      /* Blue - Information */
```

---

## 📐 Typography System

### Font Stack
```css
font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, 
             "Helvetica Neue", Arial, sans-serif;
```

### Font Sizes (Compact, Professional)
| Name | Size | Line-Height | Use Case |
|------|------|-------------|----------|
| **xs** | 12px | 1.4 (16.8px) | Labels, hints, secondary text |
| **sm** | 13px | 1.5 (19.5px) | Button text, form inputs |
| **base** | 14px | 1.6 (22.4px) | Body text, table cells |
| **lg** | 16px | 1.6 (25.6px) | Headers, key metrics |
| **xl** | 18px | 1.8 (32.4px) | Section titles |
| **2xl** | 20px | 1.8 (36px) | Page titles |

### Font Weights
```css
--font-regular: 400
--font-medium: 500
--font-semibold: 600
--font-bold: 700
--font-extrabold: 800
```

---

## 📏 Spacing System (Compact)

All measurements in pixels. Use multiples of 2px for precision.

```css
--space-1: 4px    /* Micro gaps */
--space-2: 8px    /* Small gaps, padding */
--space-3: 12px   /* Standard padding */
--space-4: 16px   /* Medium spacing */
--space-6: 24px   /* Large sections */
--space-8: 32px   /* Extra large */
--space-12: 48px  /* Huge */
```

### Padding Standards
- **Compact**: 8px 12px (buttons, small elements)
- **Standard**: 12px 16px (form fields, medium elements)
- **Large**: 16px 24px (cards, sections)

### Gap Standards
- **Tight**: 4px (inline elements)
- **Compact**: 8px (related items)
- **Standard**: 12px (items in a group)
- **Loose**: 16px-24px (section separation)

---

## 🔘 Button System

### Button Styles

#### Primary (Call-to-Action)
```css
Background: var(--primary-600)
Text: white, bold, 13px
Padding: 8px 16px
Border-radius: 6px
Hover: var(--primary-700)
Active: var(--primary-800)
Height: 36px
Icon: 16x16px, left margin 8px
```

**Usage**: Save, Submit, Create, Add, Send actions

#### Secondary (Default)
```css
Background: var(--slate-200)
Text: var(--slate-700), semibold, 13px
Padding: 8px 16px
Border-radius: 6px
Hover: var(--slate-300)
Active: var(--slate-400)
Height: 36px
```

**Usage**: Cancel, Reset, Back navigation

#### Tertiary (Subtle)
```css
Background: transparent
Border: 1px var(--slate-300)
Text: var(--slate-700), semibold, 13px
Padding: 7px 15px
Border-radius: 6px
Hover: var(--slate-100)
```

**Usage**: Optional actions, alternates

#### Danger (Destructive)
```css
Background: var(--danger-500)
Text: white, bold, 13px
Padding: 8px 16px
Border-radius: 6px
Hover: #dc2626 (darker)
Confirm: Show modal before action
```

**Usage**: Delete, Remove, Clear

#### Compact (Mini)
```css
Padding: 6px 12px
Font-size: 12px
Height: 28px
```

**Usage**: Inline actions, table cells

#### Disabled State (All types)
```css
Opacity: 0.5
Cursor: not-allowed
No hover effect
```

---

## 📝 Form Elements

### Input Fields
```css
Height: 36px
Padding: 8px 12px
Font-size: 13px
Border: 1px var(--slate-300)
Border-radius: 6px
Background: white

Focus State:
  Border-color: var(--primary-600)
  Box-shadow: 0 0 0 3px rgba(6, 163, 225, 0.1)
  Outline: none

Error State:
  Border-color: var(--danger-500)
  Color: var(--danger-500)

Disabled State:
  Background: var(--slate-100)
  Color: var(--slate-400)
  Cursor: not-allowed
```

### Labels
```css
Font-size: 12px
Font-weight: 600
Color: var(--slate-600)
Margin-bottom: 4px
Required marker: Red * after label
```

### Placeholders
```css
Color: var(--slate-400)
Font-style: regular
Example: "Search properties..."
```

### Select/Dropdown
```css
Same as input fields
Chevron icon: 16x16px, right side
Padding-right: 32px (accommodate icon)
```

### Checkboxes & Radios
```css
Size: 18x18px
Border: 2px var(--slate-300)
Border-radius: 4px (checkbox), 50% (radio)
Checked: Background var(--primary-600), checkmark/dot white
Focus: Box-shadow same as inputs
```

---

## 📊 Cards & Containers

### Card (Standard)
```css
Background: white
Border: 1px var(--slate-200)
Border-radius: 8px
Padding: 16px
Box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1)
Hover: Box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1)
```

### Card Header
```css
Border-bottom: 1px var(--slate-200)
Padding: 12px 16px
Background: var(--slate-50)
Font-weight: 600
Font-size: 13px
Color: var(--slate-700)
```

### Card Section
```css
Padding: 16px
Border-bottom: 1px var(--slate-200)
Last section: no border-bottom
```

---

## 📋 Tables

### Table Header
```css
Background: var(--slate-50)
Border-bottom: 1px var(--slate-200)
Padding: 12px 16px
Font-size: 12px
Font-weight: 600
Color: var(--slate-600)
Uppercase: Optional (use when dense)
```

### Table Row
```css
Border-bottom: 1px var(--slate-100)
Padding: 12px 16px
Height: 44px (compact)

Hover state:
  Background: var(--slate-50)
  Transition: 150ms ease
```

### Table Cell
```css
Font-size: 13px
Color: var(--slate-900)
Vertical align: middle
Left-align: Text, names
Right-align: Numbers, prices
Center-align: Status badges, actions
```

### Status Badges
```css
Padding: 4px 8px
Font-size: 11px
Font-weight: 600
Border-radius: 4px

Active: Background var(--success-500), white text
Draft: Background var(--warning-500), var(--slate-900) text
Inactive: Background var(--slate-100), var(--slate-600) text
Confirmed: Background var(--info-500), white text
Pending: Background var(--warning-500), white text
Cancelled: Background var(--slate-300), var(--slate-700) text
```

---

## 🧭 Navigation & Tabs

### Header
```css
Height: 56px (compact)
Padding: 12px 24px
Background: white
Border-bottom: 1px var(--slate-300)
Box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1)

Layout:
  Left: Logo, breadcrumb, page title
  Right: Actions, user menu, logout
  Gap: 16px between elements
```

### Sidebar Navigation
```css
Width: 260px (expanded), 80px (collapsed)
Background: var(--slate-600)
Color: white
Transition: 300ms ease

Item:
  Height: 36px
  Padding: 10px 12px
  Margin: 0 8px
  Border-radius: 6px
  Font-size: 14px
  Font-weight: 500

Active state:
  Background: var(--primary-600)
  Color: white

Hover state:
  Background: rgba(255, 255, 255, 0.1)
  Cursor: pointer

Icon:
  Size: 20x20px
  Margin-right: 12px
```

### Tabs
```css
Height: 44px
Border-bottom: 2px var(--slate-200)
Font-size: 13px
Font-weight: 500
Color: var(--slate-600)
Padding: 0 16px
Cursor: pointer

Active tab:
  Border-bottom: 2px var(--slate-900)
  Color: var(--slate-900)
  Font-weight: 600

Hover (inactive):
  Color: var(--slate-700)
```

---

## 📦 Modals & Overlays

### Modal
```css
Background: white
Border-radius: 8px
Box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2)
Width: 90% (responsive), max 500px
Min-width: 300px
Padding: 24px

Header:
  Font-size: 16px
  Font-weight: 600
  Margin-bottom: 16px
  Close button: top-right, 24x24px

Body:
  Font-size: 14px
  Color: var(--slate-600)
  Margin-bottom: 20px

Footer:
  Padding-top: 20px
  Border-top: 1px var(--slate-200)
  Buttons: Right-aligned, gap 8px
```

### Overlay/Backdrop
```css
Background: rgba(0, 0, 0, 0.5)
Position: fixed
Inset: 0
Z-index: 40
Animation: fade-in 150ms ease
```

---

## ⚠️ Alerts & Messages

### Alert Container
```css
Padding: 12px 16px
Border-radius: 6px
Font-size: 13px
Font-weight: 500
Border-left: 4px solid (color varies)
Margin-bottom: 16px

Display: flex
Align-items: center
Gap: 12px

Icon: 20x20px, left side
Close button: 16x16px, right side
```

### Alert Types

| Type | Border | Background | Text | Icon |
|------|--------|------------|------|------|
| **Success** | var(--success-500) | rgba(16, 185, 129, 0.1) | var(--success-500) | ✓ |
| **Error** | var(--danger-500) | rgba(239, 68, 68, 0.1) | var(--danger-500) | ✕ |
| **Warning** | var(--warning-500) | rgba(245, 158, 11, 0.1) | var(--warning-500) | ⚠ |
| **Info** | var(--primary-600) | rgba(2, 132, 199, 0.1) | var(--primary-600) | ℹ |

---

## 🎭 Loading & States

### Skeleton Loader
```css
Background: linear-gradient(90deg, 
  var(--slate-200) 0%, 
  var(--slate-300) 50%, 
  var(--slate-200) 100%)
Background-size: 200% 100%
Animation: loading 1.5s infinite
Border-radius: 4px
Height: varies with element
```

### Spinner
```css
Size: 20x20px (standard), 16x16px (small)
Border: 2px solid var(--slate-200)
Border-top: 2px solid var(--primary-600)
Border-radius: 50%
Animation: spin 1s linear infinite
```

### Disabled State
```css
Opacity: 0.5
Cursor: not-allowed
No hover effects
```

---

## 📱 Responsive Design Rules

### Breakpoints
```css
Mobile: < 640px (sm)
Tablet: 640px - 1024px (md)
Desktop: > 1024px (lg)
```

### Responsive Behavior
- **Mobile**: Stack vertically, full-width inputs, compact spacing
- **Tablet**: Two-column layouts, standard spacing
- **Desktop**: Multi-column layouts, full feature set

### Touch Targets
- Minimum 44x44px on mobile
- 36x36px acceptable on desktop
- Buttons always minimum 36px height

---

## 🎨 Theme Customization

### How to Change Theme Colors

Edit `/resources/css/theme-variables.css`:

```css
:root {
  --primary-50: #f0f9ff;
  --primary-100: #e0f2fe;
  --primary-200: #bae6fd;
  --primary-300: #7dd3fc;
  --primary-400: #38bdf8;
  --primary-500: #0ea5e9;  /* ← Main brand color */
  --primary-600: #0284c7;
  --primary-700: #0369a1;
  --primary-800: #075985;
  --primary-900: #0c3d66;
}
```

**Change `--primary-*` values to update entire system theme**

### Pre-built Color Palettes
- **Sky Blue** (Current): Professional, tech-forward
- **Emerald Green**: Health, growth, hospitality
- **Indigo**: Premium, luxury
- **Purple**: Creative, modern
- **Amber**: Warm, welcoming

---

## ✨ Animation & Transitions

### Transition Durations
```css
--duration-fast: 150ms      /* Quick feedback */
--duration-base: 200ms      /* Standard */
--duration-slow: 300ms      /* Deliberate */
--timing-ease: ease-in-out
```

### Standard Transitions
- Button state changes: 150ms
- Hover effects: 150ms
- Modal open/close: 200ms
- Sidebar collapse: 300ms
- Sidebar submenu: 300ms
- Form field focus: 150ms

---

## 🚀 Google-Inspired Principles

1. **Minimalism**: Remove visual noise, maximize content
2. **Whitespace**: Generous padding/margins for clarity
3. **Typography**: Clear hierarchy, readable sizes
4. **Color**: Purposeful use, not decorative
5. **Motion**: Smooth, meaningful, not distracting
6. **Consistency**: Same patterns everywhere
7. **Efficiency**: Compact but not cramped
8. **Accessibility**: High contrast, clear labels

---

## 📋 Implementation Checklist

- [ ] Create `/resources/css/theme-variables.css` with CSS variables
- [ ] Update `/resources/css/app.css` to use variables
- [ ] Apply consistent spacing across all pages
- [ ] Implement compact button styles
- [ ] Standardize form elements
- [ ] Create reusable card components
- [ ] Apply table styling consistently
- [ ] Implement loading states
- [ ] Add smooth transitions
- [ ] Test on mobile (responsive)
- [ ] Validate color contrast (WCAG AA)
- [ ] Document in Storybook (optional)

---

**Last Updated**: 2026-07-07  
**Next Review**: When adding new components
