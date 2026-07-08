# EENNRA Components Guide

**Professional, Compact, Enterprise ERP Design**  
Complete reference for all UI components with code examples.

---

## 🎯 Quick Links

- [Buttons](#buttons)
- [Forms](#forms)
- [Tables](#tables)
- [Cards](#cards)
- [Navigation](#navigation)
- [Alerts](#alerts)
- [Modals](#modals)
- [Status Badges](#status-badges)

---

## 🔘 Buttons

### Primary Button (Call-to-Action)
**Usage**: Save, Submit, Create, Add, Send

```html
<a href="#" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold 
   text-white hover:bg-sky-700 transition">
  + Add Property
</a>
```

**CSS Variables**:
```css
background-color: var(--primary-600);
color: white;
padding: var(--space-3) var(--space-4);
border-radius: var(--radius-md);
height: var(--button-height); /* 36px */
```

### Secondary Button (Safe Actions)
**Usage**: Cancel, Reset, Back

```html
<button class="rounded-lg bg-slate-200 px-4 py-2 text-sm font-bold 
  text-slate-700 hover:bg-slate-300 transition">
  Cancel
</button>
```

### Tertiary Button (Subtle)
**Usage**: Optional actions, links

```html
<button class="rounded-lg border border-slate-300 bg-white px-4 py-2 
  text-sm font-bold text-slate-700 hover:bg-slate-50 transition">
  Learn More
</button>
```

### Danger Button (Destructive)
**Usage**: Delete, Remove

```html
<button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-bold 
  text-white hover:bg-red-700 transition">
  Delete
</button>
```

### Compact Button (Inline)
**Usage**: Table rows, quick actions

```html
<a href="#" class="rounded bg-sky-600 px-3 py-1.5 text-xs font-bold 
  text-white hover:bg-sky-700 transition">
  View
</a>
```

### Disabled Button
```html
<button disabled class="rounded-lg bg-slate-300 px-4 py-2 text-sm 
  font-bold text-slate-500 cursor-not-allowed opacity-50">
  Disabled
</button>
```

---

## 📝 Forms

### Text Input
```html
<label class="block text-xs font-bold text-slate-600">
  Property Name *
</label>
<input 
  type="text"
  placeholder="Enter property name"
  class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 
         text-sm outline-none focus:border-sky-600 focus:ring-1 
         focus:ring-sky-600"
/>
```

### Select Dropdown
```html
<label class="block text-xs font-bold text-slate-600">
  Status
</label>
<select class="mt-1 h-9 w-full rounded border border-slate-300 
         bg-white px-2.5 text-sm outline-none focus:border-sky-600 
         focus:ring-1 focus:ring-sky-600">
  <option>Active</option>
  <option>Inactive</option>
  <option>Draft</option>
</select>
```

### Checkbox
```html
<label class="flex items-center gap-2">
  <input 
    type="checkbox"
    class="h-4 w-4 rounded border-2 border-slate-300 
           checked:bg-sky-600 checked:border-sky-600"
  />
  <span class="text-sm font-semibold text-slate-700">
    Accept terms
  </span>
</label>
```

### Textarea
```html
<label class="block text-xs font-bold text-slate-600">
  Description
</label>
<textarea 
  rows="3"
  class="mt-1 w-full rounded border border-slate-300 px-2.5 py-1.5 
         text-sm outline-none focus:border-sky-600 focus:ring-1 
         focus:ring-sky-600"
></textarea>
```

### Form Grid
```html
<div class="grid gap-3 md:grid-cols-2">
  <div>
    <label>Field 1</label>
    <input type="text" />
  </div>
  <div>
    <label>Field 2</label>
    <input type="text" />
  </div>
</div>
```

---

## 📊 Tables

### Compact Table
```html
<div class="border border-slate-200 bg-white">
  <table class="w-full">
    <thead class="border-b border-slate-200 bg-slate-50">
      <tr class="text-left text-xs font-bold uppercase tracking-wide text-slate-600">
        <th class="px-4 py-2">Name</th>
        <th class="px-4 py-2">Location</th>
        <th class="px-4 py-2 text-right">Price</th>
        <th class="px-4 py-2 text-center">Status</th>
        <th class="px-4 py-2 text-right">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-200">
      <tr class="hover:bg-slate-50">
        <td class="px-4 py-3">
          <p class="font-bold text-slate-900">Property Name</p>
          <p class="text-xs text-slate-500">Short description</p>
        </td>
        <td class="px-4 py-3 text-sm">City, State</td>
        <td class="px-4 py-3 text-right text-sm font-bold">₹2,500</td>
        <td class="px-4 py-3 text-center">
          <span class="inline-block rounded px-2 py-1 text-xs 
                font-bold bg-emerald-100 text-emerald-800">
            Active
          </span>
        </td>
        <td class="px-4 py-3 text-right">
          <div class="flex justify-end gap-2">
            <a href="#" class="rounded bg-sky-600 px-3 py-1.5 text-xs 
               font-bold text-white hover:bg-sky-700">
              View
            </a>
            <a href="#" class="rounded bg-slate-900 px-3 py-1.5 text-xs 
               font-bold text-white hover:bg-slate-800">
              Edit
            </a>
          </div>
        </td>
      </tr>
    </tbody>
  </table>
</div>
```

---

## 📦 Cards

### Standard Card
```html
<div class="border border-slate-200 bg-white rounded-lg">
  <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
    <h3 class="text-sm font-bold uppercase tracking-wide text-slate-700">
      Card Title
    </h3>
  </div>
  <div class="p-4">
    <p class="text-sm text-slate-600">
      Card content goes here
    </p>
  </div>
</div>
```

### Card with Multiple Sections
```html
<div class="border border-slate-200 bg-white rounded-lg">
  <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
    <h3 class="text-sm font-bold">Section 1</h3>
  </div>
  <div class="border-b border-slate-200 p-4">
    Content 1
  </div>
  <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
    <h3 class="text-sm font-bold">Section 2</h3>
  </div>
  <div class="p-4">
    Content 2
  </div>
</div>
```

### Metric Card
```html
<div class="border border-slate-200 bg-white rounded-lg p-4">
  <p class="text-xs font-bold uppercase tracking-wide text-slate-600">
    Total Revenue
  </p>
  <p class="mt-2 text-2xl font-black text-slate-900">
    ₹18.4L
  </p>
  <p class="mt-1 text-xs text-slate-500">
    +12% vs last month
  </p>
</div>
```

---

## 🧭 Navigation

### Header
```html
<header class="sticky top-0 z-20 border-b border-slate-300 bg-white">
  <div class="flex items-center justify-between px-4 py-4 lg:px-6">
    <div>
      <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
        DASHBOARD
      </p>
      <h1 class="mt-1 text-xl font-black text-slate-900">
        Page Title
      </h1>
    </div>
    <div class="flex items-center gap-3">
      <!-- Add buttons/actions here -->
    </div>
  </div>
</header>
```

### Sidebar Navigation Item
```html
<a href="#" class="block rounded px-3 py-2.5 transition font-medium 
   text-slate-200 hover:bg-white/10 hover:text-white">
  Dashboard
</a>

<!-- Active state -->
<a href="#" class="block rounded px-3 py-2.5 transition font-medium 
   bg-sky-600 text-white">
  Dashboard
</a>
```

### Tabs
```html
<div class="border-b border-slate-200 bg-white">
  <div class="flex gap-0">
    <button class="px-4 py-3 border-b-2 border-slate-900 font-semibold 
      text-slate-900 text-sm">
      General
    </button>
    <button class="px-4 py-3 border-b-2 border-transparent font-semibold 
      text-slate-600 text-sm hover:text-slate-900">
      Business
    </button>
  </div>
</div>
```

---

## ⚠️ Alerts

### Success Alert
```html
<div class="rounded border border-emerald-300 bg-emerald-50 px-4 py-2 
    text-sm font-semibold text-emerald-700">
  ✓ Settings updated successfully!
</div>
```

### Error Alert
```html
<div class="rounded border border-red-300 bg-red-50 px-4 py-2 
    text-sm font-semibold text-red-700">
  ✕ An error occurred. Please try again.
</div>
```

### Warning Alert
```html
<div class="rounded border border-amber-300 bg-amber-50 px-4 py-2 
    text-sm font-semibold text-amber-700">
  ⚠ This action cannot be undone.
</div>
```

### Info Alert
```html
<div class="rounded border border-blue-300 bg-blue-50 px-4 py-2 
    text-sm font-semibold text-blue-700">
  ℹ New features available. Learn more.
</div>
```

---

## 🎭 Modals

### Modal Template
```html
<div class="fixed inset-0 z-40 bg-black/50"></div>
<div class="fixed inset-1/2 z-50 -translate-x-1/2 -translate-y-1/2 
    w-96 rounded-lg bg-white shadow-xl">
  <div class="border-b border-slate-200 px-6 py-4">
    <h2 class="text-lg font-bold text-slate-900">
      Modal Title
    </h2>
  </div>
  <div class="px-6 py-4">
    <p class="text-sm text-slate-600">
      Modal content goes here
    </p>
  </div>
  <div class="border-t border-slate-200 px-6 py-4 flex justify-end gap-2">
    <button class="rounded-lg bg-slate-200 px-4 py-2 text-sm font-bold 
      text-slate-700 hover:bg-slate-300">
      Cancel
    </button>
    <button class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold 
      text-white hover:bg-sky-700">
      Confirm
    </button>
  </div>
</div>
```

---

## 🏷️ Status Badges

### All Status Types
```html
<!-- Active (Green) -->
<span class="inline-block rounded px-2 py-1 text-xs font-bold 
  bg-emerald-100 text-emerald-800">Active</span>

<!-- Draft (Amber) -->
<span class="inline-block rounded px-2 py-1 text-xs font-bold 
  bg-amber-100 text-amber-800">Draft</span>

<!-- Inactive (Gray) -->
<span class="inline-block rounded px-2 py-1 text-xs font-bold 
  bg-slate-100 text-slate-600">Inactive</span>

<!-- Confirmed (Blue) -->
<span class="inline-block rounded px-2 py-1 text-xs font-bold 
  bg-blue-100 text-blue-800">Confirmed</span>

<!-- Pending (Amber) -->
<span class="inline-block rounded px-2 py-1 text-xs font-bold 
  bg-amber-100 text-amber-800">Pending</span>
```

---

## 🔥 Advanced Patterns

### Filter Bar
```html
<div class="mb-4 border border-slate-200 bg-white p-3">
  <form method="GET" class="grid gap-2 md:grid-cols-[1fr_1fr_auto]">
    <div>
      <label class="block text-xs font-bold text-slate-600">City</label>
      <input type="text" placeholder="Search..." 
        class="mt-1 h-8 w-full rounded border border-slate-300 
               px-2.5 text-sm" />
    </div>
    <div>
      <label class="block text-xs font-bold text-slate-600">Status</label>
      <select class="mt-1 h-8 w-full rounded border border-slate-300 
               bg-white px-2.5 text-sm">
        <option>All</option>
      </select>
    </div>
    <div class="flex items-end gap-2">
      <button class="rounded bg-slate-900 px-3 py-2 text-sm font-bold 
        text-white hover:bg-slate-800">Filter</button>
      <a href="#" class="rounded bg-slate-200 px-3 py-2 text-sm font-bold 
        text-slate-700 hover:bg-slate-300">Reset</a>
    </div>
  </form>
</div>
```

### Empty State
```html
<div class="rounded-lg border border-dashed border-slate-300 bg-white 
    p-8 text-center">
  <h2 class="text-lg font-bold text-slate-900">
    No properties yet
  </h2>
  <p class="mt-2 text-sm text-slate-500">
    Create your first property to get started
  </p>
  <a href="#" class="mt-4 inline-block rounded bg-sky-600 px-4 py-2 
    text-sm font-bold text-white hover:bg-sky-700">
    + Create Property
  </a>
</div>
```

### Pagination
```html
<div class="mt-4 flex items-center justify-between">
  <p class="text-sm text-slate-600">
    Showing 1 to 10 of 50 results
  </p>
  <div class="flex gap-2">
    <button class="rounded border border-slate-300 bg-white px-3 py-2 
      text-sm font-semibold text-slate-700 hover:bg-slate-50">
      Previous
    </button>
    <button class="rounded border border-slate-300 bg-white px-3 py-2 
      text-sm font-semibold text-slate-700 hover:bg-slate-50">
      Next
    </button>
  </div>
</div>
```

---

## 📋 Implementation Checklist

- [ ] Include `theme-variables.css` in your main stylesheet
- [ ] Use CSS variables for all colors (never hardcode colors)
- [ ] Apply consistent spacing using `--space-*` variables
- [ ] Use `--radius-md` for most elements
- [ ] Apply `--duration-*` for all transitions
- [ ] Test all components on mobile and desktop
- [ ] Verify focus states for accessibility
- [ ] Check color contrast (WCAG AA minimum)
- [ ] Validate that buttons are at least 36px height
- [ ] Test theme switching

---

## 🎨 Theme Switching

To switch themes, simply change the primary color variables:

```css
/* In theme-variables.css */

:root {
  /* Change these colors */
  --primary-500: #0ea5e9;  /* Current: Sky Blue */
  --primary-600: #0284c7;
  --primary-700: #0369a1;
  /* ... and the rest of the primary palette */
}
```

**Pre-built palettes available:**
- Sky Blue (Current) - Professional, tech
- Emerald - Hospitality, warm
- Indigo - Premium, luxury
- Purple - Creative, modern
- Amber - Welcoming, friendly

---

**Last Updated**: 2026-07-07  
**Version**: 1.0 - Enterprise Grade
