# Hero Logo Position Design

## Goal

Move only the opening-scene HM Cargo logo slightly upward so it no longer competes with the headline, without changing its size, animation, copy, or surrounding layout.

## Design

Apply a responsive negative vertical translation to the existing wrapper that contains both the logo image and its orange SVG overlay. Use a smaller offset on compact screens and a slightly larger offset on large screens. Keep the scene layer untouched because GSAP owns its transform during scroll animation.

## Verification

- The header, opening-scene, and footer logos still load.
- The opening-scene logo is higher than its previous centered position.
- The Vite build completes successfully.
- The page has no Vite error overlay.
