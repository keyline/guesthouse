# How to Use the Design System - Step-by-Step Guide

**Quick Start in 5 minutes** ⚡

---

## Step 1: Import the Theme Variables

Edit your main CSS file: `/resources/css/app.css`

**Add this at the very top:**

```css
@import url('./theme-variables.css');
```

Or in your main blade file: `/resources/views/admin/layouts/app.blade.php`

**Add inside `<head>`:**

```html
<link rel="stylesheet" href="{{ asset('resources/css/theme-variables.css') }}">
```

✅ **Done!** All CSS variables are now available everywhere.

---

## Step 2: Use Color Variables in Your Code

### ❌ Old Way (DON'T DO THIS):
```html
<button style="background-color: #0284c7; color: white; padding: 8px 16px;">
  Click me
</button>
```

### ✅ New Way (USE THIS):
```html
<button class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold 
  text-white hover:bg-sky-700 transition">
  Click me
</button>
```

Or with CSS:
```css
button {
  background-color: var(--primary-600);
  color: white;
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius-md);
  transition: all var(--duration-fast) var(--timing-ease);
}

button:hover {
  background-color: var(--primary-700);
}
```

---

## Step 3: Use Pre-built Components

Go to **COMPONENT_GUIDE.md** and copy the HTML you need.

### Example: Add a Button

**From COMPONENT_GUIDE.md:**
```html
<a href="{{ route('admin.properties.create') }}" 
   class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold 
   text-white hover:bg-sky-700 transition">
  + Add Property
</a>
```

Just paste it in your template! ✨

---

## Step 4: Common Components (Copy-Paste Ready)

### ✔️ Primary Button
```html
<button class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold 
  text-white hover:bg-sky-700 transition">
  Save
</button>
```

### ✔️ Secondary Button
```html
<button class="rounded-lg bg-slate-200 px-4 py-2 text-sm font-bold 
  text-slate-700 hover:bg-slate-300 transition">
  Cancel
</button>
```

### ✔️ Danger Button
```html
<button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-bold 
  text-white hover:bg-red-700 transition">
  Delete
</button>
```

### ✔️ Text Input
```html
<input 
  type="text"
  placeholder="Enter text"
  class="h-9 w-full rounded border border-slate-300 px-2.5 
         text-sm outline-none focus:border-sky-600 focus:ring-1 
         focus:ring-sky-600"
/>
```

### ✔️ Alert Box (Success)
```html
<div class="rounded border border-emerald-300 bg-emerald-50 px-4 py-2 
    text-sm font-semibold text-emerald-700">
  ✓ Settings updated successfully!
</div>
```

### ✔️ Table
```html
<div class="border border-slate-200 bg-white">
  <table class="w-full">
    <thead class="border-b border-slate-200 bg-slate-50">
      <tr class="text-left text-xs font-bold uppercase text-slate-600">
        <th class="px-4 py-2">Name</th>
        <th class="px-4 py-2">Status</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-200">
      <tr class="hover:bg-slate-50">
        <td class="px-4 py-3 font-bold">Item Name</td>
        <td class="px-4 py-3">
          <span class="rounded px-2 py-1 text-xs font-bold 
            bg-emerald-100 text-emerald-800">Active</span>
        </td>
      </tr>
    </tbody>
  </table>
</div>
```

### ✔️ Card
```html
<div class="border border-slate-200 bg-white rounded-lg">
  <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
    <h3 class="text-sm font-bold text-slate-700">Card Title</h3>
  </div>
  <div class="p-4">
    Content goes here
  </div>
</div>
```

### ✔️ Form Field
```html
<div>
  <label class="block text-xs font-bold text-slate-600">
    Label Text
  </label>
  <input 
    type="text"
    class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 
           text-sm outline-none focus:border-sky-600 focus:ring-1 
           focus:ring-sky-600"
  />
</div>
```

---

## Step 5: Change Theme Colors

**Want to change from Sky Blue to Emerald (green)?**

Edit: `/resources/css/theme-variables.css`

Find this section:
```css
:root {
  --primary-50: #f0f9ff;
  --primary-100: #e0f2fe;
  --primary-200: #bae6fd;
  --primary-300: #7dd3fc;
  --primary-400: #38bdf8;
  --primary-500: #0ea5e9;   /* ← Main color */
  --primary-600: #0284c7;   /* ← Hover color */
  --primary-700: #0369a1;   /* ← Active color */
  --primary-800: #075985;
  --primary-900: #0c3d66;
}
```

Replace with Emerald palette:
```css
:root {
  --primary-50: #f0fdf4;
  --primary-100: #dcfce7;
  --primary-200: #bbf7d0;
  --primary-300: #86efac;
  --primary-400: #4ade80;
  --primary-500: #22c55e;   /* Green */
  --primary-600: #16a34a;   /* Dark green */
  --primary-700: #15803d;
  --primary-800: #166534;
  --primary-900: #134e4a;
}
```

✨ **Everything updates automatically!**

---

## Step 6: Color Palettes Available

Just uncomment the palette you want in `theme-variables.css`:

### Sky Blue (Default - Professional)
```css
--primary-500: #0ea5e9;
--primary-600: #0284c7;
--primary-700: #0369a1;
```

### Emerald (Hospitality - Warm & Welcoming)
```css
--primary-500: #22c55e;
--primary-600: #16a34a;
--primary-700: #15803d;
```

### Indigo (Premium - Luxury)
```css
--primary-500: #6366f1;
--primary-600: #4f46e5;
--primary-700: #4338ca;
```

### Purple (Creative - Modern)
```css
--primary-500: #a855f7;
--primary-600: #9333ea;
--primary-700: #7e22ce;
```

### Amber (Welcoming - Warm)
```css
--primary-500: #f59e0b;
--primary-600: #d97706;
--primary-700: #b45309;
```

---

## Step 7: Using CSS Variables in Custom Code

If you write your own CSS, always use variables:

### ✅ CORRECT:
```css
.my-button {
  background-color: var(--primary-600);
  color: white;
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  font-weight: var(--font-bold);
  transition: background-color var(--duration-fast) var(--timing-ease);
}

.my-button:hover {
  background-color: var(--primary-700);
}

.my-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
```

### ❌ WRONG (Don't do this):
```css
.my-button {
  background-color: #0284c7;  /* Hardcoded color */
  color: white;
  padding: 12px 16px;         /* Hardcoded spacing */
  border-radius: 6px;         /* Hardcoded radius */
  font-size: 13px;            /* Hardcoded size */
}
```

---

## Real-World Examples

### Example 1: Update Properties List Button

**File:** `/resources/views/admin/properties/index.blade.php`

```html
@section('header-actions')
    <!-- Use the design system -->
    <a href="{{ route('admin.properties.create') }}" 
       class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold 
       text-white hover:bg-sky-700 transition">
      + Add Property
    </a>
@endsection
```

✅ Now when you change the primary color, this button changes too!

### Example 2: Form with All Components

**File:** `/resources/views/admin/settings/index.blade.php`

```html
<form method="POST" action="{{ route('admin.settings.update') }}">
  @csrf
  
  <!-- Card section -->
  <div class="border border-slate-200 bg-white rounded-lg mb-4">
    <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
      <h3 class="text-sm font-bold text-slate-700">Basic Information</h3>
    </div>
    
    <div class="p-4 space-y-3">
      <!-- Text input -->
      <div>
        <label class="block text-xs font-bold text-slate-600">Site Name</label>
        <input 
          type="text"
          name="site_name"
          placeholder="Enter site name"
          class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 
                 text-sm outline-none focus:border-sky-600 focus:ring-1 
                 focus:ring-sky-600"
        />
      </div>
      
      <!-- Select -->
      <div>
        <label class="block text-xs font-bold text-slate-600">Status</label>
        <select class="mt-1 h-9 w-full rounded border border-slate-300 
                 bg-white px-2.5 text-sm outline-none focus:border-sky-600">
          <option>Active</option>
          <option>Inactive</option>
        </select>
      </div>
    </div>
  </div>
  
  <!-- Button group -->
  <div class="flex justify-end gap-2">
    <button type="reset" 
            class="rounded-lg bg-slate-200 px-4 py-2 text-sm font-bold 
            text-slate-700 hover:bg-slate-300 transition">
      Reset
    </button>
    <button type="submit" 
            class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold 
            text-white hover:bg-sky-700 transition">
      Save Settings
    </button>
  </div>
</form>
```

---

## Variable Reference (Most Common)

### Colors
```css
--primary-600      /* Main button color */
--primary-700      /* Hover state */
--slate-200        /* Light backgrounds, borders */
--slate-600        /* Dark backgrounds, sidebar */
--slate-900        /* Text, headings */
--success-500      /* Green badges */
--warning-500      /* Orange/amber badges */
--danger-500       /* Red/error */
```

### Spacing
```css
--space-2          /* 8px - small gaps */
--space-3          /* 12px - standard padding */
--space-4          /* 16px - medium spacing */
--space-6          /* 24px - large sections */
```

### Typography
```css
--text-xs          /* 12px - labels */
--text-sm          /* 13px - buttons */
--text-base        /* 14px - body */
--font-bold        /* 700 weight */
--font-semibold    /* 600 weight */
```

### Sizing
```css
--button-height    /* 36px */
--input-height     /* 36px */
--header-height    /* 56px */
```

### Borders & Radius
```css
--radius-md        /* 6px - buttons, inputs */
--radius-lg        /* 8px - cards */
```

### Transitions
```css
--duration-fast    /* 150ms - quick feedback */
--duration-base    /* 200ms - standard */
--timing-ease      /* ease-in-out */
```

---

## Quick Checklist

- [ ] Import `theme-variables.css` in your main CSS file
- [ ] Copy components from `COMPONENT_GUIDE.md` when needed
- [ ] Use CSS variables in all custom code (never hardcode colors)
- [ ] Test buttons, forms, tables, cards
- [ ] Change theme by editing primary colors
- [ ] Verify all components update automatically

---

## Troubleshooting

### Problem: Colors not changing
**Solution:** Make sure `theme-variables.css` is imported FIRST in your CSS

### Problem: Hover states not working
**Solution:** Always add `transition` class to interactive elements

### Problem: Need different button sizes
**Solution:** Use compact version:
```html
<!-- Full size (36px) -->
<button class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold">Save</button>

<!-- Compact (28px) -->
<button class="rounded bg-sky-600 px-3 py-1.5 text-xs font-bold">View</button>
```

### Problem: Want to customize a color
**Solution:** Edit `theme-variables.css` and change the `--primary-*` values

---

## Summary

1. **Import** `theme-variables.css`
2. **Copy** components from `COMPONENT_GUIDE.md`
3. **Use** CSS variables (not hardcoded colors)
4. **Change theme** by editing primary colors
5. **Everything updates automatically!**

You're ready to build! 🚀
