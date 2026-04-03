# The Design System: Ethereal Boutique Guidelines

## 1. Overview & Creative North Star: "The Digital Sanctuary"
This design system moves away from the rigid, grid-locked structures of traditional e-commerce to embrace a "Digital Sanctuary" aesthetic. Inspired by the meticulous curation of high-end Japanese boutiques like Ainz & Tulpe, the goal is to create an interface that feels less like a software tool and more like a physical space filled with light and air.

**The Creative North Star: Ethereal Fluidity.**
We achieve this through intentional asymmetry, overlapping elements that mimic physical layering, and an absolute rejection of harsh geometric containment. The layout should breathe; white space is not "empty," it is a structural element used to guide the eye and convey a sense of premium calm.

---

## 2. Colors & Tonal Depth
The palette is a "Pastel Sunset"—a gradient of light that shifts naturally. We avoid the clinical feel of pure greys and blacks, opting instead for tinted neutrals that maintain the "Mojo" warmth.

### The "No-Line" Rule
**Explicit Instruction:** Designers are prohibited from using 1px solid borders to section off content. 
Boundaries must be defined solely through:
- **Background Color Shifts:** Placing a `surface-container-low` section against a `surface` background.
- **Tonal Transitions:** Using soft gradients to define where one area ends and another begins.

### Surface Hierarchy & Nesting
Treat the UI as a series of stacked, semi-translucent sheets. 
- **Base:** `surface` (#FAF5FF) is your canvas.
- **Primary Containers:** Use `surface-container-lowest` (#FFFFFF) for high-priority cards to make them "pop" against the lavender base.
- **Secondary Containers:** Use `surface-container-low` (#F4EFFA) for subtle grouping.
- **Glassmorphism:** For floating navigation or modals, use `surface` with 70% opacity and a `20px` backdrop-blur to allow the "Sunset" colors to bleed through.

### Signature Textures
Avoid flat, "dead" fills for large areas. Use a subtle linear gradient (45-degree angle) from `primary` (#6448B2) to `primary-container` (#AB8FFE) for main CTA buttons and hero backgrounds. This adds a "glow" that mimics light hitting silk.

---

## 3. Typography: The Editorial Voice
Typography is the "scent" of this brand. It must feel light, expensive, and curated.

- **Display & Headlines:** Use `Plus Jakarta Sans`. Headlines should be `headline-lg` (2rem) or `headline-md` (1.75rem) with a weight of 400. Use wide letter-spacing (+0.02em) to enhance the airy feel.
- **Body & Descriptions:** Use `Plus Jakarta Sans` at weight 300. This lightness is crucial. If the text feels too "heavy," it breaks the ethereal illusion.
- **Chinese Typography:** Use a thin, elegant sans-serif (e.g., Noto Sans TC Thin or Light). The stroke weight of the Chinese characters should match the visual "weight" of the English text to ensure bilingual harmony.
- **Hierarchy:** Use the `display-lg` (3.5rem) scale for "Moment" marketing copy—large, poetic statements that define a section, rather than just functional headers.

---

## 4. Elevation & Depth: Atmospheric Layering
In this system, "Elevation" does not mean a drop shadow; it means light and air.

- **Tonal Layering:** Depth is achieved by "stacking" the surface tiers. A `surface-container-highest` card on a `surface` background creates a natural sense of lift without any shadows.
- **Ambient Shadows (Colored Glows):** When a floating effect is required (e.g., a "Buy" button), do not use grey. Use a tinted shadow:
  - **Color:** `primary` at 10% opacity.
  - **Blur:** 40px to 60px.
  - **Spread:** -5px.
  - This creates a soft purple aura, making the element look like it’s floating in a lavender mist.
- **The "Ghost Border" Fallback:** If a border is required for accessibility, use `outline-variant` (#AFABB5) at **10% opacity**. It should be barely perceptible.

---

## 5. Components: Softness Refined

### Buttons: The "Pill" Shape
- **Shape:** Always `ROUND_FULL` (9999px).
- **Primary:** Gradient fill (`primary` to `primary-container`) with a white label. Use a 24px horizontal padding to ensure the pill shape is elongated and elegant.
- **Secondary:** `surface-container-lowest` fill with a `primary` text label. No border.

### Input Fields: Organic Receptacles
- **Shape:** `xl` (3rem / 48px radius). 
- **Background:** `surface-container-highest` (#DFDBE8).
- **Interaction:** On focus, the background transitions to `surface-container-lowest` with a soft purple glow shadow.

### Cards: The Floating Sheet
- **Shape:** `xl` (3rem).
- **Rule:** Never use dividers. Use `body-sm` text as a label or vertical white space (32px+) to separate content blocks within the card.

### Additional Boutique Components
- **The "Scent" Tag:** A specialized chip for product categories. Use `tertiary-container` (#BAE6FD) with `on-tertiary-container` text. These should be `ROUND_FULL` and feature a tiny 8px icon prefix.
- **The Hover Glow:** Any interactive image or card should scale slightly (1.02x) and increase its "Ambient Shadow" intensity when hovered.

---

## 6. Do’s and Don'ts

### Do:
- **Do** allow elements to overlap. A product image should break the "container" of a card to create a 3D organic feel.
- **Do** use asymmetrical margins. Offsetting text blocks slightly to the left or right creates a sophisticated, editorial magazine layout.
- **Do** prioritize the `surface-container` tiers for hierarchy over font weight.

### Don't:
- **Don't** use black (#000000). Use `on-background` (#2F2E35) for the darkest text.
- **Don't** use sharp corners. If it's a button, a card, or an image, the minimum radius is `lg` (2rem).
- **Don't** use lines to separate list items. Use 16px of vertical space and a slight color shift on hover instead.
- **Don't** use high-contrast shadows. If the shadow is easily visible as a "dark shape," it is too heavy. It should feel like a soft glow.