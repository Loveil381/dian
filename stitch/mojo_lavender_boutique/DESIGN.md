# Design System Document: The Ethereal Apothecary

## 1. Overview & Creative North Star
**Creative North Star: The Modern Alchemist’s Sanctuary**

This design system moves away from the sterile, utilitarian aesthetic of traditional healthcare. Instead, it draws inspiration from the high-end Japanese "Zakka" and cosmetic pharmacy culture (Ainz & Tulpe, @cosme). The goal is to transform "shopping for medicine" into a ritual of self-care.

We break the "standard grid" through **Intentional Softness**. By utilizing organic layering, exaggerated rounded corners, and a total rejection of harsh structural lines, we create a UI that feels "poured" rather than "built." The experience is editorial, intimate, and deeply trustworthy—balancing "Kawaii" charm with sophisticated maturity.

## 2. Color & Surface Philosophy
The palette is a harmonic blend of lavender and pastel tones, designed to reduce user anxiety.

### The "No-Line" Rule
**Strict Mandate:** 1px solid borders are prohibited for sectioning. 
Structure is created through **Tonal Shifts**. For example, a product gallery sits on `surface-container-low`, while the individual product cards use `surface-container-lowest`. This creates a soft, discernible boundary without the "boxed-in" feeling of a corporate site.

### Surface Hierarchy & Nesting
Treat the UI as physical layers of soft-touch paper.
*   **Base:** `background` (#FAF5FF) – The canvas.
*   **Level 1:** `surface-container-low` (#F4EFFA) – Used for large grouping areas or sidebars.
*   **Level 2:** `surface-container-highest` (#DFDBE8) – Used for interactive elements or emphasized content.
*   **Level 3:** `surface-container-lowest` (#FFFFFF) – Used for the primary content cards to make them "pop" against the lavender base.

### The "Glass & Gradient" Rule
To elevate the "Witch's Shop" (魔女小店) theme, use **Glassmorphism** for floating elements like Navigation Bars or Quick-Buy drawers. 
*   **Spec:** `surface` color at 70% opacity + 20px Backdrop Blur.
*   **Signature Gradients:** Use a subtle linear gradient from `primary` (#6A37D4) to `primary-container` (#AE8DFF) on main CTAs to give them a "glowing" magical quality.

## 3. Typography: Editorial Warmth
We utilize a hierarchy that prioritizes readability with a "friendly-premium" tone.

*   **Display & Headlines:** Using **Plus Jakarta Sans** for English numerals/terms and a clean, weighted Simplified Chinese Sans-serif (like *FZLanTingYuan* or *PingFang SC* with rounded terminals).
    *   **Scale:** Large headings (`display-lg` at 3.5rem) should have generous tracking to feel breathable.
*   **Body & Labels:** **Be Vietnam Pro** provides a contemporary, slightly geometric look that remains highly legible at small scales. 
*   **Tonal Identity:** Headers should use `on-surface` (#2F2E35) for authority, while sub-headers use `secondary` (#87456C) to introduce the warm, blush-pink undertones of the brand.

## 4. Elevation & Depth: Tonal Layering
Traditional shadows are too heavy for this "drugstore" aesthetic. We use light to create safety.

*   **The Layering Principle:** Avoid shadows where background shifts suffice. Place a `surface-container-lowest` card on a `surface-container-low` background to create a "Natural Lift."
*   **Ambient Shadows:** For floating Modals or Tooltips, use an ultra-diffused shadow:
    *   `box-shadow: 0 12px 40px rgba(106, 55, 212, 0.08);` (A tinted shadow using the primary purple hue).
*   **The Ghost Border:** If a form field needs a container, use `outline-variant` (#AFABB5) at **15% opacity**. It should be felt, not seen.

## 5. Components & Primitive Styles

### Buttons: The "Pill" Aesthetic
*   **Primary:** Gradient from `primary` to `primary-container`. Corner radius: `full` (9999px). No border.
*   **Secondary:** `secondary-container` (#FFBFE0) background with `on-secondary-container` (#6F3257) text.
*   **Interaction:** On hover, a subtle `surface-tint` glow; on press, a scale-down effect (0.98).

### Input Fields: Soft Receptacles
*   **Style:** `surface-container-highest` background. 
*   **Corners:** `md` (1.5rem).
*   **State:** On focus, the background shifts to `surface-container-lowest` and a 2px `primary-fixed` "Ghost Border" appears.

### Cards: The Product Jewel Box
*   **Strict Rule:** No dividers. Separate content using `body-sm` typography and generous vertical whitespace.
*   **Corner Radius:** `DEFAULT` (1rem) for standard cards; `lg` (2rem) for featured hero cards.
*   **The Witch’s Touch:** Every featured card should include a small, high-quality icon or the signature witch hat emoji ✨ in the top-right corner as a brand watermark.

### Context-Specific Component: The "Apothecary Drawer"
*   **Concept:** Instead of standard dropdowns, use bottom sheets (mobile) or side drawers (desktop) that use the Glassmorphism spec. It should feel like pulling a drawer in a boutique pharmacy.

## 6. Do’s and Don'ts

### Do:
*   **Use Asymmetry:** Place product images slightly off-center or overlapping the card boundary to create an editorial, high-fashion look.
*   **Embrace White Space:** Use the `xl` (3rem) spacing token between sections. The goal is "breathing room."
*   **Layer Tones:** Use `tertiary-container` (Baby Blue) as a background for "Safety Information" or "Pharmacist Tips" to differentiate from "Beauty" content.

### Don’t:
*   **No 90-degree angles:** Even small badges must have at least a `sm` (0.5rem) radius.
*   **No Pure Black:** Never use #000000. Use `on-background` (#2F2E35) for all text to keep the "Soft & Warm" mood.
*   **No Harsh Grids:** Avoid dense rows of 4 items. Prefer 2 or 3 items with larger imagery to maintain the "Intimate" boutique feel.
*   **No Standard Dividers:** If you feel the need to draw a line, increase the padding by 16px instead.