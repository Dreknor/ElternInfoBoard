# Design System & Stilrichtlinien - Elterninfo Modernisierung

> **Erstellt:** 23. Oktober 2025  
> **Status:** In Bearbeitung  
> **Ziel:** Einheitliches, modernes Design basierend auf Tailwind CSS

---

## 📋 Inhaltsverzeichnis

1. [Übersicht](#übersicht)
2. [Farbschema](#farbschema)
3. [Typografie](#typografie)
4. [Spacing & Layout](#spacing--layout)
5. [Komponenten-Patterns](#komponenten-patterns)
6. [Navigation](#navigation)
7. [Buttons & Aktionen](#buttons--aktionen)
8. [Cards & Container](#cards--container)
9. [Formulare](#formulare)
10. [Badges & Notifications](#badges--notifications)
11. [Responsive Design](#responsive-design)
12. [Animationen & Transitions](#animationen--transitions)
13. [Z-Index Hierarchie](#z-index-hierarchie)
14. [Best Practices](#best-practices)

---

## Übersicht

### Technologie-Stack
- **CSS Framework:** Tailwind CSS 3.x
- **JavaScript:** Alpine.js 3.x (für Dropdowns und Interaktivität)
- **Icons:** Font Awesome 6.x
- **Build:** Vite

### Design-Philosophie
- **Modern & Clean:** Klare Linien, ausreichend Whitespace
- **Mobile First:** Responsive Design von klein nach groß
- **Accessibility:** WCAG 2.1 AA Standard
- **Konsistenz:** Einheitliche Patterns über die gesamte Anwendung
- **Performance:** Optimierte Animationen und Transitions

---

## Farbschema

### Primärfarben
```css
/* Blau - Hauptfarbe für Aktionen und Links */
Primary Blue:     #3b82f6  (blue-600)
Primary Blue Dark: #2563eb (blue-700)
Primary Blue Light: #60a5fa (blue-500)

/* Indigo - Akzentfarbe für Highlights */
Accent Indigo:    #6366f1  (indigo-600)
Accent Purple:    #9333ea  (purple-600)
```

### Sekundärfarben
```css
/* Grau - Für Text und Hintergründe */
Gray 50:  #f9fafb  (Hellste Hintergründe)
Gray 100: #f3f4f6  (Cards, Container)
Gray 200: #e5e7eb  (Borders)
Gray 300: #d1d5db  (Disabled States)
Gray 400: #9ca3af  (Placeholder Text)
Gray 500: #6b7280  (Secondary Text)
Gray 600: #4b5563  (Body Text)
Gray 700: #374151  (Headings)
Gray 800: #1f2937  (Dark Elements)
Gray 900: #111827  (Sidebar, Dark Backgrounds)
Gray 950: #030712  (Ultra Dark)
```

### Funktionale Farben
```css
/* Success */
Success:      #10b981  (green-500)
Success Dark: #059669  (green-600)

/* Warning */
Warning:      #f59e0b  (amber-500)
Warning Dark: #d97706  (amber-600)

/* Error/Danger */
Error:        #ef4444  (red-500)
Error Dark:   #dc2626  (red-600)

/* Info */
Info:         #06b6d4  (cyan-500)
Info Dark:    #0891b2  (cyan-600)

/* Teal - Für CheckIn-Status */
Teal:         #14b8a6  (teal-500)
Teal Dark:    #0d9488  (teal-600)
```

### Gradients
```css
/* Primär-Gradient */
bg-gradient-to-r from-blue-600 to-indigo-600

/* Sidebar-Gradient */
bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900

/* Success-Gradient */
bg-gradient-to-r from-teal-500 to-teal-600

/* Warning-Gradient */
bg-gradient-to-r from-orange-500 to-amber-600

/* Admin-Gradient */
bg-gradient-to-r from-purple-500 to-purple-600
```

---

## Typografie

### Schriftfamilie
```css
font-family: 'Montserrat', system-ui, -apple-system, sans-serif;
```

### Schriftgrößen
```css
/* Display */
text-3xl: 1.875rem (30px) - Hauptüberschriften
text-2xl: 1.5rem (24px)   - Seitenüberschriften
text-xl:  1.25rem (20px)   - Abschnittsüberschriften

/* Body */
text-lg:   1.125rem (18px) - Große Texte
text-base: 1rem (16px)     - Standard Body Text
text-sm:   0.875rem (14px) - Kleinere Texte
text-xs:   0.75rem (12px)  - Hinweise, Labels

/* Mobile Navigation */
text-[10px]: 10px - Mobile Bottom Nav Labels
```

### Font Weights
```css
font-light:    300 - Sehr leichte Texte
font-normal:   400 - Standard Text
font-medium:   500 - Leicht hervorgehoben
font-semibold: 600 - Navigation, Buttons
font-bold:     700 - Überschriften, wichtige Elemente
font-extrabold: 800 - Sehr wichtige Überschriften
```

### Zeilenhöhe
```css
leading-tight:   1.25  - Kompakte Überschriften
leading-normal:  1.5   - Standard Body Text
leading-relaxed: 1.625 - Lesbarer Text
```

---

## Spacing & Layout

### Padding-System
```css
/* Kompakt (Navigation, Buttons) */
px-3 py-2: Horizontal 12px, Vertikal 8px

/* Standard (Cards) */
px-4 py-3: Horizontal 16px, Vertikal 12px

/* Großzügig (Sections) */
px-6 py-4: Horizontal 24px, Vertikal 16px

/* Extra (Desktop Header/Footer) */
px-8 py-6: Horizontal 32px, Vertikal 24px
```

### Margin & Gap
```css
/* Sehr eng */
gap-1: 4px  - Innerhalb von Inline-Elementen
gap-2: 8px  - Navigation Items, Icons

/* Standard */
gap-3: 12px - Cards, Container
gap-4: 16px - Sections

/* Großzügig */
gap-6: 24px - Main Layout Sections
```

### Border Radius
```css
rounded:     4px  - Kleine Elemente
rounded-md:  6px  - Buttons
rounded-lg:  8px  - Cards, Inputs
rounded-xl:  12px - Large Cards
rounded-full: 9999px - Pills, Avatars, Badges
```

### Shadows
```css
/* Subtil */
shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05)

/* Standard */
shadow: 0 1px 3px rgba(0, 0, 0, 0.1)
shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1)

/* Prominent */
shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1)
shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.1)
shadow-2xl: 0 25px 50px rgba(0, 0, 0, 0.25)
```

---

## Komponenten-Patterns

### Header / Navbar

**Desktop-Header:**
```html
<nav class="bg-white shadow-lg border-b border-gray-200 fixed-top" style="z-index: 1030;">
    <div class="container-fluid">
        <div class="flex items-center justify-between px-4 py-3">
            <!-- Left: Logo + Toggle -->
            <div class="flex items-center gap-3">
                <button class="navbar-toggler">...</button>
                <a href="/" class="flex items-center gap-3">
                    <img src="..." class="h-10 w-auto" alt="...">
                    <span class="hidden md:inline text-lg font-bold text-gray-800">...</span>
                </a>
            </div>
            
            <!-- Center: Search -->
            <div class="hidden md:flex flex-1 max-w-xl mx-4">
                <form class="w-full">...</form>
            </div>
            
            <!-- Right: Notifications + User -->
            <div class="flex items-center gap-2 md:gap-4">
                <!-- Notifications -->
                <!-- User Dropdown -->
            </div>
        </div>
    </div>
</nav>
```

**Eigenschaften:**
- Feste Höhe: 70px (py-3 mit Inhalt)
- Z-Index: 1030
- Weißer Hintergrund mit Schatten
- Responsive: Suchleiste ausblendbar, mobile Buttons

---

### Sidebar Navigation

**Struktur:**
```html
<div class="sidebar bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900" 
     style="z-index: 1010;">
    <div class="sidebar-wrapper" style="margin-top: 70px;">
        <ul class="nav flex-column px-2 py-3 space-y-1">
            <li class="nav-item">
                <a href="..." 
                   class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg 
                          text-gray-300 hover:bg-blue-600 hover:text-white 
                          transition-all duration-200 group">
                    <i class="fas fa-icon text-base group-hover:scale-110"></i>
                    <span class="font-medium">Label</span>
                </a>
            </li>
        </ul>
    </div>
    
    <!-- User Footer -->
    <div class="absolute bottom-0 left-0 right-0 px-3 py-2 bg-gray-950">
        ...
    </div>
</div>
```

**Eigenschaften:**
- Breite: 260px
- Dunkler Gradient-Hintergrund
- Padding: px-3 py-2 für Items
- Aktiver Status: bg-blue-600 text-white shadow-lg
- Admin-Items: bg-purple-600 (mit Crown-Icon)
- Hover-Effekt: Scale-Animation auf Icons (scale-110)

**Navigation-Item-Klassen:**
```html
<!-- Standard Item -->
class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg 
       text-gray-300 hover:bg-blue-600 hover:text-white 
       transition-all duration-200 group"

<!-- Aktives Item -->
class="... bg-blue-600 text-white shadow-lg"

<!-- Admin Item -->
class="... hover:bg-purple-600"
class="... bg-purple-600 text-white shadow-lg" (aktiv)
```

---

### Mobile Bottom Navigation

**Struktur:**
```html
<nav class="mobile-bottom-nav fixed bottom-0 left-0 right-0 bg-white 
            border-t-2 border-gray-200 shadow-2xl" 
     style="z-index: 1040; backdrop-filter: blur(10px); 
            background: rgba(255, 255, 255, 0.95);">
    <div class="flex items-center justify-around h-16 px-2 safe-area-inset-bottom">
        <div class="mobile-bottom-nav_item flex-1">
            <a href="..." 
               class="flex flex-col items-center justify-center gap-0.5 py-2 
                      text-gray-600 hover:text-blue-600 group">
                <div class="relative">
                    <i class="fas fa-icon text-2xl group-hover:scale-110"></i>
                    <!-- Badge -->
                    <span class="absolute -top-1 -right-2 ... animate-pulse">5</span>
                </div>
                <span class="text-[10px] font-semibold">Label</span>
            </a>
        </div>
    </div>
</nav>
```

**Eigenschaften:**
- Höhe: 64px (h-16)
- Glassmorphism: backdrop-filter blur
- Icon-Größe: text-2xl (24px)
- Text-Größe: text-[10px]
- Safe-Area Support für Notch
- Aktiver Status: Blauer Balken oben (::before pseudo-element)

---

### Dropdown-Menüs (Alpine.js)

**Standard-Pattern:**
```html
<div x-data="{ open: false }" class="relative">
    <!-- Trigger Button -->
    <button @click="open = !open" @click.away="open = false"
            class="...">
        Trigger
    </button>
    
    <!-- Dropdown Menu -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute left-0 md:left-auto md:right-0 mt-2 
                w-48 bg-white rounded-lg shadow-xl border border-gray-200 
                py-2 z-50"
         style="display: none;">
        <!-- Menu Items -->
        <a href="..." class="flex items-center gap-3 px-4 py-2 text-sm 
                             text-gray-700 hover:bg-gray-100">
            <i class="fas fa-icon text-blue-600"></i>
            <span>Item</span>
        </a>
    </div>
</div>
```

**Eigenschaften:**
- Positionierung: `left-0 md:right-0` (mobile links, desktop rechts)
- Z-Index: 50 oder höher
- Animationen: Scale + Opacity Transitions
- Hover: bg-gray-100
- Icons: Farbige Akzente (text-blue-600)

---

### Cards & Container

**Standard Card:**
```html
<div class="bg-white rounded-lg shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3 
                border-b border-blue-800">
        <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
            <i class="fas fa-icon"></i>
            Titel
        </h5>
    </div>
    
    <!-- Body -->
    <div class="p-4">
        Inhalt
    </div>
    
    <!-- Footer (optional) -->
    <div class="bg-gray-50 border-t border-gray-200 px-4 py-3">
        Footer
    </div>
</div>
```

**Varianten:**
- **Success Card:** `from-green-600 to-green-700`
- **Teal Card (CheckIn):** `from-teal-500 to-teal-600`
- **Warning Card:** `from-orange-500 to-amber-600`
- **Info Card:** `from-cyan-600 to-cyan-700`

**Kompakte Card:**
```html
<div class="border border-gray-200 rounded-lg p-3 hover:border-blue-500 
            hover:shadow-md transition-all duration-200">
    ...
</div>
```

---

### Buttons & Aktionen

**Primärer Button:**
```html
<button class="inline-flex items-center gap-2 px-4 py-2 
               bg-blue-600 hover:bg-blue-700 text-white font-medium 
               rounded-lg transition-colors duration-200">
    <i class="fas fa-icon"></i>
    Button Text
</button>
```

**Sekundärer Button:**
```html
<button class="inline-flex items-center gap-2 px-4 py-2 
               bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium 
               rounded-lg transition-colors duration-200">
    Button Text
</button>
```

**Outline Button:**
```html
<button class="inline-flex items-center gap-2 px-4 py-2 
               border-2 border-blue-600 text-blue-600 
               hover:bg-blue-600 hover:text-white font-medium 
               rounded-lg transition-all duration-200">
    Button Text
</button>
```

**Icon Button:**
```html
<button class="inline-flex items-center justify-center p-2 
               rounded-lg text-gray-600 hover:text-blue-600 
               hover:bg-blue-50 transition-all duration-200">
    <i class="fas fa-icon"></i>
</button>
```

**Button-Größen:**
- **Small:** `px-3 py-1.5 text-sm`
- **Normal:** `px-4 py-2 text-base`
- **Large:** `px-6 py-3 text-lg`

---

### Formulare

**Input-Feld:**
```html
<input type="text" 
       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg 
              focus:border-blue-500 focus:ring-2 focus:ring-blue-200 
              transition-all duration-200 outline-none"
       placeholder="Eingabe...">
```

**Textarea:**
```html
<textarea class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg 
                 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 
                 transition-all duration-200 outline-none resize-none"
          rows="4"></textarea>
```

**Select:**
```html
<select class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg 
               focus:border-blue-500 focus:ring-2 focus:ring-blue-200 
               transition-all duration-200 outline-none">
    <option>Option 1</option>
</select>
```

**Checkbox/Radio:**
```html
<label class="flex items-center gap-2 cursor-pointer">
    <input type="checkbox" 
           class="w-4 h-4 text-blue-600 rounded 
                  focus:ring-blue-500 focus:ring-2 cursor-pointer">
    <span class="text-gray-700">Label</span>
</label>
```

**Form Group:**
```html
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-2">
        Label
    </label>
    <input type="text" class="...">
    <p class="mt-1 text-xs text-gray-500">Hinweistext</p>
</div>
```

---

### Badges & Notifications

**Badge (Notification Count):**
```html
<!-- Standard Badge -->
<span class="inline-flex items-center justify-center px-2 py-1 
             text-xs font-bold text-white bg-red-500 rounded-full">
    5
</span>

<!-- Pulse Badge -->
<span class="inline-flex items-center justify-center px-2 py-1 
             text-xs font-bold text-white bg-red-500 rounded-full 
             animate-pulse">
    5
</span>

<!-- Minimal Badge (nur Punkt) -->
<span class="inline-block w-2 h-2 bg-blue-500 rounded-full"></span>
```

**Status Badge:**
```html
<!-- Success -->
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 
             bg-green-100 text-green-700 text-xs font-medium rounded-full">
    <i class="fas fa-check"></i>
    Aktiv
</span>

<!-- Warning -->
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 
             bg-amber-100 text-amber-700 text-xs font-medium rounded-full">
    <i class="fas fa-exclamation"></i>
    Warnung
</span>

<!-- Error -->
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 
             bg-red-100 text-red-700 text-xs font-medium rounded-full">
    <i class="fas fa-times"></i>
    Fehler
</span>
```

**Alert-Box:**
```html
<!-- Info -->
<div class="flex items-start gap-3 p-3 bg-blue-50 border-l-4 
            border-blue-500 rounded">
    <i class="fas fa-info-circle text-blue-600 mt-1"></i>
    <p class="text-blue-800 text-sm mb-0">Infotext</p>
</div>

<!-- Success -->
<div class="flex items-start gap-3 p-3 bg-green-50 border-l-4 
            border-green-500 rounded">
    <i class="fas fa-check-circle text-green-600 mt-1"></i>
    <p class="text-green-800 text-sm mb-0">Erfolgstext</p>
</div>

<!-- Warning -->
<div class="flex items-start gap-3 p-3 bg-amber-50 border-l-4 
            border-amber-500 rounded">
    <i class="fas fa-exclamation-triangle text-amber-600 mt-1"></i>
    <p class="text-amber-800 text-sm mb-0">Warnungstext</p>
</div>

<!-- Error -->
<div class="flex items-start gap-3 p-3 bg-red-50 border-l-4 
            border-red-500 rounded">
    <i class="fas fa-times-circle text-red-600 mt-1"></i>
    <p class="text-red-800 text-sm mb-0">Fehlertext</p>
</div>
```

---

### Listen & Tables

**Liste mit Hover:**
```html
<ul class="space-y-2">
    <li class="p-3 border border-gray-200 rounded-lg 
               hover:border-blue-500 hover:shadow-md 
               transition-all duration-200">
        Item
    </li>
</ul>
```

**Liste mit Divider:**
```html
<ul class="divide-y divide-gray-200">
    <li class="py-3">Item 1</li>
    <li class="py-3">Item 2</li>
    <li class="py-3">Item 3</li>
</ul>
```

**Kompakte Liste (Sidebar-Style):**
```html
<ul class="space-y-1">
    <li>
        <a href="..." class="flex items-center gap-2 px-3 py-2 rounded-lg 
                             text-gray-700 hover:bg-gray-100">
            <i class="fas fa-icon"></i>
            <span>Item</span>
        </a>
    </li>
</ul>
```

---

## Responsive Design

### Breakpoints (Tailwind Standard)
```css
sm:  640px  - Small tablets
md:  768px  - Tablets
lg:  992px  - Desktop
xl:  1280px - Large Desktop
2xl: 1536px - Extra Large Desktop
```

### Mobile-First Approach
```html
<!-- Standard: Mobile -->
<div class="text-sm">

<!-- Ab Medium: Tablet/Desktop -->
<div class="text-sm md:text-base">

<!-- Verstecken auf Mobile -->
<div class="hidden md:block">

<!-- Nur auf Mobile zeigen -->
<div class="block md:hidden">
```

### Layout-Pattern
```html
<!-- Responsive Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    ...
</div>

<!-- Responsive Flex -->
<div class="flex flex-col md:flex-row gap-4">
    ...
</div>

<!-- Responsive Padding -->
<div class="px-4 md:px-6 lg:px-8 py-3 md:py-4">
    ...
</div>
```

### Wichtige Responsive-Regeln

**Navigation:**
- Mobile: Sidebar ausgeblendet, Bottom-Nav sichtbar
- Desktop (≥992px): Sidebar fixiert, Bottom-Nav ausgeblendet

**Header:**
- Mobile: Logo + Toggle + Icons
- Desktop: Logo + Suchleiste + Icons + User-Dropdown

**Content:**
- Mobile: Volle Breite, kein Sidebar-Margin
- Desktop: Margin-left 260px für Sidebar

**Typography:**
- Mobile: Kleinere Schriftgrößen
- Desktop: Standard-Schriftgrößen

---

## Animationen & Transitions

### Standard Transitions
```css
transition-all duration-200      /* Allgemein */
transition-colors duration-200   /* Farben */
transition-transform duration-200 /* Transformationen */
transition-opacity duration-200  /* Transparenz */
```

### Hover-Effekte

**Scale-Effekt (Icons):**
```html
<i class="... group-hover:scale-110 transition-transform"></i>
```

**Lift-Effekt (Cards):**
```html
<div class="... hover:shadow-lg hover:-translate-y-1 transition-all">
```

**Farb-Übergang:**
```html
<div class="bg-blue-600 hover:bg-blue-700 transition-colors">
```

### Alpine.js Transitions
```html
<!-- Fade + Scale -->
x-transition:enter="transition ease-out duration-200"
x-transition:enter-start="transform opacity-0 scale-95"
x-transition:enter-end="transform opacity-100 scale-100"
x-transition:leave="transition ease-in duration-75"
x-transition:leave-start="transform opacity-100 scale-100"
x-transition:leave-end="transform opacity-0 scale-95"

<!-- Slide Down -->
x-transition:enter="transition ease-out duration-200"
x-transition:enter-start="transform opacity-0 -translate-y-2"
x-transition:enter-end="transform opacity-100 translate-y-0"

<!-- Fade Only -->
x-transition:enter="transition ease-out duration-200"
x-transition:enter-start="opacity-0"
x-transition:enter-end="opacity-100"
```

### Animationen
```css
/* Pulse (Badges) */
animate-pulse

/* Spin (Loading) */
animate-spin

/* Bounce */
animate-bounce
```

### Custom Animations (CSS)
```css
/* Slide Down */
@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-4px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Fade In */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
```

---

## Z-Index Hierarchie

**Wichtig für Überlappungen:**

```css
/* Layer 1: Sidebar Overlay (Mobile) */
.sidebar-overlay: 1005

/* Layer 2: Sidebar */
.sidebar: 1010

/* Layer 3: Header/Navbar */
.fixed-top: 1030

/* Layer 4: Mobile Bottom Nav */
.mobile-bottom-nav: 1040

/* Layer 5: Dropdowns & Modals */
Dropdown-Menüs: 1050
Modals: 1060
Toasts/Notifications: 1070
```

**Regel:** Jedes Element 10er-Schritte, um Zwischenwerte einzufügen

---

## Best Practices

### Do's ✅

1. **Konsistente Abstände verwenden**
   - px-3 py-2 für kompakte Elemente
   - px-4 py-3 für Standard-Elemente
   - gap-2 bis gap-4 für Flex/Grid

2. **Responsive Classes nutzen**
   ```html
   <div class="text-sm md:text-base lg:text-lg">
   ```

3. **Group-Hover für verschachtelte Hover-Effekte**
   ```html
   <a class="group">
     <i class="group-hover:scale-110"></i>
   </a>
   ```

4. **Transitions für alle interaktiven Elemente**
   ```html
   <button class="... transition-colors duration-200">
   ```

5. **Alpine.js für Dropdowns**
   ```html
   <div x-data="{ open: false }">
   ```

6. **Accessibility beachten**
   - Alt-Tags für Bilder
   - ARIA-Labels für Icons
   - Keyboard-Navigation
   - Kontrastverhältnisse

7. **Mobile First denken**
   - Basis-Styles für Mobile
   - Erweiterungen für Desktop (md:, lg:)

8. **Icons mit Text kombinieren**
   ```html
   <button class="flex items-center gap-2">
     <i class="fas fa-icon"></i>
     <span>Text</span>
   </button>
   ```

### Don'ts ❌

1. **Keine festen Pixel-Werte inline**
   ❌ `style="width: 200px"`
   ✅ `class="w-48"` oder `class="w-full max-w-sm"`

2. **Keine inkonsis­tenten Farben**
   ❌ `#1E40AF` (custom hex)
   ✅ `blue-700` (Tailwind)

3. **Keine übermäßigen Animationen**
   ❌ Multiple Animationen gleichzeitig
   ✅ Subtile, gezielte Animationen

4. **Keine harten Übergänge**
   ❌ Kein `transition`
   ✅ `transition-colors duration-200`

5. **Keine veralteten Bootstrap-Klassen mischen**
   ❌ `class="btn btn-primary"`
   ✅ `class="px-4 py-2 bg-blue-600..."`

6. **Keine Z-Index Battles**
   ❌ `z-[9999]`
   ✅ Hierarchie von 1010-1070 nutzen

---

## Checkliste für neue Komponenten

Beim Erstellen neuer Komponenten beachten:

- [ ] **Mobile First:** Basis-Styles für kleinste Bildschirme
- [ ] **Responsive:** Breakpoints für md, lg definiert
- [ ] **Farben:** Aus dem Farbschema verwenden
- [ ] **Spacing:** Konsistente px/py/gap Werte
- [ ] **Typography:** Passende text-* und font-* Klassen
- [ ] **Hover-Effekte:** Transitions definiert
- [ ] **Icons:** Font Awesome mit passender Größe
- [ ] **Accessibility:** Alt-Tags, ARIA-Labels
- [ ] **Z-Index:** In Hierarchie einordnen
- [ ] **Dark Mode:** Falls Sidebar/Footer - dunkle Farben
- [ ] **Alpine.js:** Für interaktive Elemente korrekt eingesetzt
- [ ] **Konsistenz:** Mit bestehenden Patterns abgleichen

---

## Code-Snippets für häufige Aufgaben

### Neues Dropdown-Menü erstellen
```html
<div x-data="{ open: false }" class="relative">
    <button @click="open = !open" @click.away="open = false"
            class="inline-flex items-center gap-2 px-3 py-2 
                   bg-gray-100 hover:bg-gray-200 rounded-lg">
        <i class="fas fa-ellipsis-v"></i>
    </button>
    
    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute left-0 md:left-auto md:right-0 mt-2 
                w-48 bg-white rounded-lg shadow-xl border border-gray-200 
                py-1 z-50"
         style="display: none;">
        
        <a href="#" class="flex items-center gap-3 px-4 py-2 text-sm 
                           text-gray-700 hover:bg-gray-100">
            <i class="fas fa-icon text-blue-600"></i>
            <span>Aktion</span>
        </a>
    </div>
</div>
```

### Neue Card mit Gradient-Header
```html
<div class="bg-white rounded-lg shadow-lg overflow-hidden">
    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 
                px-4 py-3 border-b border-blue-800">
        <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
            <i class="fas fa-icon"></i>
            Titel
        </h5>
    </div>
    <div class="p-4">
        Inhalt
    </div>
</div>
```

### Neues Nav-Item (Sidebar)
```html
<li class="nav-item">
    <a href="/link" 
       class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg 
              text-gray-300 hover:bg-blue-600 hover:text-white 
              transition-all duration-200 
              @if(request()->path() == 'link') bg-blue-600 text-white shadow-lg @endif
              group">
        <i class="fas fa-icon text-base group-hover:scale-110 transition-transform"></i>
        <span class="font-medium">Label</span>
    </a>
</li>
```

### Neues Mobile Bottom-Nav Item
```html
<div class="mobile-bottom-nav_item flex-1 
            @if(request()->path() == 'link') mobile-bottom-nav_item--active @endif">
    <div class="mobile-bottom-nav_item-content">
        <a href="/link" 
           class="flex flex-col items-center justify-center gap-0.5 py-2 
                  text-gray-600 hover:text-blue-600 group
                  @if(request()->path() == 'link') text-blue-600 @endif">
            <div class="relative">
                <i class="fas fa-icon text-2xl group-hover:scale-110"></i>
            </div>
            <span class="text-[10px] font-semibold">Label</span>
        </a>
    </div>
</div>
```

### Alert-Box mit Icon
```html
<div class="flex items-start gap-3 p-3 bg-blue-50 border-l-4 
            border-blue-500 rounded">
    <i class="fas fa-info-circle text-blue-600 mt-1"></i>
    <div class="flex-1">
        <p class="text-blue-800 font-semibold text-sm mb-1">Titel</p>
        <p class="text-blue-700 text-sm mb-0">Nachricht</p>
    </div>
</div>
```

---

## Migration bestehender Komponenten

### Schritt-für-Schritt Anleitung

1. **Analyse der bestehenden Komponente**
   - Struktur verstehen
   - Funktionalität identifizieren
   - Bootstrap-Klassen notieren

2. **Tailwind-Äquivalente finden**
   ```
   Bootstrap -> Tailwind
   ----------------------
   .btn -> px-4 py-2 rounded-lg
   .btn-primary -> bg-blue-600 text-white
   .card -> bg-white rounded-lg shadow-lg
   .card-header -> px-4 py-3 border-b
   .badge -> px-2 py-1 rounded-full text-xs
   .alert -> p-4 rounded-lg border-l-4
   ```

3. **Struktur modernisieren**
   - Flex/Grid statt Float
   - Gap statt Margin
   - Responsive Klassen hinzufügen

4. **Interaktivität mit Alpine.js**
   - Dropdowns
   - Modals
   - Tabs
   - Accordions

5. **Testen**
   - Mobile Ansicht
   - Tablet Ansicht
   - Desktop Ansicht
   - Interaktivität
   - Accessibility

---

## Dateien & Pfade

### Wichtige Dateien
```
resources/
  ├── css/
  │   └── app.css                    # Tailwind + Custom CSS
  ├── js/
  │   └── app.js                     # Vite Entry Point
  └── views/
      ├── layouts/
      │   ├── app.blade.php          # Haupt-Layout
      │   └── elements/
      │       └── modules.blade.php  # Modul-Integration
      ├── dashboard/
      │   ├── index.blade.php        # Dashboard
      │   └── components/
      │       └── checkin-status.blade.php
      ├── nachrichten/
      │   ├── nachricht.blade.php    # Nachrichten-Card
      │   ├── header/
      │   │   ├── admin-post.blade.php
      │   │   └── info.blade.php
      │   └── footer/
      │       ├── poll_anonym.blade.php
      │       └── ...
      └── include/
          └── benachrichtigung.blade.php

config/
  └── ...

tailwind.config.js                   # Tailwind Konfiguration
vite.config.js                       # Vite Build Config
```

### CSS-Dateien
- **app.css:** Hauptdatei mit Tailwind Imports + Custom CSS
- Weitere CSS-Dateien vermeiden, alles in app.css

### JavaScript
- **Alpine.js:** Via CDN eingebunden (im Head von app.blade.php)
- **jQuery:** Für Legacy-Code noch vorhanden
- Nach und nach auf Alpine.js/Vanilla JS migrieren

---

## Nächste Schritte

### Priorität 1: Kern-Komponenten
- [ ] Termine modernisieren
- [ ] Krankmeldung-Formular
- [ ] Schickzeiten-Interface
- [ ] Kinder-Übersicht
- [ ] Listen-Ansichten

### Priorität 2: Formulare
- [ ] Login/Register-Seiten
- [ ] Profil-Einstellungen
- [ ] Alle Input-Formulare
- [ ] Validierungs-Styles

### Priorität 3: Tabellen & Listen
- [ ] Benutzer-Verwaltung
- [ ] Gruppen-Verwaltung
- [ ] Alle Admin-Bereiche
- [ ] Datatable-Integration

### Priorität 4: Spezial-Komponenten
- [ ] Kalender-Ansichten
- [ ] File-Upload-Komponenten
- [ ] Bildergalerien
- [ ] Kommentar-Systeme

---

## Support & Ressourcen

### Dokumentation
- [Tailwind CSS Docs](https://tailwindcss.com/docs)
- [Alpine.js Docs](https://alpinejs.dev)
- [Font Awesome Icons](https://fontawesome.com/icons)

### Tools
- **Tailwind IntelliSense:** VS Code Extension
- **Color Picker:** Für Farbkombinationen
- **Responsive Viewer:** Browser Extension

### Community
- Tailwind CSS Discord
- Alpine.js Community
- Laravel Community

---

## Changelog

### Version 1.0 - 23.10.2025
- Initial Design System erstellt
- Header/Navbar modernisiert
- Sidebar-Navigation überarbeitet
- Mobile Bottom-Navigation implementiert
- Dropdown-Menüs mit Alpine.js
- CheckIn-Status Component
- Nachrichten-Cards modernisiert
- Umfragen-Interface überarbeitet
- Benachrichtigungs-System modernisiert

### Geplante Updates
- [ ] Dark Mode Support
- [ ] Erweiterte Animationen
- [ ] Component Library
- [ ] Storybook Integration
- [ ] A11y Improvements

---

## Kontakt & Fragen

Bei Fragen oder Unklarheiten zum Design System:
1. Diese Dokumentation konsultieren
2. Bestehende modernisierte Komponenten als Referenz nutzen
3. Patterns aus `resources/views/dashboard/` und `resources/views/nachrichten/` übernehmen

**Wichtig:** Konsistenz ist wichtiger als Perfektion. Lieber bestehende Patterns übernehmen als neue zu erfinden.

---

*Letzte Aktualisierung: 23. Oktober 2025*

