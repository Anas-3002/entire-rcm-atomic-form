# Entire RCM — Atomic Form Widget

A native **Elementor v4 (atomic)** form element, so a form stops being a v3 island in an otherwise-v4 page.

## The problem it solves

Elementor **free has no v4 form element**. `e-form` and `e-form-input` are Pro, and the official Elementor MCP only writes atomic elements — so on a free install there is no supported way to put a working form inside a v4 page.

This plugin registers `e-rcm-form` as a real atomic widget. It appears under **v4 elements** in the panel, lives in the atomic tree, takes the same classes and variables as everything around it, and is addressable by the MCP.

## Usage

Add the **Form** element, then either:

- set **Contact Form 7 ID** (e.g. `13`), or
- paste **any shortcode** in the second field — which also makes this a general escape hatch for maps, embeds, and anything else v4 has no native element for.

## How it works

`Atomic_Widget_Base` plus a PHP `render()`, deliberately **not** the `Has_Template` trait.

The trait builds a fixed Twig context, so a template has no way to call `do_shortcode()`. Implementing `render()` in PHP keeps the shortcode pipeline while still emitting the exact wrapper the atomic system expects — classes, generated base class, `attributes`, `_cssid`, and `data-interaction-id` — so styling, interactions and the editor all keep working.

## Requirements

- Elementor **4.3+** with the `e_atomic_elements` experiment active
- Contact Form 7 (or any shortcode plugin)

If the atomic experiment is off, the widget stays inactive and the admin shows a notice saying so, rather than silently vanishing.

## Install

Upload the folder to `wp-content/plugins/` and activate, or deploy with Hostinger git-deploy:

```
directory: wp-content/plugins/entire-rcm-atomic-form
```

## Notes

- `get_element_type()` returns `e-rcm-form`. **Do not change it** once pages are built — it is the type stored in `_elementor_data`.
- Props: `form_id`, `shortcode`, `classes`, `attributes`.
